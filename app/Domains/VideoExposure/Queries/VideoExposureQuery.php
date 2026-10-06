<?php

namespace App\Domains\VideoExposure\Queries;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use App\Domains\VideoExposure\Models\VideoExposure;
use Carbon\CarbonInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class VideoExposureQuery
{
    public function categories()
    {
        $query = Category::query()->whereNull('parent_id')->whereIn('group_code', ['SURGERY', 'TREATMENT']);
        CategoryUsage::constrainActiveCategoryExists($query, CategoryUsage::USAGE_HOSPITAL_VIDEO_CATEGORY);

        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function scope(string $placement, ?int $categoryId = null): array
    {
        if (! in_array($placement, VideoExposure::PLACEMENTS, true)) {
            throw ValidationException::withMessages(['placement' => '노출 위치를 선택해 주세요.']);
        }
        $group = $placement === 'SURGERY' ? 'SURGERY' : 'TREATMENT';
        $roots = $this->categories()->where('group_code', $group);
        $rootIds = $roots->pluck('id')->all();
        if ($categoryId !== null && ! in_array($categoryId, $rootIds, true)) {
            throw ValidationException::withMessages(['category_id' => '해당 동영상 영역의 활성 대분류를 선택해 주세요.']);
        }
        $ids = $categoryId === null ? $rootIds : [$categoryId];
        $categories = Category::query()->where('domain', Category::DOMAIN_HOSPITAL_MEDICAL)->where('group_code', $group)->where('status', 'ACTIVE')->get(['id', 'parent_id']);
        do {
            $before = count($ids);
            foreach ($categories as $category) {
                if (in_array($category->parent_id, $ids, true) && ! in_array($category->id, $ids, true)) {
                    $ids[] = $category->id;
                }
            }
        } while (count($ids) !== $before);

        return ['placement' => $placement, 'category_id' => $categoryId, 'key' => $placement.($categoryId ? ':'.$categoryId : ''), 'category_ids' => $ids];
    }

    public function videos(array $scope)
    {
        return HospitalVideo::query()->where('hospital_status', HospitalVideo::HOSPITAL_STATUS_PUBLIC)->where('admin_status', HospitalVideo::ADMIN_STATUS_NORMAL)
            ->whereHas('hospital', fn ($q) => $q->where('allow_status', 'APPROVED')->where('status', 'ACTIVE'))
            ->whereDoesntHave('contentReportState', fn ($q) => $q->whereIn('report_status', [ContentReportState::STATUS_AUTO_BLOCKED, ContentReportState::STATUS_ADMIN_HIDDEN]))
            ->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $scope['category_ids']));
    }

    public function entries(array $scope, CarbonInterface $at, array $filters = [])
    {
        $at = $at->copy()->startOfSecond();
        $eligible = $this->videos($scope)->select('hospital_videos.id');
        $query = VideoExposure::query()->where('position_key', $scope['key'])
            ->selectRaw("id, 'video' as source, concat('video:', id) as source_key, start_at, end_at")
            ->selectRaw("case when status = 'ACTIVE' and start_at <= ? and end_at >= ? and hospital_video_id in (".$eligible->toSql().') then 1 else 0 end as active', [$at, $at, ...$eligible->getBindings()]);
        if ($term = trim($filters['q'] ?? '')) {
            $query->where(function ($q) use ($term) {
                $q->whereHas('video', function ($video) use ($term) {
                    $video->where('title', 'like', '%'.$term.'%')->orWhereHas('hospital', fn ($hospital) => $hospital->where('name', 'like', '%'.$term.'%'));
                    if (ctype_digit($term)) {
                        $video->orWhere('hospital_videos.id', (int) $term);
                    }
                })->orWhereHas('creator', fn ($staff) => $staff->where('name', 'like', '%'.$term.'%'));
            });
        }

        return DB::query()->fromSub($query->toBase(), 'entries')
            ->when($filters['start_date'] ?? null, fn ($q, $date) => $q->where('end_at', '>=', $date.' 00:00:00'))
            ->when($filters['end_date'] ?? null, fn ($q, $date) => $q->where('start_at', '<=', $date.' 23:59:59'));
    }

    public function position(array $scope): ?object
    {
        return DB::table('video_exposure_positions')->where('key', $scope['key'])->first();
    }

    public function activeKeys(array $scope, CarbonInterface $at): array
    {
        $order = json_decode($this->position($scope)?->ordered_keys ?? '[]', true);
        $keys = $this->entries($scope, $at)->where('active', 1)->orderBy('start_at')->orderBy('id')->pluck('source_key')->all();

        return [...array_values(array_intersect($order, $keys)), ...array_values(array_diff($keys, $order))];
    }
}
