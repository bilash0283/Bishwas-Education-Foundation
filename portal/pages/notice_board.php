<?php


if (session_status() === PHP_SESSION_NONE) { @session_start(); }
if (!function_exists('h')) { function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
mysqli_set_charset($db, 'utf8mb4');

$TYPES = ['Admin', 'General Member', 'Associate Member', 'Life Member', 'Volunteer Member'];
$org = ['name' => $site_title ?? '', 'address' => $office_address ?? '', 'phone' => $phone_number ?? '', 'email' => $email_address ?? '',
        'logo' => '../public/assets/' . ($favicon_icon ?? ''),
        'tagline' => 'নারী, পুরুষ, শিশু ও প্রতিবন্ধীদের অধিকার আদায় ও কল্যাণে কাজ করাই আমাদের উদ্দেশ্য ও লক্ষ্য।'];
$up_dir = 'uploads/notices/';

/* ---------- Role (database theke verify) ---------- */
$me = (int)($_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['uid'] ?? $_SESSION['member_id'] ?? 0);
$utype = $_SESSION['user_type'] ?? '';
if ($me > 0) { $ur = mysqli_fetch_row(mysqli_query($db, "SELECT user_type FROM users WHERE id = " . $me . " LIMIT 1")); if ($ur) { $utype = $ur[0]; } }
$isAdmin = ($utype === 'Admin');

function nb_rows($db, $sql, $t = '', $p = []) {
    $st = mysqli_prepare($db, $sql);
    if (!$st) { return []; }
    if ($t !== '') { mysqli_stmt_bind_param($st, $t, ...$p); }
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st); $o = [];
    while ($res && $r = mysqli_fetch_assoc($res)) { $o[] = $r; }
    mysqli_stmt_close($st);
    return $o;
}
function nb_run($db, $sql, $t, $p) { $st = mysqli_prepare($db, $sql); mysqli_stmt_bind_param($st, $t, ...$p); $ok = mysqli_stmt_execute($st); $n = mysqli_stmt_affected_rows($st); mysqli_stmt_close($st); return $ok ? $n : -1; }

if (!in_array($utype, $TYPES)) {
    echo '<section class="page-content max-w-xl mx-auto"><div class="p-6 bg-white rounded-2xl ring-1 ring-slate-200 text-center space-y-2"><i class="fa-solid fa-lock text-3xl text-slate-300"></i>
          <h3 class="font-bold text-slate-800">Access Restricted</h3><p class="text-xs text-slate-500">Please login to view notices. / নোটিশ দেখতে লগইন করুন।</p></div></section>';
    return;
}
$dbReady = true;
try { foreach (['notices', 'notice_editors'] as $tb) { if (!mysqli_query($db, "SELECT 1 FROM $tb LIMIT 1")) { $dbReady = false; } } } catch (Throwable $e) { $dbReady = false; }
if (!$dbReady) {
    echo '<section class="page-content max-w-xl mx-auto"><div class="p-6 bg-amber-50 border border-amber-200 rounded-2xl text-center space-y-2"><i class="fa-solid fa-database text-3xl text-amber-400"></i>
          <h3 class="font-bold text-slate-800">Database tables not found</h3><p class="text-xs text-slate-600">Please import <b>notices.sql</b> in phpMyAdmin first. / আগে notices.sql ইম্পোর্ট করুন।</p></div></section>';
    return;
}
$isEditor = !$isAdmin && $me > 0 && nb_rows($db, "SELECT 1 x FROM notice_editors WHERE user_id = ?", 'i', [$me]);
$canAdd = $isAdmin || $isEditor;
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
$page = $_GET['page'] ?? 'notice_board';
$base = 'index.php?page=' . urlencode($page);

function nb_upload($f, $dir, &$err) {
    if (!is_array($f) || $f['error'] === UPLOAD_ERR_NO_FILE) { return ''; }
    if ($f['error'] !== UPLOAD_ERR_OK) { $err = 'Attachment upload failed.'; return false; }
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    $mime = finfo_file($fi = finfo_open(FILEINFO_MIME_TYPE), $f['tmp_name']); finfo_close($fi);
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'pdf']) || !in_array($mime, ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'])) { $err = 'Attachment must be JPG, PNG, WEBP or PDF.'; return false; }
    if ($f['size'] > 5 * 1024 * 1024) { $err = 'Attachment must be under 5MB.'; return false; }
    if (!is_dir($dir)) { mkdir($dir, 0755, true); }
    $name = 'ntc_' . time() . '_' . random_int(1000, 9999) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . $name)) { $err = 'Could not save file (check folder permission).'; return false; }
    return $name;
}
function nb_unlink($dir, $f) { if ($f !== '' && is_file($dir . basename($f))) { @unlink($dir . basename($f)); } }

