<?php

namespace App\Modules\Staff\Http\Requests\Hospital;

use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Media\Models\Media;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\Hospital\Models\HospitalBusinessRegistration;
use App\Domains\HospitalFeature\Models\HospitalFeature;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * HospitalUpdateForStaffRequest 역할 정의.
 * 병원 도메인의 HTTP 요청 검증 객체로, 요청 입력값의 정규화, validation rule, 사용자용 필드명을 정의한다.
 */
final class HospitalUpdateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        $nullableKeys = [
            'description',
            'department',
            'consulting_hours',
            'direction',
            'address',
            'address_detail',
            'latitude',
            'longitude',
            'tel',
            'ad_reception_phone_1',
            'ad_reception_phone_2',
            'ad_reception_phone_3',
            'email',
            'operation_hours',
            'allow_status',
            'status',
            'status_change_reason',
            'business_number',
            'company_name',
            'ceo_name',
            'business_type',
            'business_item',
            'business_address',
            'business_address_detail',
            'settlement_bank_name',
            'settlement_account_number',
            'settlement_account_holder',
            'tax_invoice_email',
            'issued_at',
        ];

        foreach ($nullableKeys as $key) {
            if (array_key_exists($key, $data) && $data[$key] === '') {
                $data[$key] = null;
            }
        }

        if (isset($data['email']) && is_string($data['email'])) {
            $data['email'] = mb_strtolower($data['email']);
        }

        if (isset($data['business_number']) && is_string($data['business_number'])) {
            $normalizedBusinessNumber = preg_replace('/\D+/', '', $data['business_number']);
            $data['business_number'] = $normalizedBusinessNumber !== '' ? $normalizedBusinessNumber : $data['business_number'];
        }

        if (array_key_exists('operation_hours', $data)) {
            $data['operation_hours'] = $this->normalizeOperationHours($data['operation_hours']);
        }

        if (array_key_exists('category_ids', $data)) {
            $data['category_ids'] = $this->normalizeIdList($data['category_ids']);
        }

        if (array_key_exists('feature_ids', $data)) {
            $data['feature_ids'] = $this->normalizeIdList($data['feature_ids']);
        }

        if (array_key_exists('existing_gallery_ids', $data)) {
            $data['existing_gallery_ids'] = $this->normalizeIdList($data['existing_gallery_ids']);
        }

        if (array_key_exists('gallery_order', $data)) {
            $data['gallery_order'] = $this->normalizeStringList($data['gallery_order']);
        }

        foreach (['existing_logo_id', 'existing_business_registration_file_id'] as $key) {
            if (array_key_exists($key, $data) && $data[$key] === '') {
                $data[$key] = null;
            }
        }

        $this->replace($data);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['nullable', 'string', 'max:5000'],
            'department' => ['nullable', 'string', Rule::in(Hospital::departments())],
            'consulting_hours' => ['nullable', 'string', 'max:5000'],
            'direction' => ['nullable', 'string', 'max:5000'],
            'address' => ['required', 'string', 'max:255'],
            'address_detail' => ['required', 'string', 'max:255'],
            'latitude' => ['required', 'numeric', 'between:-90,90'],
            'longitude' => ['required', 'numeric', 'between:-180,180'],
            'tel' => ['required', 'string', 'max:50', 'regex:/^[0-9+\-().\s]{6,50}$/'],
            'ad_reception_phone_1' => ['sometimes', 'required', 'string', 'max:50', 'regex:/^[0-9+\-().\s]{6,50}$/'],
            'ad_reception_phone_2' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+\-().\s]{6,50}$/'],
            'ad_reception_phone_3' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+\-().\s]{6,50}$/'],
            'email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'operation_hours' => ['sometimes', 'array'],
            'operation_hours.*.is_closed' => ['required_with:operation_hours', 'boolean'],
            'operation_hours.*.start' => ['nullable', 'date_format:H:i'],
            'operation_hours.*.end' => ['nullable', 'date_format:H:i'],
            'allow_status' => ['nullable', Rule::in([Hospital::ALLOW_PENDING, Hospital::ALLOW_APPROVED, Hospital::ALLOW_REJECTED])],
            'status' => ['nullable', Rule::in([Hospital::STATUS_ACTIVE, Hospital::STATUS_SUSPENDED, Hospital::STATUS_WITHDRAWN])],
            'status_change_reason' => ['nullable', 'string', 'max:1000'],
            'business_number' => [
                'nullable',
                'string',
                'max:20',
                Rule::unique('hospital_business_registrations', 'business_number')->ignore($this->businessRegistrationId()),
            ],
            'company_name' => ['nullable', 'string', 'max:255'],
            'ceo_name' => ['nullable', 'string', 'max:100'],
            'business_type' => ['nullable', 'string', 'max:100'],
            'business_item' => ['nullable', 'string', 'max:100'],
            'issued_at' => ['nullable', 'date'],
            'business_registration_file' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'business_address' => ['nullable', 'string', 'max:255'],
            'business_address_detail' => ['nullable', 'string', 'max:255'],
            'settlement_bank_name' => ['nullable', 'string', 'max:50'],
            'settlement_account_number' => ['nullable', 'string', 'max:50', 'regex:/^[0-9\-\s]{2,50}$/'],
            'settlement_account_holder' => ['nullable', 'string', 'max:100'],
            'tax_invoice_email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'category_ids' => ['sometimes', 'array', 'max:5'],
            'category_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('categories', 'id')->where(static fn ($query) => $query
                    ->whereIn('domain', [Category::DOMAIN_HOSPITAL_REVIEW_TREATMENT, Category::DOMAIN_HOSPITAL_REVIEW_SURGERY])
                    ->whereNull('parent_id')
                    ->where('status', Category::STATUS_ACTIVE)),
            ],
            'feature_ids' => ['required', 'array', 'min:1', 'max:100'],
            'feature_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('hospital_features', 'id')->where(static fn ($query) => $query
                    ->where('status', HospitalFeature::STATUS_ACTIVE)),
            ],
            'existing_logo_id' => [
                'sometimes',
                'nullable',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $hospital = $this->route('hospital');

                    if (! $hospital instanceof Hospital) {
                        $fail('병의원 정보를 확인할 수 없습니다.');
                        return;
                    }

                    $exists = Media::query()
                        ->whereKey((int) $value)
                        ->where('model_type', Hospital::class)
                        ->where('model_id', $hospital->getKey())
                        ->where('collection', 'logo')
                        ->exists();

                    if (! $exists) {
                        $fail('선택한 로고 정보가 올바르지 않습니다.');
                    }
                },
            ],
            'existing_gallery_ids' => ['sometimes', 'array', 'max:12'],
            'existing_gallery_ids.*' => [
                'integer',
                'distinct',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $hospital = $this->route('hospital');

                    if (! $hospital instanceof Hospital) {
                        $fail('병의원 정보를 확인할 수 없습니다.');
                        return;
                    }

                    $exists = Media::query()
                        ->whereKey((int) $value)
                        ->where('model_type', Hospital::class)
                        ->where('model_id', $hospital->getKey())
                        ->where('collection', 'gallery')
                        ->exists();

                    if (! $exists) {
                        $fail('선택한 대표/내부 이미지 정보가 올바르지 않습니다.');
                    }
                },
            ],
            'gallery_order' => ['sometimes', 'array', 'max:12'],
            'gallery_order.*' => [
                'string',
                'distinct',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! is_string($value) || ! preg_match('/^(existing|new):(\d+)$/', $value, $matches)) {
                        $fail('대표/내부 이미지 순서 정보가 올바르지 않습니다.');
                        return;
                    }

                    if ($matches[1] !== 'existing') {
                        return;
                    }

                    $hospital = $this->route('hospital');

                    if (! $hospital instanceof Hospital) {
                        $fail('병의원 정보를 확인할 수 없습니다.');
                        return;
                    }

                    $exists = Media::query()
                        ->whereKey((int) $matches[2])
                        ->where('model_type', Hospital::class)
                        ->where('model_id', $hospital->getKey())
                        ->where('collection', 'gallery')
                        ->exists();

                    if (! $exists) {
                        $fail('선택한 대표/내부 이미지 정보가 올바르지 않습니다.');
                    }
                },
            ],
            'existing_business_registration_file_id' => [
                'sometimes',
                'nullable',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if ($value === null || $value === '') {
                        return;
                    }

                    $hospital = $this->route('hospital');

                    if (! $hospital instanceof Hospital) {
                        $fail('병의원 정보를 확인할 수 없습니다.');
                        return;
                    }

                    $businessRegistrationId = $hospital->businessRegistration()->value('id');

                    if (! $businessRegistrationId) {
                        $fail('사업자등록 정보를 확인할 수 없습니다.');
                        return;
                    }

                    $exists = Media::query()
                        ->whereKey((int) $value)
                        ->where('model_type', HospitalBusinessRegistration::class)
                        ->where('model_id', $businessRegistrationId)
                        ->where('collection', 'business_registration_file')
                        ->exists();

                    if (! $exists) {
                        $fail('선택한 사업자등록증 파일 정보가 올바르지 않습니다.');
                    }
                },
            ],
            'logo' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'gallery' => ['nullable', 'array', 'min:1', 'max:12'],
            'gallery.*' => ['file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
        ];
    }

    public function withValidator(\Illuminate\Validation\Validator $validator): void
    {
        $validator->after(function (\Illuminate\Validation\Validator $validator): void {
            $this->validateOperationHours($validator);
            $this->validateStatusReason($validator);

            $galleryOrder = $this->input('gallery_order');
            $uploadedGalleryFiles = $this->normalizeUploadedFiles($this->file('gallery'));

            if (is_array($galleryOrder)) {
                $parsedGalleryOrder = $this->parseGalleryOrder($galleryOrder);
                $newIndexes = $parsedGalleryOrder['new_indexes'];

                foreach ($newIndexes as $newIndex) {
                    if (! array_key_exists($newIndex, $uploadedGalleryFiles)) {
                        $validator->errors()->add('gallery', '새로 업로드한 이미지 순서 정보가 올바르지 않습니다.');
                        return;
                    }
                }

                if (count($newIndexes) !== count($uploadedGalleryFiles)) {
                    $validator->errors()->add('gallery', '새로 업로드한 대표/내부 이미지 순서 정보가 누락되었습니다.');
                    return;
                }

                if (count($parsedGalleryOrder['existing_ids']) + count($newIndexes) > 12) {
                    $validator->errors()->add('gallery', '대표/내부 이미지는 최대 12장까지 등록할 수 있습니다.');
                }

                return;
            }

            $keptGalleryCount = count($this->input('existing_gallery_ids', []));
            $newGalleryCount = count($uploadedGalleryFiles);

            if ($keptGalleryCount + $newGalleryCount > 12) {
                $validator->errors()->add('gallery', '대표/내부 이미지는 최대 12장까지 등록할 수 있습니다.');
            }
        });
    }

    public function attributes(): array
    {
        return [
            'name' => '병의원명',
            'description' => '병의원 소개',
            'department' => '분과',
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
            'status_change_reason' => '운영중지/탈퇴 사유',
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
            'existing_logo_id' => '기존 로고',
            'existing_gallery_ids' => '대표/내부 이미지 목록',
            'existing_gallery_ids.*' => '대표/내부 이미지',
            'gallery_order' => '대표/내부 이미지 순서',
            'gallery_order.*' => '대표/내부 이미지 순서',
            'existing_business_registration_file_id' => '기존 사업자등록증 파일',
            'logo' => '로고',
            'gallery' => '대표/내부 이미지',
            'gallery.*' => '대표/내부 이미지',
        ];
    }

    private function businessRegistrationId(): ?int
    {
        $hospital = $this->route('hospital');

        if (! $hospital instanceof Hospital) {
            return null;
        }

        return $hospital->businessRegistration()->value('id');
    }

    private function validateStatusReason(\Illuminate\Validation\Validator $validator): void
    {
        $hospital = $this->route('hospital');
        $status = $this->input('status');

        if ($status === null) {
            return;
        }

        if (! in_array($status, [Hospital::STATUS_SUSPENDED, Hospital::STATUS_WITHDRAWN], true)) {
            return;
        }

        if ($hospital instanceof Hospital && (string) $hospital->status === (string) $status) {
            return;
        }

        $statusReason = $this->input('status_change_reason');

        if (! is_string($statusReason) || trim($statusReason) === '') {
            $validator->errors()->add('status_change_reason', '운영중지 또는 탈퇴 상태에서는 사유를 입력해주세요.');
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
        if (! $this->has('operation_hours')) {
            return;
        }

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

    /**
     * @return array<int, string>
     */
    private function normalizeStringList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(static fn ($item): bool => is_string($item) && trim($item) !== '')
            ->map(static fn (string $item): string => trim($item))
            ->values()
            ->all();
    }

    /**
     * @return array<int, UploadedFile>
     */
    private function normalizeUploadedFiles(mixed $files): array
    {
        if ($files === null) {
            return [];
        }

        if ($files instanceof \Illuminate\Http\UploadedFile) {
            return [$files];
        }

        if (! is_array($files)) {
            return [];
        }

        return array_values(array_filter($files, static fn ($file): bool => $file instanceof \Illuminate\Http\UploadedFile));
    }

    /**
     * @param array<int, string> $galleryOrder
     * @return array{existing_ids: array<int, int>, new_indexes: array<int, int>}
     */
    private function parseGalleryOrder(array $galleryOrder): array
    {
        $existingIds = [];
        $newIndexes = [];

        foreach ($galleryOrder as $token) {
            if (! preg_match('/^(existing|new):(\d+)$/', $token, $matches)) {
                continue;
            }

            $parsedValue = (int) $matches[2];
            if ($matches[1] === 'existing') {
                $existingIds[] = $parsedValue;
                continue;
            }

            $newIndexes[] = $parsedValue;
        }

        return [
            'existing_ids' => $existingIds,
            'new_indexes' => $newIndexes,
        ];
    }
}
