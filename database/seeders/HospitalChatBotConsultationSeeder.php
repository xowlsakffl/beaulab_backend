<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Common\Support\DevelopmentDataGuard;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

final class HospitalChatBotConsultationSeeder extends Seeder
{
    public const SOURCE = 'local-chatbot-scenarios-v1';

    public function run(): void
    {
        DevelopmentDataGuard::assertEnabled();
        $connection = DB::connection();
        if (! app()->environment(['local', 'testing'])
            || ! in_array($connection->getConfig('host'), ['127.0.0.1', 'localhost', '::1'], true)) {
            throw new RuntimeException('챗봇 상담 샘플은 로컬 DB에서만 생성할 수 있습니다.');
        }

        $created = 0;
        DB::transaction(function () use (&$created): void {
            $staff = AccountStaff::query()->where('status', 'ACTIVE')->orderByRaw("nickname = 'test_super_admin' DESC")->orderBy('id')->get()
                ->filter(fn ($staff) => $staff->can(\App\Common\Authorization\AccessPermissions::BEAULAB_CHATBOT_CONSULTATION_PROCESS))->values();
            $hospitals = Hospital::query()->where('status', Hospital::STATUS_ACTIVE)
                ->where('allow_status', Hospital::ALLOW_APPROVED)
                ->whereHas('accountHospital', fn ($q) => $q->where('status', 'ACTIVE'))
                ->with('accountHospital')->orderBy('id')->get();
            if ($staff->isEmpty() || $hospitals->isEmpty()) {
                throw new RuntimeException('활성 직원과 승인된 병의원 계정이 필요합니다. 기존 계정은 생성·수정하지 않습니다.');
            }
            $scenarios = [
                ['WAITING', 0, null], ['WAITING', 0, null], ['WAITING', 0, null],
                ['CONNECTED', 8, null], ['CONNECTED', 12, null], ['CONNECTED', 0, null], ['CONNECTED', 245, null],
                ...array_map(fn ($i) => ['ENDED', 6 + $i * 2, $i % 2 ? 'USER_ENDED' : 'AGENT_COMPLETED'], range(0, 11)),
                ['CANCELED', 0, 'USER_CANCELED'], ['CANCELED', 0, 'WAITING_TIMEOUT'], ['CANCELED', 0, 'USER_CANCELED'],
            ];
            $topics = [
                ['이벤트 수정', '등록한 이벤트의 사진을 바꾸고 싶어요.', '이벤트 관리에서 수정할 이벤트를 선택한 뒤 이미지를 변경해 주세요.'],
                ['충전금 확인', '충전금 사용 내역은 어디서 확인하나요?', '충전금 내역에서 기간을 선택하면 사용 내역을 확인하실 수 있습니다.'],
                ['의료진 등록', '새로운 의료진 프로필을 등록하고 싶어요.', '의료진 메뉴에서 등록 버튼을 눌러 프로필과 사진을 입력해 주세요.'],
                ['계정 이메일', '계정 이메일 변경 절차를 알려 주세요.', '계정 정보를 확인한 뒤 이메일 변경 절차를 안내해 드리겠습니다.'],
            ];
            $now = now();
            foreach ($scenarios as $index => [$status, $count, $reason]) {
                $key = '/sample/consultation/'.($index + 1);
                if (HospitalChatBotConsultation::query()->where('source_application', self::SOURCE)->where('source_page_path', $key)->exists()) {
                    continue;
                }
                $active = in_array($status, ['WAITING', 'CONNECTED'], true);
                $hospital = $active
                    ? $hospitals->first(fn ($h) => ! HospitalChatBotConsultation::query()->where('active_hospital_id', $h->id)->exists())
                    : $hospitals[$index % $hospitals->count()];
                if (! $hospital) {
                    throw new RuntimeException('활성 상담이 없는 병의원이 부족합니다. 기존 상담을 덮어쓰지 않고 전체 추가를 취소합니다.');
                }
                $agent = $staff[$index === 4 && $staff->count() > 1 ? 1 : 0];
                $account = $hospital->accountHospital;
                [$topic, $question, $answer] = $topics[$index % count($topics)];
                $requested = $active
                    ? $now->copy()->subMinutes($status === 'WAITING' ? 3 + $index * 5 : 20 + $index)
                    : $now->copy()->subDays(1 + $index % 12)->subMinutes(50);
                $connected = in_array($status, ['CONNECTED', 'ENDED'], true) ? $requested->copy()->addMinute() : null;
                $closed = ! $active ? $requested->copy()->addMinutes($reason === 'WAITING_TIMEOUT' ? 30 : 20) : null;
                $consultation = HospitalChatBotConsultation::query()->create([
                    'hospital_id' => $hospital->id,
                    'hospital_name_snapshot' => $hospital->name,
                    'requested_by_account_hospital_id' => $account->id,
                    'requester_name_snapshot' => $hospital->name.' 담당자',
                    'context_qna_question_snapshot' => $question,
                    'context_qna_answer_snapshot' => $answer,
                    'status' => $status,
                    'active_hospital_id' => $active ? $hospital->id : null,
                    'assigned_staff_id' => $connected ? $agent->id : null,
                    'assigned_staff_name_snapshot' => $connected ? $agent->name : null,
                    'requested_at' => $requested,
                    'connected_at' => $connected,
                    'ended_at' => $status === 'ENDED' ? $closed : null,
                    'canceled_at' => $status === 'CANCELED' ? $closed : null,
                    'close_reason' => $reason,
                    'last_message_sequence' => $count,
                    'last_message_at' => $count ? $connected->copy()->addSeconds($count * 3) : null,
                    'source_application' => self::SOURCE,
                    'source_page_path' => $key,
                    'version' => $connected ? ($active ? 1 : 2) : ($active ? 0 : 1),
                    'created_at' => $requested,
                    'updated_at' => $closed ?? $now,
                ]);
                $history = fn ($from, $to, $actor, $actorId, $at, $why = null) => $consultation->statusHistories()->create([
                    'from_status' => $from, 'to_status' => $to, 'actor_type' => $actor,
                    'actor_id' => $actorId, 'occurred_at' => $at, 'reason' => $why,
                ]);
                $history(null, 'WAITING', 'HOSPITAL_USER', $account->id, $requested);
                if ($connected) {
                    $history('WAITING', 'CONNECTED', 'AGENT', $agent->id, $connected);
                }
                if ($closed) {
                    $actor = $reason === 'WAITING_TIMEOUT' ? 'SYSTEM' : ($reason === 'AGENT_COMPLETED' ? 'AGENT' : 'HOSPITAL_USER');
                    $history($connected ? 'CONNECTED' : 'WAITING', $status, $actor, $actor === 'SYSTEM' ? null : ($actor === 'AGENT' ? $agent->id : $account->id), $closed, $reason);
                }
                for ($sequence = 1; $sequence <= $count; $sequence++) {
                    $fromHospital = $sequence % 2 === 1;
                    $content = $fromHospital ? $question : $answer;
                    if ($sequence > 2) {
                        $content = $fromHospital
                            ? "{$topic} 관련해서 추가로 확인하고 싶은 내용이 있습니다. 확인 항목 {$sequence}번입니다."
                            : "확인 항목 {$sequence}번 안내드립니다.\n변경 내용을 저장한 뒤 목록에서 다시 확인해 주세요.\n추가 문의가 있으면 이 상담창에 남겨 주세요.";
                    }
                    $consultation->messages()->create([
                        'sequence' => $sequence,
                        'sender_type' => $fromHospital ? 'HOSPITAL_USER' : 'AGENT',
                        'sender_id' => $fromHospital ? $account->id : $agent->id,
                        'sender_name_snapshot' => $fromHospital ? $hospital->name.' 담당자' : $agent->name,
                        'client_message_id' => "sample-{$index}-{$sequence}",
                        'message_type' => 'TEXT', 'content' => $content,
                        'broadcast_pending' => false,
                        'created_at' => $connected->copy()->addSeconds($sequence * 3),
                    ]);
                }
                $created++;
            }
        });
        $this->command?->info("챗봇 상담 샘플 {$created}건 추가 완료. 기존 상담과 재실행 시 기존 샘플은 변경하지 않았습니다.");
    }
}
