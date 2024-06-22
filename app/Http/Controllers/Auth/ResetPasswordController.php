<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Mail\OtpMail;
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
            $mail = User::firstWhere('email', $email);
            if ($mail) {
                // send otp to this email
                Mail::to($email)->send(new OtpMail(otp: $otp, username: $mail->name));
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
                ], 404);
            } else if ($passwordReset && $passwordReset->otp == $otp) {
                return response()->json([
                    'message' => "otp is correct",
                ], 200);
            }
            return response()->json(
                [
                    'message' => "otp is not correct",
                ],
                400
            );
        } catch (Exception $e) {
            return response()->json(
                data: [
                    'message'  => "there was an error"
                ],
                status: 500
            );
        }
    }

    public function updatePassword()
    {
        try {
            $data = request()->validate([
                'email' => "required|email",
                'new_password' => "required:min:6",
            ]);
            $email = $data["email"];
            // return response()->json([
            //     "message" => $data['new_password']
            // ]);
            // $newPassword = bcrypt($data["new_password"]);
            $newPassword = $data["new_password"];

            if ($user = User::firstWhere("email", $email)) {
                // update the password
                $user->update(['password' => $newPassword]);
                return response()->json(
                    data: [
                        "message" => "password updated successfully",
                    ]
                );
            } else {
                return response()->json(
                    data: [
                        "message" => "email not found",
                    ],
                    status: 404
                );
            }
        } catch (Exception $e) {
            return response()->json(
                status: 500
            );
        }
    }
}
