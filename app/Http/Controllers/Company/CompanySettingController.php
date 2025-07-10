<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\ServiceList;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class CompanySettingController extends Controller
{

    public function updateCompanySetting(Request $request)
    {
        // Validation Rules
        $validator = Validator::make($request->all(), [
            'company_name' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string',
            'about' => 'required|string',
            'overview' => 'required|string',
            'images' => 'sometimes|array', // max 3 image
            'images.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        // Return Validation Errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $user = User::find(Auth::id());

        $paths = [];

        if ($request->hasFile('images')) {
            // ✅ পুরনো images delete করো
            if ($user->photo) {
                // $oldPhotos = json_decode($user->photo, true);
                $oldPhotos = is_string($user->photo) ? json_decode($user->photo, true) : $user->photo;

                if (is_array($oldPhotos)) {
                    foreach ($oldPhotos as $oldPhoto) {
                        $filePath = public_path($oldPhoto);
                        if (file_exists($filePath)) {
                            @unlink($filePath);
                        }
                    }
                }
            }

            // ✅ নতুন images save করো
            foreach ($request->file('images') as $image) {
                $paths[] = '/storage/' . $image->store('images', 'public');
            }
        }

        // ✅ Update user
        $user->update([
            'name' => $request->company_name,
            'city' => $request->city,
            'state' => $request->state,
            'about' => $request->about,
            'overview' => $request->overview,
            'photo' => !empty($paths) ? json_encode($paths) : $user->photo, // নতুন ছবি না থাকলে পুরনোটা রাখবে
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Profile updated successfully!',
        ]);
    }

    public function addService(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'service_name' => 'required|string|max:255',
            'starting_price' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $service = ServiceList::create([
            'user_id' => Auth::id(),
            'service_name' => $request->service_name,
            'starting_price' => $request->starting_price,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Service created successfully.',
            'data' => $service,
        ]);
    }

    public function editService(Request $request)
    {
        $service = ServiceList::find($request->service_list_id);

        if (!$service) {
            return response()->json([
                'status' => false,
                'message' => 'Not found from service list table.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'service_name' => 'sometimes|string|max:255',
            'starting_price' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        $service->update($request->only(['service_name', 'starting_price']));

        return response()->json([
            'status' => true,
            'message' => 'Service updated successfully.',
            'data' => $service,
        ]);
    }

    public function deleteService(Request $request)
    {
        $service = ServiceList::find($request->service_list_id);

        if (!$service) {
            return response()->json([
                'status' => false,
                'message' => 'Not found from service list table.',
            ], 404);
        }

        $service->delete();

        return response()->json([
            'status' => true,
            'message' => 'Service deleted successfully.',
        ]);
    }

    public function getProviderCompany(Request $request)
    {
        $provider = User::where('id', Auth::id())->where('role', 'COMPANY')->first();

        return response()->json([
            'status' => true,
            'message' => 'get provider company',
            'data' => $provider
        ]);
    }
}
