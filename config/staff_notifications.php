<?php

return [
    // 도메인에서 실제 발생 경로가 연결되면 이 목록에서 제거한다.
    'hidden_types' => [
        'staff.hospital_event_db.reported',
        'staff.remote_consultation.requested',
        'staff.remote_consultation.assignment_failed',
    ],
];
