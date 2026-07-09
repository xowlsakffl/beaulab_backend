<?php

namespace App\Modules\Hospital\Http\Requests\HospitalVideo;

use App\Domains\HospitalVideo\Models\HospitalVideo;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalVideoStatusUpdateForHospitalRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (array_key_exists('hospital_status', $data) && $data['hospital_status'] === '') {
            $data['hospital_status'] = null;
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
            'hospital_status' => ['required', Rule::in(HospitalVideo::hospitalStatuses())],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'hospital_status' => '공개여부',
        ];
    }
}
