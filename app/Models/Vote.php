<?php

namespace App\Models;

use PDO;

class Vote extends Model
{
    protected static string $table = 'votes';

    public static function recordVote(int $voterId, int $candidateId, int $positionId): bool
    {
        $stmt = static::db()->prepare("
            INSERT INTO votes (voter_id, candidate_id, position_id)
            VALUES (:voter_id, :cand_id, :pos_id)
        ");
        return $stmt->execute([
            ':voter_id' => $voterId,
            ':cand_id' => $candidateId,
            ':pos_id' => $positionId,
        ]);
    }

    public static function getResultsByPosition(): array
    {
        $positions = Position::getActiveOrdered();
        $results = [];

        foreach ($positions as $pos) {
            // Total votes for this position
            $stmtTotal = static::db()->prepare("SELECT COUNT(*) FROM votes WHERE position_id = :pos_id");
            $stmtTotal->execute([':pos_id' => $pos['id']]);
            $totalVotesForPos = (int)$stmtTotal->fetchColumn();

            // Votes per candidate
            $stmtCand = static::db()->prepare("
                SELECT c.id, c.fullname, c.recent_photo, c.university_photo, c.manifesto,
                       COUNT(v.id) as vote_count
                FROM candidates c
                LEFT JOIN votes v ON v.candidate_id = c.id AND v.position_id = :pos_id
                WHERE c.position_id = :pos_id2 AND c.active = 1
                GROUP BY c.id
                ORDER BY vote_count DESC, c.fullname ASC
            ");
            $stmtCand->execute([':pos_id' => $pos['id'], ':pos_id2' => $pos['id']]);
            $candidates = $stmtCand->fetchAll();

            $candidatesFormatted = array_map(function($c) use ($totalVotesForPos) {
                $percentage = $totalVotesForPos > 0 ? round(($c['vote_count'] / $totalVotesForPos) * 100, 1) : 0.0;
                return array_merge($c, [
                    'vote_count' => (int)$c['vote_count'],
                    'percentage' => $percentage
                ]);
            }, $candidates);

            $results[] = [
                'position' => $pos,
                'total_votes' => $totalVotesForPos,
                'candidates' => $candidatesFormatted
            ];
        }

        return $results;
    }
}
