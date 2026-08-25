<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\AccountHospital;

final class HospitalAccountInvitationListForStaffRequest extends HospitalAccountInvitationSourceForStaffRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:20'],
        ];
    }

    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'page' => '페이지',
            'per_page' => '페이지당 개수',
        ];
    }

    public function filters(): array
    {
        $validated = $this->validated();

        return [
            'source_type' => $validated['source_type'],
            'source_id' => (int) $validated['source_id'],
            'per_page' => (int) ($validated['per_page'] ?? 5),
        ];
    }
}
