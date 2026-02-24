<?php

declare(strict_types=1);

namespace App\Domains\User\Actions\Staff;

use App\Domains\User\Dto\Staff\AccountUserForStaffDto;
use App\Domains\User\Models\AccountUser;
use App\Domains\User\Queries\Staff\AccountUserListForStaffQuery;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

final class AccountUserListForStaffAction
{
    public function __construct(
        private readonly AccountUserListForStaffQuery $query,
    ) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', AccountUser::class);

        Log::info('일반회원 목록 조회 실행', ['filters' => $filters]);

        $paginator = $this->query->paginate($filters);

        $items = collect($paginator->items())
            ->map(fn ($user) => AccountUserForStaffDto::fromModel($user)->toArray())
            ->values()
            ->all();

        return [
            'items' => $items,
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }
}
