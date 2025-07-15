<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Provider;
use App\Models\User;
use App\Notifications\SendDeliveryNotification;
use App\Notifications\SendDeliveryRequestNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CompanyOrderController extends Controller
{
    public function getApprovePosts(Request $request)
    {
        $search = $request->input('search');

        $type = $request->input('type');

        if ($request->type == 'Pending') {
            $type = 'Approve';
        }

        $per_page = $request->input('per_page', 10);

        $query = Order::with('user')
            ->where('status', $type)
            ->latest();

        if ($search || $type) {
            $query->where(function ($q) use ($search, $type) {

                // ✅ Filter by user name
                if ($search) {
                    $q->whereHas('user', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%$search%");
                    });
                }

                // ✅ Filter by service_name
                if ($type) {
                    $q->Where('status', $type);
                }
            });
        }

        $bookings = $query->paginate($per_page);

        // ✅ Transform details JSON
        $bookings->getCollection()->transform(function ($booking) {
            $decoded = json_decode($booking->details, true);

            // If it's double-encoded JSON, decode again
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }

            $booking->details = $decoded;
            $booking->approve_status = $booking->status == 'Approve' ? 'Pending' : null;
            return $booking;
        });

        return response()->json([
            'status' => true,
            'message' => $search || $type
                ? ($search && $type
                    ? 'Filtered bookings retrieved successfully'
                    : ($search
                        ? 'Searched bookings retrieved successfully'
                        : 'Filtered bookings retrieved successfully'))
                : 'All approved bookings retrieved successfully',
            'data' => $bookings
        ]);
    }

    public function assignProvider(Request $request)
    {
        // ✅ Validate request
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|exists:orders,id', // ✅ টেবিল ও কলাম সঠিকভাবে লিখো
            'provider_id' => 'required|exists:providers,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        // ✅ Find the order
        $order = Order::find($request->order_id);

        // ✅ Assign provider
        $order->assign_provider_id = $request->provider_id;
        $order->save();

        return response()->json([
            'status' => true,
            'message' => 'Provider assigned successfully.',
            'data' => $order
        ]);
    }

    public function searchAssignProviders(Request $request)
    {
        $search = $request->input('search'); // search by name/email/number/type

        $query = Provider::query()->where('status', 'Available');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
                // ->orWhere('number', 'like', "%{$search}%")
                // ->orWhereRaw("JSON_CONTAINS(`type`, '\"$search\"')"); // ✅ search in JSON type array
            });
        }

        $providers = $query->get();

        // Transform JSON fields
        $providers->transform(function ($provider) {
            $provider->type = json_decode($provider->type, true);
            $provider->nid = json_decode($provider->nid, true);
            return $provider;
        });

        return response()->json([
            'status' => true,
            'message' => $search ? 'Filtered available providers' : 'All available providers',
            'data' => $providers
        ]);
    }

    public function sendDeliveryRequest(Request $request)
    {
        // ✅ Validate request input
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'user_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        // ✅ Find order
        $order = Order::find($request->order_id);

        if (!$order) {
            return response()->json([
                'status' => false,
                'message' => 'Order not found.',
            ], 404);
        }

        // ✅ Find order
        $user = User::find($request->user_id);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found.',
            ], 404);
        }

        $user->notify(new SendDeliveryNotification($order));
        

        return response()->json([
            'status' => true,
            'message' => 'Delivery request sent successfully.',
        ]);
    }


}
