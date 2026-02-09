<?php

namespace App\Domains\Common\Queries\Media;

use App\Domains\Common\Models\Media\Media;
use Illuminate\Database\Eloquent\Model;

final class MediaAttachQuery
{
    public function create(array $data): Media
    {
        return Media::create($data);
    }

    public function clearPrimary(Model $owner, string $collection): int
    {
        return Media::query()
            ->where('model_type', $owner::class)
            ->where('model_id', $owner->getKey())
            ->where('collection', $collection)
            ->where('is_primary', true)
            ->update(['is_primary' => false]);
    }
}
