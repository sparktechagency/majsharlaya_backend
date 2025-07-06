<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProviderCompany;
use App\Models\Service;
use App\Models\ServiceList;
use App\Models\User;
use Illuminate\Http\Request;
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
            'name' => 'unknown',
            'role' => 'COMPANY',
            'status' => 'active',
            'types' => $request->provider_types,
            'city' => $request->city,
            'state' => $request->state,
            'email' => $request->email,
            'password' => bcrypt($request->password)
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
        $users = User::where('role', 'COMPANY')->get();


        // প্রতিটি user এর সাথে তার service_lists যুক্ত করা
        $users->map(function ($user) {
            unset($user->types);
            // $user->service_list_names = $user->serviceLists->pluck('service_name'); auto lazy load
            $user->service_list_names = ServiceList::where('user_id', $user->id)->pluck('service_name');
            $user->plus_counts = ServiceList::where('user_id', $user->id)->pluck('service_name')->count() - 1;

            return $user;
        });

        return response()->json([
            'status' => true,
            'message' => 'Get all provider companies',
            'data' => $users
        ], 200);
    }

    public function deleteProviderCompany(Request $request)
    {
        // User খুঁজে বের করো যেটা কোম্পানি এবং ওই ID এর
        $company = User::where('id', $request->provider_company_id)->where('role', 'COMPANY')->first();

        if (!$company) {
            return response()->json([
                'status' => false,
                'message' => 'Provider company not found or not a COMPANY user.'
            ], 404);
        }

        // ডিলিট করো
        $company->delete();

        return response()->json([
            'status' => true,
            'message' => 'Provider company deleted successfully.'
        ], 200);
    }

    public function changePasswordProviderCompany(Request $request)
    {
        // ✅ Step 1: Validate input
        $request->validate([
            'provider_company_id' => 'required|exists:users,id',
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:6|confirmed', // new_password_confirmation লাগবে
        ]);

        // ✅ Step 2: ইউজার খোঁজো এবং কোম্পানি কিনা চেক করো
        $user = User::where('id', $request->provider_company_id)
            ->where('role', 'COMPANY')
            ->first();

        if (!$user) {
            return response()->json([
                'status' => false,
                'message' => 'Company user not found.',
            ], 404);
        }

        // ✅ Step 3: পুরনো পাসওয়ার্ড যাচাই করো
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'status' => false,
                'message' => 'Current password is incorrect.',
            ], 401);
        }

        // ✅ Step 4: নতুন পাসওয়ার্ড সেট করো
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
            // 'provider_company_id' => 'required|exists:users,id',
            'provider_company_id' => 'required|numeric',
        ]);

        $user = User::with('serviceLists') // relationship load
            ->where('id', $request->provider_company_id)
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
        $user->service_list_names = $user->serviceLists->pluck('service_name');

        return response()->json([
            'status' => true,
            'message' => 'Provider company profile',
            'data' => $user
        ]);
    }
}
