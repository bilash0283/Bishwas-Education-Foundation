<?php
/* ==========================================================
   settings.php  ->  index.php?page=settings   (PHP 7.2 – 8.4, live server PHP 7.4 based)

   Sections:
   - Terms & Conditions : shudhu Admin
   - Change Password    : SOBAR jonno (login kora je kono user)
       Step 1: Current password dite hobe -> match korle
       Step 2: New Password + Confirm Password dite parbe (5 minute somoy)

   Password system (beginner note):
   - Users table-er password column auto khuje ney (password / user_password / pass / passwd).
     Na pele niche $PW_COLUMN e column-er naam likhe din.
   - Apnar login jei format-e password mile (bcrypt / md5 / sha1 / plain), notun password
     SEI ONE format-e save hoy, jate login bhenge na jay.
   - Bhul password 5 bar dile 10 minute er jonno lock hoy.

   Table: terms er jonno settings.sql age import korun.
   ========================================================== */

/* ---------- Setting (dorkar hole bodlan) ---------- */
$PW_COLUMN = '';          // khali rakhle auto detect hobe. Jemon: 'password'
$PW_MIN = 8;           // minimum kotogulo character
$PW_MAX = 64;          // maximum kotogulo character
$PW_FAILS = 5;           // koto bar bhul korle lock
$PW_LOCK = 600;         // lock er somoy (second)
$PW_STEP = 300;         // current password match korar por new password dewar somoy (second)

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
if (!isset($db) || !$db) {
    echo 'Database connection not found.';
    return;
}
if (!@mysqli_set_charset($db, 'utf8mb4')) {
    @mysqli_set_charset($db, 'utf8');
}

// redirect: header jodi already pathano hoye jay (live server) tahole JS diye
function st_go($url)
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
function st_js($v, $fallback)
{
    $j = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    return ($j === false) ? $fallback : $j;
}
function st_first($rows, $key, $default = 0)
{
    return isset($rows[0][$key]) ? $rows[0][$key] : $default;
}

// bind_param er jonno reference toiri
function st_bind($st, $t, &$p)
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
function st_rows($db, $sql, $t = '', $p = array())
{
    $st = mysqli_prepare($db, $sql);
    if (!$st) {
        return array();
    }
    st_bind($st, $t, $p);
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
function st_run($db, $sql, $t, $p)
{
    $st = mysqli_prepare($db, $sql);
    if (!$st) {
        return -1;
    }
    st_bind($st, $t, $p);
    $ok = mysqli_stmt_execute($st);
    $n = mysqli_stmt_affected_rows($st);
    mysqli_stmt_close($st);
    return $ok ? $n : -1;
}

/* ---------- Password helpers ---------- */
// Joma thaka password-er sathe user-er deya password milaay (bcrypt / md5 / sha1 / sha256 / plain)
function st_pw_check($input, $stored)
{
    $input = (string) $input;
    $stored = (string) $stored;
    if ($stored === '') {
        return false;
    }
    if (preg_match('/^\$(2[abxy]|argon2i|argon2id)\$/', $stored)) {
        return password_verify($input, $stored);
    }
    if (preg_match('/^[a-f0-9]{32}$/i', $stored)) {
        return hash_equals(strtolower($stored), md5($input));
    }
    if (preg_match('/^[a-f0-9]{40}$/i', $stored)) {
        return hash_equals(strtolower($stored), sha1($input));
    }
    if (preg_match('/^[a-f0-9]{64}$/i', $stored)) {
        return hash_equals(strtolower($stored), hash('sha256', $input));
    }
    return hash_equals($stored, $input);
}
// Notun password ke agei-r format-e baniye ney (login jeno bhenge na jay)
function st_pw_make($new, $stored)
{
    $stored = (string) $stored;
    if (preg_match('/^[a-f0-9]{32}$/i', $stored)) {
        return md5($new);
    }
    if (preg_match('/^[a-f0-9]{40}$/i', $stored)) {
        return sha1($new);
    }
    if (preg_match('/^[a-f0-9]{64}$/i', $stored)) {
        return hash('sha256', $new);
    }
    if (preg_match('/^\$(2[abxy]|argon2i|argon2id)\$/', $stored) || $stored === '') {
        return password_hash($new, PASSWORD_DEFAULT);
    }
    return $new; // purono plain-text format
}
// users table-e password column-er naam khuje ber kora
function st_pw_column($db, $forced)
{
    $cols = array();
    $r = @mysqli_query($db, "SHOW COLUMNS FROM users");
    if ($r) {
        while ($x = mysqli_fetch_assoc($r)) {
            $cols[strtolower($x['Field'])] = $x['Field'];
        }
    }
    if ($forced !== '' && isset($cols[strtolower($forced)])) {
        return $cols[strtolower($forced)];
    }
    foreach (array('password', 'user_password', 'pass', 'passwd', 'pwd') as $c) {
        if (isset($cols[$c])) {
            return $cols[$c];
        }
    }
    return '';
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
    $ur = st_rows($db, "SELECT user_type, member_name FROM users WHERE id = ? LIMIT 1", 'i', array($me));
    if ($ur) {
        $utype = $ur[0]['user_type'];
        $me_name = $ur[0]['member_name'];
    }
}
if ($me <= 0 || $utype === '') {
    echo '<section class="page-content max-w-xl mx-auto"><div class="p-6 bg-white rounded-2xl ring-1 ring-slate-200 text-center space-y-2"><i class="fa-solid fa-lock text-3xl text-slate-300"></i>
          <h3 class="font-bold text-slate-800">Please login</h3><p class="text-xs text-slate-500">Settings dekhte login korun. / সেটিংস দেখতে লগইন করুন।</p></div></section>';
    return;
}
$isAdmin = ($utype === 'Admin');

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = function_exists('random_bytes') ? bin2hex(random_bytes(16)) : md5(uniqid((string) mt_rand(), true));
}
$page = isset($_GET['page']) ? $_GET['page'] : 'settings';
$base = 'index.php?page=' . urlencode($page);

