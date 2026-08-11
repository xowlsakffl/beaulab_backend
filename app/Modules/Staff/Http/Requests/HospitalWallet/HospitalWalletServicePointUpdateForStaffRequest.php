<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\HospitalWallet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalWalletServicePointUpdateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $payload = [];

        if ($this->has('hospital_ids')) {
            $payload['hospital_ids'] = $this->normalizeIdList($this->input('hospital_ids'));
        }

        $idempotencyKey = $this->input('idempotency_key') ?? $this->header('Idempotency-Key');
        if (is_string($idempotencyKey)) {
            $payload['idempotency_key'] = trim($idempotencyKey);
        }

        if (is_string($this->input('reason'))) {
            $payload['reason'] = trim((string) $this->input('reason'));
        }

        $this->merge($payload);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'hospital_ids' => ['required', 'array', 'min:1', 'max:100'],
            'hospital_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('hospitals', 'id')->whereNull('deleted_at'),
            ],
            'amount' => ['required', 'integer', 'min:1'],
            'reason' => ['required', 'string', 'max:500'],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    public function attributes(): array
    {
        return [
            'hospital_ids' => '병의원 목록',
            'hospital_ids.*' => '병의원',
            'amount' => '서비스 포인트',
            'reason' => '처리 사유',
            'idempotency_key' => '중복 처리 방지 키',
        ];
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
            $trimmed = trim($value);
            $decoded = json_decode($trimmed, true);
            $value = str_starts_with($trimmed, '[')
                && json_last_error() === JSON_ERROR_NONE
                && is_array($decoded)
                && array_is_list($decoded)
                ? $decoded
                : explode(',', $trimmed);
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
