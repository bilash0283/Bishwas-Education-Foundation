<?php

// ---------- ADD DONATION ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'add') {

    $donor_id        = $_POST['donor_id'] !== '' ? (int) $_POST['donor_id'] : null;
    $type            = trim($_POST['type']);
    $name            = trim($_POST['name']);
    $email           = trim($_POST['email']);
    $phone           = trim($_POST['phone']);
    $amount          = (float) $_POST['amount'];
    $donation_type   = trim($_POST['donation_type']);
    $fund            = trim($_POST['fund']);
    $payment_method  = trim($_POST['payment_method']);
    $transaction_id  = trim($_POST['transaction_id']);
    $payment_status  = trim($_POST['payment_status']);
    $donation_status = trim($_POST['donation_status']);
    $donation_date   = $_POST['donation_date'] !== '' ? $_POST['donation_date'] : date('Y-m-d');
    $admin_note      = trim($_POST['admin_note']);
    $receipt         = trim($_POST['receipt']);

    $sql = "INSERT INTO donations
            (donor_id, type, name, email, phone, amount, donation_type, fund,
             payment_method, transaction_id, payment_status, donation_status,
             donation_date, admin_note, receipt, created_at, updated_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())";

    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param(
        $stmt,
        "issssdsssssssss", // i=donor_id, s=text fields, d=amount (15 params total)
        $donor_id, $type, $name, $email, $phone, $amount, $donation_type, $fund,
        $payment_method, $transaction_id, $payment_status, $donation_status,
        $donation_date, $admin_note, $receipt
    );
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: donations_dashboard.php");
    exit;
}

// ---------- EDIT DONATION ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'edit') {

    $id              = (int) $_POST['id'];
    $donor_id        = $_POST['donor_id'] !== '' ? (int) $_POST['donor_id'] : null;
    $type            = trim($_POST['type']);
    $name            = trim($_POST['name']);
    $email           = trim($_POST['email']);
    $phone           = trim($_POST['phone']);
    $amount          = (float) $_POST['amount'];
    $donation_type   = trim($_POST['donation_type']);
    $fund            = trim($_POST['fund']);
    $payment_method  = trim($_POST['payment_method']);
    $transaction_id  = trim($_POST['transaction_id']);
    $payment_status  = trim($_POST['payment_status']);
    $donation_status = trim($_POST['donation_status']);
    $donation_date   = $_POST['donation_date'];
    $admin_note      = trim($_POST['admin_note']);
    $receipt         = trim($_POST['receipt']);

    $sql = "UPDATE donations SET
                donor_id = ?, type = ?, name = ?, email = ?, phone = ?, amount = ?,
                donation_type = ?, fund = ?, payment_method = ?, transaction_id = ?,
                payment_status = ?, donation_status = ?, donation_date = ?,
                admin_note = ?, receipt = ?, updated_at = NOW()
            WHERE id = ?";

    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param(
        $stmt,
        "issssdsssssssssi",
        $donor_id, $type, $name, $email, $phone, $amount, $donation_type, $fund,
        $payment_method, $transaction_id, $payment_status, $donation_status,
        $donation_date, $admin_note, $receipt, $id
    );
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: donations_dashboard.php");
    exit;
}

// ---------- DELETE DONATION ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_action']) && $_POST['form_action'] === 'delete') {
    $id = (int) $_POST['id'];

    $stmt = mysqli_prepare($db, "DELETE FROM donations WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    header("Location: donations_dashboard.php");
    exit;
}

/* =====================================================================
   2) FILTER LOGIC (SAVED IN SESSION)
   - Jokhon user "Apply Filter" button click korbe (filter_apply exists
     in $_GET) tokhon amra notun filter value gulo session e save korbo.
   - Jokhon user "Clear Filter" click korbe, amra session theke filter
     mucche felbo.
   - Onno shomoy (jemon shudhu page reload othoba pagination link click),
     age theke session e joto filter ache shegulai use hobe. Er fole
     filter kono somoy "nosto" hobe na jotokkhon na user nijei clear kore.
===================================================================== */

if (isset($_GET['clear_filter'])) {
    unset($_SESSION['donation_filter']);
} elseif (isset($_GET['filter_apply'])) {
    $_SESSION['donation_filter'] = [
        'search'          => trim($_GET['search'] ?? ''),
        'payment_status'  => trim($_GET['payment_status'] ?? ''),
        'donation_status' => trim($_GET['donation_status'] ?? ''),
        'fund'            => trim($_GET['fund'] ?? ''),
        'date_from'       => trim($_GET['date_from'] ?? ''),
        'date_to'         => trim($_GET['date_to'] ?? ''),
    ];
}

