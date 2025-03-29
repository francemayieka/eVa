<?php

namespace App\Http\Controllers;

use App\Models\Candidate;
use App\Models\Election;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class CandidateController extends Controller
{
    // Add a candidate to a position
    public function addCandidate(Request $request)
    {
        $request->validate([
            'name' => 'required|string',
            'position' => 'required|string',
            'image' => 'nullable|image|max:2048',
            'election_id' => 'required|exists:elections,id',
        ]);

        $imagePath = $request->file('image') 
            ? $request->file('image')->store('candidates', 'public') 
            : null;

        $candidate = Candidate::create([
            'name' => $request->name,
            'position' => $request->position,
            'image' => $imagePath,
            'election_id' => $request->election_id,
        ]);

        return response()->json([
            'message' => 'Candidate added successfully',
            'candidate' => $candidate
        ], Response::HTTP_CREATED);
    }

    // Fetch candidates for an election
    public function getCandidates($election_id)
    {
        $election = Election::findOrFail($election_id);
        $candidates = $election->candidates;

        return response()->json($candidates);
    }
}
