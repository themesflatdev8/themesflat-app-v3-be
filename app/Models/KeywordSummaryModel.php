<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeywordSummaryModel extends Model
{
    use HasFactory;
    protected $table = 'keyword_summary';

    protected $fillable = [
        'shop_domain',
        'keyword',
        'date',
        'count',
    ];
}
