<?php
/* ==========================================================
   settings.php  ->  index.php?page=settings
   Settings (Admin only). Ekhon: Member Registration Terms & Conditions
   Pore notun section: $SECTIONS array-te ekta line + ekta <div> add korlei hobe.
   Table: settings.sql age import korun.
   ========================================================== */
if (session_status() === PHP_SESSION_NONE) { @session_start(); }
if (!function_exists('h')) { function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
mysqli_set_charset($db, 'utf8mb4');

/* ---------- Role (database theke verify) ---------- */
$me = (int)($_SESSION['user_id'] ?? $_SESSION['id'] ?? $_SESSION['uid'] ?? $_SESSION['member_id'] ?? 0);
$utype = $_SESSION['user_type'] ?? '';
if ($me > 0) { $ur = mysqli_fetch_row(mysqli_query($db, "SELECT user_type FROM users WHERE id = " . $me . " LIMIT 1")); if ($ur) { $utype = $ur[0]; } }
if ($utype !== 'Admin') {
    echo '<section class="page-content max-w-xl mx-auto"><div class="p-6 bg-white rounded-2xl ring-1 ring-slate-200 text-center space-y-2"><i class="fa-solid fa-lock text-3xl text-slate-300"></i>
          <h3 class="font-bold text-slate-800">Access Restricted</h3><p class="text-xs text-slate-500">Only Admin can open Settings. / শুধু অ্যাডমিন সেটিংস দেখতে পারবেন।</p></div></section>';
    return;
}
$dbReady = true;
try { foreach (['terms_conditions', 'portal_settings'] as $tb) { if (!mysqli_query($db, "SELECT 1 FROM $tb LIMIT 1")) { $dbReady = false; } } } catch (Throwable $e) { $dbReady = false; }
if (!$dbReady) {
    echo '<section class="page-content max-w-xl mx-auto"><div class="p-6 bg-amber-50 border border-amber-200 rounded-2xl text-center space-y-2"><i class="fa-solid fa-database text-3xl text-amber-400"></i>
          <h3 class="font-bold text-slate-800">Database tables not found</h3><p class="text-xs text-slate-600">Please import <b>settings.sql</b> in phpMyAdmin first. / আগে settings.sql ইম্পোর্ট করুন।</p></div></section>';
    return;
}
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }
$page = $_GET['page'] ?? 'settings';
$base = 'index.php?page=' . urlencode($page);

function st_rows($db, $sql, $t = '', $p = []) {
    $st = mysqli_prepare($db, $sql);
    if (!$st) { return []; }
    if ($t !== '') { mysqli_stmt_bind_param($st, $t, ...$p); }
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st); $o = [];
    while ($res && $r = mysqli_fetch_assoc($res)) { $o[] = $r; }
    mysqli_stmt_close($st);
    return $o;
}
function st_run($db, $sql, $t, $p) { $st = mysqli_prepare($db, $sql); mysqli_stmt_bind_param($st, $t, ...$p); $ok = mysqli_stmt_execute($st); $n = mysqli_stmt_affected_rows($st); mysqli_stmt_close($st); return $ok ? $n : -1; }

/* ---------- Settings sections (pore ekhane aro add korben) ---------- */
$SECTIONS = [
    'terms'   => ['Terms & Conditions', 'সদস্য নিবন্ধনের শর্তাবলী', 'fa-file-contract', true],
    'general' => ['General Information', 'সংস্থার সাধারণ তথ্য', 'fa-building', false],
    'notify'  => ['Notifications', 'নোটিফিকেশন', 'fa-bell', false],
    'security'=> ['Security', 'নিরাপত্তা', 'fa-shield-halved', false],
];

