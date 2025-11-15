<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Account;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountOwnershipTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_list_own_accounts(): void
    {
        $user = User::factory()->create();

        Account::factory()->count(3)->create(['user_id' => $user->id]);
        Account::factory()->count(2)->create(); // Other user accounts

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/accounts');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'accounts');
    }

    public function test_user_can_view_own_account_details(): void
    {
        $user = User::factory()->create();

        $account = Account::factory()->create([
            'user_id' => $user->id,
            'name' => 'Test Account',
            'currency' => 'GBP',
            'balance' => 1000.00,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/accounts/{$account->id}");

        $response->assertStatus(200)
            ->assertJson([
                'id' => $account->id,
                'name' => 'Test Account',
                'currency' => 'GBP',
            ]);
    }

    public function test_user_cannot_view_other_users_account(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $user2Account = Account::factory()->create(['user_id' => $user2->id]);

        $response = $this->actingAs($user1, 'sanctum')
            ->getJson("/api/accounts/{$user2Account->id}");

        $response->assertStatus(404);
    }

    public function test_account_to_account_trade_requires_same_user(): void
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
            'balance' => 1000.00,
        ]);

        $response = $this->actingAs($user1, 'sanctum')
            ->postJson('/api/trades/execute', [
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

    public function test_unauthenticated_user_cannot_access_accounts(): void
    {
        $response = $this->getJson('/api/accounts');
        $response->assertStatus(401);
    }

    public function test_unauthenticated_user_cannot_execute_trades(): void
    {
        $response = $this->postJson('/api/trades/execute', []);
        $response->assertStatus(401);
    }
}
