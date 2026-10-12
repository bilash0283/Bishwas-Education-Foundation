<?php
/* ==========================================================
   contacts.php  ->  index.php?page=contacts   (PHP 7.2 – 8.4 compatible)

   Contact Messages Inbox
   - Access   : Admin + Volunteer Member (onno keu na)
   - Admin    : sob message dekhe, assign kore (Unassigned / All Volunteers / specific volunteer),
                status bodlay, comment kore, delete kore
   - Volunteer: shudhu "All Volunteers" bhabe deya ba nijeke deya message dekhe,
                comment + status update kore, "Take it" diye shared message nijer kore nite pare
   - Comment korle sender-er email-e sundor HTML mail jay (info@bishwas.org theke)

   Table: contact_messages (id, name, email, phone, subject, message, created_at)
          + assigned_to, status      (page nijei ALTER kore, na parle contacts.sql chalan)
          + contact_comments         (page nijei toiri kore)
   ========================================================== */

if (!headers_sent()) {
    ob_start();
}
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
if (!function_exists('h')) {
    function h($s)
    {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }
}
if (!defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
    define('JSON_INVALID_UTF8_SUBSTITUTE', 0);
}

/* ---------- Setting (egulo apni bodlate paren) ---------- */
$MAIL_FROM = 'info@bishwas.org';   // je address theke mail jabe (webmail / cPanel e thaka thakte hobe)
$MAIL_FROM_NAME = isset($site_title) && $site_title !== '' ? $site_title : 'Bishwas';
$PER_PAGE = 10;

$connection = isset($db) ? $db : (isset($conn) ? $conn : null);
if (!$connection) {
    echo 'Database connection not found.';
    return;
}
if (!@mysqli_set_charset($connection, 'utf8mb4')) {
    @mysqli_set_charset($connection, 'utf8');
}

// redirect: header already sent hole (live server) JS diye
function ct_go($url)
{
    if (!headers_sent()) {
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Location: ' . $url);
    } else {
        echo '<script>window.location.href=' . json_encode($url) . ';</script>';
    }
    exit;
}

/* ---------- Helpers ---------- */
function ct_js($v, $fallback)
{
    $j = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    return ($j === false) ? $fallback : $j;
}
function ct_sub($s, $len)
{
    $s = (string) $s;
    return function_exists('mb_substr') ? mb_substr($s, 0, $len, 'UTF-8') : substr($s, 0, $len);
}
function ct_initial($s)
{
    $s = trim((string) $s);
    if ($s === '') {
        return '?';
    }
    if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
        return mb_strtoupper(mb_substr($s, 0, 1, 'UTF-8'), 'UTF-8');
    }
    return strtoupper(substr($s, 0, 1));
}
function ct_lower($s)
{
    return function_exists('mb_strtolower') ? mb_strtolower((string) $s, 'UTF-8') : strtolower((string) $s);
}
function ct_excerpt($s, $len)
{
    $s = trim(preg_replace('/\s+/u', ' ', (string) $s));
    if (function_exists('mb_strlen') && function_exists('mb_substr')) {
        return mb_strlen($s, 'UTF-8') > $len ? mb_substr($s, 0, $len - 1, 'UTF-8') . '…' : $s;
    }
    return strlen($s) > $len ? substr($s, 0, $len - 1) . '…' : $s;
}
function ct_first($rows, $key, $default = 0)
{
    return isset($rows[0][$key]) ? $rows[0][$key] : $default;
}
function ct_clean_header($s)
{
    return trim(str_replace(array("\r", "\n", "%0a", "%0d"), ' ', (string) $s));
}

// bind_param er jonno reference toiri
function ct_bind($st, $t, &$p)
{
    if ($t === '' || !count($p)) {
        return;
    }
    $args = array($st, $t);
    foreach ($p as $k => $v) {
        $args[] = &$p[$k];
    }
    call_user_func_array('mysqli_stmt_bind_param', $args);
}
// SELECT: sob row array hishebe (mysqlnd lage na)
function ct_rows($db, $sql, $t = '', $p = array())
{
    $st = mysqli_prepare($db, $sql);
    if (!$st) {
        return array();
    }
    ct_bind($st, $t, $p);
    if (!mysqli_stmt_execute($st)) {
        mysqli_stmt_close($st);
        return array();
    }
    $o = array();
    if (function_exists('mysqli_stmt_get_result')) {
        $res = mysqli_stmt_get_result($st);
        while ($res && ($r = mysqli_fetch_assoc($res))) {
            $o[] = $r;
        }
    } else {
        mysqli_stmt_store_result($st);
        $meta = mysqli_stmt_result_metadata($st);
        if ($meta) {
            $row = array();
            $bind = array($st);
            while ($f = mysqli_fetch_field($meta)) {
                $row[$f->name] = null;
                $bind[] = &$row[$f->name];
            }
            mysqli_free_result($meta);
            call_user_func_array('mysqli_stmt_bind_result', $bind);
            while (mysqli_stmt_fetch($st)) {
                $copy = array();
                foreach ($row as $k => $v) {
                    $copy[$k] = $v;
                }
                $o[] = $copy;
            }
        }
    }
    mysqli_stmt_close($st);
    return $o;
}
// INSERT/UPDATE/DELETE: affected rows (fail hole -1)
function ct_run($db, $sql, $t, $p)
{
    $st = mysqli_prepare($db, $sql);
    if (!$st) {
        return -1;
    }
    ct_bind($st, $t, $p);
    $ok = mysqli_stmt_execute($st);
    $n = mysqli_stmt_affected_rows($st);
    mysqli_stmt_close($st);
    return $ok ? $n : -1;
}

