<?php
/* ==========================================================
   my_donations.php  ->  index.php?page=my_donations
   Logged-in user-er nijer Donation page:
   Add donation | Report (month/year, total/paid/pending) | History
   View / Edit / Delete | Invoice | Pay Slip (PNG/PDF)
   Rules:
   - Notun donation shob shomoy "Pending" hoye jay (Admin verify kore Paid kore)
   - Edit/Delete shudhu Pending / Failed / Rejected donation-e. Paid = locked
   - Pay Slip shudhu Paid donation-er
   ========================================================== */
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
if (!isset($_SESSION['user_type'])) {
    header('Location: index.php?page=dashboard');
    exit;
}
$me = (int) ($_SESSION['user_id'] ?? 0);
$connection = isset($db) ? $db : (isset($conn) ? $conn : null);
if (!function_exists('h')) {
    function h($s)
    {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }
}

$restricted = '<section class="page-content max-w-xl mx-auto"><div class="p-6 bg-white rounded-2xl ring-1 ring-slate-200 text-center space-y-2"><i class="fa-solid fa-lock text-3xl text-slate-300"></i>
    <h3 class="font-bold text-slate-800">Access Restricted</h3><p class="text-xs text-slate-500">Your account does not have permission to access this page.</p></div></section>';
if ($me <= 0 || !$connection) {
    echo $restricted;
    return;
}
mysqli_set_charset($connection, 'utf8mb4');
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

function md_rows($db, $sql, $t = '', $p = [])
{
    $st = mysqli_prepare($db, $sql);
    if (!$st) {
        return [];
    }
    if ($t !== '') {
        mysqli_stmt_bind_param($st, $t, ...$p);
    }
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    $o = [];
    while ($res && $r = mysqli_fetch_assoc($res)) {
        $o[] = $r;
    }
    mysqli_stmt_close($st);
    return $o;
}
function md_run($db, $sql, $t, $p)
{
    $st = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($st, $t, ...$p);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}
function md_tk($n)
{
    return '৳' . number_format((float) $n, 2);
}

$u = md_rows($connection, "SELECT member_name, email, mobile_no FROM users WHERE id = ? LIMIT 1", 'i', [$me])[0] ?? null;
if (!$u) {
    echo $restricted;
    return;
}

/* ---------- "Amar donation" mane ki: ID / email / phone (profile page-er moto) ---------- */
$email = trim((string) $u['email']);
$digits = preg_replace('/\D/', '', (string) $u['mobile_no']);
$last10 = strlen($digits) >= 10 ? substr($digits, -10) : '';
$OWN = "(d.donor_id = ? OR (? <> '' AND d.email = ?) OR (? <> '' AND RIGHT(d.phone, 10) = ?))";
$OWN_T = 'issss';
$OWN_P = [$me, $email, $email, $last10, $last10];

$methods = ['bKash', 'Nagad', 'Rocket', 'Upay', 'SureCash', 'Bank', 'Cash'];
$types = ['General', 'Monthly'];
$funds = array_column(md_rows($connection, "SELECT title FROM donation_sectors WHERE status = 'active' ORDER BY id DESC"), 'title');
$receipt_dir = dirname($_SERVER['SCRIPT_FILENAME']) . '/uploads/receipts/';
$editable = ['pending', 'failed', 'rejected'];

function md_upload($f, $dir, &$err)
{
    if (!is_array($f) || $f['error'] === UPLOAD_ERR_NO_FILE) {
        return '';
    }
    if ($f['error'] !== UPLOAD_ERR_OK) {
        $err = 'Receipt আপলোডে সমস্যা হয়েছে।';
        return false;
    }
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    $fi = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($fi, $f['tmp_name']);
    finfo_close($fi);
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp']) || !in_array($mime, ['image/jpeg', 'image/png', 'image/webp'])) {
        $err = 'Receipt শুধু JPG, PNG বা WEBP ছবি হতে হবে।';
        return false;
    }
    if ($f['size'] > 5 * 1024 * 1024) {
        $err = 'Receipt এর সাইজ ৫MB এর বেশি হতে পারবে না।';
        return false;
    }
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name = time() . '_' . rand(1000, 9999) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . $name)) {
        $err = 'Receipt সেভ করা যায়নি (ফোল্ডার পারমিশন চেক করুন)।';
        return false;
    }
    return $name;
}
function md_unlink($dir, $f)
{
    if ($f !== '' && is_file($dir . basename($f))) {
        @unlink($dir . basename($f));
    }
}

