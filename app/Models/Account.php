<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    use HasFactory;
    
    protected $fillable = ['user_id','currency','balance','name','status'];
    protected $casts = ['balance' => 'decimal:8'];


    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function tradesFrom(): HasMany
    {
        return $this->hasMany(Trade::class, 'from_account_id');
    }

    public function tradesTo(): HasMany
    {
        return $this->hasMany(Trade::class, 'to_account_id');
    }

    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }
}
