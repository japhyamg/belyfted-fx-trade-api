<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Account;
use App\Models\MarketRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TradeExecutionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Seed market rates for testing
        MarketRate::create([
            'pair' => 'GBP/EUR',
            'base_currency' => 'GBP',
            'quote_currency' => 'EUR',
            'rate' => 1.145,
            'bid' => 1.144,
            'ask' => 1.146,
        ]);
    }

    public function test_successful_trade_execution(): void
    {
        $user = User::factory()->create();

        $fromAccount = Account::factory()->create([
            'user_id' => $user->id,
            'currency' => 'GBP',
            'balance' => 1000.00,
        ]);

        $toAccount = Account::factory()->create([
            'user_id' => $user->id,
            'currency' => 'EUR',
            'balance' => 500.00,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/trades/execute', [
                'from_account_id' => $fromAccount->id,
                'to_account_id' => $toAccount->id,
                'from_currency' => 'GBP',
                'to_currency' => 'EUR',
                'from_amount' => 100.00,
                'side' => 'SELL',
                'client_order_id' => 'test-order-123',
            ]);

        $response->assertStatus(201)
            ->assertJsonStructure([
                'trade_id',
                'from_account_id',
                'to_account_id',
                'from_currency',
                'to_currency',
                'from_amount',
                'to_amount',
                'rate',
                'status',
                'executed_at',
            ]);

        $this->assertDatabaseHas('trades', [
            'user_id' => $user->id,
            'from_account_id' => $fromAccount->id,
            'to_account_id' => $toAccount->id,
            'status' => 'EXECUTED',
        ]);

        $fromAccount->refresh();
        $this->assertEquals(900.00, $fromAccount->balance);

        $toAccount->refresh();
        $this->assertGreaterThan(500.00, $toAccount->balance);
    }

    public function test_insufficient_balance_fails(): void
    {
        $user = User::factory()->create();

        $fromAccount = Account::factory()->create([
            'user_id' => $user->id,
            'currency' => 'GBP',
            'balance' => 50.00,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/trades/execute', [
                'from_account_id' => $fromAccount->id,
                'from_currency' => 'GBP',
                'to_currency' => 'EUR',
                'from_amount' => 100.00,
                'side' => 'SELL',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from_amount']);
    }

    public function test_idempotency_prevents_duplicate_trades(): void
    {
        $user = User::factory()->create();

        $fromAccount = Account::factory()->create([
            'user_id' => $user->id,
            'currency' => 'GBP',
            'balance' => 1000.00,
        ]);

        $payload = [
            'from_account_id' => $fromAccount->id,
            'from_currency' => 'GBP',
            'to_currency' => 'EUR',
            'from_amount' => 100.00,
            'side' => 'SELL',
            'client_order_id' => 'unique-order-456',
        ];

        $response1 = $this->actingAs($user, 'sanctum')
            ->postJson('/api/trades/execute', $payload);

        $response1->assertStatus(201);
        $tradeId1 = $response1->json('trade_id');

        $response2 = $this->actingAs($user, 'sanctum')
            ->postJson('/api/trades/execute', $payload);

        $response2->assertStatus(201);
        $tradeId2 = $response2->json('trade_id');

        $this->assertEquals($tradeId1, $tradeId2);

        $fromAccount->refresh();
        $this->assertEquals(900.00, $fromAccount->balance);
    }

    public function test_account_ownership_validation(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $user2Account = Account::factory()->create([
            'user_id' => $user2->id,
            'currency' => 'GBP',
            'balance' => 1000.00,
        ]);

        $response = $this->actingAs($user1, 'sanctum')
            ->postJson('/api/trades/execute', [
                'from_account_id' => $user2Account->id,
                'from_currency' => 'GBP',
                'to_currency' => 'EUR',
                'from_amount' => 100.00,
                'side' => 'SELL',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from_account_id']);
    }

    public function test_validation_errors_for_missing_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/trades/execute', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors([
                'from_account_id',
                'from_currency',
                'to_currency',
                'from_amount',
                'side',
            ]);
    }

    public function test_same_currency_trade_fails(): void
    {
        $user = User::factory()->create();

        $fromAccount = Account::factory()->create([
            'user_id' => $user->id,
            'currency' => 'GBP',
            'balance' => 1000.00,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/trades/execute', [
                'from_account_id' => $fromAccount->id,
                'from_currency' => 'GBP',
                'to_currency' => 'GBP',
                'from_amount' => 100.00,
                'side' => 'SELL',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['to_currency']);
    }
}
