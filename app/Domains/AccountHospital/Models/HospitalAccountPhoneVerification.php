<?php

declare(strict_types=1);

namespace App\Domains\AccountHospital\Models;

use Illuminate\Database\Eloquent\Model;

// Legacy SMS history references this morph type. It is never used for authentication.
final class HospitalAccountPhoneVerification extends Model
{
    protected $table = 'hospital_account_phone_verifications';

    protected $guarded = ['*'];

    protected $visible = ['id', 'created_at'];
}
