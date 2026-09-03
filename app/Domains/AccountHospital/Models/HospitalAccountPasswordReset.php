<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Models;

use Illuminate\Database\Eloquent\Model;

final class HospitalAccountPasswordReset extends Model
{
    protected $table = 'hospital_account_password_resets';

    protected $fillable = [
        'account_hospital_id', 'token_hash', 'credential_hash', 'expires_at',
        'used_at', 'revoked_at', 'created_by_staff_id',
    ];

    protected $hidden = ['token_hash', 'credential_hash'];

    protected function casts(): array
    {
        return [
            'account_hospital_id' => 'integer',
            'expires_at' => 'datetime',
            'used_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }
}