/* ---------- Settings sections: [title, bangla, icon, active?, adminOnly?] ---------- */
$SECTIONS = array(
    'password' => array('Change Password', 'পাসওয়ার্ড পরিবর্তন', 'fa-key', true, false),
    'terms' => array('Terms & Conditions', 'সদস্য নিবন্ধনের শর্তাবলী', 'fa-file-contract', true, true),
    'general' => array('General Information', 'সংস্থার সাধারণ তথ্য', 'fa-building', false, true),
    'notify' => array('Notifications', 'নোটিফিকেশন', 'fa-bell', false, true),
);
$g_s = isset($_GET['s']) ? $_GET['s'] : '';
$default_sec = $isAdmin ? 'terms' : 'password';
$sec = (isset($SECTIONS[$g_s]) && $SECTIONS[$g_s][3] && ($isAdmin || !$SECTIONS[$g_s][4])) ? $g_s : $default_sec;

// Terms table ache kina (shudhu Admin er jonno)
$termsReady = false;
if ($isAdmin) {
    $termsReady = true;
    try {
        foreach (array('terms_conditions', 'portal_settings') as $tb) {
            $chk = @mysqli_query($db, "SELECT 1 FROM $tb LIMIT 1");
            if (!$chk) {
                $termsReady = false;
            }
        }
    } catch (Throwable $e) {
        $termsReady = false;
    }
}

$pwCol = st_pw_column($db, $PW_COLUMN);

