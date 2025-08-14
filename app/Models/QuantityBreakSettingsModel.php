<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuantityBreakSettingsModel extends Model
{
    // use HasFactory;
    /**
     * @var string
     */
    protected $table = 'quantity_break_settings';

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
    ];
}
