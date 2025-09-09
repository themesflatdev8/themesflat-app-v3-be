<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddSoldRecordRequest extends FormRequest
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
            'product_id'    => 'required|integer',
            'product_name'  => 'required|string',
            'product_price' => 'required|numeric',
            'price_coupon'  => 'nullable|numeric',
            'product_unit'  => 'required|integer',
            'total'         => 'required|numeric',
            'order_id'      => 'required|string',
            'order_date'    => 'required|date',
        ];
    }
}
