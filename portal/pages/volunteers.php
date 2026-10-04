<?php
/* ==========================================================
   volunteers.php  (SINGLE FILE: page + add/edit/delete/approve
   + live search + pagination + session filter)
   NOTE: ajax response clean korte hole layout a ob_start() thaka valo.
   ========================================================== */
if (session_status() === PHP_SESSION_NONE) { @session_start(); }
if (!isset($db)) { ob_start(); include 'include/header.php'; ob_end_clean(); }
mysqli_set_charset($db, "utf8mb4");

$upload_dir   = 'public/uploads/members/';        // photo folder
$profile_page = 'volunteer_profile.php';           // profile page
$user_types   = ['General Member','Associate Member','Life Member','Volunteer Member'];

if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }

/* ---------- helpers ---------- */
function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function vol_json($arr) {
    while (ob_get_level()) { ob_end_clean(); }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

function vol_where($q, $type, $status, &$types, &$params) {
    $w = "WHERE 1=1"; $types = ''; $params = [];
    if ($q !== '') {
        $w .= " AND (member_name LIKE ? OR email LIKE ? OR mobile_no LIKE ?)";
        $like = '%' . addcslashes($q, '%_\\') . '%';
        $types .= 'sss'; array_push($params, $like, $like, $like);
    }
    if ($type !== '')   { $w .= " AND user_type = ?"; $types .= 's'; $params[] = $type; }
    if ($status !== '') { $w .= " AND status = ?";    $types .= 's'; $params[] = $status; }
    return $w;
}

function vol_group($db, $col, $q, $type, $status) {
    $w = vol_where($q, $type, $status, $t, $p);
    $st = mysqli_prepare($db, "SELECT $col AS k, COUNT(*) AS c FROM users $w GROUP BY $col");
    if ($t) { mysqli_stmt_bind_param($st, $t, ...$p); }
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    $map = [];
    while ($r = mysqli_fetch_assoc($res)) { $map[$r['k']] = (int)$r['c']; }
    return $map;
}

// Search soro somoy apply hoy. Category card = search+status, Status card = search+category, Total = sob filter
function vol_stats($db, $q, $type, $status, $user_types) {
    $catRaw = vol_group($db, 'user_type', $q, '', $status);
    $staRaw = vol_group($db, 'status', $q, $type, '');
    $all    = array_sum(vol_group($db, 'status', $q, $type, $status));
    $cats = [];
    foreach ($user_types as $t) { $cats[$t] = $catRaw[$t] ?? 0; }
    $active = $staRaw['Active'] ?? 0;
    return ['total' => $all, 'Active' => $active, 'Pending' => array_sum($staRaw) - $active, 'cats' => $cats];
}

function vol_upload($file, $dir, &$err) {
    if (!is_array($file) || $file['error'] === UPLOAD_ERR_NO_FILE) { return ''; }
    if ($file['error'] !== 0) { $err = 'ছবি আপলোড করা যায়নি।'; return false; }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png']) || !@getimagesize($file['tmp_name'])) {
        $err = 'ছবি শুধু JPG বা PNG হতে হবে।'; return false;
    }
    if ($file['size'] > 2 * 1024 * 1024) { $err = 'ছবির সাইজ সর্বোচ্চ 2MB।'; return false; }
    if (!is_dir($dir)) { mkdir($dir, 0755, true); }
    $name = 'member_' . time() . '_' . random_int(1000, 9999) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) { $err = 'ছবি সেভ করা যায়নি।'; return false; }
    return $name;
}

function vol_unlink($dir, $photo) {
    if ($photo != '' && is_file($dir . basename($photo))) { @unlink($dir . basename($photo)); }
}

function vol_cat_style($type) {
    $map = [
        'General Member'   => ['badge' => 'bg-sky-50 text-sky-700',         'icon' => 'bg-sky-50 text-sky-600',         'ring' => 'ring-sky-500',     'fa' => 'fa-user-check'],
        'Associate Member' => ['badge' => 'bg-violet-50 text-violet-700',   'icon' => 'bg-violet-50 text-violet-600',   'ring' => 'ring-violet-500',  'fa' => 'fa-user-gear'],
        'Life Member'      => ['badge' => 'bg-fuchsia-50 text-fuchsia-700', 'icon' => 'bg-fuchsia-50 text-fuchsia-600', 'ring' => 'ring-fuchsia-500', 'fa' => 'fa-crown'],
        'Volunteer Member' => ['badge' => 'bg-emerald-50 text-emerald-700', 'icon' => 'bg-emerald-50 text-emerald-600', 'ring' => 'ring-emerald-500', 'fa' => 'fa-hand-holding-heart'],
    ];
    return $map[$type] ?? ['badge' => 'bg-slate-100 text-slate-600', 'icon' => 'bg-slate-100 text-slate-500', 'ring' => 'ring-slate-400', 'fa' => 'fa-user'];
}

