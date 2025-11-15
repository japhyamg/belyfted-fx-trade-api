<?php

namespace App\Services;

use App\Models\MarketRate;

class MarketRateService
{
    public function getCurrentRate(string $fromCurrency, string $toCurrency): float
    {
        $pair = "{$fromCurrency}/{$toCurrency}";

        $marketRate = MarketRate::where('pair', $pair)->first();

        if ($marketRate) {
            return (float) $marketRate->rate;
        }

        $reversePair = "{$toCurrency}/{$fromCurrency}";
        $reverseRate = MarketRate::where('pair', $reversePair)->first();

        if ($reverseRate) {
            return 1 / (float) $reverseRate->rate;
        }

        return $this->simulateRate($fromCurrency, $toCurrency);
    }

    private function simulateRate(string $from, string $to): float
    {
        $baseRates = [
            'GBP/EUR' => 1.145,
            'GBP/USD' => 1.268,
            'EUR/USD' => 1.107,
            'USD/NGN' => 1650.50,
            'GBP/NGN' => 2093.25,
        ];

        $pair = "{$from}/{$to}";
        $baseRate = $baseRates[$pair] ?? 1.0;

        $fluctuation = ($baseRate * 0.005) * (mt_rand(-100, 100) / 100);

        return round($baseRate + $fluctuation, 8);
    }
}
