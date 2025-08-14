<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class BundlesModel extends Model
{
    // use HasFactory;

    /**
     * @var string
     */
    protected $table = 'bundles';
    /**
     * @var array
     */
    protected $fillable = [
        'store_id',
        "name",
        "status",
        "type", // specific
        "pageType", // product
        // "mainIds",
        "mode", // manual
        // "recommends",
        "maxProduct",
        "selectable",

        "useDiscount",
        "minimumAmount",
        "promotionType", //discount , freeship

        "discountType", // percent , amount
        "discountId",
        "discountCode",
        "discountContent",
        "discountValue",

        "discountFreeshipType",
        "discontFreeshipId",
        "discountFreeshipCode",
        "discountFreeshipContent",
        "discountFreeshipValue",

        "discountOncePer",

        "countdownTimerActive",
        "countdownTimerValue",
        "countdownTimerSession",
        "countdownTimerReaches",
        "templateDesktop",
        "templateMobile"
    ];

    /**
     * @var string
     */
    // protected $casts = [
    //     'cross_ids' => 'array'
    // ];

    public const PROMOTION_TYPE_DISCOUNT = "discount";
    public const PROMOTION_TYPE_GIFT = "gift";
    public const PROMOTION_TYPE_FRESHIP = "freeship";

    public const GIFT_TYPE_PERCENT = "percent";
    public const GIFT_TYPE_AMOUNT = "amount";
    public const GIFT_TYPE_FREE = "free";

    public const FREESHIP_TYPE_PERCENT = "percent";
    public const FREESHIP_TYPE_FIX = "fix";
    public const FREESHIP_TYPE_FREE = "free";


    public const BUNDLE_TYPE_SPECIFIC = "specific";
    public const BUNDLE_TYPE_COLLECTION = "collection";
    public const BUNDLE_TYPE_GENERAL = "general";

    public function commendations(): HasMany
    {
        return $this->hasMany(ProductCommenditionsModel::class, 'bundle_id', 'id');
    }

    public function listDefaultIds(): HasMany
    {
        return $this->hasMany(BundleMainIdsModel::class, 'bundle_id', 'id');
    }

    public function listCommendations(): BelongsToMany
    {
        return $this->belongsToMany(ProductModel::class, 'product_recommendations', 'bundle_id', 'product_id')->withPivot('type');
    }

    // public function product(): BelongsTo
    // {
    //     return $this->BelongsTo(ProductModel::class, 'product_id', 'id');
    // }

    // public function collection(): BelongsTo
    // {
    //     return $this->BelongsTo(CollectionModel::class, 'product_id', 'id');
    // }

    public function listDefaultProducts(): BelongsToMany
    {
        return $this->belongsToMany(ProductModel::class, 'bundle_main_ids', 'bundle_id', 'main_id');
    }

    public function listDefaultCollections(): BelongsToMany
    {
        return $this->belongsToMany(CollectionModel::class, 'bundle_main_ids', 'bundle_id', 'main_id');
    }
}
