<?php

use App\Modules\Partner\Http\Controllers\VideoRequest\VideoRequestForPartnerController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'abilities:actor:partner', 'permission:common.access'])->group(function () {
    /**
     * 파트너 동영상 검수 신청
     */
    Route::get('video-requests', [VideoRequestForPartnerController::class, 'getVideoRequestsForPartner'])
        ->name('videoRequests.getVideoRequestsForPartner');
    Route::post('video-requests', [VideoRequestForPartnerController::class, 'storeVideoRequestForPartner'])
        ->name('videoRequests.storeVideoRequestForPartner');
    Route::match(['post', 'put', 'patch'], 'video-requests/{videoRequest}', [VideoRequestForPartnerController::class, 'updateVideoRequestForPartner'])
        ->name('videoRequests.updateVideoRequestForPartner');
    Route::delete('video-requests/{videoRequest}', [VideoRequestForPartnerController::class, 'deleteVideoRequestForPartner'])
        ->name('videoRequests.deleteVideoRequestForPartner');
});
