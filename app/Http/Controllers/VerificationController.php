<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Mail\VerificationCodeMail;

class VerificationController extends Controller
{
    /**
     * 1️⃣ Request Verification Code (Send Code via Email)
     */
    public function requestVerificationCode(Request $request)
    {
        // Force Laravel to return JSON in case of errors
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Invalid request'], Response::HTTP_BAD_REQUEST);
        }

        // Validate email input
        $request->validate([
            'email' => 'required|email',
        ]);

        // Check if user exists
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'message' => 'Email not found. Please register first.'
            ], Response::HTTP_NOT_FOUND);
        }

        // Generate a unique 5-digit verification code
        $verificationCode = random_int(10000, 99999);

        // Store the code in cache for 10 minutes
        Cache::put('verification_code_' . $user->email, $verificationCode, now()->addMinutes(10));

        // Send email with the verification code
        Mail::to($user->email)->send(new VerificationCodeMail($verificationCode));

        return response()->json([
            'message' => 'Verification code sent to your email.'
        ], Response::HTTP_OK);
    }

    /**
     * 2️⃣ Verify Code API (Check if the code is correct)
     */
    public function verifyCode(Request $request)
    {
        // Force Laravel to return JSON in case of errors
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Invalid request'], Response::HTTP_BAD_REQUEST);
        }

        // Validate input
        $request->validate([
            'email' => 'required|email',
            'code' => 'required|digits:5',
        ]);

        // Retrieve the stored code from cache
        $storedCode = Cache::get('verification_code_' . $request->email);

        if (!$storedCode) {
            return response()->json([
                'message' => 'Verification code expired or not found.'
            ], Response::HTTP_BAD_REQUEST);
        }

        if ($request->code != $storedCode) {
            return response()->json([
                'message' => 'Invalid verification code.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Remove the code after successful verification
        Cache::forget('verification_code_' . $request->email);

        return response()->json([
            'message' => 'Verification successful. You may proceed to vote.'
        ], Response::HTTP_OK);
    }
}
