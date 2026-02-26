<?php

namespace App\Domains\Partner\Dto\Auth;

use App\Domains\Partner\Models\AccountPartner;

final readonly class AuthForPartnerDto
{
    public function __construct(
        public int $id,
        public string $name,
        public string $nickname,
        public string $email,
        public string $status,
        public ?string $partnerType,
        public ?int $hospitalId,
        public ?int $beautyId,
        public ?string $lastLoginAt,
        public string $createdAt,
        public string $updatedAt,
    ) {}

    public static function fromModel(AccountPartner $partner): self
    {
        return new self(
            id: $partner->id,
            name: $partner->name,
            nickname: $partner->nickname,
            email: $partner->email,
            status: $partner->status,
            partnerType: $partner->partner_type,
            hospitalId: $partner->hospital_id,
            beautyId: $partner->beauty_id,
            lastLoginAt: $partner->last_login_at?->toISOString(),
            createdAt: $partner->created_at?->toISOString() ?? '',
            updatedAt: $partner->updated_at?->toISOString() ?? '',
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
            'partner_type' => $this->partnerType,
            'hospital_id' => $this->hospitalId,
            'beauty_id' => $this->beautyId,
            'last_login_at' => $this->lastLoginAt,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
