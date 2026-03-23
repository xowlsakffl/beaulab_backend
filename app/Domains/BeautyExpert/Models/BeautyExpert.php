<?php

declare(strict_types=1);

namespace App\Domains\BeautyExpert\Models;

use App\Domains\Common\Models\Concerns\HasAuditLogs;
use App\Domains\Beauty\Models\Beauty;
use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Models\Media\Media;
use Database\Factories\BeautyExpertFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class BeautyExpert extends Model
{
    use HasFactory, SoftDeletes, HasAuditLogs;

    public const ALLOW_PENDING  = 'PENDING';
    public const ALLOW_APPROVED = 'APPROVED';
    public const ALLOW_REJECTED = 'REJECTED';

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_SUSPENDED = 'SUSPENDED';
    public const STATUS_INACTIVE = 'INACTIVE';

    protected $table = 'beauty_experts';

    protected $fillable = [
        'beauty_id',
        'sort_order',
        'name',
        'gender',
        'position',
        'career_started_at',
        'educations',
        'careers',
        'etc_contents',
        'status',
        'allow_status',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'educations' => 'array',
        'careers' => 'array',
        'etc_contents' => 'array',
        'career_started_at' => 'date',
    ];

    protected static function newFactory(): Factory
    {
        return BeautyExpertFactory::new();
    }

    public function beauty(): BelongsTo
    {
        return $this->belongsTo(Beauty::class, 'beauty_id');
    }

    public function profileImage(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')->where('collection', 'profile_image');
    }

    public function educationCertificateImages(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')->where('collection', 'education_certificate_image')->orderBy('sort_order')->orderBy('id');
    }

    public function etcCertificateImages(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')->where('collection', 'etc_certificate_image')->orderBy('sort_order')->orderBy('id');
    }

    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categorizable', 'category_assignments', 'categorizable_id', 'category_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }
}