/* ---------- ACTIONS ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
    $do = $_POST['do'] ?? ''; $ok = true; $msg = ''; $id = (int)($_POST['id'] ?? 0);

    if ($do === 'save') {
        $title = trim($_POST['title'] ?? ''); $content = trim($_POST['content'] ?? ''); $status = ($_POST['status'] ?? '') === 'Inactive' ? 'Inactive' : 'Active';
        if ($title === '' || $content === '') { $ok = false; $msg = 'Title and description are required. / শিরোনাম ও বিবরণ দিন।'; }
        elseif ($id > 0) { st_run($db, "UPDATE terms_conditions SET title = ?, content = ?, status = ?, updated_by = ? WHERE id = ?", 'sssii', [$title, $content, $status, $me ?: null, $id]); $msg = 'Clause updated.'; }
        else {
            $max = (int)(st_rows($db, "SELECT COALESCE(MAX(sort_order),0) m FROM terms_conditions")[0]['m'] ?? 0);
            st_run($db, "INSERT INTO terms_conditions (title, content, status, sort_order, updated_by) VALUES (?,?,?,?,?)", 'sssii', [$title, $content, $status, $max + 1, $me ?: null]); $msg = 'Clause added.';
        }
    } elseif ($do === 'delete') {
        st_run($db, "DELETE FROM terms_conditions WHERE id = ?", 'i', [$id]); $msg = 'Clause deleted.';
    } elseif ($do === 'toggle') {
        st_run($db, "UPDATE terms_conditions SET status = IF(status = 'Active', 'Inactive', 'Active') WHERE id = ?", 'i', [$id]); $msg = 'Status changed.';
    } elseif ($do === 'move') {
        $ids = array_map('intval', array_column(st_rows($db, "SELECT id FROM terms_conditions ORDER BY sort_order, id"), 'id'));
        $i = array_search($id, $ids); $j = $i === false ? -1 : $i + (($_POST['dir'] ?? '') === 'up' ? -1 : 1);
        if ($i !== false && isset($ids[$j])) { $x = $ids[$i]; $ids[$i] = $ids[$j]; $ids[$j] = $x; foreach ($ids as $k => $cid) { st_run($db, "UPDATE terms_conditions SET sort_order = ? WHERE id = ?", 'ii', [$k + 1, $cid]); } }
        $msg = 'Order updated.';
    } elseif ($do === 'text') {
        foreach (['terms_heading', 'terms_intro', 'terms_agree_label'] as $k) {
            st_run($db, "INSERT INTO portal_settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)", 'ss', [$k, trim($_POST[$k] ?? '')]);
        }
        $msg = 'Heading & intro saved.';
    }
    $_SESSION['st_flash'] = [$ok, $msg];
    header('Location: ' . $base . '&s=terms'); exit;
}
$flash = $_SESSION['st_flash'] ?? null; unset($_SESSION['st_flash']);

/* ---------- DATA ---------- */
$sec = isset($SECTIONS[$_GET['s'] ?? '']) && $SECTIONS[$_GET['s']][3] ? $_GET['s'] : 'terms';
$clauses = st_rows($db, "SELECT t.*, u.member_name editor FROM terms_conditions t LEFT JOIN users u ON u.id = t.updated_by ORDER BY t.sort_order, t.id");
$cfg = ['terms_heading' => 'Terms & Conditions', 'terms_intro' => 'Please read the following terms carefully before registering as a member.', 'terms_agree_label' => 'I have read and agree to the Terms & Conditions.'];
foreach (st_rows($db, "SELECT setting_key, setting_value FROM portal_settings WHERE setting_key LIKE 'terms_%'") as $r) { if ($r['setting_value'] !== '') { $cfg[$r['setting_key']] = $r['setting_value']; } }
$active = count(array_filter($clauses, function ($c) { return $c['status'] === 'Active'; }));
$last = $clauses ? max(array_column($clauses, 'updated_at')) : null;
$inp = 'w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10';
$lbl = 'block text-[11px] font-bold uppercase text-slate-500 mb-1';
$csrf = '<input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '">';
?>
<section class="page-content space-y-5 max-w-7xl mx-auto">

    <div>
        <h2 class="text-xl sm:text-2xl font-bold text-slate-800">Settings <small class="text-xs font-medium text-slate-400">/ সেটিংস</small></h2>
        <p class="text-xs text-slate-500 mt-0.5">Manage portal and website settings.</p>
    </div>

    <?php if ($flash) { ?><div class="p-3 rounded-xl text-xs font-semibold border <?= $flash[0] ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-rose-50 border-rose-200 text-rose-700' ?>"><?= h($flash[1]) ?></div><?php } ?>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-start">

        <!-- Settings menu -->
        <nav class="lg:col-span-3 bg-white rounded-2xl ring-1 ring-slate-200/80 p-2 flex lg:flex-col gap-1 overflow-x-auto">
            <?php foreach ($SECTIONS as $k => $s) { $on = $s[3]; $cur = $k === $sec; ?>
            <a <?= $on ? 'href="' . h($base . '&s=' . $k) . '"' : '' ?> class="shrink-0 flex items-center gap-3 px-3.5 py-3 rounded-xl text-xs font-semibold <?= $cur ? 'bg-emerald-600 text-white shadow-sm' : ($on ? 'text-slate-600 hover:bg-slate-50' : 'text-slate-300 cursor-not-allowed') ?>">
                <i class="fa-solid <?= $s[2] ?> w-4 text-center"></i>
                <span class="whitespace-nowrap"><?= h($s[0]) ?><small class="hidden lg:block font-normal opacity-70"><?= h($s[1]) ?></small></span>
                <?php if (!$on) { ?><span class="ml-auto px-1.5 py-0.5 rounded bg-slate-100 text-slate-400 text-[9px] font-bold">Soon</span><?php } ?>
            </a>
            <?php } ?>
        </nav>

        <!-- Content -->
        <div class="lg:col-span-9 space-y-4">
            <?php if ($sec === 'terms') { ?>

            <div class="bg-white rounded-2xl ring-1 ring-slate-200/80 p-4 sm:p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0"><i class="fa-solid fa-file-contract"></i></div>
                    <div><h3 class="font-bold text-slate-800">Member Registration — Terms & Conditions</h3>
                        <p class="text-[11px] text-slate-500">Shown to new members on the registration form. <?= $last ? 'Last updated: ' . h(date('d M Y, h:i A', strtotime($last))) : '' ?></p></div>
                </div>
                <div class="grid grid-cols-3 sm:flex gap-2">
                    <button type="button" onclick="stModal('stText', true)" class="px-3 py-2.5 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50"><i class="fa-solid fa-heading mr-1"></i>Intro</button>
                    <button type="button" onclick="stTab('preview')" class="px-3 py-2.5 border border-sky-200 bg-sky-50 rounded-xl text-xs font-semibold text-sky-700 hover:bg-sky-100"><i class="fa-solid fa-eye mr-1"></i>Preview</button>
                    <button type="button" onclick="stForm()" class="px-3 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-emerald-600/20"><i class="fa-solid fa-plus mr-1"></i>Add</button>
                </div>
            </div>

            <div class="grid grid-cols-3 gap-3">
                <?php foreach ([['Total Clauses', count($clauses), 'slate'], ['Active', $active, 'emerald'], ['Inactive', count($clauses) - $active, 'amber']] as $c) { ?>
                <div class="p-3.5 bg-white rounded-2xl ring-1 ring-slate-200/80 text-center"><p class="text-[10px] font-bold uppercase text-slate-400"><?= $c[0] ?></p><p class="text-2xl font-bold text-<?= $c[2] ?>-600"><?= $c[1] ?></p></div>
                <?php } ?>
            </div>

            <!-- tabs -->
            <div class="grid grid-cols-2 gap-1 p-1 bg-slate-100 rounded-xl sm:w-80">
                <button type="button" data-tab="manage" class="sttab py-2.5 rounded-lg text-xs font-bold bg-white text-emerald-700 shadow-sm"><i class="fa-solid fa-list-ol mr-1.5"></i>Manage</button>
                <button type="button" data-tab="preview" class="sttab py-2.5 rounded-lg text-xs font-bold text-slate-500"><i class="fa-solid fa-eye mr-1.5"></i>Preview</button>
            </div>

            <!-- MANAGE -->
            <div id="tab_manage" class="space-y-3">
                <?php foreach ($clauses as $i => $c) { $act = $c['status'] === 'Active'; ?>
                <div class="bg-white rounded-2xl ring-1 ring-slate-200/80 p-4 flex gap-3 sm:gap-4 <?= $act ? '' : 'opacity-70' ?>">
                    <div class="w-9 h-9 rounded-xl bg-emerald-600 text-white flex items-center justify-center text-sm font-bold shrink-0"><?= $i + 1 ?></div>
                    <div class="min-w-0 flex-1 space-y-2">
                        <div class="flex flex-wrap items-center gap-2">
                            <h4 class="font-bold text-sm text-slate-800"><?= h($c['title']) ?></h4>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold <?= $act ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-200 text-slate-600' ?>"><?= h($c['status']) ?></span>
                        </div>
                        <p class="text-xs text-slate-600 leading-relaxed whitespace-pre-line line-clamp-4"><?= h($c['content']) ?></p>
                        <p class="text-[10px] text-slate-400">Updated <?= h(date('d M Y', strtotime($c['updated_at']))) ?><?= $c['editor'] ? ' by ' . h($c['editor']) : '' ?></p>
                        <div class="flex flex-wrap items-center gap-1.5 pt-1">
                            <form method="POST" action="" class="flex gap-1"><?= $csrf ?><input type="hidden" name="do" value="move"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                <button name="dir" value="up" title="Move up" <?= $i === 0 ? 'disabled' : '' ?> class="w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-30"><i class="fa-solid fa-arrow-up text-xs"></i></button>
                                <button name="dir" value="down" title="Move down" <?= $i === count($clauses) - 1 ? 'disabled' : '' ?> class="w-8 h-8 rounded-lg border border-slate-200 text-slate-500 hover:bg-slate-50 disabled:opacity-30"><i class="fa-solid fa-arrow-down text-xs"></i></button></form>
                            <form method="POST" action=""><?= $csrf ?><input type="hidden" name="do" value="toggle"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                <button class="px-3 h-8 rounded-lg text-[11px] font-bold <?= $act ? 'bg-amber-50 text-amber-700 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' ?>"><i class="fa-solid <?= $act ? 'fa-eye-slash' : 'fa-eye' ?> mr-1"></i><?= $act ? 'Hide' : 'Show' ?></button></form>
                            <button type="button" onclick="stForm(<?= (int)$c['id'] ?>)" class="px-3 h-8 rounded-lg bg-sky-50 text-sky-700 text-[11px] font-bold hover:bg-sky-100"><i class="fa-solid fa-pen mr-1"></i>Edit</button>
                            <form method="POST" action="" onsubmit="return confirm('Delete this clause? / এই ধারাটি মুছে ফেলবেন?')"><?= $csrf ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                                <button class="px-3 h-8 rounded-lg bg-rose-50 text-rose-600 text-[11px] font-bold hover:bg-rose-100"><i class="fa-solid fa-trash mr-1"></i>Delete</button></form>
                        </div>
                    </div>
                </div>
                <?php } if (!$clauses) { echo '<div class="py-14 text-center text-slate-400 text-sm bg-white rounded-2xl ring-1 ring-slate-200/80"><i class="fa-solid fa-file-circle-plus text-4xl mb-3 block"></i>No clauses yet. Click “Add” to write the first one.<br>কোনো ধারা নেই। “Add” চাপুন।</div>'; } ?>
            </div>

            <!-- PREVIEW (registration form-e jemon dekhabe) -->
            <div id="tab_preview" class="hidden">
                <div class="bg-white rounded-2xl ring-1 ring-slate-200/80 overflow-hidden max-w-3xl">
                    <div class="h-2 bg-gradient-to-r from-emerald-800 via-emerald-600 to-emerald-400"></div>
                    <div class="p-5 sm:p-8 space-y-5">
                        <div class="text-center"><h3 class="text-lg sm:text-xl font-bold text-emerald-800"><?= h($cfg['terms_heading']) ?></h3>
                            <p class="text-xs text-slate-500 mt-1.5"><?= h($cfg['terms_intro']) ?></p></div>
                        <ol class="space-y-4">
                            <?php $n = 0; foreach ($clauses as $c) { if ($c['status'] !== 'Active') { continue; } $n++; ?>
                            <li class="flex gap-3"><span class="w-7 h-7 rounded-full bg-emerald-50 text-emerald-700 flex items-center justify-center text-xs font-bold shrink-0"><?= $n ?></span>
                                <div><p class="font-bold text-sm text-slate-800"><?= h($c['title']) ?></p><p class="text-xs text-slate-600 leading-relaxed whitespace-pre-line mt-0.5"><?= h($c['content']) ?></p></div></li>
                            <?php } if (!$n) { echo '<li class="text-center text-xs text-slate-400">No active clauses.</li>'; } ?>
                        </ol>
                        <label class="flex items-start gap-2.5 p-3.5 rounded-xl bg-slate-50 border border-slate-200 text-xs font-semibold text-slate-700"><input type="checkbox" disabled class="mt-0.5 rounded w-4 h-4"> <?= h($cfg['terms_agree_label']) ?></label>
                        <button type="button" disabled class="w-full py-3 rounded-xl bg-emerald-600/40 text-white text-xs font-bold cursor-not-allowed">Register</button>
                        <p class="text-center text-[10px] text-slate-400">Preview only — members will see this on the registration page.</p>
                    </div>
                </div>
            </div>

            <?php } ?>
        </div>
    </div>
