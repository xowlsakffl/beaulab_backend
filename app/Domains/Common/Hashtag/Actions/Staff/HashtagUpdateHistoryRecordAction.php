<?php

namespace App\Domains\Common\Hashtag\Actions\Staff;

use App\Domains\Common\Hashtag\Models\Hashtag;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use Illuminate\Database\Eloquent\Model;

final class HashtagUpdateHistoryRecordAction
{
    public function __construct(
        private readonly OperationHistoryCreateAction $historyCreateAction,
    ) {}

    /**
     * @return array<string, array{label:string,value:mixed,display:?string}>
     */
    public function capture(Hashtag $hashtag): array
    {
        $snapshot = [
            'name' => $this->item('해시태그명', $hashtag->name, $hashtag->name),
            'normalized_name' => $this->item('정규화명', $hashtag->normalized_name, $hashtag->normalized_name),
            'status' => $this->item('운영상태', $hashtag->resolveStatus(), Hashtag::statusLabel($hashtag->resolveStatus())),
            'usage_count' => $this->item('사용수', $hashtag->resolveUsageCount(), (string) $hashtag->resolveUsageCount()),
        ];

        return $snapshot;
    }

    public function recordCreated(Hashtag $hashtag): void
    {
        $this->record($hashtag, OperationHistory::ACTION_CREATED, 'staff.hashtag.create', []);
    }

    /**
     * @param  array<string, array{label:string,value:mixed,display:?string}>  $before
     */
    public function recordUpdated(Hashtag $hashtag, array $before): void
    {
        foreach (OperationHistoryChangeSetBuilder::groupedFromSnapshots($before, $this->capture($hashtag), ['status']) as $action => $changes) {
            $this->record($hashtag, $action, 'staff.hashtag.update', $changes);
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
     * @param  array<int, array<string, mixed>>  $changes
     */
    private function record(Hashtag $hashtag, string $action, string $source, array $changes): void
    {
        if ($changes === [] && $action !== OperationHistory::ACTION_CREATED) {
            return;
        }

        $actor = auth()->user();

        $this->historyCreateAction->execute(
            target: $hashtag,
            action: $action,
            actor: $actor instanceof Model ? $actor : null,
            reason: null,
            metadata: ['source' => $source],
            changes: $changes,
        );
    }
}