/* ---------- ACTIONS ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
    $do = $_POST['do'] ?? ''; $ok = true; $msg = ''; $id = (int)($_POST['id'] ?? 0);
    $back = (strpos($_POST['back'] ?? '', $base) === 0) ? $_POST['back'] : $base;

    if ($isAdmin && $do === 'perm') {                       // editor permission (admin only)
        nb_run($db, "DELETE FROM notice_editors WHERE id > ?", 'i', [0]);
        foreach (array_unique(array_filter(array_map('intval', (array)($_POST['editors'] ?? [])))) as $u) {
            nb_run($db, "INSERT IGNORE INTO notice_editors (user_id, granted_by) SELECT id, ? FROM users WHERE id = ?", 'ii', [$me, $u]);
        }
        $msg = 'Editor permissions saved.';
    } elseif ($isAdmin && $do === 'delete') {
        $o = nb_rows($db, "SELECT attachment FROM notices WHERE id = ?", 'i', [$id]);
        if ($o) { nb_run($db, "DELETE FROM notices WHERE id = ?", 'i', [$id]); nb_unlink($up_dir, $o[0]['attachment']); $msg = 'Notice deleted.'; }
    } elseif ($canAdd && $do === 'save') {
        $old = $id ? nb_rows($db, "SELECT attachment, created_by FROM notices WHERE id = ?", 'i', [$id]) : [];
        $title = trim($_POST['title'] ?? ''); $body = trim($_POST['body'] ?? '');
        $aud = array_values(array_intersect($TYPES, (array)($_POST['audience'] ?? [])));
        if ($id && (!$old || (!$isAdmin && (int)$old[0]['created_by'] !== $me))) { $ok = false; $msg = 'You cannot edit this notice.'; }
        elseif ($title === '' || $body === '') { $ok = false; $msg = 'Subject and notice body are required.'; }
        elseif (!$aud) { $ok = false; $msg = 'Select at least one audience (who can view).'; }
        else {
            $err = ''; $new = nb_upload($_FILES['attachment'] ?? null, $up_dir, $err);
            if ($new === false) { $ok = false; $msg = $err; }
            else {
                $att = $new !== '' ? $new : (!empty($_POST['remove_att']) ? '' : ($old[0]['attachment'] ?? ''));
                $prio = in_array($_POST['priority'] ?? '', ['Normal', 'Important', 'Urgent']) ? $_POST['priority'] : 'Normal';
                $status = ($_POST['status'] ?? '') === 'Draft' ? 'Draft' : 'Published';
                $pin = (!empty($_POST['is_pinned']) && $isAdmin) ? 1 : (int)($id ? (nb_rows($db, "SELECT is_pinned FROM notices WHERE id = ?", 'i', [$id])[0]['is_pinned'] ?? 0) : 0);
                $pd = !empty($_POST['publish_date']) ? $_POST['publish_date'] : date('Y-m-d'); $ed = !empty($_POST['expire_date']) ? $_POST['expire_date'] : null;
                $f = [$title, trim($_POST['recipient'] ?? ''), $body, trim($_POST['category'] ?? '') ?: 'General', $prio, implode(',', $aud),
                      trim($_POST['signatory_name'] ?? ''), trim($_POST['signatory_title'] ?? ''), trim($_POST['contact'] ?? ''), $att, $pin, $status, $pd, $ed];
                if ($id) {
                    nb_run($db, "UPDATE notices SET title=?, recipient=?, body=?, category=?, priority=?, audience=?, signatory_name=?, signatory_title=?, contact=?, attachment=?, is_pinned=?, status=?, publish_date=?, expire_date=?, updated_by=? WHERE id=?",
                        'ssssssssssisssii', array_merge($f, [$me ?: null, $id]));
                    if ($old && $att !== $old[0]['attachment']) { nb_unlink($up_dir, $old[0]['attachment']); }
                    $msg = 'Notice updated.';
                } else {
                    nb_run($db, "INSERT INTO notices (title, recipient, body, category, priority, audience, signatory_name, signatory_title, contact, attachment, is_pinned, status, publish_date, expire_date, created_by) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)",
                        'ssssssssssisssi', array_merge($f, [$me ?: null]));
                    $nid = mysqli_insert_id($db);
                    nb_run($db, "UPDATE notices SET ref_no = CONCAT('NTC-', YEAR(publish_date), '-', LPAD(id, 5, '0')) WHERE id = ?", 'i', [$nid]);
                    $msg = 'Notice published.';
                }
            }
        }
    }
    $_SESSION['nb_flash'] = [$ok, $msg];
    header('Location: ' . $back); exit;
}
$flash = $_SESSION['nb_flash'] ?? null; unset($_SESSION['nb_flash']);

/* ---------- LIST (role onujayi) ---------- */
$pubCond = "n.status = 'Published' AND n.publish_date <= CURDATE() AND (n.expire_date IS NULL OR n.expire_date >= CURDATE()) AND FIND_IN_SET(?, n.audience) > 0";
$scope = 'WHERE 1=1'; $st_ = ''; $sp = [];
if (!$isAdmin) {
    if ($isEditor) { $scope .= " AND (n.created_by = ? OR ($pubCond))"; $st_ = 'is'; $sp = [$me, $utype]; }
    else { $scope .= " AND $pubCond"; $st_ = 's'; $sp = [$utype]; }
}
$q = trim($_GET['q'] ?? ''); $fc = trim($_GET['cat'] ?? ''); $fp = in_array($_GET['prio'] ?? '', ['Normal', 'Important', 'Urgent']) ? $_GET['prio'] : '';
$fs = ($canAdd && in_array($_GET['status'] ?? '', ['Published', 'Draft'])) ? $_GET['status'] : '';
$pg = max(1, (int)($_GET['pg'] ?? 1));
function nb_url($o = []) { global $base, $q, $fc, $fp, $fs; return $base . '&' . http_build_query(array_merge(['q' => $q, 'cat' => $fc, 'prio' => $fp, 'status' => $fs], $o)); }

