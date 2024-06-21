<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Events\NotificationEvent;
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
    
    public function userNotifi(Request $request){
        DB::table('notifications')->select('data')->where('Owner','p')->where('Owner_id',$request->id);
        return response()->json(['message' => 'Notifications get successfully']);
    }

    public function caregiverNotifi(Request $request){
        DB::table('notifications')->select('data')->where('Owner','c')->where('Owner_id',$request->id);
        return response()->json(['message' => 'Notifications get successfully']);
    }


}
