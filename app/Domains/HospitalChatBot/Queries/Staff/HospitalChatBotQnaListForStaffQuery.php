<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Queries\Staff;

use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

final class HospitalChatBotQnaListForStaffQuery
{
    public function paginate(array $filters): LengthAwarePaginator
    {
        $query = HospitalChatBotQna::query()->with('folder:id,parent_id,name');

        if (! empty($filters['q'])) {
            $keyword = addcslashes((string) $filters['q'], '\\%_');
            $query->where(static function (Builder $builder) use ($keyword): void {
                $builder->where('question', 'like', "%{$keyword}%")
                    ->orWhere('answer', 'like', "%{$keyword}%");
            });
        }

        if (! empty($filters['folder_id'])) {
            $query->where('folder_id', (int) $filters['folder_id']);
        }

        if (! empty($filters['category'])) {
            $query->where('category', (string) $filters['category']);
        }

        if (is_array($filters['status'] ?? null) && $filters['status'] !== []) {
            $query->whereIn('status', $filters['status']);
        }

        if (! empty($filters['sort'])) {
            $query->orderBy((string) $filters['sort'], (string) $filters['direction']);
            if ($filters['sort'] !== 'id') {
                $query->orderBy('id');
            }
        } else {
            $query->orderBy('sort_order')->orderBy('id');
        }

        return $query->paginate((int) $filters['per_page'])->withQueryString();
    }
}
