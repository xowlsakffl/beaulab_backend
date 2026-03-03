<?php

namespace App\Domains\HospitalVideoRequest\Actions\Hospital;

use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\HospitalVideoRequest\Dto\Hospital\HospitalVideoRequestForHospitalDto;
use App\Domains\HospitalVideoRequest\Models\HospitalVideoRequest;
use App\Domains\HospitalVideoRequest\Queries\Hospital\HospitalVideoRequestListForHospitalQuery;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

final class HospitalVideoRequestListForHospitalAction
{
    public function __construct(private readonly HospitalVideoRequestListForHospitalQuery $query) {}

    public function execute(array $filters): array
    {
        Gate::authorize('viewAny', HospitalVideoRequest::class);

        /** @var AccountHospital $partner */
        $partner = Auth::user();

        $paginator = $this->query->paginate($partner, $filters);

        return [
            'items' => collect($paginator->items())
                ->map(fn ($videoRequest) => HospitalVideoRequestForHospitalDto::fromModel($videoRequest)->toArray())
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }
}