/* ==========================================================
   ACTIONS: add / edit / delete  (POST -> redirect + popup)
   ========================================================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ok = false;
    $msg = '';
    $do = $_POST['do'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);

    if (!hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
        $msg = 'Session expired. Please reload the page.';
    } elseif ($do === 'delete') {
        $row = md_rows($connection, "SELECT d.id, d.receipt, d.payment_status FROM donations d WHERE d.id = ? AND $OWN LIMIT 1", 'i' . $OWN_T, array_merge([$id], $OWN_P))[0] ?? null;
        if (!$row) {
            $msg = 'Donation পাওয়া যায়নি।';
        } elseif (!in_array(strtolower($row['payment_status']), $editable)) {
            $msg = 'Paid donation মোছা যাবে না।';
        } else {
            md_run($connection, "DELETE FROM donations WHERE id = ?", 'i', [$id]);
            md_unlink($receipt_dir, (string) $row['receipt']);
            $ok = true;
            $msg = 'Donation deleted successfully. / ডোনেশন মুছে ফেলা হয়েছে।';
        }
    } elseif ($do === 'add' || $do === 'edit') {
        $amount = (float) ($_POST['amount'] ?? 0);
        $dtype = $_POST['donation_type'] ?? '';
        $fund = trim($_POST['fund'] ?? '');
        $method = $_POST['payment_method'] ?? '';
        $txn = trim($_POST['transaction_id'] ?? '');
        $date = (!empty($_POST['donation_date']) && strtotime($_POST['donation_date'])) ? date('Y-m-d', strtotime($_POST['donation_date'])) : date('Y-m-d');
        $old = null;
        if ($do === 'edit') {
            $old = md_rows($connection, "SELECT d.id, d.receipt, d.payment_status FROM donations d WHERE d.id = ? AND $OWN LIMIT 1", 'i' . $OWN_T, array_merge([$id], $OWN_P))[0] ?? null;
        }
        if ($do === 'edit' && !$old) {
            $msg = 'Donation পাওয়া যায়নি।';
        } elseif ($do === 'edit' && !in_array(strtolower($old['payment_status']), $editable)) {
            $msg = 'Paid donation এডিট করা যাবে না।';
        } elseif ($amount <= 0) {
            $msg = 'সঠিক Amount দিন।';
        } elseif (!in_array($dtype, $types) || $fund === '' || !in_array($method, $methods)) {
            $msg = 'Donation Type, Fund ও Payment Method বেছে নিন।';
        } elseif ($date > date('Y-m-d')) {
            $msg = 'ভবিষ্যতের তারিখ দেওয়া যাবে না।';
        } elseif ($method !== 'Cash' && $txn === '') {
            $msg = 'Transaction ID দিন (Cash ছাড়া)।';
        } else {
            $dup = ($txn !== '') ? md_rows($connection, "SELECT id FROM donations WHERE transaction_id = ? AND id <> ? LIMIT 1", 'si', [$txn, $id]) : [];
            if ($dup) {
                $msg = 'এই Transaction ID আগেই ব্যবহার করা হয়েছে।';
            } else {
                $err = '';
                $new = md_upload($_FILES['receipt'] ?? null, $receipt_dir, $err);
                if ($new === false) {
                    $msg = $err;
                } elseif ($do === 'add') {
                    $ok = md_run(
                        $connection,
                        "INSERT INTO donations (donor_id, type, name, email, phone, amount, donation_type, fund, payment_method, transaction_id, payment_status, donation_date, admin_note, receipt, created_at, updated_at)
                        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())",
                        'issssdssssssss',
                        [$me, 'Member', $u['member_name'], $u['email'], $u['mobile_no'], $amount, $dtype, $fund, $method, $txn, 'pending', $date, '', $new]
                    );
                    $msg = $ok ? 'Donation submitted! Admin verify korle Paid hobe. / ডোনেশন জমা হয়েছে।' : 'ডাটা সেভ হয়নি।';
                    if (!$ok) {
                        md_unlink($receipt_dir, $new);
                    }
                } else {
                    $receipt = $new !== '' ? $new : (string) $old['receipt'];
                    $ok = md_run(
                        $connection,
                        "UPDATE donations SET amount=?, donation_type=?, fund=?, payment_method=?, transaction_id=?, payment_status='pending', donation_date=?, receipt=?, updated_at=NOW() WHERE id=?",
                        'dssssssi',
                        [$amount, $dtype, $fund, $method, $txn, $date, $receipt, $id]
                    );
                    if ($ok && $new !== '') {
                        md_unlink($receipt_dir, (string) $old['receipt']);
                    }
                    if (!$ok) {
                        md_unlink($receipt_dir, $new);
                    }
                    $msg = $ok ? 'Donation updated! আবার verify-এর জন্য Pending হয়েছে।' : 'আপডেট হয়নি।';
                }
            }
        }
    }
    $_SESSION['md_flash'] = [$ok, $msg];
    header('Location: ' . $_SERVER['REQUEST_URI']);
    exit;
}
$flash = $_SESSION['md_flash'] ?? null;
unset($_SESSION['md_flash']);

/* ==========================================================
   REPORT DATA
   ========================================================== */
$year = (int) ($_GET['y'] ?? 0) ?: (int) date('Y');
$mo = max(0, min(12, (int) ($_GET['mo'] ?? 0)));
$fs = in_array($_GET['st'] ?? '', ['paid', 'pending', 'failed', 'rejected']) ? $_GET['st'] : '';
$pg = max(1, (int) ($_GET['pg'] ?? 1));
function md_url($o = [])
{
    global $year, $mo, $fs;
    return 'index.php?' . http_build_query(array_merge(['page' => $_GET['page'] ?? 'my_donations', 'y' => $year, 'mo' => $mo ?: '', 'st' => $fs], $o));
}

$SUMS = "COUNT(*) c, COALESCE(SUM(d.amount),0) tot, COALESCE(SUM(CASE WHEN d.payment_status='paid' THEN d.amount END),0) paid,
         COALESCE(SUM(CASE WHEN d.payment_status='pending' THEN d.amount END),0) pend, COALESCE(SUM(d.payment_status IN ('failed','rejected')),0) bad";
$yearRows = md_rows($connection, "SELECT YEAR(d.donation_date) y, $SUMS FROM donations d WHERE $OWN GROUP BY y ORDER BY y DESC", $OWN_T, $OWN_P);
$all = ['c' => 0, 'tot' => 0, 'paid' => 0, 'pend' => 0, 'bad' => 0];
foreach ($yearRows as $r) {
    foreach ($all as $k => $_) {
        $all[$k] += $r[$k];
    }
}
$years = array_unique(array_merge([(int) date('Y')], array_map('intval', array_column($yearRows, 'y'))));
rsort($years);

$monthRows = [];
foreach (md_rows($connection, "SELECT MONTH(d.donation_date) m, $SUMS FROM donations d WHERE $OWN AND YEAR(d.donation_date) = ? GROUP BY m", $OWN_T . 'i', array_merge($OWN_P, [$year])) as $r) {
    $monthRows[(int) $r['m']] = $r;
}
$fundRows = md_rows($connection, "SELECT IF(d.fund='','General',d.fund) k, SUM(d.amount) v FROM donations d WHERE $OWN AND YEAR(d.donation_date) = ? AND d.payment_status = 'paid' GROUP BY k ORDER BY v DESC", $OWN_T . 'i', array_merge($OWN_P, [$year]));
$last = md_rows($connection, "SELECT MAX(d.donation_date) m FROM donations d WHERE $OWN AND d.payment_status = 'paid'", $OWN_T, $OWN_P)[0]['m'] ?? null;

$w = "WHERE $OWN AND YEAR(d.donation_date) = ?";
$t = $OWN_T . 'i';
$p = array_merge($OWN_P, [$year]);
if ($mo) {
    $w .= " AND MONTH(d.donation_date) = ?";
    $t .= 'i';
    $p[] = $mo;
}
if ($fs !== '') {
    $w .= " AND d.payment_status = ?";
    $t .= 's';
    $p[] = $fs;
}
$total = (int) (md_rows($connection, "SELECT COUNT(*) c FROM donations d $w", $t, $p)[0]['c'] ?? 0);
$pages = max(1, (int) ceil($total / 10));
$pg = min($pg, $pages);
$list = md_rows($connection, "SELECT d.* FROM donations d $w ORDER BY d.donation_date DESC, d.id DESC LIMIT 10 OFFSET " . (($pg - 1) * 10), $t, $p);
foreach ($list as &$d) {
    $d['receipt_url'] = ($d['receipt'] !== '' && is_file($receipt_dir . basename($d['receipt']))) ? 'uploads/receipts/' . $d['receipt'] : '';
    $d['can_edit'] = in_array(strtolower($d['payment_status']), $editable);
}
unset($d);

$MN = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$ST = ['paid' => 'bg-emerald-100 text-emerald-700', 'pending' => 'bg-amber-100 text-amber-700', 'failed' => 'bg-orange-100 text-orange-700', 'rejected' => 'bg-rose-100 text-rose-700'];
$ORG = ['name' => $site_title ?? '', 'address' => $office_address ?? '', 'phone' => $phone_number ?? '', 'email' => $email_address ?? '', 'logo' => '../public/assets/' . ($favicon_icon ?? '')];
$inp = 'w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10';
$lbl = 'block text-[11px] font-bold uppercase text-slate-500 mb-1.5';

