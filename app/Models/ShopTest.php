<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShopTest extends Model
{
    use HasFactory;
    protected $table = 'shop_tests';
    /**
     * @var array
     */
    protected $fillable = [
        'shop_id',
        'shopify_domain',
        'owner',
        'created_at',
        'updated_at'
    ];
}
