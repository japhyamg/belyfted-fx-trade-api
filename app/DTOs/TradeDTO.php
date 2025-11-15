<?php
namespace App\DTOs;

class TradeDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly int $fromAccountId,
        public readonly ?int $toAccountId,
        public readonly string $fromCurrency,
        public readonly string $toCurrency,
        public readonly float $fromAmount,
        public readonly string $side,
        public readonly ?string $clientOrderId = null,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            fromAccountId: $data['from_account_id'],
            toAccountId: $data['to_account_id'] ?? null,
            fromCurrency: $data['from_currency'],
            toCurrency: $data['to_currency'],
            fromAmount: (float) $data['from_amount'],
            side: $data['side'],
            clientOrderId: $data['client_order_id'] ?? null,
        );
    }
}
