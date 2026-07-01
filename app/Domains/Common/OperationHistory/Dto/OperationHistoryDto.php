<?php

namespace App\Domains\Common\OperationHistory\Dto;

use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryActorRegistry;
use App\Domains\Common\OperationHistory\Support\OperationHistoryTargetRegistry;
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
        public ?string $targetAlias,
        public ?string $actorAlias,
        public ?int $actorId,
        public ?array $actor,
        public string $actorLabel,
        public string $action,
        public string $actionLabel,
        public ?string $batchUuid,
        public ?string $field,
        public mixed $beforeValue,
        public mixed $afterValue,
        public ?string $reason,
        public mixed $metadata,
        public array $changes,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    public static function fromModel(OperationHistory $history): self
    {
        $history->loadMissing('changes');
        $firstChange = $history->changes->first();

        return new self(
            id: (int) $history->id,
            targetType: (string) $history->target_type,
            targetId: (int) $history->target_id,
            actorKind: (string) $history->actor_kind,
            actorType: $history->actor_type,
            targetAlias: OperationHistoryTargetRegistry::aliasForModel($history->target_type),
            actorAlias: OperationHistoryActorRegistry::aliasForModel($history->actor_type)
                ?? OperationHistoryActorRegistry::aliasForKind($history->actor_kind),
            actorId: $history->actor_id ? (int) $history->actor_id : null,
            actor: self::actor($history),
            actorLabel: self::actorLabel($history),
            action: (string) $history->action,
            actionLabel: self::actionLabel($history),
            batchUuid: $history->batch_uuid,
            field: $firstChange?->field_key,
            beforeValue: $firstChange?->before_value,
            afterValue: $firstChange?->after_value,
            reason: $history->reason,
            metadata: $history->metadata,
            changes: $history->changes
                ->map(static fn ($change): array => [
                    'id' => (int) $change->id,
                    'field_key' => (string) $change->field_key,
                    'field_label' => self::normalizeFieldLabel((string) $change->field_label),
                    'before_value' => $change->before_value,
                    'after_value' => $change->after_value,
                    'before_display' => $change->before_display,
                    'after_display' => $change->after_display,
                    'sort_order' => (int) $change->sort_order,
                ])
                ->values()
                ->all(),
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
            'target_alias' => $this->targetAlias,
            'actor_alias' => $this->actorAlias,
            'actor_id' => $this->actorId,
            'actor' => $this->actor,
            'actor_label' => $this->actorLabel,
            'action' => $this->action,
            'action_label' => $this->actionLabel,
            'batch_uuid' => $this->batchUuid,
            'field' => $this->field,
            'before_value' => $this->beforeValue,
            'after_value' => $this->afterValue,
            'reason' => $this->reason,
            'metadata' => $this->metadata,
            'changes' => $this->changes,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];

        return $data;
    }

    private static function actor(OperationHistory $history): ?array
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

    private static function actorLabel(OperationHistory $history): string
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

    private static function actionLabel(OperationHistory $history): string
    {
        $action = (string) $history->action;

        return match ($action) {
            OperationHistory::ACTION_CREATED => '생성',
            OperationHistory::ACTION_UPDATED => '수정',
            OperationHistory::ACTION_STATE_UPDATED => '상태 변경',
            OperationHistory::ACTION_DELETED => '삭제',
            default => $action,
        };
    }

    private static function normalizeFieldLabel(string $fieldLabel): string
    {
        $fieldLabel = trim($fieldLabel);

        return str_ends_with($fieldLabel, ' 변경')
            ? trim(substr($fieldLabel, 0, -strlen(' 변경')))
            : $fieldLabel;
    }
}
