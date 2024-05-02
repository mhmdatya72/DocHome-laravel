<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Image;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ImageController extends Controller
{
    public function upload_image(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:jpeg,gif,png|max:2048',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }
        // upload image in public disk
        if ($file = $request->file('image')) {
            $name = $file->getClientOriginalName();
            $path = $file->storeAs('images', $name, 'public');

            // insert image in image table
            $data = new Image();
            $data->name = $name;
            $data->path = $path;
            $data->save();
        }
        return response()->json([
            'message' => 'Image successfully uploaded',
            'image_data' => $data,

        ], 201);
    }

    public function add_user_image(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|mimes:jpeg,gif,png|max:2048',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }
        // upload image in public disk
        if ($file = $request->file('image')) {
            $name = $file->getClientOriginalName();
            $path = $file->storeAs('images/users', $name, 'public');

             // insert image in user table
             User::where('id',$request->id)->update([
                'image'=> $path,
             ]);

            // insert image in image table
            $data = new Image();
            $data->name = $name;
            $data->path = $path;
            $data->save();

        }
        return response()->json([
            'message' => 'User_mage successfully uploaded',
            'image_data' => $data,

        ], 201);
    }

    public function delete_image(Request $request): \Illuminate\Http\JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'path' => 'required',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors()->toJson(), 400);
        }

        Storage::disk('public')->delete($request->path);
        image::where('id',$request->id)->delete();

        return response()->json([
            'message' => 'Image successfully deleted',
        ], 201);
    }


}
