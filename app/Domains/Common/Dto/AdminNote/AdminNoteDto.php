<?php

namespace App\Domains\Common\Dto\AdminNote;

use App\Domains\Common\Models\AdminNote\AdminNote;
use App\Domains\Common\Support\AdminNote\AdminNoteActorRegistry;
use App\Domains\Common\Support\AdminNote\AdminNoteTargetRegistry;

/**
 * AdminNoteDto DTO.
 */
final readonly class AdminNoteDto
{
    public function __construct(
        public int $id,
        public ?string $targetType,
        public int $targetId,
        public string $note,
        public bool $isInternal,
        public ?string $creatorType,
        public ?int $creatorId,
        public ?string $creatorName,
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?string $deletedAt,
    ) {}

    public static function fromModel(AdminNote $note): self
    {
        $creator = $note->relationLoaded('creator') ? $note->creator : null;

        return new self(
            id: (int) $note->id,
            targetType: AdminNoteTargetRegistry::aliasForModel($note->target_type),
            targetId: (int) $note->target_id,
            note: (string) $note->note,
            isInternal: (bool) $note->is_internal,
            creatorType: AdminNoteActorRegistry::aliasForModel($creator ?? $note->creator_type),
            creatorId: $note->creator_id ? (int) $note->creator_id : null,
            creatorName: $creator && isset($creator->name) ? (string) $creator->name : null,
            createdAt: $note->created_at?->toISOString(),
            updatedAt: $note->updated_at?->toISOString(),
            deletedAt: $note->deleted_at?->toISOString(),
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'target_type' => $this->targetType,
            'target_id' => $this->targetId,
            'note' => $this->note,
            'is_internal' => $this->isInternal,
            'creator_type' => $this->creatorType,
            'creator_id' => $this->creatorId,
            'creator_name' => $this->creatorName,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->deletedAt,
        ];

        return $data;
    }
}
