<?php

use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\BookingController;
use App\Http\Controllers\Api\CaregiverController;
use App\Http\Controllers\Api\CategoriesController;
use App\Http\Controllers\Api\CenterController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\ImageController;
use App\Http\Controllers\Api\MessagesController;
use App\Http\Controllers\Api\ServiceController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\ReportsController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Api\RatingController;
use App\Models\Center;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\BookingDetailController;
use App\Http\Controllers\Api\PaymentController;
use App\Models\Caregiver;
use Illuminate\Support\Facades\Auth;

// Users routes
Route::post('register/user', [UserController::class, 'register']);
Route::post('login/user', [UserController::class, 'login']);
Route::post('logout/user', [UserController::class, 'logout']);

// Reset Password
Route::controller(ResetPasswordController::class)->prefix('password')->group(function () {
    Route::post('/otp/send', "sendOtp")->name('password.otp.send');
    Route::post('/otp/check', "checkOtp")->name('password.otp.check');
    Route::put('/reset', "updatePassword")->name('password.update');
});

// Admins routes
Route::post('register/admin', [AdminController::class, 'register']);
Route::post('login/admin', [AdminController::class, 'login']);
Route::post('logout/admin', [AdminController::class, 'logout']);

// Caregivers routes
Route::post('register/caregiver', [CaregiverController::class, 'register']);
Route::post('login/caregiver', [CaregiverController::class, 'login']);
Route::post('logout/caregiver', [CaregiverController::class, 'logout']);

// Categories routes
Route::get('categories', [CategoriesController::class, 'index']); // Get all categories
Route::get('centers', [CenterController::class, 'index']); // Get all centers
// TODO get all services and Caregivers in the category
Route::get('categories/{category_id}/services', [ServiceController::class, 'category_services']); // services/{categoryId} must pass category id
Route::get('categories/{category_id}/caregivers', [CategoriesController::class, 'category_Caregivers']); // Caregivers/{categoryId} must pass category id

// Middleware group for routes requiring admin authentication
Route::get('services', [ServiceController::class, 'index']); // Get all services
Route::middleware('admin.auth')->group(function () {
    // Categories routes
    Route::get('categories/{id}', [CategoriesController::class, 'show']); // Show a category
    Route::post('categories', [CategoriesController::class, 'store']); // Store a new category
    Route::post('categories/{id}', [CategoriesController::class, 'update']); // Update a category
    Route::delete('categories/{id}', [CategoriesController::class, 'destroy']); // Delete a category

    // Services routes
    Route::post('services', [ServiceController::class, 'store']); // Store a new service
    Route::get('services/{id}', [ServiceController::class, 'show']); // Show a service
    Route::post('services/{id}', [ServiceController::class, 'update']); // Update a service
    Route::delete('services/{id}', [ServiceController::class, 'destroy']); // Delete a service

    // Centers routes
    Route::get('centers/{id}', [CenterController::class, 'show']); // Show a center
    Route::post('centers', [CenterController::class, 'store']); // Store a new center
    Route::post('centers/{id}', [CenterController::class, 'update']); // Update a center
    Route::delete('centers/{id}', [CenterController::class, 'destroy']); // Delete a center

    Route::get('getAllUsers', [AdminController::class, 'getAllUser']); // Get all User
    Route::get('getAllCaregivers', [AdminController::class, 'getAllCaregiver']); // Get all User

});


// Images routes
Route::post('upload_image', [ImageController::class, 'upload_image']);
Route::post('delete_image/{id}', [ImageController::class, 'delete_image']);
Route::post('add_user_image/{id}', [ImageController::class, 'add_user_image']);


// Bookings routes
/*
|--------------------------------------------------------------------------
| Bookings Routes
|--------------------------------------------------------------------------
| This section defines routes related to bookings management.
| These routes handle operations such as viewing, creating, updating,
| and deleting bookings, as well as specific actions like approving or
| rejecting bookings.
*/

