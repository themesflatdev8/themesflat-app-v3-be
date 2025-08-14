<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreModel extends Model
{
    /**
     * @var string
     */
    protected $table = 'stores';
    /**
     * @var array
     */
    protected $fillable = [
        'store_id',
        'name',
        'shopify_domain',
        'access_token',
        'domain',
        'shopify_plan',
        'shopify_theme',
        'owner',
        'email',
        'phone',
        'country',
        'primary_locale',
        'currency',
        'money_format',
        'app_status',
        'setup_guide',
        'app_version',
        'trial_days',
        'trial_on',
        'app_plan',
        'billing_id',
        'billing_on',
        'cancelled_on',
        'created_at',
        'timezone',
        'updated_at'
    ];

    protected $casts = [
        'setup_guide' => 'array'
    ];
}
