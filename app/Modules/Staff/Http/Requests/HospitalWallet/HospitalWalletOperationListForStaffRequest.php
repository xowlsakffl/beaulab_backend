<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\HospitalWallet;

use App\Domains\HospitalWallet\Models\HospitalWalletOperation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalWalletOperationListForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'hospital_id' => ['nullable', 'integer', 'min:1'],
            'type_group' => ['nullable', Rule::in(HospitalWalletOperation::typeGroups())],
            'statuses' => ['nullable', 'array'],
            'statuses.*' => [Rule::in(HospitalWalletOperation::statuses())],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'sort' => ['nullable', 'in:id,created_at,amount'],
            'direction' => ['nullable', 'in:asc,desc'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'q' => $validated['q'] ?? null,
            'hospital_id' => isset($validated['hospital_id']) ? (int) $validated['hospital_id'] : null,
            'type_group' => $validated['type_group'] ?? HospitalWalletOperation::TYPE_GROUP_CHARGE,
            'statuses' => array_values(array_unique($validated['statuses'] ?? [])),
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'sort' => $validated['sort'] ?? 'created_at',
            'direction' => $validated['direction'] ?? 'desc',
            'per_page' => (int) ($validated['per_page'] ?? 15),
        ];
    }

    public function attributes(): array
    {
        return [
            'q' => '검색어',
            'hospital_id' => '병의원',
            'type_group' => '내역 탭',
            'statuses' => '처리 상태',
            'statuses.*' => '처리 상태',
            'start_date' => '거래 시작일',
            'end_date' => '거래 종료일',
            'sort' => '정렬 기준',
            'direction' => '정렬 방향',
            'page' => '페이지',
            'per_page' => '페이지당 개수',
        ];
    }
}
