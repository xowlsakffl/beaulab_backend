<?php

namespace App\Domains\Common\Support\AdminNote;

use App\Domains\AccountBeauty\Models\AccountBeauty;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Models\AdminNote\AdminNote;
use Illuminate\Database\Eloquent\Model;

final class AdminNoteActorRegistry
{
    public const string ALIAS_STAFF = 'staff';
    public const string ALIAS_HOSPITAL = 'hospital';
    public const string ALIAS_BEAUTY = 'beauty';

    /**
     * @var array<string, class-string<Model>>
     */
    private const MAP = [
        self::ALIAS_STAFF => AccountStaff::class,
        self::ALIAS_HOSPITAL => AccountHospital::class,
        self::ALIAS_BEAUTY => AccountBeauty::class,
    ];

    /**
     * @return array<int, string>
     */
    public static function aliases(): array
    {
        return array_keys(self::MAP);
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

    public static function isStaffActor(mixed $actor): bool
    {
        return $actor instanceof AccountStaff;
    }

    public static function isPartnerActor(mixed $actor): bool
    {
        return $actor instanceof AccountHospital || $actor instanceof AccountBeauty;
    }

    public static function isCreator(mixed $actor, AdminNote $note): bool
    {
        if (! $actor instanceof Model) {
            return false;
        }

        return $note->creator_type === $actor->getMorphClass()
            && (int) $note->creator_id === (int) $actor->getKey();
    }
}
