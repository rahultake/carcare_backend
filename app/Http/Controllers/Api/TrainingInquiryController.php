<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TrainingInquiry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TrainingInquiryController extends Controller
{
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'full_name'       => 'required|string|max:255',
            'email'           => 'required|email|max:255',
            'phone_number'    => 'required|string|max:20',
            'city'            => 'required|string|max:100',
            'state'           => 'required|string|max:100',
            'course_interest' => 'required|string|max:255',
            'message'         => 'nullable|string|max:2000',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        $metadata = [
            'ip_address'  => $request->ip(),
            'user_agent'  => $request->userAgent(),
            'referrer'    => $request->header('referer'),
            'submitted_at'=> now()->toDateTimeString(),
        ];

        $inquiry = TrainingInquiry::create([
            'full_name'       => $request->full_name,
            'email'           => $request->email,
            'phone_number'    => $request->phone_number,
            'city'            => $request->city,
            'state'           => $request->state,
            'course_interest' => $request->course_interest,
            'message'         => $request->message,
            'metadata'        => $metadata,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Inquiry submitted successfully.',
            'data' => [
                'inquiry_id' => $inquiry->id,
                'submitted_at' => $inquiry->created_at
            ]
        ], 201);
    }
}