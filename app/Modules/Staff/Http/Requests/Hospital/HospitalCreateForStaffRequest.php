<?php

namespace App\Modules\Staff\Http\Requests\Hospital;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Category\Models\CategoryUsage;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalFeature\Models\HospitalFeature;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * HospitalCreateForStaffRequest 역할 정의.
 * 병원 도메인의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class HospitalCreateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $businessNumber = $this->input('business_number');
        $categoryIds = $this->normalizeIdList($this->input('category_ids'));
        $featureIds = $this->normalizeIdList($this->input('feature_ids'));
        $operationHours = $this->normalizeOperationHours($this->input('operation_hours'));

        $mergePayload = [];

        if (is_string($businessNumber)) {
            $normalizedBusinessNumber = preg_replace('/\D+/', '', $businessNumber);
            $mergePayload['business_number'] = $normalizedBusinessNumber !== '' ? $normalizedBusinessNumber : $businessNumber;
        }

        if ($this->has('category_ids')) {
            $mergePayload['category_ids'] = array_values(array_unique($categoryIds));
        }

        if ($this->has('feature_ids')) {
            $mergePayload['feature_ids'] = array_values(array_unique($featureIds));
        }

        if ($this->has('operation_hours')) {
            $mergePayload['operation_hours'] = $operationHours;
        }

        if ($mergePayload !== []) {
            $this->merge($mergePayload);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:hospitals,name'],
            'department' => ['nullable', 'string', Rule::in(Hospital::departments())],
            'description' => ['nullable', 'string', 'max:5000'],
            'youtube_link' => ['nullable', 'url:http,https', 'max:500'],
            'consulting_hours' => ['nullable', 'string', 'max:5000'],
            'direction' => ['nullable', 'string', 'max:5000'],
            'address' => ['required', 'string', 'max:255'],
            'address_detail' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'tel' => ['required', 'string', 'max:50', 'regex:/^[0-9+\-().\s]{6,50}$/'],
            'ad_reception_phone_1' => ['required', 'string', 'max:50', 'regex:/^[0-9+\-().\s]{6,50}$/'],
            'ad_reception_phone_2' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+\-().\s]{6,50}$/'],
            'ad_reception_phone_3' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+\-().\s]{6,50}$/'],
            'email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'operation_hours' => ['required', 'array'],
            'operation_hours.*.is_closed' => ['required', 'boolean'],
            'operation_hours.*.start' => ['nullable', 'date_format:H:i'],
            'operation_hours.*.end' => ['nullable', 'date_format:H:i'],
            'allow_status' => ['required', Rule::in(Hospital::allowStatuses())],
            'status' => ['required', Rule::in([Hospital::STATUS_ACTIVE, Hospital::STATUS_SUSPENDED, Hospital::STATUS_WITHDRAWN])],

            'business_number' => ['required', 'string', 'max:20', 'unique:hospital_business_registrations,business_number'],
            'company_name' => ['required', 'string', 'max:255'],
            'ceo_name' => ['required', 'string', 'max:100'],
            'business_type' => ['required', 'string', 'max:100'],
            'business_item' => ['required', 'string', 'max:100'],
            'business_registration_file' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'business_address' => ['nullable', 'string', 'max:255'],
            'business_address_detail' => ['nullable', 'string', 'max:255'],
            'settlement_bank_name' => ['nullable', 'string', 'max:50'],
            'settlement_account_number' => ['nullable', 'string', 'max:50', 'regex:/^[0-9\-\s]{2,50}$/'],
            'settlement_account_holder' => ['nullable', 'string', 'max:100'],
            'tax_invoice_email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'issued_at' => ['nullable', 'date'],

            'category_ids' => ['nullable', 'array', 'min:1', 'max:5'],
            'category_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('categories', 'id')->where(static function ($query): void {
                    CategoryUsage::constrainActiveCategoryExists($query, CategoryUsage::USAGE_HOSPITAL_DOCTOR_SUBJECT);
                }),
            ],
            'feature_ids' => ['required', 'array', 'min:1', 'max:100'],
            'feature_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('hospital_features', 'id')->where(static fn ($query) => $query
                    ->where('status', HospitalFeature::STATUS_ACTIVE)),
            ],

            'logo' => ['required', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:ratio=1/1'],
            'gallery' => ['required', 'array', 'min:1', 'max:5'],
            'gallery.*' => ['file', 'image', 'mimes:jpg,jpeg,png', 'max:10240', 'dimensions:width=760,height=490'],
        ];
    }

    public function messages(): array
    {
        return [
            'logo.max' => '5MB 이하의 파일만 업로드 가능합니다.',
            'logo.dimensions' => '1:1비율의 이미지로 업로드 가능합니다.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => '병의원명',
            'department' => '분과',
            'description' => '병의원 소개',
            'youtube_link' => '유튜브 링크',
            'consulting_hours' => '상담 가능 시간',
            'direction' => '찾아오는 길',
            'address' => '주소',
            'address_detail' => '상세 주소',
            'latitude' => '위도',
            'longitude' => '경도',
            'tel' => '대표 번호',
            'ad_reception_phone_1' => '광고 수신 접수 전화번호 1',
            'ad_reception_phone_2' => '광고 수신 접수 전화번호 2',
            'ad_reception_phone_3' => '광고 수신 접수 전화번호 3',
            'email' => '대표 이메일',
            'operation_hours' => '진료시간',
            'operation_hours.*.is_closed' => '진료 여부',
            'operation_hours.*.start' => '진료 시작 시간',
            'operation_hours.*.end' => '진료 종료 시간',
            'allow_status' => '검수 상태',
            'status' => '운영 상태',
            'business_number' => '사업자등록번호',
            'company_name' => '상호명',
            'ceo_name' => '대표자',
            'business_type' => '업태',
            'business_item' => '종목',
            'business_registration_file' => '사업자등록증 파일',
            'business_address' => '사업장 주소',
            'business_address_detail' => '사업장 상세 주소',
            'settlement_bank_name' => '정산 은행명',
            'settlement_account_number' => '정산 계좌번호',
            'settlement_account_holder' => '정산 예금주명',
            'tax_invoice_email' => '세금계산서 이메일',
            'issued_at' => '사업자등록일',
            'category_ids' => '진료과목 목록',
            'category_ids.*' => '진료과목',
            'feature_ids' => '병원 특징 목록',
            'feature_ids.*' => '병원 특징',
            'logo' => '로고',
            'gallery' => '대표/내부 이미지',
            'gallery.*' => '대표/내부 이미지',
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            $this->validateYoutubeLink($validator);
            $this->validateOperationHours($validator);
        });
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
            ->values()
            ->all();
    }

    private function validateYoutubeLink(\Illuminate\Validation\Validator $validator): void
    {
        $value = $this->input('youtube_link');

        if ($value === null || $value === '') {
            return;
        }

        if (! is_string($value)) {
            return;
        }

        $host = parse_url($value, PHP_URL_HOST);

        if (! is_string($host)) {
            return;
        }

        $host = strtolower(preg_replace('/^www\./', '', $host));

        if ($host !== 'youtube.com' && $host !== 'youtu.be' && ! str_ends_with($host, '.youtube.com')) {
            $validator->errors()->add('youtube_link', '유튜브 링크 형식이 올바르지 않습니다.');
        }
    }

    private function normalizeOperationHours(mixed $value): mixed
    {
        if (is_string($value)) {
            $decoded = json_decode(trim($value), true);
            $value = json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
        }

        if (! is_array($value)) {
            return $value;
        }

        return collect($value)
            ->map(static function (mixed $item): mixed {
                if (! is_array($item)) {
                    return $item;
                }

                if (array_key_exists('is_closed', $item)) {
                    $item['is_closed'] = filter_var($item['is_closed'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
                }

                foreach (['start', 'end'] as $field) {
                    if (array_key_exists($field, $item) && is_string($item[$field])) {
                        $item[$field] = trim($item[$field]) === '' ? null : trim($item[$field]);
                    }
                }

                return $item;
            })
            ->all();
    }

    private function validateOperationHours(\Illuminate\Validation\Validator $validator): void
    {
        $operationHours = $this->input('operation_hours');

        if (! is_array($operationHours)) {
            return;
        }

        foreach (['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'] as $day) {
            $hours = $operationHours[$day] ?? null;

            if (! is_array($hours)) {
                $validator->errors()->add("operation_hours.{$day}", '요일별 진료시간을 모두 입력해주세요.');
                continue;
            }

            $isClosed = (bool) ($hours['is_closed'] ?? false);
            $start = $hours['start'] ?? null;
            $end = $hours['end'] ?? null;

            if ($isClosed) {
                continue;
            }

            if (! is_string($start) || $start === '') {
                $validator->errors()->add("operation_hours.{$day}.start", '진료 시작 시간을 입력해주세요.');
            }

            if (! is_string($end) || $end === '') {
                $validator->errors()->add("operation_hours.{$day}.end", '진료 종료 시간을 입력해주세요.');
            }

            if (is_string($start) && is_string($end) && $start !== '' && $end !== '' && $start >= $end) {
                $validator->errors()->add("operation_hours.{$day}.end", '진료 종료 시간은 시작 시간보다 늦어야 합니다.');
            }
        }
    }
}
