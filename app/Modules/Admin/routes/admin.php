<?php

use App\Modules\Admin\Http\Controllers\Hospital\HospitalController;
use Illuminate\Support\Facades\Route;

// Admin JSON API (/admin/api/*)
Route::prefix('admin/api')
    ->name('admin.api.')
    ->middleware(['api', 'auth:admin'])
    ->group(function () {

        // 병원 전체 목록 데이터 (뷰랩 직원 전용)
        Route::get('/hospitals', [HospitalController::class, 'apiGetHospitalListForStaff'])
            ->middleware('permission:beaulab.hospital.list')
            ->name('hospitals.apiGetHospitalList');

        // 병원 수정 (뷰랩 직원 전용 - 특정 병원 수정)
        Route::match(['put', 'patch'], '/hospitals/{hospital}', [HospitalController::class, 'apiUpdateHospitalForStaff'])
            ->middleware('permission:beaulab.hospital.update')
            ->name('hospitals.apiUpdateHospitalForStaff');

        // 내 병원 데이터 (병원 회원 전용)
        Route::get('/hospital', [HospitalController::class, 'apiGetMyHospitalForHospital'])
            ->middleware('permission:hospital.profile.show')
            ->name('hospital.apiGetMyHospitalForHospital');

        // 내 병원 수정 (병원 회원 전용)
        Route::match(['put', 'patch'], '/hospital', [HospitalController::class, 'apiUpdateMyHospitalForHospital'])
            ->middleware('permission:hospital.profile.update')
            ->name('hospital.update');
    });
