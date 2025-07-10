<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function getUsers(Request $request)
    {
        // optional query param: search
        $search = $request->input('search');

        // যদি search থাকে তাহলে name বা email match করবো
        $users = User::when($search, function ($query, $search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        })->get();

        return response()->json([
            'status' => true,
            'message' => $search ? 'Search results' : 'All users',
            'data' => $users
        ]);
    }
}
