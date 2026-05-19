<?php

namespace App\Domains\Common\ContentReport\Models;

use App\Domains\AccountUser\Models\AccountUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class ContentReport extends Model
{
    public const string REASON_ABUSE = 'ABUSE';

    public const string REASON_SPAM = 'SPAM';

    public const string REASON_ILLEGAL_AD = 'ILLEGAL_AD';

    public const string REASON_PRIVACY_COPYRIGHT = 'PRIVACY_COPYRIGHT';

    public const string REASON_OTHER = 'OTHER';

    protected $table = 'content_reports';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'reporter_user_id',
        'target_type',
        'target_id',
        'target_author_id',
        'reason',
        'reason_text',
        'content_snapshot',
        'reporter_ip',
    ];

    protected $casts = [
        'reporter_user_id' => 'integer',
        'target_id' => 'integer',
        'target_author_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * @return list<string>
     */
    public static function reasons(): array
    {
        return [
            self::REASON_ABUSE,
            self::REASON_SPAM,
            self::REASON_ILLEGAL_AD,
            self::REASON_PRIVACY_COPYRIGHT,
            self::REASON_OTHER,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function reasonLabels(): array
    {
        return [
            self::REASON_ABUSE => '비방/욕설',
            self::REASON_SPAM => '게시물/댓글 도배',
            self::REASON_ILLEGAL_AD => '불법광고/홍보',
            self::REASON_PRIVACY_COPYRIGHT => '개인정보/저작권 침해',
            self::REASON_OTHER => '기타',
        ];
    }

    public function reasonLabel(): string
    {
        return self::reasonLabels()[(string) $this->reason] ?? (string) $this->reason;
    }

    public function target(): MorphTo
    {
        return $this->morphTo('target', 'target_type', 'target_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(AccountUser::class, 'reporter_user_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ContentReportItem::class, 'content_report_id');
    }

    public function targetAuthor(): BelongsTo
    {
        return $this->belongsTo(AccountUser::class, 'target_author_id');
    }
}
