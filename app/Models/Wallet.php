<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model
{
    use HasFactory;

    protected $table = 'wallets';

    // الثوابت
    const STATUS_ACTIVE = 'active';
    const STATUS_FROZEN = 'frozen';
    const STATUS_CLOSED = 'closed';
    const CURRENCY_DEFAULT = 'YER';

    protected $fillable = [
        'user_id',
        'currency',
        'status',
        'available_balance',
        'pending_balance',
    ];

    protected function casts(): array
    {
        return [
            'available_balance' => 'decimal:2',
            'pending_balance'   => 'decimal:2',
            'created_at'        => 'datetime',
            'updated_at'        => 'datetime',
        ];
    }

    // العلاقات 
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class);
    }

    public function sentTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'sender_wallet_id');
    }

    public function receivedTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'receiver_wallet_id');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }
}