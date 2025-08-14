<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ProductVariantModel extends Model
{
    // use HasFactory;

    /**
     * @var string
     */
    protected $table = 'product_variants';
    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'store_id',
        'product_id',
        'title',
        'option1',
        'option2',
        'option3',
        'image',
        'inventory',
        'inventory_management',
        'price',
        'compare_at_price'
    ];
}
