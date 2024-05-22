<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BookingDetailController extends Controller
{
    /**
     * Get the total count of bookings for the authenticated user.
     *
     * @return JsonResponse
     */
    public function getUserBookingsCount(): JsonResponse
    {
        try {
            // Get the authenticated user's ID
            $userId = auth()->id();

            // Check if the user is authenticated
            if (!$userId) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            // Count bookings for the authenticated user
            $bookingsCount = DB::table('bookings')
                ->where('user_id', $userId)
                ->select('user_id', DB::raw('count(*) as total_bookings'))
                ->groupBy('user_id')
                ->first(); // Use first() to get a single result

            // If no bookings are found, return zero count
            if (!$bookingsCount) {
                $bookingsCount = (object) [
                    'user_id' => $userId,
                    'total_bookings' => 0
                ];
            }

            // Return the result as a JSON response
            return response()->json($bookingsCount);

        } catch (Exception $e) {
            // Handle any errors that may occur
            return response()->json(['error' => 'An error occurred', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get the count of unique caregiver IDs used by the authenticated user in their bookings.
     *
     * @return JsonResponse
     */
    public function caregiverCountByUser(): JsonResponse
    {
        try {
            // Get the authenticated user's ID
            $userId = auth()->id();

            // Check if the user is authenticated
            if (!$userId) {
                return response()->json(['error' => 'User not authenticated'], 401);
            }

            // Count distinct caregiver_id for the authenticated user
            $caregiverCount = Booking::where('user_id', $userId)
                ->distinct('caregiver_id')
                ->count('caregiver_id');

            // Return the result as a JSON response
            return response()->json([
                'user_id' =>$userId,
                'caregiver_count' => $caregiverCount
            ], 200);

        } catch (Exception $e) {
            // Handle any errors that may occur
            return response()->json([
                'error' => 'Something went wrong while calculating caregiver count.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get the total count of bookings for the authenticated caregiver.
     *
     * @return JsonResponse
     */
    public function getCaregiverBookingsCount(): JsonResponse
    {
        try {
            // Get the authenticated caregiver's ID
            $caregiverId = Auth::guard('caregiver')->id();

            // Check if the caregiver is authenticated
            if (!$caregiverId) {
                return response()->json(['error' => 'Caregiver not authenticated'], 401);
            }

            // Count bookings for the authenticated caregiver
            $bookingsCount = DB::table('bookings')
                ->where('caregiver_id', $caregiverId)
                ->select('caregiver_id', DB::raw('count(*) as total_bookings'))
                ->groupBy('caregiver_id')
                ->first(); // Use first() to get a single result

            // If no bookings are found, return zero count
            if (!$bookingsCount) {
                $bookingsCount = (object) [
                    'caregiver_id' => $caregiverId,
                    'total_bookings' => 0
                ];
            }

            // Return the result as a JSON response
            return response()->json($bookingsCount);

        } catch (Exception $e) {
            // Handle any errors that may occur
            return response()->json(['error' => 'An error occurred', 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Get the count of unique users served by the authenticated caregiver.
     *
     * @return JsonResponse
     */
    public function userCountByCaregiver(): JsonResponse
    {
        try {
            // Get the authenticated caregiver's ID
            $caregiverId = Auth::guard('caregiver')->id();

            // Check if the caregiver is authenticated
            if (!$caregiverId) {
                return response()->json(['error' => 'Caregiver not authenticated'], 401);
            }

            // Count distinct user_id for the authenticated caregiver
            $userCount = Booking::where('caregiver_id', $caregiverId)
                ->distinct('user_id')
                ->count('user_id');

            // Return the result as a JSON response
            return response()->json([
                'caregiver_id' => $caregiverId,
                'user_count' => $userCount
            ], 200);

        } catch (Exception $e) {
            // Handle any errors that may occur
            return response()->json([
                'error' => 'Something went wrong while calculating user count.',
                'message' => $e->getMessage()
            ], 500);
        }
    }

}

