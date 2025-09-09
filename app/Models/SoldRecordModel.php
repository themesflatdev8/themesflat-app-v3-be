<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SoldRecordModel extends Model
{
    use HasFactory;
    // Tên bảng (nếu khác với số nhiều của tên model thì cần khai báo)
    protected $table = 'sold_records';

    // Khóa chính
    protected $primaryKey = 'id';

    // Các cột cho phép gán giá trị hàng loạt
    protected $fillable = [
        'domain_name',
        'product_id',
        'product_name',
        'product_price',
        'price_coupon',
        'product_unit',
        'total',
        'order_id',
        'order_date',
    ];
}
