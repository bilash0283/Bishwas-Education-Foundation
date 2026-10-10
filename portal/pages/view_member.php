<?php
/*
 * PROFILE PAGE - PHP 7.2 compatible, beginner friendly version
 *
 * Live server-e ja problem hochhilo (ebong fix):
 * 1. mysqli_stmt_get_result()  -> mysqlnd driver na thakle kaj kore na.
 *                                 Tai nijer helper function (db_select) banano hoyeche.
 * 2. header('Location: ...')   -> age kono output hoye gele "headers already sent" error.
 *                                 Tai ob_start() + safe redirect function (go_to) deya hoyeche.
 * 3. session_start()           -> $_SESSION check korar AGE session start korte hobe.
 *                                 Ekhon sobar upore kora hoyeche.
 * 4. function duplicate        -> file duibar include hole fatal error hoto.
 *                                 Ekhon function_exists() diye bachano hoyeche.
 * 5. $db / $conn               -> include kora file-er bhitor theke connection na pele
 *                                 'global' diye ante hoyeche.
 *
 * IMPORTANT: File ta "UTF-8 (without BOM)" encoding-e save korben, noile Bangla
 * lekha bhenge jabe ebong BOM-er karone header error hobe.
 */

// ---------- 0. Output buffer & Session (sobar upore) ----------
if (!headers_sent()) {
    ob_start();
}
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// ---------- 1. Helper functions ----------
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
            echo '<noscript><meta http-equiv="refresh" content="0;url=' . h($url) . '"></noscript>';
        }
        exit;
    }
}

if (!function_exists('db_select')) {
    /*
     * SELECT query chalay ebong sob row array hishebe ferot dey.
     * mysqlnd (get_result) lage na, tai shob hosting-e kaj korbe.
     *
     * $types  = 'i' (integer), 's' (string)   jemon: 'iss'
     * $params = value-gulo array-te            jemon: array(5, 'a', 'b')
     */
    function db_select($conn, $sql, $types = '', $params = array())
    {
        $rows = array();
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return $rows;
        }

        if ($types !== '' && count($params) > 0) {
            $args = array($stmt, $types);
            foreach ($params as $k => $v) {
                $args[] = &$params[$k];   // bind_param-e reference lage
            }
            call_user_func_array('mysqli_stmt_bind_param', $args);
        }

        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);

        $meta = mysqli_stmt_result_metadata($stmt);
        if ($meta) {
            $row  = array();
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
                $rows[] = $copy;
            }
            mysqli_free_result($meta);
        }

        mysqli_stmt_close($stmt);
        return $rows;
    }
}

if (!function_exists('db_select_one')) {
    // Shudhu prothom row ferot dey (na thakle null)
    function db_select_one($conn, $sql, $types = '', $params = array())
    {
        $rows = db_select($conn, $sql, $types, $params);
        return count($rows) > 0 ? $rows[0] : null;
    }
}

if (!function_exists('db_execute')) {
    // INSERT / UPDATE / DELETE chalay. Success hole true.
    function db_execute($conn, $sql, $types = '', $params = array())
    {
        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            return false;
        }
        if ($types !== '' && count($params) > 0) {
            $args = array($stmt, $types);
            foreach ($params as $k => $v) {
                $args[] = &$params[$k];
            }
            call_user_func_array('mysqli_stmt_bind_param', $args);
        }
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        return $ok;
    }
}

if (!function_exists('info_row')) {
    function info_row($label, $value, $icon)
    {
        $v = trim((string) $value) === ''
            ? '<span class="text-slate-300">—</span>'
            : nl2br(h($value));
        return '<div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
            <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
                <i class="fa-solid ' . $icon . ' text-emerald-600"></i> ' . h($label) . '
            </p>
            <p class="text-sm font-semibold text-slate-800 mt-1 break-words">' . $v . '</p>
        </div>';
    }
}

if (!function_exists('status_badge_class')) {
    function status_badge_class($status)
    {
        $map = array(
            'paid'     => 'bg-emerald-50 text-emerald-700 border-emerald-200',
            'pending'  => 'bg-amber-50 text-amber-700 border-amber-200',
            'failed'   => 'bg-sky-50 text-sky-700 border-sky-200',
            'rejected' => 'bg-red-50 text-red-700 border-red-200',
        );
        $key = strtolower((string) $status);
        return isset($map[$key]) ? $map[$key] : 'bg-slate-100 text-slate-600 border-slate-200';
    }
}

// ---------- 2. Login check (all type members can visit) ----------
if (!isset($_SESSION['user_id'])) {
    go_to('index.php?page=dashboard');
}

