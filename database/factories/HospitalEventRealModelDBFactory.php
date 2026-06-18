<?php

namespace Database\Factories;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalEvent\Models\HospitalEventRealModelDB;
use Database\Factories\Support\SeedMediaFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HospitalEventRealModelDB>
 */
final class HospitalEventRealModelDBFactory extends Factory
{
    protected $model = HospitalEventRealModelDB::class;

    public function definition(): array
    {
        $event = $this->randomEvent();
        $status = $this->faker->randomElement(HospitalEventRealModelDB::statuses());
        $createdAt = $this->faker->dateTimeBetween('-60 days', 'now');

        return [
            'account_user_id' => $this->randomAccountUserId(),
            'hospital_id' => (int) $event->hospital_id,
            'hospital_event_id' => (int) $event->id,
            'name' => $this->faker->name(),
            'gender' => $this->faker->randomElement(HospitalEventRealModelDB::genders()),
            'birth_date' => $this->faker->dateTimeBetween('-45 years', '-19 years')->format('Y-m-d'),
            'phone' => $this->faker->numerify('010-####-####'),
            'height_cm' => $this->faker->numberBetween(145, 190),
            'weight_kg' => $this->faker->numberBetween(40, 95),
            'surgery_period' => $this->faker->randomElement(HospitalEventRealModelDB::surgeryPeriods()),
            'support_part' => $this->faker->randomElement(['눈', '코', '가슴', '윤곽', '피부', '리프팅', '보톡스']),
            'instagram_url' => $this->faker->boolean(70) ? 'https://instagram.com/'.$this->faker->userName() : null,
            'blog_url' => $this->faker->boolean(45) ? 'https://blog.naver.com/'.$this->faker->userName() : null,
            'special_notes' => $this->randomSpecialNotes(),
            'application_reason' => $this->faker->realText(120),
            'inquiry' => $this->faker->boolean(65) ? $this->faker->realText(80) : null,
            'status' => $status,
            'author_ip' => $this->faker->ipv4(),
            'user_agent' => $this->faker->userAgent(),
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
            ];
        });
    }

    public function withSeedMedia(int $imageCount = 3): self
    {
        return $this->afterCreating(function (HospitalEventRealModelDB $application) use ($imageCount): void {
            app(MediaAttachDeleteAction::class)->attachMany(
                $application,
                SeedMediaFactory::images("hospital-event-real-model-{$application->id}", max(1, $imageCount)),
                HospitalEventRealModelDB::COLLECTION_IMAGES,
                'hospital-event-real-model-db',
                'images',
                true,
            );
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

    /**
     * @return list<string>
     */
    private function randomSpecialNotes(): array
    {
        return collect(HospitalEventRealModelDB::specialNotes())
            ->shuffle()
            ->take($this->faker->numberBetween(0, 3))
            ->values()
            ->all();
    }
}
