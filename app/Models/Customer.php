<?php

namespace App\Models;

use App\Support\Phone;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $guarded = [];

    public static function findOrCreateByPhone(string $name, string $phone): self
    {
        return static::firstOrCreate(['phone' => Phone::normalize($phone)], ['name' => $name]);
    }

    /** Staff entered the name, so it corrects whatever was stored for this phone. */
    public static function fromStaff(string $name, string $phone): self
    {
        $customer = static::findOrCreateByPhone($name, $phone);
        $customer->update(['name' => $name]);

        return $customer;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class)->latest('id');
    }
}
