<?php

namespace App\Domains\HospitalEvaluation\Dto\Staff;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\ContentReport\Models\ContentReportState;
use App\Domains\HospitalEvaluation\Models\HospitalEvaluation;

final readonly class HospitalEvaluationForStaffDto
{
    public function __construct(
        public int $id,
        public ?string $createdAt,
        public ?array $author,
        public ?array $hospital,
        public ?array $doctor,
        public string $categoryDomain,
        public array $categories,
        public ?array $report,
        public ?string $phone,
        public int $cost,
        public float $averageRating,
        public string $status,
        public int $viewCount,
        public array $receipt,
    ) {}

    public static function fromModel(HospitalEvaluation $evaluation): self
    {
        return new self(
            id: (int) $evaluation->id,
            createdAt: $evaluation->created_at?->toISOString(),
            author: self::author($evaluation),
            hospital: self::hospital($evaluation),
            doctor: self::doctor($evaluation),
            categoryDomain: (string) $evaluation->category_domain,
            categories: self::categories($evaluation),
            report: self::report($evaluation),
            phone: $evaluation->phone,
            cost: (int) $evaluation->cost,
            averageRating: $evaluation->averageRating(),
            status: (string) $evaluation->status,
            viewCount: (int) $evaluation->view_count,
            receipt: self::receipt($evaluation),
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'created_at' => $this->createdAt,
            'author' => $this->author,
            'hospital' => $this->hospital,
            'doctor' => $this->doctor,
            'category_domain' => $this->categoryDomain,
            'categories' => $this->categories,
            'report' => $this->report,
            'phone' => $this->phone,
            'cost' => $this->cost,
            'average_rating' => $this->averageRating,
            'status' => $this->status,
            'view_count' => $this->viewCount,
            'receipt' => $this->receipt,
        ];
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
            ->map(static function (Category $category) use ($evaluation): array {
                $attributes = $category->getAttributes();

                return [
                    'id' => (int) $category->id,
                    'code' => (string) ($attributes['code'] ?? ''),
                    'domain' => (string) ($attributes['domain'] ?? $evaluation->category_domain),
                    'name' => (string) $category->name,
                    'full_path' => (string) ($attributes['full_path'] ?? ''),
                    'is_primary' => (bool) ($category->pivot?->is_primary ?? false),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    private static function receipt(HospitalEvaluation $evaluation): array
    {
        return [
            'status' => (string) $evaluation->receipt_status,
            'label' => $evaluation->receiptStatusLabel(),
        ];
    }
}
