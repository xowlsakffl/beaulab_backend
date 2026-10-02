<?php

namespace App\Domains\RemoteConsultation\Actions;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Common\Support\PaginatedResponse;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\Common\OperationHistory\Actions\OperationHistoryCreateAction;
use App\Domains\Common\OperationHistory\Models\OperationHistory;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\RemoteConsultation\Models\RemoteConsultation;
use App\Domains\RemoteConsultation\Queries\RemoteConsultationMatchingQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

final class RemoteConsultationAssignmentForStaffAction
{
    public function __construct(private readonly RemoteConsultationMatchingQuery $matching, private readonly RemoteConsultationReadForStaffAction $read) {}

    private function assertEditable(RemoteConsultation $c): void
    {
        if (! in_array($c->approval_status, ['PENDING', 'REVIEWING'], true)) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '신청 또는 검수 상태에서만 배정을 변경할 수 있습니다.');
        }
    }

    private function categoryIds(RemoteConsultation $c): array
    {
        $query = Category::whereIn('id', $c->categories()->pluck('categories.id'));
        CategoryUsage::constrainActiveCategoryExists($query, CategoryUsage::USAGE_HOSPITAL_DOCTOR_SUBJECT);

        return $query->pluck('id')->all();
    }

    public function candidates(int $id, array $filters): array
    {
        Gate::authorize('approve', new RemoteConsultation);
        $c = RemoteConsultation::findOrFail($id);
        $this->assertEditable($c);
        $query = $this->matching->eligible($this->categoryIds($c))
            ->with('businessRegistration')
            ->whereNotIn('id', $c->quotes()->pluck('hospital_id'));
        $term = trim($filters['q'] ?? '');
        if ($term !== '') {
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%'.addcslashes($term, '\\%_').'%');
                if (ctype_digit($term) && strlen($term) <= 15) {
                    $q->orWhere('id', (int) $term);
                }
                $businessNumber = preg_match('/^[0-9\s-]+$/', $term) ? preg_replace('/\D/', '', $term) : '';
                if ($businessNumber !== '') {
                    $q->orWhereHas('businessRegistration', fn ($registration) => $registration
                        ->whereRaw("REPLACE(REPLACE(business_number, '-', ''), ' ', '') LIKE ?", ['%'.$businessNumber.'%']));
                }
            });
        }

        return PaginatedResponse::fromPaginator($query->orderBy('id')->paginate(15), fn ($h) => [
            'id' => $h->id, 'name' => $h->name,
            'business_number' => $h->businessRegistration?->business_number,
            'categories' => $h->categories->map(fn ($c) => ['id' => $c->id, 'name' => $c->name])->values()->all(),
        ]);
    }

    public function add(int $id, array $hospitalIds): array
    {
        Gate::authorize('approve', new RemoteConsultation);
        DB::transaction(function () use ($id, $hospitalIds) {
            $c = RemoteConsultation::whereKey($id)->lockForUpdate()->firstOrFail();
            $this->assertEditable($c);
            $existing = $c->quotes()->pluck('hospital_id')->all();
            $newIds = array_values(array_diff(array_unique(array_map('intval', $hospitalIds)), $existing));
            if (count($existing) + count($newIds) > 6) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '최대 6곳까지 배정할 수 있습니다. 기존 견적을 삭제한 뒤 추가해 주세요.');
            }
            $hospitals = $this->matching->eligible($this->categoryIds($c))->whereIn('id', $newIds)->orderBy('id')->lockForUpdate()->get();
            if ($hospitals->count() !== count($newIds)) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '관심 진료과목과 일치하는 활성 병의원만 배정할 수 있습니다.');
            }
            foreach ($hospitals as $hospital) {
                $quote = $c->quotes()->create([
                    'hospital_id' => $hospital->id, 'hospital_name' => $hospital->name,
                    'selection_type' => 'SELECTED', 'matching_version' => 'staff-selected-v1',
                    'matched_categories' => $hospital->categories->map(fn ($v) => ['id' => $v->id, 'name' => $v->name])->values()->all(),
                    'status' => 'WAITING',
                ]);
                $this->history($c, $quote->id, null, $hospital->name);
            }
        }, 3);

        return $this->read->show($id);
    }

    public function remove(int $id, int $quoteId): array
    {
        Gate::authorize('approve', new RemoteConsultation);
        DB::transaction(function () use ($id, $quoteId) {
            $c = RemoteConsultation::whereKey($id)->lockForUpdate()->firstOrFail();
            $this->assertEditable($c);
            $quote = $c->quotes()->whereKey($quoteId)->firstOrFail();
            if ($quote->status !== 'WAITING' || $quote->answered_at !== null) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, '상담이 완료된 견적은 삭제할 수 없습니다.');
            }
            $this->history($c, $quote->id, $quote->hospital_name, null);
            $quote->delete();
        }, 3);

        return $this->read->show($id);
    }

    private function history(RemoteConsultation $c, int $quoteId, ?string $before, ?string $after): void
    {
        app(OperationHistoryCreateAction::class)->execute(
            target: $c, action: OperationHistory::ACTION_STATE_UPDATED, actor: auth()->user(),
            metadata: ['source' => 'staff.remote_consultation.assignment', 'quote_id' => $quoteId],
            changes: OperationHistoryChangeSetBuilder::single(key: 'assigned_hospital', label: '배정 병의원', before: $before, after: $after, beforeDisplay: $before ?? '-', afterDisplay: $after ?? '-'),
        );
    }
}
