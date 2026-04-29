<?php

namespace App\Domains\Common\OperationHistory\Support;

use App\Domains\AccountBeauty\Models\AccountBeauty;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use Illuminate\Database\Eloquent\Model;

final class OperationHistoryActorRegistry
{
    public const string ALIAS_STAFF = 'staff';
    public const string ALIAS_HOSPITAL = 'hospital';
    public const string ALIAS_BEAUTY = 'beauty';
    public const string ALIAS_USER = 'user';
    public const string ALIAS_SYSTEM = 'system';
    public const string ALIAS_UNKNOWN = 'unknown';

    /**
     * @var array<string, class-string<Model>>
     */
    private const MAP = [
        self::ALIAS_STAFF => AccountStaff::class,
        self::ALIAS_HOSPITAL => AccountHospital::class,
        self::ALIAS_BEAUTY => AccountBeauty::class,
        self::ALIAS_USER => AccountUser::class,
    ];

    /**
     * @return array<int, string>
     */
    public static function aliases(): array
    {
        return [
            ...array_keys(self::MAP),
            self::ALIAS_SYSTEM,
            self::ALIAS_UNKNOWN,
        ];
    }

    public static function aliasForModel(Model|string|null $actor): ?string
    {
        $className = match (true) {
            $actor instanceof Model => $actor::class,
            is_string($actor) && $actor !== '' => $actor,
            default => null,
        };

        if ($className === null) {
            return null;
        }

        foreach (self::MAP as $alias => $class) {
            if ($class === $className) {
                return $alias;
            }
        }

        return null;
    }

    public static function aliasForKind(?string $kind): ?string
    {
        return match ($kind) {
            OperationHistory::ACTOR_KIND_STAFF => self::ALIAS_STAFF,
            OperationHistory::ACTOR_KIND_HOSPITAL => self::ALIAS_HOSPITAL,
            OperationHistory::ACTOR_KIND_BEAUTY => self::ALIAS_BEAUTY,
            OperationHistory::ACTOR_KIND_USER => self::ALIAS_USER,
            OperationHistory::ACTOR_KIND_SYSTEM => self::ALIAS_SYSTEM,
            OperationHistory::ACTOR_KIND_UNKNOWN => self::ALIAS_UNKNOWN,
            default => null,
        };
    }

    public static function kindForActor(?Model $actor): string
    {
        return match (true) {
            $actor instanceof AccountStaff => OperationHistory::ACTOR_KIND_STAFF,
            $actor instanceof AccountHospital => OperationHistory::ACTOR_KIND_HOSPITAL,
            $actor instanceof AccountBeauty => OperationHistory::ACTOR_KIND_BEAUTY,
            $actor instanceof AccountUser => OperationHistory::ACTOR_KIND_USER,
            $actor === null => OperationHistory::ACTOR_KIND_SYSTEM,
            default => OperationHistory::ACTOR_KIND_UNKNOWN,
        };
    }
}
