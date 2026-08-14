<?php

declare(strict_types=1);

namespace App\Domains\HospitalWallet\Concerns;

use Illuminate\Database\Eloquent\Model;
use LogicException;

trait PreventsLedgerMutation
{
    protected static function bootPreventsLedgerMutation(): void
    {
        static::updating(static function (Model $model): never {
            throw new LogicException(sprintf('%s records are immutable.', $model::class));
        });

        static::deleting(static function (Model $model): never {
            throw new LogicException(sprintf('%s records are immutable.', $model::class));
        });
    }
}
