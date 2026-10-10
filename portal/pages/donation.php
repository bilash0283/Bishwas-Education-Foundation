<?php
/*
 * DONATION PAGE - PHP 7.2 compatible, beginner friendly version
 *
 * Live server-e ja problem hochhilo (ebong fix):
 * 1. mysqli_stmt_get_result()  -> mysqlnd na thakle kaj kore na (list + summary khali ashto).
 *                                 Ekhon nijer helper db_select() use kora hoyeche.
 * 2. session_start()           -> $_SESSION check er AGE session start korte hobe. Ekhon sobar upore.
 * 3. header('Location')        -> age output hoye gele "headers already sent" error hoy.
 *                                 Ekhon ob_start() + go_to() (JavaScript fallback soho).
 * 4. finfo_open()              -> live server-e fileinfo extension na thakle fatal error hoy.
 *                                 Ekhon thakle finfo, na thakle getimagesize() diye check kore.
 * 5. function h()              -> shesh-e chhilo ebong duplicate hole fatal error. Ekhon upore, function_exists() soho.
 * 6. $db                       -> include file-er bhitor 'global $db' lage.
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

/* =====================================================================
   HELPER FUNCTIONS
===================================================================== */

if (!function_exists('h')) {
    // Safe output (XSS theke bachay)
    function h($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
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

/*
 * SELECT query chalay, sob row array hishebe ferot dey. (mysqlnd lage na)
 * $types  = 'i' (number), 's' (text), 'd' (decimal)   jemon: 'iss'
 * $params = value-gulo array-te                        jemon: array(5, 'a', 'b')
 */
if (!function_exists('db_select')) {
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
                $rows[] = $copy;
            }
            mysqli_free_result($meta);
        }

        mysqli_stmt_close($stmt);
        return $rows;
    }
}

// Shudhu prothom row ferot dey (na thakle null)
if (!function_exists('db_select_one')) {
    function db_select_one($conn, $sql, $types = '', $params = array())
    {
        $rows = db_select($conn, $sql, $types, $params);
        return count($rows) > 0 ? $rows[0] : null;
    }
}

/*
 * INSERT / UPDATE / DELETE chalay. Success hole true.
 * Error hole $GLOBALS['db_last_error'], affected row $GLOBALS['db_affected'] te thake.
 */
if (!function_exists('db_execute')) {
    function db_execute($conn, $sql, $types = '', $params = array())
    {
        $GLOBALS['db_last_error'] = '';
        $GLOBALS['db_affected'] = 0;

        $stmt = mysqli_prepare($conn, $sql);
        if (!$stmt) {
            $GLOBALS['db_last_error'] = mysqli_error($conn);
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
        if ($ok) {
            $GLOBALS['db_affected'] = mysqli_stmt_affected_rows($stmt);
        } else {
            $GLOBALS['db_last_error'] = mysqli_stmt_error($stmt);
        }
        mysqli_stmt_close($stmt);
        return $ok;
    }
}

// Receipt ekta asol chhobi kina check kore (fileinfo na thakleo kaj kore)
if (!function_exists('don_is_valid_image')) {
    function don_is_valid_image($tmp_name)
    {
        $allowed_mime = array('image/jpeg', 'image/png', 'image/webp');

        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmp_name);
            finfo_close($finfo);
            return in_array($mime, $allowed_mime);
        }

        // fileinfo extension nei: getimagesize diye check
        $info = @getimagesize($tmp_name);
        return ($info !== false && isset($info['mime']) && in_array($info['mime'], $allowed_mime));
    }
}

/*
 * Receipt upload kore.
 * Return: file name (success) / '' (kono file dey nai) / false (error, $err te message)
 */
if (!function_exists('don_upload_receipt')) {
    function don_upload_receipt($upload_dir, &$err)
    {
        if (!isset($_FILES['receipt']) || $_FILES['receipt']['error'] === UPLOAD_ERR_NO_FILE) {
            return '';
        }
        $file = $_FILES['receipt'];

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $err = 'Receipt আপলোডে সমস্যা হয়েছে (Error code: ' . $file['error'] . ')।';
            return false;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, array('jpg', 'jpeg', 'png', 'webp')) || !don_is_valid_image($file['tmp_name'])) {
            $err = 'Receipt শুধু JPG, JPEG, PNG বা WEBP ছবি হতে হবে।';
            return false;
        }
        if ($file['size'] > 5 * 1024 * 1024) {
            $err = 'Receipt এর সাইজ ৫MB এর বেশি হতে পারবে না।';
            return false;
        }

        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, 0755, true);
        }

        $new_name = time() . '_' . mt_rand(1000, 9999) . '.' . $ext;
        if (!move_uploaded_file($file['tmp_name'], $upload_dir . $new_name)) {
            $err = 'Receipt সেভ করা যায়নি। uploads/receipts ফোল্ডারের পারমিশন (755/775) চেক করুন।';
            return false;
        }
        return $new_name;
    }
}

/* =====================================================================
   ACCESS CHECK + DATABASE
===================================================================== */

// only admin can visit
if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], array('Admin'))) {
    go_to('index.php?page=dashboard');
}

global $db;
if (!isset($db) || !$db) {
    die('Database connection not found.');
}
mysqli_set_charset($db, 'utf8mb4');

$baseUrl = 'index.php?page=donation';
$error_message = '';

// Receipt folder (index.php je folder-e ache, sekhane uploads/receipts/)
$receipt_dir = dirname($_SERVER['SCRIPT_FILENAME']) . '/uploads/receipts/';

/* =====================================================================
   1) HANDLE FORM SUBMISSIONS (ADD / EDIT / DELETE)
===================================================================== */

$form_action = ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action'])) ? $_POST['form_action'] : '';

