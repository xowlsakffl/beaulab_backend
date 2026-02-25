<?php

namespace App\Domains\Expert\Actions\Staff;

use App\Domains\Common\Actions\Media\MediaAttachAction;
use App\Domains\Expert\Dto\Staff\ExpertForStaffDetailDto;
use App\Domains\Expert\Models\Expert;
use App\Domains\Expert\Queries\Staff\ExpertCreateForStaffQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class ExpertCreateForStaffAction
{
    public function __construct(
        private readonly ExpertCreateForStaffQuery $query,
        private readonly MediaAttachAction $mediaAttachAction,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('create', Expert::class);

        $expert = DB::transaction(function () use ($payload) {
            $expert = $this->query->create($payload);

            $this->attachMedia($expert, $payload);

            return $expert->fresh();
        });

        return [
            'expert' => ExpertForStaffDetailDto::fromModel($expert->load([
                'profileImage',
                'educationCertificateImages',
                'etcCertificateImages',
            ]))->toArray(),
        ];
    }

    private function attachMedia(Expert $expert, array $payload): void
    {
        $this->mediaAttachAction->attachExpertProfileImage($expert, $payload['profile_image'] ?? null, 'expert');

        $this->mediaAttachAction->attachExpertEducationCertificateImages(
            $expert,
            $this->onlyFiles($payload['education_certificate_image'] ?? null),
            'expert',
        );

        $this->mediaAttachAction->attachExpertEtcCertificateImages(
            $expert,
            $this->onlyFiles($payload['etc_certificate_image'] ?? null),
            'expert',
        );
    }

    private function onlyFiles(mixed $files): array
    {
        if (! is_array($files)) {
            return [];
        }

        return array_values(array_filter($files, static fn ($file): bool => $file instanceof UploadedFile));
    }
}
