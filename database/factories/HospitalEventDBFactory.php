<?php

namespace Database\Factories;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Models\HospitalEventDB;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HospitalEventDB>
 */
final class HospitalEventDBFactory extends Factory
{
    protected $model = HospitalEventDB::class;

    public function definition(): array
    {
        $status = $this->faker->randomElement([
            HospitalEventDB::STATUS_NEW,
            HospitalEventDB::STATUS_CONFIRMED,
        ]);
        $createdAt = $this->faker->dateTimeBetween('-60 days', 'now');
        $processedAt = $status === HospitalEventDB::STATUS_CONFIRMED
            ? $this->faker->dateTimeBetween($createdAt, 'now')
            : null;

        return [
            'account_user_id' => AccountUser::factory(),
            'hospital_event_id' => HospitalEvent::factory()->active()->approved(),
            'hospital_id' => fn (array $attributes) => HospitalEvent::findOrFail($attributes['hospital_event_id'])->hospital_id,
            'hospital_doctor_id' => null,
            'name' => fake('ko_KR')->name(),
            'phone' => $this->faker->numerify('010-####-####'),
            'contact_method' => $this->faker->randomElement(HospitalEventDB::contactMethods()),
            'preferred_time' => $this->faker->randomElement(HospitalEventDB::preferredTimes()),
            'event_price' => fn (array $attributes) => HospitalEvent::findOrFail($attributes['hospital_event_id'])->event_price,
            'consultation_price' => fn (array $attributes) => HospitalEvent::findOrFail($attributes['hospital_event_id'])->consultation_price,
            'status' => $status,
            'allow_status' => $this->faker->randomElement(HospitalEventDB::allowStatuses()),
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
}
