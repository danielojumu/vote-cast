<?php

namespace App\Controllers;

use App\Middleware\AuthMiddleware;
use App\Models\Vote;
use App\Models\Voter;
use App\Models\AuditLog;

class ExportController extends Controller
{
    public function exportCsv(): void
    {
        AuthMiddleware::handle();

        $results = Vote::getResultsByPosition();

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=BUI_Beta_Set_Election_Results_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['BUI Beta Set Alumni Election Official Results']);
        fputcsv($output, ['Generated At:', date('Y-m-d H:i:s')]);
        fputcsv($output, []);
        fputcsv($output, ['Position', 'Candidate Name', 'Votes Received', 'Percentage (%)']);

        foreach ($results as $res) {
            $posName = $res['position']['name'];
            foreach ($res['candidates'] as $cand) {
                fputcsv($output, [
                    $posName,
                    $cand['fullname'],
                    $cand['vote_count'],
                    $cand['percentage'] . '%'
                ]);
            }
        }

        fclose($output);
        AuditLog::log("Exported election results to CSV", "Export System");
        exit;
    }

    public function exportExcel(): void
    {
        AuthMiddleware::handle();

        $results = Vote::getResultsByPosition();

        header('Content-Type: application/vnd.ms-excel; charset=utf-8');
        header('Content-Disposition: attachment; filename=BUI_Beta_Set_Election_Results_' . date('Y-m-d') . '.xls');

        echo "Position\tCandidate Name\tVotes Received\tPercentage (%)\n";
        foreach ($results as $res) {
            $posName = $res['position']['name'];
            foreach ($res['candidates'] as $cand) {
                echo "{$posName}\t{$cand['fullname']}\t{$cand['vote_count']}\t{$cand['percentage']}%\n";
            }
        }

        AuditLog::log("Exported election results to Excel", "Export System");
        exit;
    }

    public function exportPdf(): void
    {
        AuthMiddleware::handle();

        $results = Vote::getResultsByPosition();
        $voted = Voter::countVoted();
        $total = Voter::count();
        $turnout = Voter::getTurnoutPercentage();

        header('Content-Type: text/html; charset=utf-8');
        echo "
        <!DOCTYPE html>
        <html>
        <head>
            <title>BUI Beta Set Alumni Election Official PDF Report</title>
            <style>
                body { font-family: Arial, sans-serif; padding: 40px; color: #1e293b; line-height: 1.5; }
                .header { text-align: center; border-bottom: 3px solid #1A3A5C; padding-bottom: 20px; margin-bottom: 30px; }
                .header h1 { color: #1A3A5C; margin: 0; font-size: 24px; }
                .header p { margin: 5px 0 0 0; color: #64748b; font-size: 14px; }
                .stats-grid { display: flex; gap: 20px; margin-bottom: 30px; }
                .stat-box { flex: 1; background: #f8fafc; border: 1px solid #e2e8f0; padding: 15px; border-radius: 6px; text-align: center; }
                .stat-num { font-size: 22px; font-weight: bold; color: #1A3A5C; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
                th { background-color: #1A3A5C; color: white; padding: 10px; text-align: left; font-size: 13px; }
                td { padding: 10px; border-bottom: 1px solid #e2e8f0; font-size: 13px; }
                .pos-header { background: #f1f5f9; font-weight: bold; color: #0f172a; }
                .footer { margin-top: 50px; text-align: center; font-size: 11px; color: #94a3b8; border-top: 1px solid #e2e8f0; padding-top: 15px; }
                @media print {
                    body { padding: 0; }
                    .no-print { display: none; }
                }
            </style>
        </head>
        <body>
            <div class='no-print' style='margin-bottom: 20px; text-align: right;'>
                <button onclick='window.print()' style='background: #1A3A5C; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; font-weight: bold;'>Print / Save as PDF</button>
            </div>
            <div class='header'>
                <h1>BUI BETA SET ALUMNI ASSOCIATION</h1>
                <p>Official Executive Election Results Report | https://vote.buibetaset.org</p>
                <p style='font-size: 12px;'>Generated on: " . date('F j, Y - g:i A T') . "</p>
            </div>

            <div class='stats-grid'>
                <div class='stat-box'>
                    <div style='font-size: 12px; color: #64748b;'>Total Registered Voters</div>
                    <div class='stat-num'>{$total}</div>
                </div>
                <div class='stat-box'>
                    <div style='font-size: 12px; color: #64748b;'>Total Votes Cast</div>
                    <div class='stat-num'>{$voted}</div>
                </div>
                <div class='stat-box'>
                    <div style='font-size: 12px; color: #64748b;'>Voter Turnout</div>
                    <div class='stat-num'>{$turnout}%</div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Candidate Name</th>
                        <th>Position</th>
                        <th>Votes Received</th>
                        <th>Percentage</th>
                    </tr>
                </thead>
                <tbody>";

        foreach ($results as $res) {
            $posName = $res['position']['name'];
            echo "<tr class='pos-header'><td colspan='4'>Position: {$posName} (Total Votes: {$res['total_votes']})</td></tr>";
            foreach ($res['candidates'] as $cand) {
                echo "<tr>
                    <td><strong>" . htmlspecialchars($cand['fullname']) . "</strong></td>
                    <td>" . htmlspecialchars($posName) . "</td>
                    <td>{$cand['vote_count']}</td>
                    <td><strong>{$cand['percentage']}%</strong></td>
                </tr>";
            }
        }

        echo "
                </tbody>
            </table>

            <div style='margin-top: 40px;'>
                <p><strong>Certified Correct By:</strong></p>
                <div style='margin-top: 30px; border-top: 1px dashed #94a3b8; width: 250px; padding-top: 5px; font-size: 12px;'>
                    Electoral Commission Chairman<br>
                    BUI Beta Set Alumni Association
                </div>
            </div>

            <div class='footer'>
                VCAST Electoral System &copy; " . date('Y') . " BUI Beta Set Alumni Association. All Rights Reserved.
            </div>
        </body>
        </html>";

        AuditLog::log("Generated printable PDF election results report", "Export System");
        exit;
    }
}
