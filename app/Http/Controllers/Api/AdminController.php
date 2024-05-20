<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Caregiver;
use App\Models\Image;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Exceptions\JWTException;

class AdminController extends Controller
{


    public function __construct()
    {
        $this->middleware('auth:admin', ['except' => ['login', 'register']]);
    }
    /**
     * Get a JWT via given credentials.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        if (!$token = auth()->guard('admin')->attempt($validator->validated())) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->createNewToken($token);
    }

    /**
     * Register a Admin.
     *
     * @param Request $request
     * @return JsonResponse
     * @throws ValidationException
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:admins',
            'profile_image' => 'required|mimes:jpeg,gif,png|max:2048',
            'password' => 'required|string|confirmed|min:6',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        // upload image in public disk
        if ($file = $request->file('profile_image')) {
            $name = $file->getClientOriginalName();
            $profile_image_path = $file->storeAs('images/admins/' . "$request->name" . '/profile_image', $name, 'public');

            // insert image in image table
            $data = new Image();
            $data->name = $name;
            $data->path = $profile_image_path;
            $data->save();
        }

        $admin = Admin::create(array_merge(
            $validator->validated(),
            ['password' => bcrypt($request->password)],
            ['profile_image' => $profile_image_path]
        ));

        return response()->json([
            'message' => 'Admin successfully registered',
            'admin' => $admin
        ], 201);
    }

    /**
     * Log the admin out (Invalidate the token).
     *
     * @return JsonResponse
     */
    public function logout()
    {
        auth()->guard('admin')->logout();
        return response()->json(['message' => 'Admin successfully signed out']);
    }
    /**
     * Refresh a token.
     *
     * @return JsonResponse
     */
    public function refresh()
    {
        return $this->createNewToken(auth()->guard('admin')->refresh());
    }
    /**
     * Get the authenticated User.
     *
     * @return JsonResponse
     */
    public function userProfile()
    {
        return response()->json(auth()->guard('admin')->user());
    }
    /**
     * Get the token array structure.
     *
     * @param  string $token
     *
     * @return JsonResponse
     */
    protected function createNewToken($token)
    {
        $admin = Admin::where('email', auth()->guard('admin')->user()->email);
        $admin->update([
            'access_token' => $token,
        ]);
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 100000,
            'admin' => auth()->guard('admin')->user()
        ]);
    }
}
