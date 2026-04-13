<?php

declare(strict_types=1);

namespace App\Domains\Common\Models\Concerns;

use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * HasAuditLogs 역할 정의.
 * 공통 도메인의 Eloquent 모델로, 테이블 매핑, 관계, 스코프, 상태 상수를 한곳에 모아 도메인 데이터 접근 기준을 제공한다.
 */
trait HasAuditLogs
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->useLogName('audit')
            ->logFillable()
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}

