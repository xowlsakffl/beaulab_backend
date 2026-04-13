<?php

namespace App\Domains\AccountStaff\Dto\Auth;

use App\Domains\AccountStaff\Models\AccountStaff;

/**
 * ProfileForStaffDto 역할 정의.
 * 스태프 계정 도메인의 DTO로, 모델 값을 API 응답이나 계층 간 전달에 맞는 단순한 배열/값 구조로 정규화한다.
 */
final readonly class ProfileForStaffDto
{
    public function __construct(
        public int $id,
        public string $name,
        public string $nickname,
        public string $email,
        public ?string $department,
        public ?string $jobTitle,
        public string $status,
        public ?string $lastLoginAt,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromModel(AccountStaff $staff): self
    {
        return new self(
            id: $staff->id,
            name: $staff->name,
            nickname: $staff->nickname,
            email: $staff->email,
            department: $staff->department,
            jobTitle: $staff->job_title,
            status: $staff->status,
            lastLoginAt: $staff->last_login_at?->toISOString(),
            createdAt: $staff->created_at?->toISOString() ?? '',
            updatedAt: $staff->updated_at?->toISOString() ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'nickname' => $this->nickname,
            'email' => $this->email,
            'department' => $this->department,
            'job_title' => $this->jobTitle,
            'status' => $this->status,
            'last_login_at' => $this->lastLoginAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
