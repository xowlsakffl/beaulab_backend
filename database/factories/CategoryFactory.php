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

    public function faq(): static
    {
        return $this->state(fn (): array => [
            'domain' => Category::DOMAIN_FAQ,
        ]);
    }

    public static function seedHospitalCategories(): void
    {
        DB::transaction(function (): void {
            self::seedDomainTree(Category::DOMAIN_HOSPITAL_SURGERY, self::hospitalSurgeryTree());
            self::seedDomainTree(Category::DOMAIN_HOSPITAL_TREATMENT, self::hospitalTreatmentTree());
        });
    }

    public static function seedBeautyCategories(): void
    {
        DB::transaction(function (): void {
            self::seedDomainTree(Category::DOMAIN_BEAUTY, self::beautyTree());
        });
    }

    public static function seedFaqCategories(): void
    {
        DB::transaction(function (): void {
            self::seedDomainTree(Category::DOMAIN_FAQ, self::faqTree());
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
                            ['name' => '밑트임', 'code' => 'HS_EYE_LOWER_CANTHOPLASTY'],
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
                            ['name' => '복코교정', 'code' => 'HS_NOSE_TIP_BULBOUS'],
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
            [
                'name' => '체형성형',
                'code' => 'HS_BODY',
                'children' => [
                    [
                        'name' => '지방흡입',
                        'code' => 'HS_BODY_LIPOSUCTION',
                        'children' => [
                            ['name' => '복부지방흡입', 'code' => 'HS_BODY_LIPOSUCTION_ABDOMEN'],
                            ['name' => '허벅지지방흡입', 'code' => 'HS_BODY_LIPOSUCTION_THIGH'],
                        ],
                    ],
                    [
                        'name' => '복부성형',
                        'code' => 'HS_BODY_ABDOMINOPLASTY',
                        'children' => [
                            ['name' => '미니복부성형', 'code' => 'HS_BODY_ABDOMINOPLASTY_MINI'],
                            ['name' => '전체복부성형', 'code' => 'HS_BODY_ABDOMINOPLASTY_FULL'],
                        ],
                    ],
                ],
            ],
            [
                'name' => '안티에이징',
                'code' => 'HS_ANTI_AGING',
                'children' => [
                    [
                        'name' => '이마·눈썹',
                        'code' => 'HS_ANTI_AGING_FOREHEAD_BROW',
                        'children' => [
                            ['name' => '이마거상', 'code' => 'HS_ANTI_AGING_FOREHEAD_LIFT'],
                            ['name' => '눈썹거상', 'code' => 'HS_ANTI_AGING_BROW_LIFT'],
                        ],
                    ],
                    [
                        'name' => '중안면·목',
                        'code' => 'HS_ANTI_AGING_MIDFACE_NECK',
                        'children' => [
                            ['name' => '중안면거상', 'code' => 'HS_ANTI_AGING_MIDFACE_LIFT'],
                            ['name' => '목거상', 'code' => 'HS_ANTI_AGING_NECK_LIFT'],
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
                            ['name' => '미간보톡스', 'code' => 'HT_BOTOX_GLABELLA'],
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
            [
                'name' => '레이저토닝',
                'code' => 'HT_LASER_TONING',
                'children' => [
                    [
                        'name' => '색소레이저',
                        'code' => 'HT_LASER_TONING_PIGMENT',
                        'children' => [
                            ['name' => '피코토닝', 'code' => 'HT_LASER_TONING_PICO'],
                            ['name' => '레블라이트', 'code' => 'HT_LASER_TONING_REVLITE'],
                        ],
                    ],
                    [
                        'name' => '홍조레이저',
                        'code' => 'HT_LASER_TONING_REDNESS',
                        'children' => [
                            ['name' => '브이빔', 'code' => 'HT_LASER_TONING_VBEAM'],
                            ['name' => '제네시스', 'code' => 'HT_LASER_TONING_GENESIS'],
                        ],
                    ],
                ],
            ],
            [
                'name' => '제모',
                'code' => 'HT_HAIR_REMOVAL',
                'children' => [
                    [
                        'name' => '페이스 제모',
                        'code' => 'HT_HAIR_REMOVAL_FACE',
                        'children' => [
                            ['name' => '인중제모', 'code' => 'HT_HAIR_REMOVAL_PHILTRUM'],
                            ['name' => '헤어라인제모', 'code' => 'HT_HAIR_REMOVAL_HAIRLINE'],
                        ],
                    ],
                    [
                        'name' => '바디 제모',
                        'code' => 'HT_HAIR_REMOVAL_BODY',
                        'children' => [
                            ['name' => '겨드랑이제모', 'code' => 'HT_HAIR_REMOVAL_AXILLA'],
                            ['name' => '종아리제모', 'code' => 'HT_HAIR_REMOVAL_CALF'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{name:string, code:string, children:array<int, array{name:string, code:string, children:array<int, array{name:string, code:string}>}>}>
     */
    public static function beautyTree(): array
    {
        return [
            [
                'name' => '헤어',
                'code' => 'BE_HAIR',
                'children' => [
                    [
                        'name' => '컷',
                        'code' => 'BE_HAIR_CUT',
                        'children' => [
                            ['name' => 'Women Cut', 'code' => 'BE_HAIR_CUT_WOMEN'],
                            ['name' => 'Men Cut', 'code' => 'BE_HAIR_CUT_MEN'],
                        ],
                    ],
                    [
                        'name' => '펌',
                        'code' => 'BE_HAIR_PERM',
                        'children' => [
                            ['name' => 'Digital Perm', 'code' => 'BE_HAIR_PERM_DIGITAL'],
                            ['name' => 'Setting Perm', 'code' => 'BE_HAIR_PERM_SETTING'],
                        ],
                    ],
                    [
                        'name' => '염색',
                        'code' => 'BE_HAIR_COLOR',
                        'children' => [
                            ['name' => 'Root Touch Up', 'code' => 'BE_HAIR_COLOR_ROOT'],
                            ['name' => 'Full Color', 'code' => 'BE_HAIR_COLOR_FULL'],
                        ],
                    ],
                ],
            ],
            [
                'name' => '네일',
                'code' => 'BE_NAIL',
                'children' => [
                    [
                        'name' => '기본 네일',
                        'code' => 'BE_NAIL_BASIC',
                        'children' => [
                            ['name' => 'Nail Care', 'code' => 'BE_NAIL_BASIC_CARE'],
                            ['name' => 'One Color Gel', 'code' => 'BE_NAIL_BASIC_ONE_COLOR'],
                            ['name' => 'Strengthening Care', 'code' => 'BE_NAIL_BASIC_STRENGTH'],
                        ],
                    ],
                    [
                        'name' => '네일 아트',
                        'code' => 'BE_NAIL_ART',
                        'children' => [
                            ['name' => 'French Art', 'code' => 'BE_NAIL_ART_FRENCH'],
                            ['name' => 'Character Art', 'code' => 'BE_NAIL_ART_CHARACTER'],
                        ],
                    ],
                    [
                        'name' => '페디큐어',
                        'code' => 'BE_NAIL_PEDICURE',
                        'children' => [
                            ['name' => 'Basic Pedicure', 'code' => 'BE_NAIL_PEDICURE_BASIC'],
                            ['name' => 'Gel Pedicure', 'code' => 'BE_NAIL_PEDICURE_GEL'],
                        ],
                    ],
                ],
            ],
            [
                'name' => '피부',
                'code' => 'BE_SKIN',
                'children' => [
                    [
                        'name' => '기본 관리',
                        'code' => 'BE_SKIN_BASIC',
                        'children' => [
                            ['name' => 'Hydration Care', 'code' => 'BE_SKIN_BASIC_HYDRATION'],
                            ['name' => 'Whitening Care', 'code' => 'BE_SKIN_BASIC_WHITENING'],
                        ],
                    ],
                    [
                        'name' => '여드름 관리',
                        'code' => 'BE_SKIN_ACNE',
                        'children' => [
                            ['name' => 'Pore Care', 'code' => 'BE_SKIN_ACNE_PORE'],
                            ['name' => 'Acne Scar Care', 'code' => 'BE_SKIN_ACNE_SCAR'],
                        ],
                    ],
                    [
                        'name' => '리프팅 관리',
                        'code' => 'BE_SKIN_LIFTING',
                        'children' => [
                            ['name' => 'Ultrasound Lifting', 'code' => 'BE_SKIN_LIFTING_ULTRASOUND'],
                            ['name' => 'RF Lifting', 'code' => 'BE_SKIN_LIFTING_RF'],
                        ],
                    ],
                ],
            ],
            [
                'name' => '속눈썹',
                'code' => 'BE_EYELASH',
                'children' => [
                    [
                        'name' => '연장',
                        'code' => 'BE_EYELASH_EXTENSION',
                        'children' => [
                            ['name' => 'Classic Extension', 'code' => 'BE_EYELASH_EXTENSION_CLASSIC'],
                            ['name' => 'Volume Extension', 'code' => 'BE_EYELASH_EXTENSION_VOLUME'],
                        ],
                    ],
                    [
                        'name' => '속눈썹 펌',
                        'code' => 'BE_EYELASH_PERM',
                        'children' => [
                            ['name' => 'Natural Lash Perm', 'code' => 'BE_EYELASH_PERM_NATURAL'],
                            ['name' => 'Black Tint Perm', 'code' => 'BE_EYELASH_PERM_TINT'],
                        ],
                    ],
                ],
            ],
            [
                'name' => '왁싱',
                'code' => 'BE_WAXING',
                'children' => [
                    [
                        'name' => '페이스 왁싱',
                        'code' => 'BE_WAXING_FACE',
                        'children' => [
                            ['name' => 'Eyebrow Waxing', 'code' => 'BE_WAXING_FACE_EYEBROW'],
                            ['name' => 'Lip Waxing', 'code' => 'BE_WAXING_FACE_LIP'],
                        ],
                    ],
                    [
                        'name' => '바디 왁싱',
                        'code' => 'BE_WAXING_BODY',
                        'children' => [
                            ['name' => 'Arm Waxing', 'code' => 'BE_WAXING_BODY_ARM'],
                            ['name' => 'Leg Waxing', 'code' => 'BE_WAXING_BODY_LEG'],
                        ],
                    ],
                ],
            ],
            [
                'name' => '메이크업',
                'code' => 'BE_MAKEUP',
                'children' => [
                    [
                        'name' => '데일리 메이크업',
                        'code' => 'BE_MAKEUP_DAILY',
                        'children' => [
                            ['name' => '면접 메이크업', 'code' => 'BE_MAKEUP_DAILY_INTERVIEW'],
                            ['name' => '프로필 메이크업', 'code' => 'BE_MAKEUP_DAILY_PROFILE'],
                        ],
                    ],
                    [
                        'name' => '웨딩 메이크업',
                        'code' => 'BE_MAKEUP_WEDDING',
                        'children' => [
                            ['name' => '신부 메이크업', 'code' => 'BE_MAKEUP_WEDDING_BRIDE'],
                            ['name' => '혼주 메이크업', 'code' => 'BE_MAKEUP_WEDDING_FAMILY'],
                        ],
                    ],
                ],
            ],
            [
                'name' => '반영구',
                'code' => 'BE_SEMI_PERMANENT',
                'children' => [
                    [
                        'name' => '눈썹',
                        'code' => 'BE_SEMI_PERMANENT_BROW',
                        'children' => [
                            ['name' => '자연눈썹', 'code' => 'BE_SEMI_PERMANENT_BROW_NATURAL'],
                            ['name' => '콤보눈썹', 'code' => 'BE_SEMI_PERMANENT_BROW_COMBO'],
                        ],
                    ],
                    [
                        'name' => '아이라인',
                        'code' => 'BE_SEMI_PERMANENT_EYELINE',
                        'children' => [
                            ['name' => '점막아이라인', 'code' => 'BE_SEMI_PERMANENT_EYELINE_FILL'],
                            ['name' => '꼬리아이라인', 'code' => 'BE_SEMI_PERMANENT_EYELINE_TAIL'],
                        ],
                    ],
                ],
            ],
            [
                'name' => '두피케어',
                'code' => 'BE_SCALP',
                'children' => [
                    [
                        'name' => '두피 스케일링',
                        'code' => 'BE_SCALP_SCALING',
                        'children' => [
                            ['name' => '지성 두피케어', 'code' => 'BE_SCALP_SCALING_OILY'],
                            ['name' => '각질 케어', 'code' => 'BE_SCALP_SCALING_DEAD_SKIN'],
                        ],
                    ],
                    [
                        'name' => '헤드스파',
                        'code' => 'BE_SCALP_HEADSPA',
                        'children' => [
                            ['name' => '아로마 헤드스파', 'code' => 'BE_SCALP_HEADSPA_AROMA'],
                            ['name' => '탈모 케어', 'code' => 'BE_SCALP_HEADSPA_HAIR_LOSS'],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<int, array{name:string, code:string}>
     */
    public static function faqTree(): array
    {
        return [
            ['name' => '계정', 'code' => 'FAQ_ACCOUNT'],
            ['name' => '예약', 'code' => 'FAQ_RESERVATION'],
            ['name' => '결제', 'code' => 'FAQ_PAYMENT'],
            ['name' => '리뷰', 'code' => 'FAQ_REVIEW'],
            ['name' => '병원 이용', 'code' => 'FAQ_HOSPITAL'],
            ['name' => '뷰티 이용', 'code' => 'FAQ_BEAUTY'],
            ['name' => '서비스 정책', 'code' => 'FAQ_POLICY'],
            ['name' => '커뮤니티', 'code' => 'FAQ_COMMUNITY'],
        ];
    }
}
