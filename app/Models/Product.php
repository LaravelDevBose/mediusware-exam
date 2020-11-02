<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = [
        'title', 'sku', 'description'
    ];

    public function scopeSearchBy($query, $request)
    {
        if (!empty($request->title)){
            $query = $query->where('title', 'LIKE', '%'.$request->title.'%');
        }

        if (!empty($request->date)){
            $query = $query->whereDate('created_at', Carbon::parse($request->date)->format('Y-m-d'));
        }

        if (!empty($request->variant)){
            $varProIds = ProductVariant::where('variant', $request->variant)->pluck('product_id');
            $query = $query->whereIn('id', $varProIds);
        }

        if (!empty($request->price_from) && !empty($request->price_to)){
            $varProIds = ProductVariantPrice::where('price', '>=', $request->price_from)
                ->where('price', '<=', $request->price_to)->pluck('product_id');
            $query = $query->whereIn('id', $varProIds);
        }
        return $query;
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_id', 'id');
    }

    public function variant_prices()
    {
        return $this->hasMany(ProductVariantPrice::class, 'product_id', 'id');
    }

    public function variants()
    {
        return $this->hasMany(ProductVariant::class, 'product_id', 'id');
    }
}
