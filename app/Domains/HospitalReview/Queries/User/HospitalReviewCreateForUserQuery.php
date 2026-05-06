<?php

namespace App\Domains\HospitalReview\Queries\User;

use App\Domains\Common\Category\Models\Category;
use App\Domains\HospitalDoctor\Models\HospitalDoctor;
use App\Domains\HospitalReview\Models\HospitalReview;
use Illuminate\Support\Collection;

final class HospitalReviewCreateForUserQuery
{
    public function doctorBelongsToHospital(int $doctorId, int $hospitalId): bool
    {
        return HospitalDoctor::query()
            ->whereKey($doctorId)
            ->where('hospital_id', $hospitalId)
            ->exists();
    }

    /**
     * @param array<int, string> $categoryCodes
     * @return Collection<int, Category>
     */
    public function categoriesByCodes(array $categoryCodes): Collection
    {
        return Category::query()
            ->whereIn('domain', HospitalReview::categoryDomains())
            ->where('status', Category::STATUS_ACTIVE)
            ->whereIn('code', array_values($categoryCodes))
            ->get();
    }

    public function create(array $payload): HospitalReview
    {
        return HospitalReview::query()->create([
            'author_id' => (int) $payload['author_id'],
            'hospital_id' => (int) $payload['hospital_id'],
            'doctor_id' => isset($payload['doctor_id']) ? (int) $payload['doctor_id'] : null,
            'category_domain' => (string) $payload['category_domain'],
            'title' => (string) $payload['title'],
            'content' => (string) $payload['content'],
            'cost' => (int) $payload['cost'],
            'rating' => (int) $payload['rating'],
            'status' => HospitalReview::STATUS_ACTIVE,
            'post_status' => HospitalReview::POST_STATUS_NORMAL,
            'is_main_featured' => false,
            'is_sub_featured' => false,
            'view_count' => 0,
            'comment_count' => 0,
            'like_count' => 0,
            'save_count' => 0,
        ]);
    }
}
