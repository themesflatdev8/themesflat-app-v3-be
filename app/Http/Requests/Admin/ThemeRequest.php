<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class ThemeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'selector_cart_page' => 'required|string|max:255',
            'position_cart_page' => 'required|string|max:255',
            'style_cart_page' => 'max:255',
            'selector_cart_drawer' => 'max:255',
            'position_cart_drawer' => 'max:255',
            'style_cart_drawer' => 'max:255',
            'selector_button_cart_drawer' => 'max:255',
            'selector_wrap_cart_drawer' => 'max:255',
        ];
    }
}
