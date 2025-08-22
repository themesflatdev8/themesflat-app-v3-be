<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItemModel extends Model
{
    /**
     * @var string
     */
    protected $table = 'domain_order_items';
    /**
     * @var array
     */
    protected $fillable = [
        'shop_domain',
        'order_id',
        'product_id',
        'variant_id',
        'title',
        'variant_title',
        'sku',
        'handle',
        'vendor',
        'product_type',
        'image_url',
        'quantity',
        'price',
        'total_discount',
        'line_price',
        'final_line_price',
        'line_item_data',
        'created_at',
        'updated_at'
    ];

    protected $casts = [];
}