Route::get('bookings', [BookingController::class, 'index']); // Get all bookings
Route::get('getAllBookings', [BookingController::class, 'getAllBookings']); // Get all bookings (alternative route)
Route::get('bookings/{id}', [BookingController::class, 'show']); // Get a specific booking
Route::post('bookings', [BookingController::class, 'store']); // Store a new booking
Route::post('bookings/{id}', [BookingController::class, 'update']); // Update a booking
Route::delete('bookings/{id}', [BookingController::class, 'destroy']); // Delete a booking
Route::delete('bookings', [BookingController::class, 'destroyAll']); // Delete all bookings (careful!)
Route::get('bookings/{id}/edit', [BookingController::class, 'edit']); // Get the edit view for a booking
Route::get('bookingsadmin', [BookingController::class, 'bookingAdmin']); // Get bookings for admin
Route::get('bookingsuser', [BookingController::class, 'bookingUser']); // Get bookings for user
Route::get('bookingscaregiver', [BookingController::class, 'bookingCaregiver']); // Get bookings for caregiver
Route::post('bookings/{id}/approve-or-reject', [BookingController::class, 'approveOrReject']); // Approve or reject a booking


// ====================== BookingDetail api ======================================
// Route to get the total count of bookings for the authenticated user
Route::get('user-profile-statistics', [BookingDetailController::class, 'getUserProfileStatistics']);
// Route to get the total count of bookings for the authenticated caregiver
Route::get('caregiver-profile-statistics', [BookingDetailController::class, 'getCaregiverProfileStatistics']);










// ====================== chat api ======================================
// /chat [get]
// /chat [post]
// /chat/{1} [get]
Route::apiResource('chat', ChatController::class)->only(['index', 'store', 'show']);
Route::apiResource('chat_message', MessagesController::class)->only(['index', 'store']); // chat_id, receiver_id, message, created_by
Route::apiResource('user', UserController::class)->only(['index']);

//===========================NOTIFICATION ROUTE=========================
Route::post('send-notification', [NotificationController::class, 'sendNotification']);
Route::get('user-notifications', [NotificationController::class, 'userNotifications']);
Route::get('caregiver-notifications', [NotificationController::class, 'caregiverNotifications']);



//======================== reports api ===================================

// caregiver , admin and user roles

Route::get('get_all_reports', [ReportsController::class, 'index']);
Route::get('get_one_report/{id}', [ReportsController::class, 'show']);

// caregiver roles
Route::group(['middleware' => 'caregiver.auth'], function () {
    Route::post('store_report', [ReportsController::class, 'store']);
    Route::put('update_report/{id}', [ReportsController::class, 'update']);
    Route::delete('delete_report/{id}', [ReportsController::class, 'destroy']);
    Route::post('caregiver-profile-update', [CaregiverController::class, 'update']);
});

// Users roles
Route::middleware('auth:api')->group(function () {
    Route::post('user-profile-update', [UserController::class, 'update']);
});
Route::get('/my-wallet', [UserController::class, 'myWallet']);


// rating routes


Route::post('/ratings', [RatingController::class, 'store']);
Route::get('/caregiver/{caregiver_id}/ratings', [RatingController::class, 'index']);
Route::get('/caregiver/{caregiver_id}/average-rating', [RatingController::class, 'averageRating']);

//============================ statistics ================================
Route::get('statistics', [CaregiverController::class, 'statistics']);

//============================ payment =================================
Route::post('/create-payment-intent', [PaymentController::class, 'createPaymentIntent']);
Route::get('/wallet-charging-order', [PaymentController::class, 'walletChargingOrder']);

// get popular caregivers in your center
Route::get('/popular-caregivers', function () {
    if (!auth()->check()) {
        return response()->json([
            'message' => "your ar not authorized"
        ], 401);
    }
    $popular = Caregiver::where('center_id', auth()->user()->center_id)->where('stars', '>=', 4.5)->get();
    $popular->makeHidden(["professional_card_image", "id_card_image", "access_token"]);
    return response()->json([
        'data' => $popular,
    ], 200);
});



