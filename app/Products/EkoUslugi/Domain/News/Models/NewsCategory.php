<?php

namespace App\Products\EkoUslugi\Domain\News\Models;

use App\Platform\Clients\Concerns\BelongsToClient;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class NewsCategory extends Model
{
    use BelongsToClient;

    protected $table = 'eko_news_categories';

    protected $guarded = [];

    public function posts(): HasMany
    {
        return $this->hasMany(NewsPost::class, 'eko_news_category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }
}
