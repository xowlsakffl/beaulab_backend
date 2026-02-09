<?php

use App\Modules\Staff\Http\Controllers\Dashboard\DashboardForStaffController;
use App\Modules\Staff\Http\Controllers\Hospital\HospitalForStaffController;
use Illuminate\Support\Facades\Route;

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware(['web', 'auth:admin'])->group(function () {

        // 병원 생성
        Route::get('/hospitals', [HospitalForStaffController::class, 'getHospitalsForStaff'])
            ->middleware('permission:beaulab.hospital.list')
            ->name('hospitals.storeHospitalForStaff');

        // 병원 생성
        Route::post('/hospitals', [HospitalForStaffController::class, 'storeHospitalForStaff'])
            ->middleware('permission:beaulab.hospital.create')
            ->name('hospitals.storeHospitalForStaff');

        // 병원 수정 (직원 전용 - 특정 병원 수정)
        Route::match(['put', 'patch'], '/hospitals/{hospital}', [HospitalForStaffController::class, 'updateHospitalForStaff'])
            ->middleware('permission:beaulab.hospital.update')
            ->name('hospitals.updateHospitalForStaff');

        // 병원 삭제 (직원 전용 - 특정 병원 삭제)
        Route::delete('/hospitals/{hospital}', [HospitalForStaffController::class, 'deleteHospitalForStaff'])
            ->middleware('permission:beaulab.hospital.delete')
            ->name('hospitals.deleteHospitalForStaff');
    });
});