/* ---------- Role (database theke verify) ---------- */
$me = 0;
foreach (array('user_id', 'id', 'uid', 'member_id') as $sk) {
    if (isset($_SESSION[$sk]) && (int) $_SESSION[$sk] > 0) {
        $me = (int) $_SESSION[$sk];
        break;
    }
}
$utype = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '';
$me_name = '';
if ($me > 0) {
    $ur = ct_rows($connection, "SELECT user_type, member_name FROM users WHERE id = ? LIMIT 1", 'i', array($me));
    if ($ur) {
        $utype = $ur[0]['user_type'];
        $me_name = $ur[0]['member_name'];
    }
}
$isAdmin = ($utype === 'Admin');
$isVol = ($utype === 'Volunteer Member');
if (!$isAdmin && !($isVol && $me > 0)) {
    echo '<section class="page-content max-w-xl mx-auto"><div class="p-6 bg-white rounded-2xl ring-1 ring-slate-200 text-center space-y-2"><i class="fa-solid fa-lock text-3xl text-slate-300"></i>
          <h3 class="font-bold text-slate-800">Access Restricted</h3><p class="text-xs text-slate-500">Only Admin and Volunteer members can open Contact Messages. / শুধু অ্যাডমিন ও ভলান্টিয়ার এই পেজ দেখতে পারবেন।</p></div></section>';
    return;
}

/* ---------- Table setup (auto) ---------- */
$setup_errors = array();
try {
    $cr = mysqli_query($connection, "SHOW COLUMNS FROM contact_messages");
    if (!$cr) {
        echo '<section class="page-content max-w-xl mx-auto"><div class="p-6 bg-amber-50 border border-amber-200 rounded-2xl text-center space-y-2"><i class="fa-solid fa-database text-3xl text-amber-400"></i>
              <h3 class="font-bold text-slate-800">Table "contact_messages" not found</h3><p class="text-xs text-slate-600">' . h(mysqli_error($connection)) . '</p></div></section>';
        return;
    }
    $cols = array();
    while ($r = mysqli_fetch_assoc($cr)) {
        $cols[$r['Field']] = true;
    }
    if (!isset($cols['assigned_to'])) {
        if (!mysqli_query($connection, "ALTER TABLE contact_messages ADD COLUMN assigned_to INT(11) NULL DEFAULT NULL AFTER message")) {
            $setup_errors[] = mysqli_error($connection);
        }
    }
    if (!isset($cols['status'])) {
        if (!mysqli_query($connection, "ALTER TABLE contact_messages ADD COLUMN status ENUM('New','In Progress','Replied','Closed') NOT NULL DEFAULT 'New' AFTER assigned_to")) {
            $setup_errors[] = mysqli_error($connection);
        }
    }
    if (
        !mysqli_query($connection, "CREATE TABLE IF NOT EXISTS contact_comments (
        id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
        contact_id INT(11) NOT NULL,
        user_id INT(11) NULL DEFAULT NULL,
        comment TEXT NOT NULL,
        status_after VARCHAR(20) NOT NULL DEFAULT '',
        mailed TINYINT(1) NOT NULL DEFAULT 0,
        mail_note VARCHAR(255) NOT NULL DEFAULT '',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY idx_contact (contact_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4")
    ) {
        $setup_errors[] = mysqli_error($connection);
    }
} catch (Throwable $e) {
    $setup_errors[] = $e->getMessage();
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = function_exists('random_bytes') ? bin2hex(random_bytes(16)) : md5(uniqid((string) mt_rand(), true));
}
$page = isset($_GET['page']) ? $_GET['page'] : 'contacts';
$base = 'index.php?page=' . urlencode($page);

$STATUS = array(
    'New' => 'bg-rose-100 text-rose-700',
    'In Progress' => 'bg-sky-100 text-sky-700',
    'Replied' => 'bg-emerald-100 text-emerald-700',
    'Closed' => 'bg-slate-200 text-slate-600',
);

/* ---------- Visibility (volunteer shudhu nijer + shared) ---------- */
$visCond = '1=1';
$visT = '';
$visP = array();
if (!$isAdmin) {
    $visCond = '(m.assigned_to = 0 OR m.assigned_to = ?)';
    $visT = 'i';
    $visP = array($me);
}

function ct_get_visible($db, $id, $visCond, $visT, $visP)
{
    $rows = ct_rows($db, "SELECT m.* FROM contact_messages m WHERE m.id = ? AND $visCond LIMIT 1", 'i' . $visT, array_merge(array($id), $visP));
    return $rows ? $rows[0] : null;
}

/* ---------- Mail (webmail / mail()) ---------- */
function ct_mail_html($org, $name, $subject, $reply, $original, $status, $by)
{
    $e = function ($s) {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); };
    $logo = $org['logo'] !== '' ? '<img src="' . $e($org['logo']) . '" alt="" width="54" height="54" style="display:block;border:0;border-radius:10px;background:#fff;padding:3px">' : '';
    return '<!DOCTYPE html><html><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f1f5f9;font-family:Arial,Helvetica,\'Noto Sans Bengali\',sans-serif;color:#1e293b">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f1f5f9;padding:24px 10px"><tr><td align="center">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px;width:100%;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e2e8f0">
  <tr><td style="background:#047857;background:linear-gradient(135deg,#065f46,#10b981);padding:22px 28px">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
      <td width="64" valign="middle">' . $logo . '</td>
      <td valign="middle" style="color:#ffffff"><div style="font-size:19px;font-weight:bold;line-height:1.25">' . $e($org['name']) . '</div>
      <div style="font-size:11px;letter-spacing:2px;opacity:.85;margin-top:4px">REPLY TO YOUR MESSAGE</div></td>
    </tr></table></td></tr>
  <tr><td style="padding:28px 28px 8px">
    <p style="margin:0 0 14px;font-size:16px;font-weight:bold">Dear ' . $e($name) . ',</p>
    <p style="margin:0 0 18px;font-size:14px;line-height:1.7;color:#475569">Thank you for contacting us. Here is our response to your message<br>
      <span style="color:#047857;font-weight:bold">&ldquo;' . $e($subject) . '&rdquo;</span></p>
    <div style="background:#ecfdf5;border-left:4px solid #10b981;border-radius:8px;padding:16px 18px;font-size:14px;line-height:1.8;color:#0f172a">' . nl2br($e($reply)) . '</div>
    <p style="margin:14px 0 0;font-size:12px;color:#64748b">Status: <b style="color:#047857">' . $e($status) . '</b>' . ($by !== '' ? ' &nbsp;|&nbsp; Replied by: <b>' . $e($by) . '</b>' : '') . '</p>
  </td></tr>
  <tr><td style="padding:18px 28px 6px"><div style="font-size:11px;letter-spacing:2px;color:#94a3b8;margin-bottom:8px">YOUR ORIGINAL MESSAGE</div>
    <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:14px 16px;font-size:13px;line-height:1.7;color:#64748b">' . nl2br($e($original)) . '</div></td></tr>
  <tr><td style="padding:22px 28px 26px"><p style="margin:0;font-size:13px;color:#475569;line-height:1.7">If you need any further help, just reply to this email.<br>
    <b style="color:#065f46">' . $e($org['name']) . '</b></p></td></tr>
  <tr><td style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:16px 28px;font-size:11px;color:#94a3b8;line-height:1.7;text-align:center">'
        . ($org['address'] !== '' ? $e($org['address']) . '<br>' : '')
        . ($org['phone'] !== '' ? 'Tel: ' . $e($org['phone']) : '') . ($org['phone'] !== '' && $org['email'] !== '' ? ' &nbsp;|&nbsp; ' : '') . ($org['email'] !== '' ? $e($org['email']) : '') . '
    <br>This is a response to a message you sent through our website contact form.</td></tr>
</table></td></tr></table></body></html>';
}
function ct_send_mail($to, $subject, $html, $from, $fromName, $replyTo)
{
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        return array(false, 'Invalid email address');
    }
    if (!function_exists('mail')) {
        return array(false, 'mail() is disabled on this server');
    }
    $enc = function ($s) {
        return '=?UTF-8?B?' . base64_encode($s) . '?='; };
    $headers = array(
        'MIME-Version: 1.0',
        'Content-Type: text/html; charset=UTF-8',
        'Content-Transfer-Encoding: base64',
        'From: ' . $enc(ct_clean_header($fromName)) . ' <' . ct_clean_header($from) . '>',
        'Reply-To: ' . ct_clean_header($replyTo),
        'X-Mailer: PHP/' . phpversion(),
    );
    $body = chunk_split(base64_encode($html));
    $ok = @mail(ct_clean_header($to), $enc(ct_clean_header($subject)), $body, implode("\r\n", $headers), '-f' . ct_clean_header($from));
    return $ok ? array(true, '') : array(false, 'Server could not send the mail (check webmail / SPF settings)');
}

