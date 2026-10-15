<?php

namespace Database\Seeders;

use App\Common\Support\DevelopmentDataGuard;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\AdminNote\Models\AdminNote;
use App\Domains\Common\AdminNote\Support\AdminNoteTargetRegistry;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Support\OperationHistoryTargetRegistry;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Models\HospitalStatusChangeRequest;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/** 전체 데모에서 생성한 레코드의 연관 데이터와 집계만 완성한다. 단독 실행하지 않는다. */
final class DevelopmentScenarioCompletionSeeder extends Seeder
{
    public function run(): void
    {
        DevelopmentDataGuard::assertEnabled();
        if (! config('seeding.development.completing_demo')) {
            throw new \LogicException('DemoSeeder 안에서만 실행할 수 있습니다.');
        }
        $staff = AccountStaff::where('nickname', 'test_super_admin')->firstOrFail();
        $this->accounts();
        $this->hospitalRequests($staff);
        $this->interactions();
        $this->emptyMemberAndReads();
        $this->histories($staff);
        $this->call(StaffNotificationDevelopmentSeeder::class);
    }

    private function accounts(): void
    {
        foreach (['restricted' => 'ACTIVE', 'suspended' => 'SUSPENDED', 'blocked' => 'BLOCKED'] as $key => $status) {
            $staff = AccountStaff::factory()->create([
                'nickname' => 'test_'.$key, 'email' => 'test_'.$key.'@beaulab.test',
                'name' => ['restricted' => '테스트 권한없음', 'suspended' => '테스트 정지직원', 'blocked' => '테스트 차단직원'][$key],
                'status' => $status, 'password' => Hash::make((string) config('seeding.staff.password')),
                'department' => '개발팀', 'job_title' => '테스트',
            ]);
            $staff->syncRoles([\App\Common\Authorization\AccessRoles::BEAULAB_STAFF]);
            $staff->syncPermissions([]);
            $staff->forceFill(['permissions_initialized_at' => now()])->saveQuietly();
        }
        foreach (['account_hospitals', 'account_beauties'] as $table) {
            DB::table($table)->orderBy('id')->get()->each(function ($account) use ($table): void {
                DB::table($table)->where('id', $account->id)->update([
                    'email' => $account->nickname.'@beaulab.test',
                    'password' => Hash::make((string) config('seeding.staff.password')),
                    'created_at' => now()->subMonths(13), 'email_verified_at' => now()->subMonths(13),
                ]);
            });
        }
        $hospitals = Hospital::orderBy('id')->get();
        foreach ([18 => 'SUSPENDED', 19 => 'BLOCKED', 23 => 'WITHDRAWN'] as $index => $status) {
            $hospitals[$index]->accountHospital()?->update(['status' => $status]);
        }
        $hospitals[17]->accountHospital()->update(['last_login_at' => null]);
    }

    private function hospitalRequests(AccountStaff $staff): void
    {
        $requester = AccountStaff::where('nickname', 'test_admin')->firstOrFail();
        $hospitals = Hospital::orderBy('id')->get();
        foreach (['PENDING', 'APPROVED', 'REJECTED', 'CANCELLED'] as $index => $status) {
            $hospital = $status === 'APPROVED' ? $hospitals[14] : $hospitals[8 + $index];
            HospitalStatusChangeRequest::factory()->create([
                'hospital_id' => $hospital->id, 'requested_by_staff_id' => $requester->id,
                'previous_status' => 'ACTIVE', 'target_status' => 'SUSPENDED', 'status' => $status,
                'reason' => '[개발용] 운영 일정 변경에 따른 중지 신청입니다.',
                'decision_reason' => $status === 'REJECTED' ? '개발용 보완 서류가 필요합니다.' : null,
                'processed_by_staff_id' => $status === 'PENDING' ? null : $staff->id,
                'processed_at' => $status === 'PENDING' ? null : now()->subHour(),
                'created_at' => now()->subHours(2), 'updated_at' => now()->subHour(),
            ]);
        }
        foreach (Hospital::whereDoesntHave('accountHospital')->orderBy('id')->get() as $index => $hospital) {
            DB::table('hospital_account_invitations')->insert([
                'source_type' => 'HOSPITAL', 'hospital_id' => $hospital->id,
                'recipient_email' => 'invitation-'.$hospital->id.'@beaulab.test',
                'token_hash' => hash('sha256', Str::random(64)),
                'expires_at' => $index === 1 ? now()->subDay() : now()->addDays(3),
                'sent_at' => now()->subDays(4), 'revoked_at' => $index === 2 ? now()->subDay() : null,
                'created_by_staff_id' => $staff->id, 'created_at' => now()->subDays(4), 'updated_at' => now()->subDays(4),
            ]);
        }
    }

