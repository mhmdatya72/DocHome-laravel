<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use App\Models\Category; // Import the Category model
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
            $services = Service::with('category:id,name')->get();
            return response()->json($services);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred while fetching data.'], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'name_ar' => 'required|string',
                'name_en' => 'required|string',
                'category_id' => 'required|exists:categories,id',
                'price' => 'nullable|numeric',
            ], [
                'name_ar.required' => 'Please provide the Arabic name of the service.',
                'name_en.required' => 'Please provide the English name of the service.',
                'category_id.required' => 'Please select a category for the service.',
                'category_id.exists' => 'The selected category does not exist.',
                'price.numeric' => 'The price must be a number.',
            ]);

            $service = Service::create($validatedData);

            return response()->json(['message' => 'Service created successfully', 'data' => $service], 201);

        } catch (ValidationException $e) {
            return response()->json(['error' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to create service: ' . $e->getMessage()], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id): JsonResponse
    {
        try {
            $service = Service::findOrFail($id);
            return response()->json($service);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Service not found.'], 404);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'name_ar' => 'required|string',
                'name_en' => 'required|string',
                'category_id' => 'required|exists:categories,id',
                'price' => 'nullable|numeric',
            ]);

            $service = Service::findOrFail($id);

            $service->update([
                'name_ar' => $validatedData['name_ar'],
                'name_en' => $validatedData['name_en'],
                'category_id' => $validatedData['category_id'],
                'price' => $validatedData['price'],
            ]);

            return response()->json(['message' => 'Service updated successfully', 'data' => $service]);

        } catch (ValidationException $e) {
            return response()->json(['errors' => $e->errors()], 422);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to update service: ' . $e->getMessage()], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        try {
            $service = Service::findOrFail($id);
            $service->delete();

            return response()->json(['message' => 'Service deleted successfully']);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Service not found.'], 404);
        }
    }

    /**
     * Display the services of a specific category.
     */
    public function category_services($category_id): JsonResponse
    {
        try {
            $services = Service::where('category_id', $category_id)->get();

            if ($services->isEmpty()) {
                return response()->json(['error' => 'Services not found for this category.'], 404);
            }

            return response()->json($services);
        } catch (\Exception $e) {
            return response()->json(['error' => 'An error occurred.'], 500);
        }
    }
}

