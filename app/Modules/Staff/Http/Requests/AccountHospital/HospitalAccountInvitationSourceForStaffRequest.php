<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\AccountHospital;

use App\Domains\AccountHospital\Models\HospitalAccountInvitation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class HospitalAccountInvitationSourceForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'source_type' => ['required', 'string', Rule::in(HospitalAccountInvitation::sourceTypes())],
            'source_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function attributes(): array
    {
        return [
            'source_type' => '초대 원본 유형',
            'source_id' => '초대 원본 ID',
        ];
    }

    public function filters(): array
    {
        return $this->validated();
    }
}