// ---------- ADD DONATION ----------
if ($form_action === 'add') {
    // ফর্ম থেকে ডাটা নেওয়া
    $donor_id = (isset($_POST['donor_id']) && $_POST['donor_id'] !== '') ? (int) $_POST['donor_id'] : 0;
    $amount = (float) (isset($_POST['amount']) ? $_POST['amount'] : 0);
    $donation_type = trim(isset($_POST['donation_type']) ? $_POST['donation_type'] : '');
    $payment_method = trim(isset($_POST['payment_method']) ? $_POST['payment_method'] : '');
    $transaction_id = trim(isset($_POST['transaction_id']) ? $_POST['transaction_id'] : '');
    $payment_status = trim(isset($_POST['payment_status']) ? $_POST['payment_status'] : 'pending');
    $donation_date = !empty($_POST['donation_date']) ? $_POST['donation_date'] : date('Y-m-d');
    $admin_note = trim(isset($_POST['admin_note']) ? $_POST['admin_note'] : '');
    $fund = trim(isset($_POST['fund']) ? $_POST['fund'] : '');

    // ফর্মে নেই, তাই ডিফল্ট ভ্যালু
    $type = 'Member';

    $errors = array();

    // ---------- ধাপ ১: ভ্যালিডেশন ----------
    $allowed_status = array('pending', 'paid', 'failed', 'rejected');

    if ($donor_id <= 0) {
        $errors[] = 'Donor select করুন।';
    }
    if ($amount <= 0) {
        $errors[] = 'Amount সঠিক নয়।';
    }
    if ($donation_type === '' || $payment_method === '') {
        $errors[] = 'Donation Type ও Payment Method দিন।';
    }
    if (!in_array($payment_status, $allowed_status)) {
        $payment_status = 'pending';
    }

    // ---------- ধাপ ২: Donor এর তথ্য users টেবিল থেকে আনা ----------
    $name = $email = $phone = '';

    if (empty($errors)) {
        $donor = db_select_one($db, 'SELECT member_name, email, mobile_no FROM users WHERE id = ? LIMIT 1', 'i', array($donor_id));
        if ($donor) {
            $name = $donor['member_name'];
            $email = $donor['email'];
            $phone = $donor['mobile_no'];
        } else {
            $errors[] = 'Donor খুঁজে পাওয়া যায়নি।';
        }
    }

    // ---------- ধাপ ৩: রিসিপ্ট আপলোড (optional) ----------
    $receipt = '';
    if (empty($errors)) {
        $up_err = '';
        $receipt = don_upload_receipt($receipt_dir, $up_err);
        if ($receipt === false) {
            $errors[] = $up_err;
            $receipt = '';
        }
    }

    // ---------- ধাপ ৪: ডাটাবেজে ইনসার্ট ----------
    if (empty($errors)) {
        $sql = "INSERT INTO donations
                (donor_id, type, name, email, phone, amount, donation_type, fund,
                 payment_method, transaction_id, payment_status,
                 donation_date, admin_note, receipt, created_at, updated_at)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())";

        // i=donor_id, ssss=type,name,email,phone, d=amount, বাকি ৮টি s
        $ok = db_execute(
            $db,
            $sql,
            'issssdssssssss',
            array(
                $donor_id,
                $type,
                $name,
                $email,
                $phone,
                $amount,
                $donation_type,
                $fund,
                $payment_method,
                $transaction_id,
                $payment_status,
                $donation_date,
                $admin_note,
                $receipt
            )
        );

        if ($ok) {
            go_to($baseUrl);
        } else {
            $errors[] = 'ডাটা সেভ হয়নি: ' . $GLOBALS['db_last_error'];
            // সেভ ব্যর্থ হলে আপলোড করা ছবি মুছে দেওয়া
            if ($receipt !== '' && is_file($receipt_dir . $receipt)) {
                @unlink($receipt_dir . $receipt);
            }
        }
    }

    // এরর থাকলে পেজে দেখানো হবে
    $error_message = implode('<br>', $errors);
}

// ---------- EDIT DONATION ----------
if ($form_action === 'edit') {

    $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);
    $new_donor_id = (isset($_POST['donor_id']) && $_POST['donor_id'] !== '') ? (int) $_POST['donor_id'] : 0;
    $amount = (float) (isset($_POST['amount']) ? $_POST['amount'] : 0);
    $donation_type = trim(isset($_POST['donation_type']) ? $_POST['donation_type'] : '');
    $fund = trim(isset($_POST['fund']) ? $_POST['fund'] : '');
    $payment_method = trim(isset($_POST['payment_method']) ? $_POST['payment_method'] : '');
    $transaction_id = trim(isset($_POST['transaction_id']) ? $_POST['transaction_id'] : '');
    $payment_status = trim(isset($_POST['payment_status']) ? $_POST['payment_status'] : 'pending');
    $donation_date = !empty($_POST['donation_date']) ? $_POST['donation_date'] : date('Y-m-d');
    $admin_note = trim(isset($_POST['admin_note']) ? $_POST['admin_note'] : '');

    $errors = array();

    // ---------- ধাপ ১: ভ্যালিডেশন ----------
    $allowed_status = array('pending', 'paid', 'failed', 'rejected');

    if ($id <= 0) {
        $errors[] = 'Donation খুঁজে পাওয়া যায়নি।';
    }
    if ($amount <= 0) {
        $errors[] = 'Amount সঠিক নয়।';
    }
    if ($donation_type === '' || $payment_method === '') {
        $errors[] = 'Donation Type ও Payment Method দিন।';
    }
    if (!in_array($payment_status, $allowed_status)) {
        $payment_status = 'pending';
    }

    // ---------- ধাপ ২: বর্তমান রেকর্ড ডাটাবেজ থেকে আনা ----------
    $donor_id = null;
    $type = $name = $email = $phone = $old_receipt = '';

    if (empty($errors)) {
        $old = db_select_one($db, 'SELECT donor_id, type, name, email, phone, receipt FROM donations WHERE id = ? LIMIT 1', 'i', array($id));
        if ($old) {
            $donor_id = $old['donor_id'];
            $type = $old['type'];
            $name = $old['name'];
            $email = $old['email'];
            $phone = $old['phone'];
            $old_receipt = (string) $old['receipt'];
        } else {
            $errors[] = 'Donation রেকর্ড পাওয়া যায়নি।';
        }
    }

    // ---------- ধাপ ৩: Donor বদলানো হলে users টেবিল থেকে নতুন তথ্য আনা ----------
    if (empty($errors) && $new_donor_id > 0) {
        $nu = db_select_one($db, 'SELECT member_name, email, mobile_no FROM users WHERE id = ? LIMIT 1', 'i', array($new_donor_id));
        if ($nu) {
            $donor_id = $new_donor_id;
            $type = 'Member';
            $name = $nu['member_name'];
            $email = $nu['email'];
            $phone = $nu['mobile_no'];
        } else {
            $errors[] = 'Donor খুঁজে পাওয়া যায়নি।';
        }
    }
    // $new_donor_id == 0 হলে আগের donor_id, type, name, email, phone অপরিবর্তিত থাকবে

    // ---------- ধাপ ৪: নতুন রিসিপ্ট আপলোড (দিলে) ----------
    $receipt = $old_receipt;   // নতুন ফাইল না দিলে পুরনোটাই থাকবে
    $uploaded_new = false;

    if (empty($errors)) {
        $up_err = '';
        $new_receipt = don_upload_receipt($receipt_dir, $up_err);
        if ($new_receipt === false) {
            $errors[] = $up_err;
        } elseif ($new_receipt !== '') {
            $receipt = $new_receipt;
            $uploaded_new = true;
        }
    }

    // ---------- ধাপ ৫: ডাটাবেজ আপডেট ----------
    if (empty($errors)) {

        // ১৪টি কলাম + WHERE id = মোট ১৫টি ?
        $sql = "UPDATE donations SET
                    donor_id = ?, type = ?, name = ?, email = ?, phone = ?, amount = ?,
                    donation_type = ?, fund = ?, payment_method = ?, transaction_id = ?,
                    payment_status = ?, donation_date = ?,
                    admin_note = ?, receipt = ?, updated_at = NOW()
                WHERE id = ?";

        // i ssss d ssssssssss i = ১৫টি
        $ok = db_execute(
            $db,
            $sql,
            'issssdssssssssi',
            array(
                $donor_id,
                $type,
                $name,
                $email,
                $phone,
                $amount,
                $donation_type,
                $fund,
                $payment_method,
                $transaction_id,
                $payment_status,
                $donation_date,
                $admin_note,
                $receipt,
                $id
            )
        );

        if ($ok) {
            // আপডেট সফল হলে, নতুন ছবি দিলে পুরনো ছবি মুছে ফেলা
            if ($uploaded_new && $old_receipt !== '') {
                $old_path = $receipt_dir . basename($old_receipt);
                if (is_file($old_path)) {
                    @unlink($old_path);
                }
            }
            go_to($baseUrl);
        } else {
            $errors[] = 'ডাটা আপডেট হয়নি: ' . $GLOBALS['db_last_error'];

            // আপডেট ব্যর্থ হলে এইমাত্র আপলোড করা নতুন ছবি মুছে দেওয়া
            if ($uploaded_new && is_file($receipt_dir . $receipt)) {
                @unlink($receipt_dir . $receipt);
            }
        }
    }

    // এরর থাকলে পেজে দেখানো হবে
    $error_message = implode('<br>', $errors);
}

