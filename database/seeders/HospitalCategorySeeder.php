<?php

namespace Database\Seeders;

use Database\Factories\CategoryFactory;
use Illuminate\Database\Seeder;

final class HospitalCategorySeeder extends Seeder
{
    public function run(): void
    {
        CategoryFactory::seedHospitalCategories();
    }
}
