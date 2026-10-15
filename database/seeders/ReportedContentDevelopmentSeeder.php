<?php

namespace Database\Seeders;

use App\Common\Support\DevelopmentDataGuard;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Models\Chat;
use App\Domains\Chat\Models\ChatMessage;
use App\Domains\Chat\Support\ChatMatchKey;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\ContentReport\Models\ContentReport;
use App\Domains\Common\ContentReport\Models\ContentReportItem;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\Common\ContentReport\Support\ContentReportSummaryCache;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use LogicException;

final class ReportedContentDevelopmentSeeder extends Seeder
{
    private const PREFIX = '[개발용 신고] ';

    private const SCENARIOS = [
        ContentReportState::STATUS_REPORTED => 1,
        ContentReportState::STATUS_AUTO_BLOCKED => 10,
        ContentReportState::STATUS_ADMIN_HIDDEN => 2,
        ContentReportState::STATUS_NORMAL_VISIBLE => 3,
    ];

    private int $created = 0;

    public function run(): void
    {
        DevelopmentDataGuard::assertEnabled();
        $this->created = 0;
        $lock = 'development:reported-content:'.DB::connection()->getDatabaseName();
        if ((int) DB::selectOne('SELECT GET_LOCK(?, 0) AS acquired', [$lock])->acquired !== 1) {
            throw new LogicException('신고 개발 데이터 생성이 이미 실행 중입니다.');
        }

        try {
            Model::withoutEvents(fn () => DB::transaction(fn () => $this->seedContent()));
        } finally {
            DB::selectOne('SELECT RELEASE_LOCK(?)', [$lock]);
        }

        $this->command?->info("신고 대상 {$this->created}건을 추가했습니다. 기존 샘플과 게시물은 유지하며 외부 발송은 하지 않습니다.");
    }