// ---------- DELETE DONATION ----------
if ($form_action === 'delete') {

    $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);

    if ($id > 0) {

        // ধাপ ১: ডিলিটের আগে receipt এর ফাইলের নাম বের করা
        $rrow = db_select_one($db, 'SELECT receipt FROM donations WHERE id = ? LIMIT 1', 'i', array($id));
        $receipt_file = $rrow ? (string) $rrow['receipt'] : '';

        // ধাপ ২: ডাটাবেজ থেকে রেকর্ড ডিলিট
        $deleted = db_execute($db, 'DELETE FROM donations WHERE id = ?', 'i', array($id));
        $affected = $GLOBALS['db_affected'];

        // ধাপ ৩: রেকর্ড সত্যিই ডিলিট হলে তবেই ছবি মুছবে
        if ($deleted && $affected > 0 && $receipt_file !== '') {
            // basename() দিয়ে শুধু ফাইলের নাম নেওয়া (ফোল্ডারের বাইরের ফাইল মোছা আটকানোর জন্য)
            $file_path = $receipt_dir . basename($receipt_file);
            if (is_file($file_path)) {
                @unlink($file_path);
            }
        }
    }

    go_to($baseUrl);
}

/* =====================================================================
   2) FILTER (saved in session)
===================================================================== */

if (isset($_GET['clear_filter'])) {
    unset($_SESSION['donation_filter']);
} elseif (isset($_GET['filter_apply'])) {
    $_SESSION['donation_filter'] = array(
        'search' => trim(isset($_GET['search']) ? $_GET['search'] : ''),
        'payment_status' => trim(isset($_GET['payment_status']) ? $_GET['payment_status'] : ''),
        'fund' => trim(isset($_GET['fund']) ? $_GET['fund'] : ''),
        'date_from' => trim(isset($_GET['date_from']) ? $_GET['date_from'] : ''),
        'date_to' => trim(isset($_GET['date_to']) ? $_GET['date_to'] : ''),
    );
}

$filter = isset($_SESSION['donation_filter']) ? $_SESSION['donation_filter'] : array(
    'search' => '',
    'payment_status' => '',
    'fund' => '',
    'date_from' => '',
    'date_to' => '',
);

// Kono filter active ache kina (card e "Filtered" badge dekhate)
$isFiltered = false;
foreach ($filter as $fv) {
    if ($fv !== '') {
        $isFiltered = true;
        break;
    }
}

/* =====================================================================
   3) BUILD DYNAMIC WHERE CLAUSE (based on active filter)
===================================================================== */

$whereParts = array();
$params = array();
$paramTypes = '';

if ($filter['search'] !== '') {
    $whereParts[] = '(name LIKE ? OR email LIKE ? OR phone LIKE ? OR transaction_id LIKE ?)';
    $searchTerm = '%' . $filter['search'] . '%';
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $paramTypes .= 'ssss';
}

if ($filter['payment_status'] !== '') {
    $whereParts[] = 'payment_status = ?';
    $params[] = $filter['payment_status'];
    $paramTypes .= 's';
}

if ($filter['fund'] !== '') {
    $whereParts[] = 'fund = ?';
    $params[] = $filter['fund'];
    $paramTypes .= 's';
}

if ($filter['date_from'] !== '') {
    $whereParts[] = 'donation_date >= ?';
    $params[] = $filter['date_from'];
    $paramTypes .= 's';
}

if ($filter['date_to'] !== '') {
    $whereParts[] = 'donation_date <= ?';
    $params[] = $filter['date_to'];
    $paramTypes .= 's';
}

$whereSQL = '';
if (count($whereParts) > 0) {
    $whereSQL = 'WHERE ' . implode(' AND ', $whereParts);
}

/* =====================================================================
   4) PAGINATION + SUMMARY (total count & amounts, filter shoho)
   Summary shob matching record er upor calculate hoy (shudhu current
   page er na), tai card er value filter er upor base kore ashe.
===================================================================== */

$perPage = 10;
$currentPage = isset($_GET['pg']) ? (int) $_GET['pg'] : 1;
if ($currentPage < 1) {
    $currentPage = 1;
}
$offset = ($currentPage - 1) * $perPage;

$summarySql = "SELECT
                    COUNT(*)                                                       AS total,
                    COALESCE(SUM(amount), 0)                                       AS total_amount,
                    COALESCE(SUM(CASE WHEN payment_status = 'paid'    THEN amount ELSE 0 END), 0) AS paid_amount,
                    COALESCE(SUM(CASE WHEN payment_status = 'pending' THEN amount ELSE 0 END), 0) AS pending_amount
               FROM donations $whereSQL";

$summary = db_select_one($db, $summarySql, $paramTypes, $params);
if (!$summary) {
    $summary = array('total' => 0, 'total_amount' => 0, 'paid_amount' => 0, 'pending_amount' => 0);
}

$totalRows = (int) $summary['total'];
$totalAmount = (float) $summary['total_amount'];
$paidAmount = (float) $summary['paid_amount'];
$pendingAmount = (float) $summary['pending_amount'];

