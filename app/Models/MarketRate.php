<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketRate extends Model
{
    protected $fillable = ['pair','rate', 'base_currency', 'quote_currency', 'bid', 'ask'];
    protected $casts = ['rate' => 'decimal:8','fetched_at' => 'datetime'];
}
