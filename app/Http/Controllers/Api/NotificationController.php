<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Events\NotificationEvent;


class NotificationController extends Controller
{
    public function sendNotification(Request $request): \Illuminate\Http\JsonResponse
    {
        $message = $request->input('message');

        // Broadcast notification event
        event(new NotificationEvent($message));

        return response()->json(['message' => 'Notification sent successfully']);
    }
}