$w = $scope; $t = $st_; $p = $sp;
if ($q !== '')  { $w .= " AND (n.title LIKE ? OR n.body LIKE ? OR n.ref_no LIKE ?)"; $l = '%' . addcslashes($q, '%_\\') . '%'; $t .= 'sss'; array_push($p, $l, $l, $l); }
if ($fc !== '') { $w .= " AND n.category = ?"; $t .= 's'; $p[] = $fc; }
if ($fp !== '') { $w .= " AND n.priority = ?"; $t .= 's'; $p[] = $fp; }
if ($fs !== '') { $w .= " AND n.status = ?"; $t .= 's'; $p[] = $fs; }
$total = (int)(nb_rows($db, "SELECT COUNT(*) c FROM notices n $w", $t, $p)[0]['c'] ?? 0);
$pages = max(1, (int)ceil($total / 9)); $pg = min($pg, $pages);
$notices = nb_rows($db, "SELECT n.*, u.member_name author FROM notices n LEFT JOIN users u ON u.id = n.created_by $w ORDER BY n.is_pinned DESC, n.publish_date DESC, n.id DESC LIMIT 9 OFFSET " . (($pg - 1) * 9), $t, $p);
$S = nb_rows($db, "SELECT COUNT(*) total, COALESCE(SUM(n.is_pinned),0) pinned, COALESCE(SUM(n.priority <> 'Normal'),0) imp, COALESCE(SUM(n.status = 'Draft'),0) drafts FROM notices n $scope", $st_, $sp)[0];
$cats = array_column(nb_rows($db, "SELECT DISTINCT n.category FROM notices n $scope ORDER BY n.category", $st_, $sp), 'category');
foreach ($notices as &$n) {
    $n['audience_list'] = array_filter(explode(',', $n['audience']));
    $n['att_url'] = ($n['attachment'] !== '' && is_file($up_dir . basename($n['attachment']))) ? $up_dir . $n['attachment'] : '';
    $n['can_edit'] = $isAdmin || ($isEditor && (int)$n['created_by'] === $me);
    $n['is_new'] = $n['status'] === 'Published' && strtotime($n['publish_date']) >= strtotime('-3 days');
    $n['excerpt'] = mb_strimwidth(preg_replace('/\s+/u', ' ', $n['body']), 0, 140, '…', 'UTF-8');
} unset($n);

