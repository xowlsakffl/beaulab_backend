<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Queries\Staff;

use App\Domains\HospitalChatBot\Queries\HospitalChatBotQnaTreeQuery;
use Illuminate\Pagination\LengthAwarePaginator;

final class HospitalChatBotQnaListForStaffQuery
{
    public function __construct(private readonly HospitalChatBotQnaTreeQuery $tree) {}

    public function paginate(array $filters): LengthAwarePaginator
    {
        $nodes = $this->items($filters);
        $page = LengthAwarePaginator::resolveCurrentPage();

        return (new LengthAwarePaginator($nodes->forPage($page, $filters['per_page'])->values(), $nodes->count(), $filters['per_page'], $page, [
            'path' => LengthAwarePaginator::resolveCurrentPath(),
        ]))->withQueryString();
    }

    public function items(array $filters): \Illuminate\Support\Collection
    {
        $nodes = $this->tree->ordered($this->tree->all(), direction: $filters['direction'] ?? 'asc');
        if (! empty($filters['root_id'])) {
            $rootId = (int) $filters['root_id'];
            $nodes = $nodes->filter(fn ($node) => ($node->parent_id === null && $node->id === $rootId) || ($node->ancestor_ids[0] ?? null) === $rootId);
        }
        if (! empty($filters['q'])) {
            $keyword = mb_strtolower($filters['q']);
            $nodes = $nodes->filter(fn ($node) => str_contains(mb_strtolower($node->question.' '.$node->answer.' '.$node->tree_path), $keyword) || (string) $node->id === $keyword);
        }
        if (! empty($filters['status'])) {
            $nodes = $nodes->whereIn('status', $filters['status']);
        }
        if (! empty($filters['depth'])) {
            $nodes = $nodes->where('depth', (int) $filters['depth']);
        }
        if (! empty($filters['sort']) && $filters['sort'] !== 'sort_order') {
            $nodes = $nodes->sortBy([[$filters['sort'], $filters['direction']], ['id', 'asc']]);
        }

        return $nodes->values();
    }
}
