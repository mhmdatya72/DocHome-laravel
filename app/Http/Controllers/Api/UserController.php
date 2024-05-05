<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Api\Traits\ResponseTrait;
use App\Http\Controllers\Controller;
use App\Models\Center;
use App\Models\Image;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Tymon\JWTAuth\Exceptions\JWTException;

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
            // 'profile_image' => 'mimes:jpeg,gif,png|max:2048',
            'phone' => 'required|min:11|max:11',
            'center_id' => "required|exists:{$centerModel},id",
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 422);
        }

        // upload image in public disk
        // if ($file = $request->file('profile_image')) {
        //     $name = $file->getClientOriginalName();
        //     $profile_image_path = $file->storeAs('images/users/' . "$request->name" . '/profile_image', $name, 'public');

        //     // insert image in image table
        //     $data = new Image();
        //     $data->name = $name;
        //     $data->path = $profile_image_path;
        //     $data->save();
        // }
        $user = User::create(array_merge(
            $validator->validated(),
            ['password' => bcrypt($request->password)],
            // ['profile_image' => $profile_image_path]
        ));
        return response()->json([
            'message' => 'User successfully registered',
            'user' => $user
        ], 201);
    }

    /**
     * Log the user out (Invalidate the token).
     *
     */
    public function logout()
    {
        auth()->guard('api')->logout();
        return response()->json(['message' => 'User successfully signed out']);
    }
    /**
     * Refresh a token.
     *
     */
    public function refresh()
    {
        return $this->createNewToken(auth()->guard('api')->refresh());
    }
    /**
     * Get the authenticated User.
     *
     */
    public function userProfile()
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
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
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
}
