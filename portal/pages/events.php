<!-- -- =====================================================
-- Events module tables sql
-- =====================================================

CREATE TABLE IF NOT EXISTS `events` (
  `id`                     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `title`                  VARCHAR(200)  NOT NULL,
  `category`               VARCHAR(60)   NOT NULL DEFAULT 'Other',
  `description`            TEXT          NULL,
  `image`                  VARCHAR(255)  NOT NULL DEFAULT '',
  `location`               VARCHAR(255)  NOT NULL,
  `start_date`             DATE          NOT NULL,
  `end_date`               DATE          NOT NULL,
  `start_time`             TIME          NULL,
  `end_time`               TIME          NULL,
  `budget`                 DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `actual_cost`            DECIMAL(12,2) NOT NULL DEFAULT 0.00,
  `expected_beneficiaries` INT UNSIGNED  NOT NULL DEFAULT 0,
  `max_volunteers`         INT UNSIGNED  NOT NULL DEFAULT 0,   -- 0 = unlimited
  `organizer`              VARCHAR(150)  NOT NULL DEFAULT '',
  `contact_phone`          VARCHAR(20)   NOT NULL DEFAULT '',
  `status`                 ENUM('Upcoming','Ongoing','Completed','Cancelled') NOT NULL DEFAULT 'Upcoming',
  `created_at`             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at`             TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_status` (`status`),
  KEY `idx_category` (`category`),
  KEY `idx_start_date` (`start_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Event <-> Volunteer (one event can have many volunteers, one volunteer can join many events)
CREATE TABLE IF NOT EXISTS `event_volunteers` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `event_id`    INT UNSIGNED NOT NULL,
  `user_id`     INT          NOT NULL,          -- users.id
  `assigned_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_event_user` (`event_id`, `user_id`),
  KEY `idx_user` (`user_id`),
  CONSTRAINT `fk_ev_event` FOREIGN KEY (`event_id`) REFERENCES `events`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Optional: user delete korle assignment o muche jabe
-- (users.id type jodi INT UNSIGNED hoy, upore user_id o INT UNSIGNED korben)
-- ALTER TABLE `event_volunteers`
--   ADD CONSTRAINT `fk_ev_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE; -->


<?php
/* ==========================================================
   events.php  (SINGLE FILE: page + add/edit/delete/view
   + volunteer assign + live search + pagination + session filter)
   NOTE: layout a ob_start() thaka valo (ajax JSON clean rakhar jonno).
   ========================================================== */
if (session_status() === PHP_SESSION_NONE) { @session_start(); }
if (!isset($db)) { ob_start(); include 'include/header.php'; ob_end_clean(); }
mysqli_set_charset($db, "utf8mb4");

$event_dir  = 'public/uploads/events/';
$member_dir = 'public/uploads/members/';
$vol_types  = ['Volunteer Member', 'Volunteer'];     // kon user_type gulo event a assign hobe
$categories = ['Medical Camp', 'Education', 'Food Distribution', 'Fundraising', 'Awareness', 'Training', 'Relief', 'Other'];
$statuses   = ['Upcoming', 'Ongoing', 'Completed', 'Cancelled'];
$per_opts   = [6, 12, 24, 48];

if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }

/* ---------- helpers ---------- */
if (!function_exists('h')) { function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }

function evt_json($arr) {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}
function evt_money($n) { return '৳' . number_format((float)$n); }

function evt_q($db, $sql, $types = '', $params = []) {
    $st = mysqli_prepare($db, $sql);
    if ($types !== '') { mysqli_stmt_bind_param($st, $types, ...$params); }
    mysqli_stmt_execute($st);
    return $st;
}

function evt_where($q, $status, $cat, &$types, &$params) {
    $w = "WHERE 1=1"; $types = ''; $params = [];
    if ($q !== '') {
        $w .= " AND (title LIKE ? OR location LIKE ? OR organizer LIKE ?)";
        $l = '%' . addcslashes($q, '%_\\') . '%';
        $types .= 'sss'; array_push($params, $l, $l, $l);
    }
    if ($status !== '') { $w .= " AND status = ?";   $types .= 's'; $params[] = $status; }
    if ($cat !== '')    { $w .= " AND category = ?"; $types .= 's'; $params[] = $cat; }
    return $w;
}

// Status card = search+category, Total/Budget = sob filter
function evt_stats($db, $q, $status, $cat, $total) {
    $w = evt_where($q, '', $cat, $t, $p);
    $res = mysqli_stmt_get_result(evt_q($db, "SELECT status, COUNT(*) c FROM events $w GROUP BY status", $t, $p));
    $m = [];
    while ($r = mysqli_fetch_assoc($res)) { $m[$r['status']] = (int)$r['c']; }
    $w = evt_where($q, $status, $cat, $t, $p);
    $r = mysqli_fetch_assoc(mysqli_stmt_get_result(evt_q($db, "SELECT COALESCE(SUM(budget),0) b, COALESCE(SUM(actual_cost),0) a FROM events $w", $t, $p)));
    return ['total' => $total, 'Upcoming' => $m['Upcoming'] ?? 0, 'Ongoing' => $m['Ongoing'] ?? 0,
            'Completed' => $m['Completed'] ?? 0, 'budget' => evt_money($r['b']), 'spent' => evt_money($r['a'])];
}

function evt_style($s) {
    $m = [
        'Upcoming'  => ['bg-sky-100 text-sky-700',         'fa-hourglass-start'],
        'Ongoing'   => ['bg-emerald-100 text-emerald-700', 'fa-play'],
        'Completed' => ['bg-slate-200 text-slate-700',     'fa-circle-check'],
        'Cancelled' => ['bg-rose-100 text-rose-700',       'fa-ban'],
    ];
    return $m[$s] ?? $m['Upcoming'];
}

function evt_upload($file, $dir, &$err) {
    if (!is_array($file) || $file['error'] === UPLOAD_ERR_NO_FILE) { return ''; }
    if ($file['error'] !== 0) { $err = 'ছবি আপলোড করা যায়নি।'; return false; }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp']) || !@getimagesize($file['tmp_name'])) { $err = 'ছবি শুধু JPG, PNG বা WEBP হতে হবে।'; return false; }
    if ($file['size'] > 3 * 1024 * 1024) { $err = 'ছবির সাইজ সর্বোচ্চ 3MB।'; return false; }
    if (!is_dir($dir)) { mkdir($dir, 0755, true); }
    $name = 'event_' . time() . '_' . random_int(1000, 9999) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) { $err = 'ছবি সেভ করা যায়নি।'; return false; }
    return $name;
}
function evt_unlink($dir, $f) { if ($f != '' && is_file($dir . basename($f))) { @unlink($dir . basename($f)); } }

