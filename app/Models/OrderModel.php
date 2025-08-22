<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderModel extends Model
{
    /**
     * @var string
     */
    protected $table = 'domain_orders';
    /**
     * @var array
     */
    protected $fillable = [
        'domain_name',
        'shopify_order_id',
        'email',
        'total_price',
        'subtotal_price',
        'total_discounts',
        'discount_codes',
        'currency',
        'financial_status',
        'fulfillment_status',
        'customer_id',
        'order_data',
        'updated_at',
        'created_at'
    ];

    protected $casts = [
        'setup_guide' => 'array'
    ];
    public function orderItems()
    {
        return $this->hasMany(OrderItemModel::class, 'order_id', 'shopify_order_id');
    }
}
