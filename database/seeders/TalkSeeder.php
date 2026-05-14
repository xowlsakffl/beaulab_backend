<?php

namespace Database\Seeders;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;
use App\Domains\Talk\Models\TalkCommentMention;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

final class TalkSeeder extends Seeder
{
    public function run(): void
    {
        CategoryFactory::seedTalkCategories();

        $usersById = AccountUser::query()
            ->where('status', AccountUser::STATUS_ACTIVE)
            ->get(['id', 'name', 'nickname'])
            ->keyBy('id');

        if ($usersById->isEmpty()) {
            return;
        }

        $authorIds = $usersById
            ->keys()
            ->map(static fn (int|string $id): int => (int) $id)
            ->values()
            ->all();

        $talkCategoryIds = $this->loadTalkCategoryIds();

        $normalTalks = Talk::factory()
            ->count(160)
            ->active()
            ->withSeedMedia(random_int(1, Talk::MAX_IMAGE_COUNT))
            ->create()
            ->each(function (Talk $talk) use ($authorIds): void {
                $talk->forceFill([
                    'author_id' => $authorIds[array_rand($authorIds)],
                ])->save();
            });
        $talks = $normalTalks;

        $this->attachRandomCategories($talks, $talkCategoryIds);
        $this->seedComments($talks, $authorIds, $usersById);
    }

    /**
     * @return array<int, int>
     */
    private function loadTalkCategoryIds(): array
    {
        return Category::query()
            ->where('domain', Category::DOMAIN_TALK)
            ->whereIn('code', Talk::categoryCodes())
            ->orderBy('sort_order')
            ->orderBy('id')
            ->pluck('id')
            ->map(static fn (int|string $id): int => (int) $id)
            ->filter(static fn (int $id): bool => $id > 0)
            ->values()
            ->all();
    }

    /**
     * @param  iterable<int, Talk>  $talks
     * @param  array<int, int>  $categoryIds
     */
    private function attachRandomCategories(iterable $talks, array $categoryIds): void
    {
        if ($categoryIds === []) {
            return;
        }

        foreach ($talks as $talk) {
            $categoryId = (int) $categoryIds[array_rand($categoryIds)];
            $talk->categories()->sync([
                $categoryId => ['is_primary' => true],
            ]);
        }
    }

    /**
     * @param  iterable<int, Talk>  $talks
     * @param  array<int, int>  $authorIds
     * @param  Collection<int|string, AccountUser>  $usersById
     */
    private function seedComments(iterable $talks, array $authorIds, Collection $usersById): void
    {
        foreach ($talks as $talk) {
            $topLevelCount = random_int(0, 12);

            $topLevelComments = TalkComment::factory()
                ->count($topLevelCount)
                ->active()
                ->topLevel()
                ->create([
                    'talk_id' => $talk->id,
                    'author_id' => fn () => $authorIds[array_rand($authorIds)],
                ]);

            foreach ($topLevelComments as $comment) {
                $replyCount = random_int(0, 2);
                if ($replyCount === 0) {
                    continue;
                }

                TalkComment::factory()
                    ->count($replyCount)
                    ->active()
                    ->replyTo($comment)
                    ->create([
                        'author_id' => fn () => $authorIds[array_rand($authorIds)],
                    ]);
            }

            $this->seedMentions($talk, $usersById);

            $talk->forceFill([
                'comment_count' => (int) TalkComment::query()
                    ->where('talk_id', $talk->id)
                    ->count(),
            ])->save();
        }
    }

    /**
     * @param  Collection<int|string, AccountUser>  $usersById
     */
    private function seedMentions(Talk $talk, Collection $usersById): void
    {
        if ($usersById->count() < 2) {
            return;
        }

        $mentionCount = random_int(0, 5);
        if ($mentionCount === 0) {
            return;
        }

        $comments = TalkComment::query()
            ->where('talk_id', $talk->id)
            ->where('status', TalkComment::STATUS_ACTIVE)
            ->whereDoesntHave('mentions')
            ->inRandomOrder()
            ->limit($mentionCount)
            ->get(['id', 'author_id']);

        foreach ($comments as $comment) {
            $this->createMentionForComment($comment, $usersById);
        }
    }

    /**
     * @param  Collection<int|string, AccountUser>  $usersById
     */
    private function createMentionForComment(TalkComment $comment, Collection $usersById): void
    {
        $authorId = $comment->author_id !== null ? (int) $comment->author_id : null;
        $candidates = $usersById
            ->reject(static fn (AccountUser $user): bool => $authorId !== null && (int) $user->id === $authorId)
            ->values();

        if ($candidates->isEmpty()) {
            return;
        }

        /** @var AccountUser $mentionedUser */
        $mentionedUser = $candidates->random();

        TalkCommentMention::query()->updateOrCreate(
            ['talk_comment_id' => (int) $comment->id],
            [
                'mentioned_user_id' => (int) $mentionedUser->id,
                'mentioned_by_user_id' => $authorId,
                'mention_text' => $this->mentionText($mentionedUser),
            ],
        );
    }

    private function mentionText(AccountUser $user): string
    {
        return (string) ($user->nickname ?: $user->name ?: "user_{$user->id}");
    }

}
