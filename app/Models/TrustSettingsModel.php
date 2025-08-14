<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrustSettingsModel extends Model
{
    // use HasFactory;
    /**
     * @var string
     */
    protected $table = 'trust_settings';

    protected $casts = [
        'settings' => 'array'
    ];

    /**
     * @var array
     */
    protected $fillable = [
        'id',
        'type',
        'store_id',
        'settings',
        'version'
    ];
}
