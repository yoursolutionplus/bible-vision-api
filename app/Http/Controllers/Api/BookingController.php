<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Subscription;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'full_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'language' => ['required', 'string', 'in:en,fr,ht'],
            'session_date' => ['required', 'date'],
            'session_time' => ['required', 'string', 'max:20'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Please check the booking information.',
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            $sessionDate = Carbon::parse($request->session_date)->startOfDay();
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session date.',
            ], 422);
        }

        $today = now()->startOfDay();
        $lastAllowedDate = now()->startOfDay()->addDays(90);

        if ($sessionDate->lt($today) || $sessionDate->gt($lastAllowedDate)) {
            return response()->json([
                'success' => false,
                'message' => 'The session date must be between today and the next 90 days.',
            ], 422);
        }

        try {
            $sessionTime = $this->normalizeTime($request->session_time);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid session time.',
            ], 422);
        }

        $subscription = Subscription::query()
            ->with('plan')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->latest('id')
            ->first();

        if (!$subscription || !$subscription->plan) {
            return response()->json([
                'success' => false,
                'message' => 'An active support subscription is required to book a session.',
            ], 403);
        }

        if ($subscription->plan->slug !== 'support') {
            return response()->json([
                'success' => false,
                'message' => 'Your current plan does not include support sessions.',
            ], 403);
        }

        if ((int) $subscription->sessions_remaining <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'You do not have any support sessions remaining.',
            ], 403);
        }

        $booking = new Booking();
        $booking->user_id = $user->id;
        $booking->full_name = trim($request->full_name);
        $booking->email = strtolower(trim($request->email));
        $booking->phone = $request->filled('phone')
            ? trim($request->phone)
            : null;
        $booking->language = $request->language;
        $booking->session_date = $sessionDate->toDateString();
        $booking->session_time = $sessionTime;
        $booking->note = $request->filled('note')
            ? trim($request->note)
            : null;
        $booking->status = 'pending';
        $booking->zoom_link = null;
        $booking->session_credit_used = false;
        $booking->save();

        return response()->json([
            'success' => true,
            'message' => 'Booking request submitted successfully.',
            'data' => [
                'id' => $booking->id,
                'status' => $booking->status,
                'full_name' => $booking->full_name,
                'email' => $booking->email,
                'phone' => $booking->phone,
                'language' => $booking->language,
                'session_date' => $booking->session_date,
                'session_time' => $booking->session_time,
                'note' => $booking->note,
                'zoom_link' => $booking->zoom_link,
                'session_credit_used' => (bool) $booking->session_credit_used,
                'sessions_remaining' => (int) $subscription->sessions_remaining,
            ],
        ], 201);
    }

    public function myBookings(Request $request): JsonResponse
    {
        $user = $request->user();

        $bookings = Booking::query()
            ->where('user_id', $user->id)
            ->latest('session_date')
            ->latest('session_time')
            ->get()
            ->map(function (Booking $booking) {
                return [
                    'id' => $booking->id,
                    'status' => $booking->status,
                    'full_name' => $booking->full_name,
                    'email' => $booking->email,
                    'phone' => $booking->phone,
                    'language' => $booking->language,
                    'session_date' => $booking->session_date,
                    'session_time' => $booking->session_time,
                    'note' => $booking->note,
                    'zoom_link' => $booking->zoom_link,
                    'session_credit_used' => (bool) $booking->session_credit_used,
                    'created_at' => optional($booking->created_at)?->toISOString(),
                    'updated_at' => optional($booking->updated_at)?->toISOString(),
                ];
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $bookings,
        ]);
    }

    private function normalizeTime(string $value): string
    {
        $value = trim($value);

        $formats = [
            'g:i A',
            'g:i a',
            'h:i A',
            'h:i a',
            'H:i',
            'H:i:s',
        ];

        foreach ($formats as $format) {
            try {
                return Carbon::createFromFormat($format, $value)->format('H:i:s');
            } catch (\Throwable $e) {
                //
            }
        }

        throw new \InvalidArgumentException('Invalid time.');
    }
}
