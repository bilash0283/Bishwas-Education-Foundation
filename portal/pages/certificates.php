<?php
/* ==========================================================
   certificates.php  ->  index.php?page=certificates
   1) Member ID Card (view + PNG/PDF download)
   2) Certificates: custom create, history, view, edit, delete, download
   Table 'certificates' nijei toiri hoye jabe (kono SQL import lagbe na).
   ========================================================== */
if (session_status() === PHP_SESSION_NONE) { @session_start(); }
if (!function_exists('h')) { function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
mysqli_set_charset($db, 'utf8mb4');

mysqli_query($db, "CREATE TABLE IF NOT EXISTS certificates (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  cert_no VARCHAR(30) NOT NULL DEFAULT '',
  user_id INT NULL,
  recipient_name VARCHAR(150) NOT NULL,
  title VARCHAR(200) NOT NULL,
  event_name VARCHAR(200) NOT NULL DEFAULT '',
  organization VARCHAR(200) NOT NULL DEFAULT '',
  supported_by VARCHAR(200) NOT NULL DEFAULT '',
  description TEXT NULL,
  theme VARCHAR(20) NOT NULL DEFAULT 'classic',
  issue_date DATE NOT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_user (user_id), KEY idx_event (event_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }

$base       = 'index.php?page=certificates';
$member_dir = 'public/uploads/members/';
$id_prefix  = 'BEF';                       // ID card number prefix
$org = [   // apnar layout theke ashe (ager page gulor moto)
    'name' => $site_title ?? '', 'address' => $office_address ?? '', 'phone' => $phone_number ?? '',
    'email' => $email_address ?? '', 'logo' => '../public/assets/' . ($favicon_icon ?? ''),
];
// Certificate theme: bg, border, inner border, org-name color, event color, title color
$themes = [
    'classic' => ['label' => 'Classic Yellow', 'bg' => '#fdf7b0', 'b1' => '#e11d74', 'b2' => '#16a34a', 'a1' => '#15803d', 'a2' => '#e11d48', 'a3' => '#1e3a8a'],
    'emerald' => ['label' => 'Emerald',        'bg' => '#f0fdf4', 'b1' => '#047857', 'b2' => '#d4af37', 'a1' => '#065f46', 'a2' => '#b45309', 'a3' => '#064e3b'],
    'royal'   => ['label' => 'Royal Blue',     'bg' => '#eff6ff', 'b1' => '#1e3a8a', 'b2' => '#d4af37', 'a1' => '#1e40af', 'a2' => '#b45309', 'a3' => '#1e293b'],
];

function cr_rows($db, $sql, $t = '', $p = []) {
    $st = mysqli_prepare($db, $sql);
    if (!$st) { return []; }
    if ($t !== '') { mysqli_stmt_bind_param($st, $t, ...$p); }
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st); $o = [];
    while ($res && $r = mysqli_fetch_assoc($res)) { $o[] = $r; }
    mysqli_stmt_close($st);
    return $o;
}
function cr_url($over = []) {
    global $tab, $q, $ev, $yr, $uid;
    return 'index.php?' . http_build_query(array_merge(['page' => 'certificates', 'tab' => $tab, 'q' => $q, 'ev' => $ev, 'yr' => $yr, 'uid' => $uid], $over));
}

/* ---------- SAVE / DELETE ---------- */
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
    $do = $_POST['do'] ?? '';
    if ($do === 'delete') {
        $st = mysqli_prepare($db, "DELETE FROM certificates WHERE id = ?");
        $i = (int)($_POST['id'] ?? 0); mysqli_stmt_bind_param($st, 'i', $i); mysqli_stmt_execute($st);
        header("Location: $base&tab=certs"); exit;
    }
    if ($do === 'save') {
        $id    = (int)($_POST['id'] ?? 0);
        $mid   = (int)($_POST['user_id'] ?? 0);
        $name  = trim($_POST['recipient_name'] ?? '');
        $title = trim($_POST['title'] ?? '');
        $event = trim($_POST['event_name'] ?? '');
        $orgn  = trim($_POST['organization'] ?? '');
        $supp  = trim($_POST['supported_by'] ?? '');
        $desc  = trim($_POST['description'] ?? '');
        $theme = isset($themes[$_POST['theme'] ?? '']) ? $_POST['theme'] : 'classic';
        $date  = !empty($_POST['issue_date']) ? date('Y-m-d', strtotime($_POST['issue_date'])) : date('Y-m-d');
        if ($mid > 0 && $name === '') {
            $m = cr_rows($db, "SELECT member_name FROM users WHERE id = ?", 'i', [$mid]);
            $name = $m[0]['member_name'] ?? '';
        }
        if ($name === '' || $title === '') {
            $error = 'Recipient name and certificate title are required. / প্রাপকের নাম ও সার্টিফিকেটের শিরোনাম দিন।';
        } else {
            $uidv = $mid > 0 ? $mid : null;
            if ($id > 0) {
                $st = mysqli_prepare($db, "UPDATE certificates SET user_id=?, recipient_name=?, title=?, event_name=?, organization=?, supported_by=?, description=?, theme=?, issue_date=? WHERE id=?");
                mysqli_stmt_bind_param($st, 'issssssssi', $uidv, $name, $title, $event, $orgn, $supp, $desc, $theme, $date, $id);
                mysqli_stmt_execute($st);
            } else {
                $st = mysqli_prepare($db, "INSERT INTO certificates (user_id, recipient_name, title, event_name, organization, supported_by, description, theme, issue_date) VALUES (?,?,?,?,?,?,?,?,?)");
                mysqli_stmt_bind_param($st, 'issssssss', $uidv, $name, $title, $event, $orgn, $supp, $desc, $theme, $date);
                mysqli_stmt_execute($st);
                $id = mysqli_insert_id($db);
                $st = mysqli_prepare($db, "UPDATE certificates SET cert_no = CONCAT('CERT-', YEAR(issue_date), '-', LPAD(id, 5, '0')) WHERE id = ?");
                mysqli_stmt_bind_param($st, 'i', $id); mysqli_stmt_execute($st);
            }
            header("Location: $base&tab=certs&view=$id"); exit;   // save er por certificate ti dekhabe
        }
    }
}

