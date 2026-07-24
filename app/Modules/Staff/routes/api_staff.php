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
use App\Modules\Staff\Http\Controllers\ContentReport\ContentReportForStaffController;
use App\Modules\Staff\Http\Controllers\Dashboard\DashboardForStaffController;
use App\Modules\Staff\Http\Controllers\Faq\FaqForStaffController;
use App\Modules\Staff\Http\Controllers\Hashtag\HashtagForStaffController;
use App\Modules\Staff\Http\Controllers\Hospital\HospitalForStaffController;
use App\Modules\Staff\Http\Controllers\HospitalDoctor\HospitalDoctorForStaffController;
use App\Modules\Staff\Http\Controllers\HospitalEntry\HospitalEntryForStaffController;
use App\Modules\Staff\Http\Controllers\HospitalEvaluation\HospitalEvaluationForStaffController;
use App\Modules\Staff\Http\Controllers\HospitalEvent\HospitalEventDBForStaffController;
use App\Modules\Staff\Http\Controllers\HospitalEvent\HospitalEventForStaffController;
use App\Modules\Staff\Http\Controllers\HospitalEvent\HospitalEventRealModelDBForStaffController;
use App\Modules\Staff\Http\Controllers\HospitalEventAd\HospitalEventAdForStaffController;
use App\Modules\Staff\Http\Controllers\HospitalFeature\HospitalFeatureForStaffController;
use App\Modules\Staff\Http\Controllers\HospitalReview\HospitalReviewForStaffController;
use App\Modules\Staff\Http\Controllers\HospitalReviewComment\HospitalReviewCommentForStaffController;
use App\Modules\Staff\Http\Controllers\HospitalVideo\HospitalVideoForStaffController;
use App\Modules\Staff\Http\Controllers\Notice\NoticeForStaffController;
use App\Modules\Staff\Http\Controllers\Talk\TalkForStaffController;
use App\Modules\Staff\Http\Controllers\TalkComment\TalkCommentForStaffController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('login', [AuthForStaffController::class, 'login'])->name('login')->middleware('throttle:auth-login');
    Route::post('password-reset-link', [AuthForStaffController::class, 'sendPasswordResetLink'])
        ->name('password-reset-link')
        ->middleware('throttle:password-reset-link');
    Route::post('password-reset/verify', [AuthForStaffController::class, 'verifyPasswordResetToken'])
        ->name('password-reset.verify')
        ->middleware('throttle:password-reset-verify');
    Route::post('password-reset', [AuthForStaffController::class, 'resetPassword'])
        ->name('password-reset')
        ->middleware('throttle:password-reset-submit');
});

