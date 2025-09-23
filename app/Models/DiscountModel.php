<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DiscountModel extends Model
{
    // use HasFactory;
    /**
     * @var string
     */
    protected $table = 'domain_discounts';

    public const STATUS_MAP = [
        'DISABLED' => 0,
        'ACTIVE'   => 1,
        'EXPIRED'  => 2,
        'SCHEDULED' => 3
    ];


    /**
     * @var array
     */
    protected $fillable = [
        'domain_name',
        'shop_id',
        'shopify_discount_id',
        'title',
        'summary',
        'type',
        'codes',
        'discount_value',
        'minimum_requirement',
        'minimum_quantity',
        'starts_at',
        'ends_at',
        'applies_to',
        'purchase_type',
        'related_handles',
        'buy_handles',
        'get_handles',
        'status',
    ];
}
