<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuantityOfferTierModel extends Model
{
    // use HasFactory;

    /**
     * @var string
     */
    protected $table = 'quantity_offer_tier';
    /**
     * @var array
     */
    protected $fillable = [
        'store_id',
        'offer_id',
        'name',
        'quantity',
        'message',
        'useDiscount',
        'discountId',
        'discountCode',
        'discountType',
        'discountValue'
    ];
}