$USERS = []; $EDITORS = [];
if ($isAdmin) {
    $USERS = nb_rows($db, "SELECT id, member_name, user_type FROM users WHERE user_type <> 'Admin' ORDER BY FIELD(user_type,'Volunteer Member','Life Member','Associate Member','General Member'), member_name");
    $EDITORS = array_map('intval', array_column(nb_rows($db, "SELECT user_id FROM notice_editors"), 'user_id'));
}
$PR = ['Normal' => ['bg-slate-100 text-slate-600', 'border-slate-300'], 'Important' => ['bg-amber-100 text-amber-700', 'border-amber-400'], 'Urgent' => ['bg-rose-100 text-rose-700', 'border-rose-500']];
$inp = 'w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10';
$lbl = 'block text-[11px] font-bold uppercase text-slate-500 mb-1';
$csrf = '<input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '"><input type="hidden" name="back" value="' . h(nb_url(['pg' => $pg])) . '">';
?>
<section class="page-content space-y-5 max-w-7xl mx-auto">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-800">Notice Board <small class="text-xs font-medium text-slate-400">/ নোটিশ বোর্ড</small></h2>
            <p class="text-xs text-slate-500 mt-0.5"><?= $isAdmin ? 'Admin — publish notices, choose who can view and who can post.' : ($isEditor ? 'You have permission to post notices.' : 'Official notices for ' . h($utype) . '.') ?></p>
        </div>
        <div class="grid <?= $isAdmin ? 'grid-cols-2' : 'grid-cols-1' ?> sm:flex gap-2">
            <?php if ($isAdmin) { ?><button type="button" onclick="nbModal('nbPerm', true)" class="px-4 py-3 sm:py-2.5 border border-slate-200 bg-white rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50"><i class="fa-solid fa-user-shield mr-1.5"></i>Who can post</button><?php } ?>
            <?php if ($canAdd) { ?><button type="button" onclick="nbForm()" class="px-4 py-3 sm:py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-emerald-600/20"><i class="fa-solid fa-plus mr-1.5"></i>New Notice</button><?php } ?>
        </div>
    </div>

    <?php if ($flash) { ?><div class="p-3 rounded-xl text-xs font-semibold border <?= $flash[0] ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-rose-50 border-rose-200 text-rose-700' ?>"><?= h($flash[1]) ?></div><?php } ?>

    <div class="grid grid-cols-2 <?= $canAdd ? 'lg:grid-cols-4' : 'lg:grid-cols-3' ?> gap-3">
        <?php foreach (array_filter([['Total Notices', (int)$S['total'], 'emerald', 'fa-bullhorn', 1], ['Pinned', (int)$S['pinned'], 'sky', 'fa-thumbtack', 1], ['Important / Urgent', (int)$S['imp'], 'rose', 'fa-triangle-exclamation', 1], ['Drafts', (int)$S['drafts'], 'amber', 'fa-pen-ruler', $canAdd]], function ($c) { return $c[4]; }) as $c) { ?>
        <div class="p-4 bg-white rounded-2xl ring-1 ring-slate-200/80 flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-<?= $c[2] ?>-50 text-<?= $c[2] ?>-600 flex items-center justify-center text-lg shrink-0"><i class="fa-solid <?= $c[3] ?>"></i></div>
            <div><p class="text-[10px] font-bold uppercase text-slate-400"><?= $c[0] ?></p><p class="text-xl font-bold text-slate-800"><?= $c[1] ?></p></div>
        </div>
        <?php } ?>
    </div>

    <form method="GET" action="index.php" class="bg-white rounded-2xl border border-slate-200/80 p-4 grid grid-cols-2 md:grid-cols-12 gap-2">
        <input type="hidden" name="page" value="<?= h($page) ?>">
        <input type="text" name="q" value="<?= h($q) ?>" placeholder="Search notice... / খুঁজুন" class="col-span-2 md:col-span-4 <?= $inp ?>">
        <select name="cat" onchange="this.form.submit()" class="md:col-span-2 <?= $inp ?>"><option value="">All Categories</option><?php foreach ($cats as $c) { echo '<option' . ($fc === $c ? ' selected' : '') . '>' . h($c) . '</option>'; } ?></select>
        <select name="prio" onchange="this.form.submit()" class="md:col-span-2 <?= $inp ?>"><option value="">All Priority</option><?php foreach ($PR as $k => $_) { echo '<option' . ($fp === $k ? ' selected' : '') . '>' . $k . '</option>'; } ?></select>
        <?php if ($canAdd) { ?><select name="status" onchange="this.form.submit()" class="md:col-span-2 <?= $inp ?>"><option value="">All Status</option><?php foreach (['Published', 'Draft'] as $k) { echo '<option' . ($fs === $k ? ' selected' : '') . '>' . $k . '</option>'; } ?></select><?php } ?>
        <button class="md:col-span-1 px-3 py-2.5 bg-slate-800 text-white rounded-xl text-xs font-semibold"><i class="fa-solid fa-magnifying-glass"></i></button>
        <a href="<?= h($base) ?>" class="md:col-span-1 px-3 py-2.5 text-center border border-rose-200 text-rose-600 bg-rose-50 rounded-xl text-xs font-semibold" title="Reset"><i class="fa-solid fa-rotate-left"></i></a>
    </form>

    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <?php foreach ($notices as $n) { $pr = $PR[$n['priority']]; ?>
        <div class="bg-white rounded-2xl ring-1 ring-slate-200/80 border-t-4 <?= $pr[1] ?> p-4 flex flex-col gap-3 hover:shadow-lg transition">
            <div class="flex flex-wrap items-center gap-1.5">
                <?php if ($n['is_pinned']) { ?><span class="px-2 py-1 rounded-full bg-sky-100 text-sky-700 text-[10px] font-bold"><i class="fa-solid fa-thumbtack"></i> Pinned</span><?php } ?>
                <?php if ($n['is_new']) { ?><span class="px-2 py-1 rounded-full bg-emerald-600 text-white text-[10px] font-bold">NEW</span><?php } ?>
                <span class="px-2 py-1 rounded-full text-[10px] font-bold <?= $pr[0] ?>"><?= h($n['priority']) ?></span>
                <span class="px-2 py-1 rounded-full bg-slate-100 text-slate-600 text-[10px] font-semibold"><?= h($n['category']) ?></span>
                <?php if ($n['status'] === 'Draft') { ?><span class="px-2 py-1 rounded-full bg-amber-100 text-amber-700 text-[10px] font-bold">Draft</span><?php } ?>
            </div>
            <div class="cursor-pointer" onclick="nbView(<?= (int)$n['id'] ?>)">
                <h3 class="font-bold text-sm text-slate-800 leading-snug line-clamp-2 hover:text-emerald-600"><?= h($n['title']) ?></h3>
                <p class="text-xs text-slate-500 mt-1.5 line-clamp-3"><?= h($n['excerpt']) ?></p>
            </div>
            <?php if ($canAdd) { ?><div class="flex flex-wrap gap-1"><?php foreach ($n['audience_list'] as $a) { echo '<span class="px-1.5 py-0.5 rounded bg-teal-50 text-teal-700 text-[9px] font-bold">' . h($a) . '</span>'; } ?></div><?php } ?>
            <div class="mt-auto flex items-center justify-between text-[11px] text-slate-400">
                <span><i class="fa-regular fa-calendar mr-1"></i><?= h(date('d M Y', strtotime($n['publish_date']))) ?><?= $n['att_url'] ? ' • <i class="fa-solid fa-paperclip"></i>' : '' ?></span>
                <span class="truncate ml-2"><?= h($n['ref_no']) ?></span>
            </div>
            <div class="grid <?= $n['can_edit'] ? ($isAdmin ? 'grid-cols-3' : 'grid-cols-2') : 'grid-cols-1' ?> gap-2">
                <button type="button" onclick="nbView(<?= (int)$n['id'] ?>)" class="py-2 rounded-xl bg-emerald-50 text-emerald-700 text-[11px] font-bold hover:bg-emerald-100"><i class="fa-solid fa-eye mr-1"></i>View</button>
                <?php if ($n['can_edit']) { ?><button type="button" onclick="nbForm(<?= (int)$n['id'] ?>)" class="py-2 rounded-xl bg-sky-50 text-sky-700 text-[11px] font-bold hover:bg-sky-100"><i class="fa-solid fa-pen mr-1"></i>Edit</button><?php } ?>
                <?php if ($isAdmin) { ?><form method="POST" action="" onsubmit="return confirm('Delete this notice? / নোটিশটি মুছে ফেলবেন?')"><?= $csrf ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= (int)$n['id'] ?>">
                    <button class="w-full py-2 rounded-xl bg-rose-50 text-rose-600 text-[11px] font-bold hover:bg-rose-100"><i class="fa-solid fa-trash mr-1"></i>Delete</button></form><?php } ?>
            </div>
        </div>
        <?php } if (!$notices) { echo '<p class="col-span-full py-14 text-center text-slate-400 text-sm"><i class="fa-solid fa-bullhorn text-4xl mb-3 block"></i>No notices found. / কোনো নোটিশ নেই।</p>'; } ?>
    </div>

    <?php if ($pages > 1) { ?>
    <div class="flex items-center justify-between"><span class="text-xs text-slate-500">Page <?= $pg ?> / <?= $pages ?></span>
        <div class="flex gap-2">
            <?php if ($pg > 1) { ?><a href="<?= h(nb_url(['pg' => $pg - 1])) ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-xs font-semibold">‹ Prev</a><?php } ?>
            <?php if ($pg < $pages) { ?><a href="<?= h(nb_url(['pg' => $pg + 1])) ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-xs font-semibold">Next ›</a><?php } ?>
        </div></div>
    <?php } ?>
