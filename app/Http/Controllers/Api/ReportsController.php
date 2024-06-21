<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Caregiver;
use App\Models\Earning;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ReportsController extends Controller
{
    // get all reports by user_name
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        $userModel = get_class(new User());

        $validator = Validator::make($request->all(), [
            'user_name' => "required|string|exists:{$userModel},name",

        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $report = Report::where('user_name', $request->user_name)->get();
        return response()->json([
            'message' => 'Get all reports data that belongs to this user successfully',
            'report data' => $report,
        ], 201);
    }
    // get one report by id
    public function show($id): \Illuminate\Http\JsonResponse
    {
        $report = Report::where('id', $id)->get();
        return response()->json([
            'message' => 'Get one report successfully',
            'report data' => $report,
        ], 201);
    }
    // store report in database

    /**
     * @throws ValidationException
     */
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {

        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'content' => 'required|string',
            'user_name' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }
        $caregiverId = auth()->guard('caregiver')->user()->id;
        $report = Report::create(array_merge(
            $validator->validated(),
            ['date' => date('Y-m-d')],
            ['caregiver_id' => auth()->guard('caregiver')->user()->id],
        ));

        $booking = Booking::find($request->booking_id);
        // mark booking as done
        $booking->update([
            "finished" => true
        ]);

        Caregiver::find($caregiverId)->increaseSalary($booking->total_price * .75);

        Earning::create([
            "caregiver_id" => $caregiverId,
            "earning" => $booking->total_price * .25
        ]);

        return response()->json([
            'message' => 'Report successfully created',
            'report data' => $report,
        ], 201);
    }
    // update report

    /**
     * @throws ValidationException
     */
    public function update(Request $request, $id): \Illuminate\Http\JsonResponse
    {
        $userModel = get_class(new User());
        $validator = Validator::make($request->all(), [
            'title' => 'required|string',
            'content' => 'required|string',
            'user_name' => "required|string|exists:{$userModel},name",
        ]);
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $report = Report::findOrFail($id);
        $report->update(array_merge(
            $validator->validated(),
            ['date' => date('Y-m-d')],
        ));

        return response()->json([
            'message' => 'Report successfully updated',
            'report data' => $report,
        ], 201);
    }
    // delete report by id
    public function destroy($id): \Illuminate\Http\JsonResponse
    {
         Report::destroy($id);
        return response()->json([
            'message' => 'Report successfully deleted',
        ], 201);
    }
}
