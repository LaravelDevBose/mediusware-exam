<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductVariantPrice extends Model
{
    protected $fillable = [
        'product_variant_one',
        'product_variant_two',
        'product_variant_three',
        'price',
        'stock'
    ];

    public function variant_one()
    {
        return $this->hasOne(ProductVariant::class,  'id','product_variant_one');
    }

    public function variant_two()
    {
        return $this->hasOne(ProductVariant::class,  'id','product_variant_two');
    }

    public function variant_three()
    {
        return $this->hasOne(ProductVariant::class,  'id','product_variant_three');
    }
}
