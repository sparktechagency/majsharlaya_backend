<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Review;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{

    public function getServices()
    {
        $services = Service::all();
        return response()->json([
            'status' => true,
            'message' => 'Get all services',
            'data' => $services
        ]);
    }

    public function searchServiceCompanies(Request $request)
    {
        $city = $request->input('city');
        $state = $request->input('state');

        // ✅ যদি দুটি ইনপুটই না থাকে, খালি রিটার্ন করো
        if (!$city || !$state) {
            return response()->json([
                'status' => true,
                'message' => 'City and state are required to search.',
                'data' => []
            ]);
        }

        $query = User::where('role', 'COMPANY');

        if ($city && $state) {
            $query->where(function ($q) use ($city, $state) {
                $q->where('city', 'like', "%$city%")
                    ->where('state', 'like', "%$state%");
            });
        }

        $companies = $query->get(); // ✅ এখানে get() ঠিকভাবে ব্যবহার করা হয়েছে

        return response()->json([
            'status' => true,
            'message' => ($city && $state) ? 'Filtered service companies' : 'All service companies',
            'data' => $companies
        ]);
    }

    public function viewServiceCompany(Request $request)
    {
        $company = User::where('id', $request->company_id)
            ->where('role', 'COMPANY')
            ->with('serviceLists') // যদি serviceLists relation থাকে
            ->first();

        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Company not found.',
            ], 404);
        }

        // Optional: avg rating & total reviews যদি যুক্ত করতে চাও
        $company->avgRating = round(Review::where('company_id', $company->id)->avg('rating'), 2);
        $company->totalReviews = Review::where('company_id', $company->id)->count();

        return response()->json([
            'status' => true,
            'message' => 'Get service company.',
            'data' => $company
        ]);
    }


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

    public function getOrder(Request $request)
    {
        $order = Order::find($request->order_id);

        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Order not found',
            ]);
        }

        // details ফিল্ড যদি double encoded হয়, তাহলে decode করে নেই
        $decoded = json_decode($order->details, true);
        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }

        $order->details = $decoded;

        return response()->json([
            'status' => true,
            'message' => 'Get order',
            'data' => $order
        ]);
    }

    public function acceptDelivery(Request $request)
    {
        // ✅ Validate request
        $request->validate([
            'order_id' => 'required',
        ]);

        // ✅ Find the order
        $order = Order::find($request->order_id);

        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        // ✅ Update status
        $order->status = 'Completed';
        $order->save();

        return response()->json([
            'status' => true,
            'message' => 'Delivery accepted. Order marked as completed.'
        ]);
    }

    public function feedback(Request $request)
    {
        // ✅ Validate the request
        $request->validate([
            'company_id' => 'required|exists:users,id', // কোম্পানি ইউজার আইডি
            'rating' => 'required|string',
            'comment' => 'nullable|string',
            'write_review' => 'nullable|string',
        ]);

        $company_id = User::where('id', $request->company_id)->where('role', 'COMPANY')->exists();

        if (!$company_id) {
            return response()->json([
                'status' => false,
                'message' => 'Company not found'
            ]);
        }

        // ✅ Create the review
        $review = Review::create([
            'user_id' => Auth::id(), // Currently logged-in user
            'company_id' => $request->company_id,
            'rating' => $request->rating,
            'comment' => $request->comment,
            'write_review' => $request->write_review,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Feedback submitted successfully.',
            'data' => $review,
        ], 201);
    }
}
