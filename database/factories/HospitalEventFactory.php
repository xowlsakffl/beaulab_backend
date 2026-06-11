<?php

namespace Database\Factories;

use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use Database\Factories\Support\SeedMediaFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HospitalEvent>
 */
final class HospitalEventFactory extends Factory
{
    protected $model = HospitalEvent::class;

    public function definition(): array
    {
        $normalPrice = $this->faker->numberBetween(10, 500) * 10000;
        $eventPrice = (int) (floor($normalPrice * $this->faker->numberBetween(55, 95) / 10000) * 100);
        $baseConsultationPrice = HospitalEvent::consultationBasePrice($eventPrice);
        $isUnlimited = $this->faker->boolean(45);
        $startAt = $this->faker->dateTimeBetween('-15 days', '+15 days');

        $consultationPrice = $baseConsultationPrice + ($this->faker->boolean(25) ? $this->faker->randomElement([2500, 5000, 10000]) : 0);

        return [
            'hospital_id' => $this->randomHospitalId(),
            'event_type' => $this->faker->randomElement(HospitalEvent::types()),
            'name' => $this->faker->randomElement([
                '눈성형 이벤트',
                '코성형 특별가',
                '리프팅 프로모션',
                '보톡스 패키지',
                '피부관리 특가',
                '상담 전용 이벤트',
            ]),
            'description' => $this->faker->randomElement([
                '대표 의료진이 직접 상담합니다',
                '정품 정량 원칙으로 진행합니다',
                '부담 없는 가격으로 만나보세요',
                '개인 상태에 맞춰 안내합니다',
            ]),
            'is_event_period_unlimited' => $isUnlimited,
            'event_start_at' => $startAt,
            'event_end_at' => $isUnlimited ? null : (clone $startAt)->modify('+'.$this->faker->numberBetween(30, 90).' days'),
            'normal_price' => $normalPrice,
            'event_price' => $eventPrice,
            'is_vat_included' => $this->faker->boolean(80),
            'discount_rate' => HospitalEvent::calculateDiscountRate($normalPrice, $eventPrice),
            'base_consultation_price' => $baseConsultationPrice,
            'consultation_price' => $consultationPrice,
            'has_options' => false,
            'procedure_targets' => null,
            'procedure_benefits' => null,
            'side_effect_notice' => '수술/시술 후 염증, 출혈, 감염 등 부작용이 발생할 수 있어 주의가 필요합니다.',
            'allow_status' => $this->faker->randomElement(HospitalEvent::allowStatuses()),
            'status' => $this->faker->randomElement(HospitalEvent::statuses()),
            'view_count' => $this->faker->numberBetween(0, 20000),
        ];
    }

    public function textType(): self
    {
        return $this->state(fn (): array => [
            'event_type' => HospitalEvent::TYPE_TEXT,
            'procedure_targets' => [
                '첫 상담이 필요한 분',
                '자연스러운 변화를 원하는 분',
            ],
            'procedure_benefits' => [
                '개인 상태에 맞춘 상담',
                '회복 부담을 줄인 계획',
            ],
            'has_options' => false,
        ]);
    }

    public function imageType(): self
    {
        return $this->state(fn (): array => [
            'event_type' => HospitalEvent::TYPE_IMAGE,
            'procedure_targets' => null,
            'procedure_benefits' => null,
        ]);
    }

    public function active(): self
    {
        return $this->state(fn (): array => [
            'status' => HospitalEvent::STATUS_ACTIVE,
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn (): array => [
            'status' => HospitalEvent::STATUS_INACTIVE,
        ]);
    }

    public function approved(): self
    {
        return $this->state(fn (): array => [
            'allow_status' => HospitalEvent::ALLOW_APPROVED,
        ]);
    }

    public function pending(): self
    {
        return $this->state(fn (): array => [
            'allow_status' => HospitalEvent::ALLOW_PENDING,
        ]);
    }

    public function rejected(): self
    {
        return $this->state(fn (): array => [
            'allow_status' => HospitalEvent::ALLOW_REJECTED,
        ]);
    }

    public function reviewing(): self
    {
        return $this->state(fn (): array => [
            'allow_status' => HospitalEvent::ALLOW_REVIEWING,
        ]);
    }

    public function partnerCanceled(): self
    {
        return $this->state(fn (): array => [
            'allow_status' => HospitalEvent::ALLOW_PARTNER_CANCELED,
        ]);
    }

    public function forHospital(Hospital|int $hospital): self
    {
        return $this->state(fn (): array => [
            'hospital_id' => $hospital instanceof Hospital ? $hospital->getKey() : $hospital,
        ]);
    }

    public function withOptions(int $count = 3): self
    {
        return $this->afterCreating(function (HospitalEvent $event) use ($count): void {
            $event->forceFill(['has_options' => true])->save();

            for ($index = 0; $index < max(1, $count); $index++) {
                $normalPrice = $this->faker->numberBetween(5, 80) * 10000;
                $eventPrice = (int) (floor($normalPrice * $this->faker->numberBetween(55, 90) / 10000) * 100);

                $event->options()->create([
                    'sort_order' => $index,
                    'name' => $this->faker->randomElement(['기본 시술', '프리미엄 시술', '집중 관리', '추가 부위']),
                    'session_count' => $this->faker->numberBetween(1, 5),
                    'normal_price' => $normalPrice,
                    'event_price' => $eventPrice,
                    'discount_rate' => floor((1 - ($eventPrice / $normalPrice)) * 100),
                ]);
            }
        });
    }

    public function withSeedMedia(): self
    {
        return $this->afterCreating(function (HospitalEvent $event): void {
            $mediaAttachAction = app(MediaAttachDeleteAction::class);

            $mediaAttachAction->attachOne(
                $event,
                SeedMediaFactory::image("hospital-event-thumbnail-{$event->id}"),
                HospitalEvent::COLLECTION_THUMBNAIL_IMAGE,
                'hospital-event',
                'thumbnail-image',
                true,
            );

            if ($event->event_type === HospitalEvent::TYPE_IMAGE) {
                $mediaAttachAction->attachOne(
                    $event,
                    SeedMediaFactory::image("hospital-event-page-{$event->id}"),
                    HospitalEvent::COLLECTION_EVENT_PAGE_IMAGE,
                    'hospital-event',
                    'event-page-image',
                    true,
                );
            }
        });
    }

    private function randomHospitalId(): int
    {
        /** @var array<int, int>|null $hospitalIds */
        static $hospitalIds = null;

        if ($hospitalIds === null) {
            $hospitalIds = Hospital::query()
                ->where('status', Hospital::STATUS_ACTIVE)
                ->where('allow_status', Hospital::ALLOW_APPROVED)
                ->pluck('id')
                ->map(static fn (int|string $id): int => (int) $id)
                ->all();
        }

        if ($hospitalIds === []) {
            $created = Hospital::factory()->active()->approved()->withBusinessRegistration()->withSeedMedia()->create();
            $hospitalIds[] = (int) $created->id;

            return (int) $created->id;
        }

        return $hospitalIds[array_rand($hospitalIds)];
    }
}
