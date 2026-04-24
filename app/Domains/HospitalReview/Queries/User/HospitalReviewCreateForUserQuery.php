<?php

namespace App\Domains\HospitalReview\Queries\User;

use App\Domains\HospitalReview\Models\HospitalReview;

final class HospitalReviewCreateForUserQuery
{
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