function vol_row($r, $dir, $profile_page) {
    $photo = ($r['photo'] != '' && is_file($dir . basename($r['photo'])))
        ? '<img src="' . h($dir . $r['photo']) . '" class="w-9 h-9 rounded-full object-cover shrink-0">'
        : '<div class="w-9 h-9 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold shrink-0">' . h(mb_strtoupper(mb_substr($r['member_name'], 0, 1))) . '</div>';
    $status = $r['status'] == 'Active'
        ? '<span class="px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 font-bold text-[10px]">Active</span>'
        : '<span class="px-2.5 py-1 rounded-md bg-amber-50 text-amber-700 font-bold text-[10px]">Pending</span>';
    $approve = $r['status'] != 'Active'
        ? '<button type="button" data-act="approve" data-id="' . (int)$r['id'] . '" title="Approve" class="p-1.5 text-slate-400 hover:text-emerald-600"><i class="fa-solid fa-circle-check"></i></button>' : '';
    return '<tr class="hover:bg-slate-50/80">
      <td class="p-4"><div class="flex items-center gap-3">' . $photo . '<div class="min-w-0">
        <a href="' . h($profile_page) . '?id=' . (int)$r['id'] . '" class="font-semibold text-slate-800 hover:text-emerald-600 hover:underline">' . h($r['member_name']) . '</a>
        <span class="block text-[10px] text-slate-400 truncate">' . h($r['email']) . '</span></div></div></td>
      <td class="p-4"><span class="px-2.5 py-1 rounded-md font-bold text-[10px] ' . vol_cat_style($r['user_type'])['badge'] . '">' . h($r['user_type']) . '</span></td>
      <td class="p-4">+88' . h($r['mobile_no']) . '</td>
      <td class="p-4">' . $status . '</td>
      <td class="p-4 text-right"><div class="flex items-center justify-end gap-1">' . $approve . '
        <button type="button" data-act="edit" data-id="' . (int)$r['id'] . '" title="Edit" class="p-1.5 text-slate-400 hover:text-sky-600"><i class="fa-solid fa-pen-to-square"></i></button>
        <button type="button" data-act="delete" data-id="' . (int)$r['id'] . '" data-name="' . h($r['member_name']) . '" title="Delete" class="p-1.5 text-slate-400 hover:text-rose-600"><i class="fa-solid fa-trash"></i></button>
      </div></td></tr>';
}

function vol_pager($page, $pages) {
    if ($pages <= 1) { return ''; }
    $btn = function ($p, $label, $active = false, $dis = false) {
        $c = $active ? 'bg-emerald-600 text-white border-emerald-600' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50';
        if ($dis) { $c = 'bg-slate-50 text-slate-300 border-slate-100 cursor-not-allowed'; }
        return '<button type="button" ' . ($dis ? 'disabled' : 'data-page="' . $p . '"') . ' class="min-w-8 h-8 px-2 rounded-lg border text-xs font-semibold ' . $c . '">' . $label . '</button>';
    };
    $out = $btn(max(1, $page - 1), '&lsaquo;', false, $page == 1);
    $set = [1, $pages];
    for ($i = $page - 2; $i <= $page + 2; $i++) { if ($i > 0 && $i <= $pages) { $set[] = $i; } }
    $set = array_unique($set); sort($set);
    $prev = 0;
    foreach ($set as $p) {
        if ($p - $prev > 1) { $out .= '<span class="px-1 text-slate-400">…</span>'; }
        $out .= $btn($p, $p, $p == $page);
        $prev = $p;
    }
    return $out . $btn(min($pages, $page + 1), '&rsaquo;', false, $page == $pages);
}

/* ==========================================================
   AJAX HANDLER
   ========================================================== */
