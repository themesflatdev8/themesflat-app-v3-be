<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class BlackListRequest extends FormRequest
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
    public function rules(): array
    {
        return [
            'category' => 'required|in:competitor,shopify|max:255',
            'type' => 'required|in:email_domain,email,shopify_plan,shopify_domain,keyword_name',
            'value' => 'required|string|max:255',
        ];
    }

}
