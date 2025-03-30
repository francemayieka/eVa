<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ElectionController;
use App\Http\Controllers\CandidateController;
use App\Http\Controllers\VerificationController;
use App\Http\Controllers\VotingController;

// Test API route
Route::get('/test-api', function () {
    return response()->json(['message' => 'API is working!']);
});

// Authentication (No Bearer Token)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout']);

// User Management
Route::patch('/user/update', [AuthController::class, 'updateProfile']);
Route::delete('/user/delete', [AuthController::class, 'deleteAccount']);

// Election Management
Route::post('/elections', [ElectionController::class, 'createElection']);
Route::get('/elections/{id}', [ElectionController::class, 'getElectionDetails']);
Route::get('/elections', [ElectionController::class, 'getElections']);
Route::patch('/elections', [ElectionController::class, 'updateElection']);
Route::delete('/elections/{id}', [ElectionController::class, 'deleteElection']);

// Candidate Management
Route::post('/candidates', [CandidateController::class, 'addCandidate']); // Add 1 or multiple candidates
Route::get('/elections/{id}/candidates', [CandidateController::class, 'getCandidates']); // Get all candidates for an election
Route::get('/candidates/{id}', [CandidateController::class, 'getCandidate']); // Fetch a single candidate
Route::patch('/candidates', [CandidateController::class, 'updateCandidate']); // Update candidate details
Route::delete('/candidates/{id}', [CandidateController::class, 'deleteCandidate']); // Delete candidate

// Verification Code API
Route::post('/request-verification-code', [VerificationController::class, 'requestVerificationCode']);
Route::post('/verify-code', [VerificationController::class, 'verifyCode']);

// Voting API
Route::post('/vote', [VotingController::class, 'vote']);
Route::get('/vote/status', [VotingController::class, 'getVoteStatus']);

// Fetch election results
Route::get('/elections/{id}/results', [ElectionController::class, 'getResults']);

// Download election results as PDF
Route::get('/elections/{id}/results/pdf', [ElectionController::class, 'downloadResultsPdf']);
