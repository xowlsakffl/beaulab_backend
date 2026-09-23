<?php

declare(strict_types=1);

namespace App\Domains\HospitalChatBot\Actions\Staff;

use App\Domains\HospitalChatBot\Dto\Staff\HospitalChatBotQnaFolderForStaffDto;
use App\Domains\HospitalChatBot\Queries\Staff\HospitalChatBotQnaFolderForStaffQuery;
use App\Domains\HospitalChatBot\Support\HospitalChatBotStaffActor;

final class HospitalChatBotQnaFolderForStaffAction
{
    public function __construct(private readonly HospitalChatBotQnaFolderForStaffQuery $query) {}

    /** @return list<array<string, mixed>> */
    public function index(): array
    {
        return HospitalChatBotQnaFolderForStaffDto::tree($this->query->all());
    }

    /** @return list<array<string, mixed>> */
    public function store(array $payload): array
    {
        $this->query->create($payload, (int) HospitalChatBotStaffActor::current()->id);

        return $this->index();
    }

    /** @return list<array<string, mixed>> */
    public function update(int $folderId, array $payload): array
    {
        $this->query->update(
            $this->query->find($folderId),
            $payload,
            (int) HospitalChatBotStaffActor::current()->id,
        );

        return $this->index();
    }

    /** @return list<array<string, mixed>> */
    public function destroy(int $folderId): array
    {
        $this->query->delete($this->query->find($folderId));

        return $this->index();
    }
}
