<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    public function getPage(Request $request)
    {
        $page = Page::where('page_type', $request->page_type)->first();

        if (!$page) {
            return response()->json([
                'status' => false,
                'message' => $request->page_type . ' page not found',
            ], 404);
        }

        $page->content = json_decode($page->content);

        return response()->json([
            'status' => true,
            'message' => 'Get ' . $request->page_type . ' page',
            'data' => $page
        ], 200);
    }

    public function editUserProfile(Request $request)
    {
        $user = Auth::user();

        // ✅ Validation
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'avatar' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        // ✅ Avatar Upload (if provided)
        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $filepath = $file->storeAs('avatars', $filename, 'public');

            // Optional: delete old avatar if exists
            if ($user->avatar && file_exists(public_path($user->avatar))) {
                @unlink(public_path($user->avatar));
            }

            $avatar = '/storage/' . $filepath;
        }

        // ✅ Update User
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'phone_number' => $request->phone_number,
            'address' => $request->address,
            'avatar' => $avatar
        ]);

        return response()->json([
            'status' => true,
            'message' => 'User profile updated successfully',
            'data' => $user
        ]);
    }

}
