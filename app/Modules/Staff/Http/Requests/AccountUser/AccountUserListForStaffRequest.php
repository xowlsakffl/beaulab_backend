<?php

declare(strict_types=1);


namespace App\Modules\Staff\Http\Requests\AccountUser;

use Illuminate\Foundation\Http\FormRequest;

final class AccountUserListForStaffRequest extends FormRequest
{

    protected function prepareForValidation(): void
    {
        $this->merge([
            'status' => $this->normalizeToArray($this->input('status')),
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

            'status'       => ['nullable', 'array'],
            'status.*'     => ['in:ACTIVE,SUSPENDED,BLOCKED'],

            'sort'         => ['nullable', 'in:id,name,status,created_at,updated_at'],

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
            'status'       => $validate['status'] ?? null,

            'sort'         => $validate['sort'] ?? 'id',

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
        }

        if (!is_array($value)) {
            return null;
        }

        $normalized = array_values(array_filter(array_map(
            static fn ($item) => is_string($item) ? trim($item) : null,
            $value,
        )));

        return $normalized === [] ? null : $normalized;
    }


    public function attributes(): array
    {
        return [
            'q' => '검색어',
            'start_date' => '시작일',
            'end_date' => '종료일',
            'status' => '운영 상태',
            'status.*' => '운영 상태',
            'sort' => '정렬 기준',
            'page' => '페이지',
            'per_page' => '페이지당 개수',
        ];
    }

}
