<?php

namespace App\Http\Controllers\Api;

use App\Models\Center;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class CenterController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): \Illuminate\Http\JsonResponse
    {
        try {
            // get all centers
            $centers = Center::all();
            return response()->json(['data'=> $centers]);
        } catch (\Exception $e) {
            // Return an error response if fetching centers fails
            return response()->json(['message' => 'An error occurred while fetching centers.'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            // Validate request data
            $validatedData = $request->validate([
                'name_ar' => 'required|max:255|unique:centers',
                'name_en' => 'required|max:255|unique:centers',
            ]);

            // Create a new center
            $center = Center::create([
                'name_ar' => $validatedData['name_ar'],
                'name_en' => $validatedData['name_en'],
            ]);

            return response()->json($center, 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Get the validation errors
            $errors = $e->errors();

            // Prepare a response indicating the required data
            $requiredData = [];
            foreach ($errors as $field => $errorMessages) {
                $requiredData[$field] = $errorMessages[0]; // Assuming you want to return only the first error message
            }

            return response()->json(['message' => 'Validation failed', 'required_data' => $requiredData], 422);
        } catch (\Exception $e) {
            // Return an error response if creating centers fails
            return response()->json(['message' => 'Failed to create center: ' . $e->getMessage()], 422);
        }
    }



    /**
     * Display the specified resource.
     */
    public function show($id): \Illuminate\Http\JsonResponse
    {
        try {
            // Find center by ID
            $center = Center::findOrFail($id);
            return response()->json($center);
        } catch (\Exception $e) {
            // Return an error response if center not found
            return response()->json(['error' => 'center not found.'], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        try {
            // Find center by ID
            $center = Center::findOrFail($id);

            // Validate request data
            $validatedData = $request->validate([
                'name_ar' => 'required|max:255|unique:centers,name_ar,' . $id,
                'name_en' => 'required|max:255|unique:centers,name_en,' . $id,
            ]);

            // Update center details
            $center->name_ar = $validatedData['name_ar'];
            $center->name_en = $validatedData['name_en'];
            $center->save();

            return response()->json($center, 200);
        } catch (\Exception $e) {
            // Return an error response if updating center fails
            return response()->json(['message' => 'Failed to update center: ' . $e->getMessage()], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): \Illuminate\Http\JsonResponse
    {
        try {
            // Find center by ID and delete it
            $center = Center::findOrFail($id);
            $center->delete();

            return response()->json(['message' => 'center deleted.'], 200);

        } catch (\Exception $e) {
            // Return an error response if center not found
            return response()->json(['message' => 'center not found.'], 404);
        }
    }
}

