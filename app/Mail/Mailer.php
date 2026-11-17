<?php

namespace App\Mail;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;
use App\Helpers\Security;

class Mailer
{
    private array $config;

    public function __construct()
    {
        $appConfig = require __DIR__ . '/../Config/config.php';
        $this->config = $appConfig['smtp'];
    }

    /**
     * Send voting token email via PHPMailer
     */
    public function sendVotingToken(string $toEmail, string $toName, string $token): bool
    {
        $subject = "BUI Beta Set Alumni Election Voting Token";
        $appUrl = $this->config['url'] ?? $_ENV['APP_URL'] ?? 'https://vote.buibetaset.org';

        $bodyHtml = "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; background-color: #f4f6f8; margin: 0; padding: 20px; color: #333; }
                .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 8px; overflow: hidden; border: 1px solid #e1e8ed; }
                .header { background-color: #1A3A5C; color: #ffffff; padding: 25px; text-align: center; }
                .header h1 { margin: 0; font-size: 22px; letter-spacing: 0.5px; }
                .content { padding: 30px; line-height: 1.6; }
                .token-box { background-color: #F0F4F8; border: 2px dashed #1A3A5C; border-radius: 6px; padding: 20px; text-align: center; margin: 25px 0; }
                .token-code { font-family: monospace, monospace; font-size: 32px; font-weight: bold; letter-spacing: 6px; color: #1A3A5C; }
                .btn { display: inline-block; background-color: #1A3A5C; color: #ffffff !important; text-decoration: none; padding: 14px 28px; font-weight: bold; border-radius: 6px; margin-top: 15px; }
                .footer { background-color: #f8fafc; padding: 20px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>BUI Beta Set Alumni Association</h1>
                    <p style='margin: 5px 0 0 0; opacity: 0.9; font-size: 14px;'>Official Election Voting Portal</p>
                </div>
                <div class='content'>
                    <p>Dear <strong>" . Security::escape($toName) . "</strong>,</p>
                    <p>Thank you for registering for the upcoming <strong>BUI Beta Set Alumni Executive Election</strong>.</p>
                    <p>Your unique confidential voting token has been generated below:</p>
                    
                    <div class='token-box'>
                        <div style='font-size: 12px; text-transform: uppercase; color: #64748b; margin-bottom: 5px;'>Your Voting Token</div>
                        <div class='token-code'>" . Security::escape($token) . "</div>
                    </div>

                    <p style='text-align: center;'>
                        <a href='" . Security::escape($appUrl) . "/vote' class='btn'>Proceed to Voting Portal</a>
                    </p>

                    <div style='background-color: #fffbe3; border-left: 4px solid #f59e0b; padding: 12px 15px; margin-top: 25px; font-size: 13px; color: #92400e;'>
                        <strong>Important Security Notice:</strong> Keep this token confidential. It can only be used once to submit your official ballot.
                    </div>
                </div>
                <div class='footer'>
                    &copy; " . date('Y') . " BUI Beta Set Alumni Electoral Committee.<br>
                    Official Portal: <a href='" . Security::escape($appUrl) . "' style='color:#1A3A5C;'>" . Security::escape($appUrl) . "</a>
                </div>
            </div>
        </body>
        </html>
        ";

        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            try {
                $mail = new PHPMailer(true);
                $mail->isSMTP();
                $mail->Host = $this->config['host'];
                $mail->SMTPAuth = true;
                $mail->Username = $this->config['username'];
                $mail->Password = $this->config['password'];
                $mail->SMTPSecure = $this->config['secure'] === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port = (int)$this->config['port'];

                $mail->setFrom($this->config['from_email'], $this->config['from_name']);
                $mail->addAddress($toEmail, $toName);

                $mail->isHTML(true);
                $mail->Subject = $subject;
                $mail->Body = $bodyHtml;
                $mail->AltBody = "Dear {$toName},\n\nYour voting token is: {$token}\n\nVoting link: {$appUrl}/vote\n\nKeep this token confidential. It can only be used once.";

                return $mail->send();
            } catch (PHPMailerException $e) {
                error_log("PHPMailer Exception: " . $e->getMessage());
            }
        }

        // Fallback: Log to mail log if PHPMailer class is not loaded yet
        $logDir = __DIR__ . '/../../storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        $logMessage = "[" . date('Y-m-d H:i:s') . "] TO: {$toEmail} | NAME: {$toName} | TOKEN: {$token}\n";
        @file_put_contents($logDir . '/mail.log', $logMessage, FILE_APPEND);
        
        return true;
    }
}
