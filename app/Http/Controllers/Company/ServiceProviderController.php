<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class ServiceProviderController extends Controller
{
    public function addProvider(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email',
            'type' => 'required|string',
            'number' => 'nullable|string',
            'nid' => 'nullable|array', // image multiple হলে array
            'nid.*' => 'image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'address' => 'required|string',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // single image
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors()
            ], 422);
        }

        // ✅ Main image upload
        $imagePath = null;
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            $filename = time() . '_' . $file->getClientOriginalName();
            $filepath = $file->storeAs('images', $filename, 'public');
            $imagePath = '/storage/' . $filepath;
        }

        // ✅ Multiple NID images (optional)
        $nidPaths = [];
        if ($request->hasFile('nid')) {
            foreach ($request->file('nid') as $nidFile) {
                $nidPaths[] = '/storage/' . $nidFile->store('images', 'public');
            }
        }

        // ✅ type handle: JSON encode
        $type = $request->type;
        if (is_string($type)) {
            $type = json_decode($type, true);
        }

        $provider = Provider::create([
            'user_id' => Auth::id(),
            'image' => $imagePath,
            'name' => $request->name,
            'type' => json_encode($type), // JSON encode for DB
            'email' => $request->email,
            'number' => $request->number ?? null,
            'address' => $request->address,
            'nid' => !empty($nidPaths) ? json_encode($nidPaths) : null,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Provider added successfully',
            'data' => $provider
        ], 201);
    }

    public function getProviders(Request $request)
    {
        $search = $request->search;

        // Query builder দিয়ে শুরু করো
        $query = Provider::query();

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%");
                // ->orWhere('email', 'like', "%$search%")
                // ->orWhere('number', 'like', "%$search%");
            });
        }

        $providers = $query->get();

        // type ও nid ডিকোড
        $providers->transform(function ($provider) {

            $provider->type = json_decode($provider->type, true);
            $provider->nid = json_decode($provider->nid, true);

            return $provider;
        });

        return response()->json([
            'status' => true,
            'message' => $search ? 'Searching result' : 'Get providers',
            'data' => $providers
        ]);
    }

    public function viewProvider(Request $request)
    {
        $provider = Provider::find($request->provider_id);

        if (!$provider) {
            return response()->json([
                'status' => 'error',
                'message' => 'Provider not found'
            ], 404);
        }

        $provider->type = json_decode($provider->type, true);
        $provider->nid = json_decode($provider->nid, true);

        return response()->json([
            'status' => 'success',
            'data' => $provider
        ]);
    }

    public function deleteProvider(Request $request)
    {
        $provider = Provider::find($request->provider_id);

        if (!$provider) {
            return response()->json([
                'status' => 'error',
                'message' => 'Provider not found'
            ], 404);
        }

        $provider->delete();

        return response()->json([
            'status' => true,
            'message' => 'Provider deleted successfully'
        ]);
    }

    public function filterProviders(Request $request)
    {
        $status = $request->status;

        $query = Provider::query();

        if ($status === 'Available') {
            $query->where('status', 'Available');
        } elseif ($status === 'Not available') {
            $query->where('status', 'Not available');
        }

        $providers = $query->get();

        $providers->transform(function ($provider) {

            $provider->type = json_decode($provider->type, true);
            $provider->nid = json_decode($provider->nid, true);

            return $provider;
        });

        return response()->json([
            'status' => true,
            'message' => 'Filtered providers by status',
            'data' => $providers
        ]);
    }
}
