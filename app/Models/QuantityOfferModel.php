<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuantityOfferModel extends Model
{
    // use HasFactory;

    /**
     * @var string
     */
    protected $table = 'quantity_offers';
    /**
     * @var array
     */
    protected $fillable = [
        'store_id',
        'product_id',
        'name',
        'status',
        'mostPopularActive',
        'mostPopularPosition',
        'countdownTimerActive',
        'countdownTimerValue',
        'countdownTimerSession',
        'countdownTimerReaches'
    ];

    public function tiers(): HasMany
    {
        return $this->hasMany(QuantityOfferTierModel::class, 'offer_id', 'id');
    }

    // public function productCommendations(): BelongsToMany
    // {
    //     return $this->belongsToMany(ProductModel::class, 'product_recommendations', 'bundle_id', 'product_id')->withPivot('type');
    // }

    public function product(): BelongsTo
    {
        return $this->BelongsTo(ProductModel::class, 'product_id', 'id');
    }
}
