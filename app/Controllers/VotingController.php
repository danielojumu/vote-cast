<?php

namespace App\Controllers;

use App\Helpers\Security;
use App\Helpers\Response;
use App\Models\Voter;
use App\Models\Position;
use App\Models\Candidate;
use App\Models\Vote;
use App\Models\AuditLog;

class VotingController extends Controller
{
    public function showVerifyForm(): void
    {
        $this->render('vote/verify', [
            'csrf_token' => Security::generateCSRFToken()
        ]);
    }

    public function verify(): void
    {
        $email = strtolower(trim(Security::sanitize($_POST['email'] ?? '')));
        $token = strtoupper(trim(Security::sanitize($_POST['token'] ?? '')));

        if (empty($email) || empty($token)) {
            Response::json([
                'success' => false,
                'message' => 'Please enter both your email address and voting token.'
            ], 400);
        }

        $voter = Voter::findByEmailAndToken($email, $token);

        if (!$voter) {
            Response::json([
                'success' => false,
                'message' => 'Invalid email or voting token.'
            ], 401);
        }

        if ($voter['has_voted']) {
            Response::json([
                'success' => false,
                'message' => 'This voting token has already been used. Duplicate voting is strictly prohibited.'
            ], 403);
        }

        Response::json([
            'success' => true,
            'message' => 'Token verified successfully.',
            'redirect' => "/vote/ballot?email=" . urlencode($email) . "&token=" . urlencode($token)
        ]);
    }

    public function showBallot(): void
    {
        $email = strtolower(trim($_GET['email'] ?? ''));
        $token = strtoupper(trim($_GET['token'] ?? ''));

        $voter = Voter::findByEmailAndToken($email, $token);

        if (!$voter || $voter['has_voted']) {
            header('Location: /vote?error=invalid_or_used');
            exit;
        }

        $positions = Position::getActiveOrdered();
        $ballotData = [];

        foreach ($positions as $pos) {
            $candidates = Candidate::getByPosition($pos['id']);
            $ballotData[] = [
                'position' => $pos,
                'candidates' => $candidates
            ];
        }

        $this->render('vote/ballot', [
            'voter' => $voter,
            'email' => $email,
            'token' => $token,
            'ballot' => $ballotData,
            'csrf_token' => Security::generateCSRFToken()
        ]);
    }

    public function submitVote(): void
    {
        $email = strtolower(trim(Security::sanitize($_POST['email'] ?? '')));
        $token = strtoupper(trim(Security::sanitize($_POST['token'] ?? '')));
        $choices = $_POST['choices'] ?? []; // Array of [position_id => candidate_id]

        $voter = Voter::findByEmailAndToken($email, $token);

        if (!$voter) {
            Response::json([
                'success' => false,
                'message' => 'Invalid email or voting token.'
            ], 401);
        }

        if ($voter['has_voted']) {
            Response::json([
                'success' => false,
                'message' => 'Your vote has already been recorded.'
            ], 403);
        }

        $positions = Position::getActiveOrdered();
        if (empty($choices) || count($choices) < count($positions)) {
            Response::json([
                'success' => false,
                'message' => 'Please select a candidate for every position before submitting your ballot.'
            ], 422);
        }

        try {
            // Begin Transaction logic
            foreach ($positions as $pos) {
                $posId = $pos['id'];
                $candidateId = (int)($choices[$posId] ?? 0);

                if ($candidateId > 0) {
                    Vote::recordVote($voter['id'], $candidateId, $posId);
                }
            }

            // Mark token as used
            Voter::markVoted($voter['id']);

            // Log Audit
            AuditLog::log("Vote submitted successfully by voter ID {$voter['id']} ({$email})", "Voting System");

            Response::json([
                'success' => true,
                'message' => 'Thank you. Your vote has been recorded.'
            ]);
        } catch (\Exception $e) {
            error_log("Vote Submission Error: " . $e->getMessage());
            Response::json([
                'success' => false,
                'message' => 'An error occurred while submitting your vote. Please try again.'
            ], 500);
        }
    }
}
