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
     * Submit Vote API
     */
    public function vote(Request $request)
    {
        try {
            // Validate input
            $request->validate([
                'email' => 'required|email|exists:users,email',
                'votes' => 'required|array',
                'votes.*.candidate_id' => 'nullable|exists:candidates,id', // Candidate ID can be null
            ]);
    
            // Get user details
            $user = User::where('email', $request->email)->firstOrFail();
            
            // **Check if the user is verified**
            if (!$user->is_verified) {
                return response()->json(['message' => 'Kindly verify your email first.'], Response::HTTP_FORBIDDEN);
            }
    
            $election = Election::where('status', 'open')->first();
    
            if (!$election) {
                return response()->json(['message' => 'No active election at the moment.'], Response::HTTP_NOT_FOUND);
            }
    
            // Prevent normal users from voting more than once
            if ($user->role !== 'admin' && $user->has_voted) {
                return response()->json(['message' => 'You have already voted in this election.'], Response::HTTP_FORBIDDEN);
            }
    
            $votedPositions = [];
    
            foreach ($request->input('votes', []) as $voteObj) {
                $vote = (array) $voteObj; // Fix stdClass issue
    
                $candidateId = $vote['candidate_id'] ?? null;
                $position = null;
    
                // If a candidate is selected, fetch their position
                if ($candidateId) {
                    $candidate = Candidate::where('id', $candidateId)
                        ->where('election_id', $election->id)
                        ->first();
    
                    if (!$candidate) {
                        return response()->json(['message' => "Candidate ID $candidateId is not valid for this election."], Response::HTTP_BAD_REQUEST);
                    }
    
                    $position = $candidate->position;
                } else {
                    return response()->json(['message' => 'Position is required for abstaining.'], Response::HTTP_BAD_REQUEST);
                }
    
                // Ensure only one vote per position
                if (isset($votedPositions[$position])) {
                    return response()->json(['message' => "You can only vote for one candidate per position ($position)."], Response::HTTP_BAD_REQUEST);
                }
    
                // Record the vote
                Vote::create([
                    'voter_id' => $user->id,
                    'candidate_id' => $candidateId, // Null means abstain
                    'election_id' => $election->id,
                    'position' => $position,
                    'is_test_vote' => ($user->role === 'admin'),
                ]);
    
                $votedPositions[$position] = true;
            }
    
            // **Final Status Update**  
            if ($user->role === 'admin') {
                $user->update([
                    'is_verified' => true, // Keep admin verified
                    'has_voted' => false, // Allow admin to vote multiple times
                ]);
                return response()->json(['message' => 'Your test vote has been recorded.'], Response::HTTP_OK);
            } else {
                $user->update(['has_voted' => true]); // Users can only vote once
                return response()->json(['message' => 'Your vote has been recorded.'], Response::HTTP_OK);
            }
    
        } catch (\Exception $e) {
            Log::error('Voting error', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'An error occurred while submitting your vote.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
    
    


    /**
     * Fetch Voting Status API
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