/* ---------- FILTERS ---------- */
$tab = (($_GET['tab'] ?? 'cards') === 'certs') ? 'certs' : 'cards';
$q   = trim($_GET['q'] ?? '');
$ev  = trim($_GET['ev'] ?? '');
$yr  = preg_match('/^\d{4}$/', $_GET['yr'] ?? '') ? $_GET['yr'] : '';
$uid = (int)($_GET['uid'] ?? 0) ?: '';
$pg  = max(1, (int)($_GET['pg'] ?? 1));
$like = '%' . addcslashes($q, '%_\\') . '%';

/* ---------- MEMBERS (ID card) ---------- */
$members = []; $mTotal = 0; $mPages = 1;
if ($tab === 'cards') {
    $w = $q !== '' ? "WHERE member_name LIKE ? OR mobile_no LIKE ? OR email LIKE ?" : '';
    $t = $q !== '' ? 'sss' : ''; $p = $q !== '' ? [$like, $like, $like] : [];
    $mTotal = (int)(cr_rows($db, "SELECT COUNT(*) c FROM users $w", $t, $p)[0]['c'] ?? 0);
    $mPages = max(1, (int)ceil($mTotal / 24)); $pg = min($pg, $mPages);
    $members = cr_rows($db, "SELECT * FROM users $w ORDER BY member_name LIMIT 24 OFFSET " . (($pg - 1) * 24), $t, $p);
    foreach ($members as &$m) {
        $m['photo_url'] = (!empty($m['photo']) && is_file($member_dir . basename($m['photo']))) ? $member_dir . $m['photo'] : '';
        $m['id_no'] = $id_prefix . '-' . str_pad($m['id'], 5, '0', STR_PAD_LEFT);
    }
    unset($m);
}
$certCount = [];
foreach (cr_rows($db, "SELECT user_id, COUNT(*) c FROM certificates WHERE user_id IS NOT NULL GROUP BY user_id") as $r) { $certCount[$r['user_id']] = (int)$r['c']; }

/* ---------- CERTIFICATES (history) ---------- */
$certs = []; $cTotal = 0; $cPages = 1;
$allMembers = cr_rows($db, "SELECT id, member_name FROM users ORDER BY member_name");
$evOptions  = array_column(cr_rows($db, "SELECT DISTINCT event_name FROM certificates WHERE event_name <> '' ORDER BY event_name"), 'event_name');
$evSuggest  = array_unique(array_merge($evOptions, array_column(cr_rows($db, "SELECT title FROM events ORDER BY id DESC LIMIT 100"), 'title')));
$orgSuggest = array_column(cr_rows($db, "SELECT DISTINCT organization FROM certificates WHERE organization <> ''"), 'organization');
$years      = array_column(cr_rows($db, "SELECT DISTINCT YEAR(issue_date) y FROM certificates ORDER BY y DESC"), 'y');
if ($tab === 'certs') {
    $w = 'WHERE 1=1'; $t = ''; $p = [];
    if ($q !== '')  { $w .= " AND (recipient_name LIKE ? OR title LIKE ? OR event_name LIKE ? OR organization LIKE ? OR cert_no LIKE ?)"; $t .= 'sssss'; array_push($p, $like, $like, $like, $like, $like); }
    if ($ev !== '') { $w .= " AND event_name = ?"; $t .= 's'; $p[] = $ev; }
    if ($yr !== '') { $w .= " AND YEAR(issue_date) = ?"; $t .= 's'; $p[] = $yr; }
    if ($uid)       { $w .= " AND user_id = ?"; $t .= 'i'; $p[] = $uid; }
    $cTotal = (int)(cr_rows($db, "SELECT COUNT(*) c FROM certificates $w", $t, $p)[0]['c'] ?? 0);
    $cPages = max(1, (int)ceil($cTotal / 12)); $pg = min($pg, $cPages);
    $certs = cr_rows($db, "SELECT * FROM certificates $w ORDER BY issue_date DESC, id DESC LIMIT 12 OFFSET " . (($pg - 1) * 12), $t, $p);
}
$viewId = (int)($_GET['view'] ?? 0);
if ($viewId && !in_array($viewId, array_column($certs, 'id'))) { $certs = array_merge($certs, cr_rows($db, "SELECT * FROM certificates WHERE id = ?", 'i', [$viewId])); }

