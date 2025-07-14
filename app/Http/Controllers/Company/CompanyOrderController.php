<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class CompanyOrderController extends Controller
{
    public function getApprovePosts(Request $request)
    {
        $search = $request->input('search');

        $type = $request->input('type');

        if($request->type == 'Pending'){
            $type = 'Approve';
        }
        
        $per_page = $request->input('per_page', 10);

        $query = Order::with('user')
            ->where('status', 'Approve') // ✅ শুধুমাত্র Approve status
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

}
