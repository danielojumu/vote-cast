<?php

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\Vote;
use App\Models\Voter;
use App\Models\AuditLog;
use App\Helpers\Security;

class ResultsController extends Controller
{
    public function index(): void
    {
        AuthMiddleware::handle();

        $results = Vote::getResultsByPosition();
        $votedCount = Voter::countVoted();
        $totalVoters = Voter::count();
        $turnout = Voter::getTurnoutPercentage();

        $this->render('admin/results', [
            'results' => $results,
            'voted_count' => $votedCount,
            'total_voters' => $totalVoters,
            'turnout' => $turnout,
            'csrf_token' => Security::generateCSRFToken()
        ]);
    }

    public function liveApi(): void
    {
        AuthMiddleware::handle();

        $results = Vote::getResultsByPosition();
        $turnout = Voter::getTurnoutPercentage();
        $voted = Voter::countVoted();
        $total = Voter::count();

        header('Content-Type: application/json');
        echo json_encode([
            'results' => $results,
            'voted' => $voted,
            'total' => $total,
            'turnout' => $turnout,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit;
    }
}
