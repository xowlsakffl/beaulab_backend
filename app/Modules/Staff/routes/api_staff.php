<?php

use App\Modules\Staff\Http\Controllers\AccountUser\AccountUserForStaffController;
use App\Modules\Staff\Http\Controllers\Auth\AuthForStaffController;
use App\Modules\Staff\Http\Controllers\Beauty\BeautyForStaffController;
use App\Modules\Staff\Http\Controllers\Category\CategoryForStaffController;
use App\Modules\Staff\Http\Controllers\Dashboard\DashboardForStaffController;
use App\Modules\Staff\Http\Controllers\Faq\FaqForStaffController;
use App\Modules\Staff\Http\Controllers\HospitalDoctor\DoctorForStaffController;
use App\Modules\Staff\Http\Controllers\BeautyExpert\ExpertForStaffController;
use App\Modules\Staff\Http\Controllers\Hospital\HospitalForStaffController;
use App\Modules\Staff\Http\Controllers\HospitalFeature\HospitalFeatureForStaffController;
use App\Modules\Staff\Http\Controllers\Notice\NoticeForStaffController;
use App\Modules\Staff\Http\Controllers\HospitalTalk\HospitalTalkForStaffController;
use App\Modules\Staff\Http\Controllers\HospitalTalkComment\HospitalTalkCommentForStaffController;
use App\Modules\Staff\Http\Controllers\HospitalVideo\HospitalVideoForStaffController;
use App\Modules\Staff\Http\Controllers\HospitalVideoRequest\VideoRequestForStaffController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthForStaffController::class, 'login'])->name('login')->middleware('throttle:6,1');
});

