<?php

namespace App\Http\Controllers\API\v1;

use App\Http\Controllers\Controller;
use App\Services\MarketRateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketRateController extends Controller
{
    public function __construct(
        private readonly MarketRateService $marketRateService
    ) {}

    public function getRate(Request $request): JsonResponse
    {
        $request->validate([
            'pair' => 'required|string|regex:/^[A-Z]{3}\\/[A-Z]{3}$/'
        ]);

        $pair = $request->input('pair');
        list($fromCurrency, $toCurrency) = explode('/', $pair);

        $rate = $this->marketRateService->getCurrentRate($fromCurrency, $toCurrency);

        return response()->json([
            'pair' => $pair,
            'from_currency' => $fromCurrency,
            'to_currency' => $toCurrency,
            'rate' => $rate,
            'timestamp' => now()->toIso8601String(),
        ]);
    }
}
