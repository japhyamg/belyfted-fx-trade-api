<?php

namespace App\Services;

use App\DTOs\TradeDTO;
use App\Models\Trade;
use App\Repositories\AccountRepository;
use App\Repositories\TradeRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExecuteTradeService
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly TradeRepository $tradeRepository,
        private readonly MarketRateService $marketRateService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function execute(TradeDTO $dto): Trade
    {
        if ($dto->clientOrderId) {
            $existingTrade = $this->tradeRepository->findByClientOrderId($dto->clientOrderId);
            if ($existingTrade) {
                return $existingTrade;
            }
        }

        return DB::transaction(function () use ($dto) {
            $fromAccount = $this->accountRepository->lockForUpdate($dto->fromAccountId);

            if (!$fromAccount) {
                throw ValidationException::withMessages([
                    'from_account_id' => ['Account not found.']
                ]);
            }

            if ($fromAccount->user_id !== $dto->userId) {
                throw ValidationException::withMessages([
                    'from_account_id' => ['You do not own this account.']
                ]);
            }

            if (!$fromAccount->isActive()) {
                throw ValidationException::withMessages([
                    'from_account_id' => ['Account is not active.']
                ]);
            }

            if (!$fromAccount->hasSufficientBalance($dto->fromAmount)) {
                throw ValidationException::withMessages([
                    'from_amount' => ['Insufficient balance.']
                ]);
            }

            $toAccount = null;
            if ($dto->toAccountId) {
                $toAccount = $this->accountRepository->lockForUpdate($dto->toAccountId);

                if (!$toAccount || $toAccount->user_id !== $dto->userId) {
                    throw ValidationException::withMessages([
                        'to_account_id' => ['Invalid destination account.']
                    ]);
                }
            }

            $rate = $this->marketRateService->getCurrentRate($dto->fromCurrency, $dto->toCurrency);
            $toAmount = $dto->fromAmount * $rate;

            $trade = $this->tradeRepository->create([
                'user_id' => $dto->userId,
                'from_account_id' => $dto->fromAccountId,
                'to_account_id' => $dto->toAccountId,
                'from_currency' => $dto->fromCurrency,
                'to_currency' => $dto->toCurrency,
                'from_amount' => $dto->fromAmount,
                'to_amount' => $toAmount,
                'rate' => $rate,
                'side' => $dto->side,
                'status' => 'EXECUTED',
                'client_order_id' => $dto->clientOrderId,
                'executed_at' => now(),
            ]);

            $this->accountRepository->updateBalance(
                $fromAccount->id,
                $fromAccount->balance - $dto->fromAmount
            );

            if ($toAccount) {
                $this->accountRepository->updateBalance(
                    $toAccount->id,
                    $toAccount->balance + $toAmount
                );
            }

            $this->auditLogService->log(
                userId: $dto->userId,
                action: 'trade_executed',
                entityType: 'trade',
                entityId: $trade->id,
                newValues: $trade->toArray()
            );

            return $trade->fresh(['fromAccount', 'toAccount']);
        });
    }
}
