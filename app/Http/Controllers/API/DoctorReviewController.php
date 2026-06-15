<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\DoctorReviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DoctorReviewController extends Controller
{
    public function __construct(protected DoctorReviewService $doctorReviewService)
    {
    }

    public function index(Request $request, int $id): JsonResponse
    {
        $doctor = User::role('doctor')->findOrFail($id);

        $reviews = $this->doctorReviewService->listForDoctor(
            $doctor->id,
            $request->integer('per_page', 20)
        );

        $stats = $this->doctorReviewService->ratingStatsForDoctor($doctor->id);

        return response()->json([
            'success' => true,
            'data' => $reviews->items(),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'per_page' => $reviews->perPage(),
                'total' => $reviews->total(),
                'rating' => $stats['rating'],
                'review_count' => $stats['review_count'],
            ],
            'message' => 'Doctor reviews retrieved successfully',
        ]);
    }

    public function store(Request $request, int $id): JsonResponse
    {
        $user = $request->user();

        if (!$user || !$user->hasRole('patient')) {
            return response()->json([
                'success' => false,
                'message' => 'Only patients can submit doctor reviews.',
            ], 403);
        }

        $patient = $user->patient;
        if (!$patient) {
            return response()->json([
                'success' => false,
                'message' => 'Patient profile not found for this user.',
            ], 404);
        }

        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:2000',
            'appointment_id' => 'nullable|integer|exists:appointments,id',
        ]);

        $doctor = User::role('doctor')->findOrFail($id);

        $review = $this->doctorReviewService->createReview(
            $doctor,
            $patient,
            (int) $validated['rating'],
            $validated['comment'] ?? null,
            isset($validated['appointment_id']) ? (int) $validated['appointment_id'] : null
        );

        $stats = $this->doctorReviewService->ratingStatsForDoctor($doctor->id);

        return response()->json([
            'success' => true,
            'data' => $review->load(['patient:id,first_name,last_name', 'appointment:id,appointment_number']),
            'meta' => $stats,
            'message' => 'Review submitted successfully',
        ], 201);
    }
}
