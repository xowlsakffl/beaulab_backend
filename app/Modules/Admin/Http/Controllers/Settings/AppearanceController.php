<?php

namespace App\Modules\Admin\Http\Controllers\Settings;

use App\Modules\Admin\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class AppearanceController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('admin/settings/appearance');
    }
}
