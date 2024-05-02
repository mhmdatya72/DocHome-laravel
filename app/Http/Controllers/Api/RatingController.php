<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Rating;

class RatingController extends Controller
{
    public function store(Request $request)
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

        return response()->json(['message' => 'Rating submitted successfully'], 201);
    }

    public function index($caregiver_id)
    {
        $ratings = Rating::where('caregiver_id', $caregiver_id)->get();
        return response()->json($ratings);
    }

    public function averageRating($caregiver_id)
    {
        $averageRating = Rating::where('caregiver_id', $caregiver_id)->avg('rating');
        return response()->json(['average_rating' => $averageRating]);
    }
}
