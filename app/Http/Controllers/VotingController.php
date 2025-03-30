<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;
use App\Models\User;
use App\Models\Election;
use App\Models\Candidate;
use App\Models\Vote;

class VotingController extends Controller
{
    /**
     * 1️⃣ Submit Vote API (POST /api/vote)
     */
    public function vote(Request $request)
    {
        // Validate input
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'election_id' => 'required|exists:elections,id',
            'candidate_id' => 'required|exists:candidates,id',
        ]);

        // Get user details
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'message' => 'User not found.'
            ], Response::HTTP_NOT_FOUND);
        }

        // Ensure the user is verified
        if (!$user->is_verified) {
            return response()->json([
                'message' => 'You must verify your email before voting.'
            ], Response::HTTP_UNAUTHORIZED);
        }

        // Check if the user has already voted in this election
        if (Vote::where(['voter_id' => $user->id, 'election_id' => $request->election_id])->exists()) {
            return response()->json([
                'message' => 'You have already voted in this election.'
            ], Response::HTTP_FORBIDDEN);
        }

        // Ensure the candidate belongs to the specified election
        if (!Candidate::where(['id' => $request->candidate_id, 'election_id' => $request->election_id])->exists()) {
            return response()->json([
                'message' => 'Invalid candidate selection for this election.'
            ], Response::HTTP_BAD_REQUEST);
        }

        // Record the vote
        Vote::create([
            'voter_id' => $user->id,
            'candidate_id' => $request->candidate_id,
            'election_id' => $request->election_id,
            'is_test_vote' => false, // Change this for test votes if needed
        ]);

        return response()->json([
            'message' => 'Vote submitted successfully!'
        ], Response::HTTP_OK);
    }

    /**
     * 2️⃣ Fetch Voting Status API (GET /api/vote/status)
     */
    public function getVoteStatus(Request $request)
    {
        // Validate input
        $request->validate([
            'email' => 'required|email|exists:users,email',
        ]);

        // Get user details
        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json([
                'message' => 'User not found.'
            ], Response::HTTP_NOT_FOUND);
        }

        // Get all votes cast by the user
        $votes = Vote::where('voter_id', $user->id)
                     ->with(['candidate', 'election'])
                     ->get();

        if ($votes->isEmpty()) {
            return response()->json([
                'message' => 'You have not voted in any election yet.',
                'votes' => []
            ], Response::HTTP_OK);
        }

        return response()->json([
            'message' => 'Voting status retrieved successfully.',
            'votes' => $votes
        ], Response::HTTP_OK);
    }
}
