<?php

/**
 * 스태프 백오피스 API 라우트 파일.
 * 인증/권한 미들웨어와 컨트롤러 매핑만 두고 비즈니스 로직은 컨트롤러와 Action 계층으로 위임한다.
 */

use App\Domains\HospitalReview\Models\HospitalReview;
use App\Modules\Staff\Http\Controllers\AccountUser\AccountUserForStaffController;
use App\Modules\Staff\Http\Controllers\AdminNote\AdminNoteForStaffController;
use App\Modules\Staff\Http\Controllers\Auth\AuthForStaffController;
use App\Modules\Staff\Http\Controllers\Beauty\BeautyForStaffController;
use App\Modules\Staff\Http\Controllers\BeautyExpert\BeautyExpertForStaffController;
use App\Modules\Staff\Http\Controllers\Category\CategoryForStaffController;
use App\Modules\Staff\Http\Controllers\Dashboard\DashboardForStaffController;
use App\Modules\Staff\Http\Controllers\Faq\FaqForStaffController;
use App\Modules\Staff\Http\Controllers\Hashtag\HashtagForStaffController;
use App\Modules\Staff\Http\Controllers\Hospital\HospitalForStaffController;
use App\Modules\Staff\Http\Controllers\HospitalDoctor\HospitalDoctorForStaffController;
use App\Modules\Staff\Http\Controllers\HospitalEvaluation\HospitalEvaluationForStaffController;
use App\Modules\Staff\Http\Controllers\HospitalFeature\HospitalFeatureForStaffController;
use App\Modules\Staff\Http\Controllers\HospitalReview\HospitalReviewForStaffController;
use App\Modules\Staff\Http\Controllers\HospitalReviewComment\HospitalReviewCommentForStaffController;
use App\Modules\Staff\Http\Controllers\HospitalVideo\HospitalVideoForStaffController;
use App\Modules\Staff\Http\Controllers\Notice\NoticeForStaffController;
use App\Modules\Staff\Http\Controllers\Talk\TalkForStaffController;
use App\Modules\Staff\Http\Controllers\TalkComment\TalkCommentForStaffController;
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

    Route::get('/notes', [AdminNoteForStaffController::class, 'getAdminNotesForStaff'])
        ->name('notes.getAdminNotesForStaff');
    Route::post('/notes', [AdminNoteForStaffController::class, 'createAdminNoteForStaff'])
        ->name('notes.createAdminNoteForStaff');
    Route::match(['put', 'patch'], '/notes/{note}', [AdminNoteForStaffController::class, 'updateAdminNoteForStaff'])
        ->name('notes.updateAdminNoteForStaff');

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
    Route::post('hospitals', [HospitalForStaffController::class, 'createHospitalForStaff'])
        ->name('hospitals.createHospitalForStaff');
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
    Route::post('categories', [CategoryForStaffController::class, 'createCategoryForStaff'])
        ->name('categories.createCategoryForStaff');
    Route::match(['post', 'put', 'patch'], 'categories/{category}', [CategoryForStaffController::class, 'updateCategoryForStaff'])
        ->name('categories.updateCategoryForStaff');
    Route::delete('categories/{category}', [CategoryForStaffController::class, 'deleteCategoryForStaff'])
        ->name('categories.deleteCategoryForStaff');

    /**
     * 해시태그 관리
     **/
    Route::get('hashtags', [HashtagForStaffController::class, 'getHashtagsForStaff'])
        ->name('hashtags.getHashtagsForStaff');
    Route::get('hashtags/{hashtag}', [HashtagForStaffController::class, 'getHashtagForStaff'])
        ->name('hashtags.getHashtagForStaff');
    Route::post('hashtags', [HashtagForStaffController::class, 'createHashtagForStaff'])
        ->name('hashtags.createHashtagForStaff');
    Route::match(['post', 'put', 'patch'], 'hashtags/{hashtag}', [HashtagForStaffController::class, 'updateHashtagForStaff'])
        ->name('hashtags.updateHashtagForStaff');
    Route::delete('hashtags/{hashtag}', [HashtagForStaffController::class, 'deleteHashtagForStaff'])
        ->name('hashtags.deleteHashtagForStaff');

    /**
     * 뷰티 관리
     **/
    Route::get('beauties', [BeautyForStaffController::class, 'getBeautiesForStaff'])
        ->name('beauties.getBeautiesForStaff');
    Route::get('beauties/{beauty}', [BeautyForStaffController::class, 'getBeautyForStaff'])
        ->name('beauties.getBeautyForStaff');
    Route::post('beauties', [BeautyForStaffController::class, 'createBeautyForStaff'])
        ->name('beauties.createBeautyForStaff');
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
    Route::get('doctors/hospital-options', [HospitalDoctorForStaffController::class, 'getDoctorHospitalOptionsForStaff'])
        ->name('doctors.getDoctorHospitalOptionsForStaff');
    Route::get('doctors', [HospitalDoctorForStaffController::class, 'getDoctorsForStaff'])
        ->name('doctors.getDoctorsForStaff');
    Route::get('doctors/{doctor}', [HospitalDoctorForStaffController::class, 'getDoctorForStaff'])
        ->name('doctors.getDoctorForStaff');
    Route::post('doctors', [HospitalDoctorForStaffController::class, 'createDoctorForStaff'])
        ->name('doctors.createDoctorForStaff');
    Route::match(['post', 'put', 'patch'], 'doctors/{doctor}', [HospitalDoctorForStaffController::class, 'updateDoctorForStaff'])
        ->name('doctors.updateDoctorForStaff');
    Route::delete('doctors/{doctor}', [HospitalDoctorForStaffController::class, 'deleteDoctorForStaff'])
        ->name('doctors.deleteDoctorForStaff');

    /**
     * 뷰티전문가 관리
     **/
    Route::get('experts', [BeautyExpertForStaffController::class, 'getExpertsForStaff'])
        ->name('experts.getExpertsForStaff');
    Route::get('experts/{expert}', [BeautyExpertForStaffController::class, 'getExpertForStaff'])
        ->name('experts.getExpertForStaff');
    Route::post('experts', [BeautyExpertForStaffController::class, 'createExpertForStaff'])
        ->name('experts.createExpertForStaff');
    Route::match(['post', 'put', 'patch'], 'experts/{expert}', [BeautyExpertForStaffController::class, 'updateExpertForStaff'])
        ->name('experts.updateExpertForStaff');
    Route::delete('experts/{expert}', [BeautyExpertForStaffController::class, 'deleteExpertForStaff'])
        ->name('experts.deleteExpertForStaff');

    /**
     * 동영상등록 관리
     **/
    Route::get('videos/hospital-options', [HospitalVideoForStaffController::class, 'getVideoHospitalOptionsForStaff'])
        ->name('videos.getVideoHospitalOptionsForStaff');
    Route::get('videos/doctor-options', [HospitalVideoForStaffController::class, 'getVideoDoctorOptionsForStaff'])
        ->name('videos.getVideoDoctorOptionsForStaff');
    Route::get('videos', [HospitalVideoForStaffController::class, 'getVideosForStaff'])
        ->name('videos.getVideosForStaff');
    Route::get('videos/{video}/download-video-file', [HospitalVideoForStaffController::class, 'downloadVideoFileForStaff'])
        ->name('videos.downloadVideoFileForStaff');
    Route::get('videos/{video}', [HospitalVideoForStaffController::class, 'getVideoForStaff'])
        ->name('videos.getVideoForStaff');
    Route::post('videos', [HospitalVideoForStaffController::class, 'createVideoForStaff'])
        ->name('videos.createVideoForStaff');
    Route::match(['post', 'put', 'patch'], 'videos/{video}', [HospitalVideoForStaffController::class, 'updateVideoForStaff'])
        ->name('videos.updateVideoForStaff');
    Route::delete('videos/{video}', [HospitalVideoForStaffController::class, 'deleteVideoForStaff'])
        ->name('videos.deleteVideoForStaff');

    /**
     * 병의원 후기 관리
     **/
    Route::get('hospital-reviews/surgery', [HospitalReviewForStaffController::class, 'getSurgeryHospitalReviewsForStaff'])
        ->defaults('category_domain', HospitalReview::CATEGORY_DOMAIN_SURGERY)
        ->name('hospital-reviews.getSurgeryHospitalReviewsForStaff');
    Route::get('hospital-reviews/treatment', [HospitalReviewForStaffController::class, 'getTreatmentHospitalReviewsForStaff'])
        ->defaults('category_domain', HospitalReview::CATEGORY_DOMAIN_TREATMENT)
        ->name('hospital-reviews.getTreatmentHospitalReviewsForStaff');
    Route::patch('hospital-reviews/status', [HospitalReviewForStaffController::class, 'updateHospitalReviewStatusForStaff'])
        ->name('hospital-reviews.updateHospitalReviewStatusForStaff');
    Route::get('hospital-reviews/{hospitalReview}', [HospitalReviewForStaffController::class, 'getHospitalReviewForStaff'])
        ->name('hospital-reviews.getHospitalReviewForStaff');
    Route::get('hospital-review-comments', [HospitalReviewCommentForStaffController::class, 'getCommentsForStaff'])
        ->name('hospital-review-comments.getCommentsForStaff');
    Route::patch('hospital-review-comments/status', [HospitalReviewCommentForStaffController::class, 'updateHospitalReviewCommentStatusForStaff'])
        ->name('hospital-review-comments.updateHospitalReviewCommentStatusForStaff');

    /**
     * 병의원 평가 관리
     **/
    Route::get('hospital-evaluations', [HospitalEvaluationForStaffController::class, 'getHospitalEvaluationsForStaff'])
        ->name('hospital-evaluations.getHospitalEvaluationsForStaff');
    Route::patch('hospital-evaluations/status', [HospitalEvaluationForStaffController::class, 'updateHospitalEvaluationStatusForStaff'])
        ->name('hospital-evaluations.updateHospitalEvaluationStatusForStaff');
    Route::patch('hospital-evaluations/{hospitalEvaluation}/receipt/verify', [HospitalEvaluationForStaffController::class, 'verifyHospitalEvaluationReceiptForStaff'])
        ->name('hospital-evaluations.verifyHospitalEvaluationReceiptForStaff');
    Route::patch('hospital-evaluations/{hospitalEvaluation}/receipt/reject', [HospitalEvaluationForStaffController::class, 'rejectHospitalEvaluationReceiptForStaff'])
        ->name('hospital-evaluations.rejectHospitalEvaluationReceiptForStaff');
    Route::get('hospital-evaluations/{hospitalEvaluation}', [HospitalEvaluationForStaffController::class, 'getHospitalEvaluationForStaff'])
        ->name('hospital-evaluations.getHospitalEvaluationForStaff');
    /**
     * 토크 관리
     **/
    Route::get('talks', [TalkForStaffController::class, 'getTalksForStaff'])
        ->name('talks.getTalksForStaff');
    Route::get('talks/excel-download', [TalkForStaffController::class, 'downloadTalksExcelForStaff'])
        ->name('talks.downloadTalksExcelForStaff');
    Route::patch('talks/status', [TalkForStaffController::class, 'updateTalkStatusForStaff'])
        ->name('talks.updateTalkStatusForStaff');
    Route::get('talks/{talk}', [TalkForStaffController::class, 'getTalkForStaff'])
        ->name('talks.getTalkForStaff');

    /**
     * 토크 댓글 관리
     **/
    Route::get('talk-comments', [TalkCommentForStaffController::class, 'getCommentsForStaff'])
        ->name('talk-comments.getCommentsForStaff');
    Route::patch('talk-comments/status', [TalkCommentForStaffController::class, 'updateTalkCommentStatusForStaff'])
        ->name('talk-comments.updateTalkCommentStatusForStaff');

    /**
     * 공지사항
     **/
    Route::get('notices', [NoticeForStaffController::class, 'getNoticesForStaff'])
        ->name('notices.getNoticesForStaff');
    Route::get('notices/{notice}', [NoticeForStaffController::class, 'getNoticeForStaff'])
        ->name('notices.getNoticeForStaff');
    Route::post('notices', [NoticeForStaffController::class, 'createNoticeForStaff'])
        ->name('notices.createNoticeForStaff');
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
    Route::post('faqs', [FaqForStaffController::class, 'createFaqForStaff'])
        ->name('faqs.createFaqForStaff');
    Route::post('faqs/editor-images', [FaqForStaffController::class, 'uploadEditorImageForStaff'])
        ->name('faqs.uploadEditorImageForStaff');
    Route::delete('faqs/editor-images', [FaqForStaffController::class, 'cleanupEditorImagesForStaff'])
        ->name('faqs.cleanupEditorImagesForStaff');
    Route::match(['post', 'put', 'patch'], 'faqs/{faq}', [FaqForStaffController::class, 'updateFaqForStaff'])
        ->name('faqs.updateFaqForStaff');
    Route::delete('faqs/{faq}', [FaqForStaffController::class, 'deleteFaqForStaff'])
        ->name('faqs.deleteFaqForStaff');
});
