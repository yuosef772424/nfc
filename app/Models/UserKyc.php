<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserKyc extends Model
{
    use HasFactory;

    protected $table = 'user_kyc';

    // الثوابت
    const ID_TYPE_NATIONAL = 'national_id';
    const ID_TYPE_PASSPORT = 'passport';

    protected $fillable = [
        'user_id',
        'id_type',
        'id_number',
        'id_front_image',
        'id_back_image',
        'id_expiry_date',
        'date_of_birth',
        'address',
        'verified_at',
    ];

    protected function casts(): array
    {
        return [
            'id_expiry_date' => 'date',
            'date_of_birth'  => 'date',
            'verified_at'    => 'datetime',
            'created_at'     => 'datetime',
            'updated_at'     => 'datetime',
        ];
    }

    // العلاقة 
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}