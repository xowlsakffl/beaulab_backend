<?php

namespace App\Modules\Staff\Http\Requests\Expert;

use Illuminate\Foundation\Http\FormRequest;

final class ExpertUpdateForStaffRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        foreach (['gender', 'position', 'career_started_at'] as $key) {
            if (array_key_exists($key, $data) && $data[$key] === '') {
                $data[$key] = null;
            }
        }

        foreach (['educations', 'careers', 'etc_contents'] as $key) {
            if (array_key_exists($key, $data) && is_string($data[$key])) {
                $decoded = json_decode($data[$key], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $data[$key] = $decoded;
                }
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
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'name' => ['nullable', 'string', 'max:255'],
            'gender' => ['nullable', 'string', 'max:20'],
            'position' => ['nullable', 'string', 'max:50'],
            'career_started_at' => ['nullable', 'date'],
            'educations' => ['nullable', 'array'],
            'careers' => ['nullable', 'array'],
            'etc_contents' => ['nullable', 'array'],
            'status' => ['nullable', 'in:ACTIVE,SUSPENDED,INACTIVE'],
            'allow_status' => ['nullable', 'in:PENDING,APPROVED,REJECTED'],

            'profile_image' => ['nullable', 'file', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'education_certificate_image' => ['nullable', 'array', 'max:12'],
            'education_certificate_image.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
            'etc_certificate_image' => ['nullable', 'array', 'max:12'],
            'etc_certificate_image.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:10240'],
        ];
    }
}
