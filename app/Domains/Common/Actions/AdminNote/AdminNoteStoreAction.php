<?php

namespace App\Domains\Common\Actions\AdminNote;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Dto\AdminNote\AdminNoteData;
use App\Domains\Common\Support\AdminNote\AdminNoteActorRegistry;
use App\Domains\Common\Support\AdminNote\AdminNoteTargetRegistry;
use Illuminate\Support\Facades\Gate;

/**
 * AdminNoteStoreAction 역할 정의.
 * 공통 도메인의 Action 계층으로, 컨트롤러에서 넘어온 검증된 입력을 받아 권한 확인, 도메인 흐름 조합, Query 호출을 담당한다.
 */
final class AdminNoteStoreAction
{
    public function __construct(
        private readonly AdminNoteCreateAction $createAction,
    ) {}

    public function execute(mixed $actor, array $payload): array
    {
        $target = AdminNoteTargetRegistry::resolveTarget(
            (string) $payload['target_type'],
            (int) $payload['target_id'],
        );

        Gate::forUser($actor)->authorize('update', $target);

        $isInternal = AdminNoteActorRegistry::isPartnerActor($actor)
            ? (bool) ($payload['is_internal'] ?? false)
            : (bool) ($payload['is_internal'] ?? true);

        if (AdminNoteActorRegistry::isPartnerActor($actor) && $isInternal) {
            throw new CustomException(
                ErrorCode::INVALID_REQUEST,
                '병원/뷰티 관리자 메모는 공유 메모만 생성할 수 있습니다.'
            );
        }

        $note = $this->createAction
            ->execute($target, (string) $payload['note'], $actor, $isInternal)
            ->load('creator');

        return [
            'note' => AdminNoteData::fromModel($note)->toArray(),
        ];
    }
}