    private function seedContent(): void
    {
        $hospital = Hospital::query()->where('status', Hospital::STATUS_ACTIVE)
            ->where('allow_status', Hospital::ALLOW_APPROVED)->orderBy('id')->firstOrFail();
        $staff = AccountStaff::query()->where('status', 'ACTIVE')->orderBy('id')->firstOrFail();
        $talkCategory = Category::query()->where('domain', Category::DOMAIN_TALK)
            ->where('status', Category::STATUS_ACTIVE)->orderBy('id')->firstOrFail();
        $evaluationCategory = Category::query()->where('domain', Category::DOMAIN_HOSPITAL_EVALUATION)
            ->where('status', Category::STATUS_ACTIVE)->orderBy('id')->firstOrFail();
        $reviewCategories = [];
        foreach (HospitalReview::categoryDomains() as $domain) {
            $reference = HospitalReview::query()->where('category_domain', $domain)
                ->whereHas('categories')->with('categories')->orderBy('id')->firstOrFail();
            $reviewCategories[$domain] = $reference->categories->first()->id;
        }
        if (! is_file(public_path('test.png'))) {
            throw new LogicException('신고 이미지 샘플용 public/test.png가 필요합니다.');
        }

        $author = $this->user('author', '신고확인작성자');
        $reporters = [];
        for ($index = 1; $index <= 10; $index++) {
            $reporters[] = $this->user("reporter{$index}", sprintf('신고확인회원%02d', $index));
        }

        foreach (self::SCENARIOS as $status => $count) {
            $statusLabel = ContentReportState::statusLabels()[$status];
            $hidden = in_array($status, [ContentReportState::STATUS_AUTO_BLOCKED, ContentReportState::STATUS_ADMIN_HIDDEN], true);
            $common = [
                'author_id' => $author->id, 'author_ip' => '127.0.0.1',
                'status' => $hidden ? 'INACTIVE' : 'ACTIVE',
                'content' => self::PREFIX.$statusLabel.' 화면 확인용 내용입니다. 실제 신고가 아닌 합성 데이터입니다.',
                'created_at' => now()->subDays(2), 'updated_at' => now()->subHours(2),
            ];
            $videoTitle = self::PREFIX.'동영상 '.$statusLabel;
            if ($status !== ContentReportState::STATUS_AUTO_BLOCKED && ! HospitalVideo::withTrashed()->where('title', $videoTitle)->exists()) {
                $video = HospitalVideo::factory()->forHospital($hospital)->withSeedCategories()->withSeedMedia()->create([
                    'title' => $videoTitle, 'hospital_status' => 'PUBLIC',
                    'admin_status' => $hidden ? 'FORCED_STOPPED' : 'NORMAL',
                    'created_at' => now()->subDays(2), 'updated_at' => now()->subHours(2),
                ]);
                $this->report($video, $status, $count, $reporters, $staff, ContentReportState::WARNING_STATUS_NONE);
            }

            foreach ($reviewCategories as $domain => $categoryId) {
                $label = $domain === HospitalReview::CATEGORY_DOMAIN_SURGERY ? '성형후기' : '쁘띠후기';
                $title = self::PREFIX.$label.' '.$statusLabel;
                if (HospitalReview::withTrashed()->where('author_id', $author->id)->where('title', $title)->exists()) {
                    continue;
                }
                $review = HospitalReview::factory()->withSeedMedia(1, 1)->create($common + [
                    'hospital_id' => $hospital->id, 'doctor_id' => null, 'category_domain' => $domain,
                    'title' => $title, 'cost' => 350, 'rating' => 4,
                    'view_count' => 120, 'comment_count' => 1, 'like_count' => 0, 'save_count' => 0,
                ]);
                $review->categories()->attach($categoryId, ['is_primary' => true]);
                $this->report($review, $status, $count, $reporters, $staff);
                $comment = HospitalReviewComment::factory()->create($common + [
                    'hospital_review_id' => $review->id, 'parent_id' => null, 'like_count' => 0,
                ]);
                $this->report($comment, $status, $count, $reporters, $staff);
            }

            $title = self::PREFIX.'토크 '.$statusLabel;
            if (! Talk::withTrashed()->where('author_id', $author->id)->where('title', $title)->exists()) {
                $talk = Talk::factory()->withSeedMedia(2)->create($common + [
                    'title' => $title, 'is_pinned' => false, 'pinned_order' => 0,
                    'view_count' => 80, 'comment_count' => 1, 'like_count' => 0, 'save_count' => 0,
                ]);
                $talk->categories()->attach($talkCategory->id, ['is_primary' => true]);
                $this->report($talk, $status, $count, $reporters, $staff);
                $comment = TalkComment::factory()->create($common + [
                    'talk_id' => $talk->id, 'parent_id' => null, 'like_count' => 0,
                ]);
                $this->report($comment, $status, $count, $reporters, $staff);
            }

            $content = self::PREFIX.'평가 '.$statusLabel.' 확인용입니다. 상담 과정과 시설에 대한 가상 평가입니다.';
            if (! HospitalEvaluation::withTrashed()->where('author_id', $author->id)->where('content', $content)->exists()) {
                $evaluation = HospitalEvaluation::factory()->active()->receiptUploaded()->withSeedMedia(2, true)->create(array_merge($common, [
                    'content' => $content, 'hospital_id' => $hospital->id, 'doctor_id' => null,
                    'phone' => '010-0000-0000', 'cost' => 250, 'view_count' => 65,
                    'rating_staff_kindness' => 5, 'rating_surgery_satisfaction' => 4,
                    'rating_facility' => 3, 'rating_aftercare' => 4, 'rating_cost' => 5,
                    'average_rating' => HospitalEvaluation::calculateAverageRating(5, 4, 3, 4, 5),
                    'has_overtreatment' => false, 'is_waiting_time_long' => true,
                    'has_doctor_consultation' => true, 'is_recommended' => true,
                ]));
                $evaluation->categories()->attach($evaluationCategory->id, ['is_primary' => true]);
                $this->report($evaluation, $status, $count, $reporters, $staff);
            }
        }

        $this->seedChat($author, $reporters, $staff);
        if ($this->created > 0) {
            // 모델 이벤트를 끈 시더에서 파생된 병의원 평점 집계만 명시적으로 갱신한다.
            HospitalEvaluation::refreshHospitalRatingAggregates((int) $hospital->id);
        }
    }

