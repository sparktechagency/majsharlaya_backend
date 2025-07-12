<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public function createOrder(Request $request)
    {
        // Validation
        $validator = Validator::make($request->all(), [
            'provider_company_id' => 'required|exists:users,id',
            'service_name' => 'required|string',
            'assign_provider_id' => 'nullable|exists:providers,id',
            'details' => 'required',
            'price' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        // Create Order
        $order = Order::create([
            'user_id' => Auth::id(),
            'provider_company_id' => $request->provider_company_id,
            'service_name' => $request->service_name,
            'assign_provider_id' => $request->assign_provider_id,
            'details' => json_encode($request->details),
            'price' => $request->price,
            'status' => 'Pending'
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Order created successfully',
            'data' => $order
        ], 201);
    }
}
