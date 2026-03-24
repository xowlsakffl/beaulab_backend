<?php

namespace App\Modules\Staff\Http\Requests\Faq;

use App\Domains\Common\Models\Category\Category;
use App\Domains\Faq\Models\Faq;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class FaqUpdateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        foreach (['category_id', 'channel', 'question', 'content', 'status', 'sort_order'] as $nullableKey) {
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
                'sometimes',
                'integer',
                Rule::exists('categories', 'id')->where(static fn ($query) => $query
                    ->where('domain', Category::DOMAIN_FAQ)
                    ->where('status', Category::STATUS_ACTIVE)),
            ],
            'channel' => ['sometimes', Rule::in(Faq::channels())],
            'question' => ['sometimes', 'string', 'max:255'],
            'content' => ['sometimes', 'string'],
            'status' => ['sometimes', 'string', Rule::in(Faq::statuses())],
            'sort_order' => ['sometimes', 'nullable', 'integer', 'min:0'],
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
