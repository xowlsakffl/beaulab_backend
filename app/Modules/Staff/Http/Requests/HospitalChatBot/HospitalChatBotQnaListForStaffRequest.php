<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\HospitalChatBot;

use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalChatBotQnaListForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $status = $this->input('status');
        if (is_string($status)) {
            $status = array_values(array_filter(array_map('trim', explode(',', $status))));
        }

        $this->merge(['status' => $status]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:100'],
            'status' => ['nullable', 'array'],
            'status.*' => ['string', Rule::in(HospitalChatBotQna::statuses())],
            'sort' => ['nullable', 'in:id,category,question,status,sort_order,created_at,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'q' => $validated['q'] ?? null,
            'category' => $validated['category'] ?? null,
            'status' => $validated['status'] ?? null,
            'sort' => $validated['sort'] ?? null,
            'direction' => $validated['direction'] ?? 'asc',
            'per_page' => (int) ($validated['per_page'] ?? 20),
        ];
    }
}
