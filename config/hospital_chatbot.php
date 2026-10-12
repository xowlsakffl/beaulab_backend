<?php

return [
    /*
     * 대기 상담 자동 만료 시간(분). PRD D-07 기본값 30분.
     * hospital-chatbot:expire-waiting-consultations 커맨드가 이 값을 사용한다.
     */
    'waiting_timeout_minutes' => (int) env('HOSPITAL_CHATBOT_WAITING_TIMEOUT_MINUTES', 30),

    /*
     * 상담 기록 CSV 내보내기 한도(PRD ADM-S06, EX-025).
     *
     * MVP는 동기 처리다. 한 요청이 응답을 붙잡고 메모리를 쓰므로 위쪽을 막아 둔다.
     * 여기 걸리면 범위를 줄이라고 안내하고, 규모가 커지면 비동기 Job으로 옮긴다.
     */
    'export' => [
        'max_days' => (int) env('HOSPITAL_CHATBOT_EXPORT_MAX_DAYS', 92),
        'max_rows' => (int) env('HOSPITAL_CHATBOT_EXPORT_MAX_ROWS', 10000),
        'chunk_size' => 500,
    ],

    /*
     * 이용 이벤트(열람·질문 클릭) 원본 보관 기간(일). 대시보드 집계에만 쓰는 행이라
     * 지난 것은 지운다. 더 길게 두려면 일별 합산 표가 먼저 필요하다(대시보드 PRD §13).
     */
    'events' => [
        'retention_days' => (int) env('HOSPITAL_CHATBOT_EVENTS_RETENTION_DAYS', 180),
        'prune_chunk' => 10000,
    ],
];
