<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Election;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CandidateController extends Controller
{
    /**
     * Add a candidate to an election position.
     */
    public function addCandidate(Request $request)
    {
        try {
            // Validate input
            $request->validate([
                'name' => 'required|string|max:255',
                'position' => 'required|string|max:255',
                'image' => 'nullable|image|max:2048',
                'election_id' => 'required|exists:elections,id',
            ]);

            // Handle image upload if provided
            $imagePath = $request->hasFile('image')
                ? $request->file('image')->store('candidates', 'public')
                : null;

            // Create candidate
            $candidate = Candidate::create([
                'name' => $request->name,
                'position' => $request->position,
                'image' => $imagePath,
                'election_id' => $request->election_id,
            ]);

            return response()->json([
                'message' => 'Candidate added successfully.',
                'candidate' => $candidate,
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Candidate creation failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'An error occurred while adding the candidate.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Fetch all candidates for a given election.
     */
    public function getCandidates($election_id)
    {
        try {
            // Check if election exists
            $election = Election::find($election_id);
            if (!$election) {
                return response()->json([
                    'message' => 'Election not found.',
                ], Response::HTTP_NOT_FOUND);
            }

            // Fetch candidates grouped by position
            $candidates = Candidate::where('election_id', $election_id)
                ->select('id', 'name', 'position', 'image')
                ->orderBy('position')
                ->get()
                ->groupBy('position');

            return response()->json([
                'message' => 'Candidates retrieved successfully.',
                'election' => $election->name,
                'candidates' => $candidates,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Fetching candidates failed', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'An error occurred while fetching candidates.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
