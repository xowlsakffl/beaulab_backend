<?php

namespace App\Domains\Common\Queries\AdminNote;

use App\Domains\Common\Models\AdminNote\AdminNote;

final class AdminNoteCreateQuery
{
    public function create(array $payload): AdminNote
    {
        return AdminNote::create($payload);
    }
}
