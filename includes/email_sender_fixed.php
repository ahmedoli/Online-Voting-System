<?php

function getEmailConfig()
{
    static $config = null;

    if ($config === null) {
        if (file_exists(__DIR__ . '/email_config.php')) {
            $config = include __DIR__ . '/email_config.php';

            if (!is_array($config) || !isset($config['smtp'])) {
                error_log("Email config file exists but didn't return valid array");
                $config = getDefaultEmailConfig();
            }
        } else {
            error_log("Email config file not found, using defaults");
            $config = getDefaultEmailConfig();
        }
    }

    return $config;
}

function getDefaultEmailConfig()
{
    return [
        'smtp' => [
            'host' => 'smtp.gmail.com',
            'port' => 587,
            'username' => '',
            'password' => '',
            'encryption' => 'tls',
            'auth' => true
        ],
        'from' => [
            'email' => 'noreply@votingsystem.local',
            'name' => 'Online Voting System'
        ],
        'debug' => false
    ];
}

function sendOTPEmail($email, $otp)
{
    error_log("OTP Email Request: {$otp} to {$email} at " . date('Y-m-d H:i:s'));

    if (tryPHPMailerSend($email, $otp)) {
        return true;
    }

    return sendSimpleOTPEmail($email, $otp);
}

function tryPHPMailerSend($email, $otp)
{
    $email_config = getEmailConfig();

    $phpmailer_paths = [
        __DIR__ . '/../vendor/PHPMailer-master/src/PHPMailer.php',
        __DIR__ . '/../vendor/phpmailer/phpmailer/src/PHPMailer.php'
    ];

    $phpmailer_found = false;
    foreach ($phpmailer_paths as $path) {
        if (file_exists($path)) {
            require_once dirname($path) . '/PHPMailer.php';
            require_once dirname($path) . '/SMTP.php';
            require_once dirname($path) . '/Exception.php';
            $phpmailer_found = true;
            break;
        }
    }

    if (!$phpmailer_found) {
        error_log("PHPMailer not found, using simple mail");
        return false;
    }

    try {
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);

        $mail->isSMTP();
        $mail->Host = $email_config['smtp']['host'] ?? 'smtp.gmail.com';
        $mail->SMTPAuth = $email_config['smtp']['auth'] ?? true;
        $mail->Username = $email_config['smtp']['username'] ?? '';
        $mail->Password = $email_config['smtp']['password'] ?? '';
        $mail->SMTPSecure = $email_config['smtp']['encryption'] ?? 'tls';
        $mail->Port = $email_config['smtp']['port'] ?? 587;

        if (isset($email_config['debug']) && $email_config['debug']) {
            $mail->SMTPDebug = 2;
            $mail->Debugoutput = 'error_log';
        } else {
            $mail->SMTPDebug = 0;
        }

        $fromEmail = $email_config['smtp']['username'] ?? 'noreply@votingsystem.local';
        $fromName = $email_config['from']['name'] ?? 'Online Voting System';
        $mail->setFrom($fromEmail, $fromName);
        $mail->addAddress($email);
        $mail->addReplyTo($fromEmail, $fromName);
        $mail->isHTML(true);
        $mail->Subject = 'Online Voting System - Your OTP Code';
        $mail->Body = createOTPEmailHTML($otp);
        $mail->AltBody = createOTPEmailText($otp);

        $result = $mail->send();

        if ($result) {
            error_log("PHPMailer success: OTP sent to {$email}");
            return true;
        }
    } catch (Exception $e) {
        error_log("PHPMailer failed: " . $e->getMessage());
    }

    return false;
}

function sendSimpleOTPEmail($email, $otp)
{
    $subject = "Online Voting System - Your OTP Code";
    $message = createOTPEmailText($otp);

    $headers = "From: noreply@votingsystem.local\r\n";
    $headers .= "Reply-To: noreply@votingsystem.local\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $result = @mail($email, $subject, $message, $headers);

    if ($result) {
        error_log("Simple mail success: OTP sent to {$email}");
    } else {
        error_log("Simple mail failed for {$email}");
    }

    return true;
}

function createOTPEmailHTML($otp)
{
    return '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; background: #f5f5f5; margin: 0; padding: 20px; }
        .email-container { max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; }
        .content { padding: 40px 30px; text-align: center; }
        .otp-box { background: #f8f9fa; border: 2px solid #e9ecef; border-radius: 10px; padding: 25px; margin: 25px 0; }
        .otp-code { font-size: 36px; font-weight: bold; color: #495057; letter-spacing: 8px; margin: 10px 0; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 20px 0; color: #856404; }
        .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #6c757d; font-size: 14px; }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>🗳️ Online Voting System</h1>
            <p>Secure Login Verification</p>
        </div>
        <div class="content">
            <h2>Hello Voter!</h2>
            <p>Please use the following One-Time Password (OTP) to complete your login:</p>
            
            <div class="otp-box">
                <div>Your OTP Code:</div>
                <div class="otp-code">' . $otp . '</div>
            </div>
            
            <div class="warning">
                <strong>⚠️ Important Security Information:</strong><br>
                • This OTP will expire in exactly 2 minutes<br>
                • Never share this code with anyone<br>
                • If you didn\'t request this login, please ignore this email
            </div>
            
            <p>Thank you for participating in our democratic process!</p>
        </div>
        <div class="footer">
            <p>This is an automated message from the Online Voting System</p>
            <p>© ' . date('Y') . ' Online Voting System. All rights reserved.</p>
        </div>
    </div>
</body>
</html>';
}

function createOTPEmailText($otp)
{
    return "========================================\n" .
        "ONLINE VOTING SYSTEM - OTP CODE\n" .
        "========================================\n\n" .
        "Hello Voter,\n\n" .
        "Your One-Time Password (OTP) for secure login:\n\n" .
        "    >>> {$otp} <<<\n\n" .
        "IMPORTANT SECURITY INFORMATION:\n" .
        "• This OTP will expire in exactly 2 minutes\n" .
        "• Never share this code with anyone\n" .
        "• If you didn't request this login, ignore this email\n\n" .
        "Thank you for participating in our democratic process!\n\n" .
        "----------------------------------------\n" .
        "This is an automated message.\n" .
        "© " . date('Y') . " Online Voting System\n" .
        "========================================";
}
