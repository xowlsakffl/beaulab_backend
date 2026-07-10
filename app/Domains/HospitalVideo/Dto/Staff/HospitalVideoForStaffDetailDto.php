<?php

namespace App\Domains\HospitalVideo\Dto\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\Common\Hashtag\Models\Hashtag;
use App\Domains\Common\Media\Models\Media;
use App\Domains\Common\OperationHistory\Dto\OperationHistoryDto;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\HospitalVideo\Models\HospitalVideo;

final readonly class HospitalVideoForStaffDetailDto
{
    public function __construct(
        public int $id,
        public ?array $hospital,
        public ?array $doctor,
        public ?array $managerStaff,
        public string $title,
        public ?string $description,
        public ?string $externalVideoUrl,
        public ?array $thumbnailFile,
        public string $hospitalStatus,
        public string $hospitalStatusLabel,
        public string $adminStatus,
        public string $adminStatusLabel,
        public int $viewCount,
        public int $likeCount,
        public array $reportState,
        public array $categories,
        public array $hashtags,
        public array $operationHistories,
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?string $deletedAt,
    ) {}

    public static function fromModel(HospitalVideo $video): self
    {
        $hospitalStatus = (string) $video->hospital_status;
        $adminStatus = (string) $video->admin_status;

        return new self(
            id: (int) $video->id,
            hospital: self::hospital($video),
            doctor: self::doctor($video),
            managerStaff: self::managerStaff($video),
            title: (string) $video->title,
            description: $video->description,
            externalVideoUrl: $video->external_video_url,
            thumbnailFile: self::thumbnailFile($video),
            hospitalStatus: $hospitalStatus,
            hospitalStatusLabel: HospitalVideo::hospitalStatusLabel($hospitalStatus),
            adminStatus: $adminStatus,
            adminStatusLabel: HospitalVideo::adminStatusLabel($adminStatus),
            viewCount: (int) $video->view_count,
            likeCount: (int) $video->like_count,
            reportState: self::reportState($video),
            categories: self::categories($video),
            hashtags: self::hashtags($video),
            operationHistories: self::operationHistories($video),
            createdAt: $video->created_at?->toISOString(),
            updatedAt: $video->updated_at?->toISOString(),
            deletedAt: $video->deleted_at?->toISOString(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'hospital' => $this->hospital,
            'doctor' => $this->doctor,
            'manager_staff' => $this->managerStaff,
            'title' => $this->title,
            'description' => $this->description,
            'external_video_url' => $this->externalVideoUrl,
            'thumbnail_file' => $this->thumbnailFile,
            'hospital_status' => $this->hospitalStatus,
            'hospital_status_label' => $this->hospitalStatusLabel,
            'admin_status' => $this->adminStatus,
            'admin_status_label' => $this->adminStatusLabel,
            'view_count' => $this->viewCount,
            'like_count' => $this->likeCount,
            'report_state' => $this->reportState,
            'categories' => $this->categories,
            'hashtags' => $this->hashtags,
            'operation_histories' => $this->operationHistories,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->deletedAt,
        ];
    }

    private static function hospital(HospitalVideo $video): ?array
    {
        if (! $video->relationLoaded('hospital') || ! $video->hospital) {
            return null;
        }

        $businessNumber = null;
        if ($video->hospital->relationLoaded('businessRegistration') && $video->hospital->businessRegistration) {
            $businessNumber = $video->hospital->businessRegistration->business_number;
        }

        return [
            'id' => (int) $video->hospital->id,
            'name' => (string) $video->hospital->name,
            'business_number' => $businessNumber,
        ];
    }

    private static function doctor(HospitalVideo $video): ?array
    {
        if (! $video->relationLoaded('doctor') || ! $video->doctor) {
            return null;
        }

        $attributes = $video->doctor->getAttributes();

        return [
            'id' => (int) $video->doctor->getKey(),
            'name' => (string) ($attributes['name'] ?? ''),
            'position' => $attributes['position'] ?? null,
        ];
    }

    private static function managerStaff(HospitalVideo $video): ?array
    {
        if (! $video->relationLoaded('managerStaff') || ! $video->managerStaff) {
            return null;
        }

        $attributes = $video->managerStaff->getAttributes();

        return [
            'id' => (int) $video->managerStaff->getKey(),
            'name' => (string) ($attributes['name'] ?? ''),
            'email' => isset($attributes['email']) && trim((string) $attributes['email']) !== ''
                ? (string) $attributes['email']
                : null,
        ];
    }

    private static function categories(HospitalVideo $video): array
    {
        if (! $video->relationLoaded('categories')) {
            return [];
        }

        return $video->categories
            ->map(fn (Category $category): array => [
                'id' => (int) $category->id,
                'code' => (string) ($category->code ?? ''),
                'domain' => (string) ($category->domain ?? ''),
                'name' => (string) $category->name,
                'full_path' => (string) ($category->full_path ?? ''),
            ])
            ->values()
            ->all();
    }

    private static function hashtags(HospitalVideo $video): array
    {
        if (! $video->relationLoaded('hashtags')) {
            return [];
        }

        return $video->hashtags
            ->map(fn (Hashtag $hashtag): array => [
                'id' => (int) $hashtag->id,
                'name' => (string) $hashtag->name,
                'sort_order' => (int) ($hashtag->pivot?->sort_order ?? 0),
            ])
            ->values()
            ->all();
    }

    private static function thumbnailFile(HospitalVideo $video): ?array
    {
        if (! $video->relationLoaded('thumbnailMedia')) {
            return null;
        }

        return self::media($video->thumbnailMedia);
    }

    private static function reportState(HospitalVideo $video): array
    {
        $state = $video->relationLoaded('contentReportState') ? $video->contentReportState : null;

        if (! $state instanceof ContentReportState) {
            return [
                'status' => ContentReportState::STATUS_NONE,
                'label' => '없음',
                'report_count' => 0,
            ];
        }

        return [
            'status' => (string) $state->report_status,
            'label' => $state->statusLabel(),
            'report_count' => (int) $state->report_count,
            'process_reason' => $state->process_reason,
            'updated_at' => $state->updated_at?->toISOString(),
        ];
    }

    private static function operationHistories(HospitalVideo $video): array
    {
        if (! $video->relationLoaded('operationHistories')) {
            return [];
        }

        return $video->operationHistories
            ->map(fn (OperationHistory $history): array => OperationHistoryDto::fromModel($history)->toArray())
            ->values()
            ->all();
    }

    private static function media(?Media $media): ?array
    {
        if (! $media) {
            return null;
        }

        return [
            'id' => (int) $media->id,
            'collection' => (string) $media->collection,
            'disk' => (string) $media->disk,
            'path' => (string) $media->path,
            'mime_type' => $media->mime_type,
            'size' => $media->size !== null ? (int) $media->size : null,
            'width' => $media->width !== null ? (int) $media->width : null,
            'height' => $media->height !== null ? (int) $media->height : null,
            'sort_order' => (int) $media->sort_order,
            'is_primary' => (bool) $media->is_primary,
            'metadata' => $media->metadata,
            'created_at' => $media->created_at?->toISOString(),
            'updated_at' => $media->updated_at?->toISOString(),
        ];
    }
}
