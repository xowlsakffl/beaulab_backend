<?php

namespace App\Domains\AccountUser\Dto\User;

use App\Domains\AccountUser\Models\AccountUserBlock;

/**
 * AccountUserBlockForUserDto DTO.
 */
final readonly class AccountUserBlockForUserDto
{
    public function __construct(
        public int $id,
        public int $blockerUserId,
        public int $blockedUserId,
        public ?array $blockedUser,
        public ?string $blockedAt,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    public static function fromModel(AccountUserBlock $block): self
    {
        return new self(
            id: (int) $block->id,
            blockerUserId: (int) $block->blocker_user_id,
            blockedUserId: (int) $block->blocked_user_id,
            blockedUser: self::blockedUser($block),
            blockedAt: $block->blocked_at?->toISOString(),
            createdAt: $block->created_at?->toISOString(),
            updatedAt: $block->updated_at?->toISOString(),
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'blocker_user_id' => $this->blockerUserId,
            'blocked_user_id' => $this->blockedUserId,
            'blocked_user' => $this->blockedUser,
            'blocked_at' => $this->blockedAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];

        return $data;
    }

    private static function blockedUser(AccountUserBlock $block): ?array
    {
        if (! $block->relationLoaded('blocked') || ! $block->blocked) {
            return null;
        }

        return [
            'id' => (int) $block->blocked->id,
            'nickname' => (string) $block->blocked->nickname,
            'email' => (string) $block->blocked->email,
            'status' => (string) $block->blocked->status,
        ];
    }
}
