<?php

namespace Database\Seeders;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Seeder;

final class CategorySeeder extends Seeder
{
    public function run(): void
    {
        CategoryFactory::seedHospitalCategories();
        CategoryFactory::seedHospitalEvaluationCategories();
        CategoryFactory::seedBeautyCategories();
        CategoryFactory::seedTalkCategories();
        CategoryFactory::seedFaqCategories();
    }
}
