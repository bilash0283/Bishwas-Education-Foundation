<?php
/* ==========================================================
   my_certificates.php  ->  index.php?page=my_certificates
   Logged-in member-er nijer:
   1) ID Card (view + PNG/PDF)
   2) Membership Certificate (joining date diya auto, PNG/PDF)
   3) Admin-er deya onno certificate gulo (certificates table theke)
   Shob preview modal-e. Admin chara onno user-er jonno shudhu Active member hole.
   ========================================================== */
if (session_status() === PHP_SESSION_NONE) { @session_start(); }
if (!isset($_SESSION['user_type'])) {
    header('Location: index.php?page=dashboard');
    exit;
}
$me = (int)($_SESSION['user_id'] ?? 0);
$connection = isset($db) ? $db : (isset($conn) ? $conn : null);
if (!function_exists('h')) { function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
$restricted = '<section class="page-content max-w-xl mx-auto"><div class="p-6 bg-white rounded-2xl ring-1 ring-slate-200 text-center space-y-2"><i class="fa-solid fa-lock text-3xl text-slate-300"></i>
    <h3 class="font-bold text-slate-800">Access Restricted</h3><p class="text-xs text-slate-500">Your account does not have permission to access this page.</p></div></section>';
if ($me <= 0 || !$connection) { echo $restricted; return; }
mysqli_set_charset($connection, 'utf8mb4');

function mc_rows($db, $sql, $t = '', $p = []) {
    try {
        $st = mysqli_prepare($db, $sql); if (!$st) { return []; }
        if ($t !== '') { mysqli_stmt_bind_param($st, $t, ...$p); }
        mysqli_stmt_execute($st); $res = mysqli_stmt_get_result($st); $o = [];
        while ($res && $r = mysqli_fetch_assoc($res)) { $o[] = $r; }
        mysqli_stmt_close($st); return $o;
    } catch (Throwable $e) { return []; }
}

$u = mc_rows($connection, "SELECT * FROM users WHERE id = ? LIMIT 1", 'i', [$me])[0] ?? null;
if (!$u) { echo $restricted; return; }

$member_dir = 'public/uploads/members/';
$id_prefix  = 'BEF';
$photo = (!empty($u['photo']) && is_file($member_dir . basename($u['photo']))) ? $member_dir . basename($u['photo']) : '';
$isAdmin  = ($u['user_type'] === 'Admin');
$approved = $isAdmin || (($u['status'] ?? '') === 'Active');
$joined   = (!empty($u['created_at']) && strtotime($u['created_at'])) ? date('Y-m-d', strtotime($u['created_at'])) : '';

$duration = '';
if ($joined) {
    $df = (new DateTime($joined))->diff(new DateTime('today'));
    $duration = ($df->y ? $df->y . ' year' . ($df->y > 1 ? 's' : '') . ' ' : '') . ($df->m ? $df->m . ' month' . ($df->m > 1 ? 's' : '') . ' ' : '');
    $duration = trim($duration) !== '' ? trim($duration) : ($df->d . ' day' . ($df->d == 1 ? '' : 's'));
}

// Admin-er deya certificate gulo (table na thakle khali)
$certs = mc_rows($connection, "SELECT id, cert_no, title, event_name, organization, supported_by, description, theme, issue_date, recipient_name FROM certificates WHERE user_id = ? ORDER BY issue_date DESC, id DESC", 'i', [$me]);

$ME = ['name' => $u['member_name'], 'type' => $u['user_type'], 'id_no' => $id_prefix . '-' . str_pad($me, 5, '0', STR_PAD_LEFT), 'mobile' => $u['mobile_no'], 'email' => $u['email'],
       'blood' => $u['blood_group'] ?? '', 'photo' => $photo, 'joined' => $joined, 'today' => date('Y-m-d')];
$ORG = ['name' => $site_title ?? '', 'address' => $office_address ?? '', 'phone' => $phone_number ?? '', 'email' => $email_address ?? '', 'logo' => '../public/assets/' . ($favicon_icon ?? '')];
$themes = [
    'classic' => ['bg' => '#fdf7b0', 'b1' => '#e11d74', 'b2' => '#16a34a', 'a1' => '#15803d', 'a2' => '#e11d48', 'a3' => '#1e3a8a'],
    'emerald' => ['bg' => '#f0fdf4', 'b1' => '#047857', 'b2' => '#d4af37', 'a1' => '#065f46', 'a2' => '#b45309', 'a3' => '#064e3b'],
    'royal'   => ['bg' => '#eff6ff', 'b1' => '#1e3a8a', 'b2' => '#d4af37', 'a1' => '#1e40af', 'a2' => '#b45309', 'a3' => '#1e293b'],
];
$btnDis = $approved ? '' : 'opacity-40 pointer-events-none';
?>
<section class="page-content space-y-6 max-w-5xl mx-auto">

    <!-- Hero -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-slate-800 to-emerald-700 text-white p-5 sm:p-8">
        <div class="absolute -right-10 -top-10 h-56 w-56 rounded-full bg-emerald-400/20 blur-3xl pointer-events-none"></div>
        <div class="relative flex flex-col sm:flex-row items-center gap-5 text-center sm:text-left">
            <?php if ($photo) { ?><img src="<?= h($photo) ?>" alt="" class="w-24 h-28 rounded-2xl object-cover border-4 border-white/20 shadow-lg shrink-0">
            <?php } else { ?><div class="w-24 h-28 rounded-2xl bg-white/10 border-4 border-white/20 flex items-center justify-center text-4xl font-bold shrink-0"><?= h(mb_strtoupper(mb_substr($u['member_name'], 0, 1))) ?></div><?php } ?>
            <div class="min-w-0 space-y-2">
                <p class="text-xs text-emerald-200 font-semibold"><i class="fa-solid fa-id-card mr-1.5"></i>My ID Card & Certificates / আমার আইডি কার্ড ও সার্টিফিকেট</p>
                <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight break-words"><?= h($u['member_name']) ?></h2>
                <div class="flex flex-wrap justify-center sm:justify-start gap-2">
                    <span class="px-3 py-1 rounded-full bg-white/15 text-xs font-semibold"><?= h($u['user_type']) ?></span>
                    <span class="px-3 py-1 rounded-full bg-white/10 text-xs"><?= h($ME['id_no']) ?></span>
                    <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $approved ? 'bg-emerald-400/30 text-emerald-100' : 'bg-amber-400/30 text-amber-100' ?>"><?= $approved ? 'Active Member' : h($u['status'] ?: 'Pending') ?></span>
                </div>
            </div>
        </div>
    </div>

    <?php if (!$approved) { ?>
    <div class="p-4 rounded-2xl bg-amber-50 border border-amber-200 text-amber-800 text-sm flex items-start gap-3">
        <i class="fa-solid fa-hourglass-half mt-0.5"></i><p><b>Your membership is under review.</b> ID card and membership certificate will be available once the admin approves your account.<br><span class="text-xs">আপনার সদস্যপদ যাচাই চলছে। অনুমোদনের পর আইডি কার্ড ও সার্টিফিকেট পাবেন।</span></p></div>
    <?php } ?>

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <?php foreach ([['Member Since', 'সদস্য হয়েছেন', $joined ? date('d M Y', strtotime($joined)) : '—', 'emerald', 'fa-calendar-check'], ['Membership', 'সদস্যপদের সময়', $duration ?: '—', 'sky', 'fa-hourglass-half'],
                        ['Member Type', 'সদস্যের ধরন', $u['user_type'], 'violet', 'fa-user-tag'], ['Certificates', 'প্রাপ্ত সার্টিফিকেট', ($approved ? 1 : 0) + count($certs), 'amber', 'fa-award']] as $c) { ?>
        <div class="p-4 bg-white rounded-2xl ring-1 ring-slate-200/80"><div class="flex items-center justify-between"><p class="text-[10px] font-bold uppercase text-slate-400"><?= $c[0] ?></p>
            <span class="w-8 h-8 rounded-lg bg-<?= $c[3] ?>-50 text-<?= $c[3] ?>-600 flex items-center justify-center text-sm"><i class="fa-solid <?= $c[4] ?>"></i></span></div>
            <h3 class="text-base sm:text-lg font-bold text-slate-800 mt-1 break-words"><?= h($c[2]) ?></h3><p class="text-[11px] text-slate-400"><?= $c[1] ?></p></div>
        <?php } ?>
    </div>

    <!-- ID Card + Membership Certificate -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
        <?php $docs = [['idcard', 'My ID Card', 'আমার আইডি কার্ড', 'Official member identity card (front & back)', 'emerald', 'fa-id-card'],
                       ['member', 'Membership Certificate', 'সদস্যপদ সনদ', 'Issued on your joining date as a member of the foundation', 'amber', 'fa-award']];
        foreach ($docs as $d) { ?>
        <div class="bg-white rounded-3xl ring-1 ring-slate-200/80 overflow-hidden flex flex-col hover:shadow-lg transition">
            <div class="bg-slate-100 p-4 sm:p-5 flex items-center justify-center min-h-[210px] relative">
                <div id="mini_<?= $d[0] ?>" class="mx-auto overflow-hidden rounded-lg shadow-md bg-white"></div>
                <?php if (!$approved) { ?><div class="absolute inset-0 bg-slate-900/40 backdrop-blur-[2px] flex flex-col items-center justify-center text-white"><i class="fa-solid fa-lock text-2xl mb-1"></i><span class="text-xs font-semibold">Available after approval</span></div><?php } ?>
            </div>
            <div class="p-5 space-y-3 flex-1 flex flex-col">
                <div class="flex items-start gap-3"><span class="w-10 h-10 rounded-xl bg-<?= $d[4] ?>-50 text-<?= $d[4] ?>-600 flex items-center justify-center shrink-0"><i class="fa-solid <?= $d[5] ?>"></i></span>
                    <div><h3 class="font-bold text-slate-800"><?= $d[1] ?> <small class="font-medium text-slate-400">/ <?= $d[2] ?></small></h3><p class="text-[11px] text-slate-500 mt-0.5"><?= $d[3] ?></p></div></div>
                <div class="grid grid-cols-3 gap-2 mt-auto <?= $btnDis ?>">
                    <button type="button" onclick="mcOpen('<?= $d[0] ?>')" class="py-2.5 rounded-xl bg-slate-900 text-white text-xs font-bold hover:bg-slate-800"><i class="fa-solid fa-eye mr-1"></i>View</button>
                    <button type="button" onclick="mcQuick('<?= $d[0] ?>','png')" class="py-2.5 rounded-xl bg-emerald-50 text-emerald-700 text-xs font-bold hover:bg-emerald-100"><i class="fa-solid fa-image mr-1"></i>PNG</button>
                    <button type="button" onclick="mcQuick('<?= $d[0] ?>','pdf')" class="py-2.5 rounded-xl bg-rose-50 text-rose-600 text-xs font-bold hover:bg-rose-100"><i class="fa-solid fa-file-pdf mr-1"></i>PDF</button>
                </div>
            </div>
        </div>
        <?php } ?>
    </div>

    <!-- Certificates issued by the foundation -->
    <div class="bg-white rounded-3xl ring-1 ring-slate-200/80 p-4 sm:p-6">
        <div class="flex items-center justify-between mb-4"><h3 class="font-bold text-slate-800"><i class="fa-solid fa-medal text-amber-500 mr-2"></i>Certificates I Received <small class="font-medium text-slate-400">/ আমার প্রাপ্ত সার্টিফিকেট</small></h3><span class="text-xs text-slate-500"><?= count($certs) ?> total</span></div>
        <?php if ($certs) { ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-3 gap-4">
            <?php foreach ($certs as $c) { ?>
            <div class="rounded-2xl border border-slate-200 p-4 flex flex-col gap-3 hover:shadow-md transition">
                <div class="flex items-start justify-between gap-2"><div class="min-w-0"><p class="text-[10px] font-bold text-emerald-700"><?= h($c['cert_no']) ?></p><p class="font-bold text-sm text-slate-800 leading-snug"><?= h($c['title']) ?></p></div>
                    <span class="px-2 py-1 rounded-md bg-slate-100 text-slate-600 text-[10px] font-bold whitespace-nowrap"><?= h(date('d M Y', strtotime($c['issue_date']))) ?></span></div>
                <div class="text-xs text-slate-500 space-y-1"><?php if ($c['event_name'] !== '') { ?><p><i class="fa-solid fa-calendar-days text-sky-500 w-4"></i> <?= h($c['event_name']) ?></p><?php } ?>
                    <?php if ($c['organization'] !== '') { ?><p><i class="fa-solid fa-building text-slate-400 w-4"></i> <?= h($c['organization']) ?></p><?php } ?></div>
                <div class="grid grid-cols-3 gap-2 mt-auto">
                    <button type="button" onclick="mcOpen('c<?= (int)$c['id'] ?>')" class="py-2 rounded-xl bg-slate-900 text-white text-[11px] font-bold"><i class="fa-solid fa-eye mr-1"></i>View</button>
                    <button type="button" onclick="mcQuick('c<?= (int)$c['id'] ?>','png')" class="py-2 rounded-xl bg-emerald-50 text-emerald-700 text-[11px] font-bold">PNG</button>
                    <button type="button" onclick="mcQuick('c<?= (int)$c['id'] ?>','pdf')" class="py-2 rounded-xl bg-rose-50 text-rose-600 text-[11px] font-bold">PDF</button>
                </div>
            </div>
            <?php } ?>
        </div>
        <?php } else { echo '<p class="py-10 text-center text-sm text-slate-400"><i class="fa-solid fa-award text-4xl mb-3 block text-slate-300"></i>No other certificate yet. Certificates for events and programs will appear here.<br><span class="text-xs">ইভেন্ট ও প্রোগ্রামের সার্টিফিকেট এখানে দেখা যাবে।</span></p>'; } ?>
    </div>
</section>

<!-- PREVIEW MODAL -->
<div id="mcModal" class="fixed inset-0 bg-slate-900/60 hidden items-end sm:items-center justify-center sm:p-4 z-50">
    <div class="bg-white w-full sm:max-w-4xl max-h-[94vh] flex flex-col rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-4 bg-slate-900 text-white flex justify-between items-center shrink-0"><h3 class="font-bold text-sm" id="mcTitle">Preview</h3>
            <button type="button" onclick="mcClose()" class="text-slate-400 hover:text-white p-1"><i class="fa-solid fa-xmark text-lg"></i></button></div>
        <div class="overflow-y-auto bg-slate-100 p-3 sm:p-6"><div id="mcBox" class="mx-auto"></div></div>
        <div class="shrink-0 p-3 bg-white border-t border-slate-200 grid grid-cols-3 gap-2 sm:flex sm:justify-end">
            <button type="button" onclick="mcDownload('png')" class="px-4 py-2.5 rounded-xl bg-emerald-600 text-white text-xs font-semibold"><i class="fa-solid fa-image mr-1"></i>PNG</button>
            <button type="button" onclick="mcDownload('pdf')" class="px-4 py-2.5 rounded-xl bg-rose-600 text-white text-xs font-semibold"><i class="fa-solid fa-file-pdf mr-1"></i>PDF</button>
            <button type="button" onclick="mcClose()" class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600">Close</button>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
(function () {
    var ME = <?= json_encode($ME, JSON_UNESCAPED_UNICODE) ?>, ORG = <?= json_encode($ORG, JSON_UNESCAPED_UNICODE) ?>, TH = <?= json_encode($themes) ?>;
    var CERTS = <?= json_encode($certs, JSON_UNESCAPED_UNICODE) ?>, OK = <?= $approved ? 'true' : 'false' ?>, cur = null;
    var $ = function (id) { return document.getElementById(id); };
    var MON = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
    function abs(p) { try { return new URL(p, location.href).href; } catch (e) { return p; } }
    function fdate(s) { var p = String(s || '').slice(0, 10).split('-'); return p.length === 3 ? (+p[2]) + ' ' + MON[+p[1] - 1] + ', ' + p[0] : ''; }

    /* ---------- ID card (330 x 520) ---------- */
    function idFront() {
        var logo = esc(abs(ORG.logo));
        var ph = ME.photo ? '<img src="' + esc(abs(ME.photo)) + '" style="width:120px;height:120px;border-radius:50%;object-fit:cover;border:5px solid #fff;background:#fff">'
            : '<div style="width:120px;height:120px;border-radius:50%;border:5px solid #fff;background:#d1fae5;color:#047857;font:bold 48px Arial;line-height:110px;text-align:center">' + esc((ME.name || '?').charAt(0).toUpperCase()) + '</div>';
        var row = function (l, v) { return v ? '<div style="display:flex;justify-content:space-between;gap:10px;padding:6px 0;border-bottom:1px solid #eee"><span style="color:#888">' + l + '</span><b style="text-align:right;color:#334155">' + esc(v) + '</b></div>' : ''; };
        return '<div style="width:330px;height:520px;background:#fff;border-radius:18px;overflow:hidden;font-family:Arial,sans-serif;box-shadow:0 4px 18px rgba(0,0,0,.15);position:relative;flex-shrink:0">' +
            '<div style="height:150px;background:linear-gradient(135deg,#065f46,#10b981);padding:16px;display:flex;align-items:flex-start;gap:10px;color:#fff"><img src="' + logo + '" style="width:44px;height:44px;object-fit:contain;background:#fff;border-radius:8px;padding:3px" onerror="this.style.display=\'none\'">' +
            '<div><div style="font-weight:bold;font-size:15px;line-height:1.2">' + esc(ORG.name) + '</div><div style="font-size:10px;opacity:.85;letter-spacing:2px;margin-top:3px">MEMBER ID CARD</div></div></div>' +
            '<div style="display:flex;justify-content:center;margin-top:-62px">' + ph + '</div>' +
            '<div style="text-align:center;padding:12px 20px 0"><div style="font-weight:bold;font-size:20px;color:#0f172a;line-height:1.3">' + esc(ME.name) + '</div>' +
            '<div style="display:inline-block;margin-top:8px;height:26px;line-height:26px;padding:0 16px;border-radius:13px;background:#d1fae5;color:#047857;font-size:11px;font-weight:bold;text-transform:uppercase">' + esc(ME.type) + '</div></div>' +
            '<div style="padding:14px 24px 0;font-size:12px">' + row('ID No', ME.id_no) + row('Mobile', ME.mobile) + row('Email', ME.email) + row('Blood Group', ME.blood) + row('Member Since', ME.joined ? fdate(ME.joined) : '') + '</div>' +
            '<div style="position:absolute;bottom:0;left:0;right:0;height:10px;background:linear-gradient(90deg,#065f46,#10b981,#a7f3d0)"></div></div>';
    }
    function idBack() {
        return '<div style="width:330px;height:520px;background:#fff;border-radius:18px;overflow:hidden;font-family:Arial,sans-serif;box-shadow:0 4px 18px rgba(0,0,0,.15);position:relative;flex-shrink:0;text-align:center">' +
            '<div style="height:10px;background:linear-gradient(90deg,#a7f3d0,#10b981,#065f46)"></div>' +
            '<img src="' + esc(abs(ORG.logo)) + '" style="width:90px;height:90px;object-fit:contain;margin:40px auto " onerror="this.style.display=\'none\'">' +
            '<div style="font-weight:bold;font-size:17px;color:#065f46;margin-top:10px;padding:0 20px">' + esc(ORG.name) + '</div>' +
            '<div style="font-size:12px;color:#475569;padding:12px 30px;line-height:1.6">' + esc(ORG.address) + '<br>' + esc(ORG.phone) + '<br>' + esc(ORG.email) + '</div>' +
            '<div style="margin:18px 28px;padding:12px;border:1px dashed #94a3b8;border-radius:10px;font-size:11px;color:#64748b;line-height:1.5">This card is the property of the organization. If found, please return to the above address.<br><i>এই কার্ডটি সংস্থার সম্পত্তি। পেলে উপরের ঠিকানায় ফেরত দিন।</i></div>' +
            '<div style="position:absolute;bottom:40px;left:0;right:0"><div style="width:130px;margin:0 auto;border-top:1px solid #334155;padding-top:4px;font-size:11px;color:#475569">Authorized Signature</div></div>' +
            '<div style="position:absolute;bottom:0;left:0;right:0;height:10px;background:linear-gradient(90deg,#065f46,#10b981,#a7f3d0)"></div></div>';
    }

    /* ---------- Certificate (1000 x 707) ---------- */
    function certHTML(c) {
        var t = TH[c.theme] || TH.classic, logo = esc(abs(ORG.logo)), org = c.organization || ORG.name;
        return '<div style="width:1000px;height:707px;position:relative;box-sizing:border-box;background:' + t.bg + ';border:18px solid ' + t.b1 + ';font-family:Georgia,serif;overflow:hidden">' +
            '<div style="position:absolute;top:6px;left:6px;right:6px;bottom:6px;border:4px dashed ' + t.b2 + '"></div>' +
            '<img src="' + logo + '" style="position:absolute;top:210px;left:310px;width:380px;opacity:.08" onerror="this.style.display=\'none\'">' +
            '<div style="position:relative;padding:34px 60px 0;text-align:center">' +
            '<div style="display:flex;align-items:center;justify-content:center;gap:18px"><img src="' + logo + '" style="width:96px;height:96px;object-fit:contain" onerror="this.style.display=\'none\'">' +
            '<div style="text-align:left"><div style="text-transform: uppercase; font:bold 41px Arial,sans-serif;color:' + t.a1 + '">' + esc(org) + '</div>' +
            (c.event_name ? '<div style="font:bold 20px Arial,sans-serif;letter-spacing:1px;text-transform:uppercase;color:' + t.a2 + ';margin-top:6px">' + esc(c.event_name) + '</div>' : '') + '</div></div>' +
            '<div style="font:italic 44px Georgia,serif;color:' + t.a3 + ';margin-top:34px;line-height:1.2">' + esc(c.title) + '</div>' +
            '<div style="font:26px Arial,sans-serif;color:#222;margin-top:36px">Presented To</div>' +
            '<div style="display:inline-block;min-width:520px;border-bottom:3px dotted #333;font:bold 38px Georgia,serif;color:#111;padding:20px 20px;margin-top:8px">' + esc(c.recipient_name) + '</div>' +
            '<div style="font:20px Arial,sans-serif;color:#333;margin:24px auto 0;max-width:780px;line-height:1.55">' + esc(c.description || '') + '</div></div>' +
            '<div style="position:absolute;left:60px;right:60px;bottom:34px;display:flex;justify-content:space-between;align-items:flex-end;font-family:Arial,sans-serif">' +
            '<div style="width:220px;text-align:center"><div style="border-top:2px solid #222;padding-top:6px;font-size:18px">Signature</div></div>' +
            '<div style="text-align:center;font-size:13px;color:#555">' + esc(c.cert_no || '') + '<br>' + esc(fdate(c.issue_date)) + '</div>' +
            '<div style="width:220px;text-align:center;font-size:16px">' + (c.supported_by ? 'Supported by<br><b>' + esc(c.supported_by) + '</b>' : '') + '</div></div></div>';
    }
    function memberCert() {
        var since = ME.joined ? ' since <b>' + fdate(ME.joined) + '</b>' : '';
        return certHTML({ theme: 'classic', title: 'Certificate of Membership', recipient_name: ME.name, event_name: '', organization: ORG.name, supported_by: '',
            cert_no: 'MEM-' + (ME.joined ? ME.joined.slice(0, 4) : ME.today.slice(0, 4)) + '-' + ME.id_no.replace(/\D/g, ''), issue_date: ME.today,
            description: 'is a valued ' + ME.type + ' of ' + ORG.name + (ME.joined ? ' since ' + fdate(ME.joined) : '') + ' (Membership No: ' + ME.id_no + '), and has been a dedicated part of our mission. We sincerely appreciate the support and commitment.' });
    }

    /* ---------- item registry ---------- */
    function item(key) {
        if (key === 'idcard') { return { html: '<div style="display:flex;gap:24px;padding:24px;background:#fff;width:732px;box-sizing:border-box">' + idFront() + idBack() + '</div>', w: 732, h: 568, name: ME.id_no + '_ID_Card', title: 'My ID Card • ' + ME.id_no }; }
        if (key === 'member') { return { html: memberCert(), w: 1000, h: 707, name: 'Membership_Certificate_' + ME.id_no, title: 'Membership Certificate' }; }
        var c = CERTS.filter(function (x) { return 'c' + x.id === key; })[0];
        return c ? { html: certHTML(c), w: 1000, h: 707, name: (c.cert_no || 'Certificate') + '_' + ME.id_no, title: c.title } : null;
    }
    function fit(box, html, w, h) {
        var sc = Math.min(1, (box.clientWidth - 2) / w);
        box.innerHTML = '<div style="width:' + w * sc + 'px;height:' + h * sc + 'px;margin:0 auto;overflow:hidden"><div style="transform:scale(' + sc + ');transform-origin:top left;width:' + w + 'px">' + html + '</div></div>';
    }
    function minis() {
        [['idcard', 330, 520], ['member', 1000, 707]].forEach(function (m) {
            var box = $('mini_' + m[0]); if (!box) { return; }
            var it = item(m[0]), html = m[0] === 'idcard' ? idFront() : it.html, maxW = Math.min(m[0] === 'idcard' ? 190 : 340, box.parentNode.clientWidth - 40), sc = maxW / m[1];
            box.style.width = maxW + 'px'; box.style.height = m[2] * sc + 'px';
            box.innerHTML = '<div style="transform:scale(' + sc + ');transform-origin:top left;width:' + m[1] + 'px">' + html + '</div>';
        });
    }
    minis(); var rt; window.addEventListener('resize', function () { clearTimeout(rt); rt = setTimeout(minis, 200); });

    /* ---------- modal + download ---------- */
    window.mcOpen = function (key) {
        var it = item(key); if (!it) { return; } cur = it;
        $('mcTitle').textContent = it.title; var m = $('mcModal'); m.classList.remove('hidden'); m.classList.add('flex'); document.body.style.overflow = 'hidden';
        fit($('mcBox'), it.html, it.w, it.h);
    };
    window.mcClose = function () { var m = $('mcModal'); m.classList.add('hidden'); m.classList.remove('flex'); document.body.style.overflow = ''; };
    $('mcModal').addEventListener('mousedown', function (e) { if (e.target === this) { mcClose(); } });

    function capture(it, fmt) {
        if (!window.html2canvas) { alert('Library load hoyni. Internet check korun.'); return; }
        var el = document.createElement('div'); el.style.cssText = 'position:fixed;left:-99999px;top:0;width:' + it.w + 'px;height:' + it.h + 'px'; el.innerHTML = it.html; document.body.appendChild(el);
        html2canvas(el, { scale: 2, useCORS: true, backgroundColor: '#ffffff' }).then(function (cv) {
            document.body.removeChild(el);
            if (fmt === 'png') { var a = document.createElement('a'); a.href = cv.toDataURL('image/png'); a.download = it.name + '.png'; a.click(); }
            else { var pdf = new window.jspdf.jsPDF({ orientation: it.w > it.h ? 'l' : 'p', unit: 'px', format: [it.w, it.h] }); pdf.addImage(cv.toDataURL('image/jpeg', .95), 'JPEG', 0, 0, it.w, it.h); pdf.save(it.name + '.pdf'); }
        });
    }
    window.mcDownload = function (fmt) { if (cur) { capture(cur, fmt); } };
    window.mcQuick = function (key, fmt) { var it = item(key); if (it) { capture(it, fmt); } };
})();
</script>