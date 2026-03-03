<?php

namespace App\Domains\BeautyExpert\Actions\Staff;

use App\Domains\Common\Actions\Media\MediaAttachAction;
use App\Domains\Common\Models\Media\Media;
use App\Domains\BeautyExpert\Dto\Staff\BeautyExpertForStaffDetailDto;
use App\Domains\BeautyExpert\Models\BeautyExpert;
use App\Domains\BeautyExpert\Queries\Staff\BeautyExpertUpdateForStaffQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

final class BeautyExpertUpdateForStaffAction
{
    public function __construct(
        private readonly BeautyExpertUpdateForStaffQuery $query,
        private readonly MediaAttachAction $mediaAttachAction,
    ) {}

    public function execute(BeautyExpert $expert, array $payload): array
    {
        Gate::authorize('update', $expert);

        $expert = DB::transaction(function () use ($expert, $payload) {
            $updated = $this->query->update($expert, $payload);
            $this->replaceMedia($updated, $payload);
            return $updated->fresh();
        });

        return [
            'expert' => BeautyExpertForStaffDetailDto::fromModel($expert->load([
                'profileImage',
                'educationCertificateImages',
                'etcCertificateImages',
            ]))->toArray(),
        ];
    }

    private function replaceMedia(BeautyExpert $expert, array $payload): void
    {
        if (($payload['profile_image'] ?? null) instanceof UploadedFile) {
            $this->deleteCollectionMedia($expert, 'profile_image');
            $this->mediaAttachAction->attachExpertProfileImage($expert, $payload['profile_image'], 'expert');
        }

        if (array_key_exists('education_certificate_image', $payload) && is_array($payload['education_certificate_image'])) {
            $this->deleteCollectionMedia($expert, 'education_certificate_image');
            $this->mediaAttachAction->attachExpertEducationCertificateImages($expert, $this->onlyFiles($payload['education_certificate_image']), 'expert');
        }

        if (array_key_exists('etc_certificate_image', $payload) && is_array($payload['etc_certificate_image'])) {
            $this->deleteCollectionMedia($expert, 'etc_certificate_image');
            $this->mediaAttachAction->attachExpertEtcCertificateImages($expert, $this->onlyFiles($payload['etc_certificate_image']), 'expert');
        }
    }

    private function deleteCollectionMedia(BeautyExpert $expert, string $collection): void
    {
        Media::query()->for($expert)->collection($collection)->get()->each(function (Media $media): void {
            Storage::disk($media->disk)->delete($media->path);
            $media->delete();
        });
    }

    private function onlyFiles(array $files): array
    {
        return array_values(array_filter($files, static fn ($file): bool => $file instanceof UploadedFile));
    }
}
