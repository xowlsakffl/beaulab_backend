<?php

namespace App\Domains\Notice\Actions\Staff;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Notice\Actions\Common\DispatchNoticePushAction;
use App\Domains\Notice\Models\Notice;
use Illuminate\Support\Facades\Gate;

final class NoticeSendPushForStaffAction
{
    public function __construct(
        private readonly DispatchNoticePushAction $dispatchNoticePushAction,
    ) {}

    public function execute(Notice $notice): array
    {
        Gate::authorize('push', $notice);

        $actor = auth()->user();
        $staffId = $actor instanceof AccountStaff ? (int) $actor->id : null;

        $notice->forceFill([
            'is_push_enabled' => true,
            'push_sent_at' => null,
            'updated_by_staff_id' => $staffId,
        ])->save();

        $this->dispatchNoticePushAction->execute($notice->fresh());

        return [
            'notice_id' => (int) $notice->id,
            'push_requested' => true,
        ];
    }
}
