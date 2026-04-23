<?php

namespace App\Modules\User\Http\Requests\Talk;

use App\Domains\Common\Models\Category\Category;
use App\Domains\Talk\Models\Talk;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
use Illuminate\Validation\Rule;

final class TalkCreateForUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (array_key_exists('category_code', $data) && is_string($data['category_code'])) {
            $data['category_code'] = trim($data['category_code']);
        }

        if (isset($data['poll']) && is_array($data['poll']) && isset($data['poll']['options']) && is_array($data['poll']['options'])) {
            $data['poll']['options'] = array_map(
                static fn ($option) => is_string($option) ? trim($option) : $option,
                $data['poll']['options'],
            );
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
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:20000'],
            'category_code' => [
                'required',
                'string',
                Rule::in(Talk::categoryCodes()),
                Rule::exists('categories', 'code')->where(static fn ($query) => $query
                    ->where('domain', Talk::CATEGORY_DOMAIN)
                    ->where('status', Category::STATUS_ACTIVE)),
            ],
            'images' => ['nullable', 'array', 'max:' . Talk::MAX_IMAGE_COUNT],
            'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'poll' => ['nullable', 'array'],
            'poll.allow_multiple' => ['nullable', 'boolean'],
            'poll.options' => ['nullable', 'array', 'min:2', 'max:10'],
            'poll.options.*' => ['required', 'string', 'max:100', 'distinct:strict'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $poll = $this->input('poll');

            if (! is_array($poll)) {
                return;
            }

            if (! array_key_exists('allow_multiple', $poll)) {
                $validator->errors()->add('poll.allow_multiple', '투표를 등록할 때 복수 선택 허용 여부는 필수입니다.');
            }

            if (! array_key_exists('options', $poll)) {
                $validator->errors()->add('poll.options', '투표를 등록할 때 항목 목록은 필수입니다.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'title' => '제목',
            'content' => '내용',
            'category_code' => '토크 유형',
            'images' => '이미지 목록',
            'images.*' => '이미지',
            'poll' => '투표',
            'poll.allow_multiple' => '복수 선택 허용 여부',
            'poll.options' => '투표 항목 목록',
            'poll.options.*' => '투표 항목',
        ];
    }
}
