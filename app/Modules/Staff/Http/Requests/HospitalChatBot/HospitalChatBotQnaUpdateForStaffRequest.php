<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\HospitalChatBot;

use App\Domains\HospitalChatBot\Models\HospitalChatBotQna;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalChatBotQnaUpdateForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'folder_id' => ['sometimes', 'required', 'integer', 'exists:hospital_chatbot_qna_folders,id'],
            'question' => ['sometimes', 'required', 'string', 'max:255'],
            'answer' => ['sometimes', 'required', 'string', 'max:10000'],
            'status' => ['sometimes', 'required', 'string', Rule::in(HospitalChatBotQna::statuses())],
            'sort_order' => ['sometimes', 'required', 'integer', 'min:0'],
        ];
    }
}
