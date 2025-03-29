<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ElectionController;
use App\Http\Controllers\CandidateController;

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


