<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getUsers(Request $request)
    {
        $search = $request->input('search');

        $query = User::where('role', 'USER');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%");
                // ->orWhere('email', 'like', "%$search%")
                // ->orWhere('city', 'like', "%$search%");
            });
        }

        $users = $query->paginate($request->per_page ?? 10);

        return response()->json([
            'status' => true,
            'message' => $search ? 'Search result for ' . $search : 'All regular users',
            'data' => $users
        ]);
    }

    public function viewUser(Request $request)
    {
        $user = User::where('role', 'USER')->find($request->user_id);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'message' => $user->name . ' details',
            'data' => $user
        ]);
    }

    public function deleteUser(Request $request)
    {
        $user = User::where('role', 'USER')->find($request->user_id);

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'User not found',
            ], 404);
        }

        $user->delete();

        return response()->json([
            'status' => true,
            'message' => $user->name.' deleted successfully',
        ]);
    }


}
