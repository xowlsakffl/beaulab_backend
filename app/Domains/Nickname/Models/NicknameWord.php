<?php

namespace App\Domains\Nickname\Models;

use Illuminate\Database\Eloquent\Model;

final class NicknameWord extends Model
{
    public const TYPES = ['ADJECTIVE', 'NOUN'];

    public const STATUSES = ['ACTIVE', 'INACTIVE'];

    protected $fillable = ['type', 'word', 'status', 'lock_version'];

    protected function casts(): array
    {
        return ['lock_version' => 'integer'];
    }
}
