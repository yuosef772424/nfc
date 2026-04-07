<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhysicalDeviceDetail extends Model
{
    use HasFactory;

    protected $table = 'physical_device_details';
    protected $primaryKey = 'device_id';
    public $incrementing = false;

    protected $fillable = [
        'device_id',
        'serial_number',
        'installation_location',
        'installation_date',
    ];

    protected function casts(): array
    {
        return [
            'installation_date' => 'date',
            'created_at'        => 'datetime',
            'updated_at'        => 'datetime',
        ];
    }

    // العلاقة 
    public function device(): BelongsTo
    {
        return $this->belongsTo(NfcDevice::class, 'device_id');
    }
}