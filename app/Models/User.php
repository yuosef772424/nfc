<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'users';

    // الثوابت
    const TYPE_USER     = 'user';
    const TYPE_AGENT    = 'agent';
    const TYPE_MERCHANT = 'merchant';

    const STATUS_ACTIVE    = 'active';
    const STATUS_INACTIVE  = 'inactive';
    const STATUS_SUSPENDED = 'suspended';

    protected $fillable = [
        'uuid',
        'name',
        'phone',
        'email',
        'password',
        'user_type',
        'status',
        'is_verified',
        'metadata',
    ];

    protected $hidden = ['password'];

    protected function casts(): array
    {
        return [
            'is_verified' => 'boolean',
            'metadata'    => 'array',
            'created_at'  => 'datetime',
            'updated_at'  => 'datetime',
        ];
    }

    // العلاقات فقط
    public function kyc(): HasOne
    {
        return $this->hasOne(UserKyc::class, 'user_id');
    }

    public function agentProfile(): HasOne
    {
        return $this->hasOne(AgentProfile::class, 'user_id');
    }

    public function merchantProfile(): HasOne
    {
        return $this->hasOne(MerchantProfile::class, 'user_id');
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class, 'user_id');
    }

    public function defaultWallet(): HasOne
    {
        return $this->hasOne(Wallet::class, 'user_id')
                    ->where('status', 'active')
                    ->latestOfMany();
    }

    public function cards(): HasMany
    {
        return $this->hasMany(Card::class, 'agent_id');
    }

    public function nfcDevices(): HasMany
    {
        return $this->hasMany(NfcDevice::class, 'user_id');
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(Session::class, 'user_id');
    }

    public function otpVerifications(): HasMany
    {
        return $this->hasMany(OtpVerification::class, 'user_id');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class, 'user_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class, 'user_id');
    }

    public function withdrawalsAsAgent(): HasMany
    {
        return $this->hasMany(Withdrawal::class, 'agent_id');
    }

    public function commissions(): HasMany
    {
        return $this->hasMany(CommissionLog::class, 'recipient_id');
    }
}