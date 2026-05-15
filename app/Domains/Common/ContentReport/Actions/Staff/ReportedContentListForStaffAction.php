<?php

namespace App\Domains\Common\ContentReport\Actions\Staff;

use App\Common\Exceptions\CustomException;
use App\Common\Exceptions\ErrorCode;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\ContentReport\Dto\Staff\ContentReportStateForStaffDto;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\Common\ContentReport\Queries\Staff\ReportedContentListForStaffQuery;
use App\Domains\Common\ContentReport\Support\ContentReportTargetRegistry;
use App\Domains\Common\Media\Models\Media;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;
use App\Domains\HospitalReview\Models\HospitalReview;
use App\Domains\HospitalReview\Models\HospitalReviewComment;
use App\Domains\Talk\Models\Talk;
use App\Domains\Talk\Models\TalkComment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

final class ReportedContentListForStaffAction
{
    public function __construct(
        private readonly ReportedContentListForStaffQuery $query,
    ) {}

    public function execute(string $targetAlias, array $filters = []): array
    {
        $targetClass = ContentReportTargetRegistry::classForAlias($targetAlias);

        if ($targetClass === null) {
            throw new CustomException(ErrorCode::INVALID_REQUEST, '지원하지 않는 신고 대상입니다.');
        }

        Gate::authorize('viewAny', $targetClass);

        $paginator = $this->query->paginate($targetClass, $filters);
        $states = $paginator->getCollection();
        $states->loadMorph('target', $this->targetRelations());
        $reportSummaries = $this->query->reportSummaries($states);

        return [
            'items' => $states
                ->map(fn (ContentReportState $state): array => [
                    'target_type' => $targetAlias,
                    'target' => $this->targetToArray($state->target),
                    'report' => $this->reportStateToArray($state, $reportSummaries),
                ])
                ->values()
                ->all(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
                'summary' => $this->query->summary($targetClass, $filters),
            ],
        ];
    }

    /**
     * @return array<class-string<Model>, array<int, string>>
     */
    private function targetRelations(): array
    {
        return [
            Talk::class => [
                'author:id,name,nickname,email',
                'categories',
            ],
            TalkComment::class => [
                'talk.categories',
                'author:id,name,nickname,email',
            ],
            HospitalReview::class => [
                'author:id,name,nickname,email',
                'hospital:id,name',
                'beforeImages',
                'afterImages',
                'categories',
            ],
            HospitalReviewComment::class => [
                'review.beforeImages',
                'review.afterImages',
                'review.categories',
                'author:id,name,nickname,email',
            ],
            HospitalEvaluation::class => [
                'author:id,name,nickname,email',
                'hospital:id,name',
            ],
        ];
    }

    private function targetToArray(?Model $target): ?array
    {
        return match (true) {
            $target instanceof Talk => $this->talkToListArray($target),
            $target instanceof TalkComment => $this->talkCommentToListArray($target),
            $target instanceof HospitalReview => $this->hospitalReviewToListArray($target),
            $target instanceof HospitalReviewComment => $this->hospitalReviewCommentToListArray($target),
            $target instanceof HospitalEvaluation => $this->hospitalEvaluationToListArray($target),
            default => null,
        };
    }

    private function talkToListArray(Talk $talk): array
    {
        return [
            'id' => (int) $talk->id,
            'created_at' => $talk->created_at?->toISOString() ?? '',
            'author' => $this->authorToArray($talk),
            'category' => $this->primaryCategoryToArray($talk),
            'title' => (string) $talk->title,
            'status' => (string) $talk->status,
        ];
    }

    private function talkCommentToListArray(TalkComment $comment): array
    {
        $talk = $comment->relationLoaded('talk') ? $comment->talk : null;

        return [
            'id' => (int) $comment->id,
            'created_at' => $comment->created_at?->toISOString() ?? '',
            'author' => $this->authorToArray($comment),
            'category' => $talk instanceof Talk ? $this->primaryCategoryToArray($talk) : null,
            'parent_talk_title' => $talk instanceof Talk ? (string) $talk->title : null,
            'content_preview' => $this->contentPreview((string) $comment->content),
            'status' => (string) $comment->status,
        ];
    }

    private function hospitalReviewToListArray(HospitalReview $review): array
    {
        $imageSummary = $this->reviewImageSummary($review);

        return [
            'id' => (int) $review->id,
            'created_at' => $review->created_at?->toISOString() ?? '',
            'author' => $this->authorToArray($review),
            'hospital' => $this->hospitalToArray($review),
            'categories' => $this->categoriesToArray($review),
            'first_image' => $imageSummary['first_image'],
            'image_count' => $imageSummary['image_count'],
            'status' => (string) $review->status,
        ];
    }

