<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $guarded = [];

    protected static function booted(): void
    {
        static::saving(fn (Category $c) => $c->slug = Str::slug($c->name));
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