// ---------- 3. Database connection ----------
// Include kora file-er bhitor theke $db / $conn pete 'global' lage
global $db, $conn;
$connection = null;
if (isset($db) && $db) {
    $connection = $db;
} elseif (isset($conn) && $conn) {
    $connection = $conn;
}

if (!$connection) {
    die('Database connection not found.');
}
mysqli_set_charset($connection, 'utf8mb4');

// ---------- 4. CSRF token ----------
if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = bin2hex(random_bytes(16));
}

// ---------- 5. Basic settings ----------
$upload_dir = 'public/uploads/members/';
$back_page  = 'index.php?page=volunteers';

// ---------- 6. User-er tothyo ----------
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$u  = db_select_one($connection, "SELECT * FROM users WHERE id = ? LIMIT 1", 'i', array($id));

// ---------- 7. Ke edit korte parbe? ----------
// Admin = je kono profile, baki = shudhu nijer profile
$me = (int) $_SESSION['user_id'];
$utype = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '';

if ($me > 0) {
    $meRow = db_select_one($connection, "SELECT user_type FROM users WHERE id = ? LIMIT 1", 'i', array($me));
    if ($meRow) {
        $utype = $meRow['user_type'];
    }
}

$isAdmin    = ($utype === 'Admin');
$canEdit    = ($u && ($isAdmin || ($me > 0 && $me === (int) $u['id'])));
$user_types = array('Admin', 'General Member', 'Associate Member', 'Life Member', 'Volunteer Member');

// ---------- 8. PROFILE UPDATE (POST) ----------
if ($u && $_SERVER['REQUEST_METHOD'] === 'POST'
    && (isset($_POST['do']) ? $_POST['do'] : '') === 'update_profile') {

    $ok  = false;
    $msg = '';

    $postedCsrf = isset($_POST['csrf']) ? $_POST['csrf'] : '';

    if (!hash_equals($_SESSION['csrf'], $postedCsrf)) {
        $msg = 'Session expired. Please reload the page.';
    } elseif (!$canEdit) {
        $msg = 'You can only update your own profile.';
    } else {
        $name   = trim(isset($_POST['member_name']) ? $_POST['member_name'] : '');
        $mobile = preg_replace('/\D/', '', isset($_POST['mobile_no']) ? $_POST['mobile_no'] : '');
        $email  = trim(isset($_POST['email']) ? $_POST['email'] : '');

        if ($name === '') {
            $msg = 'নাম দেওয়া আবশ্যক।';
        } elseif (!preg_match('/^[0-9]{11}$/', $mobile)) {
            $msg = 'মোবাইল নম্বর ১১ ডিজিটের হতে হবে।';
        } elseif ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $msg = 'সঠিক ইমেইল দিন।';
        } else {
            // অন্য কারো সাথে মোবাইল/ইমেইল মিলে গেলে আটকানো
            $dr = db_select_one(
                $connection,
                "SELECT mobile_no, email FROM users
                 WHERE id <> ? AND (mobile_no = ? OR (? <> '' AND email = ?)) LIMIT 1",
                'isss',
                array($id, $mobile, $email, $email)
            );

            if ($dr) {
                $msg = ($dr['mobile_no'] === $mobile)
                    ? 'এই মোবাইল নম্বর অন্য একজন ব্যবহার করছেন।'
                    : 'এই ইমেইল অন্য একজন ব্যবহার করছেন।';
            } else {
                // ---- নতুন ছবি (দিলে) ----
                $new_photo = '';
                $up_ok     = true;

                if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
                    $f   = $_FILES['photo'];
                    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));

                    if ($f['error'] !== UPLOAD_ERR_OK) {
                        $msg   = 'ছবি আপলোড করা যায়নি।';
                        $up_ok = false;
                    } elseif (!in_array($ext, array('jpg', 'jpeg', 'png', 'webp')) || !@getimagesize($f['tmp_name'])) {
                        $msg   = 'ছবি শুধু JPG, PNG বা WEBP হতে হবে।';
                        $up_ok = false;
                    } elseif ($f['size'] > 2 * 1024 * 1024) {
                        $msg   = 'ছবির সাইজ সর্বোচ্চ 2MB।';
                        $up_ok = false;
                    } else {
                        if (!is_dir($upload_dir)) {
                            @mkdir($upload_dir, 0755, true);
                        }
                        $new_photo = 'mem_' . time() . '_' . random_int(1000, 9999) . '.' . $ext;
                        if (!move_uploaded_file($f['tmp_name'], $upload_dir . $new_photo)) {
                            $msg       = 'ছবি সেভ করা যায়নি (ফোল্ডার পারমিশন চেক করুন)।';
                            $up_ok     = false;
                            $new_photo = '';
                        }
                    }
                }

                if ($up_ok) {
                    // Je column-gulo update hobe tar list
                    $set = array(
                        'member_name = ?', 'mother_name = ?', 'father_husband_name = ?',
                        'id_number = ?', 'qualification = ?', 'mobile_no = ?', 'email = ?',
                        'present_address = ?', 'permanent_address = ?', 'other_info = ?'
                    );
                    // Tader value (ager list-er sathe same order)
                    $vals = array(
                        $name,
                        trim(isset($_POST['mother_name']) ? $_POST['mother_name'] : ''),
                        trim(isset($_POST['father_husband_name']) ? $_POST['father_husband_name'] : ''),
                        trim(isset($_POST['id_number']) ? $_POST['id_number'] : ''),
                        trim(isset($_POST['qualification']) ? $_POST['qualification'] : ''),
                        $mobile,
                        $email,
                        trim(isset($_POST['present_address']) ? $_POST['present_address'] : ''),
                        trim(isset($_POST['permanent_address']) ? $_POST['permanent_address'] : ''),
                        trim(isset($_POST['other_info']) ? $_POST['other_info'] : '')
                    );

                    $dob = isset($_POST['dob']) ? $_POST['dob'] : '';
                    if ($dob !== '' && strtotime($dob)) {
                        $set[]  = 'dob = ?';
                        $vals[] = date('Y-m-d', strtotime($dob));
                    }

                    $gender = isset($_POST['gender']) ? $_POST['gender'] : '';
                    if (in_array($gender, array('Male', 'Female', 'Other'))) {
                        $set[]  = 'gender = ?';
                        $vals[] = $gender;
                    }

                    $id_type = isset($_POST['id_type']) ? $_POST['id_type'] : '';
                    if (in_array($id_type, array('NID', 'Passport', 'Birth Certificate'))) {
                        $set[]  = 'id_type = ?';
                        $vals[] = $id_type;
                    }

                    if ($new_photo !== '') {
                        $set[]  = 'photo = ?';
                        $vals[] = $new_photo;
                    }

                    $new_type = isset($_POST['user_type']) ? $_POST['user_type'] : '';
                    if ($isAdmin && in_array($new_type, $user_types)) {
                        $set[]  = 'user_type = ?';
                        $vals[] = $new_type;
                    }

                    // Shesh-e WHERE id = ? er jonno id
                    $types  = str_repeat('s', count($vals)) . 'i';
                    $vals[] = $id;

                    $ok = db_execute(
                        $connection,
                        "UPDATE users SET " . implode(', ', $set) . " WHERE id = ?",
                        $types,
                        $vals
                    );

                    if ($ok) {
                        $msg = 'Profile updated successfully! / প্রোফাইল সফলভাবে আপডেট হয়েছে।';
                        // Notun chhobi dile purono chhobi muche fela
                        if ($new_photo !== '' && !empty($u['photo']) && is_file($upload_dir . basename($u['photo']))) {
                            @unlink($upload_dir . basename($u['photo']));
                        }
                    } else {
                        $msg = 'আপডেট করা যায়নি। আবার চেষ্টা করুন।';
                        if ($new_photo !== '') {
                            @unlink($upload_dir . $new_photo);
                        }
                    }
                }
            }
        }
    }

    $_SESSION['pf_flash'] = array($ok, $msg);
    go_to($_SERVER['REQUEST_URI']);
}

