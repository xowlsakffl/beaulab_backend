<?php

declare(strict_types=1);

namespace App\Modules\Staff\Http\Requests\HospitalChatBot;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

final class HospitalChatBotMessageSettingForStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'welcome_message' => [Rule::requiredIf(! $this->exists('macros') || $this->exists('connection_message')), 'string', 'max:2000'],
            'connection_message' => [Rule::requiredIf(! $this->exists('macros') || $this->exists('welcome_message')), 'string', 'max:1000'],
            'macros' => ['sometimes', 'array', 'max:50'],
            'macros.*' => ['array:shortcut,content'],
            'macros.*.shortcut' => ['required', 'string', 'max:30', 'regex:/^[가-힣a-zA-Z0-9_-]+$/u', 'distinct:ignore_case'],
            'macros.*.content' => ['required', 'string', 'max:2000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'welcome_message' => '챗봇 첫 안내',
            'connection_message' => '상담 연결 인사',
            'macros.*.shortcut' => '매크로 명령어',
            'macros.*.content' => '매크로 내용',
        ];
    }

    public function messages(): array
    {
        return [
            'macros.*.shortcut.regex' => '명령어는 한글, 영문, 숫자, 밑줄, 하이픈만 사용할 수 있습니다.',
            'macros.*.shortcut.distinct' => '같은 매크로 명령어를 중복 등록할 수 없습니다.',
        ];
    }
}
