<?php

namespace App\Domains\Common\ContentReport\Queries\Staff;

use App\Domains\Common\ContentReport\Models\ContentReport;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class ReportedContentDetailForStaffQuery
{
    /**
     * @param  class-string<Model>  $targetClass
     */
    public function state(string $targetClass, int $targetId): ContentReportState
    {
        return ContentReportState::query()
            ->where('target_type', $targetClass)
            ->where('target_id', $targetId)
            ->where('report_status', '!=', ContentReportState::STATUS_NONE)
            ->with(['processedBy:id,name,email', 'warningProcessedBy:id,name,email'])
            ->firstOrFail();
    }

    /**
     * @param  class-string<Model>  $targetClass
     * @return Builder<ContentReport>
     */
    public function reportsQuery(string $targetClass, int $targetId): Builder
    {
        return ContentReport::query()
            ->where('target_type', $targetClass)
            ->where('target_id', $targetId)
            ->with('reporter:id,name,nickname,email')
            ->latest('id');
    }

    /**
     * @param  class-string<Model>  $targetClass
     */
    public function latestReport(string $targetClass, int $targetId): ?ContentReport
    {
        return ContentReport::query()
            ->where('target_type', $targetClass)
            ->where('target_id', $targetId)
            ->with('reporter:id,name,nickname,email')
            ->latest('id')
            ->first();
    }

    /**
     * @param  class-string<Model>  $targetClass
     * @return Collection<int, object>
     */
    public function reasonCounts(string $targetClass, int $targetId): Collection
    {
        return ContentReport::query()
            ->where('target_type', $targetClass)
            ->where('target_id', $targetId)
            ->select('reason')
            ->selectRaw('COUNT(*) AS count')
            ->groupBy('reason')
            ->orderByDesc('count')
            ->get();
    }

    /**
     * @return array{posts: array{total: int, reported: int}, comments: array{total: int, reported: int}}
     */
    public function authorStats(int $authorId): array
    {
        return [
            'posts' => [
                'total' => $this->countAuthoredTargets($authorId, $this->postTargetClasses()),
                'reported' => $this->countReportedAuthoredTargets($authorId, $this->postTargetClasses()),
            ],
            'comments' => [
                'total' => $this->countAuthoredTargets($authorId, $this->commentTargetClasses()),
                'reported' => $this->countReportedAuthoredTargets($authorId, $this->commentTargetClasses()),
            ],
        ];
    }

    /**
     * @param  array<int, class-string<Model>>  $targetClasses
     */
    private function countAuthoredTargets(int $authorId, array $targetClasses): int
    {
        return collect($targetClasses)
            ->sum(static fn (string $targetClass): int => $targetClass::query()
                ->where('author_id', $authorId)
                ->count());
    }

    /**
     * @param  array<int, class-string<Model>>  $targetClasses
     */
    private function countReportedAuthoredTargets(int $authorId, array $targetClasses): int
    {
        return ContentReportState::query()
            ->whereIn('target_type', $targetClasses)
            ->where('report_status', '!=', ContentReportState::STATUS_NONE)
            ->whereHasMorph('target', $targetClasses, static fn (Builder $query) => $query
                ->where('author_id', $authorId))
            ->count();
    }

    /**
     * @return array<int, class-string<Model>>
     */
    private function postTargetClasses(): array
    {
        return [
            Talk::class,
            HospitalReview::class,
            HospitalEvaluation::class,
        ];
    }

    /**
     * @return array<int, class-string<Model>>
     */
    private function commentTargetClasses(): array
    {
        return [
            TalkComment::class,
            HospitalReviewComment::class,
        ];
    }
}