/* ---------- ACTIONS ---------- */
$post_csrf = isset($_POST['csrf']) ? (string) $_POST['csrf'] : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($_SESSION['csrf'], $post_csrf)) {
    $do = isset($_POST['do']) ? $_POST['do'] : '';
    $ok = true;
    $msg = '';
    $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
    $go_sec = 'terms';

    /* ----- Password (sobar jonno) ----- */
    if ($do === 'pw_verify' || $do === 'pw_change' || $do === 'pw_cancel') {
        $go_sec = 'password';
        $now = time();
        if ($do === 'pw_cancel') {
            unset($_SESSION['pw_ok_until']);
            $msg = 'Cancelled.';
        } elseif ($pwCol === '') {
            $ok = false;
            $msg = 'Password column not found in users table. Set $PW_COLUMN at the top of settings.php.';
        } elseif ($do === 'pw_verify') {
            $lock = isset($_SESSION['pw_lock_until']) ? (int) $_SESSION['pw_lock_until'] : 0;
            if ($lock > $now) {
                $ok = false;
                $msg = 'Too many wrong attempts. Try again after ' . ceil(($lock - $now) / 60) . ' minute(s). / অনেকবার ভুল হয়েছে, কিছুক্ষণ পর চেষ্টা করুন।';
            } else {
                $cur = isset($_POST['current_password']) ? (string) $_POST['current_password'] : '';
                $rows = st_rows($db, "SELECT `" . str_replace('`', '', $pwCol) . "` AS pw FROM users WHERE id = ? LIMIT 1", 'i', array($me));
                $stored = st_first($rows, 'pw', '');
                if ($cur !== '' && st_pw_check($cur, $stored)) {
                    $_SESSION['pw_ok_until'] = $now + $PW_STEP;
                    $_SESSION['pw_fail'] = 0;
                    unset($_SESSION['pw_lock_until']);
                    $msg = 'Current password matched. Now set your new password. / বর্তমান পাসওয়ার্ড মিলেছে, এখন নতুন পাসওয়ার্ড দিন।';
                } else {
                    $f = (isset($_SESSION['pw_fail']) ? (int) $_SESSION['pw_fail'] : 0) + 1;
                    $_SESSION['pw_fail'] = $f;
                    unset($_SESSION['pw_ok_until']);
                    $ok = false;
                    if ($f >= $PW_FAILS) {
                        $_SESSION['pw_lock_until'] = $now + $PW_LOCK;
                        $_SESSION['pw_fail'] = 0;
                        $msg = 'Wrong password. Too many attempts — locked for ' . ceil($PW_LOCK / 60) . ' minutes.';
                    } else {
                        $msg = 'Current password is incorrect. (' . ($PW_FAILS - $f) . ' attempt(s) left) / বর্তমান পাসওয়ার্ড ভুল।';
                    }
                }
            }
        } else { // pw_change
            $until = isset($_SESSION['pw_ok_until']) ? (int) $_SESSION['pw_ok_until'] : 0;
            $new = isset($_POST['new_password']) ? (string) $_POST['new_password'] : '';
            $conf = isset($_POST['confirm_password']) ? (string) $_POST['confirm_password'] : '';
            $len = function_exists('mb_strlen') ? mb_strlen($new, 'UTF-8') : strlen($new);
            if ($until <= $now) {
                unset($_SESSION['pw_ok_until']);
                $ok = false;
                $msg = 'Time is over. Please enter your current password again. / সময় শেষ, আবার বর্তমান পাসওয়ার্ড দিন।';
            } elseif ($len < $PW_MIN) {
                $ok = false;
                $msg = 'New password must be at least ' . $PW_MIN . ' characters. / কমপক্ষে ' . $PW_MIN . ' অক্ষর দিন।';
            } elseif ($len > $PW_MAX) {
                $ok = false;
                $msg = 'New password can be at most ' . $PW_MAX . ' characters.';
            } elseif (!preg_match('/[A-Za-z]/', $new) || !preg_match('/[0-9]/', $new)) {
                $ok = false;
                $msg = 'Use at least one letter and one number. / অন্তত একটি অক্ষর ও একটি সংখ্যা দিন।';
            } elseif ($new !== $conf) {
                $ok = false;
                $msg = 'New password and confirm password do not match. / দুটি পাসওয়ার্ড মেলেনি।';
            } else {
                $col = str_replace('`', '', $pwCol);
                $rows = st_rows($db, "SELECT `$col` AS pw FROM users WHERE id = ? LIMIT 1", 'i', array($me));
                $stored = st_first($rows, 'pw', '');
                if ($stored !== '' && st_pw_check($new, $stored)) {
                    $ok = false;
                    $msg = 'New password must be different from the current password. / নতুন পাসওয়ার্ড আগেরটির মতো হতে পারবে না।';
                } else {
                    $hash = st_pw_make($new, $stored);
                    $r = st_run($db, "UPDATE users SET `$col` = ? WHERE id = ?", 'si', array($hash, $me));
                    if ($r < 0) {
                        $ok = false;
                        $msg = 'Could not save the password. Please try again.';
                    } else {
                        unset($_SESSION['pw_ok_until'], $_SESSION['pw_fail'], $_SESSION['pw_lock_until']);
                        if (function_exists('session_regenerate_id')) {
                            @session_regenerate_id(true);
                        }
                        $msg = 'Password changed successfully! / পাসওয়ার্ড সফলভাবে পরিবর্তন হয়েছে।';
                    }
                }
            }
        }

        /* ----- Terms & Conditions (shudhu Admin) ----- */
    } elseif (!$isAdmin || !$termsReady) {
        $ok = false;
        $msg = 'You do not have permission for this action.';
    } elseif ($do === 'save') {
        $title = trim(isset($_POST['title']) ? $_POST['title'] : '');
        $content = trim(isset($_POST['content']) ? $_POST['content'] : '');
        $status = (isset($_POST['status']) ? $_POST['status'] : '') === 'Inactive' ? 'Inactive' : 'Active';
        if ($title === '' || $content === '') {
            $ok = false;
            $msg = 'Title and description are required. / শিরোনাম ও বিবরণ দিন।';
        } elseif ($id > 0) {
            $r = st_run($db, "UPDATE terms_conditions SET title = ?, content = ?, status = ?, updated_by = ? WHERE id = ?", 'sssii', array($title, $content, $status, $me ? $me : null, $id));
            if ($r < 0) {
                $ok = false;
                $msg = 'Could not update clause.';
            } else {
                $msg = 'Clause updated.';
            }
        } else {
            $max = (int) st_first(st_rows($db, "SELECT COALESCE(MAX(sort_order),0) m FROM terms_conditions"), 'm', 0);
            $r = st_run($db, "INSERT INTO terms_conditions (title, content, status, sort_order, updated_by) VALUES (?,?,?,?,?)", 'sssii', array($title, $content, $status, $max + 1, $me ? $me : null));
            if ($r < 0) {
                $ok = false;
                $msg = 'Could not add clause.';
            } else {
                $msg = 'Clause added.';
            }
        }
    } elseif ($do === 'delete') {
        st_run($db, "DELETE FROM terms_conditions WHERE id = ?", 'i', array($id));
        $msg = 'Clause deleted.';
    } elseif ($do === 'toggle') {
        st_run($db, "UPDATE terms_conditions SET status = IF(status = 'Active', 'Inactive', 'Active') WHERE id = ?", 'i', array($id));
        $msg = 'Status changed.';
    } elseif ($do === 'move') {
        $ids = array_map('intval', array_column(st_rows($db, "SELECT id FROM terms_conditions ORDER BY sort_order, id"), 'id'));
        $i = array_search($id, $ids);
        $dir = isset($_POST['dir']) ? $_POST['dir'] : '';
        $j = $i === false ? -1 : $i + ($dir === 'up' ? -1 : 1);
        if ($i !== false && isset($ids[$j])) {
            $x = $ids[$i];
            $ids[$i] = $ids[$j];
            $ids[$j] = $x;
            foreach ($ids as $k => $cid) {
                st_run($db, "UPDATE terms_conditions SET sort_order = ? WHERE id = ?", 'ii', array($k + 1, $cid));
            }
        }
        $msg = 'Order updated.';
    } elseif ($do === 'text') {
        foreach (array('terms_heading', 'terms_intro', 'terms_agree_label') as $k) {
            $r = st_run(
                $db,
                "INSERT INTO portal_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)",
                'ss',
                array($k, trim(isset($_POST[$k]) ? $_POST[$k] : ''))
            );
            if ($r < 0) {
                $ok = false;
            }
        }
        $msg = $ok ? 'Heading & intro saved.' : 'Could not save heading & intro.';
    }
    $_SESSION['st_flash'] = array($ok, $msg);
    st_go($base . '&s=' . $go_sec);
}
$flash = isset($_SESSION['st_flash']) ? $_SESSION['st_flash'] : null;
unset($_SESSION['st_flash']);

/* ---------- DATA ---------- */
$clauses = array();
$cfg = array();
$active = 0;
$last = null;
if ($sec === 'terms' && $termsReady) {
    $clauses = st_rows($db, "SELECT t.*, u.member_name editor FROM terms_conditions t LEFT JOIN users u ON u.id = t.updated_by ORDER BY t.sort_order, t.id");
    $cfg = array('terms_heading' => 'Terms & Conditions', 'terms_intro' => 'Please read the following terms carefully before registering as a member.', 'terms_agree_label' => 'I have read and agree to the Terms & Conditions.');
    foreach (st_rows($db, "SELECT setting_key, setting_value FROM portal_settings WHERE setting_key LIKE 'terms_%'") as $r) {
        if ($r['setting_value'] !== '') {
            $cfg[$r['setting_key']] = $r['setting_value'];
        }
    }
    $active = count(array_filter($clauses, function ($c) {
        return $c['status'] === 'Active';
    }));
    $last = $clauses ? max(array_column($clauses, 'updated_at')) : null;
}