function evt_avatar($v, $dir, $cls = 'w-7 h-7 text-[10px]') {
    if ($v['photo'] != '' && is_file($dir . basename($v['photo']))) {
        return '<img src="' . h($dir . $v['photo']) . '" title="' . h($v['member_name']) . '" class="' . $cls . ' rounded-full object-cover border-2 border-white">';
    }
    return '<div title="' . h($v['member_name']) . '" class="' . $cls . ' rounded-full bg-emerald-100 text-emerald-700 border-2 border-white flex items-center justify-center font-bold">' . h(mb_strtoupper(mb_substr($v['member_name'], 0, 1))) . '</div>';
}

function evt_dates($r) {
    $a = strtotime($r['start_date']); $b = strtotime($r['end_date']);
    return $a == $b ? date('d M Y', $a) : date('d M', $a) . ' – ' . date('d M Y', $b);
}

function evt_card($r, $vols, $ed, $md) {
    $img = ($r['image'] != '' && is_file($ed . basename($r['image']))) ? $ed . $r['image'] : '';
    $s = evt_style($r['status']);
    $cover = $img
        ? '<img src="' . h($img) . '" loading="lazy" class="w-full h-full object-cover group-hover:scale-105 transition duration-300">'
        : '<div class="w-full h-full bg-gradient-to-br from-emerald-500 via-teal-600 to-slate-800 flex items-center justify-center text-white/80 text-4xl"><i class="fa-solid fa-calendar-days"></i></div>';
    $time = ($r['start_time'] ? ' • ' . date('h:i A', strtotime($r['start_time'])) : '');
    $budget = (float)$r['budget']; $act = (float)$r['actual_cost'];
    $pct = $budget > 0 ? min(100, round($act / $budget * 100)) : 0;
    $bar = $budget > 0 ? '<div class="h-1.5 rounded-full bg-slate-100 overflow-hidden mt-1.5"><div class="h-full rounded-full ' . ($act > $budget ? 'bg-rose-500' : 'bg-emerald-500') . '" style="width:' . $pct . '%"></div></div>' : '';
    $stack = '';
    foreach (array_slice($vols, 0, 4) as $v) { $stack .= evt_avatar($v, $md); }
    if (count($vols) > 4) { $stack .= '<div class="w-7 h-7 rounded-full bg-slate-800 text-white border-2 border-white flex items-center justify-center text-[10px] font-bold">+' . (count($vols) - 4) . '</div>'; }
    if (!$vols) { $stack = '<span class="text-[11px] text-slate-400">No volunteer assigned</span>'; }
    $max = (int)$r['max_volunteers'];
    $id = (int)$r['id'];

    return '<div class="group bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden flex flex-col hover:shadow-lg transition">
      <div class="relative h-40 bg-slate-100 overflow-hidden cursor-pointer" data-act="view" data-id="' . $id . '">' . $cover . '
        <span class="absolute top-3 left-3 px-2.5 py-1 rounded-full text-[10px] font-bold flex items-center gap-1 ' . $s[0] . '"><i class="fa-solid ' . $s[1] . '"></i>' . h($r['status']) . '</span>
        <span class="absolute top-3 right-3 px-2.5 py-1 rounded-full text-[10px] font-semibold bg-slate-900/70 text-white backdrop-blur">' . h($r['category']) . '</span>
      </div>
      <div class="p-4 flex-1 flex flex-col gap-3">
        <h3 class="font-bold text-slate-800 text-sm leading-snug line-clamp-2 cursor-pointer hover:text-emerald-600" data-act="view" data-id="' . $id . '">' . h($r['title']) . '</h3>
        <div class="space-y-1.5 text-xs text-slate-500">
          <p class="flex items-start gap-2"><i class="fa-regular fa-calendar text-emerald-600 mt-0.5 w-3.5"></i><span>' . h(evt_dates($r)) . h($time) . '</span></p>
          <p class="flex items-start gap-2"><i class="fa-solid fa-location-dot text-rose-500 mt-0.5 w-3.5"></i><span class="line-clamp-1">' . h($r['location']) . '</span></p>
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
      <div class="px-4 pb-4 grid grid-cols-3 gap-2">
        <button type="button" data-act="view" data-id="' . $id . '" class="py-2 rounded-xl border border-slate-200 text-[11px] font-semibold text-slate-600 hover:bg-slate-50"><i class="fa-solid fa-eye"></i> View</button>
        <button type="button" data-act="edit" data-id="' . $id . '" class="py-2 rounded-xl border border-sky-200 bg-sky-50 text-[11px] font-semibold text-sky-700 hover:bg-sky-100"><i class="fa-solid fa-pen-to-square"></i> Edit</button>
        <button type="button" data-act="delete" data-id="' . $id . '" data-name="' . h($r['title']) . '" class="py-2 rounded-xl border border-rose-200 bg-rose-50 text-[11px] font-semibold text-rose-600 hover:bg-rose-100"><i class="fa-solid fa-trash"></i> Delete</button>
      </div></div>';
}

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

