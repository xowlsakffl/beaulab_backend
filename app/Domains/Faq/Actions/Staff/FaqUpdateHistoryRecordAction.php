<?php

namespace App\Domains\Faq\Actions\Staff;

use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\Faq\Models\Faq;
use Illuminate\Database\Eloquent\Model;

final class FaqUpdateHistoryRecordAction
{
    public function __construct(
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    /**
     * @return array<string, array{label:string,value:mixed,display:?string}>
     */
    public function capture(Faq $faq): array
    {
        $faq->loadMissing('categories');

        return [
            'channel' => $this->item('노출 대상', $faq->channel, $faq->channel),
            'question' => $this->item('질문', $faq->question, $faq->question),
            'content' => $this->item('답변', $faq->content, $faq->content),
            'status' => $this->item('노출여부', $faq->status, $faq->status),
            'sort_order' => $this->item('정렬순서', (int) $faq->sort_order, (string) (int) $faq->sort_order),
            'categories' => $this->item('카테고리', $this->categoryValue($faq), $this->categoryDisplay($faq)),
        ];
    }

    public function recordCreated(Faq $faq): void
    {
        $this->record($faq, OperationHistory::ACTION_CREATED, 'staff.faq.create', OperationHistoryChangeSetBuilder::single(
            key: 'created',
            label: '생성',
            before: null,
            after: OperationHistory::ACTION_CREATED,
            beforeDisplay: null,
            afterDisplay: '생성',
        ));
    }

    /**
     * @param array<string, array{label:string,value:mixed,display:?string}> $before
     */
    public function recordUpdated(Faq $faq, array $before): void
    {
        $this->record($faq, OperationHistory::ACTION_UPDATED, 'staff.faq.update', OperationHistoryChangeSetBuilder::fromSnapshots($before, $this->capture($faq)));
    }

    /**
     * @return array{label:string,value:mixed,display:?string}
     */
    private function item(string $label, mixed $value, ?string $display): array
    {
        return compact('label', 'value', 'display');
    }

    /**
     * @return array<int, array{id:int,path:string,is_primary:bool}>
     */
    private function categoryValue(Faq $faq): array
    {
        return $faq->categories
            ->map(static fn ($category): array => [
                'id' => (int) $category->id,
                'path' => (string) ($category->full_path ?: $category->name),
                'is_primary' => (bool) ($category->pivot?->is_primary ?? false),
            ])
            ->sortBy('path')
            ->values()
            ->all();
    }

    private function categoryDisplay(Faq $faq): ?string
    {
        return $this->lineList(collect($this->categoryValue($faq))
            ->map(static fn (array $category): string => ($category['is_primary'] ? '[대표] ' : '').$category['path'])
            ->all());
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
    private function record(Faq $faq, string $action, string $source, array $changes): void
    {
        if ($changes === []) {
            return;
        }

        $actor = auth()->user();

        $this->historyCreateAction->execute(
            target: $faq,
            action: $action,
            actor: $actor instanceof Model ? $actor : null,
            reason: null,
            metadata: ['source' => $source],
            changes: $changes,
        );
    }
}
