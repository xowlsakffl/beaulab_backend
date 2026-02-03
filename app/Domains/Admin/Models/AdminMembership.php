<?php

namespace App\Domains\Admin\Models;

use Database\Factories\AdminMembershipFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminMembership extends Model
{
    use HasFactory;

    protected $table = 'admin_memberships';

    protected $fillable = [
        'admin_id',
        'type',
        'target_id',
        'role',
        'is_primary',
    ];

    protected $casts = [
        'target_id' => 'int',
        'is_primary' => 'bool',
    ];

    protected static function newFactory(): Factory
    {
        return AdminMembershipFactory::new();
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Admin::class, 'admin_id');
    }
}
