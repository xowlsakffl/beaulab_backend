<?php

declare(strict_types=1);

namespace App\Domains\HospitalFeature\Models;

use App\Domains\Hospital\Models\Hospital;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/**
 * HospitalFeature 역할 정의.
 * 병원 특징 도메인의 Eloquent 모델로, 테이블 매핑, 관계, 스코프, 상태 상수를 한곳에 모아 도메인 데이터 접근 기준을 제공한다.
 */
final class HospitalFeature extends Model
{
    public const STATUS_ACTIVE = 'ACTIVE';
    public const STATUS_INACTIVE = 'INACTIVE';

    protected $table = 'hospital_features';

    protected $fillable = [
        'code',
        'name',
        'sort_order',
        'status',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    public function hospitals(): BelongsToMany
    {
        return $this->belongsToMany(Hospital::class, 'hospital_feature_assignments', 'hospital_feature_id', 'hospital_id')
            ->withTimestamps();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
