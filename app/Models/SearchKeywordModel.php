<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchKeywordModel extends Model
{
    use HasFactory;
    // tên bảng (nếu khác với chuẩn Laravel thì phải khai báo)
    protected $table = 'search_keywords';

    // primary key
    protected $primaryKey = 'id';

    // tắt auto increment nếu không phải
    public $incrementing = true;

    // kiểu key
    protected $keyType = 'int';

    // timestamps: vì bảng không có created_at / updated_at

    // cho phép fillable
    protected $fillable = [
        'shop_domain',
        'keyword',
        'searched_at',
        'user_ip',
        'user_agent',
        'referer',
    ];

    // Nếu muốn cast searched_at sang Carbon datetime
    protected $casts = [
        'searched_at' => 'datetime',
    ];
}
