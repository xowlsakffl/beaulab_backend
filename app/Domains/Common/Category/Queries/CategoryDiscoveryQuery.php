<?php

namespace App\Domains\Common\Category\Queries;

use App\Domains\Beauty\Models\Beauty;
use App\Domains\BeautyExpert\Models\BeautyExpert;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryTopic;
use App\Domains\Common\Category\Support\CategoryHierarchy;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use App\Domains\HospitalEvent\Models\HospitalEvent;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalVideo\Models\HospitalVideo;
use App\Domains\Talk\Models\Talk;
use Illuminate\Database\Eloquent\Builder;

final class CategoryDiscoveryQuery
{
    public const DOMAINS = [
        'hospitals' => [Hospital::class, Category::DOMAIN_HOSPITAL_SUBJECT, 'name'],
        'doctors' => [HospitalDoctor::class, Category::DOMAIN_DOCTOR_SUBJECT, 'name'],
        'events' => [HospitalEvent::class, Category::DOMAIN_HOSPITAL_MEDICAL, 'name'],
        'reviews' => [HospitalReview::class, Category::DOMAIN_HOSPITAL_REVIEW, 'title'],
        'videos' => [HospitalVideo::class, Category::DOMAIN_HOSPITAL_VIDEO, 'title'],
        'talks' => [Talk::class, Category::DOMAIN_TALK, 'title'],
        'evaluations' => [HospitalEvaluation::class, Category::DOMAIN_HOSPITAL_EVALUATION, 'content'],
        'beauties' => [Beauty::class, Category::DOMAIN_BEAUTY, 'name'],
        'beauty-experts' => [BeautyExpert::class, Category::DOMAIN_BEAUTY_EXPERT, 'name'],
    ];

    public function categoryIds(CategoryTopic $topic, ?string $domain = null): array
    {
        $linked = $topic->categories()->when($domain, fn ($q) => $q->where('domain', $domain))->get(['categories.id', 'domain']);
        if ($linked->isEmpty()) {
            return [];
        }
        $categories = Category::whereIn('domain', $linked->pluck('domain')->unique())
            ->get(['id', 'parent_id', 'depth', 'domain', 'group_code']);

        return CategoryHierarchy::expandSelection($categories, $linked->modelKeys());
    }

    public function contents(CategoryTopic $topic, string $domain): Builder
    {
        [$model, $categoryDomain] = self::DOMAINS[$domain];
        $categoryIds = $this->categoryIds($topic, $categoryDomain);
        $query = $model::query();
        // 분류 선택 중지·메뉴 숨김은 기존 콘텐츠의 공개 상태를 바꾸지 않는다.
        $query->where(function ($query) use ($categoryIds, $domain, $topic) {
            $query->whereHas('categories', fn ($q) => $q->whereIn('categories.id', $categoryIds));
            if ($domain === 'events') {
                $query->orWhereHas('discoveryTopics', fn ($q) => $q->where('category_topics.id', $topic->id));
            }
        });
        $approved = fn ($q) => $q->where('allow_status', 'APPROVED')->where('status', 'ACTIVE');
        if (in_array($domain, ['hospitals', 'doctors', 'beauties', 'beauty-experts'], true)) {
            $approved($query);
        }
        if (in_array($domain, ['doctors', 'events', 'reviews', 'videos', 'evaluations'], true)) {
            $query->whereHas('hospital', $approved);
        }
        if ($domain === 'beauty-experts') {
            $query->whereHas('beauty', $approved);
        }
        if ($domain === 'events') {
            $query->where('allow_status', 'APPROVED')->where('hospital_status', 'PUBLIC')->where('admin_status', 'NORMAL')->applicationOpen(now());
        }
        if ($domain === 'videos') {
            $query->where('hospital_status', 'PUBLIC')->where('admin_status', 'NORMAL');
        }
        if (in_array($domain, ['reviews', 'talks', 'evaluations'], true)) {
            $query->where('status', 'ACTIVE');
        }
        if ($domain === 'evaluations') {
            $query->where('post_status', HospitalEvaluation::POST_STATUS_NORMAL);
        }
        if ($domain === 'talks') {
            $query->whereDoesntHave('categories', fn ($q) => $q->where('code', Talk::CATEGORY_CODE_SECRET));
        }
        if (in_array($domain, ['reviews', 'videos', 'talks', 'evaluations'], true)) {
            $query->whereDoesntHave('contentReportState', fn ($q) => $q->whereIn('report_status', [ContentReportState::STATUS_AUTO_BLOCKED, ContentReportState::STATUS_ADMIN_HIDDEN]));
        }

        return $query;
    }
}