Route::middleware(['auth:sanctum', 'abilities:actor:staff', 'permission:common.access'])->group(function () {

    // 인증
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthForStaffController::class, 'logout'])->name('logout');
    });

    Route::get('/profile', [AuthForStaffController::class, 'getMyProfile'])->name('profile');
    Route::match(['put', 'patch'], '/profile', [AuthForStaffController::class, 'updateMyProfile'])->name('profile.update');
    Route::match(['put', 'patch'], '/password', [AuthForStaffController::class, 'updateMyPassword'])->name('password.update')
        ->middleware('throttle:6,1');

    // 대시보드
    Route::get('/dashboard', [DashboardForStaffController::class, 'getDashboardForStaff'])
        ->name('dashboard');

    /**
     * 병원 관리
     **/
    Route::get('hospital-features', [HospitalFeatureForStaffController::class, 'getHospitalFeaturesForStaff'])
        ->name('hospital-features.getHospitalFeaturesForStaff');
    Route::get('hospitals', [HospitalForStaffController::class, 'getHospitalsForStaff'])
        ->name('hospitals.getHospitalsForStaff');
    Route::post('hospitals/check-name', [HospitalForStaffController::class, 'checkHospitalNameDuplicateForStaff'])
        ->name('hospitals.checkHospitalNameDuplicateForStaff');
    Route::post('hospitals/check-business-number', [HospitalForStaffController::class, 'checkHospitalBusinessNumberDuplicateForStaff'])
        ->name('hospitals.checkHospitalBusinessNumberDuplicateForStaff');
    Route::get('hospitals/{hospital}', [HospitalForStaffController::class, 'getHospitalForStaff'])
        ->name('hospitals.getHospitalForStaff');
    Route::post('hospitals', [HospitalForStaffController::class, 'storeHospitalForStaff'])
        ->name('hospitals.storeHospitalForStaff');
    Route::match(['post', 'put', 'patch'], 'hospitals/{hospital}', [HospitalForStaffController::class, 'updateHospitalForStaff'])
        ->name('hospitals.updateHospitalForStaff');
    Route::delete('hospitals/{hospital}', [HospitalForStaffController::class, 'deleteHospitalForStaff'])
        ->name('hospitals.deleteHospitalForStaff');

    /**
     * 카테고리 관리
     **/
    Route::get('categories/selector', [CategoryForStaffController::class, 'getCategorySelectorForStaff'])
        ->name('categories.getCategorySelectorForStaff');
    Route::get('categories', [CategoryForStaffController::class, 'getCategoriesForStaff'])
        ->name('categories.getCategoriesForStaff');
    Route::get('categories/{category}', [CategoryForStaffController::class, 'getCategoryForStaff'])
        ->name('categories.getCategoryForStaff');
    Route::post('categories', [CategoryForStaffController::class, 'storeCategoryForStaff'])
        ->name('categories.storeCategoryForStaff');
    Route::match(['post', 'put', 'patch'], 'categories/{category}', [CategoryForStaffController::class, 'updateCategoryForStaff'])
        ->name('categories.updateCategoryForStaff');
    Route::delete('categories/{category}', [CategoryForStaffController::class, 'deleteCategoryForStaff'])
        ->name('categories.deleteCategoryForStaff');

    /**
     * 뷰티 관리
     **/
    Route::get('beauties', [BeautyForStaffController::class, 'getBeautiesForStaff'])
        ->name('beauties.getBeautiesForStaff');
    Route::get('beauties/{beauty}', [BeautyForStaffController::class, 'getBeautyForStaff'])
        ->name('beauties.getBeautyForStaff');
    Route::post('beauties', [BeautyForStaffController::class, 'storeBeautyForStaff'])
        ->name('beauties.storeBeautyForStaff');
    Route::match(['post', 'put', 'patch'], 'beauties/{beauty}', [BeautyForStaffController::class, 'updateBeautyForStaff'])
        ->name('beauties.updateBeautyForStaff');
    Route::delete('beauties/{beauty}', [BeautyForStaffController::class, 'deleteBeautyForStaff'])
        ->name('beauties.deleteBeautyForStaff');

    /**
     * 일반회원 관리
     **/
    Route::get('users', [AccountUserForStaffController::class, 'getAccountUsersForStaff'])
        ->name('users.getAccountUsersForStaff');
    Route::get('users/{user}', [AccountUserForStaffController::class, 'getAccountUserForStaff'])
        ->name('users.getAccountUserForStaff');
    Route::match(['post', 'put', 'patch'], 'users/{user}', [AccountUserForStaffController::class, 'updateAccountUserForStaff'])
        ->name('users.updateAccountUserForStaff');
    Route::delete('users/{user}', [AccountUserForStaffController::class, 'deleteAccountUserForStaff'])
        ->name('users.deleteAccountUserForStaff');

    /**
     * 의사 관리
     **/
    Route::get('doctors', [DoctorForStaffController::class, 'getDoctorsForStaff'])
        ->name('doctors.getDoctorsForStaff');
    Route::get('doctors/{doctor}', [DoctorForStaffController::class, 'getDoctorForStaff'])
        ->name('doctors.getDoctorForStaff');
    Route::post('doctors', [DoctorForStaffController::class, 'storeDoctorForStaff'])
        ->name('doctors.storeDoctorForStaff');
    Route::match(['post', 'put', 'patch'], 'doctors/{doctor}', [DoctorForStaffController::class, 'updateDoctorForStaff'])
        ->name('doctors.updateDoctorForStaff');
    Route::delete('doctors/{doctor}', [DoctorForStaffController::class, 'deleteDoctorForStaff'])
        ->name('doctors.deleteDoctorForStaff');

    /**
     * 뷰티전문가 관리
     **/
    Route::get('experts', [ExpertForStaffController::class, 'getExpertsForStaff'])
        ->name('experts.getExpertsForStaff');
    Route::get('experts/{expert}', [ExpertForStaffController::class, 'getExpertForStaff'])
        ->name('experts.getExpertForStaff');
    Route::post('experts', [ExpertForStaffController::class, 'storeExpertForStaff'])
        ->name('experts.storeExpertForStaff');
    Route::match(['post', 'put', 'patch'], 'experts/{expert}', [ExpertForStaffController::class, 'updateExpertForStaff'])
        ->name('experts.updateExpertForStaff');
    Route::delete('experts/{expert}', [ExpertForStaffController::class, 'deleteExpertForStaff'])
        ->name('experts.deleteExpertForStaff');

    /**
     * 동영상등록 관리
     **/
    Route::get('videos', [HospitalVideoForStaffController::class, 'getVideosForStaff'])
        ->name('videos.getVideosForStaff');
    Route::get('videos/{video}', [HospitalVideoForStaffController::class, 'getVideoForStaff'])
        ->name('videos.getVideoForStaff');
    Route::post('videos', [HospitalVideoForStaffController::class, 'storeVideoForStaff'])
        ->name('videos.storeVideoForStaff');
    Route::match(['post', 'put', 'patch'], 'videos/{video}', [HospitalVideoForStaffController::class, 'updateVideoForStaff'])
        ->name('videos.updateVideoForStaff');
    Route::delete('videos/{video}', [HospitalVideoForStaffController::class, 'deleteVideoForStaff'])
        ->name('videos.deleteVideoForStaff');

    /**
     * 토크 관리
     **/
    Route::get('talks', [HospitalTalkForStaffController::class, 'getTalksForStaff'])
        ->name('talks.getTalksForStaff');
    Route::get('talks/{talk}', [HospitalTalkForStaffController::class, 'getTalkForStaff'])
        ->name('talks.getTalkForStaff');
    Route::post('talks', [HospitalTalkForStaffController::class, 'storeTalkForStaff'])
        ->name('talks.storeTalkForStaff');
    Route::match(['post', 'put', 'patch'], 'talks/{talk}', [HospitalTalkForStaffController::class, 'updateTalkForStaff'])
        ->name('talks.updateTalkForStaff');
    Route::delete('talks/{talk}', [HospitalTalkForStaffController::class, 'deleteTalkForStaff'])
        ->name('talks.deleteTalkForStaff');

    /**
     * 토크 댓글 관리
     **/
    Route::get('talk-comments', [HospitalTalkCommentForStaffController::class, 'getCommentsForStaff'])
        ->name('talk-comments.getCommentsForStaff');
    Route::get('talk-comments/{comment}', [HospitalTalkCommentForStaffController::class, 'getCommentForStaff'])
        ->name('talk-comments.getCommentForStaff');
    Route::post('talk-comments', [HospitalTalkCommentForStaffController::class, 'storeCommentForStaff'])
        ->name('talk-comments.storeCommentForStaff');
    Route::match(['post', 'put', 'patch'], 'talk-comments/{comment}', [HospitalTalkCommentForStaffController::class, 'updateCommentForStaff'])
        ->name('talk-comments.updateCommentForStaff');
    Route::delete('talk-comments/{comment}', [HospitalTalkCommentForStaffController::class, 'deleteCommentForStaff'])
        ->name('talk-comments.deleteCommentForStaff');

    /**
     * 공지사항
     **/
    Route::get('notices', [NoticeForStaffController::class, 'getNoticesForStaff'])
        ->name('notices.getNoticesForStaff');
    Route::get('notices/{notice}', [NoticeForStaffController::class, 'getNoticeForStaff'])
        ->name('notices.getNoticeForStaff');
    Route::post('notices', [NoticeForStaffController::class, 'storeNoticeForStaff'])
        ->name('notices.storeNoticeForStaff');
    Route::post('notices/editor-images', [NoticeForStaffController::class, 'uploadEditorImageForStaff'])
        ->name('notices.uploadEditorImageForStaff');
    Route::delete('notices/editor-images', [NoticeForStaffController::class, 'cleanupEditorImagesForStaff'])
        ->name('notices.cleanupEditorImagesForStaff');
    Route::match(['post', 'put', 'patch'], 'notices/{notice}', [NoticeForStaffController::class, 'updateNoticeForStaff'])
        ->name('notices.updateNoticeForStaff');
    Route::delete('notices/{notice}', [NoticeForStaffController::class, 'deleteNoticeForStaff'])
        ->name('notices.deleteNoticeForStaff');

    /**
     * FAQ
     **/
    Route::get('faqs', [FaqForStaffController::class, 'getFaqsForStaff'])
        ->name('faqs.getFaqsForStaff');
    Route::get('faqs/{faq}', [FaqForStaffController::class, 'getFaqForStaff'])
        ->name('faqs.getFaqForStaff');
    Route::post('faqs', [FaqForStaffController::class, 'storeFaqForStaff'])
        ->name('faqs.storeFaqForStaff');
    Route::post('faqs/editor-images', [FaqForStaffController::class, 'uploadEditorImageForStaff'])
        ->name('faqs.uploadEditorImageForStaff');
    Route::delete('faqs/editor-images', [FaqForStaffController::class, 'cleanupEditorImagesForStaff'])
        ->name('faqs.cleanupEditorImagesForStaff');
    Route::match(['post', 'put', 'patch'], 'faqs/{faq}', [FaqForStaffController::class, 'updateFaqForStaff'])
        ->name('faqs.updateFaqForStaff');
    Route::delete('faqs/{faq}', [FaqForStaffController::class, 'deleteFaqForStaff'])
        ->name('faqs.deleteFaqForStaff');
});
