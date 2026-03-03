<?php

namespace App\Domains\Hospital\Models;

use App\Domains\Partner\Models\AccountPartner;
use Illuminate\Database\Eloquent\Builder;

class AccountHospital extends AccountPartner
{
    protected string $guard_name = 'hospital';

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->partner_type = self::PARTNER_HOSPITAL;
        });

        static::addGlobalScope('hospital_partner_type', function (Builder $builder): void {
            $builder->where('partner_type', self::PARTNER_HOSPITAL);
        });
    }
}
