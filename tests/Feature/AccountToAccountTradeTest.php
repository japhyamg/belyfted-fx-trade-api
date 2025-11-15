<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Account;
use App\Models\MarketRate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountToAccountTradeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        MarketRate::create([
            'pair' => 'GBP/EUR',
            'base_currency' => 'GBP',
            'quote_currency' => 'EUR',
            'rate' => 1.145,
            'bid' => 1.144,
            'ask' => 1.146,
        ]);

        MarketRate::create([
            'pair' => 'USD/NGN',
            'base_currency' => 'USD',
            'quote_currency' => 'NGN',
            'rate' => 1650.50,
            'bid' => 1649.00,
            'ask' => 1652.00,
        ]);
    }

    public function test_successful_account_to_account_trade(): void
    {
        $user = User::factory()->create();

        $fromAccount = Account::factory()->create([
            'user_id' => $user->id,
            'currency' => 'GBP',
            'balance' => 1000.00,
            'status' => 'active',
        ]);

        $toAccount = Account::factory()->create([
            'user_id' => $user->id,
            'currency' => 'EUR',
            'balance' => 500.00,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/trades/account-to-account', [
                'from_account_id' => $fromAccount->id,
                'to_account_id' => $toAccount->id,
                'from_currency' => 'GBP',
                'to_currency' => 'EUR',
                'from_amount' => 100.00,
                'side' => 'SELL',
                'client_order_id' => 'a2a-test-123',
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

        // Verify balances updated
        $fromAccount->refresh();
        $toAccount->refresh();

        $this->assertEquals(900.00, $fromAccount->balance);
        $this->assertGreaterThan(500.00, $toAccount->balance);

        // Verify trade created
        $this->assertDatabaseHas('trades', [
            'user_id' => $user->id,
            'from_account_id' => $fromAccount->id,
            'to_account_id' => $toAccount->id,
            'status' => 'EXECUTED',
        ]);
    }

    public function test_a2a_trade_fails_when_accounts_belong_to_different_users(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $user1Account = Account::factory()->create([
            'user_id' => $user1->id,
            'currency' => 'GBP',
            'balance' => 1000.00,
        ]);

        $user2Account = Account::factory()->create([
            'user_id' => $user2->id,
            'currency' => 'EUR',
            'balance' => 500.00,
        ]);

        $response = $this->actingAs($user1, 'sanctum')
            ->postJson('/api/trades/account-to-account', [
                'from_account_id' => $user1Account->id,
                'to_account_id' => $user2Account->id,
                'from_currency' => 'GBP',
                'to_currency' => 'EUR',
                'from_amount' => 100.00,
                'side' => 'SELL',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['to_account_id']);
    }

    public function test_a2a_trade_requires_destination_account(): void
    {
        $user = User::factory()->create();

        $fromAccount = Account::factory()->create([
            'user_id' => $user->id,
            'currency' => 'GBP',
            'balance' => 1000.00,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/trades/account-to-account', [
                'from_account_id' => $fromAccount->id,
                'from_currency' => 'GBP',
                'to_currency' => 'EUR',
                'from_amount' => 100.00,
                'side' => 'SELL',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['to_account_id']);
    }

    public function test_a2a_trade_fails_with_insufficient_balance(): void
    {
        $user = User::factory()->create();

        $fromAccount = Account::factory()->create([
            'user_id' => $user->id,
            'currency' => 'GBP',
            'balance' => 50.00,
        ]);

        $toAccount = Account::factory()->create([
            'user_id' => $user->id,
            'currency' => 'EUR',
            'balance' => 500.00,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/trades/account-to-account', [
                'from_account_id' => $fromAccount->id,
                'to_account_id' => $toAccount->id,
                'from_currency' => 'GBP',
                'to_currency' => 'EUR',
                'from_amount' => 100.00,
                'side' => 'SELL',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from_amount']);
    }

    public function test_a2a_trade_fails_when_from_account_is_inactive(): void
    {
        $user = User::factory()->create();

        $fromAccount = Account::factory()->create([
            'user_id' => $user->id,
            'currency' => 'GBP',
            'balance' => 1000.00,
            'status' => 'inactive',
        ]);

        $toAccount = Account::factory()->create([
            'user_id' => $user->id,
            'currency' => 'EUR',
            'balance' => 500.00,
            'status' => 'active',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/trades/account-to-account', [
                'from_account_id' => $fromAccount->id,
                'to_account_id' => $toAccount->id,
                'from_currency' => 'GBP',
                'to_currency' => 'EUR',
                'from_amount' => 100.00,
                'side' => 'SELL',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from_account_id']);
    }

    public function test_a2a_trade_fails_when_to_account_is_inactive(): void
    {
        $user = User::factory()->create();

        $fromAccount = Account::factory()->create([
            'user_id' => $user->id,
            'currency' => 'GBP',
            'balance' => 1000.00,
            'status' => 'active',
        ]);

        $toAccount = Account::factory()->create([
            'user_id' => $user->id,
            'currency' => 'EUR',
            'balance' => 500.00,
            'status' => 'suspended',
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/trades/account-to-account', [
                'from_account_id' => $fromAccount->id,
                'to_account_id' => $toAccount->id,
                'from_currency' => 'GBP',
                'to_currency' => 'EUR',
                'from_amount' => 100.00,
                'side' => 'SELL',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['to_account_id']);
    }

    public function test_a2a_trade_fails_when_currency_mismatch(): void
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
            ->postJson('/api/trades/account-to-account', [
                'from_account_id' => $fromAccount->id,
                'to_account_id' => $toAccount->id,
                'from_currency' => 'USD',
                'to_currency' => 'EUR',
                'from_amount' => 100.00,
                'side' => 'SELL',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['from_currency']);
    }

    public function test_a2a_trade_prevents_same_account_transfer(): void
    {
        $user = User::factory()->create();

        $account = Account::factory()->create([
            'user_id' => $user->id,
            'currency' => 'GBP',
            'balance' => 1000.00,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/trades/account-to-account', [
                'from_account_id' => $account->id,
                'to_account_id' => $account->id,
                'from_currency' => 'GBP',
                'to_currency' => 'GBP',
                'from_amount' => 100.00,
                'side' => 'SELL',
            ]);

        $response->assertStatus(422);
    }

    public function test_a2a_trade_is_idempotent(): void
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

        $payload = [
            'from_account_id' => $fromAccount->id,
            'to_account_id' => $toAccount->id,
            'from_currency' => 'GBP',
            'to_currency' => 'EUR',
            'from_amount' => 100.00,
            'side' => 'SELL',
            'client_order_id' => 'idempotent-a2a-456',
        ];

        // First request
        $response1 = $this->actingAs($user, 'sanctum')
            ->postJson('/api/trades/account-to-account', $payload);

        $response1->assertStatus(201);
        $tradeId1 = $response1->json('trade_id');

        // Second request with same client_order_id
        $response2 = $this->actingAs($user, 'sanctum')
            ->postJson('/api/trades/account-to-account', $payload);

        $response2->assertStatus(201);
        $tradeId2 = $response2->json('trade_id');

        // Should return the same trade
        $this->assertEquals($tradeId1, $tradeId2);

        // Balance should only be deducted once
        $fromAccount->refresh();
        $this->assertEquals(900.00, $fromAccount->balance);
    }

    public function test_a2a_trade_creates_audit_log(): void
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
            ->postJson('/api/trades/account-to-account', [
                'from_account_id' => $fromAccount->id,
                'to_account_id' => $toAccount->id,
                'from_currency' => 'GBP',
                'to_currency' => 'EUR',
                'from_amount' => 100.00,
                'side' => 'SELL',
            ]);

        $response->assertStatus(201);

        // Verify audit log created
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'action' => 'account_to_account_trade',
            'entity_type' => 'trade',
        ]);
    }

    public function test_execute_endpoint_also_handles_a2a_trades(): void
    {
        $user = User::factory()->create();

        $fromAccount = Account::factory()->create([
            'user_id' => $user->id,
            'currency' => 'USD',
            'balance' => 1000.00,
        ]);

        $toAccount = Account::factory()->create([
            'user_id' => $user->id,
            'currency' => 'NGN',
            'balance' => 10000.00,
        ]);

        // Using the general execute endpoint with to_account_id
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/trades/execute', [
                'from_account_id' => $fromAccount->id,
                'to_account_id' => $toAccount->id,
                'from_currency' => 'USD',
                'to_currency' => 'NGN',
                'from_amount' => 100.00,
                'side' => 'SELL',
            ]);

        $response->assertStatus(201);

        // Verify both balances updated
        $fromAccount->refresh();
        $toAccount->refresh();

        $this->assertEquals(900.00, $fromAccount->balance);
        $this->assertGreaterThan(10000.00, $toAccount->balance);
    }
}
