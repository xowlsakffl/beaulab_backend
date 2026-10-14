<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Staff;

use App\Common\Authorization\AccessPermissions as P;
use App\Domains\HospitalChatBot\Dto\Staff\HospitalChatBotMessageSettingForStaffDto;
use App\Domains\HospitalChatBot\Queries\HospitalChatBotMessageSettingQuery;
use App\Domains\HospitalChatBot\Support\HospitalChatBotStaffActor;
use Illuminate\Support\Facades\DB;

final class HospitalChatBotMessageSettingForStaffAction
{
    public function __construct(private readonly HospitalChatBotMessageSettingQuery $query) {}

    public function show(): array
    {
        HospitalChatBotStaffActor::authorize(P::BEAULAB_CHATBOT_OPERATION_SHOW);

        return HospitalChatBotMessageSettingForStaffDto::fromModel($this->query->current());
    }

    public function update(array $values, int $staffId): array
    {
        $staffId = (int) HospitalChatBotStaffActor::authorize(P::BEAULAB_CHATBOT_OPERATION_SHOW, P::BEAULAB_CHATBOT_OPERATION_UPDATE)->id;

        return DB::transaction(fn (): array => HospitalChatBotMessageSettingForStaffDto::fromModel(
            $this->query->save($values, $staffId),
        ));
    }

    public function macros(): array
    {
        HospitalChatBotStaffActor::authorize(P::BEAULAB_CHATBOT_CONSULTATION_SHOW, P::BEAULAB_CHATBOT_CONSULTATION_PROCESS);

        return ['macros' => HospitalChatBotMessageSettingForStaffDto::fromModel($this->query->current())['macros']];
    }
}
