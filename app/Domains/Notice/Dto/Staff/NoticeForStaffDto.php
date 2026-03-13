<?php

namespace App\Domains\Notice\Dto\Staff;

use App\Domains\Notice\Models\Notice;

final readonly class NoticeForStaffDto
{
    public function __construct(public array $notice) {}

    public static function fromModel(Notice $notice): self
    {
        return new self([
            'id' => (int) $notice->id,
            'channel' => (string) $notice->channel,
            'title' => (string) $notice->title,
            'status' => (string) $notice->status,
            'is_pinned' => (bool) $notice->is_pinned,
            'pinned_order' => (int) $notice->pinned_order,
            'is_publish_period_unlimited' => (bool) $notice->is_publish_period_unlimited,
            'publish_start_at' => $notice->publish_start_at?->toISOString(),
            'publish_end_at' => $notice->publish_end_at?->toISOString(),
            'is_important' => (bool) $notice->is_important,
            'view_count' => (int) $notice->view_count,
            'attachments_count' => (int) ($notice->attachments_count ?? 0),
            'exposure_status' => $notice->exposureStatus(),
            'created_by_staff_id' => $notice->created_by_staff_id ? (int) $notice->created_by_staff_id : null,
            'updated_by_staff_id' => $notice->updated_by_staff_id ? (int) $notice->updated_by_staff_id : null,
            'created_at' => $notice->created_at?->toISOString(),
            'updated_at' => $notice->updated_at?->toISOString(),
        ]);
    }

    public function toArray(): array
    {
        return $this->notice;
    }
}
