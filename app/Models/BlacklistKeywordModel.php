<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BlacklistKeywordModel extends Model
{
    use HasFactory;
    protected $table = 'blacklist_keywords';

    // Khóa chính (mặc định Laravel hiểu là 'id', nên có thể bỏ)
    protected $primaryKey = 'id';

    // Các cột có thể gán giá trị hàng loạt (mass assignable)
    protected $fillable = [
        'id',
        'keyword',
    ];
}
