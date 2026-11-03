<?php
/* =====================================================================
   member_registration.php  (SINGLE FILE - kono helper lagbe na)
   Registration save hole info@bishwas.org theke success email jabe
   ===================================================================== */

// ---------- SMTP SETTINGS (shudhu ekhane password din) ----------
$SMTP_HOST   = 'mail.bishwas.org';     // cPanel > Email Accounts > Connect Devices theke milan
$SMTP_PORT   = 465;                    // 465 = ssl, 587 = tls
$SMTP_SECURE = 'ssl';                  // 'ssl' othoba 'tls'
$SMTP_USER   = 'info@bishwas.org';
$SMTP_PASS   = 'YOUR_EMAIL_PASSWORD';  // <-- info@bishwas.org email-er asol password (webmail login password)
$LOGIN_URL   = 'https://bishwas.org/login.php';
$DEF_PASS    = '12345';

// ---------- LOGO ----------
// Auto: public/assets/<favicon_icon> (PNG/JPG hole mail-er bhetor embed hoy, tai kokhono block hobe na).
// Logo .ico/.svg hole ekhane PNG/JPG logo-r server path din, jemon: __DIR__ . '/public/assets/logo.png'
$LOGO_PATH   = '';
$LOGO_URL    = '';                     // optional fallback: https://bishwas.org/public/assets/logo.png

// ---------- MAIL TEST ----------
// Test link: https://bishwas.org/member_registration.php?mailtest=1&key=bef-test-2026&to=apnar@gmail.com
// Test shesh hole $TEST_KEY = ''; kore din (test off hoye jabe)
$TEST_KEY    = 'bef-test-2026';

/* ---------------------------------------------------------------------
   Log: mail_debug.log (ei file-er pashe toiri hoy). Thik hole file-ta delete kore din.
   --------------------------------------------------------------------- */
