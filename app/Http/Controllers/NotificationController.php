<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\Validator;

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

        // ✅ Query builder দিয়ে নাও
        // $notifications = $user->notifications()->latest()->take(10)->get();
        // $notification_id = $user->notifications->pluck('id');

        $notifications = DatabaseNotification::whereIn('id', $user->notifications->pluck('id'))->latest()->take(10)->get();

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

    public function read(Request $request)
    {
        // validation roles
        $validator = Validator::make($request->all(), [
            'notification_id' => 'required|string|exists:notifications,id',
        ]);

        // check validation
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        $notification = DatabaseNotification::find($request->notification_id);

        $notification->markAsRead();

        return response()->json([
            'status' => true,
            'message' => 'Read'
        ]);
    }

    // read all notification
    public function readAll(Request $request)
    {
        $ids = Auth::user()->unreadNotifications->pluck('id')->toArray();

        DatabaseNotification::whereIn('id', $ids)->update(['read_at' => now()]);
        return response()->json([
            'status' => true,
            'message' => 'Read all'
        ]);
    }

    //for unread notification count
    public function unreadCount()
    {
        return response()->json([
            'status' => true,
            'unread_count' => Auth::user()->unreadNotifications->count(),
        ]);
    }
}



