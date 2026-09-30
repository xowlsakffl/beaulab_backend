<?php

namespace App\Domains\RemoteConsultation\Actions;

use App\Common\Support\PaginatedResponse;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\RemoteConsultation\Dto\RemoteConsultationForStaffDto as Dto;
use App\Domains\RemoteConsultation\Models\RemoteConsultation;
use App\Domains\RemoteConsultation\Queries\RemoteConsultationForStaffQuery;
use Illuminate\Support\Facades\Gate;

final class RemoteConsultationReadForStaffAction
{
    public function __construct(private readonly RemoteConsultationForStaffQuery $query) {}

    public function index(array $filters): array
    {
        Gate::authorize('viewAny', RemoteConsultation::class);

        return PaginatedResponse::fromPaginator($this->query->paginate($filters), fn ($c) => Dto::row($c));
    }

    public function show(int $id): array
    {
        Gate::authorize('viewAny', RemoteConsultation::class);
        $c = $this->query->detail($id);
        Gate::authorize('view', $c);
        $requested = Hospital::withTrashed()->whereIn('id', $c->requested_hospital_ids ?? [])->get(['id', 'name'])->keyBy('id');

        return Dto::detail($c, collect($c->requested_hospital_ids ?? [])->map(fn ($id) => ['id' => $id, 'name' => $requested->get($id)?->name ?? '삭제된 병원'])->all());
    }

    public function quote(int $id, int $quoteId): array
    {
        Gate::authorize('viewAny', RemoteConsultation::class);
        $c = RemoteConsultation::findOrFail($id);
        Gate::authorize('view', $c);

        return Dto::quote($c->quotes()->findOrFail($quoteId));
    }
}
