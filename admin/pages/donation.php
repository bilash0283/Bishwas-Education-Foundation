<?php

$DEBUG_MODE = false;
if ($DEBUG_MODE) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
}

// ---------- ১. DB কানেকশন ----------
// dashboard.php আগেই db.php include করে থাকলে $db তৈরি আছে, নাহলে কয়েকটা path-এ খুঁজবে
if (!isset($db) || !($db instanceof mysqli)) {
    $db_candidates = [
        '../database/db.php',
        __DIR__ . '/../database/db.php',
        __DIR__ . '/../../database/db.php',
    ];
    foreach ($db_candidates as $db_file) {
        if (file_exists($db_file)) {
            require_once $db_file;
            break;
        }
    }
}
if (!isset($db) || !($db instanceof mysqli)) {
    echo '<div style="margin:20px;padding:12px;border:1px solid #fecdd3;background:#fff1f2;color:#be123c;border-radius:8px;font-size:14px">'
       . 'Database connection পাওয়া যায়নি। db.php এর path এবং $db ভ্যারিয়েবলের নাম চেক করুন।</div>';
    return;
}

// PHP ভার্সনভেদে mysqli আচরণ এক রাখতে (exception বন্ধ, আমরা নিজে error চেক করব)
mysqli_report(MYSQLI_REPORT_OFF);
@$db->set_charset('utf8mb4');

// ---------- Helper ----------
if (!function_exists('db_log_error')) {
    function db_log_error($db, $context = '') {
        error_log('[Donation Page] ' . $context . ' : ' . ($db instanceof mysqli ? $db->error : 'unknown'));
    }
}

if (!function_exists('safe_redirect')) {
    // header() আগে আউটপুট হয়ে গেলেও redirect কাজ করবে
    function safe_redirect($url) {
        if (!headers_sent()) {
            header('Location: ' . $url);
        } else {
            echo '<script>window.location.href=' . json_encode($url) . ';</script>';
            echo '<noscript><meta http-equiv="refresh" content="0;url=' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '"></noscript>';
        }
        exit;
    }
}

// কোয়েরি ফেইল করলে view যাতে না ভাঙে, তাই খালি result
if (!class_exists('DonationEmptyResult')) {
    class DonationEmptyResult {
        public $num_rows = 0;
        public function fetch_assoc() { return null; }
    }
}

$redirect_url = 'dashboard.php?page=donation';
$page_error   = '';

// ---------- ২. Insert / Update ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_sector'])) {
    $id          = (int)($_POST['sector_id'] ?? 0);
    $title       = trim($_POST['title'] ?? '');
    $icon_class  = trim($_POST['icon_class'] ?? '');
    $button_text = trim($_POST['button_text'] ?? '');
    $button_link = trim($_POST['button_link'] ?? '');
    $status      = trim($_POST['status'] ?? 'active');
    $description = trim($_POST['description'] ?? '');

    if (!in_array($status, ['active', 'draft'], true)) {
        $status = 'draft';
    }

    if ($title !== '') {
        if ($id > 0) {
            $stmt = $db->prepare("UPDATE donation_sectors SET title=?, icon_class=?, button_text=?, button_link=?, status=?, description=? WHERE id=?");
            if ($stmt) {
                $stmt->bind_param("ssssssi", $title, $icon_class, $button_text, $button_link, $status, $description, $id);
            }
        } else {
            $stmt = $db->prepare("INSERT INTO donation_sectors (title, icon_class, button_text, button_link, status, description) VALUES (?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                $stmt->bind_param("ssssss", $title, $icon_class, $button_text, $button_link, $status, $description);
            }
        }

        if ($stmt) {
            if (!$stmt->execute()) {
                db_log_error($db, 'save execute');
            }
            $stmt->close();
        } else {
            db_log_error($db, 'save prepare');
        }
    }

    safe_redirect($redirect_url);
}

// ---------- ৩. Delete ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action_type'] ?? '') === 'delete_activity') {
    $delete_id = (int)($_POST['delete_id'] ?? 0);

    if ($delete_id > 0) {
        $del_stmt = $db->prepare("DELETE FROM donation_sectors WHERE id = ?");
        if ($del_stmt) {
            $del_stmt->bind_param("i", $delete_id);
            if (!$del_stmt->execute()) {
                db_log_error($db, 'delete execute');
            }
            $del_stmt->close();
        } else {
            db_log_error($db, 'delete prepare');
        }
    }

    safe_redirect($redirect_url);
}

