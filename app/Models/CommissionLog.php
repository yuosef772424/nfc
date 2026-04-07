<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionLog extends Model
{
    use HasFactory;

    protected $table = 'commission_logs';

    protected $fillable = [
        'reference_type',
        'reference_id',
        'recipient_type',
        'recipient_id',
        'amount',
        'status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'     => 'decimal:2',
            'paid_at'    => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // الثوابت 
    const REF_WITHDRAWAL  = 'withdrawal';
    const REF_TRANSACTION = 'wallet_transaction';

    const RECIPIENT_AGENT    = 'agent';
    const RECIPIENT_MERCHANT = 'merchant';
    const RECIPIENT_COMPANY  = 'company';
    const RECIPIENT_SYSTEM   = 'system';

    const STATUS_PENDING   = 'pending';
    const STATUS_PAID      = 'paid';
    const STATUS_CANCELLED = 'cancelled';

    // العلاقة 
    public function recipient(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recipient_id');
    }
}