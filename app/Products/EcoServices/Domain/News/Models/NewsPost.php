<?php

namespace App\Products\EcoServices\Domain\News\Models;

use App\Platform\Clients\Concerns\BelongsToClient;
use App\Products\EcoServices\Domain\Address\Models\EcoZone;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class NewsPost extends Model
{
    use BelongsToClient, SoftDeletes;

    protected $table = 'eco_news_posts';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(NewsCategory::class, 'eco_news_category_id');
    }

    public function zone(): BelongsTo
    {
        return $this->belongsTo(EcoZone::class, 'eco_zone_id');
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('status', 'published')
            ->where(function (Builder $query): void {
                $query->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }
}