// ---------- ৪. Search + Pagination ----------
// ?page=donation হলো router, তাই পেজ নম্বর আলাদা "p" প্যারামিটারে (view-র লিংকও p ব্যবহার করে)
$search = trim($_GET['search'] ?? '');
$page   = (int)($_GET['p'] ?? 1);
if ($page < 1) {
    $page = 1;
}

$limit  = 30;
$offset = ($page - 1) * $limit;

$total_rows          = 0;
$total_sectors_count = 0;
$active_funds_count  = 0;
$sectors             = new DonationEmptyResult();

// get_result() ব্যবহার করা হয়নি (live hosting-এ mysqlnd না থাকলে ওটাই পেজ ফাঁকা করে দেয়)
// সার্চ টেক্সট escape করা হয়েছে, আর limit/offset integer
$where = '';
if ($search !== '') {
    $safe_search = $db->real_escape_string(addcslashes($search, '%_\\'));
    $where = "WHERE title LIKE '%{$safe_search}%' OR description LIKE '%{$safe_search}%'";
}

$res = $db->query("SELECT COUNT(*) AS count FROM donation_sectors {$where}");
if ($res) {
    $row = $res->fetch_assoc();
    $total_rows = (int)($row['count'] ?? 0);
    $res->free();
} else {
    $page_error = 'ডাটা লোড করা যায়নি: ' . $db->error;
    db_log_error($db, 'count query');
}

$data_res = $db->query("SELECT * FROM donation_sectors {$where} ORDER BY id DESC LIMIT {$offset}, {$limit}");
if ($data_res) {
    $sectors = $data_res;
} else {
    $page_error = 'ডাটা লোড করা যায়নি: ' . $db->error;
    db_log_error($db, 'data query');
}

$total_pages = (int)ceil($total_rows / $limit);

$res = $db->query("SELECT COUNT(*) AS count FROM donation_sectors");
if ($res) {
    $row = $res->fetch_assoc();
    $total_sectors_count = (int)($row['count'] ?? 0);
    $res->free();
}

$res = $db->query("SELECT COUNT(*) AS count FROM donation_sectors WHERE status='active'");
if ($res) {
    $row = $res->fetch_assoc();
    $active_funds_count = (int)($row['count'] ?? 0);
    $res->free();
}

// error হলে blank না হয়ে পেজের উপরে মেসেজ দেখাবে
if ($page_error !== '') {
    $shown = $DEBUG_MODE ? $page_error : 'ডাটা লোড করতে সমস্যা হয়েছে। ($DEBUG_MODE = true করে কারণ দেখুন)';
    echo '<div style="margin:20px;padding:12px;border:1px solid #fecdd3;background:#fff1f2;color:#be123c;border-radius:8px;font-size:14px">'
       . htmlspecialchars($shown, ENT_QUOTES, 'UTF-8') . '</div>';
}

?>

