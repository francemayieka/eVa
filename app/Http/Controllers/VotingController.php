<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Models\User;
use App\Models\Election;
use App\Models\Candidate;
use App\Models\Vote;
use Illuminate\Support\Facades\Log;

class VotingController extends Controller
{
    /**
     * 1️⃣ Submit Vote API (POST /api/vote)
     * Allows a user to vote for multiple candidates in a single election.
     */
    public function vote(Request $request)
    {
        try {
            // Validate input
            $request->validate([
                'email' => 'required|email|exists:users,email',
                'election_id' => 'required|exists:elections,id',
                'candidate_id' => 'required',
            ]);

            // Get user details
            $user = User::where('email', $request->email)->firstOrFail();

            // Ensure the user is verified
            if (!$user->is_verified) {
                return response()->json([
                    'message' => 'You must verify your email before voting.',
                ], Response::HTTP_UNAUTHORIZED);
            }

            // Convert single candidate_id to an array for consistency
            $candidateIds = is_array($request->candidate_id) ? $request->candidate_id : [$request->candidate_id];

            // Check if all candidates belong to the specified election
            $validCandidates = Candidate::whereIn('id', $candidateIds)
                ->where('election_id', $request->election_id)
                ->pluck('id')
                ->toArray();

            if (count($validCandidates) !== count($candidateIds)) {
                return response()->json([
                    'message' => 'Some selected candidates do not belong to this election.',
                ], Response::HTTP_BAD_REQUEST);
            }

            // Prevent duplicate voting in the same election
            if ($user->has_voted) {
                return response()->json([
                    'message' => 'You have already voted in this election.',
                ], Response::HTTP_FORBIDDEN);
            }

            // Record votes for all selected candidates
            foreach ($validCandidates as $candidate_id) {
                Vote::create([
                    'voter_id' => $user->id,
                    'candidate_id' => $candidate_id,
                    'election_id' => $request->election_id,
                    'is_test_vote' => ($user->role === 'admin'),
                ]);
            }

            // Update has_voted status
            $user->update(['has_voted' => true]);

            return response()->json([
                'message' => 'Your vote has been recorded successfully.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Voting error', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'An error occurred while submitting your vote.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * 2️⃣ Fetch Voting Status API (GET /api/vote/status)
     * Returns "You have voted" or "You have not voted" based on the user's status.
     */
    public function getVoteStatus(Request $request)
    {
        try {
            // Validate input
            $request->validate([
                'email' => 'required|email|exists:users,email',
            ]);

            // Get user details
            $user = User::where('email', $request->email)->firstOrFail();

            return response()->json([
                'message' => $user->has_voted ? 'You have voted.' : 'You have not voted.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Fetching voting status error', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'An error occurred while retrieving voting status.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
