<?php

/**
 * AMAANAT MEDICAL — Enquiry Form Handler
 * File:    submit-enquiry.php
 * Place:   Same directory as pages/contact.html  (or one level up if preferred)
 * Requires: PHP 7.4+, mail() enabled on server OR configure PHPMailer (see below)
 *
 * Accepts POST from contact.html via AJAX.
 * Returns JSON:  { "success": true }  or  { "success": false, "error": "..." }
 */

// ── 1. CONFIGURATION ─────────────────────────────────────────────────────────

define('RECIPIENT_EMAIL',   'amanaat@yahoo.com');          // Primary inbox
define('RECIPIENT_EMAIL_2', 'amaanatnetwk@gmail.com');     // CC / secondary
define('COMPANY_NAME',      'AMAANAT MEDICAL DIAGNOSTICS EQUIPMENT LIMITED');
define('SITE_URL',          'https://amaanatmedical.com'); // Your live domain
define('RATE_LIMIT_FILE',   sys_get_temp_dir() . '/amaanat_rl.json'); // rate-limit store

// ── 2. CORS & HEADERS ────────────────────────────────────────────────────────

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Allow requests only from your own domain (change to match your live URL)
$allowed_origins = [SITE_URL, 'http://localhost', 'http://127.0.0.1'];
$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
if (in_array(rtrim($origin, '/'), $allowed_origins, true)) {
    header("Access-Control-Allow-Origin: $origin");
}
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// ── 3. ONLY ACCEPT POST ──────────────────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed.']);
    exit;
}

// ── 4. RATE LIMITING (5 submissions per IP per 10 minutes) ───────────────────

function check_rate_limit(string $ip): bool
{
    $limit    = 5;
    $window   = 600; // seconds (10 min)
    $file     = RATE_LIMIT_FILE;
    $data     = [];

    if (file_exists($file)) {
        $raw = @file_get_contents($file);
        $data = $raw ? (json_decode($raw, true) ?? []) : [];
    }

    $now = time();

    // Purge old entries
    foreach ($data as $k => $entries) {
        $data[$k] = array_filter($entries, fn($t) => ($now - $t) < $window);
        if (empty($data[$k])) unset($data[$k]);
    }

    $count = count($data[$ip] ?? []);
    if ($count >= $limit) {
        return false; // rate limited
    }

    $data[$ip][] = $now;
    @file_put_contents($file, json_encode($data), LOCK_EX);
    return true;
}

$client_ip = $_SERVER['HTTP_X_FORWARDED_FOR']
    ? trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0])
    : ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

if (!check_rate_limit($client_ip)) {
    http_response_code(429);
    echo json_encode(['success' => false, 'error' => 'Too many submissions. Please wait 10 minutes and try again.']);
    exit;
}

// ── 5. HONEYPOT CHECK (bot trap — a hidden field called "website" should be empty) ──

$honeypot = trim($_POST['website'] ?? '');
if ($honeypot !== '') {
    // Silent success to confuse bots
    echo json_encode(['success' => true]);
    exit;
}

// ── 6. COLLECT & SANITIZE INPUTS ─────────────────────────────────────────────

function clean(string $val, int $max = 500): string
{
    return htmlspecialchars(substr(trim($val), 0, $max), ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

$fullName   = clean($_POST['fullName']   ?? '', 100);
$email      = filter_var(trim($_POST['email'] ?? ''), FILTER_SANITIZE_EMAIL);
$phone      = clean($_POST['phone']      ?? '', 30);
$department = clean($_POST['department'] ?? '', 100);
$interest   = clean($_POST['interest']   ?? '', 300);
$message    = clean($_POST['message']    ?? '', 2000);
$consent    = !empty($_POST['consent']) && $_POST['consent'] === 'true';

// ── 7. SERVER-SIDE VALIDATION ────────────────────────────────────────────────

$errors = [];

if (strlen($fullName) < 2) {
    $errors[] = 'Full name is required.';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email address is required.';
}

if (empty($department)) {
    $errors[] = 'Please select a department.';
}

if (strlen(trim($_POST['message'] ?? '')) < 10) {
    $errors[] = 'Please provide a message of at least 10 characters.';
}

if (!$consent) {
    $errors[] = 'You must accept the contact consent to proceed.';
}

if (!empty($errors)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'error' => implode(' ', $errors)]);
    exit;
}

