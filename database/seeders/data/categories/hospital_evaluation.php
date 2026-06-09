<?php

use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;

return [
    ['name' => '성형후기', 'code' => HospitalEvaluation::CATEGORY_CODE_SURGERY],
    ['name' => '시술후기', 'code' => HospitalEvaluation::CATEGORY_CODE_TREATMENT],
    ['name' => '상담후기', 'code' => HospitalEvaluation::CATEGORY_CODE_CONSULTATION],
];
