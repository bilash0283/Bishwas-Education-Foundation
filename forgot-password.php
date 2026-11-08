<?php
/* =====================================================================
   forgot-password.php  (SINGLE FILE)
   Flow: Email check -> Send OTP -> OTP verify -> New password -> Success mail
   Mail jabe: info@bishwas.org theke (logo soho sundor design)
   ===================================================================== */
ob_start();
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Already login thakle portal e pathai dao
if (isset($_SESSION['user_id']) && isset($_SESSION['user_login_permission']) && $_SESSION['user_login_permission'] === true) {
    header("Location: portal/index.php");
    exit();
}

// ---------- SMTP SETTINGS (shudhu ekhane password din) ----------
$SMTP_HOST   = 'mail.bishwas.org';
$SMTP_PORT   = 465;                    // 465 = ssl, 587 = tls
$SMTP_SECURE = 'ssl';                  // 'ssl' othoba 'tls'
$SMTP_USER   = 'info@bishwas.org';
$SMTP_PASS   = 'YOUR_EMAIL_PASSWORD';  // <-- info@bishwas.org email-er asol password
$LOGIN_URL   = 'https://bishwas.org/login.php';

// ---------- DEBUG / TEST ----------
// $DEBUG = true thakle OTP pathate fail hole screen-e asol karon dekhabe. Thik hole false korun.
$DEBUG       = true;
// Test link: https://bishwas.org/forgot-password.php?mailtest=1&key=bef-test-2026&to=apnar@gmail.com
// Test shesh hole $TEST_KEY = ''; kore din
$TEST_KEY    = 'bef-test-2026';

@set_time_limit(90);

// SMTP password ekhane set kora na thakle member_registration.php theke auto niye nibe
if ($SMTP_PASS === 'YOUR_EMAIL_PASSWORD' || $SMTP_PASS === '') {
    $reg_file = __DIR__ . '/member_registration.php';
    if (is_file($reg_file)) {
        $reg_src = @file_get_contents($reg_file);
        if ($reg_src && preg_match('/\$SMTP_PASS\s*=\s*([\'"])(.*?)\1\s*;/s', $reg_src, $mm)
            && $mm[2] !== 'YOUR_EMAIL_PASSWORD' && $mm[2] !== '') {
            $SMTP_PASS = $mm[2];
        }
        if ($reg_src && preg_match('/\$SMTP_HOST\s*=\s*([\'"])(.*?)\1\s*;/s', $reg_src, $mm))   $SMTP_HOST   = $mm[2];
        if ($reg_src && preg_match('/\$SMTP_PORT\s*=\s*(\d+)\s*;/', $reg_src, $mm))             $SMTP_PORT   = (int)$mm[1];
        if ($reg_src && preg_match('/\$SMTP_SECURE\s*=\s*([\'"])(.*?)\1\s*;/s', $reg_src, $mm)) $SMTP_SECURE = $mm[2];
    }
}

// ---------- LOGO ----------
$LOGO_PATH   = '';   // optional: __DIR__ . '/public/assets/logo.png'
$LOGO_URL    = '';   // optional fallback: https://bishwas.org/public/assets/logo.png

// ---------- OTP SETTINGS ----------
$OTP_VALID_SECONDS = 600;   // OTP 10 minute valid
$OTP_RESEND_WAIT   = 60;    // porer OTP chaite 60 sec lagbe
$OTP_MAX_PER_HOUR  = 5;     // ek ghontay maximum 5 bar OTP
$OTP_MAX_ATTEMPTS  = 5;     // vul OTP maximum 5 bar
$MIN_PASSWORD_LEN  = 6;

/* =====================================================================
   Helper functions
   ===================================================================== */
if (!function_exists('bef_log')) {
    function bef_log($msg) {
        @file_put_contents(__DIR__ . '/mail_debug.log', '[' . date('Y-m-d H:i:s') . '] ' . $msg . "\n", FILE_APPEND);
        error_log('BEF MAIL: ' . $msg);
    }
}

if (!function_exists('bef_find_logo')) {
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
}

