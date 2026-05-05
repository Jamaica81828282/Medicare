<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'sku',
        'product_name',
        'generic_name',
        'brand',
        'description',
        'selling_price',
        'cost_price',
        'stock_quantity',
        'reorder_level',
        'requires_rx',
        'is_active',
        'category_id',
        'image_base64',
    ];

    public function category()
    {
        return $this->belongsTo(ProductCategory::class);
    }
}