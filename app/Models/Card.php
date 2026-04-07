<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Card extends Model
{
    use HasFactory;

    protected $table = 'cards';

    protected $fillable = [
        'wallet_id',
        'agent_id',
        'card_number',
        'pin_hash',
        'nfc_uid',
        'nfc_key_ref',
        'status',
        'expiry_date',
    ];

    protected $hidden = [
        'pin_hash',
        'nfc_uid',
    ];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'created_at'  => 'datetime',
            'updated_at'  => 'datetime',
        ];
    }

    // الثوابت
    const STATUS_ACTIVE  = 'active';
    const STATUS_BLOCKED = 'blocked';
    const STATUS_EXPIRED = 'expired';

    // العلاقات
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'sender_card_id');
    }
}