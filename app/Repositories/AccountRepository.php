<?php

namespace App\Repositories;

use App\Models\Account;
use App\Repositories\Contracts\AccountRepositoryInterface;
use Illuminate\Support\Collection;

class AccountRepository implements AccountRepositoryInterface
{
    public function findById(int $id): ?Account
    {
        return Account::find($id);
    }

    public function findByUserAndId(int $userId, int $accountId): ?Account
    {
        return Account::where('user_id', $userId)
            ->where('id', $accountId)
            ->first();
    }

    public function getUserAccounts(int $userId): Collection
    {
        return Account::where('user_id', $userId)
            ->orderBy('currency')
            ->get();
    }

    public function lockForUpdate(int $id): ?Account
    {
        return Account::where('id', $id)->lockForUpdate()->first();
    }

    public function updateBalance(int $accountId, float $newBalance): bool
    {
        return Account::where('id', $accountId)
            ->update(['balance' => $newBalance]);
    }
}
