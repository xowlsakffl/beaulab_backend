<?php

namespace Database\Factories;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Models\HospitalEventConsultation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HospitalEventConsultation>
 */
final class HospitalEventConsultationFactory extends Factory
{
    protected $model = HospitalEventConsultation::class;

    public function definition(): array
    {
        $event = $this->randomEvent();
        $doctorId = $this->randomDoctorId($event);
        $status = $this->faker->randomElement([
            HospitalEventConsultation::STATUS_NEW,
            HospitalEventConsultation::STATUS_CONFIRMED,
        ]);
        $createdAt = $this->faker->dateTimeBetween('-60 days', 'now');
        $processedAt = $status === HospitalEventConsultation::STATUS_CONFIRMED
            ? $this->faker->dateTimeBetween($createdAt, 'now')
            : null;

        return [
            'account_user_id' => $this->randomAccountUserId(),
            'hospital_id' => (int) $event->hospital_id,
            'hospital_event_id' => (int) $event->id,
            'hospital_doctor_id' => $doctorId,
            'name' => $this->faker->name(),
            'phone' => $this->faker->numerify('010-####-####'),
            'contact_method' => $this->faker->randomElement(HospitalEventConsultation::contactMethods()),
            'preferred_time' => $this->faker->randomElement(HospitalEventConsultation::preferredTimes()),
            'event_price' => (int) $event->event_price,
            'consultation_price' => (int) $event->consultation_price,
            'status' => $status,
            'allow_status' => $this->faker->randomElement(HospitalEventConsultation::allowStatuses()),
            'contacted_at' => $processedAt,
            'confirmed_at' => $processedAt,
            'author_ip' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
            'privacy_agreed_at' => $createdAt,
            'marketing_agreed_at' => $this->faker->boolean(70) ? $createdAt : null,
            'created_at' => $createdAt,
            'updated_at' => now(),
        ];
    }

    public function forEvent(HospitalEvent|int $event): self
    {
        $eventId = $event instanceof HospitalEvent ? (int) $event->id : $event;

        return $this->state(function () use ($eventId): array {
            $event = HospitalEvent::query()->findOrFail($eventId);

            return [
                'hospital_id' => (int) $event->hospital_id,
                'hospital_event_id' => (int) $event->id,
                'hospital_doctor_id' => $this->randomDoctorId($event),
                'event_price' => (int) $event->event_price,
                'consultation_price' => (int) $event->consultation_price,
            ];
        });
    }

    private function randomEvent(): HospitalEvent
    {
        /** @var array<int, int>|null $eventIds */
        static $eventIds = null;

        if ($eventIds === null) {
            $eventIds = HospitalEvent::query()
                ->pluck('id')
                ->map(static fn (int|string $id): int => (int) $id)
                ->all();
        }

        if ($eventIds === []) {
            $event = HospitalEvent::factory()->active()->approved()->withSeedMedia()->create();
            $eventIds[] = (int) $event->id;

            return $event;
        }

        return HospitalEvent::query()->findOrFail($eventIds[array_rand($eventIds)]);
    }

    private function randomDoctorId(HospitalEvent $event): ?int
    {
        $doctorIds = $event->doctors()
            ->pluck('hospital_doctors.id')
            ->map(static fn (int|string $id): int => (int) $id)
            ->values();

        if ($doctorIds->isEmpty()) {
            $doctorIds = HospitalDoctor::query()
                ->where('hospital_id', $event->hospital_id)
                ->pluck('id')
                ->map(static fn (int|string $id): int => (int) $id)
                ->values();
        }

        if ($doctorIds->isEmpty()) {
            return null;
        }

        return $doctorIds->random();
    }

    private function randomAccountUserId(): int
    {
        /** @var array<int, int>|null $userIds */
        static $userIds = null;

        if ($userIds === null) {
            $userIds = AccountUser::query()
                ->pluck('id')
                ->map(static fn (int|string $id): int => (int) $id)
                ->all();
        }

        if ($userIds === []) {
            $user = AccountUser::factory()->create();
            $userIds[] = (int) $user->id;

            return (int) $user->id;
        }

        return $userIds[array_rand($userIds)];
    }
}
