<?php

namespace App\Domains\Faq\Actions\Staff;

use App\Domains\Faq\Dto\Staff\FaqForStaffDetailDto;
use App\Domains\Faq\Models\Faq;
use Illuminate\Support\Facades\Gate;

/**
 * FaqGetForStaffAction 역할 정의.
 * FAQ 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class FaqGetForStaffAction
{
    public function execute(Faq $faq): array
    {
        Gate::authorize('view', $faq);

        $loaded = $faq->load([
            'categories:id,name,domain,status,sort_order',
            'creator:id,name,email',
            'updater:id,name,email',
        ]);

        return [
            'faq' => FaqForStaffDetailDto::fromModel($loaded)->toArray(),
        ];
    }
}
