<?php

namespace App\Http\Controllers;

use App\Models\Election;
use App\Models\Candidate;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;

class ElectionController extends Controller
{
    /**
     * Create a new election (auto-closing previous elections).
     */
    public function createElection(Request $request)
    {
        Log::info('Creating Election', ['request' => $request->all()]);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|unique:elections,name',
            'start_time' => 'required|date|after:now',
            'end_time' => 'required|date|after:start_time',
            'positions' => 'required|array|min:1',
            'positions.*' => 'required|string|distinct',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            // Close ALL previous elections (whether open or pending)
            Election::whereIn('status', ['open', 'pending'])->update(['status' => 'closed']);

            // Reset voting status for all users
            User::query()->update(['has_voted' => false, 'is_verified' => false]);

            // Create the new election with status 'open'
            $election = Election::create([
                'name' => $request->name,
                'start_time' => $request->start_time,
                'end_time' => $request->end_time,
                'status' => 'open', // Default to open
                'positions' => $request->positions,
            ]);

            return response()->json([
                'message' => 'Election created successfully.',
                'election' => $election,
            ], Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Election creation failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to create election.',
                'error' => $e->getMessage(),
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
            Log::error('Fetching election details failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to retrieve election details.',
                'error' => $e->getMessage(),
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
            Log::error('Fetching elections failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to retrieve elections.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Update an election.
     */
    public function updateElection(Request $request, $id)
    {
        try {
            $election = Election::find($id);
            if (!$election) {
                return response()->json([
                    'message' => 'Election not found.',
                ], Response::HTTP_NOT_FOUND);
            }

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|string|unique:elections,name,' . $id,
                'start_time' => 'sometimes|date|after:now',
                'end_time' => 'sometimes|date|after_or_equal:start_time',
                'positions' => 'sometimes|array|min:1',
                'positions.*' => 'sometimes|string|distinct',
                'status' => 'sometimes|string|in:pending,open,closed,reset,reopen',
                'description' => 'sometimes|string|max:500',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors' => $validator->errors(),
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $election->update($request->all());

            return response()->json([
                'message' => 'Election updated successfully.',
                'election' => $election,
            ], Response::HTTP_OK);

        } catch (\Exception $e) {
            Log::error('Updating election failed', ['error' => $e->getMessage()]);
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
            Log::error('Deleting election failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to delete election.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Fetch election results grouped by position.
     */
    public function getResults($id)
    {
        try {
            $election = Election::find($id);
            if (!$election) {
                return response()->json([
                    'message' => 'Election not found.',
                    'results' => []
                ], Response::HTTP_NOT_FOUND);
            }

            $results = Candidate::where('election_id', $id)
                ->withCount('votes')
                ->orderBy('position')
                ->orderByDesc('votes_count')
                ->get(['id', 'name', 'position', 'votes_count'])
                ->groupBy('position');

            return response()->json([
                'message' => 'Election results retrieved successfully.',
                'election' => $election->name,
                'results' => $results
            ], Response::HTTP_OK);
        } catch (\Exception $e) {
            Log::error('Fetching election results failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to retrieve election results.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Generate and store a PDF of election results.
     */
    public function downloadResultsPdf($id)
    {
        try {
            $election = Election::find($id);
            if (!$election) {
                return response()->json([
                    'message' => 'Election not found.'
                ], Response::HTTP_NOT_FOUND);
            }

            $results = Candidate::where('election_id', $id)
                ->select('position', 'name')
                ->withCount('votes')
                ->orderBy('position')
                ->orderByDesc('votes_count')
                ->get()
                ->groupBy('position');

            $pdf = Pdf::loadView('pdf.results', compact('election', 'results'));

            $filePath = 'results/election_' . $election->id . '_results.pdf';
            Storage::put($filePath, $pdf->output());

            return response()->json([
                'message' => 'PDF generated successfully.',
                'path' => storage_path('app/' . $filePath)
            ]);
        } catch (\Exception $e) {
            Log::error('Generating results PDF failed', ['error' => $e->getMessage()]);
            return response()->json([
                'message' => 'Failed to generate PDF.',
                'error' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
