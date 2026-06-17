<?php

declare(strict_types=1);

namespace App\Domains\HospitalEvent\Models;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\OperationHistory\Concerns\HasOperationHistories;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use Database\Factories\HospitalEventConsultationFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class HospitalEventConsultation extends Model
{
    use HasFactory, HasOperationHistories, SoftDeletes;

    public const CONTACT_METHOD_KAKAO = 'KAKAO';

    public const CONTACT_METHOD_PHONE = 'PHONE';

    public const CONTACT_METHOD_SMS = 'SMS';

    public const PREFERRED_TIME_MORNING = 'MORNING';

    public const PREFERRED_TIME_AFTERNOON = 'AFTERNOON';

    public const PREFERRED_TIME_ANYTIME = 'ANYTIME';

    public const STATUS_NEW = 'NEW';

    public const STATUS_CONFIRMED = 'CONFIRMED';

    public const STATUS_DUPLICATE = 'DUPLICATE';

    public const ALLOW_STATUS_UNVERIFIED_REPORTED = 'UNVERIFIED_REPORTED';

    public const ALLOW_STATUS_UNVERIFIED_CONFIRMED = 'UNVERIFIED_CONFIRMED';

    public const ALLOW_STATUS_NORMAL_CONFIRMED = 'NORMAL_CONFIRMED';

    public const CONTACT_METHOD_LABELS = [
        self::CONTACT_METHOD_KAKAO => '카카오톡',
        self::CONTACT_METHOD_PHONE => '전화',
        self::CONTACT_METHOD_SMS => '문자',
    ];

    public const PREFERRED_TIME_LABELS = [
        self::PREFERRED_TIME_MORNING => '오전',
        self::PREFERRED_TIME_AFTERNOON => '오후',
        self::PREFERRED_TIME_ANYTIME => '상시',
    ];

    public const STATUS_LABELS = [
        self::STATUS_NEW => '신규',
        self::STATUS_CONFIRMED => '확인',
        self::STATUS_DUPLICATE => '중복',
    ];

    public const ALLOW_STATUS_LABELS = [
        self::ALLOW_STATUS_UNVERIFIED_REPORTED => '미인증DB 신고',
        self::ALLOW_STATUS_UNVERIFIED_CONFIRMED => '미인증DB 확정',
        self::ALLOW_STATUS_NORMAL_CONFIRMED => '정상DB 확정',
    ];

    protected $table = 'hospital_event_consultations';

    protected $fillable = [
        'account_user_id',
        'hospital_id',
        'hospital_event_id',
        'hospital_doctor_id',
        'name',
        'phone',
        'phone_normalized',
        'contact_method',
        'preferred_time',
        'event_price',
        'consultation_price',
        'status',
        'allow_status',
        'contacted_at',
        'confirmed_at',
        'duplicated_at',
        'author_ip',
        'user_agent',
        'privacy_agreed_at',
        'marketing_agreed_at',
    ];

    protected $casts = [
        'account_user_id' => 'integer',
        'hospital_id' => 'integer',
        'hospital_event_id' => 'integer',
        'hospital_doctor_id' => 'integer',
        'event_price' => 'integer',
        'consultation_price' => 'integer',
        'contacted_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'duplicated_at' => 'datetime',
        'privacy_agreed_at' => 'datetime',
        'marketing_agreed_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'contact_method' => self::CONTACT_METHOD_PHONE,
        'preferred_time' => self::PREFERRED_TIME_ANYTIME,
        'event_price' => 0,
        'consultation_price' => 0,
        'status' => self::STATUS_NEW,
        'allow_status' => self::ALLOW_STATUS_NORMAL_CONFIRMED,
    ];

    protected static function newFactory(): Factory
    {
        return HospitalEventConsultationFactory::new();
    }

    protected static function booted(): void
    {
        static::saving(static function (self $consultation): void {
            $consultation->phone_normalized = self::normalizePhone($consultation->phone);
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

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(HospitalDoctor::class, 'hospital_doctor_id');
    }

    /**
     * @return array<int, string>
     */
    public static function contactMethods(): array
    {
        return array_keys(self::CONTACT_METHOD_LABELS);
    }

    /**
     * @return array<int, string>
     */
    public static function preferredTimes(): array
    {
        return array_keys(self::PREFERRED_TIME_LABELS);
    }

    /**
     * @return array<int, string>
     */
    public static function statuses(): array
    {
        return array_keys(self::STATUS_LABELS);
    }

    /**
     * @return array<int, string>
     */
    public static function allowStatuses(): array
    {
        return array_keys(self::ALLOW_STATUS_LABELS);
    }

    public static function contactMethodLabel(?string $value): string
    {
        return self::CONTACT_METHOD_LABELS[$value ?? ''] ?? '-';
    }

    public static function preferredTimeLabel(?string $value): string
    {
        return self::PREFERRED_TIME_LABELS[$value ?? ''] ?? '-';
    }

    public static function statusLabel(?string $value): string
    {
        return self::STATUS_LABELS[$value ?? ''] ?? '-';
    }

    public static function allowStatusLabel(?string $value): string
    {
        return self::ALLOW_STATUS_LABELS[$value ?? ''] ?? '-';
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
