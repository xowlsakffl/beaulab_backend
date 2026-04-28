<?php

namespace App\Domains\Common\AdminNote\Actions;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\AdminNote\Dto\AdminNoteDto;
use App\Domains\Common\AdminNote\Queries\AdminNoteCreateQuery;
use App\Domains\Common\AdminNote\Support\AdminNoteActorRegistry;
use App\Domains\Common\AdminNote\Support\AdminNoteTargetRegistry;
use Illuminate\Support\Facades\Gate;

/**
 * AdminNoteCreateAction 역할 정의.
 * 관리자 메모 생성 유스케이스를 담당하고, 대상 해석, 권한 확인, 메모 생성 규칙 적용, DTO 응답 변환을 처리한다.
 */
final class AdminNoteCreateAction
{
    public function __construct(
        private readonly AdminNoteCreateQuery $query,
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

        $note = $this->query
            ->create([
                'target_type' => $target::class,
                'target_id' => (int) $target->getKey(),
                'note' => trim((string) $payload['note']),
                'is_internal' => $isInternal,
                'creator_type' => $actor?->getMorphClass(),
                'creator_id' => (int) $actor->getKey(),
            ])
            ->load('creator');

        return [
            'note' => AdminNoteDto::fromModel($note)->toArray(),
        ];
    }
}
