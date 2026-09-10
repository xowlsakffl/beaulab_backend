<?php

namespace Database\Seeders;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Seeder;

final class HospitalEventPromotionCategorySeeder extends Seeder
{
    public function run(): void
    {
        CategoryFactory::seedHospitalEventPromotionCategories();
    }
}
