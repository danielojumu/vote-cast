<?php
/**
 * BUI Beta Set Alumni Election Voting System (VCAST)
 * Primary Portal Landing Page & Dispatcher
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bowen University Beta Set - Alumni Executive Election</title>
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

    <!-- Main Hero Body -->
    <main class="flex-1 max-w-4xl w-full mx-auto px-6 py-12 sm:py-16">
        
        <!-- Eyebrow Subtitle -->
        <div class="text-[11px] font-bold text-[#d97706] uppercase tracking-wider mb-2">
            EXECUTIVE ELECTION
        </div>

        <!-- Main Heading -->
        <h1 class="text-3xl sm:text-4xl font-extrabold text-[#101e36] tracking-tight mb-3">
            Bowen University Beta Set
        </h1>

        <!-- Subtitle Text -->
        <p class="text-slate-600 text-xs sm:text-sm leading-relaxed max-w-2xl mb-8">
            Welcome to the official online voting portal. Register in one step, receive your unique voting token by email instantly, then cast your secure ballot.
        </p>

        <!-- Two Action Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 max-w-2xl">
            <!-- Card 1: Register -->
            <div class="bg-white border border-slate-200/90 rounded-xl p-6 shadow-sm hover:shadow-md transition-all flex flex-col justify-between space-y-4">
                <div>
                    <h2 class="text-base font-bold text-[#101e36] mb-2">
                        1. Register
                    </h2>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Fill in your details. Your voting token is emailed to you right away — no waiting.
                    </p>
                </div>
                <div>
                    <a href="register.php" class="text-xs font-bold text-[#101e36] hover:text-sky-700 transition-colors inline-flex items-center space-x-1">
                        <span>Start registration &rarr;</span>
                    </a>
                </div>
            </div>

            <!-- Card 2: Vote -->
            <div class="bg-white border border-slate-200/90 rounded-xl p-6 shadow-sm hover:shadow-md transition-all flex flex-col justify-between space-y-4">
                <div>
                    <h2 class="text-base font-bold text-[#101e36] mb-2">
                        2. Vote
                    </h2>
                    <p class="text-xs text-slate-500 leading-relaxed">
                        Enter your email and 8-character token to access the ballot and cast your vote.
                    </p>
                </div>
                <div>
                    <a href="vote.php" class="text-xs font-bold text-[#101e36] hover:text-sky-700 transition-colors inline-flex items-center space-x-1">
                        <span>Cast your vote &rarr;</span>
                    </a>
                </div>
            </div>
        </div>

        <!-- Support Contact Note -->
        <div class="mt-8 text-xs text-slate-500">
            Questions? Contact <a href="mailto:buibetasetalumni@gmail.com" class="text-slate-700 hover:underline">buibetasetalumni@gmail.com</a>.
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