function evt_vols_of($db, $eid) {
    $st = evt_q($db, "SELECT u.id, u.member_name, u.mobile_no, u.photo FROM event_volunteers ev JOIN users u ON u.id = ev.user_id WHERE ev.event_id = ? ORDER BY ev.id", 'i', [$eid]);
    $out = [];
    $res = mysqli_stmt_get_result($st);
    while ($r = mysqli_fetch_assoc($res)) { $out[] = $r; }
    return $out;
}

/* ==========================================================
   AJAX HANDLER
   ========================================================== */
$act = $_GET['act'] ?? '';
if ($act !== '') {

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($_SESSION['csrf'], $_SERVER['HTTP_X_CSRF'] ?? '')) { evt_json(['ok' => false, 'msg' => 'Invalid session. Page reload korun.']); }
    }

    /* ----- LIST ----- */
    if ($act === 'list') {
        $q = trim($_GET['es'] ?? ''); $status = trim($_GET['est'] ?? ''); $cat = trim($_GET['ec'] ?? '');
        $per = (int)($_GET['per'] ?? 6); if (!in_array($per, $per_opts)) { $per = 6; }
        $page = max(1, (int)($_GET['pg'] ?? 1));

        $where = evt_where($q, $status, $cat, $types, $params);
        mysqli_stmt_bind_result($st = evt_q($db, "SELECT COUNT(*) FROM events $where", $types, $params), $total);
        mysqli_stmt_fetch($st); mysqli_stmt_close($st);

        $pages = max(1, (int)ceil($total / $per));
        if ($page > $pages) { $page = $pages; }
        $offset = ($page - 1) * $per;
        $_SESSION['evt_filter'] = ['q' => $q, 'status' => $status, 'cat' => $cat, 'per' => $per, 'page' => $page];

        $st = evt_q($db, "SELECT * FROM events $where ORDER BY start_date DESC, id DESC LIMIT ? OFFSET ?", $types . 'ii', array_merge($params, [$per, $offset]));
        $res = mysqli_stmt_get_result($st);
        $rows = [];
        while ($r = mysqli_fetch_assoc($res)) { $rows[] = $r; }

        $vm = [];
        if ($rows) {
            $in = implode(',', array_map('intval', array_column($rows, 'id')));
            $vr = mysqli_query($db, "SELECT ev.event_id, u.id, u.member_name, u.photo FROM event_volunteers ev JOIN users u ON u.id = ev.user_id WHERE ev.event_id IN ($in) ORDER BY ev.id");
            while ($vr && $v = mysqli_fetch_assoc($vr)) { $vm[$v['event_id']][] = $v; }
        }
        $html = '';
        foreach ($rows as $r) { $html .= evt_card($r, $vm[$r['id']] ?? [], $event_dir, $member_dir); }
        if ($html === '') {
            $html = '<div class="col-span-full py-16 text-center text-slate-400"><i class="fa-regular fa-calendar-xmark text-4xl mb-3"></i><p class="text-sm font-semibold">কোনো ইভেন্ট পাওয়া যায়নি।</p></div>';
        }
        evt_json(['ok' => true, 'rows' => $html, 'pager' => evt_pager($page, $pages), 'page' => $page, 'total' => (int)$total,
                  'from' => $total ? $offset + 1 : 0, 'to' => min($offset + $per, $total),
                  'stats' => evt_stats($db, $q, $status, $cat, (int)$total), 'filtered' => ($q !== '' || $status !== '' || $cat !== '')]);
    }

    if ($act === 'clear') { unset($_SESSION['evt_filter']); evt_json(['ok' => true]); }

    /* ----- GET one (view / edit) ----- */
    if ($act === 'get') {
        $id = (int)($_GET['id'] ?? 0);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result(evt_q($db, "SELECT * FROM events WHERE id = ? LIMIT 1", 'i', [$id])));
        if (!$row) { evt_json(['ok' => false, 'msg' => 'ইভেন্ট পাওয়া যায়নি।']); }
        $row['image_url'] = ($row['image'] != '' && is_file($event_dir . basename($row['image']))) ? $event_dir . $row['image'] : '';
        $vols = evt_vols_of($db, $id);
        foreach ($vols as &$v) { $v['photo_url'] = ($v['photo'] != '' && is_file($member_dir . basename($v['photo']))) ? $member_dir . $v['photo'] : ''; }
        evt_json(['ok' => true, 'data' => $row, 'vols' => $vols]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { evt_json(['ok' => false, 'msg' => 'Invalid request.']); }

    /* ----- DELETE ----- */
    if ($act === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $old = mysqli_fetch_assoc(mysqli_stmt_get_result(evt_q($db, "SELECT image FROM events WHERE id = ? LIMIT 1", 'i', [$id])));
        if (!$old) { evt_json(['ok' => false, 'msg' => 'ইভেন্ট পাওয়া যায়নি।']); }
        evt_q($db, "DELETE FROM event_volunteers WHERE event_id = ?", 'i', [$id]);
        $st = evt_q($db, "DELETE FROM events WHERE id = ?", 'i', [$id]);
        if (mysqli_stmt_affected_rows($st) > 0) { evt_unlink($event_dir, $old['image']); evt_json(['ok' => true, 'msg' => 'ইভেন্ট ডিলিট করা হয়েছে।']); }
        evt_json(['ok' => false, 'msg' => 'ডিলিট করা যায়নি।']);
    }

    /* ----- SAVE (add + edit) ----- */
    if ($act === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $f = [];
        foreach (['title', 'category', 'description', 'location', 'start_date', 'end_date', 'start_time', 'end_time',
                  'budget', 'actual_cost', 'expected_beneficiaries', 'max_volunteers', 'organizer', 'contact_phone', 'status'] as $k) {
            $f[$k] = trim($_POST[$k] ?? '');
        }
        if ($f['title'] === '' || $f['location'] === '' || $f['start_date'] === '' || $f['end_date'] === '') { evt_json(['ok' => false, 'msg' => 'সব প্রয়োজনীয় (*) তথ্য পূরণ করুন।']); }
        if (strtotime($f['end_date']) < strtotime($f['start_date'])) { evt_json(['ok' => false, 'msg' => 'শেষ তারিখ শুরুর তারিখের আগে হতে পারবে না।']); }
        if (!in_array($f['status'], $statuses)) { evt_json(['ok' => false, 'msg' => 'সঠিক Status দিন।']); }
        if (!in_array($f['category'], $categories)) { $f['category'] = 'Other'; }
        $f['budget'] = max(0, (float)$f['budget']);
        $f['actual_cost'] = max(0, (float)$f['actual_cost']);
        $f['expected_beneficiaries'] = max(0, (int)$f['expected_beneficiaries']);
        $f['max_volunteers'] = max(0, (int)$f['max_volunteers']);
        $f['start_time'] = $f['start_time'] === '' ? null : $f['start_time'];
        $f['end_time']   = $f['end_time'] === '' ? null : $f['end_time'];

        $vol_ids = array_values(array_unique(array_filter(array_map('intval', (array)($_POST['volunteers'] ?? [])))));
        if ($f['max_volunteers'] > 0 && count($vol_ids) > $f['max_volunteers']) {
            evt_json(['ok' => false, 'msg' => 'Max volunteer limit (' . $f['max_volunteers'] . ') এর বেশি assign করা যাবে না।']);
        }

        $err = '';
        $new = evt_upload($_FILES['image'] ?? null, $event_dir, $err);
        if ($new === false) { evt_json(['ok' => false, 'msg' => $err]); }

        $vals = array_values($f);   // 15 fields, image prepend hobe
        if ($id > 0) {
            $old = mysqli_fetch_assoc(mysqli_stmt_get_result(evt_q($db, "SELECT image FROM events WHERE id = ? LIMIT 1", 'i', [$id])));
            if (!$old) { evt_unlink($event_dir, $new); evt_json(['ok' => false, 'msg' => 'ইভেন্ট পাওয়া যায়নি।']); }
            $image = $new !== '' ? $new : (!empty($_POST['remove_image']) ? '' : $old['image']);
            $st = evt_q($db, "UPDATE events SET image=?, title=?, category=?, description=?, location=?, start_date=?, end_date=?, start_time=?, end_time=?,
                budget=?, actual_cost=?, expected_beneficiaries=?, max_volunteers=?, organizer=?, contact_phone=?, status=? WHERE id=?",
                str_repeat('s', 16) . 'i', array_merge([$image], $vals, [$id]));
            // f order: title,category,description,location,start_date,end_date,start_time,end_time,budget,actual_cost,expected,max,organizer,contact,status
            if (mysqli_stmt_errno($st)) { evt_unlink($event_dir, $new); evt_json(['ok' => false, 'msg' => 'আপডেট করা যায়নি।']); }
            if ($image !== $old['image']) { evt_unlink($event_dir, $old['image']); }   // old image delete
            $eid = $id; $msg = 'ইভেন্ট আপডেট হয়েছে।';
        } else {
            $st = evt_q($db, "INSERT INTO events (image, title, category, description, location, start_date, end_date, start_time, end_time,
                budget, actual_cost, expected_beneficiaries, max_volunteers, organizer, contact_phone, status) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                str_repeat('s', 16), array_merge([$new], $vals));
            if (mysqli_stmt_errno($st)) { evt_unlink($event_dir, $new); evt_json(['ok' => false, 'msg' => 'ডাটাবেসে সেভ করতে সমস্যা হয়েছে।']); }
            $eid = mysqli_insert_id($db); $msg = 'নতুন ইভেন্ট তৈরি হয়েছে।';
        }

        // volunteer assignment sync
        evt_q($db, "DELETE FROM event_volunteers WHERE event_id = ?", 'i', [$eid]);
        foreach ($vol_ids as $uid) {
            evt_q($db, "INSERT IGNORE INTO event_volunteers (event_id, user_id) SELECT ?, id FROM users WHERE id = ?", 'ii', [$eid, $uid]);
        }
        evt_json(['ok' => true, 'msg' => $msg]);
    }
    evt_json(['ok' => false, 'msg' => 'Unknown action.']);
}

