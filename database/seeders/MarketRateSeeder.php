<?php

namespace Database\Seeders;

use App\Models\MarketRate;
use Illuminate\Database\Seeder;

class MarketRateSeeder extends Seeder
{
    public function run(): void
    {
        $rates = [
            ['pair' => 'GBP/EUR', 'base_currency' => 'GBP', 'quote_currency' => 'EUR', 'rate' => 1.145, 'bid' => 1.144, 'ask' => 1.146],
            ['pair' => 'GBP/USD', 'base_currency' => 'GBP', 'quote_currency' => 'USD', 'rate' => 1.268, 'bid' => 1.267, 'ask' => 1.269],
            ['pair' => 'EUR/USD', 'base_currency' => 'EUR', 'quote_currency' => 'USD', 'rate' => 1.107, 'bid' => 1.106, 'ask' => 1.108],
            ['pair' => 'USD/NGN', 'base_currency' => 'USD', 'quote_currency' => 'NGN', 'rate' => 1650.50, 'bid' => 1649.00, 'ask' => 1652.00],
            ['pair' => 'GBP/NGN', 'base_currency' => 'GBP', 'quote_currency' => 'NGN', 'rate' => 2093.25, 'bid' => 2090.00, 'ask' => 2096.50],
            ['pair' => 'EUR/NGN', 'base_currency' => 'EUR', 'quote_currency' => 'NGN', 'rate' => 1827.30, 'bid' => 1825.00, 'ask' => 1829.60],
        ];

        foreach ($rates as $rate) {
            MarketRate::updateOrCreate(
                ['pair' => $rate['pair']],
                $rate
            );
        }
    }
}
