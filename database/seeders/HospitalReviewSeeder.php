<?php

namespace Database\Seeders;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;

final class HospitalReviewSeeder extends Seeder
{
    public function run(): void
    {
        $authorIds = $this->activeUserIds();
        $hospitalIds = $this->approvedHospitalIds();
        $categoryIdsByDomain = $this->categoryIdsByDomain(HospitalReview::categoryDomains());

        if ($authorIds === [] || $hospitalIds === [] || $categoryIdsByDomain === []) {
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
            ->merge($this->seedReviews(12, $authorIds, $hospitalIds, $categoryIdsByDomain, 'autoBlind'))
            ->merge($this->seedReviews(8, $authorIds, $hospitalIds, $categoryIdsByDomain, 'adminStopped'))
            ->merge($this->seedReviews(6, $authorIds, $hospitalIds, $categoryIdsByDomain, 'userDeleted'))
            ->merge($this->seedReviews(8, $authorIds, $hospitalIds, $categoryIdsByDomain, 'inactive'));

        $this->seedComments($normalReviews->merge($sampleReviews), $authorIds);
    }

    /**
     * @param array<int, int> $authorIds
     * @param array<int, int> $hospitalIds
     * @param array<string, array<int, int>> $categoryIdsByDomain
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
     * @param iterable<int, HospitalReview> $reviews
     * @param array<int, int> $authorIds
     */
    private function seedComments(iterable $reviews, array $authorIds): void
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

            $review->forceFill([
                'comment_count' => (int) HospitalReviewComment::query()
                    ->where('hospital_review_id', $review->id)
                    ->count(),
            ])->save();
        }
    }

    /**
     * @param array<int, int> $authorIds
     */
    private function seedCommentStatusSamples(HospitalReview $review, array $authorIds, iterable $parentCandidates): void
    {
        $parentComments = collect($parentCandidates)
            ->filter(static fn (HospitalReviewComment $comment): bool => $comment->isRootComment())
            ->values()
            ->all();

        $samples = [
            ['chance' => 10, 'factory_state' => 'autoBlind', 'prefix' => '[자동 블라인드 샘플]'],
            ['chance' => 8, 'factory_state' => 'adminStopped', 'prefix' => '[게시중단 샘플]'],
            ['chance' => 6, 'factory_state' => 'userDeleted', 'prefix' => '[본인삭제 샘플]'],
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
     * @param array<int, int> $categoryIds
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
            return null;
        }

        return $doctorIds[array_rand($doctorIds)];
    }

    /**
     * @param array<int, string> $domains
     * @return array<string, array<int, int>>
     */
    private function categoryIdsByDomain(array $domains): array
    {
        $categories = Category::query()
            ->whereIn('domain', $domains)
            ->where('status', Category::STATUS_ACTIVE)
            ->orderBy('domain')
            ->orderBy('depth')
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
