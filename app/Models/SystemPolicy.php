<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SystemPolicy extends Model
{
    use HasFactory;

    protected $table = 'system_policies';

    // الثوابت 
    const TYPE_STRING  = 'string';
    const TYPE_NUMBER  = 'number';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_JSON    = 'json';

    const SCOPE_GLOBAL   = 'global';
    const SCOPE_USER     = 'user';
    const SCOPE_AGENT    = 'agent';
    const SCOPE_MERCHANT = 'merchant';

    const CATEGORY_FEES     = 'fees';
    const CATEGORY_LIMITS   = 'limits';
    const CATEGORY_SECURITY = 'security';
    const CATEGORY_SYSTEM   = 'system';

    protected $fillable = [
        'key', 'value', 'data_type', 'category',
        'scope_type', 'scope_id', 'description',
        'is_active', 'priority'
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'priority'   => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }
}