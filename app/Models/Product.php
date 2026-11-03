<?php

namespace App\Models;

use App\Enums\Availability;
use App\Support\CatalogSearch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Product extends Model
{
    protected $guarded = [];

    protected $attributes = [
        'stock' => 0,
        'avg_cost_cents' => 0,
        'track_stock' => true,
        'low_stock_threshold' => 3,
        'is_visible' => true,
        'is_featured' => false,
    ];

    protected function casts(): array
    {
        return [
            'track_stock' => 'boolean',
            'is_visible' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Product $p) {
            $p->slug = Str::slug($p->code);
            $p->search_key = CatalogSearch::normalize($p->code).' '
                .CatalogSearch::normalize((string) $p->measures).' '
                .CatalogSearch::normalize($p->description);
        });
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('position');
    }

    public function movements(): HasMany
    {
        return $this->hasMany(StockMovement::class)->latest('id');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->where('track_stock', true)->whereColumn('stock', '<=', 'low_stock_threshold');
    }

    public function availability(): Availability
    {
        return match (true) {
            ! $this->track_stock => Availability::Ask,
            $this->stock <= 0 => Availability::Out,
            $this->stock <= $this->low_stock_threshold => Availability::Low,
            default => Availability::Available,
        };
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
