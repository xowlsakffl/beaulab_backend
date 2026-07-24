<?php

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
