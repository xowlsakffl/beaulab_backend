<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\HospitalChatBot;

use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalChatBotQnaCreateForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'folder_id' => ['prohibited'],
            'parent_id' => ['nullable', 'integer', 'min:1'],
            'question' => ['required', 'string', 'max:255'],
            'answer' => ['nullable', 'string', 'max:10000'],
            'status' => ['sometimes', 'string', Rule::in(HospitalChatBotQna::statuses())],
            'sort_order' => ['sometimes', 'integer', 'min:0', 'max:4294967295'],
        ];
    }

    public function messages(): array
    {
        return ['folder_id.prohibited' => '폴더 대신 상위 Q&A 항목을 선택해 주세요.'];
    }
}
