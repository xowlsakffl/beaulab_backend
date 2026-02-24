<?php

declare(strict_types=1);

namespace App\Domains\Hospital\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Doctor extends Model
{
    use SoftDeletes;

    // allow_status
    public const ALLOW_PENDING  = 'PENDING';
    public const ALLOW_APPROVED = 'APPROVED';
    public const ALLOW_REJECTED = 'REJECTED';

    // status
    public const STATUS_ACTIVE    = 'ACTIVE';
    public const STATUS_SUSPENDED = 'SUSPENDED';
    public const STATUS_WITHDRAWN = 'WITHDRAWN';

    protected $table = 'doctors';

    protected $fillable = [
        'hospital_id',
        'sort_order',
        'name',
        'gender',
        'position',
        'profile_image_path',
        'career_started_at',
        'license_number',
        'license_image_path',
        'is_specialist',
        'specialist_certificate_image_path',
        'graduation_certificate_image_paths',
        'educations',
        'careers',
        'etc_contents',
        'etc_certificate_image_paths',
        'status',
        'allow_status',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'career_started_at' => 'date',
        'is_specialist' => 'boolean',
        'graduation_certificate_image_paths' => 'array',
        'educations' => 'array',
        'careers' => 'array',
        'etc_contents' => 'array',
        'etc_certificate_image_paths' => 'array',
    ];

    protected $attributes = [
        'sort_order' => 0,
        'is_specialist' => false,
        'status' => self::STATUS_SUSPENDED,
        'allow_status' => self::ALLOW_PENDING,
    ];

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }
}
