<?php

namespace Database\Seeders;

use App\Modules\Admin\Models\Admin;
use Illuminate\Database\Seeder;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Admin::factory(10)->create();

        Admin::factory()->create([
            'name' => 'Test Admin',
            'email' => 'test@example.com',
        ]);
    }
}
