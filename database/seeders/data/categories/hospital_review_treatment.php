<?php

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
