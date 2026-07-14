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
        'name' => '지방흡입/이식',
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
        'name' => '안면윤곽/양악',
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
        'name' => '거상',
        'code' => 'HS_LIFT',
    ],
    [
        'name' => '모발이식',
        'code' => 'HS_HAIR_TRANSPLANT',
    ],
    [
        'name' => '남자성형',
        'code' => 'HM_PLASTIC_MALE',
    ],
    [
        'name' => '기타성형',
        'code' => 'HM_PLASTIC_OTHER',
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
    [
        'name' => '얼굴',
        'code' => 'HM_PETIT_SKIN_FACE',
        'children' => [
            [
                'name' => '리프팅',
                'code' => 'HT_LIFTING',
                'children' => [
                    ['name' => '인모드 FX', 'code' => 'HT_LIFTING_INMODE_FX'],
                    ['name' => '인모드 FORMA', 'code' => 'HT_LIFTING_INMODE_FORMA'],
                    ['name' => '슈링크 유니버스', 'code' => 'HT_LIFTING_SHRINK_UNIVERSE'],
                    ['name' => '슈링크 리프팅', 'code' => 'HT_LIFTING_SHRINK_BASIC'],
                    ['name' => '울쎄라 300샷', 'code' => 'HT_LIFTING_ULTHERA_300'],
                    ['name' => '울쎄라 600샷', 'code' => 'HT_LIFTING_ULTHERA_600'],
                ],
            ],
            [
                'name' => '보톡스',
                'code' => 'HT_BOTOX',
                'children' => [
                    ['name' => '사각턱보톡스', 'code' => 'HT_BOTOX_JAW'],
                    ['name' => '이마보톡스', 'code' => 'HT_BOTOX_FOREHEAD'],
                    ['name' => '미간보톡스', 'code' => 'HT_BOTOX_GLABELLA'],
                    ['name' => '종아리보톡스', 'code' => 'HT_BOTOX_CALF'],
                    ['name' => '승모근보톡스', 'code' => 'HT_BOTOX_TRAP'],
                ],
            ],
            [
                'name' => '필러',
                'code' => 'HT_FILLER',
                'children' => [
                    ['name' => '입술필러', 'code' => 'HT_FILLER_LIP'],
                    ['name' => '이마필러', 'code' => 'HT_FILLER_FOREHEAD'],
                    ['name' => '턱끝필러', 'code' => 'HT_FILLER_CHIN'],
                    ['name' => '관자필러', 'code' => 'HT_FILLER_TEMPLE'],
                ],
            ],
        ],
    ],
    [
        'name' => '체형',
        'code' => 'HM_PETIT_SKIN_BODY',
        'children' => [
            [
                'name' => '지방분해/윤곽주사',
                'code' => 'HM_PETIT_SKIN_BODY_CONTOUR_INJECTION',
            ],
        ],
    ],
    [
        'name' => '피부',
        'code' => 'HM_PETIT_SKIN_CARE',
        'children' => [
            [
                'name' => '스킨부스터',
                'code' => 'HT_SKIN_BOOSTER',
                'children' => [
                    ['name' => '리쥬란', 'code' => 'HT_SKIN_BOOSTER_REJURAN'],
                    ['name' => '리쥬란 HB', 'code' => 'HT_SKIN_BOOSTER_REJURAN_HB'],
                    ['name' => '샤넬주사', 'code' => 'HT_SKIN_BOOSTER_CHANEL'],
                    ['name' => '엑소좀', 'code' => 'HT_SKIN_BOOSTER_EXOSOME'],
                ],
            ],
            [
                'name' => '레이저토닝',
                'code' => 'HT_LASER_TONING',
                'children' => [
                    ['name' => '피코토닝', 'code' => 'HT_LASER_TONING_PICO'],
                    ['name' => '레블라이트', 'code' => 'HT_LASER_TONING_REVLITE'],
                    ['name' => '브이빔', 'code' => 'HT_LASER_TONING_VBEAM'],
                    ['name' => '제네시스', 'code' => 'HT_LASER_TONING_GENESIS'],
                ],
            ],
            [
                'name' => '제모',
                'code' => 'HT_HAIR_REMOVAL',
                'children' => [
                    ['name' => '인중제모', 'code' => 'HT_HAIR_REMOVAL_PHILTRUM'],
                    ['name' => '헤어라인제모', 'code' => 'HT_HAIR_REMOVAL_HAIRLINE'],
                    ['name' => '겨드랑이제모', 'code' => 'HT_HAIR_REMOVAL_AXILLA'],
                    ['name' => '종아리제모', 'code' => 'HT_HAIR_REMOVAL_CALF'],
                ],
            ],
        ],
    ],
    [
        'name' => '치과',
        'code' => 'HM_PETIT_SKIN_DENTAL',
    ],
    [
        'name' => '헤어',
        'code' => 'HM_PETIT_SKIN_HAIR',
    ],
    [
        'name' => '두피',
        'code' => 'HM_PETIT_SKIN_SCALP',
    ],
    [
        'name' => '탈모',
        'code' => 'HM_PETIT_SKIN_HAIR_LOSS',
    ],
    [
        'name' => '부인과',
        'code' => 'HM_PETIT_SKIN_GYNECOLOGY',
    ],
    [
        'name' => '안과',
        'code' => 'HM_PETIT_SKIN_OPHTHALMOLOGY',
    ],
    [
        'name' => '한방',
        'code' => 'HM_PETIT_SKIN_ORIENTAL',
    ],
    [
        'name' => '기타쁘띠/피부',
        'code' => 'HM_PETIT_SKIN_OTHER',
    ],
];
