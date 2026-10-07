<?php
/* ==========================================================
   meetings.php  ->  index.php?page=meetings
   - Admin: add / edit / delete / status / "who can add" permission
   - Permitted user (meeting_editors): add + edit + status update (delete noy)
   - Others: view only (shudhu tader user_type er meeting)
   - Calendar: meeting thaka date gulo dekhay
   Table: meetings.sql age import korun.
   ========================================================== */
if (session_status() === PHP_SESSION_NONE) { @session_start(); }
if (!function_exists('h')) { function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
mysqli_set_charset($db, 'utf8mb4');

$TYPES = ['Admin', 'General Member', 'Associate Member', 'Life Member', 'Volunteer Member'];
$STATUS = ['Scheduled' => 'bg-sky-100 text-sky-700', 'Ongoing' => 'bg-teal-100 text-teal-700', 'Completed' => 'bg-emerald-100 text-emerald-700',
           'Postponed' => 'bg-amber-100 text-amber-700', 'Cancelled' => 'bg-rose-100 text-rose-700'];
$MODE = ['Online' => 'fa-video', 'Offline' => 'fa-location-dot', 'Hybrid' => 'fa-people-arrows'];

/* ---------- Role (database theke verify) ---------- */
$me = (int)($_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['uid'] ?? $_SESSION['member_id'] ?? 0);
$utype = $_SESSION['user_type'] ?? '';
if ($me > 0) { $ur = mysqli_fetch_row(mysqli_query($db, "SELECT user_type FROM users WHERE id = " . $me . " LIMIT 1")); if ($ur) { $utype = $ur[0]; } }
$isAdmin = ($utype === 'Admin');

function mt_rows($db, $sql, $t = '', $p = []) {
    $st = mysqli_prepare($db, $sql);
    if (!$st) { return []; }
    if ($t !== '') { mysqli_stmt_bind_param($st, $t, ...$p); }
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st); $o = [];
    while ($res && $r = mysqli_fetch_assoc($res)) { $o[] = $r; }
    mysqli_stmt_close($st);
    return $o;
}
function mt_run($db, $sql, $t, $p) { $st = mysqli_prepare($db, $sql); mysqli_stmt_bind_param($st, $t, ...$p); $ok = mysqli_stmt_execute($st); $n = mysqli_stmt_affected_rows($st); mysqli_stmt_close($st); return $ok ? $n : -1; }

if (!in_array($utype, $TYPES)) {
    echo '<section class="page-content max-w-xl mx-auto"><div class="p-6 bg-white rounded-2xl ring-1 ring-slate-200 text-center space-y-2"><i class="fa-solid fa-lock text-3xl text-slate-300"></i>
          <h3 class="font-bold text-slate-800">Access Restricted</h3><p class="text-xs text-slate-500">Please login to view meetings. / মিটিং দেখতে লগইন করুন।</p></div></section>';
    return;
}
$dbReady = true;
try { foreach (['meetings', 'meeting_editors'] as $tb) { if (!mysqli_query($db, "SELECT 1 FROM $tb LIMIT 1")) { $dbReady = false; } } } catch (Throwable $e) { $dbReady = false; }
if (!$dbReady) {
    echo '<section class="page-content max-w-xl mx-auto"><div class="p-6 bg-amber-50 border border-amber-200 rounded-2xl text-center space-y-2"><i class="fa-solid fa-database text-3xl text-amber-400"></i>
          <h3 class="font-bold text-slate-800">Database tables not found</h3><p class="text-xs text-slate-600">Please import <b>meetings.sql</b> in phpMyAdmin first. / আগে meetings.sql ইম্পোর্ট করুন।</p></div></section>';
    return;
}
$isEditor = !$isAdmin && $me > 0 && mt_rows($db, "SELECT 1 x FROM meeting_editors WHERE user_id = ?", 'i', [$me]);
$canEdit = $isAdmin || $isEditor;
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
$page = $_GET['page'] ?? 'meetings';
$base = 'index.php?page=' . urlencode($page);

