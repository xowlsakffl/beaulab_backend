<?php

declare(strict_types=1);

namespace App\Domains\Common\ContentReport\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\ContentReport\Dto\Staff\ReportedContentSummaryForStaffDto;
use App\Domains\Common\ContentReport\Queries\Staff\ReportedContentSummaryForStaffQuery;
use App\Domains\Common\ContentReport\Support\ContentReportTargetRegistry;
use Illuminate\Support\Facades\Gate;

final class ReportedContentSummaryForStaffAction
{
    public function __construct(
        private readonly ReportedContentSummaryForStaffQuery $query,
    ) {}

    /**
     * @return array<string, int>
     */
    public function execute(string $targetAlias, array $filters = []): array
    {
        $targetClass = ContentReportTargetRegistry::classForAlias($targetAlias);

        if ($targetClass === null) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '지원하지 않는 신고 대상입니다.');
        }

        Gate::authorize('viewAny', $targetClass);

        return ReportedContentSummaryForStaffDto::fromArray($this->query->get($targetClass, $filters))->toArray();
    }
}
