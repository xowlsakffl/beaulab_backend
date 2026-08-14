<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Controllers\HospitalWallet;

use App\Common\Http\Controllers\Controller;
use App\Common\Http\Responses\ApiResponse;
use App\Domains\Common\Sms\Models\SmsBatch;
use App\Domains\HospitalWallet\Actions\Staff\HospitalWalletDashboardGetForStaffAction;
use App\Domains\HospitalWallet\Actions\Staff\HospitalWalletListForStaffAction;
use App\Domains\HospitalWallet\Actions\Staff\HospitalWalletNoticeCreateForStaffAction;
use App\Domains\HospitalWallet\Actions\Staff\HospitalWalletNoticeGetForStaffAction;
use App\Domains\HospitalWallet\Actions\Staff\HospitalWalletNoticeListForStaffAction;
use App\Domains\HospitalWallet\Actions\Staff\HospitalWalletOperationListForStaffAction;
use App\Domains\HospitalWallet\Actions\Staff\HospitalWalletRefundCreateForStaffAction;
use App\Domains\HospitalWallet\Actions\Staff\HospitalWalletRefundDocumentDownloadForStaffAction;
use App\Domains\HospitalWallet\Actions\Staff\HospitalWalletRefundDocumentsGetForStaffAction;
use App\Domains\HospitalWallet\Actions\Staff\HospitalWalletRefundDocumentsUpdateForStaffAction;
use App\Domains\HospitalWallet\Actions\Staff\HospitalWalletRefundProcessForStaffAction;
use App\Domains\HospitalWallet\Actions\Staff\HospitalWalletServicePointGrantForStaffAction;
use App\Domains\HospitalWallet\Actions\Staff\HospitalWalletServicePointReclaimForStaffAction;
use App\Domains\HospitalWallet\Actions\Staff\HospitalWalletTopHospitalsForStaffAction;
use App\Domains\HospitalWallet\Models\HospitalWalletOperation;
use App\Modules\Staff\Http\Requests\HospitalWallet\HospitalWalletDashboardForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalWallet\HospitalWalletListForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalWallet\HospitalWalletNoticeCreateForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalWallet\HospitalWalletNoticeListForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalWallet\HospitalWalletOperationListForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalWallet\HospitalWalletRefundCreateForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalWallet\HospitalWalletRefundDocumentsUpdateForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalWallet\HospitalWalletRefundProcessForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalWallet\HospitalWalletServicePointUpdateForStaffRequest;
use App\Modules\Staff\Http\Requests\HospitalWallet\HospitalWalletTopHospitalsForStaffRequest;

final class HospitalWalletForStaffController extends Controller
{
    /** 충전금 정산 및 이용 현황을 조회합니다. */
    public function getHospitalWalletDashboardForStaff(
        HospitalWalletDashboardForStaffRequest $request,
        HospitalWalletDashboardGetForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($request->filters()));
    }

    /** 선택한 기간의 충전금 소진 상위 병의원을 조회합니다. */
    public function getHospitalWalletTopHospitalsForStaff(
        HospitalWalletTopHospitalsForStaffRequest $request,
        HospitalWalletTopHospitalsForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($request->filters()));
    }

    /**
     * 병의원 충전금 현재 잔액 목록을 조회합니다.
     */
    public function getHospitalWalletsForStaff(
        HospitalWalletListForStaffRequest $request,
        HospitalWalletListForStaffAction $action,
    ) {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    /**
     * 병의원 충전금 거래내역을 조회합니다.
     */
    public function getHospitalWalletOperationsForStaff(
        HospitalWalletOperationListForStaffRequest $request,
        HospitalWalletOperationListForStaffAction $action,
    ) {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    /** 충전금 환불을 신청하거나 최고관리자 권한으로 즉시 처리합니다. */
    public function createHospitalWalletRefundForStaff(
        HospitalWalletRefundCreateForStaffRequest $request,
        HospitalWalletRefundCreateForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($request->validated()));
    }

    /** 환불 신청을 완료 또는 반려 처리합니다. */
    public function processHospitalWalletRefundForStaff(
        HospitalWalletOperation $hospitalWalletOperation,
        HospitalWalletRefundProcessForStaffRequest $request,
        HospitalWalletRefundProcessForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($hospitalWalletOperation, $request->validated()));
    }

    /** 환불 첨부서류를 조회합니다. */
    public function getHospitalWalletRefundDocumentsForStaff(
        HospitalWalletOperation $hospitalWalletOperation,
        HospitalWalletRefundDocumentsGetForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($hospitalWalletOperation));
    }

    /** 환불 첨부서류 원본을 다운로드합니다. */
    public function downloadHospitalWalletRefundDocumentForStaff(
        HospitalWalletOperation $hospitalWalletOperation,
        string $document,
        HospitalWalletRefundDocumentDownloadForStaffAction $action,
    ) {
        return $action->execute($hospitalWalletOperation, $document);
    }

    /** 처리 대기 중인 환불 첨부서류를 교체하거나 삭제합니다. */
    public function updateHospitalWalletRefundDocumentsForStaff(
        HospitalWalletOperation $hospitalWalletOperation,
        HospitalWalletRefundDocumentsUpdateForStaffRequest $request,
        HospitalWalletRefundDocumentsUpdateForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($hospitalWalletOperation, $request->validated()));
    }

    /**
     * 충전금 안내 문자 발송 이력을 조회합니다.
     */
    public function getHospitalWalletNoticesForStaff(
        HospitalWalletNoticeListForStaffRequest $request,
        HospitalWalletNoticeListForStaffAction $action,
    ) {
        $result = $action->execute($request->filters());

        return ApiResponse::success($result['items'], $result['meta'] ?? null);
    }

    /**
     * 충전금 안내 문자 발송 이력 상세를 조회합니다.
     */
    public function getHospitalWalletNoticeForStaff(
        SmsBatch $hospitalWalletNoticeBatch,
        HospitalWalletNoticeGetForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($hospitalWalletNoticeBatch));
    }

    /**
     * 선택한 병의원에 충전금 안내 문자를 비동기로 발송합니다.
     */
    public function createHospitalWalletNoticeForStaff(
        HospitalWalletNoticeCreateForStaffRequest $request,
        HospitalWalletNoticeCreateForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($request->validated()));
    }

    /**
     * 선택한 병의원에 서비스 포인트를 지급합니다.
     */
    public function grantHospitalWalletServicePointsForStaff(
        HospitalWalletServicePointUpdateForStaffRequest $request,
        HospitalWalletServicePointGrantForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($request->validated()));
    }

    /**
     * 선택한 병의원에서 서비스 포인트를 회수합니다.
     */
    public function reclaimHospitalWalletServicePointsForStaff(
        HospitalWalletServicePointUpdateForStaffRequest $request,
        HospitalWalletServicePointReclaimForStaffAction $action,
    ) {
        return ApiResponse::success($action->execute($request->validated()));
    }
}
