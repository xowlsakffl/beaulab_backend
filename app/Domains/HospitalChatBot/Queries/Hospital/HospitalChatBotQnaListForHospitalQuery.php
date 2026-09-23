<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Queries\Hospital;

use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use Illuminate\Database\Eloquent\Collection;

final class HospitalChatBotQnaListForHospitalQuery
{
    /**
     * @return Collection<int, HospitalChatBotQna>
     */
    public function get(): Collection
    {
        return HospitalChatBotQna::query()
            ->active()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->with('folder:id,parent_id,name')
            ->get(['id', 'folder_id', 'category', 'question', 'answer', 'sort_order']);
    }
}