Route::middleware(['auth:sanctum', 'abilities:actor:staff', 'permission:common.access'])->group(function () {

    // 인증
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthForStaffController::class, 'logout'])->name('logout');
    });

    Route::get('/profile', [AuthForStaffController::class, 'getMyProfile'])->name('profile');
    Route::match(['put', 'patch'], '/profile', [AuthForStaffController::class, 'updateMyProfile'])->name('profile.update');
    Route::match(['put', 'patch'], '/password', [AuthForStaffController::class, 'updateMyPassword'])->name('password.update')
        ->middleware('throttle:password-update');

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
     * 신고 게시물 관리
     **/
    Route::get('reported-contents/talks/summary', [ContentReportForStaffController::class, 'getReportedTalksSummaryForStaff'])
        ->name('reported-contents.getReportedTalksSummaryForStaff');
    Route::get('reported-contents/talks', [ContentReportForStaffController::class, 'getReportedTalksForStaff'])
        ->name('reported-contents.getReportedTalksForStaff');
    Route::get('reported-contents/talk-comments/summary', [ContentReportForStaffController::class, 'getReportedTalkCommentsSummaryForStaff'])
        ->name('reported-contents.getReportedTalkCommentsSummaryForStaff');
    Route::get('reported-contents/talk-comments', [ContentReportForStaffController::class, 'getReportedTalkCommentsForStaff'])
        ->name('reported-contents.getReportedTalkCommentsForStaff');
    Route::get('reported-contents/hospital-reviews/surgery/summary', [ContentReportForStaffController::class, 'getReportedSurgeryHospitalReviewsSummaryForStaff'])
        ->defaults('category_domain', HospitalReview::CATEGORY_DOMAIN_SURGERY)
        ->name('reported-contents.getReportedSurgeryHospitalReviewsSummaryForStaff');
    Route::get('reported-contents/hospital-reviews/surgery', [ContentReportForStaffController::class, 'getReportedSurgeryHospitalReviewsForStaff'])
        ->defaults('category_domain', HospitalReview::CATEGORY_DOMAIN_SURGERY)
        ->name('reported-contents.getReportedSurgeryHospitalReviewsForStaff');
    Route::get('reported-contents/hospital-reviews/treatment/summary', [ContentReportForStaffController::class, 'getReportedTreatmentHospitalReviewsSummaryForStaff'])
        ->defaults('category_domain', HospitalReview::CATEGORY_DOMAIN_TREATMENT)
        ->name('reported-contents.getReportedTreatmentHospitalReviewsSummaryForStaff');
    Route::get('reported-contents/hospital-reviews/treatment', [ContentReportForStaffController::class, 'getReportedTreatmentHospitalReviewsForStaff'])
        ->defaults('category_domain', HospitalReview::CATEGORY_DOMAIN_TREATMENT)
        ->name('reported-contents.getReportedTreatmentHospitalReviewsForStaff');
    Route::get('reported-contents/hospital-review-comments/surgery/summary', [ContentReportForStaffController::class, 'getReportedSurgeryHospitalReviewCommentsSummaryForStaff'])
        ->defaults('category_domain', HospitalReview::CATEGORY_DOMAIN_SURGERY)
        ->name('reported-contents.getReportedSurgeryHospitalReviewCommentsSummaryForStaff');
    Route::get('reported-contents/hospital-review-comments/surgery', [ContentReportForStaffController::class, 'getReportedSurgeryHospitalReviewCommentsForStaff'])
        ->defaults('category_domain', HospitalReview::CATEGORY_DOMAIN_SURGERY)
        ->name('reported-contents.getReportedSurgeryHospitalReviewCommentsForStaff');
    Route::get('reported-contents/hospital-review-comments/treatment/summary', [ContentReportForStaffController::class, 'getReportedTreatmentHospitalReviewCommentsSummaryForStaff'])
        ->defaults('category_domain', HospitalReview::CATEGORY_DOMAIN_TREATMENT)
        ->name('reported-contents.getReportedTreatmentHospitalReviewCommentsSummaryForStaff');
    Route::get('reported-contents/hospital-review-comments/treatment', [ContentReportForStaffController::class, 'getReportedTreatmentHospitalReviewCommentsForStaff'])
        ->defaults('category_domain', HospitalReview::CATEGORY_DOMAIN_TREATMENT)
        ->name('reported-contents.getReportedTreatmentHospitalReviewCommentsForStaff');
    Route::get('reported-contents/hospital-evaluations/summary', [ContentReportForStaffController::class, 'getReportedHospitalEvaluationsSummaryForStaff'])
        ->name('reported-contents.getReportedHospitalEvaluationsSummaryForStaff');
    Route::get('reported-contents/hospital-evaluations', [ContentReportForStaffController::class, 'getReportedHospitalEvaluationsForStaff'])
        ->name('reported-contents.getReportedHospitalEvaluationsForStaff');
    Route::get('reported-contents/chats/summary', [ContentReportForStaffController::class, 'getReportedChatMessagesSummaryForStaff'])
        ->name('reported-contents.getReportedChatMessagesSummaryForStaff');
    Route::get('reported-contents/chats', [ContentReportForStaffController::class, 'getReportedChatMessagesForStaff'])
        ->name('reported-contents.getReportedChatMessagesForStaff');
    Route::get('reported-contents/videos/summary', [ContentReportForStaffController::class, 'getReportedHospitalVideosSummaryForStaff'])
        ->name('reported-contents.getReportedHospitalVideosSummaryForStaff');
    Route::get('reported-contents/videos', [ContentReportForStaffController::class, 'getReportedHospitalVideosForStaff'])
        ->name('reported-contents.getReportedHospitalVideosForStaff');
    Route::get('reported-contents/detail/{targetType}/{targetId}', [ContentReportForStaffController::class, 'getReportedContentDetailForStaff'])
        ->whereNumber('targetId')
        ->name('reported-contents.getReportedContentDetailForStaff');
    Route::get('reported-contents/{targetType}/{targetId}/reports', [ContentReportForStaffController::class, 'getReportedContentReportsForStaff'])
        ->whereNumber('targetId')
        ->name('reported-contents.getReportedContentReportsForStaff');
    Route::patch('reported-contents/status', [ContentReportForStaffController::class, 'updateReportedContentStatusForStaff'])
        ->name('reported-contents.updateReportedContentStatusForStaff');
    Route::patch('reported-contents/process', [ContentReportForStaffController::class, 'processReportedContentForStaff'])
        ->name('reported-contents.processReportedContentForStaff');
    Route::patch('reported-contents/warning-status', [ContentReportForStaffController::class, 'updateReportedContentWarningStatusForStaff'])
        ->name('reported-contents.updateReportedContentWarningStatusForStaff');

    /**
     * 병원 관리
     **/
    Route::get('hospital-entries', [HospitalEntryForStaffController::class, 'getHospitalEntriesForStaff'])
        ->name('hospital-entries.getHospitalEntriesForStaff');
    Route::get('hospital-entries/summary', [HospitalEntryForStaffController::class, 'getHospitalEntrySummaryForStaff'])
        ->name('hospital-entries.getHospitalEntrySummaryForStaff');
    Route::patch('hospital-entries/allow-status', [HospitalEntryForStaffController::class, 'updateHospitalEntryAllowStatusForStaff'])
        ->name('hospital-entries.updateHospitalEntryAllowStatusForStaff');
    Route::get('hospital-entries/{hospitalEntry}', [HospitalEntryForStaffController::class, 'getHospitalEntryForStaff'])
        ->name('hospital-entries.getHospitalEntryForStaff');
    Route::match(['post', 'put', 'patch'], 'hospital-entries/{hospitalEntry}', [HospitalEntryForStaffController::class, 'updateHospitalEntryForStaff'])
        ->name('hospital-entries.updateHospitalEntryForStaff');
    Route::get('hospital-features', [HospitalFeatureForStaffController::class, 'getHospitalFeaturesForStaff'])
        ->name('hospital-features.getHospitalFeaturesForStaff');
    Route::get('hospitals', [HospitalForStaffController::class, 'getHospitalsForStaff'])
        ->name('hospitals.getHospitalsForStaff');
    Route::get('hospitals/summary', [HospitalForStaffController::class, 'getHospitalSummaryForStaff'])
        ->name('hospitals.getHospitalSummaryForStaff');
    Route::post('hospitals/check-name', [HospitalForStaffController::class, 'checkHospitalNameDuplicateForStaff'])
        ->name('hospitals.checkHospitalNameDuplicateForStaff');
    Route::post('hospitals/check-business-number', [HospitalForStaffController::class, 'checkHospitalBusinessNumberDuplicateForStaff'])
        ->name('hospitals.checkHospitalBusinessNumberDuplicateForStaff');
    Route::patch('hospitals/allow-status', [HospitalForStaffController::class, 'updateHospitalAllowStatusForStaff'])
        ->name('hospitals.updateHospitalAllowStatusForStaff');
    Route::get('hospitals/{hospital}/operation-histories', [HospitalForStaffController::class, 'getHospitalOperationHistoriesForStaff'])
        ->name('hospitals.getHospitalOperationHistoriesForStaff');
    Route::get('hospitals/{hospital}', [HospitalForStaffController::class, 'getHospitalForStaff'])
        ->name('hospitals.getHospitalForStaff');
    Route::post('hospitals', [HospitalForStaffController::class, 'createHospitalForStaff'])
        ->name('hospitals.createHospitalForStaff');
    Route::patch('hospitals/{hospital}/status', [HospitalForStaffController::class, 'updateHospitalStatusForStaff'])
        ->name('hospitals.updateHospitalStatusForStaff');
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
    Route::get('users/summary', [AccountUserForStaffController::class, 'getAccountUserSummaryForStaff'])
        ->name('users.getAccountUserSummaryForStaff');
    Route::get('users/{user}', [AccountUserForStaffController::class, 'getAccountUserForStaff'])
        ->withTrashed()
        ->name('users.getAccountUserForStaff');
    Route::patch('users/{user}/status', [AccountUserForStaffController::class, 'updateAccountUserStatusForStaff'])
        ->name('users.updateAccountUserStatusForStaff');

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
     * 병의원 이벤트 관리
     **/
    Route::get('hospital-event-dbs', [HospitalEventDBForStaffController::class, 'getHospitalEventDBsForStaff'])
        ->name('hospital-event-dbs.getHospitalEventDBsForStaff');
    Route::patch('hospital-event-dbs/status', [HospitalEventDBForStaffController::class, 'updateHospitalEventDBStatusForStaff'])
        ->name('hospital-event-dbs.updateHospitalEventDBStatusForStaff');
    Route::patch('hospital-event-dbs/allow-status', [HospitalEventDBForStaffController::class, 'updateHospitalEventDBAllowStatusForStaff'])
        ->name('hospital-event-dbs.updateHospitalEventDBAllowStatusForStaff');
    Route::get('hospital-event-dbs/{hospitalEventDB}/operation-histories', [HospitalEventDBForStaffController::class, 'getHospitalEventDBOperationHistoriesForStaff'])
        ->name('hospital-event-dbs.getHospitalEventDBOperationHistoriesForStaff');
    Route::get('hospital-event-real-model-dbs', [HospitalEventRealModelDBForStaffController::class, 'getHospitalEventRealModelDBsForStaff'])
        ->name('hospital-event-real-model-dbs.getHospitalEventRealModelDBsForStaff');
    Route::patch('hospital-event-real-model-dbs/status', [HospitalEventRealModelDBForStaffController::class, 'updateHospitalEventRealModelDBStatusForStaff'])
        ->name('hospital-event-real-model-dbs.updateHospitalEventRealModelDBStatusForStaff');
    Route::get('hospital-event-real-model-dbs/{hospitalEventRealModelDB}/operation-histories', [HospitalEventRealModelDBForStaffController::class, 'getHospitalEventRealModelDBOperationHistoriesForStaff'])
        ->name('hospital-event-real-model-dbs.getHospitalEventRealModelDBOperationHistoriesForStaff');
    Route::get('hospital-event-real-model-dbs/{hospitalEventRealModelDB}', [HospitalEventRealModelDBForStaffController::class, 'getHospitalEventRealModelDBForStaff'])
        ->name('hospital-event-real-model-dbs.getHospitalEventRealModelDBForStaff');
    Route::get('hospital-event-ads', [HospitalEventAdForStaffController::class, 'getHospitalEventAdsForStaff'])
        ->name('hospital-event-ads.getHospitalEventAdsForStaff');
    Route::get('hospital-event-ads/placements', [HospitalEventAdForStaffController::class, 'getHospitalEventAdPlacementOptionsForStaff'])
        ->name('hospital-event-ads.getHospitalEventAdPlacementOptionsForStaff');
    Route::get('hospital-event-ads/availability', [HospitalEventAdForStaffController::class, 'getHospitalEventAdAvailabilityForStaff'])
        ->name('hospital-event-ads.getHospitalEventAdAvailabilityForStaff');
    Route::get('hospital-event-ads/calendar', [HospitalEventAdForStaffController::class, 'getHospitalEventAdCalendarForStaff'])
        ->name('hospital-event-ads.getHospitalEventAdCalendarForStaff');
    Route::patch('hospital-event-ads/allow-status', [HospitalEventAdForStaffController::class, 'updateHospitalEventAdAllowStatusForStaff'])
        ->name('hospital-event-ads.updateHospitalEventAdAllowStatusForStaff');
    Route::get('hospital-event-ads/{hospitalEventAd}/operation-histories', [HospitalEventAdForStaffController::class, 'getHospitalEventAdOperationHistoriesForStaff'])
        ->name('hospital-event-ads.getHospitalEventAdOperationHistoriesForStaff');
    Route::get('hospital-event-ads/{hospitalEventAd}', [HospitalEventAdForStaffController::class, 'getHospitalEventAdForStaff'])
        ->name('hospital-event-ads.getHospitalEventAdForStaff');
    Route::post('hospital-event-ads', [HospitalEventAdForStaffController::class, 'createHospitalEventAdForStaff'])
        ->name('hospital-event-ads.createHospitalEventAdForStaff');
    Route::match(['post', 'put', 'patch'], 'hospital-event-ads/{hospitalEventAd}', [HospitalEventAdForStaffController::class, 'updateHospitalEventAdForStaff'])
        ->name('hospital-event-ads.updateHospitalEventAdForStaff');
    Route::get('hospital-events', [HospitalEventForStaffController::class, 'getHospitalEventsForStaff'])
        ->name('hospital-events.getHospitalEventsForStaff');
    Route::get('hospital-events/summary', [HospitalEventForStaffController::class, 'getHospitalEventSummaryForStaff'])
        ->name('hospital-events.getHospitalEventSummaryForStaff');
    Route::get('hospital-events/category-filter-options', [HospitalEventForStaffController::class, 'getHospitalEventCategoryFilterOptionsForStaff'])
        ->name('hospital-events.getHospitalEventCategoryFilterOptionsForStaff');
    Route::patch('hospital-events/admin-status', [HospitalEventForStaffController::class, 'updateHospitalEventAdminStatusForStaff'])
        ->name('hospital-events.updateHospitalEventAdminStatusForStaff');
    Route::patch('hospital-events/allow-status', [HospitalEventForStaffController::class, 'updateHospitalEventAllowStatusForStaff'])
        ->name('hospital-events.updateHospitalEventAllowStatusForStaff');
    Route::get('hospital-events/{hospitalEvent}/operation-histories', [HospitalEventForStaffController::class, 'getHospitalEventOperationHistoriesForStaff'])
        ->name('hospital-events.getHospitalEventOperationHistoriesForStaff');
    Route::get('hospital-events/{hospitalEvent}', [HospitalEventForStaffController::class, 'getHospitalEventForStaff'])
        ->name('hospital-events.getHospitalEventForStaff');
    Route::post('hospital-events', [HospitalEventForStaffController::class, 'createHospitalEventForStaff'])
        ->name('hospital-events.createHospitalEventForStaff');
    Route::post('hospital-events/{hospitalEvent}/duplicate', [HospitalEventForStaffController::class, 'duplicateHospitalEventForStaff'])
        ->name('hospital-events.duplicateHospitalEventForStaff');
    Route::patch('hospital-events/{hospitalEvent}/period', [HospitalEventForStaffController::class, 'updateHospitalEventPeriodForStaff'])
        ->name('hospital-events.updateHospitalEventPeriodForStaff');
    Route::match(['post', 'put', 'patch'], 'hospital-events/{hospitalEvent}', [HospitalEventForStaffController::class, 'updateHospitalEventForStaff'])
        ->name('hospital-events.updateHospitalEventForStaff');
    Route::delete('hospital-events/{hospitalEvent}', [HospitalEventForStaffController::class, 'deleteHospitalEventForStaff'])
        ->name('hospital-events.deleteHospitalEventForStaff');

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
    Route::get('videos/summary', [HospitalVideoForStaffController::class, 'getVideoSummaryForStaff'])
        ->name('videos.getVideoSummaryForStaff');
    Route::patch('videos/admin-status', [HospitalVideoForStaffController::class, 'updateVideoAdminStatusForStaff'])
        ->name('videos.updateVideoAdminStatusForStaff');
    Route::get('videos/{video}/operation-histories', [HospitalVideoForStaffController::class, 'getVideoOperationHistoriesForStaff'])
        ->name('videos.getVideoOperationHistoriesForStaff');
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
    Route::get('hospital-reviews', [HospitalReviewForStaffController::class, 'getHospitalReviewsForStaff'])
        ->name('hospital-reviews.getHospitalReviewsForStaff');
    Route::patch('hospital-reviews/status', [HospitalReviewForStaffController::class, 'updateHospitalReviewStatusForStaff'])
        ->name('hospital-reviews.updateHospitalReviewStatusForStaff');
    Route::get('hospital-reviews/{hospitalReview}/comments', [HospitalReviewForStaffController::class, 'getHospitalReviewCommentsForStaff'])
        ->name('hospital-reviews.getHospitalReviewCommentsForStaff');
    Route::get('hospital-reviews/{hospitalReview}/operation-histories', [HospitalReviewForStaffController::class, 'getHospitalReviewOperationHistoriesForStaff'])
        ->name('hospital-reviews.getHospitalReviewOperationHistoriesForStaff');
    Route::get('hospital-reviews/{hospitalReview}', [HospitalReviewForStaffController::class, 'getHospitalReviewForStaff'])
        ->name('hospital-reviews.getHospitalReviewForStaff');
    Route::get('hospital-review-comments', [HospitalReviewCommentForStaffController::class, 'getCommentsForStaff'])
        ->name('hospital-review-comments.getCommentsForStaff');
    Route::patch('hospital-review-comments/status', [HospitalReviewCommentForStaffController::class, 'updateHospitalReviewCommentStatusForStaff'])
        ->name('hospital-review-comments.updateHospitalReviewCommentStatusForStaff');
    Route::get('hospital-review-comments/{comment}/operation-histories', [HospitalReviewCommentForStaffController::class, 'getHospitalReviewCommentOperationHistoriesForStaff'])
        ->name('hospital-review-comments.getHospitalReviewCommentOperationHistoriesForStaff');

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
    Route::get('hospital-evaluations/{hospitalEvaluation}/operation-histories', [HospitalEvaluationForStaffController::class, 'getHospitalEvaluationOperationHistoriesForStaff'])
        ->name('hospital-evaluations.getHospitalEvaluationOperationHistoriesForStaff');
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
    Route::get('talks/{talk}/comments', [TalkForStaffController::class, 'getTalkCommentsForStaff'])
        ->name('talks.getTalkCommentsForStaff');
    Route::get('talks/{talk}/operation-histories', [TalkForStaffController::class, 'getTalkOperationHistoriesForStaff'])
        ->name('talks.getTalkOperationHistoriesForStaff');
    Route::get('talks/{talk}', [TalkForStaffController::class, 'getTalkForStaff'])
        ->name('talks.getTalkForStaff');

    /**
     * 토크 댓글 관리
     **/
    Route::get('talk-comments', [TalkCommentForStaffController::class, 'getCommentsForStaff'])
        ->name('talk-comments.getCommentsForStaff');
    Route::patch('talk-comments/status', [TalkCommentForStaffController::class, 'updateTalkCommentStatusForStaff'])
        ->name('talk-comments.updateTalkCommentStatusForStaff');
    Route::get('talk-comments/{comment}/operation-histories', [TalkCommentForStaffController::class, 'getTalkCommentOperationHistoriesForStaff'])
        ->name('talk-comments.getTalkCommentOperationHistoriesForStaff');

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
