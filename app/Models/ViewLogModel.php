<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ViewLogModel extends Model
{
    use HasFactory;
    protected $table = 'view_logs';

    protected $fillable = [
        'product_id',
        'user_id',
        'handle',
        'domain_name',
        'viewed_at',
        'user_agent',
        'referer',
    ];

    protected $casts = [
        'viewed_at' => 'datetime',
    ];
}
