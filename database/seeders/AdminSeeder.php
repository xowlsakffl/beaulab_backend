<?php

namespace Database\Seeders;

use App\Modules\Admin\Models\Admin;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('SEED_ADMIN_EMAIL', '');
        $name = (string) env('SEED_ADMIN_NAME', 'Admin');
        $password = (string) env('SEED_ADMIN_PASSWORD', '');

        if ($email === '' || $password === '') {
            $this->command?->warn('AdminSeeder skipped: SEED_ADMIN_EMAIL / SEED_ADMIN_PASSWORD 가 설정되지 않았습니다.');
            return;
        }

        Admin::firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => $password, // Admin 모델 casts에 의해 자동 해시
            ]
        );
    }
}
