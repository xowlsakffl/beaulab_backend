<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\HospitalWallet;

use App\Domains\HospitalWallet\Support\HospitalWalletSms;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

final class HospitalWalletNoticeCreateForStaffRequest extends FormRequest
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
            'message_parts' => ['required', 'array', 'min:1', 'max:200'],
            'message_parts.*' => ['required', 'array:type,text,key'],
            'message_parts.*.type' => ['required', 'string', Rule::in(HospitalWalletSms::MESSAGE_PART_TYPES)],
            'message_parts.*.text' => ['nullable', 'string', 'max:2000'],
            'message_parts.*.key' => ['nullable', 'string', Rule::in(HospitalWalletSms::VARIABLE_KEYS)],
            'send_to_manager' => ['required', 'boolean'],
            'send_to_representative' => ['sometimes', 'boolean', Rule::in([false, 0, '0'])],
            'idempotency_key' => ['required', 'uuid'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if (! $this->boolean('send_to_manager')) {
                    $validator->errors()->add('send_to_manager', '담당자 수신번호를 선택해 주세요.');
                }

                $parts = $this->input('message_parts', []);
                if (! is_array($parts)) {
                    return;
                }

                foreach ($parts as $index => $part) {
                    if (! is_array($part)) {
                        continue;
                    }

                    $type = $part['type'] ?? null;
                    if ($type === HospitalWalletSms::MESSAGE_PART_TEXT && ! is_string($part['text'] ?? null)) {
                        $validator->errors()->add("message_parts.{$index}.text", '텍스트 내용을 입력해 주세요.');
                    }

                    if ($type === HospitalWalletSms::MESSAGE_PART_VARIABLE && ! is_string($part['key'] ?? null)) {
                        $validator->errors()->add("message_parts.{$index}.key", '문자 템플릿 변수를 선택해 주세요.');
                    }
                }

                $normalizedParts = HospitalWalletSms::normalizeMessageParts($parts);
                $hasMessage = collect($normalizedParts)->contains(
                    static fn (array $part): bool => $part['type'] === HospitalWalletSms::MESSAGE_PART_VARIABLE
                        || trim((string) ($part['text'] ?? '')) !== '',
                );

                if (! $hasMessage) {
                    $validator->errors()->add('message_parts', '문자 내용을 입력해 주세요.');
                }

                $textLength = collect($normalizedParts)
                    ->where('type', HospitalWalletSms::MESSAGE_PART_TEXT)
                    ->sum(static fn (array $part): int => mb_strlen((string) ($part['text'] ?? '')));

                if ($textLength > 2000) {
                    $validator->errors()->add('message_parts', '문자 내용은 2,000자 이하로 입력해 주세요.');
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'hospital_ids' => '병의원 목록',
            'hospital_ids.*' => '병의원',
            'message_parts' => '문자 내용',
            'message_parts.*.type' => '문자 구성 유형',
            'message_parts.*.text' => '문자 텍스트',
            'message_parts.*.key' => '문자 변수',
            'send_to_manager' => '담당자 발송 여부',
            'send_to_representative' => '대표자 발송 여부',
            'idempotency_key' => '중복 발송 방지 키',
        ];
    }

    /**
     * @return list<int>
     */
    private function normalizeIdList(mixed $value): array
    {
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
