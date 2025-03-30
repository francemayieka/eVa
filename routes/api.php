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

// Authentication
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Election Management
Route::post('/elections', [ElectionController::class, 'createElection']);
Route::patch('/elections/{id}/status', [ElectionController::class, 'updateStatus']);
Route::get('/elections/{id}', [ElectionController::class, 'getElectionDetails']);
Route::get('/elections', [ElectionController::class, 'getElections']);

// Candidate Management
Route::post('/candidates', [CandidateController::class, 'addCandidate']);
Route::get('/elections/{id}/candidates', [CandidateController::class, 'getCandidates']);

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