$pages = $tab === 'cards' ? $mPages : $cPages;
$inp = 'w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10';
$lbl = 'block text-[11px] font-bold uppercase text-slate-500 mb-1';
?>
<section class="page-content space-y-5 max-w-7xl mx-auto">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-800">ID Cards & Certificates <small class="text-xs font-medium text-slate-400">/ আইডি কার্ড ও সার্টিফিকেট</small></h2>
            <p class="text-xs text-slate-500 mt-0.5">Generate member ID cards and manage certificate history.</p>
        </div>
        <?php if ($tab === 'certs') { ?>
        <button type="button" onclick="crForm()" class="px-4 py-3 sm:py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-emerald-600/20"><i class="fa-solid fa-plus mr-1.5"></i> New Certificate</button>
        <?php } ?>
    </div>

    <?php if ($error) { ?><div class="p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-lg"><?= h($error) ?></div><?php } ?>

    <!-- Tabs -->
    <div class="grid grid-cols-2 gap-1 p-1 bg-slate-100 rounded-xl sm:inline-grid sm:w-96">
        <a href="<?= h($base) ?>&tab=cards" class="py-2.5 text-center rounded-lg text-xs font-bold <?= $tab === 'cards' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500' ?>"><i class="fa-solid fa-id-card mr-1.5"></i>ID Cards</a>
        <a href="<?= h($base) ?>&tab=certs" class="py-2.5 text-center rounded-lg text-xs font-bold <?= $tab === 'certs' ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500' ?>"><i class="fa-solid fa-award mr-1.5"></i>Certificates</a>
    </div>

    <!-- Filter -->
    <form method="GET" action="index.php" class="bg-white rounded-2xl border border-slate-200/80 p-4 grid grid-cols-2 md:grid-cols-12 gap-2">
        <input type="hidden" name="page" value="certificates"><input type="hidden" name="tab" value="<?= $tab ?>">
        <?php if ($uid) { echo '<input type="hidden" name="uid" value="' . (int)$uid . '">'; } ?>
        <input type="text" name="q" value="<?= h($q) ?>" placeholder="Search name, phone, event, certificate no... / খুঁজুন" class="col-span-2 <?= $tab === 'certs' ? 'md:col-span-5' : 'md:col-span-9' ?> <?= $inp ?>">
        <?php if ($tab === 'certs') { ?>
        <select name="ev" onchange="this.form.submit()" class="col-span-2 md:col-span-3 <?= $inp ?>"><option value="">All Events / Programs</option>
            <?php foreach ($evOptions as $e) { echo '<option' . ($ev === $e ? ' selected' : '') . '>' . h($e) . '</option>'; } ?></select>
        <select name="yr" onchange="this.form.submit()" class="md:col-span-2 <?= $inp ?>"><option value="">All Years</option>
            <?php foreach ($years as $y) { echo '<option' . ($yr == $y ? ' selected' : '') . '>' . (int)$y . '</option>'; } ?></select>
        <?php } ?>
        <button class="md:col-span-1 px-3 py-2.5 bg-slate-800 text-white rounded-xl text-xs font-semibold"><i class="fa-solid fa-magnifying-glass"></i></button>
        <a href="<?= h($base . '&tab=' . $tab) ?>" class="md:col-span-1 px-3 py-2.5 text-center border border-rose-200 text-rose-600 bg-rose-50 rounded-xl text-xs font-semibold" title="Reset"><i class="fa-solid fa-rotate-left"></i></a>
    </form>
    <?php if ($uid) { ?><p class="text-xs text-sky-700 bg-sky-50 border border-sky-100 rounded-lg px-3 py-2">Showing certificates of one member only. <a class="font-bold underline" href="<?= h(cr_url(['uid' => ''])) ?>">Show all</a></p><?php } ?>

    <?php if ($tab === 'cards') { ?>
    <!-- ID CARD LIST -->
    <p class="text-xs text-slate-500"><?= $mTotal ?> members</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <?php foreach ($members as $m) { $n = $certCount[$m['id']] ?? 0; ?>
        <div class="bg-white rounded-2xl ring-1 ring-slate-200/80 p-4 flex items-center gap-3">
            <?php if ($m['photo_url']) { ?><img src="<?= h($m['photo_url']) ?>" class="w-14 h-14 rounded-full object-cover shrink-0"><?php } else { ?>
            <div class="w-14 h-14 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-lg shrink-0"><?= h(mb_strtoupper(mb_substr($m['member_name'], 0, 1))) ?></div><?php } ?>
            <div class="min-w-0 flex-1">
                <p class="font-bold text-sm text-slate-800 truncate"><?= h($m['member_name']) ?></p>
                <p class="text-[11px] text-slate-400"><?= h($m['id_no']) ?> • <?= h($m['user_type'] ?? '') ?></p>
                <div class="flex gap-1.5 mt-2">
                    <button type="button" onclick="crCard(<?= (int)$m['id'] ?>)" class="px-2.5 py-1.5 rounded-lg bg-emerald-50 text-emerald-700 text-[11px] font-bold hover:bg-emerald-100"><i class="fa-solid fa-id-card"></i> ID Card</button>
                    <a href="<?= h($base . '&tab=certs&uid=' . (int)$m['id']) ?>" class="px-2.5 py-1.5 rounded-lg bg-amber-50 text-amber-700 text-[11px] font-bold hover:bg-amber-100"><i class="fa-solid fa-award"></i> <?= $n ?></a>
                </div>
            </div>
        </div>
        <?php } if (!$members) { echo '<p class="col-span-full py-10 text-center text-slate-400 text-sm">No members found.</p>'; } ?>
    </div>

    <?php } else { ?>
    <!-- CERTIFICATE HISTORY -->
    <p class="text-xs text-slate-500"><?= $cTotal ?> certificates</p>
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
        <?php foreach ($certs as $c) { if (!in_array($c['id'], array_column(array_slice($certs, 0, 12), 'id')) && $c['id'] != $viewId) { continue; } ?>
        <div class="bg-white rounded-2xl ring-1 ring-slate-200/80 p-4 flex flex-col gap-3">
            <div class="flex items-start justify-between gap-2">
                <div class="min-w-0">
                    <p class="text-[10px] font-bold text-emerald-700"><?= h($c['cert_no']) ?></p>
                    <p class="font-bold text-sm text-slate-800 truncate"><?= h($c['recipient_name']) ?></p>
                </div>
                <span class="px-2 py-1 rounded-md bg-slate-100 text-slate-600 text-[10px] font-bold whitespace-nowrap"><?= h(date('d M Y', strtotime($c['issue_date']))) ?></span>
            </div>
            <div class="text-xs text-slate-600 space-y-1">
                <p class="font-semibold"><i class="fa-solid fa-award text-amber-500 w-4"></i> <?= h($c['title']) ?></p>
                <?php if ($c['event_name'] !== '') { ?><p><i class="fa-solid fa-calendar-days text-sky-500 w-4"></i> <?= h($c['event_name']) ?></p><?php } ?>
                <?php if ($c['organization'] !== '') { ?><p><i class="fa-solid fa-building text-slate-400 w-4"></i> <?= h($c['organization']) ?></p><?php } ?>
            </div>
            <div class="grid grid-cols-3 gap-2 mt-auto">
                <button type="button" onclick="crView(<?= (int)$c['id'] ?>)" class="py-2 rounded-xl bg-emerald-50 text-emerald-700 text-[11px] font-bold hover:bg-emerald-100"><i class="fa-solid fa-eye"></i> View</button>
                <button type="button" onclick="crForm(<?= (int)$c['id'] ?>)" class="py-2 rounded-xl bg-sky-50 text-sky-700 text-[11px] font-bold hover:bg-sky-100"><i class="fa-solid fa-pen"></i> Edit</button>
                <form method="POST" action="<?= h($base) ?>&tab=certs" onsubmit="return confirm('Delete this certificate? / মুছে ফেলবেন?')">
                    <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                    <button class="w-full py-2 rounded-xl bg-rose-50 text-rose-600 text-[11px] font-bold hover:bg-rose-100"><i class="fa-solid fa-trash"></i> Delete</button>
                </form>
            </div>
        </div>
        <?php } if (!$certs) { echo '<p class="col-span-full py-10 text-center text-slate-400 text-sm"><i class="fa-solid fa-award text-3xl mb-2 block"></i>No certificates yet. Click “New Certificate”.</p>'; } ?>
    </div>
    <?php } ?>

    <?php if ($pages > 1) { ?>
    <div class="flex items-center justify-between">
        <span class="text-xs text-slate-500">Page <?= $pg ?> / <?= $pages ?></span>
        <div class="flex gap-2">
            <?php if ($pg > 1) { ?><a href="<?= h(cr_url(['pg' => $pg - 1])) ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold bg-white">‹ Prev</a><?php } ?>
            <?php if ($pg < $pages) { ?><a href="<?= h(cr_url(['pg' => $pg + 1])) ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold bg-white">Next ›</a><?php } ?>
        </div>
    </div>
    <?php } ?>
