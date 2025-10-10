<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ResponseModel extends Model
{
    /**
     * @var string
     */
    protected $table = 'response';
    /**
     * @var array
     */
    protected $fillable = [
        'shop_domain',
        'param',
        'api_name',
        'response',
        'expire_time',
        'updated_at',
        'created_at'
    ];
}
