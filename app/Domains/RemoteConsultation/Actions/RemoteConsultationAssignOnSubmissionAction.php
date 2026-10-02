<?php

namespace App\Domains\RemoteConsultation\Actions;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\RemoteConsultation\Models\RemoteConsultation;
use App\Domains\RemoteConsultation\Queries\RemoteConsultationMatchingQuery;
use Illuminate\Support\Facades\DB;

/** Internal submission step: call inside the transaction that creates the application and categories. */
final class RemoteConsultationAssignOnSubmissionAction
{
    public function __construct(private readonly RemoteConsultationMatchingQuery $matching) {}

    public function execute(int $id): void
    {
        DB::transaction(function () use ($id) {
            $c = RemoteConsultation::whereKey($id)->lockForUpdate()->firstOrFail();
            if ($c->quotes()->exists()) {
                return;
            }
            if ($c->approval_status !== 'PENDING') {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '신청 상태에서만 최초 배정할 수 있습니다.');
            }
            $categoryIds = $c->categories()->pluck('categories.id')->map(fn ($id) => (int) $id)->all();
            $valid = Category::whereIn('id', $categoryIds);
            CategoryUsage::constrainActiveCategoryExists($valid, CategoryUsage::USAGE_HOSPITAL_DOCTOR_SUBJECT);
            if (! $categoryIds || $valid->count() !== count($categoryIds)) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '관심 진료과목이 없거나 현재 사용할 수 없습니다.');
            }
            $selected = array_values(array_unique(array_map('intval', $c->requested_hospital_ids ?? [])));
            if (count($selected) > 6) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '선택 병의원은 최대 6개입니다.');
            }
            $random = $this->matching->randomIds($categoryIds, $selected, 6 - count($selected));
            $hospitals = $this->matching->eligible($categoryIds)->whereIn('id', [...$selected, ...$random])->orderBy('id')->lockForUpdate()->get();
            if (array_diff($selected, $hospitals->modelKeys())) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '선택 병의원 중 배정 조건을 충족하지 않는 병의원이 있습니다.');
            }
            foreach ($hospitals as $hospital) {
                $c->quotes()->create([
                    'hospital_id' => $hospital->id,
                    'hospital_name' => $hospital->name,
                    'selection_type' => in_array($hospital->id, $selected, true) ? 'SELECTED' : 'RANDOM',
                    'matching_version' => 'random-v1',
                    'matched_categories' => $hospital->categories->map(fn ($v) => ['id' => $v->id, 'name' => $v->name])->values()->all(),
                    'status' => 'WAITING',
                    'created_at' => $c->created_at,
                    'updated_at' => $c->created_at,
                ]);
            }
        }, 3);
    }
}