if (!function_exists('bef_smtp_try')) {
    /* $secure: 'ssl' (implicit TLS) | 'tls' (STARTTLS) | 'none' (plain). $user === '' hole auth ba login kora hobe na */
    function bef_smtp_try($host, $port, $secure, $user, $pass, $to, $raw, &$err, $from = '') {
        $ctx = stream_context_create(['ssl' => [
            'verify_peer' => false, 'verify_peer_name' => false, 'allow_self_signed' => true
        ]]);
        if ($from === '') $from = $user;
        $remote = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $fp = @stream_socket_client($remote, $errno, $errstr, 6, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) { $err = "connect failed: {$errstr} ({$errno})"; return false; }
        stream_set_timeout($fp, 10);

        $last = '';
        $read = function () use ($fp) {
            $d = '';
            while (($l = fgets($fp, 515)) !== false) {
                $d .= $l;
                if (strlen($l) < 4 || $l[3] === ' ') break;
            }
            return $d;
        };
        $cmd = function ($c, $okCodes, $label) use ($fp, $read, &$err, &$last) {
            if ($c !== '') fwrite($fp, $c . "\r\n");
            $r = $read();
            $last = $r;
            if (!in_array((int)substr($r, 0, 3), $okCodes, true)) {
                $err = "[{$label}] " . (trim($r) !== '' ? trim($r) : 'no response / timeout');
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

        // Auth (shudhu user deya thakle)
        if ($ok && $user !== '') {
            $caps = strtoupper($last);
            if (strpos($caps, 'AUTH') !== false && strpos($caps, 'LOGIN') === false && strpos($caps, 'PLAIN') !== false) {
                $ok = $cmd('AUTH PLAIN ' . base64_encode("\0" . $user . "\0" . $pass), [235], 'AUTH PLAIN');
            } else {
                $ok = $cmd('AUTH LOGIN', [334], 'AUTH')
                   && $cmd(base64_encode($user), [334], 'AUTH user')
                   && $cmd(base64_encode($pass), [235], 'AUTH password');
            }
        }

        $ok = $ok
           && $cmd("MAIL FROM:<{$from}>", [250], 'MAIL FROM')
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
}

if (!function_exists('bef_send_mail')) {
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
        $raw_full = $headers . "\r\n" . $body;
        $t0 = microtime(true);
        $has_pass = ($cfg['pass'] !== 'YOUR_EMAIL_PASSWORD' && $cfg['pass'] !== '');
        $auth_bad = false;

        if (!$has_pass) {
            $info[] = 'WARNING: SMTP password set kora nai ($SMTP_PASS placeholder-i royeche) - login chara try korchi';
        }

        // [host, port, mode, authenticated?]
        $attempts = [];
        if ($has_pass) {
            $attempts[] = [$cfg['host'], (int)$cfg['port'], $cfg['secure'], true];
            $attempts[] = [$cfg['host'], 587, 'tls', true];
            $attempts[] = ['localhost', 465, 'ssl', true];
            $attempts[] = ['localhost', 587, 'tls', true];
            $attempts[] = ['localhost', 25, 'tls', true];
            $attempts[] = ['bishwas.org', 465, 'ssl', true];
        }
        // Login chara local server (cPanel-e onek somoy kaj kore)
        $attempts[] = ['localhost', 25, 'none', false];
        $attempts[] = ['127.0.0.1', 25, 'none', false];

        $seen = [];
        foreach ($attempts as $a) {
            list($h, $p, $mode, $useAuth) = $a;
            $key = "{$h}:{$p}:{$mode}:" . ($useAuth ? 'a' : 'n');
            if (isset($seen[$key])) continue;
            $seen[$key] = true;

            if ($useAuth && $auth_bad) continue;                       // password vul hole authenticated try kore lav nai
            if ((microtime(true) - $t0) > 24) { $info[] = 'time budget shesh, baki SMTP attempt skip'; break; }

            $err = '';
            $u = $useAuth ? $cfg['user'] : '';
            $label = "{$h}:{$p} ({$mode}" . ($useAuth ? ', auth' : ', no-auth') . ')';
            if (bef_smtp_try($h, $p, $mode, $u, $useAuth ? $cfg['pass'] : '', $to, $raw_full, $err, $cfg['user'])) {
                $info[] = "SMTP OK via {$label}";
                return true;
            }
            $info[] = "SMTP FAIL {$label} -> {$err}";
            if ($useAuth && (strpos($err, '535') !== false || stripos($err, 'authentication') !== false)) {
                $info[] = 'Email password vul. $SMTP_PASS thik korun (info@bishwas.org-er webmail login password).';
                $auth_bad = true;
            }
        }

        // Fallback: PHP mail() (age -f soho, na hole -f charu)
        $h2 = preg_replace('/^(To|Subject): .*\r\n/m', '', $headers);
        $ok = @mail($to, $encSubj, $body, $h2, '-f' . $from);
        $info[] = 'mail() with -f: ' . ($ok ? 'server accepted' : 'FAILED');
        if (!$ok) {
            $ok = @mail($to, $encSubj, $body, $h2);
            $info[] = 'mail() without -f: ' . ($ok ? 'server accepted' : 'FAILED');
        }
        if (!$ok && !function_exists('mail')) { $info[] = 'mail() function disabled on this server'; }
        return $ok;
    }
}

/* ---------------------------------------------------------------------
   Common email layout (logo + header + body + footer)
   $inner = already-escaped HTML
   --------------------------------------------------------------------- */
function bef_mail_layout($logo_src, $heading, $subheading, $iconHtml, $iconBg, $inner, $addr, $phone, $cmail) {
    $h = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
    $logo = $h($logo_src); $addr = $h($addr); $phone = $h($phone); $cmail = $h($cmail);
    $heading = $h($heading); $subheading = $h($subheading);
    $year = date('Y');

    $logoBlock = $logo !== ''
        ? '<table role="presentation" cellpadding="0" cellspacing="0"><tr><td align="center" style="background:#ffffff;border-radius:50%;padding:8px;width:84px;height:84px;"><img src="' . $logo . '" alt="Bishwas Education Foundation" width="84" height="84" style="display:block;border-radius:50%;object-fit:contain;"></td></tr></table>'
        : '';

    return <<<HTML
<!DOCTYPE html>
<html lang="bn"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>{$heading}</title></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:'Segoe UI',Tahoma,Arial,sans-serif;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 0;"><tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 24px rgba(15,23,42,0.08);">

  <tr><td align="center" bgcolor="#0f172a" style="background:#0f172a;background-image:linear-gradient(135deg,#0f172a 0%,#1e3a5f 55%,#0369a1 100%);padding:36px 24px 30px;">
    {$logoBlock}
    <h1 style="margin:18px 0 4px;font-size:22px;line-height:1.3;color:#ffffff;letter-spacing:0.5px;text-transform:uppercase;">Bishwas Education Foundation</h1>
    <p style="margin:0;font-size:13px;color:#bae6fd;">Official Member Portal</p>
  </td></tr>

  <tr><td align="center" style="padding:32px 32px 8px;">
    <table role="presentation" cellpadding="0" cellspacing="0"><tr><td align="center" style="width:56px;height:56px;border-radius:50%;background:{$iconBg};font-size:28px;font-weight:bold;line-height:56px;color:#059669;">{$iconHtml}</td></tr></table>
    <h2 style="margin:16px 0 6px;font-size:22px;color:#0f172a;">{$heading}</h2>
    <p style="margin:0;font-size:15px;color:#475569;">{$subheading}</p>
  </td></tr>

  <tr><td style="padding:20px 32px 0;font-size:15px;line-height:1.7;color:#334155;">
    {$inner}
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
        &copy; {$year} Bishwas Education Foundation. All rights reserved.
      </td></tr>
    </table>
  </td></tr>

</table>
</td></tr></table>
</body></html>
HTML;
}

function bef_otp_mail_html($name, $otp, $minutes, $logo_src, $addr, $phone, $cmail) {
    $h = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
    $name = $h($name); $otp = $h($otp); $minutes = (int)$minutes;

    $inner = '
    <p style="margin:0 0 10px;">প্রিয় <strong style="color:#0f172a;">' . $name . '</strong>,</p>
    <p style="margin:0 0 18px;">আপনার অ্যাকাউন্টের পাসওয়ার্ড রিসেট করার অনুরোধ পাওয়া গেছে। নিচের ৬ ডিজিটের OTP কোডটি ব্যবহার করে আপনার পরিচয় যাচাই করুন।</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:12px;">
      <tr><td align="center" style="padding:22px 16px;">
        <div style="font-size:12px;font-weight:bold;letter-spacing:1px;color:#1d4ed8;text-transform:uppercase;margin-bottom:10px;">Your One-Time Password</div>
        <div style="display:inline-block;background:#ffffff;border:2px dashed #60a5fa;border-radius:10px;padding:10px 26px;font-family:Consolas,\'Courier New\',monospace;font-size:34px;font-weight:bold;color:#0f172a;letter-spacing:10px;">' . $otp . '</div>
        <div style="margin-top:12px;font-size:13px;color:#64748b;">এই কোডটি <strong>' . $minutes . ' মিনিট</strong> পর্যন্ত কার্যকর থাকবে।</div>
      </td></tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:20px;background:#fffbeb;border:1px solid #fde68a;border-radius:10px;">
      <tr><td style="padding:12px 16px;font-size:13px;line-height:1.6;color:#92400e;"><strong>নিরাপত্তা পরামর্শ:</strong> এই OTP কারো সাথে শেয়ার করবেন না। বিশ্বাস এডুকেশন ফাউন্ডেশনের কোনো প্রতিনিধি কখনো আপনার OTP চাইবেন না।</td></tr>
    </table>

    <p style="margin:20px 0 0;font-size:13px;color:#94a3b8;">আপনি যদি পাসওয়ার্ড রিসেটের অনুরোধ না করে থাকেন, তবে এই ইমেইলটি উপেক্ষা করুন। আপনার পাসওয়ার্ড অপরিবর্তিত থাকবে।</p>';

    return bef_mail_layout($logo_src, 'Password Reset OTP', 'আপনার পাসওয়ার্ড রিসেট কোড', '&#128273;', '#dbeafe', $inner, $addr, $phone, $cmail);
}

function bef_success_mail_html($name, $loginUrl, $when, $ip, $logo_src, $addr, $phone, $cmail) {
    $h = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
    $name = $h($name); $loginUrl = $h($loginUrl); $when = $h($when); $ip = $h($ip); $cmail_placeholder_fix = $h($cmail);

    $inner = '
    <p style="margin:0 0 10px;">প্রিয় <strong style="color:#0f172a;">' . $name . '</strong>,</p>
    <p style="margin:0 0 18px;">আপনার বিশ্বাস এডুকেশন ফাউন্ডেশন অ্যাকাউন্টের পাসওয়ার্ড <strong style="color:#059669;">সফলভাবে পরিবর্তন</strong> করা হয়েছে। এখন থেকে নতুন পাসওয়ার্ড দিয়ে লগইন করতে পারবেন।</p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:12px;">
      <tr><td style="padding:14px 18px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:14px;color:#334155;">
          <tr><td style="padding:5px 0;color:#64748b;width:35%;">পরিবর্তনের সময়</td><td style="padding:5px 0;font-weight:bold;color:#0f172a;">' . $when . '</td></tr>
          <tr><td style="padding:5px 0;color:#64748b;">IP Address</td><td style="padding:5px 0;font-weight:bold;color:#0f172a;">' . $ip . '</td></tr>
          <tr><td style="padding:5px 0;color:#64748b;">Status</td><td style="padding:5px 0;"><span style="background:#d1fae5;color:#047857;font-size:12px;font-weight:bold;padding:3px 10px;border-radius:20px;">Password Changed</span></td></tr>
        </table>
      </td></tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr><td align="center" style="padding:24px 0 0;">
      <a href="' . $loginUrl . '" style="display:inline-block;background:#0369a1;background-image:linear-gradient(90deg,#0369a1,#0284c7);color:#ffffff;text-decoration:none;font-weight:bold;font-size:15px;padding:14px 38px;border-radius:10px;">Login to Member Portal &rarr;</a>
    </td></tr></table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:22px;background:#fef2f2;border:1px solid #fecaca;border-radius:10px;">
      <tr><td style="padding:12px 16px;font-size:13px;line-height:1.6;color:#991b1b;"><strong>এটি আপনি না করে থাকলে?</strong> অনুগ্রহ করে এখনই <a href="mailto:' . $cmail_placeholder_fix . '" style="color:#991b1b;">আমাদের</a> সাথে যোগাযোগ করুন, যাতে আপনার অ্যাকাউন্ট সুরক্ষিত করা যায়।</td></tr>
    </table>';

    return bef_mail_layout($logo_src, 'Password Changed Successfully', 'আপনার পাসওয়ার্ড সফলভাবে পরিবর্তন হয়েছে', '&#10003;', '#d1fae5', $inner, $addr, $phone, $cmail);
}

function bef_json($ok, $msg = '', $extra = []) {
    while (ob_get_level() > 0) { ob_end_clean(); }
    if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');
    $json = json_encode(array_merge(['ok' => $ok, 'msg' => $msg], $extra), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    echo $json !== false ? $json : '{"ok":false,"msg":"Response encode error"}';
    exit;
}

function bef_mask_email($e) {
    $parts = explode('@', $e, 2);
    if (count($parts) !== 2) return $e;
    $l = $parts[0];
    $keep = substr($l, 0, 2);
    return $keep . str_repeat('*', max(1, strlen($l) - 2)) . '@' . $parts[1];
}

/* =====================================================================
   Header include ($db, $favicon_icon ashbe)
   ===================================================================== */
include 'include/header.php';
$conn = $db;
mysqli_set_charset($db, "utf8mb4");

// ---------- Table auto create ----------
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);   // DB error hole exception (catch kora jabe)
$PR_SQL = "CREATE TABLE IF NOT EXISTS `password_resets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `email` VARCHAR(190) NOT NULL,
    `otp_hash` VARCHAR(64) NOT NULL,
    `attempts` TINYINT NOT NULL DEFAULT 0,
    `verified` TINYINT(1) NOT NULL DEFAULT 0,
    `used` TINYINT(1) NOT NULL DEFAULT 0,
    `expires_at` INT NOT NULL,
    `created_at` INT NOT NULL,
    `ip` VARCHAR(45) DEFAULT NULL,
    INDEX (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
$PR_ERR = '';
try {
    mysqli_query($db, $PR_SQL);
    mysqli_query($db, "SELECT 1 FROM `password_resets` LIMIT 1");
} catch (Throwable $e) {
    $PR_ERR = $e->getMessage();
    bef_log('password_resets table problem: ' . $PR_ERR);
}

// ---------- Logo ----------
$fav       = (isset($favicon_icon) && $favicon_icon !== '') ? $favicon_icon : '';
$logo_file = bef_find_logo($fav, $LOGO_PATH);
if ($logo_file) {
    $logo_src = 'cid:beflogo';
} elseif ($LOGO_URL !== '') {
    $logo_src = $LOGO_URL;
} else {
    $logo_src = $fav !== '' ? 'https://bishwas.org/public/assets/' . rawurlencode($fav) : '';
}

$mail_cfg = ['host' => $SMTP_HOST, 'port' => $SMTP_PORT, 'secure' => $SMTP_SECURE, 'user' => $SMTP_USER, 'pass' => $SMTP_PASS];

// ---------- Contact info (email footer) ----------
$c_addr  = '১/জি/১০/১, মীরবাগ হাতিরঝিল, নতুন রাস্তা, ৩ নং লেন, ঢাকা-১২১৭, বাংলাদেশ';
$c_phone = '+৮৮০ ১৭১৫-৪৮২৩৬৩';
$c_mail  = 'info@bishwas.org';
try {
    $cr = mysqli_query($db, "SELECT * FROM contact_settings WHERE id = 1 LIMIT 1");
    if ($cr && ($crow = mysqli_fetch_assoc($cr))) {
        if (!empty($crow['office_address'])) $c_addr  = $crow['office_address'];
        if (!empty($crow['phone_number']))   $c_phone = $crow['phone_number'];
        if (!empty($crow['email_address']))  $c_mail  = $crow['email_address'];
    }
} catch (Throwable $e) { /* contact_settings na thakle default footer */ }

// ---------- CSRF ----------
if (empty($_SESSION['fp_csrf'])) {
    $_SESSION['fp_csrf'] = bin2hex(random_bytes(16));
}
$csrf = $_SESSION['fp_csrf'];

/* =====================================================================
   MAIL TEST MODE  (forgot-password.php?mailtest=1&key=...&to=...)
   ===================================================================== */
if (isset($_GET['mailtest']) && $TEST_KEY !== '' && ($_GET['key'] ?? '') === $TEST_KEY) {
    while (ob_get_level() > 0) { ob_end_clean(); }
    header('Content-Type: text/plain; charset=utf-8');
    $to = trim($_GET['to'] ?? '');
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) { exit("&to=apnar@gmail.com dite hobe\n"); }

    echo "PHP version : " . PHP_VERSION . "\n";
    echo "OpenSSL     : " . (extension_loaded('openssl') ? 'yes' : 'NO (ssl smtp kaj korbe na)') . "\n";
    echo "SMTP        : {$SMTP_HOST}:{$SMTP_PORT} ({$SMTP_SECURE}) user={$SMTP_USER}\n";
    echo "Password    : " . (($SMTP_PASS === 'YOUR_EMAIL_PASSWORD' || $SMTP_PASS === '') ? 'NOT SET  <-- ekhanei somossa!' : 'set') . "\n";
    echo "Logo        : " . ($logo_file ? "embedded ({$logo_file})" : "link ({$logo_src})") . "\n";
    echo "DB table    : " . ($PR_ERR === '' ? 'password_resets OK' : 'PROBLEM: ' . $PR_ERR) . "\n\n";

    $html = bef_otp_mail_html('Test Member', '123456', 10, $logo_src, $c_addr, $c_phone, $c_mail);
    $info = [];
    $ok = bef_send_mail($to, 'Test OTP - Bishwas Education Foundation', $html, "Test OTP: 123456", $mail_cfg, $logo_file, $info);

    foreach ($info as $line) echo "- {$line}\n";
    echo "\nRESULT: " . ($ok ? "SENT -> {$to} (Inbox + Spam dekhun)" : "FAILED (upore karon dekhun)") . "\n";
    exit;
}

/* =====================================================================
   AJAX handler
   ===================================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    // Fatal error hole-o JSON diye janabe
    register_shutdown_function(function () use ($DEBUG) {
        $e = error_get_last();
        if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
            bef_log('FATAL: ' . $e['message'] . ' @ ' . $e['file'] . ':' . $e['line']);
            bef_json(false, 'সার্ভারে সমস্যা হয়েছে।', $DEBUG ? ['debug' => $e['message'] . ' (' . basename($e['file']) . ':' . $e['line'] . ')'] : []);
        }
    });
    try {
        if (!hash_equals($_SESSION['fp_csrf'], (string)($_POST['csrf'] ?? ''))) {
            bef_json(false, 'সেশন মেয়াদোত্তীর্ণ হয়েছে। পেজটি রিফ্রেশ করে আবার চেষ্টা করুন।');
        }

        $action = $_POST['action'];
        $now    = time();
        $ip     = $_SERVER['REMOTE_ADDR'] ?? '';

        /* ---------- 1. Email check ---------- */
        if ($action === 'check_email') {
            $email = trim($_POST['email'] ?? '');
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                bef_json(false, 'সঠিক ইমেইল ঠিকানা লিখুন।');
            }

            $st = mysqli_prepare($db, "SELECT id, email FROM users WHERE email = ? LIMIT 1");
            mysqli_stmt_bind_param($st, "s", $email);
            mysqli_stmt_execute($st);
            $res = mysqli_stmt_get_result($st);
            $u = mysqli_fetch_assoc($res);
            mysqli_stmt_close($st);

            if (!$u) {
                bef_json(false, 'এই ইমেইল ঠিকানা দিয়ে কোনো অ্যাকাউন্ট পাওয়া যায়নি।');
            }

            unset($_SESSION['fp_reset_id']);
            $_SESSION['fp_email'] = $u['email'];
            $_SESSION['fp_uid']   = (int)$u['id'];

            bef_json(true, 'ইমেইলটি পাওয়া গেছে।', ['masked' => bef_mask_email($u['email'])]);
        }

        // Baki action gulo-r jonno email session lagbe
        if (empty($_SESSION['fp_email']) || empty($_SESSION['fp_uid'])) {
            bef_json(false, 'প্রথমে আপনার ইমেইল যাচাই করুন।');
        }
        $email = $_SESSION['fp_email'];
        $uid   = (int)$_SESSION['fp_uid'];

        /* ---------- 2. Send OTP ---------- */
        if ($action === 'send_otp') {
            if ($PR_ERR !== '') {
                bef_json(false, 'ডাটাবেসে password_resets টেবিল তৈরি করা যায়নি।', $DEBUG ? ['debug' => $PR_ERR . "\n\nphpMyAdmin-e ei SQL ti manually run korun:\n" . $PR_SQL] : []);
            }
            // Rate limit: last 1 hour
            $since = $now - 3600;
            $st = mysqli_prepare($db, "SELECT COUNT(*) AS c, MAX(created_at) AS last_at FROM password_resets WHERE email = ? AND created_at > ?");
            mysqli_stmt_bind_param($st, "si", $email, $since);
            mysqli_stmt_execute($st);
            $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
            mysqli_stmt_close($st);

            if ((int)$row['c'] >= $OTP_MAX_PER_HOUR) {
                bef_json(false, 'অনেকবার OTP চাওয়া হয়েছে। অনুগ্রহ করে ১ ঘণ্টা পর আবার চেষ্টা করুন।');
            }
            if (!empty($row['last_at']) && ($now - (int)$row['last_at']) < $OTP_RESEND_WAIT) {
                $wait = $OTP_RESEND_WAIT - ($now - (int)$row['last_at']);
                bef_json(false, "অনুগ্রহ করে {$wait} সেকেন্ড পর আবার চেষ্টা করুন।", ['wait' => $wait]);
            }

            // User name
            $st = mysqli_prepare($db, "SELECT member_name FROM users WHERE id = ? LIMIT 1");
            mysqli_stmt_bind_param($st, "i", $uid);
            mysqli_stmt_execute($st);
            $urow = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
            mysqli_stmt_close($st);
            $member_name = $urow['member_name'] ?? 'সদস্য';

            // Purono OTP gulo bondho
            $st = mysqli_prepare($db, "UPDATE password_resets SET used = 1 WHERE email = ? AND used = 0");
            mysqli_stmt_bind_param($st, "s", $email);
            mysqli_stmt_execute($st);
            mysqli_stmt_close($st);

            // New OTP
            $otp      = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
            $otp_hash = hash('sha256', $otp . '|' . strtolower($email) . '|bef_otp_salt');
            $expires  = $now + $OTP_VALID_SECONDS;

            $st = mysqli_prepare($db, "INSERT INTO password_resets (user_id, email, otp_hash, expires_at, created_at, ip) VALUES (?, ?, ?, ?, ?, ?)");
            mysqli_stmt_bind_param($st, "issiis", $uid, $email, $otp_hash, $expires, $now, $ip);
            mysqli_stmt_execute($st);
            $reset_id = mysqli_insert_id($db);
            mysqli_stmt_close($st);

            // Mail pathao
            $html = bef_otp_mail_html($member_name, $otp, (int)($OTP_VALID_SECONDS / 60), $logo_src, $c_addr, $c_phone, $c_mail);
            $text = "Password Reset OTP - Bishwas Education Foundation\n\n"
                  . "Dear {$member_name},\n\nYour OTP is: {$otp}\nValid for " . (int)($OTP_VALID_SECONDS / 60) . " minutes.\n\n"
                  . "Do not share this code with anyone.\nIf you did not request this, please ignore this email.\n\nBishwas Education Foundation\ninfo@bishwas.org";

            $info = [];
            $sent = bef_send_mail($email, 'Your Password Reset OTP - Bishwas Education Foundation', $html, $text, $mail_cfg, $logo_file, $info);
            bef_log(($sent ? 'OTP SENT' : 'OTP FAILED') . " to {$email} (user #{$uid}) | " . implode(' | ', $info));

            if (!$sent) {
                $st = mysqli_prepare($db, "DELETE FROM password_resets WHERE id = ?");
                mysqli_stmt_bind_param($st, "i", $reset_id);
                mysqli_stmt_execute($st);
                mysqli_stmt_close($st);
                bef_json(false, 'ইমেইল পাঠানো সম্ভব হয়নি। কিছুক্ষণ পর আবার চেষ্টা করুন অথবা অ্যাডমিনের সাথে যোগাযোগ করুন।',
                    $DEBUG ? ['debug' => implode("\n", $info)] : []);
            }

            bef_json(true, 'OTP পাঠানো হয়েছে।', ['masked' => bef_mask_email($email), 'wait' => $OTP_RESEND_WAIT]);
        }

        /* ---------- 3. Verify OTP ---------- */
        if ($action === 'verify_otp') {
            $otp = trim($_POST['otp'] ?? '');
            if (!preg_match('/^[0-9]{6}$/', $otp)) {
                bef_json(false, '৬ ডিজিটের সম্পূর্ণ OTP লিখুন।');
            }

            $st = mysqli_prepare($db, "SELECT id, otp_hash, attempts, expires_at FROM password_resets WHERE email = ? AND used = 0 ORDER BY id DESC LIMIT 1");
            mysqli_stmt_bind_param($st, "s", $email);
            mysqli_stmt_execute($st);
            $r = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
            mysqli_stmt_close($st);

            if (!$r) {
                bef_json(false, 'কোনো সক্রিয় OTP পাওয়া যায়নি। নতুন OTP চেয়ে নিন।', ['expired' => true]);
            }
            if ($now > (int)$r['expires_at']) {
                bef_json(false, 'OTP-এর মেয়াদ শেষ হয়ে গেছে। নতুন OTP চেয়ে নিন।', ['expired' => true]);
            }
            if ((int)$r['attempts'] >= $OTP_MAX_ATTEMPTS) {
                bef_json(false, 'অনেকবার ভুল OTP দেওয়া হয়েছে। নতুন OTP চেয়ে নিন।', ['expired' => true]);
            }

            // Attempt count baro
            $st = mysqli_prepare($db, "UPDATE password_resets SET attempts = attempts + 1 WHERE id = ?");
            mysqli_stmt_bind_param($st, "i", $r['id']);
            mysqli_stmt_execute($st);
            mysqli_stmt_close($st);

            $given = hash('sha256', $otp . '|' . strtolower($email) . '|bef_otp_salt');
            if (!hash_equals($r['otp_hash'], $given)) {
                $left = $OTP_MAX_ATTEMPTS - ((int)$r['attempts'] + 1);
                if ($left <= 0) {
                    bef_json(false, 'ভুল OTP। আপনার সব চেষ্টা শেষ, নতুন OTP চেয়ে নিন।', ['expired' => true]);
                }
                bef_json(false, "ভুল OTP দেওয়া হয়েছে। আর {$left} বার চেষ্টা করতে পারবেন।");
            }

            $st = mysqli_prepare($db, "UPDATE password_resets SET verified = 1 WHERE id = ?");
            mysqli_stmt_bind_param($st, "i", $r['id']);
            mysqli_stmt_execute($st);
            mysqli_stmt_close($st);

            $_SESSION['fp_reset_id'] = (int)$r['id'];
            bef_json(true, 'OTP সফলভাবে যাচাই হয়েছে।');
        }

        /* ---------- 4. Reset password ---------- */
        if ($action === 'reset_password') {
            if (empty($_SESSION['fp_reset_id'])) {
                bef_json(false, 'প্রথমে OTP যাচাই করুন।', ['restart' => true]);
            }
            $rid = (int)$_SESSION['fp_reset_id'];

            $st = mysqli_prepare($db, "SELECT id, expires_at FROM password_resets WHERE id = ? AND email = ? AND verified = 1 AND used = 0 LIMIT 1");
            mysqli_stmt_bind_param($st, "is", $rid, $email);
            mysqli_stmt_execute($st);
            $r = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
            mysqli_stmt_close($st);

            if (!$r || $now > ((int)$r['expires_at'] + 600)) {
                unset($_SESSION['fp_reset_id']);
                bef_json(false, 'সেশনের মেয়াদ শেষ হয়েছে। অনুগ্রহ করে শুরু থেকে আবার চেষ্টা করুন।', ['restart' => true]);
            }

            $p1 = (string)($_POST['password'] ?? '');
            $p2 = (string)($_POST['confirm_password'] ?? '');

            if (strlen($p1) < $MIN_PASSWORD_LEN) {
                bef_json(false, "পাসওয়ার্ড কমপক্ষে {$MIN_PASSWORD_LEN} অক্ষরের হতে হবে।");
            }
            if ($p1 !== $p2) {
                bef_json(false, 'পাসওয়ার্ড এবং কনফার্ম পাসওয়ার্ড মিলছে না।');
            }

            // Login system md5 use kore, tai md5 e save
            $new_hash = md5($p1);
            $st = mysqli_prepare($db, "UPDATE users SET password = ? WHERE id = ?");
            mysqli_stmt_bind_param($st, "si", $new_hash, $uid);
            mysqli_stmt_execute($st);
            mysqli_stmt_close($st);

            $st = mysqli_prepare($db, "UPDATE password_resets SET used = 1 WHERE email = ?");
            mysqli_stmt_bind_param($st, "s", $email);
            mysqli_stmt_execute($st);
            mysqli_stmt_close($st);

            // Success mail
            try {
                $st = mysqli_prepare($db, "SELECT member_name FROM users WHERE id = ? LIMIT 1");
                mysqli_stmt_bind_param($st, "i", $uid);
                mysqli_stmt_execute($st);
                $urow = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
                mysqli_stmt_close($st);
                $member_name = $urow['member_name'] ?? 'সদস্য';

                $when = date('d M Y, h:i A');
                $html = bef_success_mail_html($member_name, $LOGIN_URL, $when, $ip, $logo_src, $c_addr, $c_phone, $c_mail);
                $text = "Password Changed Successfully - Bishwas Education Foundation\n\n"
                      . "Dear {$member_name},\n\nYour password has been changed successfully on {$when}.\n"
                      . "Login: {$LOGIN_URL}\n\nIf this was not you, please contact {$c_mail} immediately.\n\nBishwas Education Foundation";
                $info = [];
                $sent = bef_send_mail($email, 'Password Changed Successfully - Bishwas Education Foundation', $html, $text, $mail_cfg, $logo_file, $info);
                bef_log(($sent ? 'SUCCESS MAIL SENT' : 'SUCCESS MAIL FAILED') . " to {$email} (user #{$uid}) | " . implode(' | ', $info));
            } catch (Throwable $e) {
                bef_log("EXCEPTION success mail {$email}: " . $e->getMessage());
            }

            unset($_SESSION['fp_email'], $_SESSION['fp_uid'], $_SESSION['fp_reset_id']);
            $_SESSION['fp_csrf'] = bin2hex(random_bytes(16));
            bef_json(true, 'পাসওয়ার্ড সফলভাবে পরিবর্তন হয়েছে।');
        }

        bef_json(false, 'অবৈধ অনুরোধ।');

    } catch (Throwable $e) {
        bef_log('EXCEPTION forgot-password: ' . $e->getMessage() . ' @ line ' . $e->getLine());
        bef_json(false, 'সার্ভারে সমস্যা হয়েছে। অনুগ্রহ করে আবার চেষ্টা করুন।',
            $DEBUG ? ['debug' => $e->getMessage() . ' (line ' . $e->getLine() . ')'] : []);
    }
}

