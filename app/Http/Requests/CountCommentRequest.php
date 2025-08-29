<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CountCommentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'product_id' => 'required|integer',
            'type'       => 'nullable|string|in:product,article', // cho phép product hoặc article
        ];
    }
}
