<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare inputs for validation to support both nested customer object
     * and flat customer_name / customer_email fields.
     */
    protected function prepareForValidation(): void
    {
        $data = $this->all();

        if (isset($data['customer_email']) && !isset($data['customer']['email'])) {
            $data['customer']['email'] = $data['customer_email'];
        }
        if (isset($data['customer_name']) && !isset($data['customer']['name'])) {
            $data['customer']['name'] = $data['customer_name'];
        }

        $this->replace($data);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'customer' => ['required', 'array'],
            'customer.email' => ['required', 'string', 'email:rfc,filter', 'max:255'],
            'customer.name' => ['required', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
            'amount_given' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'customer.email.required' => 'Customer email is required.',
            'customer.email.email' => 'Please provide a valid email address.',
            'customer.name.required' => 'Customer name is required.',
            'items.required' => 'At least one product item is required to place an order.',
            'items.min' => 'At least one product item is required to place an order.',
            'items.*.product_id.required' => 'Product ID is required for each line item.',
            'items.*.product_id.exists' => 'Selected product does not exist in the catalog.',
            'items.*.quantity.required' => 'Quantity is required for each line item.',
            'items.*.quantity.min' => 'Quantity must be at least 1.',
        ];
    }

    /**
     * Handle failed validation for API calls.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => 'The given data was invalid.',
            'errors' => $validator->errors(),
        ], 422));
    }
}
