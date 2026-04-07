<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AgentProfile extends Model
{
    use HasFactory;

    protected $table = 'agent_profiles';
    protected $primaryKey = 'user_id';
    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'commission_type',
        'commission_value',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'commission_value' => 'decimal:2',
            'is_active'        => 'boolean',
            'metadata'         => 'array',
            'created_at'       => 'datetime',
            'updated_at'       => 'datetime',
        ];
    }

    // الثوابت  
    const COMMISSION_FIXED      = 'fixed';
    const COMMISSION_PERCENTAGE = 'percentage';

    // العلاقة 
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}