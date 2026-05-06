<?php

namespace App\Domains\Notice\Dto\Staff;

use App\Domains\Notice\Models\Notice;

/**
 * NoticeForStaffDto DTO.
 */
final readonly class NoticeForStaffDto
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
        public ?array $creator,
        public ?array $updater,
        public ?string $createdAt,
        public ?string $updatedAt,
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
            creator: self::creator($notice),
            updater: self::updater($notice),
            createdAt: $notice->created_at?->toISOString(),
            updatedAt: $notice->updated_at?->toISOString(),
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
            'creator' => $this->creator,
            'updater' => $this->updater,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];

        return $data;
    }

    private static function creator(Notice $notice): ?array
    {
        if (! $notice->relationLoaded('creator') || ! $notice->creator) {
            return null;
        }

        return [
            'id' => (int) $notice->creator->id,
            'name' => (string) $notice->creator->name,
            'email' => (string) $notice->creator->email,
        ];
    }

    private static function updater(Notice $notice): ?array
    {
        if (! $notice->relationLoaded('updater') || ! $notice->updater) {
            return null;
        }

        return [
            'id' => (int) $notice->updater->id,
            'name' => (string) $notice->updater->name,
            'email' => (string) $notice->updater->email,
        ];
    }
}
