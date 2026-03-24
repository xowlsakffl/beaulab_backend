<?php


namespace App\Modules\Staff\Http\Requests\Hospital;

use App\Domains\Common\Models\Category\Category;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalListForStaffRequest extends FormRequest
{

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->normalizeToArray($this->input('status')),
            'allow_status' => $this->normalizeToArray($this->input('allow_status')),
            'category_ids' => $this->normalizeToArray($this->input('category_ids') ?? $this->input('category_id')),
            'include' => $this->normalizeToArray($this->input('include')),
        ]);
    }

    public function authorize(): bool
    {
        // 이미 라우트에서 검사함
        return true;
    }

    public function rules(): array
    {
        return [
            'q'            => ['nullable', 'string', 'max:100'],
            'start_date'   => ['nullable', 'date_format:Y-m-d'],
            'end_date'     => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'updated_start_date' => ['nullable', 'date_format:Y-m-d'],
            'updated_end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:updated_start_date'],

            'status'       => ['nullable', 'array'],
            'status.*'     => ['in:ACTIVE,SUSPENDED,WITHDRAWN'],
            'allow_status' => ['nullable', 'array'],
            'allow_status.*' => ['in:PENDING,APPROVED,REJECTED'],
            'category_ids' => ['nullable', 'array', 'min:1', 'max:100'],
            'category_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('categories', 'id')->where(static fn ($query) => $query
                    ->whereIn('domain', [Category::DOMAIN_HOSPITAL_TREATMENT, Category::DOMAIN_HOSPITAL_SURGERY])),
            ],
            'include' => ['nullable', 'array'],
            'include.*' => ['in:categories,features'],

            'sort'         => ['nullable', 'in:id,name,view_count,allow_status,status,created_at,updated_at'],
            'direction'    => ['nullable', 'in:asc,desc'],

            'page'         => ['nullable', 'integer', 'min:1'],
            'per_page'     => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        $validate = $this->validated();

        return [
            'q'            => $validate['q'] ?? null,
            'start_date'   => $validate['start_date'] ?? null,
            'end_date'     => $validate['end_date'] ?? null,
            'updated_start_date' => $validate['updated_start_date'] ?? null,
            'updated_end_date' => $validate['updated_end_date'] ?? null,
            'status'       => $validate['status'] ?? null,
            'allow_status' => $validate['allow_status'] ?? null,
            'category_ids' => $validate['category_ids'] ?? null,
            'include' => $validate['include'] ?? [],

            'sort'         => $validate['sort'] ?? 'id',
            'direction'    => $validate['direction'] ?? 'desc',

            'per_page'     => (int)($validate['per_page'] ?? 15),
        ];
    }

    /**
     * @return array<int, string>|null
     */
    private function normalizeToArray(mixed $value): ?array
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_string($value)) {
            $value = explode(',', $value);
        } elseif (is_int($value)) {
            $value = [(string) $value];
        }

        if (!is_array($value)) {
            return null;
        }

        $normalized = array_values(array_filter(array_map(
            static function ($item): ?string {
                if (is_string($item)) {
                    $item = trim($item);
                    return $item === '' ? null : $item;
                }

                if (is_int($item)) {
                    return (string) $item;
                }

                return null;
            },
            $value,
        )));

        return $normalized === [] ? null : array_values(array_unique($normalized));
    }


    public function attributes(): array
    {
        return [
            'q' => '검색어',
            'start_date' => '시작일',
            'end_date' => '종료일',
            'updated_start_date' => '수정 시작일',
            'updated_end_date' => '수정 종료일',
            'status' => '운영 상태',
            'status.*' => '운영 상태',
            'allow_status' => '검수 상태',
            'allow_status.*' => '검수 상태',
            'category_ids' => '카테고리 목록',
            'category_ids.*' => '카테고리',
            'include' => '포함 항목',
            'include.*' => '포함 항목',
            'sort' => '정렬 기준',
            'direction' => '정렬 방향',
            'page' => '페이지',
            'per_page' => '페이지당 개수',
        ];
    }

}
