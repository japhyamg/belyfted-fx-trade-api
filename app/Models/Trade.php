<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Trade extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'from_account_id',
        'to_account_id',
        'from_currency',
        'to_currency',
        'from_amount',
        'to_amount',
        'rate',
        'side',
        'status',
        'client_order_id',
        'executed_at',
    ];

    protected $casts = [
        'from_amount' => 'decimal:8',
        'to_amount' => 'decimal:8',
        'rate' => 'decimal:8',
        'executed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'from_account_id');
    }

    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'to_account_id');
    }
}
