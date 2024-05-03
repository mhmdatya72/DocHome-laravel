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
     * Show the form for creating a new resource.
     */
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        try {
            // Validate request data
            $validatedData = $request->validate([
                'name' => 'required|max:255|unique:centers',
            ]);



            // Create a new center
            $center = Center::create([
                'name' => $validatedData['name'],
            ]);

            return response()->json($center, 201);

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
     * Show the form for editing the specified resource.
     */
    public function edit(Center $center)
    {
        //
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
                'name' => 'required|max:255|unique:centers',
            ]);

            // Update center details
            $center->name = $validatedData['name'];
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

            return response()->json(['message' => 'center deleted.'], 201);


        } catch (\Exception $e) {
            // Return an error response if center not found
            return response()->json(['message' => 'center not found.'], 404);
        }
    }
}