$totalPages = (int) ceil($totalRows / $perPage);
if ($totalPages < 1) {
    $totalPages = 1;
}

/* =====================================================================
   5) FETCH DONATIONS (filter + pagination shoho)
===================================================================== */

$listSql = "SELECT id, donor_id, type, name, email, phone, amount, donation_type,
                   fund, payment_method, transaction_id, payment_status,
                   donation_date, admin_note, receipt,
                   created_at, updated_at
            FROM donations
            $whereSQL
            ORDER BY id DESC
            LIMIT ? OFFSET ?";

$listParams = $params;
$listParams[] = $perPage;
$listParams[] = $offset;

$donations = db_select($db, $listSql, $paramTypes . 'ii', $listParams);
?>

<section class="page-content space-y-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Donations</h2>
            <p class="text-xs text-slate-500 mt-0.5">Manage all donation records, filter, edit and delete.</p>
        </div>
        <button onclick="openModal('addDonationModal')"
            class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-emerald-600/20 flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Add Donation
        </button>
    </div>

    <!-- ===================== FILTER BAR ===================== -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5">
        <form method="GET" action="<?= h($baseUrl) ?>" class="grid grid-cols-1 md:grid-cols-6 gap-3">

            <!-- GET form e query string hariye jay, tai page=donation hidden field e pathai -->
            <input type="hidden" name="page" value="donation">
            <input type="hidden" name="filter_apply" value="1">

            <div class="md:col-span-2">
                <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Search</label>
                <input type="text" name="search" value="<?= h($filter['search']) ?>"
                    placeholder="Name, email, phone, transaction id..."
                    class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
            </div>

            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Payment Status</label>
                <select name="payment_status"
                    class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                    <option value="">All</option>
                    <?php foreach (['pending', 'paid', 'failed', 'rejected'] as $ps): ?>
                        <option value="<?= h($ps) ?>" <?= $filter['payment_status'] === $ps ? 'selected' : '' ?>>
                            <?= h(ucfirst($ps)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Date From</label>
                <input type="date" name="date_from" value="<?= h($filter['date_from']) ?>"
                    class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
            </div>

            <div>
                <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Date To</label>
                <input type="date" name="date_to" value="<?= h($filter['date_to']) ?>"
                    class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
            </div>

            <div class="md:col-span-6 flex items-center gap-2 pt-1">
                <button type="submit"
                    class="px-4 py-2 bg-slate-800 hover:bg-slate-900 text-white rounded-xl text-xs font-semibold flex items-center gap-2">
                    <i class="fa-solid fa-filter"></i> Apply Filter
                </button>
                <a href="<?= h($baseUrl) ?>&clear_filter=1"
                    class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50 flex items-center gap-2">
                    <i class="fa-solid fa-xmark"></i> Clear Filter
                </a>
                <span class="text-[11px] text-slate-400 ml-2">
                    Total records found: <b><?= (int) $totalRows ?></b>
                </span>
            </div>
        </form>
    </div>

    <!-- ===================== SUMMARY CARDS (filter based) ===================== -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">

        <!-- Total Amount -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 flex items-center gap-4">
            <div
                class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg shrink-0">
                <i class="fa-solid fa-sack-dollar"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-bold uppercase text-slate-500">
                    Total Amount
                    <?php if ($isFiltered): ?>
                        <span
                            class="ml-1 px-1.5 py-0.5 rounded bg-emerald-100 text-emerald-700 text-[9px] normal-case">Filtered</span>
                    <?php endif; ?>
                </p>
                <p class="text-xl font-bold text-slate-800 truncate">৳ <?= number_format($totalAmount, 2) ?></p>
            </div>
        </div>

        <!-- Total Records -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-lg shrink-0">
                <i class="fa-solid fa-list-check"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-bold uppercase text-slate-500">Total Donations</p>
                <p class="text-xl font-bold text-slate-800"><?= number_format($totalRows) ?></p>
            </div>
        </div>

        <!-- Paid Amount -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 flex items-center gap-4">
            <div
                class="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-lg shrink-0">
                <i class="fa-solid fa-circle-check"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-bold uppercase text-slate-500">Paid Amount</p>
                <p class="text-xl font-bold text-slate-800 truncate">৳ <?= number_format($paidAmount, 2) ?></p>
            </div>
        </div>

        <!-- Pending Amount -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 flex items-center gap-4">
            <div
                class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-lg shrink-0">
                <i class="fa-solid fa-hourglass-half"></i>
            </div>
            <div class="min-w-0">
                <p class="text-[11px] font-bold uppercase text-slate-500">Pending Amount</p>
                <p class="text-xl font-bold text-slate-800 truncate">৳ <?= number_format($pendingAmount, 2) ?></p>
            </div>
        </div>
    </div>

    <?php if (!empty($error_message)): ?>
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-lg">
            <?= $error_message ?>
        </div>
    <?php endif; ?>

    <!-- ===================== DONATIONS TABLE ===================== -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 text-sm">Donation Records</h3>
            <span class="text-xs text-slate-400">Page <?= $currentPage ?> of <?= $totalPages ?></span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-700 uppercase font-bold border-b border-slate-200">
                    <tr>
                        <th class="p-4">Donor</th>
                        <th class="p-4">Contact</th>
                        <th class="p-4">Amount</th>
                        <th class="p-4">Fund / Type</th>
                        <th class="p-4">Payment</th>
                        <th class="p-4">Status</th>
                        <th class="p-4">Date</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (count($donations) === 0): ?>
                        <tr>
                            <td colspan="8" class="p-6 text-center text-slate-400">No donation records found.</td>
                        </tr>
                    <?php endif; ?>

                    <?php foreach ($donations as $d): ?>
                        <tr class="hover:bg-slate-50/80">
                            <td class="p-4 font-semibold text-slate-800">
                                <?= h($d['name']) ?>
                                <span class="block text-[10px] font-normal text-slate-400">
                                    <?= h($d['type']) ?>
                                </span>
                            </td>
                            <td class="p-4">
                                <?= h($d['email']) ?>
                                <span class="block text-[10px] text-slate-400"><?= h($d['phone']) ?></span>
                            </td>
                            <td class="p-4 font-semibold text-slate-800">
                                <?= number_format((float) $d['amount'], 2) ?>
                            </td>
                            <td class="p-4">
                                <?= h($d['fund']) ?>
                                <span class="block text-[10px] text-slate-400"><?= h($d['donation_type']) ?></span>
                            </td>
                            <td class="p-4">
                                <?= h($d['payment_method']) ?>
                                <span class="block text-[10px] text-slate-400"><?= h($d['transaction_id']) ?></span>
                            </td>
                            <td class="p-4 space-y-1">
                                <?php
                                $status = strtolower($d['payment_status'] ?? '');

                                $status_colors = [
                                    'paid' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                    'pending' => 'bg-amber-50 text-amber-700 border-amber-200',
                                    'failed' => 'bg-sky-50 text-sky-700 border-sky-200',
                                    'rejected' => 'bg-red-50 text-red-700 border-red-200',
                                ];

                                $badge_class = $status_colors[$status] ?? 'bg-slate-100 text-slate-600 border-slate-200';
                                ?>
                                <span
                                    class="inline-block px-2.5 py-1 rounded-md border font-bold text-[10px] <?= $badge_class ?>">
                                    <?= h(ucfirst($d['payment_status'])) ?>
                                </span>
                            </td>
                            <td class="p-4 whitespace-nowrap">
                                <?= h(date('d-m-y', strtotime($d['donation_date']))) ?>
                            </td>
                            <td class="p-4 text-right space-x-1 whitespace-nowrap">
                                <!-- Receipt দেখুন -->
                                <button onclick='openReceiptModal(<?= json_encode($d, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                    title="View Receipt" class="p-1.5 text-slate-400 hover:text-sky-600 transition-colors">
                                    <i class="fa-solid fa-image"></i>
                                </button>

                                <!-- Pay Slip দেখুন -->
                                <button onclick='openPaySlipModal(<?= json_encode($d, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                    title="View Pay Slip"
                                    class="p-1.5 text-slate-400 hover:text-amber-600 transition-colors">
                                    <i class="fa-solid fa-file-invoice"></i>
                                </button>

                                <button onclick='openEditModal(<?= json_encode($d, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                    class="p-1.5 text-slate-400 hover:text-emerald-600 transition-colors">
                                    <i class="fa-solid fa-pen"></i>
                                </button>

                                <button
                                    onclick="openDeleteModal('<?= (int) $d['id'] ?>', '<?= h(addslashes($d['name'])) ?>')"
                                    class="p-1.5 text-slate-400 hover:text-rose-600 transition-colors">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- ===================== PAGINATION ===================== -->
        <div class="p-4 flex items-center justify-between border-t border-slate-100 text-xs">
            <div class="text-slate-400">
                Showing <?= count($donations) ?> of <?= (int) $totalRows ?> records
            </div>
            <div class="flex items-center gap-1">
                <?php
                $prevPage = max(1, $currentPage - 1);
                $nextPage = min($totalPages, $currentPage + 1);
                ?>
                <a href="<?= h($baseUrl) ?>&pg=<?= $prevPage ?>"
                    class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 <?= $currentPage <= 1 ? 'pointer-events-none opacity-40' : '' ?>">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>

                <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a href="<?= h($baseUrl) ?>&pg=<?= $p ?>"
                        class="px-3 py-1.5 rounded-lg border <?= $p == $currentPage ? 'bg-emerald-600 text-white border-emerald-600' : 'border-slate-200 hover:bg-slate-50' ?>">
                        <?= $p ?>
                    </a>
                <?php endfor; ?>

                <a href="<?= h($baseUrl) ?>&pg=<?= $nextPage ?>"
                    class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 <?= $currentPage >= $totalPages ? 'pointer-events-none opacity-40' : '' ?>">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ===================== VIEW RECEIPT MODAL ===================== -->
<div id="receiptModal"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center p-4 z-50">
    <div
        class="bg-white w-full max-w-xl rounded-2xl shadow-2xl overflow-hidden border border-slate-100 max-h-[90vh] overflow-y-auto">
        <div class="p-5 bg-slate-900 text-white flex justify-between items-center sticky top-0">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <i class="fa-solid fa-image text-sky-400"></i>
                <span>Receipt</span>
            </h3>
            <button onclick="closeModal('receiptModal')" class="text-slate-400 hover:text-white">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="p-6 text-center">
            <!-- receipt থাকলে ছবি দেখাবে -->
            <div id="receipt_wrapper" class="hidden">
                <img id="receipt_img" src="" alt="Receipt"
                    class="max-w-full max-h-[60vh] mx-auto rounded-lg border border-slate-200">
                <a id="receipt_open_link" href="#" target="_blank"
                    class="inline-block mt-4 text-xs font-semibold text-emerald-600 underline">
                    নতুন ট্যাবে বড় করে দেখুন
                </a>
            </div>

            <!-- receipt না থাকলে এই মেসেজ -->
            <div id="receipt_empty" class="hidden py-10 text-slate-400">
                <i class="fa-regular fa-image text-4xl mb-3"></i>
                <p class="text-sm">No receipt uploaded.</p>
            </div>
        </div>
    </div>
</div>

<!-- ===================== VIEW PAY SLIP MODAL ===================== -->
<div id="paySlipModal"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center p-4 z-50">
    <div
        class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden border border-slate-100 max-h-[92vh] overflow-y-auto">

        <!-- মডালের হেডার (প্রিন্টে আসবে না) -->
        <div class="p-4 bg-slate-900 text-white flex justify-between items-center sticky top-0 z-10">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <i class="fa-solid fa-file-invoice text-amber-400"></i>
                <span>Pay Slip</span>
            </h3>
            <button onclick="closeModal('paySlipModal')" class="text-slate-400 hover:text-white">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="p-4 sm:p-6 bg-slate-100">

            <!-- ============ এই অংশটাই প্রিন্ট হবে ============ -->
            <div id="payslip_content">
                <div
                    class="relative bg-white border border-slate-200 rounded-xl overflow-hidden text-slate-800 font-sans [-webkit-print-color-adjust:exact] [print-color-adjust:exact]">

                    <!-- ওয়াটারমার্ক -->
                    <img id="ps_watermark" src="" alt="" onerror="this.style.display='none'"
                        class="absolute top-1/2 left-1/2 w-1/2 -translate-x-1/2 -translate-y-1/2 opacity-[0.07] pointer-events-none z-0">

                    <!-- উপরের রঙিন লাইন -->
                    <div class="h-2 bg-gradient-to-r from-emerald-800 via-emerald-600 to-emerald-400"></div>

                    <div class="relative z-10 px-5 sm:px-7 py-6">

                        <!-- হেডার: লোগো + সংস্থার নাম + Slip No -->
                        <div
                            class="flex flex-col sm:flex-row sm:justify-between sm:items-start gap-4 pb-4 border-b-2 border-emerald-700">
                            <div class="flex items-center gap-3">
                                <img id="ps_logo" src="" alt="Logo" onerror="this.style.display='none'"
                                    class="w-16 h-16 object-contain">
                                <div>
                                    <div id="ps_org_name" class="text-lg font-bold text-emerald-800 leading-tight">
                                    </div>
                                    <div id="ps_org_address" class="text-[11px] text-slate-500 mt-0.5"></div>
                                    <div id="ps_org_contact" class="text-[11px] text-slate-500"></div>
                                </div>
                            </div>
                            <div class="sm:text-right shrink-0">
                                <div class="text-[10px] uppercase tracking-widest text-slate-400">Slip No</div>
                                <div id="ps_id" class="text-lg font-bold text-emerald-700"></div>
                                <div class="text-[10px] uppercase tracking-widest text-slate-400 mt-1.5">Date</div>
                                <div id="ps_date" class="text-sm font-bold"></div>
                            </div>
                        </div>

                        <!-- শিরোনাম -->
                        <div class="text-center my-4 text-[15px] font-bold tracking-[3px] text-slate-700">
                            DONATION PAY SLIP
                        </div>

                        <!-- Amount বক্স -->
                        <div
                            class="flex justify-between items-center gap-3 bg-emerald-50 border border-emerald-200 rounded-lg px-4 py-3.5 mb-4">
                            <div>
                                <div class="text-[10px] uppercase tracking-widest text-emerald-700">Total Amount</div>
                                <div id="ps_amount" class="text-2xl sm:text-3xl font-bold text-emerald-800"></div>
                            </div>
                            <div id="ps_status"
                                class="px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest border">
                            </div>
                        </div>

                        <!-- তথ্যের দুই কলাম -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3.5">
                            <div class="border border-slate-200 rounded-lg p-3.5 bg-white/70">
                                <div
                                    class="text-[11px] font-bold uppercase tracking-widest text-emerald-700 border-b border-slate-200 pb-1.5 mb-2">
                                    Donor Information
                                </div>
                                <div class="flex justify-between gap-3 text-xs py-1">
                                    <span class="text-slate-500 shrink-0">Name</span>
                                    <b id="ps_name" class="text-right break-words"></b>
                                </div>
                                <div class="flex justify-between gap-3 text-xs py-1">
                                    <span class="text-slate-500 shrink-0">Email</span>
                                    <b id="ps_email" class="text-right break-all"></b>
                                </div>
                                <div class="flex justify-between gap-3 text-xs py-1">
                                    <span class="text-slate-500 shrink-0">Phone</span>
                                    <b id="ps_phone" class="text-right break-words"></b>
                                </div>
                            </div>

                            <div class="border border-slate-200 rounded-lg p-3.5 bg-white/70">
                                <div
                                    class="text-[11px] font-bold uppercase tracking-widest text-emerald-700 border-b border-slate-200 pb-1.5 mb-2">
                                    Payment Details
                                </div>
                                <div class="flex justify-between gap-3 text-xs py-1">
                                    <span class="text-slate-500 shrink-0">Donation Type</span>
                                    <b id="ps_type" class="text-right break-words"></b>
                                </div>
                                <div class="flex justify-between gap-3 text-xs py-1">
                                    <span class="text-slate-500 shrink-0">Fund</span>
                                    <b id="ps_fund" class="text-right break-words"></b>
                                </div>
                                <div class="flex justify-between gap-3 text-xs py-1">
                                    <span class="text-slate-500 shrink-0">Method</span>
                                    <b id="ps_method" class="text-right break-words"></b>
                                </div>
                                <div class="flex justify-between gap-3 text-xs py-1">
                                    <span class="text-slate-500 shrink-0">Transaction ID</span>
                                    <b id="ps_trx" class="text-right break-all"></b>
                                </div>
                            </div>
                        </div>

                        <!-- নোট -->
                        <div class="mt-3.5 text-xs bg-slate-50 border-l-[3px] border-emerald-700 px-3 py-2 rounded">
                            <span class="font-bold text-emerald-700">Note:</span>
                            <span id="ps_note"></span>
                        </div>

                        <!-- ফুটার -->
                        <div class="flex justify-between items-end gap-4 mt-9">
                            <div class="text-[13px] font-bold text-emerald-800">
                                <!-- জাযাকাল্লাহু খাইরান।<br> -->
                                <small class="font-normal text-slate-500 text-[11px]">Thank you for your generous
                                    donation.</small>
                            </div>
                            <div class="text-center w-40">
                                <div class="border-t border-slate-700 mb-1"></div>
                                <div class="text-[10px] uppercase tracking-widest text-slate-500">Authorized Signature
                                </div>
                            </div>
                        </div>

                        <div class="text-center mt-5 text-[10px] text-slate-400">
                            This is a computer generated pay slip.
                        </div>
                    </div>

                    <!-- নিচের রঙিন লাইন -->
                    <div class="h-2 bg-gradient-to-r from-emerald-800 via-emerald-600 to-emerald-400"></div>
                </div>
            </div>
            <!-- ============ প্রিন্ট অংশ শেষ ============ -->

            <div class="flex justify-end gap-2 pt-5">
                <button type="button" onclick="closeModal('paySlipModal')"
                    class="px-4 py-2 border border-slate-300 bg-white rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Close</button>
                <button type="button" onclick="printPaySlip()"
                    class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700 shadow-sm flex items-center gap-2">
                    <i class="fa-solid fa-print"></i> Print / Save PDF
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===================== ADD DONATION MODAL ===================== -->
<div id="addDonationModal"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center p-4 z-50">
    <div
        class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden border border-slate-100 max-h-[90vh] overflow-y-auto">
        <div class="p-5 bg-slate-900 text-white flex justify-between items-center sticky top-0">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <i class="fa-solid fa-square-plus text-emerald-400"></i>
                <span>Add Donation</span>
            </h3>
            <button onclick="closeModal('addDonationModal')" class="text-slate-400 hover:text-white">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>
        <form method="POST" action="<?= h($baseUrl) ?>" enctype="multipart/form-data" class="p-6 space-y-4">
            <input type="hidden" name="form_action" value="add">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Donor Select <span
                            class="text-red-800">*</span> </label>
                    <select name="donor_id"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500"
                        required>
                        <option value="" selected disabled>Select a Donor</option>
                        <?php
                        $sql = "SELECT id, member_name FROM users";
                        $res = mysqli_query($db, $sql);
                        while ($row = mysqli_fetch_assoc($res)) {
                            ?>
                            <option value="<?= (int) $row['id'] ?>"><?= h($row['member_name']) ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Receipt (JPG, PNG, WEBP - max
                        5MB)</label>
                    <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Amount <span
                            class="text-red-800">*</span></label>
                    <input type="number" step="0.01" name="amount" required
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Donation Type <span
                            class="text-red-800">*</span></label>
                    <select name="donation_type"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500"
                        required>
                        <option value="" disabled selected>Select Donation Type</option>
                        <option value="General">General</option>
                        <option value="Monthly">Monthly</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Fund <span
                            class="text-red-800">*</span></label>
                    <select name="fund"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500"
                        required>
                        <option value="" disabled selected>Select Fund</option>
                        <?php
                        $sql = mysqli_query($db, "SELECT * FROM donation_sectors WHERE status = 'active' ORDER BY id DESC");
                        while ($row = mysqli_fetch_assoc($sql)) {
                            ?>
                            <option value="<?= h($row['title']) ?>"><?= h($row['title']) ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Payment Method <span
                            class="text-red-800">*</span></label>
                    <select name="payment_method"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500"
                        required>
                        <option value="" disabled selected>Select Payment Method</option>
                        <option value="bKash">bKash</option>
                        <option value="Nagad">Nagad</option>
                        <option value="Rocket">Rocket</option>
                        <option value="Upay">Upay</option>
                        <option value="SureCash">Sure Cash</option>
                        <option value="Bank">Bank</option>
                        <option value="Cash">Cash</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Transaction ID </label>
                    <input type="text" name="transaction_id"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Payment Status <span
                            class="text-red-800">*</span></label>
                    <select name="payment_status"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500"
                        required>
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="failed">Failed</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Donation Date <span
                            class="text-red-800">*</span></label>
                    <input type="date" name="donation_date" value="<?= date('Y-m-d') ?>"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500"
                        required>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Admin Note</label>
                <textarea rows="2" name="admin_note"
                    class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('addDonationModal')"
                    class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="submit"
                    class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700 shadow-sm">Save
                    Donation</button>
            </div>
        </form>
    </div>
</div>

<!-- ===================== EDIT DONATION MODAL ===================== -->
<div id="editDonationModal"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center p-4 z-50">
    <div
        class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden border border-slate-100 max-h-[90vh] overflow-y-auto">
        <div class="p-5 bg-slate-900 text-white flex justify-between items-center sticky top-0">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <i class="fa-solid fa-pen text-emerald-400"></i>
                <span>Edit Donation</span>
            </h3>
            <button onclick="closeModal('editDonationModal')" class="text-slate-400 hover:text-white">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form method="POST" action="<?= h($baseUrl) ?>" enctype="multipart/form-data" class="p-6 space-y-4">
            <input type="hidden" name="form_action" value="edit">
            <input type="hidden" name="id" id="edit_id">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Donor Select</label>
                    <select name="donor_id" id="edit_donor_id"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                        <option value="">Keep current donor</option>
                        <?php
                        $e_res = mysqli_query($db, "SELECT id, member_name FROM users");
                        while ($e_row = mysqli_fetch_assoc($e_res)) {
                            ?>
                            <option value="<?= (int) $e_row['id'] ?>"><?= h($e_row['member_name']) ?></option>
                        <?php } ?>
                    </select>
                    <p class="text-[10px] text-slate-400 mt-1">
                        Current: <span id="edit_current_donor" class="font-semibold"></span>
                    </p>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Receipt (JPG, PNG, WEBP - max
                        5MB)</label>
                    <input type="file" name="receipt" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                    <p class="text-[10px] text-slate-400 mt-1">
                        Current:
                        <a id="edit_current_receipt" href="#" target="_blank"
                            class="text-emerald-600 font-semibold underline"></a>
                        <span id="edit_no_receipt" class="hidden">No receipt</span>
                        <span class="block">নতুন ছবি দিলে পুরনোটা মুছে যাবে।</span>
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Amount <span
                            class="text-red-800">*</span></label>
                    <input type="number" step="0.01" name="amount" id="edit_amount" required
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Donation Type <span
                            class="text-red-800">*</span></label>
                    <select name="donation_type" id="edit_donation_type"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500"
                        required>
                        <option value="" disabled>Select Donation Type</option>
                        <option value="General">General</option>
                        <option value="Monthly">Monthly</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Fund <span
                            class="text-red-800">*</span></label>
                    <select name="fund" id="edit_fund"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500"
                        required>
                        <option value="" disabled>Select Fund</option>
                        <?php
                        $f_res = mysqli_query($db, "SELECT * FROM donation_sectors WHERE status = 'active' ORDER BY id DESC");
                        while ($f_row = mysqli_fetch_assoc($f_res)) {
                            ?>
                            <option value="<?= h($f_row['title']) ?>"><?= h($f_row['title']) ?></option>
                        <?php } ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Payment Method <span
                            class="text-red-800">*</span></label>
                    <select name="payment_method" id="edit_payment_method"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500"
                        required>
                        <option value="" disabled>Select Payment Method</option>
                        <option value="bKash">bKash</option>
                        <option value="Nagad">Nagad</option>
                        <option value="Rocket">Rocket</option>
                        <option value="Upay">Upay</option>
                        <option value="SureCash">Sure Cash</option>
                        <option value="Bank">Bank</option>
                        <option value="Cash">Cash</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Transaction ID</label>
                    <input type="text" name="transaction_id" id="edit_transaction_id"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Payment Status <span
                            class="text-red-800">*</span></label>
                    <select name="payment_status" id="edit_payment_status"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500"
                        required>
                        <option value="pending">Pending</option>
                        <option value="paid">Paid</option>
                        <option value="failed">Failed</option>
                        <option value="rejected">Rejected</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Donation Date <span
                            class="text-red-800">*</span></label>
                    <input type="date" name="donation_date" id="edit_donation_date" required
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Admin Note</label>
                <textarea rows="2" name="admin_note" id="edit_admin_note"
                    class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('editDonationModal')"
                    class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="submit"
                    class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700 shadow-sm">Update
                    Donation</button>
            </div>
        </form>
    </div>
</div>

<!-- ===================== DELETE CONFIRM MODAL ===================== -->
<div id="deleteDonationModal"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center p-4 z-50">
    <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl p-6 text-center border border-slate-100">
        <div
            class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center text-xl mx-auto mb-4">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h3 class="font-bold text-slate-800 text-base">Confirm Deletion</h3>
        <p class="text-xs text-slate-500 mt-1">
            Are you sure you want to remove
            <span id="deleteDonationTargetName" class="font-bold text-slate-700"></span>?
            This action cannot be undone.
        </p>
        <form method="POST" action="<?= h($baseUrl) ?>" class="flex justify-center gap-3 mt-6">
            <input type="hidden" name="form_action" value="delete">
            <input type="hidden" name="id" id="delete_id">
            <button type="button" onclick="closeModal('deleteDonationModal')"
                class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
            <button type="submit"
                class="px-4 py-2 bg-rose-600 text-white rounded-xl text-xs font-semibold hover:bg-rose-700 shadow-sm">Delete</button>
        </form>
    </div>
</div>

<script>
    // ---------- Receipt modal ----------
    function openReceiptModal(donation) {
        const wrapper = document.getElementById('receipt_wrapper');
        const empty = document.getElementById('receipt_empty');

        if (donation.receipt) {
            // Add/Edit এ যেখানে সেভ করা হয়, সেই একই ফোল্ডার
            const url = 'uploads/receipts/' + donation.receipt;
            document.getElementById('receipt_img').src = url;
            document.getElementById('receipt_open_link').href = url;
            wrapper.classList.remove('hidden');
            empty.classList.add('hidden');
        } else {
            wrapper.classList.add('hidden');
            empty.classList.remove('hidden');
        }

        openModal('receiptModal');
    }

    // ===== সংস্থার তথ্য (নিজের তথ্য দিন) =====
    const ORG = {
        name: '<?php echo $site_title; ?>',
        address: '<?php echo $office_address; ?>',
        contact: 'ফোন: <?php echo $phone_number; ?> | ইমেইল: <?php echo $email_address; ?>',
        logo: '../public/assets/<?php echo $favicon_icon; ?>'  // লোগো ফাইলের path (যেমন: uploads/logo.png)
    };

    // Status ব্যাজের রঙ (Tailwind ক্লাস পুরো লিখতে হবে, নইলে Tailwind চিনবে না)
    const STATUS_CLASSES = {
        paid: 'px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest border bg-emerald-100 text-emerald-800 border-emerald-300',
        pending: 'px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest border bg-amber-100 text-amber-800 border-amber-300',
        failed: 'px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest border bg-red-100 text-red-800 border-red-300',
        rejected: 'px-4 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest border bg-blue-100 text-blue-800 border-blue-300'
    };

    // ---------- Pay Slip modal ----------
    function openPaySlipModal(donation) {
        // সংস্থার তথ্য ও লোগো
        document.getElementById('ps_org_name').textContent = ORG.name;
        document.getElementById('ps_org_address').textContent = ORG.address;
        document.getElementById('ps_org_contact').textContent = ORG.contact;

        const logo = document.getElementById('ps_logo');
        const mark = document.getElementById('ps_watermark');
        logo.style.display = '';
        mark.style.display = '';
        logo.src = ORG.logo;
        mark.src = ORG.logo;

        // Slip No: যেমন DN-000125
        document.getElementById('ps_id').textContent = 'DN-' + String(donation.id).padStart(6, '0');
        document.getElementById('ps_date').textContent = donation.donation_date || '-';

        // textContent ব্যবহার করা হয়েছে, তাই ডাটায় HTML থাকলেও নিরাপদ
        document.getElementById('ps_name').textContent = donation.name || '-';
        document.getElementById('ps_email').textContent = donation.email || '-';
        document.getElementById('ps_phone').textContent = donation.phone || '-';
        document.getElementById('ps_type').textContent = donation.donation_type || '-';
        document.getElementById('ps_fund').textContent = donation.fund || '-';
        document.getElementById('ps_method').textContent = donation.payment_method || '-';
        document.getElementById('ps_trx').textContent = donation.transaction_id || '-';
        document.getElementById('ps_note').textContent = donation.admin_note || '-';

        document.getElementById('ps_amount').textContent =
            '৳ ' + Number(donation.amount || 0).toLocaleString('en-US', {
                minimumFractionDigits: 2, maximumFractionDigits: 2
            });

        // Status ব্যাজ
        const status = (donation.payment_status || 'pending').toLowerCase();
        const badge = document.getElementById('ps_status');
        badge.textContent = status;
        badge.className = STATUS_CLASSES[status] || STATUS_CLASSES.pending;

        openModal('paySlipModal');
    }

    // ---------- Pay Slip প্রিন্ট ----------
    function printPaySlip() {
        // এই পেজে যে Tailwind (CSS ফাইল বা CDN script) আছে, প্রিন্ট উইন্ডোতেও সেটা পাঠানো
        let head = '';
        document.querySelectorAll('link[rel="stylesheet"]').forEach(function (l) {
            head += '<link rel="stylesheet" href="' + l.href + '">';
        });
        document.querySelectorAll('script[src*="tailwind"]').forEach(function (s) {
            head += '<script src="' + s.src + '"><\/script>';
        });

        const content = document.getElementById('payslip_content').innerHTML;
        const win = window.open('', '_blank', 'width=900,height=900');

        win.document.write(
            '<html><head><meta charset="UTF-8"><title>Pay Slip</title>' + head +
            '<style>@page{size:A4;margin:10mm;} body{margin:0;background:#fff;}</style>' +
            '</head><body>' + content + '</body></html>'
        );
        win.document.close();

        // Tailwind ও ছবি লোড হওয়ার জন্য একটু অপেক্ষা করে প্রিন্ট
        setTimeout(function () {
            win.focus();
            win.print();
        }, 1200);
    }

    // Dynamic open function for any modal
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    // Dynamic close function for any modal
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }
    }

    // Delete modal open + set target id/name
    function openDeleteModal(id, name) {
        document.getElementById('delete_id').value = id;
        document.getElementById('deleteDonationTargetName').innerText = name;
        openModal('deleteDonationModal');
    }

    // Select এ ভ্যালু বসানো। ভ্যালু তালিকায় না থাকলে (পুরনো ডাটা) নতুন অপশন যোগ করে দেয়
    function setSelectValue(selectId, value) {
        const select = document.getElementById(selectId);
        if (!select) return;

        if (value && !Array.from(select.options).some(opt => opt.value === value)) {
            const opt = document.createElement('option');
            opt.value = value;
            opt.textContent = value;
            select.appendChild(opt);
        }
        select.value = value || '';
    }

    // Edit modal open + clicked row এর ডাটা দিয়ে ফর্ম পূরণ
    function openEditModal(donation) {
        document.getElementById('edit_id').value = donation.id;
        document.getElementById('edit_amount').value = donation.amount || '';
        document.getElementById('edit_transaction_id').value = donation.transaction_id || '';
        document.getElementById('edit_donation_date').value = donation.donation_date || '';
        document.getElementById('edit_admin_note').value = donation.admin_note || '';

        setSelectValue('edit_donation_type', donation.donation_type);
        setSelectValue('edit_fund', donation.fund);
        setSelectValue('edit_payment_method', donation.payment_method);
        setSelectValue('edit_payment_status', donation.payment_status || 'pending');

        // Donor: আগের donor সিলেক্ট থাকবে। donor_id না থাকলে "Keep current donor"
        document.getElementById('edit_donor_id').value = donation.donor_id || '';
        document.getElementById('edit_current_donor').innerText =
            (donation.name || '-') + (donation.email ? ' (' + donation.email + ')' : '');

        // বর্তমান receipt এর লিংক
        const link = document.getElementById('edit_current_receipt');
        const noReceipt = document.getElementById('edit_no_receipt');
        if (donation.receipt) {
            link.href = 'uploads/receipts/' + donation.receipt;
            link.innerText = donation.receipt;
            link.classList.remove('hidden');
            noReceipt.classList.add('hidden');
        } else {
            link.classList.add('hidden');
            noReceipt.classList.remove('hidden');
        }

        openModal('editDonationModal');
    }
</script>