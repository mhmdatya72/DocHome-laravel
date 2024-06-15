<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Caregiver;
use Illuminate\Http\Request;
use App\Models\Rating;
use App\Models\User;

class RatingController extends Controller
{
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'caregiver_id' => 'required|exists:caregivers,id',
            'user_id' => 'required|exists:users,id',
            'rating' => 'required|numeric|min:1|max:5',
            'comments' => 'nullable|string',
        ]);

        $rating = Rating::create([
            'caregiver_id' => $request->caregiver_id,
            'user_id' => $request->user_id,
            'rating' => $request->rating,
            'comments' => $request->comments,
        ]);
        $averageRating = Rating::where('caregiver_id', $request->caregiver_id)->avg('rating');
        $numberOfRatings = Rating::where('caregiver_id', $request->caregiver_id);
        Caregiver::where('caregiver_id',$request->caregiver_id)->update(['stars' => $averageRating]);

        return response()->json(['message' => 'Rating submitted successfully'], 201);
    }

    public function index($caregiver_id): \Illuminate\Http\JsonResponse
    {
        $ratings = Rating::where('caregiver_id', $caregiver_id)->with('user')->get();
        return response()->json($ratings);
    }

    public function averageRating($caregiver_id): \Illuminate\Http\JsonResponse
    {
        $averageRating = Rating::where('caregiver_id', $caregiver_id)->avg('rating');
        return response()->json(['average_rating' => $averageRating]);
    }
}
