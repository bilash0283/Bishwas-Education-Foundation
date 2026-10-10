<?php
/*
 * EXPENSES PAGE - PHP 7.2 compatible, beginner friendly version
 *
 * Live server-e ja problem hochhilo (ebong fix):
 * 1. mysqli_stmt_get_result()  -> mysqlnd na thakle kaj kore na (list/stats/view khali ashto).
 *                                 Ekhon exp_rows() nijei bind_result diye data ane.
 * 2. session_start()           -> $_SESSION check er AGE session start korte hobe. Ekhon sobar upore.
 * 3. const EXP_FROM            -> file duibar load hole "already defined" warning dey. Ekhon define() + defined() check.
 * 4. global $CATS / $statuses  -> include kora file-er bhitor theke global kaj kore na.
 *                                 Ekhon exp_cats() / exp_statuses() function theke ane.
 * 5. Query fail hole silent    -> ager code error dhorto na. Ekhon exp_exec() error hole Exception dey,
 *                                 tai transaction rollback hoy ebong error message dekha jay.
 * 6. $db                       -> include file-er bhitor 'global $db' lage.
 * 7. exp_row()                 -> $is_admin ager code-e kokhono true hoto na (list theke pass hoto na).
 *                                 Ekhon session theke ana hoy, ebong toggle/delete server-e-o Admin chhara block kora hoyeche.
 *
 * IMPORTANT: File ta "UTF-8 (without BOM)" encoding-e save korben.
 */

// ---------- 0. Output buffer & Session (sobar upore) ----------
if (!headers_sent()) {
    ob_start();
}
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

/* ==========================================================
   CONFIG (category, status, form fields)
   ========================================================== */

// category => [badge, icon-box, ring, fontawesome]
function exp_cats()
{
    return array(
        'Medical' => array('bg-rose-50 text-rose-700', 'bg-rose-50 text-rose-600', 'ring-rose-500', 'fa-briefcase-medical'),
        'Education' => array('bg-sky-50 text-sky-700', 'bg-sky-50 text-sky-600', 'ring-sky-500', 'fa-graduation-cap'),
        'Food' => array('bg-amber-50 text-amber-700', 'bg-amber-50 text-amber-600', 'ring-amber-500', 'fa-utensils'),
        'Clothing' => array('bg-violet-50 text-violet-700', 'bg-violet-50 text-violet-600', 'ring-violet-500', 'fa-shirt'),
        'Shelter' => array('bg-orange-50 text-orange-700', 'bg-orange-50 text-orange-600', 'ring-orange-500', 'fa-house'),
        'Transport' => array('bg-teal-50 text-teal-700', 'bg-teal-50 text-teal-600', 'ring-teal-500', 'fa-bus'),
        'Emergency Relief' => array('bg-red-50 text-red-700', 'bg-red-50 text-red-600', 'ring-red-500', 'fa-triangle-exclamation'),
        'Event' => array('bg-fuchsia-50 text-fuchsia-700', 'bg-fuchsia-50 text-fuchsia-600', 'ring-fuchsia-500', 'fa-calendar-days'),
        'Administrative' => array('bg-slate-100 text-slate-700', 'bg-slate-100 text-slate-600', 'ring-slate-500', 'fa-building'),
        'Other' => array('bg-emerald-50 text-emerald-700', 'bg-emerald-50 text-emerald-600', 'ring-emerald-500', 'fa-ellipsis'),
    );
}

function exp_statuses()
{
    return array('Pending', 'Complete');
}

$exp_dir = 'uploads/expenses/';      // receipt (index.php folder er bhetor)
$rec_dir = 'uploads/recipients/';    // recipient photo
$act_prefix = '';                       // activities.image er path prefix (admin folder theke)

$statuses = exp_statuses();
$methods = array('Cash', 'bKash', 'Nagad', 'Rocket', 'Upay', 'Bank', 'Cheque', 'Other');
$CATS = exp_cats();
$per_opts = array(10, 20, 50, 100);

// Recipient (সেবা গ্রহণকারীর আবেদন পত্র) fields: key => [label, type, span]
$RF = array(
    'member_name' => array("Member's Name <i>/ সদস্যের নাম</i> *", 'text', 'sm:col-span-2'),
    'mother_name' => array("Mother's Name <i>/ মাতার নাম</i>", 'text', ''),
    'father_husband_name' => array("Father/Husband's Name <i>/ পিতা/স্বামীর নাম</i>", 'text', ''),
    'dob' => array('Date of Birth <i>/ জন্ম তারিখ</i>', 'date', ''),
    'gender' => array('Gender <i>/ লিঙ্গ</i>', 'select:Male,Female,Other', ''),
    'nid_no' => array('National ID No <i>/ জাতীয় পরিচয়পত্র নম্বর</i>', 'text', ''),
    'birth_cert_no' => array('Birth Certificate No <i>/ জন্ম নিবন্ধন নম্বর</i>', 'text', ''),
    'present_address' => array('Present Address <i>/ বর্তমান ঠিকানা</i>', 'textarea', 'sm:col-span-2'),
    'permanent_address' => array('Permanent Address <i>/ স্থায়ী ঠিকানা</i>', 'textarea', 'sm:col-span-2'),
    'blood_group' => array('Blood Group <i>/ রক্তের গ্রুপ</i>', 'select:A+,A-,B+,B-,AB+,AB-,O+,O-', ''),
    'height' => array('Height <i>/ উচ্চতা</i>', 'text', ''),
    'disability_type' => array('Type of Disabled <i>/ প্রতিবন্ধকতার ধরন</i>', 'list:dl_dis', ''),
    'financial_status' => array('Financial Status <i>/ আর্থিক অবস্থা</i>', 'list:dl_fin', ''),
    'social_status' => array('Social Status <i>/ সামাজিক অবস্থা</i>', 'list:dl_soc', ''),
    'mobile_no' => array('Mobile No <i>/ মোবাইল</i>', 'text', ''),
    'email' => array('E-mail <i>/ ই-মেইল</i>', 'email', ''),
    'other_info' => array("Other's Information <i>/ অন্যান্য তথ্য</i>", 'textarea', 'sm:col-span-2'),
    // Office use only
    'application_date' => array('Application Date', 'date', ''),
    'membership_no' => array('Membership No', 'text', ''),
    'registration_no' => array('Registration No', 'text', ''),
    'remarks' => array('Remarks', 'textarea', 'sm:col-span-2'),
);
$RF_KEYS = array_keys($RF);
$RF_OFFICE = array('application_date', 'membership_no', 'registration_no', 'remarks');
$RF_NULLABLE = array('dob', 'application_date');

if (!defined('EXP_FROM')) {
    define('EXP_FROM', 'FROM expenses e LEFT JOIN service_recipients r ON r.id = e.recipient_id LEFT JOIN activities a ON a.id = e.activity_id');
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

/* ==========================================================
   HELPER FUNCTIONS
   ========================================================== */

if (!function_exists('h')) {
    // Safe output (XSS theke bachay)
    function h($s)
    {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('go_to')) {
    // Safe redirect: header kaj na korle JavaScript diye redirect hobe
    function go_to($url)
    {
        if (!headers_sent()) {
            header('Location: ' . $url);
        } else {
            echo '<script>window.location.href=' . json_encode($url) . ';</script>';
        }
        exit;
    }
}

// JSON response pathay (AJAX er jonno)
function exp_json($arr)
{
    while (ob_get_level()) {
        ob_end_clean();
    }
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($arr, JSON_UNESCAPED_UNICODE);
    exit;
}

function exp_money($n)
{
    return '৳' . number_format((float) $n, 2);
}

function exp_cat($c)
{
    $cats = exp_cats();
    return isset($cats[$c]) ? $cats[$c] : $cats['Other'];
}

// bind_param er jonno values-ke reference-e convert kore
function exp_bind($stmt, $types, &$params)
{
    if ($types !== '' && count($params) > 0) {
        $args = array($stmt, $types);
        foreach ($params as $k => $v) {
            $args[] = &$params[$k];
        }
        call_user_func_array('mysqli_stmt_bind_param', $args);
    }
}

/*
 * SELECT query chalay, sob row array hishebe ferot dey. (mysqlnd lage na)
 * $t = types ('i','s'...), $p = values array
 */
function exp_rows($db, $sql, $t = '', $p = array())
{
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        throw new Exception('Query error: ' . mysqli_error($db));
    }
    exp_bind($stmt, $t, $p);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    $out = array();
    $meta = mysqli_stmt_result_metadata($stmt);
    if ($meta) {
        $row = array();
        $bind = array($stmt);
        while ($field = mysqli_fetch_field($meta)) {
            $row[$field->name] = null;
            $bind[] = &$row[$field->name];
        }
        call_user_func_array('mysqli_stmt_bind_result', $bind);

        while (mysqli_stmt_fetch($stmt)) {
            $copy = array();
            foreach ($row as $key => $val) {
                $copy[$key] = $val;
            }
            $out[] = $copy;
        }
        mysqli_free_result($meta);
    }
    mysqli_stmt_close($stmt);
    return $out;
}

// Prothom row ferot dey (na thakle null)
function exp_one($db, $sql, $t = '', $p = array())
{
    $r = exp_rows($db, $sql, $t, $p);
    return isset($r[0]) ? $r[0] : null;
}

/*
 * INSERT / UPDATE / DELETE chalay. Fail hole Exception dey.
 * Return: affected rows. Insert id pete: $GLOBALS['exp_insert_id']
 */
function exp_exec($db, $sql, $t = '', $p = array())
{
    $stmt = mysqli_prepare($db, $sql);
    if (!$stmt) {
        throw new Exception('Query error: ' . mysqli_error($db));
    }
    exp_bind($stmt, $t, $p);
    if (!mysqli_stmt_execute($stmt)) {
        $m = mysqli_stmt_error($stmt);
        mysqli_stmt_close($stmt);
        throw new Exception($m);
    }
    $GLOBALS['exp_insert_id'] = mysqli_stmt_insert_id($stmt);
    $aff = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);
    return $aff;
}

