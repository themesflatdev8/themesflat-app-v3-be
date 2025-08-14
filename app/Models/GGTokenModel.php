<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GGTokenModel extends Model
{
    /**
     * @var string
     */
    protected $table = 'gg_token';

    /**
     * @var string
     */
    protected $casts = [
        'value' => 'array'
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'key',
        'value',
    ];
}
