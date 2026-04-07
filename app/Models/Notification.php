<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasFactory;

    protected $table = 'notifications';

    // الثوابت 
    const TYPE_TRANSACTION = 'transaction';
    const TYPE_SECURITY    = 'security';
    const TYPE_SYSTEM      = 'system';
    const TYPE_PROMOTION   = 'promotion';

    const CHANNEL_PUSH  = 'push';
    const CHANNEL_SMS   = 'sms';
    const CHANNEL_EMAIL = 'email';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'channel',
        'is_read',
        'data',
    ];

    protected function casts(): array
    {
        return [
            'is_read'    => 'boolean',
            'data'       => 'array',
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