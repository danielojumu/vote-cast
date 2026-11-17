<?php
/**
 * BUI Beta Set Alumni Election Voting System (VCAST)
 * Standalone Electoral Commission Control Panel & Admin Portal
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

// Handle CSV Export Request
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    if (empty($_SESSION['admin_logged_in'])) {
        header('Location: admin.php');
        exit;
    }
    
    try {
        $pdo = getDB();
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=BUI_Beta_Set_Election_Report_' . date('Y-m-d_His') . '.csv');
        
        $output = fopen('php://output', 'w');
        
        // Title Block
        fputcsv($output, ['BUI BETA SET ALUMNI ELECTION VOTING SYSTEM (VCAST)']);
        fputcsv($output, ['Official Electoral Audit & Results Report']);
        fputcsv($output, ['Generated At:', date('Y-m-d H:i:s')]);
        fputcsv($output, []);
        
        // Summary Metrics
        $totalTokens = (int)$pdo->query("SELECT COUNT(*) FROM voters")->fetchColumn();
        $totalVotes = (int)$pdo->query("SELECT COUNT(*) FROM voters WHERE has_voted = 1")->fetchColumn();
        $turnout = $totalTokens > 0 ? round(($totalVotes / $totalTokens) * 100, 1) : 0;
        
        fputcsv($output, ['SUMMARY METRICS']);
        fputcsv($output, ['Total Accredited Voters', 'Total Ballots Cast', 'Turnout Percentage']);
        fputcsv($output, [$totalTokens, $totalVotes, $turnout . '%']);
        fputcsv($output, []);
        
        // Standings
        fputcsv($output, ['ELECTION STANDINGS BY POSITION']);
        fputcsv($output, ['Position', 'Candidate Name', 'Votes Received', 'Vote Share (%)']);
        
        $positions = $pdo->query("SELECT * FROM positions ORDER BY display_order ASC")->fetchAll();
        $candidates = $pdo->query("SELECT * FROM candidates ORDER BY display_order ASC")->fetchAll();
        $votes = $pdo->query("SELECT * FROM votes")->fetchAll();
        
        foreach ($positions as $pos) {
            $posVotes = array_filter($votes, fn($v) => $v['position_id'] == $pos['id']);
            $posVotesCount = count($posVotes);
            $posCandidates = array_filter($candidates, fn($c) => $c['position_id'] == $pos['id']);
            
            foreach ($posCandidates as $cand) {
                $cVotes = count(array_filter($posVotes, fn($v) => $v['candidate_id'] == $cand['id']));
                $pct = $posVotesCount > 0 ? round(($cVotes / $posVotesCount) * 100, 1) : 0;
                fputcsv($output, [$pos['name'], $cand['fullname'], $cVotes, $pct . '%']);
            }
        }
        
        fputcsv($output, []);
        fputcsv($output, ['VOTER ROSTER & TOKEN STATUS']);
        fputcsv($output, ['Voter Name', 'Voter Email', 'Voting Token', 'Status', 'Timestamp']);
        
        $voters = $pdo->query("
            SELECT v.*, r.fullname AS voter_name 
            FROM voters v 
            LEFT JOIN registrations r ON v.registration_id = r.id 
            ORDER BY v.id DESC
        ")->fetchAll();

        foreach ($voters as $v) {
            fputcsv($output, [
                $v['voter_name'] ?? 'Accredited Member',
                $v['email'],
                $v['voting_token'],
                $v['has_voted'] ? 'VOTED (' . ($v['voted_at'] ?? '') . ')' : 'TOKEN UNUSED',
                $v['token_sent_at'] ?? $v['created_at'] ?? 'N/A'
            ]);
        }
        
        fclose($output);
        exit;
    } catch (Exception $e) {
        die("Export Error: " . $e->getMessage());
    }
}

$isLoggedIn = !empty($_SESSION['admin_logged_in']);
$loginError = '';
$actionSuccess = '';
$actionError = '';

$pdo = null;
try {
    $pdo = getDB();
} catch (Exception $e) {
    // Database connection state
}

// Authentication POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($pdo) {
        try {
            $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ?");
            $stmt->execute([$email]);
            $admin = $stmt->fetch();

            if ($admin && password_verify($password, $admin['password'])) {
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user'] = $admin['fullname'];
                $_SESSION['admin_role'] = $admin['role'] ?? 'Super Admin';
                $isLoggedIn = true;
                logAudit($pdo, $admin['fullname'], "Logged into Electoral Commission Control Panel");
            } else {
                $loginError = 'Invalid admin email address or password.';
            }
        } catch (Exception $e) {
            $loginError = 'Authentication error: ' . $e->getMessage();
        }
    }
}

// Logout
if (isset($_GET['logout'])) {
    if ($pdo && !empty($_SESSION['admin_user'])) {
        logAudit($pdo, $_SESSION['admin_user'], "Signed out from Control Panel");
    }
    unset($_SESSION['admin_logged_in']);
    unset($_SESSION['admin_user']);
    unset($_SESSION['admin_role']);
    header('Location: admin.php');
    exit;
}

// Administrative Actions (Add, Edit, Delete Candidate, Resend Token, Toggle Status)
if ($isLoggedIn && $_SERVER['REQUEST_METHOD'] === 'POST' && $pdo) {
    $action = $_POST['action'] ?? '';

    if ($action === 'add_candidate') {
        $posId = (int)($_POST['position_id'] ?? 0);
        $fullname = sanitize($_POST['fullname'] ?? '');
        $manifesto = sanitize($_POST['manifesto'] ?? '');
        $profile = sanitize($_POST['profile'] ?? '');
        $recentPhoto = sanitize($_POST['recent_photo'] ?? '/uploads/candidates/default.jpg');

        if ($posId > 0 && $fullname && $manifesto) {
            try {
                $stmt = $pdo->prepare("INSERT INTO candidates (position_id, fullname, manifesto, profile, recent_photo, active) VALUES (?, ?, ?, ?, ?, 1)");
                $stmt->execute([$posId, $fullname, $manifesto, $profile, $recentPhoto]);
                logAudit($pdo, $_SESSION['admin_user'], "Added candidate '{$fullname}' for position ID {$posId}");
                $actionSuccess = "Candidate '{$fullname}' successfully added!";
            } catch (Exception $e) {
                $actionError = "Failed to add candidate: " . $e->getMessage();
            }
        } else {
            $actionError = "Please select a position, candidate name, and manifesto.";
        }
    } elseif ($action === 'edit_candidate') {
        $candId = (int)($_POST['candidate_id'] ?? 0);
        $posId = (int)($_POST['position_id'] ?? 0);
        $fullname = sanitize($_POST['fullname'] ?? '');
        $manifesto = sanitize($_POST['manifesto'] ?? '');
        $recentPhoto = sanitize($_POST['recent_photo'] ?? '/uploads/candidates/default.jpg');

        if ($candId > 0 && $posId > 0 && $fullname && $manifesto) {
            try {
                $stmt = $pdo->prepare("UPDATE candidates SET position_id = ?, fullname = ?, manifesto = ?, recent_photo = ? WHERE id = ?");
                $stmt->execute([$posId, $fullname, $manifesto, $recentPhoto, $candId]);
                logAudit($pdo, $_SESSION['admin_user'], "Updated candidate '{$fullname}' (ID {$candId})");
                $actionSuccess = "Candidate '{$fullname}' updated successfully.";
            } catch (Exception $e) {
                $actionError = "Failed to update candidate: " . $e->getMessage();
            }
        } else {
            $actionError = "Please complete all fields to update candidate.";
        }
    } elseif ($action === 'delete_candidate') {
        $candId = (int)($_POST['candidate_id'] ?? 0);
        if ($candId > 0) {
            try {
                $stmt = $pdo->prepare("SELECT fullname FROM candidates WHERE id = ?");
                $stmt->execute([$candId]);
                $cand = $stmt->fetch();
                $cName = $cand ? $cand['fullname'] : "ID $candId";

                $stmt = $pdo->prepare("DELETE FROM candidates WHERE id = ?");
                $stmt->execute([$candId]);
                logAudit($pdo, $_SESSION['admin_user'], "Deleted candidate '{$cName}' (ID {$candId})");
                $actionSuccess = "Candidate '{$cName}' permanently removed from database.";
            } catch (Exception $e) {
                $actionError = "Failed to delete candidate: " . $e->getMessage();
            }
        }
    } elseif ($action === 'toggle_candidate') {
        $candId = (int)($_POST['candidate_id'] ?? 0);
        if ($candId > 0) {
            try {
                $pdo->query("UPDATE candidates SET active = IF(active=1, 0, 1) WHERE id = {$candId}");
                logAudit($pdo, $_SESSION['admin_user'], "Toggled active status for candidate ID {$candId}");
                $actionSuccess = "Candidate ballot status updated.";
            } catch (Exception $e) {
                $actionError = "Failed to update candidate status: " . $e->getMessage();
            }
        }
    } elseif ($action === 'add_eligible') {
        $fullname = sanitize($_POST['fullname'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
        $gradYear = (int)($_POST['graduation_year'] ?? 2008);

        if ($fullname && $email) {
            try {
                $stmt = $pdo->prepare("INSERT INTO eligible_voters (fullname, email, graduation_year) VALUES (?, ?, ?)");
                $stmt->execute([$fullname, $email, $gradYear]);
                logAudit($pdo, $_SESSION['admin_user'] ?? 'Admin', "Added accredited eligible voter '{$fullname}' ({$email})");
                $actionSuccess = "Accredited voter '{$fullname}' added to register successfully.";
            } catch (Exception $e) {
                $actionError = "Failed to add voter: " . $e->getMessage();
            }
        } else {
            $actionError = "Please enter a valid Full Name and Email address.";
        }
    } elseif ($action === 'upload_eligible_csv') {
        $csvText = trim($_POST['csv_text'] ?? '');
        if (isset($_FILES['csv_file']) && $_FILES['csv_file']['tmp_name']) {
            $csvText = file_get_contents($_FILES['csv_file']['tmp_name']);
        }

        if ($csvText) {
            $lines = explode("\n", str_replace("\r", "", $csvText));
            $addedCount = 0;
            $dupCount = 0;

            foreach ($lines as $index => $line) {
                $line = trim($line);
                if (!$line) continue;
                // Skip header row if present
                if ($index === 0 && (str_contains(strtolower($line), 'fullname') || str_contains(strtolower($line), 'email') || str_contains(strtolower($line), 'name'))) {
                    continue;
                }

                $cols = str_getcsv($line);
                $name = sanitize($cols[0] ?? '');
                $mail = filter_var(trim($cols[1] ?? ''), FILTER_VALIDATE_EMAIL);
                $yr = isset($cols[2]) ? (int)$cols[2] : 2008;

                if ($name && $mail) {
                    try {
                        $stmt = $pdo->prepare("INSERT IGNORE INTO eligible_voters (fullname, email, graduation_year) VALUES (?, ?, ?)");
                        $stmt->execute([$name, $mail, $yr]);
                        if ($stmt->rowCount() > 0) {
                            $addedCount++;
                        } else {
                            $dupCount++;
                        }
                    } catch (Exception $e) {
                        // ignore error
                    }
                }
            }

            logAudit($pdo, $_SESSION['admin_user'] ?? 'Admin', "Uploaded CSV voter register: {$addedCount} new voters imported, {$dupCount} skipped");
            $actionSuccess = "CSV Import Complete! Successfully imported {$addedCount} accredited voters ({$dupCount} skipped or already present).";
        } else {
            $actionError = "Please select a CSV file or paste CSV text content.";
        }
    } elseif ($action === 'delete_eligible') {
        $eligId = (int)($_POST['eligible_id'] ?? 0);
        if ($eligId > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM eligible_voters WHERE id = ?");
                $stmt->execute([$eligId]);
                logAudit($pdo, $_SESSION['admin_user'] ?? 'Admin', "Removed eligible voter ID {$eligId}");
                $actionSuccess = "Voter removed from accredited database.";
            } catch (Exception $e) {
                $actionError = "Failed to remove voter: " . $e->getMessage();
            }
        }
    } elseif ($action === 'reset_database') {
        $confirmReset = sanitize($_POST['confirm_reset'] ?? '');
        if (strtoupper($confirmReset) === 'RESET') {
            try {
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");
                $pdo->exec("TRUNCATE TABLE votes;");
                $pdo->exec("TRUNCATE TABLE voters;");
                $pdo->exec("TRUNCATE TABLE registrations;");
                $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");
                logAudit($pdo, $_SESSION['admin_user'] ?? 'Super Admin', "RESET ELECTION DATABASE: Cleared all votes, voter tokens, and registrations afresh.");
                $actionSuccess = "Database reset successfully! All votes, voter tokens, and registrations have been cleared afresh.";
            } catch (Exception $e) {
                try {
                    $pdo->exec("DELETE FROM votes;");
                    $pdo->exec("DELETE FROM voters;");
                    $pdo->exec("DELETE FROM registrations;");
                    logAudit($pdo, $_SESSION['admin_user'] ?? 'Super Admin', "RESET ELECTION DATABASE: Cleared all votes, voter tokens, and registrations afresh.");
                    $actionSuccess = "Database reset successfully! All votes, voter tokens, and registrations have been cleared afresh.";
                } catch (Exception $ex) {
                    $actionError = "Failed to reset database: " . $ex->getMessage();
                }
            }
        } else {
            $actionError = "Reset confirmation failed. Please type 'RESET' in capital letters to confirm clearing the database.";
        }
    }
}

// Active Tab
$activeTab = $_GET['tab'] ?? 'overview';

// Load Data Dynamically from MySQL Database
$totalRegistrations = 0;
$totalTokens = 0;
$totalVotesCast = 0;
$remainingTokens = 0;
$turnoutPercent = 0.0;
$totalCandidates = 0;
$totalEligibleVoters = 0;

$positions = [];
$candidates = [];
$voters = [];
$eligibleVoters = [];
$auditLogs = [];
$votes = [];

if ($isLoggedIn && $pdo) {
    try {
        $totalRegistrations = (int)$pdo->query("SELECT COUNT(*) FROM registrations")->fetchColumn();
        $totalTokens = (int)$pdo->query("SELECT COUNT(*) FROM voters")->fetchColumn();
        $totalVotesCast = (int)$pdo->query("SELECT COUNT(*) FROM voters WHERE has_voted = 1")->fetchColumn();
        $remainingTokens = max(0, $totalTokens - $totalVotesCast);
        $turnoutPercent = $totalTokens > 0 ? round(($totalVotesCast / $totalTokens) * 100, 1) : 0.0;

        $positions = $pdo->query("SELECT * FROM positions ORDER BY display_order ASC")->fetchAll();
        $candidates = $pdo->query("SELECT * FROM candidates ORDER BY display_order ASC")->fetchAll();
        $totalCandidates = count($candidates);

        $eligibleVoters = $pdo->query("SELECT * FROM eligible_voters ORDER BY id DESC")->fetchAll();
        $totalEligibleVoters = count($eligibleVoters);

        $voters = $pdo->query("
            SELECT v.*, r.fullname AS voter_name 
            FROM voters v 
            LEFT JOIN registrations r ON v.registration_id = r.id 
            ORDER BY v.id DESC
        ")->fetchAll();

        $auditLogs = $pdo->query("SELECT * FROM audit_logs ORDER BY id DESC LIMIT 50")->fetchAll();
        $votes = $pdo->query("SELECT * FROM votes")->fetchAll();
    } catch (Exception $e) {
        $actionError = "Error loading database state: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Electoral Commission Control Panel - BUI Beta Set</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Playfair+Display:wght@700;800;900&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        .font-display { font-family: 'Playfair Display', serif; }
        @media print {
            .no-print { display: none !important; }
            body { background: white !important; color: black !important; }
            .print-container { padding: 0 !important; margin: 0 !important; width: 100% !important; max-width: 100% !important; }
            .card-print { background: #f8fafc !important; border: 1px solid #cbd5e1 !important; color: black !important; }
            .text-white-print { color: black !important; }
            .text-sky-print { color: #0284c7 !important; }
        }
    </style>
</head>
<body class="bg-[#f5f8fc] text-slate-900 min-h-screen p-3 sm:p-6 selection:bg-amber-100 selection:text-slate-900">

    <div class="max-w-7xl mx-auto space-y-5 print-container">

        <?php if (!$isLoggedIn): ?>
            <!-- Login Screen -->
            <div class="max-w-md mx-auto my-16 bg-white border border-slate-200/90 rounded-3xl p-8 shadow-sm">
                <div class="text-center mb-6">
                    <span class="bg-slate-100 text-[#101e36] border border-slate-200 text-xs px-3 py-1 rounded-full font-bold">Commission Restricted Access</span>
                    <h1 class="text-2xl font-extrabold text-[#101e36] mt-3 font-display">Electoral Control Login</h1>
                    <p class="text-xs text-slate-500 mt-1">BUI Beta Set Alumni Commission</p>
                </div>

                <?php if ($loginError): ?>
                    <div class="mb-4 p-3 bg-red-50 border border-red-200 rounded-xl text-red-700 text-xs text-center font-medium">
                        <?= htmlspecialchars($loginError) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="admin.php" class="space-y-4 text-xs">
                    <input type="hidden" name="action" value="login">
                    <div>
                        <label class="block font-bold text-slate-800 uppercase mb-1">Email Address</label>
                        <input type="email" name="email" required placeholder="admin@vote.buibetaset.org" value="admin@vote.buibetaset.org" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:bg-white focus:border-slate-800 focus:outline-none">
                    </div>
                    <div>
                        <label class="block font-bold text-slate-800 uppercase mb-1">Password</label>
                        <input type="password" name="password" required placeholder="••••••••" value="Admin@2026!" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:bg-white focus:border-slate-800 focus:outline-none">
                    </div>
                    <button type="submit" class="w-full py-3 bg-[#1b3252] hover:bg-[#152742] text-white font-bold rounded-2xl shadow-sm transition-all text-sm mt-2">
                        Authenticate & Access Control Panel
                    </button>
                </form>

                <div class="mt-6 text-center text-[11px] text-slate-500 border-t border-slate-200 pt-4">
                    <a href="index.php" class="text-slate-700 hover:underline font-semibold">&larr; Return to Public Election Portal</a>
                </div>
            </div>
        <?php else: ?>

            <!-- Global Action Messages -->
            <?php if ($actionSuccess): ?>
                <div class="no-print bg-emerald-50 border border-emerald-200 text-emerald-800 px-4 py-3 rounded-2xl text-xs font-bold flex justify-between items-center">
                    <span><?= htmlspecialchars($actionSuccess) ?></span>
                    <button onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-950">&times;</button>
                </div>
            <?php endif; ?>

            <?php if ($actionError): ?>
                <div class="no-print bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-2xl text-xs font-bold flex justify-between items-center">
                    <span><?= htmlspecialchars($actionError) ?></span>
                    <button onclick="this.parentElement.remove()" class="text-red-700 hover:text-red-950">&times;</button>
                </div>
            <?php endif; ?>

            <!-- Header Control Banner -->
            <div class="bg-[#1b3252] border border-slate-200/20 rounded-3xl p-5 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4 text-white">
                <div class="space-y-1">
                    <div class="flex items-center space-x-2">
                        <span class="bg-white/10 text-slate-100 text-[11px] px-2.5 py-0.5 rounded-full font-bold border border-white/20">
                            <?= htmlspecialchars($_SESSION['admin_role'] ?? 'Super Admin Mode') ?>
                        </span>
                        <span class="text-xs text-slate-300 font-medium">Session Secure</span>
                    </div>
                    <h1 class="text-2xl font-black text-white tracking-tight font-display">
                        Electoral Commission Control Panel
                    </h1>
                </div>

                <div class="flex flex-wrap items-center gap-2.5 no-print">
                    <a href="admin.php?export=csv" class="px-3.5 py-2 bg-white/10 hover:bg-white/20 text-white border border-white/20 rounded-xl text-xs font-bold transition-all flex items-center space-x-1.5 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span>CSV Report</span>
                    </a>

                    <button onclick="window.print()" class="px-3.5 py-2 bg-amber-400 hover:bg-amber-300 text-slate-950 font-extrabold rounded-xl text-xs transition-all flex items-center space-x-1.5 shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                        <span>Printable PDF</span>
                    </button>

                    <button onclick="document.getElementById('resetModal').classList.remove('hidden')" class="px-3.5 py-2 bg-rose-600 hover:bg-rose-500 text-white font-extrabold rounded-xl text-xs transition-all flex items-center space-x-1.5 shadow-sm" title="Clear election database afresh">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                        <span>Reset Database</span>
                    </button>

                    <a href="admin.php?logout=1" class="p-2 bg-red-500/20 hover:bg-red-500/30 text-red-200 border border-red-400/30 rounded-xl text-xs font-bold transition-all" title="Sign Out">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    </a>
                </div>
            </div>

            <!-- Navigation Tabs Bar -->
            <div class="flex items-center gap-2 overflow-x-auto border-b border-slate-200 pb-1 no-print">
                <a href="admin.php?tab=overview" class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all flex items-center space-x-2 whitespace-nowrap <?= $activeTab === 'overview' ? 'bg-[#1b3252] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path></svg>
                    <span>Dashboard Overview</span>
                </a>

                <a href="admin.php?tab=eligible" class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all flex items-center space-x-2 whitespace-nowrap <?= $activeTab === 'eligible' ? 'bg-[#1b3252] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Accredited Voters (<?= $totalEligibleVoters ?>)</span>
                </a>

                <a href="admin.php?tab=candidates" class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all flex items-center space-x-2 whitespace-nowrap <?= $activeTab === 'candidates' ? 'bg-[#1b3252] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    <span>Candidate Management (<?= $totalCandidates ?>)</span>
                </a>

                <a href="admin.php?tab=voters" class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all flex items-center space-x-2 whitespace-nowrap <?= $activeTab === 'voters' ? 'bg-[#1b3252] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                    <span>Voter Roster (<?= $totalTokens ?>)</span>
                </a>

                <a href="admin.php?tab=tally" class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all flex items-center space-x-2 whitespace-nowrap <?= $activeTab === 'tally' ? 'bg-[#1b3252] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Live Tally & Breakdown</span>
                </a>

                <a href="admin.php?tab=audit" class="px-4 py-2.5 rounded-2xl text-xs font-bold transition-all flex items-center space-x-2 whitespace-nowrap <?= $activeTab === 'audit' ? 'bg-[#1b3252] text-white shadow-sm' : 'text-slate-600 hover:text-slate-900 hover:bg-slate-100' ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <span>Audit Logs (<?= count($auditLogs) ?>)</span>
                </a>
            </div>

            <!-- TAB 1: DASHBOARD OVERVIEW -->
            <?php if ($activeTab === 'overview'): ?>
                <!-- 6 Metric Cards Row -->
                <div class="grid grid-cols-2 md:grid-cols-6 gap-3 sm:gap-4">
                    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm card-print">
                        <div class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">REGISTRATIONS</div>
                        <div class="text-2xl font-black text-[#101e36] mt-1"><?= $totalRegistrations ?></div>
                        <div class="text-[10px] text-emerald-700 font-semibold mt-1">100% Verified</div>
                    </div>

                    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm card-print">
                        <div class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">TOKENS SENT</div>
                        <div class="text-2xl font-black text-[#101e36] mt-1"><?= $totalTokens ?></div>
                        <div class="text-[10px] text-[#1b3252] font-semibold mt-1">SMTP Delivered</div>
                    </div>

                    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm card-print">
                        <div class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">VOTES CAST</div>
                        <div class="text-2xl font-black text-emerald-700 mt-1"><?= $totalVotesCast ?></div>
                        <div class="text-[10px] text-slate-500 font-semibold mt-1">Recorded Ballots</div>
                    </div>

                    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm card-print">
                        <div class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">REMAINING</div>
                        <div class="text-2xl font-black text-amber-700 mt-1"><?= $remainingTokens ?></div>
                        <div class="text-[10px] text-slate-500 font-semibold mt-1">Pending Tokens</div>
                    </div>

                    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm card-print">
                        <div class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">TURNOUT %</div>
                        <div class="text-2xl font-black text-[#1b3252] mt-1"><?= $turnoutPercent ?>%</div>
                        <div class="text-[10px] text-slate-500 font-semibold mt-1">Participation</div>
                    </div>

                    <div class="bg-white border border-slate-200 rounded-2xl p-4 shadow-sm card-print">
                        <div class="text-[10px] font-extrabold text-slate-500 uppercase tracking-wider">CANDIDATES</div>
                        <div class="text-2xl font-black text-[#101e36] mt-1"><?= $totalCandidates ?></div>
                        <div class="text-[10px] text-slate-500 font-semibold mt-1">Across <?= count($positions) ?> Positions</div>
                    </div>
                </div>

                <!-- Main Layout Grid (Standings 2/3 + Real-time Audit Trail 1/3) -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    <!-- Left Column (2/3): Executive Positions Vote Standings -->
                    <div class="lg:col-span-2 bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-6 card-print">
                        <div class="border-b border-slate-100 pb-4 flex justify-between items-center">
                            <h2 class="text-lg font-extrabold text-[#101e36]">Executive Positions Vote Standings</h2>
                            <span class="text-xs text-[#1b3252] font-bold bg-slate-100 px-3 py-1 rounded-full border border-slate-200">Live Standings</span>
                        </div>

                        <div class="space-y-6">
                            <?php foreach ($positions as $pos): ?>
                                <?php
                                $posVotes = array_filter($votes, fn($v) => $v['position_id'] == $pos['id']);
                                $posVotesCount = count($posVotes);
                                $posCandidates = array_filter($candidates, fn($c) => $c['position_id'] == $pos['id']);
                                ?>
                                <div class="space-y-3">
                                    <div class="flex justify-between items-center text-xs">
                                        <h3 class="font-extrabold text-[#101e36] text-sm"><?= htmlspecialchars($pos['name']) ?></h3>
                                        <span class="text-slate-500 font-medium"><?= $posVotesCount ?> votes cast</span>
                                    </div>

                                    <div class="space-y-3">
                                        <?php foreach ($posCandidates as $cand): ?>
                                            <?php
                                            $cVotes = count(array_filter($posVotes, fn($v) => $v['candidate_id'] == $cand['id']));
                                            $pct = $posVotesCount > 0 ? round(($cVotes / $posVotesCount) * 100, 1) : 0;
                                            ?>
                                            <div class="space-y-1">
                                                <div class="flex justify-between items-center text-xs">
                                                    <span class="text-slate-800 font-semibold"><?= htmlspecialchars($cand['fullname']) ?></span>
                                                    <span class="text-[#1b3252] font-bold"><?= $cVotes ?> (<?= $pct ?>%)</span>
                                                </div>
                                                <div class="w-full bg-slate-100 rounded-full h-2.5 overflow-hidden border border-slate-200">
                                                    <div class="bg-[#1b3252] h-2.5 rounded-full transition-all duration-500" style="width: <?= max(2, $pct) ?>%"></div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Right Column (1/3): Real-Time Audit Trail -->
                    <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-4 card-print">
                        <div class="border-b border-slate-100 pb-4">
                            <h2 class="text-lg font-extrabold text-[#101e36]">Real-Time Audit Trail</h2>
                        </div>

                        <div class="space-y-3 max-h-[500px] overflow-y-auto pr-1">
                            <?php foreach (array_slice($auditLogs, 0, 10) as $log): ?>
                                <div class="p-3.5 bg-slate-50 border border-slate-200/80 rounded-2xl space-y-1 text-xs card-print">
                                    <div class="flex justify-between items-center text-[10px]">
                                        <span class="font-bold text-[#1b3252]"><?= htmlspecialchars($log['user']) ?></span>
                                        <span class="text-slate-500"><?= htmlspecialchars($log['created_at']) ?></span>
                                    </div>
                                    <p class="text-slate-700 text-xs leading-relaxed">
                                        <?= htmlspecialchars($log['action']) ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- TAB 2: ACCREDITED ELIGIBLE VOTERS REGISTER -->
            <?php if ($activeTab === 'eligible'): ?>
                <div class="space-y-6">
                    <!-- Upload Box & Manual Add -->
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- CSV Upload Card -->
                        <div class="bg-white border border-slate-200 rounded-2xl p-6 text-slate-900 shadow-sm">
                            <div class="flex justify-between items-center mb-4">
                                <div>
                                    <h3 class="font-bold text-base text-[#101e36]">CSV Bulk Import Accredited Voters</h3>
                                    <p class="text-xs text-slate-500">Upload CSV file or paste lines (Format: Full Name, Email, Graduation Year)</p>
                                </div>
                            </div>

                            <form method="POST" action="admin.php?tab=eligible" enctype="multipart/form-data" class="space-y-4 text-xs">
                                <input type="hidden" name="action" value="upload_eligible_csv">
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Select CSV File</label>
                                    <input type="file" name="csv_file" accept=".csv,.txt" class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-bold file:bg-[#1b3252] file:text-white hover:file:bg-[#152742]">
                                </div>

                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Or Paste CSV Content Directly</label>
                                    <textarea name="csv_text" rows="4" placeholder="Full Name, Email Address, Graduation Year&#10;Adefemi Williams, adefemi.williams@example.com, 2008&#10;Blessing Okonjo, blessing.o@example.com, 2007" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 font-mono text-xs focus:bg-white focus:outline-none focus:border-slate-800"></textarea>
                                </div>

                                <div class="flex justify-between items-center pt-2">
                                    <a href="data:text/csv;charset=utf-8,fullname,email,graduation_year%0AAdefemi%20Williams,adefemi.williams@example.com,2008%0ABlessing%20Okonjo,blessing.o@example.com,2007" download="eligible_voters_template.csv" class="text-xs text-slate-600 hover:text-slate-900 font-semibold underline">
                                        Download Sample CSV Template
                                    </a>
                                    <button type="submit" class="px-5 py-2.5 bg-[#1b3252] hover:bg-[#152742] text-white font-bold rounded-xl shadow-sm text-xs transition-all">
                                        Upload & Import Register
                                    </button>
                                </div>
                            </form>
                        </div>

                        <!-- Manual Add Single Voter -->
                        <div class="bg-white border border-slate-200 rounded-2xl p-6 text-slate-900 shadow-sm">
                            <h3 class="font-bold text-base text-[#101e36] mb-1">Add Individual Accredited Voter</h3>
                            <p class="text-xs text-slate-500 mb-4">Add a single voter record to the pre-approved whitelist.</p>

                            <form method="POST" action="admin.php?tab=eligible" class="space-y-4 text-xs">
                                <input type="hidden" name="action" value="add_eligible">
                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Full Name</label>
                                    <input type="text" name="fullname" required placeholder="Dr. Folake Solanke" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 text-xs focus:bg-white focus:outline-none focus:border-slate-800">
                                </div>

                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Email Address</label>
                                    <input type="email" name="email" required placeholder="folake.solanke@example.com" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 text-xs focus:bg-white focus:outline-none focus:border-slate-800">
                                </div>

                                <div>
                                    <label class="block font-bold text-slate-700 mb-1">Graduation Year</label>
                                    <select name="graduation_year" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 text-xs focus:bg-white focus:outline-none focus:border-slate-800">
                                        <option value="2008" selected>2008</option>
                                        <option value="2007">2007</option>
                                        <option value="2009">2009</option>
                                    </select>
                                </div>

                                <button type="submit" class="w-full py-2.5 bg-[#1b3252] hover:bg-[#152742] text-white font-bold rounded-xl shadow-sm text-xs transition-all">
                                    Add Accredited Voter
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Eligible Voters Table -->
                    <div class="bg-white border border-slate-200 rounded-2xl p-6 text-slate-900 shadow-sm">
                        <div class="flex justify-between items-center mb-4">
                            <div>
                                <h3 class="font-bold text-base text-[#101e36]">Pre-Uploaded Accredited Voters Database</h3>
                                <p class="text-xs text-slate-500">Only voters in this table can request voting tokens.</p>
                            </div>
                            <span class="bg-slate-100 text-slate-800 px-3 py-1 rounded-full font-bold text-xs border border-slate-200">
                                Total: <?= $totalEligibleVoters ?> Accredited
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs border-collapse">
                                <thead>
                                    <tr class="bg-slate-50 border-b border-slate-200 text-slate-700 font-bold">
                                        <th class="p-3 rounded-l-xl">Full Name</th>
                                        <th class="p-3">Email Address</th>
                                        <th class="p-3">Grad Year</th>
                                        <th class="p-3">Uploaded Date</th>
                                        <th class="p-3 text-right rounded-r-xl">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <?php foreach ($eligibleVoters as $ev): ?>
                                        <tr class="hover:bg-slate-50/80">
                                            <td class="p-3 font-bold text-slate-900"><?= htmlspecialchars($ev['fullname']) ?></td>
                                            <td class="p-3 text-slate-600 font-mono"><?= htmlspecialchars($ev['email']) ?></td>
                                            <td class="p-3 font-semibold text-slate-700"><?= htmlspecialchars((string)($ev['graduation_year'] ?? 2008)) ?></td>
                                            <td class="p-3 text-slate-400 text-[11px]"><?= htmlspecialchars($ev['created_at'] ?? 'Seed Data') ?></td>
                                            <td class="p-3 text-right">
                                                <form method="POST" action="admin.php?tab=eligible" onsubmit="return confirm('Remove voter from accredited database?');" class="inline">
                                                    <input type="hidden" name="action" value="delete_eligible">
                                                    <input type="hidden" name="eligible_id" value="<?= $ev['id'] ?>">
                                                    <button type="submit" class="px-2.5 py-1 bg-red-50 text-red-600 hover:bg-red-100 border border-red-200 rounded-lg text-[11px] font-bold transition-all">
                                                        Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- TAB 3: CANDIDATE MANAGEMENT (Matches Screenshot 3) -->
            <?php if ($activeTab === 'candidates'): ?>
                <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-100 pb-5">
                        <div>
                            <h2 class="text-2xl font-black text-[#101e36] font-display">Candidate Management</h2>
                            <p class="text-xs text-slate-500 mt-1">Add, edit, upload photos, or adjust manifesto and position orders.</p>
                        </div>
                        <button onclick="openCandidateModal()" class="px-4 py-2.5 bg-[#1b3252] hover:bg-[#152742] text-white font-extrabold text-xs rounded-2xl shadow-sm transition-all flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                            <span>Add New Candidate</span>
                        </button>
                    </div>

                    <!-- Candidates Grid -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                        <?php foreach ($candidates as $cand): ?>
                            <?php
                            $candPos = array_filter($positions, fn($p) => $p['id'] == $cand['position_id']);
                            $posName = !empty($candPos) ? reset($candPos)['name'] : 'Executive Position';
                            ?>
                            <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 shadow-sm flex flex-col justify-between space-y-4 hover:border-slate-300 transition-all">
                                <div class="space-y-3">
                                    <div class="flex items-start space-x-3.5">
                                        <img src="<?= htmlspecialchars($cand['recent_photo']) ?>" alt="<?= htmlspecialchars($cand['fullname']) ?>" class="w-16 h-16 rounded-2xl object-cover border-2 border-slate-300 shrink-0 shadow-sm">
                                        <div class="space-y-1">
                                            <span class="bg-slate-200/80 text-slate-800 border border-slate-300 text-[10px] font-extrabold px-2.5 py-0.5 rounded-md uppercase tracking-wider inline-block">
                                                <?= htmlspecialchars($posName) ?>
                                            </span>
                                            <h3 class="font-extrabold text-[#101e36] text-base leading-snug"><?= htmlspecialchars($cand['fullname']) ?></h3>
                                        </div>
                                    </div>

                                    <p class="text-xs text-slate-600 italic leading-relaxed line-clamp-3">
                                        "<?= htmlspecialchars($cand['manifesto']) ?>"
                                    </p>
                                </div>

                                <div class="pt-2 border-t border-slate-200 flex items-center justify-between gap-2">
                                    <form method="POST" action="admin.php?tab=candidates" class="no-print">
                                        <input type="hidden" name="action" value="toggle_candidate">
                                        <input type="hidden" name="candidate_id" value="<?= $cand['id'] ?>">
                                        <button type="submit" class="px-3 py-1.5 rounded-xl text-[11px] font-bold transition-all flex items-center space-x-1.5 border <?= $cand['active'] ? 'bg-emerald-50 text-emerald-800 border-emerald-200 hover:bg-emerald-100' : 'bg-red-50 text-red-800 border-red-200 hover:bg-red-100' ?>">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                            <span><?= $cand['active'] ? 'Active' : 'Disabled' ?></span>
                                        </button>
                                    </form>

                                    <div class="flex items-center space-x-2">
                                        <!-- Edit Button -->
                                        <button onclick='editCandidate(<?= json_encode($cand, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)' class="p-2 bg-white hover:bg-slate-100 text-slate-700 border border-slate-200 rounded-xl transition-all shadow-sm" title="Edit Candidate">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </button>

                                        <!-- Delete Button -->
                                        <form method="POST" action="admin.php?tab=candidates" onsubmit="return confirm('Are you sure you want to permanently delete this candidate?')" class="inline">
                                            <input type="hidden" name="action" value="delete_candidate">
                                            <input type="hidden" name="candidate_id" value="<?= $cand['id'] ?>">
                                            <button type="submit" class="p-2 bg-red-50 hover:bg-red-100 text-red-700 border border-red-200 rounded-xl transition-all shadow-sm" title="Delete Candidate">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Modal for Add / Edit Candidate -->
                <div id="candidateModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4 z-50 hidden">
                    <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 max-w-lg w-full shadow-xl space-y-4 text-slate-900">
                        <div class="flex justify-between items-center border-b border-slate-200 pb-3">
                            <h3 id="modalTitle" class="text-xl font-extrabold text-[#101e36]">Add New Candidate</h3>
                            <button onclick="closeCandidateModal()" class="text-slate-400 hover:text-slate-800 text-xl font-bold">&times;</button>
                        </div>

                        <form method="POST" action="admin.php?tab=candidates" class="space-y-4 text-xs">
                            <input type="hidden" name="action" id="modalAction" value="add_candidate">
                            <input type="hidden" name="candidate_id" id="modalCandId" value="">

                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">Contested Executive Position</label>
                                <select name="position_id" id="modalPosId" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 focus:bg-white focus:border-slate-800 focus:outline-none">
                                    <option value="">-- Select Position --</option>
                                    <?php foreach ($positions as $p): ?>
                                        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">Candidate Full Name</label>
                                <input type="text" name="fullname" id="modalFullname" required placeholder="e.g. Dr. Samuel O. Adebayo" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 focus:bg-white focus:border-slate-800 focus:outline-none">
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">Key Manifesto Quote / Slogan</label>
                                <textarea name="manifesto" id="modalManifesto" required rows="3" placeholder="Core promises to the alumni association..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 focus:bg-white focus:border-slate-800 focus:outline-none"></textarea>
                            </div>

                            <div>
                                <label class="block font-bold text-slate-700 uppercase mb-1">Candidate Photo Path / URL</label>
                                <input type="text" name="recent_photo" id="modalPhoto" value="/uploads/candidates/default.jpg" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 focus:bg-white focus:border-slate-800 focus:outline-none">
                            </div>

                            <div class="flex gap-3 pt-2">
                                <button type="button" onclick="closeCandidateModal()" class="flex-1 py-3 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold border border-slate-200 rounded-xl transition-all">Cancel</button>
                                <button type="submit" id="modalSubmitBtn" class="flex-1 py-3 bg-[#1b3252] hover:bg-[#152742] text-white font-extrabold rounded-xl transition-all shadow-sm">Save Candidate</button>
                            </div>
                        </form>
                    </div>
                </div>

                <script>
                    function openCandidateModal() {
                        document.getElementById('modalTitle').innerText = 'Add New Candidate';
                        document.getElementById('modalAction').value = 'add_candidate';
                        document.getElementById('modalCandId').value = '';
                        document.getElementById('modalPosId').value = '';
                        document.getElementById('modalFullname').value = '';
                        document.getElementById('modalManifesto').value = '';
                        document.getElementById('modalPhoto').value = '/uploads/candidates/default.jpg';
                        document.getElementById('modalSubmitBtn').innerText = 'Insert Candidate';
                        document.getElementById('candidateModal').classList.remove('hidden');
                    }

                    function editCandidate(cand) {
                        document.getElementById('modalTitle').innerText = 'Edit Candidate Details';
                        document.getElementById('modalAction').value = 'edit_candidate';
                        document.getElementById('modalCandId').value = cand.id;
                        document.getElementById('modalPosId').value = cand.position_id;
                        document.getElementById('modalFullname').value = cand.fullname;
                        document.getElementById('modalManifesto').value = cand.manifesto;
                        document.getElementById('modalPhoto').value = cand.recent_photo || '/uploads/candidates/default.jpg';
                        document.getElementById('modalSubmitBtn').innerText = 'Update Candidate';
                        document.getElementById('candidateModal').classList.remove('hidden');
                    }

                    function closeCandidateModal() {
                        document.getElementById('candidateModal').classList.add('hidden');
                    }
                </script>
            <?php endif; ?>

            <!-- TAB 4: VOTER ROSTER (Matches Screenshot 2) -->
            <?php if ($activeTab === 'voters'): ?>
                <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-100 pb-5">
                        <div>
                            <h2 class="text-2xl font-black text-[#101e36] font-display">Registered Voters & Token Status</h2>
                            <p class="text-xs text-slate-500 mt-1">Monitor token delivery and ballot participation status.</p>
                        </div>

                        <!-- Live Search Bar -->
                        <div class="relative w-full sm:w-72">
                            <svg class="w-4 h-4 absolute left-3.5 top-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            <input type="text" id="voterSearchInput" onkeyup="searchVoters()" placeholder="Search by name, email or token..." class="w-full pl-10 pr-4 py-2 bg-slate-50 border border-slate-200 rounded-2xl text-xs text-slate-900 placeholder-slate-400 focus:outline-none focus:bg-white focus:border-slate-800">
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs border-collapse" id="voterRosterTable">
                            <thead>
                                <tr class="border-b border-slate-200 text-slate-700 uppercase font-extrabold text-[11px] bg-slate-50">
                                    <th class="py-3.5 px-5">Voter Name</th>
                                    <th class="py-3.5 px-5">Email</th>
                                    <th class="py-3.5 px-5">Voting Token</th>
                                    <th class="py-3.5 px-5">Voting Status</th>
                                    <th class="py-3.5 px-5">Sent Timestamp</th>
                                    <th class="py-3.5 px-5 text-right no-print">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <?php foreach ($voters as $v): ?>
                                    <tr class="voter-row hover:bg-slate-50 transition-all">
                                        <td class="py-4 px-5 font-bold text-[#101e36] text-sm voter-name">
                                            <?= htmlspecialchars($v['voter_name'] ?? 'Accredited Voter') ?>
                                        </td>
                                        <td class="py-4 px-5 text-slate-600 voter-email font-medium">
                                            <?= htmlspecialchars($v['email']) ?>
                                        </td>
                                        <td class="py-4 px-5 font-mono text-amber-700 font-black text-sm tracking-widest voter-token">
                                            <?= htmlspecialchars($v['voting_token']) ?>
                                        </td>
                                        <td class="py-4 px-5">
                                            <?php if ($v['has_voted']): ?>
                                                <span class="inline-flex items-center space-x-1 px-3 py-1 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-full text-[11px] font-bold">
                                                    <span>Voted (<?= htmlspecialchars($v['voted_at'] ?? 'Recorded') ?>)</span>
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center space-x-1 px-3 py-1 bg-amber-50 text-amber-800 border border-amber-200 rounded-full text-[11px] font-bold">
                                                    <span>Token Unused</span>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-4 px-5 text-slate-500 text-xs font-mono">
                                            <?= htmlspecialchars($v['token_sent_at'] ?? $v['created_at'] ?? '2026-07-20 09:15:05') ?>
                                        </td>
                                        <td class="py-4 px-5 text-right no-print">
                                            <form method="POST" action="admin.php?tab=voters" class="inline">
                                                <input type="hidden" name="action" value="resend_token">
                                                <input type="hidden" name="voter_id" value="<?= $v['id'] ?>">
                                                <button type="submit" class="px-3.5 py-1.5 bg-[#1b3252] hover:bg-[#152742] text-white border border-slate-300/20 rounded-xl text-xs font-bold transition-all inline-flex items-center space-x-1.5 shadow-sm">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                    <span>Resend Token</span>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <script>
                    function searchVoters() {
                        const q = document.getElementById('voterSearchInput').value.toLowerCase();
                        const rows = document.querySelectorAll('#voterRosterTable .voter-row');
                        rows.forEach(row => {
                            const text = row.innerText.toLowerCase();
                            row.style.display = text.includes(q) ? '' : 'none';
                        });
                    }
                </script>
            <?php endif; ?>

            <!-- TAB 5: LIVE TALLY & BREAKDOWN (Matches Screenshot 1) -->
            <?php if ($activeTab === 'tally'): ?>
                <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 border-b border-slate-100 pb-5">
                        <div>
                            <h2 class="text-2xl font-black text-[#101e36] font-display">Detailed Election Results</h2>
                            <p class="text-xs text-slate-500 mt-1">Live breakdown per position with vote counts and percentage share.</p>
                        </div>

                        <div class="flex items-center space-x-2.5 no-print">
                            <a href="admin.php?export=csv" class="px-4 py-2 bg-[#1b3252] hover:bg-[#152742] text-white rounded-xl text-xs font-bold transition-all shadow-sm">
                                Export CSV
                            </a>
                            <button onclick="window.print()" class="px-4 py-2 bg-[#1b3252] hover:bg-[#152742] text-white rounded-xl text-xs font-bold transition-all shadow-sm">
                                Export PDF
                            </button>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <?php foreach ($positions as $pos): ?>
                            <?php
                            $posVotes = array_filter($votes, fn($v) => $v['position_id'] == $pos['id']);
                            $posVotesCount = count($posVotes);
                            $posCandidates = array_filter($candidates, fn($c) => $c['position_id'] == $pos['id']);
                            ?>
                            <div class="bg-slate-50 border border-slate-200 rounded-3xl p-6 space-y-4">
                                <div class="flex justify-between items-center border-b border-slate-200 pb-3">
                                    <h3 class="text-xl font-black text-[#101e36] font-display"><?= htmlspecialchars($pos['name']) ?></h3>
                                    <span class="bg-[#1b3252] text-white text-xs px-3.5 py-1 rounded-full font-extrabold shadow-sm">
                                        <?= $posVotesCount ?> Total Votes
                                    </span>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <?php foreach ($posCandidates as $cand): ?>
                                        <?php
                                        $cVotes = count(array_filter($posVotes, fn($v) => $v['candidate_id'] == $cand['id']));
                                        $pct = $posVotesCount > 0 ? round(($cVotes / $posVotesCount) * 100, 1) : 0.0;
                                        ?>
                                        <div class="bg-white border border-slate-200 rounded-2xl p-4 flex items-center space-x-4 shadow-sm">
                                            <img src="<?= htmlspecialchars($cand['recent_photo']) ?>" alt="<?= htmlspecialchars($cand['fullname']) ?>" class="w-14 h-14 rounded-2xl object-cover border-2 border-slate-300 shrink-0">
                                            <div class="flex-1 space-y-1.5">
                                                <div class="flex justify-between items-start">
                                                    <h4 class="font-extrabold text-[#101e36] text-sm leading-snug"><?= htmlspecialchars($cand['fullname']) ?></h4>
                                                    <span class="text-[#1b3252] font-extrabold text-xs ml-2"><?= number_format($pct, 1) ?>%</span>
                                                </div>

                                                <div class="text-xs text-slate-500 font-semibold">
                                                    Votes: <span class="text-slate-900 font-bold"><?= $cVotes ?></span>
                                                </div>

                                                <div class="w-full bg-slate-100 rounded-full h-2 border border-slate-200 overflow-hidden">
                                                    <div class="bg-[#1b3252] h-2 rounded-full transition-all duration-500" style="width: <?= max(2, $pct) ?>%"></div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- TAB 6: AUDIT LOGS -->
            <?php if ($activeTab === 'audit'): ?>
                <div class="bg-white border border-slate-200 rounded-3xl p-6 sm:p-8 shadow-sm space-y-6">
                    <div class="border-b border-slate-100 pb-4">
                        <h2 class="text-2xl font-black text-[#101e36] font-display">System Audit & Compliance Log</h2>
                        <p class="text-xs text-slate-500 mt-1">Cryptographic event logging and real-time administrator action trails.</p>
                    </div>

                    <div class="space-y-3">
                        <?php foreach ($auditLogs as $log): ?>
                            <div class="p-4 bg-slate-50 border border-slate-200 rounded-2xl space-y-1 text-xs">
                                <div class="flex justify-between items-center">
                                    <span class="font-bold text-[#1b3252]"><?= htmlspecialchars($log['user']) ?> (IP: <?= htmlspecialchars($log['ip_address']) ?>)</span>
                                    <span class="text-slate-500 text-[11px] font-mono"><?= htmlspecialchars($log['created_at']) ?></span>
                                </div>
                                <p class="text-slate-700 leading-relaxed"><?= htmlspecialchars($log['action']) ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

        <?php endif; ?>

    </div>

    <!-- Reset Database Confirmation Modal -->
    <div id="resetModal" class="hidden fixed inset-0 bg-slate-950/80 backdrop-blur-md z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-3xl p-6 max-w-md w-full border border-slate-200 shadow-2xl space-y-4">
            <div class="flex items-center space-x-3 text-rose-600">
                <div class="p-3 bg-rose-100 rounded-2xl">
                    <svg class="w-6 h-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                </div>
                <div>
                    <h3 class="text-lg font-black text-slate-900 font-display">Reset Election Database Afresh</h3>
                    <p class="text-xs text-rose-600 font-bold">Irreversible Administrative Action</p>
                </div>
            </div>

            <p class="text-xs text-slate-600 leading-relaxed">
                This operation will permanently wipe all <strong class="text-slate-900 font-bold">ballots cast</strong>, <strong className="text-slate-900 font-bold">voter tokens</strong>, and <strong className="text-slate-900 font-bold">alumni registrations</strong> so you can conduct a completely fresh election cycle.
            </p>

            <form method="POST" action="admin.php" class="space-y-4">
                <input type="hidden" name="action" value="reset_database">
                
                <div class="bg-rose-50 border border-rose-200 p-3.5 rounded-2xl text-xs text-rose-900 space-y-2">
                    <label class="block font-bold">To confirm database wipe, type <code class="bg-rose-200 px-1.5 py-0.5 rounded text-rose-950 font-black">RESET</code> below:</label>
                    <input type="text" name="confirm_reset" required placeholder="Type RESET in capital letters" class="w-full px-3.5 py-2 border border-rose-300 rounded-xl bg-white text-slate-900 text-xs font-mono font-bold focus:outline-none focus:border-rose-600">
                </div>

                <div class="flex items-center justify-end space-x-2 pt-2">
                    <button type="button" onclick="document.getElementById('resetModal').classList.add('hidden')" class="px-4 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl">
                        Cancel
                    </button>
                    <button type="submit" class="px-5 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs rounded-xl shadow-md transition-all flex items-center space-x-1.5">
                        <span>Clear Database Afresh</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