/* ---------- Donation form (add + edit duijoner jonno ek-i) ---------- */
function md_form($p, $funds, $methods, $types, $inp, $lbl)
{
    ob_start(); ?>
    <div>
        <label class="<?= $lbl ?>">Amount / পরিমাণ (৳) <span class="text-rose-500">*</span></label>
        <div class="flex flex-wrap gap-2 mb-2">
            <?php foreach ([500, 1000, 2000, 5000, 10000] as $a) { ?><button type="button" data-chip="<?= $a ?>"
                    data-target="<?= $p ?>amount"
                    class="px-3.5 py-1.5 rounded-full border border-slate-200 bg-white text-xs font-bold text-slate-600 hover:border-emerald-500 hover:text-emerald-700">৳<?= number_format($a) ?></button><?php } ?>
        </div>
        <div class="relative"><span
                class="absolute inset-y-0 left-0 pl-4 flex items-center text-slate-400 font-bold">৳</span>
            <input type="number" min="1" step="0.01" name="amount" id="<?= $p ?>amount" required placeholder="Enter amount"
                class="<?= $inp ?> pl-9 text-lg font-bold">
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div><label class="<?= $lbl ?>">Donation Type <span class="text-rose-500">*</span></label>
            <div class="grid grid-cols-2 gap-2"><?php foreach ($types as $i => $ty) { ?>
                    <label
                        class="flex items-center justify-center gap-1.5 p-3 rounded-xl border border-slate-200 text-sm font-semibold text-slate-600 cursor-pointer has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 has-[:checked]:text-emerald-700">
                        <input type="radio" name="donation_type" value="<?= $ty ?>" class="sr-only" <?= $i === 0 ? 'checked' : '' ?>><i
                            class="fa-solid <?= $ty === 'Monthly' ? 'fa-rotate' : 'fa-heart' ?>"></i><?= $ty ?></label><?php } ?>
            </div>
        </div>
        <div><label class="<?= $lbl ?>">Fund / তহবিল <span class="text-rose-500">*</span></label>
            <select name="fund" id="<?= $p ?>fund" required class="<?= $inp ?>">
                <option value="">Select fund</option>
                <?php foreach ($funds as $f) {
                    echo '<option>' . h($f) . '</option>';
                } ?>
            </select>
        </div>
    </div>
    <div><label class="<?= $lbl ?>">Payment Method / পেমেন্ট মাধ্যম <span class="text-rose-500">*</span></label>
        <div class="grid grid-cols-3 sm:grid-cols-4 gap-2"><?php foreach ($methods as $m) { ?>
                <label
                    class="flex items-center justify-center p-2.5 rounded-xl border border-slate-200 text-xs font-bold text-slate-600 cursor-pointer text-center has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 has-[:checked]:text-emerald-700">
                    <input type="radio" name="payment_method" value="<?= $m ?>" required
                        class="sr-only"><?= $m === 'SureCash' ? 'Sure Cash' : $m ?></label><?php } ?>
        </div>
    </div>
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        <div><label class="<?= $lbl ?>">Transaction ID <small class="normal-case">(Cash ছাড়া আবশ্যক)</small></label><input
                type="text" name="transaction_id" id="<?= $p ?>txn" placeholder="e.g. 9AB7XYZ123" class="<?= $inp ?>"></div>
        <div><label class="<?= $lbl ?>">Donation Date / তারিখ</label><input type="date" name="donation_date"
                id="<?= $p ?>date" max="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" class="<?= $inp ?>"></div>
    </div>
    <div><label class="<?= $lbl ?>">Receipt / Screenshot <small class="normal-case">(optional • JPG, PNG, WEBP • max
                5MB)</small></label>
        <input type="file" name="receipt" accept="image/png,image/jpeg,image/webp"
            class="text-sm w-full file:mr-3 file:px-4 file:py-2 file:rounded-lg file:border-0 file:bg-slate-100 file:text-xs file:font-semibold">
    </div>
    <?php return ob_get_clean();
}
?>
<style>
    @keyframes mdIn {
        from {
            opacity: 0;
            transform: translateX(40px);
        }

        to {
            opacity: 1;
            transform: none;
        }
    }

    @keyframes mdOut {
        to {
            opacity: 0;
            transform: translateX(40px);
        }
    }

    @keyframes mdBar {
        from {
            width: 100%;
        }

        to {
            width: 0;
        }
    }

    @media print {
        .no-print {
            display: none !important;
        }
    }
</style>

<?php if ($flash) { ?>
    <div id="mdToast"
        class="fixed top-5 right-5 z-[80] w-80 max-w-[calc(100vw-2.5rem)] bg-white rounded-2xl shadow-2xl border <?= $flash[0] ? 'border-emerald-200' : 'border-rose-200' ?> overflow-hidden no-print"
        style="animation:mdIn .35s ease-out">
        <div class="p-4 flex items-start gap-3">
            <span
                class="w-9 h-9 rounded-full flex items-center justify-center shrink-0 text-lg <?= $flash[0] ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600' ?>"><i
                    class="fa-solid <?= $flash[0] ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i></span>
            <div class="min-w-0 flex-1">
                <p class="text-sm font-bold <?= $flash[0] ? 'text-emerald-700' : 'text-rose-700' ?>">
                    <?= $flash[0] ? 'Success' : 'Error' ?></p>
                <p class="text-xs text-slate-600 mt-0.5 break-words"><?= h($flash[1]) ?></p>
            </div>
            <button type="button" onclick="mdHide()" class="text-slate-300 hover:text-slate-500"><i
                    class="fa-solid fa-xmark"></i></button>
        </div>
        <div class="h-1 <?= $flash[0] ? 'bg-emerald-500' : 'bg-rose-500' ?>" style="animation:mdBar 4.5s linear forwards">
        </div>
    </div>
    <script>function mdHide() { var t = document.getElementById('mdToast'); if (!t) return; t.style.animation = 'mdOut .35s ease-in forwards'; setTimeout(function () { t.remove() }, 360) } setTimeout(mdHide, 4500);</script>
<?php } ?>

