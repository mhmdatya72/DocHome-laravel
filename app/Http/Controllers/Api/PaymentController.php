<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Exception;
use Illuminate\Http\Request;
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
}
