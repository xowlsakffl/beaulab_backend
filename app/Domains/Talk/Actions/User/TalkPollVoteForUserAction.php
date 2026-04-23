<?php

namespace App\Domains\Talk\Actions\User;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Talk\Dto\User\TalkPollForUserDto;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkPoll;
use App\Domains\Talk\Models\TalkPollOption;
use App\Domains\Talk\Models\TalkPollVote;
use Illuminate\Support\Facades\DB;

/**
 * TalkPollVoteForUserAction 역할 정의.
 * 앱 사용자의 토크 투표 선택을 저장한다.
 */
final class TalkPollVoteForUserAction
{
    public function execute(AccountUser $user, Talk $talk, array $payload): array
    {
        if ($talk->status !== Talk::STATUS_ACTIVE || $talk->post_status !== Talk::POST_STATUS_NORMAL) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '투표할 수 없는 토크입니다.');
        }

        $optionIds = collect($payload['option_ids'] ?? [])
            ->map(static fn (int|string $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        $poll = DB::transaction(function () use ($user, $talk, $optionIds): TalkPoll {
            $poll = TalkPoll::query()
                ->where('talk_id', (int) $talk->id)
                ->lockForUpdate()
                ->first();

            if (! $poll instanceof TalkPoll) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '투표가 없는 토크입니다.');
            }

            if (! $poll->allow_multiple && count($optionIds) > 1) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '이 투표는 하나의 항목만 선택할 수 있습니다.');
            }

            $validOptionIds = TalkPollOption::query()
                ->where('talk_poll_id', (int) $poll->id)
                ->whereIn('id', $optionIds)
                ->pluck('id')
                ->map(static fn (int|string $id): int => (int) $id)
                ->sort()
                ->values()
                ->all();

            if ($validOptionIds !== collect($optionIds)->sort()->values()->all()) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '투표 항목이 올바르지 않습니다.');
            }

            $existingOptionIds = TalkPollVote::query()
                ->where('talk_poll_id', (int) $poll->id)
                ->where('user_id', (int) $user->id)
                ->lockForUpdate()
                ->pluck('talk_poll_option_id')
                ->map(static fn (int|string $id): int => (int) $id)
                ->values()
                ->all();

            $removeOptionIds = array_values(array_diff($existingOptionIds, $optionIds));
            $addOptionIds = array_values(array_diff($optionIds, $existingOptionIds));

            if ($removeOptionIds !== []) {
                TalkPollVote::query()
                    ->where('talk_poll_id', (int) $poll->id)
                    ->where('user_id', (int) $user->id)
                    ->whereIn('talk_poll_option_id', $removeOptionIds)
                    ->delete();

                TalkPollOption::query()
                    ->whereIn('id', $removeOptionIds)
                    ->decrement('vote_count');
            }

            if ($addOptionIds !== []) {
                $now = now();

                TalkPollVote::query()->insert(
                    collect($addOptionIds)
                        ->map(fn (int $optionId): array => [
                            'talk_poll_id' => (int) $poll->id,
                            'talk_poll_option_id' => $optionId,
                            'user_id' => (int) $user->id,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ])
                        ->all()
                );

                TalkPollOption::query()
                    ->whereIn('id', $addOptionIds)
                    ->increment('vote_count');
            }

            $refreshed = $poll->fresh(['options']);

            if (! $refreshed instanceof TalkPoll) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '투표 정보를 찾을 수 없습니다.');
            }

            return $refreshed;
        });

        return [
            'poll' => TalkPollForUserDto::fromModel($poll, $optionIds)->toArray(),
        ];
    }
}
