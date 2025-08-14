<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchSettingsModel extends Model
{
    // use HasFactory;
    /**
     * @var string
     */
    protected $table = 'search_settings';

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
