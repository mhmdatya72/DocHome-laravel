<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            // Retrieve all services with their associated categories
            $services = Service::with('category:id,name')->get();
//            $services = Service::with('category')->get();
//            // Retrieve all categories
//            $categories = Category::all();
            // Return JSON response with services and categories
//            return response()->json(['services' => $services, 'categories' => $categories]);
            // Return JSON response with services and categories
            return response()->json($services);
        } catch (\Exception $e) {
            // Return an error response if fetching data fails
            return response()->json(['error' => 'An error occurred while fetching data.'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validating incoming request data with custom error messages
            $validatedData = $request->validate([
                'name' => 'required|string',
                'category_id' => 'required|exists:categories,id',
                'price' => 'nullable|numeric',
            ], [
                'name.required' => 'Please provide the name of the service.',
                'category_id.required' => 'Please select a category for the service.',
                'category_id.exists' => 'The selected category does not exist.',
                'price.numeric' => 'The price must be a number.',
            ]);

            // Creating a new service
            $service = Service::create($validatedData);

            // Returning a success response
            return response()->json(['message' => 'Service created successfully'], 201);

        } catch (ValidationException $e) {
            // Returning a validation error response
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            // Returning an error response if an exception occurred
            return response()->json(['message' => 'Failed to create service: ' . $e->getMessage()], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id): JsonResponse
    {
        try {
            // Find the service by ID
            $service = Service::findOrFail($id);
            return response()->json($service);
        } catch (\Exception $e) {
            // Return an error response if service not found
            return response()->json(['error' => 'Service not found.'], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            // Validate the incoming request data
            $validatedData = $request->validate([
                'name' => 'required|string',
                'category_id' => 'required|exists:categories,id', // Assuming the table name is 'categories'
                'price' => 'nullable|numeric', // Use 'numeric' instead of 'decimal' for validation
            ]);

            // Find the Service by ID or throw an exception if not found
            $service = Service::findOrFail($id);

            // Update the Service with the validated data
            $service->update([
                'name' => $validatedData['name'],
                'category_id' => $validatedData['category_id'],
                'price' => $validatedData['price'],
            ]);

            // Return a JSON response indicating success
            return response()->json(['message' => 'Service updated successfully']);
        } catch (ValidationException $e) {
            // If validation fails, return a JSON response with error messages
            return response()->json(['errors' => $e->errors()], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        try {
            // Find the service by ID and delete it
            $service = Service::findOrFail($id);
            $service->delete();

            // Return success message
            return response()->json(['message' => 'Service deleted successfully']);
        } catch (\Exception $e) {
            // Return an error response if service not found
            return response()->json(['error' => 'Service not found.'], 404);
        }
    }
    /**
     * Display the services of a specific category.
     */
    public function category_services($category_id): JsonResponse
    {
        try {
            // Find services by category_id
            $services = Service::where('category_id', $category_id)->get();

            // Check if services are found
            if ($services->isEmpty()) {
                return response()->json(['error' => 'Services not found for this category.'], 404);
            }

            return response()->json($services);
        } catch (\Exception $e) {
            // Return an error response if an unexpected error occurs
            return response()->json(['error' => 'An error occurred.'], 500);
        }
    }

}
