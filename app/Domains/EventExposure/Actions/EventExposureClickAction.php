<?php

namespace App\Domains\EventExposure\Actions;

use App\Domains\EventExposure\Queries\EventExposureQuery;
use Illuminate\Support\Facades\DB;

final class EventExposureClickAction
{
    public function __construct(private readonly EventExposureQuery $query) {}

    public function execute(string $placement, ?int $categoryId, string $sourceKey): void
    {
        $scope = $this->query->scope($placement, $categoryId);
        abort_unless($this->query->entries($scope, now())->where('active', 1)->where('source_key', $sourceKey)->exists(), 404);
        DB::table('event_exposure_metrics')->upsert(
            [['position_key' => $scope['key'], 'source_key' => $sourceKey, 'click_count' => 1]],
            ['position_key', 'source_key'],
            ['click_count' => DB::raw('click_count + 1')],
        );
    }
}
