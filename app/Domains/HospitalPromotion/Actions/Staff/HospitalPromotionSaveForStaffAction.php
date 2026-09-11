<?php

namespace App\Domains\HospitalPromotion\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Media\Actions\MediaAttachDeleteAction;
use App\Domains\Common\Media\Support\EditorHtmlSanitizer;
use App\Domains\Common\OperationHistory\Support\OperationHistoryChangeSetBuilder;
use App\Domains\HospitalPromotion\Actions\Common\HospitalPromotionEditorImagesAction;
use App\Domains\HospitalPromotion\Dto\HospitalPromotionAvailabilityDto;
use App\Domains\HospitalPromotion\Dto\HospitalPromotionDto;
use App\Domains\HospitalPromotion\Models\HospitalPromotion;
use App\Domains\HospitalPromotion\Queries\HospitalPromotionQuery;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

final class HospitalPromotionSaveForStaffAction
{
    public function __construct(
        private readonly HospitalPromotionQuery $query,
        private readonly MediaAttachDeleteAction $mediaAction,
        private readonly HospitalPromotionEditorImagesAction $editorImages,
        private readonly HospitalPromotionUpdateHistoryRecordAction $history,
    ) {}

    public function execute(array $payload, ?HospitalPromotion $promotion = null): array
    {
        $promotion === null ? Gate::authorize('create', HospitalPromotion::class) : Gate::authorize('update', $promotion);
        if (array_key_exists('content', $payload)) {
            $payload['content'] = EditorHtmlSanitizer::clean((string) $payload['content']);
            if ($payload['content'] === '') {
                throw ValidationException::withMessages(['content' => '내용을 입력해 주세요.']);
            }
        }
        $saved = DB::transaction(function () use ($payload, $promotion): HospitalPromotion {
            $oldSlot = $promotion ? ['side' => $promotion->side, 'slot' => $promotion->slot] : null;
            $newSlot = ['side' => $payload['side'] ?? $promotion?->side, 'slot' => (int) ($payload['slot'] ?? $promotion?->slot)];
            $this->query->lockSlots($oldSlot === null ? [$newSlot] : [$oldSlot, $newSlot]);
            $before = null;
            if ($promotion !== null) {
                $promotion = $this->query->lockPromotion((int) $promotion->id);
                // Route binding is only a lock hint. Never mutate an unprotected actual slot.
                if ($oldSlot !== ['side' => $promotion->side, 'slot' => $promotion->slot]
                    || ($payload['expected_updated_at'] ?? null) !== $promotion->updated_at?->toISOString()) {
                    throw ValidationException::withMessages(['expected_updated_at' => '다른 사용자가 수정했습니다. 최신 내용을 다시 조회한 뒤 저장해 주세요.']);
                }
                Gate::authorize('update', $promotion);
                $before = $this->history->capture($promotion);
            } else {
                $promotion = new HospitalPromotion;
                $promotion->created_by_staff_id = auth()->id();
            }

            $oldStatus = (string) $promotion->status;
            $promotion->fill(Arr::only($payload, ['title', 'content', 'status', ...HospitalPromotion::SCHEDULE_FIELDS]));
            if (array_key_exists('title', $payload)) {
                $promotion->title = trim((string) $payload['title']);
                if ($promotion->title === '') {
                    throw ValidationException::withMessages(['title' => '프로모션명을 입력해 주세요.']);
                }
            }
            if ($promotion->status !== $oldStatus || ($before !== null && $oldStatus === 'ACTIVE' && $promotion->isDirty(HospitalPromotion::SCHEDULE_FIELDS))) {
                Gate::authorize('updateStatus', $promotion);
            }
            $schedule = ['side' => $promotion->side, 'slot' => $promotion->slot, 'start_date' => $promotion->start_date->toDateString(), 'end_date' => $promotion->end_date->toDateString()];
            if (! $promotion->exists && $schedule['start_date'] < HospitalPromotion::today()) {
                throw ValidationException::withMessages(['start_date' => '게시 시작일은 오늘 이후로 선택해 주세요.']);
            }
            if ($schedule['start_date'] > $schedule['end_date']) {
                throw ValidationException::withMessages(['end_date' => '종료일은 시작일 이후여야 합니다.']);
            }
            // A locking read avoids a stale MySQL repeatable-read snapshot after waiting.
            $conflicts = $this->query->scheduleConflicts($schedule, $promotion->exists ? (int) $promotion->id : null)->lockForUpdate()->get();
            if ($conflicts->isNotEmpty()) {
                throw new CustomException(ErrorCode::INVALID_REQUEST, HospitalPromotionAvailabilityDto::MESSAGE, [
                    'errors' => ['slot' => [HospitalPromotionAvailabilityDto::MESSAGE]],
                    'availability' => HospitalPromotionAvailabilityDto::fromModels(
                        $conflicts, Gate::allows('viewAny', HospitalPromotion::class),
                    )->toArray(),
                ]);
            }
            if (! $promotion->exists) {
                $promotion->updated_by_staff_id = auth()->id();
                $promotion->save();
            }
            if (array_key_exists('banner', $payload)) {
                $this->mediaAction->deleteCollectionMedia($promotion, 'banner');
                $this->mediaAction->attachOne($promotion, $payload['banner'], 'banner', 'hospital-promotion', 'banner', true);
            }
            if (array_key_exists('content', $payload)) {
                $promotion->content = $this->editorImages->sync($promotion, (string) $payload['content']);
            }
            $promotion->unsetRelation('banner')->unsetRelation('editorImages');
            $changed = $before === null || OperationHistoryChangeSetBuilder::fromSnapshots($before, $this->history->capture($promotion)) !== [];
            if ($changed) {
                $promotion->updated_by_staff_id = auth()->id();
                // Microsecond, monotonic edit token also changes for file-only edits.
                $now = $promotion->freshTimestamp();
                $promotion->updated_at = $promotion->updated_at !== null && $now->lte($promotion->updated_at) ? $promotion->updated_at->addMicrosecond() : $now;
                $promotion->save();
                $this->history->record($promotion, $before);
            }

            return $promotion->fresh(['banner', 'creator:id,name']);
        });

        return HospitalPromotionDto::fromModel($saved, HospitalPromotion::today(), true)->toArray();
    }
}
