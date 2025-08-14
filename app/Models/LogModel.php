<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LogModel extends Model
{
    /**
     * @var string
     */
    protected $table = 'logs';
    /**
     * @var array
     */
    protected $fillable = [
        'store_id',
        'total_order',
        'total_bundle',
        'total_revenue'
    ];
}
