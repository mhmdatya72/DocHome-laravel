<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Caregiver;
use App\Models\Category;
use App\Models\Center;
use App\Models\Image;
use App\Models\Rating;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

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
        $status = Caregiver::firstWhere("email", $request->email)->status;
        if ($status == 0) {
            return response()->json(['error' => "Please wait until admin accept you"], 403);
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

                //caregiver notifications for registering


                $caregiver_id = auth()->caregiver()->id;
                $message = 'welcome to our homecare services';
                DB::table('notifications')->insert([
                    'Owner'=>'c',
                    'Owner_id'=> $caregiver_id
               ]);
                Notification::send($user,NewUseNotification($caregiver_id, $message));


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
        $caregiver = Caregiver::where('email', auth()->guard('caregiver')->user()->email);
        $caregiver->update([
            'access_token' => $token,
        ]);
        return response()->json([
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL(),
            'caregiver' => auth()->guard('caregiver')->user()
        ]);
    }
    public function category_Caregivers($category_id): JsonResponse
    {
        try {
            // Find Caregivers by category_id
            $Caregivers = Caregiver::where('category_id', $category_id)->where('status', 1)->get();
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
    public function statistics()
    {
        $caregiver_numbers = Caregiver::count();
        $booking_numbers = Booking::count();
        $rating_numbers = Rating::count();
        return response()->json([
            'message' => 'Ok',
            'caregivers_numbers' => $caregiver_numbers,
            'booking_numbers' => $booking_numbers,
            'rating_numbers' => $rating_numbers,
        ], 200);
    }


    public function update(Request $request)
    {
        $centerModel = get_class(new Center());
        $categoryModel = get_class(new Category());

        // Get the authenticated caregiver
        $caregiver = Auth::guard('caregiver')->user(); // Assuming you're using a 'caregiver' guard

        // Define validation rules
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|between:2,100',
            'email' => "sometimes|required|string|email|max:100|unique:caregivers,email,{$caregiver->id}",
            'password' => 'sometimes|nullable|string|confirmed|min:6',
            'phone' => "sometimes|required|min:11|max:11|unique:caregivers,phone,{$caregiver->id}",
            'profile_image' => 'nullable|mimes:jpeg,gif,png|max:2048',
            'center_id' => "sometimes|required|exists:{$centerModel},id",
            'category_id' => "sometimes|required|exists:{$categoryModel},id",
        ]);

        // Return validation errors if validation fails
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $profile_image_path = $caregiver->profile_image;

        // Prepare array for new image paths
        $updateData = $validator->validated();

        // Check if profile_image is uploaded and handle the upload
        if ($file = $request->file('profile_image')) {
            if ($profile_image_path) {
                Storage::disk('public')->delete($profile_image_path);
            }

            $name = Str::uuid()  . $file->getClientOriginalName();
            $profile_image_path = $file->storeAs('images/caregivers/profile_images', $name, 'public');

            $image = new Image();
            $image->name = $name;
            $image->path = $profile_image_path;
            $image->save();

            $updateData['profile_image'] = $profile_image_path;
        }

        // Check if password is provided and hash it
        if ($request->password) {
            $updateData['password'] = bcrypt($request->password);
        }

        // Update caregiver data
        $caregiver->update($updateData);

        return response()->json([
            'message' => 'Caregiver successfully updated',
            'caregiver' => $caregiver
        ], 200);
    }
}
