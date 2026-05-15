<?php

namespace App\Domains\Common\ContentReport\Support;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;
use Illuminate\Database\Eloquent\Model;

final class ContentReportTargetRegistry
{
    public const string ALIAS_TALK = 'talk';

    public const string ALIAS_TALK_COMMENT = 'talk_comment';

    public const string ALIAS_HOSPITAL_REVIEW = 'hospital_review';

    public const string ALIAS_HOSPITAL_REVIEW_COMMENT = 'hospital_review_comment';

    public const string ALIAS_HOSPITAL_EVALUATION = 'hospital_evaluation';

    /**
     * @var array<string, class-string<Model>>
     */
    private const MAP = [
        self::ALIAS_TALK => Talk::class,
        self::ALIAS_TALK_COMMENT => TalkComment::class,
        self::ALIAS_HOSPITAL_REVIEW => HospitalReview::class,
        self::ALIAS_HOSPITAL_REVIEW_COMMENT => HospitalReviewComment::class,
        self::ALIAS_HOSPITAL_EVALUATION => HospitalEvaluation::class,
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
            throw new CustomException(ErrorCode::INVALID_REQUEST, '지원하지 않는 신고 대상입니다.');
        }

        return $className::query()->findOrFail($id);
    }

    public static function assertSupported(Model $target): void
    {
        if (self::aliasForModel($target) !== null) {
            return;
        }

        throw new CustomException(ErrorCode::INVALID_REQUEST, '지원하지 않는 신고 대상입니다.');
    }
}
