<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class CatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = require database_path('data/catalog.php');
        $images = (require database_path('data/images.php'))['products'];

        foreach ($catalog['categories'] as $position => $name) {
            Category::updateOrCreate(['name' => $name], ['position' => $position + 1]);
        }

        foreach ($catalog['products'] as $item) {
            $product = Product::firstOrCreate(['code' => $item['code']], [
                'category_id' => Category::where('name', $item['category'])->value('id'),
                'measures' => $item['measures'],
                'description' => $item['description'],
                'brand' => $item['brand'],
                'track_stock' => $item['track_stock'] ?? true,
                'is_featured' => $item['featured'] ?? false,
            ]);

            if (isset($images[$item['code']]) && ! $product->images()->exists()) {
                $image = $images[$item['code']];
                $product->images()->create([
                    'path' => $image['path'],
                    'is_reference' => $image['reference'],
                    'credit_author' => $image['author'] ?? null,
                    'credit_license' => $image['license'] ?? null,
                    'credit_url' => $image['url'] ?? null,
                ]);
            }
        }
    }
}