// Search / filter er WHERE condition banay
function exp_where($f, &$types, &$params, $skip = '')
{
    $w = 'WHERE 1=1';
    $types = '';
    $params = array();

    if ($f['q'] !== '' && $skip !== 'q') {
        $w .= ' AND (e.voucher_no LIKE ? OR e.title LIKE ? OR e.paid_to LIKE ? OR r.member_name LIKE ? OR r.mobile_no LIKE ? OR a.title LIKE ?)';
        $l = '%' . addcslashes($f['q'], '%_\\') . '%';
        $types .= 'ssssss';
        for ($i = 0; $i < 6; $i++) {
            $params[] = $l;
        }
    }
    if ($f['cat'] !== '' && $skip !== 'cat') {
        $w .= ' AND e.category = ?';
        $types .= 's';
        $params[] = $f['cat'];
    }
    if ($f['status'] !== '' && $skip !== 'status') {
        $w .= ' AND e.status = ?';
        $types .= 's';
        $params[] = $f['status'];
    }
    if ($f['act'] !== '') {
        $w .= ' AND e.activity_id = ?';
        $types .= 's';
        $params[] = $f['act'];
    }
    if ($f['df'] !== '') {
        $w .= ' AND e.expense_date >= ?';
        $types .= 's';
        $params[] = $f['df'];
    }
    if ($f['dt'] !== '') {
        $w .= ' AND e.expense_date <= ?';
        $types .= 's';
        $params[] = $f['dt'];
    }
    return $w;
}

// Upore-r card gulor stats
function exp_stats($db, $f)
{
    $CATS = exp_cats();
    $statuses = exp_statuses();
    $t = '';
    $p = array();

    $cats = array();
    foreach ($CATS as $k => $_) {
        $cats[$k] = array('a' => exp_money(0), 'c' => 0);
    }
    $w = exp_where($f, $t, $p, 'cat');
    foreach (exp_rows($db, 'SELECT e.category k, COUNT(*) c, COALESCE(SUM(e.amount),0) s ' . EXP_FROM . " $w GROUP BY e.category", $t, $p) as $r) {
        $key = isset($CATS[$r['k']]) ? $r['k'] : 'Other';
        $cats[$key] = array('a' => exp_money($r['s']), 'c' => (int) $r['c']);
    }

    $st = array();
    foreach ($statuses as $s) {
        $st[$s] = array('a' => exp_money(0), 'c' => 0);
    }
    $w = exp_where($f, $t, $p, 'status');
    foreach (exp_rows($db, 'SELECT e.status k, COUNT(*) c, COALESCE(SUM(e.amount),0) s ' . EXP_FROM . " $w GROUP BY e.status", $t, $p) as $r) {
        $st[$r['k']] = array('a' => exp_money($r['s']), 'c' => (int) $r['c']);
    }

    $w = exp_where($f, $t, $p);
    $tot = exp_one($db, 'SELECT COUNT(*) c, COALESCE(SUM(e.amount),0) s ' . EXP_FROM . " $w", $t, $p);

    return array(
        'cats' => $cats,
        'status' => $st,
        'total_amount' => exp_money($tot['s']),
        'total_count' => (int) $tot['c'],
    );
}

// Chhobi upload. Return: file name / '' (file dey nai) / false (error, $err te message)
function exp_upload($file, $dir, &$err, $prefix)
{
    if (!is_array($file) || $file['error'] === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $err = 'ছবি আপলোডে সমস্যা হয়েছে।';
        return false;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, array('jpg', 'jpeg', 'png', 'webp')) || !@getimagesize($file['tmp_name'])) {
        $err = 'ছবি শুধু JPG, PNG বা WEBP হতে হবে।';
        return false;
    }
    if ($file['size'] > 3 * 1024 * 1024) {
        $err = 'ছবির সাইজ সর্বোচ্চ 3MB।';
        return false;
    }
    if (!is_dir($dir)) {
        @mkdir($dir, 0755, true);
    }
    $name = $prefix . '_' . time() . '_' . random_int(1000, 9999) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . $name)) {
        $err = 'ছবি সেভ করা যায়নি। ফোল্ডার পারমিশন চেক করুন।';
        return false;
    }
    return $name;
}

function exp_unlink($dir, $f)
{
    if ($f != '' && is_file($dir . basename($f))) {
        @unlink($dir . basename($f));
    }
}

function exp_url($dir, $f)
{
    return ($f != '' && is_file($dir . basename($f))) ? $dir . $f : '';
}

// Table-er ekta row-er HTML. $is_admin = true hole toggle + delete button dekhabe
function exp_row($r, $rd, $is_admin = false)
{
    $cs = exp_cat($r['category']);
    $done = ($r['status'] === 'Complete');
    $for = '';

    if ($r['rec_name'] !== null) {
        $ph = exp_url($rd, $r['rec_photo']);
        $av = $ph
            ? '<img src="' . h($ph) . '" class="w-4 h-4 rounded-full object-cover">'
            : '<i class="fa-solid fa-user text-sky-500"></i>';
        $for .= '<span class="inline-flex items-center gap-1.5 text-[11px] text-slate-600">' . $av . h($r['rec_name'])
            . ' <span class="text-slate-400">' . h($r['rec_serial']) . '</span></span>';
    }

    if ($r['act_title'] !== null) {
        $for .= '<span class="inline-flex items-center gap-1.5 text-[11px] text-slate-600"><i class="fa-solid fa-diagram-project text-emerald-600"></i>'
            . h($r['act_title']) . '</span>';
    }

    $id = (int) $r['id'];

    // Shudhu Admin-er jonno button
    $toggle_btn = '';
    $delete_btn = '';
    if ($is_admin) {
        $toggle_btn = '<button type="button" data-act="toggle" data-id="' . $id . '" title="' . ($done ? 'Mark Pending' : 'Mark Complete')
            . '" class="p-1.5 ' . ($done ? 'text-emerald-500 hover:text-amber-600' : 'text-slate-400 hover:text-emerald-600')
            . '"><i class="fa-solid ' . ($done ? 'fa-circle-check' : 'fa-hourglass-half') . '"></i></button>';

        $delete_btn = '<button type="button" data-act="delete" data-id="' . $id . '" data-name="' . h($r['title'])
            . '" title="Delete" class="p-1.5 text-slate-400 hover:text-rose-600"><i class="fa-solid fa-trash"></i></button>';
    }

    return '
    <tr class="hover:bg-slate-50/80 align-top">
      <td class="p-4"><button type="button" data-act="view" data-id="' . $id . '" class="font-semibold text-left text-slate-800 hover:text-emerald-600 hover:underline">' . h($r['title']) . '</button>
        <span class="block text-[10px] text-slate-400 mt-0.5">' . h($r['voucher_no']) . ' • ' . h(date('d M Y', strtotime($r['expense_date']))) . '</span>
        <span class="sm:hidden inline-block mt-1 px-2 py-0.5 rounded-md font-bold text-[10px] ' . $cs[0] . '">' . h($r['category']) . '</span>
        <div class="mt-1.5 flex flex-col gap-1">' . $for . '</div></td>
      <td class="p-4 hidden sm:table-cell"><span class="px-2.5 py-1 rounded-md font-bold text-[10px] whitespace-nowrap ' . $cs[0] . '"><i class="fa-solid ' . $cs[3] . ' mr-1"></i>' . h($r['category']) . '</span></td>
      <td class="p-4 font-bold text-slate-800 whitespace-nowrap">' . exp_money($r['amount']) . '</td>
      <td class="p-4"><span class="px-2.5 py-1 rounded-md font-bold text-[10px] ' . ($done ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700') . '">' . h($r['status']) . '</span></td>
      <td class="p-4 text-right whitespace-nowrap">
        <button type="button" data-act="view" data-id="' . $id . '" title="View" class="p-1.5 text-slate-400 hover:text-sky-600"><i class="fa-solid fa-eye"></i></button>
        ' . $toggle_btn . '
        <button type="button" data-act="edit" data-id="' . $id . '" title="Edit" class="p-1.5 text-slate-400 hover:text-emerald-600"><i class="fa-solid fa-pen"></i></button>
        ' . $delete_btn . '
      </td>
    </tr>';
}

// Pagination button-er HTML
function exp_pager($page, $pages)
{
    if ($pages <= 1) {
        return '';
    }
    $btn = function ($p, $label, $active = false, $dis = false) {
        $c = $active
            ? 'bg-emerald-600 text-white border-emerald-600'
            : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-50';
        if ($dis) {
            $c = 'bg-slate-50 text-slate-300 border-slate-100 cursor-not-allowed';
        }
        return '<button type="button" ' . ($dis ? 'disabled' : 'data-page="' . $p . '"')
            . ' class="min-w-8 h-8 px-2 rounded-lg border text-xs font-semibold ' . $c . '">' . $label . '</button>';
    };

    $out = $btn(max(1, $page - 1), '&lsaquo;', false, $page == 1);
    $set = array(1, $pages);
    for ($i = $page - 1; $i <= $page + 1; $i++) {
        if ($i > 0 && $i <= $pages) {
            $set[] = $i;
        }
    }
    $set = array_unique($set);
    sort($set);

    $prev = 0;
    foreach ($set as $p) {
        if ($p - $prev > 1) {
            $out .= '<span class="px-1 text-slate-400">…</span>';
        }
        $out .= $btn($p, $p, $p == $page);
        $prev = $p;
    }
    return $out . $btn(min($pages, $page + 1), '&rsaquo;', false, $page == $pages);
}

// Recipient-er photo URL jog kore
function exp_rec_out($r, $rd)
{
    if (!$r) {
        return null;
    }
    $r['photo_url'] = exp_url($rd, $r['photo']);
    $r['guardian_url'] = exp_url($rd, $r['guardian_photo']);
    return $r;
}

// Recipient form-er ekta field-er HTML
function exp_field($k, $d, $inp, $lbl)
{
    $id = 'r_' . $k;
    $type = $d[1];
    $o = '<div class="' . $d[2] . '"><label class="' . $lbl . ' normal-case">' . $d[0] . '</label>';

    if ($type === 'textarea') {
        $o .= '<textarea name="' . $id . '" id="' . $id . '" rows="2" class="' . $inp . '"></textarea>';
    } elseif (strpos($type, 'select:') === 0) {
        $o .= '<select name="' . $id . '" id="' . $id . '" class="' . $inp . '"><option value="">—</option>';
        foreach (explode(',', substr($type, 7)) as $x) {
            $o .= '<option>' . h($x) . '</option>';
        }
        $o .= '</select>';
    } elseif (strpos($type, 'list:') === 0) {
        $o .= '<input type="text" name="' . $id . '" id="' . $id . '" list="' . substr($type, 5) . '" class="' . $inp . '">';
    } else {
        $o .= '<input type="' . $type . '" name="' . $id . '" id="' . $id . '" class="' . $inp . '">';
    }
    return $o . '</div>';
}

/* ==========================================================
   ACCESS CHECK + DATABASE
   ========================================================== */
$user_type = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '';
$is_admin = ($user_type === 'Admin');
$act = isset($_GET['act']) ? $_GET['act'] : '';

// only admin and volunteers can visit
if (!in_array($user_type, array('Admin', 'Volunteer Member'))) {
    if ($act !== '') {
        exp_json(array('ok' => false, 'msg' => 'Access denied.'));
    }
    go_to('index.php?page=dashboard');
}

