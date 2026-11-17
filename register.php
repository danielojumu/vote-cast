<?php
/**
 * Voter Registration Handler & View
 */
require_once __DIR__ . '/config.php';

$message = '';
$error = '';
$generatedToken = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = sanitize($_POST['fullname'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $phone = sanitize($_POST['phone'] ?? '');
    $gradYear = (int)($_POST['graduation_year'] ?? 2008);
    $idType = sanitize($_POST['id_type'] ?? 'Last 4 digits of National ID');
    $idValue = sanitize($_POST['id_value'] ?? '');

    if (!$fullname || !$email) {
        $error = 'Please fill in both your Full Name and Email Address.';
    } else {
        try {
            $pdo = getDB();

            // 1. Check if email exists in pre-uploaded eligible_voters database
            $eligStmt = $pdo->prepare("SELECT * FROM eligible_voters WHERE LOWER(email) = ?");
            $eligStmt->execute([strtolower($email)]);
            $eligible = $eligStmt->fetch();

            if (!$eligible) {
                $error = "Validation Failed: The email address '$email' was not found in the official accredited alumni voter register. Please contact buibetasetalumni@gmail.com if you believe this is an error.";
            } elseif (strcasecmp(trim($eligible['fullname']), trim($fullname)) !== 0 && !str_contains(strtolower($eligible['fullname']), strtolower($fullname)) && !str_contains(strtolower($fullname), strtolower($eligible['fullname']))) {
                $error = "Validation Failed: The provided Full Name ('$fullname') does not match the accredited record on file for $email.";
            } else {
                // 2. Check if voter token was already generated
                $checkVoter = $pdo->prepare("SELECT id FROM voters WHERE LOWER(email) = ?");
                $checkVoter->execute([strtolower($email)]);
                if ($checkVoter->fetch()) {
                    $error = 'An official voting token has already been issued to this email address.';
                } else {
                    // Insert Registration
                    $regStmt = $pdo->prepare("INSERT INTO registrations (fullname, phone, email, graduation_year, id_type, id_value) VALUES (?, ?, ?, ?, ?, ?)");
                    $regStmt->execute([$fullname, $phone ?: '+234', $email, $gradYear ?: ($eligible['graduation_year'] ?? 2008), $idType, $idValue ?: 'Pre-validated Record']);
                    $regId = $pdo->lastInsertId();

                    // Generate Voting Token
                    $token = generateVotingToken();

                    // Insert Voter
                    $voterStmt = $pdo->prepare("INSERT INTO voters (registration_id, email, voting_token, token_sent) VALUES (?, ?, ?, 1)");
                    $voterStmt->execute([$regId, $email, $token]);

                    // Log Audit
                    logAudit($pdo, $fullname, "Validated against pre-uploaded voter register and received token ($token)");

                    // Send Email
                    sendTokenEmail($email, $fullname, $token);

                    $generatedToken = $token;
                    $message = "Accreditation verified! Your official voting token has been sent to $email.";
                }
            }
        } catch (Exception $e) {
            $error = 'Registration failed: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voter Registration - Bowen University Beta Set</title>
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
    <main class="flex-1 max-w-xl w-full mx-auto px-4 sm:px-6 py-8 sm:py-12">
        
        <!-- Header Text -->
        <div class="text-center mb-6">
            <h1 class="text-2xl sm:text-3xl font-bold text-[#101e36] tracking-tight">
                Accredited Voter Token Request
            </h1>
            <p class="text-xs text-slate-500 mt-2 max-w-md mx-auto leading-relaxed">
                The system checks your Full Name and Email Address against the pre-uploaded accredited alumni voter database before issuing your single-use voting token.
            </p>
        </div>

        <!-- Registration Form Card -->
        <div class="bg-white border border-slate-200/90 rounded-2xl p-6 sm:p-8 shadow-sm">
            
            <?php if ($message): ?>
                <div class="space-y-4 text-center">
                    <div class="p-4 bg-emerald-50 border border-emerald-200 rounded-xl">
                        <p class="text-emerald-800 font-bold text-xs sm:text-sm"><?= htmlspecialchars($message) ?></p>
                        <div class="mt-3 py-3 px-4 bg-white rounded-lg font-mono text-xl font-black text-[#101e36] tracking-widest border border-slate-200 shadow-inner">
                            <?= htmlspecialchars($generatedToken) ?>
                        </div>
                        <p class="text-[11px] text-slate-500 mt-2">Copy this 8-character token to access your ballot paper.</p>
                    </div>
                    <div class="pt-2">
                        <a href="vote.php?email=<?= urlencode($_POST['email'] ?? '') ?>&token=<?= urlencode($generatedToken) ?>" class="w-full py-3 px-6 bg-[#1b3252] hover:bg-[#152742] text-white font-bold rounded-lg text-xs transition-all shadow-sm block text-center">
                            Continue to ballot &rarr;
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="mb-5 p-3.5 bg-red-50 border border-red-200 rounded-xl text-red-700 font-semibold text-xs text-center">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>

            <?php if (!$message): ?>
                <form method="POST" action="register.php" class="space-y-4 text-xs">
                    <div>
                        <label class="block font-bold text-slate-800 mb-1.5">Full Name</label>
                        <input type="text" name="fullname" required placeholder="Jane Doe" value="<?= htmlspecialchars($_POST['fullname'] ?? '') ?>" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 bg-slate-50/50 text-slate-900 text-xs placeholder-slate-400 focus:bg-white focus:border-slate-800 focus:outline-none transition-all">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-800 mb-1.5">Phone Number (with country code)</label>
                        <input type="text" name="phone" required placeholder="+234" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 bg-slate-50/50 text-slate-900 text-xs placeholder-slate-400 focus:bg-white focus:border-slate-800 focus:outline-none transition-all">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-800 mb-1.5">Email Address</label>
                        <input type="email" name="email" required placeholder="you@example.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 bg-slate-50/50 text-slate-900 text-xs placeholder-slate-400 focus:bg-white focus:border-slate-800 focus:outline-none transition-all">
                    </div>

                    <div>
                        <label class="block font-bold text-slate-800 mb-1.5">Graduation Year</label>
                        <select name="graduation_year" required class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 bg-slate-50/50 text-slate-900 text-xs focus:bg-white focus:border-slate-800 focus:outline-none transition-all">
                            <option value="2008" selected>2008</option>
                            <option value="2007">2007</option>
                            <option value="2009">2009</option>
                        </select>
                    </div>

                    <div>
                        <label class="block font-bold text-slate-800 mb-1.5">Personal Verification</label>
                        <select name="id_type" id="idTypeSelect" onchange="updateIdLabel()" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 bg-slate-50/50 text-slate-900 text-xs focus:bg-white focus:border-slate-800 focus:outline-none transition-all">
                            <option value="Last 4 digits of National ID" selected>Last 4 digits of National ID</option>
                            <option value="Student ID Number">Student ID Number</option>
                            <option value="Passport Number">Passport Number</option>
                            <option value="Personal Code">Personal Alumni Code</option>
                        </select>
                    </div>

                    <div>
                        <label id="idValueLabel" class="block font-bold text-slate-800 mb-1.5">Last 4 digits of National ID</label>
                        <input type="text" name="id_value" required placeholder="Enter value" value="<?= htmlspecialchars($_POST['id_value'] ?? '') ?>" class="w-full px-3.5 py-2.5 rounded-lg border border-slate-200 bg-slate-50/50 text-slate-900 text-xs placeholder-slate-400 focus:bg-white focus:border-slate-800 focus:outline-none transition-all">
                    </div>

                    <div class="pt-3 flex items-center justify-between">
                        <a href="index.php" class="text-xs text-slate-500 hover:text-slate-900 font-medium transition-colors">
                            &larr; Cancel
                        </a>
                        <button type="submit" class="px-6 py-2.5 bg-[#1b3252] hover:bg-[#152742] text-white font-bold rounded-lg text-xs shadow-sm transition-all">
                            Register
                        </button>
                    </div>
                </form>
            <?php endif; ?>

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

    <script>
        function updateIdLabel() {
            const select = document.getElementById('idTypeSelect');
            const label = document.getElementById('idValueLabel');
            label.innerText = select.value;
        }
    </script>
</body>
</html>