$flash = isset($_SESSION['pf_flash']) ? $_SESSION['pf_flash'] : null;
unset($_SESSION['pf_flash']);

// ---------- 9. Profile photo ----------
$photo = '';
if ($u && !empty($u['photo']) && is_file($upload_dir . basename($u['photo']))) {
    $photo = $upload_dir . basename($u['photo']);
}

// ---------- 10. Donation History ----------
// ID অথবা ইমেইল অথবা ফোনের শেষ ১০ সংখ্যা, যেকোনো একটা মিললেই আসবে
$donations     = array();
$total_paid    = 0;
$total_pending = 0;
$paid_count    = 0;

if ($u) {
    $d_email  = trim((string) $u['email']);
    $d_digits = preg_replace('/\D/', '', (string) $u['mobile_no']);
    $d_last10 = strlen($d_digits) >= 10 ? substr($d_digits, -10) : '';

    $sql = "SELECT id, amount, donation_type, fund, payment_method, transaction_id,
                   payment_status, donation_date
            FROM donations
            WHERE donor_id = ?
               OR (? <> '' AND email = ?)
               OR (? <> '' AND RIGHT(phone, 10) = ?)
            ORDER BY donation_date DESC, id DESC";

    $donations = db_select(
        $connection,
        $sql,
        'issss',
        array($id, $d_email, $d_email, $d_last10, $d_last10)
    );

    foreach ($donations as $r) {
        $s = strtolower((string) $r['payment_status']);
        if ($s === 'paid') {
            $total_paid += (float) $r['amount'];
            $paid_count++;
        }
        if ($s === 'pending') {
            $total_pending += (float) $r['amount'];
        }
    }
}