// Password step info
$pwNow = time();
$pwUntil = isset($_SESSION['pw_ok_until']) ? (int) $_SESSION['pw_ok_until'] : 0;
$pwVerified = ($pwUntil > $pwNow);
$pwLockUntil = isset($_SESSION['pw_lock_until']) ? (int) $_SESSION['pw_lock_until'] : 0;
$pwLocked = ($pwLockUntil > $pwNow);

$inp = 'w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10';
$lbl = 'block text-[11px] font-bold uppercase text-slate-500 mb-1';
$csrf = '<input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '">';
?>
<section class="page-content space-y-5 max-w-7xl mx-auto">

    <div>
        <h2 class="text-xl sm:text-2xl font-bold text-slate-800">Settings <small
                class="text-xs font-medium text-slate-400">/ সেটিংস</small></h2>
        <p class="text-xs text-slate-500 mt-0.5">
            <?php echo $isAdmin ? 'Manage portal, website and your account settings.' : 'Manage your account settings.'; ?>
        </p>
    </div>

    <?php if ($flash) { ?>
        <div
            class="p-3 rounded-xl text-xs font-semibold border <?php echo $flash[0] ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-rose-50 border-rose-200 text-rose-700'; ?>">
            <?php echo h($flash[1]); ?>
        </div><?php } ?>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">

        <!-- Settings menu -->
        <nav
            class="lg:col-span-3 bg-white rounded-2xl ring-1 ring-slate-200/80 p-2 flex lg:flex-col gap-1 overflow-x-auto">
            <?php foreach ($SECTIONS as $k => $s) {
                if ($s[4] && !$isAdmin) {
                    continue;
                }
                $on = $s[3];
                $cur = $k === $sec; ?>
                <a <?php echo $on ? 'href="' . h($base . '&s=' . $k) . '"' : ''; ?>
                    class="shrink-0 flex items-center gap-3 px-3.5 py-3 rounded-xl text-xs font-semibold <?php echo $cur ? 'bg-emerald-600 text-white shadow-sm' : ($on ? 'text-slate-600 hover:bg-slate-50' : 'text-slate-300 cursor-not-allowed'); ?>">
                    <i class="fa-solid <?php echo $s[2]; ?> w-4 text-center"></i>
                    <span class="whitespace-nowrap"><?php echo h($s[0]); ?><small
                            class="hidden lg:block font-normal opacity-70"><?php echo h($s[1]); ?></small></span>
                    <?php if (!$on) { ?><span
                            class="ml-auto px-1.5 py-0.5 rounded bg-slate-100 text-slate-400 text-[9px] font-bold">Soon</span><?php } ?>
                </a>
            <?php } ?>
        </nav>

        <!-- Content -->
        <div class="lg:col-span-9 space-y-4">

            <?php if ($sec === 'password') { ?>
                <!-- ================= CHANGE PASSWORD ================= -->
                <div class="bg-white rounded-2xl ring-1 ring-slate-200/80 p-4 sm:p-5 flex items-center gap-3">
                    <div
                        class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                        <i class="fa-solid fa-key"></i>
                    </div>
                    <div>
                        <h3 class="font-bold text-slate-800">Change Password <small class="font-medium text-slate-400">/
                                পাসওয়ার্ড পরিবর্তন</small></h3>
                        <p class="text-[11px] text-slate-500">Account:
                            <b><?php echo h($me_name !== '' ? $me_name : 'Me'); ?></b> • <?php echo h($utype); ?>
                        </p>
                    </div>
                </div>

                <?php if ($pwCol === '') { ?>
                    <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-xs text-amber-800"><b>Password column
                            not found.</b> users table-e password column-er naam khuje paoya jayni. <code>settings.php</code>-er
                        upore <code>$PW_COLUMN</code> e column-er naam likhe din.</div>
                <?php } else { ?>

                    <!-- Step indicator -->
                    <div class="flex items-center gap-2 text-[11px] font-bold">
                        <span
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-full <?php echo $pwVerified ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-900 text-white'; ?>"><i
                                class="fa-solid <?php echo $pwVerified ? 'fa-circle-check' : 'fa-1'; ?>"></i> Current
                            password</span>
                        <i class="fa-solid fa-chevron-right text-slate-300"></i>
                        <span
                            class="flex items-center gap-1.5 px-3 py-1.5 rounded-full <?php echo $pwVerified ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-400'; ?>"><i
                                class="fa-solid fa-2"></i> New password</span>
                    </div>

                    <?php if (!$pwVerified) { ?>
                        <!-- STEP 1 -->
                        <form method="POST" action="" autocomplete="off"
                            class="bg-white rounded-2xl ring-1 ring-slate-200/80 p-4 sm:p-6 space-y-4 max-w-xl">
                            <?php echo $csrf; ?><input type="hidden" name="do" value="pw_verify">
                            <p class="text-xs text-slate-500">Prothome apnar <b>current password</b> din. Match korle new password
                                set korte parben. <br><span class="text-slate-400">আগে বর্তমান পাসওয়ার্ড দিন, মিললে নতুন পাসওয়ার্ড
                                    দিতে পারবেন।</span></p>
                            <?php if ($pwLocked) { ?>
                                <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-xs text-rose-700 font-semibold"><i
                                        class="fa-solid fa-lock mr-1.5"></i>Locked. Try again after
                                    <?php echo (int) ceil(($pwLockUntil - $pwNow) / 60); ?> minute(s).
                                </div><?php } ?>
                            <div><label class="<?php echo $lbl; ?>">Current Password / বর্তমান পাসওয়ার্ড *</label>
                                <div class="relative"><input type="password" name="current_password" id="pw_cur" required <?php echo $pwLocked ? 'disabled' : ''; ?> autocomplete="current-password"
                                        class="<?php echo $inp; ?> pr-10" placeholder="Enter current password">
                                    <button type="button" data-eye="pw_cur"
                                        class="absolute inset-y-0 right-0 px-3 text-slate-400 hover:text-slate-600"><i
                                            class="fa-solid fa-eye"></i></button>
                                </div>
                            </div>
                            <button <?php echo $pwLocked ? 'disabled' : ''; ?>
                                class="w-full sm:w-auto px-6 py-3 sm:py-2.5 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700 disabled:opacity-50"><i
                                    class="fa-solid fa-shield-halved mr-1.5"></i>Verify & Continue</button>
                        </form>

                    <?php } else { ?>
                        <!-- STEP 2 -->
                        <form method="POST" action="" autocomplete="off" id="pwForm"
                            class="bg-white rounded-2xl ring-1 ring-slate-200/80 p-4 sm:p-6 space-y-4 max-w-xl">
                            <?php echo $csrf; ?><input type="hidden" name="do" value="pw_change">
                            <div
                                class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-xs text-emerald-700 font-semibold flex items-center justify-between gap-3">
                                <span><i class="fa-solid fa-circle-check mr-1.5"></i>Current password matched. Set your new password
                                    within <b
                                        id="pwTimer"><?php echo (int) floor(($pwUntil - $pwNow) / 60); ?>:<?php echo str_pad((string) (($pwUntil - $pwNow) % 60), 2, '0', STR_PAD_LEFT); ?></b></span>
                            </div>
                            <div><label class="<?php echo $lbl; ?>">New Password / নতুন পাসওয়ার্ড *</label>
                                <div class="relative"><input type="password" name="new_password" id="pw_new" required
                                        minlength="<?php echo (int) $PW_MIN; ?>" maxlength="<?php echo (int) $PW_MAX; ?>"
                                        autocomplete="new-password" class="<?php echo $inp; ?> pr-10"
                                        placeholder="At least <?php echo (int) $PW_MIN; ?> characters">
                                    <button type="button" data-eye="pw_new"
                                        class="absolute inset-y-0 right-0 px-3 text-slate-400 hover:text-slate-600"><i
                                            class="fa-solid fa-eye"></i></button>
                                </div>
                                <div class="mt-2 h-1.5 rounded-full bg-slate-100 overflow-hidden">
                                    <div id="pwBar" class="h-full w-0 rounded-full transition-all"></div>
                                </div>
                                <p class="text-[11px] mt-1 font-semibold" id="pwStrength">&nbsp;</p>
                                <ul class="mt-1.5 space-y-1 text-[11px] text-slate-500">
                                    <li id="r_len"><i class="fa-regular fa-circle mr-1.5"></i>At least <?php echo (int) $PW_MIN; ?>
                                        characters</li>
                                    <li id="r_let"><i class="fa-regular fa-circle mr-1.5"></i>At least one letter</li>
                                    <li id="r_num"><i class="fa-regular fa-circle mr-1.5"></i>At least one number</li>
                                </ul>
                            </div>
                            <div><label class="<?php echo $lbl; ?>">Confirm Password / পাসওয়ার্ড নিশ্চিত করুন *</label>
                                <div class="relative"><input type="password" name="confirm_password" id="pw_conf" required
                                        maxlength="<?php echo (int) $PW_MAX; ?>" autocomplete="new-password"
                                        class="<?php echo $inp; ?> pr-10" placeholder="Type the new password again">
                                    <button type="button" data-eye="pw_conf"
                                        class="absolute inset-y-0 right-0 px-3 text-slate-400 hover:text-slate-600"><i
                                            class="fa-solid fa-eye"></i></button>
                                </div>
                                <p class="text-[11px] mt-1 font-semibold" id="pwMatch">&nbsp;</p>
                            </div>
                            <div class="flex flex-col-reverse sm:flex-row gap-2">
                                <button type="submit" form="pwCancel"
                                    class="px-5 py-3 sm:py-2.5 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                                <button id="pwSubmit" disabled
                                    class="sm:ml-auto px-6 py-3 sm:py-2.5 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700 disabled:opacity-40 disabled:cursor-not-allowed"><i
                                        class="fa-solid fa-floppy-disk mr-1.5"></i>Save New Password</button>
                            </div>
                        </form>
                        <form method="POST" action="" id="pwCancel"><?php echo $csrf; ?><input type="hidden" name="do"
                                value="pw_cancel"></form>
                    <?php } ?>

                <?php } ?>

            <?php } elseif ($sec === 'terms') { ?>
                <!-- ================= TERMS & CONDITIONS (Admin) ================= -->

                <?php if (!$termsReady) { ?>
                    <div class="p-6 bg-amber-50 border border-amber-200 rounded-2xl text-center space-y-2"><i
                            class="fa-solid fa-database text-3xl text-amber-400"></i>
                        <h3 class="font-bold text-slate-800">Database tables not found</h3>
                        <p class="text-xs text-slate-600">Please import <b>settings.sql</b> in phpMyAdmin first. / আগে
                            settings.sql ইম্পোর্ট করুন।</p>
                    </div>
                <?php } else { ?>

                    <div
                        class="bg-white rounded-2xl ring-1 ring-slate-200/80 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div
                                class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                                <i class="fa-solid fa-file-contract"></i>
                            </div>
                            <div>
                                <h3 class="font-bold text-slate-800">Member Registration — Terms & Conditions</h3>
                                <p class="text-[11px] text-slate-500">Shown to new members on the registration form.
                                    <?php echo $last ? 'Last updated: ' . h(date('d M Y, h:i A', strtotime($last))) : ''; ?>
                                </p>
                            </div>
                        </div>
                        <div class="grid grid-cols-3 sm:flex gap-2">
                            <button type="button" onclick="stModal('stText', true)"
                                class="px-3 py-2.5 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50"><i
                                    class="fa-solid fa-heading mr-1"></i>Intro</button>
                            <button type="button" onclick="stTab('preview')"
                                class="px-3 py-2.5 border border-sky-200 bg-sky-50 rounded-xl text-xs font-semibold text-sky-700 hover:bg-sky-100"><i
                                    class="fa-solid fa-eye mr-1"></i>Preview</button>
                            <button type="button" onclick="stForm()"
                                class="px-3 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-emerald-600/20"><i
                                    class="fa-solid fa-plus mr-1"></i>Add</button>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-3">
                        <?php foreach (array(array('Total Clauses', count($clauses), 'slate'), array('Active', $active, 'emerald'), array('Inactive', count($clauses) - $active, 'amber')) as $c) { ?>
                            <div class="p-3.5 bg-white rounded-2xl ring-1 ring-slate-200/80 text-center">
                                <p class="text-[10px] font-bold uppercase text-slate-400"><?php echo $c[0]; ?></p>
                                <p class="text-2xl font-bold text-<?php echo $c[2]; ?>-600"><?php echo $c[1]; ?></p>
                            </div>
                        <?php } ?>
                    </div>

                    <div class="grid grid-cols-2 gap-1 p-1 bg-slate-100 rounded-xl sm:w-80">
                        <button type="button" data-tab="manage"
                            class="sttab py-2.5 rounded-lg text-xs font-bold bg-white text-emerald-700 shadow-sm"><i
                                class="fa-solid fa-list-ol mr-1.5"></i>Manage</button>
                        <button type="button" data-tab="preview"
                            class="sttab py-2.5 rounded-lg text-xs font-bold text-slate-500"><i
                                class="fa-solid fa-eye mr-1.5"></i>Preview</button>
                    </div>

                    <!-- MANAGE -->
                    <div id="tab_manage" class="space-y-3">
                        <?php foreach ($clauses as $i => $c) {
                            $act = $c['status'] === 'Active'; ?>
                            <div
                                class="bg-white rounded-2xl ring-1 ring-slate-200/80 p-4 flex gap-3 sm:gap-4 <?php echo $act ? '' : 'opacity-70'; ?>">
                                <div
                                    class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-sm font-bold shrink-0">
                                    <?php echo $i + 1; ?>
                                </div>
                                <div class="min-w-0 flex-1 space-y-2">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="font-bold text-sm text-slate-800"><?php echo h($c['title']); ?></h4>
                                        <span
                                            class="px-2 py-0.5 rounded-full text-[10px] font-bold <?php echo $act ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600'; ?>"><?php echo h($c['status']); ?></span>
                                    </div>
                                    <p class="text-xs text-slate-600 leading-relaxed whitespace-pre-line line-clamp-4">
                                        <?php echo h($c['content']); ?>
                                    </p>
                                    <p class="text-[10px] text-slate-400">Updated
                                        <?php echo h(date('d M Y', strtotime($c['updated_at']))); ?>
                                        <?php echo $c['editor'] ? ' by ' . h($c['editor']) : ''; ?>
                                    </p>
                                    <div class="flex flex-wrap items-center gap-1.5 pt-1">
                                        <form method="POST" action="" class="flex gap-1"><?php echo $csrf; ?><input type="hidden"
                                                name="do" value="move"><input type="hidden" name="id"
                                                value="<?php echo (int) $c['id']; ?>">
                                            <button name="dir" value="up" title="Move up" <?php echo $i === 0 ? 'disabled' : ''; ?>
                                                class="w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-30"><i
                                                    class="fa-solid fa-arrow-up text-xs"></i></button>
                                            <button name="dir" value="down" title="Move down" <?php echo $i === count($clauses) - 1 ? 'disabled' : ''; ?>
                                                class="w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-30"><i
                                                    class="fa-solid fa-arrow-down text-xs"></i></button>
                                        </form>
                                        <form method="POST" action=""><?php echo $csrf; ?><input type="hidden" name="do"
                                                value="toggle"><input type="hidden" name="id" value="<?php echo (int) $c['id']; ?>">
                                            <button
                                                class="px-3 h-8 rounded-lg text-[11px] font-bold <?php echo $act ? 'bg-amber-50 text-amber-700 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'; ?>"><i
                                                    class="fa-solid <?php echo $act ? 'fa-eye-slash' : 'fa-eye'; ?> mr-1"></i><?php echo $act ? 'Hide' : 'Show'; ?></button>
                                        </form>
                                        <button type="button" onclick="stForm(<?php echo (int) $c['id']; ?>)"
                                            class="px-3 h-8 rounded-lg bg-sky-50 text-sky-700 text-[11px] font-bold hover:bg-sky-100"><i
                                                class="fa-solid fa-pen mr-1"></i>Edit</button>
                                        <form method="POST" action=""
                                            onsubmit="return confirm('Delete this clause? / এই ধারাটি মুছে ফেলবেন?')">
                                            <?php echo $csrf; ?><input type="hidden" name="do" value="delete"><input type="hidden"
                                                name="id" value="<?php echo (int) $c['id']; ?>">
                                            <button
                                                class="px-3 h-8 rounded-lg bg-rose-50 text-rose-600 text-[11px] font-bold hover:bg-rose-100"><i
                                                    class="fa-solid fa-trash mr-1"></i>Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php }
                        if (!$clauses) {
                            echo '<div class="py-14 text-center text-slate-400 text-sm bg-white rounded-2xl ring-1 ring-slate-200/80"><i class="fa-solid fa-file-circle-plus text-4xl mb-3 block"></i>No clauses yet. Click “Add” to write the first one.<br>কোনো ধারা নেই। “Add” চাপুন।</div>';
                        } ?>
                    </div>

                    <!-- PREVIEW -->
                    <div id="tab_preview" class="hidden">
                        <div class="bg-white rounded-2xl ring-1 ring-slate-200/80 overflow-hidden max-w-3xl">
                            <div class="h-2 bg-gradient-to-r from-emerald-800 via-emerald-600 to-emerald-400"></div>
                            <div class="p-5 sm:p-8 space-y-5">
                                <div class="text-center">
                                    <h3 class="text-lg sm:text-xl font-bold text-emerald-800">
                                        <?php echo h($cfg['terms_heading']); ?>
                                    </h3>
                                    <p class="text-xs text-slate-500 mt-1.5"><?php echo h($cfg['terms_intro']); ?></p>
                                </div>
                                <ol class="space-y-4">
                                    <?php $n = 0;
                                    foreach ($clauses as $c) {
                                        if ($c['status'] !== 'Active') {
                                            continue;
                                        }
                                        $n++; ?>
                                        <li class="flex gap-3"><span
                                                class="w-7 h-7 rounded-full bg-emerald-50 text-emerald-700 flex items-center justify-center text-xs font-bold shrink-0"><?php echo $n; ?></span>
                                            <div>
                                                <p class="font-bold text-sm text-slate-800"><?php echo h($c['title']); ?></p>
                                                <p class="text-xs text-slate-600 leading-relaxed whitespace-pre-line mt-0.5">
                                                    <?php echo h($c['content']); ?>
                                                </p>
                                            </div>
                                        </li>
                                    <?php }
                                    if (!$n) {
                                        echo '<li class="text-center text-xs text-slate-400">No active clauses.</li>';
                                    } ?>
                                </ol>
                                <label
                                    class="flex items-start gap-2.5 p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-700"><input
                                        type="checkbox" disabled class="mt-0.5 rounded w-4 h-4">
                                    <?php echo h($cfg['terms_agree_label']); ?></label>
                                <button type="button" disabled
                                    class="w-full py-3 rounded-xl bg-emerald-600/40 text-white text-xs font-bold cursor-not-allowed">Register</button>
                                <p class="text-center text-[10px] text-slate-400">Preview only — members will see this on the
                                    registration page.</p>
                            </div>
                        </div>
                    </div>
                <?php } ?>
            <?php } ?>
        </div>
    </div>