function bef_log($msg) {
    @file_put_contents(__DIR__ . '/mail_debug.log', '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n", FILE_APPEND);
    error_log('BEF MAIL: ' . $msg);
}

/* Logo file khuje ber kora (embed-er jonno) */
function bef_find_logo($favicon_icon, $custom_path) {
    $cands = [];
    if ($custom_path !== '') $cands[] = $custom_path;
    if (!empty($favicon_icon)) $cands[] = __DIR__ . '/public/assets/' . $favicon_icon;
    foreach ($cands as $p) {
        if (is_file($p) && preg_match('/\.(png|jpe?g|gif)$/i', $p) && filesize($p) < 1048576) {
            return $p;
        }
    }
    return null;
}

/* Ekta SMTP attempt */
function bef_smtp_try($host, $port, $secure, $user, $pass, $to, $raw, &$err) {
    $ctx = stream_context_create(['ssl' => [
        'verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true
    ]]);
    $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
    $fp = @stream_socket_client($remote, $errno, $errstr, 12, STREAM_CLIENT_CONNECT, $ctx);
    if (!$fp) { $err = "connect failed: {$errstr} ({$errno})"; return false; }
    stream_set_timeout($fp, 15);

    $read = function () use ($fp) {
        $d = '';
        while (($l = fgets($fp, 515)) !== false) {
            $d .= $l;
            if (strlen($l) < 4 || $l[3] === ' ') break;
        }
        return $d;
    };
    $cmd = function ($c, $okCodes, $label) use ($fp, $read, &$err) {
        if ($c !== '') fwrite($fp, $c . "\r\n");
        $r = $read();
        if (!in_array((int)substr($r, 0, 3), $okCodes, true)) {
            $err = "[{$label}] " . trim($r);
            return false;
        }
        return true;
    };

    $ok = $cmd('', [220], 'greeting') && $cmd('EHLO bishwas.org', [250], 'EHLO');

    if ($ok && $secure === 'tls') {
        $ok = $cmd('STARTTLS', [220], 'STARTTLS');
        if ($ok && !@stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
            $err = '[TLS] handshake failed';
            $ok = false;
        }
        $ok = $ok && $cmd('EHLO bishwas.org', [250], 'EHLO-TLS');
    }

    $ok = $ok
       && $cmd('AUTH LOGIN', [334], 'AUTH')
       && $cmd(base64_encode($user), [334], 'AUTH user')
       && $cmd(base64_encode($pass), [235], 'AUTH password')
       && $cmd("MAIL FROM:<{$user}>", [250], 'MAIL FROM')
       && $cmd("RCPT TO:<{$to}>", [250, 251], 'RCPT TO')
       && $cmd('DATA', [354], 'DATA');

    if ($ok) {
        fwrite($fp, preg_replace('/^\./m', '..', $raw) . "\r\n.\r\n");
        $ok = $cmd('', [250], 'send body');
    }
    @fwrite($fp, "QUIT\r\n");
    @fclose($fp);
    return $ok;
}

/* Email pathano: SMTP (auto multi-try) -> mail() fallback.  $info = ki hoyeche tar list */
function bef_send_mail($to, $subject, $html, $text, $cfg, $logo_file, &$info) {
    $from     = $cfg['user'];
    $fromName = '=?UTF-8?B?' . base64_encode('Bishwas Education Foundation') . '?=';
    $encSubj  = '=?UTF-8?B?' . base64_encode($subject) . '?=';
    $alt      = 'bef_alt_' . md5(uniqid((string)mt_rand(), true));
    $rel      = 'bef_rel_' . md5(uniqid((string)mt_rand(), true));

    $altBody  = "--{$alt}\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
    $altBody .= chunk_split(base64_encode($text)) . "\r\n";
    $altBody .= "--{$alt}\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n";
    $altBody .= chunk_split(base64_encode($html)) . "\r\n";
    $altBody .= "--{$alt}--";

    $headers  = "Date: " . date('r') . "\r\n";
    $headers .= "From: {$fromName} <{$from}>\r\n";
    $headers .= "To: <{$to}>\r\n";
    $headers .= "Reply-To: {$from}\r\n";
    $headers .= "Subject: {$encSubj}\r\n";
    $headers .= "Message-ID: <" . md5(uniqid('', true)) . "@bishwas.org>\r\n";
    $headers .= "MIME-Version: 1.0\r\n";

    if ($logo_file) {
        $ext  = strtolower(pathinfo($logo_file, PATHINFO_EXTENSION));
        $mime = ($ext === 'png') ? 'image/png' : (($ext === 'gif') ? 'image/gif' : 'image/jpeg');
        $headers .= "Content-Type: multipart/related; type=\"multipart/alternative\"; boundary=\"{$rel}\"\r\n";
        $body  = "--{$rel}\r\nContent-Type: multipart/alternative; boundary=\"{$alt}\"\r\n\r\n";
        $body .= $altBody . "\r\n";
        $body .= "--{$rel}\r\nContent-Type: {$mime}; name=\"logo.{$ext}\"\r\nContent-Transfer-Encoding: base64\r\n";
        $body .= "Content-ID: <beflogo>\r\nContent-Disposition: inline; filename=\"logo.{$ext}\"\r\n\r\n";
        $body .= chunk_split(base64_encode(file_get_contents($logo_file))) . "\r\n";
        $body .= "--{$rel}--";
    } else {
        $headers .= "Content-Type: multipart/alternative; boundary=\"{$alt}\"\r\n";
        $body = $altBody;
    }

    $info = [];

    // ---- SMTP: configured server age, tarpor common fallback gulo ----
    if ($cfg['pass'] !== 'YOUR_EMAIL_PASSWORD' && $cfg['pass'] !== '') {
        $attempts = [
            [$cfg['host'], $cfg['port'], $cfg['secure']],
            [$cfg['host'], 587, 'tls'],
            ['localhost', 465, 'ssl'],
            ['localhost', 587, 'tls'],
            ['bishwas.org', 465, 'ssl'],
        ];
        $seen = [];
        foreach ($attempts as $a) {
            $key = $a[0] . ':' . $a[1];
            if (isset($seen[$key])) continue;
            $seen[$key] = true;

            $err = '';
            if (bef_smtp_try($a[0], $a[1], $a[2], $cfg['user'], $cfg['pass'], $to, $headers . "\r\n" . $body, $err)) {
                $info[] = "SMTP OK via {$a[0]}:{$a[1]} ({$a[2]})";
                return true;
            }
            $info[] = "SMTP FAIL {$a[0]}:{$a[1]} ({$a[2]}) -> {$err}";
            // Password vul hole ar onno server try kore lav nai
            if (strpos($err, '535') !== false || stripos($err, 'authentication') !== false) {
                $info[] = 'Email password vul. $SMTP_PASS thik korun.';
                break;
            }
        }
    } else {
        $info[] = 'SMTP password set kora nai ($SMTP_PASS placeholder-i royeche)';
    }

    // ---- Fallback: PHP mail() ----
    $h2 = preg_replace('/^(To|Subject): .*\r\n/m', '', $headers);
    $ok = @mail($to, $encSubj, $body, $h2, '-f' . $from);
    $info[] = 'mail() fallback: ' . ($ok ? 'server accepted (Inbox/Spam dekhun)' : 'FAILED');
    return $ok;
}

/* ---------------------------------------------------------------------
   Email design (logo: $logo_src = "cid:beflogo" othoba full URL)
   --------------------------------------------------------------------- */
function bef_email_html($name, $email, $type, $ref, $logo_src, $loginUrl, $pass, $addr, $phone, $cmail) {
    $h = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
    $name = $h($name); $email = $h($email); $type = $h($type); $ref = $h($ref); $logo = $h($logo_src);
    $loginUrl = $h($loginUrl); $pass = $h($pass); $addr = $h($addr); $phone = $h($phone); $cmail = $h($cmail);
    $year = date('Y');

    $logoBlock = $logo !== ''
        ? '<table role="presentation" cellpadding="0" cellspacing="0"><tr><td align="center" style="background:#ffffff;border-radius:50%;padding:8px;width:84px;height:84px;"><img src="' . $logo . '" alt="Bishwas Education Foundation" width="84" height="84" style="display:block;border-radius:50%;object-fit:contain;"></td></tr></table>'
        : '';

    return <<<HTML
<!DOCTYPE html>
<html lang="bn"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Registration Successful</title></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Tahoma,Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 0;"><tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(15,23,42,0.08);">

  <tr><td align="center" bgcolor="#0f172a" style="background:#0f172a;background-image:linear-gradient(135deg,#0f172a 0%,#1e3a5f 55%,#0369a1 100%);padding:36px 24px 30px;">
    {$logoBlock}
    <h1 style="margin:18px 0 4px;font-size:22px;line-height:1.3;color:#ffffff;letter-spacing:0.5px;text-transform:uppercase;">Bishwas Education Foundation</h1>
    <p style="margin:0;font-size:13px;color:#bae6fd;">Official Member Portal</p>
  </td></tr>

  <tr><td align="center" style="padding:32px 32px 8px;">
    <table role="presentation" cellpadding="0" cellspacing="0"><tr><td align="center" style="width:56px;height:56px;border-radius:50%;background:#d1fae5;color:#059669;font-size:30px;font-weight:bold;line-height:56px;">&#10003;</td></tr></table>
    <h2 style="margin:16px 0 6px;font-size:22px;color:#0f172a;">Registration Successful!</h2>
    <p style="margin:0;font-size:15px;color:#475569;">আপনার আবেদন সফলভাবে জমা হয়েছে।</p>
  </td></tr>

  <tr><td style="padding:20px 32px 0;font-size:15px;line-height:1.7;color:#334155;">
    <p style="margin:0 0 10px;">প্রিয় <strong style="color:#0f172a;">{$name}</strong>,</p>
    <p style="margin:0;">বিশ্বাস এডুকেশন ফাউন্ডেশনে সদস্য হওয়ার জন্য আবেদন করার জন্য আপনাকে আন্তরিক ধন্যবাদ। আপনার আবেদনটি এখন <strong>অ্যাডমিনের অনুমোদনের অপেক্ষায়</strong> আছে।</p>
  </td></tr>

  <tr><td style="padding:22px 32px 0;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;">
      <tr><td style="padding:14px 18px;border-bottom:1px solid #e2e8f0;font-size:12px;font-weight:bold;letter-spacing:1px;color:#0369a1;text-transform:uppercase;">Application Details / আবেদনের তথ্য</td></tr>
      <tr><td style="padding:14px 18px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;color:#334155;">
          <tr><td style="padding:5px 0;color:#64748b;width:42%;">Reference No.</td><td style="padding:5px 0;font-weight:bold;color:#0f172a;">{$ref}</td></tr>
          <tr><td style="padding:5px 0;color:#64748b;">Membership Type</td><td style="padding:5px 0;font-weight:bold;color:#0f172a;">{$type}</td></tr>
          <tr><td style="padding:5px 0;color:#64748b;">Status</td><td style="padding:5px 0;"><span style="background:#fef3c7;color:#b45309;font-size:12px;font-weight:bold;padding:3px 10px;border-radius:20px;">Pending Approval</span></td></tr>
        </table>
      </td></tr>
    </table>
  </td></tr>

  <tr><td style="padding:18px 32px 0;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;">
      <tr><td style="padding:14px 18px;border-bottom:1px solid #bfdbfe;font-size:12px;font-weight:bold;letter-spacing:1px;color:#1d4ed8;text-transform:uppercase;">Your Login Details / লগইন তথ্য</td></tr>
      <tr><td style="padding:14px 18px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;color:#334155;">
          <tr><td style="padding:6px 0;color:#64748b;width:30%;vertical-align:top;">Link</td><td style="padding:6px 0;"><a href="{$loginUrl}" style="color:#1d4ed8;font-weight:bold;text-decoration:none;word-break:break-all;">{$loginUrl}</a></td></tr>
          <tr><td style="padding:6px 0;color:#64748b;vertical-align:top;">Email</td><td style="padding:6px 0;font-weight:bold;color:#0f172a;word-break:break-all;">{$email}</td></tr>
          <tr><td style="padding:6px 0;color:#64748b;vertical-align:top;">Password</td><td style="padding:6px 0;"><span style="background:#ffffff;border:1px dashed #60a5fa;border-radius:6px;padding:4px 12px;font-family:Consolas,'Courier New',monospace;font-size:15px;font-weight:bold;color:#0f172a;letter-spacing:1px;">{$pass}</span></td></tr>
        </table>
      </td></tr>
    </table>
  </td></tr>

  <tr><td align="center" style="padding:24px 32px 0;">
    <a href="{$loginUrl}" style="display:inline-block;background:#0369a1;background-image:linear-gradient(90deg,#0369a1,#0284c7);color:#ffffff;text-decoration:none;font-weight:bold;font-size:15px;padding:14px 38px;border-radius:10px;">Login to Member Portal &rarr;</a>
  </td></tr>

  <tr><td style="padding:26px 32px 0;">
    <p style="margin:0 0 10px;font-size:14px;font-weight:bold;color:#0f172a;">পরবর্তী ধাপ:</p>
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;line-height:1.6;color:#475569;">
      <tr><td style="padding:4px 0;vertical-align:top;width:26px;color:#0369a1;font-weight:bold;">1.</td><td style="padding:4px 0;">অ্যাডমিন আপনার আবেদন যাচাই করে অনুমোদন দেবেন।</td></tr>
      <tr><td style="padding:4px 0;vertical-align:top;color:#0369a1;font-weight:bold;">2.</td><td style="padding:4px 0;">অনুমোদনের পর উপরের তথ্য দিয়ে পোর্টালে লগইন করুন।</td></tr>
      <tr><td style="padding:4px 0;vertical-align:top;color:#0369a1;font-weight:bold;">3.</td><td style="padding:4px 0;">লগইন করে পোর্টালের <strong>Settings</strong> থেকে আপনার পাসওয়ার্ড পরিবর্তন করে নিন।</td></tr>
    </table>
  </td></tr>

  <tr><td style="padding:20px 32px 0;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#fffbeb;border:1px solid #fde68a;border-radius:10px;">
      <tr><td style="padding:12px 16px;font-size:13px;line-height:1.6;color:#92400e;"><strong>নিরাপত্তা পরামর্শ:</strong> এটি একটি সাধারণ (ডিফল্ট) পাসওয়ার্ড। প্রথমবার লগইন করার পরই পাসওয়ার্ড পরিবর্তন করুন এবং কারো সাথে শেয়ার করবেন না।</td></tr>
    </table>
  </td></tr>

  <tr><td style="padding:30px 32px 28px;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid #e2e8f0;">
      <tr><td align="center" style="padding-top:20px;font-size:12px;line-height:1.8;color:#64748b;">
        <strong style="color:#0f172a;font-size:13px;">Bishwas Education Foundation</strong><br>
        {$addr}<br>
        Phone: {$phone} &nbsp;|&nbsp; Email: <a href="mailto:{$cmail}" style="color:#0369a1;text-decoration:none;">{$cmail}</a><br>
        <a href="https://bishwas.org" style="color:#0369a1;text-decoration:none;">bishwas.org</a>
      </td></tr>
      <tr><td align="center" style="padding-top:14px;font-size:11px;color:#94a3b8;">
        আপনি যদি এই আবেদন না করে থাকেন, তবে এই ইমেইলটি উপেক্ষা করুন বা আমাদের জানান।<br>
        &copy; {$year} Bishwas Education Foundation. All rights reserved.
      </td></tr>
    </table>
  </td></tr>

</table>
</td></tr></table>
</body></html>
HTML;
}

/* =====================================================================
   Main
   ===================================================================== */

// header a HTML output ache, tai buffer diye chapa dhore rakhi jate redirect kaj kore
ob_start();
include 'include/header.php';   // ekhan theke $db ashbe
ob_end_clean();

// Logo ber kora
$fav        = (isset($favicon_icon) && $favicon_icon !== '') ? $favicon_icon : '';
$logo_file  = bef_find_logo($fav, $LOGO_PATH);                       // embed korar jonno
if ($logo_file) {
    $logo_src = 'cid:beflogo';
} elseif ($LOGO_URL !== '') {
    $logo_src = $LOGO_URL;
} else {
    $logo_src = $fav !== '' ? 'https://bishwas.org/public/assets/' . rawurlencode($fav) : '';
}

$mail_cfg = ['host' => $SMTP_HOST, 'port' => $SMTP_PORT, 'secure' => $SMTP_SECURE, 'user' => $SMTP_USER, 'pass' => $SMTP_PASS];

// Contact info (email footer)
$c_addr  = '১/জি/১০/১, মীরবাগ হাতিরঝিল, নতুন রাস্তা, ৩ নং লেন, ঢাকা-১২১৭, বাংলাদেশ';
$c_phone = '+৮৮০ ১৭১৫-৪৮২৩৬৩';
$c_mail  = 'info@bishwas.org';
if (isset($db)) {
    mysqli_set_charset($db, "utf8mb4");
    $cr = @mysqli_query($db, "SELECT * FROM contact_settings WHERE id = 1 LIMIT 1");
    if ($cr && ($crow = mysqli_fetch_assoc($cr))) {
        if (!empty($crow['office_address'])) $c_addr  = $crow['office_address'];
        if (!empty($crow['phone_number']))   $c_phone = $crow['phone_number'];
        if (!empty($crow['email_address']))  $c_mail  = $crow['email_address'];
    }
}

/* ---------- MAIL TEST MODE ---------- */
if (isset($_GET['mailtest']) && $TEST_KEY !== '' && ($_GET['key'] ?? '') === $TEST_KEY) {
    header('Content-Type: text/plain; charset=utf-8');
    $to = trim($_GET['to'] ?? '');
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) { exit("&to=apnar@gmail.com dite hobe\n"); }

    echo "PHP version : " . PHP_VERSION . "\n";
    echo "OpenSSL     : " . (extension_loaded('openssl') ? 'yes' : 'NO (ssl smtp kaj korbe na)') . "\n";
    echo "SMTP        : {$SMTP_HOST}:{$SMTP_PORT} ({$SMTP_SECURE}) user={$SMTP_USER}\n";
    echo "Password    : " . (($SMTP_PASS === 'YOUR_EMAIL_PASSWORD' || $SMTP_PASS === '') ? 'NOT SET' : 'set') . "\n";
    echo "Logo        : " . ($logo_file ? "embedded ({$logo_file})" : "link ({$logo_src})") . "\n\n";

    $html = bef_email_html('Test Member', $to, 'General Member', 'BEF-TEST-00001', $logo_src, $LOGIN_URL, $DEF_PASS, $c_addr, $c_phone, $c_mail);
    $info = [];
    $ok = bef_send_mail($to, 'Registration Successful - Bishwas Education Foundation', $html,
        "Test mail - Bishwas Education Foundation", $mail_cfg, $logo_file, $info);

    foreach ($info as $line) echo "- {$line}\n";
    echo "\nRESULT: " . ($ok ? "SENT -> {$to} (Inbox + Spam dekhun)" : "FAILED (upore karon dekhun)") . "\n";
    exit;
}

