<?php

namespace App\Domains\Common\Actions\AdminNote;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Dto\AdminNote\AdminNoteData;
use App\Domains\Common\Models\AdminNote\AdminNote;
use App\Domains\Common\Queries\AdminNote\AdminNoteUpdateQuery;
use App\Domains\Common\Support\AdminNote\AdminNoteActorRegistry;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class AdminNoteUpdateAction
{
    public function __construct(
        private readonly AdminNoteUpdateQuery $query,
    ) {}

    public function execute(mixed $actor, AdminNote $note, array $payload): array
    {
        if (! AdminNoteActorRegistry::isCreator($actor, $note)) {
            throw new CustomException(ErrorCode::FORBIDDEN, '본인이 작성한 메모만 수정할 수 있습니다.');
        }

        $note->loadMissing('target');

        if (! $note->target) {
            throw new CustomException(ErrorCode::NOT_FOUND, '메모 대상 정보를 찾을 수 없습니다.');
        }

        Gate::forUser($actor)->authorize('update', $note->target);

        $isInternal = AdminNoteActorRegistry::isPartnerActor($actor)
            ? (bool) ($payload['is_internal'] ?? false)
            : (bool) ($payload['is_internal'] ?? $note->is_internal);

        if (AdminNoteActorRegistry::isPartnerActor($actor) && $isInternal) {
            throw new CustomException(
                ErrorCode::INVALID_REQUEST,
                '병원/뷰티 관리자 메모는 공유 메모만 수정할 수 있습니다.'
            );
        }

        $updated = DB::transaction(fn () => $this->query->update($note, [
            'note' => trim((string) $payload['note']),
            'is_internal' => $isInternal,
        ]));

        return [
            'note' => AdminNoteData::fromModel($updated)->toArray(),
        ];
    }
}