</section>

<datalist id="dl_cat"><option>General</option><option>Event</option><option>Meeting</option><option>Holiday</option><option>Financial</option><option>Training</option><option>Competition</option></datalist>

<!-- READ (letterhead format) -->
<div id="nbView" class="fixed inset-0 bg-slate-900/60 hidden items-end sm:items-center justify-center sm:p-4 z-50">
    <div class="bg-white w-full sm:max-w-3xl max-h-[94vh] flex flex-col rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-4 bg-slate-900 text-white flex justify-between items-center shrink-0"><h3 class="font-bold text-sm"><i class="fa-solid fa-bullhorn text-emerald-400 mr-2"></i>Notice</h3>
            <button type="button" onclick="nbModal('nbView', false)" class="text-slate-400 hover:text-white p-1"><i class="fa-solid fa-xmark text-lg"></i></button></div>
        <div class="overflow-y-auto bg-slate-100 p-3 sm:p-5 space-y-3"><div id="nbBox" class="mx-auto"></div><div id="nbAtt"></div></div>
        <div class="shrink-0 p-3 bg-white border-t border-slate-200 grid grid-cols-3 gap-2 sm:flex sm:justify-end">
            <button type="button" onclick="nbDownload('png')" class="px-4 py-2.5 rounded-xl bg-emerald-600 text-white text-xs font-semibold"><i class="fa-solid fa-image mr-1"></i>PNG</button>
            <button type="button" onclick="nbDownload('pdf')" class="px-4 py-2.5 rounded-xl bg-rose-600 text-white text-xs font-semibold"><i class="fa-solid fa-file-pdf mr-1"></i>PDF</button>
            <button type="button" onclick="nbModal('nbView', false)" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600">Close</button>
        </div>
    </div>
</div>

