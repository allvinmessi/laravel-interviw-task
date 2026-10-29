<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare inputs for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('code')) {
            $this->merge([
                'code' => strtoupper(trim($this->code)),
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $productParam = $this->route('product');
        $productId = $productParam instanceof Product ? $productParam->id : $productParam;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                Rule::unique('products', 'code')->ignore($productId),
            ],
            'price' => ['sometimes', 'required', 'numeric', 'min:0.01'],
            'tax_percentage' => ['sometimes', 'required', 'numeric', 'min:0', 'max:100'],
            'stock' => ['sometimes', 'required', 'integer', 'min:0'],
            'low_stock_threshold' => ['nullable', 'integer', 'min:0'],
        ];
    }

    /**
     * Custom messages for validation errors.
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Product name is required.',
            'code.required' => 'Product code (SKU) is required.',
            'code.unique' => 'A product with this code already exists (duplicate code not allowed).',
            'price.required' => 'Price per unit is required.',
            'price.min' => 'Price must be greater than zero.',
            'tax_percentage.required' => 'GST / Tax percentage is required.',
            'tax_percentage.min' => 'GST / Tax percentage cannot be negative.',
            'tax_percentage.max' => 'GST / Tax percentage cannot exceed 100%.',
            'stock.required' => 'Stock on hand is required.',
            'stock.min' => 'Stock cannot be negative.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
