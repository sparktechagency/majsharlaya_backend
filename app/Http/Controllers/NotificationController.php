<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function getNotifications()
    {
        $user = Auth::user();

        // 🔐 যদি user null হয়
        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Unauthorized.',
            ], 401);
        }

        // 🔔 সর্বশেষ ১০টি notification
        $notifications = $user->notifications()->latest()->take(10)->get();

        // 🎯 ফরম্যাট করে রিটার্ন
        $formatted = $notifications->transform(function (DatabaseNotification $notification) {
            return [
                'id' => $notification->id,
                'order_id' => $notification->data['order_id'] ?? null,
                'user_id' => $notification->data['user_id'] ?? null,
                'message' => $notification->data['message'] ?? '',
                'read_at' => $notification->read_at,
                'created_at' => optional($notification->created_at)->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'status' => true,
            'message' => 'Latest 10 notifications',
            'data' => $formatted
        ]);
    }
}



