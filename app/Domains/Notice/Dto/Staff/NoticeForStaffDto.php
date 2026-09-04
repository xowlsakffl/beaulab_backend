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
        public int $viewCount,
        public ?array $creator,
        public ?string $createdAt,
    ) {}

    public static function fromModel(Notice $notice): self
    {
        return new self(
            id: (int) $notice->id,
            channel: (string) $notice->channel,
            title: (string) $notice->title,
            status: (string) $notice->status,
            viewCount: (int) $notice->view_count,
            creator: self::creator($notice),
            createdAt: $notice->created_at?->toISOString(),
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'channel' => $this->channel,
            'title' => $this->title,
            'status' => $this->status,
            'view_count' => $this->viewCount,
            'creator' => $this->creator,
            'created_at' => $this->createdAt,
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
}