/* ---------- ACTIONS ---------- */
$post_csrf = isset($_POST['csrf']) ? (string) $_POST['csrf'] : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($_SESSION['csrf'], $post_csrf)) {
    $do = isset($_POST['do']) ? $_POST['do'] : '';
    $ok = true;
    $msg = '';
    $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
    $back_in = isset($_POST['back']) ? $_POST['back'] : '';
    $back = (strpos($back_in, $base) === 0) ? $back_in : $base;
    $row = $id > 0 ? ct_get_visible($connection, $id, $visCond, $visT, $visP) : null;

    if (!$row) {
        $ok = false;
        $msg = 'Message not found or you do not have access. / মেসেজ পাওয়া যায়নি।';
    } elseif ($do === 'delete') {
        if (!$isAdmin) {
            $ok = false;
            $msg = 'Only Admin can delete.';
        } else {
            ct_run($connection, "DELETE FROM contact_comments WHERE contact_id = ?", 'i', array($id));
            ct_run($connection, "DELETE FROM contact_messages WHERE id = ?", 'i', array($id));
            $msg = 'Message deleted.';
        }
    } elseif ($do === 'assign') {
        if (!$isAdmin) {
            $ok = false;
            $msg = 'Only Admin can assign.';
        } else {
            $target = isset($_POST['target']) ? (string) $_POST['target'] : '';
            if ($target === 'none') {
                $val = null;
                $label = 'Unassigned (Admin only)';
            } elseif ($target === 'all') {
                $val = 0;
                $label = 'All Volunteers';
            } else {
                $vid = (int) $target;
                $vr = ct_rows($connection, "SELECT member_name FROM users WHERE id = ? AND user_type = 'Volunteer Member' LIMIT 1", 'i', array($vid));
                if (!$vr) {
                    $ok = false;
                    $msg = 'Please select a valid volunteer.';
                    $val = false;
                    $label = '';
                } else {
                    $val = $vid;
                    $label = $vr[0]['member_name'];
                }
            }
            if ($ok) {
                $r = ct_run($connection, "UPDATE contact_messages SET assigned_to = ? WHERE id = ?", 'ii', array($val, $id));
                if ($r < 0) {
                    $ok = false;
                    $msg = 'Could not assign.';
                } else {
                    ct_run(
                        $connection,
                        "INSERT INTO contact_comments (contact_id, user_id, comment, status_after) VALUES (?,?,?,?)",
                        'iiss',
                        array($id, $me, 'Assigned to: ' . $label, $row['status'])
                    );
                    $msg = 'Assigned to ' . $label . '.';
                }
            }
        }
    } elseif ($do === 'claim') {
        if ($isAdmin || (int) $row['assigned_to'] !== 0) {
            $ok = false;
            $msg = 'This message cannot be taken.';
        } else {
            ct_run($connection, "UPDATE contact_messages SET assigned_to = ? WHERE id = ? AND assigned_to = 0", 'ii', array($me, $id));
            ct_run(
                $connection,
                "INSERT INTO contact_comments (contact_id, user_id, comment, status_after) VALUES (?,?,?,?)",
                'iiss',
                array($id, $me, 'Taken by ' . $me_name, $row['status'])
            );
            $msg = 'You took this message.';
        }
    } elseif ($do === 'comment') {
        $comment = trim(ct_sub(isset($_POST['comment']) ? $_POST['comment'] : '', 3000));
        $st_in = isset($_POST['status']) ? $_POST['status'] : '';
        $new_status = isset($STATUS[$st_in]) ? $st_in : $row['status'];
        $send = !empty($_POST['send_mail']);
        if ($comment === '' && $new_status === $row['status']) {
            $ok = false;
            $msg = 'Write a comment or change the status. / মন্তব্য লিখুন বা স্ট্যাটাস বদলান।';
        } else {
            $text = $comment !== '' ? $comment : 'Status changed to ' . $new_status;
            $r = ct_run($connection, "INSERT INTO contact_comments (contact_id, user_id, comment, status_after) VALUES (?,?,?,?)", 'iiss', array($id, $me, $text, $new_status));
            if ($r < 0) {
                $ok = false;
                $msg = 'Could not save comment.';
            } else {
                $cid = mysqli_insert_id($connection);
                ct_run($connection, "UPDATE contact_messages SET status = ? WHERE id = ?", 'si', array($new_status, $id));
                $msg = 'Comment saved.';
                if ($comment !== '' && $send) {
                    $logo = '';
                    if (!empty($favicon_icon)) {
                        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                        $root = str_replace('\\', '/', dirname(dirname(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '/index.php')));
                        $logo = $scheme . '://' . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . rtrim($root, '/') . '/public/assets/' . $favicon_icon;
                    }
                    $org = array(
                        'name' => $MAIL_FROM_NAME,
                        'address' => isset($office_address) ? $office_address : '',
                        'phone' => isset($phone_number) ? $phone_number : '',
                        'email' => $MAIL_FROM,
                        'logo' => $logo,
                    );
                    $subj = trim((string) $row['subject']);
                    if ($subj === '') {
                        $subj = 'Your message';
                    }
                    $html = ct_mail_html($org, $row['name'], $subj, $comment, $row['message'], $new_status, $me_name);
                    $res = ct_send_mail($row['email'], 'Re: ' . $subj, $html, $MAIL_FROM, $MAIL_FROM_NAME, $MAIL_FROM);
                    if ($res[0]) {
                        ct_run($connection, "UPDATE contact_comments SET mailed = 1, mail_note = ? WHERE id = ?", 'si', array($row['email'], $cid));
                        $msg = 'Comment saved and email sent to ' . $row['email'] . '.';
                    } else {
                        ct_run($connection, "UPDATE contact_comments SET mailed = 0, mail_note = ? WHERE id = ?", 'si', array(ct_sub($res[1], 250), $cid));
                        $ok = false;
                        $msg = 'Comment saved, but email was NOT sent: ' . $res[1];
                    }
                }
            }
        }
    }
    $_SESSION['ct_flash'] = array($ok, $msg);
    ct_go($back);
}
$flash = isset($_SESSION['ct_flash']) ? $_SESSION['ct_flash'] : null;
unset($_SESSION['ct_flash']);

