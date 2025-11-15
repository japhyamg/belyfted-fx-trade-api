<?php

namespace App\Http\Controllers\API\v1;

use App\DTOs\TradeDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\ExecuteTradeRequest;
use App\Services\ExecuteTradeService;
use App\Repositories\TradeRepository;
use App\Services\AccountToAccountTradeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TradeController extends Controller
{
    public function __construct(
        private readonly ExecuteTradeService $executeTradeService,
        private readonly TradeRepository $tradeRepository,
        private readonly AccountToAccountTradeService $accountToAccountTradeService,
    ) {}

    public function execute(ExecuteTradeRequest $request): JsonResponse
    {
        try {
            $dto = TradeDTO::fromArray([
                ...$request->validated(),
                'user_id' => $request->user()->id,
            ]);

            $trade = $this->executeTradeService->execute($dto);

            return response()->json([
                'trade_id' => $trade->id,
                'from_account_id' => $trade->from_account_id,
                'to_account_id' => $trade->to_account_id,
                'from_currency' => $trade->from_currency,
                'to_currency' => $trade->to_currency,
                'from_amount' => $trade->from_amount,
                'to_amount' => $trade->to_amount,
                'rate' => $trade->rate,
                'status' => $trade->status,
                'executed_at' => $trade->executed_at,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function accountToAccount(ExecuteTradeRequest $request): JsonResponse
    {
        try {
            $dto = TradeDTO::fromArray([
                ...$request->validated(),
                'user_id' => $request->user()->id,
            ]);

            $trade = $this->accountToAccountTradeService->execute($dto);

            return response()->json([
                'trade_id' => $trade->id,
                'from_account_id' => $trade->from_account_id,
                'to_account_id' => $trade->to_account_id,
                'from_currency' => $trade->from_currency,
                'to_currency' => $trade->to_currency,
                'from_amount' => $trade->from_amount,
                'to_amount' => $trade->to_amount,
                'rate' => $trade->rate,
                'status' => $trade->status,
                'executed_at' => $trade->executed_at,
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    public function index(Request $request): JsonResponse
    {
        $trades = $this->tradeRepository->getUserTrades($request->user()->id);
        return response()->json($trades);
    }

    public function show(Request $request, int $id): JsonResponse
    {
        $trade = $this->tradeRepository->findById($id);

        if (!$trade || $trade->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Trade not found'], 404);
        }

        return response()->json($trade);
    }
}
