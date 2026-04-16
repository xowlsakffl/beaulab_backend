<?php

namespace Database\Seeders;

use App\Domains\AccountUser\Models\AccountUser;
use App\Domains\Common\Models\Category\Category;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;
use Illuminate\Database\Seeder;

final class TalkSeeder extends Seeder
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

        $communityCategoryIds = $this->ensureTalkCategories();

        $talks = Talk::factory()
            ->count(200)
            ->active()
            ->create()
            ->each(function (Talk $talk) use ($authorIds): void {
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
    private function ensureTalkCategories(): array
    {
        $root = Category::query()->updateOrCreate(
            [
                'domain' => Category::DOMAIN_HOSPITAL_COMMUNITY,
                'parent_id' => null,
                'name' => 'Talk',
            ],
            [
                'depth' => 1,
                'code' => 'TALK_ROOT',
                'full_path' => 'Talk',
                'sort_order' => 1,
                'status' => Category::STATUS_ACTIVE,
                'is_menu_visible' => true,
            ]
        );

        $children = [
            ['name' => '성형/쁘띠', 'code' => 'TALK_PLASTIC_PETIT', 'sort_order' => 1],
            ['name' => '뷰티', 'code' => 'TALK_BEAUTY', 'sort_order' => 2],
            ['name' => '일상', 'code' => 'TALK_DAILY', 'sort_order' => 3],
            ['name' => '시크릿', 'code' => 'TALK_SECRET', 'sort_order' => 4],
        ];

        $childIds = [];
        foreach ($children as $child) {
            $category = Category::query()->updateOrCreate(
                [
                    'domain' => Category::DOMAIN_HOSPITAL_COMMUNITY,
                    'parent_id' => $root->id,
                    'name' => $child['name'],
                ],
                [
                    'depth' => 2,
                    'code' => $child['code'],
                    'full_path' => "Talk > {$child['name']}",
                    'sort_order' => $child['sort_order'],
                    'status' => Category::STATUS_ACTIVE,
                    'is_menu_visible' => true,
                ]
            );

            $childIds[] = (int) $category->id;
        }

        return $childIds;
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
     */
    private function seedComments(iterable $talks, array $authorIds): void
    {
        foreach ($talks as $talk) {
            $topLevelCount = random_int(0, 12);

            $topLevelComments = TalkComment::factory()
                ->count($topLevelCount)
                ->active()
                ->create([
                    'talk_id' => $talk->id,
                    'author_id' => fn () => $authorIds[array_rand($authorIds)],
                    'parent_id' => null,
                ]);

            foreach ($topLevelComments as $comment) {
                $replyCount = random_int(0, 2);
                if ($replyCount === 0) {
                    continue;
                }

                TalkComment::factory()
                    ->count($replyCount)
                    ->active()
                    ->create([
                        'talk_id' => $talk->id,
                        'author_id' => fn () => $authorIds[array_rand($authorIds)],
                        'parent_id' => $comment->id,
                    ]);
            }

            $talk->forceFill([
                'comment_count' => (int) TalkComment::query()
                    ->where('talk_id', $talk->id)
                    ->count(),
            ])->save();
        }
    }
}
