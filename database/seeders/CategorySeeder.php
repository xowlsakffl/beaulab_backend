<?php

namespace Database\Seeders;

use Database\Seeders\Support\CategoryDefaults;
use Illuminate\Database\Seeder;

final class CategorySeeder extends Seeder
{
    public function run(): void
    {
        CategoryDefaults::seedHospitalDepartments();
        CategoryDefaults::seedHospitalCategories();
        CategoryDefaults::seedHospitalEvaluationCategories();
        CategoryDefaults::seedBeautyCategories();
        CategoryDefaults::seedTalkCategories();
        CategoryDefaults::seedFaqCategories();
    }
}
