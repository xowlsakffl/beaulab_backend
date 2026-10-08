<?php

namespace Database\Seeders;

use App\Common\Support\DevelopmentDataGuard;
use Illuminate\Database\Seeder;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $withDemo = (bool) config('seeding.development.enabled');
        if ($withDemo) {
            DevelopmentDataGuard::assertEnabled();
            DevelopmentDataGuard::assertDemoEmpty();
        }
        $this->call([
            AuthorizationSeeder::class,
            AccountStaffSeeder::class,
            CategorySeeder::class,
            NicknameWordSeeder::class,
            ConsultationPricingSeeder::class,
            HospitalFeatureSeeder::class,
        ]);

        if ($withDemo) {
            $this->call(DemoSeeder::class);
        }
    }
}