// Session e filter thakle seta niye ashi, na thakle shobgulo empty
$filter = $_SESSION['donation_filter'] ?? [
    'search'          => '',
    'payment_status'  => '',
    'donation_status' => '',
    'fund'            => '',
    'date_from'       => '',
    'date_to'         => '',
];

/* =====================================================================
   3) BUILD DYNAMIC WHERE CLAUSE (based on active filter)
===================================================================== */

$whereParts = [];
$params     = [];
$paramTypes = "";

if ($filter['search'] !== '') {
    $whereParts[]  = "(name LIKE ? OR email LIKE ? OR phone LIKE ? OR transaction_id LIKE ?)";
    $searchTerm    = "%" . $filter['search'] . "%";
    $params[]      = $searchTerm;
    $params[]      = $searchTerm;
    $params[]      = $searchTerm;
    $params[]      = $searchTerm;
    $paramTypes   .= "ssss";
}

if ($filter['payment_status'] !== '') {
    $whereParts[] = "payment_status = ?";
    $params[]     = $filter['payment_status'];
    $paramTypes  .= "s";
}

if ($filter['donation_status'] !== '') {
    $whereParts[] = "donation_status = ?";
    $params[]     = $filter['donation_status'];
    $paramTypes  .= "s";
}

if ($filter['fund'] !== '') {
    $whereParts[] = "fund = ?";
    $params[]     = $filter['fund'];
    $paramTypes  .= "s";
}

if ($filter['date_from'] !== '') {
    $whereParts[] = "donation_date >= ?";
    $params[]     = $filter['date_from'];
    $paramTypes  .= "s";
}

if ($filter['date_to'] !== '') {
    $whereParts[] = "donation_date <= ?";
    $params[]     = $filter['date_to'];
    $paramTypes  .= "s";
}

$whereSQL = "";
if (count($whereParts) > 0) {
    $whereSQL = "WHERE " . implode(" AND ", $whereParts);
}

/* =====================================================================
   4) PAGINATION SETUP
===================================================================== */

$perPage     = 10; // proti page e koyta row dekhabe
$currentPage = isset($_GET['page']) ? (int) $_GET['page'] : 1;
if ($currentPage < 1) {
    $currentPage = 1;
}
$offset = ($currentPage - 1) * $perPage;

// --- Total row count (filter shoho) ---
$countSql = "SELECT COUNT(*) AS total FROM donations $whereSQL";
$countStmt = mysqli_prepare($db, $countSql);
if (count($params) > 0) {
    mysqli_stmt_bind_param($countStmt, $paramTypes, ...$params);
}
mysqli_stmt_execute($countStmt);
$countResult = mysqli_stmt_get_result($countStmt);
$totalRows   = mysqli_fetch_assoc($countResult)['total'];
mysqli_stmt_close($countStmt);

$totalPages = (int) ceil($totalRows / $perPage);
if ($totalPages < 1) {
    $totalPages = 1;
}

/* =====================================================================
   5) FETCH DONATIONS (filter + pagination shoho)
===================================================================== */

$listSql = "SELECT id, donor_id, type, name, email, phone, amount, donation_type,
                   fund, payment_method, transaction_id, payment_status,
                   donation_status, donation_date, admin_note, receipt,
                   created_at, updated_at
            FROM donations
            $whereSQL
            ORDER BY id DESC
            LIMIT ? OFFSET ?";

$listStmt = mysqli_prepare($db, $listSql);

$listParamTypes = $paramTypes . "ii";
$listParams     = $params;
$listParams[]   = $perPage;
$listParams[]   = $offset;

mysqli_stmt_bind_param($listStmt, $listParamTypes, ...$listParams);
mysqli_stmt_execute($listStmt);
$result = mysqli_stmt_get_result($listStmt);

$donations = [];
while ($row = mysqli_fetch_assoc($result)) {
    $donations[] = $row;
}
mysqli_stmt_close($listStmt);

// Helper: safely print HTML (XSS protection)
function h($value)
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Donations Dashboard</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
</head>

