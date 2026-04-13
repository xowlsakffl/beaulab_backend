<?php

namespace App\Domains\Common\Dto\AdminNote;

use App\Domains\Common\Models\AdminNote\AdminNote;
use App\Domains\Common\Support\AdminNote\AdminNoteActorRegistry;
use App\Domains\Common\Support\AdminNote\AdminNoteTargetRegistry;

/**
 * AdminNoteData 역할 정의.
 * 공통 도메인의 DTO로, 모델 값을 API 응답이나 계층 간 전달에 맞는 단순한 배열/값 구조로 정규화한다.
 */
final readonly class AdminNoteData
{
    public function __construct(public array $note) {}

    public static function fromModel(AdminNote $note): self
    {
        $creator = $note->relationLoaded('creator') ? $note->creator : null;
        $creatorType = AdminNoteActorRegistry::aliasForModel($creator ?? $note->creator_type);
        $creatorName = $creator && isset($creator->name) ? (string) $creator->name : null;

        return new self([
            'id' => (int) $note->id,
            'target_type' => AdminNoteTargetRegistry::aliasForModel($note->target_type),
            'target_id' => (int) $note->target_id,
            'note' => (string) $note->note,
            'is_internal' => (bool) $note->is_internal,
            'creator_type' => $creatorType,
            'creator_id' => $note->creator_id ? (int) $note->creator_id : null,
            'creator_name' => $creatorName,
            'created_at' => $note->created_at?->toISOString(),
            'updated_at' => $note->updated_at?->toISOString(),
            'deleted_at' => $note->deleted_at?->toISOString(),
        ]);
    }

    public function toArray(): array
    {
        return $this->note;
    }
}
