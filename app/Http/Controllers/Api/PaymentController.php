<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\CustomMail;
use App\Mail\OtpMail;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Stripe\PaymentIntent;
use Stripe\Stripe;

class PaymentController extends Controller
{
    public function createPaymentIntent(Request $request)
    {
        try {
            Stripe::setApiKey(config('services.stripe.secret'));

            $paymentIntent = PaymentIntent::create([
                'amount' => $request->amount * 100,
                'currency' => 'egp',
                'payment_method_types' => ['card'],
            ]);

            return response()->json([
                'client_secret' => $paymentIntent->client_secret,
            ]);
        } catch (Exception $e) {
            return response()->json([
                "message" => $e->getMessage(),
            ], 400);
        }
    }
    public function walletChargingOrder()
    {
        if (auth()->check()) {
            $user = auth()->user();
            Mail::to("dochome740@gmail.com")->send(new CustomMail(view: "emails.wallet_charging_request", data: [
                "username" => $user->name,
                "email" => $user->email,
                "phone" => $user->phone,
            ]));
            return response()->json([
                "message" => "wallet charging request send successfully",
            ]);
        } else{
            return response()->json([
                "error" => "un authorized",
            ], 401);
        }
    }
}
