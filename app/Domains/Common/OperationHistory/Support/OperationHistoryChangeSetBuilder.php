<?php

namespace App\Domains\Common\OperationHistory\Support;

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
