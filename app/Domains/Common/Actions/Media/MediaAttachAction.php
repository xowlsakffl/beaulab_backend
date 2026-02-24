<?php

namespace App\Domains\Common\Actions\Media;

use App\Domains\Common\Models\Media\Media;
use App\Domains\Common\Queries\Media\MediaAttachQuery;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class MediaAttachAction
{
    public function __construct(
        private readonly MediaAttachQuery $query,
    ) {}

    public function attachLogo(Model $owner, ?UploadedFile $file, string $dirPrefix): ?Media
    {
        if (!$file) return null;

        return $this->storeOne($owner, $file, 'logo', "{$dirPrefix}/{$owner->getKey()}/logo", false, 0);
    }

    public function attachCertificate(Model $owner, UploadedFile $file, string $dirPrefix): Media
    {
        return $this->storeOne(
            $owner,
            $file,
            'business_registration_file',
            "{$dirPrefix}/{$owner->getKey()}/business-registration",
            false,
            0,
        );
    }

    /**
     * @param array<int, UploadedFile> $files
     * @return array<int, Media>
     */
    public function attachGallery(Model $owner, array $files, string $dirPrefix): array
    {
        $out = [];
        foreach (array_values($files) as $i => $file) {
            $out[] = $this->storeOne(
                $owner,
                $file,
                'gallery',
                "{$dirPrefix}/{$owner->getKey()}/gallery",
                $i === 0,
                $i
            );
        }
        return $out;
    }

    public function attachDoctorProfileImage(Model $owner, ?UploadedFile $file, string $dirPrefix): ?Media
    {
        if (! $file) return null;

        return $this->storeOne($owner, $file, 'profile_image', "{$dirPrefix}/{$owner->getKey()}/profile-image", false, 0);
    }

    public function attachDoctorLicenseImage(Model $owner, ?UploadedFile $file, string $dirPrefix): ?Media
    {
        if (! $file) return null;

        return $this->storeOne($owner, $file, 'license_image', "{$dirPrefix}/{$owner->getKey()}/license-image", false, 0);
    }

    /** @param array<int, UploadedFile> $files */
    public function attachDoctorSpecialistCertificateImages(Model $owner, array $files, string $dirPrefix): array
    {
        return $this->storeMany($owner, $files, 'specialist_certificate_image', "{$dirPrefix}/{$owner->getKey()}/specialist-certificate-image");
    }

    /** @param array<int, UploadedFile> $files */
    public function attachDoctorGraduationCertificateImages(Model $owner, array $files, string $dirPrefix): array
    {
        return $this->storeMany($owner, $files, 'graduation_certificate_image', "{$dirPrefix}/{$owner->getKey()}/graduation-certificate-image");
    }

    /** @param array<int, UploadedFile> $files */
    public function attachDoctorEtcCertificateImages(Model $owner, array $files, string $dirPrefix): array
    {
        return $this->storeMany($owner, $files, 'etc_certificate_image', "{$dirPrefix}/{$owner->getKey()}/etc-certificate-image");
    }

    /**
     * @param array<int, UploadedFile> $files
     * @return array<int, Media>
     */
    private function storeMany(Model $owner, array $files, string $collection, string $dir): array
    {
        $out = [];
        foreach (array_values($files) as $i => $file) {
            $out[] = $this->storeOne($owner, $file, $collection, $dir, false, $i);
        }

        return $out;
    }

    private function storeOne(
        Model $owner,
        UploadedFile $file,
        string $collection,
        string $dir,
        bool $isPrimary,
        int $sortOrder,
    ): Media {
        $disk = 'public';
        $path = Storage::disk($disk)->putFile($dir, $file);

        [$w, $h] = $this->imageSize($file);

        $media = $this->query->create([
            'model_type' => $owner::class,
            'model_id' => $owner->getKey(),
            'collection' => $collection,
            'disk' => $disk,
            'path' => $path,
            'mime_type' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $w,
            'height' => $h,
            'sort_order' => max(0, $sortOrder),
            'is_primary' => false,
            'metadata' => null,
        ]);

        if ($isPrimary) {
            $media->setPrimary(true);
        }

        return $media;
    }

    /**
     * @return array{0:int|null,1:int|null}
     */
    private function imageSize(UploadedFile $file): array
    {
        try {
            $info = @getimagesize($file->getRealPath());
            if (!$info) return [null, null];
            return [(int)$info[0], (int)$info[1]];
        } catch (\Throwable) {
            return [null, null];
        }
    }
}
