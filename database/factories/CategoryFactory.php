<?php

namespace Database\Factories;

use App\Domains\Common\Models\Category\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/**
 * @extends Factory<Category>
 */
final class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = $this->faker->unique()->word();

        return [
            'domain' => Category::DOMAIN_HOSPITAL_SURGERY,
            'parent_id' => null,
            'depth' => 1,
            'name' => $name,
            'code' => strtoupper($name),
            'full_path' => $name,
            'sort_order' => 1,
            'status' => Category::STATUS_ACTIVE,
            'is_menu_visible' => true,
        ];
    }

    public static function seedHospitalCategories(): void
    {
        DB::transaction(function (): void {
            self::seedDomainTree(Category::DOMAIN_HOSPITAL_SURGERY, self::hospitalSurgeryTree());
            self::seedDomainTree(Category::DOMAIN_HOSPITAL_TREATMENT, self::hospitalTreatmentTree());
        });
    }

    /**
     * @param array<int, array{name:string, code:string, children?:array<int, array{name:string, code:string, children?:array<int, array{name:string, code:string}>}>}> $tree
     */
    private static function seedDomainTree(string $domain, array $tree): void
    {
        foreach ($tree as $index => $node) {
            self::upsertNode(
                domain: $domain,
                node: $node,
                parent: null,
                depth: 1,
                sortOrder: $index + 1,
            );
        }
    }

    /**
     * @param array{name:string, code:string, children?:array<int, array{name:string, code:string, children?:array<int, array{name:string, code:string}>}>} $node
     */
    private static function upsertNode(string $domain, array $node, ?Category $parent, int $depth, int $sortOrder): Category
    {
        $name = $node['name'];
        $parentPath = $parent
            ? trim((string) ($parent->full_path ?: $parent->name))
            : null;
        $path = $parentPath ? "{$parentPath} > {$name}" : $name;

        $category = Category::query()->updateOrCreate(
            [
                'domain' => $domain,
                'parent_id' => $parent?->id,
                'name' => $name,
            ],
            [
                'depth' => $depth,
                'code' => $node['code'],
                'full_path' => $path,
                'sort_order' => $sortOrder,
                'status' => Category::STATUS_ACTIVE,
                'is_menu_visible' => true,
            ]
        );

        $children = $node['children'] ?? [];
        foreach ($children as $childIndex => $childNode) {
            self::upsertNode(
                domain: $domain,
                node: $childNode,
                parent: $category,
                depth: $depth + 1,
                sortOrder: $childIndex + 1,
            );
        }

        return $category;
    }

    /**
     * @return array<int, array{name:string, code:string, children:array<int, array{name:string, code:string, children:array<int, array{name:string, code:string}>}>}>
     */
    public static function hospitalSurgeryTree(): array
    {
        return [
            [
                'name' => '눈',
                'code' => 'HS_EYE',
                'children' => [
                    [
                        'name' => '쌍꺼풀',
                        'code' => 'HS_EYE_DOUBLE',
                        'children' => [
                            ['name' => '자연유착', 'code' => 'HS_EYE_DOUBLE_NATURAL'],
                            ['name' => '절개쌍꺼풀', 'code' => 'HS_EYE_DOUBLE_INCISION'],
                        ],
                    ],
                    [
                        'name' => '눈매교정',
                        'code' => 'HS_EYE_PTOSIS',
                        'children' => [
                            ['name' => '비절개눈매교정', 'code' => 'HS_EYE_PTOSIS_NON_INCISION'],
                            ['name' => '절개눈매교정', 'code' => 'HS_EYE_PTOSIS_INCISION'],
                        ],
                    ],
                    [
                        'name' => '트임성형',
                        'code' => 'HS_EYE_CANTHOPLASTY',
                        'children' => [
                            ['name' => '앞트임', 'code' => 'HS_EYE_EPICANTHOPLASTY'],
                            ['name' => '뒤트임', 'code' => 'HS_EYE_LATERAL_CANTHOPLASTY'],
                        ],
                    ],
                ],
            ],
            [
                'name' => '코',
                'code' => 'HS_NOSE',
                'children' => [
                    [
                        'name' => '코끝성형',
                        'code' => 'HS_NOSE_TIP',
                        'children' => [
                            ['name' => '연골코끝성형', 'code' => 'HS_NOSE_TIP_CARTILAGE'],
                            ['name' => '비중격연장', 'code' => 'HS_NOSE_SEPTAL_EXTENSION'],
                        ],
                    ],
                    [
                        'name' => '콧대성형',
                        'code' => 'HS_NOSE_BRIDGE',
                        'children' => [
                            ['name' => '실리콘콧대', 'code' => 'HS_NOSE_BRIDGE_SILICONE'],
                            ['name' => '자가조직콧대', 'code' => 'HS_NOSE_BRIDGE_AUTOGRAFT'],
                        ],
                    ],
                    [
                        'name' => '코재수술',
                        'code' => 'HS_NOSE_REVISION',
                        'children' => [
                            ['name' => '구축코재수술', 'code' => 'HS_NOSE_REVISION_CONTRACTED'],
                            ['name' => '기능코재수술', 'code' => 'HS_NOSE_REVISION_FUNCTIONAL'],
                        ],
                    ],
                ],
            ],
            [
                'name' => '얼굴윤곽',
                'code' => 'HS_FACE_CONTOUR',
                'children' => [
                    [
                        'name' => '윤곽수술',
                        'code' => 'HS_FACE_BONE_CONTOUR',
                        'children' => [
                            ['name' => '사각턱수술', 'code' => 'HS_FACE_ANGLE_REDUCTION'],
                            ['name' => '광대축소', 'code' => 'HS_FACE_ZYGOMA_REDUCTION'],
                        ],
                    ],
                    [
                        'name' => '안면거상',
                        'code' => 'HS_FACE_LIFT',
                        'children' => [
                            ['name' => '미니거상', 'code' => 'HS_FACE_LIFT_MINI'],
                            ['name' => '풀페이스거상', 'code' => 'HS_FACE_LIFT_FULL'],
                        ],
                    ],
                ],
            ],
            [
                'name' => '가슴',
                'code' => 'HS_BREAST',
                'children' => [
                    [
                        'name' => '가슴확대',
                        'code' => 'HS_BREAST_AUGMENTATION',
                        'children' => [
                            ['name' => '보형물가슴확대', 'code' => 'HS_BREAST_IMPLANT'],
                            ['name' => '지방이식가슴확대', 'code' => 'HS_BREAST_FAT_GRAFT'],
                        ],
                    ],
                    [
                        'name' => '가슴재수술',
                        'code' => 'HS_BREAST_REVISION',
                        'children' => [
                            ['name' => '보형물교체', 'code' => 'HS_BREAST_IMPLANT_REPLACE'],
                            ['name' => '구축교정', 'code' => 'HS_BREAST_CONTRACTURE_FIX'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{name:string, code:string, children:array<int, array{name:string, code:string, children:array<int, array{name:string, code:string}>}>}>
     */
    public static function hospitalTreatmentTree(): array
    {
        return [
            [
                'name' => '리프팅',
                'code' => 'HT_LIFTING',
                'children' => [
                    [
                        'name' => '인모드',
                        'code' => 'HT_LIFTING_INMODE',
                        'children' => [
                            ['name' => '인모드 FX', 'code' => 'HT_LIFTING_INMODE_FX'],
                            ['name' => '인모드 FORMA', 'code' => 'HT_LIFTING_INMODE_FORMA'],
                        ],
                    ],
                    [
                        'name' => '슈링크',
                        'code' => 'HT_LIFTING_SHRINK',
                        'children' => [
                            ['name' => '슈링크 유니버스', 'code' => 'HT_LIFTING_SHRINK_UNIVERSE'],
                            ['name' => '슈링크 리프팅', 'code' => 'HT_LIFTING_SHRINK_BASIC'],
                        ],
                    ],
                    [
                        'name' => '울쎄라',
                        'code' => 'HT_LIFTING_ULTHERA',
                        'children' => [
                            ['name' => '울쎄라 300샷', 'code' => 'HT_LIFTING_ULTHERA_300'],
                            ['name' => '울쎄라 600샷', 'code' => 'HT_LIFTING_ULTHERA_600'],
                        ],
                    ],
                ],
            ],
            [
                'name' => '보톡스',
                'code' => 'HT_BOTOX',
                'children' => [
                    [
                        'name' => '얼굴보톡스',
                        'code' => 'HT_BOTOX_FACE',
                        'children' => [
                            ['name' => '사각턱보톡스', 'code' => 'HT_BOTOX_JAW'],
                            ['name' => '이마보톡스', 'code' => 'HT_BOTOX_FOREHEAD'],
                        ],
                    ],
                    [
                        'name' => '바디보톡스',
                        'code' => 'HT_BOTOX_BODY',
                        'children' => [
                            ['name' => '종아리보톡스', 'code' => 'HT_BOTOX_CALF'],
                            ['name' => '승모근보톡스', 'code' => 'HT_BOTOX_TRAP'],
                        ],
                    ],
                ],
            ],
            [
                'name' => '필러',
                'code' => 'HT_FILLER',
                'children' => [
                    [
                        'name' => '얼굴필러',
                        'code' => 'HT_FILLER_FACE',
                        'children' => [
                            ['name' => '입술필러', 'code' => 'HT_FILLER_LIP'],
                            ['name' => '이마필러', 'code' => 'HT_FILLER_FOREHEAD'],
                        ],
                    ],
                    [
                        'name' => '윤곽필러',
                        'code' => 'HT_FILLER_CONTOUR',
                        'children' => [
                            ['name' => '턱끝필러', 'code' => 'HT_FILLER_CHIN'],
                            ['name' => '관자필러', 'code' => 'HT_FILLER_TEMPLE'],
                        ],
                    ],
                ],
            ],
            [
                'name' => '스킨부스터',
                'code' => 'HT_SKIN_BOOSTER',
                'children' => [
                    [
                        'name' => '연어주사',
                        'code' => 'HT_SKIN_BOOSTER_SALMON',
                        'children' => [
                            ['name' => '리쥬란', 'code' => 'HT_SKIN_BOOSTER_REJURAN'],
                            ['name' => '리쥬란 HB', 'code' => 'HT_SKIN_BOOSTER_REJURAN_HB'],
                        ],
                    ],
                    [
                        'name' => '물광주사',
                        'code' => 'HT_SKIN_BOOSTER_HYDRATION',
                        'children' => [
                            ['name' => '샤넬주사', 'code' => 'HT_SKIN_BOOSTER_CHANEL'],
                            ['name' => '엑소좀', 'code' => 'HT_SKIN_BOOSTER_EXOSOME'],
                        ],
                    ],
                ],
            ],
        ];
    }
}
