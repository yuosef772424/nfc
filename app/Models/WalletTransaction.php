<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WalletTransaction extends Model
{
    use HasFactory;

    protected $table = 'wallet_transactions';

    // الثوابت
    const TYPE_PAYMENT    = 'payment';
    const TYPE_TRANSFER   = 'transfer';
    const TYPE_DEPOSIT    = 'deposit';
    const TYPE_WITHDRAWAL = 'withdrawal';
    const TYPE_REFUND     = 'refund';

    const STATUS_PENDING   = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED    = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'transaction_uuid',
        'sender_wallet_id',
        'receiver_wallet_id',
        'sender_card_id',
        'sender_device_id',
        'receiver_device_id',
        'type',
        'amount',
        'fee',
        'net_amount',
        'currency',
        'status',
        'failure_reason',
        'failure_code',
        'description',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount'     => 'decimal:2',
            'fee'        => 'decimal:2',
            'net_amount' => 'decimal:2',
            'metadata'   => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // العلاقات 
    public function senderWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'sender_wallet_id');
    }

    public function receiverWallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class, 'receiver_wallet_id');
    }

    public function senderCard(): BelongsTo
    {
        return $this->belongsTo(Card::class, 'sender_card_id');
    }

    public function senderDevice(): BelongsTo
    {
        return $this->belongsTo(NfcDevice::class, 'sender_device_id');
    }

    public function receiverDevice(): BelongsTo
    {
        return $this->belongsTo(NfcDevice::class, 'receiver_device_id');
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class, 'transaction_id');
    }
}