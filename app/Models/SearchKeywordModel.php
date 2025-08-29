<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchKeywordModel extends Model
{
    use HasFactory;
    protected $table = 'search_keywords';

    protected $fillable = [
        'shop_domain',
        'keyword',
        'date',
        'count',
    ];

    // Nếu bạn muốn Laravel tự động cast `date` thành Carbon
    protected $casts = [
        'date' => 'date',
    ];
}
