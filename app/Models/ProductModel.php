<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class ProductModel extends Model
{
    // use HasFactory;

    /**
     * @var string
     */
    protected $table = 'products';
    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'store_id',
        'title',
        'handle',
        'image',
        'available',
        'price',
        'status',
        'stock',
        'inventory_management',
        'compare_at_price',
        'requires_selling_plan'
    ];

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariantModel::class, 'product_id', 'id');
    }

    public function options(): HasMany
    {
        return $this->hasMany(ProductOptionModel::class, 'product_id', 'id')->orderBy('created_at', 'asc')->orderBy('id', 'ASC');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(QuantityOfferModel::class, 'product_id', 'id');
    }
}
