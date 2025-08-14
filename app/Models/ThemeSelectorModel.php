<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ThemeSelectorModel extends Model
{
    /**
     * @var string
     */
    protected $table = 'theme_selector';
    /**
     * @var array
     */

    protected $fillable = [
        'name',
        'selector_cart_page',
        'position_cart_page',
        'style_cart_page',
        'selector_cart_drawer',
        'position_cart_drawer',
        'style_cart_drawer',
        'selector_button_cart_drawer',
        'selector_wrap_cart_drawer'
    ];
}
