<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProviderCompany;
use App\Models\Review;
use App\Models\Service;
use App\Models\ServiceList;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ProviderCompanyController extends Controller
{
    public function createProviderCompany(Request $request)
    {
        // Validation Rules
        $validator = Validator::make($request->all(), [
            'provider_types' => 'required',
            'city' => 'sometimes|string',
            'state' => 'sometimes|string',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        // Validation Errors
        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => $validator->errors(),
            ], 422);
        }

        // যদি provider_types stringified JSON হয়:
        $providerTypes = is_string($request->provider_types)
            ? json_decode($request->provider_types, true)
            : $request->provider_types;

        $provider_company = User::create([
            'name' => 'Unknown',
            'role' => 'COMPANY',
            'status' => 'active',
            'city' => $request->city,
            'state' => $request->state,
            'email' => $request->email,
            'email_verified_at' => Carbon::now(),
            'password' => bcrypt($request->password),
            'company_type' => $request->provider_types
        ]);

        foreach ($providerTypes as $value) {
            ServiceList::create([
                'user_id' => $provider_company->id,
                'service_name' => $value,
            ]);
        }

        return response()->json([
            'status' => true,
            'message' => 'Provider_company created successful',
            'data' => $provider_company
        ], 201);
    }

    public function getProviderCompanies(Request $request)
    {
        $search = $request->input('search'); // ?search=dhaka, etc

        $usersQuery = User::where('role', 'COMPANY');

        if ($search) {
            $usersQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%$search%");
                // ->orWhere('email', 'like', "%$search%")
                // ->orWhere('city', 'like', "%$search%")
                // ->orWhere('state', 'like', "%$search%");
            });
        }

        $users = $usersQuery->paginate(10);

        $users->map(function ($user) {
            unset($user->types);

            $user->service_list_names = ServiceList::where('user_id', $user->id)->pluck('service_name');
            $user->plus_counts = $user->service_list_names->count() - 1;

            // ✅ avg rating and total count from reviews
            $user->avgRating = round(Review::where('provider_company_id', $user->id)->avg('rating'), 1);
            $user->totalReviews = Review::where('provider_company_id', $user->id)->count();

            return $user;
        });

        return response()->json([
            'status' => true,
            'message' => $search ? "Searching by company name" : "Get all provider companies",
            'data' => $users
        ], 200);
    }

    public function filterProviderCompanies(Request $request)
    {
        $inputType = $request->input('type'); // example: 'plamber'

        if (!$inputType) {
            return response()->json([
                'status' => false,
                'message' => 'Type field is required'
            ], 422);
        }

        $companies = User::where('role', 'COMPANY')->get();

        $matched = $companies->filter(function ($company) use ($inputType) {
            $types = json_decode($company->company_type, true);

            if (!is_array($types))
                return false;

            return in_array($inputType, $types);
        })->values();

        return response()->json([
            'status' => true,
            'message' => 'Filter by ' . $inputType,
            'data' => $matched
        ]);
    }

    public function deleteProviderCompany(Request $request)
    {
        $company = User::where('id', $request->provider_company_id)->where('role', 'COMPANY')->first();

        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Provider company not found or not a COMPANY user.'
            ], 404);
        }

        $company->delete();

        return response()->json([
            'status' => true,
            'message' => 'Provider company deleted successfully.'
        ], 200);
    }

    public function changePasswordProviderCompany(Request $request)
    {
        $request->validate([
            'provider_company_id' => 'required|exists:users,id',
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed', // new_password_confirmation লাগবে
        ]);

        $user = User::where('id', $request->provider_company_id)
            ->where('role', 'COMPANY')
            ->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Company user not found.',
            ], 404);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Current password is incorrect.',
            ], 401);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'status' => true,
            'message' => 'Password changed successfully.',
        ]);
    }

    public function viewProviderCompany(Request $request)
    {
        $request->validate([
            'provider_company_id' => 'required|numeric',
        ]);

        $user = User::where('id', $request->provider_company_id)
            ->where('role', 'COMPANY')
            ->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Provider company not found.',
            ], 404);
        }

        // Optional: types remove if exists
        unset($user->types);


        // Optional: service name list
        // $user->service_list_names = $user->serviceLists->pluck('service_name');
        $user->service_list_names = ServiceList::where('user_id', $user->id)->pluck('service_name');

        // avg rating and total count form reviews
        $user->avgRating = Review::where('provider_company_id', $user->id)->avg('rating');
        $user->totalReviews = Review::where('provider_company_id', $user->id)->count();

        return response()->json([
            'status' => true,
            'message' => 'Provider company profile',
            'data' => $user
        ]);
    }

    public function searchFilterProviderCompanies(Request $request)
    {
        $search = $request->input('search');    // for name search
        $type = $request->input('type');        // for filtering company_type

        $usersQuery = User::where('role', 'COMPANY');

        // 🔍 Search by name/email/city/state
        if ($search) {
            $usersQuery->where(function ($query) use ($search) {
                $query->where('name', 'like', "%$search%")
                    ->orWhere('email', 'like', "%$search%")
                    ->orWhere('city', 'like', "%$search%")
                    ->orWhere('state', 'like', "%$search%");
            });
        }

        $users = $usersQuery->get();

        // 🔎 Filter by type (like plamber, cutting)
        if ($type) {
            $users = $users->filter(function ($user) use ($type) {
                $types = json_decode($user->company_type, true);
                return is_array($types) && in_array($type, $types);
            })->values();
        }

        // 🛠️ Enhance data
        $users->map(function ($user) {
            unset($user->types);
            $user->service_list_names = ServiceList::where('user_id', $user->id)->pluck('service_name');
            $user->plus_counts = $user->service_list_names->count() - 1;
            $user->avgRating = round(Review::where('provider_company_id', $user->id)->avg('rating'), 1);
            $user->totalReviews = Review::where('provider_company_id', $user->id)->count();
            return $user;
        });

        return response()->json([
            'status' => true,
            'message' => $search || $type ? ($search && $type ? 'Filtered Companies' : ($search ? 'Searched Companies' : 'Filtered Companies')) : 'All Companies',
            'data' => $users
        ], 200);
    }
}