$act = $_GET['act'] ?? '';
if ($act !== '') {

    // POST action gulor jonno CSRF check
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $tok = $_SERVER['HTTP_X_CSRF'] ?? '';
        if (!hash_equals($_SESSION['csrf'], $tok)) { vol_json(['ok' => false, 'msg' => 'Invalid session. Page reload korun.']); }
    }

    /* ----- LIST (search + filter + pagination, session a save) ----- */
    if ($act === 'list') {
        $q      = trim($_GET['vs'] ?? '');
        $type   = trim($_GET['vt'] ?? '');
        $status = trim($_GET['vst'] ?? '');
        $per    = (int)($_GET['per'] ?? 10);
        if (!in_array($per, [10, 20, 50, 100])) { $per = 10; }
        $page   = max(1, (int)($_GET['pg'] ?? 1));

        $where = vol_where($q, $type, $status, $types, $params);

        $st = mysqli_prepare($db, "SELECT COUNT(*) FROM users $where");
        if ($types) { mysqli_stmt_bind_param($st, $types, ...$params); }
        mysqli_stmt_execute($st); mysqli_stmt_bind_result($st, $total); mysqli_stmt_fetch($st); mysqli_stmt_close($st);

        $pages = max(1, (int)ceil($total / $per));
        if ($page > $pages) { $page = $pages; }
        $offset = ($page - 1) * $per;

        // session a filter rakha (clear na kora porjonto thakbe)
        $_SESSION['vol_filter'] = ['q' => $q, 'type' => $type, 'status' => $status, 'per' => $per, 'page' => $page];

        $st = mysqli_prepare($db, "SELECT id, photo, member_name, email, mobile_no, user_type, status FROM users $where ORDER BY id DESC LIMIT ? OFFSET ?");
        mysqli_stmt_bind_param($st, $types . 'ii', ...array_merge($params, [$per, $offset]));
        mysqli_stmt_execute($st);
        $res = mysqli_stmt_get_result($st);
        $html = '';
        while ($r = mysqli_fetch_assoc($res)) { $html .= vol_row($r, $upload_dir, $profile_page); }
        if ($html === '') { $html = '<tr><td colspan="5" class="p-6 text-center text-slate-400">কোনো ডাটা পাওয়া যায়নি।</td></tr>'; }

        vol_json([
            'ok' => true, 'rows' => $html, 'pager' => vol_pager($page, $pages), 'page' => $page,
            'total' => (int)$total, 'from' => $total ? $offset + 1 : 0, 'to' => min($offset + $per, $total),
            'stats' => vol_stats($db, $q, $type, $status, $user_types),
            'filtered' => ($q !== '' || $type !== '' || $status !== ''),
        ]);
    }

    /* ----- CLEAR FILTER ----- */
    if ($act === 'clear') {
        unset($_SESSION['vol_filter']);
        vol_json(['ok' => true]);
    }

    /* ----- GET one (edit modal) ----- */
    if ($act === 'get') {
        $id = (int)($_GET['id'] ?? 0);
        $st = mysqli_prepare($db, "SELECT * FROM users WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($st, 'i', $id);
        mysqli_stmt_execute($st);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
        if (!$row) { vol_json(['ok' => false, 'msg' => 'User পাওয়া যায়নি।']); }
        unset($row['password']);
        $row['photo_url'] = ($row['photo'] != '' && is_file($upload_dir . basename($row['photo']))) ? $upload_dir . $row['photo'] : '';
        vol_json(['ok' => true, 'data' => $row]);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { vol_json(['ok' => false, 'msg' => 'Invalid request.']); }

    /* ----- APPROVE ----- */
    if ($act === 'approve') {
        $id = (int)($_POST['id'] ?? 0);
        $st = mysqli_prepare($db, "UPDATE users SET status = 'Active' WHERE id = ?");
        mysqli_stmt_bind_param($st, 'i', $id);
        mysqli_stmt_execute($st);
        vol_json(['ok' => true, 'msg' => 'Approve করা হয়েছে।']);
    }

    /* ----- DELETE (photo soho) ----- */
    if ($act === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        $st = mysqli_prepare($db, "SELECT photo FROM users WHERE id = ? LIMIT 1");
        mysqli_stmt_bind_param($st, 'i', $id);
        mysqli_stmt_execute($st);
        $old = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
        if (!$old) { vol_json(['ok' => false, 'msg' => 'User পাওয়া যায়নি।']); }
        $st = mysqli_prepare($db, "DELETE FROM users WHERE id = ?");
        mysqli_stmt_bind_param($st, 'i', $id);
        if (mysqli_stmt_execute($st)) {
            vol_unlink($upload_dir, $old['photo']);
            vol_json(['ok' => true, 'msg' => 'ডিলিট করা হয়েছে।']);
        }
        vol_json(['ok' => false, 'msg' => 'ডিলিট করা যায়নি।']);
    }

    /* ----- SAVE (add + edit) ----- */
    if ($act === 'save') {
        $id = (int)($_POST['id'] ?? 0);
        $keys = ['member_name', 'mother_name', 'father_husband_name', 'dob', 'gender', 'id_type', 'id_number',
                 'qualification', 'mobile_no', 'email', 'present_address', 'permanent_address', 'other_info', 'user_type'];
        $f = [];
        foreach ($keys as $k) { $f[$k] = trim($_POST[$k] ?? ''); }

        foreach (['member_name', 'mother_name', 'father_husband_name', 'dob', 'gender', 'id_type', 'id_number', 'mobile_no', 'present_address', 'permanent_address', 'user_type'] as $k) {
            if ($f[$k] === '') { vol_json(['ok' => false, 'msg' => 'সব প্রয়োজনীয় (*) তথ্য পূরণ করুন।']); }
        }
        if (!preg_match('/^[0-9]{11}$/', $f['mobile_no'])) { vol_json(['ok' => false, 'msg' => 'মোবাইল নম্বর ১১ ডিজিটের হতে হবে।']); }
        if ($f['email'] !== '' && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) { vol_json(['ok' => false, 'msg' => 'সঠিক ইমেইল দিন।']); }

        if (!in_array($f['user_type'], $user_types)) { vol_json(['ok' => false, 'msg' => 'সঠিক Category নির্বাচন করুন।']); }

        // duplicate mobile
        $st = mysqli_prepare($db, "SELECT id FROM users WHERE mobile_no = ? AND id <> ? LIMIT 1");
        mysqli_stmt_bind_param($st, 'si', $f['mobile_no'], $id);
        mysqli_stmt_execute($st); mysqli_stmt_store_result($st);
        if (mysqli_stmt_num_rows($st) > 0) { vol_json(['ok' => false, 'msg' => 'এই মোবাইল নম্বর আগেই ব্যবহার করা হয়েছে।']); }
        mysqli_stmt_close($st);

        $err = '';
        $new = vol_upload($_FILES['photo'] ?? null, $upload_dir, $err);
        if ($new === false) { vol_json(['ok' => false, 'msg' => $err]); }

        if ($id > 0) {
            $st = mysqli_prepare($db, "SELECT photo FROM users WHERE id = ? LIMIT 1");
            mysqli_stmt_bind_param($st, 'i', $id);
            mysqli_stmt_execute($st);
            $old = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
            if (!$old) { vol_unlink($upload_dir, $new); vol_json(['ok' => false, 'msg' => 'User পাওয়া যায়নি।']); }

            $photo = ($new !== '') ? $new : $old['photo'];
            $st = mysqli_prepare($db, "UPDATE users SET photo=?, member_name=?, mother_name=?, father_husband_name=?, dob=?, gender=?, id_type=?, id_number=?,
                qualification=?, mobile_no=?, email=?, present_address=?, permanent_address=?, other_info=?, user_type=? WHERE id=?");
            $vals = array_merge([$photo], array_values($f), [$id]);
            mysqli_stmt_bind_param($st, str_repeat('s', 15) . 'i', ...$vals);
            if (mysqli_stmt_execute($st)) {
                if ($new !== '') { vol_unlink($upload_dir, $old['photo']); }   // old image delete
                vol_json(['ok' => true, 'msg' => 'আপডেট করা হয়েছে।']);
            }
            vol_unlink($upload_dir, $new);
            vol_json(['ok' => false, 'msg' => 'আপডেট করা যায়নি।']);
        }

        // INSERT
        $st = mysqli_prepare($db, "INSERT INTO users (photo, member_name, mother_name, father_husband_name, dob, gender, id_type, id_number,
            qualification, mobile_no, email, present_address, permanent_address, other_info, user_type, status, password)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)");
        $vals = array_merge([$new], array_values($f), ['Active', md5('12345')]);
        mysqli_stmt_bind_param($st, str_repeat('s', 17), ...$vals);
        if (mysqli_stmt_execute($st)) { vol_json(['ok' => true, 'msg' => 'নতুন এন্ট্রি সেভ হয়েছে।']); }
        vol_unlink($upload_dir, $new);
        vol_json(['ok' => false, 'msg' => 'ডাটাবেসে সেভ করতে সমস্যা হয়েছে।']);
    }

    vol_json(['ok' => false, 'msg' => 'Unknown action.']);
}

/* ---------- Page load: session theke filter ---------- */
$saved = $_SESSION['vol_filter'] ?? ['q' => '', 'type' => '', 'status' => '', 'per' => 10, 'page' => 1];
if (!in_array($saved['type'], $user_types)) { $saved['type'] = ''; }
if (!in_array($saved['status'], ['', 'Active', 'Pending'])) { $saved['status'] = ''; }
$inp = 'w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500';
$lbl = 'block text-xs font-bold uppercase text-slate-500 mb-1';
?>

<section class="page-content space-y-6">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Members & Volunteers</h2>
            <p class="text-xs text-slate-500 mt-0.5">Real-time stats and foundation activity metrics.</p>
        </div>
        <button type="button" onclick="volOpenForm()"
            class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-emerald-600/20 flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Add New
        </button>
    </div>

    <!-- Stats: status wise -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
        <?php
        $statCards = [['total', 'Total', 'slate', 'fa-users', ''], ['Active', 'Active', 'emerald', 'fa-circle-check', 'Active'], ['Pending', 'Pending', 'amber', 'fa-clock', 'Pending']];
        foreach ($statCards as $c) { ?>
        <div <?php echo $c[4] ? 'data-fstatus="' . $c[4] . '" data-ring="ring-' . $c[2] . '-500"' : ''; ?> class="p-5 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4 <?php echo $c[4] ? 'cursor-pointer hover:shadow-md transition' : ''; ?>">
            <div class="w-12 h-12 rounded-xl bg-<?php echo $c[2]; ?>-50 text-<?php echo $c[2]; ?>-600 flex items-center justify-center text-xl shrink-0"><i class="fa-solid <?php echo $c[3]; ?>"></i></div>
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase"><?php echo $c[1]; ?></p>
                <h3 class="text-2xl font-bold text-slate-800 mt-0.5" data-stat="<?php echo $c[0]; ?>">0</h3>
            </div>
        </div>
        <?php } ?>
    </div>

    <!-- Stats: category wise (each category has its own color) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php foreach ($user_types as $t) { $cs = vol_cat_style($t); ?>
        <div data-ftype="<?php echo h($t); ?>" data-ring="<?php echo $cs['ring']; ?>" class="p-5 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4 cursor-pointer hover:shadow-md transition">
            <div class="w-12 h-12 rounded-xl <?php echo $cs['icon']; ?> flex items-center justify-center text-xl shrink-0"><i class="fa-solid <?php echo $cs['fa']; ?>"></i></div>
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase"><?php echo h($t); ?></p>
                <h3 class="text-2xl font-bold text-slate-800 mt-0.5" data-stat="cat:<?php echo h($t); ?>">0</h3>
            </div>
        </div>
        <?php } ?>
    </div>
    <!-- ring class safelist (Tailwind CDN) -->
    <div class="hidden ring-2 ring-sky-500 ring-violet-500 ring-fuchsia-500 ring-emerald-500 ring-amber-500 ring-slate-400 ring-slate-500"></div>

    <!-- Table card -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 space-y-4">
            <div class="flex items-center justify-between">
                <h3 class="font-bold text-slate-800 text-sm">Recent Activity & Submissions</h3>
                <span class="text-xs text-emerald-600 font-medium">Live Updates</span>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-12 gap-3">
                <div class="md:col-span-5 relative">
                    <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                    <input type="text" id="fSearch" value="<?php echo h($saved['q']); ?>" placeholder="Search name, email or phone..."
                        class="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-emerald-500">
                </div>
                <select id="fType" class="md:col-span-2 <?php echo $inp; ?>">
                    <option value="">All Categories</option>
                    <?php foreach ($user_types as $t) { echo '<option value="' . h($t) . '"' . ($saved['type'] === $t ? ' selected' : '') . '>' . h($t) . '</option>'; } ?>
                </select>
                <select id="fStatus" class="md:col-span-2 <?php echo $inp; ?>">
                    <option value="">All Status</option>
                    <option value="Active" <?php echo $saved['status'] === 'Active' ? 'selected' : ''; ?>>Active</option>
                    <option value="Pending" <?php echo $saved['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                </select>
                <select id="fPer" class="md:col-span-1 <?php echo $inp; ?>">
                    <?php foreach ([10, 20, 50, 100] as $n) { echo '<option value="' . $n . '"' . ((int)$saved['per'] === $n ? ' selected' : '') . '>' . $n . '</option>'; } ?>
                </select>
                <button type="button" id="fClear" onclick="volClear()"
                    class="md:col-span-2 hidden px-3 py-2.5 border border-rose-200 text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-xl text-xs font-semibold items-center justify-center gap-1.5">
                    <i class="fa-solid fa-filter-circle-xmark"></i> Remove Filter
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-700 uppercase font-bold border-b border-slate-200">
                    <tr>
                        <th class="p-4">Applicant / Member</th>
                        <th class="p-4">Category</th>
                        <th class="p-4">Contact</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody id="volBody" class="divide-y divide-slate-100">
                    <tr><td colspan="5" class="p-6 text-center text-slate-400">Loading...</td></tr>
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
            <span id="volInfo" class="text-xs text-slate-500"></span>
            <div id="volPager" class="flex items-center gap-1 flex-wrap"></div>
        </div>
    </div>
</section>

<!-- ADD / EDIT MODAL -->
<div id="volFormModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center p-4 z-50">
    <div class="bg-white w-full max-w-2xl max-h-[92vh] flex flex-col rounded-2xl shadow-2xl overflow-hidden border border-slate-100">
        <div class="p-5 bg-slate-900 text-white flex justify-between items-center shrink-0">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <i class="fa-solid fa-square-plus text-emerald-400" id="volFormIcon"></i>
                <span id="volFormTitle">Add Volunteer</span>
            </h3>
            <button type="button" onclick="volCloseForm()" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>

        <form id="volForm" class="p-6 space-y-4 overflow-y-auto" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" name="id" id="f_id" value="0">

            <div class="flex items-center gap-4">
                <div id="f_prevBox" class="w-20 h-24 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 flex items-center justify-center overflow-hidden shrink-0">
                    <i class="fa-solid fa-camera text-slate-400 text-xl" id="f_prevIcon"></i>
                    <img id="f_prev" class="hidden w-full h-full object-cover">
                </div>
                <div>
                    <label class="<?php echo $lbl; ?>">Photo (JPG/PNG, max 2MB)</label>
                    <input type="file" name="photo" id="f_photo" accept="image/png,image/jpeg" class="text-xs">
                    <p class="text-[10px] text-slate-400 mt-1" id="f_photoHint"></p>
                </div>
            </div>

            <div>
                <label class="<?php echo $lbl; ?>">Full Name *</label>
                <input type="text" name="member_name" id="f_member_name" required class="<?php echo $inp; ?>">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div><label class="<?php echo $lbl; ?>">Mother's Name *</label><input type="text" name="mother_name" id="f_mother_name" required class="<?php echo $inp; ?>"></div>
                <div><label class="<?php echo $lbl; ?>">Father / Husband *</label><input type="text" name="father_husband_name" id="f_father_husband_name" required class="<?php echo $inp; ?>"></div>
                <div><label class="<?php echo $lbl; ?>">Date of Birth *</label><input type="date" name="dob" id="f_dob" required class="<?php echo $inp; ?>"></div>
                <div><label class="<?php echo $lbl; ?>">Gender *</label>
                    <select name="gender" id="f_gender" required class="<?php echo $inp; ?>">
                        <option value="Male">Male</option><option value="Female">Female</option><option value="Other">Other</option>
                    </select></div>
                <div><label class="<?php echo $lbl; ?>">ID Type *</label>
                    <select name="id_type" id="f_id_type" required class="<?php echo $inp; ?>">
                        <option value="NID">NID</option><option value="Passport">Passport</option><option value="Birth Certificate">Birth Certificate</option>
                    </select></div>
                <div><label class="<?php echo $lbl; ?>">ID Number *</label><input type="text" name="id_number" id="f_id_number" required class="<?php echo $inp; ?>"></div>
                <div><label class="<?php echo $lbl; ?>">Phone Number *</label><input type="text" name="mobile_no" id="f_mobile_no" required pattern="[0-9]{11}" maxlength="11" placeholder="01XXXXXXXXX" class="<?php echo $inp; ?>"></div>
                <div><label class="<?php echo $lbl; ?>">Email</label><input type="email" name="email" id="f_email" class="<?php echo $inp; ?>"></div>
                <div><label class="<?php echo $lbl; ?>">Category / Role *</label>
                    <select name="user_type" id="f_user_type" required class="<?php echo $inp; ?>">
                        <?php foreach ($user_types as $t) { echo '<option value="' . h($t) . '">' . h($t) . '</option>'; } ?>
                    </select></div>
                <div><label class="<?php echo $lbl; ?>">Qualification</label><input type="text" name="qualification" id="f_qualification" class="<?php echo $inp; ?>"></div>
            </div>

            <div><label class="<?php echo $lbl; ?>">Present Address *</label><textarea name="present_address" id="f_present_address" rows="2" required class="<?php echo $inp; ?>"></textarea></div>
            <div><label class="<?php echo $lbl; ?>">Permanent Address *</label><textarea name="permanent_address" id="f_permanent_address" rows="2" required class="<?php echo $inp; ?>"></textarea></div>
            <div><label class="<?php echo $lbl; ?>">Other Info</label><input type="text" name="other_info" id="f_other_info" class="<?php echo $inp; ?>"></div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="volCloseForm()" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="submit" id="f_submit" class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700 shadow-sm">Save Entry</button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE MODAL -->
<div id="volDeleteModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center p-4 z-50">
    <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl overflow-hidden border border-slate-100 p-6 text-center space-y-4">
        <div class="w-14 h-14 mx-auto rounded-full bg-rose-50 text-rose-600 flex items-center justify-center text-2xl"><i class="fa-solid fa-trash"></i></div>
        <div>
            <h3 class="font-bold text-slate-800">Delete this entry?</h3>
            <p class="text-xs text-slate-500 mt-1"><b id="delName"></b> এবং তার ছবি স্থায়ীভাবে মুছে যাবে।</p>
        </div>
        <div class="flex justify-center gap-2">
            <button type="button" onclick="volModal('volDeleteModal', false)" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
            <button type="button" id="delConfirm" class="px-4 py-2 bg-rose-600 text-white rounded-xl text-xs font-semibold hover:bg-rose-700">Yes, Delete</button>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="volToast" class="fixed top-5 right-5 z-[60] hidden px-4 py-3 rounded-xl text-xs font-semibold shadow-lg border"></div>

<script>
(function () {
    var CSRF = <?php echo json_encode($_SESSION['csrf']); ?>;
    var state = <?php echo json_encode($saved); ?>;   // session theke
    var $ = function (id) { return document.getElementById(id); };
    var seq = 0, timer = null, delId = 0;

    function url(act, params) {
        var u = new URL(location.href);
        ['act', 'vs', 'vt', 'vst', 'pg', 'per', 'id'].forEach(function (k) { u.searchParams.delete(k); });
        u.searchParams.set('act', act);
        for (var k in (params || {})) { u.searchParams.set(k, params[k]); }
        return u.toString();
    }
    function post(act, fd) {
        return fetch(url(act), { method: 'POST', body: fd, headers: { 'X-CSRF': CSRF } }).then(function (r) { return r.json(); });
    }
    function toast(msg, ok) {
        var t = $('volToast');
        t.className = 'fixed top-5 right-5 z-[60] px-4 py-3 rounded-xl text-xs font-semibold shadow-lg border ' +
            (ok ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-rose-50 border-rose-200 text-rose-700');
        t.textContent = msg;
        clearTimeout(t._t); t._t = setTimeout(function () { t.classList.add('hidden'); }, 3500);
    }
    window.volModal = function (id, show) {
        var m = $(id);
        m.classList.toggle('hidden', !show);
        m.classList.toggle('flex', show);
    };

    function setStat(k, v) {
        document.querySelectorAll('[data-stat="' + k + '"]').forEach(function (e) { e.textContent = v; });
    }
    function markCards() {
        document.querySelectorAll('[data-ftype],[data-fstatus]').forEach(function (e) {
            var on = e.dataset.ftype ? e.dataset.ftype === state.type : e.dataset.fstatus === state.status;
            ['ring-2', e.dataset.ring].forEach(function (r) { if (r) { e.classList.toggle(r, on); } });
        });
    }
    // card click = filter toggle
    document.addEventListener('click', function (e) {
        var t = e.target.closest('[data-ftype]'), st = e.target.closest('[data-fstatus]');
        if (t) { state.type = state.type === t.dataset.ftype ? '' : t.dataset.ftype; $('fType').value = state.type; state.page = 1; load(); }
        if (st) { state.status = state.status === st.dataset.fstatus ? '' : st.dataset.fstatus; $('fStatus').value = state.status; state.page = 1; load(); }
    });

    /* ---------- LIST ---------- */
    function load() {
        var my = ++seq;
        fetch(url('list', { vs: state.q, vt: state.type, vst: state.status, pg: state.page, per: state.per }))
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (my !== seq || !d.ok) { return; }
                state.page = d.page;
                $('volBody').innerHTML = d.rows;
                $('volPager').innerHTML = d.pager;
                $('volInfo').textContent = d.total ? 'Showing ' + d.from + '–' + d.to + ' of ' + d.total : 'No results';
                setStat('total', d.stats.total); setStat('Active', d.stats.Active); setStat('Pending', d.stats.Pending);
                for (var c in d.stats.cats) { setStat('cat:' + c, d.stats.cats[c]); }
                markCards();
                var c = $('fClear');
                c.classList.toggle('hidden', !d.filtered);
                c.classList.toggle('flex', d.filtered);
            })
            .catch(function () { $('volBody').innerHTML = '<tr><td colspan="5" class="p-6 text-center text-rose-500">Load করা যায়নি।</td></tr>'; });
    }

    $('fSearch').addEventListener('input', function () {
        clearTimeout(timer);
        var v = this.value;
        timer = setTimeout(function () { state.q = v.trim(); state.page = 1; load(); }, 300);
    });
    $('fType').addEventListener('change', function () { state.type = this.value; state.page = 1; load(); });
    $('fStatus').addEventListener('change', function () { state.status = this.value; state.page = 1; load(); });
    $('fPer').addEventListener('change', function () { state.per = this.value; state.page = 1; load(); });
    $('volPager').addEventListener('click', function (e) {
        var b = e.target.closest('[data-page]');
        if (b) { state.page = parseInt(b.dataset.page, 10); load(); }
    });

    // Filter remove: session theke muche felbe
    window.volClear = function () {
        fetch(url('clear')).then(function () {
            state = { q: '', type: '', status: '', per: state.per, page: 1 };
            $('fSearch').value = ''; $('fType').value = ''; $('fStatus').value = '';
            load();
        });
    };

    /* ---------- TABLE ACTIONS ---------- */
    $('volBody').addEventListener('click', function (e) {
        var b = e.target.closest('[data-act]');
        if (!b) { return; }
        var id = b.dataset.id, act = b.dataset.act;

        if (act === 'edit') { volOpenForm(id); }

        if (act === 'delete') {
            delId = id; $('delName').textContent = b.dataset.name;
            volModal('volDeleteModal', true);
        }

        if (act === 'approve') {
            var fd = new FormData(); fd.append('id', id);
            post('approve', fd).then(function (d) { toast(d.msg, d.ok); if (d.ok) { load(); } });
        }
    });

    $('delConfirm').addEventListener('click', function () {
        var fd = new FormData(); fd.append('id', delId);
        post('delete', fd).then(function (d) {
            toast(d.msg, d.ok); volModal('volDeleteModal', false); if (d.ok) { load(); }
        });
    });

    /* ---------- ADD / EDIT FORM ---------- */
    var fields = ['member_name', 'mother_name', 'father_husband_name', 'dob', 'gender', 'id_type', 'id_number',
                  'qualification', 'mobile_no', 'email', 'present_address', 'permanent_address', 'other_info', 'user_type'];

    function setPreview(src) {
        $('f_prev').classList.toggle('hidden', !src);
        $('f_prevIcon').classList.toggle('hidden', !!src);
        $('f_prev').src = src || '';
    }

    window.volOpenForm = function (id) {
        $('volForm').reset(); $('f_id').value = 0; setPreview('');
        $('f_photoHint').textContent = '';
        $('volFormTitle').textContent = 'Add Volunteer';
        $('f_user_type').value = 'General Member';
        if (!id) { volModal('volFormModal', true); return; }

        $('volFormTitle').textContent = 'Edit Member';
        fetch(url('get', { id: id })).then(function (r) { return r.json(); }).then(function (d) {
            if (!d.ok) { toast(d.msg, false); return; }
            $('f_id').value = d.data.id;
            fields.forEach(function (k) { if ($('f_' + k)) { $('f_' + k).value = d.data[k] || ''; } });
            setPreview(d.data.photo_url);
            $('f_photoHint').textContent = d.data.photo_url ? 'নতুন ছবি দিলে পুরনো ছবি ডিলিট হয়ে যাবে।' : '';
            volModal('volFormModal', true);
        });
    };
    window.volCloseForm = function () { volModal('volFormModal', false); };

    $('f_photo').addEventListener('change', function () {
        var f = this.files[0];
        if (!f) { return; }
        if (f.size > 2 * 1024 * 1024) { toast('ছবির সাইজ সর্বোচ্চ 2MB।', false); this.value = ''; return; }
        var r = new FileReader();
        r.onload = function (e) { setPreview(e.target.result); };
        r.readAsDataURL(f);
    });

    $('volForm').addEventListener('submit', function (e) {
        e.preventDefault();
        var btn = $('f_submit'); btn.disabled = true; btn.textContent = 'Saving...';
        post('save', new FormData(this)).then(function (d) {
            toast(d.msg, d.ok);
            if (d.ok) { volCloseForm(); load(); }
        }).catch(function () { toast('Server error.', false); })
          .finally(function () { btn.disabled = false; btn.textContent = 'Save Entry'; });
    });

    ['volFormModal', 'volDeleteModal'].forEach(function (id) {
        $(id).addEventListener('mousedown', function (e) { if (e.target === this) { volModal(id, false); } });
    });

    load();
})();
</script>