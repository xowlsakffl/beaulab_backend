<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Dto\Staff;

use App\Domains\HospitalChatBot\Models\HospitalChatBotMessageSetting;

final readonly class HospitalChatBotMessageSettingForStaffDto
{
    public static function fromModel(HospitalChatBotMessageSetting $setting): array
    {
        return [
            'welcome_message' => $setting->welcome_message,
            'connection_message' => $setting->connection_message,
            'macros' => $setting->macros,
        ];
    }
}
