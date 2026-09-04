<?php

namespace App\Common\Auth;

use App\Domains\AccountBeauty\Models\AccountBeauty;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountUser\Models\AccountUser;

enum AuthActor: string
{
    case STAFF = 'staff';
    case HOSPITAL = 'hospital';
    case BEAUTY = 'beauty';
    case USER = 'user';

    public function model(): string
    {
        return match ($this) {
            self::STAFF => AccountStaff::class,
            self::HOSPITAL => AccountHospital::class,
            self::BEAUTY => AccountBeauty::class,
            self::USER => AccountUser::class,
        };
    }

    public function guard(): string
    {
        return $this->value.'_web';
    }

    public function policy(): array
    {
        return config('web_auth.actors.'.$this->value);
    }
}
