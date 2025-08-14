<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class CollectionModel extends Model
{
    /**
     * @var string
     */
    protected $table = 'collections';
    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'store_id',
        'title',
        'handle',
        'type',
        'image',
        'products_count'
    ];
}
