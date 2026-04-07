<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MobileDeviceDetail extends Model
{
    use HasFactory;

    protected $table = 'mobile_device_details';
    protected $primaryKey = 'device_id';
    public $incrementing = false;

    // الثوابت 
    const BIOMETRIC_FACE        = 'face';
    const BIOMETRIC_FINGERPRINT = 'fingerprint';
    const BIOMETRIC_NONE        = 'none';

    protected $fillable = [
        'device_id',
        'phone_model',
        'phone_os',
        'device_fingerprint',
        'nfc_supported',
        'biometric_type',
    ];

    protected function casts(): array
    {
        return [
            'nfc_supported' => 'boolean',
            'created_at'    => 'datetime',
            'updated_at'    => 'datetime',
        ];
    }

    // العلاقة 
    public function device(): BelongsTo
    {
        return $this->belongsTo(NfcDevice::class, 'device_id');
    }
}