<?php

declare(strict_types=1);

namespace App\Domains\BeautyBusinessRegistration\Models;

use App\Domains\Common\Models\Concerns\HasAuditLogs;
use App\Domains\Common\Models\Media\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * BeautyBusinessRegistration 역할 정의.
 * 뷰티 사업자 등록 도메인의 Eloquent 모델로, 테이블 매핑, 관계, 스코프, 상태 상수를 한곳에 모아 도메인 데이터 접근 기준을 제공한다.
 */
final class BeautyBusinessRegistration extends Model
{
    use HasAuditLogs;

    // status
    public const STATUS_ACTIVE    = 'ACTIVE';
    public const STATUS_EXPIRED = 'EXPIRED';
    public const STATUS_REVOKED = 'REVOKED';

    protected $table = 'beauty_business_registrations';

    protected $fillable = [
        'beauty_id',
        'business_number',
        'company_name',
        'ceo_name',
        'business_type',
        'business_item',
        'business_address',
        'business_address_detail',
        'issued_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'date',
        ];
    }

    public function certificateMedia(): HasOne
    {
        return $this->hasOne(Media::class, 'model_id', 'id')
            ->where('model_type', self::class)
            ->where('collection', 'business_registration_file');
    }
}
