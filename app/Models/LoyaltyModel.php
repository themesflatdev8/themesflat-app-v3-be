<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoyaltyModel extends Model
{
    /**
     * @var string
     */
    protected $table = 'loyalty';
    /**
     * @var array
     */
    protected $fillable = [
        'store_id',
        'quest_ext',
        'quest_bundle',
        'quest_review',
        'force_loyalty',
        'apply',
        'sent_mail',
        'email'
    ];
}
