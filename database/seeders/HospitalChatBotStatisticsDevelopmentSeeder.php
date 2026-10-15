<?php

namespace Database\Seeders;

use App\Common\Support\DevelopmentDataGuard;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use App\Domains\HospitalChatBot\Models\HospitalChatBotEvent;
use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class HospitalChatBotStatisticsDevelopmentSeeder extends Seeder
{
    public const SOURCE = 'local-chatbot-statistics-v1';

    public function run(): void
    {
        DevelopmentDataGuard::assertEnabled();
        DB::transaction(function (): void {
            if (HospitalChatBotConsultation::where('source_application', self::SOURCE)->exists()) {
                return;
            }
            $staff = AccountStaff::where('nickname', 'test_super_admin')->firstOrFail();
            $hospitals = Hospital::where('allow_status', 'APPROVED')->where('status', 'ACTIVE')
                ->whereHas('accountHospital', fn ($q) => $q->where('status', 'ACTIVE'))->with('accountHospital')->orderBy('id')->get();
            if ($hospitals->isEmpty()) {
                throw new \LogicException('챗봇 통계에는 기존 승인 병의원 계정이 필요합니다.');
            }
            $this->tree();
            $questions = HospitalChatBotQna::where('status', 'ACTIVE')
                ->whereNotIn('id', HospitalChatBotQna::select('parent_id')->whereNotNull('parent_id'))->orderBy('id')->take(20)->get();
            if ($questions->isEmpty()) {
                throw new \LogicException('챗봇 통계에는 답변 가능한 말단 Q&A가 필요합니다.');
            }
            $now = now('Asia/Seoul');
            for ($day = 89; $day >= 0; $day--) {
                for ($index = 0; $index < 3 + $day % 5; $index++) {
                    $at = $now->copy()->subDays($day)->startOfDay()->addHours(9 + $index)->addMinutes(10 + $day % 35);
                    if ($at->copy()->addHour()->isAfter($now)) {
                        continue;
                    }
                    $at = $at->setTimezone(config('app.timezone'));
                    $hospital = $hospitals[($day + $index) % $hospitals->count()];
                    $account = $hospital->accountHospital;
                    $question = $questions[($day * 3 + $index) % $questions->count()];
                    $reason = match (($day + $index) % 5) {
                        0 => 'WAITING_TIMEOUT', 1 => 'USER_CANCELED', default => 'AGENT_COMPLETED'
                    };
                    $ended = $reason === 'AGENT_COMPLETED';
                    $connected = $ended ? $at->copy()->addSeconds(45 + $day % 8 * 30) : null;
                    $closed = $ended ? $connected->copy()->addMinutes(8 + $index) : $at->copy()->addMinutes($reason === 'WAITING_TIMEOUT' ? 30 : 2);
                    $context = $index % 4 === 0 ? null : $question;
                    $consultation = HospitalChatBotConsultation::create([
                        'hospital_id' => $hospital->id, 'hospital_name_snapshot' => $hospital->name,
                        'requested_by_account_hospital_id' => $account->id, 'requester_name_snapshot' => $account->name,
                        'context_qna_id' => $context?->id, 'context_qna_question_snapshot' => $context?->question,
                        'context_qna_answer_snapshot' => $context?->answer, 'status' => $ended ? 'ENDED' : 'CANCELED',
                        'active_hospital_id' => null, 'assigned_staff_id' => $ended ? $staff->id : null,
                        'assigned_staff_name_snapshot' => $ended ? $staff->name : null,
                        'requested_at' => $at, 'connected_at' => $connected, 'ended_at' => $ended ? $closed : null,
                        'canceled_at' => $ended ? null : $closed, 'close_reason' => $reason,
                        'last_message_sequence' => $ended ? 2 : 0,
                        'last_message_at' => $ended ? $connected->copy()->addSeconds(80 + $index * 10) : null,
                        'source_application' => self::SOURCE, 'source_page_path' => '/sample/statistics/'.$day.'/'.$index,
                        'version' => $ended ? 2 : 1, 'created_at' => $at, 'updated_at' => $closed,
                    ]);
                    $consultation->statusHistories()->create(['from_status' => null, 'to_status' => 'WAITING', 'actor_type' => 'HOSPITAL_USER', 'actor_id' => $account->id, 'occurred_at' => $at]);
                    if ($ended) {
                        $consultation->statusHistories()->create(['from_status' => 'WAITING', 'to_status' => 'CONNECTED', 'actor_type' => 'AGENT', 'actor_id' => $staff->id, 'occurred_at' => $connected]);
                        foreach ([1, 2] as $sequence) {
                            $consultation->messages()->create([
                                'sequence' => $sequence, 'sender_type' => $sequence === 1 ? 'HOSPITAL_USER' : 'AGENT',
                                'sender_id' => $sequence === 1 ? $account->id : $staff->id,
                                'sender_name_snapshot' => $sequence === 1 ? $account->name : $staff->name,
                                'client_message_id' => 'stats-'.$consultation->id.'-'.$sequence,
                                'message_type' => 'TEXT', 'content' => $sequence === 1 ? $question->question : '개발용 상담입니다. '.$question->answer,
                                'broadcast_pending' => false,
                                'created_at' => $sequence === 1 ? $connected->copy()->addSeconds(10) : $connected->copy()->addSeconds(80 + $index * 10),
                            ]);
                        }
                    }
                    $consultation->statusHistories()->create([
                        'from_status' => $ended ? 'CONNECTED' : 'WAITING', 'to_status' => $consultation->status,
                        'actor_type' => $ended ? 'AGENT' : ($reason === 'WAITING_TIMEOUT' ? 'SYSTEM' : 'HOSPITAL_USER'),
                        'actor_id' => $ended ? $staff->id : ($reason === 'WAITING_TIMEOUT' ? null : $account->id),
                        'occurred_at' => $closed, 'reason' => $reason,
                    ]);
                    for ($view = 0; $view < 3 + $index % 3; $view++) {
                        foreach (['WIDGET_OPENED', 'QNA_VIEWED'] as $type) {
                            HospitalChatBotEvent::create([
                                'hospital_id' => $hospital->id, 'account_hospital_id' => $account->id,
                                'event_type' => $type, 'qna_id' => $type === 'QNA_VIEWED' ? $question->id : null,
                                'source_page_path' => '/sample/statistics',
                                'occurred_at' => $at->copy()->subSeconds(60 + $view * 5),
                            ]);
                        }
                    }
                }
            }
        });
    }

    private function tree(): void
    {
        $parent = null;
        foreach (['[개발용] 이용 안내', '상담 안내', '상담 진행은 어떻게 하나요?'] as $depth => $question) {
            $parent = HospitalChatBotQna::withTrashed()->firstOrCreate([
                'question_key' => HospitalChatBotQna::questionKey($question, $parent?->id),
            ], ['parent_id' => $parent?->id, 'depth' => $depth + 1, 'question' => $question,
                'answer' => $depth === 2 ? '상담 목록에서 담당자를 연결해 문의를 이어갈 수 있습니다.' : '', 'category' => '', 'sort_order' => 90]);
        }
        HospitalChatBotQna::withTrashed()->firstOrCreate(['question_key' => HospitalChatBotQna::questionKey('[개발용] 비공개 질문')], [
            'question' => '[개발용] 비공개 질문', 'answer' => '검토 중인 답변입니다.', 'category' => '', 'status' => 'INACTIVE', 'depth' => 1,
        ]);
    }
}