// Shudhu POST hole kaj korbe
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

// ---------- 1. Form theke data neya ----------
$member_name         = trim($_POST['member_name'] ?? '');
$mother_name         = trim($_POST['mother_name'] ?? '');
$father_husband_name = trim($_POST['father_husband_name'] ?? '');
$dob                 = trim($_POST['dob'] ?? '');
$gender              = trim($_POST['gender'] ?? '');
$id_type             = trim($_POST['id_type'] ?? '');
$id_number           = trim($_POST['id_number'] ?? '');
$qualification       = trim($_POST['qualification'] ?? '');
$mobile_no           = trim($_POST['mobile_no'] ?? '');
$email               = trim($_POST['email'] ?? '');
$present_address     = trim($_POST['present_address'] ?? '');
$permanent_address   = trim($_POST['permanent_address'] ?? '');
$other_info          = trim($_POST['other_info'] ?? '');
$user_type           = trim($_POST['user_type'] ?? '');

// Password form theke ashbe na. Default 12345, md5 kore rakhbo
$password = md5('12345');

// Je page theke ashche sei page a ferot jabe (nam alada hole ekhane change koro)
$back_page = 'volunteer_register.php';

// ---------- 2. Validation ----------
if ($member_name == '' || $mother_name == '' || $father_husband_name == '' || $dob == '' ||
    $gender == '' || $id_type == '' || $id_number == '' || $mobile_no == '' ||
    $present_address == '' || $permanent_address == '' || $user_type == '') {
    header("Location: $back_page?error=" . urlencode('সব প্রয়োজনীয় তথ্য পূরণ করুন।'));
    exit;
}

