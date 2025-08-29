<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetReviewRequest extends FormRequest
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
            'product_id' => 'required|integer|min:1',
            'type'       => 'nullable|string|' //in:product,shop',
            // type: mặc định là 'product', bạn có thể thêm các giá trị hợp lệ khác
        ];
    }
}
