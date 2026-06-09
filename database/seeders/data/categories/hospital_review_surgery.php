<?php

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