/* ---------- FILTERS ---------- */
$q = trim(isset($_GET['q']) ? $_GET['q'] : '');
$g_status = isset($_GET['status']) ? $_GET['status'] : '';
$fs = isset($STATUS[$g_status]) ? $g_status : '';
$who = isset($_GET['who']) ? (string) $_GET['who'] : '';
$pg = max(1, (int) (isset($_GET['pg']) ? $_GET['pg'] : 1));
function ct_url($o = array())
{
    global $base, $q, $fs, $who;
    return $base . '&' . http_build_query(array_merge(array('q' => $q, 'status' => $fs, 'who' => $who), $o));
}

$w = 'WHERE ' . $visCond;
$t = $visT;
$p = $visP;
if ($q !== '') {
    $w .= " AND (m.name LIKE ? OR m.email LIKE ? OR m.phone LIKE ? OR m.subject LIKE ? OR m.message LIKE ?)";
    $l = '%' . addcslashes($q, '%_\\') . '%';
    $t .= 'sssss';
    for ($i = 0; $i < 5; $i++) {
        $p[] = $l;
    }
}
if ($fs !== '') {
    $w .= " AND m.status = ?";
    $t .= 's';
    $p[] = $fs;
}
if ($isAdmin) {
    if ($who === 'none') {
        $w .= " AND m.assigned_to IS NULL";
    } elseif ($who === 'all') {
        $w .= " AND m.assigned_to = 0";
    } elseif ((int) $who > 0) {
        $w .= " AND m.assigned_to = ?";
        $t .= 'i';
        $p[] = (int) $who;
    } else {
        $who = '';
    }
} else {
    if ($who === 'mine') {
        $w .= " AND m.assigned_to = ?";
        $t .= 'i';
        $p[] = $me;
    } elseif ($who === 'all') {
        $w .= " AND m.assigned_to = 0";
    } else {
        $who = '';
    }
}