<?php if ($canAdd) { ?>
<!-- ADD / EDIT -->
<div id="nbForm" class="fixed inset-0 bg-slate-900/60 hidden items-end sm:items-center justify-center sm:p-4 z-50">
    <div class="bg-white w-full sm:max-w-3xl max-h-[94vh] flex flex-col rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-4 bg-slate-900 text-white flex justify-between items-center shrink-0"><h3 class="font-bold text-sm"><i class="fa-solid fa-pen-ruler text-emerald-400 mr-2"></i><span id="nbFormTitle">New Notice</span></h3>
            <button type="button" onclick="nbModal('nbForm', false)" class="text-slate-400 hover:text-white p-1"><i class="fa-solid fa-xmark text-lg"></i></button></div>
        <form method="POST" action="" enctype="multipart/form-data" class="p-4 sm:p-6 space-y-4 overflow-y-auto" autocomplete="off">
            <?= $csrf ?><input type="hidden" name="do" value="save"><input type="hidden" name="id" id="f_id" value="0">
            <div><label class="<?= $lbl ?>">Subject / বিষয় *</label><input type="text" name="title" id="f_title" required class="<?= $inp ?>" placeholder="চিত্রাংকন প্রতিযোগিতায় অংশগ্রহন প্রসঙ্গে"></div>
            <div><label class="<?= $lbl ?>">To / বরাবর <small class="normal-case">(optional)</small></label><textarea name="recipient" id="f_recipient" rows="2" class="<?= $inp ?>" placeholder="প্রধান শিক্ষক, ..."></textarea></div>
            <div><label class="<?= $lbl ?>">Notice Body / বিবরণ *</label><textarea name="body" id="f_body" rows="8" required class="<?= $inp ?>" placeholder="জনাব, ..."></textarea></div>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3">
                <div><label class="<?= $lbl ?>">Category</label><input type="text" name="category" id="f_category" list="dl_cat" value="General" class="<?= $inp ?>"></div>
                <div><label class="<?= $lbl ?>">Priority</label><select name="priority" id="f_priority" class="<?= $inp ?>"><option>Normal</option><option>Important</option><option>Urgent</option></select></div>
                <div><label class="<?= $lbl ?>">Publish Date</label><input type="date" name="publish_date" id="f_publish_date" class="<?= $inp ?>"></div>
                <div><label class="<?= $lbl ?>">Expire Date</label><input type="date" name="expire_date" id="f_expire_date" class="<?= $inp ?>"></div>
            </div>
            <div class="rounded-2xl border border-teal-200 bg-teal-50/40 p-4 space-y-2">
                <div class="flex items-center justify-between"><label class="<?= $lbl ?> mb-0">Who can view? / কারা দেখতে পাবে *</label><button type="button" id="f_allaud" class="text-[11px] font-bold text-teal-700 underline">Select all</button></div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <?php foreach ($TYPES as $ty) { ?>
                    <label class="flex items-center gap-2 p-2.5 rounded-xl bg-white border border-slate-200 text-xs font-semibold text-slate-700 cursor-pointer has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50">
                        <input type="checkbox" name="audience[]" value="<?= h($ty) ?>" class="aud rounded border-slate-300 w-4 h-4"> <?= h($ty) ?></label>
                    <?php } ?>
                </div>
                <p class="text-[11px] text-slate-500">Admin always sees every notice. / অ্যাডমিন সব নোটিশ দেখতে পায়।</p>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div><label class="<?= $lbl ?>">Signatory Name / নিবেদক</label><input type="text" name="signatory_name" id="f_signatory_name" class="<?= $inp ?>" placeholder="আহমদ উল্লাহ"></div>
                <div><label class="<?= $lbl ?>">Designation / পদবী</label><input type="text" name="signatory_title" id="f_signatory_title" class="<?= $inp ?>" placeholder="সাধারণ সম্পাদক"></div>
                <div><label class="<?= $lbl ?>">Contact / যোগাযোগ</label><input type="text" name="contact" id="f_contact" class="<?= $inp ?>"></div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div><label class="<?= $lbl ?>">Attachment (JPG, PNG, PDF - max 5MB)</label><input type="file" name="attachment" accept=".jpg,.jpeg,.png,.webp,.pdf" class="text-xs w-full">
                    <label id="f_rm" class="hidden mt-1 items-center gap-1.5 text-[11px] text-rose-600 font-semibold cursor-pointer"><input type="checkbox" name="remove_att" value="1"> Remove current attachment</label></div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="<?= $lbl ?>">Status</label><select name="status" id="f_status" class="<?= $inp ?>"><option>Published</option><option>Draft</option></select></div>
                    <?php if ($isAdmin) { ?><label class="flex items-center gap-2 mt-5 text-xs font-bold text-sky-700 cursor-pointer"><input type="checkbox" name="is_pinned" id="f_pin" value="1" class="rounded w-4 h-4"> <i class="fa-solid fa-thumbtack"></i> Pin on top</label><?php } ?>
                </div>
            </div>
            <div class="flex gap-2 pt-1">
                <button type="button" onclick="nbModal('nbForm', false)" class="flex-1 sm:flex-none px-5 py-3 sm:py-2.5 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600">Cancel</button>
                <button class="flex-1 sm:flex-none sm:ml-auto px-6 py-3 sm:py-2.5 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700">Save Notice</button>
            </div>
        </form>
    </div>
</div>
<?php } ?>

