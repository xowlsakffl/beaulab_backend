<?php

namespace App\Domains\HospitalEvaluation\Dto\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\Common\Media\Models\Media;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;

final readonly class HospitalEvaluationForStaffDetailDto
{
    public function __construct(
        public int $id,
        public ?array $author,
        public ?array $hospital,
        public ?array $doctor,
        public array $categories,
        public ?array $report,
        public string $content,
        public ?string $phone,
        public ?string $authorIp,
        public int $cost,
        public array $ratings,
        public array $assessment,
        public string $status,
        public string $postStatus,
        public int $viewCount,
        public array $receipt,
        public array $images,
        public array $receiptImages,
        public ?array $operationHistories,
        public ?string $createdAt,
        public ?string $updatedAt,
        public ?string $deletedAt,
    ) {}

    public static function fromModel(HospitalEvaluation $evaluation, ?array $operationHistories = null): self
    {
        return new self(
            id: (int) $evaluation->id,
            author: self::author($evaluation),
            hospital: self::hospital($evaluation),
            doctor: self::doctor($evaluation),
            categories: self::categories($evaluation),
            report: self::report($evaluation),
            content: (string) $evaluation->content,
            phone: $evaluation->phone,
            authorIp: $evaluation->author_ip,
            cost: (int) $evaluation->cost,
            ratings: self::ratings($evaluation),
            assessment: self::assessment($evaluation),
            status: (string) $evaluation->status,
            postStatus: (string) $evaluation->post_status,
            viewCount: (int) $evaluation->view_count,
            receipt: self::receipt($evaluation),
            images: self::images($evaluation),
            receiptImages: self::receiptImages($evaluation),
            operationHistories: $operationHistories,
            createdAt: $evaluation->created_at?->toISOString(),
            updatedAt: $evaluation->updated_at?->toISOString(),
            deletedAt: $evaluation->deleted_at?->toISOString(),
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'author' => $this->author,
            'hospital' => $this->hospital,
            'doctor' => $this->doctor,
            'categories' => $this->categories,
            'report' => $this->report,
            'content' => $this->content,
            'phone' => $this->phone,
            'author_ip' => $this->authorIp,
            'cost' => $this->cost,
            'ratings' => $this->ratings,
            'assessment' => $this->assessment,
            'status' => $this->status,
            'post_status' => $this->postStatus,
            'view_count' => $this->viewCount,
            'receipt' => $this->receipt,
            'images' => $this->images,
            'receipt_images' => $this->receiptImages,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
            'deleted_at' => $this->deletedAt,
        ];

        if ($this->operationHistories !== null) {
            $data['operation_histories'] = $this->operationHistories;
        }

        return $data;
    }

    private static function report(HospitalEvaluation $evaluation): ?array
    {
        if (! $evaluation->relationLoaded('contentReportState') || ! $evaluation->contentReportState) {
            return null;
        }

        $state = $evaluation->contentReportState;

        if ((string) $state->report_status === ContentReportState::STATUS_NONE) {
            return null;
        }

        return [
            'status' => (string) $state->report_status,
            'label' => $state->statusLabel(),
        ];
    }

    private static function author(HospitalEvaluation $evaluation): ?array
    {
        if (! $evaluation->relationLoaded('author') || ! $evaluation->author) {
            return null;
        }

        $attributes = $evaluation->author->getAttributes();

        return [
            'id' => (int) $evaluation->author->getKey(),
            'name' => (string) ($attributes['name'] ?? ''),
            'nickname' => isset($attributes['nickname']) && trim((string) $attributes['nickname']) !== ''
                ? (string) $attributes['nickname']
                : null,
            'email' => isset($attributes['email']) && trim((string) $attributes['email']) !== ''
                ? (string) $attributes['email']
                : null,
        ];
    }

    private static function hospital(HospitalEvaluation $evaluation): ?array
    {
        if (! $evaluation->relationLoaded('hospital') || ! $evaluation->hospital) {
            return null;
        }

        $businessNumber = null;
        if ($evaluation->hospital->relationLoaded('businessRegistration') && $evaluation->hospital->businessRegistration) {
            $businessNumber = $evaluation->hospital->businessRegistration->business_number;
        }

        return [
            'id' => (int) $evaluation->hospital->getKey(),
            'name' => (string) $evaluation->hospital->name,
            'business_number' => $businessNumber,
        ];
    }

    private static function doctor(HospitalEvaluation $evaluation): ?array
    {
        if (! $evaluation->relationLoaded('doctor') || ! $evaluation->doctor) {
            return null;
        }

        return [
            'id' => (int) $evaluation->doctor->getKey(),
            'name' => (string) $evaluation->doctor->name,
            'position' => $evaluation->doctor->position,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function categories(HospitalEvaluation $evaluation): array
    {
        if (! $evaluation->relationLoaded('categories')) {
            return [];
        }

        return $evaluation->categories
            ->map(static function (Category $category): array {
                $attributes = $category->getAttributes();

                return [
                    'id' => (int) $category->id,
                    'code' => (string) ($attributes['code'] ?? ''),
                    'domain' => (string) ($attributes['domain'] ?? ''),
                    'name' => (string) $category->name,
                    'full_path' => (string) ($attributes['full_path'] ?? ''),
                    'is_primary' => (bool) ($category->pivot?->is_primary ?? false),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, int|float>
     */
    private static function ratings(HospitalEvaluation $evaluation): array
    {
        return [
            'staff_kindness' => (int) $evaluation->rating_staff_kindness,
            'surgery_satisfaction' => (int) $evaluation->rating_surgery_satisfaction,
            'facility' => (int) $evaluation->rating_facility,
            'aftercare' => (int) $evaluation->rating_aftercare,
            'cost' => (int) $evaluation->rating_cost,
            'average' => $evaluation->averageRating(),
        ];
    }

    /**
     * @return array<string, array{value: bool, label: string}>
     */
    private static function assessment(HospitalEvaluation $evaluation): array
    {
        return [
            'overtreatment' => [
                'value' => (bool) $evaluation->has_overtreatment,
                'label' => (bool) $evaluation->has_overtreatment ? '있음' : '없음',
            ],
            'waiting_time' => [
                'value' => (bool) $evaluation->is_waiting_time_long,
                'label' => (bool) $evaluation->is_waiting_time_long ? '길었음' : '짧았음',
            ],
            'doctor_consultation' => [
                'value' => (bool) $evaluation->has_doctor_consultation,
                'label' => (bool) $evaluation->has_doctor_consultation ? '상담함' : '상담안함',
            ],
            'recommendation' => [
                'value' => (bool) $evaluation->is_recommended,
                'label' => (bool) $evaluation->is_recommended ? '추천' : '비추천',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private static function receipt(HospitalEvaluation $evaluation): array
    {
        return [
            'status' => (string) $evaluation->receipt_status,
            'label' => $evaluation->receiptStatusLabel(),
            'rejection_reason' => $evaluation->receipt_rejection_reason,
            'rejection_reason_label' => $evaluation->receiptRejectionReasonLabel(),
            'rejection_reason_text' => $evaluation->receipt_rejection_reason_text,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function images(HospitalEvaluation $evaluation): array
    {
        if (! $evaluation->relationLoaded('images')) {
            return [];
        }

        return self::mediaList($evaluation->images);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function receiptImages(HospitalEvaluation $evaluation): array
    {
        if (! $evaluation->relationLoaded('receiptImages')) {
            return [];
        }

        return self::mediaList($evaluation->receiptImages);
    }

    /**
     * @param  iterable<int, Media>  $mediaList
     * @return array<int, array<string, mixed>>
     */
    private static function mediaList(iterable $mediaList): array
    {
        return collect($mediaList)
            ->map(fn (Media $media): array => [
                'id' => (int) $media->id,
                'collection' => (string) $media->collection,
                'disk' => (string) $media->disk,
                'path' => (string) $media->path,
                'mime_type' => (string) $media->mime_type,
                'size' => (int) $media->size,
                'width' => $media->width !== null ? (int) $media->width : null,
                'height' => $media->height !== null ? (int) $media->height : null,
                'sort_order' => (int) $media->sort_order,
                'is_primary' => (bool) $media->is_primary,
                'metadata' => $media->metadata,
                'created_at' => $media->created_at?->toISOString(),
                'updated_at' => $media->updated_at?->toISOString(),
            ])
            ->values()
            ->all();
    }
}