<div class="p-4 sm:p-6 lg:p-8">
    <!-- Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Donation Sectors</h2>
            <p class="text-sm text-slate-500">Manage section headers and donation categories for bishwas.org</p>
        </div>
        <div>
            <button onclick="openSectorModal()"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-4 py-2.5 rounded-lg shadow transition-all">
                <i class="fa-solid fa-plus"></i>
                Add New Sector
            </button>
        </div>
    </div>

    <!-- Analytics / Counter Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Total Sectors</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1"><?= sprintf("%02d", $total_sectors_count); ?></h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-hand-holding-dollar"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Active Funds</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1"><?= sprintf("%02d", $active_funds_count); ?></h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Primary CTA</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">জরুরি ত্রাণ</h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-kit-medical"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Status</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">Live</h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-globe"></i>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-8">
        <!-- Table Header Controls -->
        <div class="p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <h3 class="font-bold text-slate-800">All Donation Sectors</h3>
                <span class="text-xs bg-emerald-100 text-emerald-800 font-semibold px-2.5 py-1 rounded-full">Active Section</span>
            </div>
            
            <form method="GET" action="dashboard.php" class="flex items-center gap-3">
                <input type="hidden" name="page" value="donation">
                <div class="relative w-full sm:w-64">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400 text-xs">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" name="search" value="<?= htmlspecialchars($search); ?>" placeholder="Search sector..." 
                        class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg pl-8 pr-3 py-2 text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </form>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5">Icon & Title</th>
                        <th class="px-6 py-3.5">Description</th>
                        <!-- <th class="px-6 py-3.5">Button Text & Link</th> -->
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if ($sectors->num_rows > 0): ?>
                        <?php while ($sector = $sectors->fetch_assoc()): ?>
                        <tr class="hover:bg-slate-50/50">
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center text-lg shrink-0">
                                        <i class="<?= htmlspecialchars($sector['icon_class']); ?>"></i>
                                    </div>
                                    <span class="font-semibold text-slate-800 line-clamp-1"><?= htmlspecialchars($sector['title']); ?></span>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-xs text-slate-500 line-clamp-2 max-w-xs"><?= htmlspecialchars($sector['description']); ?></p>
                            </td>
                            <!-- <td class="px-6 py-4 whitespace-nowrap">
                                <span class="bg-slate-100 text-slate-700 text-xs font-medium px-2.5 py-1 rounded border border-slate-200">
                                    <?= htmlspecialchars($sector['button_text']); ?> <span class="text-slate-400">(<?= htmlspecialchars($sector['button_link']); ?>)</span>
                                </span>
                            </td> -->
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($sector['status'] === 'active'): ?>
                                    <span class="text-emerald-600 bg-emerald-50 border border-emerald-200 text-xs font-semibold px-2.5 py-1 rounded-full">Active</span>
                                <?php else: ?>
                                    <span class="text-amber-600 bg-amber-50 border border-amber-200 text-xs font-semibold px-2.5 py-1 rounded-full">Draft</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                                <button onclick='editSector(<?= json_encode($sector, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>)' class="text-slate-400 hover:text-emerald-600 p-1.5 transition-colors" title="Edit">
                                    <i class="fa-solid fa-pen"></i>
                                </button>
                                <!-- Modal Trigger Button -->
                                <!-- <button type="button" 
                                        onclick="openDeleteModal(<?= $sector['id']; ?>, '<?= htmlspecialchars($sector['title'], ENT_QUOTES); ?>')" 
                                        class="text-slate-400 hover:text-rose-600 p-1.5 transition-colors inline-block" 
                                        title="Delete">
                                    <i class="fa-solid fa-trash"></i>
                                </button> -->

                                <button type="button"
                                    class="delete-btn text-slate-400 hover:text-rose-600 p-1.5 transition-colors"
                                    title="Delete"
                                    data-id="<?= (int)$sector['id'] ?>"
                                    data-title="<?= htmlspecialchars($sector['title'], ENT_QUOTES) ?>">
                                    <i class="fa-solid fa-trash"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-400 text-sm">No sectors found.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Table Footer Pagination -->
        <div class="p-4 border-t border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
            <span>Showing <?= $total_rows > 0 ? $offset + 1 : 0; ?> to <?= min($offset + $limit, $total_rows); ?> of <?= $total_rows; ?> entries</span>
            
            <?php if ($total_pages > 1): ?>
            <div class="flex items-center gap-1">
                <a href="?page=donation&p=<?= max(1, $page - 1); ?>&search=<?= urlencode($search); ?>" 
                   class="px-3 py-1.5 rounded border border-slate-200 bg-white hover:bg-slate-100 text-slate-600 <?= ($page <= 1) ? 'pointer-events-none opacity-50' : ''; ?>">
                   Previous
                </a>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=donation&p=<?= $i; ?>&search=<?= urlencode($search); ?>" 
                       class="px-3 py-1.5 rounded border <?= ($i == $page) ? 'border-emerald-500 bg-emerald-600 text-white font-semibold' : 'border-slate-200 bg-white hover:bg-slate-100 text-slate-600'; ?>">
                        <?= $i; ?>
                    </a>
                <?php endfor; ?>

                <a href="?page=donation&p=<?= min($total_pages, $page + 1); ?>&search=<?= urlencode($search); ?>" 
                   class="px-3 py-1.5 rounded border border-slate-200 bg-white hover:bg-slate-100 text-slate-600 <?= ($page >= $total_pages) ? 'pointer-events-none opacity-50' : ''; ?>">
                   Next
                </a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ================= ADD / EDIT MODAL FORM ================= -->