<?php if ($isAdmin) { ?>
<!-- WHO CAN POST -->
<div id="nbPerm" class="fixed inset-0 bg-slate-900/60 hidden items-end sm:items-center justify-center sm:p-4 z-50">
    <div class="bg-white w-full sm:max-w-lg max-h-[90vh] flex flex-col rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-4 bg-slate-900 text-white flex justify-between items-center shrink-0"><h3 class="font-bold text-sm"><i class="fa-solid fa-user-shield text-emerald-400 mr-2"></i>Who can add / edit notices</h3>
            <button type="button" onclick="nbModal('nbPerm', false)" class="text-slate-400 hover:text-white p-1"><i class="fa-solid fa-xmark text-lg"></i></button></div>
        <form method="POST" action="" class="p-4 space-y-3 overflow-y-auto">
            <?= $csrf ?><input type="hidden" name="do" value="perm">
            <p class="text-xs text-slate-500">Selected users can post notices and edit their own. Leave empty so only Admin can post. / কাউকে না দিলে শুধু অ্যাডমিন নোটিশ দিতে পারবে।</p>
            <div class="flex gap-2"><input type="text" id="p_search" placeholder="Search user..." class="<?= $inp ?>"><button type="button" id="p_none" class="px-3 rounded-xl bg-rose-50 text-rose-600 text-[11px] font-bold shrink-0">Clear all</button></div>
            <div id="p_list" class="max-h-72 overflow-y-auto rounded-xl border border-slate-200 divide-y divide-slate-100">
                <?php foreach ($USERS as $u) { ?>
                <label data-n="<?= h(mb_strtolower($u['member_name'] . ' ' . $u['user_type'])) ?>" class="flex items-center gap-3 p-2.5 cursor-pointer hover:bg-slate-50">
                    <input type="checkbox" name="editors[]" value="<?= (int)$u['id'] ?>" class="rounded border-slate-300 w-4 h-4" <?= in_array((int)$u['id'], $EDITORS) ? 'checked' : '' ?>>
                    <div class="min-w-0"><p class="text-xs font-semibold text-slate-800 truncate"><?= h($u['member_name']) ?></p><p class="text-[10px] text-slate-400"><?= h($u['user_type']) ?></p></div></label>
                <?php } if (!$USERS) { echo '<p class="p-4 text-center text-xs text-slate-400">No users found.</p>'; } ?>
            </div>
            <button class="w-full py-3 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700">Save Permissions</button>
        </form>
    </div>
</div>
<?php } ?>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
(function () {
    var N = <?= json_encode($notices, JSON_UNESCAPED_UNICODE) ?>, ORG = <?= json_encode($org, JSON_UNESCAPED_UNICODE) ?>, cur = null;
    var $ = function (id) { return document.getElementById(id); };
    var MON = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
    function abs(p) { try { return new URL(p, location.href).href; } catch (e) { return p; } }
    function fdate(s) { var p = String(s || '').slice(0, 10).split('-'); return p.length === 3 ? (+p[2]) + ' ' + MON[+p[1] - 1] + ', ' + p[0] : ''; }
    function find(id) { return N.filter(function (x) { return +x.id === +id; })[0]; }
    window.nbModal = function (id, on) { var m = $(id); if (!m) { return; } m.classList.toggle('hidden', !on); m.classList.toggle('flex', on); document.body.style.overflow = on ? 'hidden' : ''; };

    /* ---------- Letterhead (794px wide A4) ---------- */
    function letter(n) {
        var logo = esc(abs(ORG.logo)), sig = n.signatory_name || n.signatory_title || n.contact;
        return '<div style="width:794px;min-height:1000px;box-sizing:border-box;background:#fff;position:relative;padding:44px 56px 50px;font-family:\'Noto Sans Bengali\',\'SolaimanLipi\',Arial,sans-serif;color:#1e293b;overflow:hidden">' +
            '<img src="' + logo + '" style="position:absolute;top:340px;left:197px;width:400px;opacity:.06" onerror="this.style.display=\'none\'">' +
            '<div style="position:relative;display:flex;align-items:center;gap:16px;padding-bottom:12px;border-bottom:3px double #166534"><img src="' + logo + '" style="width:92px;height:92px;object-fit:contain" onerror="this.style.display=\'none\'">' +
            '<div><div style="font:bold 27px Georgia,serif;color:#166534;letter-spacing:1px;text-transform:uppercase;line-height:1.1">' + esc(ORG.name) + '</div>' +
            '<div style="font-size:12px;color:#334155;margin-top:5px">' + esc(ORG.tagline) + '</div>' +
            '<div style="font-size:11px;color:#475569;margin-top:3px">' + esc(ORG.address) + '</div><div style="font-size:11px;color:#475569">Tel: ' + esc(ORG.phone) + ' | E-mail: ' + esc(ORG.email) + '</div></div></div>' +
            '<div style="position:relative;display:flex;justify-content:space-between;font-size:13px;margin-top:22px;color:#475569"><span>Ref: ' + esc(n.ref_no) + '</span><span>Date: ' + esc(fdate(n.publish_date)) + '</span></div>' +
            (n.recipient ? '<div style="position:relative;margin-top:22px;font-size:15px;line-height:1.7"><div>বরাবর,</div><div style="white-space:pre-wrap;font-weight:600">' + esc(n.recipient) + '</div></div>' : '') +
            '<div style="position:relative;margin-top:24px;font-size:16px;font-weight:bold;color:#9f1239"><span style="text-decoration:underline">বিষয়ঃ ' + esc(n.title) + '</span></div>' +
            '<div style="position:relative;margin-top:22px;font-size:15px;line-height:2;white-space:pre-wrap">' + esc(n.body) + '</div>' +
            (sig ? '<div style="position:relative;margin-top:44px;font-size:14px;line-height:1.7"><div>নিবেদক</div><div style="width:210px;border-top:1.5px solid #334155;margin-top:46px"></div>' +
            (n.signatory_name ? '<div style="font-weight:bold;font-size:15px">' + esc(n.signatory_name) + '</div>' : '') + (n.signatory_title ? '<div>' + esc(n.signatory_title) + '</div>' : '') +
            '<div>' + esc(ORG.name) + '</div>' + (n.contact ? '<div>যোগাযোগঃ ' + esc(n.contact) + '</div>' : '') + '</div>' : '') + '</div>';
    }
    window.nbView = function (id) {
        var n = find(id); if (!n) { return; } cur = n;
        var box = $('nbBox'); nbModal('nbView', true);
        var sc = Math.min(1, (box.clientWidth - 2) / 794);
        box.innerHTML = '<div id="nbScaler" style="width:' + 794 * sc + 'px;margin:0 auto;overflow:hidden"><div style="transform:scale(' + sc + ');transform-origin:top left;width:794px">' + letter(n) + '</div></div>';
        var inner = box.querySelector('#nbScaler > div'); $('nbScaler').style.height = inner.offsetHeight * sc + 'px';
        var a = n.att_url; $('nbAtt').innerHTML = !a ? '' : (/\.pdf$/i.test(a)
            ? '<a href="' + esc(a) + '" target="_blank" class="flex items-center gap-2 p-3 bg-white rounded-xl border border-slate-200 text-xs font-semibold text-rose-600"><i class="fa-solid fa-file-pdf text-lg"></i> Open attached PDF</a>'
            : '<a href="' + esc(a) + '" target="_blank"><img src="' + esc(a) + '" class="max-h-80 mx-auto rounded-xl border border-slate-200"></a>');
    };
    window.nbDownload = function (fmt) {
        if (!cur || !window.html2canvas) { alert('Library load hoyni. Internet check korun.'); return; }
        var el = document.createElement('div'); el.style.cssText = 'position:fixed;left:-99999px;top:0;width:794px'; el.innerHTML = letter(cur); document.body.appendChild(el);
        var h = el.offsetHeight, name = (cur.ref_no || 'notice');
        html2canvas(el, { scale: 2, useCORS: true, backgroundColor: '#ffffff' }).then(function (cv) {
            document.body.removeChild(el);
            if (fmt === 'png') { var a = document.createElement('a'); a.href = cv.toDataURL('image/png'); a.download = name + '.png'; a.click(); }
            else { var pdf = new window.jspdf.jsPDF({ orientation: 'p', unit: 'px', format: [794, Math.max(h, 1123)] }); pdf.addImage(cv.toDataURL('image/jpeg', .95), 'JPEG', 0, 0, 794, h); pdf.save(name + '.pdf'); }
        });
    };

    /* ---------- Form ---------- */
    window.nbForm = function (id) {
        var n = id ? find(id) : null, F = ['title', 'recipient', 'body', 'category', 'priority', 'publish_date', 'expire_date', 'signatory_name', 'signatory_title', 'contact', 'status'];
        $('f_id').value = n ? n.id : 0; $('nbFormTitle').textContent = n ? 'Edit Notice' : 'New Notice';
        F.forEach(function (k) { $('f_' + k).value = n ? (n[k] == null ? '' : n[k]) : (k === 'category' ? 'General' : (k === 'priority' ? 'Normal' : (k === 'status' ? 'Published' : (k === 'publish_date' ? new Date().toISOString().slice(0, 10) : '')))); });
        document.querySelectorAll('.aud').forEach(function (c) { c.checked = n ? n.audience_list.indexOf(c.value) !== -1 : false; });
        if ($('f_pin')) { $('f_pin').checked = !!(n && +n.is_pinned); }
        var rm = $('f_rm'); rm.classList.toggle('hidden', !(n && n.att_url)); rm.classList.toggle('flex', !!(n && n.att_url));
        nbModal('nbForm', true);
    };
    if ($('f_allaud')) { $('f_allaud').addEventListener('click', function () { document.querySelectorAll('.aud').forEach(function (c) { c.checked = true; }); }); }
    if ($('p_search')) {
        $('p_search').addEventListener('input', function () { var q = this.value.trim().toLowerCase(); document.querySelectorAll('#p_list label').forEach(function (l) { l.style.display = l.dataset.n.indexOf(q) === -1 ? 'none' : ''; }); });
        $('p_none').addEventListener('click', function () { document.querySelectorAll('#p_list input').forEach(function (c) { c.checked = false; }); });
    }
    ['nbView', 'nbForm', 'nbPerm'].forEach(function (id) { var m = $(id); if (m) { m.addEventListener('mousedown', function (e) { if (e.target === this) { nbModal(id, false); } }); } });
})();
</script>