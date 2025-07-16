<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    public function editCompanyProfile(Request $request)
    {
        $user = User::where('id', Auth::id())->where('role', 'COMPANY')->first();

        // Validation Rules
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string',
            'email' => 'required|email',
        ]);

        // Validation Errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $user->name = $request->company_name;
        $user->email = $request->email;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Company profile update successful',
            'data' => $user
        ], 201);
    }

    public function changeCompanyAvatar(Request $request)
    {
        $user = User::where('id', Auth::id())->where('role', 'COMPANY')->first();

        $validator = Validator::make($request->all(), [
            'avatar' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048', // 2MB max
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        if ($request->hasFile('avatar')) {
            if ($user->avatar && file_exists(public_path($user->avatar))) {
                unlink(public_path($user->avatar));
            }

            $file = $request->file('avatar');
            $filename = time() . '_' . $file->getClientOriginalName();
            $filepath = $file->storeAs('images', $filename, 'public');

            $user->avatar = '/storage/' . $filepath;
            $user->save();

            return response()->json([
                'status' => true,
                'message' => 'Company avatar updated successfully!',
                'path' => $user->avatar,
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'No image uploaded!',
        ], 400);
    }
}
