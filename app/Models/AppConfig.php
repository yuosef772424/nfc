<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class AppConfig extends Model
{
    use HasFactory;

    protected $table = 'app_configs';

    protected $fillable = [
        'group', 'key', 'value', 'data_type', 'label',
        'sort_order', 'is_active', 'meta'
    ];

    protected function casts(): array
    {
        return [
            'is_active'  => 'boolean',
            'sort_order' => 'integer',
            'meta'       => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // إرجاع القيمة محولة حسب data_type
    public function getCastedValueAttribute(): mixed
    {
        return match($this->data_type) {
            'number'  => (float) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json'    => json_decode($this->value, true),
            default   => $this->value,
        };
    }
}