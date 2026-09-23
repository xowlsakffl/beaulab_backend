<?php

return [
    /*
     * 대기 상담 자동 만료 시간(분). PRD D-07 기본값 30분.
     * hospital-chatbot:expire-waiting-consultations 커맨드가 이 값을 사용한다.
     */
    'waiting_timeout_minutes' => (int) env('HOSPITAL_CHATBOT_WAITING_TIMEOUT_MINUTES', 30),
];
