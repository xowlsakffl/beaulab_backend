<?php

namespace App\Domains\BeautyExpert\Actions\Staff;

use App\Domains\Common\Actions\Media\MediaAttachAction;
use App\Domains\BeautyExpert\Dto\Staff\BeautyExpertForStaffDetailDto;
use App\Domains\BeautyExpert\Models\BeautyExpert;
use App\Domains\BeautyExpert\Queries\Staff\BeautyExpertCreateForStaffQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class BeautyExpertCreateForStaffAction
{
    public function __construct(
        private readonly BeautyExpertCreateForStaffQuery $query,
        private readonly MediaAttachAction               $mediaAttachAction,
    ) {}

    public function execute(array $payload): array
    {
        Gate::authorize('create', BeautyExpert::class);

        $expert = DB::transaction(function () use ($payload) {
            $expert = $this->query->create($payload);

            $this->attachMedia($expert, $payload);

            return $expert->fresh();
        });

        return [
            'expert' => BeautyExpertForStaffDetailDto::fromModel($expert->load([
                'profileImage',
                'educationCertificateImages',
                'etcCertificateImages',
            ]))->toArray(),
        ];
    }

    private function attachMedia(BeautyExpert $expert, array $payload): void
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
