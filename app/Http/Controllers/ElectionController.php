<?php

namespace App\Http\Controllers;

use App\Models\Election;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class ElectionController
{
    /**
     * Create a new election with positions.
     */
    public function createElection(Request $request)
    {
        // Log request for debugging
        Log::info('Creating Election', ['request' => $request->all()]);

        // Validate input
        $request->validate([
            'name' => 'required|string|unique:elections,name',
            'start_time' => 'nullable|date',
            'end_time' => 'nullable|date|after_or_equal:start_time',
            'positions' => 'required|array|min:1',
            'positions.*' => 'required|string|distinct',
        ]);

        // Create election with embedded positions
        $election = Election::create([
            'name' => $request->name,
            'start_time' => $request->start_time,
            'end_time' => $request->end_time,
            'status' => 'pending',
            'positions' => $request->positions, // Store positions as an array
        ]);

        // Return response
        return response()->json([
            'message' => 'Election created successfully with positions',
            'election' => $election,
        ], Response::HTTP_CREATED);
    }

    /**
     * Fetch a specific election with its candidates.
     */
    public function getElectionDetails($id)
    {
        $election = Election::with('candidates')->findOrFail($id);

        return response()->json($election, Response::HTTP_OK);
    }

    /**
     * Fetch all elections.
     */
    public function getElections()
    {
        $elections = Election::all();
        return response()->json($elections, Response::HTTP_OK);
    }

    /**
     * Update the status of an election.
     */
    public function updateElectionStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:pending,ongoing,completed',
        ]);

        $election = Election::findOrFail($id);
        $election->update(['status' => $request->status]);

        return response()->json([
            'message' => 'Election status updated successfully',
            'election' => $election,
        ], Response::HTTP_OK);
    }

    public function getResults($id)
    {
        // Validate election existence
        $election = Election::find($id);
        if (!$election) {
            return response()->json([
                'message' => 'Election not found.'
            ], Response::HTTP_NOT_FOUND);
        }

        // Fetch candidates and their vote counts
        $results = Candidate::where('election_id', $id)
            ->withCount('votes')
            ->orderByDesc('votes_count')
            ->get(['id', 'name', 'position', 'votes_count']);

        return response()->json([
            'message' => 'Election results retrieved successfully.',
            'election' => $election->name,
            'results' => $results
        ], Response::HTTP_OK);
    }

}
