<?php

namespace App\Http\Controllers;

use App\Models\Election;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ElectionController
{
    /**
     * Create a new election with positions.
     */
    public function createElection(Request $request)
    {
        Log::info('Creating Election', ['request' => $request->all()]);

        $validated = $request->validate([
            'name' => 'required|string|unique:elections,name',
            'start_time' => 'required|date',
            'end_time' => 'required|date|after_or_equal:start_time',
            'positions' => 'required|array|min:1',
            'positions.*' => 'required|string|distinct',
        ]);

        try {
            $election = Election::create([
                'name' => $validated['name'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'status' => 'pending', // ✅ Default when created
                'positions' => $validated['positions'], 
            ]);

            return response()->json([
                'message' => 'Election created successfully.',
                'election' => $election,
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Error creating election', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to create election.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Fetch a specific election.
     */
    public function getElectionDetails($id)
    {
        try {
            $election = Election::with('candidates')->find($id);
            if (!$election) {
                return response()->json(['message' => 'Election not found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json($election, Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Error fetching election details', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to retrieve election details.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Fetch all elections.
     */
    public function getElections()
    {
        try {
            $elections = Election::all();

            return response()->json([
                'message' => 'Elections retrieved successfully.',
                'elections' => $elections
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Error fetching elections', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to retrieve elections.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

        /**
     * Update an election (PATCH).
     */
    public function updateElection(Request $request)
    {
        try {
            // Validate the request
            $validated = $request->validate([
                'id' => 'required|exists:elections,id',
                'name' => 'sometimes|string|unique:elections,name,' . $request->id,
                'start_time' => 'sometimes|date',
                'end_time' => 'sometimes|date|after_or_equal:start_time',
                'positions' => 'sometimes|array|min:1',
                'positions.*' => 'sometimes|string|distinct',
                'status' => 'sometimes|string|in:pending,open,closed,reset,reopen',
                'description' => 'sometimes|string|max:500',
            ]);

            // Find the election
            $election = Election::find($validated['id']);
            if (!$election) {
                return response()->json([
                    'message' => 'Election not found.',
                    'error' => 'No election exists with the given ID.',
                ], Response::HTTP_NOT_FOUND);
            }

            // Update the election
            $election->update($validated);

            return response()->json([
                'message' => 'Election updated successfully.',
                'election' => $election,
            ], Response::HTTP_OK);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        } catch (\Exception $e) {
            Log::error('Error updating election', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to update election.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * Delete an election.
     */
    public function deleteElection($id)
    {
        try {
            $election = Election::find($id);
            if (!$election) {
                return response()->json(['message' => 'Election not found.'], Response::HTTP_NOT_FOUND);
            }

            $election->delete();
            return response()->json(['message' => 'Election deleted successfully.'], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Error deleting election', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to delete election.',
                'error' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
