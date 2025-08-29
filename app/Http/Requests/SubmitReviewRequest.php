<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SubmitReviewRequest extends FormRequest
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
            'user_id'      => 'nullable|max:255',
            'product_id'   => 'required|integer|min:1',
            'review_title' => 'nullable|string|max:255',
            'rating'       => 'required|integer|min:1|max:5',
            'review_text'  => 'required|string|min:5',
            'user_name'    => 'nullable|string|max:100',
            'user_email'   => 'nullable|email|max:255',
            'type'         => 'nullable|string|' //in:product,shop',
        ];
    }
}
