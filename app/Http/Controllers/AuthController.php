<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    /**
     * Register a new user
     */
    public function register(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|string|email|unique:users,email',
                'reg_no' => 'required|string|unique:users,reg_no',
                'password' => 'required|string|min:6',
                'role' => 'required|in:user,admin'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'reg_no' => $request->reg_no,
                'password' => Hash::make($request->password),
                'role' => $request->role,
                'is_logged_in' => false,
                'has_voted' => false
            ]);

            return response()->json([
                'message' => 'User registered successfully',
                'user' => $user
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('User registration failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'An error occurred during registration'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Login User
     */
    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'message' => 'Invalid email or password'
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Mark user as logged in
            $user->is_logged_in = true;
            $user->save();

            return response()->json([
                'message' => 'Login successful',
                'user' => $user
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Login failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'An error occurred during login'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Logout User (Set `is_logged_in` to false)
     */
    public function logout(Request $request)
    {
        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            $user->is_logged_in = false;
            $user->save();

            return response()->json([
                'message' => 'Logout successful'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Logout failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'An error occurred during logout'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update Profile (Partial Updates Supported)
     */
    public function updateProfile(Request $request)
    {
        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|max:255',
                'email' => 'sometimes|email|unique:users,email,' . $user->id,
                'password' => 'sometimes|string|min:6|confirmed',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            if ($request->has('name')) {
                $user->name = $request->name;
            }
            if ($request->has('email')) {
                $user->email = $request->email;
            }
            if ($request->has('password')) {
                $user->password = Hash::make($request->password);
            }

            $user->save();

            return response()->json([
                'message' => 'Profile updated successfully',
                'user' => $user
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Profile update failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'An error occurred while updating profile'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete Account
     */
    public function deleteAccount(Request $request)
    {
        try {
            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json(['message' => 'User not found'], Response::HTTP_NOT_FOUND);
            }

            $user->delete();

            return response()->json([
                'message' => 'Your account has been deleted successfully.'
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Account deletion failed', ['error' => $e->getMessage()]);
            return response()->json(['message' => 'An error occurred while deleting account'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
