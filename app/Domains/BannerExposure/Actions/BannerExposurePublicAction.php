<?php

namespace App\Domains\BannerExposure\Actions;

use App\Domains\BannerExposure\Dto\BannerExposureDto;
use App\Domains\BannerExposure\Queries\BannerExposureQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

final class BannerExposurePublicAction
{
    public function __construct(private readonly BannerExposureQuery $query) {}

    public function listing(string $placement): array
    {
        $now = now()->startOfSecond();
        $keys = $this->query->activeKeys($placement, $now);
        $entries = $this->query->entries($placement, $now)->where('active', 1)->get()
            ->sortBy(fn ($entry) => array_search($entry->source_key, $keys, true))->values();

        return array_map(fn ($row) => [
            'key' => $row['source_key'], 'title' => $row['title'], 'image' => $row['image'],
            'landing' => ['type' => $row['target_type'], 'id' => $row['target_id'], 'url' => $row['external_url'], 'open_mode' => $row['open_mode']],
            'click_url' => URL::temporarySignedRoute('user.banner-exposures.click', now()->addMinutes(30), ['placement' => $placement, 'sourceKey' => $row['source_key']], absolute: false),
        ], BannerExposureDto::rows($entries, $now));
    }

    public function click(string $placement, string $key): void
    {
        abort_unless($this->query->entries($placement, now())->where('active', 1)->where('source_key', $key)->exists(), 404);
        DB::table('banner_exposure_metrics')->upsert(
            [['source_key' => $key, 'click_count' => 1]], ['source_key'], ['click_count' => DB::raw('click_count + 1')],
        );
    }
}