</section>

<?php if ($sec === 'terms' && $isAdmin && $termsReady) { ?>
    <!-- ADD / EDIT CLAUSE -->
    <div id="stForm" class="fixed inset-0 bg-slate-900/60 hidden items-end sm:items-center justify-center sm:p-4 z-50">
        <div
            class="bg-white w-full sm:max-w-xl max-h-[94vh] flex flex-col rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden">
            <div class="p-4 bg-slate-900 text-white flex justify-between items-center shrink-0">
                <h3 class="font-bold text-sm"><i class="fa-solid fa-file-contract text-emerald-400 mr-2"></i><span
                        id="stFormTitle">Add Clause</span></h3>
                <button type="button" onclick="stModal('stForm', false)" class="text-slate-400 hover:text-white p-1"><i
                        class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <form method="POST" action="" class="p-4 sm:p-6 space-y-4 overflow-y-auto">
                <?php echo $csrf; ?><input type="hidden" name="do" value="save"><input type="hidden" name="id" id="f_id"
                    value="0">
                <div><label class="<?php echo $lbl; ?>">Clause Title * <small class="normal-case">/ ধারার
                            শিরোনাম</small></label><input type="text" name="title" id="f_title" required
                        class="<?php echo $inp; ?>" placeholder="e.g. Membership Fee / সদস্য চাঁদা"></div>
                <div><label class="<?php echo $lbl; ?>">Description * <small class="normal-case">/
                            বিবরণ</small></label><textarea name="content" id="f_content" rows="7" required
                        class="<?php echo $inp; ?>" placeholder="Write the full condition here..."></textarea></div>
                <div><label class="<?php echo $lbl; ?>">Status</label><select name="status" id="f_status"
                        class="<?php echo $inp; ?>">
                        <option>Active</option>
                        <option>Inactive</option>
                    </select>
                    <p class="text-[11px] text-slate-400 mt-1">Inactive clauses are hidden from the registration form.</p>
                </div>
                <div class="flex gap-2 pt-1">
                    <button type="button" onclick="stModal('stForm', false)"
                        class="flex-1 sm:flex-none px-5 py-3 sm:py-2.5 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600">Cancel</button>
                    <button
                        class="flex-1 sm:flex-none sm:ml-auto px-6 py-3 sm:py-2.5 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700">Save
                        Clause</button>
                </div>
            </form>
        </div>
    </div>

    <!-- HEADING & INTRO -->
    <div id="stText" class="fixed inset-0 bg-slate-900/60 hidden items-end sm:items-center justify-center sm:p-4 z-50">
        <div class="bg-white w-full sm:max-w-xl rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden">
            <div class="p-4 bg-slate-900 text-white flex justify-between items-center">
                <h3 class="font-bold text-sm"><i class="fa-solid fa-heading text-emerald-400 mr-2"></i>Heading, Intro &
                    Agree Text</h3>
                <button type="button" onclick="stModal('stText', false)" class="text-slate-400 hover:text-white p-1"><i
                        class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <form method="POST" action="" class="p-4 sm:p-6 space-y-4">
                <?php echo $csrf; ?><input type="hidden" name="do" value="text">
                <div><label class="<?php echo $lbl; ?>">Heading</label><input type="text" name="terms_heading"
                        value="<?php echo h($cfg['terms_heading']); ?>" class="<?php echo $inp; ?>"></div>
                <div><label class="<?php echo $lbl; ?>">Intro text</label><textarea name="terms_intro" rows="3"
                        class="<?php echo $inp; ?>"><?php echo h($cfg['terms_intro']); ?></textarea></div>
                <div><label class="<?php echo $lbl; ?>">Checkbox text <small class="normal-case">(“I
                            agree...”)</small></label><input type="text" name="terms_agree_label"
                        value="<?php echo h($cfg['terms_agree_label']); ?>" class="<?php echo $inp; ?>"></div>
                <button
                    class="w-full py-3 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700">Save</button>
            </form>
        </div>
    </div>

    <script>
        (function () {
            var C = <?php echo st_js(array_values($clauses), '[]'); ?>, $ = function (id) { return document.getElementById(id); };
            window.stModal = function (id, on) { var m = $(id); m.classList.toggle('hidden', !on); m.classList.toggle('flex', on); document.body.style.overflow = on ? 'hidden' : ''; };
            window.stTab = function (t) {
                $('tab_manage').classList.toggle('hidden', t !== 'manage'); $('tab_preview').classList.toggle('hidden', t !== 'preview');
                document.querySelectorAll('.sttab').forEach(function (b) { b.className = 'sttab py-2.5 rounded-lg text-xs font-bold ' + (b.dataset.tab === t ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500'); });
            };
            document.querySelectorAll('.sttab').forEach(function (b) { b.addEventListener('click', function () { stTab(this.dataset.tab); }); });
            window.stForm = function (id) {
                var c = id ? C.filter(function (x) { return +x.id === +id; })[0] : null;
                $('f_id').value = c ? c.id : 0; $('stFormTitle').textContent = c ? 'Edit Clause' : 'Add Clause';
                $('f_title').value = c ? c.title : ''; $('f_content').value = c ? c.content : ''; $('f_status').value = c ? c.status : 'Active';
                stModal('stForm', true);
            };
            ['stForm', 'stText'].forEach(function (id) { $(id).addEventListener('mousedown', function (e) { if (e.target === this) { stModal(id, false); } }); });
        })();
    </script>
<?php } ?>

<?php if ($sec === 'password' && $pwCol !== '') { ?>
    <script>
        (function () {
            var $ = function (id) { return document.getElementById(id); };
            var MIN = <?php echo (int) $PW_MIN; ?>;

            /* show / hide password */
            document.querySelectorAll('[data-eye]').forEach(function (b) {
                b.addEventListener('click', function () {
                    var f = $(this.dataset.eye); if (!f) { return; }
                    var show = f.type === 'password'; f.type = show ? 'text' : 'password';
                    this.firstChild.className = show ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
                });
            });

            var nw = $('pw_new'), cf = $('pw_conf'), btn = $('pwSubmit');
            if (nw && cf && btn) {
                function rule(id, ok) { var el = $(id); el.className = ok ? 'text-emerald-600 font-semibold' : 'text-slate-500'; el.firstChild.className = ok ? 'fa-solid fa-circle-check mr-1.5' : 'fa-regular fa-circle mr-1.5'; }
                function check() {
                    var v = nw.value, c = cf.value;
                    var okLen = v.length >= MIN, okLet = /[A-Za-z]/.test(v), okNum = /[0-9]/.test(v);
                    rule('r_len', okLen); rule('r_let', okLet); rule('r_num', okNum);
                    var score = (okLen ? 1 : 0) + (okLet ? 1 : 0) + (okNum ? 1 : 0) + (v.length >= 12 ? 1 : 0) + (/[^A-Za-z0-9]/.test(v) ? 1 : 0) + (/[a-z]/.test(v) && /[A-Z]/.test(v) ? 1 : 0);
                    var bar = $('pwBar'), tx = $('pwStrength');
                    if (!v) { bar.style.width = '0'; tx.textContent = '\u00a0'; }
                    else if (score <= 2) { bar.style.width = '33%'; bar.style.background = '#f43f5e'; tx.textContent = 'Weak'; tx.style.color = '#e11d48'; }
                    else if (score <= 4) { bar.style.width = '66%'; bar.style.background = '#f59e0b'; tx.textContent = 'Medium'; tx.style.color = '#d97706'; }
                    else { bar.style.width = '100%'; bar.style.background = '#10b981'; tx.textContent = 'Strong'; tx.style.color = '#059669'; }
                    var m = $('pwMatch'), same = c !== '' && v === c;
                    if (!c) { m.textContent = '\u00a0'; } else if (same) { m.textContent = '\u2714 Passwords match'; m.style.color = '#059669'; } else { m.textContent = '\u2716 Passwords do not match'; m.style.color = '#e11d48'; }
                    btn.disabled = !(okLen && okLet && okNum && same);
                }
                nw.addEventListener('input', check); cf.addEventListener('input', check); check();

                /* countdown (step 2 somoy) */
                var t = $('pwTimer'), left = <?php echo (int) max(0, $pwUntil - $pwNow); ?>;
                if (t) {
                    var iv = setInterval(function () {
                        left--; if (left <= 0) { clearInterval(iv); location.reload(); return; }
                        t.textContent = Math.floor(left / 60) + ':' + ('0' + (left % 60)).slice(-2);
                    }, 1000);
                }
            }
        })();
    </script>
<?php } ?>