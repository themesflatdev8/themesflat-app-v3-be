<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductOptionModel extends Model
{
    // use HasFactory;
    protected $table = 'product_options';

    /**
     * @var string
     */
    protected $casts = [
        'values' => 'array'
    ];

    protected $fillable = [
        'id',
        'store_id',
        'product_id',
        'name',
        'values',
    ];
}
