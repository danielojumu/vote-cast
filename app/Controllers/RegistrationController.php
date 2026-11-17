<?php

namespace App\Controllers;

use App\Helpers\Validator;
use App\Helpers\Security;
use App\Helpers\Response;
use App\Models\Registration;
use App\Models\Voter;
use App\Models\AuditLog;
use App\Mail\Mailer;

class RegistrationController extends Controller
{
    public function showForm(): void
    {
        $this->render('register', [
            'years' => range(2005, 2024),
            'idTypes' => ['Student ID', 'Passport', 'National ID', 'Personal Code'],
            'csrf_token' => Security::generateCSRFToken()
        ]);
    }

    public function submit(): void
    {
        $data = [
            'fullname' => Security::sanitize($_POST['fullname'] ?? ''),
            'phone' => Security::sanitize($_POST['phone'] ?? ''),
            'email' => strtolower(trim(Security::sanitize($_POST['email'] ?? ''))),
            'graduation_year' => $_POST['graduation_year'] ?? '',
            'id_type' => $_POST['id_type'] ?? '',
            'id_value' => Security::sanitize($_POST['id_value'] ?? ''),
        ];

        $validator = new Validator($data);
        $validator->required('fullname', 'Full Name')
                  ->required('phone', 'Phone Number')
                  ->phone('phone')
                  ->unique('phone', 'registrations', 'phone')
                  ->required('email', 'Email Address')
                  ->email('email')
                  ->unique('email', 'registrations', 'email')
                  ->required('graduation_year', 'Graduation Year')
                  ->required('id_type', 'Verification ID Type')
                  ->required('id_value', 'Verification ID Value');

        if (!$validator->passes()) {
            Response::json([
                'success' => false,
                'message' => 'Please fix the validation errors.',
                'errors' => $validator->getErrors()
            ], 422);
        }

        try {
            // 1. Save Registration
            $regId = Registration::create($data);

            // 2. Generate Unique Token
            $token = Security::generateVotingToken(8);

            // 3. Save Voter record
            $voterId = Voter::create($regId, $data['email'], $token);

            // 4. Send Email via PHPMailer
            $mailer = new Mailer();
            $emailSent = $mailer->sendVotingToken($data['email'], $data['fullname'], $token);

            // 5. Audit Log
            AuditLog::log("Alumni registered: {$data['fullname']} ({$data['email']}) - Token: {$token}", "Registration System");

            Response::json([
                'success' => true,
                'message' => 'Registration Successful. Your voting token has been sent to your email.',
                'token' => $token, // Provided for AJAX display/testing
                'email' => $data['email']
            ]);
        } catch (\Exception $e) {
            error_log("Registration Error: " . $e->getMessage());
            Response::json([
                'success' => false,
                'message' => 'An error occurred while processing your registration. Please try again.'
            ], 500);
        }
    }
}