</section>

<datalist id="dl_ev"><?php foreach ($evSuggest as $e) { echo '<option value="' . h($e) . '">'; } ?></datalist>
<datalist id="dl_org"><?php foreach ($orgSuggest as $e) { echo '<option value="' . h($e) . '">'; } ?></datalist>
<datalist id="dl_title"><option>Certificate of Participation</option><option>Certificate of Appreciation</option><option>Certificate of Achievement</option><option>Certificate of Completion</option><option>Volunteer Certificate</option><option>Certificate of Membership</option></datalist>

<!-- VIEW MODAL (ID card + certificate) -->
<div id="crView" class="fixed inset-0 bg-slate-900/60 hidden items-end sm:items-center justify-center sm:p-4 z-50">
    <div class="bg-white w-full sm:max-w-5xl max-h-[94vh] flex flex-col rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-4 bg-slate-900 text-white flex justify-between items-center shrink-0">
            <h3 class="font-bold text-sm" id="crViewTitle">Preview</h3>
            <button type="button" onclick="crClose('crView')" class="text-slate-400 hover:text-white p-1"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <div class="overflow-y-auto bg-slate-100 p-3 sm:p-6"><div id="crBox" class="mx-auto"></div></div>
        <div class="shrink-0 p-3 bg-white border-t border-slate-200 grid grid-cols-3 gap-2 sm:flex sm:justify-end">
            <button type="button" onclick="crDownload('png')" class="px-4 py-2.5 rounded-xl bg-emerald-600 text-white text-xs font-semibold"><i class="fa-solid fa-image mr-1"></i>PNG</button>
            <button type="button" onclick="crDownload('pdf')" class="px-4 py-2.5 rounded-xl bg-rose-600 text-white text-xs font-semibold"><i class="fa-solid fa-file-pdf mr-1"></i>PDF</button>
            <button type="button" onclick="crClose('crView')" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600">Close</button>
        </div>
    </div>
