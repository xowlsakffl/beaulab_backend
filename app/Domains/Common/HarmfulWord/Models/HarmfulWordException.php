<?php

namespace App\Domains\Common\HarmfulWord\Models;

use Illuminate\Database\Eloquent\Model;

final class HarmfulWordException extends Model
{
    public $timestamps = false;

    protected $fillable = ['phrase', 'normalized_phrase'];
}
