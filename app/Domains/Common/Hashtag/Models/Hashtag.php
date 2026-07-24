<?php

namespace App\Domains\Common\Hashtag\Models;

use App\Common\Concerns\HasAuditLogs;
use App\Domains\Common\OperationHistory\Concerns\HasOperationHistories;
use Database\Factories\HashtagFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Hashtag 역할 정의.
 * 공통 도메인의 Eloquent 모델로, 테이블 매핑, 관계, 스코프, 상태 상수를 한곳에 모아 도메인 데이터 접근 기준을 제공한다.
 */
final class Hashtag extends Model
{
    use HasAuditLogs, HasFactory, HasOperationHistories;

    public const NAME_MAX_LENGTH = 20;

    public const VALID_NAME_REGEX = '/^[0-9A-Za-z가-힣_]+$/u';

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_INACTIVE = 'INACTIVE';

    private const LEGACY_STATUS_BLOCKED = 'BLOCKED';

    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    protected $table = 'hashtags';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'normalized_name',
        'status',
        'usage_count',
    ];

    protected $casts = [
        'usage_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        $keyword = is_string($keyword) ? trim($keyword) : null;

        if ($keyword === null || $keyword === '') {
            return $query;
        }

        return $query->where(function (Builder $builder) use ($keyword): void {
            $builder->where('name', 'like', "%{$keyword}%")
                ->orWhere('normalized_name', 'like', "%{$keyword}%");
        });
    }

    /**
     * @param  array<int, string>  $statuses
     */
    public function scopeStatusIn(Builder $query, array $statuses): Builder
    {
        $normalizedStatuses = collect($statuses)
            ->map(static fn (mixed $value): string => strtoupper(trim((string) $value)))
            ->filter(static fn (string $value): bool => self::isValidStatus($value))
            ->map(static fn (string $value): string => self::normalizeStatus($value))
            ->values()
            ->all();

        if ($normalizedStatuses === []) {
            return $query;
        }

        $queryStatuses = $normalizedStatuses;

        if (in_array(self::STATUS_INACTIVE, $normalizedStatuses, true)) {
            $queryStatuses[] = self::LEGACY_STATUS_BLOCKED;
        }

        return $query->whereIn('status', array_values(array_unique($queryStatuses)));
    }

    public static function sanitizeName(string $value): string
    {
        $sanitized = trim($value);

        if ($sanitized === '') {
            return '';
        }

        if (class_exists(\Normalizer::class)) {
            $sanitized = \Normalizer::normalize($sanitized, \Normalizer::FORM_KC) ?: $sanitized;
        }

        $sanitized = preg_replace('/^[#＃]+/u', '', $sanitized) ?? $sanitized;

        return trim($sanitized);
    }

    public static function normalizeName(string $value): string
    {
        $sanitized = self::sanitizeName($value);

        if ($sanitized === '') {
            return '';
        }

        return mb_strtolower($sanitized, 'UTF-8');
    }

    public static function isValidName(string $value): bool
    {
        $sanitized = self::sanitizeName($value);

        if ($sanitized === '') {
            return false;
        }

        if (mb_strlen($sanitized, 'UTF-8') > self::NAME_MAX_LENGTH) {
            return false;
        }

        return (bool) preg_match(self::VALID_NAME_REGEX, $sanitized);
    }

    public static function normalizeStatus(?string $value): string
    {
        $status = strtoupper(trim((string) $value));

        if ($status === self::LEGACY_STATUS_BLOCKED) {
            return self::STATUS_INACTIVE;
        }

        return in_array($status, self::STATUSES, true)
            ? $status
            : self::STATUS_ACTIVE;
    }

    public static function isValidStatus(?string $value): bool
    {
        return in_array(strtoupper(trim((string) $value)), self::STATUSES, true);
    }

    /**
     * @return array<int, string>
     */
    public static function statuses(): array
    {
        return self::STATUSES;
    }

    public function resolveStatus(?string $fallback = null): string
    {
        $status = $this->getAttribute('status') ?? $fallback ?? self::STATUS_ACTIVE;

        return self::normalizeStatus((string) $status);
    }

    public function resolveUsageCount(?int $fallback = null): int
    {
        if (array_key_exists('usage_count', $this->getAttributes())) {
            return (int) ($this->getAttribute('usage_count') ?? 0);
        }

        return $fallback ?? 0;
    }

    public static function statusLabel(?string $status): string
    {
        $normalized = strtoupper(trim((string) $status));

        return match ($normalized) {
            self::STATUS_ACTIVE => '활성',
            self::LEGACY_STATUS_BLOCKED,
            self::STATUS_INACTIVE => '비활성',
            default => $status ?: '-',
        };
    }

    protected static function newFactory(): Factory
    {
        return HashtagFactory::new();
    }
}
