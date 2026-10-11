<?php
/* ==========================================================
   events.php  (PHP 7.2 – 8.4 compatible, single file)
   Page + Add/Edit/Delete/View + Volunteer assign + Live search
   + Pagination + Session filter

   PERMISSION:
   - Admin            : create + edit + delete + view
   - Volunteer Member : edit + view
   - Others           : view only

   COMPATIBILITY NOTE (live server fix):
   - mysqli_stmt_get_result() (mysqlnd) না থাকলে fallback আছে
   - mbstring না থাকলে fallback আছে
   - JSON_INVALID_UTF8_SUBSTITUTE না থাকলে fallback আছে
   - PHP 8.1+ এর mysqli exception এবং PHP 7.2 এর false রিটার্ন, দুটোই সামলানো আছে
   ========================================================== */

if (session_status() === PHP_SESSION_NONE) { @session_start(); }

// login ছাড়া ঢুকতে পারবে না
if (!isset($_SESSION['user_type'])) {
    header('Location: index.php?page=dashboard');
    exit;
}

if (!isset($db)) { ob_start(); include 'include/header.php'; ob_end_clean(); }

// পুরনো PHP তে এই ধ্রুবক নাও থাকতে পারে
if (!defined('JSON_INVALID_UTF8_SUBSTITUTE')) { define('JSON_INVALID_UTF8_SUBSTITUTE', 0); }
if (!defined('JSON_UNESCAPED_UNICODE'))       { define('JSON_UNESCAPED_UNICODE', 256); }

// utf8mb4 না পেলে utf8 দিয়ে চালানো (পুরনো MySQL এর জন্য)
if (!@mysqli_set_charset($db, "utf8mb4")) { @mysqli_set_charset($db, "utf8"); }

/* ==========================================================
   ১) সেটিংস (এখানে সহজে বদলাতে পারবেন)
   ========================================================== */
$event_dir  = 'public/uploads/events/';     // ইভেন্টের ছবির ফোল্ডার
$member_dir = 'public/uploads/members/';    // মেম্বারের ছবির ফোল্ডার
$vol_types  = ['Volunteer Member', 'Volunteer'];   // কোন user_type volunteer ধরা হবে
$categories = ['Medical Camp', 'Education', 'Food Distribution', 'Fundraising', 'Awareness', 'Training', 'Relief', 'Other'];
$statuses   = ['Upcoming', 'Ongoing', 'Completed', 'Cancelled'];
$per_opts   = [6, 12, 24, 48];              // প্রতি পেজে কয়টা কার্ড

/* ==========================================================
   ২) Role (ডাটাবেজ থেকে যাচাই)
   ========================================================== */
$me = 0;
foreach (['user_id', 'id', 'uid', 'member_id'] as $sk) {
    if (isset($_SESSION[$sk]) && (int) $_SESSION[$sk] > 0) { $me = (int) $_SESSION[$sk]; break; }
}
$utype = (string) $_SESSION['user_type'];
if ($me > 0) {
    $ures = mysqli_query($db, "SELECT user_type FROM users WHERE id = " . $me . " LIMIT 1");
    if ($ures) {
        $ur = mysqli_fetch_row($ures);
        if ($ur) { $utype = $ur[0]; }
    }
}
$isAdmin   = ($utype === 'Admin');
$isVol     = in_array($utype, $vol_types, true);
$canCreate = $isAdmin;               // নতুন ইভেন্ট: শুধু Admin
$canEdit   = ($isAdmin || $isVol);   // এডিট: Admin + Volunteer
$canDelete = $isAdmin;               // ডিলিট: শুধু Admin

if (empty($_SESSION['csrf'])) {
    if (function_exists('random_bytes')) {
        $_SESSION['csrf'] = bin2hex(random_bytes(16));
    } else {
        $_SESSION['csrf'] = md5(uniqid((string) mt_rand(), true));
    }
}

/* ==========================================================
   ৩) Helper ফাংশন
   ========================================================== */
if (!function_exists('h')) {
    function h($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
}

// mbstring না থাকলে ব্যবহারের জন্য: নামের প্রথম অক্ষর (বড় হাতের)
function evt_initial($s) {
    $s = (string) $s;
    if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
        return mb_strtoupper(mb_substr($s, 0, 1, 'UTF-8'), 'UTF-8');
    }
    if (preg_match('/^./su', $s, $m)) { return strtoupper($m[0]); }
    return '?';
}

// JSON পাঠানো। চিহ্ন দেওয়া আছে, তাই আগে-পরে অন্য আউটপুট থাকলেও JS ঠিক অংশ পড়তে পারে
function evt_json($arr) {
    while (ob_get_level()) { ob_end_clean(); }
    if (!headers_sent()) { header('Content-Type: text/plain; charset=utf-8'); }
    $json = json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    if ($json === false) {
        $json = json_encode(['ok' => false, 'msg' => 'JSON তৈরি করা যায়নি (ডাটায় ভুল অক্ষর থাকতে পারে)।']);
    }
    echo '<<EVT_START>>' . $json . '<<EVT_END>>';
    exit;
}

function evt_money($n) { return '৳' . number_format((float) $n); }

// SQL চালানো। ভুল হলে Exception দেয়, যাতে আসল এরর দেখা যায়
function evt_q($db, $sql, $types = '', $params = []) {
    $st = mysqli_prepare($db, $sql);
    if (!$st) { throw new Exception('SQL error: ' . mysqli_error($db)); }
    if ($types !== '') {
        // reference দিয়ে bind (সব PHP ভার্সনে নিরাপদ)
        $refs = [];
        foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
        array_unshift($refs, $types);
        array_unshift($refs, $st);
        call_user_func_array('mysqli_stmt_bind_param', $refs);
    }
    if (!mysqli_stmt_execute($st)) { throw new Exception('SQL error: ' . mysqli_stmt_error($st)); }
    return $st;
}

// সব রো array আকারে আনা (mysqlnd না থাকলেও চলবে)
function evt_all($st) {
    $out = [];
    if (function_exists('mysqli_stmt_get_result')) {
        $res = mysqli_stmt_get_result($st);
        if ($res) {
            while ($r = mysqli_fetch_assoc($res)) { $out[] = $r; }
        }
        return $out;
    }
    // fallback: bind_result
    mysqli_stmt_store_result($st);
    $meta = mysqli_stmt_result_metadata($st);
    if (!$meta) { return $out; }
    $row = [];
    $bind = [];
    while ($f = mysqli_fetch_field($meta)) {
        $row[$f->name] = null;
        $bind[] = &$row[$f->name];
    }
    mysqli_free_result($meta);
    array_unshift($bind, $st);
    call_user_func_array('mysqli_stmt_bind_result', $bind);
    while (mysqli_stmt_fetch($st)) {
        $copy = [];
        foreach ($row as $k => $v) { $copy[$k] = $v; }
        $out[] = $copy;
    }
    return $out;
}

// প্রথম রো (না থাকলে null)
function evt_one($st) {
    $rows = evt_all($st);
    return $rows ? $rows[0] : null;
}

function evt_where($q, $status, $cat, &$types, &$params) {
    $w = "WHERE 1=1"; $types = ''; $params = [];
    if ($q !== '') {
        $w .= " AND (title LIKE ? OR location LIKE ? OR organizer LIKE ?)";
        $l = '%' . addcslashes($q, '%_\\') . '%';
        $types .= 'sss'; $params[] = $l; $params[] = $l; $params[] = $l;
    }
    if ($status !== '') { $w .= " AND status = ?";   $types .= 's'; $params[] = $status; }
    if ($cat !== '')    { $w .= " AND category = ?"; $types .= 's'; $params[] = $cat; }
    return $w;
}

