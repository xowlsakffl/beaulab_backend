<?php

namespace App\Domains\Common\OperationHistory\Support;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Beauty\Models\Beauty;
use App\Domains\BeautyExpert\Models\BeautyExpert;
use App\Domains\Chat\Models\ChatMessage;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Hashtag\Models\Hashtag;
use App\Domains\Faq\Models\Faq;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use App\Domains\Notice\Models\Notice;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;
use Illuminate\Database\Eloquent\Model;

final class OperationHistoryTargetRegistry
{
    public const string ALIAS_HOSPITAL = 'hospital';

    public const string ALIAS_HOSPITAL_DOCTOR = 'hospital_doctor';

    public const string ALIAS_BEAUTY = 'beauty';

    public const string ALIAS_HOSPITAL_VIDEO = 'hospital_video';

    public const string ALIAS_NOTICE = 'notice';

    public const string ALIAS_FAQ = 'faq';

    public const string ALIAS_CATEGORY = 'category';

    public const string ALIAS_HASHTAG = 'hashtag';

    public const string ALIAS_ACCOUNT_USER = 'account_user';

    public const string ALIAS_BEAUTY_EXPERT = 'beauty_expert';

    public const string ALIAS_HOSPITAL_EVALUATION = 'hospital_evaluation';

    public const string ALIAS_HOSPITAL_EVENT = 'hospital_event';

    public const string ALIAS_HOSPITAL_REVIEW = 'hospital_review';

    public const string ALIAS_HOSPITAL_REVIEW_COMMENT = 'hospital_review_comment';

    public const string ALIAS_TALK = 'talk';

    public const string ALIAS_TALK_COMMENT = 'talk_comment';

    public const string ALIAS_CHAT_MESSAGE = 'chat_message';

    /**
     * @var array<string, class-string<Model>>
     */
    private const MAP = [
        self::ALIAS_HOSPITAL => Hospital::class,
        self::ALIAS_HOSPITAL_DOCTOR => HospitalDoctor::class,
        self::ALIAS_BEAUTY => Beauty::class,
        self::ALIAS_BEAUTY_EXPERT => BeautyExpert::class,
        self::ALIAS_HOSPITAL_VIDEO => HospitalVideo::class,
        self::ALIAS_NOTICE => Notice::class,
        self::ALIAS_FAQ => Faq::class,
        self::ALIAS_CATEGORY => Category::class,
        self::ALIAS_HASHTAG => Hashtag::class,
        self::ALIAS_ACCOUNT_USER => AccountUser::class,
        self::ALIAS_HOSPITAL_EVALUATION => HospitalEvaluation::class,
        self::ALIAS_HOSPITAL_EVENT => HospitalEvent::class,
        self::ALIAS_HOSPITAL_REVIEW => HospitalReview::class,
        self::ALIAS_HOSPITAL_REVIEW_COMMENT => HospitalReviewComment::class,
        self::ALIAS_TALK => Talk::class,
        self::ALIAS_TALK_COMMENT => TalkComment::class,
        self::ALIAS_CHAT_MESSAGE => ChatMessage::class,
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
            throw new CustomException(ErrorCode::INVALID_REQUEST, '지원하지 않는 히스토리 대상입니다.');
        }

        return $className::query()->findOrFail($id);
    }

    public static function assertSupported(Model $target): void
    {
        if (self::aliasForModel($target) !== null) {
            return;
        }

        throw new \InvalidArgumentException(sprintf(
            'Unsupported operation history target model: %s',
            $target::class,
        ));
    }
}
