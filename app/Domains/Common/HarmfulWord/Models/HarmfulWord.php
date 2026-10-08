<?php

namespace App\Domains\Common\HarmfulWord\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class HarmfulWord extends Model
{
    public const SCOPES = ['REVIEW', 'TALK', 'EVALUATION', 'COMMENT', 'NICKNAME'];

    public const MODES = ['CONTAINS', 'STANDALONE'];

    public const STATUSES = ['ACTIVE', 'INACTIVE'];

    protected $fillable = ['word', 'normalized_word', 'matching_mode', 'detect_variants', 'status', 'lock_version'];

    protected function casts(): array
    {
        return ['detect_variants' => 'boolean', 'lock_version' => 'integer'];
    }

    public function scopes(): HasMany
    {
        return $this->hasMany(HarmfulWordScope::class);
    }

    public function exceptions(): HasMany
    {
        return $this->hasMany(HarmfulWordException::class);
    }

    public function snapshot(): array
    {
        return $this->only(['id', 'word', 'matching_mode', 'detect_variants', 'status', 'lock_version', 'updated_at']) + [
            'scopes' => $this->scopes->pluck('scope')->all(),
            'exceptions' => $this->exceptions->pluck('phrase')->all(),
        ];
    }
}
