<?php

namespace App\Http\Controllers\Api;

use App\Models\Caregiver;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class CategoriesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            // Retrieve all categories
            $categories = Category::all();
            return response()->json($categories);
        } catch (\Exception $e) {
            // Return an error response if an exception occurs
            return response()->json(['error' => 'An error occurred while fetching categories: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id): JsonResponse
    {
        try {
            // Find category by ID
            $category = Category::findOrFail($id);
            return response()->json($category);
        } catch (\Exception $e) {
            // Return an error response if category not found
            return response()->json(['error' => 'Category not found.'], 404);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): JsonResponse
    {
        try {
            // Validate request data
            $validatedData = $request->validate([
                'name_ar' => 'required|max:255',
                'name_en' => 'required|max:255',
                'description_ar' => 'required',
                'description_en' => 'required',
                'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // Process image upload
            $image = $request->file('image');
            if (!$image->isValid()) {
                throw new \Exception('Invalid image file.');
            }

            // Ensure it's an image file
            $imageExtension = $image->getClientOriginalExtension();
            if (!in_array($imageExtension, ['jpeg', 'png', 'jpg', 'gif'])) {
                throw new \Exception('The uploaded file must be an image (JPEG, PNG, JPG, GIF).');
            }

            // Generate image name and store it
            $imageName = $validatedData['name_en'] . '.' . $imageExtension;
            $directory = 'categories';
            $imagePath = $image->storeAs($directory, $imageName, 'public');


            // Create a new category
            $category = Category::create([
                'name_ar' => $validatedData['name_ar'],
                'name_en' => $validatedData['name_en'],
                'description_ar' => $validatedData['description_ar'],
                'description_en' => $validatedData['description_en'],
                'image' => $imagePath,
            ]);

            return response()->json($category, 201);
        } catch (ValidationException $e) {
            // Get the validation errors
            $errors = $e->validator->errors()->messages();

            // Prepare a response indicating the required data
            $requiredData = [];
            foreach ($errors as $field => $errorMessages) {
                $requiredData[$field] = $errorMessages[0]; // Assuming you want to return only the first error message
            }

            return response()->json(['message' => 'Validation failed', 'required_data' => $requiredData], 422);
        } catch (\Exception $e) {
            // Return error response if failed to create category
            return response()->json(['message' => 'Failed to create category: ' . $e->getMessage()], 422);
        }
    }


    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            // Validate request data
            $validatedData = $request->validate([
                'name_ar' => 'required|max:255',
                'name_en' => 'required|max:255',
                'description_ar' => 'required',
                'description_en' => 'required',
                'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // Find the category by ID
            $category = Category::findOrFail($id);

            // Update category fields
            $category->name_ar = $validatedData['name_ar'];
            $category->name_en = $validatedData['name_en'];
            $category->description_ar = $validatedData['description_ar'];
            $category->description_en = $validatedData['description_en'];

            // Handle image update if provided
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                if (!$image->isValid()) {
                    throw new \Exception('Invalid image file.');
                }
                $imageName = $validatedData['name_en'] . '.' . $image->getClientOriginalExtension();
                Storage::delete($category->image);
                $imagePath = $image->storeAs('', $imageName, 'categories');
                $category->image = $imagePath;
            }

            // Save the updated category
            $category->save();

            return response()->json($category, 200);
        } catch (ValidationException $e) {
            // Return validation error response
            return response()->json(['message' => 'Validation failed: ' . $e->getMessage()], 422);
        } catch (\Exception $e) {
            // Return error response if failed to update category
            return response()->json(['message' => 'Failed to update category: ' . $e->getMessage()], 422);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id): JsonResponse
    {
        try {
            // Find the category by ID
            $category = Category::findOrFail($id);

            // Delete the category image if it exists
            Storage::delete($category->image);

            // Delete the category
            $category->delete();

            return response()->json(['message' => 'Category deleted successfully.'], 200);
        } catch (\Exception $e) {
            // Return error response if category not found
            return response()->json(['error' => 'Category not found.'], 404);
        }
    }

    /**
     * Retrieve caregivers based on category ID.
     */
    public function category_Caregivers(int $category_id): JsonResponse
    {
        try {
            // Get the logged-in user's center_id
            $user = Auth::user();
            $center_id = $user->center_id;
            // Find Caregivers by category_id and center_id
            $caregivers = Caregiver::where('category_id', $category_id)
                ->where('center_id', $center_id)
                ->get();
            // Check if Caregivers are found
            if ($caregivers->isEmpty()) {
                // Return a 404 response if no Caregivers are found for this category and center
                return response()->json(['error' => 'Caregivers not found for this category and center.'], 404);
            }

            // Return Caregivers if found
            return response()->json($caregivers);
        } catch (\Exception $e) {
            // Return a 500 response if an unexpected error occurs
            return response()->json(['error' => 'An error occurred.'], 500);
        }
    }
}

