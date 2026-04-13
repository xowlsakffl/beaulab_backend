<?php

namespace App\Domains\Common\Queries\AdminNote;

use App\Domains\Common\Models\AdminNote\AdminNote;

/**
 * AdminNoteUpdateQuery 역할 정의.
 * 공통 도메인의 Query 계층으로, Eloquent 조회/저장 조건을 캡슐화해 Action 계층에 DB 쿼리가 흩어지지 않게 한다.
 */
final class AdminNoteUpdateQuery
{
    public function update(AdminNote $note, array $payload): AdminNote
    {
        $note->fill($payload);

        if ($note->isDirty()) {
            $note->save();
        }

        return $note->fresh(['creator']);
    }
}
