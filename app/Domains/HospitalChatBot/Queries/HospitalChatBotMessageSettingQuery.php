<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Queries;

use App\Domains\HospitalChatBot\Models\HospitalChatBotMessageSetting;

final class HospitalChatBotMessageSettingQuery
{
    public function current(): HospitalChatBotMessageSetting
    {
        return HospitalChatBotMessageSetting::query()->find(1)
            ?? new HospitalChatBotMessageSetting(HospitalChatBotMessageSetting::defaults());
    }

    public function save(array $values, int $staffId): HospitalChatBotMessageSetting
    {
        // 고정 PK로 최초 동시 저장에서도 설정이 여러 행으로 나뉘지 않게 한다.
        HospitalChatBotMessageSetting::query()->firstOrCreate(['id' => 1], HospitalChatBotMessageSetting::defaults());
        $setting = HospitalChatBotMessageSetting::query()->lockForUpdate()->findOrFail(1);
        $setting->fill([...$values, 'updated_by_staff_id' => $staffId])->save();

        return $setting;
    }
}
