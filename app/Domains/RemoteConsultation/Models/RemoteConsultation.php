<?php

namespace App\Domains\RemoteConsultation\Models;

use App\Domains\AccountStaff\Models\AccountStaff;
use App\Domains\Common\Category\Models\Category;
use App\Domains\Common\Media\Models\Media;
use App\Domains\Common\OperationHistory\Concerns\HasOperationHistories;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

final class RemoteConsultation extends Model
{
    use HasOperationHistories;

    protected $guarded = ['id'];

    protected $casts = ['birth_date' => 'date', 'height_cm' => 'integer', 'budget_amount' => 'integer', 'special_conditions' => 'array', 'requested_hospital_ids' => 'array', 'approved_at' => 'datetime'];

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'remote_consultation_categories')->withPivot('category_name')->orderBy('categories.id');
    }

    public function quotes(): HasMany
    {
        return $this->hasMany(RemoteConsultationQuote::class)->orderBy('id');
    }

    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')->whereIn('collection', ['member_images', 'style_images'])->orderBy('sort_order')->orderBy('id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(AccountStaff::class, 'approved_by_staff_id');
    }
}
