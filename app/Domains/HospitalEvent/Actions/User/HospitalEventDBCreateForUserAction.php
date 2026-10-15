<?php

namespace App\Domains\HospitalEvent\Actions\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Notification\Actions\StaffNotificationPublisher;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Models\HospitalEventDB;
use App\Domains\HospitalEvent\Queries\User\HospitalEventDBCreateForUserQuery;
use App\Domains\HospitalEvent\Support\HospitalEventApplicationGuard;
use Illuminate\Support\Facades\DB;

final class HospitalEventDBCreateForUserAction
{
    public function __construct(
        private readonly HospitalEventDBCreateForUserQuery $query,
        private readonly StaffNotificationPublisher $notifications,
        private readonly HospitalEventApplicationGuard $applicationGuard,
    ) {}

    public function execute(AccountUser $user, HospitalEvent $event, array $payload): array
    {
        DB::transaction(function () use ($user, $event, $payload): void {
            $event = $this->applicationGuard->lockOpenEvent((int) $event->id);
            if (! empty($payload['hospital_doctor_id'])
                && ! $this->query->doctorBelongsToEvent($event, (int) $payload['hospital_doctor_id'])) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '선택한 의료진이 이벤트에 등록되어 있지 않습니다.');
            }

            $isDuplicate = $this->query->existsDuplicate(
                $event,
                (string) $payload['name'],
                (string) $payload['phone'],
            );

            $application = $this->query->create([
                'account_user_id' => (int) $user->id,
                'hospital_id' => (int) $event->hospital_id,
                'hospital_event_id' => (int) $event->id,
                'hospital_doctor_id' => $payload['hospital_doctor_id'] ?? null,
                'name' => $payload['name'],
                'phone' => $payload['phone'],
                'contact_method' => $payload['contact_method'],
                'preferred_time' => $payload['preferred_time'],
                'event_price' => (int) $event->event_price,
                'consultation_price' => app(\App\Domains\ConsultationPricing\Support\ConsultationPricingResolver::class)->forEvent($event)['price'],
                'status' => $isDuplicate
                    ? HospitalEventDB::STATUS_DUPLICATE
                    : HospitalEventDB::STATUS_NEW,
                'allow_status' => HospitalEventDB::ALLOW_STATUS_NORMAL_CONFIRMED,
                'duplicated_at' => $isDuplicate ? now() : null,
                'author_ip' => $payload['author_ip'] ?? null,
                'user_agent' => $payload['user_agent'] ?? null,
                'privacy_agreed_at' => now(),
                'marketing_agreed_at' => ! empty($payload['marketing_agreed']) ? now() : null,
            ]);

            $this->notifications->record(
                eventType: 'staff.hospital_event_db.created',
                eventKey: 'hospital-event-db:created:'.$application->id,
                targetType: 'hospital_event_db',
                targetId: (int) $application->id,
                payload: array_filter([
                    'hospital_id' => (int) $event->hospital_id,
                    'hospital_name' => $event->hospital?->name,
                    'target_name' => $event->name,
                ], static fn (mixed $value): bool => $value !== null),
            );
        });

        return [
            'message' => '신청이 완료되었습니다.',
        ];
    }
}
