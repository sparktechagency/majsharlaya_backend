<?php

use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\ProviderCompanyController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Company\CompanySettingController;
use App\Http\Controllers\Company\ServiceProviderController;
use App\Http\Controllers\User\OrderController;
use App\Models\ProviderCompany;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// public route for user
Route::post('/register', [AuthController::class, 'register']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/resend-otp', [AuthController::class, 'resendOtp']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

// private route for user
Route::middleware('auth:api')->group(function () {
    Route::get('/logout', [AuthController::class, 'logout']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);
    Route::get('/profile', [AuthController::class, 'profile']);
    Route::post('/update-password', [AuthController::class, 'updatePassword']);

    // ADMIN
    Route::middleware('admin')->group(function () {

        // users       
        Route::get('/get-users', [UserController::class, 'getUsers']);
        Route::get('/view-user', [UserController::class, 'viewUser']);
        Route::delete('/delete-user', [UserController::class, 'deleteUser']);

        // bookings
        Route::get('/get-bookings', [BookingController::class, 'getBookings']);
        Route::get('/view-booking', [BookingController::class, 'viewBooking']);
        Route::delete('/delete-booking', [BookingController::class, 'deleteBooking']);
        Route::put('/approve-booking', [BookingController::class, 'approveBooking']);



        // service
        Route::post('/create-service', [ServiceController::class, 'createService']);
        Route::post('/create-page', [ServiceController::class, 'createPage']);

        Route::post('/add-field', [ServiceController::class, 'addField']);
        Route::post('/add-button', [ServiceController::class, 'addButton']);
        Route::post('/button-action-modal', [ServiceController::class, 'buttonActionModal']);
        Route::post('/add-selection', [ServiceController::class, 'addSelection']);
        Route::post('/add-select-area-item', [ServiceController::class, 'addSelectAreaItem']);

        Route::get('/get-services', [ServiceController::class, 'getServices']);
        Route::get('/get-service-lists', [ServiceController::class, 'getServiceLists']);

        // provider
        Route::post('/create-provider-company', [ProviderCompanyController::class, 'createProviderCompany']);
        Route::get('/get-provider-companies', [ProviderCompanyController::class, 'getProviderCompanies']);
        Route::delete('/delete-provider-company', [ProviderCompanyController::class, 'deleteProviderCompany']);
        Route::post('/change-password-provider-company', [ProviderCompanyController::class, 'changePasswordProviderCompany']);
        Route::get('/view-provider-company', [ProviderCompanyController::class, 'viewProviderCompany']);
        Route::get('/filter-provider-companies', [ProviderCompanyController::class, 'filterProviderCompanies']);
        Route::get('/search-filter-provider-companies', [ProviderCompanyController::class, 'searchFilterProviderCompanies']);

        // settings
        Route::post('edit-profile', [SettingsController::class, 'editProfile']);
        // Route::post('update-password',[SettingsController::class,'updatePassword']);
        Route::post('change-avatar', [SettingsController::class, 'changeAvatar']);
    });

    // COMPANY
    Route::middleware('company')->group(function () {
        // company setting
        Route::put('/update-company-setting', [CompanySettingController::class, 'updateCompanySetting']);
        Route::post('/add-service', [CompanySettingController::class, 'addService']);
        Route::put('/edit-service', [CompanySettingController::class, 'editService']);
        Route::delete('/delete-service', [CompanySettingController::class, 'deleteService']);
        Route::get('/get-provider-company', [CompanySettingController::class, 'getProviderCompany']);

        // service provider
        Route::post('/add-provider', [ServiceProviderController::class, 'addProvider']);
        Route::get('/get-providers', [ServiceProviderController::class, 'getProviders']);
        Route::get('/view-provider', [ServiceProviderController::class, 'viewProvider']);
        Route::delete('/delete-provider', [ServiceProviderController::class, 'deleteProvider']);
        Route::get('/filter-providers', [ServiceProviderController::class, 'filterProviders']);


    });

    // USER
    Route::middleware('user')->group(function () {
        Route::post('create-order',[OrderController::class,'createOrder']);
    });
});
