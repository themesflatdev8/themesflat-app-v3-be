<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EditReviewRequest extends FormRequest
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
            'id'         => 'required|integer|exists:product_reviews,id',
            'user_id'    => 'required|string|max:255',
            'domain'     => 'required|string|max:255',
            'review_text' => 'required|string',
            'rating'     => 'nullable|integer|min:1|max:5',
        ];
    }
}
