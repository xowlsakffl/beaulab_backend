<?php

namespace Database\Seeders;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
use App\Domains\HospitalReview\Models\HospitalReviewCommentMention;
use Database\Factories\CategoryFactory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

final class HospitalReviewSeeder extends Seeder
{
    public function run(): void
    {
        CategoryFactory::seedHospitalCategories();
        $this->ensureSeedUsers();
        $this->ensureSeedHospitals();

        $authorIds = $this->activeUserIds();
        $usersById = $this->usersById($authorIds);
        $hospitalIds = $this->approvedHospitalIds();
        $categoryIdsByDomain = $this->categoryIdsByDomain(HospitalReview::categoryDomains());

        if ($authorIds === [] || $hospitalIds === [] || $categoryIdsByDomain === []) {
            $this->command?->warn('HospitalReviewSeeder skipped: active users, approved hospitals, or review categories are missing.');

            return;
        }

        $normalReviews = $this->seedReviews(
            count: 120,
            authorIds: $authorIds,
            hospitalIds: $hospitalIds,
            categoryIdsByDomain: $categoryIdsByDomain,
            factoryState: 'active',
        );

        $sampleReviews = collect()
            ->merge($this->seedReviews(8, $authorIds, $hospitalIds, $categoryIdsByDomain, 'inactive'));

        $this->seedComments($normalReviews->merge($sampleReviews), $authorIds, $usersById);
    }

    private function ensureSeedUsers(): void
    {
        if (AccountUser::query()->where('status', AccountUser::STATUS_ACTIVE)->exists()) {
            return;
        }

        AccountUser::factory()->count(10)->create();
    }

    private function ensureSeedHospitals(): void
    {
        if (Hospital::query()
            ->where('status', Hospital::STATUS_ACTIVE)
            ->where('allow_status', Hospital::ALLOW_APPROVED)
            ->exists()
        ) {
            return;
        }

        $hospitals = Hospital::factory()
            ->count(5)
            ->active()
            ->approved()
            ->withBusinessRegistration()
            ->create();

        foreach ($hospitals as $hospital) {
            HospitalDoctor::factory()
                ->count(2)
                ->forHospital($hospital)
                ->active()
                ->approved()
                ->withSeedMedia()
                ->create();
        }
    }

    /**
     * @param  array<int, int>  $authorIds
     * @param  array<int, int>  $hospitalIds
     * @param  array<string, array<int, int>>  $categoryIdsByDomain
     * @return Collection<int, HospitalReview>
     */
    private function seedReviews(
        int $count,
        array $authorIds,
        array $hospitalIds,
        array $categoryIdsByDomain,
        string $factoryState,
    ): Collection {
        $reviews = collect();

        for ($index = 0; $index < $count; $index++) {
            $domain = array_rand($categoryIdsByDomain);
            $hospitalId = $hospitalIds[array_rand($hospitalIds)];

            $review = HospitalReview::factory()
                ->{$factoryState}()
                ->withSeedMedia(random_int(1, 3), random_int(1, 3))
                ->create([
                    'author_id' => $authorIds[array_rand($authorIds)],
                    'hospital_id' => $hospitalId,
                    'doctor_id' => $this->randomDoctorId($hospitalId),
                    'category_domain' => $domain,
                ]);

            $this->syncCategories($review, $categoryIdsByDomain[$domain]);
            $reviews->push($review);
        }

        return $reviews;
    }

    /**
     * @param  iterable<int, HospitalReview>  $reviews
     * @param  array<int, int>  $authorIds
     * @param  Collection<int|string, AccountUser>  $usersById
     */
    private function seedComments(iterable $reviews, array $authorIds, Collection $usersById): void
    {
        foreach ($reviews as $review) {
            $topLevelComments = HospitalReviewComment::factory()
                ->count(random_int(0, 8))
                ->active()
                ->topLevel()
                ->create([
                    'hospital_review_id' => $review->id,
                    'author_id' => fn () => $authorIds[array_rand($authorIds)],
                ]);

            foreach ($topLevelComments as $comment) {
                HospitalReviewComment::factory()
                    ->count(random_int(0, 2))
                    ->active()
                    ->replyTo($comment)
                    ->create([
                        'author_id' => fn () => $authorIds[array_rand($authorIds)],
                    ]);
            }

            $this->seedCommentStatusSamples($review, $authorIds, $topLevelComments);
            $this->seedMentions($review, $usersById);

            $review->forceFill([
                'comment_count' => (int) HospitalReviewComment::query()
                    ->where('hospital_review_id', $review->id)
                    ->count(),
            ])->save();
        }
    }