// উপরের কার্ডগুলোর সংখ্যা
function evt_stats($db, $q, $status, $cat, $total) {
    $t = ''; $p = [];
    $w = evt_where($q, '', $cat, $t, $p);
    $m = [];
    foreach (evt_all(evt_q($db, "SELECT status, COUNT(*) AS c FROM events $w GROUP BY status", $t, $p)) as $r) {
        $m[$r['status']] = (int) $r['c'];
    }

    $t = ''; $p = [];
    $w = evt_where($q, $status, $cat, $t, $p);
    $r = evt_one(evt_q($db, "SELECT COALESCE(SUM(budget),0) AS b, COALESCE(SUM(actual_cost),0) AS a FROM events $w", $t, $p));
    if (!$r) { $r = ['b' => 0, 'a' => 0]; }

    return [
        'total'     => $total,
        'Upcoming'  => isset($m['Upcoming'])  ? $m['Upcoming']  : 0,
        'Ongoing'   => isset($m['Ongoing'])   ? $m['Ongoing']   : 0,
        'Completed' => isset($m['Completed']) ? $m['Completed'] : 0,
        'budget'    => evt_money($r['b']),
        'spent'     => evt_money($r['a']),
    ];
}

function evt_style($s) {
    $m = [
        'Upcoming'  => ['bg-sky-100 text-sky-700',         'fa-hourglass-start'],
        'Ongoing'   => ['bg-emerald-100 text-emerald-700', 'fa-play'],
        'Completed' => ['bg-slate-200 text-slate-700',     'fa-circle-check'],
        'Cancelled' => ['bg-rose-100 text-rose-700',       'fa-ban'],
    ];
    return isset($m[$s]) ? $m[$s] : $m['Upcoming'];
}

// ছবি আপলোড। সফল হলে ফাইলের নাম, ছবি না দিলে '', ভুল হলে false
function evt_upload($file, $dir, &$err) {
    if (!is_array($file) || $file['error'] === UPLOAD_ERR_NO_FILE) { return ''; }
    if ($file['error'] !== 0) { $err = 'ছবি আপলোড করা যায়নি।'; return false; }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp']) || !@getimagesize($file['tmp_name'])) {
        $err = 'ছবি শুধু JPG, PNG বা WEBP হতে হবে।'; return false;
    }
    if ($file['size'] > 3 * 1024 * 1024) { $err = 'ছবির সাইজ সর্বোচ্চ 3MB।'; return false; }
    if (!is_dir($dir)) { @mkdir($dir, 0755, true); }
    $rand = function_exists('random_int') ? random_int(1000, 9999) : mt_rand(1000, 9999);
    $name = 'event_' . time() . '_' . $rand . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) { $err = 'ছবি সেভ করা যায়নি।'; return false; }
    return $name;
}

function evt_unlink($dir, $f) {
    if ($f != '' && is_file($dir . basename($f))) { @unlink($dir . basename($f)); }
}

function evt_avatar($v, $dir, $cls = 'w-7 h-7 text-[10px]') {
    if ($v['photo'] != '' && is_file($dir . basename($v['photo']))) {
        return '<img src="' . h($dir . $v['photo']) . '" title="' . h($v['member_name']) . '" class="' . $cls . ' rounded-full object-cover border-2 border-white">';
    }
    return '<div title="' . h($v['member_name']) . '" class="' . $cls . ' rounded-full bg-emerald-100 text-emerald-700 border-2 border-white flex items-center justify-center font-bold">'
        . h(evt_initial($v['member_name'])) . '</div>';
}

function evt_dates($r) {
    $a = strtotime($r['start_date']); $b = strtotime($r['end_date']);
    return $a == $b ? date('d M Y', $a) : date('d M', $a) . ' – ' . date('d M Y', $b);
}

// একটা ইভেন্ট কার্ডের HTML
function evt_card($r, $vols, $ed, $md, $canEdit = false, $canDelete = false) {
    $img = ($r['image'] != '' && is_file($ed . basename($r['image']))) ? $ed . $r['image'] : '';
    $s = evt_style($r['status']);
    $cover = $img
        ? '<img src="' . h($img) . '" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">'
        : '<div class="w-full h-full bg-gradient-to-br from-emerald-500 via-teal-600 to-slate-800 flex items-center justify-center text-white/80 text-4xl"><i class="fa-solid fa-calendar-days"></i></div>';
    $time = ($r['start_time'] ? ' • ' . date('h:i A', strtotime($r['start_time'])) : '');
    $budget = (float) $r['budget']; $act = (float) $r['actual_cost'];
    $pct = $budget > 0 ? min(100, round($act / $budget * 100)) : 0;
    $bar = $budget > 0
        ? '<div class="h-1.5 rounded-full bg-slate-100 overflow-hidden mt-1.5"><div class="h-full rounded-full ' . ($act > $budget ? 'bg-rose-500' : 'bg-emerald-500') . '" style="width:' . $pct . '%"></div></div>'
        : '';

    $stack = '';
    foreach (array_slice($vols, 0, 4) as $v) { $stack .= evt_avatar($v, $md); }
    if (count($vols) > 4) {
        $stack .= '<div class="w-7 h-7 rounded-full bg-slate-800 text-white border-2 border-white flex items-center justify-center text-[10px] font-bold">+' . (count($vols) - 4) . '</div>';
    }
    if (!$vols) { $stack = '<span class="text-[11px] text-slate-400">No volunteer assigned</span>'; }

    $max = (int) $r['max_volunteers'];
    $id  = (int) $r['id'];

    // বাটন (role অনুযায়ী)
    $btns = '<button type="button" data-act="view" data-id="' . $id . '" class="py-2 rounded-xl border border-slate-200 text-[11px] font-semibold text-slate-600 hover:bg-slate-50"><i class="fa-solid fa-eye"></i> View</button>';
    $cols = 'grid-cols-1';
    if ($canEdit) {
        $btns .= '<button type="button" data-act="edit" data-id="' . $id . '" class="py-2 rounded-xl border border-sky-200 bg-sky-50 text-[11px] font-semibold text-sky-700 hover:bg-sky-100"><i class="fa-solid fa-pen-to-square"></i> Edit</button>';
        $cols = 'grid-cols-2';
    }
    if ($canDelete) {
        $btns .= '<button type="button" data-act="delete" data-id="' . $id . '" data-name="' . h($r['title']) . '" class="py-2 rounded-xl border border-rose-200 bg-rose-50 text-[11px] font-semibold text-rose-600 hover:bg-rose-100"><i class="fa-solid fa-trash"></i> Delete</button>';
        $cols = 'grid-cols-3';
    }

    return '<div class="group bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col hover:shadow-lg transition">
      <div class="relative h-40 bg-slate-100 overflow-hidden cursor-pointer" data-act="view" data-id="' . $id . '">' . $cover . '
        <span class="absolute top-3 left-3 px-2.5 py-1 rounded-full text-[10px] font-bold flex items-center gap-1 ' . $s[0] . '"><i class="fa-solid ' . $s[1] . '"></i>' . h($r['status']) . '</span>
        <span class="absolute top-3 right-3 px-2.5 py-1 rounded-full text-[10px] font-semibold bg-slate-900/70 text-white backdrop-blur">' . h($r['category']) . '</span>
      </div>
      <div class="p-4 flex-1 flex flex-col gap-3">
        <h3 class="font-bold text-slate-800 text-sm leading-snug cursor-pointer hover:text-emerald-600" data-act="view" data-id="' . $id . '">' . h($r['title']) . '</h3>
        <div class="space-y-1.5 text-xs text-slate-500">
          <p class="flex items-start gap-2"><i class="fa-regular fa-calendar text-emerald-600 mt-0.5 w-3.5"></i><span>' . h(evt_dates($r)) . h($time) . '</span></p>
          <p class="flex items-start gap-2"><i class="fa-solid fa-location-dot text-rose-500 mt-0.5 w-3.5"></i><span>' . h($r['location']) . '</span></p>
        </div>
        <div class="p-3 rounded-xl bg-slate-50 border border-slate-100">
          <div class="flex justify-between text-[11px]"><span class="text-slate-400 font-semibold uppercase">Budget</span><span class="font-bold text-slate-700">' . evt_money($budget) . '</span></div>
          <div class="flex justify-between text-[11px] mt-1"><span class="text-slate-400 font-semibold uppercase">Spent</span><span class="font-bold ' . ($act > $budget && $budget > 0 ? 'text-rose-600' : 'text-emerald-600') . '">' . evt_money($act) . '</span></div>' . $bar . '
        </div>
        <div class="mt-auto flex items-center justify-between gap-2">
          <div class="flex -space-x-2 items-center">' . $stack . '</div>
          <span class="text-[11px] font-semibold text-slate-500 whitespace-nowrap"><i class="fa-solid fa-user-group mr-1"></i>' . count($vols) . ($max > 0 ? '/' . $max : '') . '</span>
        </div>
      </div>
      <div class="px-4 pb-4 grid ' . $cols . ' gap-2">' . $btns . '</div></div>';
}

