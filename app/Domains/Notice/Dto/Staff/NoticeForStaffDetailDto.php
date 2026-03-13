<?php

namespace App\Domains\Notice\Dto\Staff;

use App\Domains\Common\Models\Media\Media;
use App\Domains\Notice\Models\Notice;
use Illuminate\Support\Collection;

final readonly class NoticeForStaffDetailDto
{
    public function __construct(public array $notice) {}

    public static function fromModel(Notice $notice): self
    {
        $data = NoticeForStaffDto::fromModel($notice)->toArray();

        $data['content'] = (string) $notice->content;
        $data['attachments'] = self::resolveAttachments($notice)
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

        $data['creator'] = $notice->relationLoaded('creator') && $notice->creator
            ? [
                'id' => (int) $notice->creator->id,
                'name' => (string) $notice->creator->name,
                'email' => (string) $notice->creator->email,
            ]
            : null;

        $data['updater'] = $notice->relationLoaded('updater') && $notice->updater
            ? [
                'id' => (int) $notice->updater->id,
                'name' => (string) $notice->updater->name,
                'email' => (string) $notice->updater->email,
            ]
            : null;

        return new self($data);
    }

    public function toArray(): array
    {
        return $this->notice;
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
