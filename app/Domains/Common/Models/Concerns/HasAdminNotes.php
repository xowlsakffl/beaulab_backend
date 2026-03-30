<?php

namespace App\Domains\Common\Models\Concerns;

use App\Domains\Common\Models\AdminNote\AdminNote;
use Illuminate\Database\Eloquent\Relations\MorphMany;

trait HasAdminNotes
{
    public function adminNotes(): MorphMany
    {
        return $this->morphMany(AdminNote::class, 'target', 'target_type', 'target_id')
            ->latest('id');
    }
}
