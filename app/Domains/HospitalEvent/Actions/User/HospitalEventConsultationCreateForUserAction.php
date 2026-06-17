<?php

namespace App\Domains\HospitalEvent\Actions\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Models\HospitalEventConsultation;
use App\Domains\HospitalEvent\Queries\User\HospitalEventConsultationCreateForUserQuery;
use Illuminate\Support\Facades\DB;

final class HospitalEventConsultationCreateForUserAction
{
    public function __construct(
        private readonly HospitalEventConsultationCreateForUserQuery $query,
    ) {}

    public function execute(AccountUser $user, HospitalEvent $event, array $payload): array
    {
        $this->assertEventCanBeApplied($event);

        if (! empty($payload['hospital_doctor_id'])) {
            if (! $this->query->doctorBelongsToEvent($event, (int) $payload['hospital_doctor_id'])) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '선택한 의료진이 이벤트에 등록되어 있지 않습니다.');
            }
        }

        DB::transaction(function () use ($user, $event, $payload): void {
            $isDuplicate = $this->query->existsDuplicate(
                $event,
                (string) $payload['name'],
                (string) $payload['phone'],
            );

            $this->query->create([
                'account_user_id' => (int) $user->id,
                'hospital_id' => (int) $event->hospital_id,
                'hospital_event_id' => (int) $event->id,
                'hospital_doctor_id' => $payload['hospital_doctor_id'] ?? null,
                'name' => $payload['name'],
                'phone' => $payload['phone'],
                'contact_method' => $payload['contact_method'],
                'preferred_time' => $payload['preferred_time'],
                'event_price' => (int) $event->event_price,
                'consultation_price' => (int) $event->consultation_price,
                'status' => $isDuplicate
                    ? HospitalEventConsultation::STATUS_DUPLICATE
                    : HospitalEventConsultation::STATUS_NEW,
                'allow_status' => HospitalEventConsultation::ALLOW_STATUS_NORMAL_CONFIRMED,
                'duplicated_at' => $isDuplicate ? now() : null,
                'author_ip' => $payload['author_ip'] ?? null,
                'user_agent' => $payload['user_agent'] ?? null,
                'privacy_agreed_at' => now(),
                'marketing_agreed_at' => ! empty($payload['marketing_agreed']) ? now() : null,
            ]);
        });

        return [
            'message' => '신청이 완료되었습니다.',
        ];
    }

    private function assertEventCanBeApplied(HospitalEvent $event): void
    {
        $now = now();

        if (
            $event->status !== HospitalEvent::STATUS_ACTIVE
            || $event->allow_status !== HospitalEvent::ALLOW_APPROVED
            || $event->deleted_at !== null
            || ($event->event_start_at !== null && $event->event_start_at->greaterThan($now))
            || (
                ! $event->is_event_period_unlimited
                && $event->event_end_at !== null
                && $event->event_end_at->copy()->endOfDay()->lessThan($now)
            )
        ) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '신청 가능한 이벤트가 아닙니다.');
        }
    }
}
