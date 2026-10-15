<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Support\CategoryUsageAvailability;

trait WithHospitalDepartment
{
    protected function setUpWithHospitalDepartment(): void
    {
        Category::firstOrCreate(
            ['domain' => Category::DOMAIN_HOSPITAL_DEPARTMENT, 'code' => 'PLASTIC_SURGERY'],
            ['depth' => 1, 'name' => '성형외과', 'full_path' => '성형외과', 'status' => 'ACTIVE'],
        );
        CategoryUsageAvailability::forgetRequestCache();
    }
}
