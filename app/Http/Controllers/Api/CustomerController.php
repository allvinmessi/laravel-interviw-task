<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Http\Resources\OrderResource;
use App\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    /**
     * Fetch a customer's order history by email.
     */
    public function orders(Request $request, ?string $email = null): JsonResponse
    {
        $targetEmail = $email ?? $request->query('email');

        $validator = Validator::make(['email' => $targetEmail], [
            'email' => ['required', 'string', 'email:rfc,filter'],
        ], [
            'email.required' => 'Customer email is required to fetch order history.',
            'email.email' => 'Please provide a valid email address.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $customer = Customer::where('email', strtolower(trim($targetEmail)))
            ->with(['orders.items'])
            ->first();

        if (!$customer) {
            return response()->json([
                'message' => "Customer with email '{$targetEmail}' not found.",
                'customer' => null,
                'orders' => [],
            ], 404);
        }

        return response()->json([
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
            ],
            'orders' => OrderResource::collection($customer->orders),
        ]);
    }

    /**
     * Auto-lookup customer by email for POS counter auto-fill.
     */
    public function lookup(Request $request): JsonResponse
    {
        $email = $request->query('email');

        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return response()->json([
                'exists' => false,
                'customer' => null,
            ]);
        }

        $customer = Customer::where('email', strtolower(trim($email)))->first();

        if (!$customer) {
            return response()->json([
                'exists' => false,
                'customer' => null,
            ]);
        }

        return response()->json([
            'exists' => true,
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
            ],
        ]);
    }
}
