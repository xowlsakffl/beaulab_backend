<?php

namespace App\Domains\Common\Models\Category;

use App\Domains\Common\Models\Concerns\HasAuditLogs;
use App\Domains\Common\Models\Media\Media;
use App\Domains\Hospital\Models\Hospital;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

final class Category extends Model
{
    use HasFactory, HasAuditLogs;

    public const DOMAIN_HOSPITAL_SURGERY = 'HOSPITAL_SURGERY';
    public const DOMAIN_HOSPITAL_TREATMENT = 'HOSPITAL_TREATMENT';
    public const DOMAIN_HOSPITAL_COMMUNITY = 'HOSPITAL_COMMUNITY';

    public const DOMAIN_BEAUTY = 'BEAUTY';
    public const DOMAIN_BEAUTY_COMMUNITY = 'BEAUTY_COMMUNITY';
    public const DOMAIN_FAQ = 'FAQ';

    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    protected $table = 'categories';

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'domain',
        'parent_id',
        'depth',
        'name',
        'code',
        'full_path',
        'sort_order',
        'status',
        'is_menu_visible',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'depth' => 'integer',
        'sort_order' => 'integer',
        'is_menu_visible' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function iconMedia(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')
            ->where('collection', 'icon');
    }

    public function hospitals(): MorphToMany
    {
        return $this->morphedByMany(Hospital::class, 'categorizable', 'category_assignments', 'category_id', 'categorizable_id')
            ->withPivot('is_primary')
            ->withTimestamps();
    }

    public function scopeDomain(Builder $query, string $domain): Builder
    {
        return $query->where('domain', $domain);
    }

    public function isDomain(string $domain): bool
    {
        return $this->domain === $domain;
    }

    /**
     * @return array<int, string>
     */
    public static function domains(): array
    {
        return [
            self::DOMAIN_HOSPITAL_SURGERY,
            self::DOMAIN_HOSPITAL_TREATMENT,
            self::DOMAIN_HOSPITAL_COMMUNITY,
            self::DOMAIN_BEAUTY,
            self::DOMAIN_BEAUTY_COMMUNITY,
            self::DOMAIN_FAQ,
        ];
    }

    protected static function newFactory(): Factory
    {
        return CategoryFactory::new();
    }
}
