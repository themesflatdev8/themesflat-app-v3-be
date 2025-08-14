<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class BundleMainIdsModel extends Model
{
    // use HasFactory;

    /**
     * @var string
     */
    protected $table = 'bundle_main_ids';
    /**
     * @var array
     */
    protected $fillable = [
        'store_id',
        'main_id',
        'bundle_id',
        // 'type',
    ];
}