// ---------- 11. Date & CSS class helpers ----------
$dob_text = '';
$dob_val  = '';
if ($u && !empty($u['dob']) && $u['dob'] !== '0000-00-00') {
    $dob_text = date('d M Y', strtotime($u['dob']));
    $dob_val  = date('Y-m-d', strtotime($u['dob']));
}

$inp = 'w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10';
$lbl = 'block text-[11px] font-bold uppercase text-slate-500 mb-1';
?>

<style>
    @keyframes pfIn  { from { opacity: 0; transform: translateX(40px); } to { opacity: 1; transform: translateX(0); } }
    @keyframes pfOut { from { opacity: 1; transform: translateX(0); } to { opacity: 0; transform: translateX(40px); } }
    @keyframes pfBar { from { width: 100%; } to { width: 0%; } }
    .pf-toast-in  { animation: pfIn .35s ease-out forwards; }
    .pf-toast-out { animation: pfOut .35s ease-in forwards; }
</style>

<?php if ($flash) { ?>
<!-- ===================== SUCCESS / ERROR POPUP (side e ashe, nijei hide hoy) ===================== -->
<div id="pfToast" class="pf-toast-in fixed top-5 right-5 z-[80] w-80 max-w-[calc(100vw-2.5rem)] bg-white rounded-2xl shadow-2xl border <?= $flash[0] ? 'border-emerald-200' : 'border-rose-200' ?> overflow-hidden print:hidden">
    <div class="p-4 flex items-start gap-3">
        <span class="w-9 h-9 rounded-full flex items-center justify-center shrink-0 text-lg <?= $flash[0] ? 'bg-emerald-100 text-emerald-600' : 'bg-rose-100 text-rose-600' ?>">
            <i class="fa-solid <?= $flash[0] ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
        </span>
        <div class="min-w-0 flex-1">
            <p class="text-sm font-bold <?= $flash[0] ? 'text-emerald-700' : 'text-rose-700' ?>"><?= $flash[0] ? 'Success' : 'Error' ?></p>
            <p class="text-xs text-slate-600 mt-0.5 break-words"><?= h($flash[1]) ?></p>
        </div>
        <button type="button" onclick="pfHideToast()" class="text-slate-300 hover:text-slate-500"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="h-1 <?= $flash[0] ? 'bg-emerald-500' : 'bg-rose-500' ?>" style="animation: pfBar 4s linear forwards"></div>
</div>
<script>
    function pfHideToast() {
        var t = document.getElementById('pfToast');
        if (!t) { return; }
        t.classList.remove('pf-toast-in');
        t.classList.add('pf-toast-out');
        setTimeout(function () { if (t.parentNode) { t.parentNode.removeChild(t); } }, 380);
    }
    setTimeout(pfHideToast, 4000);
</script>
<?php } ?>

