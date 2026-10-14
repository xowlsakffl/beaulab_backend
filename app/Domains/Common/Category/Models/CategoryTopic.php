<?php

namespace App\Domains\Common\Category\Models;

use Illuminate\Database\Eloquent\Model;

final class CategoryTopic extends Model
{
    protected $fillable = ['code', 'name', 'group_code', 'sort_order', 'is_menu_visible'];

    protected $casts = ['sort_order' => 'integer', 'is_menu_visible' => 'boolean'];

    public function categories(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_topic_links', 'topic_id', 'category_id');
    }
}
