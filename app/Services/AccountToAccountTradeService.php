<?php

namespace App\Services;

use App\DTOs\TradeDTO;
use App\Models\Trade;
use App\Repositories\AccountRepository;
use App\Repositories\TradeRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountToAccountTradeService
{
    public function __construct(
        private readonly AccountRepository $accountRepository,
        private readonly TradeRepository $tradeRepository,
        private readonly MarketRateService $marketRateService,
        private readonly AuditLogService $auditLogService,
    ) {}

    public function execute(TradeDTO $dto): Trade
    {
        // Validate that to_account_id is provided for A2A trades
        if (!$dto->toAccountId) {
            throw ValidationException::withMessages([
                'to_account_id' => ['Destination account is required for account-to-account trades.']
            ]);
        }

        // Check for duplicate using client_order_id
        if ($dto->clientOrderId) {
            $existingTrade = $this->tradeRepository->findByClientOrderId($dto->clientOrderId);
            if ($existingTrade) {
                return $existingTrade;
            }
        }

        return DB::transaction(function () use ($dto) {
            // Lock both accounts for update to prevent race conditions
            $fromAccount = $this->accountRepository->lockForUpdate($dto->fromAccountId);
            $toAccount = $this->accountRepository->lockForUpdate($dto->toAccountId);

            // Verify from account exists and ownership
            if (!$fromAccount) {
                throw ValidationException::withMessages([
                    'from_account_id' => ['Source account not found.']
                ]);
            }

            if ($fromAccount->user_id !== $dto->userId) {
                throw ValidationException::withMessages([
                    'from_account_id' => ['You do not own the source account.']
                ]);
            }

            // Verify to account exists and ownership
            if (!$toAccount) {
                throw ValidationException::withMessages([
                    'to_account_id' => ['Destination account not found.']
                ]);
            }

            if ($toAccount->user_id !== $dto->userId) {
                throw ValidationException::withMessages([
                    'to_account_id' => ['You do not own the destination account. Account-to-account trades must be between your own accounts.']
                ]);
            }

            // Verify both accounts are active
            if (!$fromAccount->isActive()) {
                throw ValidationException::withMessages([
                    'from_account_id' => ['Source account is not active.']
                ]);
            }

            if (!$toAccount->isActive()) {
                throw ValidationException::withMessages([
                    'to_account_id' => ['Destination account is not active.']
                ]);
            }

            // Verify currencies match the accounts
            if ($fromAccount->currency !== $dto->fromCurrency) {
                throw ValidationException::withMessages([
                    'from_currency' => ['Currency does not match source account currency.']
                ]);
            }

            if ($toAccount->currency !== $dto->toCurrency) {
                throw ValidationException::withMessages([
                    'to_currency' => ['Currency does not match destination account currency.']
                ]);
            }

            // Check sufficient balance
            if (!$fromAccount->hasSufficientBalance($dto->fromAmount)) {
                throw ValidationException::withMessages([
                    'from_amount' => ['Insufficient balance in source account.']
                ]);
            }

            // Prevent same account trades
            if ($fromAccount->id === $toAccount->id) {
                throw ValidationException::withMessages([
                    'to_account_id' => ['Cannot trade between the same account.']
                ]);
            }

            // Get current market rate
            $rate = $this->marketRateService->getCurrentRate($dto->fromCurrency, $dto->toCurrency);
            $toAmount = $dto->fromAmount * $rate;

            // Create trade record
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

            // Update balances atomically
            $this->accountRepository->updateBalance(
                $fromAccount->id,
                $fromAccount->balance - $dto->fromAmount
            );

            $this->accountRepository->updateBalance(
                $toAccount->id,
                $toAccount->balance + $toAmount
            );

            // Log the transaction
            $this->auditLogService->log(
                userId: $dto->userId,
                action: 'account_to_account_trade',
                entityType: 'trade',
                entityId: $trade->id,
                newValues: [
                    'trade' => $trade->toArray(),
                    'from_account_balance' => $fromAccount->balance - $dto->fromAmount,
                    'to_account_balance' => $toAccount->balance + $toAmount,
                ]
            );

            return $trade->fresh(['fromAccount', 'toAccount']);
        });
    }
}
