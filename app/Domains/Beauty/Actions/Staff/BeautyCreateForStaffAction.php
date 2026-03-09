<?php

namespace App\Domains\Beauty\Actions\Staff;

use App\Domains\Beauty\Dto\Staff\BeautyForStaffDetailDto;
use App\Domains\Beauty\Models\Beauty;
use App\Domains\Beauty\Queries\Staff\BeautyCreateForStaffQuery;
use App\Domains\BeautyBusinessRegistration\Actions\BeautyBusinessRegistrationCreateForStaffAction;
use App\Domains\Common\Actions\Media\MediaAttachDeleteAction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

final class BeautyCreateForStaffAction
{
    public function __construct(
        private readonly BeautyCreateForStaffQuery                      $query,
        private readonly MediaAttachDeleteAction                        $mediaAttachAction,
        private readonly BeautyBusinessRegistrationCreateForStaffAction $businessRegistrationCreateAction,
    ) {}

    /**
     * @return array{hospital: array}
     */
    public function execute(array $filters): array
    {
        Gate::authorize('create', Beauty::class);

        Log::info('뷰티 생성', [
            'filters' => array_diff_key($filters, array_flip(['owner_password'])),
        ]);

        $beauty = DB::transaction(function () use ($filters) {
            $beauty = $this->query->create([
                ...$filters,
                'email' => mb_strtolower((string) ($filters['email'] ?? '')) ?: null,
            ]);

            $this->mediaAttachAction->attachOne($beauty, $filters['logo'], 'logo', 'beauty', 'logo');
            $this->mediaAttachAction->attachMany($beauty, $filters['gallery'], 'gallery', 'beauty', 'gallery', true);

            $this->businessRegistrationCreateAction->execute($beauty, $filters);
            $this->syncCategories($beauty, $filters['category_ids'] ?? []);

            return $beauty->fresh();
        });

        return [
            'beauty' => BeautyForStaffDetailDto::fromModel(
                $beauty->load(['businessRegistration.certificateMedia', 'logoMedia', 'galleryMedia', 'categories']),
                ['business_registration'],
            )->toArray(),
        ];
    }

    /**
     * @param array<int, int|string> $categoryIds
     */
    private function syncCategories(Beauty $beauty, array $categoryIds): void
    {
        if ($categoryIds === []) {
            return;
        }

        $payload = collect($categoryIds)
            ->map(static fn (int|string $categoryId): int => (int) $categoryId)
            ->filter(static fn (int $categoryId): bool => $categoryId > 0)
            ->unique()
            ->values()
            ->mapWithKeys(static fn (int $categoryId, int $index): array => [
                $categoryId => ['is_primary' => $index === 0],
            ])
            ->all();

        if ($payload === []) {
            return;
        }

        $beauty->categories()->sync($payload);
    }
}
