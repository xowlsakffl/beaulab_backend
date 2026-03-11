<?php

namespace Database\Seeders;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Models\Category\Category;
use App\Domains\HospitalTalk\Models\HospitalTalk;
use App\Domains\HospitalTalk\Models\HospitalTalkComment;
use Illuminate\Database\Seeder;

final class HospitalTalkSeeder extends Seeder
{
    public function run(): void
    {
        $authorIds = AccountUser::query()
            ->where('status', AccountUser::STATUS_ACTIVE)
            ->pluck('id')
            ->all();

        if ($authorIds === []) {
            return;
        }

        $communityCategoryIds = $this->ensureHospitalCommunityCategories();

        $talks = HospitalTalk::factory()
            ->count(60)
            ->active()
            ->create()
            ->each(function (HospitalTalk $talk) use ($authorIds): void {
                $talk->forceFill([
                    'author_id' => $authorIds[array_rand($authorIds)],
                ])->save();
            });

        $this->attachRandomCategories($talks, $communityCategoryIds);
        $this->seedComments($talks, $authorIds);
    }

    /**
     * @return array<int, int>
     */
    private function ensureHospitalCommunityCategories(): array
    {
        $existingLeafIds = Category::query()
            ->where('domain', Category::DOMAIN_HOSPITAL_COMMUNITY)
            ->whereDoesntHave('children')
            ->pluck('id')
            ->all();

        if ($existingLeafIds !== []) {
            return $existingLeafIds;
        }

        $root = Category::query()->updateOrCreate(
            [
                'domain' => Category::DOMAIN_HOSPITAL_COMMUNITY,
                'parent_id' => null,
                'name' => '병원 커뮤니티',
            ],
            [
                'depth' => 1,
                'code' => 'HOSPITAL_COMMUNITY_ROOT',
                'full_path' => '병원 커뮤니티',
                'sort_order' => 1,
                'status' => Category::STATUS_ACTIVE,
                'is_menu_visible' => true,
            ]
        );

        $children = [
            ['name' => '자유 토크', 'code' => 'HOSPITAL_COMMUNITY_FREE', 'sort_order' => 1],
            ['name' => '시술 후기', 'code' => 'HOSPITAL_COMMUNITY_REVIEW', 'sort_order' => 2],
            ['name' => '질문/답변', 'code' => 'HOSPITAL_COMMUNITY_QNA', 'sort_order' => 3],
        ];

        foreach ($children as $child) {
            Category::query()->updateOrCreate(
                [
                    'domain' => Category::DOMAIN_HOSPITAL_COMMUNITY,
                    'parent_id' => $root->id,
                    'name' => $child['name'],
                ],
                [
                    'depth' => 2,
                    'code' => $child['code'],
                    'full_path' => "병원 커뮤니티 > {$child['name']}",
                    'sort_order' => $child['sort_order'],
                    'status' => Category::STATUS_ACTIVE,
                    'is_menu_visible' => true,
                ]
            );
        }

        return Category::query()
            ->where('domain', Category::DOMAIN_HOSPITAL_COMMUNITY)
            ->whereDoesntHave('children')
            ->pluck('id')
            ->all();
    }

    /**
     * @param iterable<int, HospitalTalk> $talks
     * @param array<int, int> $categoryIds
     */
    private function attachRandomCategories(iterable $talks, array $categoryIds): void
    {
        if ($categoryIds === []) {
            return;
        }

        $maxAvailable = count($categoryIds);
        $minAssignCount = min(1, $maxAvailable);
        $maxAssignCount = min(3, $maxAvailable);

        foreach ($talks as $talk) {
            $assignCount = random_int($minAssignCount, $maxAssignCount);
            $selectedCategoryIds = collect($categoryIds)
                ->shuffle()
                ->take($assignCount)
                ->values()
                ->all();

            $payload = [];
            foreach ($selectedCategoryIds as $index => $categoryId) {
                $payload[$categoryId] = ['is_primary' => $index === 0];
            }

            $talk->categories()->syncWithoutDetaching($payload);
        }
    }

    /**
     * @param iterable<int, HospitalTalk> $talks
     * @param array<int, int> $authorIds
     */
    private function seedComments(iterable $talks, array $authorIds): void
    {
        foreach ($talks as $talk) {
            $topLevelCount = random_int(0, 12);

            $topLevelComments = HospitalTalkComment::factory()
                ->count($topLevelCount)
                ->active()
                ->create([
                    'hospital_talk_id' => $talk->id,
                    'author_id' => fn () => $authorIds[array_rand($authorIds)],
                    'parent_id' => null,
                ]);

            foreach ($topLevelComments as $comment) {
                $replyCount = random_int(0, 2);
                if ($replyCount === 0) {
                    continue;
                }

                HospitalTalkComment::factory()
                    ->count($replyCount)
                    ->active()
                    ->create([
                        'hospital_talk_id' => $talk->id,
                        'author_id' => fn () => $authorIds[array_rand($authorIds)],
                        'parent_id' => $comment->id,
                    ]);
            }

            $talk->forceFill([
                'comment_count' => (int) HospitalTalkComment::query()
                    ->where('hospital_talk_id', $talk->id)
                    ->count(),
            ])->save();
        }
    }
}