if (!preg_match('/^[0-9]{11}$/', $mobile_no)) {
    header("Location: $back_page?error=" . urlencode('মোবাইল নম্বর ১১ ডিজিটের হতে হবে।'));
    exit;
}

if ($email != '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: $back_page?error=" . urlencode('সঠিক ইমেইল দিন।'));
    exit;
}

// Mobile number age thekei ache kina check
$check = mysqli_prepare($db, "SELECT id FROM users WHERE mobile_no = ? LIMIT 1");
mysqli_stmt_bind_param($check, "s", $mobile_no);
mysqli_stmt_execute($check);
mysqli_stmt_store_result($check);
if (mysqli_stmt_num_rows($check) > 0) {
    header("Location: $back_page?error=" . urlencode('এই মোবাইল নম্বর দিয়ে আগেই আবেদন করা হয়েছে।'));
    exit;
}
mysqli_stmt_close($check);

// ---------- 3. Photo upload ----------
$photo_name = '';

if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {

    $allowed_ext = array('jpg', 'jpeg', 'png');
    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed_ext)) {
        header("Location: $back_page?error=" . urlencode('ছবি শুধু JPG বা PNG হতে হবে।'));
        exit;
    }

    if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
        header("Location: $back_page?error=" . urlencode('ছবির সাইজ সর্বোচ্চ 2MB।'));
        exit;
    }

    $upload_dir = 'portal/public/uploads/members/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $photo_name = 'member_' . time() . '_' . rand(1000, 9999) . '.' . $ext;

    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $photo_name)) {
        header("Location: $back_page?error=" . urlencode('ছবি আপলোড করা যায়নি।'));
        exit;
    }
}

