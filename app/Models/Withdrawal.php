<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Withdrawal extends Model
{
    use HasFactory;

    protected $table = 'withdrawals';

    // الثوابت
    const STATUS_PENDING   = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED    = 'failed';
    const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'wallet_id',
        'agent_id',
        'requested_amount',
        'commission_amount',
        'total_amount',
        'commission_type',
        'commission_value',
        'verification_code',
        'expires_at',
        'status',
        'completed_at',
    ];

    protected $hidden = ['verification_code'];

    protected function casts(): array
    {
        return [
            'requested_amount'  => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'total_amount'      => 'decimal:2',
            'commission_value'  => 'decimal:2',
            'expires_at'        => 'datetime',
            'completed_at'      => 'datetime',
            'created_at'        => 'datetime',
            'updated_at'        => 'datetime',
        ];
    }

    // العلاقات فقط
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    public function agent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'agent_id');
    }

    public function commissionLogs(): HasMany
    {
        return $this->hasMany(CommissionLog::class, 'reference_id')
                    ->where('reference_type', 'withdrawal');
    }
}