/* ---------- ACTIONS ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
    $do = $_POST['do'] ?? ''; $ok = true; $msg = ''; $id = (int)($_POST['id'] ?? 0);
    $back = (strpos($_POST['back'] ?? '', $base) === 0) ? $_POST['back'] : $base;

    if ($isAdmin && $do === 'perm') {
        mt_run($db, "DELETE FROM meeting_editors WHERE id > ?", 'i', [0]);
        foreach (array_unique(array_filter(array_map('intval', (array)($_POST['editors'] ?? [])))) as $u) {
            mt_run($db, "INSERT IGNORE INTO meeting_editors (user_id, granted_by) SELECT id, ? FROM users WHERE id = ?", 'ii', [$me, $u]);
        }
        $msg = 'Permissions saved.';
    } elseif ($isAdmin && $do === 'delete') {
        mt_run($db, "DELETE FROM meetings WHERE id = ?", 'i', [$id]); $msg = 'Meeting deleted.';
    } elseif ($canEdit && $do === 'status') {
        $s = $_POST['status'] ?? '';
        if (!isset($STATUS[$s])) { $ok = false; $msg = 'Invalid status.'; }
        else { mt_run($db, "UPDATE meetings SET status = ?, minutes = ?, updated_by = ? WHERE id = ?", 'ssii', [$s, trim($_POST['minutes'] ?? ''), $me ?: null, $id]); $msg = 'Meeting status updated.'; }
    } elseif ($canEdit && $do === 'save') {
        $title = trim($_POST['title'] ?? ''); $date = $_POST['meeting_date'] ?? '';
        $mtype = isset($MODE[$_POST['mtype'] ?? '']) ? $_POST['mtype'] : 'Offline';
        $link = trim($_POST['link'] ?? ''); $addr = trim($_POST['address'] ?? '');
        $aud = array_values(array_intersect($TYPES, (array)($_POST['audience'] ?? [])));
        $status = isset($STATUS[$_POST['status'] ?? '']) ? $_POST['status'] : 'Scheduled';
        $st = ($_POST['start_time'] ?? '') ?: null; $et = ($_POST['end_time'] ?? '') ?: null;
        if ($title === '' || !strtotime($date)) { $ok = false; $msg = 'Meeting title and date are required.'; }
        elseif (!$aud) { $ok = false; $msg = 'Select at least one audience (who can view).'; }
        elseif ($link !== '' && !preg_match('#^https?://#i', $link)) { $ok = false; $msg = 'Meeting link must start with http:// or https://'; }
        elseif (($mtype === 'Online' && $link === '') || ($mtype === 'Offline' && $addr === '') || ($mtype === 'Hybrid' && $link === '' && $addr === '')) { $ok = false; $msg = $mtype === 'Online' ? 'Online meeting needs a link.' : 'Meeting address is required.'; }
        elseif ($st && $et && $et <= $st) { $ok = false; $msg = 'End time must be after start time.'; }
        else {
            $f = [$title, trim($_POST['agenda'] ?? ''), trim($_POST['category'] ?? '') ?: 'General', date('Y-m-d', strtotime($date)), $st, $et, $mtype, $link, $addr,
                  trim($_POST['organizer'] ?? ''), implode(',', $aud), $status, trim($_POST['minutes'] ?? '')];
            if ($id) {
                mt_run($db, "UPDATE meetings SET title=?, agenda=?, category=?, meeting_date=?, start_time=?, end_time=?, mtype=?, link=?, address=?, organizer=?, audience=?, status=?, minutes=?, updated_by=? WHERE id=?",
                    str_repeat('s', 13) . 'ii', array_merge($f, [$me ?: null, $id]));
                $msg = 'Meeting updated.';
            } else {
                mt_run($db, "INSERT INTO meetings (title, agenda, category, meeting_date, start_time, end_time, mtype, link, address, organizer, audience, status, minutes, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                    str_repeat('s', 13) . 'i', array_merge($f, [$me ?: null]));
                $nid = mysqli_insert_id($db);
                mt_run($db, "UPDATE meetings SET meeting_no = CONCAT('MTG-', YEAR(meeting_date), '-', LPAD(id, 5, '0')) WHERE id = ?", 'i', [$nid]);
                $msg = 'Meeting scheduled.';
            }
        }
    }
    $_SESSION['mt_flash'] = [$ok, $msg];
    header('Location: ' . $back); exit;
}
$flash = $_SESSION['mt_flash'] ?? null; unset($_SESSION['mt_flash']);

/* ---------- FILTERS ---------- */
$q = trim($_GET['q'] ?? ''); $fs = isset($STATUS[$_GET['status'] ?? '']) ? $_GET['status'] : ''; $fm = isset($MODE[$_GET['type'] ?? '']) ? $_GET['type'] : '';
$when = in_array($_GET['when'] ?? '', ['upcoming', 'past', 'all']) ? $_GET['when'] : 'upcoming';
$day = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['day'] ?? '') ? $_GET['day'] : '';
$cm = preg_match('/^\d{4}-\d{2}$/', $_GET['cm'] ?? '') ? $_GET['cm'] : ($day ? substr($day, 0, 7) : date('Y-m'));
$pg = max(1, (int)($_GET['pg'] ?? 1));
function mt_url($o = []) { global $base, $q, $fs, $fm, $when, $day, $cm; return $base . '&' . http_build_query(array_merge(['q' => $q, 'status' => $fs, 'type' => $fm, 'when' => $when, 'day' => $day, 'cm' => $cm], $o)); }

// scope: je meeting ei user dekhte pabe
$vis = "FIND_IN_SET(?, m.audience) > 0";
$scope = 'WHERE 1=1'; $t0 = ''; $p0 = [];
if (!$isAdmin) { if ($isEditor) { $scope .= " AND (m.created_by = ? OR $vis)"; $t0 = 'is'; $p0 = [$me, $utype]; } else { $scope .= " AND $vis"; $t0 = 's'; $p0 = [$utype]; } }

$w = $scope; $t = $t0; $p = $p0;
if ($q !== '')  { $w .= " AND (m.title LIKE ? OR m.agenda LIKE ? OR m.address LIKE ?)"; $l = '%' . addcslashes($q, '%_\\') . '%'; $t .= 'sss'; array_push($p, $l, $l, $l); }
if ($fs !== '') { $w .= " AND m.status = ?"; $t .= 's'; $p[] = $fs; }
if ($fm !== '') { $w .= " AND m.mtype = ?"; $t .= 's'; $p[] = $fm; }
if ($day !== '') { $w .= " AND m.meeting_date = ?"; $t .= 's'; $p[] = $day; }
elseif ($when === 'upcoming') { $w .= " AND m.meeting_date >= CURDATE()"; }
elseif ($when === 'past') { $w .= " AND m.meeting_date < CURDATE()"; }
$order = ($day === '' && $when === 'upcoming') ? 'm.meeting_date ASC, m.start_time ASC' : 'm.meeting_date DESC, m.start_time DESC';
$total = (int)(mt_rows($db, "SELECT COUNT(*) c FROM meetings m $w", $t, $p)[0]['c'] ?? 0);
$pages = max(1, (int)ceil($total / 8)); $pg = min($pg, $pages);
$meetings = mt_rows($db, "SELECT m.*, u.member_name author FROM meetings m LEFT JOIN users u ON u.id = m.created_by $w ORDER BY $order LIMIT 8 OFFSET " . (($pg - 1) * 8), $t, $p);
foreach ($meetings as &$m) {
    $m['audience_list'] = array_filter(explode(',', $m['audience']));
    $m['date_label'] = date('l, d F Y', strtotime($m['meeting_date']));
    $m['time_label'] = $m['start_time'] ? date('h:i A', strtotime($m['start_time'])) . ($m['end_time'] ? ' – ' . date('h:i A', strtotime($m['end_time'])) : '') : 'Time not set';
    $m['is_today'] = $m['meeting_date'] === date('Y-m-d');
} unset($m);

