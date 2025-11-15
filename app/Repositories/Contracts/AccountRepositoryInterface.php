<?php

namespace App\Repositories\Contracts;

use App\Models\Account;
use Illuminate\Support\Collection;

interface AccountRepositoryInterface
{
    public function findById(int $id): ?Account;
    public function findByUserAndId(int $userId, int $accountId): ?Account;
    public function getUserAccounts(int $userId): Collection;
    public function lockForUpdate(int $id): ?Account;
    public function updateBalance(int $accountId, float $newBalance): bool;
}
