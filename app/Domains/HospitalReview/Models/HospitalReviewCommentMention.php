<?php

namespace App\Domains\HospitalReview\Models;

use App\Common\Concerns\HasAuditLogs;
use App\Domains\AccountUser\Models\AccountUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class HospitalReviewCommentMention extends Model
{
    use HasAuditLogs;

    protected $table = 'hospital_review_comment_mentions';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'hospital_review_comment_id',
        'mentioned_user_id',
        'mentioned_by_user_id',
        'mention_text',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'hospital_review_comment_id' => 'integer',
        'mentioned_user_id' => 'integer',
        'mentioned_by_user_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function comment(): BelongsTo
    {
        return $this->belongsTo(HospitalReviewComment::class, 'hospital_review_comment_id');
    }

    public function mentionedUser(): BelongsTo
    {
        return $this->belongsTo(AccountUser::class, 'mentioned_user_id');
    }

    public function mentionedByUser(): BelongsTo
    {
        return $this->belongsTo(AccountUser::class, 'mentioned_by_user_id');
    }
}