<div class="max-w-4xl mx-auto space-y-6">
    <!-- উপরের বাটন -->
    <div class="flex items-center justify-between print:hidden">
        <button onclick="history.back()" 
            class="px-4 py-2.5 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors flex items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i> Back
        </button>
        <?php if ($u) { ?>
            <div class="flex flex-wrap items-center justify-end gap-2">
                <?php if ($canEdit) { ?>
                <button onclick="openModal('memberEditModal')"
                    class="px-4 py-2 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-semibold flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-pen-to-square"></i>
                </button>
                <?php } ?>
                <button onclick="openModal('memberDonationModal')"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-hand-holding-heart"></i>
                    
                    <span class="px-1.5 py-0.5 rounded bg-white/20 text-[10px]"><?= count($donations) ?></span>
                </button>
                <button onclick="window.print()"
                    class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-semibold flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-print"></i> Print
                </button>
            </div>
        <?php } ?>
    </div>

    <?php if (!$u) { ?>

        <div class="p-6 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-sm font-semibold">
            <i class="fa-solid fa-circle-exclamation"></i> User পাওয়া যায়নি।
        </div>

    <?php } else { ?>

    <!-- প্রোফাইল হেডার -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-slate-800 to-emerald-800 text-white shadow-xl [print-color-adjust:exact] [-webkit-print-color-adjust:exact]">
        <div class="absolute -top-16 -right-16 w-56 h-56 rounded-full bg-emerald-400/10"></div>
        <div class="absolute -bottom-20 -left-10 w-48 h-48 rounded-full bg-white/5"></div>

        <div class="relative p-6 sm:p-10 flex flex-col lg:flex-row lg:items-center gap-6">

            <!-- ছবি + তথ্য -->
            <div class="flex flex-col sm:flex-row items-center gap-6 flex-1 min-w-0">
                <?php if ($photo) { ?>
                    <img src="<?= h($photo) ?>" alt="Photo"
                        class="w-28 h-32 rounded-2xl object-cover border-4 border-white/20 shadow-lg shrink-0">
                <?php } else { ?>
                    <div class="w-28 h-32 rounded-2xl bg-white/10 border-4 border-white/20 flex items-center justify-center text-4xl font-bold shrink-0">
                        <?= h(mb_strtoupper(mb_substr($u['member_name'], 0, 1))) ?>
                    </div>
                <?php } ?>

                <div class="text-center sm:text-left space-y-3 min-w-0">
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight break-words"><?= h($u['member_name']) ?></h1>

                    <div class="flex flex-wrap justify-center sm:justify-start gap-2">
                        <span class="px-3 py-1 rounded-full bg-white/15 text-xs font-semibold"><?= h($u['user_type']) ?></span>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $u['status'] == 'Active' ? 'bg-emerald-400/30 text-emerald-100' : 'bg-amber-400/30 text-amber-100' ?>">
                            <?= h($u['status'] ?: 'Pending') ?>
                        </span>
                        <span class="px-3 py-1 rounded-full bg-white/10 text-xs">ID #<?= (int) $u['id'] ?></span>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:flex-wrap gap-x-5 gap-y-1 text-sm text-slate-200">
                        <span><i class="fa-solid fa-phone mr-1.5 text-emerald-300"></i>+88<?= h($u['mobile_no']) ?></span>
                        <?php if ($u['email'] != '') { ?>
                            <span class="break-all"><i class="fa-solid fa-envelope mr-1.5 text-emerald-300"></i><?= h($u['email']) ?></span>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <!-- Total Paid কার্ড (ডান পাশ) -->
            <button type="button" onclick="openModal('memberDonationModal')"
                class="shrink-0 w-full lg:w-64 text-left rounded-2xl bg-white/10 hover:bg-white/15 border border-white/20 backdrop-blur-sm p-5 transition-colors print:hidden">
                <div class="flex items-center gap-2 text-emerald-300 text-[11px] font-bold uppercase tracking-widest">
                    <i class="fa-solid fa-circle-check"></i> Total Paid
                </div>
                <div class="text-3xl font-extrabold mt-2 break-words">
                    ৳ <?= number_format($total_paid, 2) ?>
                </div>
                <div class="text-[11px] text-slate-300 mt-2">
                    <?= (int) $paid_count ?> paid donation(s)
                </div>
                <div class="text-[10px] text-emerald-300 mt-3 flex items-center gap-1.5">
                    <i class="fa-solid fa-clock-rotate-left"></i> View history
                </div>
            </button>
        </div>
    </div>

    <!-- ব্যক্তিগত তথ্য -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200/80 p-6 sm:p-8 space-y-6">

        <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
            <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                <i class="fa-solid fa-user"></i>
            </span>
            <h2 class="text-base font-bold text-slate-900">Personal Details / ব্যক্তিগত তথ্য</h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php
            echo info_row('Full Name', $u['member_name'], 'fa-user');
            echo info_row("Mother's Name", $u['mother_name'], 'fa-person-dress');
            echo info_row('Father / Husband', $u['father_husband_name'], 'fa-person');
            echo info_row('Date of Birth', $dob_text, 'fa-cake-candles');
            echo info_row('Gender', $u['gender'], 'fa-venus-mars');
            echo info_row($u['id_type'] ?: 'ID Type', $u['id_number'], 'fa-id-card');
            echo info_row('Qualification', $u['qualification'], 'fa-graduation-cap');
            echo info_row('Category', $u['user_type'], 'fa-tag');
            ?>
        </div>

        <div class="flex items-center gap-3 border-b border-slate-100 pb-3 pt-2">
            <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                <i class="fa-solid fa-location-dot"></i>
            </span>
            <h2 class="text-base font-bold text-slate-900">Contact & Address / যোগাযোগ ও ঠিকানা</h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php
            echo info_row('Mobile', '+88' . $u['mobile_no'], 'fa-phone');
            echo info_row('Email', $u['email'], 'fa-envelope');
            echo info_row('Present Address', $u['present_address'], 'fa-location-dot');
            echo info_row('Permanent Address', $u['permanent_address'], 'fa-house');
            ?>
        </div>

        <?php echo info_row('Other Information', $u['other_info'], 'fa-circle-info'); ?>

        <?php if (!empty($u['created_at'])) { ?>
            <p class="text-[11px] text-slate-400">
                Registered: <?= h(date('d M Y, h:i A', strtotime($u['created_at']))) ?>
            </p>
        <?php } ?>
    </div>
    <?php } ?>
</div>

<?php if ($u) { ?>
<!-- ===================== DONATION HISTORY MODAL ===================== -->
<div id="memberDonationModal"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center p-4 z-50 print:hidden">
    <div class="bg-white w-full max-w-3xl rounded-2xl shadow-2xl overflow-hidden border border-slate-100 max-h-[92vh] overflow-y-auto">

        <div class="p-5 bg-slate-900 text-white flex justify-between items-start gap-3 sticky top-0 z-10">
            <div class="min-w-0">
                <h3 class="font-bold text-sm flex items-center gap-2">
                    <i class="fa-solid fa-hand-holding-heart text-emerald-400"></i>
                    <span>Donation History</span>
                </h3>
                <p class="text-xs text-slate-300 mt-1 break-words">
                    <?= h($u['member_name']) ?> • ID #<?= (int) $u['id'] ?> • +88<?= h($u['mobile_no']) ?>
                </p>
            </div>
            <button onclick="closeModal('memberDonationModal')" class="text-slate-400 hover:text-white shrink-0">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="p-5 space-y-4">

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4">
                    <p class="text-[10px] font-bold uppercase text-emerald-700">Total Paid</p>
                    <p class="text-lg font-bold text-emerald-800">৳ <?= number_format($total_paid, 2) ?></p>
                </div>
                <div class="rounded-xl bg-amber-50 border border-amber-200 p-4">
                    <p class="text-[10px] font-bold uppercase text-amber-700">Pending</p>
                    <p class="text-lg font-bold text-amber-800">৳ <?= number_format($total_pending, 2) ?></p>
                </div>
                <div class="rounded-xl bg-sky-50 border border-sky-200 p-4">
                    <p class="text-[10px] font-bold uppercase text-sky-700">Total Donations</p>
                    <p class="text-lg font-bold text-sky-800"><?= count($donations) ?></p>
                </div>
            </div>

            <div class="overflow-x-auto border border-slate-200 rounded-xl">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-slate-700 uppercase font-bold border-b border-slate-200">
                        <tr>
                            <th class="p-3">#</th>
                            <th class="p-3">Date</th>
                            <th class="p-3">Amount</th>
                            <th class="p-3">Payment Method</th>
                            <th class="p-3">Transaction ID</th>
                            <th class="p-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (count($donations) === 0) { ?>
                            <tr><td colspan="6" class="p-8 text-center text-slate-400">
                                <i class="fa-regular fa-folder-open text-3xl mb-2 block"></i>
                                এই ইউজারের কোনো donation পাওয়া যায়নি।
                            </td></tr>
                        <?php } ?>

                        <?php foreach ($donations as $i => $d) { ?>
                            <tr class="hover:bg-slate-50/80">
                                <td class="p-3 text-slate-400"><?= $i + 1 ?></td>
                                <td class="p-3 whitespace-nowrap"><?= h(date('d M Y', strtotime($d['donation_date']))) ?></td>
                                <td class="p-3 font-bold text-slate-800 whitespace-nowrap">৳ <?= number_format((float) $d['amount'], 2) ?></td>
                                <td class="p-3"><?= h($d['payment_method']) ?></td>
                                <td class="p-3 break-all"><?= h($d['transaction_id'] ?: '—') ?></td>
                                <td class="p-3">
                                    <span class="inline-block px-2.5 py-1 rounded-md border font-bold text-[10px] <?= status_badge_class($d['payment_status']) ?>">
                                        <?= h(ucfirst($d['payment_status'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>

                    <?php if (count($donations) > 0) { ?>
                        <tfoot class="bg-emerald-50 border-t-2 border-emerald-200">
                            <tr>
                                <td colspan="2" class="p-3 text-right font-bold uppercase text-emerald-800">Total Paid</td>
                                <td colspan="4" class="p-3 font-extrabold text-base text-emerald-800">৳ <?= number_format($total_paid, 2) ?></td>
                            </tr>
                        </tfoot>
                    <?php } ?>
                </table>
            </div>

            <div class="flex justify-end">
                <button type="button" onclick="closeModal('memberDonationModal')"
                    class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Close</button>
            </div>
        </div>
    </div>
</div>

<?php if ($canEdit) { ?>
<!-- ===================== EDIT PROFILE MODAL ===================== -->
<div id="memberEditModal"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs hidden items-end sm:items-center justify-center sm:p-4 z-50 print:hidden">
    <div class="bg-white w-full sm:max-w-3xl max-h-[94vh] flex flex-col rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden border border-slate-100">

        <div class="p-4 sm:p-5 bg-slate-900 text-white flex justify-between items-center shrink-0">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-sky-400"></i>
                <span>Edit Profile <small class="font-normal text-slate-300">/ প্রোফাইল আপডেট</small></span>
            </h3>
            <button type="button" onclick="closeModal('memberEditModal')" class="text-slate-400 hover:text-white p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form id="profileEditForm" method="POST" action="" enctype="multipart/form-data" class="p-4 sm:p-6 space-y-5 overflow-y-auto" novalidate>
            <input type="hidden" name="do" value="update_profile">
            <input type="hidden" name="csrf" value="<?= h($_SESSION['csrf']) ?>">

            <!-- ছবি -->
            <div class="flex items-center gap-4">
                <div class="w-20 h-24 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 overflow-hidden shrink-0 flex items-center justify-center">
                    <img id="editPhotoPreview" src="<?= h($photo) ?>" class="<?= $photo ? '' : 'hidden' ?> w-full h-full object-cover" alt="">
                    <i id="editPhotoIcon" class="fa-solid fa-camera text-slate-300 text-xl <?= $photo ? 'hidden' : '' ?>"></i>
                </div>
                <div class="min-w-0">
                    <label class="<?= $lbl ?>">Photo / ছবি <small class="normal-case">(JPG, PNG, WEBP - max 2MB)</small></label>
                    <input type="file" name="photo" id="editPhotoInput" accept="image/png,image/jpeg,image/webp" class="text-xs w-full">
                    <p class="text-[10px] text-slate-400 mt-1">নতুন ছবি দিলে পুরনো ছবি বদলে যাবে।</p>
                </div>
            </div>

            <div class="space-y-3">
                <h4 class="text-xs font-bold text-slate-800 border-b border-slate-100 pb-2"><i class="fa-solid fa-user text-emerald-600 mr-1.5"></i>Personal Details / ব্যক্তিগত তথ্য</h4>
                <div>
                    <label class="<?= $lbl ?>">Full Name / পূর্ণ নাম <span class="text-rose-500">*</span></label>
                    <input type="text" name="member_name" value="<?= h($u['member_name']) ?>" required class="<?= $inp ?>">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div><label class="<?= $lbl ?>">Mother's Name / মাতার নাম</label><input type="text" name="mother_name" value="<?= h($u['mother_name']) ?>" class="<?= $inp ?>"></div>
                    <div><label class="<?= $lbl ?>">Father / Husband / পিতা-স্বামী</label><input type="text" name="father_husband_name" value="<?= h($u['father_husband_name']) ?>" class="<?= $inp ?>"></div>
                    <div><label class="<?= $lbl ?>">Date of Birth / জন্ম তারিখ</label><input type="date" name="dob" value="<?= h($dob_val) ?>" class="<?= $inp ?>"></div>
                    <div><label class="<?= $lbl ?>">Gender / লিঙ্গ</label>
                        <select name="gender" class="<?= $inp ?>">
                            <?php foreach (['Male' => 'Male / পুরুষ', 'Female' => 'Female / মহিলা', 'Other' => 'Other / অন্যান্য'] as $gv => $gl) { echo '<option value="' . $gv . '"' . ($u['gender'] === $gv ? ' selected' : '') . '>' . $gl . '</option>'; } ?>
                        </select></div>
                    <div><label class="<?= $lbl ?>">ID Type / পরিচয়পত্রের ধরন</label>
                        <select name="id_type" class="<?= $inp ?>">
                            <?php foreach (['NID' => 'NID Card / জাতীয় পরিচয়পত্র', 'Passport' => 'Passport / পাসপোর্ট', 'Birth Certificate' => 'Birth Certificate / জন্ম নিবন্ধন'] as $tv => $tl) { echo '<option value="' . $tv . '"' . ($u['id_type'] === $tv ? ' selected' : '') . '>' . $tl . '</option>'; } ?>
                        </select></div>
                    <div><label class="<?= $lbl ?>">ID Number / পরিচয়পত্র নম্বর</label><input type="text" name="id_number" value="<?= h($u['id_number']) ?>" class="<?= $inp ?>"></div>
                    <div class="sm:col-span-2"><label class="<?= $lbl ?>">Qualification / শিক্ষাগত যোগ্যতা</label><input type="text" name="qualification" value="<?= h($u['qualification']) ?>" class="<?= $inp ?>"></div>
                    <?php if ($isAdmin) { ?>
                    <div class="sm:col-span-2"><label class="<?= $lbl ?>">Member Category / সদস্যের ধরন <small class="normal-case text-sky-600">(Admin only)</small></label>
                        <select name="user_type" class="<?= $inp ?>">
                            <?php foreach ($user_types as $ut) { echo '<option' . ($u['user_type'] === $ut ? ' selected' : '') . '>' . h($ut) . '</option>'; } ?>
                        </select></div>
                    <?php } ?>
                </div>
            </div>

            <div class="space-y-3">
                <h4 class="text-xs font-bold text-slate-800 border-b border-slate-100 pb-2"><i class="fa-solid fa-location-dot text-rose-500 mr-1.5"></i>Contact & Address / যোগাযোগ ও ঠিকানা</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div><label class="<?= $lbl ?>">Mobile / মোবাইল <span class="text-rose-500">*</span></label>
                        <div class="relative"><span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 text-xs font-bold pointer-events-none">+88</span>
                            <input type="tel" name="mobile_no" value="<?= h($u['mobile_no']) ?>" required maxlength="11" pattern="[0-9]{11}" class="<?= $inp ?> pl-11"></div></div>
                    <div>
                        <label class="<?= $lbl ?>">Email / ই-মেইল</label>
                        <input type="email" name="email" disabled value="<?= h($u['email']) ?>" class="<?= $inp ?>">
                        <input type="email" hidden name="email" value="<?= h($u['email']) ?>">
                    </div>
                </div>
                <div><label class="<?= $lbl ?>">Present Address / বর্তমান ঠিকানা</label><textarea name="present_address" id="editPresent" rows="2" class="<?= $inp ?>"><?= h($u['present_address']) ?></textarea></div>
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="<?= $lbl ?> mb-0">Permanent Address / স্থায়ী ঠিকানা</label>
                        <label class="flex items-center gap-1.5 cursor-pointer text-[11px] text-emerald-700 font-semibold select-none"><input type="checkbox" id="editSame" class="rounded border-slate-300"> Same as Present</label>
                    </div>
                    <textarea name="permanent_address" id="editPermanent" rows="2" class="<?= $inp ?>"><?= h($u['permanent_address']) ?></textarea>
                </div>
                <div><label class="<?= $lbl ?>">Other Information / অন্যান্য তথ্য</label><input type="text" name="other_info" value="<?= h($u['other_info']) ?>" class="<?= $inp ?>"></div>
            </div>

            <div class="flex gap-2 pt-1 sticky bottom-0 bg-white pb-1">
                <button type="button" onclick="closeModal('memberEditModal')" class="flex-1 sm:flex-none px-5 py-3 sm:py-2.5 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="submit" id="editSubmitBtn" class="flex-1 sm:flex-none sm:ml-auto px-6 py-3 sm:py-2.5 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700 shadow-sm flex items-center justify-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i> Update Profile
                </button>
            </div>
        </form>
    </div>
</div>
<?php } ?>

<script>
    function openModal(id) {
        const m = document.getElementById(id);
        if (m) { m.classList.remove('hidden'); m.classList.add('flex'); }
    }
    function closeModal(id) {
        const m = document.getElementById(id);
        if (m) { m.classList.remove('flex'); m.classList.add('hidden'); }
    }

    <?php if ($canEdit) { ?>
    // ---- Edit profile modal ----
    (function () {
        const form = document.getElementById('profileEditForm');
        const input = document.getElementById('editPhotoInput');
        const prev = document.getElementById('editPhotoPreview');
        const icon = document.getElementById('editPhotoIcon');

        input.addEventListener('change', function () {
            const f = this.files[0];
            if (!f) { return; }
            if (f.size > 2 * 1024 * 1024) { alert('ছবির সাইজ সর্বোচ্চ 2MB হতে পারবে।'); this.value = ''; return; }
            const r = new FileReader();
            r.onload = function (e) { prev.src = e.target.result; prev.classList.remove('hidden'); icon.classList.add('hidden'); };
            r.readAsDataURL(f);
        });

        const same = document.getElementById('editSame'), pres = document.getElementById('editPresent'), perm = document.getElementById('editPermanent');
        same.addEventListener('change', function () { if (this.checked) { perm.value = pres.value; } });
        pres.addEventListener('input', function () { if (same.checked) { perm.value = this.value; } });

        form.addEventListener('submit', function (e) {
            const name = form.member_name.value.trim(), mob = form.mobile_no.value.replace(/\D/g, '');
            if (!name) { e.preventDefault(); alert('নাম দেওয়া আবশ্যক।'); return; }
            if (!/^[0-9]{11}$/.test(mob)) { e.preventDefault(); alert('মোবাইল নম্বর ১১ ডিজিটের হতে হবে।'); return; }
            const btn = document.getElementById('editSubmitBtn');
            btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Saving...';
        });

        document.getElementById('memberEditModal').addEventListener('mousedown', function (e) { if (e.target === this) { closeModal('memberEditModal'); } });
    })();
    <?php } ?>
</script>
<?php } ?>