// পেজিনেশন বাটন
function evt_pager($page, $pages) {
    if ($pages <= 1) { return ''; }
    $btn = function ($p, $label, $active = false, $dis = false) {
        $c = $active ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50';
        if ($dis) { $c = 'bg-slate-50 text-slate-300 border-slate-100 cursor-not-allowed'; }
        return '<button type="button" ' . ($dis ? 'disabled' : 'data-page="' . $p . '"') . ' class="min-w-8 h-8 px-2 rounded-lg border text-xs font-semibold ' . $c . '">' . $label . '</button>';
    };
    $out = $btn(max(1, $page - 1), '&lsaquo;', false, $page == 1);
    $set = [1, $pages];
    for ($i = $page - 1; $i <= $page + 1; $i++) { if ($i > 0 && $i <= $pages) { $set[] = $i; } }
    $set = array_unique($set); sort($set);
    $prev = 0;
    foreach ($set as $p) {
        if ($p - $prev > 1) { $out .= '<span class="px-1 text-slate-400">…</span>'; }
        $out .= $btn($p, $p, $p == $page);
        $prev = $p;
    }
    return $out . $btn(min($pages, $page + 1), '&rsaquo;', false, $page == $pages);
}

// একটা ইভেন্টের volunteer তালিকা
function evt_vols_of($db, $eid) {
    return evt_all(evt_q($db, "SELECT u.id, u.member_name, u.mobile_no, u.photo FROM event_volunteers ev JOIN users u ON u.id = ev.user_id WHERE ev.event_id = ? ORDER BY ev.id", 'i', [$eid]));
}

/* ==========================================================
   ৪) AJAX HANDLER (?act=list / get / save / delete / clear)
   ========================================================== */
$act = isset($_GET['act']) ? $_GET['act'] : '';
if ($act !== '') {
    try {

        // POST হলে CSRF যাচাই (header অথবা ফর্ম ডাটা, যেকোনো একটা)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tok = isset($_SERVER['HTTP_X_CSRF']) ? $_SERVER['HTTP_X_CSRF'] : (isset($_POST['csrf']) ? $_POST['csrf'] : '');
            if (!hash_equals($_SESSION['csrf'], (string) $tok)) {
                evt_json(['ok' => false, 'msg' => 'Invalid session. পেজ রিলোড করুন।']);
            }
        }

        /* ----- LIST ----- */
        if ($act === 'list') {
            $q      = trim(isset($_GET['es'])  ? $_GET['es']  : '');
            $status = trim(isset($_GET['est']) ? $_GET['est'] : '');
            $cat    = trim(isset($_GET['ec'])  ? $_GET['ec']  : '');
            $per    = (int) (isset($_GET['per']) ? $_GET['per'] : 6);
            if (!in_array($per, $per_opts)) { $per = 6; }
            $page = max(1, (int) (isset($_GET['pg']) ? $_GET['pg'] : 1));

            $types = ''; $params = [];
            $where = evt_where($q, $status, $cat, $types, $params);

            $cr = evt_one(evt_q($db, "SELECT COUNT(*) AS c FROM events $where", $types, $params));
            $total = $cr ? (int) $cr['c'] : 0;

            $pages = max(1, (int) ceil($total / $per));
            if ($page > $pages) { $page = $pages; }
            $offset = ($page - 1) * $per;
            $_SESSION['evt_filter'] = ['q' => $q, 'status' => $status, 'cat' => $cat, 'per' => $per, 'page' => $page];

            $rows = evt_all(evt_q($db, "SELECT * FROM events $where ORDER BY start_date DESC, id DESC LIMIT ? OFFSET ?",
                        $types . 'ii', array_merge($params, [$per, $offset])));

            // এই পেজের সব ইভেন্টের volunteer একবারে আনা
            $vm = [];
            if ($rows) {
                $in = implode(',', array_map('intval', array_column($rows, 'id')));
                $vr = mysqli_query($db, "SELECT ev.event_id, u.id, u.member_name, u.photo FROM event_volunteers ev JOIN users u ON u.id = ev.user_id WHERE ev.event_id IN ($in) ORDER BY ev.id");
                while ($vr && ($v = mysqli_fetch_assoc($vr))) { $vm[$v['event_id']][] = $v; }
            }

            $html = '';
            foreach ($rows as $r) {
                $html .= evt_card($r, isset($vm[$r['id']]) ? $vm[$r['id']] : [], $event_dir, $member_dir, $canEdit, $canDelete);
            }
            if ($html === '') {
                $html = '<div class="col-span-full py-16 text-center text-slate-400"><i class="fa-regular fa-calendar-xmark text-4xl mb-3"></i><p class="text-sm font-semibold">কোনো ইভেন্ট পাওয়া যায়নি।</p></div>';
            }

            evt_json([
                'ok' => true, 'rows' => $html, 'pager' => evt_pager($page, $pages),
                'page' => $page, 'total' => $total,
                'from' => $total ? $offset + 1 : 0, 'to' => min($offset + $per, $total),
                'stats' => evt_stats($db, $q, $status, $cat, $total),
                'filtered' => ($q !== '' || $status !== '' || $cat !== ''),
            ]);
        }

        /* ----- CLEAR FILTER ----- */
        if ($act === 'clear') { unset($_SESSION['evt_filter']); evt_json(['ok' => true]); }

        /* ----- GET ONE (view / edit) ----- */
        if ($act === 'get') {
            $id = (int) (isset($_GET['id']) ? $_GET['id'] : 0);
            $row = evt_one(evt_q($db, "SELECT * FROM events WHERE id = ? LIMIT 1", 'i', [$id]));
            if (!$row) { evt_json(['ok' => false, 'msg' => 'ইভেন্ট পাওয়া যায়নি।']); }
            $row['image_url'] = ($row['image'] != '' && is_file($event_dir . basename($row['image']))) ? $event_dir . $row['image'] : '';
            $vols = evt_vols_of($db, $id);
            foreach ($vols as $vi => $v) {
                $vols[$vi]['photo_url'] = ($v['photo'] != '' && is_file($member_dir . basename($v['photo']))) ? $member_dir . $v['photo'] : '';
            }
            evt_json(['ok' => true, 'data' => $row, 'vols' => $vols]);
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') { evt_json(['ok' => false, 'msg' => 'Invalid request.']); }

        /* ----- DELETE (শুধু Admin) ----- */
        if ($act === 'delete') {
            if (!$canDelete) { evt_json(['ok' => false, 'msg' => 'শুধু Admin ইভেন্ট ডিলিট করতে পারবে।']); }
            $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
            $old = evt_one(evt_q($db, "SELECT image FROM events WHERE id = ? LIMIT 1", 'i', [$id]));
            if (!$old) { evt_json(['ok' => false, 'msg' => 'ইভেন্ট পাওয়া যায়নি।']); }

            evt_q($db, "DELETE FROM event_volunteers WHERE event_id = ?", 'i', [$id]);
            $st = evt_q($db, "DELETE FROM events WHERE id = ?", 'i', [$id]);
            if (mysqli_stmt_affected_rows($st) > 0) {
                evt_unlink($event_dir, $old['image']);
                evt_json(['ok' => true, 'msg' => 'ইভেন্ট ডিলিট করা হয়েছে।']);
            }
            evt_json(['ok' => false, 'msg' => 'ডিলিট করা যায়নি।']);
        }

        /* ----- SAVE (add = শুধু Admin, edit = Admin + Volunteer) ----- */
        if ($act === 'save') {
            $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
            if ($id > 0 && !$canEdit)    { evt_json(['ok' => false, 'msg' => 'আপনার এই ইভেন্ট এডিট করার অনুমতি নেই।']); }
            if ($id <= 0 && !$canCreate) { evt_json(['ok' => false, 'msg' => 'শুধু Admin নতুন ইভেন্ট তৈরি করতে পারবে।']); }

            // ফর্মের ফিল্ড (এই ক্রম SQL এর ক্রমের সাথে মেলানো, বদলাবেন না)
            $f = [];
            $keys = ['title', 'category', 'description', 'location', 'start_date', 'end_date', 'start_time', 'end_time',
                     'budget', 'actual_cost', 'expected_beneficiaries', 'max_volunteers', 'organizer', 'contact_phone', 'status'];
            foreach ($keys as $k) { $f[$k] = trim(isset($_POST[$k]) ? $_POST[$k] : ''); }

            if ($f['title'] === '' || $f['location'] === '' || $f['start_date'] === '' || $f['end_date'] === '') {
                evt_json(['ok' => false, 'msg' => 'সব প্রয়োজনীয় (*) তথ্য পূরণ করুন।']);
            }
            if (strtotime($f['end_date']) < strtotime($f['start_date'])) {
                evt_json(['ok' => false, 'msg' => 'শেষ তারিখ শুরুর তারিখের আগে হতে পারবে না।']);
            }
            if (!in_array($f['status'], $statuses)) { evt_json(['ok' => false, 'msg' => 'সঠিক Status দিন।']); }
            if (!in_array($f['category'], $categories)) { $f['category'] = 'Other'; }

            $f['budget']                 = max(0, (float) $f['budget']);
            $f['actual_cost']            = max(0, (float) $f['actual_cost']);
            $f['expected_beneficiaries'] = max(0, (int) $f['expected_beneficiaries']);
            $f['max_volunteers']         = max(0, (int) $f['max_volunteers']);
            $f['start_time'] = $f['start_time'] === '' ? null : $f['start_time'];
            $f['end_time']   = $f['end_time']   === '' ? null : $f['end_time'];

            $vol_raw = isset($_POST['volunteers']) ? (array) $_POST['volunteers'] : [];
            $vol_ids = array_values(array_unique(array_filter(array_map('intval', $vol_raw))));
            if ($f['max_volunteers'] > 0 && count($vol_ids) > $f['max_volunteers']) {
                evt_json(['ok' => false, 'msg' => 'Max volunteer limit (' . $f['max_volunteers'] . ') এর বেশি assign করা যাবে না।']);
            }

            // ছবি আপলোড
            $err = '';
            $new = evt_upload(isset($_FILES['image']) ? $_FILES['image'] : null, $event_dir, $err);
            if ($new === false) { evt_json(['ok' => false, 'msg' => $err]); }

            $vals = array_values($f);   // ১৫টি ভ্যালু

            try {
                if ($id > 0) {
                    // ---------- আপডেট ----------
                    $old = evt_one(evt_q($db, "SELECT image FROM events WHERE id = ? LIMIT 1", 'i', [$id]));
                    if (!$old) { evt_unlink($event_dir, $new); evt_json(['ok' => false, 'msg' => 'ইভেন্ট পাওয়া যায়নি।']); }

                    if ($new !== '')                         { $image = $new; }
                    elseif (!empty($_POST['remove_image']))  { $image = ''; }
                    else                                     { $image = $old['image']; }

                    evt_q($db, "UPDATE events SET image=?, title=?, category=?, description=?, location=?, start_date=?, end_date=?, start_time=?, end_time=?,
                        budget=?, actual_cost=?, expected_beneficiaries=?, max_volunteers=?, organizer=?, contact_phone=?, status=? WHERE id=?",
                        str_repeat('s', 16) . 'i', array_merge([$image], $vals, [$id]));

                    if ($image !== $old['image']) { evt_unlink($event_dir, $old['image']); }   // পুরনো ছবি মোছা
                    $eid = $id; $msg = 'ইভেন্ট আপডেট হয়েছে।';
                } else {
                    // ---------- নতুন ----------
                    evt_q($db, "INSERT INTO events (image, title, category, description, location, start_date, end_date, start_time, end_time,
                        budget, actual_cost, expected_beneficiaries, max_volunteers, organizer, contact_phone, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                        str_repeat('s', 16), array_merge([$new], $vals));
                    $eid = mysqli_insert_id($db); $msg = 'নতুন ইভেন্ট তৈরি হয়েছে।';
                }

                // volunteer তালিকা নতুন করে বসানো
                evt_q($db, "DELETE FROM event_volunteers WHERE event_id = ?", 'i', [$eid]);
                foreach ($vol_ids as $uid) {
                    // শুধু বৈধ user হলে insert হবে
                    $exists = evt_one(evt_q($db, "SELECT id FROM users WHERE id = ? LIMIT 1", 'i', [$uid]));
                    if ($exists) {
                        evt_q($db, "INSERT IGNORE INTO event_volunteers (event_id, user_id) VALUES (?, ?)", 'ii', [$eid, $uid]);
                    }
                }
            } catch (Exception $e) {
                evt_unlink($event_dir, $new);   // সেভ না হলে আপলোড করা ছবি মুছে ফেলা
                throw $e;
            }

            evt_json(['ok' => true, 'msg' => $msg]);
        }

        evt_json(['ok' => false, 'msg' => 'Unknown action.']);

    } catch (Exception $e) {
        evt_json(['ok' => false, 'msg' => $e->getMessage()]);
    } catch (Throwable $e) {
        evt_json(['ok' => false, 'msg' => 'Error: ' . $e->getMessage()]);
    }
}

