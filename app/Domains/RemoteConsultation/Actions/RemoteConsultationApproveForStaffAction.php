<?php

namespace App\Domains\RemoteConsultation\Actions;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\RemoteConsultation\Models\RemoteConsultation;
use App\Domains\RemoteConsultation\Queries\RemoteConsultationMatchingQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class RemoteConsultationApproveForStaffAction
{
    public function __construct(private readonly RemoteConsultationReadForStaffAction $read) {}

    public function execute(int $id): array
    {
        // Authorize before locking or revealing whether a record exists.
        Gate::authorize('approve', new RemoteConsultation);
        DB::transaction(function () use ($id) {
            $c = RemoteConsultation::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($c->approval_status === 'APPROVED') {
                return;
            }
            if (! in_array($c->approval_status, ['PENDING', 'REVIEWING'], true)) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '신청 또는 검수 상태에서만 승인할 수 있습니다.');
            }
            $before = $c->approval_status;
            $categoryIds = $c->categories()->pluck('categories.id')->map(fn ($id) => (int) $id)->all();
            $valid = Category::whereIn('id', $categoryIds);
            CategoryUsage::constrainActiveCategoryExists($valid, CategoryUsage::USAGE_HOSPITAL_DOCTOR_SUBJECT);
            if (! $categoryIds || $valid->count() !== count($categoryIds)) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '관심 진료과목이 없거나 현재 사용할 수 없습니다.');
            }
            if (! $c->media()->where('collection', 'member_images')->exists()) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '회원 이미지가 1장 이상 있어야 승인할 수 있습니다.');
            }
            $hospitalIds = $c->quotes()->pluck('hospital_id')->all();
            $eligible = app(RemoteConsultationMatchingQuery::class)->eligible($categoryIds)
                ->whereIn('id', $hospitalIds)->orderBy('id')->lockForUpdate()->get();
            if (count($hospitalIds) > 6 || $eligible->count() !== count($hospitalIds)) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '배정 병의원 중 현재 배정 조건을 충족하지 않는 병의원이 있습니다. 견적 목록을 확인해 주세요.');
            }
            $c->update(['approval_status' => 'APPROVED', 'approved_by_staff_id' => auth()->id(), 'approved_at' => now()]);
            app(OperationHistoryCreateAction::class)->execute(target: $c, action: OperationHistory::ACTION_STATE_UPDATED, actor: auth()->user(), metadata: ['source' => 'staff.remote_consultation.approve'], changes: OperationHistoryChangeSetBuilder::single(key: 'approval_status', label: '승인상태', before: $before, after: 'APPROVED', beforeDisplay: $before === 'PENDING' ? '신청' : '검수', afterDisplay: '승인'));
        }, 3);

        return $this->read->show($id);
    }
}
