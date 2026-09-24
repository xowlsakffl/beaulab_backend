<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\HospitalChatBot;

use Illuminate\Foundation\Http\FormRequest;

final class HospitalChatBotQnaFolderMoveForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 비우면 대분류로 올린다. 화면에서 트리 맨 바깥에 놓은 경우다.
            'parent_id' => ['present', 'nullable', 'integer', 'exists:hospital_chatbot_qna_folders,id'],
            // 형제 목록에서 자기 자신을 뺀 0-based 자리. 끝에 놓으면 형제 수와 같다.
            'position' => ['required', 'integer', 'min:0'],
        ];
    }

    /** @return array{parent_id:?int, position:int} */
    public function payload(): array
    {
        $validated = $this->validated();
        $parentId = $validated['parent_id'] ?? null;

        return [
            'parent_id' => $parentId !== null ? (int) $parentId : null,
            'position' => (int) $validated['position'],
        ];
    }
}
