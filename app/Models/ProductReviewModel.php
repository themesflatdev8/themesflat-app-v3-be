<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductReviewModel extends Model
{
    use HasFactory;
    protected $table = 'product_reviews';

    protected $primaryKey = 'id';

    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'domain_name',
        'title',
        'handle',
        'user_name',
        'product_id',
        'parent_id',
        'review_text',
        'review_title',
        'rating',
        'is_admin',
        'status',
        'type',
        'created_at',
        'updated_at'
    ];
    /**
     * Scope tìm kiếm theo keyword (ILIKE + trigram index trong PostgreSQL).
     */
    public function scopeSearch($query, string $keyword)
    {
        return $query->where('review_text', 'ILIKE', "%{$keyword}%");
    }

    /**
     * Quan hệ: một review có thể có nhiều reply (con).
     */
    public function replies()
    {
        return $this->hasMany(ProductReviewModel::class, 'parent_id');
    }

    /**
     * Quan hệ: review con thuộc về một review cha.
     */
    public function parent()
    {
        return $this->belongsTo(ProductReviewModel::class, 'parent_id');
    }
}
