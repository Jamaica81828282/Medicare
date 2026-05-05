<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_name',
        'description',
        'is_active',
    ];

    public function products()
    {
        return $this->hasMany(Product::class);
    }
}