</section>

<!-- ADD / EDIT CLAUSE -->
<div id="stForm" class="fixed inset-0 bg-slate-900/60 hidden items-end sm:items-center justify-center sm:p-4 z-50">
    <div class="bg-white w-full sm:max-w-xl max-h-[94vh] flex flex-col rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-4 bg-slate-900 text-white flex justify-between items-center shrink-0"><h3 class="font-bold text-sm"><i class="fa-solid fa-file-contract text-emerald-400 mr-2"></i><span id="stFormTitle">Add Clause</span></h3>
            <button type="button" onclick="stModal('stForm', false)" class="text-slate-400 hover:text-white p-1"><i class="fa-solid fa-xmark text-lg"></i></button></div>
        <form method="POST" action="" class="p-4 sm:p-6 space-y-4 overflow-y-auto">
            <?= $csrf ?><input type="hidden" name="do" value="save"><input type="hidden" name="id" id="f_id" value="0">
            <div><label class="<?= $lbl ?>">Clause Title * <small class="normal-case">/ ধারার শিরোনাম</small></label><input type="text" name="title" id="f_title" required class="<?= $inp ?>" placeholder="e.g. Membership Fee / সদস্য চাঁদা"></div>
            <div><label class="<?= $lbl ?>">Description * <small class="normal-case">/ বিবরণ</small></label><textarea name="content" id="f_content" rows="7" required class="<?= $inp ?>" placeholder="Write the full condition here..."></textarea></div>
            <div><label class="<?= $lbl ?>">Status</label><select name="status" id="f_status" class="<?= $inp ?>"><option>Active</option><option>Inactive</option></select>
                <p class="text-[11px] text-slate-400 mt-1">Inactive clauses are hidden from the registration form.</p></div>
            <div class="flex gap-2 pt-1">
                <button type="button" onclick="stModal('stForm', false)" class="flex-1 sm:flex-none px-5 py-3 sm:py-2.5 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600">Cancel</button>
                <button class="flex-1 sm:flex-none sm:ml-auto px-6 py-3 sm:py-2.5 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700">Save Clause</button>
            </div>
        </form>
    </div>
