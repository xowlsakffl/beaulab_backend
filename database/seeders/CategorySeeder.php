<?php

namespace Database\Seeders;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Seeder;

final class CategorySeeder extends Seeder
{
    public function run(): void
    {
        CategoryFactory::seedHospitalCategories();
        CategoryFactory::seedBeautyCategories();
        CategoryFactory::seedFaqCategories();
    }
}
