<?php
namespace Database\Seeders;

use App\Models\User;
use App\Models\Account;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::create([
            'name' => 'Demo User',
            'email' => 'demo@belyfted.com',
            'password' => Hash::make('password123'),
        ]);

        Account::create([
            'user_id' => $user->id,
            'name' => 'GBP Main Account',
            'currency' => 'GBP',
            'balance' => 10000.00,
            'status' => 'active',
        ]);

        Account::create([
            'user_id' => $user->id,
            'name' => 'EUR Savings Account',
            'currency' => 'EUR',
            'balance' => 5000.00,
            'status' => 'active',
        ]);

        Account::create([
            'user_id' => $user->id,
            'name' => 'USD Trading Account',
            'currency' => 'USD',
            'balance' => 8000.00,
            'status' => 'active',
        ]);

        Account::create([
            'user_id' => $user->id,
            'name' => 'NGN Local Account',
            'currency' => 'NGN',
            'balance' => 1000000.00,
            'status' => 'active',
        ]);
    }
}
