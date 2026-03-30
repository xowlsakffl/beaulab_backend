<?php

namespace App\Domains\Common\Support\AdminNote;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Beauty\Models\Beauty;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalTalk\Models\HospitalTalk;
use App\Domains\HospitalTalk\Models\HospitalTalkComment;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use Illuminate\Database\Eloquent\Model;

final class AdminNoteTargetRegistry
{
    public const string ALIAS_HOSPITAL = 'hospital';
    public const string ALIAS_BEAUTY = 'beauty';
    public const string ALIAS_HOSPITAL_VIDEO = 'hospital_video';
    public const string ALIAS_HOSPITAL_TALK = 'hospital_talk';
    public const string ALIAS_HOSPITAL_TALK_COMMENT = 'hospital_talk_comment';

    /**
     * @var array<string, class-string<Model>>
     */
    private const MAP = [
        self::ALIAS_HOSPITAL => Hospital::class,
        self::ALIAS_BEAUTY => Beauty::class,
        self::ALIAS_HOSPITAL_VIDEO => HospitalVideo::class,
        self::ALIAS_HOSPITAL_TALK => HospitalTalk::class,
        self::ALIAS_HOSPITAL_TALK_COMMENT => HospitalTalkComment::class,
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

        return $className::query()->findOrFail($id);
    }
}
