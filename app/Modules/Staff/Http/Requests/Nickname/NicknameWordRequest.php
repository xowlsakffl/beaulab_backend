<?php

namespace App\Modules\Staff\Http\Requests\Nickname;

use App\Common\Authorization\AccessPermissions;
use App\Domains\Nickname\Models\NicknameWord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class NicknameWordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(AccessPermissions::BEAULAB_NICKNAME_MANAGE)
            || ($this->isMethod('GET') && $this->user()?->can(AccessPermissions::BEAULAB_NICKNAME_SHOW));
    }

    public function rules(): array
    {
        if ($this->routeIs('*.nickname-words.preview')) {
            return [];
        }
        $rules = ['type' => ['required', Rule::in(NicknameWord::TYPES)]];
        if ($this->isMethod('GET')) {
            return $rules + [
                'q' => ['nullable', 'string', 'max:40'],
                'status' => ['nullable', Rule::in(NicknameWord::STATUSES)],
                'page' => ['sometimes', 'integer', 'min:1', 'max:100000'],
            ];
        }
        if ($this->isMethod('PUT')) {
            return $rules + [
                'operation' => ['required', Rule::in(['edit', 'enable', 'disable', 'delete'])],
                'items' => ['required', 'array', 'min:1', $this->input('operation') === 'edit' ? 'size:1' : 'max:200'],
                'items.*.id' => ['required', 'integer', 'min:1', 'distinct'],
                'items.*.lock_version' => ['required', 'integer', 'min:0'],
                'word' => ['required_if:operation,edit', 'string', 'max:100'],
            ];
        }

        return $rules + ['text' => ['required', 'string', 'max:10000']];
    }

    public function attributes(): array
    {
        return ['word' => '단어', 'text' => '단어', 'type' => '단어 종류', 'items' => '선택한 단어'];
    }
}
