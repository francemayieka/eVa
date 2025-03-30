<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\User;
use App\Mail\VerificationCodeMail;

class VerificationController extends Controller
{
    /**
     * 1️⃣ Request Verification Code (Send Code via Email)
     */
    public function requestVerificationCode(Request $request)
    {
        try {
            // Validate email input
            $request->validate([
                'email' => 'required|email|exists:users,email',
            ]);

            // Get user details
            $user = User::where('email', $request->email)->firstOrFail();

            // Check if the user is already verified
            if ($user->is_verified) {
                return response()->json([
                    'message' => 'You are already verified. You may proceed to vote.',
                ], Response::HTTP_OK);
            }

            // Generate a unique 6-digit verification code
            $verificationCode = random_int(100000, 999999);

            // Set expiration time (10 minutes from now)
            $expirationTime = Carbon::now()->addMinutes(10);

            // Update user record with verification code & expiration time
            $user->update([
                'verification_code' => $verificationCode,
                'code_expires_at' => $expirationTime
            ]);

            // Send email with the verification code
            Mail::to($user->email)->send(new VerificationCodeMail($verificationCode));

            return response()->json([
                'message' => 'Verification code sent to your email.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Error sending verification code', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'An error occurred while sending the verification code.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * 2️⃣ Verify Code API (Check if the code is correct)
     */
    public function verifyCode(Request $request)
    {
        try {
            // Validate input
            $request->validate([
                'email' => 'required|email|exists:users,email',
                'code' => 'required|digits:6',
            ]);

            // Get user details
            $user = User::where('email', $request->email)->firstOrFail();

            // Check if already verified
            if ($user->is_verified) {
                return response()->json([
                    'message' => 'You are already verified. You may proceed to vote.',
                ], Response::HTTP_OK);
            }

            // Check if a verification code exists
            if (!$user->verification_code || !$user->code_expires_at) {
                return response()->json([
                    'message' => 'No verification code found. Please request a new one.',
                ], Response::HTTP_BAD_REQUEST);
            }

            // Check if code is expired
            if (Carbon::now()->greaterThan($user->code_expires_at)) {
                return response()->json([
                    'message' => 'Verification code has expired. Please request a new one.',
                ], Response::HTTP_BAD_REQUEST);
            }

            // Check if the code matches
            if ($request->code != $user->verification_code) {
                return response()->json([
                    'message' => 'Invalid verification code.',
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Mark user as verified and clear verification fields
            $user->update([
                'is_verified' => true,
                'verification_code' => null,
                'code_expires_at' => null
            ]);

            return response()->json([
                'message' => 'Verification successful. You may proceed to vote.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Error verifying code', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'An error occurred while verifying the code.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
