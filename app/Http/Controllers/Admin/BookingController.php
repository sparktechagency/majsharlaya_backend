<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class BookingController extends Controller
{
    // public function getBookings(Request $request)
    // {
    //     $search = $request->input('search');
    //     $type = $request->input('type');   

    //     // Query শুরু: latest order থেকে
    //     $query = Order::with('user')->latest();

    //     if ($search) {
    //         $query->where(function ($q) use ($search) {
    //             // search by user name (relation)
    //             $q->whereHas('user', function ($q2) use ($search) {
    //                 $q2->where('name', 'like', "%$search%");
    //             });

    //             // বা search by service_name in details JSON (MySQL JSON_SEARCH or LIKE)
    //             // $q->orWhere('details', 'like', "%$search%");
    //         });
    //     }

    //     $bookings = $query->paginate($per_page ?? 10);



    //     $bookings->transform(function ($booking) {
    //         // double decode করার জন্য প্রথমে string থেকে json, তারপর array
    //         $decoded = json_decode($booking->details, true); // প্রথম decode
    //         if (is_string($decoded)) {
    //             $decoded = json_decode($decoded, true); // দ্বিতীয় decode
    //         }

    //         $booking->details = $decoded; // details update করে দেওয়া
    //         return $booking;
    //     });

    //     return response()->json([
    //         'status' => true,
    //         'message' => 'All bookings retrieved successfully',
    //         'data' => $bookings
    //     ]);
    // }


    public function getBookings(Request $request)
    {
        $search = $request->input('search');
        $type = $request->input('type'); // "plamber", "cutting", "fishing"
        $per_page = $request->input('per_page', 10);

        $query = Order::with('user')->latest();

        if ($search || $type) {
            $query->where(function ($q) use ($search, $type) {

                // ✅ Filter by user name
                if ($search) {
                    $q->whereHas('user', function ($q2) use ($search) {
                        $q2->where('name', 'like', "%$search%");
                    });
                }

                // ✅ Filter by service_name column directly
                if ($type) {
                    $q->orWhere('service_name', 'like', "%$type%");
                }
            });
        }

        $bookings = $query->paginate($per_page);

        $bookings->getCollection()->transform(function ($booking) {
            // যদি details double encoded হয়, decode করে নিই
            $decoded = json_decode($booking->details, true);
            if (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }

            $booking->details = $decoded;
            return $booking;
        });

        return response()->json([
            'status' => true,
            'message' => $search || $type ? ($search && $type ? 'Filtered bookings retrieved successfully' : ($search ? 'Searched bookings retrieved successfully' : 'Filtered bookings retrieved successfully')) : 'All bookings retrieved successfully',
            'data' => $bookings
        ]);
    }

    public function viewBooking(Request $request)
    {
        $booking = Order::with('user')->find($request->order_id);

        if (!$booking) {
            return response()->json([
                'status' => false,
                'message' => 'Booking not found',
            ], 404);
        }

        // details ফিল্ড যদি double encoded হয়, তাহলে decode করে নেই
        $decoded = json_decode($booking->details, true);
        if (is_string($decoded)) {
            $decoded = json_decode($decoded, true);
        }

        $booking->details = $decoded;

        return response()->json([
            'status' => true,
            'message' => 'Booking retrieved successfully',
            'data' => $booking
        ]);
    }

    public function deleteBooking(Request $request)
    {
        $booking = Order::find($request->order_id);

        if (!$booking) {
            return response()->json([
                'status' => false,
                'message' => 'Booking not found',
            ], 404);
        }

        // বুকিং ডিলিট করা হচ্ছে
        $booking->delete();

        return response()->json([
            'status' => true,
            'message' => 'Booking deleted successfully',
        ]);
    }

    public function approveBooking(Request $request)
    {
        $booking = Order::find($request->order_id);

        if (!$booking) {
            return response()->json([
                'status' => false,
                'message' => 'Booking not found',
            ], 404);
        }

        // যদি status আগেই approved হয়
        if ($booking->status === 'approved') {
            return response()->json([
                'status' => false,
                'message' => 'Booking is already approved',
            ]);
        }

        $booking->status = 'Approve';
        $booking->save();

        return response()->json([
            'status' => true,
            'message' => 'Booking approved successfully',
        ]);
    }



}