$total = (int) ct_first(ct_rows($connection, "SELECT COUNT(*) c FROM contact_messages m $w", $t, $p), 'c', 0);
$pages = max(1, (int) ceil($total / $PER_PAGE));
$pg = min($pg, $pages);
$msgs = ct_rows($connection, "SELECT m.*, u.member_name assignee,
        (SELECT COUNT(*) FROM contact_comments c WHERE c.contact_id = m.id) cc
        FROM contact_messages m LEFT JOIN users u ON u.id = m.assigned_to $w
        ORDER BY FIELD(m.status,'New','In Progress','Replied','Closed'), m.id DESC
        LIMIT $PER_PAGE OFFSET " . (($pg - 1) * $PER_PAGE), $t, $p);

// comments for visible page
$ids = array_map('intval', array_column($msgs, 'id'));
$in = $ids ? implode(',', $ids) : '0';
$cmts = array();
foreach (ct_rows($connection, "SELECT c.*, u.member_name author FROM contact_comments c LEFT JOIN users u ON u.id = c.user_id WHERE c.contact_id IN ($in) ORDER BY c.id DESC") as $r) {
    $cmts[$r['contact_id']][] = array(
        'by' => $r['author'] ? $r['author'] : 'System',
        'text' => $r['comment'],
        'status' => $r['status_after'],
        'mailed' => (int) $r['mailed'],
        'note' => $r['mail_note'],
        'at' => date('d M Y, h:i A', strtotime($r['created_at'])),
    );
}
foreach ($msgs as $i => $row) {
    $a = $row['assigned_to'];
    $msgs[$i]['assign_kind'] = ($a === null) ? 'none' : ((int) $a === 0 ? 'all' : 'one');
    $msgs[$i]['assign_label'] = ($a === null) ? 'Unassigned' : ((int) $a === 0 ? 'All Volunteers' : ($row['assignee'] ? $row['assignee'] : 'User #' . (int) $a));
    $msgs[$i]['is_mine'] = ($a !== null && (int) $a === $me);
    $msgs[$i]['comments'] = isset($cmts[$row['id']]) ? $cmts[$row['id']] : array();
    $msgs[$i]['date_label'] = date('d M Y, h:i A', strtotime($row['created_at']));
    $msgs[$i]['excerpt'] = ct_excerpt($row['message'], 130);
}

// stats (role scope)
$stat = array('total' => 0, 'none' => 0);
foreach ($STATUS as $k => $unused) {
    $stat[$k] = 0;
}
foreach (ct_rows($connection, "SELECT m.status, COUNT(*) c FROM contact_messages m WHERE $visCond GROUP BY m.status", $visT, $visP) as $r) {
    $stat[$r['status']] = (int) $r['c'];
    $stat['total'] += (int) $r['c'];
}
if ($isAdmin) {
    $stat['none'] = (int) ct_first(ct_rows($connection, "SELECT COUNT(*) c FROM contact_messages m WHERE m.assigned_to IS NULL"), 'c', 0);
} else {
    $stat['none'] = (int) ct_first(ct_rows($connection, "SELECT COUNT(*) c FROM contact_messages m WHERE m.assigned_to = 0 AND m.status = 'New'"), 'c', 0);
}

$VOLS = array();
if ($isAdmin) {
    foreach (ct_rows($connection, "SELECT id, member_name, mobile_no FROM users WHERE user_type = 'Volunteer Member' ORDER BY member_name") as $r) {
        $VOLS[] = array('id' => (int) $r['id'], 'name' => $r['member_name'], 'phone' => $r['mobile_no']);
    }
}
$inp = 'w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10';
$lbl = 'block text-[11px] font-bold uppercase text-slate-500 mb-1';
$csrf = '<input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '"><input type="hidden" name="back" value="' . h(ct_url(array('pg' => $pg))) . '">';
$stColor = array('New' => 'rose', 'In Progress' => 'sky', 'Replied' => 'emerald', 'Closed' => 'slate');
?>
<section class="page-content space-y-5 max-w-7xl mx-auto">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-800">Contact Messages <small
                    class="text-xs font-medium text-slate-400">/ যোগাযোগ বার্তা</small></h2>
            <p class="text-xs text-slate-500 mt-0.5">
                <?php echo $isAdmin ? 'Admin view — assign messages to volunteers, track status and reply to senders.' : 'My Inbox — messages assigned to you or shared with all volunteers.'; ?>
            </p>
        </div>
    </div>

    <?php if ($setup_errors) { ?>
        <div class="p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-lg"><b>Database
                error:</b><br><?php echo h(implode(' | ', array_unique($setup_errors))); ?><br>Run <b>contacts.sql</b> in
            phpMyAdmin.</div><?php } ?>
    <?php if ($flash) { ?>
        <div
            class="p-3 rounded-xl text-xs font-semibold border <?php echo $flash[0] ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-rose-50 border-rose-200 text-rose-700'; ?>">
            <?php echo h($flash[1]); ?></div><?php } ?>

    <!-- Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3">
        <a href="<?php echo h(ct_url(array('status' => '', 'who' => '', 'pg' => 1))); ?>"
            class="p-3.5 bg-slate-900 text-white rounded-2xl">
            <p class="text-[10px] font-bold uppercase text-slate-300">Total</p>
            <p class="text-2xl font-bold"><?php echo $stat['total']; ?></p>
        </a>
        <?php foreach ($STATUS as $k => $cls) {
            $c = $stColor[$k]; ?>
            <a href="<?php echo h(ct_url(array('status' => $k, 'pg' => 1))); ?>"
                class="p-3.5 bg-white rounded-2xl ring-1 <?php echo $fs === $k ? 'ring-2 ring-emerald-500' : 'ring-slate-200/80'; ?> hover:shadow-md transition">
                <p class="text-[10px] font-bold uppercase text-slate-400 truncate"><?php echo $k; ?></p>
                <p class="text-2xl font-bold text-<?php echo $c; ?>-600"><?php echo $stat[$k]; ?></p>
            </a>
        <?php } ?>
        <?php if ($isAdmin) { ?>
            <a href="<?php echo h(ct_url(array('who' => 'none', 'status' => '', 'pg' => 1))); ?>"
                class="p-3.5 bg-amber-50 rounded-2xl ring-1 <?php echo $who === 'none' ? 'ring-2 ring-amber-500' : 'ring-amber-200'; ?>">
                <p class="text-[10px] font-bold uppercase text-amber-600">Unassigned</p>
                <p class="text-2xl font-bold text-amber-600"><?php echo $stat['none']; ?></p>
            </a>
        <?php } else { ?>
            <a href="<?php echo h(ct_url(array('who' => 'all', 'status' => 'New', 'pg' => 1))); ?>"
                class="p-3.5 bg-amber-50 rounded-2xl ring-1 ring-amber-200">
                <p class="text-[10px] font-bold uppercase text-amber-600">Shared & New</p>
                <p class="text-2xl font-bold text-amber-600"><?php echo $stat['none']; ?></p>
            </a>
        <?php } ?>
    </div>

    <!-- Filters -->
    <form method="GET" action="index.php"
        class="bg-white rounded-2xl border border-slate-200/80 p-4 grid grid-cols-2 md:grid-cols-12 gap-2">
        <input type="hidden" name="page" value="<?php echo h($page); ?>">
        <input type="text" name="q" value="<?php echo h($q); ?>"
            placeholder="Search name, email, phone, subject... / খুঁজুন"
            class="col-span-2 md:col-span-5 <?php echo $inp; ?>">
        <select name="status" onchange="this.form.submit()" class="md:col-span-2 <?php echo $inp; ?>">
            <option value="">All Status</option>
            <?php foreach ($STATUS as $k => $unused) {
                echo '<option' . ($fs === $k ? ' selected' : '') . '>' . $k . '</option>';
            } ?>
        </select>
        <?php if ($isAdmin) { ?>
            <select name="who" onchange="this.form.submit()" class="col-span-2 md:col-span-3 <?php echo $inp; ?>">
                <option value="">All Assignments</option>
                <option value="none" <?php echo $who === 'none' ? ' selected' : ''; ?>>Unassigned (Admin only)</option>
                <option value="all" <?php echo $who === 'all' ? ' selected' : ''; ?>>All Volunteers (shared)</option>
                <?php foreach ($VOLS as $v) {
                    echo '<option value="' . $v['id'] . '"' . ((string) $who === (string) $v['id'] ? ' selected' : '') . '>' . h($v['name']) . '</option>';
                } ?>
            </select>
        <?php } else { ?>
            <select name="who" onchange="this.form.submit()" class="col-span-2 md:col-span-3 <?php echo $inp; ?>">
                <option value="">All my messages</option>
                <option value="mine" <?php echo $who === 'mine' ? ' selected' : ''; ?>>Assigned to me</option>
                <option value="all" <?php echo $who === 'all' ? ' selected' : ''; ?>>Shared with all volunteers</option>
            </select>
        <?php } ?>
        <button class="md:col-span-1 px-3 py-2.5 bg-slate-800 text-white rounded-xl text-xs font-semibold"><i
                class="fa-solid fa-magnifying-glass"></i></button>
        <a href="<?php echo h($base); ?>"
            class="md:col-span-1 px-3 py-2.5 text-center border border-rose-200 text-rose-600 bg-rose-50 rounded-xl text-xs font-semibold"
            title="Reset"><i class="fa-solid fa-rotate-left"></i></a>
    </form>
    <p class="text-xs text-slate-500"><?php echo $total; ?> message(s)</p>

    <!-- Message list -->
    <div class="space-y-3">
        <?php foreach ($msgs as $m) {
            $sc = isset($STATUS[$m['status']]) ? $STATUS[$m['status']] : 'bg-slate-100 text-slate-600';
            $isNew = $m['status'] === 'New'; ?>
            <div
                class="bg-white rounded-2xl ring-1 <?php echo $isNew ? 'ring-2 ring-rose-200' : 'ring-slate-200/80'; ?> p-4 flex gap-3 sm:gap-4 hover:shadow-lg transition">
                <div
                    class="w-11 h-11 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold shrink-0">
                    <?php echo h(ct_initial($m['name'])); ?></div>
                <div class="min-w-0 flex-1 space-y-1.5">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="font-bold text-sm text-slate-800 truncate"><?php echo h($m['name']); ?></span>
                        <span
                            class="px-2 py-0.5 rounded-full text-[10px] font-bold <?php echo $sc; ?>"><?php echo h($m['status']); ?></span>
                        <?php if ($m['assign_kind'] === 'none') { ?><span
                                class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700"><i
                                    class="fa-solid fa-user-slash mr-1"></i>Unassigned</span>
                        <?php } elseif ($m['assign_kind'] === 'all') { ?><span
                                class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-100 text-teal-700"><i
                                    class="fa-solid fa-users mr-1"></i>All Volunteers</span>
                        <?php } else { ?><span
                                class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-violet-100 text-violet-700"><i
                                    class="fa-solid fa-user-check mr-1"></i><?php echo $m['is_mine'] && !$isAdmin ? 'Assigned to me' : h($m['assign_label']); ?></span><?php } ?>
                        <?php if ((int) $m['cc'] > 0) { ?><span
                                class="px-2 py-0.5 rounded-full text-[10px] font-semibold bg-slate-100 text-slate-600"><i
                                    class="fa-regular fa-comment mr-1"></i><?php echo (int) $m['cc']; ?></span><?php } ?>
                    </div>
                    <p class="font-semibold text-xs text-slate-700 cursor-pointer hover:text-emerald-600"
                        onclick="ctView(<?php echo (int) $m['id']; ?>)">
                        <?php echo h($m['subject'] !== '' ? $m['subject'] : '(No subject)'); ?></p>
                    <p class="text-xs text-slate-500 line-clamp-2"><?php echo h($m['excerpt']); ?></p>
                    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-[11px] text-slate-400">
                        <span><i class="fa-regular fa-envelope mr-1"></i><?php echo h($m['email']); ?></span>
                        <?php if ($m['phone'] !== '') { ?><span><i
                                    class="fa-solid fa-phone mr-1"></i><?php echo h($m['phone']); ?></span><?php } ?>
                        <span><i class="fa-regular fa-clock mr-1"></i><?php echo h($m['date_label']); ?></span>
                    </div>
                    <div class="flex flex-wrap gap-2 pt-1">
                        <button type="button" onclick="ctView(<?php echo (int) $m['id']; ?>)"
                            class="px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 text-[11px] font-bold hover:bg-emerald-100"><i
                                class="fa-solid fa-comments mr-1"></i>Open & Reply</button>
                        <?php if ($isAdmin) { ?>
                            <form method="POST" action=""
                                onsubmit="return confirm('Delete this message and its comments? / মেসেজটি মুছে ফেলবেন?')">
                                <?php echo $csrf; ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id"
                                    value="<?php echo (int) $m['id']; ?>">
                                <button
                                    class="px-3 py-1.5 rounded-lg bg-rose-50 text-rose-600 text-[11px] font-bold hover:bg-rose-100"><i
                                        class="fa-solid fa-trash mr-1"></i>Delete</button>
                            </form>
                        <?php } elseif ($m['assign_kind'] === 'all') { ?>
                            <form method="POST" action=""><?php echo $csrf; ?><input type="hidden" name="do"
                                    value="claim"><input type="hidden" name="id" value="<?php echo (int) $m['id']; ?>">
                                <button
                                    class="px-3 py-1.5 rounded-lg bg-violet-50 text-violet-700 text-[11px] font-bold hover:bg-violet-100"><i
                                        class="fa-solid fa-hand mr-1"></i>Take it</button>
                            </form>
                        <?php } ?>
                    </div>
                </div>
            </div>
        <?php }
        if (!$msgs) {
            echo '<p class="py-14 text-center text-slate-400 text-sm bg-white rounded-2xl ring-1 ring-slate-200/80"><i class="fa-regular fa-envelope-open text-4xl mb-3 block"></i>' . ($isAdmin ? 'No messages found.' : 'No message assigned to you yet.') . '</p>';
        } ?>
    </div>

    <?php if ($pages > 1) { ?>
        <div class="flex items-center justify-between"><span class="text-xs text-slate-500">Page <?php echo $pg; ?> /
                <?php echo $pages; ?></span>
            <div class="flex gap-2">
                <?php if ($pg > 1) { ?><a href="<?php echo h(ct_url(array('pg' => $pg - 1))); ?>"
                        class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-xs font-semibold">‹
                        Prev</a><?php } ?>
                <?php if ($pg < $pages) { ?><a href="<?php echo h(ct_url(array('pg' => $pg + 1))); ?>"
                        class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-xs font-semibold">Next
                        ›</a><?php } ?>
            </div>
        </div>
    <?php } ?>
</section>

<!-- DETAIL MODAL -->
<div id="ctModal" class="fixed inset-0 bg-slate-900/60 hidden items-end sm:items-center justify-center sm:p-4 z-50">
    <div
        class="bg-white w-full sm:max-w-3xl max-h-[94vh] flex flex-col rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-4 bg-slate-900 text-white flex justify-between items-center shrink-0">
            <h3 class="font-bold text-sm"><i class="fa-solid fa-envelope-open-text text-emerald-400 mr-2"></i>Message
                Details</h3>
            <button type="button" onclick="ctModal(false)" class="text-slate-400 hover:text-white p-1"><i
                    class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <div class="overflow-y-auto p-4 sm:p-6 space-y-5">
            <div id="ctBody" class="space-y-4"></div>

            <?php if ($isAdmin) { ?>
                <form method="POST" action="" class="rounded-2xl border border-violet-200 bg-violet-50/40 p-4 space-y-3">
                    <?php echo $csrf; ?><input type="hidden" name="do" value="assign"><input type="hidden" name="id"
                        class="ct_id">
                    <p class="text-xs font-bold text-violet-800"><i class="fa-solid fa-user-check mr-1.5"></i>Assign <small
                            class="font-normal">/ দায়িত্ব দিন</small></p>
                    <div class="flex flex-col sm:flex-row gap-2">
                        <select name="target" id="ct_target" class="<?php echo $inp; ?>">
                            <option value="none">Unassigned (Admin only)</option>
                            <option value="all">All Volunteers (shared)</option>
                            <optgroup label="Specific volunteer">
                                <?php foreach ($VOLS as $v) {
                                    echo '<option value="' . $v['id'] . '">' . h($v['name']) . ($v['phone'] ? ' — ' . h($v['phone']) : '') . '</option>';
                                } ?>
                            </optgroup>
                        </select>
                        <button
                            class="px-6 py-2.5 bg-violet-600 text-white rounded-xl text-xs font-semibold hover:bg-violet-700 shrink-0">Save
                            Assignment</button>
                    </div>
                </form>
            <?php } else { ?>
                <form method="POST" action="" id="ctClaimForm"
                    class="hidden rounded-2xl border border-violet-200 bg-violet-50/40 p-3 flex items-center justify-between gap-3">
                    <?php echo $csrf; ?><input type="hidden" name="do" value="claim"><input type="hidden" name="id"
                        class="ct_id">
                    <p class="text-xs text-violet-800">This message is shared with all volunteers. Take it to handle it
                        yourself.</p>
                    <button class="px-4 py-2 bg-violet-600 text-white rounded-xl text-xs font-semibold shrink-0"><i
                            class="fa-solid fa-hand mr-1"></i>Take it</button>
                </form>
            <?php } ?>

            <form method="POST" action="" class="rounded-2xl border border-emerald-200 bg-emerald-50/40 p-4 space-y-3">
                <?php echo $csrf; ?><input type="hidden" name="do" value="comment"><input type="hidden" name="id"
                    class="ct_id">
                <p class="text-xs font-bold text-emerald-800"><i class="fa-solid fa-comment-dots mr-1.5"></i>Comment /
                    Reply <small class="font-normal">/ মন্তব্য ও উত্তর</small></p>
                <textarea name="comment" id="ct_comment" rows="4" maxlength="3000" class="<?php echo $inp; ?>"
                    placeholder="Write your reply or internal note..."></textarea>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 items-center">
                    <div><label class="<?php echo $lbl; ?>">Status</label><select name="status" id="ct_status"
                            class="<?php echo $inp; ?>"><?php foreach ($STATUS as $k => $unused) {
                                   echo '<option>' . $k . '</option>';
                               } ?></select>
                    </div>
                    <label
                        class="flex items-start gap-2 text-xs font-semibold text-slate-700 cursor-pointer sm:mt-5"><input
                            type="checkbox" name="send_mail" value="1" checked class="rounded w-4 h-4 mt-0.5">
                        <span>Send this comment by email to the sender<br><small class="font-normal text-slate-400"
                                id="ct_mailto"></small></span></label>
                </div>
                <button
                    class="w-full sm:w-auto px-6 py-3 sm:py-2.5 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700"><i
                        class="fa-solid fa-paper-plane mr-1.5"></i>Save & Send</button>
            </form>
        </div>
    </div>
</div>

<script>
    (function () {
        var M = <?php echo ct_js(array_values($msgs), '[]'); ?>, ST = <?php echo ct_js($STATUS, '{}'); ?>, ADMIN = <?php echo $isAdmin ? 'true' : 'false'; ?>;
        var $ = function (id) { return document.getElementById(id); };
        function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
        function find(id) { return M.filter(function (x) { return +x.id === +id; })[0]; }
        window.ctModal = function (on) { var m = $('ctModal'); m.classList.toggle('hidden', !on); m.classList.toggle('flex', on); document.body.style.overflow = on ? 'hidden' : ''; };
        $('ctModal').addEventListener('mousedown', function (e) { if (e.target === this) { ctModal(false); } });

        window.ctView = function (id) {
            var m = find(id); if (!m) { return; }
            var cell = function (ic, l, v) { return '<div class="p-3 rounded-xl bg-slate-50 border border-slate-100"><p class="text-[10px] font-bold uppercase text-slate-400"><i class="fa-solid ' + ic + ' mr-1"></i>' + l + '</p><p class="text-xs font-semibold text-slate-800 mt-0.5 break-words">' + (v || '—') + '</p></div>'; };
            var contact = [
                cell('fa-user', 'Name', esc(m.name)),
                cell('fa-envelope', 'Email', m.email ? '<a class="text-emerald-700 underline" href="mailto:' + esc(m.email) + '">' + esc(m.email) + '</a>' : ''),
                cell('fa-phone', 'Phone', m.phone ? '<a class="text-emerald-700 underline" href="tel:' + esc(m.phone) + '">' + esc(m.phone) + '</a>' : ''),
                cell('fa-clock', 'Received', esc(m.date_label))
            ].join('');
            var hist = m.comments.length ? m.comments.map(function (c) {
                return '<div class="flex gap-3 text-xs"><span class="w-2 h-2 mt-1.5 rounded-full bg-emerald-500 shrink-0"></span><div class="min-w-0">' +
                    '<p class="font-semibold text-slate-700">' + esc(c.by) + (c.status ? ' <span class="px-1.5 py-0.5 rounded text-[10px] ' + (ST[c.status] || '') + '">' + esc(c.status) + '</span>' : '') +
                    (c.mailed ? ' <span class="px-1.5 py-0.5 rounded text-[10px] bg-sky-100 text-sky-700"><i class="fa-solid fa-envelope-circle-check"></i> mailed</span>' : (c.note ? ' <span class="px-1.5 py-0.5 rounded text-[10px] bg-rose-100 text-rose-700" title="' + esc(c.note) + '">mail failed</span>' : '')) + '</p>' +
                    '<p class="text-slate-600 whitespace-pre-line break-words">' + esc(c.text) + '</p><p class="text-[10px] text-slate-400">' + esc(c.at) + '</p></div></div>';
            }).join('') : '<p class="text-xs text-slate-400">No comments yet.</p>';
            var kindBadge = m.assign_kind === 'none' ? '<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-amber-100 text-amber-700">Unassigned</span>'
                : (m.assign_kind === 'all' ? '<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-teal-100 text-teal-700">All Volunteers</span>'
                    : '<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-violet-100 text-violet-700">' + esc(m.assign_label) + '</span>');
            $('ctBody').innerHTML =
                '<div class="flex flex-wrap items-center gap-1.5"><span class="px-2.5 py-1 rounded-full text-[10px] font-bold ' + (ST[m.status] || '') + '">' + esc(m.status) + '</span>' + kindBadge + '</div>' +
                '<h3 class="text-lg font-bold text-slate-800 leading-snug break-words">' + esc(m.subject || '(No subject)') + '</h3>' +
                '<div class="grid grid-cols-2 gap-2">' + contact + '</div>' +
                '<div class="p-4 rounded-xl bg-slate-50 border border-slate-200 text-sm text-slate-700 leading-relaxed whitespace-pre-line break-words">' + esc(m.message) + '</div>' +
                '<div><h4 class="text-xs font-bold text-slate-800 mb-2"><i class="fa-solid fa-clock-rotate-left text-sky-600 mr-1.5"></i>Comments & History</h4><div class="space-y-3">' + hist + '</div></div>';
            document.querySelectorAll('.ct_id').forEach(function (e) { e.value = m.id; });
            $('ct_status').value = m.status; $('ct_comment').value = '';
            $('ct_mailto').textContent = m.email ? 'To: ' + m.email : 'No email address on this message';
            if (ADMIN) { $('ct_target').value = m.assign_kind === 'none' ? 'none' : (m.assign_kind === 'all' ? 'all' : String(m.assigned_to)); }
            else { var cf = $('ctClaimForm'); if (cf) { cf.classList.toggle('hidden', m.assign_kind !== 'all'); cf.classList.toggle('flex', m.assign_kind === 'all'); } }
            ctModal(true);
        };
    })();
</script>