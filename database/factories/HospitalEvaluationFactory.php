<?php

namespace Database\Factories;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\Hospital\Models\Hospital;
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

        return [
            'author_id' => AccountUser::factory(),
            'hospital_id' => Hospital::factory()->active()->approved(),
            'doctor_id' => null,
            'content' => $this->faker->randomElement(['예약 시간에 방문해 상담받았습니다. 직원 안내가 친절했고 진료 과정에 대한 설명을 충분히 들었습니다.', '시설이 깔끔하고 상담 내용과 예상 비용을 자세히 안내받았습니다.']),
            'phone' => $this->faker->numerify('010-0000-####'),
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
                ? '결제 내역과 진료 항목을 확인할 수 있는 영수증을 다시 첨부해 주세요.'
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
}
