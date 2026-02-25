<?php

namespace App\Domains\Expert\Actions\Staff;

use App\Domains\Expert\Models\Expert;
use App\Domains\Expert\Queries\Staff\ExpertDeleteForStaffQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class ExpertDeleteForStaffAction
{
    public function __construct(private readonly ExpertDeleteForStaffQuery $query) {}

    public function execute(Expert $expert): array
    {
        Gate::authorize('delete', $expert);

        return DB::transaction(function () use ($expert) {
            $this->query->softDelete($expert);
            $expert->refresh();

            return [
                'deleted_id' => (int) $expert->id,
                'deleted_at' => optional($expert->deleted_at)?->toISOString(),
            ];
        });
    }
}
