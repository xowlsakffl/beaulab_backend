<?php

namespace Database\Seeders;

use App\Common\Support\DevelopmentDataGuard;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\RemoteConsultation\Actions\RemoteConsultationAssignOnSubmissionAction;
use App\Domains\RemoteConsultation\Models\RemoteConsultation;
use App\Domains\RemoteConsultation\Queries\RemoteConsultationMatchingQuery;
use Database\Factories\Support\SeedMediaFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** Explicit opt-in fixtures. Never called from DatabaseSeeder. */
final class RemoteConsultationDevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        DevelopmentDataGuard::assertEnabled();
        if (! app()->environment(['local', 'testing'])) {
            throw new \LogicException('비대면 상담 시드는 local/testing 환경에서만 생성할 수 있습니다.');
        }

        $categories = Category::query();
        CategoryUsage::constrainActiveCategoryExists($categories, CategoryUsage::USAGE_HOSPITAL_DOCTOR_SUBJECT);
        $categories = $categories->orderBy('id')->get();
        if ($categories->isEmpty()) {
            throw new \LogicException('병의원 진료과목 카테고리 시드를 먼저 생성해 주세요.');
        }
        if (! is_file(public_path('test.png'))) {
            throw new \LogicException('개발용 샘플 이미지 public/test.png가 필요합니다.');
        }

        $matching = app(RemoteConsultationMatchingQuery::class);
        $hospitals = $matching->eligible($categories->modelKeys())->orderBy('id')->get();
        if ($hospitals->isEmpty()) {
            throw new \LogicException('배정 조건을 충족하는 기존 병의원이 최소 1곳 필요합니다. 계정과 병의원은 새로 생성하지 않습니다.');
        }

        $created = 0;
        $skipped = 0;
        $backfilled = 0;
        for ($index = 1; $index <= 24; $index++) {
            $name = sprintf('[개발용] 상담회원 %02d', $index);
            $existing = RemoteConsultation::where('name', $name)->first();
            if ($existing) {
                if ($existing->approval_status === 'PENDING' && ! $existing->quotes()->exists()) {
                    app(RemoteConsultationAssignOnSubmissionAction::class)->execute($existing->id);
                    $backfilled++;
                }
                $skipped++;
                $this->seedRecommendations($existing);

                continue;
            }

            // Use existing eligible hospitals only; do not modify accounts, permissions or send notifications.
            $reference = $hospitals[($index - 1) % $hospitals->count()];
            $selectedCategories = $reference->categories->take($index % 2 + 1);
            $eligible = $matching->eligible($selectedCategories->modelKeys())->orderBy('id')->limit(6)->get();
            $assigned = $eligible;
            $approved = $index > 12;
            $submittedAt = now()->subDays(($index - 1) % 14)->subMinutes($index * 2);
            $approvedAt = $submittedAt->copy()->addMinute();

            DB::transaction(function () use ($index, $name, $selectedCategories, $assigned, $approved, $submittedAt, $approvedAt): void {
                $requested = $index % 3 === 0 ? $assigned->take(1)->modelKeys() : [];
                $consultation = RemoteConsultation::create([
                    'name' => $name,
                    'birth_date' => sprintf('%04d-%02d-15', 1980 + $index, ($index - 1) % 12 + 1),
                    'gender' => $index % 2 === 0 ? 'MALE' : 'FEMALE',
                    'phone' => '010-0000-0000',
                    'surgery_timing' => ['WITHIN_1_MONTH', 'WITHIN_2_MONTHS', 'WITHIN_3_MONTHS', 'WITHIN_4_MONTHS', 'WITHIN_6_MONTHS', 'UNDECIDED'][($index - 1) % 6],
                    'height_cm' => $index % 4 === 0 ? null : 155 + $index,
                    'budget_amount' => $index % 4 === 0 ? null : 1000000 + $index * 100000,
                    'special_conditions' => $index % 3 === 0 ? [] : ($index % 2 === 0 ? ['FUNCTIONAL_ISSUE'] : ['REVISION', 'RECENT_SURGERY']),
                    'desired_style' => $index % 4 === 0 ? null : "자연스러운 스타일을 원합니다. 실제 신청이 아닌 화면 검증용 데이터입니다.\n회복 기간과 일상 복귀 시점을 함께 안내해 주세요.",
                    'inquiry' => $index % 5 === 0 ? null : '예상 비용과 회복 기간, 사후 관리 방법이 궁금합니다.',
                    'requested_hospital_ids' => $requested,
                    'approval_status' => 'PENDING',
                    'approved_at' => null,
                    'created_at' => $submittedAt,
                    'updated_at' => $approved ? $approvedAt : $submittedAt,
                ]);
                foreach ($selectedCategories as $category) {
                    $consultation->categories()->attach($category->id, ['category_name' => $category->name]);
                }

                $mediaAction = app(MediaAttachDeleteAction::class);
                $slots = $index % 4 === 0 ? ['FRONT', 'SIDE', 'ANGLE_45', 'OTHER'] : ['FRONT'];
                foreach ($slots as $sort => $slot) {
                    $media = $mediaAction->attachOne($consultation, SeedMediaFactory::image('remote-consultation'), 'member_images', 'remote-consultations', 'member-images', $sort === 0, $sort);
                    $media->update(['metadata' => array_merge($media->metadata ?? [], ['slot' => $slot])]);
                }
                if ($index % 3 === 0) {
                    $mediaAction->attachMany($consultation, SeedMediaFactory::images('remote-consultation-style', 2), 'style_images', 'remote-consultations', 'style-images');
                }

                app(RemoteConsultationAssignOnSubmissionAction::class)->execute($consultation->id);
                if (! $approved) {
                    return;
                }
                $consultation->update(['approval_status' => 'APPROVED', 'approved_at' => $approvedAt]);
                foreach ($consultation->quotes()->get() as $offset => $quote) {
                    $answered = ($index + $offset) % 3 !== 0;
                    $matched = $quote->matched_categories;
                    $quote->update([
                        'status' => $answered ? 'ANSWERED' : 'WAITING',
                        'quoted_categories' => $answered ? $matched : null,
                        'answer' => $answered ? "희망 스타일을 확인했습니다. 화면 검증용 가상 상담 안내입니다.\n예상 회복 기간은 1~2주이며, 세부 비용은 방문 상담 후 달라질 수 있습니다." : null,
                        'total_amount' => $answered ? 1500000 + ($index + $offset) * 100000 : null,
                        'recommended_events' => [],
                        'answered_at' => $answered ? $approvedAt->copy()->addMinute() : null,
                        'updated_at' => $answered ? $approvedAt->copy()->addMinute() : $approvedAt,
                    ]);
                }
                $this->seedRecommendations($consultation);
            });
            $created++;
        }

        $this->command?->info("비대면 상담 시드: 신규 {$created}건, 기존 {$skipped}건 유지, 대기 신청 배정 {$backfilled}건 보완. 메시지와 알림은 발송하지 않았습니다.");
    }

    private function seedRecommendations(RemoteConsultation $consultation): void
    {
        foreach ($consultation->quotes()->where('status', 'ANSWERED')->get() as $quote) {
            if ($quote->recommended_events) {
                continue;
            }
            $events = HospitalEvent::where('hospital_id', $quote->hospital_id)->with('thumbnailImage')->orderBy('id')->limit(3)->get();
            $quote->update(['recommended_events' => $events->map(fn ($event) => [
                'id' => $event->id, 'title' => $event->name, 'price' => $event->event_price,
                'image_url' => $event->thumbnailImage?->publicUrl() ?? url('/test.png'),
            ])->all()]);
        }
    }
}
