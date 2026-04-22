<?php

namespace App\Domains\Notice\Dto\Staff;

use App\Domains\Common\Models\Media\Media;
use App\Domains\Notice\Models\Notice;
use Illuminate\Support\Collection;

/**
 * NoticeForStaffDetailDto DTO.
 */
final readonly class NoticeForStaffDetailDto
{
    public function __construct(
        public int $id,
        public string $channel,
        public string $title,
        public string $status,
        public bool $isPinned,
        public bool $isPublishPeriodUnlimited,
        public ?string $publishStartAt,
        public ?string $publishEndAt,
        public bool $isImportant,
        public int $viewCount,
        public int $attachmentsCount,
        public ?int $createdByStaffId,
        public ?string $creatorName,
        public ?int $updatedByStaffId,
        public ?string $createdAt,
        public ?string $updatedAt,
        public string $content,
        public array $attachments,
    ) {}

    public static function fromModel(Notice $notice): self
    {
        return new self(
            id: (int) $notice->id,
            channel: (string) $notice->channel,
            title: (string) $notice->title,
            status: (string) $notice->status,
            isPinned: (bool) $notice->is_pinned,
            isPublishPeriodUnlimited: (bool) $notice->is_publish_period_unlimited,
            publishStartAt: $notice->publish_start_at?->toISOString(),
            publishEndAt: $notice->publish_end_at?->toISOString(),
            isImportant: (bool) $notice->is_important,
            viewCount: (int) $notice->view_count,
            attachmentsCount: (int) ($notice->attachments_count ?? 0),
            createdByStaffId: $notice->created_by_staff_id ? (int) $notice->created_by_staff_id : null,
            creatorName: $notice->relationLoaded('creator') && $notice->creator
                ? (string) $notice->creator->name
                : null,
            updatedByStaffId: $notice->updated_by_staff_id ? (int) $notice->updated_by_staff_id : null,
            createdAt: $notice->created_at?->toISOString(),
            updatedAt: $notice->updated_at?->toISOString(),
            content: (string) $notice->content,
            attachments: self::attachments($notice),
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'channel' => $this->channel,
            'title' => $this->title,
            'status' => $this->status,
            'is_pinned' => $this->isPinned,
            'is_publish_period_unlimited' => $this->isPublishPeriodUnlimited,
            'publish_start_at' => $this->publishStartAt,
            'publish_end_at' => $this->publishEndAt,
            'is_important' => $this->isImportant,
            'view_count' => $this->viewCount,
            'attachments_count' => $this->attachmentsCount,
            'created_by_staff_id' => $this->createdByStaffId,
            'creator_name' => $this->creatorName,
            'updated_by_staff_id' => $this->updatedByStaffId,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'content' => $this->content,
            'attachments' => $this->attachments,
        ];

        return $data;
    }

    private static function attachments(Notice $notice): array
    {
        return self::resolveAttachments($notice)
            ->map(static fn (Media $media): array => [
                'id' => (int) $media->id,
                'collection' => (string) $media->collection,
                'disk' => (string) $media->disk,
                'path' => (string) $media->path,
                'mime_type' => $media->mime_type,
                'size' => $media->size,
                'width' => $media->width,
                'height' => $media->height,
                'sort_order' => (int) $media->sort_order,
                'is_primary' => (bool) $media->is_primary,
                'created_at' => $media->created_at?->toISOString(),
                'updated_at' => $media->updated_at?->toISOString(),
            ])
            ->values()
            ->all();
    }

    /**
     * @return Collection<int, Media>
     */
    private static function resolveAttachments(Notice $notice): Collection
    {
        if (! $notice->relationLoaded('attachments')) {
            return collect();
        }

        return $notice->attachments;
    }
}
