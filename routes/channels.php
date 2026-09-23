<?php

use App\Domains\AccountHospital\Models\AccountHospital;
use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Models\ChatParticipant;
use App\Domains\HospitalChatBot\Models\HospitalChatBotConsultation;
use Illuminate\Support\Facades\Broadcast;

// 앱 사용자 개인 알림 채널. 본인 userId 채널만 구독할 수 있다.
//
// 액터별 인가 라우트가 생기면서 앱 사용자가 아닌 계정도 이 콜백에 닿을 수 있다.
// 타입을 고정해 두면 TypeError로 500이 나므로, 종류를 확인해 거부로 돌려준다.
Broadcast::channel('user.{userId}', function ($account, int $userId): bool {
    return $account instanceof AccountUser && (int) $account->id === $userId;
});

// 채팅방 실시간 채널. 해당 채팅방 participant만 구독할 수 있다.
Broadcast::channel('chat.{chatId}', function ($account, int $chatId): bool {
    if (! $account instanceof AccountUser) {
        return false;
    }

    return ChatParticipant::query()
        ->where('chat_id', $chatId)
        ->where('account_user_id', $account->id)
        ->exists();
});

/**
 * 챗봇 상담 채널. 병원 사용자와 상담원이 함께 구독한다.
 *
 * 액터마다 인가 라우트가 다르므로($actor 별 /broadcasting/auth) 여기서는 모델 종류로 갈라
 * 각자의 조건만 본다. 타입을 느슨하게 받는 이유다.
 */
Broadcast::channel('hospital-chatbot.consultation.{consultationId}', function ($account, int $consultationId): bool {
    if ($account instanceof AccountStaff) {
        // 상담원은 아직 담당이 아니어도 목록에서 상세로 들어가 볼 수 있어야 한다.
        return HospitalChatBotConsultation::query()->whereKey($consultationId)->exists();
    }

    if ($account instanceof AccountHospital) {
        // 병원 사용자는 자기 병원의 상담만 본다.
        return $account->hospital_id !== null
            && HospitalChatBotConsultation::query()
                ->whereKey($consultationId)
                ->where('hospital_id', $account->hospital_id)
                ->exists();
    }

    return false;
});

/** 챗봇 대기 목록 채널. 병원명·요청자가 흐르므로 상담원만 구독한다. */
Broadcast::channel('hospital-chatbot.queue', function ($account): bool {
    return $account instanceof AccountStaff;
});
