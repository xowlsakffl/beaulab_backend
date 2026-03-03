<?php

namespace App\Domains\AccountBeauty\Models;

use App\Domains\Partner\Models\AccountPartner;
use Illuminate\Database\Eloquent\Builder;

class AccountBeauty extends AccountPartner
{
    protected string $guard_name = 'beauty';

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            $model->partner_type = self::PARTNER_BEAUTY;
        });

        static::addGlobalScope('beauty_partner_type', function (Builder $builder): void {
            $builder->where('partner_type', self::PARTNER_BEAUTY);
        });
    }
}