</div>

<!-- CREATE / EDIT MODAL -->
<div id="crFormModal" class="fixed inset-0 bg-slate-900/60 hidden items-end sm:items-center justify-center sm:p-4 z-50">
    <div class="bg-white w-full sm:max-w-2xl max-h-[94vh] flex flex-col rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-4 bg-slate-900 text-white flex justify-between items-center shrink-0">
            <h3 class="font-bold text-sm"><i class="fa-solid fa-award text-amber-400 mr-2"></i><span id="crFormTitle">New Certificate</span></h3>
            <button type="button" onclick="crClose('crFormModal')" class="text-slate-400 hover:text-white p-1"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <form method="POST" action="<?= h($base) ?>&tab=certs" class="p-4 sm:p-6 space-y-4 overflow-y-auto" autocomplete="off">
            <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="do" value="save"><input type="hidden" name="id" id="f_id" value="0">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div><label class="<?= $lbl ?>">Member (optional) <small class="normal-case">/ সদস্য</small></label>
                    <select name="user_id" id="f_user_id" class="<?= $inp ?>"><option value="">— Not a member / Other —</option>
                        <?php foreach ($allMembers as $m) { echo '<option value="' . (int)$m['id'] . '">' . h($m['member_name']) . '</option>'; } ?></select></div>
                <div><label class="<?= $lbl ?>">Recipient Name * <small class="normal-case">/ প্রাপকের নাম</small></label>
                    <input type="text" name="recipient_name" id="f_recipient_name" class="<?= $inp ?>" placeholder="Auto-filled from member"></div>
            </div>
            <div><label class="<?= $lbl ?>">Certificate Title * <small class="normal-case">/ শিরোনাম</small></label>
                <input type="text" name="title" id="f_title" list="dl_title" class="<?= $inp ?>" placeholder="Certificate of Attending the Creative Art Competition"></div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div><label class="<?= $lbl ?>">Event / Program <small class="normal-case">/ ইভেন্ট</small></label><input type="text" name="event_name" id="f_event_name" list="dl_ev" class="<?= $inp ?>" placeholder="National Independent Day 2018"></div>
                <div><label class="<?= $lbl ?>">Organization <small class="normal-case">/ সংস্থা</small></label><input type="text" name="organization" id="f_organization" list="dl_org" class="<?= $inp ?>"></div>
                <div><label class="<?= $lbl ?>">Supported By <small class="normal-case">/ সহযোগিতায়</small></label><input type="text" name="supported_by" id="f_supported_by" class="<?= $inp ?>"></div>
                <div><label class="<?= $lbl ?>">Issue Date <small class="normal-case">/ তারিখ</small></label><input type="date" name="issue_date" id="f_issue_date" class="<?= $inp ?>"></div>
            </div>
            <div><label class="<?= $lbl ?>">Message <small class="normal-case">/ বিবরণ</small></label>
                <textarea name="description" id="f_description" rows="3" class="<?= $inp ?>" placeholder="In recognition of outstanding participation and contribution."></textarea></div>
            <div><label class="<?= $lbl ?>">Design <small class="normal-case">/ ডিজাইন</small></label>
                <div class="grid grid-cols-3 gap-2">
                    <?php foreach ($themes as $k => $t) { ?>
                    <label class="p-2 rounded-xl border border-slate-200 text-center text-[11px] font-semibold cursor-pointer has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50">
                        <input type="radio" name="theme" value="<?= $k ?>" class="sr-only" <?= $k === 'classic' ? 'checked' : '' ?>>
                        <span class="block h-8 rounded mb-1" style="background:<?= $t['bg'] ?>;border:4px solid <?= $t['b1'] ?>"></span><?= h($t['label']) ?>
                    </label>
                    <?php } ?>
                </div>
            </div>
            <div class="flex gap-2 pt-1">
                <button type="button" onclick="crClose('crFormModal')" class="flex-1 sm:flex-none px-5 py-3 sm:py-2.5 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600">Cancel</button>
                <button class="flex-1 sm:flex-none sm:ml-auto px-6 py-3 sm:py-2.5 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700">Save & View</button>
            </div>
        </form>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
