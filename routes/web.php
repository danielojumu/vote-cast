<?php

use App\Controllers\RegistrationController;
use App\Controllers\VotingController;
use App\Controllers\AuthController;
use App\Controllers\AdminController;
use App\Controllers\CandidateController;
use App\Controllers\ResultsController;
use App\Controllers\ExportController;

return [
    'GET' => [
        '/' => [RegistrationController::class, 'showForm'],
        '/register' => [RegistrationController::class, 'showForm'],
        '/vote' => [VotingController::class, 'showVerifyForm'],
        '/vote/ballot' => [VotingController::class, 'showBallot'],
        
        // Admin Auth
        '/admin/login' => [AuthController::class, 'showLogin'],
        '/admin/logout' => [AuthController::class, 'logout'],
        
        // Admin Portal
        '/admin/dashboard' => [AdminController::class, 'dashboard'],
        '/admin/voters' => [AdminController::class, 'voters'],
        '/admin/audit-logs' => [AdminController::class, 'auditLogs'],
        
        // Candidates
        '/admin/candidates' => [CandidateController::class, 'index'],
        '/admin/candidates/create' => [CandidateController::class, 'create'],
        
        // Results & Exports
        '/admin/results' => [ResultsController::class, 'index'],
        '/admin/results/api' => [ResultsController::class, 'liveApi'],
        '/admin/export/csv' => [ExportController::class, 'exportCsv'],
        '/admin/export/excel' => [ExportController::class, 'exportExcel'],
        '/admin/export/pdf' => [ExportController::class, 'exportPdf'],
    ],
    'POST' => [
        '/register' => [RegistrationController::class, 'submit'],
        '/vote/verify' => [VotingController::class, 'verify'],
        '/vote/submit' => [VotingController::class, 'submitVote'],
        
        // Admin Auth
        '/admin/login' => [AuthController::class, 'login'],
        
        // Candidates Actions
        '/admin/candidates/store' => [CandidateController::class, 'store'],
        '/admin/resend-token' => [AdminController::class, 'resendToken'],
    ]
];
