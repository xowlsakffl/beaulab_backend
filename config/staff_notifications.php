<?php

return [
    // 수신 설정에서 제외할 유형. 발생 경로의 연결 여부는 messaging.md를 따른다.
    'hidden_types' => [
        'staff.hospital_event_db.reported',
        'staff.remote_consultation.assignment_failed',
    ],
];
