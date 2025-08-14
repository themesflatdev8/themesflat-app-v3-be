<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StoreTestModel extends Model
{
    /**
     * @var string
     */
    protected $table = 'test_stores';
    /**
     * @var array
     */
    protected $fillable = [
        'store_id',
        'owner',
        'created_at',
        'updated_at'
    ];
}
