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
            'all' => ['nullable', 'boolean'],
            'q' => ['nullable', 'string', 'max:100'],
            'depth' => ['nullable', 'integer', 'between:1,3'],
            'root_id' => ['nullable', 'integer', 'min:1'],
            'category' => ['nullable', 'string', 'max:100'],
            'folder_id' => ['nullable', 'integer', 'min:1'],
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
            'all' => (bool) ($validated['all'] ?? false),
            'q' => $validated['q'] ?? null,
            'depth' => $validated['depth'] ?? null,
            'root_id' => isset($validated['root_id']) ? (int) $validated['root_id'] : null,
            'category' => $validated['category'] ?? null,
            'folder_id' => isset($validated['folder_id']) ? (int) $validated['folder_id'] : null,
            'status' => $validated['status'] ?? null,
            'sort' => $validated['sort'] ?? null,
            'direction' => $validated['direction'] ?? 'asc',
            'per_page' => (int) ($validated['per_page'] ?? 20),
        ];
    }
}
