<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Caregiver;
use App\Models\Category;
use App\Models\Center;
use App\Models\Image;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class CaregiverController extends Controller
{



    public function __construct()
    {
        $this->middleware('auth:caregiver', ['except' => ['login', 'register']]);
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
        if (!$token = auth()->guard('caregiver')->attempt($validator->validated())) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->createNewToken($token);
    }

    /**
     * Register a Admin.
     *
     * @return JsonResponse
     * @throws ValidationException
     */
    public function register(Request $request)
    {
        $centerModel = get_class(new Center());
        $categoryModel = get_class(new Category());
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|between:2,100',
            'email' => 'required|string|email|max:100|unique:caregivers',
            'password' => 'required|string|confirmed|min:6',
            'phone' => 'required|min:11|max:11',
            'profile_image' => 'required|mimes:jpeg,gif,png|max:2048',
            'professional_card_image' => 'required|mimes:jpeg,gif,png|max:2048',
            'id_card_image' => 'required|mimes:jpeg,gif,png|max:2048',
            'center_id' => "required|exists:{$centerModel},id",
            'category_id' => "required|exists:{$categoryModel},id",
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        // upload image in public disk
        if ($file = $request->file('profile_image')) {
            $name = $file->getClientOriginalName();
            $profile_image_path = $file->storeAs('images/caregivers/' . "$request->name" . '/profile_image', $name, 'public');

            // insert image in image table
            $data = new Image();
            $data->name = $name;
            $data->path = $profile_image_path;
            $data->save();
        }
        if ($file = $request->file('professional_card_image')) {
            $name = $file->getClientOriginalName();
            $professional_card_image_path = $file->storeAs('images/caregivers/' . "$request->name" . '/professional_card_image', $name, 'public');

            // insert image in image table
            $data = new Image();
            $data->name = $name;
            $data->path = $professional_card_image_path;
            $data->save();
        }
        if ($file = $request->file('id_card_image')) {
            $name = $file->getClientOriginalName();
            $id_card_image_path = $file->storeAs('images/caregivers/' . "$request->name" . '/id_card_image', $name, 'public');

            // insert image in image table
            $data = new Image();
            $data->name = $name;
            $data->path = $id_card_image_path;
            $data->save();
        }


        $caregiver = Caregiver::create(array_merge(
            $validator->validated(),
            ['password' => bcrypt($request->password)],
            ['profile_image' => $profile_image_path],
            ['professional_card_image' => $professional_card_image_path],
            ['id_card_image' => $id_card_image_path],
        ));


        return response()->json([
            'message' => 'Caregiver successfully registered',
            'caregiver' => $caregiver,
        ], 201);
    }

    /**
     * Log the admin out (Invalidate the token).
     *
     * @return JsonResponse
     */
    public function logout()
    {
        auth()->guard('caregiver')->logout();
        return response()->json(['message' => 'Caregiver successfully signed out']);
    }
    /**
     * Refresh a token.
     *
     * @return JsonResponse
     */
    public function refresh()
    {
        return $this->createNewToken(auth()->guard('caregiver')->refresh());
    }
    /**
     * Get the authenticated User.
     *
     * @return JsonResponse
     */
    public function userProfile(): JsonResponse
    {
        return response()->json(auth()->guard('caregiver')->user());
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
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60,
            'caregiver' => auth()->guard('caregiver')->user()
        ]);
    }
    public function category_Caregivers($category_id): JsonResponse
    {
        try {
            // Find Caregivers by category_id
            $Caregivers = Caregiver::where('category_id', $category_id)->get();

            // Check if Caregivers are found
            if ($Caregivers->isEmpty()) {
                return response()->json(['error' => 'Caregivers not found for this category.'], 404);
            }

            return response()->json($Caregivers);
        } catch (\Exception $e) {
            // Return an error response if an unexpected error occurs
            return response()->json(['error' => 'An error occurred.'], 500);
        }
    }
}
