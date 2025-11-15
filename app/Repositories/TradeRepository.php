<?php
namespace App\Repositories;

use App\Models\Trade;
use Illuminate\Support\Collection;

class TradeRepository
{
    public function create(array $data): Trade
    {
        return Trade::create($data);
    }

    public function findByClientOrderId(string $clientOrderId): ?Trade
    {
        return Trade::where('client_order_id', $clientOrderId)->first();
    }

    public function getUserTrades(int $userId, int $perPage = 20)
    {
        return Trade::where('user_id', $userId)
            ->with(['fromAccount', 'toAccount'])
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    public function findById(int $id): ?Trade
    {
        return Trade::with(['fromAccount', 'toAccount'])->find($id);
    }
}
