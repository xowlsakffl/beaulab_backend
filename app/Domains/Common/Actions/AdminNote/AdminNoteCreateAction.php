<?php

namespace App\Domains\Common\Actions\AdminNote;

use App\Domains\Common\Models\AdminNote\AdminNote;
use App\Domains\Common\Queries\AdminNote\AdminNoteCreateQuery;
use Illuminate\Database\Eloquent\Model;

final class AdminNoteCreateAction
{
    public function __construct(
        private readonly AdminNoteCreateQuery $query,
    ) {}

    public function execute(
        Model $target,
        string $note,
        ?int $createdByStaffId = null,
        bool $isInternal = true,
    ): AdminNote {
        return $this->query->create([
            'target_type' => $target::class,
            'target_id' => (int) $target->getKey(),
            'note' => trim($note),
            'is_internal' => $isInternal,
            'created_by_staff_id' => $createdByStaffId,
        ]);
    }
}
