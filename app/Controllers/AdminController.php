<?php

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\Registration;
use App\Models\Voter;
use App\Models\Candidate;
use App\Models\Vote;
use App\Models\AuditLog;
use App\Helpers\Security;
use App\Helpers\Response;
use App\Mail\Mailer;

class AdminController extends Controller
{
    public function dashboard(): void
    {
        AuthMiddleware::handle();

        $totalRegistrations = Registration::count();
        $tokensSent = Voter::count();
        $votesCast = Voter::countVoted();
        $remainingVoters = Voter::countRemaining();
        $turnoutPercent = Voter::getTurnoutPercentage();
        $totalCandidates = Candidate::count();

        $results = Vote::getResultsByPosition();
        $recentAudits = AuditLog::getRecent(10);

        $this->render('admin/dashboard', [
            'metrics' => [
                'total_registrations' => $totalRegistrations,
                'tokens_sent' => $tokensSent,
                'votes_cast' => $votesCast,
                'remaining_voters' => $remainingVoters,
                'turnout_percent' => $turnoutPercent,
                'total_candidates' => $totalCandidates,
            ],
            'results' => $results,
            'recent_audits' => $recentAudits,
            'csrf_token' => Security::generateCSRFToken()
        ]);
    }

    public function voters(): void
    {
        AuthMiddleware::handle();

        $voters = Voter::all();
        $this->render('admin/voters/index', [
            'voters' => $voters,
            'csrf_token' => Security::generateCSRFToken()
        ]);
    }

    public function resendToken(): void
    {
        AuthMiddleware::handle();

        $voterId = (int)($_POST['voter_id'] ?? 0);
        $voter = Voter::find($voterId);

        if (!$voter) {
            Response::json(['success' => false, 'message' => 'Voter not found.'], 44());
        }

        $reg = Registration::find($voter['registration_id']);
        $mailer = new Mailer();
        $sent = $mailer->sendVotingToken($voter['email'], $reg['fullname'] ?? 'Alumni Member', $voter['voting_token']);

        AuditLog::log("Resent voting token to {$voter['email']}", "Admin Management");

        Response::json([
            'success' => true,
            'message' => "Voting token resent successfully to {$voter['email']}."
        ]);
    }

    public function auditLogs(): void
    {
        AuthMiddleware::handle();

        $logs = AuditLog::getRecent(100);
        $this->render('admin/audit_logs', [
            'logs' => $logs
        ]);
    }
}
