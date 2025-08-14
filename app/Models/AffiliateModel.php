<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AffiliateModel extends Model
{
    /**
     * @var string
     */
    protected $table = 'affiliate';

    protected $fillable = [
        'domain',
        'iframe',
        "cookie_name",
        "timeout"
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'iframe' => 'array',
    ];

    public function setIframeAttribute($value)
    {
        $this->attributes['iframe'] = $value;
    }

    public function getIframeAttribute($value)
    {
        $decoded = json_decode($value, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        return $value;
    }
}
