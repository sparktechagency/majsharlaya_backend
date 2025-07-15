<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

use function Pest\Laravel\json;

class SettingsController extends Controller
{
    public function editProfile(Request $request)
    {
        $user = User::where('id', Auth::id())->where('role', 'ADMIN')->first();

        // Validation Rules
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email',
        ]);

        // Validation Errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $user->name = $request->name;
        $user->email = $request->email;
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Profile update successful',
            'data' => $user
        ], 201);
    }

    public function changeAvatar(Request $request)
    {
        $user = User::where('id', Auth::id())->where('role', 'ADMIN')->first();

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
                'message' => 'Avatar updated successfully!',
                'path' => $user->avatar,
            ]);
        }

        return response()->json([
            'status' => false,
            'message' => 'No image uploaded!',
        ], 400);
    }

    public function privacyPolicy(Request $request)
    {
        return $this->storeOrUpdatePage($request, $request->page_type, $request->content);
    }

    public function aboutUs(Request $request)
    {
        return $this->storeOrUpdatePage($request, $request->page_type, $request->content);
    }

    public function termsConditions(Request $request)
    {
        return $this->storeOrUpdatePage($request, $request->page_type, $request->content);
    }

    private function storeOrUpdatePage(Request $request, $page_type, $content)
    {
        // Validation (only content)
        $request->validate([
            'content' => 'required|string',
        ]);

        // Update or Create based on 'type'
        $page = Page::updateOrCreate(
            ['page_type' => $page_type], // condition
            ['content' => json_encode($request->content)]
        );

        return response()->json([
            'status' => true,
            'message' => $page_type . ' page saved successfully.',
            'data' => [
                'page_type' => $page_type,
                'content' => $page->content,
            ],
        ]);
    }

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

}
