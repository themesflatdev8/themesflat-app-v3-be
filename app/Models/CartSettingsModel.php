<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartSettingsModel extends Model
{
    // use HasFactory;
    /**
     * @var string
     */
    protected $table = 'cart_settings';

    protected $casts = [
        'settings' => 'array'
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        // 'enabled',
        'settings',
        'template_version'
    ];
}