$S = mt_rows($db, "SELECT COUNT(*) total, COALESCE(SUM(m.meeting_date = CURDATE() AND m.status NOT IN ('Cancelled')),0) today,
        COALESCE(SUM(m.meeting_date >= CURDATE() AND m.status IN ('Scheduled','Ongoing','Postponed')),0) upc, COALESCE(SUM(m.status = 'Completed'),0) done FROM meetings m $scope", $t0, $p0)[0];

/* ---------- Calendar (Saturday theke shuru) ---------- */
$first = strtotime($cm . '-01'); $dim = (int)date('t', $first); $off = ((int)date('w', $first) + 1) % 7;
$cal = [];
foreach (mt_rows($db, "SELECT m.meeting_date d, COUNT(*) c, SUM(m.status='Completed') done, SUM(m.status='Cancelled') x FROM meetings m $scope AND m.meeting_date BETWEEN ? AND ? GROUP BY m.meeting_date",
        $t0 . 'ss', array_merge($p0, [$cm . '-01', $cm . '-' . $dim])) as $r) { $cal[$r['d']] = $r; }
$prevM = date('Y-m', strtotime('-1 month', $first)); $nextM = date('Y-m', strtotime('+1 month', $first));

$USERS = []; $EDITORS = [];
if ($isAdmin) {
    $USERS = mt_rows($db, "SELECT id, member_name, user_type FROM users WHERE user_type <> 'Admin' ORDER BY FIELD(user_type,'Volunteer Member','Life Member','Associate Member','General Member'), member_name");
    $EDITORS = array_map('intval', array_column(mt_rows($db, "SELECT user_id FROM meeting_editors"), 'user_id'));
}
$inp = 'w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10';
$lbl = 'block text-[11px] font-bold uppercase text-slate-500 mb-1';
$csrf = '<input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '"><input type="hidden" name="back" value="' . h(mt_url(['pg' => $pg])) . '">';
?>
<section class="page-content space-y-5 max-w-7xl mx-auto">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-800">Meetings <small class="text-xs font-medium text-slate-400">/ মিটিং</small></h2>
            <p class="text-xs text-slate-500 mt-0.5"><?= $isAdmin ? 'Admin — schedule meetings, update status, choose who can view and who can add.' : ($isEditor ? 'You can add and edit meetings.' : 'Meetings for ' . h($utype) . ' (view only).') ?></p>
        </div>
        <div class="grid <?= $isAdmin ? 'grid-cols-2' : 'grid-cols-1' ?> sm:flex gap-2">
            <?php if ($isAdmin) { ?><button type="button" onclick="mtModal('mtPerm', true)" class="px-4 py-3 sm:py-2.5 border border-slate-200 bg-white rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50"><i class="fa-solid fa-user-shield mr-1.5"></i>Who can add</button><?php } ?>
            <?php if ($canEdit) { ?><button type="button" onclick="mtForm()" class="px-4 py-3 sm:py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-emerald-600/20"><i class="fa-solid fa-plus mr-1.5"></i>New Meeting</button><?php } ?>
        </div>
    </div>

    <?php if ($flash) { ?><div class="p-3 rounded-xl text-xs font-semibold border <?= $flash[0] ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-rose-50 border-rose-200 text-rose-700' ?>"><?= h($flash[1]) ?></div><?php } ?>

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3">
        <?php foreach ([['Today', (int)$S['today'], 'rose', 'fa-bell'], ['Upcoming', (int)$S['upc'], 'sky', 'fa-calendar-check'], ['Completed', (int)$S['done'], 'emerald', 'fa-circle-check'], ['Total', (int)$S['total'], 'slate', 'fa-people-group']] as $c) { ?>
        <div class="p-4 bg-white rounded-2xl ring-1 ring-slate-200/80 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-<?= $c[2] ?>-50 text-<?= $c[2] ?>-600 flex items-center justify-center text-lg shrink-0"><i class="fa-solid <?= $c[3] ?>"></i></div>
            <div><p class="text-[10px] font-bold uppercase text-slate-400"><?= $c[0] ?></p><p class="text-xl font-bold text-slate-800"><?= $c[1] ?></p></div>
        </div>
        <?php } ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">

        <!-- CALENDAR -->
        <div class="lg:col-span-4 lg:sticky lg:top-4 bg-white rounded-2xl ring-1 ring-slate-200/80 p-4">
            <div class="flex items-center justify-between mb-3">
                <a href="<?= h(mt_url(['cm' => $prevM, 'day' => '', 'pg' => 1])) ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 hover:bg-slate-50"><i class="fa-solid fa-chevron-left text-xs"></i></a>
                <div class="text-center"><p class="font-bold text-sm text-slate-800"><?= h(date('F Y', $first)) ?></p>
                    <a href="<?= h(mt_url(['cm' => date('Y-m'), 'day' => '', 'pg' => 1])) ?>" class="text-[10px] font-bold text-emerald-600">Today</a></div>
                <a href="<?= h(mt_url(['cm' => $nextM, 'day' => '', 'pg' => 1])) ?>" class="w-8 h-8 flex items-center justify-center rounded-lg border border-slate-200 hover:bg-slate-50"><i class="fa-solid fa-chevron-right text-xs"></i></a>
            </div>
            <div class="grid grid-cols-7 gap-1 text-center">
                <?php foreach (['Sat', 'Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri'] as $d) { echo '<div class="text-[10px] font-bold text-slate-400 py-1">' . $d . '</div>'; }
                for ($i = 0; $i < $off; $i++) { echo '<div></div>'; }
                for ($d = 1; $d <= $dim; $d++) {
                    $ds = $cm . '-' . str_pad($d, 2, '0', STR_PAD_LEFT); $c = $cal[$ds] ?? null; $isT = $ds === date('Y-m-d'); $sel = $ds === $day;
                    $dot = !$c ? '' : (((int)$c['x'] === (int)$c['c']) ? 'bg-rose-500' : (((int)$c['done'] === (int)$c['c']) ? 'bg-slate-400' : 'bg-emerald-500'));
                    $cls = $sel ? 'bg-emerald-600 text-white' : ($isT ? 'bg-sky-50 text-sky-700 ring-1 ring-sky-300' : ($c ? 'bg-emerald-50 text-slate-800 hover:bg-emerald-100' : 'text-slate-600 hover:bg-slate-50'));
                    echo '<a href="' . h(mt_url(['day' => $sel ? '' : $ds, 'cm' => $cm, 'pg' => 1])) . '" class="relative aspect-square rounded-lg flex flex-col items-center justify-center text-xs font-semibold ' . $cls . '">' . $d .
                         ($c ? '<span class="absolute bottom-1 flex items-center gap-0.5"><i class="w-1.5 h-1.5 rounded-full ' . ($sel ? 'bg-white' : $dot) . '"></i>' . ((int)$c['c'] > 1 ? '<b class="text-[8px]">' . (int)$c['c'] . '</b>' : '') . '</span>' : '') . '</a>';
                } ?>
            </div>
            <div class="flex flex-wrap gap-x-3 gap-y-1 mt-3 text-[10px] text-slate-500"><span><i class="inline-block w-2 h-2 rounded-full bg-emerald-500"></i> Meeting</span><span><i class="inline-block w-2 h-2 rounded-full bg-slate-400"></i> Completed</span><span><i class="inline-block w-2 h-2 rounded-full bg-rose-500"></i> Cancelled</span></div>
            <?php if ($day) { ?><p class="mt-3 text-xs bg-emerald-50 text-emerald-700 rounded-lg px-3 py-2 font-semibold">Showing <?= h(date('d M Y', strtotime($day))) ?> · <a class="underline" href="<?= h(mt_url(['day' => '', 'pg' => 1])) ?>">Clear</a></p><?php } ?>
        </div>

        <!-- LIST -->
        <div class="lg:col-span-8 space-y-4">
            <form method="GET" action="index.php" class="bg-white rounded-2xl border border-slate-200/80 p-3 grid grid-cols-2 md:grid-cols-12 gap-2">
                <input type="hidden" name="page" value="<?= h($page) ?>"><input type="hidden" name="cm" value="<?= h($cm) ?>"><input type="hidden" name="day" value="<?= h($day) ?>">
                <div class="col-span-2 md:col-span-12 grid grid-cols-3 gap-1 p-1 bg-slate-100 rounded-xl">
                    <?php foreach (['upcoming' => 'Upcoming', 'past' => 'Past', 'all' => 'All'] as $k => $l) { ?><a href="<?= h(mt_url(['when' => $k, 'day' => '', 'pg' => 1])) ?>" class="py-2 text-center rounded-lg text-xs font-bold <?= (!$day && $when === $k) ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500' ?>"><?= $l ?></a><?php } ?>
                </div>
                <input type="text" name="q" value="<?= h($q) ?>" placeholder="Search meeting... / খুঁজুন" class="col-span-2 md:col-span-5 <?= $inp ?>">
                <select name="status" onchange="this.form.submit()" class="md:col-span-3 <?= $inp ?>"><option value="">All Status</option><?php foreach ($STATUS as $k => $_) { echo '<option' . ($fs === $k ? ' selected' : '') . '>' . $k . '</option>'; } ?></select>
                <select name="type" onchange="this.form.submit()" class="md:col-span-3 <?= $inp ?>"><option value="">Online / Offline</option><?php foreach ($MODE as $k => $_) { echo '<option' . ($fm === $k ? ' selected' : '') . '>' . $k . '</option>'; } ?></select>
                <button class="md:col-span-1 px-3 py-2.5 bg-slate-800 text-white rounded-xl text-xs font-semibold"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>

            <?php foreach ($meetings as $m) { $pd = strtotime($m['meeting_date']); ?>
            <div class="bg-white rounded-2xl ring-1 <?= $m['is_today'] ? 'ring-2 ring-rose-300' : 'ring-slate-200/80' ?> p-4 flex gap-4 hover:shadow-lg transition">
                <div class="w-16 shrink-0 rounded-xl overflow-hidden text-center ring-1 ring-slate-200 self-start">
                    <div class="bg-emerald-600 text-white text-[10px] font-bold py-1 uppercase"><?= date('M', $pd) ?></div>
                    <div class="text-2xl font-bold text-slate-800 leading-none pt-2"><?= date('d', $pd) ?></div>
                    <div class="text-[10px] text-slate-400 pb-1.5"><?= date('D', $pd) ?></div>
                </div>
                <div class="min-w-0 flex-1 space-y-2">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="px-2.5 py-1 rounded-full text-[10px] font-bold <?= $STATUS[$m['status']] ?>"><?= h($m['status']) ?></span>
                        <span class="px-2.5 py-1 rounded-full bg-slate-100 text-slate-600 text-[10px] font-semibold"><i class="fa-solid <?= $MODE[$m['mtype']] ?> mr-1"></i><?= h($m['mtype']) ?></span>
                        <span class="px-2.5 py-1 rounded-full bg-violet-50 text-violet-700 text-[10px] font-semibold"><?= h($m['category']) ?></span>
                        <?php if ($m['is_today']) { ?><span class="px-2 py-1 rounded-full bg-rose-600 text-white text-[10px] font-bold">TODAY</span><?php } ?>
                    </div>
                    <h3 class="font-bold text-sm text-slate-800 leading-snug cursor-pointer hover:text-emerald-600" onclick="mtView(<?= (int)$m['id'] ?>)"><?= h($m['title']) ?></h3>
                    <div class="text-xs text-slate-500 space-y-1">
                        <p><i class="fa-regular fa-clock w-4 text-sky-500"></i> <?= h($m['time_label']) ?></p>
                        <p class="truncate"><i class="fa-solid <?= $m['mtype'] === 'Online' ? 'fa-link' : 'fa-location-dot' ?> w-4 text-rose-500"></i> <?= h($m['mtype'] === 'Online' ? 'Online meeting' : ($m['address'] ?: 'Online meeting')) ?></p>
                    </div>
                    <?php if ($canEdit) { ?><div class="flex flex-wrap gap-1"><?php foreach ($m['audience_list'] as $a) { echo '<span class="px-1.5 py-0.5 rounded bg-teal-50 text-teal-700 text-[9px] font-bold">' . h($a) . '</span>'; } ?></div><?php } ?>
                    <div class="flex flex-wrap gap-2 pt-1">
                        <button type="button" onclick="mtView(<?= (int)$m['id'] ?>)" class="px-3 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 text-[11px] font-bold hover:bg-emerald-100"><i class="fa-solid fa-eye mr-1"></i>Details</button>
                        <?php if ($canEdit) { ?><button type="button" onclick="mtForm(<?= (int)$m['id'] ?>)" class="px-3 py-1.5 rounded-lg bg-sky-50 text-sky-700 text-[11px] font-bold hover:bg-sky-100"><i class="fa-solid fa-pen mr-1"></i>Edit</button><?php } ?>
                        <?php if ($isAdmin) { ?><form method="POST" action="" onsubmit="return confirm('Delete this meeting? / মিটিংটি মুছে ফেলবেন?')"><?= $csrf ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
                            <button class="px-3 py-1.5 rounded-lg bg-rose-50 text-rose-600 text-[11px] font-bold hover:bg-rose-100"><i class="fa-solid fa-trash mr-1"></i>Delete</button></form><?php } ?>
                    </div>
                </div>
            </div>
            <?php } if (!$meetings) { echo '<p class="py-14 text-center text-slate-400 text-sm bg-white rounded-2xl ring-1 ring-slate-200/80"><i class="fa-regular fa-calendar-xmark text-4xl mb-3 block"></i>No meetings found. / কোনো মিটিং নেই।</p>'; } ?>

            <?php if ($pages > 1) { ?>
            <div class="flex items-center justify-between"><span class="text-xs text-slate-500">Page <?= $pg ?> / <?= $pages ?></span>
                <div class="flex gap-2">
                    <?php if ($pg > 1) { ?><a href="<?= h(mt_url(['pg' => $pg - 1])) ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-xs font-semibold">‹ Prev</a><?php } ?>
                    <?php if ($pg < $pages) { ?><a href="<?= h(mt_url(['pg' => $pg + 1])) ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-xs font-semibold">Next ›</a><?php } ?>
                </div></div>
            <?php } ?>
        </div>
    </div>
</section>

<datalist id="dl_cat"><option>General</option><option>Executive Committee</option><option>Volunteer</option><option>Event Planning</option><option>Fundraising</option><option>Training</option><option>Annual General Meeting</option></datalist>

<!-- DETAILS -->
<div id="mtView" class="fixed inset-0 bg-slate-900/60 hidden items-end sm:items-center justify-center sm:p-4 z-50">
    <div class="bg-white w-full sm:max-w-2xl max-h-[94vh] overflow-y-auto rounded-t-3xl sm:rounded-2xl shadow-2xl relative">
        <button type="button" onclick="mtModal('mtView', false)" class="absolute top-3 right-3 z-10 w-8 h-8 rounded-full bg-slate-900/70 text-white flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button>
        <div id="mtBody"></div>
        <?php if ($canEdit) { ?>
        <form method="POST" action="" class="m-4 sm:m-6 mt-0 rounded-2xl border border-emerald-200 bg-emerald-50/40 p-4 space-y-3">
            <?= $csrf ?><input type="hidden" name="do" value="status"><input type="hidden" name="id" id="s_id">
            <p class="text-xs font-bold text-emerald-800"><i class="fa-solid fa-clipboard-check mr-1.5"></i>Update Status & Minutes <small class="font-normal">/ স্ট্যাটাস ও কার্যবিবরণী</small></p>
            <select name="status" id="s_status" class="<?= $inp ?>"><?php foreach ($STATUS as $k => $_) { echo '<option>' . $k . '</option>'; } ?></select>
            <textarea name="minutes" id="s_minutes" rows="3" class="<?= $inp ?>" placeholder="Decisions / minutes of the meeting..."></textarea>
            <button class="w-full sm:w-auto px-6 py-3 sm:py-2.5 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700">Save Status</button>
        </form>
        <?php } ?>
    </div>
</div>

<?php if ($canEdit) { ?>
<!-- ADD / EDIT -->
<div id="mtForm" class="fixed inset-0 bg-slate-900/60 hidden items-end sm:items-center justify-center sm:p-4 z-50">
    <div class="bg-white w-full sm:max-w-3xl max-h-[94vh] flex flex-col rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-4 bg-slate-900 text-white flex justify-between items-center shrink-0"><h3 class="font-bold text-sm"><i class="fa-solid fa-calendar-plus text-emerald-400 mr-2"></i><span id="mtFormTitle">New Meeting</span></h3>
            <button type="button" onclick="mtModal('mtForm', false)" class="text-slate-400 hover:text-white p-1"><i class="fa-solid fa-xmark text-lg"></i></button></div>
        <form method="POST" action="" class="p-4 sm:p-6 space-y-4 overflow-y-auto" autocomplete="off">
            <?= $csrf ?><input type="hidden" name="do" value="save"><input type="hidden" name="id" id="f_id" value="0">
            <div><label class="<?= $lbl ?>">Meeting Title * <small class="normal-case">/ বিষয়</small></label><input type="text" name="title" id="f_title" required class="<?= $inp ?>" placeholder="e.g. Monthly Executive Committee Meeting"></div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div class="col-span-2 sm:col-span-1"><label class="<?= $lbl ?>">Date *</label><input type="date" name="meeting_date" id="f_meeting_date" required class="<?= $inp ?>"></div>
                <div><label class="<?= $lbl ?>">Start Time</label><input type="time" name="start_time" id="f_start_time" class="<?= $inp ?>"></div>
                <div><label class="<?= $lbl ?>">End Time</label><input type="time" name="end_time" id="f_end_time" class="<?= $inp ?>"></div>
                <div><label class="<?= $lbl ?>">Status</label><select name="status" id="f_status" class="<?= $inp ?>"><?php foreach ($STATUS as $k => $_) { echo '<option>' . $k . '</option>'; } ?></select></div>
            </div>
            <div>
                <label class="<?= $lbl ?>">Meeting Type *</label>
                <div class="grid grid-cols-3 gap-2">
                    <?php foreach ($MODE as $k => $ic) { ?><label class="flex items-center justify-center gap-1.5 p-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 cursor-pointer has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 has-[:checked]:text-emerald-700">
                        <input type="radio" name="mtype" value="<?= $k ?>" class="sr-only mtype" <?= $k === 'Offline' ? 'checked' : '' ?>><i class="fa-solid <?= $ic ?>"></i><?= $k ?></label><?php } ?>
                </div>
            </div>
            <div id="g_link"><label class="<?= $lbl ?>">Meeting Link <small class="normal-case">(Zoom / Google Meet...)</small></label><input type="url" name="link" id="f_link" class="<?= $inp ?>" placeholder="https://meet.google.com/..."></div>
            <div id="g_addr"><label class="<?= $lbl ?>">Address / Venue <small class="normal-case">/ ঠিকানা (map দেখাবে)</small></label><textarea name="address" id="f_address" rows="2" class="<?= $inp ?>" placeholder="Venue, road, area, city"></textarea></div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div><label class="<?= $lbl ?>">Category</label><input type="text" name="category" id="f_category" list="dl_cat" value="General" class="<?= $inp ?>"></div>
                <div><label class="<?= $lbl ?>">Organizer / Host</label><input type="text" name="organizer" id="f_organizer" class="<?= $inp ?>"></div>
            </div>
            <div><label class="<?= $lbl ?>">Agenda <small class="normal-case">/ আলোচ্যসূচি</small></label><textarea name="agenda" id="f_agenda" rows="4" class="<?= $inp ?>" placeholder="1. ...&#10;2. ..."></textarea></div>
            <div><label class="<?= $lbl ?>">Minutes / Decisions <small class="normal-case">(after meeting)</small></label><textarea name="minutes" id="f_minutes" rows="2" class="<?= $inp ?>"></textarea></div>
            <div class="rounded-2xl border border-teal-200 bg-teal-50/40 p-4 space-y-2">
                <div class="flex items-center justify-between"><label class="<?= $lbl ?> mb-0">Who can view? / কারা দেখতে পাবে *</label><button type="button" id="f_allaud" class="text-[11px] font-bold text-teal-700 underline">Select all</button></div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <?php foreach ($TYPES as $ty) { ?><label class="flex items-center gap-2 p-2.5 rounded-xl bg-white border border-slate-200 text-xs font-semibold text-slate-700 cursor-pointer has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50">
                        <input type="checkbox" name="audience[]" value="<?= h($ty) ?>" class="aud rounded border-slate-300 w-4 h-4"> <?= h($ty) ?></label><?php } ?>
                </div>
                <p class="text-[11px] text-slate-500">Admin always sees every meeting. / অ্যাডমিন সব মিটিং দেখতে পায়।</p>
            </div>
            <div class="flex gap-2 pt-1">
                <button type="button" onclick="mtModal('mtForm', false)" class="flex-1 sm:flex-none px-5 py-3 sm:py-2.5 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600">Cancel</button>
                <button class="flex-1 sm:flex-none sm:ml-auto px-6 py-3 sm:py-2.5 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700">Save Meeting</button>
            </div>
        </form>
    </div>
</div>
<?php } ?>

<?php if ($isAdmin) { ?>
<!-- WHO CAN ADD -->
<div id="mtPerm" class="fixed inset-0 bg-slate-900/60 hidden items-end sm:items-center justify-center sm:p-4 z-50">
    <div class="bg-white w-full sm:max-w-lg max-h-[90vh] flex flex-col rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-4 bg-slate-900 text-white flex justify-between items-center shrink-0"><h3 class="font-bold text-sm"><i class="fa-solid fa-user-shield text-emerald-400 mr-2"></i>Who can add / edit meetings</h3>
            <button type="button" onclick="mtModal('mtPerm', false)" class="text-slate-400 hover:text-white p-1"><i class="fa-solid fa-xmark text-lg"></i></button></div>
        <form method="POST" action="" class="p-4 space-y-3 overflow-y-auto">
            <?= $csrf ?><input type="hidden" name="do" value="perm">
            <p class="text-xs text-slate-500">Selected users can add and edit meetings and update status. Delete is Admin only. Leave empty so only Admin can manage. / কাউকে না দিলে শুধু অ্যাডমিন পারবে।</p>
            <div class="flex gap-2"><input type="text" id="p_search" placeholder="Search user..." class="<?= $inp ?>"><button type="button" id="p_none" class="px-3 rounded-xl bg-rose-50 text-rose-600 text-[11px] font-bold shrink-0">Clear all</button></div>
            <div id="p_list" class="max-h-72 overflow-y-auto rounded-xl border border-slate-200 divide-y divide-slate-100">
                <?php foreach ($USERS as $u) { ?><label data-n="<?= h(mb_strtolower($u['member_name'] . ' ' . $u['user_type'])) ?>" class="flex items-center gap-3 p-2.5 cursor-pointer hover:bg-slate-50">
                    <input type="checkbox" name="editors[]" value="<?= (int)$u['id'] ?>" class="rounded border-slate-300 w-4 h-4" <?= in_array((int)$u['id'], $EDITORS) ? 'checked' : '' ?>>
                    <div class="min-w-0"><p class="text-xs font-semibold text-slate-800 truncate"><?= h($u['member_name']) ?></p><p class="text-[10px] text-slate-400"><?= h($u['user_type']) ?></p></div></label>
                <?php } if (!$USERS) { echo '<p class="p-4 text-center text-xs text-slate-400">No users found.</p>'; } ?>
            </div>
            <button class="w-full py-3 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700">Save Permissions</button>
        </form>
    </div>
</div>
<?php } ?>

<script>
(function () {
    var M = <?= json_encode($meetings, JSON_UNESCAPED_UNICODE) ?>, ST = <?= json_encode($STATUS) ?>, MODE = <?= json_encode($MODE) ?>, CAN = <?= $canEdit ? 'true' : 'false' ?>;
    var $ = function (id) { return document.getElementById(id); };
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
    function find(id) { return M.filter(function (x) { return +x.id === +id; })[0]; }
    window.mtModal = function (id, on) { var m = $(id); if (!m) { return; } m.classList.toggle('hidden', !on); m.classList.toggle('flex', on); document.body.style.overflow = on ? 'hidden' : ''; };

    function gcal(m) {
        var d = m.meeting_date.replace(/-/g, ''), t = function (x, def) { return (x || def).slice(0, 5).replace(':', '') + '00'; };
        return 'https://calendar.google.com/calendar/render?action=TEMPLATE&text=' + encodeURIComponent(m.title) + '&dates=' + d + 'T' + t(m.start_time, '10:00') + '/' + d + 'T' + t(m.end_time || m.start_time, '11:00') +
            '&details=' + encodeURIComponent((m.agenda || '') + (m.link ? '\n' + m.link : '')) + '&location=' + encodeURIComponent(m.address || m.link || '');
    }
    window.mtView = function (id) {
        var m = find(id), cell = function (ic, l, v) { return '<div class="p-3 rounded-xl bg-slate-50 border border-slate-100"><p class="text-[10px] font-bold uppercase text-slate-400"><i class="fa-solid ' + ic + ' mr-1"></i>' + l + '</p><p class="text-xs font-semibold text-slate-800 mt-0.5 break-words">' + (v || '—') + '</p></div>'; };
        var place = '';
        if (m.link && m.mtype !== 'Offline') { place += '<a href="' + esc(m.link) + '" target="_blank" rel="noopener" class="flex items-center justify-center gap-2 py-3 rounded-xl bg-sky-600 text-white text-xs font-bold hover:bg-sky-700"><i class="fa-solid fa-video"></i> Join Online Meeting</a>'; }
        if (m.address && m.mtype !== 'Online') {
            place += '<div class="rounded-xl overflow-hidden border border-slate-200"><div class="p-3 bg-white text-xs"><i class="fa-solid fa-location-dot text-rose-500 mr-1.5"></i><b>' + esc(m.address) + '</b></div>' +
                '<iframe loading="lazy" class="w-full h-56 border-0" src="https://maps.google.com/maps?q=' + encodeURIComponent(m.address) + '&output=embed"></iframe>' +
                '<a href="https://www.google.com/maps/search/?api=1&query=' + encodeURIComponent(m.address) + '" target="_blank" rel="noopener" class="block p-2.5 text-center bg-slate-50 text-[11px] font-bold text-emerald-700 hover:bg-slate-100">Open in Google Maps ↗</a></div>';
        }
        $('mtBody').innerHTML =
            '<div class="p-5 sm:p-6 pr-14 text-white bg-gradient-to-br from-emerald-700 via-teal-700 to-slate-800"><div class="flex flex-wrap gap-1.5 mb-3"><span class="px-2.5 py-1 rounded-full text-[10px] font-bold ' + ST[m.status] + '">' + esc(m.status) + '</span>' +
            '<span class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-white/20"><i class="fa-solid ' + MODE[m.mtype] + ' mr-1"></i>' + esc(m.mtype) + '</span><span class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-white/20">' + esc(m.category) + '</span></div>' +
            '<h3 class="text-lg sm:text-xl font-bold leading-snug">' + esc(m.title) + '</h3><p class="text-[11px] text-white/70 mt-1">' + esc(m.meeting_no) + '</p></div>' +
            '<div class="p-4 sm:p-6 space-y-4"><div class="grid grid-cols-2 gap-2">' + cell('fa-calendar', 'Date', esc(m.date_label)) + cell('fa-clock', 'Time', esc(m.time_label)) + cell('fa-user-tie', 'Organizer', esc(m.organizer)) + cell('fa-pen', 'Added by', esc(m.author)) + '</div>' +
            place +
            (m.agenda ? '<div><h4 class="text-xs font-bold text-slate-800 mb-1.5"><i class="fa-solid fa-list-check text-emerald-600 mr-1.5"></i>Agenda</h4><p class="text-xs text-slate-600 leading-relaxed whitespace-pre-line">' + esc(m.agenda) + '</p></div>' : '') +
            (m.minutes ? '<div class="p-3 rounded-xl bg-emerald-50 border border-emerald-100"><h4 class="text-xs font-bold text-emerald-800 mb-1.5"><i class="fa-solid fa-clipboard-check mr-1.5"></i>Minutes / Decisions</h4><p class="text-xs text-slate-700 leading-relaxed whitespace-pre-line">' + esc(m.minutes) + '</p></div>' : '') +
            (CAN ? '<div class="flex flex-wrap gap-1">' + m.audience_list.map(function (a) { return '<span class="px-2 py-1 rounded bg-teal-50 text-teal-700 text-[10px] font-bold">' + esc(a) + '</span>'; }).join('') + '</div>' : '') +
            '<a href="' + gcal(m) + '" target="_blank" rel="noopener" class="block text-center py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 hover:bg-slate-50"><i class="fa-regular fa-calendar-plus mr-1"></i>Add to Google Calendar</a></div>';
        if (CAN) { $('s_id').value = m.id; $('s_status').value = m.status; $('s_minutes').value = m.minutes || ''; }
        mtModal('mtView', true);
    };
    if (!CAN) { ['mtView'].forEach(function (id) { $(id).addEventListener('mousedown', function (e) { if (e.target === this) { mtModal(id, false); } }); }); return; }

    /* ---------- Form ---------- */
    function toggleType() {
        var t = document.querySelector('.mtype:checked').value;
        $('g_link').style.display = t === 'Offline' ? 'none' : ''; $('g_addr').style.display = t === 'Online' ? 'none' : '';
    }
    document.querySelectorAll('.mtype').forEach(function (r) { r.addEventListener('change', toggleType); });
    window.mtForm = function (id) {
        var m = id ? find(id) : null, F = ['title', 'meeting_date', 'start_time', 'end_time', 'status', 'link', 'address', 'category', 'organizer', 'agenda', 'minutes'];
        $('f_id').value = m ? m.id : 0; $('mtFormTitle').textContent = m ? 'Edit Meeting' : 'New Meeting';
        F.forEach(function (k) { var v = m ? (m[k] == null ? '' : m[k]) : (k === 'category' ? 'General' : (k === 'status' ? 'Scheduled' : '')); if (/time$/.test(k)) { v = String(v).slice(0, 5); } $('f_' + k).value = v; });
        document.querySelector('.mtype[value=' + (m ? m.mtype : 'Offline') + ']').checked = true; toggleType();
        document.querySelectorAll('.aud').forEach(function (c) { c.checked = m ? m.audience_list.indexOf(c.value) !== -1 : false; });
        mtModal('mtForm', true);
    };
    $('f_allaud').addEventListener('click', function () { document.querySelectorAll('.aud').forEach(function (c) { c.checked = true; }); });
    if ($('p_search')) {
        $('p_search').addEventListener('input', function () { var q = this.value.trim().toLowerCase(); document.querySelectorAll('#p_list label').forEach(function (l) { l.style.display = l.dataset.n.indexOf(q) === -1 ? 'none' : ''; }); });
        $('p_none').addEventListener('click', function () { document.querySelectorAll('#p_list input').forEach(function (c) { c.checked = false; }); });
    }
    ['mtView', 'mtForm', 'mtPerm'].forEach(function (id) { var m = $(id); if (m) { m.addEventListener('mousedown', function (e) { if (e.target === this) { mtModal(id, false); } }); } });
})();
</script>