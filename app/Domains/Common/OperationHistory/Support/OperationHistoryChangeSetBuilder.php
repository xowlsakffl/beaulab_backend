<?php

namespace App\Domains\Common\OperationHistory\Support;

use App\Domains\Common\OperationHistory\Models\OperationHistory;

/**
 * OperationHistoryChangeSetBuilder 역할 정의.
 * 도메인별 변경 전/후 값을 표준 operation_history_changes payload로 변환한다.
 */
final class OperationHistoryChangeSetBuilder
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $changes = [];

    public static function make(): self
    {
        return new self;
    }

    /**
     * @param  array<string, array{label:string,value:mixed,display:?string}>  $before
     * @param  array<string, array{label:string,value:mixed,display:?string}>  $after
     * @return array<int, array<string, mixed>>
     */
    public static function fromSnapshots(array $before, array $after): array
    {
        $builder = self::make();

        foreach ($after as $key => $afterItem) {
            $beforeItem = $before[$key] ?? [
                'label' => $afterItem['label'],
                'value' => null,
                'display' => null,
            ];

            $builder->compare(
                key: $key,
                label: $afterItem['label'],
                before: $beforeItem['value'],
                after: $afterItem['value'],
                beforeDisplay: $beforeItem['display'],
                afterDisplay: $afterItem['display'],
            );
        }

        return $builder->toArray();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public static function single(
        string $key,
        string $label,
        mixed $before,
        mixed $after,
        ?string $beforeDisplay = null,
        ?string $afterDisplay = null,
    ): array {
        return self::make()
            ->compare(
                key: $key,
                label: $label,
                before: $before,
                after: $after,
                beforeDisplay: $beforeDisplay,
                afterDisplay: $afterDisplay,
            )
            ->toArray();
    }

    /**
     * @param  array<string, array{label:string,value:mixed,display:?string}>  $before
     * @param  array<string, array{label:string,value:mixed,display:?string}>  $after
     * @param  list<string>  $stateFields
     * @return array<string, list<array<string, mixed>>>
     */
    public static function groupedFromSnapshots(array $before, array $after, array $stateFields): array
    {
        $groups = [OperationHistory::ACTION_UPDATED => [], OperationHistory::ACTION_STATE_UPDATED => []];

        foreach (self::fromSnapshots($before, $after) as $change) {
            $action = in_array($change['field_key'], $stateFields, true)
                ? OperationHistory::ACTION_STATE_UPDATED
                : OperationHistory::ACTION_UPDATED;
            $groups[$action][] = $change;
        }

        return array_filter($groups, static fn (array $changes): bool => $changes !== []);
    }

    public function compare(
        string $key,
        string $label,
        mixed $before,
        mixed $after,
        ?string $beforeDisplay = null,
        ?string $afterDisplay = null,
    ): self {
        if ($this->same($before, $after)) {
            return $this;
        }

        $this->changes[] = [
            'field_key' => $key,
            'field_label' => $label,
            'before_value' => $before,
            'after_value' => $after,
            'before_display' => $beforeDisplay,
            'after_display' => $afterDisplay,
            'sort_order' => count($this->changes),
        ];

        return $this;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        return $this->changes;
    }

    private function same(mixed $before, mixed $after): bool
    {
        return json_encode($before, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            === json_encode($after, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
