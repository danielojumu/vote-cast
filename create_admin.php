<?php
/**
 * BUI Beta Set Alumni Election Voting System (VCAST)
 * Secure Admin Account Generator Utility
 * 
 * Run this script via web browser (e.g., domain.com/create_admin.php) or CLI
 * to generate encrypted passwords or automatically insert new Electoral Officers into MySQL.
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

$message = '';
$error = '';
$generatedSql = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = sanitize($_POST['fullname'] ?? '');
    $email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'] ?? '';
    $role = sanitize($_POST['role'] ?? 'Electoral Commissioner');
    $action = $_POST['action'] ?? 'db_insert';

    if (!$fullname || !$email || strlen($password) < 6) {
        $error = 'Full name, valid email, and password (min 6 chars) are required.';
    } else {
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        if ($action === 'db_insert') {
            try {
                $pdo = getDB();
                $stmt = $pdo->prepare("INSERT INTO admins (fullname, email, password, role) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE password = VALUES(password), fullname = VALUES(fullname), role = VALUES(role)");
                $stmt->execute([$fullname, $email, $hashedPassword, $role]);
                logAudit($pdo, 'System/CLI', "Created/updated admin user ($email)");
                $message = "Admin user successfully created/updated in database!";
            } catch (Exception $e) {
                $error = "Database error: " . $e->getMessage();
            }
        }

        $escapedName = addslashes($fullname);
        $escapedEmail = addslashes($email);
        $generatedSql = "INSERT INTO `admins` (`fullname`, `email`, `password`, `role`) \nVALUES ('{$escapedName}', '{$escapedEmail}', '{$hashedPassword}', '{$role}') \nON DUPLICATE KEY UPDATE `password` = '{$hashedPassword}';";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Admin User - BUI Beta Set Election</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-[#f5f8fc] text-slate-900 min-h-screen p-4 sm:p-8 flex items-center justify-center selection:bg-amber-100 selection:text-slate-900">
    <div class="max-w-xl w-full bg-white border border-slate-200/90 rounded-3xl p-6 sm:p-8 shadow-sm">
        <div class="text-center mb-6">
            <span class="bg-amber-50 text-amber-800 border border-amber-200 text-xs px-3 py-1 rounded-full font-bold">Security Utility</span>
            <h1 class="text-2xl font-extrabold text-[#101e36] mt-2">Create Admin Credentials</h1>
            <p class="text-xs text-slate-500 mt-1">Generates Bcrypt-hashed admin credentials for MySQL</p>
        </div>

        <?php if ($message): ?>
            <div class="mb-6 p-4 bg-emerald-50 border border-emerald-200 rounded-2xl text-emerald-800 text-xs text-center font-bold">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-2xl text-red-800 text-xs text-center font-medium">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="create_admin.php" class="space-y-4 text-xs">
            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Full Name</label>
                <input type="text" name="fullname" required placeholder="e.g. Chief Returning Officer" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:bg-white focus:border-slate-800 focus:outline-none">
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Admin Email Address</label>
                <input type="email" name="email" required placeholder="e.g. elections@vote.buibetaset.org" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:bg-white focus:border-slate-800 focus:outline-none">
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">New Secure Password</label>
                <input type="password" name="password" required placeholder="Min. 6 characters" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 placeholder-slate-400 focus:bg-white focus:border-slate-800 focus:outline-none">
            </div>

            <div>
                <label class="block font-bold text-slate-700 uppercase mb-1">Role Permission</label>
                <select name="role" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 bg-slate-50 text-slate-900 focus:bg-white focus:border-slate-800 focus:outline-none">
                    <option value="Super Admin">Super Admin</option>
                    <option value="Electoral Commissioner">Electoral Commissioner</option>
                    <option value="Auditor">Auditor</option>
                </select>
            </div>

            <div class="pt-2 flex gap-3">
                <button type="submit" name="action" value="db_insert" class="flex-1 py-3 bg-[#1b3252] hover:bg-[#152742] text-white font-bold rounded-xl text-xs uppercase tracking-wider transition-all shadow-sm">
                    Insert Directly into DB
                </button>
                <button type="submit" name="action" value="generate_sql" class="flex-1 py-3 bg-slate-100 hover:bg-slate-200 text-slate-800 border border-slate-200 font-bold rounded-xl text-xs uppercase tracking-wider transition-all">
                    Generate SQL Script
                </button>
            </div>
        </form>

        <?php if ($generatedSql): ?>
            <div class="mt-6">
                <label class="block text-xs font-bold text-slate-800 uppercase mb-1">Generated SQL Query (phpMyAdmin):</label>
                <textarea readonly class="w-full h-24 p-3 bg-slate-50 border border-slate-200 rounded-xl font-mono text-[11px] text-slate-800 focus:outline-none select-all"><?= htmlspecialchars($generatedSql) ?></textarea>
            </div>
        <?php endif; ?>

        <div class="mt-6 text-center text-[11px] text-slate-500 border-t border-slate-200 pt-4">
            <a href="admin.php" class="text-[#1b3252] hover:underline font-semibold">Go to Admin Portal Login &rarr;</a>
        </div>
    </div>
</body>
</html>
