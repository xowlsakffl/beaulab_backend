<?php

namespace App\Domains\Talk\Queries\User;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Talk\Models\Talk;

final class TalkCreateForUserQuery
{
    public function categoryIdByCode(string $categoryCode): int
    {
        $categoryId = Category::query()
            ->where('domain', Talk::CATEGORY_DOMAIN)
            ->where('status', Category::STATUS_ACTIVE)
            ->where('code', $categoryCode)
            ->value('id');

        return is_numeric($categoryId) ? (int) $categoryId : 0;
    }

    public function create(array $payload): Talk
    {
        return Talk::query()->create([
            'author_id' => (int) $payload['author_id'],
            'title' => (string) $payload['title'],
            'content' => (string) $payload['content'],
            'status' => Talk::STATUS_ACTIVE,
            'author_ip' => $payload['author_ip'] ?? null,
            'is_pinned' => false,
            'pinned_order' => 0,
            'view_count' => 0,
            'comment_count' => 0,
            'like_count' => 0,
            'save_count' => 0,
        ]);
    }
}
