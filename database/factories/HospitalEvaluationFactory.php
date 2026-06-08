<?php

namespace Database\Factories;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use Database\Factories\Support\SeedMediaFactory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<HospitalEvaluation>
 */
final class HospitalEvaluationFactory extends Factory
{
    protected $model = HospitalEvaluation::class;

    public function definition(): array
    {
        $postStatus = $this->faker->randomElement(HospitalEvaluation::postStatuses());
        $hospitalId = $this->randomHospitalId();

        return [
            'author_id' => $this->randomAuthorId(),
            'hospital_id' => $hospitalId,
            'doctor_id' => $this->randomDoctorId($hospitalId),
            'category_domain' => $this->faker->randomElement(HospitalEvaluation::categoryDomains()),
            'content' => $this->faker->paragraphs(4, true),
            'phone' => $this->faker->phoneNumber(),
            'author_ip' => $this->faker->ipv4(),
            'cost' => $this->faker->numberBetween(20, 1500),
            'rating_staff_kindness' => $this->faker->numberBetween(1, 5),
            'rating_surgery_satisfaction' => $this->faker->numberBetween(1, 5),
            'rating_facility' => $this->faker->numberBetween(1, 5),
            'rating_aftercare' => $this->faker->numberBetween(1, 5),
            'rating_cost' => $this->faker->numberBetween(1, 5),
            'has_overtreatment' => $this->faker->boolean(18),
            'is_waiting_time_long' => $this->faker->boolean(35),
            'has_doctor_consultation' => $this->faker->boolean(70),
            'is_recommended' => $this->faker->boolean(82),
            'status' => $postStatus === HospitalEvaluation::POST_STATUS_NORMAL
                ? $this->faker->randomElement(HospitalEvaluation::statuses())
                : HospitalEvaluation::STATUS_INACTIVE,
            'post_status' => $postStatus,
            'view_count' => $this->faker->numberBetween(0, 20000),
            'receipt_status' => HospitalEvaluation::RECEIPT_STATUS_NONE,
            'receipt_rejection_reason' => null,
            'receipt_rejection_reason_text' => null,
        ];
    }

    public function active(): self
    {
        return $this->state(fn (): array => [
            'status' => HospitalEvaluation::STATUS_ACTIVE,
            'post_status' => HospitalEvaluation::POST_STATUS_NORMAL,
        ]);
    }

    public function inactive(): self
    {
        return $this->state(fn (): array => [
            'status' => HospitalEvaluation::STATUS_INACTIVE,
            'post_status' => HospitalEvaluation::POST_STATUS_NORMAL,
        ]);
    }

    public function autoBlind(): self
    {
        return $this->state(fn (): array => [
            'status' => HospitalEvaluation::STATUS_INACTIVE,
            'post_status' => HospitalEvaluation::POST_STATUS_AUTO_BLIND,
        ]);
    }

    public function adminStopped(): self
    {
        return $this->state(fn (): array => [
            'status' => HospitalEvaluation::STATUS_INACTIVE,
            'post_status' => HospitalEvaluation::POST_STATUS_ADMIN_STOP,
        ]);
    }

    public function userDeleted(): self
    {
        return $this->state(fn (): array => [
            'status' => HospitalEvaluation::STATUS_INACTIVE,
            'post_status' => HospitalEvaluation::POST_STATUS_USER_DELETE,
        ]);
    }

    public function receiptUploaded(): self
    {
        return $this->state(fn (): array => [
            'receipt_status' => HospitalEvaluation::RECEIPT_STATUS_UPLOADED,
            'receipt_rejection_reason' => null,
            'receipt_rejection_reason_text' => null,
        ]);
    }

    public function receiptVerified(): self
    {
        return $this->state(fn (): array => [
            'receipt_status' => HospitalEvaluation::RECEIPT_STATUS_VERIFIED,
            'receipt_rejection_reason' => null,
            'receipt_rejection_reason_text' => null,
        ]);
    }

    public function receiptRejected(?string $reason = null): self
    {
        $reason ??= $this->faker->randomElement(HospitalEvaluation::receiptRejectionReasons());

        return $this->state(fn (): array => [
            'receipt_status' => HospitalEvaluation::RECEIPT_STATUS_REJECTED,
            'receipt_rejection_reason' => $reason,
            'receipt_rejection_reason_text' => $reason === HospitalEvaluation::RECEIPT_REJECTION_REASON_OTHER
                ? $this->faker->sentence(10)
                : null,
        ]);
    }

    public function withSeedMedia(int $imageCount = 2, bool $withReceiptImages = false): self
    {
        return $this->afterCreating(function (HospitalEvaluation $evaluation) use ($imageCount, $withReceiptImages): void {
            $mediaAttachAction = app(MediaAttachDeleteAction::class);

            $mediaAttachAction->attachMany(
                $evaluation,
                SeedMediaFactory::images("hospital-evaluation-{$evaluation->id}", max(1, $imageCount)),
                'images',
                'hospital-evaluation',
                'images',
                true,
            );

            if (! $withReceiptImages) {
                return;
            }

            $mediaAttachAction->attachMany(
                $evaluation,
                SeedMediaFactory::images("hospital-evaluation-receipt-{$evaluation->id}", 1),
                'receipt_images',
                'hospital-evaluation',
                'receipt-images',
                true,
            );
        });
    }

    private function randomAuthorId(): int
    {
        /** @var array<int, int>|null $userIds */
        static $userIds = null;

        if ($userIds === null) {
            $userIds = AccountUser::query()
                ->where('status', AccountUser::STATUS_ACTIVE)
                ->pluck('id')
                ->map(static fn ($id): int => (int) $id)
                ->all();
        }

        if ($userIds === []) {
            $created = AccountUser::factory()->create();
            $userIds[] = (int) $created->id;

            return (int) $created->id;
        }

        return $userIds[array_rand($userIds)];
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
                ->map(static fn ($id): int => (int) $id)
                ->all();
        }

        if ($hospitalIds === []) {
            $created = Hospital::factory()->active()->approved()->create();
            $hospitalIds[] = (int) $created->id;

            return (int) $created->id;
        }

        return $hospitalIds[array_rand($hospitalIds)];
    }

    private function randomDoctorId(int $hospitalId): ?int
    {
        $doctorIds = HospitalDoctor::query()
            ->where('hospital_id', $hospitalId)
            ->where('status', HospitalDoctor::STATUS_ACTIVE)
            ->where('allow_status', HospitalDoctor::ALLOW_APPROVED)
            ->pluck('id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        if ($doctorIds === []) {
            $created = HospitalDoctor::factory()
                ->forHospital($hospitalId)
                ->active()
                ->approved()
                ->withSeedMedia()
                ->create();

            return (int) $created->id;
        }

        return $doctorIds[array_rand($doctorIds)];
    }
}