// ── 8. BUILD THE EMAIL ───────────────────────────────────────────────────────

$submitted_at = date('D, d M Y \a\t H:i:s T');
$interest_str = $interest ?: '(not specified)';
$phone_str    = $phone    ?: '(not provided)';

// Plain-text version
$plain = <<<TEXT
NEW ENQUIRY — AMAANAT MEDICAL DIAGNOSTICS EQUIPMENT LIMITED
============================================================

Submitted:   $submitted_at
IP Address:  $client_ip

CONTACT DETAILS
---------------
Name:        $fullName
Email:       $email
Phone:       $phone_str
Department:  $department

ENQUIRY DETAILS
---------------
Interest:    $interest_str

Message:
$message

--
This message was submitted via the contact form at AMAANAT MEDICAL website.
Do NOT reply to this email directly — reply to: $email
TEXT;

// HTML version
$html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<style>
  body { font-family: 'Segoe UI', Arial, sans-serif; background:#f8f9ff; margin:0; padding:0; color:#0b1c30; }
  .wrap { max-width:600px; margin:32px auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 4px 24px rgba(11,60,93,.10); }
  .header { background:#00263f; padding:32px 36px 24px; }
  .header h1 { color:#fff; margin:0 0 4px; font-size:22px; letter-spacing:-.5px; }
  .header p { color:#7fa7cd; margin:0; font-size:13px; }
  .body { padding:32px 36px; }
  .section-title { font-size:11px; font-weight:700; letter-spacing:.1em; text-transform:uppercase; color:#006783; margin:24px 0 10px; border-bottom:1px solid #e5eeff; padding-bottom:6px; }
  .row { display:flex; gap:8px; margin-bottom:10px; }
  .label { font-size:13px; color:#72777e; min-width:100px; flex-shrink:0; }
  .value { font-size:13px; color:#0b1c30; font-weight:500; }
  .message-box { background:#eff4ff; border-left:3px solid #006783; border-radius:4px; padding:16px; margin-top:8px; font-size:14px; line-height:1.6; white-space:pre-wrap; }
  .footer { background:#eff4ff; padding:16px 36px; font-size:12px; color:#72777e; border-top:1px solid #e5eeff; }
  .badge { display:inline-block; padding:3px 10px; border-radius:20px; font-size:11px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; }
  .badge-new { background:#dce9ff; color:#0b3c5d; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>New Enquiry Received</h1>
    <p>AMAANAT MEDICAL DIAGNOSTICS EQUIPMENT LIMITED &nbsp;•&nbsp; $submitted_at</p>
  </div>
  <div class="body">
    <span class="badge badge-new">&#9679; New Submission</span>

    <div class="section-title">Contact Details</div>
    <div class="row"><span class="label">Name</span><span class="value">$fullName</span></div>
    <div class="row"><span class="label">Email</span><span class="value"><a href="mailto:$email" style="color:#006783;">$email</a></span></div>
    <div class="row"><span class="label">Phone</span><span class="value">$phone_str</span></div>
    <div class="row"><span class="label">Department</span><span class="value">$department</span></div>

    <div class="section-title">Enquiry Details</div>
    <div class="row"><span class="label">Interest</span><span class="value">$interest_str</span></div>
    <div style="margin-top:4px;"><span class="label" style="display:block;margin-bottom:6px;">Message</span>
      <div class="message-box">$message</div>
    </div>

    <div class="section-title">Submission Metadata</div>
    <div class="row"><span class="label">IP Address</span><span class="value">$client_ip</span></div>
    <div class="row"><span class="label">Submitted</span><span class="value">$submitted_at</span></div>
  </div>
  <div class="footer">
    This email was generated automatically from the AMAANAT MEDICAL website contact form.<br/>
    To reply, use: <a href="mailto:$email" style="color:#006783;">$email</a>
  </div>
</div>
</body>
</html>
HTML;

// ── 9. AUTO-REPLY TO THE SENDER ──────────────────────────────────────────────

$auto_reply_html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"/>
<style>
  body { font-family:'Segoe UI',Arial,sans-serif; background:#f8f9ff; margin:0; padding:0; color:#0b1c30; }
  .wrap { max-width:600px; margin:32px auto; background:#fff; border-radius:12px; overflow:hidden; box-shadow:0 4px 24px rgba(11,60,93,.10); }
  .header { background:#00263f; padding:36px; text-align:center; }
  .header h1 { color:#fff; margin:0 0 6px; font-size:24px; }
  .header p { color:#7fa7cd; margin:0; font-size:14px; }
  .body { padding:36px; }
  p { font-size:15px; line-height:1.7; color:#42474e; margin:0 0 14px; }
  .summary { background:#eff4ff; border-radius:8px; padding:20px 24px; margin:24px 0; }
  .summary-row { display:flex; gap:8px; margin-bottom:8px; font-size:14px; }
  .slabel { color:#72777e; min-width:90px; flex-shrink:0; }
  .svalue { color:#0b1c30; font-weight:500; }
  .footer { background:#eff4ff; padding:16px 36px; font-size:12px; color:#72777e; text-align:center; border-top:1px solid #e5eeff; }
  a { color:#006783; }
</style>
</head>
<body>
<div class="wrap">
  <div class="header">
    <h1>Thank You, $fullName</h1>
    <p>Your enquiry has been received — we'll respond within 24 hours.</p>
  </div>
  <div class="body">
    <p>Dear $fullName,</p>
    <p>Thank you for reaching out to <strong>AMAANAT MEDICAL DIAGNOSTICS EQUIPMENT LIMITED</strong>. We have received your enquiry and our engineering team will review it and get back to you within <strong>24 business hours</strong>.</p>

    <div class="summary">
      <div class="summary-row"><span class="slabel">Department</span><span class="svalue">$department</span></div>
      <div class="summary-row"><span class="slabel">Interest</span><span class="svalue">$interest_str</span></div>
    </div>

    <p>If your matter is urgent, please contact us directly:</p>
    <p>
      📞 <strong>08023011646 / 08035026442 / 08033184305</strong><br/>
      📧 <a href="mailto:amanaat@yahoo.com">amanaat@yahoo.com</a> &nbsp;|&nbsp; <a href="mailto:amaanatnetwk@gmail.com">amaanatnetwk@gmail.com</a>
    </p>
    <p>We look forward to serving you.</p>
    <p>Warm regards,<br/><strong>The AMAANAT MEDICAL Team</strong><br/>17, Kudirat Abiola Way, Oregun, Ikeja, Lagos.</p>
  </div>
  <div class="footer">
    © 2025 AMAANAT MEDICAL DIAGNOSTICS EQUIPMENT LIMITED. All rights reserved.<br/>
    This is an automated reply. Please do not reply to this email.
  </div>
</div>
</body>
</html>
HTML;

// ── 10. SEND EMAILS ──────────────────────────────────────────────────────────

/**
 * send_email() — wrapper around PHP mail().
 * For production, swap this for PHPMailer/SMTP (see commented block below).
 */
function send_email(string $to, string $subject, string $html, string $plain, string $reply_to = ''): bool
{
    $boundary = '----=_Part_' . md5(uniqid('', true));
    $from_name  = addslashes(COMPANY_NAME);
    $from_email = 'noreply@' . ($_SERVER['HTTP_HOST'] ?? 'amaanatmedical.com');

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "From: {$from_name} <{$from_email}>\r\n";
    if ($reply_to) $headers .= "Reply-To: {$reply_to}\r\n";
    $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion() . "\r\n";

    $body  = "--{$boundary}\r\n";
    $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
    $body .= quoted_printable_encode($plain) . "\r\n";
    $body .= "--{$boundary}\r\n";
    $body .= "Content-Type: text/html; charset=UTF-8\r\n";
    $body .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
    $body .= quoted_printable_encode($html) . "\r\n";
    $body .= "--{$boundary}--";

    return mail($to, $subject, $body, $headers);
}

/*
 * ── OPTIONAL: PHPMailer / SMTP (recommended for production) ─────────────────
 * Uncomment and configure if your host blocks mail() or for better deliverability.
 *
 * require_once __DIR__ . '/vendor/autoload.php';
 * use PHPMailer\PHPMailer\PHPMailer;
 *
 * function send_email(string $to, string $subject, string $html, string $plain, string $reply_to = ''): bool {
 *     $mail = new PHPMailer(true);
 *     try {
 *         $mail->isSMTP();
 *         $mail->Host       = 'smtp.gmail.com';          // e.g. smtp.gmail.com
 *         $mail->SMTPAuth   = true;
 *         $mail->Username   = 'your@gmail.com';
 *         $mail->Password   = 'your_app_password';       // Gmail App Password
 *         $mail->SMTPSecure = 'tls';
 *         $mail->Port       = 587;
 *         $mail->setFrom('your@gmail.com', COMPANY_NAME);
 *         $mail->addAddress($to);
 *         if ($reply_to) $mail->addReplyTo($reply_to);
 *         $mail->isHTML(true);
 *         $mail->Subject  = $subject;
 *         $mail->Body     = $html;
 *         $mail->AltBody  = $plain;
 *         return $mail->send();
 *     } catch (\Exception $e) {
 *         error_log('PHPMailer error: ' . $mail->ErrorInfo);
 *         return false;
 *     }
 * }
 * ─────────────────────────────────────────────────────────────────────────── */

$subject_internal = "New Enquiry from {$fullName} — {$department}";
$subject_autoreply = "We received your enquiry — " . COMPANY_NAME;

// Send to AMAANAT team (primary + CC)
$sent_internal = send_email(
    RECIPIENT_EMAIL,
    $subject_internal,
    $html,
    $plain,
    $email  // Reply-To set to the enquirer
);

// CC to second address
send_email(RECIPIENT_EMAIL_2, $subject_internal, $html, $plain, $email);

// Auto-reply to sender
send_email($email, $subject_autoreply, $auto_reply_html, "Dear {$fullName},\n\nThank you for contacting AMAANAT MEDICAL. We have received your enquiry and will respond within 24 business hours.\n\nFor urgent matters call: 08023011646\n\nWarm regards,\nThe AMAANAT MEDICAL Team");

// ── 11. LOG TO FILE (optional — disable in production if disk space is a concern)

$log_entry = json_encode([
    'timestamp'  => $submitted_at,
    'ip'         => $client_ip,
    'name'       => $fullName,
    'email'      => $email,
    'phone'      => $phone_str,
    'department' => $department,
    'interest'   => $interest_str,
    'message'    => substr($message, 0, 200) . (strlen($message) > 200 ? '…' : ''),
]) . "\n";

$log_file = __DIR__ . '/enquiries.log';
@file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);

// ── 12. RESPOND ──────────────────────────────────────────────────────────────

if ($sent_internal) {
    http_response_code(200);
    echo json_encode(['success' => true, 'message' => 'Enquiry submitted successfully.']);
} else {
    // mail() failed — still log it but tell the user something went wrong
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error'   => 'Your enquiry was received but we could not send the confirmation email. Please call us directly on 08023011646.',
    ]);
}