    private function interactions(): void
    {
        $users = AccountUser::where('status', 'ACTIVE')->orderBy('id')->take(12)->get();
        foreach ([Talk::class, TalkComment::class, HospitalReview::class, HospitalReviewComment::class, HospitalVideo::class] as $model) {
            foreach ($model::orderBy('id')->get() as $index => $target) {
                $ids = $users->take($index % 6)->modelKeys();
                foreach ($ids as $userId) {
                    DB::table('likes')->insertOrIgnore([
                        'likeable_type' => $target->getMorphClass(), 'likeable_id' => $target->id,
                        'account_user_id' => $userId, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
                if (array_key_exists('like_count', $target->getAttributes())) {
                    $target->forceFill(['like_count' => count($ids)])->saveQuietly();
                }
                if ($target instanceof Talk) {
                    foreach ($users->take($index % 4) as $user) {
                        DB::table('talk_saves')->insertOrIgnore(['talk_id' => $target->id, 'account_user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()]);
                    }
                    $target->forceFill(['save_count' => $index % 4])->saveQuietly();
                } elseif ($target instanceof HospitalReview) {
                    // 후기 저장 기능의 관계 테이블은 아직 없다. 가짜 저장수를 만들지 않는다.
                    $target->forceFill(['save_count' => 0])->saveQuietly();
                }
            }
        }
        foreach (Talk::orderBy('id')->take(3)->get() as $index => $talk) {
            $poll = DB::table('talk_polls')->insertGetId(['talk_id' => $talk->id, 'allow_multiple' => $index === 1, 'created_at' => now(), 'updated_at' => now()]);
            foreach (['오전 상담', '오후 상담', '일정 협의'] as $sort => $text) {
                $voters = $index === 2 ? collect() : $users->slice($sort * 3, 3);
                $option = DB::table('talk_poll_options')->insertGetId(['talk_poll_id' => $poll, 'content' => $text, 'sort_order' => $sort, 'vote_count' => $voters->count(), 'created_at' => now(), 'updated_at' => now()]);
                foreach ($voters as $user) {
                    DB::table('talk_poll_votes')->insert(['talk_poll_id' => $poll, 'talk_poll_option_id' => $option, 'user_id' => $user->id, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        }
    }

    private function histories(AccountStaff $staff): void
    {
        foreach (OperationHistoryTargetRegistry::aliases() as $alias) {
            $model = OperationHistoryTargetRegistry::classForAlias($alias);
            foreach ($model::orderBy('id')->take(3)->get() as $target) {
                $history = app(OperationHistoryCreateAction::class)->execute(
                    target: $target, action: 'CREATED', actor: $staff, reason: '개발용 데이터 등록',
                    changes: [['field_key' => 'name', 'field_label' => '등록 내용', 'before' => null,
                        'after' => $target->getAttribute('name') ?? $target->getAttribute('title') ?? '개발용 샘플']],
                );
                $history->forceFill(['created_at' => $target->created_at, 'updated_at' => $target->created_at])->saveQuietly();
            }
        }
        foreach (AdminNoteTargetRegistry::aliases() as $alias) {
            $model = AdminNoteTargetRegistry::classForAlias($alias);
            foreach ($model::orderBy('id')->take(3)->get() as $target) {
                AdminNote::create([
                    'target_type' => $target::class, 'target_id' => $target->id,
                    'note' => "개발용 검수 메모입니다.\n연락처와 첨부 자료 확인이 필요한 상황을 재현합니다.",
                    'is_internal' => true, 'creator_type' => $staff->getMorphClass(), 'creator_id' => $staff->id,
                ]);
            }
        }
    }

    private function emptyMemberAndReads(): void
    {
        AccountUser::factory()->withPassword((string) config('seeding.staff.password'))->create([
            'name' => '테스트 빈회원', 'nickname' => '아직활동없는회원', 'email' => 'empty@beaulab.test',
            'phone' => null, 'last_login_at' => null, 'last_accessed_at' => null, 'last_access_ip' => null,
        ]);
        $users = AccountUser::where('status', 'ACTIVE')->orderBy('id')->take(3)->get();
        DB::table('account_user_blocks')->insert([
            'blocker_user_id' => $users[0]->id, 'blocked_user_id' => $users[2]->id,
            'blocked_at' => now(), 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach (\App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation::where('status', 'CONNECTED')->where('last_message_sequence', '>', 0)->get() as $index => $consultation) {
            DB::table('hospital_chatbot_consultation_reads')->insert([
                'consultation_id' => $consultation->id, 'staff_id' => $consultation->assigned_staff_id,
                'last_read_sequence' => $index % 2 ? max(0, $consultation->last_message_sequence - 2) : $consultation->last_message_sequence,
                'updated_at' => now(),
            ]);
        }
    }
}
