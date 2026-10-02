<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Staff;

use App\Domains\HospitalChatBot\Queries\HospitalChatBotQnaTreeQuery;

final readonly class HospitalChatBotQnaTreeForStaffAction
{
    public function __construct(private HospitalChatBotQnaTreeQuery $query) {}

    public function execute(): array
    {
        return $this->query->ordered($this->query->all())->map(static fn ($node) => [
            'id' => $node->id, 'parent_id' => $node->parent_id, 'depth' => $node->depth,
            'question' => $node->question, 'parent_path' => $node->tree_path,
            'ancestor_ids' => $node->ancestor_ids, 'status' => $node->status,
        ])->all();
    }
}
