<?php

namespace App\Domains\Common\ContentReport\Models;

use App\Domains\AccountUser\Models\AccountUser;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

final class ContentReportItem extends Model
{
    protected $table = 'content_report_items';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'content_report_id',
        'reporter_user_id',
        'target_type',
        'target_id',
        'target_author_id',
        'content_snapshot',
    ];

    protected $casts = [
        'content_report_id' => 'integer',
        'reporter_user_id' => 'integer',
        'target_id' => 'integer',
        'target_author_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(ContentReport::class, 'content_report_id');
    }

    public function reporter(): BelongsTo
    {
        return $this->belongsTo(AccountUser::class, 'reporter_user_id');
    }

    public function target(): MorphTo
    {
        return $this->morphTo('target', 'target_type', 'target_id');
    }

    public function targetAuthor(): BelongsTo
    {
        return $this->belongsTo(AccountUser::class, 'target_author_id');
    }
}
