<?php

namespace App\Http\Controllers\Api;
use App\Models\Caregiver;
use App\Models\Category;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
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
            if (Auth::guard('admin')->check()) {
                // Retrieve all categories with their services
                $categories = Category::with('services')->get();
            } elseif (Auth::guard('caregiver')->check() || Auth::guard('api')->check()) {
                // Retrieve all categories with selected properties of their services
                $categories = Category::with('services')->get();
            } else {
//                 Retrieve all categories without their services
                $categories = Category::all();
            }
            return response()->json($categories);
        } catch (\Exception $e) {
            // Return an error response with detailed error message
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
                'name' => 'required|max:255',
                'description' => 'required',
                'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // Store the uploaded image
            $image = $request->file('image');
            if (!$image->isValid()) {
                throw new \Exception('Invalid image file.');
            }

            // Use the category name as the image name
            $imageName = $validatedData['name'] . '.' . $image->getClientOriginalExtension();

            // Store the image with the category name
            $imagePath = $image->storeAs('', $imageName, 'categories');

            // Create a new category
            $category = Category::create([
                'name' => $validatedData['name'],
                'description' => $validatedData['description'],
                'image' => $imagePath,
            ]);

            return response()->json($category, 201);
        } catch (ValidationException $e) {
            // Handle validation errors
            return response()->json(['message' => 'Validation failed: ' . $e->getMessage()], 422);
        } catch (\Exception $e) {
            // Handle other exceptions
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
                'name' => 'required|max:255',
                'description' => 'required',
                'image' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);

            // Find the category by ID
            $category = Category::findOrFail($id);

            // Update category fields
            $category->name = $validatedData['name'];
            $category->description = $validatedData['description'];

            // Check if there's an image uploaded
            if ($request->hasFile('image')) {
                $image = $request->file('image');

                // Validate the uploaded image
                if (!$image->isValid()) {
                    throw new \Exception('Invalid image file.');
                }

                // Use the category name as the image name
                $imageName = $validatedData['name'] . '.' . $image->getClientOriginalExtension();

                // Delete the previous image if exists
                Storage::delete($category->image);

                // Store the new image with the updated category name
                $imagePath = $image->storeAs('', $imageName, 'categories');
                $category->image = $imagePath;
            }

            // Save the updated category
            $category->save();

            return response()->json($category, 200);
        } catch (ValidationException $e) {
            // Handle validation errors
            return response()->json(['message' => 'Validation failed: ' . $e->getMessage()], 422);
        } catch (\Exception $e) {
            // Handle other exceptions
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
            if ($category->image) {
                Storage::delete($category->image);
            }

            // Delete the category
            $category->delete();

            return response()->json(['message' => 'Category deleted successfully.'], 200);
        } catch (\Exception $e) {
            // Return an error response if category not found
            return response()->json(['error' => 'Category not found.'], 404);
        }
    }

    /**
     * Retrieve caregivers based on category ID.
     *
     * @param int $category_id
     * @return \Illuminate\Http\JsonResponse
     */
//    public function category_Caregivers(int $category_id): JsonResponse
//    {
//        try {
//            // Find Caregivers by category_id
//            $Caregivers = Caregiver::where('category_id', $category_id)->get();
//
//            // Check if Caregivers are found
//            if ($Caregivers->isEmpty()) {
//                // Return a 404 response if no Caregivers are found for this category
//                return response()->json(['error' => 'Caregivers not found for this category.'], 404);
//            }
//
//            // Return Caregivers if found
//            return response()->json($Caregivers);
//        } catch (\Exception $e) {
//            // Return a 500 response if an unexpected error occurs
//            return response()->json(['error' => 'An error occurred.'], 500);
//        }
//    }
    public function category_Caregivers(int $category_id): JsonResponse
    {
        try {
            // Get the logged-in user's center_id
            $user = Auth::user(); // Assuming you're using Laravel's authentication
            $center_id = $user->center_id;

            // Find Caregivers by category_id and center_id
            $Caregivers = Caregiver::where('category_id', $category_id)
                ->where('center_id', $center_id)
                ->get();

            // Check if Caregivers are found
            if ($Caregivers->isEmpty()) {
                // Return a 404 response if no Caregivers are found for this category and center
                return response()->json(['error' => 'Caregivers not found for this category and center.'], 404);
            }

            // Return Caregivers if found
            return response()->json($Caregivers);
        } catch (\Exception $e) {
            // Return a 500 response if an unexpected error occurs
            return response()->json(['error' => 'An error occurred.'], 500);
        }
    }

}
