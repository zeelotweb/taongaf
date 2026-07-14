<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token_balance',
        'earnings_balance',
        'total_earned',
        'total_spent',
        'stripe_account_id',
        'payouts_enabled',
        'last_payout_at',
    ];

    protected function casts(): array
    {
        return [
            'token_balance' => 'integer',
            'earnings_balance' => 'integer',
            'total_earned' => 'integer',
            'total_spent' => 'integer',
            'payouts_enabled' => 'boolean',
            'last_payout_at' => 'datetime',
        ];
    }

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function transactions()
    {
        return $this->hasMany(TokenTransaction::class);
    }

    // Helpers
    public function hasEnoughTokens(int $amount): bool
    {
        return $this->token_balance >= $amount;
    }

    public function creditTokens(int $amount): void
    {
        $this->increment('token_balance', $amount);
        $this->increment('total_earned', $amount);
    }

    public function debitTokens(int $amount): void
    {
        $this->decrement('token_balance', $amount);
        $this->increment('total_spent', $amount);
    }

    public function creditEarnings(int $amount): void
    {
        $this->increment('earnings_balance', $amount);
        $this->increment('total_earned', $amount);
    }
}