<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopifyRecentViewModel extends Model
{
    use HasFactory;
    protected $table = 'shopify_recent_views';

    protected $primaryKey = 'id';

    // Cho phép fill dữ liệu qua mass assignment
    protected $fillable = [
        'product_id',
        'user_id',
        'handle',
        'domain_name',
        'viewed_at',
        'position',
    ];

    // Kiểu dữ liệu cho từng field (casting)
    protected $casts = [
        'viewed_at' => 'datetime',
        'position'  => 'integer',
    ];
}
