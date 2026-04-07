<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OtpVerification extends Model
{
    use HasFactory;

    protected $table = 'otp_verifications';

    // الثوابت 
    const PURPOSE_REGISTRATION = 'registration';
    const PURPOSE_WITHDRAWAL   = 'withdrawal';
    const PURPOSE_PIN_CHANGE   = 'pin_change';
    const PURPOSE_LOGIN        = 'login';

    protected $fillable = [
        'user_id',
        'purpose',
        'code_hash',
        'is_used',
        'expires_at',
    ];

    protected $hidden = ['code_hash'];

    protected function casts(): array
    {
        return [
            'is_used'    => 'boolean',
            'expires_at' => 'datetime',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // العلاقة 
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}