<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Election;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class CandidateController extends Controller
{
    /**
     * 1️⃣ Add multiple candidates to an election.
     */
    public function addCandidate(Request $request)
    {
        try {
            $validated = $request->validate([
                'candidates' => 'required|array|min:1',
                'candidates.*.name' => 'required|string|max:255',
                'candidates.*.position' => 'required|string|max:255',
                'candidates.*.image' => 'nullable|string', // Expect image URL or stored path
                'election_id' => 'required|exists:elections,id',
            ]);

            $candidatesData = [];
            foreach ($validated['candidates'] as $candidate) {
                $candidatesData[] = [
                    'name' => $candidate['name'],
                    'position' => $candidate['position'],
                    'image' => $candidate['image'] ?? null,
                    'election_id' => $validated['election_id'],
                ];
            }

            Candidate::insert($candidatesData);

            return response()->json([
                'message' => 'Candidates added successfully.',
                'candidates' => $candidatesData,
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Error adding candidates', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'An error occurred while adding candidates.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Fetch all candidates for a given election.
     */
    public function getCandidates($election_id)
    {
        try {
            // Ensure the election exists
            $election = Election::find($election_id);

            if (!$election) {
                return response()->json([
                    'message' => 'Election not found.',
                ], Response::HTTP_NOT_FOUND);
            }

            // Fetch candidates
            $candidates = Candidate::where('election_id', $election_id)
                ->orderBy('position')
                ->get()
                ->groupBy('position');

            return response()->json([
                'message' => 'Candidates retrieved successfully.',
                'election' => $election->name,
                'candidates' => $candidates,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Error fetching candidates', ['error' => $e->getMessage()]);

            return response()->json([
                'message' => 'An error occurred while fetching candidates.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Fetch a single candidate.
     */
    public function getCandidate($id)
    {
        try {
            $candidate = Candidate::find($id);

            if (!$candidate) {
                return response()->json(['message' => 'Candidate not found.'], Response::HTTP_NOT_FOUND);
            }

            return response()->json([
                'message' => 'Candidate retrieved successfully.',
                'candidate' => $candidate,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Error fetching candidate', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'An error occurred while fetching the candidate.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update a candidate's details.
     */
    public function updateCandidate(Request $request)
    {
        try {
            $validated = $request->validate([
                'id' => 'required|exists:candidates,id',
                'name' => 'sometimes|string|max:255',
                'position' => 'sometimes|string|max:255',
                'image' => 'nullable|string', // Expect image URL or stored path
            ]);

            $candidate = Candidate::find($validated['id']);
            $candidate->update($validated);

            return response()->json([
                'message' => 'Candidate updated successfully.',
                'candidate' => $candidate,
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Error updating candidate', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'An error occurred while updating the candidate.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Delete a candidate.
     */
    public function deleteCandidate($id)
    {
        try {
            $candidate = Candidate::find($id);

            if (!$candidate) {
                return response()->json(['message' => 'Candidate not found.'], Response::HTTP_NOT_FOUND);
            }

            $candidate->delete();

            return response()->json([
                'message' => 'Candidate deleted successfully.',
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Error deleting candidate', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'An error occurred while deleting the candidate.',
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
