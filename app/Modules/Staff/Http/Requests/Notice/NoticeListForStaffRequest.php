<?php

namespace App\Modules\Staff\Http\Requests\Notice;

use App\Domains\Notice\Models\Notice;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class NoticeListForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'channel' => $this->normalizeToArray($this->input('channel')),
            'exposure_status' => $this->normalizeToArray($this->input('exposure_status')),
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:100'],
            'channel' => ['nullable', 'array'],
            'channel.*' => [Rule::in(Notice::channels())],
            'is_visible' => ['nullable', 'boolean'],
            'is_pinned' => ['nullable', 'boolean'],
            'is_important' => ['nullable', 'boolean'],
            'exposure_status' => ['nullable', 'array'],
            'exposure_status.*' => [Rule::in(Notice::exposureStatuses())],
            'sort' => ['nullable', 'in:id,title,channel,is_visible,is_pinned,pinned_order,publish_start_at,publish_end_at,push_sent_at,is_important,view_count,created_at,updated_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'q' => $validated['q'] ?? null,
            'channel' => $validated['channel'] ?? null,
            'is_visible' => $validated['is_visible'] ?? null,
            'is_pinned' => $validated['is_pinned'] ?? null,
            'is_important' => $validated['is_important'] ?? null,
            'exposure_status' => $validated['exposure_status'] ?? null,
            'sort' => $validated['sort'] ?? null,
            'direction' => $validated['direction'] ?? 'desc',
            'per_page' => (int) ($validated['per_page'] ?? 15),
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

        if (! is_array($value)) {
            return null;
        }

        $normalized = array_values(array_filter(array_map(
            static fn ($item): ?string => is_scalar($item) ? trim((string) $item) : null,
            $value,
        )));

        return $normalized === [] ? null : array_values(array_unique($normalized));
    }
}
