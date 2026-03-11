<?php

namespace App\Domains\HospitalTalk\Models;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Models\AdminNote\AdminNote;
use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Models\Concerns\HasAuditLogs;
use App\Domains\Common\Models\Media\Media;
use Database\Factories\HospitalTalkFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class HospitalTalk extends Model
{
    use HasFactory, SoftDeletes, HasAuditLogs;

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    protected $table = 'hospital_talks';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'author_id',
        'title',
        'content',
        'status',
        'is_visible',
        'author_ip',
        'is_pinned',
        'pinned_order',
        'view_count',
        'comment_count',
        'like_count',
    ];

    protected $casts = [
        'author_id' => 'integer',
        'is_visible' => 'boolean',
        'is_pinned' => 'boolean',
        'pinned_order' => 'integer',
        'view_count' => 'integer',
        'comment_count' => 'integer',
        'like_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected $attributes = [
        'status' => self::STATUS_ACTIVE,
        'is_visible' => true,
        'is_pinned' => false,
        'pinned_order' => 0,
        'view_count' => 0,
        'comment_count' => 0,
        'like_count' => 0,
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(AccountUser::class, 'author_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(HospitalTalkComment::class, 'hospital_talk_id')
            ->orderBy('id');
    }

    public function images(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')
            ->where('collection', 'images')
            ->ordered();
    }

    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categorizable', 'category_assignments', 'categorizable_id', 'category_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function adminNotes(): MorphMany
    {
        return $this->morphMany(AdminNote::class, 'target', 'target_type', 'target_id')
            ->latest('id');
    }

    protected static function newFactory(): Factory
    {
        return HospitalTalkFactory::new();
    }
}