    private function user(string $key, string $name): AccountUser
    {
        $email = "reported-content-{$key}@seed.test";
        $existing = AccountUser::withTrashed()->where('email', $email)->first();
        if ($existing) {
            if ($existing->trashed()) {
                throw new LogicException('삭제된 신고 샘플 계정은 자동으로 복원하지 않습니다.');
            }

            return $existing;
        }

        return AccountUser::query()->create([
            'name' => $name, 'nickname' => $name, 'email' => $email,
            'phone' => null, 'signup_channel' => AccountUser::SIGNUP_CHANNEL_EMAIL,
            'password' => Hash::make(Str::random(64)), 'status' => AccountUser::STATUS_ACTIVE,
            'email_verified_at' => now(), 'last_accessed_at' => null,
            'created_at' => now()->subDays(3), 'updated_at' => now()->subDays(3),
        ]);
    }

    private function report(Model $target, string $status, int $count, array $reporters, AccountStaff $staff, ?string $warning = null): void
    {
        $lastAt = now()->subMinutes(5);
        $firstAt = $lastAt->copy()->subMinutes($count - 1);
        $authorId = $target->getAttribute('author_id') ?? $target->getAttribute('sender_user_id');
        $snapshot = $target->getAttribute('content') ?? $target->getAttribute('body') ?? $target->getAttribute('title');
        foreach (array_slice($reporters, 0, $count) as $index => $reporter) {
            $at = $firstAt->copy()->addMinutes($index);
            $attributes = [
                'reporter_user_id' => $reporter->id, 'target_type' => $target::class,
                'target_id' => $target->getKey(), 'target_author_id' => $authorId,
                'content_snapshot' => $snapshot, 'created_at' => $at, 'updated_at' => $at,
            ];
            $reason = ContentReport::reasons()[$index % count(ContentReport::reasons())];
            $report = ContentReport::query()->create($attributes + [
                'reason' => $reason, 'reporter_ip' => '127.0.0.1',
                'reason_text' => $reason === ContentReport::REASON_OTHER ? '개발용 신고 처리 화면 확인' : null,
            ]);
            ContentReportItem::query()->create($attributes + ['content_report_id' => $report->id]);
        }

        $processed = in_array($status, [ContentReportState::STATUS_ADMIN_HIDDEN, ContentReportState::STATUS_NORMAL_VISIBLE], true);
        $processedAt = $lastAt->copy()->addMinute();
        $warning ??= match ($status) {
            ContentReportState::STATUS_ADMIN_HIDDEN => ContentReportState::WARNING_STATUS_WARNED,
            ContentReportState::STATUS_NORMAL_VISIBLE => ContentReportState::WARNING_STATUS_IGNORED,
            default => ContentReportState::WARNING_STATUS_NONE,
        };
        ContentReportState::query()->create([
            'target_type' => $target::class, 'target_id' => $target->getKey(),
            'report_status' => $status, 'report_count' => $count,
            'recent_hour_report_count' => $status === ContentReportState::STATUS_NORMAL_VISIBLE ? 0 : $count,
            'normal_visible_count' => $status === ContentReportState::STATUS_NORMAL_VISIBLE ? 1 : 0,
            'first_reported_at' => $firstAt, 'last_reported_at' => $lastAt,
            'auto_blocked_at' => $status === ContentReportState::STATUS_AUTO_BLOCKED ? $lastAt : null,
            'admin_hidden_at' => $status === ContentReportState::STATUS_ADMIN_HIDDEN ? $processedAt : null,
            'normal_visible_at' => $status === ContentReportState::STATUS_NORMAL_VISIBLE ? $processedAt : null,
            'processed_by' => $processed ? $staff->id : null,
            'process_reason' => $processed ? '개발용 신고 처리 예시입니다.' : null,
            'warning_status' => $warning,
            'warning_processed_at' => $warning !== ContentReportState::WARNING_STATUS_NONE ? $processedAt : null,
            'warning_processed_by' => $warning !== ContentReportState::WARNING_STATUS_NONE ? $staff->id : null,
        ]);
        if ($warning === ContentReportState::WARNING_STATUS_WARNED) {
            $author = AccountUser::query()->lockForUpdate()->findOrFail($authorId);
            $author->warning_count++;
            if ($author->warning_count >= AccountUser::WARNING_BLOCK_THRESHOLD) {
                $author->status = AccountUser::STATUS_BLOCKED;
                $author->blocked_at ??= $processedAt;
            }
            $author->save();
        }
        $this->history($target, $staff, OperationHistory::ACTION_CREATED, $target->created_at, null, null);
        $field = $target instanceof ChatMessage ? 'body' : ($target instanceof HospitalVideo ? 'title' : 'content');
        $this->history($target, $staff, OperationHistory::ACTION_UPDATED, $firstAt->copy()->subMinute(), $field, $snapshot);
        if ($processed || $status === ContentReportState::STATUS_AUTO_BLOCKED) {
            $this->history($target, $staff, OperationHistory::ACTION_STATE_UPDATED,
                $status === ContentReportState::STATUS_AUTO_BLOCKED ? $lastAt : $processedAt,
                'report_status', $status, $status === ContentReportState::STATUS_AUTO_BLOCKED);
        }
        $target->forceFill(['updated_at' => $processed || $status === ContentReportState::STATUS_AUTO_BLOCKED
            ? $processedAt : $firstAt->copy()->subMinute()])->save();
        ContentReportSummaryCache::forgetForTarget($target);
        $this->created++;
    }

