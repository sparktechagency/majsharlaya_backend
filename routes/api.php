<?php

use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\ProviderCompanyController;
use App\Http\Controllers\Admin\ServiceController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Company\CompanyOrderController;
use App\Http\Controllers\Company\CompanySettingController;
use App\Http\Controllers\Company\ServiceProviderController;
use App\Http\Controllers\Company\SettingsController as CompanySettingsController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\User\OrderController;
use App\Http\Controllers\User\SettingsController as UserSettingsController;
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

    // ADMIN and COMPANY
    Route::middleware(['admin', 'company'])->group(function () {
        Route::post('edit-profile', [SettingsController::class, 'editProfile']);
        Route::post('change-avatar', [SettingsController::class, 'changeAvatar']);
    });

    // ADMIN
    Route::middleware('admin')->prefix('admin')->group(function () {
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

        // company
        Route::post('/create-company', [ProviderCompanyController::class, 'createCompany']);
        Route::get('/get-companies', [ProviderCompanyController::class, 'getCompanies']);
        Route::delete('/delete-company', [ProviderCompanyController::class, 'deleteCompany']);
        Route::post('/change-password-company', [ProviderCompanyController::class, 'changePasswordCompany']);
        Route::get('/view-company', [ProviderCompanyController::class, 'viewCompany']);
        Route::get('/filter-companies', [ProviderCompanyController::class, 'filterCompanies']);
        Route::get('/search-filter-companies', [ProviderCompanyController::class, 'searchFilterCompanies']);

        // settings
        Route::post('edit-profile', [SettingsController::class, 'editProfile']);
        Route::post('change-avatar', [SettingsController::class, 'changeAvatar']);
        Route::post('/create-page', [SettingsController::class, 'createPage']);
        

    });

    // COMPANY
    Route::middleware('company')->prefix('company')->group(function () {
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
        Route::get('/search-filter-providers', [ServiceProviderController::class, 'searchFilterProviders']);

        // order
        Route::get('/get-approve-posts', [CompanyOrderController::class, 'getApprovePosts']);
        Route::put('/assign-provider', [CompanyOrderController::class, 'assignProvider']);
        Route::get('/search-assign-providers', [CompanyOrderController::class, 'searchAssignProviders']);
        Route::post('/send-delivery-request', [CompanyOrderController::class, 'sendDeliveryRequest']);

        // setting
        Route::post('edit-company-profile', [CompanySettingsController::class, 'editCompanyProfile']);
        Route::post('change-company-avatar', [CompanySettingsController::class, 'changeCompanyAvatar']);

    });

    // USER
    Route::middleware('user')->prefix('user')->group(function () {

        // retail

        // service
        Route::get('/my-booking-lists', [OrderController::class, 'myBookingLists']);
        Route::get('/get-services', [OrderController::class, 'getServices']);
        Route::get('/search-service-companies', [OrderController::class, 'searchServiceCompanies']);
        Route::get('/view-service-company', [OrderController::class, 'viewServiceCompany']);
        Route::post('/create-order', [OrderController::class, 'createOrder']);
        Route::get('/get-order', [OrderController::class, 'getOrder']);
        Route::post('/accept-delivery', [OrderController::class, 'acceptDelivery']);
        Route::post('/feedback', [OrderController::class, 'feedback']);

        // notifications 
        Route::get('/get-notifications', [NotificationController::class, 'getNotifications']);
        Route::post('/read', [NotificationController::class, 'read']);
        Route::post('/read-all', [NotificationController::class, 'readAll']);
        Route::get('/unread-count', [NotificationController::class, 'unreadCount']);

        // // setting
        Route::post('/edit-user-profile',[UserSettingsController::class,'editUserProfile']);
        Route::get('/get-page', [UserSettingsController::class, 'getPage']);
    });
});
