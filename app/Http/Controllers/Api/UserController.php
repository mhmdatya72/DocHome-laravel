<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Models\Image;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Notification;
use App\Notifications\NewUseNotification;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    /**
     * Create a new AuthController instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['login', 'register']]);
    }

    /**
     * Get a JWT via given credentials.
     *
     * @throws ValidationException
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        if (!$token = auth()->guard('api')->attempt($validator->validated())) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        return $this->createNewToken($token);
    }

    /**
     * Register a User.
     *
     * @throws ValidationException
     */
    public function register(Request $request)
    {
        $centerModel = get_class(new Center());
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:users',
            'password' => 'required|string|confirmed|min:6',
            'profile_image' => 'nullable|mimes:jpeg,gif,png|max:2048',
            'phone' => 'required|digits:11',
            'center_id' => "required|exists:{$centerModel},id",
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 422);
        }

        $profile_image_path = null;

        // Upload image in public disk if exists
        if ($file = $request->file('profile_image')) {
            $name = time() . '_' . $file->getClientOriginalName();
            $profile_image_path = $file->storeAs('images/users/' . $request->name . '/profile_image', $name, 'public');

            // Insert image in image table
            $data = new Image();
            $data->name = $name;
            $data->path = $profile_image_path;
            $data->save();
        }

        // Create the user
        $user = User::create(array_merge(
            $validator->validated(),
            ['password' => Hash::make($request->password)],
            ['profile_image' => $profile_image_path]
        ));

        //user notifications for registering

        $user = User::where('id', $user_id)->first();
        $user_id = auth()->user()->id;
        $messageEn = 'welcome to our homecare services';
        $messageAr = 'مرحبا بك في هوم كير';

        DB::table('notifications')->insert([
            'Owner' => 'p',
            'Owner_id' => $user_id,
            'data' => json_encode([
                'msg_ar' => $messageAr,
                'msg_en' => $messageEn,
            ])
        ]);
        // Notification::send($user, NewUseNotification($user_id, $message));



        return response()->json([
            'message' => 'User successfully registered',
            'user' => $user
        ], 201);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     */
    public function logout(): JsonResponse
    {
        auth()->guard('api')->logout();
        return response()->json(['message' => 'User successfully signed out']);
    }
    /**
     * Refresh a token.
     *
     */
    public function refresh(): JsonResponse
    {
        return $this->createNewToken(auth()->guard('api')->refresh());
    }
    /**
     * Get the authenticated User.
     *
     */
    public function userProfile(): JsonResponse
    {
        return response()->json(auth()->guard('api')->user());
    }
    /**
     * Get the token array structure.
     *
     * @param string $token
     *
     * @return \Illuminate\Http\JsonResponse
     */
    protected function createNewToken($token)
    {
        $user = User::where('email', auth()->guard('api')->user()->email);
        $user->update([
            'access_token' => $token,
        ]);
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL(),
            'user' => auth()->guard('api')->user()
        ]);
    }

    /**
     * Gets users except yourself
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        $users = User::where('id', '!=', auth()->guard('api')->user()->id)->get();
        return response()->json([
            'data' => $users,
            'status' => 200,
            'message' => "all users except me"
        ]);
    }
    /**
     * Update the authenticated user's profile information.
     */
    public function update(Request $request)
    {
        $centerModel = get_class(new Center());

        // Get the authenticated user
        $user = Auth::user();

        // Define validation rules
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|between:2,100',
            'email' => "sometimes|required|string|email|max:100|unique:users,email,{$user->id}",
            'password' => 'sometimes|nullable|string|confirmed|min:6',
            'profile_image' => 'nullable|mimes:jpeg,gif,png|max:2048',
            'phone' => 'sometimes|required|digits:11',
            'center_id' => "sometimes|required|exists:{$centerModel},id",
        ]);

        // Return validation errors if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $profile_image_path = $user->profile_image;

        // Check if a profile image is uploaded and handle the upload
        if ($file = $request->file('profile_image')) {
            // Delete the old profile image if it exists
            if ($profile_image_path) {
                Storage::disk('public')->delete($profile_image_path);
            }

            // Use  dynamic unique id as the image file name
            $name = Str::uuid() . '.' . $file->getClientOriginalExtension(); // store image with
            $profile_image_path = $file->storeAs('images/users/profile_images', $name, 'public');

            // Insert new image in image table
            $image = new Image();
            $image->name = $name;
            $image->path = $profile_image_path;
            $image->save();
        }

        // Update user data
        $user->update(array_merge(
            $validator->validated(),
            $request->password ? ['password' => Hash::make($request->password)] : [],
            ['profile_image' => $profile_image_path]
        ));

        return response()->json([
            'message' => 'User successfully updated',
            'user' => $user
        ], 200);
    }
    public function myWallet(Request $request)
    {
        $wallet = Wallet::firstWhere("user_id", auth()->user()->id);
        return response()->json([
            'balance' => $wallet->balance ?? 0
        ], 200);
    }
}
