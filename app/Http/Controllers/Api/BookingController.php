<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Caregiver;
use App\Models\Service;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

use Illuminate\Support\Facades\Notification;
use App\Notifications\MakeBookingNotification;
use Illuminate\Support\Facades\Hash;

class BookingController extends Controller
{
    /**
     * Retrieve bookings based on user or caregiver ID and return formatted JSON response.
     *
     * @param Request $request The HTTP request instance.
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            // Check if the authenticated user is an admin
            if (Auth::guard('admin')->check()) {
                $bookings = Booking::all();
            } elseif (Auth::guard('api')->check()) {
                $authenticatedUser = Auth::guard('api')->user();
                $bookings = Booking::where('user_id', $authenticatedUser->id)->get();
            } elseif (Auth::guard('caregiver')->check()) {
                $authenticatedCaregiver = Auth::guard('caregiver')->user();
                $bookings = Booking::where('caregiver_id', $authenticatedCaregiver->id)->get();
            } else {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            $formattedBookings = [];

            foreach ($bookings as $booking) {
                $userName = $booking->user->name;
                $caregiverName = $booking->caregiver->name;

                if (!empty($booking->services)) {
                    // Decode service IDs and fetch corresponding services
                    $serviceIds = json_decode($booking->services, true);
                    $serviceIds = Arr::flatten($serviceIds);
                    $services = Service::whereIn('id', $serviceIds)->get();

                    // Calculate total price of services
                    $totalPrice = $services->sum('price');
                } else {
                    // Return appropriate message if no services selected
                    return response()->json(['message' => 'No services selected for booking with ID: ' . $booking->id], 404);
                }

                // Retrieve location data
                $locationData = DB::table('bookings')
                    ->select(DB::raw('X(location) as latitude, Y(location) as longitude'))
                    ->where('id', $booking->id)
                    ->first();

                $location = [
                    'latitude' => $locationData->latitude,
                    'longitude' => $locationData->longitude
                ];

                // Prepare formatted booking data
                $formattedBooking = [
                    'id' => $booking->id,
                    'user_name' => $userName,
                    'caregiver_name' => $caregiverName,
                    'services' => $services,
                    'total_price' => $totalPrice,
                    'location' => $location,
                    'status' => $booking->approval_status, // Use the stored approval status directly
                    'phone_number' => $booking->phone_number,
                ];

                $formattedBookings[] = $formattedBooking;
            }

            // Return formatted bookings as JSON response
            return response()->json(['bookings' => $formattedBookings], 200);
        } catch (\Exception $e) {
            // Handle exceptions
            return response()->json(['error' => 'An error occurred while processing the request.'], 500);
        }
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {

        try {
            // Ensure the current authenticated user is making the booking
            if (auth()->check()) {
                $request->merge(['user_id' => auth()->id()]);
            } else {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            // Validating the request data
            $validatedData = $request->validate([
                'location.latitude' => 'required|numeric',
                'location.longitude' => 'required|numeric',
                'user_id' => 'required|exists:users,id',
                'services.*' => 'required|exists:services,id',
                'caregiver_id' => 'required|exists:caregivers,id',
                'booking_date' => 'required|date', // Make booking_date required
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'phone_number' => 'required|string|digits:11',
            ]);

            // Set start_date to booking_date if not provided
            if (empty($validatedData['start_date'])) {
                $validatedData['start_date'] = $validatedData['booking_date'];
            }

            // Check if approval_status is set, otherwise set to null
            $approvalStatus = array_key_exists('approval_status', $validatedData) ? $validatedData['approval_status'] : null;

            // Calculate total price of services
            $totalPrice = Service::whereIn('id', $validatedData['services'])->sum('price');

            //? wallet transactions

            try {
                $user = auth()->user();
                if (!Hash::check($request->password, $user->password)) {
                    return response()->json(['errorEn' => 'Incorrect password', 'errorAr' => 'كلمة المرور خاطئة '], 401);
                }
                $user->wallet->decreaseBalance($totalPrice);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 400);
            }
            //?
            // Store location as POINT in the database
            $latitude = $validatedData['location']['latitude'];
            $longitude = $validatedData['location']['longitude'];
            $point = DB::raw("POINT($longitude, $latitude)");

            // Calculate end date
            $startDate = Carbon::parse($validatedData['start_date']);
            $endDate = $startDate->copy()->addDays(2); // Add 2 days to start date

            // Create new booking instance
            $booking = new Booking();
            $booking->fill([
                'user_id' => $validatedData['user_id'],
                'services' => json_encode($validatedData['services']),
                'caregiver_id' => $validatedData['caregiver_id'],
                'total_price' => $totalPrice,
                'booking_date' => $validatedData['booking_date'],
                'start_date' => $validatedData['start_date'],
                'end_date' => $endDate, // Set end date
                'location' => $point,
                'phone_number' => $validatedData['phone_number'],
            ]);
            $booking->save();

            // Return the latitude and longitude in the response
            $location = [
                'latitude' => $latitude,
                'longitude' => $longitude,
            ];
            //user notifications for Making booking
            $user_id = auth()->user()->id;
            $user = User::where('id', $user_id)->first();
            $caregiver = Caregiver::firstWhere('id', $validatedData['caregiver_id'])->name;

            DB::table('notifications')->insert([
                'Owner' => 'p',
                'Owner_id' => $user_id,
                "data" => json_encode([
                    'msg_en' => 'Booking created successfully with ' . $caregiver,
                    'msg_ar' => " تم ارسال طلب حجز بنجاح الي" . $caregiver
                ])
            ]);
            DB::table('notifications')->insert([
                'Owner' => 'c',
                'Owner_id' => $validatedData['caregiver_id'],
                "data" => json_encode([
                    'msg_en' => 'New Booking from ' . auth()->user()->name,
                    'msg_ar' => " هناك طلب موعد جديد من" .  auth()->user()->name
                ])
            ]);
            // Notification::send($user,MakeBookingNotification($user_id, $messageEn));

            return response()->json(['message' => 'Booking created successfully', 'booking' => $booking, 'location' => $location], 201);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    /**
     * Display the details of a booking.
     * Shows bookings associated with the user who opened them or the caregiver associated with them.
     * Admin can view all bookings.
     */
    public function show($id): JsonResponse
    {
        try {
            $booking = Booking::findOrFail($id);

            // Retrieve user and caregiver names
            $userName = $booking->user->name;
            $caregiverName = $booking->caregiver->name;

            // Retrieve services and calculate total price
            $services = Service::whereIn('id', json_decode($booking->services))->get();
            $totalPrice = $services->sum('price');

            // Retrieve location data
            $locationData = DB::table('bookings')
                ->select(DB::raw('X(location) as latitude, Y(location) as longitude'))
                ->where('id', $id)
                ->first();

            $location = [
                'latitude' => $locationData->latitude,
                'longitude' => $locationData->longitude
            ];

            // Prepare response data
            $data = [
                'user_name' => $userName,
                'caregiver_name' => $caregiverName,
                'services' => $services,
                'total_price' => $totalPrice,
                'location' => $location,
                'status' => $booking->approval_status, // Use the stored approval status directly
                'phone_number' => $booking->phone_number,
            ];

            return response()->json($data, 200);
            // Retrieve user and caregiver names
            $userName = $booking->user->name;
            $caregiverName = $booking->caregiver->name;

            // Retrieve services and calculate total price
            $services = Service::whereIn('id', json_decode($booking->services))->get();
            $totalPrice = $services->sum('price');

            // Retrieve location data
            $locationData = DB::table('bookings')
                ->select(DB::raw('X(location) as latitude, Y(location) as longitude'))
                ->where('id', $id)
                ->first();

            $location = [
                'latitude' => $locationData->latitude,
                'longitude' => $locationData->longitude
            ];

            // Prepare response data
            $data = [
                'user_name' => $userName,
                'caregiver_name' => $caregiverName,
                'services' => $services,
                'total_price' => $totalPrice,
                'location' => $location,
                'status' => $booking->approval_status, // Use the stored approval status directly
                'phone_number' => $booking->phone_number,
            ];
            return response()->json($data, 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Booking not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Edit a booking.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit(int $id): JsonResponse
    {
        try {
            $booking = Booking::findOrFail($id);

            // Check if the authenticated user is authorized to edit this booking
            if (auth()->check() && auth()->id() == $booking->user_id) {
                return response()->json(['booking' => $booking], 200);
            } else {
                return response()->json(['error' => 'Unauthorized'], 401);
            }
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function update(Request $request, $id): JsonResponse
    {
        try {
            // Ensure the current authenticated user is making the booking

            // Retrieve the booking by its ID
            $booking = Booking::findOrFail($id);

            // Validating the request data
            $validatedData = $request->validate([
                'location.latitude' => 'required|numeric',
                'location.longitude' => 'required|numeric',
                'services.*' => 'required|exists:services,id',
                'caregiver_id' => 'required|exists:caregivers,id',
                'booking_date' => 'nullable|date',
                'start_date' => 'nullable|date',
                'end_date' => 'nullable|date',
                'phone_number' => 'required|string|digits:11',
                'approval_status' => 'nullable|boolean',
            ]);

            // Check if approval_status is set, otherwise set to null
            $approvalStatus = array_key_exists('approval_status', $validatedData) ? $validatedData['approval_status'] : null;

            // Calculate total price of services
            $totalPrice = Service::whereIn('id', $validatedData['services'])->sum('price');

            // Store location as POINT in the database
            $latitude = $validatedData['location']['latitude'];
            $longitude = $validatedData['location']['longitude'];
            $point = DB::raw("POINT($longitude, $latitude)");

            // Calculate end date
            $startDate = Carbon::parse($validatedData['start_date']);
            $endDate = $startDate->copy()->addDays(2); // Add 2 days to start date

            // Update the booking instance
            $booking->fill([
                'services' => json_encode($validatedData['services']),
                'caregiver_id' => $validatedData['caregiver_id'],
                'total_price' => $totalPrice,
                'booking_date' => $validatedData['booking_date'],
                'start_date' => $validatedData['start_date'],
                'end_date' => $endDate, // Set end date,
                'location' => $point,
                'phone_number' => $validatedData['phone_number'],
                'approval_status' => $approvalStatus,
            ]);
            $booking->save();

            // Return the updated booking details as JSON response
            return response()->json(['message' => 'Booking updated successfully', 'booking' => $booking], 200);
        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 400);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }



    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        try {
            // Retrieve the booking by its ID
            $booking = Booking::findOrFail($id);
            // Delete the booking
            $booking->delete();

            // Return a success message
            return response()->json(['message' => 'Booking deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Booking not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove all resources from storage.
     */
    public function destroyAll(): JsonResponse
    {
        try {
            // Delete all bookings
            Booking::query()->delete();

            // Return success message
            return response()->json(['message' => 'All bookings deleted successfully'], 200);
        } catch (\Exception $e) {
            // Handle exceptions
            return response()->json(['error' => 'An error occurred while deleting bookings: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Retrieve bookings for a specific admin.
     */
    public function bookingAdmin(Request $request): JsonResponse
    {
        try {
            // Check if the authenticated user is an admin
            if (!Auth::guard('admin')->check()) {
                return response()->json(['error' => 'Unauthorized'], 401);
            }

            // Retrieve all bookings
            $bookings = Booking::all();
            $formattedBookings = [];

            foreach ($bookings as $booking) {
                $userName = $booking->user->name;
                $caregiverName = $booking->caregiver->name;

                if (!empty($booking->services)) {
                    // Decode service IDs and fetch corresponding services
                    $serviceIds = json_decode($booking->services, true);
                    $serviceIds = Arr::flatten($serviceIds);
                    $services = Service::whereIn('id', $serviceIds)->get();

                    // Calculate total price of services
                    $totalPrice = $services->sum('price');
                } else {
                    // Return appropriate message if no services selected
                    return response()->json(['message' => 'No services selected for booking with ID: ' . $booking->id], 404);
                }

                // Retrieve location data
                $locationData = DB::table('bookings')
                    ->select(DB::raw('X(location) as latitude, Y(location) as longitude'))
                    ->where('id', $booking->id)
                    ->first();

                $location = [
                    'latitude' => $locationData->latitude,
                    'longitude' => $locationData->longitude
                ];

                // Prepare formatted booking data
                $formattedBooking = [
                    'id' => $booking->id,
                    'user_name' => $userName,
                    'caregiver_name' => $caregiverName,
                    'services' => $services,
                    'total_price' => $totalPrice,
                    'location' => $location,
                    'status' => $booking->approval_status, // Use the stored approval status directly
                    'phone_number' => $booking->phone_number,
                ];

                $formattedBookings[] = $formattedBooking;
            }

            // Return formatted bookings as JSON response
            return response()->json(['bookings' => $formattedBookings], 200);
        } catch (\Exception $e) {
            // Handle exceptions
            return response()->json(['error' => 'An error occurred while processing the request.'], 500);
        }
    }
    /**
     * Retrieve bookings for a specific user.
     */
    public function bookingUser(): JsonResponse
    {
        // try {
        // Get the authenticated user
        $authenticatedUser = Auth::user();

        // Retrieve all bookings associated with the authenticated user
        $bookings = Booking::where('user_id', $authenticatedUser->id)->get();

        $formattedBookings = [];

        foreach ($bookings as $booking) {
            $userName = $booking->user->name;
            $caregiverName = $booking->caregiver->name;
            $caregiverAvatar = $booking->caregiver->profile_image;

            if (!empty($booking->services)) {
                // Decode service IDs and fetch corresponding services
                $serviceIds = json_decode($booking->services, true);
                $serviceIds = Arr::flatten($serviceIds);
                $services = Service::whereIn('id', $serviceIds)->with('category')->get();

                // Calculate total price of services
                $totalPrice = $services->sum('price');
            } else {
                // Return appropriate message if no services selected
                return response()->json(['message' => 'No services selected for booking with ID: ' . $booking->id], 404);
            }

            // Retrieve location data
            $locationData = DB::table('bookings')
                ->select(DB::raw('X(location) as latitude, Y(location) as longitude'))
                ->where('id', $booking->id)
                ->first();

            $location = [
                'latitude' => $locationData->latitude,
                'longitude' => $locationData->longitude
            ];

            // Prepare formatted booking data
            $formattedBooking = [
                'id' => $booking->id,
                'user_name' => $userName,
                'caregiver_name' => $caregiverName,
                'caregiver_profile_image' => $caregiverAvatar,
                'start_date' => $booking->start_date,
                'services' => $services,
                'total_price' => $totalPrice,
                'location' => $location,
                'status' => $booking->approval_status, // Use the stored approval status directly
                'phone_number' => $booking->phone_number,
            ];

            $formattedBookings[] = $formattedBooking;
        }

        // Return formatted bookings as JSON response
        return response()->json(['bookings' => $formattedBookings], 200);
        // } catch (\Exception $e) {
        //     // Handle exceptions
        //     return response()->json(['error' => 'An error occurred while processing the request.'], 500);
        // }
    }
    /**
     * Retrieve bookings associated with a specific caregiver.
     */
    public function bookingCaregiver()
    {
        try {
            if (!auth()->guard('caregiver')->check()) {
                return response()->json([
                    "message"  => "you are not authorized"
                ], 401);
            }
            // Get the authenticated caregiver
            $authenticatedCaregiver = Auth::guard('caregiver')->user();

            // Retrieve all bookings associated with the authenticated caregiver
            $bookings = Booking::where('caregiver_id', $authenticatedCaregiver->id)->get();
            $formattedBookings = [];

            foreach ($bookings as $booking) {
                $userName = $booking->user->name;
                $caregiverName = $booking->caregiver->name;
                if (!empty($booking->services)) {
                    // Decode service IDs and fetch corresponding services
                    $serviceIds = json_decode($booking->services, true);
                    $serviceIds = Arr::flatten($serviceIds);
                    $services = Service::whereIn('id', $serviceIds)->get();

                    // Calculate total price of services
                    $totalPrice = $services->sum('price');
                } else {
                    // Return appropriate message if no services selected
                    return response()->json(['message' => 'No services selected for booking with ID: ' . $booking->id], 404);
                }

                // Retrieve location data
                $locationData = DB::table('bookings')
                    ->select(DB::raw('X(location) as latitude, Y(location) as longitude'))
                    ->where('id', $booking->id)
                    ->first();

                $location = [
                    'latitude' => $locationData->latitude,
                    'longitude' => $locationData->longitude
                ];

                // Prepare formatted booking data
                $formattedBooking = [
                    'id' => $booking->id,
                    'start_date' => $booking->start_date,
                    'user_name' => $userName,
                    'user_profile_image' => User::find($booking->user_id)->profile_image,
                    'center' => User::find($booking->user_id)->center,
                    'caregiver_name' => $caregiverName,
                    'services' => $services,
                    'total_price' => $totalPrice,
                    'location' => $location,
                    'status' => $booking->approval_status, // Use the stored approval status directly
                    'phone_number' => $booking->phone_number,
                    "finished" => $booking->finished
                ];

                $formattedBookings[] = $formattedBooking;
            }

            // Return formatted bookings as JSON response
            return response()->json(['bookings' => $formattedBookings], 200);
        } catch (\Exception $e) {
            // Handle exceptions
            return response()->json(['error' => 'An error occurred while processing the request.'], 500);
        }
    }


    /**
     * Approve or reject a booking by the caregiver.
     * Only the caregiver can approve or reject bookings associated with them.
     */
    public function approveOrReject(Request $request, $id): JsonResponse
    {
        try {

            // Validate request data
            $validatedData = $request->validate([
                'approval_status' => 'required',
            ]);
            // Find the booking to be updated
            $booking = Booking::findOrFail($id);

            //? wallet transactions
            if ($validatedData['approval_status'] == 0) {
                try {
                    $user = User::find($booking->user_id);
                    $user->wallet->increaseBalance($booking->total_price);
                    DB::table('notifications')->insert([
                        'Owner' => 'p',
                        'Owner_id' => $user->id,
                        "data" => json_encode([
                            'msg_en' => 'Your booking request was canceled with ' . Caregiver::find($booking->caregiver_id)->name . "and we send the money back to your wallet",
                            'msg_ar' => " تم الغاء حجزك مع الدكتور" . Caregiver::find($booking->caregiver_id)->name . "وتم استرجعاع ثمن الحجز اللي محفظتك بنجاح"
                        ])
                    ]);
                } catch (\Exception $e) {
                    return response()->json(['error' => $e->getMessage()], 400);
                }
            }
            //?
            // Update the approval status
            $booking->approval_status = $validatedData['approval_status'];
            $booking->save();

            // Encode the response data to UTF-8
            $encodedBooking = $this->utf8EncodeBooking($booking);

            // Return success message and updated booking data
            return response()->json(['message' => 'Booking approval status updated successfully', 'booking' => $encodedBooking], 200);
        } catch (ModelNotFoundException $e) {
            // Return an error message if the booking is not found
            return response()->json(['error' => 'Booking not found'], 404);
        } catch (ValidationException $e) {
            // Return an error message if validation fails
            return response()->json(['error' => $e->errors()], 400);
        } catch (\Exception $e) {
            // Return an error message if an exception occurs
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Function to encode booking data to UTF-8.
     */
    private function utf8EncodeBooking($booking): array
    {
        $encodedBooking = [];
        foreach ($booking->toArray() as $key => $value) {
            $encodedBooking[$key] = is_string($value) ? utf8_encode($value) : $value;
        }
        return $encodedBooking;
    }
}
