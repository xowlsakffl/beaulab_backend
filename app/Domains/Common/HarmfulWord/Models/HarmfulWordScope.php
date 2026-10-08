<?php

namespace App\Domains\Common\HarmfulWord\Models;

use Illuminate\Database\Eloquent\Model;

final class HarmfulWordScope extends Model
{
    public $timestamps = false;

    public $incrementing = false;

    protected $fillable = ['scope'];
}
