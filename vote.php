<?php
/**
 * Ballot Paper Voting Page & Processing
 */
require_once __DIR__ . '/config.php';

$error = '';
$success = false;
$voter = null;
$positions = [];
$candidates = [];

try {
    $pdo = getDB();

    // Fetch active positions and candidates
    $posStmt = $pdo->query("SELECT * FROM positions WHERE active = 1 ORDER BY display_order ASC");
    $positions = $posStmt->fetchAll();

    $candStmt = $pdo->query("SELECT * FROM candidates WHERE active = 1 ORDER BY display_order ASC");
    $candidates = $candStmt->fetchAll();
} catch (Exception $e) {
    $error = "Database Error: " . $e->getMessage();
}

$inputEmail = sanitize($_GET['email'] ?? $_POST['email'] ?? '');
$inputToken = strtoupper(sanitize($_GET['token'] ?? $_POST['token'] ?? ''));

// If token provided via URL or POST verification
if ($_SERVER['REQUEST_METHOD'] === 'POST' || ($inputEmail && $inputToken)) {
    $action = $_POST['action'] ?? ($inputToken ? 'verify_token' : '');

    if ($action === 'verify_token') {
        $token = strtoupper(sanitize($_POST['token'] ?? $inputToken));
        $email = strtolower(sanitize($_POST['email'] ?? $inputEmail));

        try {
            $stmt = $pdo->prepare("SELECT * FROM voters WHERE UPPER(voting_token) = ? AND LOWER(email) = ?");
            $stmt->execute([$token, $email]);
            $voter = $stmt->fetch();

            if (!$voter) {
                // Fallback check by token alone if email matching is lenient
                $stmtToken = $pdo->prepare("SELECT * FROM voters WHERE UPPER(voting_token) = ?");
                $stmtToken->execute([$token]);
                $voter = $stmtToken->fetch();
            }

            if (!$voter) {
                $error = "Invalid email address or voting token. Please verify your token from registration.";
            } elseif ($voter['has_voted']) {
                $error = "This voting token has already been used on " . $voter['voted_at'] . ". Double voting is strictly prohibited.";
                $voter = null;
            }
        } catch (Exception $e) {
            $error = "Verification failed: " . $e->getMessage();
        }
    } elseif ($action === 'cast_vote') {
        $token = strtoupper(sanitize($_POST['token'] ?? ''));
        $voterId = (int)($_POST['voter_id'] ?? 0);

        try {
            $stmt = $pdo->prepare("SELECT * FROM voters WHERE id = ? AND UPPER(voting_token) = ?");
            $stmt->execute([$voterId, $token]);
            $voter = $stmt->fetch();

            if (!$voter || $voter['has_voted']) {
                $error = "Token invalid or already used.";
            } else {
                $pdo->beginTransaction();

                // Record vote for each position
                foreach ($positions as $pos) {
                    $candKey = "position_" . $pos['id'];
                    $candidateId = (int)($_POST[$candKey] ?? 0);
                    if ($candidateId > 0) {
                        $voteStmt = $pdo->prepare("INSERT INTO votes (voter_id, candidate_id, position_id) VALUES (?, ?, ?)");
                        $voteStmt->execute([$voter['id'], $candidateId, $pos['id']]);
                    }
                }

                // Mark voter as voted
                $updateVoter = $pdo->prepare("UPDATE voters SET has_voted = 1, voted_at = NOW() WHERE id = ?");
                $updateVoter->execute([$voter['id']]);

                // Log audit
                logAudit($pdo, $voter['email'], "Cast official electronic ballot across all contested seats");

                $pdo->commit();
                $success = true;
            }
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = "Ballot submission failed: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BUI Beta Set Alumni Executive Election - Cast Ballot</title>
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

    <!-- Main Container -->
    <main class="flex-1 max-w-2xl w-full mx-auto px-4 sm:px-6 py-10 sm:py-16">

        <?php if ($success): ?>
            <!-- Success Confirmation State -->
            <div class="bg-white border border-slate-200/90 rounded-2xl p-8 sm:p-10 shadow-sm text-center space-y-4">
                <div class="w-14 h-14 bg-emerald-100 text-emerald-700 rounded-full flex items-center justify-center mx-auto text-2xl font-bold">
                    ✓
                </div>
                <h2 class="text-2xl font-bold text-[#101e36]">Ballot Cast Successfully!</h2>
                <p class="text-xs text-slate-600 max-w-md mx-auto leading-relaxed">
                    Your ballot has been securely recorded and audited. Thank you for participating in the Bowen University Beta Set Executive Election.
                </p>
                <div class="pt-4 flex justify-center gap-3">
                    <a href="results.php" class="px-5 py-2.5 bg-[#1b3252] hover:bg-[#152742] text-white font-bold text-xs rounded-lg transition-all shadow-sm">
                        View Live Tally & Results &rarr;
                    </a>
                    <a href="index.php" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-lg transition-all">
                        Return to Home
                    </a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$success && !$voter): ?>
            <!-- Token & Email Entry Card (Matches Screenshot 1) -->
            <div class="bg-white border border-slate-200/90 rounded-2xl p-8 sm:p-10 shadow-sm max-w-lg mx-auto">
                <h1 class="text-xl sm:text-2xl font-bold text-[#101e36] tracking-tight mb-2">
                    BUI Beta Set Alumni Executive Election
                </h1>
                <p class="text-xs text-slate-500 leading-relaxed mb-6">
                    Enter your registered email and the 8-character token you received to access the ballot.
                </p>

                <?php if ($error): ?>
                    <div class="mb-5 p-3.5 bg-red-50 border border-red-200 rounded-xl text-red-700 font-semibold text-xs text-center">
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="vote.php" class="space-y-4">
                    <input type="hidden" name="action" value="verify_token">

                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1.5">Email address</label>
                        <input type="email" name="email" required placeholder="you@example.com" value="<?= htmlspecialchars($inputEmail) ?>" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-300 text-slate-900 text-xs placeholder-slate-400 focus:border-slate-800 focus:outline-none transition-all">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-800 mb-1.5">Voting token</label>
                        <input type="text" name="token" required placeholder="A 7 B 3 K 9 M 2" value="<?= htmlspecialchars($inputToken) ?>" class="w-full px-3.5 py-3 rounded-lg border border-slate-200 bg-slate-50 text-center font-mono text-slate-700 tracking-[0.2em] text-sm uppercase placeholder-slate-300 focus:border-slate-800 focus:bg-white focus:outline-none transition-all">
                    </div>

                    <button type="submit" class="w-full py-3 bg-[#1b3252] hover:bg-[#152742] text-white font-bold rounded-lg text-xs transition-all shadow-sm mt-2">
                        Continue to ballot
                    </button>
                </form>

                <div class="mt-6 pt-4 border-t border-slate-100 text-center text-xs text-slate-500">
                    Don't have a token? <a href="register.php" class="text-[#1b3252] font-bold hover:underline">Register here &rarr;</a>
                </div>
            </div>
        <?php endif; ?>

        <?php if (!$success && $voter): ?>
            <!-- Unlocked Ballot Paper -->
            <form method="POST" action="vote.php" class="space-y-6">
                <input type="hidden" name="action" value="cast_vote">
                <input type="hidden" name="voter_id" value="<?= $voter['id'] ?>">
                <input type="hidden" name="token" value="<?= htmlspecialchars($voter['voting_token']) ?>">

                <!-- Accredited Voter Info Banner -->
                <div class="bg-white border border-slate-200/90 rounded-2xl p-4 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 text-xs shadow-sm">
                    <div>
                        <span class="text-slate-500 font-medium">Verified Voter:</span> <strong class="text-slate-900"><?= htmlspecialchars($voter['email']) ?></strong>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="text-slate-500 font-medium">Token:</span>
                        <span class="font-mono bg-slate-100 text-[#1b3252] px-2.5 py-0.5 rounded font-bold uppercase tracking-wider"><?= htmlspecialchars($voter['voting_token']) ?></span>
                    </div>
                </div>

                <div class="text-center py-2">
                    <h2 class="text-2xl font-bold text-[#101e36]">Official Ballot Paper</h2>
                    <p class="text-xs text-slate-500 mt-1">Select your preferred candidate for each executive seat below.</p>
                </div>

                <?php foreach ($positions as $pos): ?>
                    <?php $posCandidates = array_filter($candidates, fn($c) => $c['position_id'] == $pos['id']); ?>
                    <div class="bg-white border border-slate-200/90 rounded-2xl p-6 space-y-4 shadow-sm">
                        <div class="border-b border-slate-100 pb-3 flex justify-between items-center">
                            <h3 class="text-base font-bold text-[#101e36]"><?= htmlspecialchars($pos['name']) ?></h3>
                            <span class="text-[11px] font-semibold text-slate-400 bg-slate-100 px-2.5 py-0.5 rounded-full">Select 1 Candidate</span>
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <?php foreach ($posCandidates as $cand): ?>
                                <label class="block cursor-pointer group">
                                    <input type="radio" name="position_<?= $pos['id'] ?>" value="<?= $cand['id'] ?>" class="peer sr-only" required>
                                    <div class="border border-slate-200 rounded-xl p-4 bg-slate-50/50 peer-checked:border-[#1b3252] peer-checked:bg-sky-50/40 peer-checked:ring-2 peer-checked:ring-[#1b3252]/20 transition-all flex space-x-3.5 items-start">
                                        <img src="<?= htmlspecialchars($cand['recent_photo']) ?>" alt="<?= htmlspecialchars($cand['fullname']) ?>" class="w-14 h-14 rounded-lg object-cover border border-slate-200 shrink-0">
                                        <div>
                                            <h4 class="font-bold text-slate-900 text-xs sm:text-sm group-hover:text-sky-800 transition-colors"><?= htmlspecialchars($cand['fullname']) ?></h4>
                                            <p class="text-[11px] text-slate-500 mt-1 italic leading-relaxed line-clamp-3">"<?= htmlspecialchars($cand['manifesto']) ?>"</p>
                                        </div>
                                    </div>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="text-center pt-4">
                    <button type="submit" class="w-full sm:w-auto px-8 py-3.5 bg-[#1b3252] hover:bg-[#152742] text-white font-bold text-xs rounded-xl shadow-md transition-all">
                        Submit Official Ballot Paper
                    </button>
                </div>
            </form>
        <?php endif; ?>

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
