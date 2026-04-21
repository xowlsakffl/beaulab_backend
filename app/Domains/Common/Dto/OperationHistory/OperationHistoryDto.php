<?php

namespace App\Domains\Common\Dto\OperationHistory;

use App\Domains\Common\Models\OperationHistory\OperationHistory;
use Illuminate\Database\Eloquent\Model;

/**
 * OperationHistoryDto 역할 정의.
 * 운영/시스템 액션 히스토리를 API 응답용 배열로 정규화한다.
 */
final readonly class OperationHistoryDto
{
    public function __construct(public array $history) {}

    public static function fromModel(OperationHistory $history): self
    {
        return new self([
            'id' => (int) $history->id,
            'target_type' => (string) $history->target_type,
            'target_id' => (int) $history->target_id,
            'actor_kind' => (string) $history->actor_kind,
            'actor_type' => $history->actor_type,
            'actor_id' => $history->actor_id ? (int) $history->actor_id : null,
            'actor' => self::resolveActor($history),
            'actor_label' => self::resolveActorLabel($history),
            'action' => (string) $history->action,
            'field' => $history->field,
            'before_value' => $history->before_value,
            'after_value' => $history->after_value,
            'reason' => $history->reason,
            'metadata' => $history->metadata,
            'created_at' => $history->created_at?->toISOString(),
            'updated_at' => $history->updated_at?->toISOString(),
        ]);
    }

    public function toArray(): array
    {
        return $this->history;
    }

    private static function resolveActor(OperationHistory $history): ?array
    {
        if (! $history->relationLoaded('actor') || ! $history->actor instanceof Model) {
            return null;
        }

        return [
            'id' => (int) $history->actor->getKey(),
            'name' => (string) ($history->actor->name ?? $history->actor->nickname ?? ''),
            'email' => $history->actor->email ?? null,
        ];
    }

    private static function resolveActorLabel(OperationHistory $history): string
    {
        if ($history->actor_kind === OperationHistory::ACTOR_KIND_SYSTEM) {
            return '시스템';
        }

        if ($history->relationLoaded('actor') && $history->actor instanceof Model) {
            $name = trim((string) ($history->actor->name ?? $history->actor->nickname ?? ''));

            return $name !== '' ? $name : (string) ($history->actor->email ?? $history->actor_kind);
        }

        return (string) $history->actor_kind;
    }
}
