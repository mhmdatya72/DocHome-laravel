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
        if (!auth()->check()) {
            return response()->json([
                'message' => "your ar not authorized"
            ], 401);
        }
        $request->validate([
            'caregiver_id' => 'required|exists:caregivers,id',
            'rating' => 'required',
            'comments' => 'nullable|string',
        ]);
        $rating = Rating::where('user_id', auth()->user()->id)->where('caregiver_id', $request->caregiver_id)->first();
        if ($rating) {
            $rating->rating = $request->rating;
            $rating->comments = $request->comments;
            $rating->save();
            $averageRating = Rating::where('caregiver_id', $request->caregiver_id)->avg('rating');
            Caregiver::find($request->caregiver_id)->update(['stars' => $averageRating]);
        } else {
            Rating::create([
                'caregiver_id' => $request->caregiver_id,
                'user_id' => auth()->user()->id,
                'rating' => $request->rating,
                'comments' => $request->comments,
            ]);
            $averageRating = Rating::where('caregiver_id', $request->caregiver_id)->avg('rating');
            $numberOfRatings = Rating::where('caregiver_id', $request->caregiver_id)->count();
            Caregiver::find($request->caregiver_id)->update(['stars' => $averageRating, "number_of_reviews" => $numberOfRatings]);
        }



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