// ---------- 4. Database a save ----------
$sql = "INSERT INTO users
        (photo, member_name, mother_name, father_husband_name, dob, gender, id_type, id_number,
         qualification, mobile_no, email, present_address, permanent_address, other_info,
         user_type, password)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($db, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "ssssssssssssssss",
    $photo_name, $member_name, $mother_name, $father_husband_name, $dob, $gender,
    $id_type, $id_number, $qualification, $mobile_no, $email, $present_address,
    $permanent_address, $other_info, $user_type, $password
);

if (mysqli_stmt_execute($stmt)) {

    $new_id = mysqli_insert_id($db);
    mysqli_stmt_close($stmt);

    // ---------- 5. Success email ----------
    if ($email != '') {
        try {
            $ref  = 'BEF-' . date('Y') . '-' . str_pad((string)$new_id, 5, '0', STR_PAD_LEFT);

            $html = bef_email_html($member_name, $email, $user_type, $ref, $logo_src, $LOGIN_URL, $DEF_PASS, $c_addr, $c_phone, $c_mail);
            $text = "Registration Successful - Bishwas Education Foundation\n\n"
                  . "Dear {$member_name},\n\nYour membership application has been submitted and is pending admin approval.\n"
                  . "Reference: {$ref}\nMembership: {$user_type}\n\n"
                  . "Login: {$LOGIN_URL}\nEmail: {$email}\nPassword: {$DEF_PASS}\n\n"
                  . "After approval, login and change your password from Settings.\n\nBishwas Education Foundation\ninfo@bishwas.org";

            $info = [];
            $sent = bef_send_mail($email, 'Registration Successful - Bishwas Education Foundation', $html, $text, $mail_cfg, $logo_file, $info);
            bef_log(($sent ? 'SENT' : 'FAILED') . " to {$email} (user #{$new_id}) | " . implode(' | ', $info));
        } catch (Throwable $e) {
            bef_log("EXCEPTION for {$email}: " . $e->getMessage());
        }
    }

    header("Location: $back_page?success=1");
} else {
    header("Location: $back_page?error=" . urlencode('ডাটাবেসে সেভ করতে সমস্যা হয়েছে।'));
}
exit;
?>