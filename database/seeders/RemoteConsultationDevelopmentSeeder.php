<?php

namespace Database\Seeders;

use App\Common\Support\DevelopmentDataGuard;
use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\RemoteConsultation\Actions\RemoteConsultationAnswerForHospitalAction;
use App\Domains\RemoteConsultation\Actions\RemoteConsultationAssignOnSubmissionAction;
use App\Domains\RemoteConsultation\Models\RemoteConsultation;
use App\Domains\RemoteConsultation\Queries\RemoteConsultationMatchingQuery;
use Database\Factories\Support\SeedMediaFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/** 전체 데모 또는 명시적으로 실행한 로컬 샘플에만 사용한다. */
final class RemoteConsultationDevelopmentSeeder extends Seeder
{
    public function run(): void
    {
        DevelopmentDataGuard::assertEnabled();
        if (! app()->environment(['local', 'testing'])) {
            throw new \LogicException('비대면 상담 시드는 local/testing 환경에서만 생성할 수 있습니다.');
        }

        $categories = Category::query();
        CategoryUsage::constrainActiveCategoryExists($categories, CategoryUsage::USAGE_HOSPITAL_SUBJECT);
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
        $users = AccountUser::where('status', 'ACTIVE')->where('created_at', '<', now()->subDays(120))->get();
        $staff = AccountStaff::where('status', 'ACTIVE')->orderBy('id')->firstOrFail();
        if ($users->isEmpty()) {
            throw new \LogicException('기존 활성 개발 회원이 필요합니다.');
        }
        for ($index = 1; $index <= 60; $index++) {
            $name = sprintf('[개발용] 상담회원 %02d', $index);
            $existing = RemoteConsultation::where('name', $name)->first();
            if ($existing) {
                $skipped++;

                continue;
            }

            // 기존 배정 가능 병의원만 사용하며 계정·권한이나 외부 알림은 변경하지 않는다.
            $reference = $hospitals[($index - 1) % $hospitals->count()];
            $selectedCategories = $reference->categories->take($index % 2 + 1);
            $eligible = $matching->eligible($selectedCategories->modelKeys())->orderBy('id')->limit(6)->get();
            $assigned = $eligible;
            $approved = $index % 4 === 0;
            $submittedAt = now()->subDays($index - 1)->subMinutes($index * 2);
            $approvedAt = $submittedAt->copy()->addMinute();

            DB::transaction(function () use ($index, $name, $selectedCategories, $assigned, $approved, $submittedAt, $approvedAt, $users, $staff): void {
                $user = $users[($index - 1) % $users->count()];
                $requested = $index % 3 === 0 ? $assigned->take(1)->modelKeys() : [];
                $consultation = RemoteConsultation::create([
                    'name' => $name,
                    'account_user_id' => $user->id,
                    'birth_date' => sprintf('%04d-%02d-15', 1975 + $index % 30, ($index - 1) % 12 + 1),
                    'gender' => $index % 2 === 0 ? 'MALE' : 'FEMALE',
                    'phone' => $user->phone,
                    'surgery_timing' => ['WITHIN_1_MONTH', 'WITHIN_2_MONTHS', 'WITHIN_3_MONTHS', 'WITHIN_4_MONTHS', 'WITHIN_6_MONTHS', 'UNDECIDED'][($index - 1) % 6],
                    'height_cm' => $index % 4 === 0 ? null : 155 + $index % 30,
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
                $slots = $index % 5 === 0 ? [] : ($index % 4 === 0 ? ['FRONT', 'SIDE', 'ANGLE_45', 'OTHER'] : ['FRONT']);
                foreach ($slots as $sort => $slot) {
                    $media = $mediaAction->attachOne($consultation, SeedMediaFactory::image('remote-consultation'), 'member_images', 'remote-consultations', 'member-images', $sort === 0, $sort);
                    $media->update(['metadata' => array_merge($media->metadata ?? [], ['slot' => $slot])]);
                }
                if ($index % 3 === 0) {
                    $mediaAction->attachMany($consultation, SeedMediaFactory::images('remote-consultation-style', 2), 'style_images', 'remote-consultations', 'style-images');
                }

                app(RemoteConsultationAssignOnSubmissionAction::class)->execute($consultation->id);
                if (! $approved) {
                    $consultation->update(['approval_status' => ['PENDING', 'PENDING', 'REVIEWING', 'REJECTED'][$index % 4]]);

                    return;
                }
                $consultation->update(['approval_status' => 'APPROVED', 'approved_at' => $approvedAt, 'approved_by_staff_id' => $staff->id]);
                foreach ($consultation->quotes()->get() as $offset => $quote) {
                    $answered = ($index + $offset) % 3 !== 0;
                    if ($answered) {
                        $actor = AccountHospital::where('hospital_id', $quote->hospital_id)->where('status', 'ACTIVE')->firstOrFail();
                        app(RemoteConsultationAnswerForHospitalAction::class)->execute($actor, $quote->id, [
                            'answer' => "희망 스타일을 확인했습니다. 개발용 가상 답변입니다.\n비용과 방문 가능 일정을 안내드립니다.",
                            'total_amount' => 1500000 + ($index + $offset) * 100000,
                        ]);
                        $quote->update(['quoted_categories' => $quote->matched_categories]);
                    }
                }
                $this->seedRecommendations($consultation);
            });
            $created++;
        }

        $this->command?->info("비대면 상담 시드: 신규 {$created}건, 기존 {$skipped}건 유지. 답변은 실제 소진 원장과 연결했습니다. 외부 발송은 없습니다.");
    }

    private function seedRecommendations(RemoteConsultation $consultation): void
    {
        foreach ($consultation->quotes()->where('status', 'ANSWERED')->get() as $quote) {
            if ($quote->recommended_events) {
                continue;
            }
            $events = HospitalEvent::where('hospital_id', $quote->hospital_id)->with('thumbnailImage')->orderBy('id')->get()->filter(fn ($event) => $event->isApplicationOpen())->take(3);
            $quote->update(['recommended_events' => $events->map(fn ($event) => [
                'id' => $event->id, 'title' => $event->name, 'price' => $event->event_price,
                'image_url' => $event->thumbnailImage?->publicUrl() ?? url('/test.png'),
            ])->all()]);
        }
    }
}
