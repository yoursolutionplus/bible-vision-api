<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\SessionCreditTransaction;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => strtolower($validated['email']),
            'password' => Hash::make($validated['password']),
        ]);

        $token = $user->createToken('bible-vision-mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Account created successfully.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ], 201);
    }

    public function login(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::where('email', strtolower($validated['email']))->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['The email or password is incorrect.'],
            ]);
        }

        // Keep one active mobile token per login session.
        $user->tokens()->where('name', 'bible-vision-mobile')->delete();

        $token = $user->createToken('bible-vision-mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful.',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
        ]);

        $email = strtolower($validated['email']);
        $user = User::where('email', $email)->first();

        // Always return the same public response so we do not reveal
        // whether an account exists for the submitted email.
        if (! $user) {
            return response()->json([
                'success' => true,
                'message' => 'If an account exists for this email, a verification code has been sent.',
            ]);
        }

        // Prevent excessive requests: one code per 60 seconds.
        $existing = DB::table('password_reset_codes')
            ->where('email', $email)
            ->first();

        if (
            $existing
            && isset($existing->updated_at)
            && \Carbon\Carbon::parse($existing->updated_at)->addSeconds(60)->isFuture()
        ) {
            return response()->json([
                'success' => true,
                'message' => 'If an account exists for this email, a verification code has been sent.',
            ]);
        }

        $code = (string) random_int(100000, 999999);

        DB::table('password_reset_codes')->updateOrInsert(
            ['email' => $email],
            [
                'code_hash' => Hash::make($code),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(15),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        Mail::raw(
            "Bible Vision password reset code: {$code}\n\n"
            . "This code expires in 15 minutes.\n\n"
            . "If you did not request a password reset, you can ignore this message.",
            function ($message) use ($email) {
                $message
                    ->to($email)
                    ->subject('Bible Vision - Password Reset Code');
            }
        );

        return response()->json([
            'success' => true,
            'message' => 'If an account exists for this email, a verification code has been sent.',
        ]);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'code' => ['required', 'digits:6'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $email = strtolower($validated['email']);

        $reset = DB::table('password_reset_codes')
            ->where('email', $email)
            ->first();

        if (! $reset) {
            throw ValidationException::withMessages([
                'code' => ['The verification code is invalid or has expired.'],
            ]);
        }

        if (now()->greaterThan($reset->expires_at)) {
            DB::table('password_reset_codes')
                ->where('email', $email)
                ->delete();

            throw ValidationException::withMessages([
                'code' => ['The verification code is invalid or has expired.'],
            ]);
        }

        if ((int) $reset->attempts >= 5) {
            DB::table('password_reset_codes')
                ->where('email', $email)
                ->delete();

            throw ValidationException::withMessages([
                'code' => ['Too many incorrect attempts. Please request a new code.'],
            ]);
        }

        if (! Hash::check($validated['code'], $reset->code_hash)) {
            DB::table('password_reset_codes')
                ->where('email', $email)
                ->increment('attempts');

            throw ValidationException::withMessages([
                'code' => ['The verification code is incorrect.'],
            ]);
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            DB::table('password_reset_codes')
                ->where('email', $email)
                ->delete();

            throw ValidationException::withMessages([
                'email' => ['Unable to reset the password for this account.'],
            ]);
        }

        DB::transaction(function () use ($user, $email, $validated) {
            $user->forceFill([
                'password' => Hash::make($validated['password']),
            ])->save();

            // Revoke all old sessions after a successful password reset.
            $user->tokens()->delete();

            DB::table('password_reset_codes')
                ->where('email', $email)
                ->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Password reset successfully. You can now sign in with your new password.',
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    public function deleteAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string'],
        ]);

        /** @var User $user */
        $user = $request->user();

        if (! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The password is incorrect.'],
            ]);
        }

        DB::transaction(function () use ($user) {
            // Delete dependent Bible Vision data first so foreign keys
            // do not block the account deletion.
            SessionCreditTransaction::where('user_id', $user->id)->delete();
            Booking::where('user_id', $user->id)->delete();
            Subscription::where('user_id', $user->id)->delete();

            DB::table('password_reset_codes')
                ->where('email', strtolower($user->email))
                ->delete();

            // Delete all Sanctum tokens for the account.
            $user->tokens()->delete();

            // Delete the user account itself.
            $user->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully.',
        ]);
    }
}