/* ==========================================================
   ৫) পেজ লোডের ডাটা
   ========================================================== */
$saved = isset($_SESSION['evt_filter']) ? $_SESSION['evt_filter'] : ['q' => '', 'status' => '', 'cat' => '', 'per' => 6, 'page' => 1];
if (!in_array($saved['status'], array_merge([''], $statuses))) { $saved['status'] = ''; }
if (!in_array($saved['cat'], array_merge([''], $categories))) { $saved['cat'] = ''; }

// Assign করার জন্য সব member (শুধু এডিট করতে পারে যারা, তাদের জন্য)
$VOLS = [];
if ($canEdit) {
    $vres = mysqli_query($db, "SELECT id, member_name, mobile_no, photo, user_type, status FROM users ORDER BY member_name");
    while ($vres && ($v = mysqli_fetch_assoc($vres))) {
        $v['photo_url'] = ($v['photo'] != '' && is_file($member_dir . basename($v['photo']))) ? $member_dir . $v['photo'] : '';
        $v['is_vol'] = in_array($v['user_type'], $vol_types) ? 1 : 0;
        unset($v['photo']);
        $VOLS[] = $v;
    }
    usort($VOLS, function ($x, $y) { return $y['is_vol'] - $x['is_vol']; });   // volunteer উপরে
}

// ফর্মের ক্লাস (এক জায়গা থেকে বদলানো যাবে)
$inp = 'w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10';
$lbl = 'block text-[11px] font-bold uppercase text-slate-500 mb-1';
?>