    private function hospitalReviewCommentToListArray(HospitalReviewComment $comment): array
    {
        $review = $comment->relationLoaded('review') ? $comment->review : null;
        $imageSummary = $review instanceof HospitalReview
            ? $this->reviewImageSummary($review)
            : ['first_image' => null, 'image_count' => 0];

        return [
            'id' => (int) $comment->id,
            'created_at' => $comment->created_at?->toISOString() ?? '',
            'author' => $this->authorToArray($comment),
            'categories' => $review instanceof HospitalReview ? $this->categoriesToArray($review) : [],
            'parent' => $review instanceof HospitalReview ? [
                'id' => (int) $review->id,
                'title' => (string) $review->title,
                'categories' => $this->categoriesToArray($review),
                'first_image' => $imageSummary['first_image'],
                'image_count' => $imageSummary['image_count'],
            ] : null,
            'content_preview' => $this->contentPreview((string) $comment->content),
            'status' => (string) $comment->status,
        ];
    }

    private function hospitalEvaluationToListArray(HospitalEvaluation $evaluation): array
    {
        return [
            'id' => (int) $evaluation->id,
            'created_at' => $evaluation->created_at?->toISOString() ?? '',
            'category_domain' => (string) $evaluation->category_domain,
            'author' => $this->authorToArray($evaluation),
            'hospital' => $this->hospitalToArray($evaluation),
            'phone' => $evaluation->phone,
            'status' => (string) $evaluation->status,
        ];
    }

    private function authorToArray(Model $model): ?array
    {
        if (! $model->relationLoaded('author') || ! $model->getRelation('author')) {
            return null;
        }

        $author = $model->getRelation('author');
        $attributes = $author->getAttributes();

        return [
            'id' => (int) $author->getKey(),
            'name' => (string) ($attributes['name'] ?? ''),
            'nickname' => isset($attributes['nickname']) && trim((string) $attributes['nickname']) !== ''
                ? (string) $attributes['nickname']
                : null,
            'email' => isset($attributes['email']) && trim((string) $attributes['email']) !== ''
                ? (string) $attributes['email']
                : null,
        ];
    }

    private function hospitalToArray(Model $model): ?array
    {
        if (! $model->relationLoaded('hospital') || ! $model->getRelation('hospital')) {
            return null;
        }

        $hospital = $model->getRelation('hospital');

        return [
            'id' => (int) $hospital->getKey(),
            'name' => (string) $hospital->name,
        ];
    }

    private function primaryCategoryToArray(Model $model): ?array
    {
        if (! $model->relationLoaded('categories')) {
            return null;
        }

        $category = $model->getRelation('categories')
            ->sortByDesc(fn (Category $category): bool => (bool) ($category->pivot?->is_primary ?? false))
            ->values()
            ->first();

        if (! $category instanceof Category) {
            return null;
        }

        return $this->categoryToArray($category);
    }

    private function categoriesToArray(Model $model): array
    {
        if (! $model->relationLoaded('categories')) {
            return [];
        }

        return $model->getRelation('categories')
            ->map(fn (Category $category): array => $this->categoryToArray($category))
            ->values()
            ->all();
    }

    private function categoryToArray(Category $category): array
    {
        $attributes = $category->getAttributes();

        return [
            'id' => (int) $category->id,
            'name' => (string) $category->name,
            'full_path' => (string) ($attributes['full_path'] ?? ''),
            'depth' => isset($attributes['depth']) ? (int) $attributes['depth'] : null,
        ];
    }

    /**
     * @return array{first_image: ?array, image_count: int}
     */
    private function reviewImageSummary(HospitalReview $review): array
    {
        $beforeImages = $review->relationLoaded('beforeImages') ? $review->beforeImages : collect();
        $afterImages = $review->relationLoaded('afterImages') ? $review->afterImages : collect();
        $firstImage = $beforeImages->first() ?? $afterImages->first();

        return [
            'first_image' => $firstImage instanceof Media ? $this->mediaToArray($firstImage) : null,
            'image_count' => $beforeImages->count() + $afterImages->count(),
        ];
    }

    private function mediaToArray(Media $media): array
    {
        return [
            'id' => (int) $media->id,
            'collection' => (string) $media->collection,
            'disk' => (string) $media->disk,
            'path' => (string) $media->path,
            'mime_type' => (string) $media->mime_type,
            'width' => $media->width !== null ? (int) $media->width : null,
            'height' => $media->height !== null ? (int) $media->height : null,
            'sort_order' => (int) $media->sort_order,
            'is_primary' => (bool) $media->is_primary,
        ];
    }

    private function contentPreview(string $content, int $maxLength = 140): string
    {
        $decoded = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $normalized = trim((string) preg_replace('/\s+/u', ' ', $decoded));

        return $normalized === '' ? '-' : Str::limit($normalized, $maxLength, '...');
    }

    private function reportStateToArray(ContentReportState $state, array $reportSummaries): array
    {
        $summary = $reportSummaries[$this->reportKey($state->target_type, (int) $state->target_id)] ?? [];

        return ContentReportStateForStaffDto::fromModel(
            $state,
            $summary['latest_report'] ?? null,
            $summary['reason_counts'] ?? [],
        )->toArray();
    }

    private function reportKey(string $targetType, int $targetId): string
    {
        return "{$targetType}:{$targetId}";
    }
}
