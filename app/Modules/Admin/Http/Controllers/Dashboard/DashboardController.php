<?php

namespace App\Modules\Admin\Http\Controllers\Dashboard;

use App\Modules\Admin\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('admin/dashboard');
    }
}