<section class="page-content space-y-5">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-800 tracking-tight">Event Management</h2>
            <p class="text-xs text-slate-500 mt-0.5">
                <?php
                if ($isAdmin)      { echo 'Admin — create, edit and delete events, track costs and assign volunteers.'; }
                elseif ($isVol)    { echo 'Volunteer — you can view and edit events.'; }
                else               { echo 'View all events, schedules and assigned volunteers.'; }
                ?>
            </p>
        </div>
        <?php if ($canCreate) { ?>
            <button type="button" onclick="evtOpenForm()"
                class="px-4 py-3 sm:py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-emerald-600/20 flex items-center justify-center gap-2">
                <i class="fa-solid fa-plus"></i> Create Event
            </button>
        <?php } elseif (!$canEdit) { ?>
            <span class="px-3 py-2 rounded-xl bg-slate-100 text-slate-500 text-[11px] font-bold"><i class="fa-solid fa-eye mr-1.5"></i>View only</span>
        <?php } ?>
    </div>

    <!-- Stats কার্ড -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
        <?php
        // [data-stat key, লেখা, আইকনের রঙ, আইকন, ক্লিকে কোন status ফিল্টার]
        $cards = [
            ['total',     'Total Events', 'bg-slate-50 text-slate-600',     'fa-calendar-days',  ''],
            ['Upcoming',  'Upcoming',     'bg-sky-50 text-sky-600',         'fa-hourglass-start', 'Upcoming'],
            ['Ongoing',   'Ongoing',      'bg-emerald-50 text-emerald-600', 'fa-play',            'Ongoing'],
            ['Completed', 'Completed',    'bg-violet-50 text-violet-600',   'fa-circle-check',    'Completed'],
        ];
        foreach ($cards as $c) { ?>
            <div <?php echo $c[4] ? 'data-fstatus="' . $c[4] . '"' : ''; ?>
                 class="p-3.5 sm:p-5 bg-white rounded-2xl ring-1 ring-slate-200/80 shadow-xs flex items-center gap-3 <?php echo $c[4] ? 'cursor-pointer hover:shadow-md transition' : ''; ?>">
                <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl <?php echo $c[2]; ?> flex items-center justify-center text-lg shrink-0">
                    <i class="fa-solid <?php echo $c[3]; ?>"></i>
                </div>
                <div class="min-w-0">
                    <p class="text-[10px] font-bold text-slate-400 uppercase truncate"><?php echo $c[1]; ?></p>
                    <h3 class="text-xl sm:text-2xl font-bold text-slate-800" data-stat="<?php echo $c[0]; ?>">0</h3>
                </div>
            </div>
        <?php } ?>
        <div class="col-span-2 lg:col-span-1 p-3.5 sm:p-5 bg-gradient-to-br from-slate-900 to-slate-700 text-white rounded-2xl shadow-xs">
            <p class="text-[10px] font-bold text-slate-300 uppercase">Total Budget</p>
            <h3 class="text-xl sm:text-2xl font-bold" data-stat="budget">৳0</h3>
            <p class="text-[11px] text-emerald-300 mt-0.5">Spent: <span data-stat="spent">৳0</span></p>
        </div>
    </div>

    <!-- ফিল্টার -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 space-y-3">
        <div class="grid grid-cols-2 md:grid-cols-12 gap-3">
            <div class="col-span-2 md:col-span-5 relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" id="fSearch" value="<?php echo h($saved['q']); ?>" placeholder="Search title, location or organizer..."
                    class="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-emerald-500">
            </div>
            <select id="fCat" class="md:col-span-3 <?php echo $inp; ?>">
                <option value="">All Categories</option>
                <?php foreach ($categories as $c) { echo '<option value="' . h($c) . '"' . ($saved['cat'] === $c ? ' selected' : '') . '>' . h($c) . '</option>'; } ?>
            </select>
            <select id="fStatus" class="md:col-span-2 <?php echo $inp; ?>">
                <option value="">All Status</option>
                <?php foreach ($statuses as $c) { echo '<option value="' . $c . '"' . ($saved['status'] === $c ? ' selected' : '') . '>' . $c . '</option>'; } ?>
            </select>
            <select id="fPer" class="md:col-span-2 <?php echo $inp; ?>">
                <?php foreach ($per_opts as $n) { echo '<option value="' . $n . '"' . ((int) $saved['per'] === $n ? ' selected' : '') . '>' . $n . ' / page</option>'; } ?>
            </select>
        </div>
        <button type="button" id="fClear" onclick="evtClear()"
            class="hidden px-3 py-2 border border-rose-200 text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-xl text-xs font-semibold items-center gap-1.5">
            <i class="fa-solid fa-filter-circle-xmark"></i> Remove Filter
        </button>
    </div>

    <!-- ইভেন্ট কার্ড -->
    <div id="evtGrid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-5">
        <div class="col-span-full py-16 text-center text-slate-400 text-sm">Loading...</div>
    </div>

    <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
        <span id="evtInfo" class="text-xs text-slate-500"></span>
        <div id="evtPager" class="flex items-center gap-1 flex-wrap justify-center"></div>
    </div>
</section>

<?php if ($canEdit) { ?>
<!-- ================= ADD / EDIT MODAL (Admin + Volunteer) ================= -->
<div id="evtFormModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs hidden items-end sm:items-center justify-center sm:p-4 z-50">
    <div class="bg-white w-full sm:max-w-3xl max-h-[94vh] flex flex-col rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-4 sm:p-5 bg-slate-900 text-white flex justify-between items-center shrink-0">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <i class="fa-solid fa-calendar-plus text-emerald-400"></i><span id="evtFormTitle">Create Event</span>
            </h3>
            <button type="button" onclick="evtModal('evtFormModal', false)" class="text-slate-400 hover:text-white p-1"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>

        <form id="evtForm" class="p-4 sm:p-6 space-y-5 overflow-y-auto" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" name="id" id="e_id" value="0">

            <!-- Cover image -->
            <div>
                <label class="<?php echo $lbl; ?>">Cover Image (JPG/PNG/WEBP, max 3MB)</label>
                <div class="flex items-center gap-3">
                    <div class="w-28 h-20 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 flex items-center justify-center overflow-hidden shrink-0">
                        <i class="fa-solid fa-image text-slate-400 text-xl" id="e_prevIcon"></i>
                        <img id="e_prev" class="hidden w-full h-full object-cover">
                    </div>
                    <div class="min-w-0">
                        <input type="file" name="image" id="e_image" accept="image/png,image/jpeg,image/webp" class="text-xs w-full">
                        <label id="e_removeWrap" class="hidden mt-1.5 items-center gap-1.5 text-[11px] text-rose-600 font-semibold cursor-pointer">
                            <input type="checkbox" name="remove_image" value="1" id="e_remove"> Remove current image
                        </label>
                        <p class="text-[10px] text-slate-400 mt-1" id="e_hint"></p>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <h4 class="text-xs font-bold text-slate-800 border-b border-slate-100 pb-2"><i class="fa-solid fa-circle-info text-emerald-600 mr-1.5"></i>Event Details</h4>
                <div><label class="<?php echo $lbl; ?>">Event Title *</label>
                    <input type="text" name="title" id="e_title" required class="<?php echo $inp; ?>" placeholder="e.g. Free Medical Camp 2026"></div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="<?php echo $lbl; ?>">Category *</label>
                        <select name="category" id="e_category" class="<?php echo $inp; ?>"><?php foreach ($categories as $c) { echo '<option>' . h($c) . '</option>'; } ?></select></div>
                    <div><label class="<?php echo $lbl; ?>">Status *</label>
                        <select name="status" id="e_status" class="<?php echo $inp; ?>"><?php foreach ($statuses as $c) { echo '<option>' . $c . '</option>'; } ?></select></div>
                </div>
                <div><label class="<?php echo $lbl; ?>">Description</label>
                    <textarea name="description" id="e_description" rows="3" class="<?php echo $inp; ?>" placeholder="Event purpose, activities, target group..."></textarea></div>
            </div>

            <div class="space-y-3">
                <h4 class="text-xs font-bold text-slate-800 border-b border-slate-100 pb-2"><i class="fa-solid fa-location-dot text-rose-500 mr-1.5"></i>Schedule & Location</h4>
                <div><label class="<?php echo $lbl; ?>">Location *</label>
                    <input type="text" name="location" id="e_location" required class="<?php echo $inp; ?>" placeholder="Venue, area, district"></div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="<?php echo $lbl; ?>">Start Date *</label><input type="date" name="start_date" id="e_start_date" required class="<?php echo $inp; ?>"></div>
                    <div><label class="<?php echo $lbl; ?>">End Date *</label><input type="date" name="end_date" id="e_end_date" required class="<?php echo $inp; ?>"></div>
                    <div><label class="<?php echo $lbl; ?>">Start Time</label><input type="time" name="start_time" id="e_start_time" class="<?php echo $inp; ?>"></div>
                    <div><label class="<?php echo $lbl; ?>">End Time</label><input type="time" name="end_time" id="e_end_time" class="<?php echo $inp; ?>"></div>
                </div>
            </div>

            <div class="space-y-3">
                <h4 class="text-xs font-bold text-slate-800 border-b border-slate-100 pb-2"><i class="fa-solid fa-coins text-amber-500 mr-1.5"></i>Cost & Target</h4>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="<?php echo $lbl; ?>">Budget (৳)</label><input type="number" min="0" step="0.01" name="budget" id="e_budget" value="0" class="<?php echo $inp; ?>"></div>
                    <div><label class="<?php echo $lbl; ?>">Actual Cost (৳)</label><input type="number" min="0" step="0.01" name="actual_cost" id="e_actual_cost" value="0" class="<?php echo $inp; ?>"></div>
                    <div><label class="<?php echo $lbl; ?>">Expected Beneficiaries</label><input type="number" min="0" name="expected_beneficiaries" id="e_expected_beneficiaries" value="0" class="<?php echo $inp; ?>"></div>
                    <div><label class="<?php echo $lbl; ?>">Max Volunteers (0 = ∞)</label><input type="number" min="0" name="max_volunteers" id="e_max_volunteers" value="0" class="<?php echo $inp; ?>"></div>
                    <div><label class="<?php echo $lbl; ?>">Organizer</label><input type="text" name="organizer" id="e_organizer" class="<?php echo $inp; ?>"></div>
                    <div><label class="<?php echo $lbl; ?>">Contact Phone</label><input type="text" name="contact_phone" id="e_contact_phone" maxlength="20" class="<?php echo $inp; ?>"></div>
                </div>
            </div>

            <!-- Volunteers -->
            <div class="space-y-3">
                <div class="flex items-center justify-between border-b border-slate-100 pb-2">
                    <h4 class="text-xs font-bold text-slate-800"><i class="fa-solid fa-user-group text-teal-600 mr-1.5"></i>Assign Volunteers</h4>
                    <span id="e_volCount" class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-teal-50 text-teal-700">0 selected</span>
                </div>
                <div class="flex gap-2">
                    <input type="text" id="e_volSearch" placeholder="Search by name or phone..." class="<?php echo $inp; ?>">
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" data-vtab="all" class="vtab px-3 py-1.5 rounded-lg text-[11px] font-bold bg-slate-900 text-white">All Members</button>
                    <button type="button" data-vtab="vol" class="vtab px-3 py-1.5 rounded-lg text-[11px] font-bold bg-slate-100 text-slate-600">Volunteers only</button>
                    <button type="button" id="e_volAll" class="ml-auto px-3 py-1.5 rounded-lg text-[11px] font-bold bg-emerald-50 text-emerald-700 hover:bg-emerald-100"><i class="fa-solid fa-check-double"></i> Select all</button>
                    <button type="button" id="e_volNone" class="px-3 py-1.5 rounded-lg text-[11px] font-bold bg-rose-50 text-rose-600 hover:bg-rose-100"><i class="fa-solid fa-xmark"></i> Clear</button>
                </div>
                <div id="e_volList" class="max-h-72 overflow-y-auto rounded-xl border border-slate-200 divide-y divide-slate-100"></div>
                <p id="e_volTotal" class="text-[11px] text-slate-400"></p>
            </div>

            <div class="flex gap-2 pt-2 sticky bottom-0 bg-white pb-1">
                <button type="button" onclick="evtModal('evtFormModal', false)" class="flex-1 sm:flex-none px-5 py-3 sm:py-2.5 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="submit" id="e_submit" class="flex-1 sm:flex-none sm:ml-auto px-6 py-3 sm:py-2.5 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700 shadow-sm">Save Event</button>
            </div>
        </form>
    </div>
</div>
<?php } ?>

