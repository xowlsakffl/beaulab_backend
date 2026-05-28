<?php

namespace App\Domains\Common\AdminNote\Support;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Beauty\Models\Beauty;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use Illuminate\Database\Eloquent\Model;

/**
 * AdminNoteTargetRegistry 역할 정의.
 * 메모가 무엇에 달리는지
 * 공통 도메인의 지원 유틸리티로, 여러 계층에서 반복되는 계산이나 매핑 규칙을 분리해 재사용한다.
 */
final class AdminNoteTargetRegistry
{
    public const string ALIAS_HOSPITAL = 'hospital';
    public const string ALIAS_BEAUTY = 'beauty';
    public const string ALIAS_HOSPITAL_VIDEO = 'hospital_video';
    public const string ALIAS_ACCOUNT_USER = 'account_user';

    /**
     * @var array<string, class-string<Model>>
     */
    private const MAP = [
        self::ALIAS_HOSPITAL => Hospital::class,
        self::ALIAS_BEAUTY => Beauty::class,
        self::ALIAS_HOSPITAL_VIDEO => HospitalVideo::class,
        self::ALIAS_ACCOUNT_USER => AccountUser::class,
    ];

    /**
     * @return array<int, string>
     */
    public static function aliases(): array
    {
        return array_keys(self::MAP);
    }

    /**
     * @return class-string<Model>|null
     */
    public static function classForAlias(string $alias): ?string
    {
        return self::MAP[$alias] ?? null;
    }

    public static function aliasForModel(Model|string|null $target): ?string
    {
        $className = match (true) {
            $target instanceof Model => $target::class,
            is_string($target) && $target !== '' => $target,
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

    public static function resolveTarget(string $alias, int $id): Model
    {
        $className = self::classForAlias($alias);

        if ($className === null) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '지원하지 않는 메모 대상입니다.');
        }

        if ($className === AccountUser::class) {
            return AccountUser::withTrashed()->findOrFail($id);
        }

        return $className::query()->findOrFail($id);
    }
}