// Normal page load: purono session reset
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    unset($_SESSION['fp_email'], $_SESSION['fp_uid'], $_SESSION['fp_reset_id']);
}
?>

<style>
    @keyframes fpFadeUp { from {opacity:0; transform:translateY(12px);} to {opacity:1; transform:translateY(0);} }
    .fp-anim { animation: fpFadeUp .35s ease both; }
    .otp-box:focus { box-shadow: 0 0 0 3px rgba(5,150,105,.18); }
    .fp-spin { animation: fpSpin 1s linear infinite; }
    @keyframes fpSpin { to { transform: rotate(360deg); } }
    @keyframes fpPop { 0%{transform:scale(.4);opacity:0} 70%{transform:scale(1.12)} 100%{transform:scale(1);opacity:1} }
    .fp-pop { animation: fpPop .5s ease both; }
</style>

<div class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="w-full max-w-md bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden">

        <!-- Top banner -->
        <div class="bg-gradient-to-br from-emerald-800 via-emerald-700 to-teal-600 px-6 pt-7 pb-6 text-center text-white relative">
            <div class="mx-auto w-14 h-14 rounded-2xl bg-white/15 backdrop-blur flex items-center justify-center mb-3 ring-1 ring-white/25">
                <i id="topIcon" class="fa-solid fa-lock text-2xl"></i>
            </div>
            <h1 id="topTitle" class="text-lg md:text-xl font-bold">পাসওয়ার্ড ভুলে গেছেন?</h1>
            <p id="topSub" class="text-xs md:text-sm text-emerald-100 mt-1">চিন্তা নেই! আপনার ইমেইল দিয়ে পাসওয়ার্ড রিসেট করুন।</p>

            <!-- Step indicator -->
            <div id="stepper" class="flex items-center justify-center gap-2 mt-5">
                <div class="step-dot flex items-center gap-2" data-step="1">
                    <span class="dot w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center bg-white text-emerald-800">1</span>
                </div>
                <span class="w-8 h-0.5 bg-white/30 rounded"></span>
                <div class="step-dot" data-step="2">
                    <span class="dot w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center bg-white/20 text-white">2</span>
                </div>
                <span class="w-8 h-0.5 bg-white/30 rounded"></span>
                <div class="step-dot" data-step="3">
                    <span class="dot w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center bg-white/20 text-white">3</span>
                </div>
            </div>
        </div>

        <div class="p-6 md:p-7">

            <!-- Alert -->
            <div id="alertBox" class="hidden mb-4 p-3.5 rounded-xl text-xs font-semibold items-start gap-2 fp-anim">
                <i id="alertIcon" class="text-base shrink-0 mt-0.5"></i>
                <span id="alertMsg"></span>
            </div>

            <!-- ============ STEP 1: Email ============ -->
            <div id="panel1" class="fp-anim">
                <label class="block text-sm font-bold text-slate-700 mb-1.5">
                    আপনার ইমেইল ঠিকানা <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <i class="fa-regular fa-envelope absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 text-sm"></i>
                    <input type="email" id="emailInput" placeholder="আপনার নিবন্ধিত ইমেইল লিখুন" autocomplete="email"
                        class="w-full pl-10 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:bg-white transition-all placeholder:text-slate-400">
                </div>

                <button type="button" id="checkBtn"
                    class="mt-4 w-full inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-semibold rounded-lg shadow-md transition-all active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed">
                    <span>ইমেইল যাচাই করুন</span>
                    <i class="fa-solid fa-magnifying-glass text-sm"></i>
                </button>

                <!-- Found box -->
                <div id="foundBox" class="hidden mt-4 fp-anim">
                    <div class="p-4 rounded-xl bg-emerald-50 border border-emerald-200">
                        <div class="flex items-start gap-3">
                            <span class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center shrink-0">
                                <i class="fa-solid fa-circle-check"></i>
                            </span>
                            <div class="min-w-0">
                                <p class="text-sm font-bold text-emerald-800">ইমেইলটি পাওয়া গেছে!</p>
                                <p class="text-xs text-slate-600 mt-0.5 break-all" id="foundEmail"></p>
                                <p class="text-xs text-slate-500 mt-1.5">নিচের বাটনে ক্লিক করলে এই ইমেইলে একটি ৬ ডিজিটের OTP পাঠানো হবে।</p>
                            </div>
                        </div>
                        <button type="button" id="sendOtpBtn"
                            class="mt-3 w-full inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-semibold rounded-lg shadow-md transition-all active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed">
                            <i class="fa-regular fa-paper-plane text-sm"></i>
                            <span>OTP পাঠান</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- ============ STEP 2: OTP ============ -->
            <div id="panel2" class="hidden fp-anim">
                <div class="p-3.5 rounded-xl bg-sky-50 border border-sky-200 text-xs md:text-[13px] text-sky-900 leading-relaxed">
                    <div class="flex items-start gap-2.5">
                        <i class="fa-solid fa-envelope-circle-check text-sky-600 text-base mt-0.5 shrink-0"></i>
                        <div>
                            আপনার এই ইমেইল ঠিকানায় (<strong id="otpEmail" class="break-all"></strong>) একটি <strong>৬ ডিজিটের OTP</strong> পাঠানো হয়েছে।
                            অনুগ্রহ করে OTP-টি নিচে বসিয়ে পরবর্তী ধাপে যান।
                            <span class="block mt-1.5 text-sky-700"><i class="fa-solid fa-triangle-exclamation mr-1"></i>ইনবক্সের পাশাপাশি <strong>স্প্যাম (Spam) ফোল্ডারও</strong> দেখতে পারেন।</span>
                        </div>
                    </div>
                </div>

                <label class="block text-sm font-bold text-slate-700 mt-5 mb-2 text-center">OTP কোড লিখুন</label>
                <div id="otpWrap" class="flex items-center justify-center gap-2">
                    <?php for ($i = 0; $i < 6; $i++): ?>
                        <input type="text" inputmode="numeric" maxlength="1" autocomplete="one-time-code"
                            class="otp-box w-11 h-12 md:w-12 md:h-14 text-center text-xl font-bold text-slate-800 bg-slate-50 border-2 border-slate-200 rounded-xl focus:outline-none focus:border-emerald-600 focus:bg-white transition-all">
                    <?php endfor; ?>
                </div>

                <button type="button" id="verifyBtn"
                    class="mt-5 w-full inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-semibold rounded-lg shadow-md transition-all active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed">
                    <span>OTP যাচাই করুন</span>
                    <i class="fa-solid fa-arrow-right text-sm"></i>
                </button>

                <div class="flex items-center justify-between mt-4 text-xs">
                    <button type="button" id="changeEmailBtn" class="text-slate-500 hover:text-emerald-700 font-semibold transition-colors">
                        <i class="fa-solid fa-arrow-left mr-1"></i>ইমেইল পরিবর্তন
                    </button>
                    <button type="button" id="resendBtn" class="text-emerald-700 hover:text-emerald-900 font-semibold transition-colors disabled:text-slate-400 disabled:cursor-not-allowed" disabled>
                        আবার পাঠান <span id="resendTimer"></span>
                    </button>
                </div>
            </div>

            <!-- ============ STEP 3: New Password ============ -->
            <div id="panel3" class="hidden fp-anim">
                <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-800 flex items-center gap-2 mb-4">
                    <i class="fa-solid fa-shield-check text-emerald-600"></i>
                    <span>OTP যাচাই সফল হয়েছে! এখন আপনার নতুন পাসওয়ার্ড সেট করুন।</span>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">নতুন পাসওয়ার্ড <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="password" id="newPass" placeholder="কমপক্ষে <?php echo $MIN_PASSWORD_LEN; ?> অক্ষর" autocomplete="new-password"
                                class="w-full px-3.5 py-2.5 pr-10 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:bg-white transition-all placeholder:text-slate-400">
                            <button type="button" class="toggle-eye absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-emerald-600 transition-colors" data-target="newPass">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <div class="mt-2 flex items-center gap-2">
                            <div class="flex-1 h-1.5 bg-slate-200 rounded-full overflow-hidden">
                                <div id="strengthBar" class="h-full w-0 rounded-full transition-all duration-300"></div>
                            </div>
                            <span id="strengthText" class="text-[11px] font-semibold text-slate-400 w-14 text-right"></span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">কনফার্ম পাসওয়ার্ড <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <input type="password" id="confirmPass" placeholder="পাসওয়ার্ডটি আবার লিখুন" autocomplete="new-password"
                                class="w-full px-3.5 py-2.5 pr-10 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:bg-white transition-all placeholder:text-slate-400">
                            <button type="button" class="toggle-eye absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-emerald-600 transition-colors" data-target="confirmPass">
                                <i class="fa-solid fa-eye"></i>
                            </button>
                        </div>
                        <p id="matchText" class="text-[11px] mt-1.5 font-semibold h-4"></p>
                    </div>
                </div>

                <button type="button" id="resetBtn"
                    class="mt-5 w-full inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-semibold rounded-lg shadow-md transition-all active:scale-95 disabled:opacity-60 disabled:cursor-not-allowed">
                    <span>পাসওয়ার্ড পরিবর্তন করুন</span>
                    <i class="fa-solid fa-check text-sm"></i>
                </button>
            </div>

            <!-- ============ DONE ============ -->
            <div id="panelDone" class="hidden text-center py-2">
                <div class="fp-pop mx-auto w-20 h-20 rounded-full bg-emerald-100 flex items-center justify-center mb-4">
                    <i class="fa-solid fa-check text-4xl text-emerald-600"></i>
                </div>
                <h2 class="text-xl font-bold text-slate-800">পাসওয়ার্ড পরিবর্তন সফল!</h2>
                <p class="text-sm text-slate-500 mt-2 leading-relaxed">
                    আপনার পাসওয়ার্ড সফলভাবে পরিবর্তন করা হয়েছে।<br>
                    নিশ্চিতকরণের একটি ইমেইলও আপনার ইনবক্সে পাঠানো হয়েছে।
                </p>
                <a href="login.php"
                    class="mt-6 inline-flex items-center justify-center gap-2 px-8 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-semibold rounded-lg shadow-md transition-all active:scale-95">
                    <span>লগইন করুন</span>
                    <i class="fa-solid fa-arrow-right text-sm"></i>
                </a>
            </div>

            <!-- Back to login -->
            <div id="backLogin" class="text-center mt-6 pt-4 border-t border-slate-100">
                <a href="login.php" class="text-xs font-semibold text-slate-500 hover:text-emerald-700 transition-colors">
                    <i class="fa-solid fa-arrow-left mr-1"></i>লগইন পেজে ফিরে যান
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const CSRF = '<?php echo $csrf; ?>';
    const MIN_LEN = <?php echo (int)$MIN_PASSWORD_LEN; ?>;

    const $ = (id) => document.getElementById(id);
    const panels = { 1: $('panel1'), 2: $('panel2'), 3: $('panel3') };
    const titles = {
        1: ['fa-lock',        'পাসওয়ার্ড ভুলে গেছেন?', 'চিন্তা নেই! আপনার ইমেইল দিয়ে পাসওয়ার্ড রিসেট করুন।'],
        2: ['fa-envelope-open-text', 'OTP যাচাই করুন', 'আপনার ইমেইলে পাঠানো ৬ ডিজিটের কোডটি দিন।'],
        3: ['fa-key',         'নতুন পাসওয়ার্ড সেট করুন', 'একটি শক্তিশালী পাসওয়ার্ড বেছে নিন।']
    };

    const emailInput = $('emailInput'), checkBtn = $('checkBtn'), foundBox = $('foundBox'),
          sendOtpBtn = $('sendOtpBtn'), verifyBtn = $('verifyBtn'), resendBtn = $('resendBtn'),
          resetBtn = $('resetBtn');
    const otpBoxes = Array.from(document.querySelectorAll('.otp-box'));

    /* ---------- API ---------- */
    async function api(action, data) {
        const fd = new FormData();
        fd.append('action', action);
        fd.append('csrf', CSRF);
        Object.keys(data || {}).forEach(k => fd.append(k, data[k]));
        try {
            const r = await fetch(window.location.pathname, { method: 'POST', body: fd });
            const raw = await r.text();
            try {
                return JSON.parse(raw);
            } catch (je) {
                return { ok: false, msg: 'সার্ভার থেকে সঠিক উত্তর আসেনি।', debug: 'HTTP ' + r.status + ' | ' + raw.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').slice(0, 400) };
            }
        } catch (e) {
            return { ok: false, msg: 'নেটওয়ার্ক সমস্যা হয়েছে। ইন্টারনেট সংযোগ পরীক্ষা করে আবার চেষ্টা করুন।' };
        }
    }

    /* ---------- UI helpers ---------- */
    function showAlert(msg, type, debug) {
        const box = $('alertBox'), icon = $('alertIcon');
        box.className = 'mb-4 p-3.5 rounded-xl text-xs font-semibold flex items-start gap-2 fp-anim ' +
            (type === 'success' ? 'bg-emerald-50 border border-emerald-200 text-emerald-700'
                                : 'bg-rose-50 border border-rose-200 text-rose-600');
        icon.className = 'text-base shrink-0 mt-0.5 fa-solid ' + (type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation');
        const m = $('alertMsg');
        m.textContent = msg;
        if (debug) {
            const pre = document.createElement('pre');
            pre.style.cssText = 'margin-top:8px;padding:8px;background:#fff;border:1px dashed #fda4af;border-radius:8px;font-size:11px;font-weight:500;white-space:pre-wrap;word-break:break-all;color:#334155;max-height:160px;overflow:auto;';
            pre.textContent = 'DEBUG:\n' + debug;
            m.appendChild(pre);
        }
    }
    function hideAlert() { $('alertBox').className = 'hidden'; }

    function setLoading(btn, loading, label) {
        const span = btn.querySelector('span');
        if (loading) {
            btn.dataset.old = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-circle-notch fp-spin"></i><span>' + (label || 'অপেক্ষা করুন...') + '</span>';
        } else {
            btn.disabled = false;
            if (btn.dataset.old) btn.innerHTML = btn.dataset.old;
        }
    }

    function goStep(n) {
        hideAlert();
        Object.keys(panels).forEach(k => panels[k].classList.add('hidden'));
        panels[n].classList.remove('hidden');
        // header
        document.getElementById('topIcon').className = 'fa-solid ' + titles[n][0] + ' text-2xl';
        $('topTitle').textContent = titles[n][1];
        $('topSub').textContent = titles[n][2];
        // stepper
        document.querySelectorAll('#stepper .step-dot').forEach(el => {
            const s = parseInt(el.dataset.step, 10), dot = el.querySelector('.dot');
            if (s < n) {
                dot.className = 'dot w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center bg-emerald-300 text-emerald-900';
                dot.innerHTML = '<i class="fa-solid fa-check text-[10px]"></i>';
            } else if (s === n) {
                dot.className = 'dot w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center bg-white text-emerald-800';
                dot.textContent = s;
            } else {
                dot.className = 'dot w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center bg-white/20 text-white';
                dot.textContent = s;
            }
        });
    }

    /* ---------- Resend timer ---------- */
    let timerId = null;
    function startTimer(sec) {
        clearInterval(timerId);
        let left = sec;
        resendBtn.disabled = true;
        $('resendTimer').textContent = '(' + left + 's)';
        timerId = setInterval(() => {
            left--;
            if (left <= 0) {
                clearInterval(timerId);
                resendBtn.disabled = false;
                $('resendTimer').textContent = '';
            } else {
                $('resendTimer').textContent = '(' + left + 's)';
            }
        }, 1000);
    }

    /* ---------- Step 1: check email ---------- */
    async function checkEmail() {
        hideAlert();
        const email = emailInput.value.trim();
        if (!email) { showAlert('আপনার ইমেইল ঠিকানা লিখুন।'); return; }
        setLoading(checkBtn, true, 'যাচাই হচ্ছে...');
        const res = await api('check_email', { email });
        setLoading(checkBtn, false);
        if (res.ok) {
            $('foundEmail').textContent = res.masked || email;
            foundBox.classList.remove('hidden');
            emailInput.readOnly = true;
            emailInput.classList.add('opacity-70');
            checkBtn.classList.add('hidden');
        } else {
            foundBox.classList.add('hidden');
            showAlert(res.msg, "error", res.debug);
        }
    }
    checkBtn.addEventListener('click', checkEmail);
    emailInput.addEventListener('keydown', e => { if (e.key === 'Enter') { e.preventDefault(); if (!emailInput.readOnly) checkEmail(); } });
    emailInput.addEventListener('input', () => { foundBox.classList.add('hidden'); });

    /* ---------- Step 1 -> 2: send OTP ---------- */
    async function sendOtp(fromResend) {
        hideAlert();
        const btn = fromResend ? resendBtn : sendOtpBtn;
        if (!fromResend) setLoading(btn, true, 'OTP পাঠানো হচ্ছে...');
        else { resendBtn.disabled = true; $('resendTimer').textContent = '...'; }

        const res = await api('send_otp');

        if (!fromResend) setLoading(btn, false);
        if (res.ok) {
            $('otpEmail').textContent = res.masked || '';
            otpBoxes.forEach(b => b.value = '');
            goStep(2);
            otpBoxes[0].focus();
            startTimer(res.wait || 60);
            if (fromResend) showAlert('নতুন OTP পাঠানো হয়েছে।', 'success');
        } else {
            if (res.wait) { startTimer(res.wait); }
            else if (fromResend) { resendBtn.disabled = false; $('resendTimer').textContent = ''; }
            showAlert(res.msg, "error", res.debug);
        }
    }
    sendOtpBtn.addEventListener('click', () => sendOtp(false));
    resendBtn.addEventListener('click', () => sendOtp(true));

    $('changeEmailBtn').addEventListener('click', () => {
        clearInterval(timerId);
        emailInput.readOnly = false;
        emailInput.classList.remove('opacity-70');
        foundBox.classList.add('hidden');
        checkBtn.classList.remove('hidden');
        goStep(1);
        emailInput.focus();
    });

    /* ---------- OTP boxes ---------- */
    otpBoxes.forEach((box, i) => {
        box.addEventListener('input', () => {
            box.value = box.value.replace(/\D/g, '').slice(0, 1);
            if (box.value && i < otpBoxes.length - 1) otpBoxes[i + 1].focus();
            if (getOtp().length === 6) verifyOtp();
        });
        box.addEventListener('keydown', e => {
            if (e.key === 'Backspace' && !box.value && i > 0) otpBoxes[i - 1].focus();
            if (e.key === 'ArrowLeft' && i > 0) otpBoxes[i - 1].focus();
            if (e.key === 'ArrowRight' && i < otpBoxes.length - 1) otpBoxes[i + 1].focus();
            if (e.key === 'Enter') verifyOtp();
        });
        box.addEventListener('paste', e => {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '').slice(0, 6);
            text.split('').forEach((ch, idx) => { if (otpBoxes[idx]) otpBoxes[idx].value = ch; });
            const next = Math.min(text.length, 5);
            otpBoxes[next].focus();
            if (text.length === 6) verifyOtp();
        });
    });
    function getOtp() { return otpBoxes.map(b => b.value).join(''); }

    /* ---------- Step 2 -> 3: verify OTP ---------- */
    let verifying = false;
    async function verifyOtp() {
        if (verifying) return;
        hideAlert();
        const otp = getOtp();
        if (otp.length !== 6) { showAlert('৬ ডিজিটের সম্পূর্ণ OTP লিখুন।'); return; }
        verifying = true;
        setLoading(verifyBtn, true, 'যাচাই হচ্ছে...');
        const res = await api('verify_otp', { otp });
        setLoading(verifyBtn, false);
        verifying = false;
        if (res.ok) {
            clearInterval(timerId);
            goStep(3);
            $('newPass').focus();
        } else {
            showAlert(res.msg, "error", res.debug);
            otpBoxes.forEach(b => { b.value = ''; b.classList.add('border-rose-400'); });
            setTimeout(() => otpBoxes.forEach(b => b.classList.remove('border-rose-400')), 1200);
            otpBoxes[0].focus();
        }
    }
    verifyBtn.addEventListener('click', verifyOtp);

    /* ---------- Step 3: password ---------- */
    document.querySelectorAll('.toggle-eye').forEach(btn => {
        btn.addEventListener('click', () => {
            const inp = $(btn.dataset.target), icon = btn.querySelector('i');
            const show = inp.type === 'password';
            inp.type = show ? 'text' : 'password';
            icon.className = 'fa-solid ' + (show ? 'fa-eye-slash' : 'fa-eye');
        });
    });

    function strength(p) {
        let s = 0;
        if (p.length >= MIN_LEN) s++;
        if (p.length >= 10) s++;
        if (/[A-Z]/.test(p) && /[a-z]/.test(p)) s++;
        if (/\d/.test(p)) s++;
        if (/[^A-Za-z0-9]/.test(p)) s++;
        return Math.min(s, 4);
    }
    function updateStrength() {
        const p = $('newPass').value, bar = $('strengthBar'), txt = $('strengthText');
        if (!p) { bar.style.width = '0'; txt.textContent = ''; return; }
        const s = p.length < MIN_LEN ? 1 : Math.max(strength(p), 1);
        const map = {
            1: ['25%', '#ef4444', 'দুর্বল'],
            2: ['50%', '#f59e0b', 'মোটামুটি'],
            3: ['75%', '#10b981', 'ভালো'],
            4: ['100%', '#059669', 'শক্তিশালী']
        };
        bar.style.width = map[s][0];
        bar.style.background = map[s][1];
        txt.textContent = map[s][2];
        txt.style.color = map[s][1];
    }
    function updateMatch() {
        const a = $('newPass').value, b = $('confirmPass').value, m = $('matchText');
        if (!b) { m.textContent = ''; return; }
        if (a === b) { m.textContent = '✓ পাসওয়ার্ড মিলেছে'; m.className = 'text-[11px] mt-1.5 font-semibold h-4 text-emerald-600'; }
        else { m.textContent = '✗ পাসওয়ার্ড মিলছে না'; m.className = 'text-[11px] mt-1.5 font-semibold h-4 text-rose-500'; }
    }
    $('newPass').addEventListener('input', () => { updateStrength(); updateMatch(); });
    $('confirmPass').addEventListener('input', updateMatch);
    $('confirmPass').addEventListener('keydown', e => { if (e.key === 'Enter') resetPassword(); });

    async function resetPassword() {
        hideAlert();
        const p1 = $('newPass').value, p2 = $('confirmPass').value;
        if (p1.length < MIN_LEN) { showAlert('পাসওয়ার্ড কমপক্ষে ' + MIN_LEN + ' অক্ষরের হতে হবে।'); return; }
        if (p1 !== p2) { showAlert('পাসওয়ার্ড এবং কনফার্ম পাসওয়ার্ড মিলছে না।'); return; }

        setLoading(resetBtn, true, 'পরিবর্তন হচ্ছে...');
        const res = await api('reset_password', { password: p1, confirm_password: p2 });
        setLoading(resetBtn, false);

        if (res.ok) {
            hideAlert();
            Object.keys(panels).forEach(k => panels[k].classList.add('hidden'));
            $('stepper').classList.add('hidden');
            $('backLogin').classList.add('hidden');
            $('panelDone').classList.remove('hidden');
            document.getElementById('topIcon').className = 'fa-solid fa-circle-check text-2xl';
            $('topTitle').textContent = 'সম্পন্ন হয়েছে';
            $('topSub').textContent = 'আপনার অ্যাকাউন্ট এখন নিরাপদ।';
        } else {
            showAlert(res.msg, "error", res.debug);
            if (res.restart) setTimeout(() => window.location.reload(), 2500);
        }
    }
    resetBtn.addEventListener('click', resetPassword);
});
</script>

<?php include 'include/footer.php'; ?>