<?php

declare(strict_types=1);

namespace App\Domains\HospitalEvent\Models;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Media\Models\Media;
use App\Domains\Common\OperationHistory\Concerns\HasOperationHistories;
use App\Domains\Hospital\Models\Hospital;
use Database\Factories\HospitalEventRealModelDBFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class HospitalEventRealModelDB extends Model
{
    use HasFactory, HasOperationHistories, SoftDeletes;

    public const string GENDER_MALE = 'MALE';

    public const string GENDER_FEMALE = 'FEMALE';

    public const string SURGERY_PERIOD_WITHIN_1_MONTH = 'WITHIN_1_MONTH';

    public const string SURGERY_PERIOD_WITHIN_3_MONTHS = 'WITHIN_3_MONTHS';

    public const string SURGERY_PERIOD_WITHIN_6_MONTHS = 'WITHIN_6_MONTHS';

    public const string SURGERY_PERIOD_WITHIN_1_YEAR = 'WITHIN_1_YEAR';

    public const string SURGERY_PERIOD_OVER_1_YEAR = 'OVER_1_YEAR';

    public const string SURGERY_PERIOD_UNDECIDED = 'UNDECIDED';

    public const string SPECIAL_NOTE_REVISION_SURGERY = 'REVISION_SURGERY';

    public const string SPECIAL_NOTE_RECENT_SURGERY_WITHIN_6_MONTHS = 'RECENT_SURGERY_WITHIN_6_MONTHS';

    public const string SPECIAL_NOTE_SIDE_EFFECT_EXPERIENCED = 'SIDE_EFFECT_EXPERIENCED';

    public const string SPECIAL_NOTE_FUNCTIONAL_PROBLEM = 'FUNCTIONAL_PROBLEM';

    public const string STATUS_RECEIVED = 'RECEIVED';

    public const string STATUS_APPROVED = 'APPROVED';

    public const string STATUS_REJECTED = 'REJECTED';

    public const string COLLECTION_IMAGES = 'real_model_db_images';

    public const int MAX_IMAGE_COUNT = 10;

    public const array GENDER_LABELS = [
        self::GENDER_MALE => '남',
        self::GENDER_FEMALE => '여',
    ];

    public const array SURGERY_PERIOD_LABELS = [
        self::SURGERY_PERIOD_WITHIN_1_MONTH => '1개월 이내',
        self::SURGERY_PERIOD_WITHIN_3_MONTHS => '3개월 이내',
        self::SURGERY_PERIOD_WITHIN_6_MONTHS => '6개월 이내',
        self::SURGERY_PERIOD_WITHIN_1_YEAR => '1년 이내',
        self::SURGERY_PERIOD_OVER_1_YEAR => '1년 이상',
        self::SURGERY_PERIOD_UNDECIDED => '미정',
    ];

    public const array SPECIAL_NOTE_LABELS = [
        self::SPECIAL_NOTE_REVISION_SURGERY => '재수술이에요',
        self::SPECIAL_NOTE_RECENT_SURGERY_WITHIN_6_MONTHS => '6개월 이내 수술 이력이 있어요',
        self::SPECIAL_NOTE_SIDE_EFFECT_EXPERIENCED => '수술 후 부작용을 겪고 있어요',
        self::SPECIAL_NOTE_FUNCTIONAL_PROBLEM => '선천 / 후천적 기능 문제가 있어요',
    ];

    public const array STATUS_LABELS = [
        self::STATUS_RECEIVED => '접수',
        self::STATUS_APPROVED => '승인',
        self::STATUS_REJECTED => '미승인',
    ];

    protected $table = 'hospital_event_real_model_dbs';

    protected $fillable = [
        'account_user_id',
        'hospital_id',
        'hospital_event_id',
        'name',
        'gender',
        'birth_date',
        'phone',
        'phone_normalized',
        'height_cm',
        'weight_kg',
        'surgery_period',
        'support_part',
        'instagram_url',
        'blog_url',
        'special_notes',
        'application_reason',
        'inquiry',
        'status',
        'author_ip',
        'user_agent',
    ];

    protected $casts = [
        'account_user_id' => 'integer',
        'hospital_id' => 'integer',
        'hospital_event_id' => 'integer',
        'birth_date' => 'date',
        'height_cm' => 'integer',
        'weight_kg' => 'integer',
        'special_notes' => 'array',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_RECEIVED,
    ];

    protected static function newFactory(): Factory
    {
        return HospitalEventRealModelDBFactory::new();
    }

    protected static function booted(): void
    {
        static::saving(static function (self $application): void {
            $application->phone_normalized = self::normalizePhone($application->phone);
        });
    }

    public function accountUser(): BelongsTo
    {
        return $this->belongsTo(AccountUser::class, 'account_user_id');
    }

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(HospitalEvent::class, 'hospital_event_id');
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')
            ->where('collection', self::COLLECTION_IMAGES)
            ->ordered();
    }

    /**
     * @return list<string>
     */
    public static function genders(): array
    {
        return array_keys(self::GENDER_LABELS);
    }

    /**
     * @return list<string>
     */
    public static function surgeryPeriods(): array
    {
        return array_keys(self::SURGERY_PERIOD_LABELS);
    }

    /**
     * @return list<string>
     */
    public static function specialNotes(): array
    {
        return array_keys(self::SPECIAL_NOTE_LABELS);
    }

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return array_keys(self::STATUS_LABELS);
    }

    public static function genderLabel(?string $value): string
    {
        return self::GENDER_LABELS[$value ?? ''] ?? '-';
    }

    public static function surgeryPeriodLabel(?string $value): string
    {
        return self::SURGERY_PERIOD_LABELS[$value ?? ''] ?? '-';
    }

    public static function specialNoteLabel(?string $value): string
    {
        return self::SPECIAL_NOTE_LABELS[$value ?? ''] ?? '-';
    }

    public static function statusLabel(?string $value): string
    {
        return self::STATUS_LABELS[$value ?? ''] ?? '-';
    }

    public static function normalizePhone(?string $phone): string
    {
        $digits = preg_replace('/\D+/', '', (string) $phone) ?? '';

        if (str_starts_with($digits, '82') && strlen($digits) >= 11) {
            return '0'.substr($digits, 2);
        }

        return $digits;
    }
}
