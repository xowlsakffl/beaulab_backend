<?php

namespace App\Domains\AccountUser\Dto\User;

use App\Domains\AccountUser\Models\AccountUserBlock;

/**
 * 앱 사용자 차단 응답 DTO.
 * API 응답에는 차단 관계와 차단된 사용자 요약 정보만 노출한다.
 */
final readonly class AccountUserBlockForUserDto
{
    public static function fromModel(AccountUserBlock $block): array
    {
        return [
            'id' => (int) $block->id,
            'blocker_user_id' => (int) $block->blocker_user_id,
            'blocked_user_id' => (int) $block->blocked_user_id,
            'blocked_user' => $block->relationLoaded('blocked') && $block->blocked
                ? [
                    'id' => (int) $block->blocked->id,
                    'nickname' => (string) $block->blocked->nickname,
                    'email' => (string) $block->blocked->email,
                    'status' => (string) $block->blocked->status,
                ]
                : null,
            'blocked_at' => $block->blocked_at?->toISOString(),
            'created_at' => $block->created_at?->toISOString(),
            'updated_at' => $block->updated_at?->toISOString(),
        ];
    }
}
