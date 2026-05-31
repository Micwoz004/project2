<?php

namespace App\Products\EcoServices\Domain\News\Models;

use App\Platform\Clients\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsCategory extends Model
{
    use BelongsToClient;

    protected $table = 'eco_news_categories';

    protected $guarded = [];

    public function posts(): HasMany
    {
        return $this->hasMany(NewsPost::class, 'eco_news_category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