    private function history(Model $target, AccountStaff $staff, string $action, CarbonInterface $at, ?string $field, mixed $after, bool $system = false): void
    {
        $isUser = $action !== OperationHistory::ACTION_STATE_UPDATED && ! $target instanceof HospitalVideo;
        $history = OperationHistory::query()->create([
            'target_type' => $target::class, 'target_id' => $target->getKey(),
            'actor_type' => $system ? null : ($isUser ? AccountUser::class : AccountStaff::class),
            'actor_id' => $system ? null : ($isUser ? ($target->getAttribute('author_id') ?? $target->getAttribute('sender_user_id')) : $staff->id),
            'actor_kind' => $system ? OperationHistory::ACTOR_KIND_SYSTEM : ($isUser ? OperationHistory::ACTOR_KIND_USER : OperationHistory::ACTOR_KIND_STAFF),
            'action' => $action,
            'reason' => '개발용 신고 화면 확인 이력입니다.',
            'created_at' => $at, 'updated_at' => $at,
        ]);
        if ($field !== null) {
            $history->changes()->create([
                'field_key' => $field, 'field_label' => $field === 'report_status' ? '신고상태' : '내용',
                'before_value' => $field === 'report_status' ? ContentReportState::STATUS_REPORTED : '개발용 초안',
                'after_value' => $after, 'sort_order' => 0,
                'before_display' => $field === 'report_status' ? ContentReportState::statusLabels()[ContentReportState::STATUS_REPORTED] : '개발용 초안',
                'after_display' => $field === 'report_status' ? ContentReportState::statusLabels()[$after] : $after,
            ]);
        }
    }

    private function seedChat(AccountUser $author, array $reporters, AccountStaff $staff): void
    {
        foreach ([ContentReportState::WARNING_STATUS_NONE, ContentReportState::WARNING_STATUS_WARNED, ContentReportState::WARNING_STATUS_IGNORED] as $index => $warning) {
            $key = ChatMatchKey::forUsers($author->id, $reporters[$index]->id);
            if (Chat::withTrashed()->where('match_key', $key)->exists()) {
                continue;
            }
            $chat = Chat::query()->create([
                'match_key' => $key, 'created_by_user_id' => $author->id,
                'metadata' => ['development_fixture' => 'reported-content'],
                'created_at' => now()->subDays(2)->subHour(), 'updated_at' => now()->subDays(2),
            ]);
            foreach ([$author, $reporters[$index]] as $user) {
                $chat->participants()->create([
                    'account_user_id' => $user->id, 'notifications_enabled' => false,
                    'created_at' => $chat->created_at, 'updated_at' => $chat->created_at,
                ]);
            }
            $message = $chat->messages()->create([
                'sender_user_id' => $author->id, 'client_message_id' => "reported-content-{$index}",
                'body' => self::PREFIX.'채팅 신고 처리 화면을 확인하는 가상 대화입니다.',
                'message_type' => ChatMessage::TYPE_TEXT, 'broadcast_pending' => false,
                'created_at' => now()->subDays(2), 'updated_at' => now()->subDays(2),
            ]);
            $chat->update(['last_message_id' => $message->id, 'last_message_at' => $message->created_at]);
            $this->report($message, ContentReportState::STATUS_REPORTED, 1, [$reporters[$index]], $staff, $warning);
        }
    }
}
