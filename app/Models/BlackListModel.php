<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BlackListModel extends Model
{
    /**
     * @var string
     */
    protected $table = 'blacklist';
    /**
     * @var array
     */

    public const CATEGORY_SHOPIFY = "shopify";
    public const CATEGORY_COMPETITOR = "competitor";


    public const TYPE_EMAIL = "email";
    public const TYPE_DOMAIN = "shopify_domain";
    public const TYPE_PLAN = "shopify_plan";
    public const TYPE_KEYWORD_EMAIL = "keyword_email";
    public const TYPE_KEYWORD_DOMAIN = "keyword_domain";
    public const TYPE_KEYWORD_NAME = "keyword_name";

    protected $fillable = [
        'category',
        'type',
        'value',
    ];
}
