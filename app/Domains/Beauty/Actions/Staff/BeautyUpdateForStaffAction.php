<?php

namespace App\Domains\Beauty\Actions\Staff;

use App\Domains\Common\Actions\Media\MediaAttachAction;
use App\Domains\Common\Models\Media\Media;
use App\Domains\Beauty\Dto\Staff\BeautyForStaffDetailDto;
use App\Domains\Beauty\Models\Beauty;
use App\Domains\Beauty\Queries\Staff\BeautyUpdateForStaffQuery;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

final class BeautyUpdateForStaffAction
{
    public function __construct(
        private readonly BeautyUpdateForStaffQuery $query,
        private readonly MediaAttachAction $mediaAttachAction,
    ) {}

    /**
     * @return array{beauty: array}
     */
    public function execute(Beauty $beauty, array $payload): array
    {
        Gate::authorize('update', $beauty);

        Log::info('뷰티업체 정보 수정 실행', [
            'hospital_id' => $beauty->id,
        ]);

        $updated = DB::transaction(function () use ($beauty, $payload) {
            $updatedBeauty = $this->query->update($beauty, $payload);

            $this->replaceMedia($updatedBeauty, $payload);
            $this->updateBusinessRegistration($updatedBeauty, $payload);

            return $updatedBeauty->fresh();
        });

        return [
            'beauty' => BeautyForStaffDetailDto::fromModel($updated->load(['businessRegistration.certificateMedia', 'logoMedia', 'galleryMedia']))->toArray(),
        ];
    }

    private function replaceMedia(Beauty $beauty, array $payload): void
    {
        if (isset($payload['logo']) && $payload['logo'] instanceof UploadedFile) {
            $this->deleteCollectionMedia($beauty, 'logo');
            $this->mediaAttachAction->attachLogo($beauty, $payload['logo'], 'beauty');
        }

        if (isset($payload['gallery']) && is_array($payload['gallery'])) {
            $galleryFiles = array_values(array_filter(
                $payload['gallery'],
                static fn ($file): bool => $file instanceof UploadedFile,
            ));

            if ($galleryFiles !== []) {
                $this->deleteCollectionMedia($beauty, 'gallery');
                $this->mediaAttachAction->attachGallery($beauty, $galleryFiles, 'beauty');
            }
        }
    }

    private function updateBusinessRegistration(Beauty $beauty, array $payload): void
    {
        $businessRegistration = $beauty->businessRegistration()->first();
        if (! $businessRegistration) {
            return;
        }

        $updates = [];
        foreach ([
                     'business_number',
                     'company_name',
                     'ceo_name',
                     'business_type',
                     'business_item',
                     'business_address',
                     'business_address_detail',
                 ] as $field) {
            if (array_key_exists($field, $payload)) {
                $updates[$field] = $payload[$field];
            }
        }

        if ($updates !== []) {
            $businessRegistration->update($updates);
        }

        if (isset($payload['business_registration_file']) && $payload['business_registration_file'] instanceof UploadedFile) {
            $existingCertificate = $businessRegistration->certificateMedia()->first();
            if ($existingCertificate) {
                Storage::disk($existingCertificate->disk)->delete($existingCertificate->path);
                $existingCertificate->delete();
            }

            $newCertificate = $this->mediaAttachAction->attachCertificate(
                $beauty,
                $payload['business_registration_file'],
                'beauty',
            );

            $businessRegistration->update([
                'certificate_media_id' => $newCertificate->id,
            ]);
        }
    }

    private function deleteCollectionMedia(Beauty $beauty, string $collection): void
    {
        Media::query()
            ->for($beauty)
            ->collection($collection)
            ->get()
            ->each(function (Media $media): void {
                Storage::disk($media->disk)->delete($media->path);
                $media->delete();
            });
    }
}
