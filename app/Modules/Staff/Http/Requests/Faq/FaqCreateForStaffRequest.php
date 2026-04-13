<?php

namespace App\Modules\Staff\Http\Requests\Faq;

use App\Domains\Common\Models\Category\Category;
use App\Domains\Faq\Models\Faq;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * FaqCreateForStaffRequest 역할 정의.
 * FAQ 도메인의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class FaqCreateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        foreach (['status', 'sort_order'] as $nullableKey) {
            if (array_key_exists($nullableKey, $data) && $data[$nullableKey] === '') {
                $data[$nullableKey] = null;
            }
        }

        $this->replace($data);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id')->where(static fn ($query) => $query
                    ->where('domain', Category::DOMAIN_FAQ)
                    ->where('status', Category::STATUS_ACTIVE)),
            ],
            'channel' => ['required', Rule::in(Faq::channels())],
            'question' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'status' => ['sometimes', 'string', Rule::in(Faq::statuses())],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function attributes(): array
    {
        return [
            'category_id' => 'FAQ 카테고리',
            'channel' => 'FAQ 채널',
            'question' => '질문',
            'content' => '답변 내용',
            'status' => '운영 상태',
            'sort_order' => '노출 순서',
        ];
    }
}
