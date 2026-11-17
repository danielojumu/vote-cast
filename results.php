<?php
/**
 * Public Live Results & Turnout Dashboard
 */
require_once __DIR__ . '/config.php';

$totalRegistrations = 0;
$totalTokens = 0;
$totalVotesCast = 0;
$positions = [];
$candidates = [];
$votes = [];

try {
    $pdo = getDB();

    $totalRegistrations = (int)$pdo->query("SELECT COUNT(*) FROM registrations")->fetchColumn();
    $totalTokens = (int)$pdo->query("SELECT COUNT(*) FROM voters")->fetchColumn();
    $totalVotesCast = (int)$pdo->query("SELECT COUNT(*) FROM voters WHERE has_voted = 1")->fetchColumn();

    $positions = $pdo->query("SELECT * FROM positions WHERE active = 1 ORDER BY display_order ASC")->fetchAll();
    $candidates = $pdo->query("SELECT * FROM candidates WHERE active = 1 ORDER BY display_order ASC")->fetchAll();
    $votes = $pdo->query("SELECT * FROM votes")->fetchAll();
} catch (Exception $e) {
    // Graceful fallback
}

$turnoutPercent = $totalTokens > 0 ? round(($totalVotesCast / $totalTokens) * 100, 1) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Election Standings - Bowen University Beta Set</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
    </style>
</head>
<body class="bg-[#f5f8fc] text-slate-900 min-h-screen flex flex-col justify-between selection:bg-amber-100 selection:text-slate-900">

    <!-- Top Dark Navy Navigation Bar -->
    <header class="bg-[#1b3252] text-white py-3.5 px-6 sm:px-10 shadow-sm">
        <div class="max-w-6xl mx-auto flex items-center justify-between">
            <a href="index.php" class="font-bold text-sm tracking-tight text-white hover:text-slate-200 transition-colors">
                Bowen University Beta Set
            </a>
            <div class="flex items-center space-x-6 text-xs">
                <a href="admin.php" class="text-slate-200 hover:text-white font-medium transition-colors">
                    Admin login
                </a>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 max-w-4xl w-full mx-auto px-4 sm:px-6 py-10 space-y-8">
        
        <!-- Page Title Header -->
        <div class="text-center">
            <span class="bg-sky-100 text-sky-800 text-[11px] px-3 py-1 rounded-full font-bold uppercase tracking-wider">Public Live Dashboard</span>
            <h1 class="text-2xl sm:text-3xl font-extrabold text-[#101e36] tracking-tight mt-2">BUI Beta Set Live Election Standings</h1>
            <p class="text-xs text-slate-500 mt-1">Real-time audited voting statistics and official standings per position.</p>
        </div>

        <!-- 3 Key Metric Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <div class="bg-white border border-slate-200/90 rounded-2xl p-5 text-center shadow-sm">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Accredited Voters</div>
                <div class="text-2xl sm:text-3xl font-black text-[#101e36] mt-1"><?= $totalTokens ?></div>
            </div>
            <div class="bg-white border border-slate-200/90 rounded-2xl p-5 text-center shadow-sm">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Total Ballots Cast</div>
                <div class="text-2xl sm:text-3xl font-black text-emerald-600 mt-1"><?= $totalVotesCast ?></div>
            </div>
            <div class="bg-white border border-slate-200/90 rounded-2xl p-5 text-center shadow-sm">
                <div class="text-[11px] font-bold text-slate-400 uppercase tracking-wider">Participation Rate</div>
                <div class="text-2xl sm:text-3xl font-black text-amber-600 mt-1"><?= $turnoutPercent ?>%</div>
            </div>
        </div>

        <!-- Position Results List -->
        <div class="space-y-6">
            <?php foreach ($positions as $pos): ?>
                <?php
                $posVotes = array_filter($votes, fn($v) => $v['position_id'] == $pos['id']);
                $posVotesCount = count($posVotes);
                $posCandidates = array_filter($candidates, fn($c) => $c['position_id'] == $pos['id']);
                ?>
                <div class="bg-white border border-slate-200/90 rounded-2xl p-6 shadow-sm space-y-4">
                    <div class="flex justify-between items-center border-b border-slate-100 pb-3">
                        <h2 class="text-base font-bold text-[#101e36]"><?= htmlspecialchars($pos['name']) ?></h2>
                        <span class="bg-slate-100 text-slate-700 text-xs font-bold px-3 py-1 rounded-full">
                            <?= $posVotesCount ?> Votes Cast
                        </span>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <?php foreach ($posCandidates as $cand): ?>
                            <?php
                            $cVotes = count(array_filter($posVotes, fn($v) => $v['candidate_id'] == $cand['id']));
                            $pct = $posVotesCount > 0 ? round(($cVotes / $posVotesCount) * 100, 1) : 0;
                            ?>
                            <div class="border border-slate-200 rounded-xl p-4 bg-slate-50/50 flex space-x-3.5 items-center">
                                <img src="<?= htmlspecialchars($cand['recent_photo']) ?>" alt="<?= htmlspecialchars($cand['fullname']) ?>" class="w-14 h-14 rounded-lg object-cover border border-slate-200 shrink-0">
                                <div class="flex-1">
                                    <h3 class="font-bold text-slate-900 text-xs sm:text-sm"><?= htmlspecialchars($cand['fullname']) ?></h3>
                                    <div class="flex justify-between items-center text-xs text-slate-600 mt-2 mb-1 font-semibold">
                                        <span><?= $cVotes ?> votes</span>
                                        <span class="text-[#1b3252] font-extrabold"><?= $pct ?>%</span>
                                    </div>
                                    <div class="w-full bg-slate-200 rounded-full h-2.5 overflow-hidden">
                                        <div class="bg-[#1b3252] h-2.5 rounded-full transition-all duration-500" style="width: <?= max(2, $pct) ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="text-center pt-2">
            <a href="index.php" class="text-xs font-bold text-[#1b3252] hover:underline">&larr; Return to Main Portal</a>
        </div>

    </main>

    <!-- Footer Bar -->
    <footer class="border-t border-slate-200/80 bg-slate-50/50 py-4 px-6 sm:px-10 text-[11px] text-slate-500">
        <div class="max-w-6xl mx-auto flex flex-col sm:flex-row justify-between items-center gap-2">
            <div>
                &copy; 2026 Bowen University Beta Set
            </div>
            <div>
                Secure online voting
            </div>
        </div>
    </footer>

</body>
</html>
