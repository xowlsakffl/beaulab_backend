<?php

namespace App\Modules\Staff\Http\Requests\HarmfulWord;

use App\Common\Authorization\AccessPermissions;
use App\Domains\Common\HarmfulWord\Models\HarmfulWord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HarmfulWordRequest extends FormRequest
{
    public function authorize(): bool
    {
        $read = $this->isMethod('GET') || $this->routeIs('*.harmful-words.check');

        return $this->user()?->can(AccessPermissions::BEAULAB_HARMFUL_WORD_MANAGE)
            || ($read && $this->user()?->can(AccessPermissions::BEAULAB_HARMFUL_WORD_SHOW));
    }

    public function rules(): array
    {
        if ($this->isMethod('GET')) {
            return [
                'q' => ['nullable', 'string', 'max:100'],
                'scopes' => ['sometimes', 'array', 'max:5'],
                'scopes.*' => [Rule::in(HarmfulWord::SCOPES)],
                'statuses' => ['sometimes', 'array', 'max:2'],
                'statuses.*' => [Rule::in(HarmfulWord::STATUSES)],
                'page' => ['sometimes', 'integer', 'min:1', 'max:100000'],
            ];
        }
        if ($this->routeIs('*.harmful-words.check')) {
            return ['scope' => ['required', Rule::in(HarmfulWord::SCOPES)], 'text' => ['required', 'string', 'max:20000']];
        }
        if ($this->routeIs('*.harmful-words.validate')) {
            return ['text' => ['required', 'string', 'max:20000']];
        }
        $settings = [
            'matching_mode' => ['required', Rule::in(HarmfulWord::MODES)],
            'detect_variants' => ['required', 'boolean'],
            'status' => ['required', Rule::in(HarmfulWord::STATUSES)],
            'scopes' => ['required', 'array', 'min:1', 'max:5'],
            'scopes.*' => ['required', 'distinct', Rule::in(HarmfulWord::SCOPES)],
            'exceptions' => ['sometimes', 'array', 'max:20'],
            'exceptions.*' => ['required', 'string', 'max:100'],
        ];
        if ($this->isMethod('PUT')) {
            $edit = $this->input('operation') === 'edit';

            return [
                'operation' => ['required', Rule::in(['edit', 'enable', 'disable', 'delete'])],
                'items' => ['required', 'array', 'min:1', $edit ? 'size:1' : 'max:50'],
                'items.*.id' => ['required', 'integer', 'min:1', 'distinct'],
                'items.*.lock_version' => ['required', 'integer', 'min:0'],
            ] + ($edit ? $settings + ['word' => ['required', 'string', 'max:50']] : []);
        }

        return $settings + ['text' => ['required', 'string', 'max:20000']];
    }

    public function attributes(): array
    {
        return ['text' => '단어', 'word' => '금지어', 'scopes' => '적용 영역', 'exceptions' => '예외 표현', 'exceptions.*' => '예외 표현', 'matching_mode' => '일치 방식', 'items' => '선택한 금지어'];
    }
}
