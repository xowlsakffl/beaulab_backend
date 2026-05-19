<?php

namespace App\Domains\Common\ContentReport\Models;

use App\Domains\AccountStaff\Models\AccountStaff;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class ContentReportState extends Model
{
    public const string STATUS_NONE = 'NONE';

    public const string STATUS_REPORTED = 'REPORTED';

    public const string STATUS_AUTO_BLOCKED = 'AUTO_BLOCKED';

    public const string STATUS_ADMIN_HIDDEN = 'ADMIN_HIDDEN';

    public const string STATUS_NORMAL_VISIBLE = 'NORMAL_VISIBLE';

    public const string STATUS_REEXPOSED = 'REEXPOSED';

    public const string STATUS_VALID = 'VALID';

    public const string STATUS_INVALID = 'INVALID';

    public const string WARNING_STATUS_NONE = 'NONE';

    public const string WARNING_STATUS_WARNED = 'WARNED';

    public const string WARNING_STATUS_IGNORED = 'IGNORED';

    public const int AUTO_BLOCK_RECENT_HOUR_THRESHOLD = 10;

    public const int AUTO_ACTION_LOCK_NORMAL_VISIBLE_THRESHOLD = 3;

    protected $table = 'content_report_states';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'target_type',
        'target_id',
        'report_status',
        'report_count',
        'recent_hour_report_count',
        'normal_visible_count',
        'first_reported_at',
        'last_reported_at',
        'auto_blocked_at',
        'admin_hidden_at',
        'normal_visible_at',
        'processed_by',
        'process_reason',
        'warning_status',
        'warning_processed_at',
        'warning_processed_by',
    ];

    protected $casts = [
        'target_id' => 'integer',
        'report_count' => 'integer',
        'recent_hour_report_count' => 'integer',
        'normal_visible_count' => 'integer',
        'first_reported_at' => 'datetime',
        'last_reported_at' => 'datetime',
        'auto_blocked_at' => 'datetime',
        'admin_hidden_at' => 'datetime',
        'normal_visible_at' => 'datetime',
        'processed_by' => 'integer',
        'warning_processed_by' => 'integer',
        'warning_processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $attributes = [
        'report_status' => self::STATUS_NONE,
        'report_count' => 0,
        'recent_hour_report_count' => 0,
        'normal_visible_count' => 0,
        'warning_status' => self::WARNING_STATUS_NONE,
    ];

    /**
     * @return list<string>
     */
    public static function statuses(): array
    {
        return [
            self::STATUS_NONE,
            self::STATUS_REPORTED,
            self::STATUS_AUTO_BLOCKED,
            self::STATUS_ADMIN_HIDDEN,
            self::STATUS_NORMAL_VISIBLE,
            self::STATUS_REEXPOSED,
            self::STATUS_VALID,
            self::STATUS_INVALID,
        ];
    }

    /**
     * @return list<string>
     */
    public static function processableStatuses(): array
    {
        return [
            self::STATUS_ADMIN_HIDDEN,
            self::STATUS_NORMAL_VISIBLE,
            self::STATUS_VALID,
            self::STATUS_INVALID,
        ];
    }

    /**
     * @return list<string>
     */
    public static function warningStatuses(): array
    {
        return [
            self::WARNING_STATUS_NONE,
            self::WARNING_STATUS_WARNED,
            self::WARNING_STATUS_IGNORED,
        ];
    }

    /**
     * @return list<string>
     */
    public static function warningProcessableStatuses(): array
    {
        return [
            self::WARNING_STATUS_WARNED,
            self::WARNING_STATUS_IGNORED,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function warningStatusLabels(): array
    {
        return [
            self::WARNING_STATUS_NONE => '미처리',
            self::WARNING_STATUS_WARNED => '경고',
            self::WARNING_STATUS_IGNORED => '무시',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function statusLabels(): array
    {
        return [
            self::STATUS_NONE => '없음',
            self::STATUS_REPORTED => '신고접수',
            self::STATUS_AUTO_BLOCKED => '자동차단',
            self::STATUS_ADMIN_HIDDEN => '노출중지',
            self::STATUS_NORMAL_VISIBLE => '정상노출',
            self::STATUS_REEXPOSED => '재노출',
            self::STATUS_VALID => '적합',
            self::STATUS_INVALID => '부적합',
        ];
    }

    public function statusLabel(): string
    {
        return self::statusLabels()[(string) $this->report_status] ?? (string) $this->report_status;
    }

    public function isAutoActionLocked(): bool
    {
        return (int) $this->normal_visible_count >= self::AUTO_ACTION_LOCK_NORMAL_VISIBLE_THRESHOLD;
    }

    public function warningStatusLabel(): string
    {
        return self::warningStatusLabels()[(string) $this->warning_status] ?? (string) $this->warning_status;
    }

    public function target(): MorphTo
    {
        return $this->morphTo('target', 'target_type', 'target_id');
    }

    public function processedBy(): BelongsTo
    {
        return $this->belongsTo(AccountStaff::class, 'processed_by');
    }

    public function warningProcessedBy(): BelongsTo
    {
        return $this->belongsTo(AccountStaff::class, 'warning_processed_by');
    }
}