(function () {
    var ORG = <?= json_encode($org, JSON_UNESCAPED_UNICODE) ?>, TH = <?= json_encode($themes) ?>;
    var CERTS = <?= json_encode($certs, JSON_UNESCAPED_UNICODE) ?>, MEM = <?= json_encode($members, JSON_UNESCAPED_UNICODE) ?>;
    var DEFORG = ORG.name, cur = null, $ = function (id) { return document.getElementById(id); };
    var MON = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
    function abs(p) { try { return new URL(p, location.href).href; } catch (e) { return p; } }
    function fdate(s) { var p = String(s || '').slice(0, 10).split('-'); return p.length === 3 ? p[2] + ' ' + MON[+p[1] - 1] + ' ' + p[0] : ''; }
    function open(id, on) { var m = $(id); m.classList.toggle('hidden', !on); m.classList.toggle('flex', on); document.body.style.overflow = on ? 'hidden' : ''; }
    window.crClose = function (id) { open(id, false); };

    /* ---------- Certificate design (1000 x 707) ---------- */
    function certHTML(c) {
        var t = TH[c.theme] || TH.classic, logo = esc(abs(ORG.logo)), org = c.organization || DEFORG;
        return '<div style="width:1000px;height:707px;position:relative;box-sizing:border-box;background:' + t.bg + ';border:18px solid ' + t.b1 + ';font-family:Georgia,serif;overflow:hidden">' +
            '<div style="position:absolute;top:6px;left:6px;right:6px;bottom:6px;border:4px dashed ' + t.b2 + '"></div>' +
            '<img src="' + logo + '" style="position:absolute;top:210px;left:310px;width:380px;opacity:.08" onerror="this.style.display=\'none\'">' +
            '<div style="position:relative;padding:34px 60px 0;text-align:center">' +
              '<div style="display:flex;align-items:center;justify-content:center;gap:18px"><img src="' + logo + '" style="width:96px;height:96px;object-fit:contain" onerror="this.style.display=\'none\'">' +
              '<div style="text-align:left"><div style="font:bold 42px Arial,sans-serif;color:' + t.a1 + '">' + esc(org) + '</div>' +
              (c.event_name ? '<div style="font:bold 20px Arial,sans-serif;letter-spacing:1px;text-transform:uppercase;color:' + t.a2 + ';margin-top:6px">' + esc(c.event_name) + '</div>' : '') + '</div></div>' +
              '<div style="font:italic 44px Georgia,serif;color:' + t.a3 + ';margin-top:34px;line-height:1.2">' + esc(c.title) + '</div>' +
              '<div style="font:26px Arial,sans-serif;color:#222;margin-top:40px">Presented To</div>' +
              '<div style="display:inline-block;min-width:520px;border-bottom:3px dotted #333;font:bold 38px Georgia,serif;color:#111;padding:6px 20px;margin-top:8px">' + esc(c.recipient_name) + '</div>' +
              '<div style="font:20px Arial,sans-serif;color:#333;margin:26px auto 0;max-width:760px;line-height:1.5">' + esc(c.description || 'In recognition of valuable participation and contribution.') + '</div>' +
            '</div>' +
            '<div style="position:absolute;left:60px;right:60px;bottom:34px;display:flex;justify-content:space-between;align-items:flex-end;font-family:Arial,sans-serif">' +
              '<div style="width:220px;text-align:center"><div style="border-top:2px solid #222;padding-top:6px;font-size:18px">Signature</div></div>' +
              '<div style="text-align:center;font-size:13px;color:#555">' + esc(c.cert_no || '') + '<br>' + esc(fdate(c.issue_date)) + '</div>' +
              '<div style="width:220px;text-align:center;font-size:16px">' + (c.supported_by ? 'Supported by<br><b>' + esc(c.supported_by) + '</b>' : '') + '</div>' +
            '</div></div>';
    }

    /* ---------- ID card design (330 x 520) ---------- */
    function idFront(m) {
        var logo = esc(abs(ORG.logo));
        var ph = m.photo_url ? '<img src="' + esc(abs(m.photo_url)) + '" style="width:120px;height:120px;border-radius:50%;object-fit:cover;border:5px solid #fff;background:#fff">'
            : '<div style="width:120px;height:120px;border-radius:50%;border:5px solid #fff;background:#d1fae5;color:#047857;font:bold 48px Arial;display:flex;align-items:center;justify-content:center">' + esc((m.member_name || '?').charAt(0).toUpperCase()) + '</div>';
        var row = function (l, v) { return v ? '<div style="display:flex;justify-content:space-between;gap:10px;padding:5px 0;border-bottom:1px solid #eee"><span style="color:#888">' + l + '</span><b style="text-align:right">' + esc(v) + '</b></div>' : ''; };
        return '<div style="width:330px;height:520px;background:#fff;border-radius:18px;overflow:hidden;font-family:Arial,sans-serif;box-shadow:0 4px 18px rgba(0,0,0,.15);position:relative;flex-shrink:0">' +
            '<div style="height:150px;background:linear-gradient(135deg,#065f46,#10b981);padding:16px;display:flex;align-items:flex-start;gap:10px;color:#fff"><img src="' + logo + '" style="width:44px;height:44px;object-fit:contain;background:#fff;border-radius:8px;padding:3px" onerror="this.style.display=\'none\'">' +
            '<div><div style="font-weight:bold;font-size:15px;line-height:1.2">' + esc(ORG.name) + '</div><div style="font-size:10px;opacity:.85;letter-spacing:2px;margin-top:3px">MEMBER ID CARD</div></div></div>' +
            '<div style="display:flex;justify-content:center;margin-top:-62px">' + ph + '</div>' +
            '<div style="text-align:center;padding:10px 20px 0"><div style="font-weight:bold;font-size:20px;color:#0f172a">' + esc(m.member_name) + '</div>' +
            '<div style="display:inline-block;margin-top:6px;padding:4px 14px;border-radius:99px;background:#d1fae5;color:#047857;font-size:11px;font-weight:bold;text-transform:uppercase">' + esc(m.user_type || 'Member') + '</div></div>' +
            '<div style="padding:14px 24px 0;font-size:12px;color:#334155">' + row('ID No', m.id_no) + row('Mobile', m.mobile_no) + row('Email', m.email) + row('Blood Group', m.blood_group) + '</div>' +
            '<div style="position:absolute;bottom:0;left:0;right:0;height:10px;background:linear-gradient(90deg,#065f46,#10b981,#a7f3d0)"></div></div>';
    }
    function idBack(m) {
        return '<div style="width:330px;height:520px;background:#fff;border-radius:18px;overflow:hidden;font-family:Arial,sans-serif;box-shadow:0 4px 18px rgba(0,0,0,.15);position:relative;flex-shrink:0;text-align:center">' +
            '<div style="height:10px;background:linear-gradient(90deg,#a7f3d0,#10b981,#065f46)"></div>' +
            '<img src="' + esc(abs(ORG.logo)) + '" style="width:90px;height:90px;object-fit:contain;margin-top:40px" onerror="this.style.display=\'none\'">' +
            '<div style="font-weight:bold;font-size:17px;color:#065f46;margin-top:10px;padding:0 20px">' + esc(ORG.name) + '</div>' +
            '<div style="font-size:12px;color:#475569;padding:12px 30px;line-height:1.6">' + esc(ORG.address) + '<br>' + esc(ORG.phone) + '<br>' + esc(ORG.email) + '</div>' +
            '<div style="margin:18px 28px;padding:12px;border:1px dashed #94a3b8;border-radius:10px;font-size:11px;color:#64748b;line-height:1.5">This card is the property of the organization. If found, please return to the above address.<br><i>এই কার্ডটি সংস্থার সম্পত্তি। পেলে উপরের ঠিকানায় ফেরত দিন।</i></div>' +
            '<div style="position:absolute;bottom:40px;left:0;right:0"><div style="width:130px;margin:0 auto;border-top:1px solid #334155;padding-top:4px;font-size:11px;color:#475569">Authorized Signature</div></div>' +
            '<div style="position:absolute;bottom:0;left:0;right:0;height:10px;background:linear-gradient(90deg,#065f46,#10b981,#a7f3d0)"></div></div>';
    }

    /* ---------- Show ---------- */
    function show(title, html, w, h, name) {
        cur = { html: html, w: w, h: h, name: name };
        $('crViewTitle').textContent = title;
        var box = $('crBox'); box.style.width = '100%'; open('crView', true);
        var sc = Math.min(1, (box.clientWidth - 4) / w);
        box.innerHTML = '<div style="width:' + w * sc + 'px;height:' + h * sc + 'px;margin:0 auto"><div style="transform:scale(' + sc + ');transform-origin:top left;width:' + w + 'px">' + html + '</div></div>';
    }
    window.crView = function (id) {
        var c = CERTS.filter(function (x) { return +x.id === +id; })[0]; if (!c) { return; }
        show('Certificate • ' + c.cert_no, certHTML(c), 1000, 707, (c.cert_no || 'certificate') + '_' + c.recipient_name.replace(/\s+/g, '_'));
    };
    window.crCard = function (id) {
        var m = MEM.filter(function (x) { return +x.id === +id; })[0]; if (!m) { return; }
        var w = 330 * 2 + 72, h = 520 + 48;
        show('ID Card • ' + m.member_name, '<div style="display:flex;gap:24px;padding:24px;background:#fff;width:' + w + 'px;box-sizing:border-box">' + idFront(m) + idBack(m) + '</div>', w, h, m.id_no + '_' + m.member_name.replace(/\s+/g, '_'));
    };

    /* ---------- Download (PNG / PDF) ---------- */
    window.crDownload = function (fmt) {
        if (!cur || !window.html2canvas) { alert('Library load hoyni. Internet check korun.'); return; }
        var el = document.createElement('div');
        el.style.cssText = 'position:fixed;left:-99999px;top:0;width:' + cur.w + 'px;height:' + cur.h + 'px';
        el.innerHTML = cur.html; document.body.appendChild(el);
        html2canvas(el, { scale: 2, useCORS: true, backgroundColor: '#ffffff' }).then(function (cv) {
            document.body.removeChild(el);
            if (fmt === 'png') {
                var a = document.createElement('a'); a.href = cv.toDataURL('image/png'); a.download = cur.name + '.png'; a.click();
            } else {
                var pdf = new window.jspdf.jsPDF({ orientation: cur.w > cur.h ? 'l' : 'p', unit: 'px', format: [cur.w, cur.h] });
                pdf.addImage(cv.toDataURL('image/jpeg', 0.95), 'JPEG', 0, 0, cur.w, cur.h); pdf.save(cur.name + '.pdf');
            }
        });
    };

    /* ---------- Form ---------- */
    var F = ['user_id', 'recipient_name', 'title', 'event_name', 'organization', 'supported_by', 'issue_date', 'description'];
    window.crForm = function (id) {
        var c = id ? CERTS.filter(function (x) { return +x.id === +id; })[0] : null;
        $('f_id').value = c ? c.id : 0; $('crFormTitle').textContent = c ? 'Edit Certificate' : 'New Certificate';
        F.forEach(function (k) { $('f_' + k).value = c ? (c[k] == null ? '' : c[k]) : ''; });
        if (!c) { $('f_issue_date').value = new Date().toISOString().slice(0, 10); $('f_organization').value = DEFORG; }
        var r = document.querySelector('input[name=theme][value=' + (c ? c.theme : 'classic') + ']'); if (r) { r.checked = true; }
        open('crFormModal', true);
    };
    $('f_user_id').addEventListener('change', function () {
        if (this.value) { $('f_recipient_name').value = this.options[this.selectedIndex].text; }
    });
    ['crView', 'crFormModal'].forEach(function (id) { $(id).addEventListener('mousedown', function (e) { if (e.target === this) { open(id, false); } }); });

    var v = <?= (int)$viewId ?>; if (v) { crView(v); }
})();
</script>