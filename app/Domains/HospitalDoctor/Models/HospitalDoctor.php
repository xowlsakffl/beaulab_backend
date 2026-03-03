<?php

declare(strict_types=1);

namespace App\Domains\HospitalDoctor\Models;

use App\Domains\Common\Models\Concerns\HasAuditLogs;
use App\Domains\Common\Models\Media\Media;
use App\Domains\Hospital\Models\Hospital;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;

final class HospitalDoctor extends Model
{
    use SoftDeletes, HasAuditLogs;

    public const ALLOW_PENDING  = 'PENDING';
    public const ALLOW_APPROVED = 'APPROVED';
    public const ALLOW_REJECTED = 'REJECTED';

    // status
    public const STATUS_ACTIVE    = 'ACTIVE';
    public const STATUS_SUSPENDED = 'SUSPENDED';
    public const STATUS_INACTIVE = 'INACTIVE';

    protected $table = 'hospital_doctors';

    protected $fillable = [
        'hospital_id',
        'sort_order',
        'name',
        'gender',
        'position',
        'career_started_at',
        'license_number',
        'is_specialist',
        'educations',
        'careers',
        'etc_contents',
        'status',
        'allow_status',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_specialist' => 'boolean',
        'educations' => 'array',
        'careers' => 'array',
        'etc_contents' => 'array',
        'career_started_at' => 'date',
    ];

    public function hospital(): BelongsTo
    {
        return $this->belongsTo(Hospital::class, 'hospital_id');
    }

    public function profileImage(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')->where('collection', 'profile_image');
    }

    public function licenseImage(): MorphOne
    {
        return $this->morphOne(Media::class, 'model')->where('collection', 'license_image');
    }

    public function specialistCertificateImages(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')->where('collection', 'specialist_certificate_image')->orderBy('sort_order')->orderBy('id');
    }

    public function educationCertificateImages(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')->where('collection', 'education_certificate_image')->orderBy('sort_order')->orderBy('id');
    }

    public function etcCertificateImages(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')->where('collection', 'etc_certificate_image')->orderBy('sort_order')->orderBy('id');
    }

    public function isApproved(): bool
    {
        return $this->allow_status === self::ALLOW_APPROVED;
    }

    public function isPending(): bool
    {
        return $this->allow_status === self::ALLOW_PENDING;
    }

    public function isRejected(): bool
    {
        return $this->allow_status === self::ALLOW_REJECTED;
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    public function isSuspended(): bool
    {
        return $this->status === self::STATUS_SUSPENDED;
    }

    public function isInactive(): bool
    {
        return $this->status === self::STATUS_INACTIVE;
    }
}