</div>

<!-- HEADING & INTRO -->
<div id="stText" class="fixed inset-0 bg-slate-900/60 hidden items-end sm:items-center justify-center sm:p-4 z-50">
    <div class="bg-white w-full sm:max-w-xl rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-4 bg-slate-900 text-white flex justify-between items-center"><h3 class="font-bold text-sm"><i class="fa-solid fa-heading text-emerald-400 mr-2"></i>Heading, Intro & Agree Text</h3>
            <button type="button" onclick="stModal('stText', false)" class="text-slate-400 hover:text-white p-1"><i class="fa-solid fa-xmark text-lg"></i></button></div>
        <form method="POST" action="" class="p-4 sm:p-6 space-y-4">
            <?= $csrf ?><input type="hidden" name="do" value="text">
            <div><label class="<?= $lbl ?>">Heading</label><input type="text" name="terms_heading" value="<?= h($cfg['terms_heading']) ?>" class="<?= $inp ?>"></div>
            <div><label class="<?= $lbl ?>">Intro text</label><textarea name="terms_intro" rows="3" class="<?= $inp ?>"><?= h($cfg['terms_intro']) ?></textarea></div>
            <div><label class="<?= $lbl ?>">Checkbox text <small class="normal-case">(“I agree...”)</small></label><input type="text" name="terms_agree_label" value="<?= h($cfg['terms_agree_label']) ?>" class="<?= $inp ?>"></div>
            <button class="w-full py-3 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700">Save</button>
        </form>
    </div>
</div>

<script>
(function () {
    var C = <?= json_encode($clauses, JSON_UNESCAPED_UNICODE) ?>, $ = function (id) { return document.getElementById(id); };
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