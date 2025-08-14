<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SettingsModel extends Model
{
    // use HasFactory;
    /**
     * @var string
     */
    protected $table = 'settings';

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
