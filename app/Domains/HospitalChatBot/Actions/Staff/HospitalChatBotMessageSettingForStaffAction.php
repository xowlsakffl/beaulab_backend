<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Staff;

use App\Domains\HospitalChatBot\Dto\Staff\HospitalChatBotMessageSettingForStaffDto;
use App\Domains\HospitalChatBot\Queries\HospitalChatBotMessageSettingQuery;
use Illuminate\Support\Facades\DB;

final class HospitalChatBotMessageSettingForStaffAction
{
    public function __construct(private readonly HospitalChatBotMessageSettingQuery $query) {}

    public function show(): array
    {
        return HospitalChatBotMessageSettingForStaffDto::fromModel($this->query->current());
    }

    public function update(array $values, int $staffId): array
    {
        return DB::transaction(fn (): array => HospitalChatBotMessageSettingForStaffDto::fromModel(
            $this->query->save($values, $staffId),
        ));
    }
}
