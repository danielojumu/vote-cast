# VCAST - BUI Beta Set Alumni Election Voting System

VCAST is a secure, production-ready Alumni Election Voting Web Application built specifically for the **BUI Beta Set Alumni Association** (`https://vote.buibetaset.org`).

Engineered strictly with **PHP 8.2+**, **MySQL 8+**, **Tailwind CSS**, **Vanilla JavaScript**, **AJAX**, **Chart.js**, and **PHPMailer** for zero-friction deployment on **Hostinger Business Web Hosting**.

---

## Technical Stack & Architecture

- **Backend**: Plain PHP 8.2+ following clean MVC (Model-View-Controller) architecture.
- **Database**: MySQL 8+ with PDO Prepared Statements, Foreign Keys, and strict indexing.
- **Frontend**: HTML5, Tailwind CSS, Vanilla JS, AJAX, Chart.js.
- **Email System**: PHPMailer via Hostinger SMTP (`smtp.hostinger.com`).
- **Security**:
  - CSRF Token verification on all form submissions.
  - Password hashing with `password_hash()` and `password_verify()`.
  - Input sanitization & HTML output escaping (`Security::escape()`).
  - Session regeneration and 30-minute inactivity timeout.
  - Audit logging for every administrative and voting transaction.
  - Image upload validation (Max 5MB, JPG/PNG/WebP only).

---

## Folder Structure

```
vcast/
├── app/
│   ├── Config/          # Database & App configurations
│   ├── Controllers/     # Registration, Voting, Auth, Admin, Candidate, Results, Export
│   ├── Helpers/         # Security, Session, Validator, Response
│   ├── Mail/            # PHPMailer Email dispatch wrapper
│   ├── Middleware/      # Auth & CSRF validation
│   ├── Models/          # Admin, Registration, Voter, Position, Candidate, Vote, AuditLog
│   └── Views/           # Layouts and UI template components
├── database/
│   └── schema.sql       # Complete MySQL 8+ database schema & seed script
├── public/
│   ├── css/             # Custom Tailwind & CSS stylesheets
│   ├── js/              # Application AJAX handlers
│   ├── uploads/         # Candidate photos storage
│   └── index.php        # Front Controller / Routing entry point
├── routes/
│   └── web.php          # Route mappings
├── storage/
│   └── logs/            # Application & Mail fallback logs
├── .env.example         # Environment template file
├── composer.json        # PHP Dependencies
├── HOSTINGER_DEPLOYMENT.md # Hostinger Deployment instructions
└── README.md
```

---

## Core System Features

1. **Alumni Registration**:
   - Collects Full Name, Phone Number, Email, Graduation Year (2005-2024), Verification ID Type (Student ID, Passport, National ID, Personal Code) and ID Value.
   - Enforces unique email and unique phone number validation.
   - Automatically generates cryptographically secure 8-character voting token (e.g. `A8G2K9LQ`).
   - Immediately dispatches formatted HTML email with token to the alumnus via PHPMailer SMTP.

2. **Secure One-Time Voting System**:
   - **Step 1**: Verification of Email and Voting Token. Checks that token exists, matches email, and has NOT been used.
   - **Step 2**: Ballot view displaying candidates organized by Executive Position (President, Vice President, Secretary, Treasurer).
   - Shows candidate profiles, University Photos, Recent Photos, and Manifesto summaries.
   - **Step 3**: Instant AJAX submission, atomic vote recording, token invalidation (`has_voted = 1`, `voted_at = NOW()`), and duplicate voting block.

3. **Admin Portal & Live Dashboard**:
   - Admin Login with credentials, CSRF, and Session timeout protection.
   - **Metrics Cards**: Total Registrations, Tokens Sent, Votes Cast, Remaining Voters, Turnout %, Candidate Count.
   - **Candidate Management**: Add, Edit, Delete, Enable/Disable candidates, upload photos (5MB max), edit bio & manifesto, set position display order.
   - **Voters & Token Roster**: Search registered alumni, view token delivery status, resend voting tokens on demand.
   - **Live Results**: Progress bars, candidate vote counts & percentage share per position.
   - **Data Exports**: Download official election results in **CSV**, **Excel**, and **Printable PDF** formats.
   - **Audit Logs**: Immutable log of all system activity with IP addresses.

---

## Deployment Instructions for Hostinger

Please consult `HOSTINGER_DEPLOYMENT.md` for complete step-by-step instructions on importing `database/schema.sql`, configuring `.env`, and setting up Hostinger Webmail SMTP.
