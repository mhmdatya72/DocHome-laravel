<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Events\NotificationEvent;
use Exception;
use Illuminate\Support\Facades\DB;


class NotificationController extends Controller
{
    public function sendNotification(Request $request): \Illuminate\Http\JsonResponse
    {
        $message = $request->input('message');

        // Broadcast notification event
        event(new NotificationEvent($message));

        return response()->json(['message' => 'Notification sent successfully']);
    }

    public function userNotifications(Request $request)
    {
        try {
            $id = auth()->guard('api')->user()->id;
            $notifications = DB::table('notifications')->select('data')->where('Owner', 'p')->where('Owner_id', $id)->get();
            return response()->json(['message' => 'Notifications get successfully','notifications' => $notifications]);
        } catch (Exception $e) {
            return response()->json(['message' => "server error"], 500);
        }
    }

    public function caregiverNotifications(Request $request)
    {
        $id = auth()->guard('caregiver')->user()->id;
        $notifications = DB::table('notifications')->select('data')->where('Owner', 'c')->where('Owner_id', $id)->get();
        return response()->json(['message' => 'Notifications get successfully','notifications' => $notifications]);
    }
}