<section class="page-content space-y-6 max-w-6xl mx-auto">
    <!-- Hero -->
    <div
        class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-slate-800 to-emerald-700 text-white p-5 sm:p-8">
        <div class="absolute -right-10 -top-10 h-56 w-56 rounded-full bg-emerald-400/20 blur-3xl pointer-events-none">
        </div>
        <div class="relative flex flex-col md:flex-row md:items-center justify-between gap-4">
            <div>
                <p class="text-xs text-emerald-200 font-semibold"><i
                        class="fa-solid fa-hand-holding-heart mr-1.5"></i>My Donations / আমার ডোনেশন</p>
                <h2 class="text-2xl sm:text-3xl font-extrabold tracking-tight mt-1 break-words">
                    <?= h($u['member_name']) ?></h2>
                <p class="text-xs text-slate-300 mt-1">ID #<?= $me ?> •
                    +88<?= h($u['mobile_no']) ?><?= $last ? ' • Last paid: ' . h(date('d M Y', strtotime($last))) : '' ?>
                </p>
            </div>
            <div class="rounded-2xl bg-white/10 border border-white/15 backdrop-blur px-5 py-4 md:text-right">
                <p class="text-[10px] font-bold uppercase tracking-widest text-emerald-300">Total Paid</p>
                <p class="text-3xl font-extrabold"><?= md_tk($all['paid']) ?></p>
            </div>
        </div>
    </div>

    <!-- Add donation -->
    <div class="bg-white rounded-3xl ring-1 ring-slate-200/80 shadow-sm overflow-hidden no-print">
        <div class="px-5 sm:px-8 py-4 border-b border-slate-100 flex items-center gap-3 bg-emerald-50/50">
            <span class="w-10 h-10 rounded-xl bg-emerald-600 text-white flex items-center justify-center"><i
                    class="fa-solid fa-circle-plus"></i></span>
            <div>
                <h3 class="font-bold text-slate-800">Make a Donation <small class="font-medium text-slate-400">/ নতুন
                        ডোনেশন</small></h3>
                <p class="text-[11px] text-slate-500">Submit your payment details. Admin will verify and mark it as
                    Paid.</p>
            </div>
        </div>
        <form method="POST" action="" enctype="multipart/form-data" class="p-5 sm:p-8 space-y-5" autocomplete="off">
            <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="do"
                value="add">
            <?= md_form('a_', $funds, $methods, $types, $inp, $lbl) ?>
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 pt-2">
                <p class="text-[11px] text-slate-400"><i class="fa-solid fa-lock text-emerald-500 mr-1"></i>Status will
                    be <b>Pending</b> until verified by Admin.</p>
                <button
                    class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-gradient-to-r from-emerald-600 to-teal-600 hover:from-emerald-700 hover:to-teal-700 text-white font-bold text-sm shadow-lg shadow-emerald-600/25 flex items-center justify-center gap-2"><i
                        class="fa-solid fa-paper-plane"></i> Submit Donation</button>
            </div>
        </form>
    </div>

    <!-- KPI cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <?php foreach ([
            ['Total Deposit', 'মোট জমা', md_tk($all['tot']), $all['c'] . ' donation(s)', 'sky', 'fa-sack-dollar'],
            ['Paid', 'গৃহীত', md_tk($all['paid']), 'Verified by admin', 'emerald', 'fa-circle-check'],
            ['Pending', 'অপেক্ষমান', md_tk($all['pend']), 'Waiting for verification', 'amber', 'fa-hourglass-half'],
            ['Failed / Rejected', 'বাতিল', (int) $all['bad'], 'Needs attention', 'rose', 'fa-circle-xmark']
        ] as $c) { ?>
            <div class="p-4 sm:p-5 bg-white rounded-2xl ring-1 ring-slate-200/80">
                <div class="flex items-center justify-between">
                    <p class="text-[10px] font-bold uppercase text-slate-400"><?= $c[0] ?> <span
                            class="normal-case font-medium">/ <?= $c[1] ?></span></p>
                    <span
                        class="w-8 h-8 rounded-lg bg-<?= $c[4] ?>-50 text-<?= $c[4] ?>-600 flex items-center justify-center text-sm"><i
                            class="fa-solid <?= $c[5] ?>"></i></span>
                </div>
                <h3 class="text-lg sm:text-2xl font-bold text-slate-800 mt-1 break-words"><?= $c[2] ?></h3>
                <p class="text-[11px] text-slate-400 mt-0.5"><?= $c[3] ?></p>
            </div>
        <?php } ?>
    </div>

    <!-- Report -->
    <div class="bg-white rounded-3xl ring-1 ring-slate-200/80 p-4 sm:p-6 space-y-5">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
            <h3 class="font-bold text-slate-800"><i class="fa-solid fa-chart-column text-emerald-600 mr-2"></i>Donation
                Report <small class="font-medium text-slate-400">/ রিপোর্ট</small></h3>
            <div class="flex items-center gap-2 no-print">
                <label class="text-xs font-bold text-slate-500">Year</label>
                <select onchange="location.href=this.value"
                    class="p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm font-semibold">
                    <?php foreach ($years as $y) {
                        echo '<option value="' . h(md_url(['y' => $y, 'mo' => '', 'pg' => 1])) . '"' . ($y == $year ? ' selected' : '') . '>' . $y . '</option>';
                    } ?></select>
                <button type="button" onclick="window.print()"
                    class="px-3 py-2.5 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50"><i
                        class="fa-solid fa-print"></i></button>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
            <div class="lg:col-span-2">
                <p class="text-xs font-bold text-slate-500 mb-2">Month-wise — <?= $year ?></p>
                <div class="h-64"><canvas id="mdChart"></canvas></div>
            </div>
            <div>
                <p class="text-xs font-bold text-slate-500 mb-3">Fund-wise (Paid) — <?= $year ?></p>
                <?php $mx = $fundRows ? max(array_column($fundRows, 'v')) : 1;
                foreach ($fundRows as $f) { ?>
                    <div class="mb-3">
                        <div class="flex justify-between text-xs mb-1"><span
                                class="font-semibold text-slate-700 truncate"><?= h($f['k']) ?></span><b
                                class="text-emerald-700 shrink-0 ml-2"><?= md_tk($f['v']) ?></b></div>
                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-500"
                                style="width:<?= round($f['v'] / $mx * 100) ?>%"></div>
                        </div>
                    </div>
                <?php }
                if (!$fundRows) {
                    echo '<p class="text-xs text-slate-400 py-8 text-center">No paid donation in ' . $year . '.</p>';
                } ?>
            </div>
        </div>

        <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
            <!-- Month-wise table -->
            <div class="overflow-x-auto rounded-xl border border-slate-200">
                <table class="w-full text-left text-xs text-slate-600 min-w-[420px]">
                    <thead class="bg-slate-50 text-slate-700 uppercase font-bold border-b border-slate-200">
                        <tr>
                            <th class="p-3">Month</th>
                            <th class="p-3">Count</th>
                            <th class="p-3">Paid</th>
                            <th class="p-3">Pending</th>
                            <th class="p-3">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php $yt = ['c' => 0, 'paid' => 0, 'pend' => 0, 'tot' => 0];
                        for ($m = 1; $m <= 12; $m++) {
                            $r = $monthRows[$m] ?? null;
                            if ($r) {
                                foreach ($yt as $k => $_) {
                                    $yt[$k] += $r[$k];
                                }
                            } ?>
                            <tr
                                class="<?= $mo == $m ? 'bg-emerald-50' : 'hover:bg-slate-50/80' ?> <?= $r ? '' : 'text-slate-300' ?>">
                                <td class="p-3 font-semibold">
                                    <?= $r ? '<a class="text-slate-800 hover:text-emerald-600 underline decoration-dotted" href="' . h(md_url(['mo' => $mo == $m ? '' : $m, 'pg' => 1])) . '#history">' . $MN[$m] . '</a>' : $MN[$m] ?>
                                </td>
                                <td class="p-3"><?= $r ? (int) $r['c'] : '—' ?></td>
                                <td class="p-3 text-emerald-700 font-semibold"><?= $r ? md_tk($r['paid']) : '—' ?></td>
                                <td class="p-3 text-amber-700"><?= $r ? md_tk($r['pend']) : '—' ?></td>
                                <td class="p-3 font-bold text-slate-800"><?= $r ? md_tk($r['tot']) : '—' ?></td>
                            </tr>
                        <?php } ?>
                    </tbody>
                    <tfoot class="bg-slate-900 text-white font-bold">
                        <tr>
                            <td class="p-3">Total <?= $year ?></td>
                            <td class="p-3"><?= (int) $yt['c'] ?></td>
                            <td class="p-3"><?= md_tk($yt['paid']) ?></td>
                            <td class="p-3"><?= md_tk($yt['pend']) ?></td>
                            <td class="p-3"><?= md_tk($yt['tot']) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <!-- Year-wise table -->
            <div class="overflow-x-auto rounded-xl border border-slate-200 self-start">
                <table class="w-full text-left text-xs text-slate-600 min-w-[420px]">
                    <thead class="bg-slate-50 text-slate-700 uppercase font-bold border-b border-slate-200">
                        <tr>
                            <th class="p-3">Year</th>
                            <th class="p-3">Count</th>
                            <th class="p-3">Paid</th>
                            <th class="p-3">Pending</th>
                            <th class="p-3">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php foreach ($yearRows as $r) { ?>
                            <tr class="<?= $r['y'] == $year ? 'bg-emerald-50' : 'hover:bg-slate-50/80' ?>">
                                <td class="p-3 font-bold"><a class="text-slate-800 hover:text-emerald-600"
                                        href="<?= h(md_url(['y' => $r['y'], 'mo' => '', 'pg' => 1])) ?>"><?= (int) $r['y'] ?></a>
                                </td>
                                <td class="p-3"><?= (int) $r['c'] ?></td>
                                <td class="p-3 text-emerald-700 font-semibold"><?= md_tk($r['paid']) ?></td>
                                <td class="p-3 text-amber-700"><?= md_tk($r['pend']) ?></td>
                                <td class="p-3 font-bold text-slate-800"><?= md_tk($r['tot']) ?></td>
                            </tr>
                        <?php }
                        if (!$yearRows) {
                            echo '<tr><td colspan="5" class="p-6 text-center text-slate-400">No donation yet.</td></tr>';
                        } ?>
                    </tbody>
                    <tfoot class="bg-slate-900 text-white font-bold">
                        <tr>
                            <td class="p-3">All Time</td>
                            <td class="p-3"><?= (int) $all['c'] ?></td>
                            <td class="p-3"><?= md_tk($all['paid']) ?></td>
                            <td class="p-3"><?= md_tk($all['pend']) ?></td>
                            <td class="p-3"><?= md_tk($all['tot']) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>

    <!-- History -->
    <div id="history" class="bg-white rounded-3xl ring-1 ring-slate-200/80 overflow-hidden">
        <div class="p-4 sm:p-5 border-b border-slate-100 space-y-3 no-print">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                <h3 class="font-bold text-slate-800"><i
                        class="fa-solid fa-clock-rotate-left text-sky-600 mr-2"></i>Donation History <small
                        class="font-medium text-slate-400">/ ইতিহাস —
                        <?= $year ?><?= $mo ? ', ' . $MN[$mo] : '' ?></small></h3>
                <span class="text-xs text-slate-500"><?= $total ?> record(s)</span>
            </div>
            <div class="flex flex-wrap gap-1.5">
                <?php foreach (['' => 'All', 'paid' => 'Paid', 'pending' => 'Pending', 'failed' => 'Failed', 'rejected' => 'Rejected'] as $k => $l) { ?>
                    <a href="<?= h(md_url(['st' => $k, 'pg' => 1])) ?>#history"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold <?= $fs === $k ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200' ?>"><?= $l ?></a><?php } ?>
                <?php if ($mo) { ?><a href="<?= h(md_url(['mo' => '', 'pg' => 1])) ?>#history"
                        class="px-3.5 py-2 rounded-xl text-xs font-bold bg-sky-50 text-sky-700"><?= $MN[$mo] ?> <i
                            class="fa-solid fa-xmark ml-1"></i></a><?php } ?>
            </div>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600 min-w-[760px]">
                <thead class="bg-slate-50 text-slate-700 uppercase font-bold border-b border-slate-200">
                    <tr>
                        <th class="p-4">Date</th>
                        <th class="p-4">Fund / Type</th>
                        <th class="p-4">Amount</th>
                        <th class="p-4">Payment</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 text-right no-print">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php foreach ($list as $d) {
                        $s = strtolower($d['payment_status']); ?>
                        <tr class="hover:bg-slate-50/80">
                            <td class="p-4 whitespace-nowrap"><?= h(date('d M Y', strtotime($d['donation_date']))) ?><span
                                    class="block text-[10px] text-slate-400">DN-<?= str_pad($d['id'], 6, '0', STR_PAD_LEFT) ?></span>
                            </td>
                            <td class="p-4 font-semibold text-slate-800"><?= h($d['fund'] ?: 'General') ?><span
                                    class="block text-[10px] font-normal text-slate-400"><?= h($d['donation_type']) ?></span>
                            </td>
                            <td class="p-4 font-bold text-slate-800 whitespace-nowrap"><?= md_tk($d['amount']) ?></td>
                            <td class="p-4"><?= h($d['payment_method']) ?><span
                                    class="block text-[10px] text-slate-400 break-all"><?= h($d['transaction_id']) ?></span>
                            </td>
                            <td class="p-4"><span
                                    class="px-2.5 py-1 rounded-md font-bold text-[10px] <?= $ST[$s] ?? 'bg-slate-100 text-slate-600' ?>"><?= h(ucfirst($d['payment_status'])) ?></span>
                            </td>
                            <td class="p-4 text-right whitespace-nowrap no-print">
                                <button type="button" onclick="mdView(<?= (int) $d['id'] ?>)" title="View"
                                    class="p-2 text-slate-400 hover:text-sky-600"><i class="fa-solid fa-eye"></i></button>
                                <button type="button" onclick="mdDoc(<?= (int) $d['id'] ?>,'invoice')" title="Invoice"
                                    class="p-2 text-slate-400 hover:text-violet-600"><i
                                        class="fa-solid fa-file-invoice-dollar"></i></button>
                                <?php if ($s === 'paid') { ?><button type="button"
                                        onclick="mdDoc(<?= (int) $d['id'] ?>,'payslip')" title="Pay Slip"
                                        class="p-2 text-slate-400 hover:text-amber-600"><i
                                            class="fa-solid fa-file-invoice"></i></button><?php } ?>
                                <?php if ($d['can_edit']) { ?>
                                    <button type="button" onclick="mdEdit(<?= (int) $d['id'] ?>)" title="Edit"
                                        class="p-2 text-slate-400 hover:text-emerald-600"><i
                                            class="fa-solid fa-pen"></i></button>
                                    <form method="POST" action="" class="inline"
                                        onsubmit="return confirm('Delete this donation? / ডোনেশনটি মুছে ফেলবেন?')"><input
                                            type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden"
                                            name="do" value="delete"><input type="hidden" name="id"
                                            value="<?= (int) $d['id'] ?>">
                                        <button title="Delete" class="p-2 text-slate-400 hover:text-rose-600"><i
                                                class="fa-solid fa-trash"></i></button>
                                    </form>
                                <?php } else { ?><span title="Paid donation locked" class="p-2 text-slate-300"><i
                                            class="fa-solid fa-lock"></i></span><?php } ?>
                            </td>
                        </tr>
                    <?php }
                    if (!$list) {
                        echo '<tr><td colspan="6" class="p-10 text-center text-slate-400"><i class="fa-regular fa-folder-open text-3xl mb-2 block"></i>No donation found for this period.</td></tr>';
                    } ?>
                </tbody>
                <?php if ($list) { ?>
                    <tfoot class="bg-emerald-50 font-bold text-emerald-800">
                        <tr>
                            <td colspan="2" class="p-4 text-right uppercase">This page total</td>
                            <td class="p-4"><?= md_tk(array_sum(array_column($list, 'amount'))) ?></td>
                            <td colspan="3"></td>
                        </tr>
                    </tfoot><?php } ?>
            </table>
        </div>
        <?php if ($pages > 1) { ?>
            <div class="p-4 border-t border-slate-100 flex items-center justify-between no-print"><span
                    class="text-xs text-slate-500">Page <?= $pg ?> / <?= $pages ?></span>
                <div class="flex gap-2">
                    <?php if ($pg > 1) { ?><a href="<?= h(md_url(['pg' => $pg - 1])) ?>#history"
                            class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold">‹ Prev</a><?php } ?>
                    <?php if ($pg < $pages) { ?><a href="<?= h(md_url(['pg' => $pg + 1])) ?>#history"
                            class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold">Next ›</a><?php } ?>
                </div>
            </div>
        <?php } ?>
    </div>
    
</section>

<!-- VIEW -->
<div id="mdView"
    class="fixed inset-0 bg-slate-900/60 hidden items-end sm:items-center justify-center sm:p-4 z-50 no-print">
    <div
        class="bg-white w-full sm:max-w-lg max-h-[94vh] overflow-y-auto rounded-t-3xl sm:rounded-2xl shadow-2xl relative">
        <button type="button" onclick="mdModal('mdView', false)"
            class="absolute top-3 right-3 z-10 w-8 h-8 rounded-full bg-slate-900/70 text-white flex items-center justify-center"><i
                class="fa-solid fa-xmark"></i></button>
        <div id="mdViewBody"></div>
    </div>
</div>

<!-- EDIT -->
<div id="mdEdit"
    class="fixed inset-0 bg-slate-900/60 hidden items-end sm:items-center justify-center sm:p-4 z-50 no-print">
    <div
        class="bg-white w-full sm:max-w-2xl max-h-[94vh] flex flex-col rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-4 bg-slate-900 text-white flex justify-between items-center shrink-0">
            <h3 class="font-bold text-sm"><i class="fa-solid fa-pen text-emerald-400 mr-2"></i>Edit Donation <small
                    class="font-normal text-slate-300">— will go back to Pending</small></h3>
            <button type="button" onclick="mdModal('mdEdit', false)" class="text-slate-400 hover:text-white p-1"><i
                    class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data" class="p-4 sm:p-6 space-y-5 overflow-y-auto"
            autocomplete="off">
            <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>"><input type="hidden" name="do"
                value="edit"><input type="hidden" name="id" id="e_id">
            <?= md_form('e_', $funds, $methods, $types, $inp, $lbl) ?>
            <p id="e_rec" class="text-[11px] text-slate-400"></p>
            <div class="flex gap-2"><button type="button" onclick="mdModal('mdEdit', false)"
                    class="flex-1 sm:flex-none px-5 py-3 border border-slate-200 rounded-xl text-sm font-semibold text-slate-600">Cancel</button>
                <button
                    class="flex-1 sm:flex-none sm:ml-auto px-6 py-3 bg-emerald-600 text-white rounded-xl text-sm font-bold hover:bg-emerald-700">Update
                    Donation</button>
            </div>
        </form>
    </div>
</div>

<!-- INVOICE / PAY SLIP -->
<div id="mdDoc"
    class="fixed inset-0 bg-slate-900/60 hidden items-end sm:items-center justify-center sm:p-4 z-50 no-print">
    <div
        class="bg-white w-full sm:max-w-3xl max-h-[94vh] flex flex-col rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-4 bg-slate-900 text-white flex justify-between items-center shrink-0">
            <h3 class="font-bold text-sm" id="mdDocTitle">Document</h3>
            <button type="button" onclick="mdModal('mdDoc', false)" class="text-slate-400 hover:text-white p-1"><i
                    class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <div class="overflow-y-auto bg-slate-100 p-3 sm:p-5">
            <div id="mdDocBox" class="mx-auto"></div>
        </div>
        <div class="shrink-0 p-3 bg-white border-t border-slate-200 grid grid-cols-3 gap-2 sm:flex sm:justify-end">
            <button type="button" onclick="mdDownload('png')"
                class="px-4 py-2.5 rounded-xl bg-emerald-600 text-white text-xs font-semibold"><i
                    class="fa-solid fa-image mr-1"></i>PNG</button>
            <button type="button" onclick="mdDownload('pdf')"
                class="px-4 py-2.5 rounded-xl bg-rose-600 text-white text-xs font-semibold"><i
                    class="fa-solid fa-file-pdf mr-1"></i>PDF</button>
            <button type="button" onclick="mdModal('mdDoc', false)"
                class="px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-semibold text-slate-600">Close</button>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
    (function () {
        var D = <?= json_encode($list, JSON_UNESCAPED_UNICODE) ?>, ORG = <?= json_encode($ORG, JSON_UNESCAPED_UNICODE) ?>, USER = <?= json_encode(['name' => $u['member_name'], 'email' => $u['email'], 'phone' => $u['mobile_no']], JSON_UNESCAPED_UNICODE) ?>;
        var ST = { paid: 'bg-emerald-100 text-emerald-700', pending: 'bg-amber-100 text-amber-700', failed: 'bg-orange-100 text-orange-700', rejected: 'bg-rose-100 text-rose-700' };
        var $ = function (id) { return document.getElementById(id); }, cur = null;
        var MON = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
        function abs(p) { try { return new URL(p, location.href).href; } catch (e) { return p; } }
        function tk(n) { return '৳ ' + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
        function fd(s) { var p = String(s || '').slice(0, 10).split('-'); return p.length === 3 ? p[2] + ' ' + MON[+p[1] - 1] + ' ' + p[0] : '—'; }
        function find(id) { return D.filter(function (x) { return +x.id === +id; })[0]; }
        function no(d) { return 'DN-' + String(d.id).padStart(6, '0'); }
        window.mdModal = function (id, on) { var m = $(id); m.classList.toggle('hidden', !on); m.classList.toggle('flex', on); document.body.style.overflow = on ? 'hidden' : ''; };
        ['mdView', 'mdEdit', 'mdDoc'].forEach(function (id) { $(id).addEventListener('mousedown', function (e) { if (e.target === this) { mdModal(id, false); } }); });

        /* amount chips */
        document.querySelectorAll('[data-chip]').forEach(function (b) {
            b.addEventListener('click', function () {
                var t = $(this.dataset.target); t.value = this.dataset.chip; t.focus();
                this.parentNode.querySelectorAll('[data-chip]').forEach(function (x) { x.classList.remove('border-emerald-500', 'bg-emerald-50', 'text-emerald-700'); });
                this.classList.add('border-emerald-500', 'bg-emerald-50', 'text-emerald-700');
            });
        });

        /* chart */
        var M = <?= json_encode(array_map(function ($m) use ($monthRows) {
            return ['paid' => (float) ($monthRows[$m]['paid'] ?? 0), 'pend' => (float) ($monthRows[$m]['pend'] ?? 0)]; }, range(1, 12))) ?>;
        if (window.Chart) {
            Chart.defaults.font.family = "Inter, 'Hind Siliguri', sans-serif"; Chart.defaults.font.size = 11;
            new Chart($('mdChart'), {
                type: 'bar', data: {
                    labels: MON, datasets: [
                        { label: 'Paid', data: M.map(function (x) { return x.paid; }), backgroundColor: '#10b981', borderRadius: 5 },
                        { label: 'Pending', data: M.map(function (x) { return x.pend; }), backgroundColor: '#f59e0b', borderRadius: 5 }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } }, tooltip: { callbacks: { label: function (c) { return c.dataset.label + ': ' + tk(c.parsed.y); } } } },
                    scales: { x: { stacked: true, grid: { display: false } }, y: { stacked: true, beginAtZero: true, grid: { color: '#f1f5f9' } } }
                }
            });
        }

        /* view */
        window.mdView = function (id) {
            var d = find(id), s = String(d.payment_status).toLowerCase();
            var cell = function (l, v) { return '<div class="p-3 rounded-xl bg-slate-50 border border-slate-100"><p class="text-[10px] font-bold uppercase text-slate-400">' + l + '</p><p class="text-xs font-semibold text-slate-800 mt-0.5 break-words">' + (v || '—') + '</p></div>'; };
            $('mdViewBody').innerHTML = '<div class="p-5 pr-14 bg-gradient-to-br from-emerald-700 to-slate-800 text-white"><p class="text-[11px] text-emerald-200">' + no(d) + '</p><h3 class="text-2xl font-extrabold mt-0.5">' + tk(d.amount) + '</h3><span class="inline-block mt-2 px-3 py-1 rounded-full text-[10px] font-bold ' + (ST[s] || '') + '">' + esc(d.payment_status) + '</span></div>' +
                '<div class="p-4 sm:p-5 space-y-3"><div class="grid grid-cols-2 gap-2">' + cell('Date', fd(d.donation_date)) + cell('Type', esc(d.donation_type)) + cell('Fund', esc(d.fund || 'General')) + cell('Method', esc(d.payment_method)) + cell('Transaction ID', esc(d.transaction_id)) + cell('Submitted', fd(d.created_at)) + '</div>' +
                (d.admin_note ? '<div class="p-3 rounded-xl bg-sky-50 border border-sky-100 text-xs text-slate-700"><b class="text-sky-700">Admin note:</b> ' + esc(d.admin_note) + '</div>' : '') +
                (d.receipt_url ? '<a href="' + esc(d.receipt_url) + '" target="_blank"><img src="' + esc(d.receipt_url) + '" class="max-h-64 mx-auto rounded-xl border border-slate-200"></a>' : '<p class="text-[11px] text-slate-400 text-center">No receipt uploaded.</p>') +
                '<div class="grid grid-cols-2 gap-2"><button type="button" onclick="mdModal(\'mdView\',false);mdDoc(' + d.id + ',\'invoice\')" class="py-2.5 rounded-xl bg-violet-50 text-violet-700 text-xs font-bold"><i class="fa-solid fa-file-invoice-dollar mr-1"></i>Invoice</button>' +
                (s === 'paid' ? '<button type="button" onclick="mdModal(\'mdView\',false);mdDoc(' + d.id + ',\'payslip\')" class="py-2.5 rounded-xl bg-amber-50 text-amber-700 text-xs font-bold"><i class="fa-solid fa-file-invoice mr-1"></i>Pay Slip</button>' : '<span class="py-2.5 rounded-xl bg-slate-50 text-slate-400 text-xs font-bold text-center">Pay Slip after Paid</span>') + '</div></div>';
            mdModal('mdView', true);
        };

        /* edit */
        window.mdEdit = function (id) {
            var d = find(id); if (!d || !d.can_edit) { return; }
            $('e_id').value = d.id; $('e_amount').value = d.amount; $('e_txn').value = d.transaction_id || ''; $('e_date').value = String(d.donation_date).slice(0, 10);
            var fs = $('e_fund'); if (d.fund && !Array.prototype.some.call(fs.options, function (o) { return o.value === d.fund; })) { fs.add(new Option(d.fund, d.fund)); } fs.value = d.fund || '';
            document.querySelectorAll('#mdEdit [name=donation_type]').forEach(function (r) { r.checked = r.value === d.donation_type; });
            document.querySelectorAll('#mdEdit [name=payment_method]').forEach(function (r) { r.checked = r.value === d.payment_method; });
            $('e_rec').textContent = d.receipt_url ? 'Current receipt: ' + d.receipt + ' (নতুন ছবি দিলে বদলে যাবে)' : 'No receipt uploaded yet.';
            mdModal('mdEdit', true);
        };

        /* invoice / pay slip */
        function docHTML(d, kind) {
            var pay = kind === 'payslip', s = String(d.payment_status).toLowerCase(), logo = esc(abs(ORG.logo));
            var badgeC = { paid: ['#d1fae5', '#065f46', '#6ee7b7'], pending: ['#fef3c7', '#92400e', '#fcd34d'], failed: ['#ffedd5', '#9a3412', '#fdba74'], rejected: ['#ffe4e6', '#9f1239', '#fda4af'] }[s] || ['#f1f5f9', '#334155', '#cbd5e1'];
            var row = function (l, v) { return '<div style="display:flex;justify-content:space-between;gap:12px;padding:6px 0;font-size:13px"><span style="color:#64748b">' + l + '</span><b style="text-align:right;word-break:break-word">' + (v || '—') + '</b></div>'; };
            return '<div style="width:794px;min-height:700px;box-sizing:border-box;background:#fff;border:1px solid #e2e8f0;position:relative;overflow:hidden;font-family:\'Inter\',\'Hind Siliguri\',Arial,sans-serif;color:#1e293b">' +
                '<img src="' + logo + '" style="position:absolute;top:200px;left:222px;width:350px;opacity:.06" onerror="this.style.display=\'none\'">' +
                (pay ? '<div style="position:absolute;top:290px;left:150px;font:900 150px Arial;color:#10b981;opacity:.07;transform:rotate(-25deg);letter-spacing:10px">PAID</div>' : '') +
                '<div style="height:10px;background:linear-gradient(90deg,#065f46,#10b981,#6ee7b7)"></div>' +
                '<div style="position:relative;padding:30px 44px 36px">' +
                '<div style="display:flex;justify-content:space-between;align-items:flex-start;gap:20px;padding-bottom:16px;border-bottom:3px solid #047857"><div style="display:flex;gap:14px;align-items:center"><img src="' + logo + '" style="width:76px;height:76px;object-fit:contain" onerror="this.style.display=\'none\'">' +
                '<div><div style="font:bold 22px Georgia,serif;color:#065f46;line-height:1.2">' + esc(ORG.name) + '</div><div style="font-size:11px;color:#64748b;margin-top:3px">' + esc(ORG.address) + '</div><div style="font-size:11px;color:#64748b">' + esc(ORG.phone) + ' | ' + esc(ORG.email) + '</div></div></div>' +
                '<div style="text-align:right"><div style="font-size:10px;letter-spacing:2px;color:#94a3b8">' + (pay ? 'SLIP NO' : 'INVOICE NO') + '</div><div style="font:bold 20px Arial;color:#047857">' + (pay ? no(d) : 'INV-' + String(d.id).padStart(6, '0')) + '</div><div style="font-size:10px;letter-spacing:2px;color:#94a3b8;margin-top:6px">DATE</div><div style="font:bold 14px Arial">' + esc(fd(d.donation_date)) + '</div></div></div>' +
                '<div style="text-align:center;margin:20px 0 16px;font:bold 17px Arial;letter-spacing:5px;color:#334155">' + (pay ? 'DONATION PAY SLIP' : 'DONATION INVOICE') + '</div>' +
                '<div style="display:flex;justify-content:space-between;align-items:center;gap:12px;background:#ecfdf5;border:1px solid #a7f3d0;border-radius:12px;padding:16px 20px;margin-bottom:18px"><div><div style="font-size:10px;letter-spacing:2px;color:#047857">' + (pay ? 'AMOUNT RECEIVED' : 'AMOUNT') + '</div><div style="font:bold 32px Arial;color:#065f46">' + tk(d.amount) + '</div></div>' +
                '<div style="padding:7px 18px;border-radius:99px;font:bold 12px Arial;letter-spacing:2px;text-transform:uppercase;background:' + badgeC[0] + ';color:' + badgeC[1] + ';border:1px solid ' + badgeC[2] + '">' + esc(d.payment_status) + '</div></div>' +
                '<div style="display:flex;gap:16px"><div style="flex:1;border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px"><div style="font:bold 11px Arial;letter-spacing:2px;color:#047857;border-bottom:1px solid #e2e8f0;padding-bottom:6px;margin-bottom:6px">DONOR INFORMATION</div>' + row('Name', esc(USER.name)) + row('Email', esc(USER.email)) + row('Phone', '+88' + esc(USER.phone)) + '</div>' +
                '<div style="flex:1;border:1px solid #e2e8f0;border-radius:12px;padding:14px 16px"><div style="font:bold 11px Arial;letter-spacing:2px;color:#047857;border-bottom:1px solid #e2e8f0;padding-bottom:6px;margin-bottom:6px">PAYMENT DETAILS</div>' + row('Fund', esc(d.fund || 'General')) + row('Type', esc(d.donation_type)) + row('Method', esc(d.payment_method)) + row('Transaction ID', esc(d.transaction_id)) + '</div></div>' +
                (d.admin_note ? '<div style="margin-top:14px;font-size:12px;background:#f8fafc;border-left:3px solid #047857;padding:8px 12px;border-radius:4px"><b style="color:#047857">Note:</b> ' + esc(d.admin_note) + '</div>' : '') +
                (!pay && s !== 'paid' ? '<div style="margin-top:14px;font-size:12px;color:#92400e;background:#fffbeb;border:1px solid #fde68a;padding:9px 12px;border-radius:8px">This invoice is awaiting verification. A Pay Slip will be available once the payment is marked Paid.</div>' : '') +
                '<div style="display:flex;justify-content:space-between;align-items:flex-end;margin-top:46px"><div style="font:bold 13px Arial;color:#065f46">' + (pay ? 'জাযাকাল্লাহু খাইরান।<br>' : '') + '<span style="font-weight:normal;font-size:11px;color:#64748b">' + (pay ? 'Thank you for your generous donation.' : 'Thank you for your support.') + '</span></div>' +
                '<div style="width:170px;text-align:center"><div style="border-top:1px solid #334155;margin-bottom:4px"></div><div style="font-size:10px;letter-spacing:2px;color:#64748b">AUTHORIZED SIGNATURE</div></div></div>' +
                '<div style="text-align:center;margin-top:20px;font-size:10px;color:#94a3b8">This is a computer generated ' + (pay ? 'pay slip' : 'invoice') + '.</div></div>' +
                '<div style="height:10px;background:linear-gradient(90deg,#065f46,#10b981,#6ee7b7)"></div></div>';
        }
        window.mdDoc = function (id, kind) {
            var d = find(id); if (!d) { return; }
            cur = { html: docHTML(d, kind), name: (kind === 'payslip' ? 'PaySlip_' : 'Invoice_') + no(d) };
            $('mdDocTitle').textContent = (kind === 'payslip' ? 'Pay Slip • ' : 'Invoice • ') + no(d);
            mdModal('mdDoc', true);
            var box = $('mdDocBox'), sc = Math.min(1, (box.clientWidth - 2) / 794);
            box.innerHTML = '<div id="mdScaler" style="width:' + 794 * sc + 'px;margin:0 auto;overflow:hidden"><div style="transform:scale(' + sc + ');transform-origin:top left;width:794px">' + cur.html + '</div></div>';
            $('mdScaler').style.height = $('mdScaler').firstChild.offsetHeight * sc + 'px';
        };
        window.mdDownload = function (fmt) {
            if (!cur || !window.html2canvas) { alert('Library load hoyni. Internet check korun.'); return; }
            var el = document.createElement('div'); el.style.cssText = 'position:fixed;left:-99999px;top:0;width:794px'; el.innerHTML = cur.html; document.body.appendChild(el);
            var h = el.offsetHeight;
            html2canvas(el, { scale: 2, useCORS: true, backgroundColor: '#ffffff' }).then(function (cv) {
                document.body.removeChild(el);
                if (fmt === 'png') { var a = document.createElement('a'); a.href = cv.toDataURL('image/png'); a.download = cur.name + '.png'; a.click(); }
                else { var pdf = new window.jspdf.jsPDF({ orientation: 'p', unit: 'px', format: [794, h] }); pdf.addImage(cv.toDataURL('image/jpeg', .95), 'JPEG', 0, 0, 794, h); pdf.save(cur.name + '.pdf'); }
            });
        };
    })();
</script>