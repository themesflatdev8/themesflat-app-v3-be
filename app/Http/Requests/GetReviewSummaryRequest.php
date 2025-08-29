<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetReviewSummaryRequest extends FormRequest
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
            'product_id' => ['required', 'integer', 'min:1'],
            'type'       => ['nullable', 'string'],
            // 👆 tuỳ chỉnh "in" nếu có nhiều type khác ngoài product/shop
        ];
    }
}