    /**
     * @param  Collection<int|string, AccountUser>  $usersById
     */
    private function seedMentions(HospitalReview $review, Collection $usersById): void
    {
        if ($usersById->count() < 2) {
            return;
        }

        $mentionCount = random_int(0, 5);
        if ($mentionCount === 0) {
            return;
        }

        $comments = HospitalReviewComment::query()
            ->where('hospital_review_id', $review->id)
            ->where('status', HospitalReviewComment::STATUS_ACTIVE)
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
    private function createMentionForComment(HospitalReviewComment $comment, Collection $usersById): void
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

        HospitalReviewCommentMention::query()->updateOrCreate(
            ['hospital_review_comment_id' => (int) $comment->id],
            [
                'mentioned_user_id' => (int) $mentionedUser->id,
                'mentioned_by_user_id' => $authorId,
                'mention_text' => $this->mentionText($mentionedUser),
            ],
        );
    }

    /**
     * @param  array<int, int>  $authorIds
     */
    private function seedCommentStatusSamples(HospitalReview $review, array $authorIds, iterable $parentCandidates): void
    {
        $parentComments = collect($parentCandidates)
            ->filter(static fn (HospitalReviewComment $comment): bool => $comment->isRootComment())
            ->values()
            ->all();

        $samples = [
            ['chance' => 8, 'factory_state' => 'inactive', 'prefix' => '[비노출 샘플]'],
        ];

        foreach ($samples as $sample) {
            if (random_int(1, 100) > $sample['chance']) {
                continue;
            }

            $parent = null;
            if (random_int(1, 100) <= 55) {
                if ($parentComments === []) {
                    $parentComments[] = HospitalReviewComment::factory()
                        ->active()
                        ->topLevel()
                        ->create([
                            'hospital_review_id' => $review->id,
                            'author_id' => $authorIds[array_rand($authorIds)],
                            'content' => '[상태 샘플 부모 댓글] '.fake()->sentence(14),
                        ]);
                }

                $parent = $parentComments[array_rand($parentComments)];
            }

            $factory = HospitalReviewComment::factory()->{$sample['factory_state']}();
            $factory = $parent instanceof HospitalReviewComment
                ? $factory->replyTo($parent)
                : $factory->topLevel();

            $factory->count(random_int(1, 2))->create([
                'hospital_review_id' => $review->id,
                'author_id' => fn () => $authorIds[array_rand($authorIds)],
                'content' => fn () => $sample['prefix'].' '.fake()->sentence(14),
            ]);
        }
    }

    /**
     * @param  array<int, int>  $categoryIds
     */
    private function syncCategories(HospitalReview $review, array $categoryIds): void
    {
        $selectedIds = collect($categoryIds)
            ->shuffle()
            ->take(random_int(1, min(3, count($categoryIds))))
            ->values();

        $review->categories()->sync(
            $selectedIds
                ->mapWithKeys(static fn (int $categoryId, int $index): array => [
                    $categoryId => ['is_primary' => $index === 0],
                ])
                ->all(),
        );
    }

    /**
     * @return array<int, int>
     */
    private function activeUserIds(): array
    {
        return AccountUser::query()
            ->where('status', AccountUser::STATUS_ACTIVE)
            ->pluck('id')
            ->map(static fn (int|string $id): int => (int) $id)
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>  $authorIds
     * @return Collection<int|string, AccountUser>
     */
    private function usersById(array $authorIds): Collection
    {
        if ($authorIds === []) {
            return collect();
        }

        return AccountUser::query()
            ->whereIn('id', $authorIds)
            ->get(['id', 'name', 'nickname'])
            ->keyBy('id');
    }

    private function mentionText(AccountUser $user): string
    {
        return (string) ($user->nickname ?: $user->name ?: "user_{$user->id}");
    }

    /**
     * @return array<int, int>
     */
    private function approvedHospitalIds(): array
    {
        return Hospital::query()
            ->where('status', Hospital::STATUS_ACTIVE)
            ->where('allow_status', Hospital::ALLOW_APPROVED)
            ->pluck('id')
            ->map(static fn (int|string $id): int => (int) $id)
            ->values()
            ->all();
    }

    private function randomDoctorId(int $hospitalId): ?int
    {
        $doctorIds = HospitalDoctor::query()
            ->where('hospital_id', $hospitalId)
            ->where('status', HospitalDoctor::STATUS_ACTIVE)
            ->where('allow_status', HospitalDoctor::ALLOW_APPROVED)
            ->pluck('id')
            ->map(static fn (int|string $id): int => (int) $id)
            ->values()
            ->all();

        if ($doctorIds === []) {
            $doctor = HospitalDoctor::factory()
                ->forHospital($hospitalId)
                ->active()
                ->approved()
                ->withSeedMedia()
                ->create();

            return (int) $doctor->id;
        }

        return $doctorIds[array_rand($doctorIds)];
    }

    /**
     * @param  array<int, string>  $domains
     * @return array<string, array<int, int>>
     */
    private function categoryIdsByDomain(array $domains): array
    {
        $categories = Category::query()
            ->whereIn('domain', $domains)
            ->where('status', Category::STATUS_ACTIVE)
            ->where('depth', 3)
            ->whereDoesntHave('children')
            ->orderBy('domain')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'domain']);

        $grouped = [];
        foreach ($categories as $category) {
            $grouped[(string) $category->domain][] = (int) $category->id;
        }

        return array_filter($grouped, static fn (array $ids): bool => $ids !== []);
    }
}
