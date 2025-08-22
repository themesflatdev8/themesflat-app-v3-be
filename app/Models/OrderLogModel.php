<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderLogModel extends Model
{
    /**
     * @var string
     */
    protected $table = 'domain_orders_logs';
    /**
     * @var array
     */
    protected $fillable = [
        'shopify_order_id',
        'domain_name',
        'action_type',
        'log_data',
        'created_at',
        'updated_at'
    ];

    protected $casts = [];
}
