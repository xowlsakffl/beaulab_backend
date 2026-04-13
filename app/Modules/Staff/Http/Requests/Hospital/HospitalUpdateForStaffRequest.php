<?php

namespace App\Modules\Staff\Http\Requests\Hospital;

use App\Domains\Common\Models\Category\Category;
use App\Domains\Common\Models\Media\Media;
use App\Domains\Hospital\Models\Hospital;
use App\Domains\HospitalBusinessRegistration\Models\HospitalBusinessRegistration;
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
            'consulting_hours',
            'direction',
            'address',
            'address_detail',
            'latitude',
            'longitude',
            'tel',
            'email',
            'allow_status',
            'status',
            'business_number',
            'company_name',
            'ceo_name',
            'business_type',
            'business_item',
            'business_address',
            'business_address_detail',
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
            'consulting_hours' => ['nullable', 'string', 'max:5000'],
            'direction' => ['nullable', 'string', 'max:5000'],
            'address' => ['nullable', 'string', 'max:255'],
            'address_detail' => ['nullable', 'string', 'max:255'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'tel' => ['nullable', 'string', 'max:50', 'regex:/^[0-9+\-().\s]{6,50}$/'],
            'email' => ['nullable', 'email:rfc,dns', 'max:255'],
            'allow_status' => ['nullable', Rule::in([Hospital::ALLOW_PENDING, Hospital::ALLOW_APPROVED, Hospital::ALLOW_REJECTED])],
            'status' => ['nullable', Rule::in([Hospital::STATUS_ACTIVE, Hospital::STATUS_SUSPENDED, Hospital::STATUS_WITHDRAWN])],
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
            'category_ids' => ['sometimes', 'array', 'max:100'],
            'category_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('categories', 'id')->where(static fn ($query) => $query
                    ->whereIn('domain', [Category::DOMAIN_HOSPITAL_TREATMENT, Category::DOMAIN_HOSPITAL_SURGERY])
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
            'consulting_hours' => '상담 가능 시간',
            'direction' => '찾아오는 길',
            'address' => '주소',
            'address_detail' => '상세 주소',
            'latitude' => '위도',
            'longitude' => '경도',
            'tel' => '대표 번호',
            'email' => '대표 이메일',
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
            'issued_at' => '사업자등록일',
            'category_ids' => '카테고리 목록',
            'category_ids.*' => '카테고리',
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

    /**
     * @return array<int, int>
     */
    private function normalizeIdList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }

        if (is_string($value)) {
            $value = explode(',', $value);
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
