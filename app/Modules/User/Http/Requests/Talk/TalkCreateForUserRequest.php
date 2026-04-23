<?php

namespace App\Modules\User\Http\Requests\Talk;

use App\Domains\Common\Models\Category\Category;
use App\Domains\Talk\Models\Talk;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class TalkCreateForUserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (array_key_exists('category_id', $data)) {
            $data['category_id'] = $this->normalizeId($data['category_id']);
        } elseif (array_key_exists('category_ids', $data)) {
            $categoryIds = $this->normalizeIdList($data['category_ids']);
            $data['category_id'] = $categoryIds[0] ?? null;
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
            'category_id' => [
                'nullable',
                'integer',
                Rule::exists('categories', 'id')->where(static fn ($query) => $query
                    ->where('domain', Category::DOMAIN_HOSPITAL_COMMUNITY)
                    ->where('status', Category::STATUS_ACTIVE)),
            ],
            'images' => ['nullable', 'array', 'max:' . Talk::MAX_IMAGE_COUNT],
            'images.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'poll' => ['nullable', 'array'],
            'poll.allow_multiple' => ['required_with:poll', 'boolean'],
            'poll.options' => ['required_with:poll', 'array', 'min:2', 'max:10'],
            'poll.options.*' => ['required', 'string', 'max:100', 'distinct:strict'],
        ];
    }

    private function normalizeId(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value > 0 ? $value : null;
        }

        if (is_string($value) && ctype_digit(trim($value))) {
            $id = (int) $value;

            return $id > 0 ? $id : null;
        }

        return null;
    }

    /**
     * @return array<int, int>
     */
    private function normalizeIdList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $value = explode(',', $value);
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(static fn ($item): bool => is_int($item) || (is_string($item) && ctype_digit(trim($item))))
            ->map(static fn ($item): int => (int) $item)
            ->filter(static fn (int $item): bool => $item > 0)
            ->unique()
            ->values()
            ->all();
    }
}