<body class="bg-slate-50">

    <section class="page-content space-y-6 p-6 max-w-7xl mx-auto">

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
            <form method="GET" action="donations_dashboard.php" class="grid grid-cols-1 md:grid-cols-6 gap-3">

                <!-- Ei hidden field diye bujha hoy je "Apply Filter" button chapa hoyeche -->
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
                        <?php foreach (['pending', 'paid', 'failed', 'refunded'] as $ps): ?>
                            <option value="<?= h($ps) ?>" <?= $filter['payment_status'] === $ps ? 'selected' : '' ?>>
                                <?= h(ucfirst($ps)) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">Donation Status</label>
                    <select name="donation_status"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                        <option value="">All</option>
                        <?php foreach (['pending', 'approved', 'rejected', 'completed'] as $ds): ?>
                            <option value="<?= h($ds) ?>" <?= $filter['donation_status'] === $ds ? 'selected' : '' ?>>
                                <?= h(ucfirst($ds)) ?>
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
                    <a href="donations_dashboard.php?clear_filter=1"
                        class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50 flex items-center gap-2">
                        <i class="fa-solid fa-xmark"></i> Clear Filter
                    </a>
                    <span class="text-[11px] text-slate-400 ml-2">
                        Total records found: <b><?= (int) $totalRows ?></b>
                    </span>
                </div>
            </form>
        </div>

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
                                    <span
                                        class="inline-block px-2.5 py-1 rounded-md bg-amber-50 text-amber-700 font-bold text-[10px]">
                                        <?= h(ucfirst($d['payment_status'])) ?>
                                    </span>
                                    <span
                                        class="block px-2.5 py-1 rounded-md bg-sky-50 text-sky-700 font-bold text-[10px] w-fit">
                                        <?= h(ucfirst($d['donation_status'])) ?>
                                    </span>
                                </td>
                                <td class="p-4"><?= h($d['donation_date']) ?></td>
                                <td class="p-4 text-right space-x-1 whitespace-nowrap">
                                    <button
                                        onclick='openEditModal(<?= json_encode($d, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
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
                    // Pagination link e filter dubar pathanor dorkar nai,
                    // karon filter ta already session e save kora ache.
                    $prevPage = max(1, $currentPage - 1);
                    $nextPage = min($totalPages, $currentPage + 1);
                    ?>
                    <a href="?page=<?= $prevPage ?>"
                        class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 <?= $currentPage <= 1 ? 'pointer-events-none opacity-40' : '' ?>">
                        <i class="fa-solid fa-chevron-left"></i>
                    </a>

                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <a href="?page=<?= $p ?>"
                            class="px-3 py-1.5 rounded-lg border <?= $p == $currentPage ? 'bg-emerald-600 text-white border-emerald-600' : 'border-slate-200 hover:bg-slate-50' ?>">
                            <?= $p ?>
                        </a>
                    <?php endfor; ?>

                    <a href="?page=<?= $nextPage ?>"
                        class="px-3 py-1.5 rounded-lg border border-slate-200 hover:bg-slate-50 <?= $currentPage >= $totalPages ? 'pointer-events-none opacity-40' : '' ?>">
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- ===================== ADD DONATION MODAL ===================== -->
    <div id="addDonationModal"
        class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center p-4 z-50">
        <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden border border-slate-100 max-h-[90vh] overflow-y-auto">
            <div class="p-5 bg-slate-900 text-white flex justify-between items-center sticky top-0">
                <h3 class="font-bold text-sm flex items-center gap-2">
                    <i class="fa-solid fa-square-plus text-emerald-400"></i>
                    <span>Add Donation</span>
                </h3>
                <button onclick="closeModal('addDonationModal')" class="text-slate-400 hover:text-white">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <form method="POST" action="donations_dashboard.php" class="p-6 space-y-4">
                <input type="hidden" name="form_action" value="add">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Donor Name</label>
                        <input type="text" name="name" required
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Donor Type</label>
                        <input type="text" name="type" placeholder="Individual / Organization"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-emerald-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Email</label>
                        <input type="email" name="email"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Phone</label>
                        <input type="text" name="phone" placeholder="+8801XXXXXXXXX"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Amount</label>
                        <input type="number" step="0.01" name="amount" required
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Donation Type</label>
                        <input type="text" name="donation_type" placeholder="Zakat / Sadaqah / General"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Fund</label>
                        <input type="text" name="fund"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Payment Method</label>
                        <input type="text" name="payment_method" placeholder="bKash / Bank / Cash"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Transaction ID</label>
                        <input type="text" name="transaction_id"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Payment Status</label>
                        <select name="payment_status"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                            <option value="failed">Failed</option>
                            <option value="refunded">Refunded</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Donation Status</label>
                        <select name="donation_status"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Donation Date</label>
                        <input type="date" name="donation_date" value="<?= date('Y-m-d') ?>"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Donor ID (optional)</label>
                        <input type="number" name="donor_id"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Receipt (file path / URL)</label>
                        <input type="text" name="receipt"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
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
        <div class="bg-white w-full max-w-2xl rounded-2xl shadow-2xl overflow-hidden border border-slate-100 max-h-[90vh] overflow-y-auto">
            <div class="p-5 bg-slate-900 text-white flex justify-between items-center sticky top-0">
                <h3 class="font-bold text-sm flex items-center gap-2">
                    <i class="fa-solid fa-pen text-emerald-400"></i>
                    <span>Edit Donation</span>
                </h3>
                <button onclick="closeModal('editDonationModal')" class="text-slate-400 hover:text-white">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>
            <form method="POST" action="donations_dashboard.php" class="p-6 space-y-4">
                <input type="hidden" name="form_action" value="edit">
                <input type="hidden" name="id" id="edit_id">

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Donor Name</label>
                        <input type="text" name="name" id="edit_name" required
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Donor Type</label>
                        <input type="text" name="type" id="edit_type"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-emerald-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Email</label>
                        <input type="email" name="email" id="edit_email"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Phone</label>
                        <input type="text" name="phone" id="edit_phone"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Amount</label>
                        <input type="number" step="0.01" name="amount" id="edit_amount" required
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Donation Type</label>
                        <input type="text" name="donation_type" id="edit_donation_type"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Fund</label>
                        <input type="text" name="fund" id="edit_fund"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Payment Method</label>
                        <input type="text" name="payment_method" id="edit_payment_method"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Transaction ID</label>
                        <input type="text" name="transaction_id" id="edit_transaction_id"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Payment Status</label>
                        <select name="payment_status" id="edit_payment_status"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                            <option value="pending">Pending</option>
                            <option value="paid">Paid</option>
                            <option value="failed">Failed</option>
                            <option value="refunded">Refunded</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Donation Status</label>
                        <select name="donation_status" id="edit_donation_status"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Donation Date</label>
                        <input type="date" name="donation_date" id="edit_donation_date"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Donor ID (optional)</label>
                        <input type="number" name="donor_id" id="edit_donor_id"
                            class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Receipt (file path / URL)</label>
                        <input type="text" name="receipt" id="edit_receipt"
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
            <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center text-xl mx-auto mb-4">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h3 class="font-bold text-slate-800 text-base">Confirm Deletion</h3>
            <p class="text-xs text-slate-500 mt-1">
                Are you sure you want to remove
                <span id="deleteDonationTargetName" class="font-bold text-slate-700"></span>?
                This action cannot be undone.
            </p>
            <form method="POST" action="donations_dashboard.php" class="flex justify-center gap-3 mt-6">
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

        // Edit modal open + fill with the clicked row's data
        function openEditModal(donation) {
            document.getElementById('edit_id').value = donation.id;
            document.getElementById('edit_name').value = donation.name || '';
            document.getElementById('edit_type').value = donation.type || '';
            document.getElementById('edit_email').value = donation.email || '';
            document.getElementById('edit_phone').value = donation.phone || '';
            document.getElementById('edit_amount').value = donation.amount || '';
            document.getElementById('edit_donation_type').value = donation.donation_type || '';
            document.getElementById('edit_fund').value = donation.fund || '';
            document.getElementById('edit_payment_method').value = donation.payment_method || '';
            document.getElementById('edit_transaction_id').value = donation.transaction_id || '';
            document.getElementById('edit_payment_status').value = donation.payment_status || 'pending';
            document.getElementById('edit_donation_status').value = donation.donation_status || 'pending';
            document.getElementById('edit_donation_date').value = donation.donation_date || '';
            document.getElementById('edit_donor_id').value = donation.donor_id || '';
            document.getElementById('edit_receipt').value = donation.receipt || '';
            document.getElementById('edit_admin_note').value = donation.admin_note || '';
            openModal('editDonationModal');
        }

        // Delete modal open + set target id/name
        function openDeleteModal(id, name) {
            document.getElementById('delete_id').value = id;
            document.getElementById('deleteDonationTargetName').innerText = name;
            openModal('deleteDonationModal');
        }
    </script>

</body>

</html>