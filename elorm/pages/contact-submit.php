<?php
// contact-submit.php: Receives POST from contact form and sends SMS via Twilio
// Requirements: PHP with cURL enabled (XAMPP has it by default). Create pages/config.php from config.example.php with your credentials.

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method Not Allowed']);
    exit;
}

// Load config
$configFile = __DIR__ . '/config.php';
if (!file_exists($configFile)) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Missing config.php. Copy pages/config.example.php to pages/config.php and fill credentials.']);
    exit;
}
$config = require $configFile;

$accountSid = $config['TWILIO_ACCOUNT_SID'] ?? '';
$authToken  = $config['TWILIO_AUTH_TOKEN'] ?? '';
$fromNumber = $config['TWILIO_FROM'] ?? '';
$toNumber   = $config['DEST_TO'] ?? '';

// SMTP (Gmail) config (optional, preferred)
$smtpHost   = $config['SMTP_HOST']   ?? '';
$smtpPort   = (int)($config['SMTP_PORT']   ?? 587);
$smtpSecure = strtolower($config['SMTP_SECURE'] ?? 'tls');
$smtpUser   = $config['SMTP_USER']   ?? '';
$smtpPass   = $config['SMTP_PASS']   ?? '';
$smtpTo     = $config['SMTP_TO']     ?? '';
$smtpFrom   = $config['SMTP_FROM']   ?? ($smtpUser ?: '');

// Only error if neither SMTP nor Twilio is configured at all
$twilioReady = ($accountSid && $authToken && $fromNumber && $toNumber);
$smtpReady   = ($smtpHost && $smtpUser && $smtpPass && $smtpTo);
if (!$twilioReady && !$smtpReady) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'No messaging transport configured (set Gmail SMTP in pages/config.php or Twilio).']);
    exit;
}

// Validate inputs
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($name === '' || $email === '' || $message === '') {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'All fields are required.']);
    exit;
}

// Build message bodies
$textBody = "New contact message:\nName: {$name}\nEmail: {$email}\nMessage: {$message}";
$htmlBody = "<h2>New contact message</h2>"
          . "<p><strong>Name:</strong> " . htmlspecialchars($name) . "</p>"
          . "<p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>"
          . "<p><strong>Message:</strong><br>" . nl2br(htmlspecialchars($message)) . "</p>";

// Try EMAIL via SMTP first, if configured
if ($smtpReady) {
    $send = send_via_smtp([
        'host' => $smtpHost,
        'port' => $smtpPort,
        'secure' => $smtpSecure, // tls or ssl
        'user' => $smtpUser,
        'pass' => $smtpPass,
        'from' => $smtpFrom ?: $smtpUser,
        'to'   => $smtpTo,
        'subject' => 'New contact message from portfolio',
        'text' => $textBody,
        'html' => $htmlBody,
        'replyTo' => $email,
        'replyName' => $name,
    ]);
    if ($send['ok'] ?? false) {
        echo json_encode(['ok' => true, 'message' => 'Email sent']);
        exit;
    } else {
        http_response_code(502);
        echo json_encode(['ok' => false, 'error' => 'SMTP error: ' . ($send['error'] ?? 'unknown')]);
        exit;
    }
}

// Fallback: Twilio REST API (Messages)
$twilioUrl = "https://api.twilio.com/2010-04-01/Accounts/{$accountSid}/Messages.json";

$postData = http_build_query([
    'To'   => $toNumber,
    'From' => $fromNumber,
    'Body' => $textBody,
]);

$ch = curl_init($twilioUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_USERPWD, $accountSid . ':' . $authToken);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = curl_error($ch);
curl_close($ch);

if ($error) {
    http_response_code(502);
    echo json_encode(['ok' => false, 'error' => 'cURL error: ' . $error]);
    exit;
}

if ($httpCode >= 200 && $httpCode < 300) {
    echo json_encode(['ok' => true, 'message' => 'SMS sent']);
} else {
    // Try to decode Twilio's JSON error to surface details
    $twilio = json_decode($response, true);
    $details = [];
    if (is_array($twilio)) {
        if (!empty($twilio['message'])) $details['twilio_message'] = $twilio['message'];
        if (!empty($twilio['code'])) $details['twilio_code'] = $twilio['code'];
        if (!empty($twilio['more_info'])) $details['twilio_more_info'] = $twilio['more_info'];
    }
    http_response_code($httpCode ?: 500);
    echo json_encode(array_merge(['ok' => false, 'error' => 'Twilio API error'], $details));
}

