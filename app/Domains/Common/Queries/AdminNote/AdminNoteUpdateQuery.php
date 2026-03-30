<?php

namespace App\Domains\Common\Queries\AdminNote;

use App\Domains\Common\Models\AdminNote\AdminNote;

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
