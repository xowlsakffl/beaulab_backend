<?php

return [
    [
        'name' => '눈',
        'code' => 'HS_EYE',
        'group_code' => 'SURGERY',
        'children' => [
            [
                'name' => '쌍꺼풀',
                'code' => 'HS_EYE_DOUBLE',
                'children' => [
                    ['name' => '자연유착', 'code' => 'HS_EYE_DOUBLE_NATURAL'],
                    ['name' => '매몰', 'code' => 'HS_EYE_DOUBLE_BURIED'],
                    ['name' => '절개', 'code' => 'HS_EYE_DOUBLE_INCISION'],
                    ['name' => '부분절개', 'code' => 'HS_EYE_DOUBLE_PARTIAL_INCISION'],
                ],
            ],
            [
                'name' => '트임',
                'code' => 'HS_EYE_CANTHOPLASTY',
                'children' => [
                    ['name' => '앞트임', 'code' => 'HS_EYE_EPICANTHOPLASTY'],
                    ['name' => '뒤트임', 'code' => 'HS_EYE_LATERAL_CANTHOPLASTY'],
                    ['name' => '윗트임', 'code' => 'HS_EYE_UPPER_CANTHOPLASTY'],
                    ['name' => '밑트임', 'code' => 'HS_EYE_LOWER_CANTHOPLASTY'],
                    ['name' => '트임복원', 'code' => 'HS_EYE_CANTHOPLASTY_RESTORE'],
                ],
            ],
            [
                'name' => '눈매교정',
                'code' => 'HS_EYE_PTOSIS',
                'children' => [
                    ['name' => '절개', 'code' => 'HS_EYE_PTOSIS_INCISION'],
                    ['name' => '비절개', 'code' => 'HS_EYE_PTOSIS_NON_INCISION'],
                ],
            ],
            [
                'name' => '눈거상',
                'code' => 'HS_EYE_LIFT',
                'children' => [
                    ['name' => '상안검', 'code' => 'HS_EYE_LIFT_UPPER_BLEPHAROPLASTY'],
                    ['name' => '하안검', 'code' => 'HS_EYE_LIFT_LOWER_BLEPHAROPLASTY'],
                    ['name' => '눈썹거상', 'code' => 'HS_EYE_LIFT_BROW'],
                ],
            ],
            [
                'name' => '눈지방',
                'code' => 'HS_EYE_FAT',
                'children' => [
                    ['name' => '눈밑지방재배치', 'code' => 'HS_EYE_FAT_UNDER_EYE_REPOSITION'],
                    ['name' => '눈꺼풀지방제거', 'code' => 'HS_EYE_FAT_EYELID_REMOVAL'],
                ],
            ],
            [
                'name' => '눈재수술',
                'code' => 'HS_EYE_REVISION',
                'children' => [
                    ['name' => '쌍꺼풀', 'code' => 'HS_EYE_REVISION_DOUBLE'],
                    ['name' => '트임', 'code' => 'HS_EYE_REVISION_CANTHOPLASTY'],
                    ['name' => '눈매교정', 'code' => 'HS_EYE_REVISION_PTOSIS'],
                    ['name' => '눈거상', 'code' => 'HS_EYE_REVISION_LIFT'],
                    ['name' => '기타', 'code' => 'HS_EYE_REVISION_OTHER'],
                ],
            ],
        ],
    ],
    [
        'name' => '코',
        'code' => 'HS_NOSE',
        'group_code' => 'SURGERY',
        'children' => [
            [
                'name' => '첫코',
                'code' => 'HS_NOSE_FIRST',
                'children' => [
                    ['name' => '콧대 + 코끝', 'code' => 'HS_NOSE_FIRST_BRIDGE_TIP'],
                    ['name' => '절골술', 'code' => 'HS_NOSE_FIRST_OSTEOTOMY'],
                    ['name' => '무보형물', 'code' => 'HS_NOSE_FIRST_NO_IMPLANT'],
                ],
            ],
            [
                'name' => '콧대',
                'code' => 'HS_NOSE_BRIDGE',
                'children' => [
                    ['name' => '콧대(자가조직)', 'code' => 'HS_NOSE_BRIDGE_AUTOGRAFT'],
                    ['name' => '콧대(보형물)', 'code' => 'HS_NOSE_BRIDGE_IMPLANT'],
                    ['name' => '코절골술', 'code' => 'HS_NOSE_BRIDGE_OSTEOTOMY'],
                ],
            ],
            [
                'name' => '코끝',
                'code' => 'HS_NOSE_TIP',
                'children' => [
                    ['name' => '코끝', 'code' => 'HS_NOSE_TIP_BASIC'],
                    ['name' => '연골묶기', 'code' => 'HS_NOSE_TIP_CARTILAGE_BINDING'],
                    ['name' => '비주(코기둥)', 'code' => 'HS_NOSE_TIP_COLUMELLA'],
                    ['name' => '인중', 'code' => 'HS_NOSE_TIP_PHILTRUM'],
                ],
            ],
            [
                'name' => '기능코',
                'code' => 'HS_NOSE_FUNCTIONAL',
                'children' => [
                    ['name' => '비염', 'code' => 'HS_NOSE_FUNCTIONAL_RHINITIS'],
                    ['name' => '비중격만곡', 'code' => 'HS_NOSE_FUNCTIONAL_SEPTUM'],
                    ['name' => '기타(비밸브, 축농증)', 'code' => 'HS_NOSE_FUNCTIONAL_OTHER'],
                ],
            ],
            [
                'name' => '콧볼',
                'code' => 'HS_NOSE_ALAR',
                'children' => [
                    ['name' => '비절개', 'code' => 'HS_NOSE_ALAR_NON_INCISION'],
                    ['name' => '내외측절개', 'code' => 'HS_NOSE_ALAR_INCISION'],
                ],
            ],
            [
                'name' => '코재수술',
                'code' => 'HS_NOSE_REVISION',
                'children' => [
                    ['name' => '콧대 + 코끝', 'code' => 'HS_NOSE_REVISION_BRIDGE_TIP'],
                    ['name' => '콧대', 'code' => 'HS_NOSE_REVISION_BRIDGE'],
                    ['name' => '코끝', 'code' => 'HS_NOSE_REVISION_TIP'],
                    ['name' => '기능코', 'code' => 'HS_NOSE_REVISION_FUNCTIONAL'],
                    ['name' => '콧볼', 'code' => 'HS_NOSE_REVISION_ALAR'],
                    ['name' => '기타', 'code' => 'HS_NOSE_REVISION_OTHER'],
                ],
            ],
        ],
    ],
    [
        'name' => '지방흡입 / 이식',
        'code' => 'HS_BODY',
        'group_code' => 'SURGERY',
        'children' => [
            [
                'name' => '바디지방흡입',
                'code' => 'HS_BODY_LIPOSUCTION',
                'children' => [
                    ['name' => '복부', 'code' => 'HS_BODY_LIPOSUCTION_ABDOMEN'],
                    ['name' => '팔', 'code' => 'HS_BODY_LIPOSUCTION_ARM'],
                    ['name' => '허벅지', 'code' => 'HS_BODY_LIPOSUCTION_THIGH'],
                    ['name' => '엉덩이 / 골반', 'code' => 'HS_BODY_LIPOSUCTION_HIP_PELVIS'],
                    ['name' => '기타(무릎 / 종아리 / 발목 / 등 / 승모근 / 쇄골 등)', 'code' => 'HS_BODY_LIPOSUCTION_OTHER'],
                ],
            ],
            [
                'name' => '바디지방흡입 재수술',
                'code' => 'HS_BODY_LIPOSUCTION_REVISION',
                'children' => [
                    ['name' => '복부', 'code' => 'HS_BODY_LIPOSUCTION_REVISION_ABDOMEN'],
                    ['name' => '팔', 'code' => 'HS_BODY_LIPOSUCTION_REVISION_ARM'],
                    ['name' => '허벅지', 'code' => 'HS_BODY_LIPOSUCTION_REVISION_THIGH'],
                    ['name' => '엉덩이/골반', 'code' => 'HS_BODY_LIPOSUCTION_REVISION_HIP_PELVIS'],
                    ['name' => '기타', 'code' => 'HS_BODY_LIPOSUCTION_REVISION_OTHER'],
                ],
            ],
            [
                'name' => '얼굴지방흡입',
                'code' => 'HS_FACE_LIPOSUCTION',
                'children' => [
                    ['name' => '풀페이스', 'code' => 'HS_FACE_LIPOSUCTION_FULL_FACE'],
                    ['name' => '볼', 'code' => 'HS_FACE_LIPOSUCTION_CHEEK'],
                    ['name' => '턱', 'code' => 'HS_FACE_LIPOSUCTION_CHIN'],
                ],
            ],
            [
                'name' => '바디지방이식',
                'code' => 'HS_BODY_FAT_GRAFT',
                'children' => [
                    ['name' => '가슴', 'code' => 'HS_BODY_FAT_GRAFT_BREAST'],
                    ['name' => '엉덩이 / 골반', 'code' => 'HS_BODY_FAT_GRAFT_HIP_PELVIS'],
                    ['name' => '기타', 'code' => 'HS_BODY_FAT_GRAFT_OTHER'],
                ],
            ],
            [
                'name' => '얼굴지방이식',
                'code' => 'HS_FACE_FAT_GRAFT',
                'children' => [
                    ['name' => '풀페이스', 'code' => 'HS_FACE_FAT_GRAFT_FULL_FACE'],
                    ['name' => '이마', 'code' => 'HS_FACE_FAT_GRAFT_FOREHEAD'],
                    ['name' => '볼', 'code' => 'HS_FACE_FAT_GRAFT_CHEEK'],
                    ['name' => '눈', 'code' => 'HS_FACE_FAT_GRAFT_EYE'],
                    ['name' => '기타 (턱, 광대, 관자, 팔자 등)', 'code' => 'HS_FACE_FAT_GRAFT_OTHER'],
                ],
            ],
        ],
    ],
    [
        'name' => '가슴',
        'code' => 'HS_BREAST',
        'group_code' => 'SURGERY',
        'children' => [
            [
                'name' => '가슴모양교정',
                'code' => 'HS_BREAST_SHAPE',
                'children' => [
                    ['name' => '가슴확대(보형물)', 'code' => 'HS_BREAST_SHAPE_IMPLANT_AUGMENTATION'],
                    ['name' => '가슴축소', 'code' => 'HS_BREAST_SHAPE_REDUCTION'],
                    ['name' => '부유방제거', 'code' => 'HS_BREAST_SHAPE_ACCESSORY_REMOVAL'],
                    ['name' => '기타(보형물제거, 이물질제거, 여유증, 흉터 등)', 'code' => 'HS_BREAST_SHAPE_OTHER'],
                ],
            ],
            [
                'name' => '유두 / 유륜',
                'code' => 'HS_BREAST_NIPPLE_AREOLA',
                'children' => [
                    ['name' => '함몰유두', 'code' => 'HS_BREAST_NIPPLE_INVERTED'],
                    ['name' => '유두 / 유륜축소', 'code' => 'HS_BREAST_NIPPLE_AREOLA_REDUCTION'],
                ],
            ],
            [
                'name' => '가슴재수술',
                'code' => 'HS_BREAST_REVISION',
                'children' => [
                    ['name' => '가슴모양교정', 'code' => 'HS_BREAST_REVISION_SHAPE'],
                    ['name' => '유두 / 유륜', 'code' => 'HS_BREAST_REVISION_NIPPLE_AREOLA'],
                    ['name' => '기타', 'code' => 'HS_BREAST_REVISION_OTHER'],
                ],
            ],
        ],
    ],
    [
        'name' => '거상',
        'code' => 'HS_LIFT',
        'group_code' => 'SURGERY',
        'children' => [
            [
                'name' => '얼굴거상',
                'code' => 'HS_LIFT_FACE',
                'children' => [
                    ['name' => '안면거상', 'code' => 'HS_LIFT_FACE_FULL'],
                    ['name' => '미니거상', 'code' => 'HS_LIFT_FACE_MINI'],
                    ['name' => '이마거상', 'code' => 'HS_LIFT_FOREHEAD'],
                    ['name' => '이마축소', 'code' => 'HS_LIFT_FOREHEAD_REDUCTION'],
                    ['name' => '목거상', 'code' => 'HS_LIFT_NECK'],
                ],
            ],
            [
                'name' => '바디거상',
                'code' => 'HS_LIFT_BODY',
                'children' => [
                    ['name' => '가슴거상', 'code' => 'HS_LIFT_BODY_BREAST'],
                    ['name' => '복부거상', 'code' => 'HS_LIFT_BODY_ABDOMEN'],
                    ['name' => '팔거상', 'code' => 'HS_LIFT_BODY_ARM'],
                    ['name' => '기타', 'code' => 'HS_LIFT_BODY_OTHER'],
                ],
            ],
            [
                'name' => '거상재수술',
                'code' => 'HS_LIFT_REVISION',
                'children' => [
                    ['name' => '가슴모양교정', 'code' => 'HS_LIFT_REVISION_BREAST_SHAPE'],
                    ['name' => '유두/유륜', 'code' => 'HS_LIFT_REVISION_NIPPLE_AREOLA'],
                    ['name' => '기타', 'code' => 'HS_LIFT_REVISION_OTHER'],
                ],
            ],
        ],
    ],
    [
        'name' => '안면윤곽 / 양악',
        'code' => 'HS_FACE_CONTOUR',
        'group_code' => 'SURGERY',
        'children' => [
            [
                'name' => '광대',
                'code' => 'HS_FACE_CONTOUR_CHEEKBONE',
                'children' => [
                    ['name' => '광대축소', 'code' => 'HS_FACE_CONTOUR_CHEEKBONE_REDUCTION'],
                    ['name' => '광대확대', 'code' => 'HS_FACE_CONTOUR_CHEEKBONE_AUGMENTATION'],
                ],
            ],
            [
                'name' => '턱',
                'code' => 'HS_FACE_CONTOUR_JAW',
                'children' => [
                    ['name' => '사각턱', 'code' => 'HS_FACE_CONTOUR_SQUARE_JAW'],
                    ['name' => '턱끝', 'code' => 'HS_FACE_CONTOUR_CHIN'],
                ],
            ],
            [
                'name' => '양악',
                'code' => 'HS_FACE_CONTOUR_DOUBLE_JAW',
                'children' => [
                    ['name' => '양악', 'code' => 'HS_FACE_CONTOUR_DOUBLE_JAW_BASIC'],
                    ['name' => '상악', 'code' => 'HS_FACE_CONTOUR_UPPER_JAW'],
                    ['name' => '하악', 'code' => 'HS_FACE_CONTOUR_LOWER_JAW'],
                ],
            ],
            [
                'name' => '안면윤곽재수술',
                'code' => 'HS_FACE_CONTOUR_REVISION',
                'children' => [
                    ['name' => '광대', 'code' => 'HS_FACE_CONTOUR_REVISION_CHEEKBONE'],
                    ['name' => '윤곽', 'code' => 'HS_FACE_CONTOUR_REVISION_CONTOUR'],
                    ['name' => '양악', 'code' => 'HS_FACE_CONTOUR_REVISION_DOUBLE_JAW'],
                ],
            ],
        ],
    ],
    [
        'name' => '모발이식',
        'code' => 'HS_HAIR_TRANSPLANT',
        'group_code' => 'SURGERY',
        'children' => [
            ['name' => '절개', 'code' => 'HS_HAIR_TRANSPLANT_INCISION'],
            ['name' => '비절개', 'code' => 'HS_HAIR_TRANSPLANT_NON_INCISION'],
        ],
    ],
    [
        'name' => '기타',
        'code' => 'HS_SURGERY_OTHER',
        'group_code' => 'SURGERY',
        'children' => [
            [
                'name' => '입술성형',
                'code' => 'HS_SURGERY_OTHER_LIP',
                'children' => [
                    ['name' => '입꼬리', 'code' => 'HS_SURGERY_OTHER_LIP_CORNER'],
                    ['name' => '입술축소', 'code' => 'HS_SURGERY_OTHER_LIP_REDUCTION'],
                ],
            ],
            ['name' => '보조개성형', 'code' => 'HS_SURGERY_OTHER_DIMPLE'],
            ['name' => '귀성형', 'code' => 'HS_SURGERY_OTHER_EAR'],
            [
                'name' => '보형물',
                'code' => 'HS_SURGERY_OTHER_IMPLANT',
                'children' => [
                    ['name' => '이마', 'code' => 'HS_SURGERY_OTHER_IMPLANT_FOREHEAD'],
                    ['name' => '턱', 'code' => 'HS_SURGERY_OTHER_IMPLANT_CHIN'],
                    ['name' => '팔자', 'code' => 'HS_SURGERY_OTHER_IMPLANT_NASOLABIAL'],
                ],
            ],
            ['name' => '기타성형', 'code' => 'HS_SURGERY_OTHER_ETC'],
        ],
    ],
    [
        'name' => '리프팅',
        'code' => 'HT_LIFTING',
        'group_code' => 'TREATMENT',
        'children' => [
            ['name' => '레이저리프팅', 'code' => 'HT_LIFTING_LASER'],
            ['name' => '실리프팅', 'code' => 'HT_LIFTING_THREAD'],
            ['name' => '그외 (코 / 신경차단)', 'code' => 'HT_LIFTING_OTHER'],
        ],
    ],
    [
        'name' => '필러',
        'code' => 'HT_FILLER',
        'group_code' => 'TREATMENT',
        'children' => [
            ['name' => '풀페이스', 'code' => 'HT_FILLER_FULL_FACE'],
            ['name' => '이마', 'code' => 'HT_FILLER_FOREHEAD'],
            ['name' => '코', 'code' => 'HT_FILLER_NOSE'],
            ['name' => '입술', 'code' => 'HT_FILLER_LIP'],
            ['name' => '앞턱', 'code' => 'HT_FILLER_CHIN'],
            ['name' => '팔자', 'code' => 'HT_FILLER_NASOLABIAL'],
            ['name' => '엉덩이/골반', 'code' => 'HT_FILLER_HIP_PELVIS'],
            ['name' => '가슴', 'code' => 'HT_FILLER_BREAST'],
            ['name' => '어깨', 'code' => 'HT_FILLER_SHOULDER'],
            ['name' => '필러제거', 'code' => 'HT_FILLER_REMOVAL'],
            ['name' => '기타 (눈/미간/관자놀이/볼/광대/목/귀/종아리)', 'code' => 'HT_FILLER_OTHER'],
        ],
    ],
    [
        'name' => '보톡스',
        'code' => 'HT_BOTOX',
        'group_code' => 'TREATMENT',
        'children' => [
            ['name' => '풀페이스', 'code' => 'HT_BOTOX_FULL_FACE'],
            ['name' => '턱', 'code' => 'HT_BOTOX_JAW'],
            ['name' => '침샘', 'code' => 'HT_BOTOX_SALIVARY'],
            ['name' => '이마', 'code' => 'HT_BOTOX_FOREHEAD'],
            ['name' => '미간', 'code' => 'HT_BOTOX_GLABELLA'],
            ['name' => '눈가', 'code' => 'HT_BOTOX_EYE'],
            ['name' => '입꼬리', 'code' => 'HT_BOTOX_MOUTH_CORNER'],
            ['name' => '스킨', 'code' => 'HT_BOTOX_SKIN'],
            ['name' => '승모근/쇄골', 'code' => 'HT_BOTOX_TRAP_CLAVICLE'],
            ['name' => '기타 (잇몸/다한증/볼/광대/코/목/팔자/관자놀이/종아리/허벅지/팔)', 'code' => 'HT_BOTOX_OTHER'],
        ],
    ],
    [
        'name' => '지방분해주사',
        'code' => 'HT_FAT_DISSOLVING_INJECTION',
        'group_code' => 'TREATMENT',
        'children' => [
            ['name' => '풀페이스', 'code' => 'HT_FAT_DISSOLVING_FULL_FACE'],
            ['name' => '볼/광대', 'code' => 'HT_FAT_DISSOLVING_CHEEK_CHEEKBONE'],
            ['name' => '턱', 'code' => 'HT_FAT_DISSOLVING_CHIN'],
            ['name' => '복부', 'code' => 'HT_FAT_DISSOLVING_ABDOMEN'],
            ['name' => '팔', 'code' => 'HT_FAT_DISSOLVING_ARM'],
            ['name' => '허벅지', 'code' => 'HT_FAT_DISSOLVING_THIGH'],
            ['name' => '엉덩이', 'code' => 'HT_FAT_DISSOLVING_HIP'],
            ['name' => '기타 (무릎/종아리/발목/승모근/쇄골 등)', 'code' => 'HT_FAT_DISSOLVING_OTHER'],
        ],
    ],
    [
        'name' => '피부',
        'code' => 'HT_SKIN',
        'group_code' => 'TREATMENT',
        'children' => [
            [
                'name' => '탄력/재생',
                'code' => 'HT_SKIN_ELASTIC_REGEN',
                'children' => [
                    ['name' => '스킨부스터', 'code' => 'HT_SKIN_ELASTIC_REGEN_SKINBOOSTER'],
                    ['name' => '콜라겐부스터', 'code' => 'HT_SKIN_ELASTIC_REGEN_COLLAGEN'],
                    ['name' => '비타민주사/수액', 'code' => 'HT_SKIN_ELASTIC_REGEN_VITAMIN'],
                    ['name' => '스킨케어', 'code' => 'HT_SKIN_ELASTIC_REGEN_SKINCARE'],
                ],
            ],
            [
                'name' => '미백/잡티/홍조',
                'code' => 'HT_SKIN_WHITENING_REDNESS',
                'children' => [
                    ['name' => '기미/잡티/주근깨', 'code' => 'HT_SKIN_WHITENING_BLEMISH'],
                    ['name' => '점/검버섯', 'code' => 'HT_SKIN_WHITENING_MOLE_SPOT'],
                    ['name' => '피부톤업', 'code' => 'HT_SKIN_WHITENING_TONE_UP'],
                    ['name' => '다크서클', 'code' => 'HT_SKIN_WHITENING_DARK_CIRCLE'],
                    ['name' => '홍조', 'code' => 'HT_SKIN_WHITENING_REDNESS_BASIC'],
                    ['name' => '스킨케어', 'code' => 'HT_SKIN_WHITENING_SKINCARE'],
                    ['name' => '바디미백 (겨드랑이/팔꿈치/Y존/엉덩이/무릎/발꿈치/복숭아뼈/유두/유륜)', 'code' => 'HT_SKIN_WHITENING_BODY'],
                    ['name' => '튼살치료 (레이저, 마이크로니들)', 'code' => 'HT_SKIN_WHITENING_STRETCH_MARK'],
                ],
            ],
            [
                'name' => '보습/진정',
                'code' => 'HT_SKIN_MOISTURE_SOOTHING',
                'children' => [
                    ['name' => '스킨부스터', 'code' => 'HT_SKIN_MOISTURE_SKINBOOSTER'],
                    ['name' => '비타민주사/수액', 'code' => 'HT_SKIN_MOISTURE_VITAMIN'],
                    ['name' => '스킨케어', 'code' => 'HT_SKIN_MOISTURE_SKINCARE'],
                ],
            ],
            [
                'name' => '여드름/모공',
                'code' => 'HT_SKIN_ACNE_PORE',
                'children' => [
                    ['name' => '모공케어', 'code' => 'HT_SKIN_ACNE_PORE_CARE'],
                    ['name' => '여드름압출', 'code' => 'HT_SKIN_ACNE_EXTRACTION'],
                    ['name' => '여드름흉터', 'code' => 'HT_SKIN_ACNE_SCAR'],
                    ['name' => '여드름염증', 'code' => 'HT_SKIN_ACNE_INFLAMMATION'],
                    ['name' => '스킨케어', 'code' => 'HT_SKIN_ACNE_SKINCARE'],
                ],
            ],
            [
                'name' => '피부질환',
                'code' => 'HT_SKIN_DISEASE',
                'children' => [
                    ['name' => '아토피', 'code' => 'HT_SKIN_DISEASE_ATOPY'],
                    ['name' => '지루성피부염', 'code' => 'HT_SKIN_DISEASE_SEBORRHEIC'],
                    ['name' => '액취증/다한증', 'code' => 'HT_SKIN_DISEASE_OSMIDROSIS_HYPERHIDROSIS'],
                    ['name' => '흉터/화상', 'code' => 'HT_SKIN_DISEASE_SCAR_BURN'],
                    ['name' => '사마귀/티눈', 'code' => 'HT_SKIN_DISEASE_WART_CALLUS'],
                    ['name' => '환관종/비립종/쥐젖', 'code' => 'HT_SKIN_DISEASE_SYRINGOMA_MILIA_TAG'],
                    ['name' => '기타', 'code' => 'HT_SKIN_DISEASE_OTHER'],
                ],
            ],
        ],
    ],
    [
        'name' => '제모/탈모',
        'code' => 'HT_HAIR_REMOVAL_LOSS',
        'group_code' => 'TREATMENT',
        'children' => [
            [
                'name' => '제모',
                'code' => 'HT_HAIR_REMOVAL',
                'children' => [
                    ['name' => '얼굴제모', 'code' => 'HT_HAIR_REMOVAL_FACE'],
                    ['name' => '바디제모', 'code' => 'HT_HAIR_REMOVAL_BODY'],
                ],
            ],
            [
                'name' => '탈모',
                'code' => 'HT_HAIR_LOSS',
                'children' => [
                    ['name' => '레이저', 'code' => 'HT_HAIR_LOSS_LASER'],
                    ['name' => '약처방', 'code' => 'HT_HAIR_LOSS_MEDICINE'],
                    ['name' => '기타', 'code' => 'HT_HAIR_LOSS_OTHER'],
                ],
            ],
            [
                'name' => '케어',
                'code' => 'HT_HAIR_CARE',
                'children' => [
                    ['name' => '두피케어', 'code' => 'HT_HAIR_CARE_SCALP'],
                    ['name' => '두피/모낭주사', 'code' => 'HT_HAIR_CARE_SCALP_INJECTION'],
                ],
            ],
        ],
    ],
    [
        'name' => '치과',
        'code' => 'HT_DENTAL',
        'group_code' => 'TREATMENT',
        'children' => [
            [
                'name' => '치아교정',
                'code' => 'HT_DENTAL_ORTHODONTICS',
                'children' => [
                    ['name' => '전체교정', 'code' => 'HT_DENTAL_ORTHODONTICS_FULL'],
                    ['name' => '부분교정', 'code' => 'HT_DENTAL_ORTHODONTICS_PARTIAL'],
                ],
            ],
            [
                'name' => '치아미백',
                'code' => 'HT_DENTAL_WHITENING',
                'children' => [
                    ['name' => '라미네이트', 'code' => 'HT_DENTAL_WHITENING_LAMINATE'],
                    ['name' => '올세라믹', 'code' => 'HT_DENTAL_WHITENING_ALL_CERAMIC'],
                    ['name' => '미백제도포', 'code' => 'HT_DENTAL_WHITENING_AGENT'],
                    ['name' => '미니쉬', 'code' => 'HT_DENTAL_WHITENING_MINISH'],
                ],
            ],
            [
                'name' => '치아치료',
                'code' => 'HT_DENTAL_TREATMENT',
                'children' => [
                    ['name' => '임플란트', 'code' => 'HT_DENTAL_TREATMENT_IMPLANT'],
                    ['name' => '충치치료', 'code' => 'HT_DENTAL_TREATMENT_CAVITY'],
                    ['name' => '치아다듬기', 'code' => 'HT_DENTAL_TREATMENT_TOOTH_CONTOURING'],
                ],
            ],
            [
                'name' => '잇몸관리',
                'code' => 'HT_DENTAL_GUM',
                'children' => [
                    ['name' => '잇몸라인', 'code' => 'HT_DENTAL_GUM_LINE'],
                ],
            ],
        ],
    ],
    [
        'name' => '부인과',
        'code' => 'HT_GYNECOLOGY',
        'group_code' => 'TREATMENT',
        'children' => [
            ['name' => '질필러', 'code' => 'HT_GYNECOLOGY_VAGINAL_FILLER'],
            ['name' => '질레이저', 'code' => 'HT_GYNECOLOGY_VAGINAL_LASER'],
            ['name' => '그외', 'code' => 'HT_GYNECOLOGY_OTHER'],
        ],
    ],
    [
        'name' => '안과',
        'code' => 'HT_OPHTHALMOLOGY',
        'group_code' => 'TREATMENT',
        'children' => [
            ['name' => '라식/라섹', 'code' => 'HT_OPHTHALMOLOGY_LASIK_LASEK'],
            ['name' => '렌즈삽입', 'code' => 'HT_OPHTHALMOLOGY_LENS_IMPLANT'],
            ['name' => '안구건조증', 'code' => 'HT_OPHTHALMOLOGY_DRY_EYE'],
        ],
    ],
    [
        'name' => '한방',
        'code' => 'HT_ORIENTAL',
        'group_code' => 'TREATMENT',
        'children' => [
            ['name' => '다이어트', 'code' => 'HT_ORIENTAL_DIET'],
            ['name' => '여드름/모공', 'code' => 'HT_ORIENTAL_ACNE_PORE'],
            ['name' => '탄력/재생', 'code' => 'HT_ORIENTAL_ELASTIC_REGEN'],
            ['name' => '미백/잡티/홍조', 'code' => 'HT_ORIENTAL_WHITENING_REDNESS'],
            ['name' => '튼살치료', 'code' => 'HT_ORIENTAL_STRETCH_MARK'],
        ],
    ],
];