/* ---------- Page load data ---------- */
$saved = $_SESSION['evt_filter'] ?? ['q' => '', 'status' => '', 'cat' => '', 'per' => 6, 'page' => 1];
if (!in_array($saved['status'], array_merge([''], $statuses))) { $saved['status'] = ''; }
if (!in_array($saved['cat'], array_merge([''], $categories))) { $saved['cat'] = ''; }

// Assign korar jonno SOB member/volunteer (status jai hok), volunteer type gulo age
$vres = mysqli_query($db, "SELECT id, member_name, mobile_no, photo, user_type, status FROM users ORDER BY member_name");
$VOLS = [];
while ($vres && $v = mysqli_fetch_assoc($vres)) {
    $v['photo_url'] = ($v['photo'] != '' && is_file($member_dir . basename($v['photo']))) ? $member_dir . $v['photo'] : '';
    $v['is_vol'] = in_array($v['user_type'], $vol_types) ? 1 : 0;
    unset($v['photo']);
    $VOLS[] = $v;
}
usort($VOLS, function ($x, $y) { return $y['is_vol'] <=> $x['is_vol']; });   // volunteer gulo upore

$inp = 'w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10';
$lbl = 'block text-[11px] font-bold uppercase text-slate-500 mb-1';
?>

<section class="page-content space-y-5">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-800 tracking-tight">Event Management</h2>
            <p class="text-xs text-slate-500 mt-0.5">Plan events, track costs and assign volunteers.</p>
        </div>
        <button type="button" onclick="evtOpenForm()"
            class="px-4 py-3 sm:py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-emerald-600/20 flex items-center justify-center gap-2">
            <i class="fa-solid fa-plus"></i> Create Event
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
        <?php
        $cards = [['total', 'Total Events', 'slate', 'fa-calendar-days', ''], ['Upcoming', 'Upcoming', 'sky', 'fa-hourglass-start', 'Upcoming'],
                  ['Ongoing', 'Ongoing', 'emerald', 'fa-play', 'Ongoing'], ['Completed', 'Completed', 'violet', 'fa-circle-check', 'Completed']];
        foreach ($cards as $c) { ?>
        <div <?php echo $c[4] ? 'data-fstatus="' . $c[4] . '"' : ''; ?> class="p-3.5 sm:p-5 bg-white rounded-2xl border-2 border-transparent ring-1 ring-slate-200/80 shadow-xs flex items-center gap-3 <?php echo $c[4] ? 'cursor-pointer hover:shadow-md transition' : ''; ?>">
            <div class="w-10 h-10 sm:w-12 sm:h-12 rounded-xl bg-<?php echo $c[2]; ?>-50 text-<?php echo $c[2]; ?>-600 flex items-center justify-center text-lg shrink-0"><i class="fa-solid <?php echo $c[3]; ?>"></i></div>
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

    <!-- Filters -->
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
                <?php foreach ($per_opts as $n) { echo '<option value="' . $n . '"' . ((int)$saved['per'] === $n ? ' selected' : '') . '>' . $n . ' / page</option>'; } ?>
            </select>
        </div>
        <button type="button" id="fClear" onclick="evtClear()" class="hidden px-3 py-2 border border-rose-200 text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-xl text-xs font-semibold items-center gap-1.5">
            <i class="fa-solid fa-filter-circle-xmark"></i> Remove Filter
        </button>
    </div>

    <!-- Event cards -->
    <div id="evtGrid" class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4 sm:gap-5">
        <div class="col-span-full py-16 text-center text-slate-400 text-sm">Loading...</div>
    </div>

    <div class="flex flex-col sm:flex-row items-center justify-between gap-3">
        <span id="evtInfo" class="text-xs text-slate-500"></span>
        <div id="evtPager" class="flex items-center gap-1 flex-wrap justify-center"></div>
    </div>
</section>

<!-- ADD / EDIT MODAL -->
<div id="evtFormModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs hidden items-end sm:items-center justify-center sm:p-4 z-50">
    <div class="bg-white w-full sm:max-w-3xl max-h-[94vh] flex flex-col rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-4 sm:p-5 bg-slate-900 text-white flex justify-between items-center shrink-0">
            <h3 class="font-bold text-sm flex items-center gap-2"><i class="fa-solid fa-calendar-plus text-emerald-400"></i><span id="evtFormTitle">Create Event</span></h3>
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
                <div><label class="<?php echo $lbl; ?>">Event Title *</label><input type="text" name="title" id="e_title" required class="<?php echo $inp; ?>" placeholder="e.g. Free Medical Camp 2026"></div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="<?php echo $lbl; ?>">Category *</label>
                        <select name="category" id="e_category" class="<?php echo $inp; ?>"><?php foreach ($categories as $c) { echo '<option>' . h($c) . '</option>'; } ?></select></div>
                    <div><label class="<?php echo $lbl; ?>">Status *</label>
                        <select name="status" id="e_status" class="<?php echo $inp; ?>"><?php foreach ($statuses as $c) { echo '<option>' . $c . '</option>'; } ?></select></div>
                </div>
                <div><label class="<?php echo $lbl; ?>">Description</label><textarea name="description" id="e_description" rows="3" class="<?php echo $inp; ?>" placeholder="Event purpose, activities, target group..."></textarea></div>
            </div>

            <div class="space-y-3">
                <h4 class="text-xs font-bold text-slate-800 border-b border-slate-100 pb-2"><i class="fa-solid fa-location-dot text-rose-500 mr-1.5"></i>Schedule & Location</h4>
                <div><label class="<?php echo $lbl; ?>">Location *</label><input type="text" name="location" id="e_location" required class="<?php echo $inp; ?>" placeholder="Venue, area, district"></div>
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

<!-- VIEW MODAL -->
<div id="evtViewModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs hidden items-end sm:items-center justify-center sm:p-4 z-50">
    <div class="bg-white w-full sm:max-w-2xl max-h-[94vh] overflow-y-auto rounded-t-3xl sm:rounded-2xl shadow-2xl relative">
        <button type="button" onclick="evtModal('evtViewModal', false)" class="absolute top-3 right-3 z-10 w-8 h-8 rounded-full bg-slate-900/70 text-white flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button>
        <div id="evtViewBody"></div>
    </div>
</div>

<!-- DELETE MODAL -->
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

<div id="evtToast" class="fixed top-4 left-4 right-4 sm:left-auto sm:right-5 sm:w-80 z-[60] hidden px-4 py-3 rounded-xl text-xs font-semibold shadow-lg border"></div>
<div class="hidden ring-2 ring-sky-500 ring-emerald-500 ring-violet-500 ring-slate-400"></div>

<script>
(function () {
    var CSRF = <?php echo json_encode($_SESSION['csrf']); ?>;
    var state = <?php echo json_encode($saved); ?>;
    var VOLS = <?php echo json_encode($VOLS, JSON_UNESCAPED_UNICODE); ?>;
    var $ = function (id) { return document.getElementById(id); };
    var seq = 0, timer = null, delId = 0, selected = new Set();
    var STYLE = { Upcoming: 'bg-sky-100 text-sky-700', Ongoing: 'bg-emerald-100 text-emerald-700', Completed: 'bg-slate-200 text-slate-700', Cancelled: 'bg-rose-100 text-rose-700' };
    var RING = { Upcoming: 'ring-sky-500', Ongoing: 'ring-emerald-500', Completed: 'ring-violet-500' };

    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
    function money(n) { return '৳' + Number(n || 0).toLocaleString('en-US'); }
    function url(act, params) {
        var u = new URL(location.href);
        ['act', 'es', 'est', 'ec', 'pg', 'per', 'id'].forEach(function (k) { u.searchParams.delete(k); });
        u.searchParams.set('act', act);
        for (var k in (params || {})) { u.searchParams.set(k, params[k]); }
        return u.toString();
    }
    function post(act, fd) { return fetch(url(act), { method: 'POST', body: fd, headers: { 'X-CSRF': CSRF } }).then(function (r) { return r.json(); }); }
    function toast(msg, ok) {
        var t = $('evtToast');
        t.className = 'fixed top-4 left-4 right-4 sm:left-auto sm:right-5 sm:w-80 z-[60] px-4 py-3 rounded-xl text-xs font-semibold shadow-lg border ' +
            (ok ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-rose-50 border-rose-200 text-rose-700');
        t.textContent = msg; clearTimeout(t._t); t._t = setTimeout(function () { t.classList.add('hidden'); }, 3500);
    }
    window.evtModal = function (id, show) {
        var m = $(id); m.classList.toggle('hidden', !show); m.classList.toggle('flex', show);
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
        fetch(url('list', { es: state.q, est: state.status, ec: state.cat, pg: state.page, per: state.per }))
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (my !== seq || !d.ok) { return; }
                state.page = d.page;
                $('evtGrid').innerHTML = d.rows; $('evtPager').innerHTML = d.pager;
                $('evtInfo').textContent = d.total ? 'Showing ' + d.from + '–' + d.to + ' of ' + d.total + ' events' : 'No results';
                ['total', 'Upcoming', 'Ongoing', 'Completed', 'budget', 'spent'].forEach(function (k) { setStat(k, d.stats[k]); });
                var c = $('fClear'); c.classList.toggle('hidden', !d.filtered); c.classList.toggle('flex', d.filtered);
                markCards();
            })
            .catch(function () { $('evtGrid').innerHTML = '<div class="col-span-full py-16 text-center text-rose-500 text-sm">Load করা যায়নি।</div>'; });
    }
    $('fSearch').addEventListener('input', function () {
        clearTimeout(timer); var v = this.value;
        timer = setTimeout(function () { state.q = v.trim(); state.page = 1; load(); }, 300);
    });
    $('fCat').addEventListener('change', function () { state.cat = this.value; state.page = 1; load(); });
    $('fStatus').addEventListener('change', function () { state.status = this.value; state.page = 1; load(); });
    $('fPer').addEventListener('change', function () { state.per = this.value; state.page = 1; load(); });
    $('evtPager').addEventListener('click', function (e) {
        var b = e.target.closest('[data-page]');
        if (b) { state.page = parseInt(b.dataset.page, 10); load(); window.scrollTo({ top: 0, behavior: 'smooth' }); }
    });
    document.addEventListener('click', function (e) {
        var st = e.target.closest('[data-fstatus]');
        if (st) { state.status = state.status === st.dataset.fstatus ? '' : st.dataset.fstatus; $('fStatus').value = state.status; state.page = 1; load(); }
    });
    window.evtClear = function () {
        fetch(url('clear')).then(function () {
            state = { q: '', status: '', cat: '', per: state.per, page: 1 };
            $('fSearch').value = ''; $('fCat').value = ''; $('fStatus').value = ''; load();
        });
    };

    /* ---------- CARD ACTIONS ---------- */
    $('evtGrid').addEventListener('click', function (e) {
        var b = e.target.closest('[data-act]'); if (!b) { return; }
        var id = b.dataset.id, act = b.dataset.act;
        if (act === 'view') { openView(id); }
        if (act === 'edit') { evtOpenForm(id); }
        if (act === 'delete') { delId = id; $('evtDelName').textContent = b.dataset.name; evtModal('evtDeleteModal', true); }
    });
    $('evtDelConfirm').addEventListener('click', function () {
        var fd = new FormData(); fd.append('id', delId);
        post('delete', fd).then(function (d) { toast(d.msg, d.ok); evtModal('evtDeleteModal', false); if (d.ok) { load(); } });
    });

    /* ---------- VIEW ---------- */
    function openView(id) {
        fetch(url('get', { id: id })).then(function (r) { return r.json(); }).then(function (d) {
            if (!d.ok) { toast(d.msg, false); return; }
            var e = d.data, diff = Number(e.budget) - Number(e.actual_cost);
            var cover = e.image_url ? '<img src="' + esc(e.image_url) + '" class="w-full h-full object-cover">'
                : '<div class="w-full h-full bg-gradient-to-br from-emerald-500 via-teal-600 to-slate-800 flex items-center justify-center text-white/80 text-5xl"><i class="fa-solid fa-calendar-days"></i></div>';
            var row = function (ic, l, v) { return '<div class="p-3 rounded-xl bg-slate-50 border border-slate-100"><p class="text-[10px] font-bold uppercase text-slate-400"><i class="fa-solid ' + ic + ' mr-1"></i>' + l + '</p><p class="text-xs font-semibold text-slate-800 mt-0.5 break-words">' + (v || '<span class="text-slate-300">—</span>') + '</p></div>'; };
            var dates = e.start_date === e.end_date ? e.start_date : e.start_date + ' → ' + e.end_date;
            var time = (e.start_time ? e.start_time.slice(0, 5) : '') + (e.end_time ? ' – ' + e.end_time.slice(0, 5) : '');
            var vols = d.vols.length ? d.vols.map(function (v) {
                var av = v.photo_url ? '<img src="' + esc(v.photo_url) + '" class="w-9 h-9 rounded-full object-cover">'
                    : '<div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs">' + esc((v.member_name || '?').charAt(0).toUpperCase()) + '</div>';
                return '<div class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-100 bg-white">' + av + '<div class="min-w-0"><p class="text-xs font-semibold text-slate-800 truncate">' + esc(v.member_name) + '</p><p class="text-[10px] text-slate-400">+88' + esc(v.mobile_no) + '</p></div></div>';
            }).join('') : '<p class="text-xs text-slate-400 col-span-full">কোনো volunteer assign করা হয়নি।</p>';

            $('evtViewBody').innerHTML =
                '<div class="h-48 sm:h-56 relative">' + cover + '<div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 to-transparent"></div>' +
                '<div class="absolute bottom-4 left-4 right-4 text-white"><div class="flex gap-2 mb-2"><span class="px-2.5 py-1 rounded-full text-[10px] font-bold ' + (STYLE[e.status] || '') + '">' + esc(e.status) + '</span><span class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-white/20 backdrop-blur">' + esc(e.category) + '</span></div><h3 class="text-lg sm:text-xl font-bold leading-snug">' + esc(e.title) + '</h3></div></div>' +
                '<div class="p-4 sm:p-6 space-y-5">' +
                (e.description ? '<p class="text-xs text-slate-600 leading-relaxed whitespace-pre-line">' + esc(e.description) + '</p>' : '') +
                '<div class="grid grid-cols-2 gap-3">' + row('fa-calendar', 'Date', esc(dates)) + row('fa-clock', 'Time', esc(time)) + row('fa-location-dot', 'Location', esc(e.location)) +
                row('fa-user-tie', 'Organizer', esc(e.organizer)) + row('fa-phone', 'Contact', esc(e.contact_phone)) + row('fa-people-group', 'Expected Beneficiaries', esc(e.expected_beneficiaries)) + '</div>' +
                '<div class="grid grid-cols-3 gap-3 text-center"><div class="p-3 rounded-xl bg-slate-900 text-white"><p class="text-[10px] uppercase text-slate-300 font-bold">Budget</p><p class="text-sm font-bold">' + money(e.budget) + '</p></div>' +
                '<div class="p-3 rounded-xl bg-emerald-50 text-emerald-700"><p class="text-[10px] uppercase font-bold">Spent</p><p class="text-sm font-bold">' + money(e.actual_cost) + '</p></div>' +
                '<div class="p-3 rounded-xl ' + (diff < 0 ? 'bg-rose-50 text-rose-700' : 'bg-sky-50 text-sky-700') + '"><p class="text-[10px] uppercase font-bold">' + (diff < 0 ? 'Over' : 'Remaining') + '</p><p class="text-sm font-bold">' + money(Math.abs(diff)) + '</p></div></div>' +
                '<div><h4 class="text-xs font-bold text-slate-800 mb-3"><i class="fa-solid fa-user-group text-teal-600 mr-1.5"></i>Volunteers (' + d.vols.length + (Number(e.max_volunteers) > 0 ? '/' + e.max_volunteers : '') + ')</h4><div class="grid grid-cols-1 sm:grid-cols-2 gap-2">' + vols + '</div></div>' +
                '<div class="flex gap-2"><button type="button" onclick="evtModal(\'evtViewModal\', false); evtOpenForm(' + e.id + ')" class="flex-1 py-3 rounded-xl bg-sky-600 text-white text-xs font-semibold"><i class="fa-solid fa-pen-to-square mr-1"></i>Edit Event</button>' +
                '<button type="button" onclick="evtModal(\'evtViewModal\', false)" class="flex-1 py-3 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600">Close</button></div></div>';
            evtModal('evtViewModal', true);
        });
    }

    /* ---------- VOLUNTEER PICKER ---------- */
    var volTab = 'all';
    function visibleVols() {
        var q = $('e_volSearch').value.trim().toLowerCase();
        return VOLS.filter(function (v) {
            if (volTab === 'vol' && !+v.is_vol) { return false; }
            return !q || (v.member_name + ' ' + v.mobile_no).toLowerCase().indexOf(q) !== -1;
        });
    }
    function updCount() {
        var m = parseInt($('e_max_volunteers').value || 0, 10);
        $('e_volCount').textContent = selected.size + (m > 0 ? ' / ' + m : '') + ' selected';
    }
    function renderVols() {
        var list = visibleVols(), html = '';
        list.forEach(function (v) {
            var av = v.photo_url ? '<img src="' + esc(v.photo_url) + '" class="w-9 h-9 rounded-full object-cover shrink-0">'
                : '<div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs shrink-0">' + esc((v.member_name || '?').charAt(0).toUpperCase()) + '</div>';
            var tag = '<span class="px-1.5 py-0.5 rounded text-[9px] font-bold ' + (+v.is_vol ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500') + '">' + esc(v.user_type || '') + '</span>' +
                (v.status && v.status !== 'Active' ? ' <span class="px-1.5 py-0.5 rounded text-[9px] font-bold bg-amber-50 text-amber-700">' + esc(v.status) + '</span>' : '');
            html += '<label class="flex items-center gap-3 p-2.5 cursor-pointer hover:bg-slate-50"><input type="checkbox" value="' + v.id + '" class="rounded border-slate-300 text-emerald-600 w-4 h-4" ' + (selected.has(+v.id) ? 'checked' : '') + '>' + av +
                '<div class="min-w-0 flex-1"><p class="text-xs font-semibold text-slate-800 truncate">' + esc(v.member_name) + '</p><p class="text-[10px] text-slate-400">+88' + esc(v.mobile_no) + ' &nbsp;' + tag + '</p></div></label>';
        });
        $('e_volList').innerHTML = html || '<p class="p-4 text-center text-xs text-slate-400">কোনো member পাওয়া যায়নি।</p>';
        $('e_volTotal').textContent = 'Showing ' + list.length + ' of ' + VOLS.length + ' members';
        updCount();
    }
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

    /* ---------- ADD / EDIT ---------- */
    var fields = ['title', 'category', 'status', 'description', 'location', 'start_date', 'end_date', 'start_time', 'end_time', 'budget', 'actual_cost', 'expected_beneficiaries', 'max_volunteers', 'organizer', 'contact_phone'];
    function setPrev(src) { $('e_prev').classList.toggle('hidden', !src); $('e_prevIcon').classList.toggle('hidden', !!src); $('e_prev').src = src || ''; }

    window.evtOpenForm = function (id) {
        $('evtForm').reset(); $('e_id').value = 0; setPrev(''); selected = new Set(); $('e_volSearch').value = '';
        $('e_removeWrap').classList.add('hidden'); $('e_removeWrap').classList.remove('flex'); $('e_hint').textContent = '';
        $('evtFormTitle').textContent = 'Create Event';
        if (!id) { renderVols(); evtModal('evtFormModal', true); return; }
        $('evtFormTitle').textContent = 'Edit Event';
        fetch(url('get', { id: id })).then(function (r) { return r.json(); }).then(function (d) {
            if (!d.ok) { toast(d.msg, false); return; }
            $('e_id').value = d.data.id;
            fields.forEach(function (k) { var v = d.data[k]; if (v == null) { v = ''; } if (/time$/.test(k)) { v = String(v).slice(0, 5); } $('e_' + k).value = v; });
            d.vols.forEach(function (v) {
                selected.add(+v.id);
                if (!VOLS.some(function (x) { return +x.id === +v.id; })) { VOLS.push(v); }   // inactive hole o dekhabe
            });
            setPrev(d.data.image_url);
            if (d.data.image_url) {
                $('e_removeWrap').classList.remove('hidden'); $('e_removeWrap').classList.add('flex');
                $('e_hint').textContent = 'নতুন ছবি দিলে পুরনো ছবি ডিলিট হয়ে যাবে।';
            }
            renderVols(); evtModal('evtFormModal', true);
        });
    };
    $('e_image').addEventListener('change', function () {
        var f = this.files[0]; if (!f) { return; }
        if (f.size > 3 * 1024 * 1024) { toast('ছবির সাইজ সর্বোচ্চ 3MB।', false); this.value = ''; return; }
        var r = new FileReader(); r.onload = function (ev) { setPrev(ev.target.result); }; r.readAsDataURL(f);
    });
    $('e_start_date').addEventListener('change', function () { if (!$('e_end_date').value || $('e_end_date').value < this.value) { $('e_end_date').value = this.value; } });

    $('evtForm').addEventListener('submit', function (e) {
        e.preventDefault();
        var max = parseInt($('e_max_volunteers').value || 0, 10);
        if (max > 0 && selected.size > max) { toast('Max volunteer limit (' + max + ') এর বেশি select করা হয়েছে।', false); return; }
        var fd = new FormData(this);
        selected.forEach(function (id) { fd.append('volunteers[]', id); });
        var btn = $('e_submit'); btn.disabled = true; btn.textContent = 'Saving...';
        post('save', fd).then(function (d) { toast(d.msg, d.ok); if (d.ok) { evtModal('evtFormModal', false); load(); } })
            .catch(function () { toast('Server error.', false); })
            .finally(function () { btn.disabled = false; btn.textContent = 'Save Event'; });
    });

    ['evtFormModal', 'evtViewModal', 'evtDeleteModal'].forEach(function (id) {
        $(id).addEventListener('mousedown', function (e) { if (e.target === this) { evtModal(id, false); } });
    });
    load();
})();
</script>