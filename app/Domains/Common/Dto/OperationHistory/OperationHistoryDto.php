<?php

namespace App\Domains\Common\Dto\OperationHistory;

use App\Domains\Common\Models\OperationHistory\OperationHistory;
use Illuminate\Database\Eloquent\Model;

/**
 * OperationHistoryDto DTO.
 */
final readonly class OperationHistoryDto
{
    public function __construct(
        public int $id,
        public string $targetType,
        public int $targetId,
        public string $actorKind,
        public ?string $actorType,
        public ?int $actorId,
        public ?array $actor,
        public string $actorLabel,
        public string $action,
        public ?string $field,
        public mixed $beforeValue,
        public mixed $afterValue,
        public ?string $reason,
        public mixed $metadata,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    public static function fromModel(OperationHistory $history): self
    {
        return new self(
            id: (int) $history->id,
            targetType: (string) $history->target_type,
            targetId: (int) $history->target_id,
            actorKind: (string) $history->actor_kind,
            actorType: $history->actor_type,
            actorId: $history->actor_id ? (int) $history->actor_id : null,
            actor: self::resolveActor($history),
            actorLabel: self::resolveActorLabel($history),
            action: (string) $history->action,
            field: $history->field,
            beforeValue: $history->before_value,
            afterValue: $history->after_value,
            reason: $history->reason,
            metadata: $history->metadata,
            createdAt: $history->created_at?->toISOString(),
            updatedAt: $history->updated_at?->toISOString(),
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'target_type' => $this->targetType,
            'target_id' => $this->targetId,
            'actor_kind' => $this->actorKind,
            'actor_type' => $this->actorType,
            'actor_id' => $this->actorId,
            'actor' => $this->actor,
            'actor_label' => $this->actorLabel,
            'action' => $this->action,
            'field' => $this->field,
            'before_value' => $this->beforeValue,
            'after_value' => $this->afterValue,
            'reason' => $this->reason,
            'metadata' => $this->metadata,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];

        return $data;
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
