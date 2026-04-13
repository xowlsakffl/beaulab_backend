<?php

namespace App\Domains\Chat\Actions\User;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Chat\Dto\User\ChatForUserDto;
use App\Domains\Chat\Queries\User\ChatListForUserQuery;

final class ChatListForUserAction
{
    public function __construct(
        private readonly ChatListForUserQuery $query,
    ) {}

    public function execute(AccountUser $user, array $filters): array
    {
        $paginator = $this->query->paginate((int) $user->id, $filters);

        return [
            'items' => collect($paginator->items())
                ->map(fn ($chat) => ChatForUserDto::fromModel($chat, (int) $user->id))
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ];
    }
}