global $db;
if (!isset($db) || !$db) {
    die('Database connection not found.');
}
mysqli_set_charset($db, 'utf8mb4');

/* ==========================================================
   AJAX HANDLER
   ========================================================== */
$newf = array();   // notun upload hoya file-er list (error hole muche felar jonno)

if ($act !== '') {
    try {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $tok = isset($_SERVER['HTTP_X_CSRF']) ? $_SERVER['HTTP_X_CSRF'] : '';
            if (!hash_equals($_SESSION['csrf'], $tok)) {
                exp_json(array('ok' => false, 'msg' => 'Invalid session. Page reload korun.'));
            }
        }

        /* ----- LIST ----- */
        if ($act === 'list') {
            $ea = isset($_GET['ea']) ? (int) $_GET['ea'] : 0;
            $f = array(
                'q' => trim(isset($_GET['es']) ? $_GET['es'] : ''),
                'cat' => trim(isset($_GET['ec']) ? $_GET['ec'] : ''),
                'status' => trim(isset($_GET['est']) ? $_GET['est'] : ''),
                'act' => $ea > 0 ? (string) $ea : '',
                'df' => trim(isset($_GET['df']) ? $_GET['df'] : ''),
                'dt' => trim(isset($_GET['dt']) ? $_GET['dt'] : ''),
            );
            $per = isset($_GET['per']) ? (int) $_GET['per'] : 10;
            if (!in_array($per, $per_opts)) {
                $per = 10;
            }
            $page = max(1, isset($_GET['pg']) ? (int) $_GET['pg'] : 1);

            $t = '';
            $p = array();
            $w = exp_where($f, $t, $p);
            $tot = exp_one($db, 'SELECT COUNT(*) c ' . EXP_FROM . " $w", $t, $p);
            $total = (int) $tot['c'];
            $pages = max(1, (int) ceil($total / $per));
            if ($page > $pages) {
                $page = $pages;
            }
            $offset = ($page - 1) * $per;

            $_SESSION['exp_filter'] = $f + array('per' => $per, 'page' => $page);

            $listP = $p;
            $listP[] = $per;
            $listP[] = $offset;
            $rows = exp_rows(
                $db,
                'SELECT e.*, r.member_name rec_name, r.serial_no rec_serial, r.photo rec_photo, a.title act_title '
                . EXP_FROM . " $w ORDER BY e.expense_date DESC, e.id DESC LIMIT ? OFFSET ?",
                $t . 'ii',
                $listP
            );

            $html = '';
            foreach ($rows as $r) {
                $html .= exp_row($r, $rec_dir, $is_admin);
            }
            if ($html === '') {
                $html = '<tr><td colspan="5" class="p-10 text-center text-slate-400"><i class="fa-solid fa-receipt text-3xl mb-2"></i><p>কোনো খরচের রেকর্ড পাওয়া যায়নি।</p></td></tr>';
            }

            exp_json(array(
                'ok' => true,
                'rows' => $html,
                'pager' => exp_pager($page, $pages),
                'page' => $page,
                'total' => $total,
                'from' => $total ? $offset + 1 : 0,
                'to' => min($offset + $per, $total),
                'stats' => exp_stats($db, $f),
                'filtered' => ($f['q'] !== '' || $f['cat'] !== '' || $f['status'] !== '' || $f['act'] !== '' || $f['df'] !== '' || $f['dt'] !== ''),
            ));
        }

        if ($act === 'clear') {
            unset($_SESSION['exp_filter']);
            exp_json(array('ok' => true));
        }

        if ($act === 'recs') {
            exp_json(array('ok' => true, 'recs' => exp_rows($db, 'SELECT id, member_name, serial_no, mobile_no FROM service_recipients ORDER BY id DESC')));
        }

        if ($act === 'rget') {
            $rid0 = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            $r = exp_rec_out(exp_one($db, 'SELECT * FROM service_recipients WHERE id = ?', 'i', array($rid0)), $rec_dir);
            exp_json($r ? array('ok' => true, 'data' => $r) : array('ok' => false, 'msg' => 'Recipient পাওয়া যায়নি।'));
        }

        if ($act === 'get') {
            $gid = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            $e = exp_one($db, 'SELECT * FROM expenses WHERE id = ?', 'i', array($gid));
            if (!$e) {
                exp_json(array('ok' => false, 'msg' => 'খরচ পাওয়া যায়নি।'));
            }
            $e['receipt_url'] = exp_url($exp_dir, $e['receipt']);
            $rec = $e['recipient_id']
                ? exp_rec_out(exp_one($db, 'SELECT * FROM service_recipients WHERE id = ?', 'i', array((int) $e['recipient_id'])), $rec_dir)
                : null;
            $ac = $e['activity_id']
                ? exp_one($db, 'SELECT id, title, status FROM activities WHERE id = ?', 'i', array((int) $e['activity_id']))
                : null;
            exp_json(array('ok' => true, 'data' => $e, 'rec' => $rec, 'act' => $ac));
        }

        /* ----- VIEW (voucher modal er data) ----- */
        if ($act === 'view') {
            $vid = isset($_GET['id']) ? (int) $_GET['id'] : 0;
            $e = exp_one(
                $db,
                'SELECT e.*, a.title act_title, a.badge_text act_badge, a.description act_desc, a.image act_image
                 FROM expenses e LEFT JOIN activities a ON a.id = e.activity_id WHERE e.id = ?',
                'i',
                array($vid)
            );
            if (!$e) {
                exp_json(array('ok' => false, 'msg' => 'খরচ পাওয়া যায়নি।'));
            }
            $e['receipt_url'] = exp_url($exp_dir, $e['receipt']);
            $actImg = isset($e['act_image']) ? (string) $e['act_image'] : '';
            $e['act_image_url'] = ($actImg !== '' && is_file($act_prefix . $actImg)) ? $act_prefix . $actImg : '';

            $rec = $e['recipient_id']
                ? exp_rec_out(exp_one($db, 'SELECT * FROM service_recipients WHERE id = ?', 'i', array((int) $e['recipient_id'])), $rec_dir)
                : null;
            $recT = $rec
                ? exp_one($db, 'SELECT COUNT(*) c, COALESCE(SUM(amount),0) s FROM expenses WHERE recipient_id = ?', 'i', array((int) $rec['id']))
                : null;
            $actT = $e['activity_id']
                ? exp_one($db, 'SELECT COUNT(*) c, COALESCE(SUM(amount),0) s FROM expenses WHERE activity_id = ?', 'i', array((int) $e['activity_id']))
                : null;

            exp_json(array('ok' => true, 'e' => $e, 'rec' => $rec, 'recT' => $recT, 'actT' => $actT));
        }

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            exp_json(array('ok' => false, 'msg' => 'Invalid request.'));
        }

        /* ----- TOGGLE STATUS (shudhu Admin) ----- */
        if ($act === 'toggle') {
            if (!$is_admin) {
                exp_json(array('ok' => false, 'msg' => 'শুধু Admin এই কাজ করতে পারবে।'));
            }
            $tid = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            exp_exec($db, "UPDATE expenses SET status = IF(status = 'Pending', 'Complete', 'Pending') WHERE id = ?", 'i', array($tid));
            exp_json(array('ok' => true, 'msg' => 'Status পরিবর্তন করা হয়েছে।'));
        }

        /* ----- DELETE (shudhu Admin) ----- */
        if ($act === 'delete') {
            if (!$is_admin) {
                exp_json(array('ok' => false, 'msg' => 'শুধু Admin এই কাজ করতে পারবে।'));
            }
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
            $o = exp_one($db, 'SELECT receipt FROM expenses WHERE id = ?', 'i', array($id));
            if (!$o) {
                exp_json(array('ok' => false, 'msg' => 'খরচ পাওয়া যায়নি।'));
            }
            $aff = exp_exec($db, 'DELETE FROM expenses WHERE id = ?', 'i', array($id));
            if ($aff > 0) {
                exp_unlink($exp_dir, $o['receipt']);
                exp_json(array('ok' => true, 'msg' => 'খরচ ডিলিট করা হয়েছে।'));
            }
            exp_json(array('ok' => false, 'msg' => 'ডিলিট করা যায়নি।'));
        }

        /* ----- SAVE (add + edit, recipient soho) ----- */
        if ($act === 'save') {
            $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;

            $e = array();
            foreach (array('title', 'category', 'amount', 'expense_date', 'payment_method', 'transaction_id', 'paid_to', 'description', 'status') as $k) {
                $e[$k] = trim(isset($_POST[$k]) ? $_POST[$k] : '');
            }
            $activity_id = isset($_POST['activity_id']) ? (int) $_POST['activity_id'] : 0;
            $mode = isset($_POST['recipient_mode']) ? $_POST['recipient_mode'] : 'none';
            if (!in_array($mode, array('none', 'existing', 'new'))) {
                $mode = 'none';
            }
            $rid = isset($_POST['recipient_id']) ? (int) $_POST['recipient_id'] : 0;
            $amount = (float) $e['amount'];

            if ($e['title'] === '' || $e['expense_date'] === '' || $amount <= 0) {
                exp_json(array('ok' => false, 'msg' => 'শিরোনাম, তারিখ ও সঠিক Amount দিন।'));
            }
            if (!isset($CATS[$e['category']])) {
                $e['category'] = 'Other';
            }
            if (!in_array($e['status'], $statuses)) {
                $e['status'] = 'Pending';
            }
            if ($activity_id <= 0 && $mode === 'none') {
                exp_json(array('ok' => false, 'msg' => 'কোনো কার্যক্রম অথবা সেবা গ্রহণকারী নির্বাচন করুন।'));
            }
            if ($activity_id > 0 && !exp_one($db, 'SELECT id FROM activities WHERE id = ?', 'i', array($activity_id))) {
                exp_json(array('ok' => false, 'msg' => 'কার্যক্রম পাওয়া যায়নি।'));
            }

            // Recipient-er data
            $r = array();
            foreach ($RF_KEYS as $k) {
                $v = trim(isset($_POST['r_' . $k]) ? $_POST['r_' . $k] : '');
                $r[$k] = ($v === '' && in_array($k, $RF_NULLABLE)) ? null : $v;
            }
            if ($mode !== 'none') {
                if ($r['member_name'] === '') {
                    exp_json(array('ok' => false, 'msg' => 'সেবা গ্রহণকারীর নাম দিন।'));
                }
                if ($r['mobile_no'] !== '' && !preg_match('/^[0-9]{11}$/', $r['mobile_no'])) {
                    exp_json(array('ok' => false, 'msg' => 'মোবাইল নম্বর ১১ ডিজিটের হতে হবে।'));
                }
                if ($r['email'] !== '' && !filter_var($r['email'], FILTER_VALIDATE_EMAIL)) {
                    exp_json(array('ok' => false, 'msg' => 'সঠিক ইমেইল দিন।'));
                }
                if ($mode === 'existing' && $rid <= 0) {
                    exp_json(array('ok' => false, 'msg' => 'সেবা গ্রহণকারী সিলেক্ট করুন।'));
                }
            }

            // ---- uploads ----
            $err = '';
            $ph = '';
            $gp = '';

            $receipt = exp_upload(isset($_FILES['receipt']) ? $_FILES['receipt'] : null, $exp_dir, $err, 'exp');
            if ($receipt === false) {
                exp_json(array('ok' => false, 'msg' => $err));
            }
            if ($receipt !== '') {
                $newf[] = array($exp_dir, $receipt);
            }

            if ($mode !== 'none') {
                $ph = exp_upload(isset($_FILES['r_photo']) ? $_FILES['r_photo'] : null, $rec_dir, $err, 'rec');
                if ($ph === false) {
                    foreach ($newf as $n) {
                        exp_unlink($n[0], $n[1]);
                    }
                    exp_json(array('ok' => false, 'msg' => $err));
                }
                if ($ph !== '') {
                    $newf[] = array($rec_dir, $ph);
                }

                $gp = exp_upload(isset($_FILES['r_guardian_photo']) ? $_FILES['r_guardian_photo'] : null, $rec_dir, $err, 'grd');
                if ($gp === false) {
                    foreach ($newf as $n) {
                        exp_unlink($n[0], $n[1]);
                    }
                    exp_json(array('ok' => false, 'msg' => $err));
                }
                if ($gp !== '') {
                    $newf[] = array($rec_dir, $gp);
                }
            }

            mysqli_begin_transaction($db);
            $old_files = array();

            // --- recipient ---
            if ($mode === 'new') {
                $cols = array_merge(array('photo', 'guardian_photo'), $RF_KEYS);
                $vals = array_merge(array($ph, $gp), array_values($r));
                exp_exec(
                    $db,
                    'INSERT INTO service_recipients (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')',
                    str_repeat('s', count($vals)),
                    $vals
                );
                $rid = (int) $GLOBALS['exp_insert_id'];
                exp_exec($db, "UPDATE service_recipients SET serial_no = CONCAT('SR-', LPAD(id, 6, '0')) WHERE id = ?", 'i', array($rid));

            } elseif ($mode === 'existing') {
                $o = exp_one($db, 'SELECT photo, guardian_photo FROM service_recipients WHERE id = ?', 'i', array($rid));
                if (!$o) {
                    throw new Exception('Recipient পাওয়া যায়নি।');
                }
                $nph = ($ph !== '') ? $ph : $o['photo'];
                $ngp = ($gp !== '') ? $gp : $o['guardian_photo'];
                if ($ph !== '' && $o['photo'] !== '') {
                    $old_files[] = array($rec_dir, $o['photo']);
                }
                if ($gp !== '' && $o['guardian_photo'] !== '') {
                    $old_files[] = array($rec_dir, $o['guardian_photo']);
                }
                $cols = array_merge(array('photo', 'guardian_photo'), $RF_KEYS);
                $vals = array_merge(array($nph, $ngp), array_values($r), array($rid));
                exp_exec(
                    $db,
                    'UPDATE service_recipients SET ' . implode('=?, ', $cols) . '=? WHERE id = ?',
                    str_repeat('s', count($cols)) . 'i',
                    $vals
                );
            } else {
                $rid = 0;
            }

            // --- expense ---
            $final = $receipt;
            if ($id > 0) {
                $o = exp_one($db, 'SELECT receipt FROM expenses WHERE id = ?', 'i', array($id));
                if (!$o) {
                    throw new Exception('খরচ পাওয়া যায়নি।');
                }
                if ($receipt !== '') {
                    $final = $receipt;
                } elseif (!empty($_POST['remove_receipt'])) {
                    $final = '';
                } else {
                    $final = $o['receipt'];
                }
                if ($final !== $o['receipt'] && $o['receipt'] !== '') {
                    $old_files[] = array($exp_dir, $o['receipt']);
                }
            }

            $cols = array(
                'title',
                'category',
                'amount',
                'expense_date',
                'activity_id',
                'recipient_id',
                'payment_method',
                'transaction_id',
                'paid_to',
                'description',
                'receipt',
                'status'
            );
            $vals = array(
                $e['title'],
                $e['category'],
                $amount,
                $e['expense_date'],
                $activity_id ? $activity_id : null,
                $rid ? $rid : null,
                $e['payment_method'],
                $e['transaction_id'],
                $e['paid_to'],
                $e['description'],
                $final,
                $e['status']
            );

            if ($id > 0) {
                exp_exec(
                    $db,
                    'UPDATE expenses SET ' . implode('=?, ', $cols) . '=? WHERE id = ?',
                    str_repeat('s', count($cols)) . 'i',
                    array_merge($vals, array($id))
                );
                $msg = 'খরচ আপডেট হয়েছে।';
            } else {
                exp_exec(
                    $db,
                    'INSERT INTO expenses (' . implode(',', $cols) . ') VALUES (' . implode(',', array_fill(0, count($cols), '?')) . ')',
                    str_repeat('s', count($cols)),
                    $vals
                );
                $nid = (int) $GLOBALS['exp_insert_id'];
                exp_exec($db, "UPDATE expenses SET voucher_no = CONCAT('EXP-', LPAD(id, 6, '0')) WHERE id = ?", 'i', array($nid));
                $msg = 'নতুন খরচ যোগ হয়েছে।';
            }

            mysqli_commit($db);
            foreach ($old_files as $of) {
                exp_unlink($of[0], $of[1]);   // purono image delete
            }
            exp_json(array('ok' => true, 'msg' => $msg));
        }

        exp_json(array('ok' => false, 'msg' => 'Unknown action.'));

    } catch (Throwable $ex) {
        @mysqli_rollback($db);
        foreach ($newf as $n) {
            exp_unlink($n[0], $n[1]);
        }
        exp_json(array('ok' => false, 'msg' => 'Server error: ' . $ex->getMessage()));
    }
}

