<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\AccountHospital;

final class HospitalAccountInvitationSendForStaffRequest extends HospitalAccountInvitationSourceForStaffRequest
{
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'recipient_email' => ['required', 'email:rfc', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            ...parent::attributes(),
            'recipient_email' => '초대 이메일',
        ];
    }
}
