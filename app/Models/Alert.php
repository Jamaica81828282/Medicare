<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use App\Models\User;

class Alert extends Model
{
    protected $fillable = [
        'type',
        'product_id',
        'batch_id',
        'created_by',
        'message',
        'status',
        'resolved_by',
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Batch alerts are currently stored with an optional batch_id,
    // but no ProductBatch model exists in this codebase yet.
    // If a ProductBatch model is added later, restore this relation.
    // public function batch()
    // {
    //     return $this->belongsTo(ProductBatch::class, 'batch_id');
    // }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
