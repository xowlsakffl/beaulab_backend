<?php

namespace App\Domains\Notice\Actions\Staff;

use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
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
            'channel' => $this->item('노출 대상', $notice->channel, $notice->channel),
            'title' => $this->item('제목', $notice->title, $notice->title),
            'content' => $this->item('내용', $notice->content, $notice->content),
            'status' => $this->item('노출여부', $notice->status, $notice->status),
            'is_pinned' => $this->item('상단 고정', (bool) $notice->is_pinned, (bool) $notice->is_pinned ? '예' : '아니오'),
            'is_important' => $this->item('중요 공지', (bool) $notice->is_important, (bool) $notice->is_important ? '예' : '아니오'),
            'publish_period' => $this->item('게시기간', [
                'is_unlimited' => (bool) $notice->is_publish_period_unlimited,
                'start' => $notice->publish_start_at?->toDateString(),
                'end' => $notice->publish_end_at?->toDateString(),
            ], $this->periodLabel($notice)),
            'attachments' => $this->item('첨부파일', $this->attachmentValue($notice), $this->attachmentDisplay($notice)),
        ];
    }

    public function recordCreated(Notice $notice): void
    {
        $this->record($notice, OperationHistory::ACTION_CREATED, 'staff.notice.create', []);
    }

    /**
     * @param array<string, array{label:string,value:mixed,display:?string}> $before
     */
    public function recordUpdated(Notice $notice, array $before): void
    {
        $this->record($notice, OperationHistory::ACTION_UPDATED, 'staff.notice.update', OperationHistoryChangeSetBuilder::fromSnapshots($before, $this->capture($notice)));
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
        return $this->lineList(collect($this->attachmentValue($notice))
            ->map(static fn (array $media): string => basename($media['path']))
            ->all());
    }

    private function periodLabel(Notice $notice): string
    {
        $startAt = $notice->publish_start_at?->format('y.m.d') ?? '-';
        if ((bool) $notice->is_publish_period_unlimited) {
            return "{$startAt} ~ 무기한";
        }

        return "{$startAt} ~ ".($notice->publish_end_at?->format('y.m.d') ?? '-');
    }

    /**
     * @param array<int, mixed> $items
     */
    private function lineList(array $items): ?string
    {
        $items = collect($items)
            ->map(static fn (mixed $item): string => trim((string) $item))
            ->filter(static fn (string $item): bool => $item !== '')
            ->values()
            ->all();

        return $items === [] ? null : implode("\n", $items);
    }

    /**
     * @param array<int, array<string, mixed>> $changes
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
