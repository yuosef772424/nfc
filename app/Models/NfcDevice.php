<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NfcDevice extends Model
{
    use HasFactory;

    protected $table = 'nfc_devices';

    // الثوابت
    const TYPE_PHYSICAL = 'physical';
    const TYPE_MOBILE   = 'mobile';

    const STATUS_ACTIVE      = 'active';
    const STATUS_INACTIVE    = 'inactive';
    const STATUS_MAINTENANCE = 'maintenance';

    protected $fillable = [
        'user_id',
        'device_uuid',
        'device_type',
        'status',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata'   => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // العلاقات 
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function physicalDetails(): HasOne
    {
        return $this->hasOne(PhysicalDeviceDetail::class, 'device_id');
    }

    public function mobileDetails(): HasOne
    {
        return $this->hasOne(MobileDeviceDetail::class, 'device_id');
    }

    public function sentTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'sender_device_id');
    }

    public function receivedTransactions(): HasMany
    {
        return $this->hasMany(WalletTransaction::class, 'receiver_device_id');
    }
}