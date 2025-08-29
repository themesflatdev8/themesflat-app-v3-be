<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddReviewRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'user_id'     => 'required|string|max:255',
            'product_id'  => 'required|integer',
            'review_text' => 'required|string',
            'rating'      => 'nullable|integer|min:1|max:5',
            'parent_id'   => 'nullable|integer',
            'is_admin'    => 'nullable|boolean',
        ];
    }
}
