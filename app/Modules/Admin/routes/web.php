<?php

use App\Modules\Admin\Http\Controllers\Dashboard\DashboardController;
use App\Modules\Admin\Http\Controllers\Hospital\HospitalController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('home');
})->name('home');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware(['web', 'auth:admin'])->group(function () {
        // 대시보드
        Route::get('', [DashboardController::class, 'indexDashboard'])
            ->middleware('permission:common.dashboard.show')
            ->name('dashboard');

        /**
         * 뷰랩 직원 전용
         */
        // 병원 전체 목록 페이지(직원 전용)
        Route::get('/hospitals', [HospitalController::class, 'indexHospitalPageForStaff'])
            ->middleware('permission:beaulab.hospital.list')
            ->name('hospitals.indexHospitalPageForStaff');

        // 병원 생성 (직원 전용)
        Route::get('/hospitals/create', [HospitalController::class, 'createHospitalForStaff'])
            ->middleware('permission:beaulab.hospital.create')
            ->name('hospitals.createHospitalForStaff');

        // 병원 수정 (직원 전용)
        Route::get('/hospitals/{hospital}/edit', [HospitalController::class, 'editHospitalForStaff'])
            ->middleware('permission:beaulab.hospital.delete')
            ->name('hospitals.editHospitalForStaff');

        // 병원 상세 (직원 전용)
        Route::get('/hospitals/{hospital}/edit', [HospitalController::class, 'showHospitalForStaff'])
            ->middleware('permission:beaulab.hospital.show')
            ->name('hospitals.showHospitalForStaff');

        // 병원 생성 (뷰랩 직원 전용)
        Route::post('/hospitals', [HospitalController::class, 'storeHospitalForStaff'])
            ->middleware('permission:beaulab.hospital.create')
            ->name('hospitals.storeHospitalForStaff');

        // 병원 수정 (뷰랩 직원 전용 - 특정 병원 수정)
        Route::match(['put', 'patch'], '/hospitals/{hospital}', [HospitalController::class, 'updateHospitalForStaff'])
            ->middleware('permission:beaulab.hospital.update')
            ->name('hospitals.updateHospitalForStaff');

        // 병원 삭제 (뷰랩 직원 전용 - 특정 병원 삭제)
        Route::delete('/hospitals/{hospital}', [HospitalController::class, 'deleteHospitalForStaff'])
            ->middleware('permission:beaulab.hospital.delete')
            ->name('hospitals.deleteHospitalForStaff');

        /**
         * 병원 전용
         */

        // 내 병원 데이터 (병원 회원 전용)
        Route::get('/hospital', [HospitalController::class, 'apiGetMyHospitalForHospital'])
            ->middleware('permission:hospital.profile.show')
            ->name('hospital.apiGetMyHospitalForHospital');

        // 내 병원 수정 (병원 회원 전용)
        Route::match(['put', 'patch'], '/hospital', [HospitalController::class, 'apiUpdateMyHospitalForHospital'])
            ->middleware('permission:hospital.profile.update')
            ->name('hospital.update');

        // 내 병원 정보 조회 (병원회원 전용)
        Route::get('/hospital/my', [HospitalController::class, 'myHospitalForHospital'])
            ->middleware('permission:hospital.profile.show')
            ->name('hospital.myHospitalForHospital');



        // 설정
        require __DIR__.'/settings.php';
    });
});