// --- Minimal SMTP client (STARTTLS/AUTH LOGIN) ---
function send_via_smtp(array $cfg) {
    $err = function($m){ return ['ok'=>false,'error'=>$m]; };
    $host = $cfg['host'];
    $port = (int)$cfg['port'];
    $secure = strtolower($cfg['secure'] ?? 'tls'); // 'tls' or 'ssl'
    $user = $cfg['user'];
    $pass = $cfg['pass'];
    $from = $cfg['from'];
    $to   = $cfg['to'];
    $subject = $cfg['subject'];
    $text = $cfg['text'];
    $html = $cfg['html'];
    $replyTo = $cfg['replyTo'] ?? '';
    $replyName = $cfg['replyName'] ?? '';

    $endpoint = ($secure === 'ssl') ? ('ssl://' . $host . ':' . $port) : ($host . ':' . $port);
    $fp = @stream_socket_client($endpoint, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
    if (!$fp) return $err("connect failed: $errstr ($errno)");
    stream_set_timeout($fp, 20);

    $read = function() use ($fp){
        $out = '';
        do {
            $line = fgets($fp, 2048);
            if ($line === false) break;
            $out = $line;
        } while (isset($line[3]) && $line[3] === '-');
        return $out;
    };
    $write = function($cmd) use ($fp){ fwrite($fp, $cmd . "\r\n"); };
    $expect = function($code) use ($read){
        $line = $read();
        return (is_string($line) && strpos($line, (string)$code) === 0) ? [true,$line] : [false,$line];
    };

    [$ok,$line] = $expect('220'); if(!$ok) return $err('greeting: '.$line);
    $write('EHLO localhost'); [$ok,$line] = $expect('250'); if(!$ok) return $err('EHLO: '.$line);

    if ($secure === 'tls') {
        $write('STARTTLS'); [$ok,$line] = $expect('220'); if(!$ok) return $err('STARTTLS: '.$line);
        if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) return $err('TLS negotiation failed');
        $write('EHLO localhost'); [$ok,$line] = $expect('250'); if(!$ok) return $err('EHLO(2): '.$line);
    }

    $write('AUTH LOGIN'); [$ok,$line] = $expect('334'); if(!$ok) return $err('AUTH: '.$line);
    $write(base64_encode($user)); [$ok,$line] = $expect('334'); if(!$ok) return $err('USER: '.$line);
    $write(base64_encode($pass)); [$ok,$line] = $expect('235'); if(!$ok) return $err('PASS: '.$line);

    $write('MAIL FROM: <' . $from . '>'); [$ok,$line] = $expect('250'); if(!$ok) return $err('MAIL FROM: '.$line);
    $write('RCPT TO: <' . $to .   '>'); [$ok,$line] = $expect('250'); if(!$ok) return $err('RCPT TO: '.$line);

    $boundary = 'bnd_' . bin2hex(random_bytes(6));
    $headers = [];
    $headers[] = 'From: ' . $from;
    $headers[] = 'To: ' . $to;
    $headers[] = 'Subject: ' . encode_header($subject);
    $headers[] = 'MIME-Version: 1.0';
    if ($replyTo) $headers[] = 'Reply-To: ' . encode_header(($replyName? ($replyName.' ') : '') . '<'.$replyTo.'>');
    $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';

    $mime  = "--$boundary\r\n";
    $mime .= "Content-Type: text/plain; charset=UTF-8\r\n\r\n" . $text . "\r\n";
    $mime .= "--$boundary\r\n";
    $mime .= "Content-Type: text/html; charset=UTF-8\r\n\r\n" . $html . "\r\n";
    $mime .= "--$boundary--\r\n";

    $write('DATA'); [$ok,$line] = $expect('354'); if(!$ok) return $err('DATA: '.$line);
    fwrite($fp, implode("\r\n", $headers) . "\r\n\r\n" . $mime . "\r\n.\r\n");
    [$ok,$line] = $expect('250'); if(!$ok) return $err('DATA end: '.$line);

    $write('QUIT'); fclose($fp);
    return ['ok'=>true];
}

function encode_header($str){
    return preg_match('/[\x80-\xFF]/', $str) ? ('=?UTF-8?B?'.base64_encode($str).'?=') : $str;
}
