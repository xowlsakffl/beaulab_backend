<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\HospitalWallet;

use App\Domains\Common\Sms\Models\SmsBatch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalWalletNoticeListForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', Rule::in(SmsBatch::statuses())],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'q' => $validated['q'] ?? null,
            'status' => $validated['status'] ?? null,
            'start_date' => $validated['start_date'] ?? null,
            'end_date' => $validated['end_date'] ?? null,
            'per_page' => (int) ($validated['per_page'] ?? 15),
        ];
    }

    public function attributes(): array
    {
        return [
            'q' => '검색어',
            'status' => '발송 상태',
            'start_date' => '발송 시작일',
            'end_date' => '발송 종료일',
            'page' => '페이지',
            'per_page' => '페이지당 개수',
        ];
    }
}
