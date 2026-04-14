<?php

namespace App\Domains\AccountBeauty\Dto\Beauty;

use App\Domains\AccountBeauty\Models\AccountBeauty;

/**
 * AuthForAccountBeautyDto 역할 정의.
 * 뷰티 계정 도메인의 DTO로, 모델 값을 API 응답이나 계층 간 전달에 맞는 단순한 배열/값 구조로 정규화한다.
 */
final readonly class AuthForAccountBeautyDto
{
    public function __construct(
        public int $id,
        public string $name,
        public string $nickname,
        public string $email,
        public string $status,
        public ?int $beautyId,
        public ?string $lastLoginAt,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromModel(AccountBeauty $beauty): self
    {
        return new self(
            id: $beauty->id,
            name: $beauty->name,
            nickname: $beauty->nickname,
            email: $beauty->email,
            status: $beauty->status,
            beautyId: $beauty->beauty_id,
            lastLoginAt: $beauty->last_login_at?->toISOString(),
            createdAt: $beauty->created_at?->toISOString() ?? '',
            updatedAt: $beauty->updated_at?->toISOString() ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'nickname' => $this->nickname,
            'email' => $this->email,
            'status' => $this->status,
            'beauty_id' => $this->beautyId,
            'last_login_at' => $this->lastLoginAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