<!-- ================= VIEW MODAL ================= -->
<div id="evtViewModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs hidden items-end sm:items-center justify-center sm:p-4 z-50">
    <div class="bg-white w-full sm:max-w-2xl max-h-[94vh] overflow-y-auto rounded-t-3xl sm:rounded-2xl shadow-2xl relative">
        <button type="button" onclick="evtModal('evtViewModal', false)" class="absolute top-3 right-3 z-10 w-8 h-8 rounded-full bg-slate-900/70 text-white flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button>
        <div id="evtViewBody"></div>
    </div>
</div>

<?php if ($canDelete) { ?>
<!-- ================= DELETE MODAL (শুধু Admin) ================= -->
<div id="evtDeleteModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs hidden items-center justify-center p-4 z-50">
    <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl p-6 text-center space-y-4">
        <div class="w-14 h-14 mx-auto rounded-full bg-rose-50 text-rose-600 flex items-center justify-center text-2xl"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div>
            <h3 class="font-bold text-slate-800">Delete this event?</h3>
            <p class="text-xs text-slate-500 mt-1"><b id="evtDelName"></b>, তার ছবি ও volunteer assignment স্থায়ীভাবে মুছে যাবে।</p>
        </div>
        <div class="flex gap-2">
            <button type="button" onclick="evtModal('evtDeleteModal', false)" class="flex-1 px-4 py-2.5 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
            <button type="button" id="evtDelConfirm" class="flex-1 px-4 py-2.5 bg-rose-600 text-white rounded-xl text-xs font-semibold hover:bg-rose-700">Yes, Delete</button>
        </div>
    </div>
</div>
<?php } ?>

<div id="evtToast" class="fixed top-4 left-4 right-4 sm:left-auto sm:right-5 sm:w-80 z-[60] hidden px-4 py-3 rounded-xl text-xs font-semibold shadow-lg border"></div>
<!-- Tailwind কে এই ক্লাসগুলো চেনানোর জন্য (JS দিয়ে বসানো হয়) -->
<div class="hidden ring-2 ring-sky-500 ring-emerald-500 ring-violet-500 ring-slate-400"></div>

