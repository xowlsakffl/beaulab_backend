<?php

namespace App\Domains\Talk\Dto\User;

use App\Domains\Talk\Models\TalkPoll;
use App\Domains\Talk\Models\TalkPollOption;

/**
 * TalkPollForUserDto 역할 정의.
 * 앱 사용자에게 내려줄 토크 투표 응답을 정규화한다.
 */
final readonly class TalkPollForUserDto
{
    /**
     * @param  array<int, int>  $myVotedOptionIds
     */
    public function __construct(
        public int $id,
        public int $talkId,
        public bool $allowMultiple,
        public array $options,
        public array $myVotedOptionIds,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    /**
     * @param  array<int, int>  $myVotedOptionIds
     */
    public static function fromModel(TalkPoll $poll, array $myVotedOptionIds = []): self
    {
        $votedOptionIds = collect($myVotedOptionIds)
            ->map(static fn (int|string $id): int => (int) $id)
            ->values()
            ->all();

        return new self(
            id: (int) $poll->id,
            talkId: (int) $poll->talk_id,
            allowMultiple: (bool) $poll->allow_multiple,
            options: $poll->relationLoaded('options')
                ? $poll->options
                    ->map(fn (TalkPollOption $option): array => [
                        'id' => (int) $option->id,
                        'content' => (string) $option->content,
                        'sort_order' => (int) $option->sort_order,
                        'vote_count' => (int) $option->vote_count,
                        'is_voted' => in_array((int) $option->id, $votedOptionIds, true),
                    ])
                    ->values()
                    ->all()
                : [],
            myVotedOptionIds: $votedOptionIds,
            createdAt: $poll->created_at?->toISOString(),
            updatedAt: $poll->updated_at?->toISOString(),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'talk_id' => $this->talkId,
            'allow_multiple' => $this->allowMultiple,
            'options' => $this->options,
            'my_voted_option_ids' => $this->myVotedOptionIds,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];
    }
}
