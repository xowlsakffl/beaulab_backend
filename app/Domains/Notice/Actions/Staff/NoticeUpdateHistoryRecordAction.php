<?php

namespace App\Domains\Notice\Actions\Staff;

use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\Common\OperationHistory\Support\OperationHistoryDisplayValue;
use App\Domains\Notice\Models\Notice;
use Illuminate\Database\Eloquent\Model;

final class NoticeUpdateHistoryRecordAction
{
    public function __construct(
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    /**
     * @return array<string, array{label:string,value:mixed,display:?string}>
     */
    public function capture(Notice $notice): array
    {
        $notice->loadMissing('attachments');

        return [
            'channel' => $this->item('채널', $notice->channel, match ($notice->channel) {
                Notice::CHANNEL_ALL => '전체 채널',
                Notice::CHANNEL_APP_WEB => '앱/웹',
                Notice::CHANNEL_HOSPITAL => '병의원',
                Notice::CHANNEL_BEAUTY => '뷰티',
                default => $notice->channel,
            }),
            'title' => $this->item('제목', $notice->title, $notice->title),
            'content' => $this->item('내용', $notice->content, $notice->content),
            'status' => $this->item('공개여부', $notice->status, $notice->status === Notice::STATUS_ACTIVE ? '공개' : '비공개'),
            'is_pinned' => $this->item('상단공지', (bool) $notice->is_pinned, (bool) $notice->is_pinned ? '예' : '아니오'),
            'attachments' => $this->item('첨부파일', $this->attachmentValue($notice), $this->attachmentDisplay($notice)),
        ];
    }

    public function recordCreated(Notice $notice): void
    {
        $this->record($notice, OperationHistory::ACTION_CREATED, 'staff.notice.create', []);
    }

    /**
     * @param  array<string, array{label:string,value:mixed,display:?string}>  $before
     */
    public function recordUpdated(Notice $notice, array $before): void
    {
        foreach (OperationHistoryChangeSetBuilder::groupedFromSnapshots($before, $this->capture($notice), ['status']) as $action => $changes) {
            $this->record($notice, $action, 'staff.notice.update', $changes);
        }
    }

    /**
     * @return array{label:string,value:mixed,display:?string}
     */
    private function item(string $label, mixed $value, ?string $display): array
    {
        return compact('label', 'value', 'display');
    }

    /**
     * @return array<int, array{id:int,path:string}>
     */
    private function attachmentValue(Notice $notice): array
    {
        return $notice->attachments
            ->map(static fn ($media): array => [
                'id' => (int) $media->id,
                'path' => (string) $media->path,
            ])
            ->values()
            ->all();
    }

    private function attachmentDisplay(Notice $notice): ?string
    {
        return OperationHistoryDisplayValue::lines(collect($this->attachmentValue($notice))
            ->map(static fn (array $media): string => OperationHistoryDisplayValue::fileName($media['path']))
            ->all());
    }

    /**
     * @param  array<int, array<string, mixed>>  $changes
     */
    private function record(Notice $notice, string $action, string $source, array $changes): void
    {
        if ($changes === [] && $action !== OperationHistory::ACTION_CREATED) {
            return;
        }

        $actor = auth()->user();

        $this->historyCreateAction->execute(
            target: $notice,
            action: $action,
            actor: $actor instanceof Model ? $actor : null,
            reason: null,
            metadata: ['source' => $source],
            changes: $changes,
        );
    }
}