<script>
(function () {
    var CSRF = <?php echo json_encode($_SESSION['csrf']); ?>;
    var CAN_CREATE = <?php echo $canCreate ? 'true' : 'false'; ?>;
    var CAN_EDIT   = <?php echo $canEdit ? 'true' : 'false'; ?>;
    var CAN_DELETE = <?php echo $canDelete ? 'true' : 'false'; ?>;
    var state = <?php echo json_encode($saved); ?>;
    var VOLS  = <?php echo json_encode($VOLS, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE); ?>;
    if (!VOLS) { VOLS = []; }

    var $ = function (id) { return document.getElementById(id); };
    var seq = 0, timer = null, delId = 0, selected = new Set();
    var STYLE = { Upcoming: 'bg-sky-100 text-sky-700', Ongoing: 'bg-emerald-100 text-emerald-700', Completed: 'bg-slate-200 text-slate-700', Cancelled: 'bg-rose-100 text-rose-700' };
    var RING  = { Upcoming: 'ring-sky-500', Ongoing: 'ring-emerald-500', Completed: 'ring-violet-500' };

    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
    function money(n) { return '৳' + Number(n || 0).toLocaleString('en-US'); }

    // সার্ভারের উত্তর থেকে শুধু JSON অংশ বের করা
    function readJson(r) {
        return r.text().then(function (t) {
            var S = '<<EVT_START>>', E = '<<EVT_END>>';
            var a = t.indexOf(S), b = t.indexOf(E);
            if (a === -1 || b === -1) {
                throw new Error('Server এর উত্তর বোঝা যায়নি: ' + t.replace(/<[^>]*>/g, ' ').replace(/\s+/g, ' ').slice(0, 250));
            }
            return JSON.parse(t.substring(a + S.length, b));
        });
    }

    function url(act, params) {
        var u = new URL(location.href);
        ['act', 'es', 'est', 'ec', 'pg', 'per', 'id'].forEach(function (k) { u.searchParams.delete(k); });
        u.searchParams.set('act', act);
        for (var k in (params || {})) { u.searchParams.set(k, params[k]); }
        return u.toString();
    }
    function get(act, params) { return fetch(url(act, params), { credentials: 'same-origin' }).then(readJson); }
    function post(act, fd) {
        fd.append('csrf', CSRF);
        return fetch(url(act), { method: 'POST', body: fd, headers: { 'X-CSRF': CSRF }, credentials: 'same-origin' }).then(readJson);
    }

    function toast(msg, ok) {
        var t = $('evtToast');
        t.className = 'fixed top-4 left-4 right-4 sm:left-auto sm:right-5 sm:w-80 z-[60] px-4 py-3 rounded-xl text-xs font-semibold shadow-lg border ' +
            (ok ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-rose-50 border-rose-200 text-rose-700');
        t.textContent = msg; clearTimeout(t._t); t._t = setTimeout(function () { t.classList.add('hidden'); }, 4000);
    }

    window.evtModal = function (id, show) {
        var m = $(id); if (!m) { return; }
        m.classList.toggle('hidden', !show); m.classList.toggle('flex', show);
        document.body.style.overflow = show ? 'hidden' : '';
    };

    /* ---------- LIST ---------- */
    function setStat(k, v) { document.querySelectorAll('[data-stat="' + k + '"]').forEach(function (e) { e.textContent = v; }); }
    function markCards() {
        document.querySelectorAll('[data-fstatus]').forEach(function (e) {
            var on = e.dataset.fstatus === state.status, r = RING[e.dataset.fstatus];
            e.classList.toggle('ring-2', on); e.classList.toggle(r, on); e.classList.toggle('ring-1', !on);
        });
    }
    function load() {
        var my = ++seq;
        get('list', { es: state.q, est: state.status, ec: state.cat, pg: state.page, per: state.per })
            .then(function (d) {
                if (my !== seq) { return; }
                if (!d.ok) { $('evtGrid').innerHTML = '<div class="col-span-full py-10 text-center text-rose-500 text-sm">' + esc(d.msg) + '</div>'; return; }
                state.page = d.page;
                $('evtGrid').innerHTML = d.rows; $('evtPager').innerHTML = d.pager;
                $('evtInfo').textContent = d.total ? 'Showing ' + d.from + '–' + d.to + ' of ' + d.total + ' events' : 'No results';
                ['total', 'Upcoming', 'Ongoing', 'Completed', 'budget', 'spent'].forEach(function (k) { setStat(k, d.stats[k]); });
                var c = $('fClear'); c.classList.toggle('hidden', !d.filtered); c.classList.toggle('flex', d.filtered);
                markCards();
            })
            .catch(function (err) {
                $('evtGrid').innerHTML = '<div class="col-span-full py-10 text-center text-rose-500 text-sm px-4">Load করা যায়নি।<br><span class="text-xs text-slate-500">' + esc(err.message) + '</span></div>';
            });
    }

    $('fSearch').addEventListener('input', function () {
        clearTimeout(timer); var v = this.value;
        timer = setTimeout(function () { state.q = v.trim(); state.page = 1; load(); }, 300);
    });
    $('fCat').addEventListener('change',    function () { state.cat = this.value;    state.page = 1; load(); });
    $('fStatus').addEventListener('change', function () { state.status = this.value; state.page = 1; load(); });
    $('fPer').addEventListener('change',    function () { state.per = this.value;    state.page = 1; load(); });

    $('evtPager').addEventListener('click', function (e) {
        var b = e.target.closest('[data-page]');
        if (b) { state.page = parseInt(b.dataset.page, 10); load(); window.scrollTo({ top: 0, behavior: 'smooth' }); }
    });
    document.addEventListener('click', function (e) {
        var st = e.target.closest('[data-fstatus]');
        if (st) {
            state.status = state.status === st.dataset.fstatus ? '' : st.dataset.fstatus;
            $('fStatus').value = state.status; state.page = 1; load();
        }
    });
    window.evtClear = function () {
        get('clear').then(function () {
            state = { q: '', status: '', cat: '', per: state.per, page: 1 };
            $('fSearch').value = ''; $('fCat').value = ''; $('fStatus').value = ''; load();
        });
    };

    /* ---------- CARD বাটন ---------- */
    $('evtGrid').addEventListener('click', function (e) {
        var b = e.target.closest('[data-act]'); if (!b) { return; }
        var id = b.dataset.id, act = b.dataset.act;
        if (act === 'view') { openView(id); }
        if (act === 'edit' && CAN_EDIT) { window.evtOpenForm(id); }
        if (act === 'delete' && CAN_DELETE) { delId = id; $('evtDelName').textContent = b.dataset.name; window.evtModal('evtDeleteModal', true); }
    });
    if (CAN_DELETE) {
        $('evtDelConfirm').addEventListener('click', function () {
            var fd = new FormData(); fd.append('id', delId);
            post('delete', fd)
                .then(function (d) { toast(d.msg, d.ok); window.evtModal('evtDeleteModal', false); if (d.ok) { load(); } })
                .catch(function (err) { toast(err.message, false); });
        });
    }

    /* ---------- VIEW ---------- */
    function openView(id) {
        get('get', { id: id }).then(function (d) {
            if (!d.ok) { toast(d.msg, false); return; }
            var e = d.data, diff = Number(e.budget) - Number(e.actual_cost);
            var cover = e.image_url
                ? '<img src="' + esc(e.image_url) + '" class="w-full h-full object-cover">'
                : '<div class="w-full h-full bg-gradient-to-br from-emerald-500 via-teal-600 to-slate-800 flex items-center justify-center text-white/80 text-5xl"><i class="fa-solid fa-calendar-days"></i></div>';
            var row = function (ic, l, v) {
                return '<div class="p-3 rounded-xl bg-slate-50 border border-slate-100"><p class="text-[10px] font-bold uppercase text-slate-400"><i class="fa-solid ' + ic + ' mr-1"></i>' + l +
                    '</p><p class="text-xs font-semibold text-slate-800 mt-0.5 break-words">' + (v || '<span class="text-slate-300">—</span>') + '</p></div>';
            };
            var dates = e.start_date === e.end_date ? e.start_date : e.start_date + ' → ' + e.end_date;
            var time = (e.start_time ? e.start_time.slice(0, 5) : '') + (e.end_time ? ' – ' + e.end_time.slice(0, 5) : '');
            var vols = d.vols.length ? d.vols.map(function (v) {
                var av = v.photo_url
                    ? '<img src="' + esc(v.photo_url) + '" class="w-9 h-9 rounded-full object-cover">'
                    : '<div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs">' + esc((v.member_name || '?').charAt(0).toUpperCase()) + '</div>';
                return '<div class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-100 bg-white">' + av +
                    '<div class="min-w-0"><p class="text-xs font-semibold text-slate-800 truncate">' + esc(v.member_name) + '</p><p class="text-[10px] text-slate-400">+88' + esc(v.mobile_no) + '</p></div></div>';
            }).join('') : '<p class="text-xs text-slate-400 col-span-full">কোনো volunteer assign করা হয়নি।</p>';

            var editBtn = CAN_EDIT
                ? '<button type="button" onclick="evtModal(\'evtViewModal\', false); evtOpenForm(' + e.id + ')" class="flex-1 py-3 rounded-xl bg-sky-600 text-white text-xs font-semibold"><i class="fa-solid fa-pen-to-square mr-1"></i>Edit Event</button>'
                : '';

            $('evtViewBody').innerHTML =
                '<div class="h-48 sm:h-56 relative">' + cover + '<div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 to-transparent"></div>' +
                '<div class="absolute bottom-4 left-4 right-4 text-white"><div class="flex gap-2 mb-2"><span class="px-2.5 py-1 rounded-full text-[10px] font-bold ' + (STYLE[e.status] || '') + '">' + esc(e.status) +
                '</span><span class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-white/20 backdrop-blur">' + esc(e.category) + '</span></div><h3 class="text-lg sm:text-xl font-bold leading-snug">' + esc(e.title) + '</h3></div></div>' +
                '<div class="p-4 sm:p-6 space-y-5">' +
                (e.description ? '<p class="text-xs text-slate-600 leading-relaxed whitespace-pre-line">' + esc(e.description) + '</p>' : '') +
                '<div class="grid grid-cols-2 gap-3">' + row('fa-calendar', 'Date', esc(dates)) + row('fa-clock', 'Time', esc(time)) + row('fa-location-dot', 'Location', esc(e.location)) +
                row('fa-user-tie', 'Organizer', esc(e.organizer)) + row('fa-phone', 'Contact', esc(e.contact_phone)) + row('fa-people-group', 'Expected Beneficiaries', esc(e.expected_beneficiaries)) + '</div>' +
                '<div class="grid grid-cols-3 gap-3 text-center"><div class="p-3 rounded-xl bg-slate-900 text-white"><p class="text-[10px] uppercase text-slate-300 font-bold">Budget</p><p class="text-sm font-bold">' + money(e.budget) + '</p></div>' +
                '<div class="p-3 rounded-xl bg-emerald-50 text-emerald-700"><p class="text-[10px] uppercase font-bold">Spent</p><p class="text-sm font-bold">' + money(e.actual_cost) + '</p></div>' +
                '<div class="p-3 rounded-xl ' + (diff < 0 ? 'bg-rose-50 text-rose-700' : 'bg-sky-50 text-sky-700') + '"><p class="text-[10px] uppercase font-bold">' + (diff < 0 ? 'Over' : 'Remaining') + '</p><p class="text-sm font-bold">' + money(Math.abs(diff)) + '</p></div></div>' +
                '<div><h4 class="text-xs font-bold text-slate-800 mb-3"><i class="fa-solid fa-user-group text-teal-600 mr-1.5"></i>Volunteers (' + d.vols.length + (Number(e.max_volunteers) > 0 ? '/' + e.max_volunteers : '') +
                ')</h4><div class="grid grid-cols-1 sm:grid-cols-2 gap-2">' + vols + '</div></div>' +
                '<div class="flex gap-2">' + editBtn + '<button type="button" onclick="evtModal(\'evtViewModal\', false)" class="flex-1 py-3 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600">Close</button></div></div>';
            window.evtModal('evtViewModal', true);
        }).catch(function (err) { toast(err.message, false); });
    }

    // মডালের বাইরে ক্লিক করলে বন্ধ
    ['evtFormModal', 'evtViewModal', 'evtDeleteModal'].forEach(function (id) {
        var m = $(id);
        if (m) { m.addEventListener('mousedown', function (e) { if (e.target === this) { window.evtModal(id, false); } }); }
    });

    /* ---------- ADD / EDIT (যাদের permission আছে) ---------- */
    if (CAN_EDIT) {

        /* Volunteer picker */
        var volTab = 'all';
        var visibleVols = function () {
            var q = $('e_volSearch').value.trim().toLowerCase();
            return VOLS.filter(function (v) {
                if (volTab === 'vol' && !+v.is_vol) { return false; }
                return !q || (v.member_name + ' ' + v.mobile_no).toLowerCase().indexOf(q) !== -1;
            });
        };
        var updCount = function () {
            var m = parseInt($('e_max_volunteers').value || 0, 10);
            $('e_volCount').textContent = selected.size + (m > 0 ? ' / ' + m : '') + ' selected';
        };
        var renderVols = function () {
            var list = visibleVols(), html = '';
            list.forEach(function (v) {
                var av = v.photo_url
                    ? '<img src="' + esc(v.photo_url) + '" class="w-9 h-9 rounded-full object-cover shrink-0">'
                    : '<div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs shrink-0">' + esc((v.member_name || '?').charAt(0).toUpperCase()) + '</div>';
                var tag = '<span class="px-1.5 py-0.5 rounded text-[9px] font-bold ' + (+v.is_vol ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500') + '">' + esc(v.user_type || '') + '</span>' +
                    (v.status && v.status !== 'Active' ? ' <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-50 text-amber-700">' + esc(v.status) + '</span>' : '');
                html += '<label class="flex items-center gap-3 p-2.5 cursor-pointer hover:bg-slate-50"><input type="checkbox" value="' + v.id + '" class="rounded border-slate-300 text-emerald-600 w-4 h-4" ' +
                    (selected.has(+v.id) ? 'checked' : '') + '>' + av + '<div class="min-w-0 flex-1"><p class="text-xs font-semibold text-slate-800 truncate">' + esc(v.member_name) +
                    '</p><p class="text-[10px] text-slate-400">+88' + esc(v.mobile_no) + ' &nbsp;' + tag + '</p></div></label>';
            });
            $('e_volList').innerHTML = html || '<p class="p-4 text-center text-xs text-slate-400">কোনো member পাওয়া যায়নি।</p>';
            $('e_volTotal').textContent = 'Showing ' + list.length + ' of ' + VOLS.length + ' members';
            updCount();
        };

        document.querySelectorAll('.vtab').forEach(function (b) {
            b.addEventListener('click', function () {
                volTab = this.dataset.vtab;
                document.querySelectorAll('.vtab').forEach(function (x) {
                    var on = x.dataset.vtab === volTab;
                    x.className = 'vtab px-3 py-1.5 rounded-lg text-[11px] font-bold ' + (on ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600');
                });
                renderVols();
            });
        });
        $('e_volAll').addEventListener('click', function () {
            var m = parseInt($('e_max_volunteers').value || 0, 10);
            visibleVols().forEach(function (v) {
                if (m > 0 && selected.size >= m && !selected.has(+v.id)) { return; }
                selected.add(+v.id);
            });
            if (m > 0 && selected.size >= m) { toast('Max volunteer limit (' + m + ') পর্যন্ত select হয়েছে।', false); }
            renderVols();
        });
        $('e_volNone').addEventListener('click', function () {
            visibleVols().forEach(function (v) { selected.delete(+v.id); });
            renderVols();
        });
        $('e_max_volunteers').addEventListener('input', updCount);
        $('e_volSearch').addEventListener('input', renderVols);
        $('e_volList').addEventListener('change', function (e) {
            var id = +e.target.value;
            if (e.target.checked) { selected.add(id); } else { selected.delete(id); }
            updCount();
        });

        /* ফর্ম */
        var fields = ['title', 'category', 'status', 'description', 'location', 'start_date', 'end_date', 'start_time', 'end_time',
                      'budget', 'actual_cost', 'expected_beneficiaries', 'max_volunteers', 'organizer', 'contact_phone'];
        var setPrev = function (src) {
            $('e_prev').classList.toggle('hidden', !src);
            $('e_prevIcon').classList.toggle('hidden', !!src);
            $('e_prev').src = src || '';
        };

        window.evtOpenForm = function (id) {
            if (!id && !CAN_CREATE) { toast('শুধু Admin নতুন ইভেন্ট তৈরি করতে পারবে।', false); return; }
            $('evtForm').reset(); $('e_id').value = 0; setPrev(''); selected = new Set(); $('e_volSearch').value = '';
            $('e_removeWrap').classList.add('hidden'); $('e_removeWrap').classList.remove('flex'); $('e_hint').textContent = '';
            $('evtFormTitle').textContent = 'Create Event';

            if (!id) { renderVols(); window.evtModal('evtFormModal', true); return; }

            $('evtFormTitle').textContent = 'Edit Event';
            get('get', { id: id }).then(function (d) {
                if (!d.ok) { toast(d.msg, false); return; }
                $('e_id').value = d.data.id;
                fields.forEach(function (k) {
                    var v = d.data[k]; if (v == null) { v = ''; }
                    if (/time$/.test(k)) { v = String(v).slice(0, 5); }
                    $('e_' + k).value = v;
                });
                d.vols.forEach(function (v) {
                    selected.add(+v.id);
                    if (!VOLS.some(function (x) { return +x.id === +v.id; })) { VOLS.push(v); }
                });
                setPrev(d.data.image_url);
                if (d.data.image_url) {
                    $('e_removeWrap').classList.remove('hidden'); $('e_removeWrap').classList.add('flex');
                    $('e_hint').textContent = 'নতুন ছবি দিলে পুরনো ছবি ডিলিট হয়ে যাবে।';
                }
                renderVols(); window.evtModal('evtFormModal', true);
            }).catch(function (err) { toast(err.message, false); });
        };

        $('e_image').addEventListener('change', function () {
            var f = this.files[0]; if (!f) { return; }
            if (f.size > 3 * 1024 * 1024) { toast('ছবির সাইজ সর্বোচ্চ 3MB।', false); this.value = ''; return; }
            var r = new FileReader(); r.onload = function (ev) { setPrev(ev.target.result); }; r.readAsDataURL(f);
        });
        $('e_start_date').addEventListener('change', function () {
            if (!$('e_end_date').value || $('e_end_date').value < this.value) { $('e_end_date').value = this.value; }
        });

        $('evtForm').addEventListener('submit', function (e) {
            e.preventDefault();
            var max = parseInt($('e_max_volunteers').value || 0, 10);
            if (max > 0 && selected.size > max) { toast('Max volunteer limit (' + max + ') এর বেশি select করা হয়েছে।', false); return; }

            var fd = new FormData(this);
            selected.forEach(function (id) { fd.append('volunteers[]', id); });

            var btn = $('e_submit'); btn.disabled = true; btn.textContent = 'Saving...';
            post('save', fd)
                .then(function (d) { toast(d.msg, d.ok); if (d.ok) { window.evtModal('evtFormModal', false); load(); } })
                .catch(function (err) { toast(err.message, false); })
                .then(function () { btn.disabled = false; btn.textContent = 'Save Event'; });
        });
    }

    load();
})();
</script>