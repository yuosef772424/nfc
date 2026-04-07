<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditLog extends Model
{
    use HasFactory;

    protected $table = 'audit_log';
    public $timestamps = false;

    // الثوابت 
    const ACTION_CREATE = 'create';
    const ACTION_UPDATE = 'update';
    const ACTION_DELETE = 'delete';
    const ACTION_LOGIN  = 'login';
    const ACTION_LOGOUT = 'logout';
    const ACTION_VERIFY = 'verify';

    protected $fillable = ['user_id', 'action', 'entity', 'entity_id', 'old_data', 'new_data', 'ip_address'];

    protected function casts(): array
    {
        return [
            'old_data'   => 'array',
            'new_data'   => 'array',
            'created_at' => 'datetime',
        ];
    }

    // العلاقة 
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}