<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShopModel extends Model
{
    /**
     * @var string
     */
    protected $table = 'shops';
    protected $primaryKey = 'shop_id';
    /**
     * @var array
     */
    protected $fillable = [
        'shop_id',
        'shop',
        'access_token',
        'is_active',
        'shopify_plan',
        'app_version',
        'app_plan',
        'email',
        'phone',
        'billing_id',
        'billing_on',
        'cancelled_on',
        'installed_at',
        'uninstalled_at',
        'scope',
        'domain',
        'created_at',
        'updated_at'
    ];

    protected $casts = [
        'setup_guide' => 'array'
    ];
}
