<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\HospitalChatBot;

use Illuminate\Foundation\Http\FormRequest;

final class HospitalChatBotQnaFolderForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            // 생성에서만 쓴다. 수정은 상위를 바꾸지 않으므로 라우트에서 막는다.
            'parent_id' => ['nullable', 'integer', 'exists:hospital_chatbot_qna_folders,id', 'prohibited_if:_method,PATCH,PUT'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    /** @return array{name:string, parent_id:?int, sort_order:int} */
    public function payload(): array
    {
        $validated = $this->validated();

        return [
            'name' => trim((string) $validated['name']),
            'parent_id' => isset($validated['parent_id']) ? (int) $validated['parent_id'] : null,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ];
    }
}
