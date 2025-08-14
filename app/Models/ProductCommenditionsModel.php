<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ProductCommenditionsModel extends Model
{
    // use HasFactory;

    /**
     * @var string
     */
    protected $table = 'product_recommendations';
    /**
     * @var array
     */
    protected $fillable = [
        'store_id',
        'product_id',
        'bundle_id',
        'type',
    ];

}