/* ---------- Page load data ---------- */
$saved = isset($_SESSION['exp_filter'])
    ? $_SESSION['exp_filter']
    : array('q' => '', 'cat' => '', 'status' => '', 'act' => '', 'df' => '', 'dt' => '', 'per' => 10, 'page' => 1);

if ($saved['cat'] !== '' && !isset($CATS[$saved['cat']])) {
    $saved['cat'] = '';
}
if ($saved['status'] !== '' && !in_array($saved['status'], $statuses)) {
    $saved['status'] = '';
}

$ACTS = array();
$RECS = array();
try {
    $ACTS = exp_rows($db, 'SELECT id, title, status FROM activities ORDER BY id DESC');
    $RECS = exp_rows($db, 'SELECT id, member_name, serial_no, mobile_no FROM service_recipients ORDER BY id DESC');
} catch (Exception $ex) {
    // table na thakle page crash korbe na
}

$CAT_BADGES = array();
foreach ($CATS as $k => $v) {
    $CAT_BADGES[$k] = $v[0];
}

$inp = 'w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10';
$lbl = 'block text-[11px] font-bold uppercase text-slate-500 mb-1';
?>

<section class="page-content space-y-5">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-800 tracking-tight">Service Recipients</h2>
            <p class="text-xs text-slate-500 mt-0.5">Record expenses for activities and service recipients.</p>
        </div>
        <button type="button" onclick="expOpenForm()" class="px-4 py-3 sm:py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-emerald-600/20 flex items-center justify-center gap-2">
            <i class="fa-solid fa-plus"></i> Add New
        </button>
    </div>

    <!-- Totals -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <div class="col-span-2 lg:col-span-1 p-4 sm:p-5 bg-gradient-to-br from-slate-900 to-slate-700 text-white rounded-2xl">
            <p class="text-[10px] font-bold text-slate-300 uppercase">Total Amount <span id="fBadge" class="hidden ml-1 px-1.5 py-0.5 rounded bg-emerald-400/20 text-emerald-300 text-[9px] normal-case">Filtered</span></p>
            <h3 class="text-xl sm:text-2xl font-bold mt-0.5" data-stat="total_amount">৳0.00</h3>
        </div>
        <div class="p-4 sm:p-5 bg-white rounded-2xl ring-1 ring-slate-200/80 flex items-center gap-3">
            <div class="w-10 h-10 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center shrink-0"><i class="fa-solid fa-receipt"></i></div>
            <div><p class="text-[10px] font-bold text-slate-400 uppercase">Records</p><h3 class="text-xl font-bold text-slate-800" data-stat="total_count">0</h3></div>
        </div>
        <?php foreach ([['Complete', 'emerald', 'fa-circle-check', 'ring-emerald-500'], ['Pending', 'amber', 'fa-hourglass-half', 'ring-amber-500']] as $s) { ?>
            <div data-fstatus="<?php echo $s[0]; ?>" data-ring="<?php echo $s[3]; ?>" class="p-4 sm:p-5 bg-white rounded-2xl ring-1 ring-slate-200/80 flex items-center gap-3 cursor-pointer hover:shadow-md transition">
                <div class="w-10 h-10 rounded-xl bg-<?php echo $s[1]; ?>-50 text-<?php echo $s[1]; ?>-600 flex items-center justify-center shrink-0"><i class="fa-solid <?php echo $s[2]; ?>"></i></div>
                <div class="min-w-0"><p class="text-[10px] font-bold text-slate-400 uppercase"><?php echo $s[0]; ?> <span class="normal-case" data-stat="st_c:<?php echo $s[0]; ?>"></span></p>
                    <h3 class="text-base sm:text-lg font-bold text-slate-800 truncate" data-stat="st_a:<?php echo $s[0]; ?>">৳0.00</h3></div>
            </div>
        <?php } ?>
    </div>

    <!-- Category wise totals -->
    <div>
        <h3 class="text-xs font-bold text-slate-500 uppercase mb-2">Category wise total</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
            <?php foreach ($CATS as $name => $cs) { ?>
                <div data-fcat="<?php echo h($name); ?>" data-ring="<?php echo $cs[2]; ?>" class="p-3 bg-white rounded-2xl ring-1 ring-slate-200/80 flex items-center gap-2.5 cursor-pointer hover:shadow-md transition">
                    <div class="w-9 h-9 rounded-xl <?php echo $cs[1]; ?> flex items-center justify-center text-sm shrink-0"><i class="fa-solid <?php echo $cs[3]; ?>"></i></div>
                    <div class="min-w-0">
                        <p class="text-[10px] font-bold text-slate-400 uppercase truncate"><?php echo h($name); ?> <span class="normal-case font-semibold" data-stat="cat_c:<?php echo h($name); ?>"></span></p>
                        <p class="text-xs font-bold text-slate-800 truncate" data-stat="cat_a:<?php echo h($name); ?>">৳0.00</p>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
    <div class="hidden ring-2 ring-rose-500 ring-sky-500 ring-amber-500 ring-violet-500 ring-orange-500 ring-teal-500 ring-red-500 ring-fuchsia-500 ring-slate-500 ring-emerald-500"></div>

    <!-- Filters -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-4 space-y-3">
        <div class="grid grid-cols-2 md:grid-cols-12 gap-3">
            <div class="col-span-2 md:col-span-4 relative">
                <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-xs"></i>
                <input type="text" id="fSearch" value="<?php echo h($saved['q']); ?>" placeholder="Voucher, title, name, phone, activity..." class="w-full pl-9 pr-3 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-emerald-500">
            </div>
            <select id="fCat" class="md:col-span-2 <?php echo $inp; ?>">
                <option value="">All Categories</option>
                <?php foreach ($CATS as $n => $_) {
                    echo '<option' . ($saved['cat'] === $n ? ' selected' : '') . '>' . h($n) . '</option>';
                } ?>
            </select>
            <select id="fStatus" class="md:col-span-2 <?php echo $inp; ?>">
                <option value="">All Status</option>
                <?php foreach ($statuses as $n) {
                    echo '<option' . ($saved['status'] === $n ? ' selected' : '') . '>' . $n . '</option>';
                } ?>
            </select>
            <select id="fAct" class="col-span-2 md:col-span-4 <?php echo $inp; ?>">
                <option value="">All Activities</option>
                <?php foreach ($ACTS as $a) {
                    echo '<option value="' . (int) $a['id'] . '"' . ((string) $saved['act'] === (string) $a['id'] ? ' selected' : '') . '>' . h($a['title']) . '</option>';
                } ?>
            </select>
            <div class="md:col-span-3"><label class="<?php echo $lbl; ?>">From</label><input type="date" id="fDf" value="<?php echo h($saved['df']); ?>" class="<?php echo $inp; ?>"></div>
            <div class="md:col-span-3"><label class="<?php echo $lbl; ?>">To</label><input type="date" id="fDt" value="<?php echo h($saved['dt']); ?>" class="<?php echo $inp; ?>"></div>
            <div class="md:col-span-2"><label class="<?php echo $lbl; ?>">Per page</label>
                <select id="fPer" class="<?php echo $inp; ?>"><?php foreach ($per_opts as $n) {
                       echo '<option value="' . $n . '"' . ((int) $saved['per'] === $n ? ' selected' : '') . '>' . $n . '</option>';
                   } ?></select></div>
            <div class="col-span-2 md:col-span-4 flex items-end">
                <button type="button" id="fClear" onclick="expClear()" class="hidden w-full md:w-auto px-4 py-2.5 border border-rose-200 text-rose-600 bg-rose-50 hover:bg-rose-100 rounded-xl text-xs font-semibold items-center justify-center gap-1.5">
                    <i class="fa-solid fa-filter-circle-xmark"></i> Remove Filter
                </button>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-700 uppercase font-bold border-b border-slate-200">
                    <tr><th class="p-4">Expense</th><th class="p-4 hidden sm:table-cell">Category</th><th class="p-4">Amount</th><th class="p-4">Status</th><th class="p-4 text-right">Actions</th></tr>
                </thead>
                <tbody id="expBody" class="divide-y divide-slate-100"><tr><td colspan="5" class="p-6 text-center text-slate-400">Loading...</td></tr></tbody>
            </table>
        </div>
        <div class="p-4 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-3">
            <span id="expInfo" class="text-xs text-slate-500"></span>
            <div id="expPager" class="flex items-center gap-1 flex-wrap justify-center"></div>
        </div>
    </div>
</section>

<datalist id="dl_dis"><option>Physical</option><option>Visual</option><option>Hearing</option><option>Speech</option><option>Intellectual</option><option>Autism</option><option>Multiple</option><option>None</option></datalist>
<datalist id="dl_fin"><option>Extreme Poor</option><option>Poor</option><option>Lower Middle Class</option><option>Middle Class</option></datalist>
<datalist id="dl_soc"><option>Orphan</option><option>Widow</option><option>Divorced</option><option>Abandoned</option><option>Freedom Fighter Family</option><option>Other</option></datalist>

<!-- ADD / EDIT MODAL -->
<div id="expFormModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs hidden items-end sm:items-center justify-center sm:p-4 z-50">
    <div class="bg-white w-full sm:max-w-3xl max-h-[94vh] flex flex-col rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-4 sm:p-5 bg-slate-900 text-white flex justify-between items-center shrink-0">
            <h3 class="font-bold text-sm flex items-center gap-2"><i class="fa-solid fa-receipt text-emerald-400"></i><span id="expFormTitle">Add Expense</span></h3>
            <button type="button" onclick="expModal('expFormModal', false)" class="text-slate-400 hover:text-white p-1"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>

        <form id="expForm" class="p-4 sm:p-6 space-y-5 overflow-y-auto" enctype="multipart/form-data" autocomplete="off">
            <input type="hidden" name="id" id="x_id" value="0">

            <div class="space-y-3">
                <h4 class="text-xs font-bold text-slate-800 border-b border-slate-100 pb-2"><i class="fa-solid fa-circle-info text-emerald-600 mr-1.5"></i>Expense Details</h4>
                <div><label class="<?php echo $lbl; ?>">Title / Purpose *</label><input type="text" name="title" id="x_title" class="<?php echo $inp; ?>" placeholder="e.g. Wheelchair purchase for ..."></div>
                <div class="grid grid-cols-2 gap-3">
                    <div><label class="<?php echo $lbl; ?>">Category *</label><select name="category" id="x_category" class="<?php echo $inp; ?>"><?php foreach ($CATS as $n => $_) {
                              echo '<option>' . h($n) . '</option>';
                          } ?></select></div>
                    <div><label class="<?php echo $lbl; ?>">Amount (৳) *</label><input type="number" min="0" step="0.01" name="amount" id="x_amount" class="<?php echo $inp; ?>"></div>
                    <div><label class="<?php echo $lbl; ?>">Expense Date *</label><input type="date" name="expense_date" id="x_expense_date" class="<?php echo $inp; ?>"></div>
                    <div><label class="<?php echo $lbl; ?>">Status *</label><select name="status" id="x_status" class="<?php echo $inp; ?>"><?php foreach ($statuses as $n) {
                              echo '<option>' . $n . '</option>';
                          } ?></select></div>
                    <div><label class="<?php echo $lbl; ?>">Payment Method</label><select name="payment_method" id="x_payment_method" class="<?php echo $inp; ?>"><option value="">—</option><?php foreach ($methods as $n) {
                              echo '<option>' . $n . '</option>';
                          } ?></select></div>
                    <div><label class="<?php echo $lbl; ?>">Transaction ID</label><input type="text" name="transaction_id" id="x_transaction_id" class="<?php echo $inp; ?>"></div>
                </div>
                <div><label class="<?php echo $lbl; ?>">Paid To (Vendor / Person)</label><input type="text" name="paid_to" id="x_paid_to" class="<?php echo $inp; ?>"></div>
                <div><label class="<?php echo $lbl; ?>">Description</label><textarea name="description" id="x_description" rows="3" class="<?php echo $inp; ?>" placeholder="খরচের বিস্তারিত বর্ণনা..."></textarea></div>
                <div>
                    <label class="<?php echo $lbl; ?>">Receipt / Voucher Image (max 3MB)</label>
                    <div class="flex items-center gap-3">
                        <div class="w-20 h-16 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 flex items-center justify-center overflow-hidden shrink-0"><i class="fa-solid fa-image text-slate-400" id="x_prevIcon"></i><img id="x_prev" class="hidden w-full h-full object-cover"></div>
                        <div class="min-w-0"><input type="file" name="receipt" id="x_receipt" accept="image/png,image/jpeg,image/webp" class="text-xs w-full">
                            <label id="x_removeWrap" class="hidden mt-1 items-center gap-1.5 text-[11px] text-rose-600 font-semibold cursor-pointer"><input type="checkbox" name="remove_receipt" value="1"> Remove current image</label></div>
                    </div>
                </div>
            </div>

            <div class="space-y-3">
                <h4 class="text-xs font-bold text-slate-800 border-b border-slate-100 pb-2"><i class="fa-solid fa-diagram-project text-teal-600 mr-1.5"></i>Expense For <span class="font-normal text-slate-400">(কমপক্ষে একটি দিন)</span></h4>
                <div><label class="<?php echo $lbl; ?>">Activity / কার্যক্রম</label><select name="activity_id" id="x_activity_id" class="<?php echo $inp; ?>"></select></div>

                <div>
                    <label class="<?php echo $lbl; ?>">Service Recipient / সেবা গ্রহণকারী</label>
                    <div class="grid grid-cols-3 gap-2" id="x_modes">
                        <?php foreach ([['none', 'None', 'fa-ban'], ['existing', 'Existing', 'fa-user-check'], ['new', 'New', 'fa-user-plus']] as $m) { ?>
                            <label class="flex items-center justify-center gap-1.5 p-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600 cursor-pointer has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 has-[:checked]:text-emerald-700">
                                <input type="radio" name="recipient_mode" value="<?php echo $m[0]; ?>" class="sr-only" <?php echo $m[0] === 'none' ? 'checked' : ''; ?>><i class="fa-solid <?php echo $m[2]; ?>"></i><?php echo $m[1]; ?>
                            </label>
                        <?php } ?>
                    </div>
                </div>

                <div id="rWrap" class="hidden space-y-3">
                    <div id="rPick" class="hidden space-y-2">
                        <input type="text" id="x_recSearch" placeholder="Search recipient by name, serial or phone..." class="<?php echo $inp; ?>">
                        <select name="recipient_id" id="x_recipient_id" class="<?php echo $inp; ?>"></select>
                    </div>

                    <!-- আবেদন পত্র -->
                    <div id="rFields" class="hidden rounded-2xl border border-emerald-200 bg-emerald-50/30 p-4 space-y-4">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs font-bold text-emerald-800"><i class="fa-solid fa-file-lines mr-1.5"></i>সেবা গ্রহণকারীর আবেদন পত্র</p>
                            <span class="text-[11px] font-bold px-2 py-1 rounded-lg bg-white border border-emerald-200 text-emerald-700">Serial: <span id="r_serial">Auto</span></span>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <?php foreach (['r_photo' => 'Photo / ছবি', 'r_guardian_photo' => 'Guardian Photo / অভিভাবকের ছবি'] as $fid => $fl) { ?>
                                <div>
                                    <label class="<?php echo $lbl; ?> normal-case"><?php echo $fl; ?></label>
                                    <div class="flex items-center gap-2">
                                        <div class="w-14 h-16 rounded-lg border-2 border-dashed border-slate-300 bg-white flex items-center justify-center overflow-hidden shrink-0"><i class="fa-solid fa-camera text-slate-300" id="<?php echo $fid; ?>_icon"></i><img id="<?php echo $fid; ?>_prev" class="hidden w-full h-full object-cover"></div>
                                        <input type="file" name="<?php echo $fid; ?>" id="<?php echo $fid; ?>" accept="image/png,image/jpeg,image/webp" class="text-[10px] w-full min-w-0">
                                    </div>
                                </div>
                            <?php } ?>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <?php foreach ($RF as $k => $d) {
                                if (!in_array($k, $RF_OFFICE)) {
                                    echo exp_field($k, $d, $inp, $lbl);
                                    if ($k === 'permanent_address') {
                                        echo '<label class="sm:col-span-2 -mt-1 flex items-center gap-1.5 text-[11px] text-emerald-700 font-semibold cursor-pointer"><input type="checkbox" id="r_same" class="rounded"> Same as Present Address</label>';
                                    }
                                }
                            } ?>
                        </div>
                        <details class="rounded-xl bg-white border border-slate-200 p-3">
                            <summary class="text-xs font-bold text-slate-700 cursor-pointer"><i class="fa-solid fa-stamp mr-1.5 text-emerald-600"></i>For Official Use Only <span class="font-normal text-slate-400">(অফিস ব্যবহারের জন্য)</span></summary>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-3">
                                <?php foreach ($RF_OFFICE as $k) {
                                    echo exp_field($k, $RF[$k], $inp, $lbl);
                                } ?>
                            </div>
                        </details>
                    </div>
                </div>
            </div>

            <div class="flex gap-2 pt-2 sticky bottom-0 bg-white pb-1">
                <button type="button" onclick="expModal('expFormModal', false)" class="flex-1 sm:flex-none px-5 py-3 sm:py-2.5 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="submit" id="x_submit" class="flex-1 sm:flex-none sm:ml-auto px-6 py-3 sm:py-2.5 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700 shadow-sm">Save Expense</button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE MODAL -->
<div id="expDeleteModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs hidden items-center justify-center p-4 z-50">
    <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl p-6 text-center space-y-4">
        <div class="w-14 h-14 mx-auto rounded-full bg-rose-50 text-rose-600 flex items-center justify-center text-2xl"><i class="fa-solid fa-triangle-exclamation"></i></div>
        <div><h3 class="font-bold text-slate-800">Delete this expense?</h3>
            <p class="text-xs text-slate-500 mt-1"><b id="expDelName"></b> ও তার receipt ছবি স্থায়ীভাবে মুছে যাবে। (সেবা গ্রহণকারীর তথ্য থাকবে)</p></div>
        <div class="flex gap-2">
            <button type="button" onclick="expModal('expDeleteModal', false)" class="flex-1 px-4 py-2.5 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
            <button type="button" id="expDelConfirm" class="flex-1 px-4 py-2.5 bg-rose-600 text-white rounded-xl text-xs font-semibold hover:bg-rose-700">Yes, Delete</button>
        </div>
    </div>
</div>

<!-- VIEW / PRINT MODAL -->
<div id="expViewModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-xs hidden items-end sm:items-center justify-center sm:p-4 z-50">
    <div class="bg-white w-full sm:max-w-3xl max-h-[94vh] flex flex-col rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-4 bg-slate-900 text-white flex justify-between items-center shrink-0">
            <h3 class="font-bold text-sm flex items-center gap-2"><i class="fa-solid fa-file-invoice text-amber-400"></i><span>Expense Voucher</span></h3>
            <button type="button" onclick="expModal('expViewModal', false)" class="text-slate-400 hover:text-white p-1"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <div class="overflow-y-auto bg-slate-100 p-3 sm:p-5"><div id="expVoucher"></div></div>
        <div class="shrink-0 p-3 sm:p-4 bg-white border-t border-slate-200 grid grid-cols-2 sm:flex sm:justify-end gap-2">
            <button type="button" onclick="expModal('expViewModal', false)" class="px-4 py-2.5 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Close</button>
            <button type="button" id="vToggle" class="px-4 py-2.5 rounded-xl text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100"></button>
            <button type="button" id="vEdit" class="px-4 py-2.5 rounded-xl text-xs font-semibold bg-sky-600 text-white hover:bg-sky-700"><i class="fa-solid fa-pen mr-1"></i>Edit</button>
            <button type="button" id="vPrint" class="px-4 py-2.5 rounded-xl text-xs font-semibold bg-emerald-600 text-white hover:bg-emerald-700 flex items-center justify-center gap-2"><i class="fa-solid fa-print"></i> Print / Save PDF</button>
        </div>
    </div>
</div>

<div id="expToast" class="fixed top-4 left-4 right-4 sm:left-auto sm:right-5 sm:w-80 z-[60] hidden px-4 py-3 rounded-xl text-xs font-semibold shadow-lg border"></div>

<script>
(function () {
    var CSRF = <?php echo json_encode($_SESSION['csrf']); ?>;
    var state = <?php echo json_encode($saved); ?>;
    var ACTS = <?php echo json_encode($ACTS, JSON_UNESCAPED_UNICODE); ?>;
    var RECS = <?php echo json_encode($RECS, JSON_UNESCAPED_UNICODE); ?>;
    var RK = <?php echo json_encode($RF_KEYS); ?>;
    var $ = function (id) { return document.getElementById(id); };
    var seq = 0, timer = null, delId = 0;

    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
    function url(act, params) {
        var u = new URL(location.href);
        ['act', 'es', 'ec', 'est', 'ea', 'df', 'dt', 'pg', 'per', 'id', 'edit'].forEach(function (k) { u.searchParams.delete(k); });
        u.searchParams.set('act', act);
        for (var k in (params || {})) { u.searchParams.set(k, params[k]); }
        return u.toString();
    }
    function post(act, fd) { return fetch(url(act), { method: 'POST', body: fd, headers: { 'X-CSRF': CSRF } }).then(function (r) { return r.json(); }); }
    function toast(msg, ok) {
        var t = $('expToast');
        t.className = 'fixed top-4 left-4 right-4 sm:left-auto sm:right-5 sm:w-80 z-[60] px-4 py-3 rounded-xl text-xs font-semibold shadow-lg border ' + (ok ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-rose-50 border-rose-200 text-rose-700');
        t.textContent = msg; clearTimeout(t._t); t._t = setTimeout(function () { t.classList.add('hidden'); }, 4000);
    }
    window.expModal = function (id, show) {
        var m = $(id); m.classList.toggle('hidden', !show); m.classList.toggle('flex', show);
        document.body.style.overflow = show ? 'hidden' : '';
    };

    /* ---------- LIST + STATS ---------- */
    function setStat(k, v) { document.querySelectorAll('[data-stat="' + k + '"]').forEach(function (e) { e.textContent = v; }); }
    function markCards() {
        document.querySelectorAll('[data-fcat],[data-fstatus]').forEach(function (e) {
            var on = e.dataset.fcat ? e.dataset.fcat === state.cat : e.dataset.fstatus === state.status;
            e.classList.toggle('ring-2', on); e.classList.toggle('ring-1', !on); if (e.dataset.ring) { e.classList.toggle(e.dataset.ring, on); }
        });
    }
    function load() {
        var my = ++seq;
        fetch(url('list', { es: state.q, ec: state.cat, est: state.status, ea: state.act, df: state.df, dt: state.dt, pg: state.page, per: state.per }))
            .then(function (r) { return r.json(); })
            .then(function (d) {
                if (my !== seq) { return; }
                if (!d.ok) { toast(d.msg, false); return; }
                state.page = d.page;
                $('expBody').innerHTML = d.rows; $('expPager').innerHTML = d.pager;
                $('expInfo').textContent = d.total ? 'Showing ' + d.from + '–' + d.to + ' of ' + d.total : 'No results';
                var s = d.stats;
                setStat('total_amount', s.total_amount); setStat('total_count', s.total_count);
                for (var k in s.status) { setStat('st_a:' + k, s.status[k].a); setStat('st_c:' + k, '(' + s.status[k].c + ')'); }
                for (var c in s.cats) { setStat('cat_a:' + c, s.cats[c].a); setStat('cat_c:' + c, '(' + s.cats[c].c + ')'); }
                var cl = $('fClear'); cl.classList.toggle('hidden', !d.filtered); cl.classList.toggle('flex', d.filtered);
                $('fBadge').classList.toggle('hidden', !d.filtered);
                markCards();
            })
            .catch(function () { $('expBody').innerHTML = '<tr><td colspan="5" class="p-6 text-center text-rose-500">Load করা যায়নি।</td></tr>'; });
    }
    $('fSearch').addEventListener('input', function () { clearTimeout(timer); var v = this.value; timer = setTimeout(function () { state.q = v.trim(); state.page = 1; load(); }, 300); });
    [['fCat', 'cat'], ['fStatus', 'status'], ['fAct', 'act'], ['fDf', 'df'], ['fDt', 'dt'], ['fPer', 'per']].forEach(function (p) {
        $(p[0]).addEventListener('change', function () { state[p[1]] = this.value; state.page = 1; load(); });
    });
    $('expPager').addEventListener('click', function (e) { var b = e.target.closest('[data-page]'); if (b) { state.page = parseInt(b.dataset.page, 10); load(); } });
    document.addEventListener('click', function (e) {
        var c = e.target.closest('[data-fcat]'), s = e.target.closest('[data-fstatus]');
        if (c) { state.cat = state.cat === c.dataset.fcat ? '' : c.dataset.fcat; $('fCat').value = state.cat; state.page = 1; load(); }
        if (s) { state.status = state.status === s.dataset.fstatus ? '' : s.dataset.fstatus; $('fStatus').value = state.status; state.page = 1; load(); }
    });
    window.expClear = function () {
        fetch(url('clear')).then(function () {
            state = { q: '', cat: '', status: '', act: '', df: '', dt: '', per: state.per, page: 1 };
            ['fSearch', 'fCat', 'fStatus', 'fAct', 'fDf', 'fDt'].forEach(function (i) { $(i).value = ''; }); load();
        });
    };

    /* ---------- ROW ACTIONS ---------- */
    $('expBody').addEventListener('click', function (e) {
        var b = e.target.closest('[data-act]'); if (!b) { return; }
        var id = b.dataset.id, a = b.dataset.act;
        if (a === 'view') { expView(id); }
        if (a === 'edit') { expOpenForm(id); }
        if (a === 'delete') { delId = id; $('expDelName').textContent = b.dataset.name; expModal('expDeleteModal', true); }
        if (a === 'toggle') { var fd = new FormData(); fd.append('id', id); post('toggle', fd).then(function (d) { toast(d.msg, d.ok); load(); }); }
    });
    $('expDelConfirm').addEventListener('click', function () {
        var fd = new FormData(); fd.append('id', delId);
        post('delete', fd).then(function (d) { toast(d.msg, d.ok); expModal('expDeleteModal', false); if (d.ok) { load(); } });
    });

    /* ---------- VIEW (voucher modal + print) ---------- */
    var ORG = <?php echo json_encode(['name' => $site_title ?? '', 'address' => $office_address ?? '', 'phone' => $phone_number ?? '', 'email' => $email_address ?? '', 'logo' => '../public/assets/' . ($favicon_icon ?? '')], JSON_UNESCAPED_UNICODE); ?>;
    var CAT_BADGE = <?php echo json_encode($CAT_BADGES, JSON_UNESCAPED_UNICODE); ?>;
    var viewId = 0, viewNo = '';
    var MON = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    function abs(p) { try { return new URL(p, location.href).href; } catch (x) { return p; } }
    function money(n) { return '৳ ' + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function fdate(s) { if (!s) { return ''; } var p = String(s).slice(0, 10).split('-'); return p.length === 3 ? p[2] + ' ' + MON[+p[1] - 1] + ' ' + p[0] : s; }
    function cell(label, val, span) {
        var v = (val === null || val === undefined || String(val).trim() === '') ? '<span class="text-slate-300">—</span>' : esc(val).replace(/\n/g, '<br>');
        return '<div class="p-2.5 rounded-lg bg-slate-50/80 border border-slate-100 ' + (span || '') + '"><p class="text-[9px] font-bold uppercase tracking-wider text-slate-400">' + label + '</p><p class="text-xs font-semibold text-slate-800 mt-0.5 break-words">' + v + '</p></div>';
    }
    function sec(title, inner) {
        return '<div class="break-inside-avoid"><div class="text-[11px] font-bold uppercase tracking-widest text-emerald-700 border-b border-slate-200 pb-1.5 mb-3">' + title + '</div>' + inner + '</div>';
    }

    function buildVoucher(d) {
        var e = d.e, rec = d.rec, done = e.status === 'Complete';
        var cat = CAT_BADGE[e.category] || CAT_BADGE.Other, logo = esc(abs(ORG.logo)), o = '';
        o += '<div class="relative bg-white border border-slate-200 rounded-xl overflow-hidden text-slate-800">';
        o += '<img src="' + logo + '" alt="" onerror="this.style.display=\'none\'" class="absolute top-1/2 left-1/2 w-3/5 -translate-x-1/2 -translate-y-1/2 opacity-[0.06] pointer-events-none z-0">';
        o += '<div class="h-2 bg-gradient-to-r from-emerald-800 via-emerald-600 to-emerald-400"></div>';
        o += '<div class="relative z-10 px-4 sm:px-8 py-6 space-y-5">';

        // header
        o += '<div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 pb-4 border-b-2 border-emerald-700">' +
             '<div class="flex items-center gap-3"><img src="' + logo + '" alt="Logo" onerror="this.style.display=\'none\'" class="w-16 h-16 object-contain">' +
             '<div><div class="text-lg font-bold text-emerald-800 leading-tight">' + esc(ORG.name) + '</div>' +
             '<div class="text-[11px] text-slate-500 mt-0.5">' + esc(ORG.address) + '</div>' +
             '<div class="text-[11px] text-slate-500">' + esc([ORG.phone, ORG.email].filter(Boolean).join(' | ')) + '</div></div></div>' +
             '<div class="sm:text-right shrink-0"><div class="text-[10px] uppercase tracking-widest text-slate-400">Voucher No</div>' +
             '<div class="text-lg font-bold text-emerald-700">' + esc(e.voucher_no) + '</div>' +
             '<div class="text-[10px] uppercase tracking-widest text-slate-400 mt-1.5">Date</div>' +
             '<div class="text-sm font-bold">' + esc(fdate(e.expense_date)) + '</div></div></div>';

        o += '<div class="text-center text-[15px] font-bold tracking-[3px] text-slate-700">EXPENSE VOUCHER</div>';

        // amount + status
        o += '<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3.5 break-inside-avoid">' +
             '<div class="min-w-0"><div class="text-[10px] uppercase tracking-widest text-emerald-700">Total Amount</div>' +
             '<div class="text-2xl sm:text-3xl font-bold text-emerald-800">' + money(e.amount) + '</div>' +
             '<div class="text-sm font-semibold text-slate-700 mt-1 break-words">' + esc(e.title) + '</div></div>' +
             '<div class="flex sm:flex-col items-center sm:items-end gap-2">' +
             '<span class="px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest border ' + (done ? 'bg-emerald-100 text-emerald-800 border-emerald-300' : 'bg-amber-100 text-amber-800 border-amber-300') + '">' + (done ? '&#10004; ' : '&#9203; ') + esc(e.status) + '</span>' +
             '<span class="px-3 py-1 rounded-full text-[11px] font-bold ' + cat + '">' + esc(e.category) + '</span></div></div>';

        // payment details
        o += '<div class="grid grid-cols-2 sm:grid-cols-4 gap-2.5 break-inside-avoid">' +
             cell('Payment Method', e.payment_method) + cell('Transaction ID', e.transaction_id) + cell('Paid To', e.paid_to) + cell('Recorded On', fdate(e.created_at)) + '</div>';

        // description
        o += sec('Expense Description / খরচের বিবরণ',
             '<div class="text-xs text-slate-600 leading-relaxed bg-slate-50 border-l-[3px] border-emerald-700 px-3 py-2.5 rounded">' +
             (String(e.description || '').trim() ? esc(e.description).replace(/\n/g, '<br>') : '<span class="text-slate-300">কোনো বিবরণ দেওয়া হয়নি।</span>') + '</div>');

        // activity
        if (e.act_title) {
            o += sec('Activity / কার্যক্রম',
                 '<div class="rounded-xl border border-slate-200 overflow-hidden flex flex-col sm:flex-row bg-white/70">' +
                 (e.act_image_url ? '<img src="' + esc(abs(e.act_image_url)) + '" class="w-full sm:w-40 h-32 sm:h-auto object-cover">' : '') +
                 '<div class="p-3.5 flex-1 min-w-0"><div class="flex flex-wrap items-center gap-2"><span class="font-bold text-sm text-slate-800">' + esc(e.act_title) + '</span>' +
                 (e.act_badge ? '<span class="px-2 py-0.5 rounded-md bg-emerald-50 text-emerald-700 text-[10px] font-bold">' + esc(e.act_badge) + '</span>' : '') + '</div>' +
                 (e.act_desc ? '<p class="text-[11px] text-slate-500 mt-1.5">' + esc(e.act_desc) + '</p>' : '') +
                 (d.actT ? '<p class="text-[11px] mt-2 text-slate-600">এই কার্যক্রমে মোট খরচ: <b class="text-emerald-700">' + money(d.actT.s) + '</b> (' + d.actT.c + ' টি এন্ট্রি)</p>' : '') +
                 '</div></div>');
        }

        // recipient (আবেদন পত্র)
        if (rec) {
            var ph = function (u, l) {
                return '<div class="text-center">' + (u ? '<img src="' + esc(abs(u)) + '" class="w-20 h-24 rounded-lg object-cover border border-slate-200">'
                    : '<div class="w-20 h-24 rounded-lg bg-slate-100 border border-dashed border-slate-300 flex items-center justify-center text-slate-300 text-2xl">&#128100;</div>') +
                    '<p class="text-[9px] text-slate-400 mt-1">' + l + '</p></div>';
            };
            o += sec('Service Recipient / সেবা গ্রহণকারীর আবেদন পত্র',
                 '<div class="rounded-xl border border-slate-200 p-3.5 space-y-3 bg-white/70">' +
                 '<div class="flex items-start gap-3"><div class="flex gap-2 shrink-0">' + ph(rec.photo_url, 'Photo') + ph(rec.guardian_url, 'Guardian') + '</div>' +
                 '<div class="min-w-0"><p class="text-[10px] font-bold text-emerald-700">' + esc(rec.serial_no) + '</p>' +
                 '<p class="text-base font-bold text-slate-800 break-words">' + esc(rec.member_name) + '</p>' +
                 '<p class="text-xs text-slate-500">&#9990; ' + esc(rec.mobile_no || '—') + '</p>' +
                 (d.recT ? '<p class="text-[11px] mt-1.5 text-slate-600">মোট পেয়েছেন: <b class="text-emerald-700">' + money(d.recT.s) + '</b> (' + d.recT.c + ' বার)</p>' : '') + '</div></div>' +
                 '<div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">' +
                 cell("Mother's Name", rec.mother_name) + cell("Father/Husband's Name", rec.father_husband_name) + cell('Date of Birth', fdate(rec.dob)) +
                 cell('Gender', rec.gender) + cell('National ID No', rec.nid_no) + cell('Birth Certificate No', rec.birth_cert_no) +
                 cell('Blood Group', rec.blood_group) + cell('Height', rec.height) + cell('Type of Disabled', rec.disability_type) +
                 cell('Financial Status', rec.financial_status) + cell('Social Status', rec.social_status) + cell('E-mail', rec.email) +
                 cell('Present Address', rec.present_address, 'col-span-2 sm:col-span-3') + cell('Permanent Address', rec.permanent_address, 'col-span-2 sm:col-span-3') +
                 cell("Other's Information", rec.other_info, 'col-span-2 sm:col-span-3') + '</div>' +
                 '<div class="inline-block rounded-md bg-emerald-600 text-white text-[10px] font-bold px-2.5 py-1">For Official Use Only</div>' +
                 '<div class="grid grid-cols-2 sm:grid-cols-3 gap-2.5">' + cell('Application Date', fdate(rec.application_date)) + cell('Membership No', rec.membership_no) +
                 cell('Registration No', rec.registration_no) + cell('Remarks', rec.remarks, 'col-span-2 sm:col-span-3') + '</div></div>');
        }

        // receipt (print a ashbe na)
        if (e.receipt_url) {
            o += '<div class="no-print"><div class="text-[11px] font-bold uppercase tracking-widest text-emerald-700 border-b border-slate-200 pb-1.5 mb-3">Receipt / Voucher Image</div>' +
                 '<a href="' + esc(abs(e.receipt_url)) + '" target="_blank"><img src="' + esc(abs(e.receipt_url)) + '" class="max-h-80 rounded-lg border border-slate-200"></a></div>';
        }

        // signatures + footer
        o += '<div class="flex justify-between items-end gap-4 pt-8 break-inside-avoid">' +
             '<div class="text-center w-36 sm:w-40"><div class="border-t border-slate-700 mb-1"></div><div class="text-[10px] uppercase tracking-widest text-slate-500">Prepared By</div></div>' +
             '<div class="text-center w-36 sm:w-40"><div class="border-t border-slate-700 mb-1"></div><div class="text-[10px] uppercase tracking-widest text-slate-500">Authorized Signature</div></div></div>';
        o += '<div class="text-center text-[10px] text-slate-400">This is a computer generated expense voucher.</div>';
        o += '</div><div class="h-2 bg-gradient-to-r from-emerald-800 via-emerald-600 to-emerald-400"></div></div>';
        return o;
    }

    window.expView = function (id) {
        fetch(url('view', { id: id })).then(function (r) { return r.json(); }).then(function (d) {
            if (!d.ok) { toast(d.msg, false); return; }
            viewId = d.e.id; viewNo = d.e.voucher_no;
            $('expVoucher').innerHTML = buildVoucher(d);
            var done = d.e.status === 'Complete';
            $('vToggle').innerHTML = done ? '<i class="fa-solid fa-hourglass-half mr-1"></i>Mark Pending' : '<i class="fa-solid fa-circle-check mr-1"></i>Mark Complete';
            $('vToggle').className = 'px-4 py-2.5 rounded-xl text-xs font-semibold ' + (done ? 'bg-amber-50 text-amber-700 border border-amber-200 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-700 border border-emerald-200 hover:bg-emerald-100');
            expModal('expViewModal', true);
        }).catch(function () { toast('Load করা যায়নি।', false); });
    };
    $('vToggle').addEventListener('click', function () {
        var fd = new FormData(); fd.append('id', viewId);
        post('toggle', fd).then(function (d) { toast(d.msg, d.ok); expView(viewId); load(); });
    });
    $('vEdit').addEventListener('click', function () { expModal('expViewModal', false); expOpenForm(viewId); });
    $('vPrint').addEventListener('click', function () {
        // Page a jei Tailwind (CSS file ba CDN script) ache, print window te o pathano
        var head = '';
        document.querySelectorAll('link[rel="stylesheet"]').forEach(function (l) { head += '<link rel="stylesheet" href="' + l.href + '">'; });
        document.querySelectorAll('script[src*="tailwind"]').forEach(function (s) { head += '<script src="' + s.src + '"><\/script>'; });
        var win = window.open('', '_blank', 'width=900,height=900');
        if (!win) { toast('Pop-up block করা আছে। অনুমতি দিন।', false); return; }
        win.document.write('<html><head><meta charset="UTF-8"><title>Expense Voucher ' + esc(viewNo) + '</title>' + head +
            '<style>@page{size:A4;margin:10mm;} body{margin:0;background:#fff;} .no-print{display:none!important;} *{-webkit-print-color-adjust:exact;print-color-adjust:exact;}</style>' +
            '</head><body>' + $('expVoucher').innerHTML + '</body></html>');
        win.document.close();
        setTimeout(function () { win.focus(); win.print(); }, 1200);   // Tailwind o chobi load howar jonno opekkha
    });

    /* ---------- FORM: helpers ---------- */
    function prev(inputId, imgId, iconId) {
        $(inputId).addEventListener('change', function () {
            var f = this.files[0]; if (!f) { return; }
            if (f.size > 3 * 1024 * 1024) { toast('ছবির সাইজ সর্বোচ্চ 3MB।', false); this.value = ''; return; }
            var r = new FileReader(); r.onload = function (ev) { setImg(imgId, iconId, ev.target.result); }; r.readAsDataURL(f);
        });
    }
    function setImg(imgId, iconId, src) { $(imgId).classList.toggle('hidden', !src); $(iconId).classList.toggle('hidden', !!src); $(imgId).src = src || ''; }
    prev('x_receipt', 'x_prev', 'x_prevIcon'); prev('r_photo', 'r_photo_prev', 'r_photo_icon'); prev('r_guardian_photo', 'r_guardian_photo_prev', 'r_guardian_photo_icon');

    function mode() { return document.querySelector('input[name=recipient_mode]:checked').value; }
    function updRecUI() {
        var m = mode();
        $('rWrap').classList.toggle('hidden', m === 'none');
        $('rPick').classList.toggle('hidden', m !== 'existing');
        $('rFields').classList.toggle('hidden', m === 'none' || (m === 'existing' && !$('x_recipient_id').value));
    }
    function clearRec() {
        RK.forEach(function (k) { $('r_' + k).value = ''; });
        ['r_photo', 'r_guardian_photo'].forEach(function (i) { $(i).value = ''; setImg(i + '_prev', i + '_icon', ''); });
        $('r_serial').textContent = 'Auto'; $('r_same').checked = false;
    }
    function fillRec(d) {
        RK.forEach(function (k) { $('r_' + k).value = d[k] == null ? '' : d[k]; });
        setImg('r_photo_prev', 'r_photo_icon', d.photo_url); setImg('r_guardian_photo_prev', 'r_guardian_photo_icon', d.guardian_url);
        $('r_photo').value = ''; $('r_guardian_photo').value = '';
        $('r_serial').textContent = d.serial_no || 'Auto';
    }
    function renderRecOptions(sel) {
        var q = $('x_recSearch').value.trim().toLowerCase(), cur = sel != null ? String(sel) : $('x_recipient_id').value;
        var h = '<option value="">-- Select recipient --</option>';
        RECS.forEach(function (r) {
            if (q && (r.member_name + ' ' + r.serial_no + ' ' + r.mobile_no).toLowerCase().indexOf(q) === -1 && String(r.id) !== cur) { return; }
            h += '<option value="' + r.id + '"' + (String(r.id) === cur ? ' selected' : '') + '>' + esc(r.serial_no + ' — ' + r.member_name + (r.mobile_no ? ' (' + r.mobile_no + ')' : '')) + '</option>';
        });
        $('x_recipient_id').innerHTML = h;
    }
    function renderActs(cur) {
        var h = '<option value="">— None —</option>';
        ACTS.forEach(function (a) { if (a.status === 'active' || String(a.id) === String(cur)) { h += '<option value="' + a.id + '"' + (String(a.id) === String(cur) ? ' selected' : '') + '>' + esc(a.title) + '</option>'; } });
        $('x_activity_id').innerHTML = h;
    }
    function loadRecs() { return fetch(url('recs')).then(function (r) { return r.json(); }).then(function (d) { if (d.ok) { RECS = d.recs; } }); }

    document.querySelectorAll('input[name=recipient_mode]').forEach(function (r) {
        r.addEventListener('change', function () { if (mode() === 'new') { clearRec(); } if (mode() === 'existing') { $('x_recipient_id').value = ''; clearRec(); } updRecUI(); });
    });
    $('x_recSearch').addEventListener('input', function () { renderRecOptions(); });
    $('x_recipient_id').addEventListener('change', function () {
        if (!this.value) { clearRec(); updRecUI(); return; }
        fetch(url('rget', { id: this.value })).then(function (r) { return r.json(); }).then(function (d) { if (d.ok) { fillRec(d.data); updRecUI(); } else { toast(d.msg, false); } });
    });
    $('r_same').addEventListener('change', function () { if (this.checked) { $('r_permanent_address').value = $('r_present_address').value; } });

    /* ---------- FORM: open ---------- */
    var xf = ['title', 'category', 'amount', 'expense_date', 'status', 'payment_method', 'transaction_id', 'paid_to', 'description'];
    window.expOpenForm = function (id) {
        $('expForm').reset(); $('x_id').value = 0; setImg('x_prev', 'x_prevIcon', ''); clearRec(); $('x_recSearch').value = '';
        $('x_removeWrap').classList.add('hidden'); $('x_removeWrap').classList.remove('flex');
        $('expFormTitle').textContent = 'Add Expense';
        $('x_expense_date').value = new Date().toISOString().slice(0, 10);
        document.querySelector('input[name=recipient_mode][value=none]').checked = true;
        renderActs(''); renderRecOptions(''); updRecUI();
        if (!id) { expModal('expFormModal', true); return; }

        $('expFormTitle').textContent = 'Edit Expense';
        fetch(url('get', { id: id })).then(function (r) { return r.json(); }).then(function (d) {
            if (!d.ok) { toast(d.msg, false); return; }
            $('x_id').value = d.data.id;
            xf.forEach(function (k) { $('x_' + k).value = d.data[k] == null ? '' : d.data[k]; });
            if (d.act && !ACTS.some(function (a) { return String(a.id) === String(d.act.id); })) { ACTS.push(d.act); }
            renderActs(d.data.activity_id || '');
            setImg('x_prev', 'x_prevIcon', d.data.receipt_url);
            if (d.data.receipt_url) { $('x_removeWrap').classList.remove('hidden'); $('x_removeWrap').classList.add('flex'); }
            if (d.rec) {
                if (!RECS.some(function (r) { return String(r.id) === String(d.rec.id); })) { RECS.unshift(d.rec); }
                document.querySelector('input[name=recipient_mode][value=existing]').checked = true;
                renderRecOptions(d.rec.id); fillRec(d.rec);
            }
            updRecUI(); expModal('expFormModal', true);
        });
    };

    $('expForm').addEventListener('submit', function (e) {
        e.preventDefault();
        var m = mode();
        if (!$('x_title').value.trim() || !$('x_expense_date').value || !(parseFloat($('x_amount').value) > 0)) { toast('শিরোনাম, তারিখ ও সঠিক Amount দিন।', false); return; }
        if (!$('x_activity_id').value && m === 'none') { toast('কোনো কার্যক্রম অথবা সেবা গ্রহণকারী নির্বাচন করুন।', false); return; }
        if (m === 'existing' && !$('x_recipient_id').value) { toast('সেবা গ্রহণকারী সিলেক্ট করুন।', false); return; }
        if (m !== 'none' && !$('r_member_name').value.trim()) { toast('সেবা গ্রহণকারীর নাম দিন।', false); return; }
        var btn = $('x_submit'); btn.disabled = true; btn.textContent = 'Saving...';
        post('save', new FormData(this)).then(function (d) {
            toast(d.msg, d.ok);
            if (d.ok) { expModal('expFormModal', false); loadRecs(); load(); }
        }).catch(function () { toast('Server error.', false); }).finally(function () { btn.disabled = false; btn.textContent = 'Save Expense'; });
    });

    ['expFormModal', 'expDeleteModal', 'expViewModal'].forEach(function (id) { $(id).addEventListener('mousedown', function (e) { if (e.target === this) { expModal(id, false); } }); });
    load();
    var ed = new URL(location.href).searchParams.get('edit'); if (ed) { expOpenForm(ed); }   // view page theke Edit
})();
</script>
