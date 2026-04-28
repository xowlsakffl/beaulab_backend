<?php

namespace App\Domains\Common\Hashtag\Dto\Staff;

use App\Domains\Common\Hashtag\Models\Hashtag;
use Illuminate\Support\Facades\DB;

/**
 * HashtagForStaffDto DTO.
 */
final readonly class HashtagForStaffDto
{
    public function __construct(
        public int $id,
        public string $name,
        public string $normalizedName,
        public string $status,
        public int $usageCount,
        public int $assignmentCount,
        public ?string $createdAt,
        public ?string $updatedAt,
    ) {}

    public static function fromModel(Hashtag $hashtag): self
    {
        $assignmentCount = self::assignmentCount($hashtag);

        return new self(
            id: (int) $hashtag->id,
            name: (string) $hashtag->name,
            normalizedName: (string) $hashtag->normalized_name,
            status: $hashtag->resolveStatus(),
            usageCount: $hashtag->resolveUsageCount($assignmentCount),
            assignmentCount: $assignmentCount,
            createdAt: $hashtag->created_at?->toISOString(),
            updatedAt: $hashtag->updated_at?->toISOString(),
        );
    }

    public function toArray(): array
    {
        $data = [
            'id' => $this->id,
            'name' => $this->name,
            'normalized_name' => $this->normalizedName,
            'status' => $this->status,
            'usage_count' => $this->usageCount,
            'assignment_count' => $this->assignmentCount,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt,
        ];

        return $data;
    }

    private static function assignmentCount(Hashtag $hashtag): int
    {
        if (array_key_exists('assignment_count', $hashtag->getAttributes())) {
            return (int) ($hashtag->getAttribute('assignment_count') ?? 0);
        }

        return (int) DB::table('hashtaggables')
            ->where('hashtag_id', $hashtag->id)
            ->count();
    }
}
