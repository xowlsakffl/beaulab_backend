<?php

namespace Database\Seeders;

use App\Domains\Beauty\Models\Beauty;
use App\Domains\Hospital\Models\Hospital;
use Illuminate\Database\Seeder;

final class BeautySeeder extends Seeder
{
    public function run(): void
    {
        Beauty::factory()
            ->count(40)
            ->approved()
            ->active()
            ->withOwner()
            ->create();

        // 나머지 랜덤 뷰티 + 소유주
        Beauty::factory()
            ->count(10)
            ->withOwner()
            ->create();
    }
}
