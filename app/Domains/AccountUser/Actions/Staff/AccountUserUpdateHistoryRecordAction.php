<?php

namespace App\Domains\AccountUser\Actions\Staff;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use Illuminate\Database\Eloquent\Model;

final class AccountUserUpdateHistoryRecordAction
{
    public function __construct(
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    /**
     * @return array<string, array{label:string,value:mixed,display:?string}>
     */
    public function capture(AccountUser $user): array
    {
        return [
            'name' => $this->item('이름', $user->name, $user->name),
            'nickname' => $this->item('닉네임', $user->nickname, $user->nickname),
            'phone' => $this->item('전화번호', $user->phone, $user->phone),
            'status' => $this->item('회원상태', $user->status, AccountUser::statusLabels()[$user->status] ?? $user->status),
            'blocked_at' => $this->item('차단일시', $user->blocked_at?->format('Y-m-d H:i:s'), $user->blocked_at?->format('Y-m-d H:i:s')),
            'comment_notification_enabled' => $this->boolean('댓글 알림', $user->comment_notification_enabled),
            'note_notification_enabled' => $this->boolean('쪽지 알림', $user->note_notification_enabled),
            'marketing_sms_agreed' => $this->boolean('마케팅 SMS', $user->marketing_sms_agreed),
            'marketing_email_agreed' => $this->boolean('마케팅 이메일', $user->marketing_email_agreed),
            'marketing_push_agreed' => $this->boolean('마케팅 푸시', $user->marketing_push_agreed),
            'marketing_night_push_agreed' => $this->boolean('야간 푸시', $user->marketing_night_push_agreed),
        ];
    }

    /**
     * @param array<string, array{label:string,value:mixed,display:?string}> $before
     */
    public function recordUpdated(AccountUser $user, array $before): void
    {
        $changes = OperationHistoryChangeSetBuilder::fromSnapshots($before, $this->capture($user));
        if ($changes === []) {
            return;
        }

        $actor = auth()->user();

        $this->historyCreateAction->execute(
            target: $user,
            action: OperationHistory::ACTION_UPDATED,
            actor: $actor instanceof Model ? $actor : null,
            reason: null,
            metadata: ['source' => 'staff.account_user.update'],
            changes: $changes,
        );
    }

    /**
     * @return array{label:string,value:mixed,display:?string}
     */
    private function boolean(string $label, mixed $value): array
    {
        return $this->item($label, (bool) $value, (bool) $value ? '동의' : '동의안함');
    }

    /**
     * @return array{label:string,value:mixed,display:?string}
     */
    private function item(string $label, mixed $value, ?string $display): array
    {
        return compact('label', 'value', 'display');
    }
}
