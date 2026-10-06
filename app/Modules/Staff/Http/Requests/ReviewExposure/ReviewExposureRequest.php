<?php

namespace App\Modules\Staff\Http\Requests\ReviewExposure;

use App\Domains\ReviewExposure\Models\ReviewExposure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class ReviewExposureRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $scope = ['placement' => ['required', Rule::in(ReviewExposure::PLACEMENTS)], 'category_id' => ['nullable', 'integer', 'min:1']];
        if ($this->routeIs('staff.review-exposures.order')) {
            return [...$scope, 'revision' => ['required', 'integer', 'min:0'], 'keys' => ['required', 'array', 'min:1', 'max:100'], 'keys.*' => ['string', 'distinct', 'regex:/^review:[1-9][0-9]*$/']];
        }
        if ($this->isMethod('get')) {
            return [...$scope, 'q' => ['nullable', 'string', 'max:100'], 'start_date' => ['nullable', 'date_format:Y-m-d'], 'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'], 'page' => ['sometimes', 'integer', 'min:1']];
        }
        $updating = $this->route('reviewExposure') !== null;
        $version = $updating ? ['expected_updated_at' => ['required', 'date_format:Y-m-d\TH:i:s.u\Z']] : [];
        if ($this->routeIs('staff.review-exposures.status')) {
            return [...$version, 'status' => ['required', Rule::in(['ACTIVE', 'INACTIVE'])]];
        }

        return [...($updating ? ['placement' => ['prohibited'], 'category_id' => ['prohibited']] : $scope), ...$version, 'hospital_review_id' => ['required', 'integer', 'min:1'], 'start_at' => ['required', 'date'], 'end_at' => ['required', 'date', 'after_or_equal:start_at']];
    }

    public function attributes(): array
    {
        return ['hospital_review_id' => '후기', 'start_at' => '게시 시작일시', 'end_at' => '게시 종료일시', 'category_id' => '대분류', 'placement' => '노출 위치'];
    }
}
