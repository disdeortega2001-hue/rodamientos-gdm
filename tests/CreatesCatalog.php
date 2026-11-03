<?php

namespace Tests;

use App\Enums\Role;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;

trait CreatesCatalog
{
    protected function category(string $name = 'Rígidos 2RS'): Category
    {
        return Category::firstOrCreate(['name' => $name], ['position' => 1]);
    }

    protected function product(array $attrs = [], string $category = 'Rígidos 2RS'): Product
    {
        return Product::create($attrs + [
            'category_id' => $this->category($category)->id,
            'code' => 'P'.uniqid(),
            'description' => 'Rodamiento de prueba',
            'stock' => 10,
            'price_cents' => 600,
            'avg_cost_cents' => 360,
        ]);
    }

    protected function admin(): User
    {
        return User::factory()->create(['role' => Role::Admin]);
    }

    protected function seller(): User
    {
        return User::factory()->create(['role' => Role::Seller]);
    }

    protected function customer(): Customer
    {
        return Customer::firstOrCreate(['phone' => '04121234567'], ['name' => 'Ana Pérez']);
    }
}
