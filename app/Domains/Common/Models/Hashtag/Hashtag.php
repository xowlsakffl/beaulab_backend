<?php

namespace App\Domains\Common\Models\Hashtag;

use App\Domains\Common\Models\Concerns\HasAuditLogs;
use Database\Factories\HashtagFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class Hashtag extends Model
{
    use HasFactory, HasAuditLogs;

    public const NAME_MAX_LENGTH = 20;
    public const VALID_NAME_REGEX = '/^[0-9A-Za-z가-힣_]+$/u';
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';
    private const LEGACY_STATUS_BLOCKED = 'BLOCKED';
    public const STATUSES = [
        self::STATUS_ACTIVE,
        self::STATUS_INACTIVE,
    ];

    private static ?bool $supportsUsageCountColumn = null;
    private static ?bool $supportsStatusColumn = null;

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
     * @param array<int, string> $statuses
     */
    public function scopeStatusIn(Builder $query, array $statuses): Builder
    {
        $normalizedStatuses = collect($statuses)
            ->map(static fn (mixed $value): string => self::normalizeStatus((string) $value))
            ->filter(static fn (string $value): bool => self::isValidStatus($value))
            ->values()
            ->all();

        if ($normalizedStatuses === [] || !self::supportsStatus()) {
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

    public static function supportsUsageCount(): bool
    {
        if (self::$supportsUsageCountColumn !== null) {
            return self::$supportsUsageCountColumn;
        }

        return self::$supportsUsageCountColumn = Schema::hasColumn((new self())->getTable(), 'usage_count');
    }

    public static function supportsStatus(): bool
    {
        if (self::$supportsStatusColumn !== null) {
            return self::$supportsStatusColumn;
        }

        return self::$supportsStatusColumn = Schema::hasColumn((new self())->getTable(), 'status');
    }

    public function resolveStatus(?string $fallback = null): string
    {
        if (self::supportsStatus() && array_key_exists('status', $this->getAttributes())) {
            return self::normalizeStatus((string) ($this->getAttribute('status') ?? self::STATUS_ACTIVE));
        }

        if ($fallback !== null && self::isValidStatus($fallback)) {
            return self::normalizeStatus($fallback);
        }

        return self::STATUS_ACTIVE;
    }

    public function resolveUsageCount(?int $fallback = null): int
    {
        if (self::supportsUsageCount() && array_key_exists('usage_count', $this->getAttributes())) {
            return (int) ($this->getAttribute('usage_count') ?? 0);
        }

        if ($fallback !== null) {
            return $fallback;
        }

        return (int) DB::table('hashtaggables')
            ->where('hashtag_id', $this->getKey())
            ->count();
    }

    public function syncUsageCount(): int
    {
        $count = (int) DB::table('hashtaggables')
            ->where('hashtag_id', $this->getKey())
            ->count();

        $this->forceFill(['usage_count' => $count]);

        if (self::supportsUsageCount()) {
            static::query()
                ->whereKey($this->getKey())
                ->update(['usage_count' => $count]);
        }

        return $count;
    }

    /**
     * @param array<int, int|string> $hashtagIds
     */
    public static function syncUsageCounts(array $hashtagIds): void
    {
        if (!self::supportsUsageCount()) {
            return;
        }

        $ids = collect($hashtagIds)
            ->map(static fn ($id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($ids === []) {
            return;
        }

        $countsById = DB::table('hashtaggables')
            ->selectRaw('hashtag_id, COUNT(*) as aggregate_count')
            ->whereIn('hashtag_id', $ids)
            ->groupBy('hashtag_id')
            ->pluck('aggregate_count', 'hashtag_id');

        foreach ($ids as $id) {
            static::query()
                ->whereKey($id)
                ->update([
                    'usage_count' => (int) ($countsById[$id] ?? 0),
                ]);
        }
    }

    protected static function newFactory(): Factory
    {
        return HashtagFactory::new();
    }
}
