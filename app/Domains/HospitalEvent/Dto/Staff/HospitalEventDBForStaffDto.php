<?php

declare(strict_types=1);

namespace App\Domains\HospitalEvent\Dto\Staff;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\HospitalEvent\Models\HospitalEventDB;

final readonly class HospitalEventDBForStaffDto
{
    public function __construct(private HospitalEventDB $eventDB) {}

    public static function fromModel(HospitalEventDB $eventDB): self
    {
        return new self($eventDB);
    }

    public function toArray(): array
    {
        return [
            'id' => (int) $this->consultation->id,
            'hospital' => $this->hospital(),
            'event' => $this->event(),
            'doctor' => $this->doctor(),
            'account_user' => $this->accountUser(),
            'name' => (string) $this->consultation->name,
            'phone' => (string) $this->consultation->phone,
            'phone_normalized' => (string) $this->consultation->phone_normalized,
            'contact_method' => [
                'code' => (string) $this->consultation->contact_method,
                'label' => HospitalEventDB::contactMethodLabel($this->consultation->contact_method),
            ],
            'preferred_time' => [
                'code' => (string) $this->consultation->preferred_time,
                'label' => HospitalEventDB::preferredTimeLabel($this->consultation->preferred_time),
            ],
            'event_price' => (int) $this->consultation->event_price,
            'consultation_price' => (int) $this->consultation->consultation_price,
            'status' => [
                'code' => (string) $this->consultation->status,
                'label' => HospitalEventDB::statusLabel($this->consultation->status),
            ],
            'allow_status' => [
                'code' => (string) $this->consultation->allow_status,
                'label' => HospitalEventDB::allowStatusLabel($this->consultation->allow_status),
            ],
            'contacted_at' => $this->consultation->contacted_at?->toISOString(),
            'confirmed_at' => $this->consultation->confirmed_at?->toISOString(),
            'duplicated_at' => $this->consultation->duplicated_at?->toISOString(),
            'author_ip' => $this->consultation->author_ip,
            'user_agent' => $this->consultation->user_agent,
            'privacy_agreed_at' => $this->consultation->privacy_agreed_at?->toISOString(),
            'marketing_agreed_at' => $this->consultation->marketing_agreed_at?->toISOString(),
            'created_at' => $this->consultation->created_at?->toISOString(),
            'updated_at' => $this->consultation->updated_at?->toISOString(),
        ];
    }

    private function hospital(): ?array
    {
        if (! $this->consultation->relationLoaded('hospital') || ! $this->consultation->hospital) {
            return null;
        }

        return [
            'id' => (int) $this->consultation->hospital->id,
            'name' => (string) $this->consultation->hospital->name,
        ];
    }

    private function event(): ?array
    {
        if (! $this->consultation->relationLoaded('event') || ! $this->consultation->event) {
            return null;
        }

        return [
            'id' => (int) $this->consultation->event->id,
            'name' => (string) $this->consultation->event->name,
            'event_price' => (int) $this->consultation->event->event_price,
            'consultation_price' => (int) $this->consultation->event->consultation_price,
        ];
    }

    private function doctor(): ?array
    {
        if (! $this->consultation->relationLoaded('doctor') || ! $this->consultation->doctor) {
            return null;
        }

        return [
            'id' => (int) $this->consultation->doctor->id,
            'name' => (string) $this->consultation->doctor->name,
            'position' => $this->consultation->doctor->position,
        ];
    }

    private function accountUser(): ?array
    {
        if (! $this->consultation->relationLoaded('accountUser') || ! $this->consultation->accountUser) {
            return null;
        }

        return [
            'id' => (int) $this->consultation->accountUser->id,
            'name' => $this->consultation->accountUser->name,
            'nickname' => $this->consultation->accountUser->nickname,
            'email' => $this->consultation->accountUser->email,
            'phone' => $this->consultation->accountUser->phone,
            'status' => [
                'code' => (string) $this->consultation->accountUser->status,
                'label' => AccountUser::statusLabels()[$this->consultation->accountUser->status] ?? '-',
            ],
        ];
    }

}
