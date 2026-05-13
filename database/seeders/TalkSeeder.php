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
        $statusSampleTalks = $this->seedPostStatusSamples($authorIds);
        $talks = $normalTalks->merge($statusSampleTalks);

        $this->attachRandomCategories($talks, $talkCategoryIds);
        $this->seedComments($talks, $authorIds, $usersById);
    }

    /**
     * @param  array<int, int>  $authorIds
     */
    private function seedPostStatusSamples(array $authorIds): iterable
    {
        $samples = [
            [
                'count' => 16,
                'post_status' => Talk::POST_STATUS_AUTO_BLIND,
                'status' => Talk::STATUS_INACTIVE,
            ],
            [
                'count' => 10,
                'post_status' => Talk::POST_STATUS_ADMIN_STOP,
                'status' => Talk::STATUS_INACTIVE,
            ],
            [
                'count' => 8,
                'post_status' => Talk::POST_STATUS_USER_DELETE,
                'status' => Talk::STATUS_INACTIVE,
            ],
            [
                'count' => 6,
                'post_status' => Talk::POST_STATUS_NORMAL,
                'status' => Talk::STATUS_INACTIVE,
            ],
        ];

        $talks = collect();

        foreach ($samples as $sample) {
            $createdTalks = Talk::factory()
                ->withSeedMedia(random_int(1, Talk::MAX_IMAGE_COUNT))
                ->count($sample['count'])
                ->create([
                    'author_id' => fn () => $authorIds[array_rand($authorIds)],
                    'status' => $sample['status'],
                    'post_status' => $sample['post_status'],
                    'is_pinned' => false,
                    'pinned_order' => 0,
                ]);

            $talks = $talks->merge($createdTalks);
        }

        return $talks;
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

            $this->seedCommentPostStatusSamples($talk, $authorIds, $topLevelComments);
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
            ->where('post_status', TalkComment::POST_STATUS_NORMAL)
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

    /**
     * @param  array<int, int>  $authorIds
     */
    private function seedCommentPostStatusSamples(Talk $talk, array $authorIds, iterable $parentCandidates): void
    {
        $parentComments = collect($parentCandidates)
            ->filter(static fn (TalkComment $comment): bool => $comment->isRootComment())
            ->values()
            ->all();

        $samples = [
            [
                'chance' => 12,
                'factory_state' => 'systemBlocked',
                'prefix' => '[시스템 차단 샘플]',
            ],
            [
                'chance' => 8,
                'factory_state' => 'adminStopped',
                'prefix' => '[게시중단 샘플]',
            ],
            [
                'chance' => 6,
                'factory_state' => 'userDeleted',
                'prefix' => '[본인삭제 샘플]',
            ],
            [
                'chance' => 8,
                'factory_state' => 'inactive',
                'prefix' => '[비노출 정상 샘플]',
            ],
        ];

        foreach ($samples as $sample) {
            if (random_int(1, 100) > $sample['chance']) {
                continue;
            }

            $parent = null;
            if (random_int(1, 100) <= 60) {
                if ($parentComments === []) {
                    $parentComments[] = $this->createSampleParentComment($talk, $authorIds);
                }

                $parent = $parentComments[array_rand($parentComments)];
            }

            $factory = TalkComment::factory()->{$sample['factory_state']}();
            $factory = $parent instanceof TalkComment
                ? $factory->replyTo($parent)
                : $factory->topLevel();

            $factory->count(random_int(1, 2))->create([
                'talk_id' => $talk->id,
                'author_id' => fn () => $authorIds[array_rand($authorIds)],
                'content' => fn () => $sample['prefix'].' '.fake()->sentence(14),
            ]);
        }
    }

    /**
     * @param  array<int, int>  $authorIds
     */
    private function createSampleParentComment(Talk $talk, array $authorIds): TalkComment
    {
        return TalkComment::factory()
            ->active()
            ->topLevel()
            ->create([
                'talk_id' => $talk->id,
                'author_id' => fn () => $authorIds[array_rand($authorIds)],
                'content' => '[상태 샘플 부모 댓글] '.fake()->sentence(14),
            ]);
    }
}
