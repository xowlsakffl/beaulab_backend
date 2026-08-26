<?php

declare(strict_types=1);

namespace App\Common\Support;

use App\Domains\AccountHospital\Models\AccountHospital;
use Illuminate\Database\Eloquent\Model;

final class ActorDisplay
{
    public static function name(Model $actor): string
    {
        if ($actor instanceof AccountHospital) {
            $hospital = $actor->relationLoaded('hospital')
                ? $actor->hospital
                : $actor->hospital()->first(['id', 'name']);

            return trim((string) ($hospital?->name ?? ''));
        }

        return trim((string) ($actor->name ?? $actor->nickname ?? ''));
    }

    public static function label(?Model $actor, string $fallback = '-'): string
    {
        if (! $actor instanceof Model) {
            return $fallback;
        }

        $name = self::name($actor);

        return $name !== '' ? $name : (string) ($actor->email ?? $fallback);
    }

    /** @return array{id:int,name:string,email:?string}|null */
    public static function toArray(?Model $actor): ?array
    {
        if (! $actor instanceof Model) {
            return null;
        }

        return [
            'id' => (int) $actor->getKey(),
            'name' => self::name($actor),
            'email' => is_string($actor->email ?? null) ? $actor->email : null,
        ];
    }
}
