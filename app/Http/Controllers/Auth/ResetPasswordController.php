<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\WelcomeMail;
use App\Models\PasswordResets;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class ResetPasswordController extends Controller
{
    public function sendOtp()
    {
        try {
            $data = request()->validate([
                'email' => "required|email",
            ]);
            $email = $data["email"];
            // generate random number with four digits
            $otp = rand(1000, 9999);
            // check if email exists in users table or not
            $mailExist = User::firstWhere('email', $email);
            if ($mailExist) {
                // send otp to this email
                Mail::to($email)->send(new WelcomeMail("Please use this otp for continue reset your password", "OTP: $otp"));
                // remove old record
                PasswordResets::where('email', $email)->delete();
                // add new record
                PasswordResets::create(['email' => $email, 'otp' => $otp]);
                return response()->json([
                    'success' => "we send the otp to your email $email",
                ]);
            } else {
                return response()->json([
                    'message' => "mail $email not exist in or system",
                ], status: 404);
            }
        } catch (Exception $e) {
            return response()->json(
                [
                    "message" => $e->getMessage()
                ],
                status: 500
            );
        }
    }
    public function checkOtp()
    {
        try {
            $data = request()->validate([
                'email' => "required|email",
                'otp' => "required",
            ]);
            $email = $data["email"];
            $otp = $data["otp"];
            $passwordReset = PasswordResets::firstWhere('email', $email);
            // otp code will expire after 1 hour from date of creation
            // check if it's expired or not
            if ($passwordReset && Carbon::parse($passwordReset->created_at)->addHour() < now()) {
                return response()->json([
                    'message' => "otp expired",
                ],404);
            } else if ($passwordReset && $passwordReset->otp != $otp) {
                return response()->json([
                    'message' => "otp not correct",
                ], 404);
            }
            return response()->json([
                'success' => "otp is correct"
            ]);
        } catch (Exception $e) {
            return response()->json(
                status: 500
            );
        }
    }

    public function updatePassword()
    {
        try {
            $data = request()->validate([
                'email' => "required|email",
                'new_password' => "required",
            ]);
            $email = $data["email"];
            $newPassword = bcrypt($data["new_password"]);
            // update the password
            User::firstWhere('email', $email)->update(['password' => $newPassword]);
            return response()->json(
                data: [
                    "message" => "password updated successfully",
                ]
            );
        } catch (Exception $e) {
            return response()->json(
                status: 500
            );
        }
    }
}
