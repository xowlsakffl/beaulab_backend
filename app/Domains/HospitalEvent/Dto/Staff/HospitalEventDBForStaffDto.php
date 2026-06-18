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
            'id' => (int) $this->eventDB->id,
            'hospital' => $this->hospital(),
            'event' => $this->event(),
            'doctor' => $this->doctor(),
            'account_user' => $this->accountUser(),
            'name' => (string) $this->eventDB->name,
            'phone' => (string) $this->eventDB->phone,
            'phone_normalized' => (string) $this->eventDB->phone_normalized,
            'contact_method' => [
                'code' => (string) $this->eventDB->contact_method,
                'label' => HospitalEventDB::contactMethodLabel($this->eventDB->contact_method),
            ],
            'preferred_time' => [
                'code' => (string) $this->eventDB->preferred_time,
                'label' => HospitalEventDB::preferredTimeLabel($this->eventDB->preferred_time),
            ],
            'event_price' => (int) $this->eventDB->event_price,
            'consultation_price' => (int) $this->eventDB->consultation_price,
            'status' => [
                'code' => (string) $this->eventDB->status,
                'label' => HospitalEventDB::statusLabel($this->eventDB->status),
            ],
            'allow_status' => [
                'code' => (string) $this->eventDB->allow_status,
                'label' => HospitalEventDB::allowStatusLabel($this->eventDB->allow_status),
            ],
            'contacted_at' => $this->eventDB->contacted_at?->toISOString(),
            'confirmed_at' => $this->eventDB->confirmed_at?->toISOString(),
            'duplicated_at' => $this->eventDB->duplicated_at?->toISOString(),
            'author_ip' => $this->eventDB->author_ip,
            'user_agent' => $this->eventDB->user_agent,
            'privacy_agreed_at' => $this->eventDB->privacy_agreed_at?->toISOString(),
            'marketing_agreed_at' => $this->eventDB->marketing_agreed_at?->toISOString(),
            'created_at' => $this->eventDB->created_at?->toISOString(),
            'updated_at' => $this->eventDB->updated_at?->toISOString(),
        ];
    }

    private function hospital(): ?array
    {
        if (! $this->eventDB->relationLoaded('hospital') || ! $this->eventDB->hospital) {
            return null;
        }

        return [
            'id' => (int) $this->eventDB->hospital->id,
            'name' => (string) $this->eventDB->hospital->name,
        ];
    }

    private function event(): ?array
    {
        if (! $this->eventDB->relationLoaded('event') || ! $this->eventDB->event) {
            return null;
        }

        return [
            'id' => (int) $this->eventDB->event->id,
            'name' => (string) $this->eventDB->event->name,
            'event_price' => (int) $this->eventDB->event->event_price,
            'consultation_price' => (int) $this->eventDB->event->consultation_price,
        ];
    }

    private function doctor(): ?array
    {
        if (! $this->eventDB->relationLoaded('doctor') || ! $this->eventDB->doctor) {
            return null;
        }

        return [
            'id' => (int) $this->eventDB->doctor->id,
            'name' => (string) $this->eventDB->doctor->name,
            'position' => $this->eventDB->doctor->position,
        ];
    }

    private function accountUser(): ?array
    {
        if (! $this->eventDB->relationLoaded('accountUser') || ! $this->eventDB->accountUser) {
            return null;
        }

        return [
            'id' => (int) $this->eventDB->accountUser->id,
            'name' => $this->eventDB->accountUser->name,
            'nickname' => $this->eventDB->accountUser->nickname,
            'email' => $this->eventDB->accountUser->email,
            'phone' => $this->eventDB->accountUser->phone,
            'status' => [
                'code' => (string) $this->eventDB->accountUser->status,
                'label' => AccountUser::statusLabels()[$this->eventDB->accountUser->status] ?? '-',
            ],
        ];
    }

}
