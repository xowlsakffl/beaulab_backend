<?php

namespace App\Domains\Faq\Actions\Staff;

use App\Domains\Faq\Dto\Staff\FaqForStaffDetailDto;
use App\Domains\Faq\Models\Faq;
use Illuminate\Support\Facades\Gate;

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