<div id="sectorModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xl w-full max-w-2xl overflow-hidden">
        <div class="p-5 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
            <h3 id="modalTitle" class="font-bold text-slate-800 text-lg flex items-center gap-2">
                <i class="fa-solid fa-square-plus text-emerald-600"></i>
                Add New Donation Sector
            </h3>
            <button type="button" onclick="closeSectorModal()" class="text-slate-400 hover:text-slate-600 p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="" method="POST" class="p-6 space-y-5">
            <input type="hidden" name="save_sector" value="1">
            <input type="hidden" name="sector_id" id="sector_id" value="">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Sector Title</label>
                    <input type="text" name="title" id="form_title" required placeholder="যেমন: জরুরি ত্রাণ তহবিল" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">FontAwesome Icon Class</label>
                    <input type="text" name="icon_class" id="form_icon" required placeholder="যেমন: fa-solid fa-kit-medical" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Button Label</label>
                    <input type="text" name="button_text" id="form_btn_text" required placeholder="যেমন: অনুদানে শরীক হোন" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Button Link</label>
                    <input type="text" name="button_link" id="form_btn_link"  placeholder="/donate-relief or N/A" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Status</label>
                    <select name="status" id="form_status" class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="active">Active (Publish)</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Short Description</label>
                <textarea name="description" id="form_description" rows="3" required placeholder="খাতের সংক্ষিপ্ত বিবরণ লিখুন..." 
                    class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <button type="button" onclick="closeSectorModal()" 
                    class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                    class="px-5 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow transition-all flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save Sector
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ================= DELETE CONFIRMATION MODAL ================= -->
<div id="deleteModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xl w-full max-w-md overflow-hidden p-6 text-center">
        <div class="w-12 h-12 rounded-full bg-rose-100 text-rose-600 flex items-center justify-center text-xl mx-auto mb-4">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h3 class="text-lg font-bold text-slate-800 mb-2">আপনি কি নিশ্চিত?</h3>
        <p class="text-xs text-slate-500 mb-6"><b id="deleteItemTitle" class="text-slate-700"></b> আইটেমটি স্থায়ীভাবে ডিলিট হয়ে যাবে!</p>

        <form action="" method="POST" class="flex items-center justify-center gap-3">
            <input type="hidden" name="action_type" value="delete_activity">
            <input type="hidden" name="delete_id" id="delete_id_input" value="">

            <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 text-xs font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                বাতিল করুন
            </button>
            <button type="submit" class="px-4 py-2 text-xs font-medium text-white bg-rose-600 hover:bg-rose-700 rounded-lg shadow transition-colors">
                হ্যাঁ, ডিলিট করুন
            </button>
        </form>
    </div>
</div>

<!-- ================= JAVASCRIPT HANDLERS ================= -->
<script>
       document.querySelectorAll('.delete-btn').forEach(button => {
        button.addEventListener('click', function() {
            // Data Attributes থেকে id এবং title রিড করা
            const id = this.getAttribute('data-id');
            const title = this.getAttribute('data-title');

            // Modals-এর hidden input এবং title text সেট করা
            document.getElementById('delete_id_input').value = id;
            document.getElementById('deleteItemTitle').textContent = title;

            // Modal ওপেন করা (hidden class রিমুভ করে)
            document.getElementById('deleteModal').classList.remove('hidden');
        });
    });

    // Modal বন্ধ করার ফাংশন
    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }

    // Add / Edit Modal Functions
    function openSectorModal() {
        document.getElementById('sector_id').value = '';
        document.getElementById('form_title').value = '';
        document.getElementById('form_icon').value = '';
        document.getElementById('form_btn_text').value = '';
        document.getElementById('form_btn_link').value = '';
        document.getElementById('form_status').value = 'active';
        document.getElementById('form_description').value = '';
        document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-square-plus text-emerald-600"></i> Add New Donation Sector';
        document.getElementById('sectorModal').classList.remove('hidden');
    }

    function editSector(sector) {
        document.getElementById('sector_id').value = sector.id;
        document.getElementById('form_title').value = sector.title;
        document.getElementById('form_icon').value = sector.icon_class;
        document.getElementById('form_btn_text').value = sector.button_text;
        document.getElementById('form_btn_link').value = sector.button_link;
        document.getElementById('form_status').value = sector.status;
        document.getElementById('form_description').value = sector.description;
        document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square text-emerald-600"></i> Edit Donation Sector';
        document.getElementById('sectorModal').classList.remove('hidden');
    }

    function closeSectorModal() {
        document.getElementById('sectorModal').classList.add('hidden');
    }

</script>  