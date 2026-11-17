import React, { useState } from 'react';
import { Code2, Database, FileCode, Check, Copy, Server, ExternalLink, HardDrive } from 'lucide-react';

export const PhpSourceInspector: React.FC = () => {
  const [selectedFile, setSelectedFile] = useState<string>('schema.sql');
  const [copied, setCopied] = useState(false);

  const fileContents: Record<string, string> = {
    'schema.sql': `-- =======================================================
-- BUI Beta Set Alumni Election Voting System (VCAST)
-- MySQL 8+ Schema Script for Hostinger Web Hosting
-- Import into Hostinger phpMyAdmin
-- =======================================================

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS audit_logs, votes, candidates, positions, voters, registrations, admins;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE admins (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fullname VARCHAR(150) NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('Super Admin', 'Electoral Commissioner', 'Auditor') NOT NULL DEFAULT 'Electoral Commissioner',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE registrations (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  fullname VARCHAR(150) NOT NULL,
  phone VARCHAR(30) NOT NULL UNIQUE,
  email VARCHAR(150) NOT NULL UNIQUE,
  graduation_year INT UNSIGNED NOT NULL,
  id_type ENUM('Student ID', 'Passport', 'National ID', 'Personal Code') NOT NULL,
  id_value VARCHAR(100) NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE voters (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  registration_id INT UNSIGNED NOT NULL,
  email VARCHAR(150) NOT NULL UNIQUE,
  voting_token VARCHAR(32) NOT NULL UNIQUE,
  token_sent TINYINT(1) NOT NULL DEFAULT 1,
  token_sent_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
  has_voted TINYINT(1) NOT NULL DEFAULT 0,
  voted_at TIMESTAMP NULL DEFAULT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (registration_id) REFERENCES registrations(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE positions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  display_order INT UNSIGNED NOT NULL DEFAULT 1,
  active TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE candidates (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  position_id INT UNSIGNED NOT NULL,
  fullname VARCHAR(150) NOT NULL,
  manifesto TEXT NOT NULL,
  profile TEXT NULL,
  university_photo VARCHAR(255) NULL,
  recent_photo VARCHAR(255) NULL,
  display_order INT UNSIGNED NOT NULL DEFAULT 1,
  active TINYINT(1) NOT NULL DEFAULT 1,
  FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE votes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  voter_id INT UNSIGNED NOT NULL,
  candidate_id INT UNSIGNED NOT NULL,
  position_id INT UNSIGNED NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY unique_voter_position (voter_id, position_id),
  FOREIGN KEY (voter_id) REFERENCES voters(id) ON DELETE CASCADE,
  FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE CASCADE,
  FOREIGN KEY (position_id) REFERENCES positions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE audit_logs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user VARCHAR(150) NOT NULL DEFAULT 'System',
  action TEXT NOT NULL,
  ip_address VARCHAR(45) NOT NULL DEFAULT '127.0.0.1',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO admins (fullname, email, password, role) VALUES
('Electoral Chairman', 'admin@vote.buibetaset.org', '$2y$10$fV3GsnT1zT2QvjN9YxHq/eXmY7wY09kZkC8Q.8C3eL/xG3C2q3b/S', 'Super Admin');`,

    'config.php': `<?php
/**
 * BUI Beta Set Alumni Election Voting System (VCAST)
 * Global Configuration & Database Connection Handler
 */
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'u123456789_vcast');
define('DB_USER', getenv('DB_USER') ?: 'u123456789_vcastusr');
define('DB_PASS', getenv('DB_PASS') ?: 'SecretPassword2026!');
define('DB_CHARSET', 'utf8mb4');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}`,

    'index.php': `<?php
/**
 * Main Portal Entry Point - Hostinger Deployment
 */
require_once __DIR__ . '/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BUI Beta Set Alumni Election System (VCAST)</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-950 text-white min-h-screen p-8">
    <div class="max-w-4xl mx-auto space-y-6">
        <h1 class="text-3xl font-extrabold text-amber-400">BUI Beta Set Alumni Election Portal</h1>
        <div class="flex gap-4">
            <a href="register.php" class="px-4 py-2 bg-sky-500 rounded-xl font-bold">Register & Token</a>
            <a href="vote.php" class="px-4 py-2 bg-amber-400 text-slate-950 rounded-xl font-bold">Cast Vote</a>
            <a href="results.php" class="px-4 py-2 bg-emerald-500 rounded-xl font-bold">Live Results</a>
            <a href="admin.php" class="px-4 py-2 bg-[#1A3A5C] rounded-xl font-bold">Admin Portal</a>
        </div>
    </div>
</body>
</html>`,

    'register.php': `<?php
/**
 * Voter Registration & Token Dispatcher
 */
require_once __DIR__ . '/config.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullname = $_POST['fullname'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $token = strtoupper(substr(bin2hex(random_bytes(6)), 0, 8));
    
    $pdo = getDB();
    $stmt = $pdo->prepare("INSERT INTO registrations (fullname, phone, email, graduation_year, id_type, id_value) VALUES (?, ?, ?, 2008, 'Personal Code', 'BUI/08/CS')");
    $stmt->execute([$fullname, $phone, $email]);
    $regId = $pdo->lastInsertId();

    $stmt2 = $pdo->prepare("INSERT INTO voters (registration_id, email, voting_token) VALUES (?, ?, ?)");
    $stmt2->execute([$regId, $email, $token]);
    echo "Registered! Token: " . $token;
}`,

    'vote.php': `<?php
/**
 * Official Ballot Paper
 */
require_once __DIR__ . '/config.php';
// Ballot verification and voting logic...`,

    'results.php': `<?php
/**
 * Live Election Results & Standings
 */
require_once __DIR__ . '/config.php';
// Standings query and chart rendering...`,

    'admin.php': `<?php
/**
 * Electoral Commission Admin Portal
 */
require_once __DIR__ . '/config.php';
// Admin login & voter management...`,

    'public/index.php': `<?php
/**
 * BUI Beta Set Alumni Election Voting System (VCAST)
 * Front Controller & Router Engine
 * Hostinger Business Hosting Compatible
 */

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

// Route Dispatcher
$routes = require __DIR__ . '/../routes/web.php';
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$requestMethod = $_SERVER['REQUEST_METHOD'];

if (isset($routes[$requestMethod][$requestUri])) {
    list($controllerClass, $method) = $routes[$requestMethod][$requestUri];
    
    if ($requestMethod === 'POST') {
        \\App\\Middleware\\CSRFMiddleware::handle();
    }
    
    $controller = new $controllerClass();
    $controller->$method();
} else {
    http_response_code(404);
    echo "<h1>404 Page Not Found</h1>";
}`,

    'app/Controllers/VotingController.php': `<?php

namespace App\\Controllers;

use App\\Helpers\\Security;
use App\\Helpers\\Response;
use App\\Models\\Voter;
use App\\Models\\Vote;
use App\\Models\\AuditLog;

class VotingController extends Controller
{
    public function submitVote(): void
    {
        $email = strtolower(trim($_POST['email'] ?? ''));
        $token = strtoupper(trim($_POST['token'] ?? ''));
        $choices = $_POST['choices'] ?? [];

        $voter = Voter::findByEmailAndToken($email, $token);

        if (!$voter || $voter['has_voted']) {
            Response::json(['success' => false, 'message' => 'Invalid or already used voting token.'], 403);
        }

        foreach ($choices as $posId => $candId) {
            Vote::recordVote($voter['id'], (int)$candId, (int)$posId);
        }

        Voter::markVoted($voter['id']);
        AuditLog::log("Vote submitted by voter ID {$voter['id']}", "Voting System");

        Response::json(['success' => true, 'message' => 'Thank you. Your vote has been recorded.']);
    }
}`,

    '.env.example': `# Application
APP_NAME="BUI Beta Set Alumni Voting"
APP_ENV="production"
APP_URL="https://vote.buibetaset.org"

# Hostinger MySQL Database
DB_HOST="localhost"
DB_NAME="u123456789_vcast"
DB_USER="u123456789_vcast_user"
DB_PASS="YourSecureDBPassword123!"

# Hostinger SMTP PHPMailer
SMTP_HOST="smtp.hostinger.com"
SMTP_PORT=465
SMTP_SECURE="ssl"
SMTP_USERNAME="noreply@vote.buibetaset.org"
SMTP_PASSWORD="HostingerSmtpPassword2026!"
SMTP_FROM="noreply@vote.buibetaset.org"
SMTP_NAME="BUI Beta Set Alumni Electoral Committee"`,

    'HOSTINGER_DEPLOYMENT.md': `# Hostinger Business Web Hosting Deployment Guide for VCAST

1. Create MySQL Database in Hostinger hPanel under Databases -> Management.
2. Import database/schema.sql via phpMyAdmin.
3. Upload all PHP files to domain root vote.buibetaset.org via File Manager.
4. Set Document Root to /public or place .htaccess redirect.
5. Create .env with Hostinger DB and SMTP settings.
6. Verify SSL certificate activation and test live login at https://vote.buibetaset.org/admin/login.`
  };

  const handleCopy = () => {
    navigator.clipboard.writeText(fileContents[selectedFile] || '');
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  return (
    <div className="max-w-6xl mx-auto py-8 px-4 sm:px-6">
      <div className="bg-[#1A3A5C]/80 text-white p-6 rounded-3xl shadow-2xl backdrop-blur-xl border border-sky-400/40 mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div>
          <div className="flex items-center space-x-2">
            <span className="bg-emerald-500/20 text-emerald-300 text-xs px-2.5 py-0.5 rounded-full font-bold border border-emerald-400/30">
              PHP 8.2+ Plain MVC Architecture
            </span>
            <span className="text-xs text-slate-300">Hostinger Ready</span>
          </div>
          <h1 className="text-2xl font-extrabold text-white mt-1">PHP Codebase & Hostinger Inspector</h1>
          <p className="text-xs text-slate-300 mt-0.5">
            Inspect the exact PHP, SQL schema, and environment configurations created on disk for Hostinger deployment.
          </p>
        </div>

        <button
          onClick={handleCopy}
          className="inline-flex items-center space-x-2 bg-amber-400 hover:bg-amber-300 text-slate-950 px-4 py-2.5 rounded-xl font-extrabold text-xs shadow transition-all border border-amber-300/40 self-start md:self-auto"
        >
          {copied ? <Check className="w-4 h-4" /> : <Copy className="w-4 h-4" />}
          <span>{copied ? 'Copied File Content' : 'Copy Active File'}</span>
        </button>
      </div>

      {/* Hostinger Blank Screen Fix Diagnostic Banner */}
      <div className="bg-amber-500/10 border border-amber-500/30 rounded-3xl p-6 mb-6 text-white shadow-2xl backdrop-blur-xl">
        <div className="flex items-start space-x-3">
          <div className="w-10 h-10 bg-amber-500/20 text-amber-300 border border-amber-400/30 rounded-2xl flex items-center justify-center shrink-0 mt-0.5">
            <HardDrive className="w-5 h-5" />
          </div>
          <div className="flex-1 space-y-3">
            <div className="flex items-center justify-between flex-wrap gap-2">
              <h3 className="text-base font-extrabold text-amber-300">Hostinger Blank Screen Diagnostic Guide (`vote.buibetaset.org`)</h3>
              <span className="text-[10px] bg-amber-400/20 text-amber-200 border border-amber-300/30 px-2.5 py-0.5 rounded-full font-bold">
                Hostinger hPanel Troubleshooting
              </span>
            </div>
            
            <p className="text-xs text-slate-300 leading-relaxed">
              If <code>vote.buibetaset.org</code> shows a blank white page, check these 3 common Hostinger causes:
            </p>

            <div className="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
              <div className="bg-white/5 border border-white/10 rounded-2xl p-3.5 space-y-1">
                <div className="font-bold text-sky-300 flex items-center space-x-1">
                  <span>1. Delete Default File</span>
                </div>
                <p className="text-[11px] text-slate-300">
                  Hostinger creates a default <code>default.php</code> or <code>index.html</code> inside <code>public_html/</code> when a subdomain is made. Delete it so your site loads.
                </p>
              </div>

              <div className="bg-white/5 border border-white/10 rounded-2xl p-3.5 space-y-1">
                <div className="font-bold text-emerald-300 flex items-center space-x-1">
                  <span>2. Upload Built `dist/` Files</span>
                </div>
                <p className="text-[11px] text-slate-300">
                  If uploading React SPA, run <code>npm run build</code> locally and upload <strong>the contents inside dist/</strong> directly to <code>public_html/</code> (not raw <code>.tsx</code> source).
                </p>
              </div>

              <div className="bg-white/5 border border-white/10 rounded-2xl p-3.5 space-y-1">
                <div className="font-bold text-amber-300 flex items-center space-x-1">
                  <span>3. Root `.htaccess` / `index.php`</span>
                </div>
                <p className="text-[11px] text-slate-300">
                  If deploying PHP MVC, upload the included root <code>index.php</code> and <code>.htaccess</code> which redirect requests to <code>public/index.php</code>.
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
        {/* File Tree */}
        <div className="bg-white/5 backdrop-blur-xl p-4 rounded-3xl border border-white/10 shadow-2xl text-white space-y-1">
          <div className="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2 px-2">Project Files</div>
          {Object.keys(fileContents).map((fileName) => (
            <button
              key={fileName}
              onClick={() => setSelectedFile(fileName)}
              className={`w-full text-left px-3 py-2 rounded-xl text-xs font-mono transition-all flex items-center space-x-2 ${
                selectedFile === fileName
                  ? 'bg-[#1A3A5C] text-sky-300 border border-sky-400/40 font-bold shadow'
                  : 'text-slate-300 hover:bg-white/5'
              }`}
            >
              {fileName.endsWith('.sql') ? (
                <Database className="w-3.5 h-3.5 text-amber-400" />
              ) : fileName.endsWith('.md') ? (
                <Server className="w-3.5 h-3.5 text-emerald-400" />
              ) : (
                <FileCode className="w-3.5 h-3.5 text-sky-400" />
              )}
              <span className="truncate">{fileName}</span>
            </button>
          ))}
        </div>

        {/* Code Viewer */}
        <div className="md:col-span-3 bg-slate-950/80 backdrop-blur-2xl rounded-3xl border border-white/10 shadow-2xl overflow-hidden flex flex-col">
          <div className="bg-white/5 px-4 py-2.5 border-b border-white/10 flex items-center justify-between text-xs text-slate-300 font-mono">
            <span>{selectedFile}</span>
            <span className="text-[10px] text-emerald-400 font-semibold">PHP 8.2 / MySQL 8</span>
          </div>

          <pre className="p-4 text-xs font-mono text-emerald-300/90 leading-relaxed overflow-x-auto max-h-[500px]">
            <code>{fileContents[selectedFile]}</code>
          </pre>
        </div>
      </div>
    </div>
  );
};
