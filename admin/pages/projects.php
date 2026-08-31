<?php
ob_start();

include '../database/db.php';

// ---------- Helper: log DB errors (guns for debugging) ----------
function db_log_error($db, $label) {
    error_log("[Activities Page] $label mysqli error: " . mysqli_error($db));
}

// ১. Activity Add / Update / Delete Processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {

    // ----------------------------------------------------
    // Activity Add/Update প্রসেসিং
    // ----------------------------------------------------
    if ($_POST['action_type'] === 'save_activity') {
        $id = isset($_POST['activity_id']) ? (int)$_POST['activity_id'] : 0;
        $title = mysqli_real_escape_string($db, trim($_POST['title'] ?? ''));
        $badge_text = mysqli_real_escape_string($db, trim($_POST['badge_text'] ?? ''));
        $status = mysqli_real_escape_string($db, trim($_POST['status'] ?? 'active'));
        $description = mysqli_real_escape_string($db, trim($_POST['description'] ?? ''));

        $image_name = '';

        // ইমেজ আপলোড প্রসেসিং
        if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'public/project_img/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
            $image_name = $upload_dir . time() . '_' . uniqid() . '.' . $file_ext;

            if (!move_uploaded_file($_FILES['image']['tmp_name'], $image_name)) {
                error_log("[Activities Page] move_uploaded_file failed for: " . $_FILES['image']['name']);
                $image_name = '';
            }

            // আপডেট করার সময় পুরনো ছবি সার্ভার থেকে ডিলিট করা
            if ($id > 0 && !empty($image_name)) {
                $stmt = mysqli_prepare($db, "SELECT image FROM activities WHERE id = ?");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "i", $id);
                    mysqli_stmt_execute($stmt);
                    $res = mysqli_stmt_get_result($stmt);
                    if ($res && ($old_row = mysqli_fetch_assoc($res))) {
                        if (!empty($old_row['image']) && file_exists($old_row['image'])) {
                            @unlink($old_row['image']);
                        }
                    }
                    mysqli_stmt_close($stmt);
                } else {
                    db_log_error($db, "old image select prepare");
                }
            }
        }

        if ($id > 0) {
            // Update Query
            if (!empty($image_name)) {
                $stmt = mysqli_prepare($db, "UPDATE activities SET title=?, badge_text=?, image=?, status=?, description=? WHERE id=?");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "sssssi", $title, $badge_text, $image_name, $status, $description, $id);
                }
            } else {
                $stmt = mysqli_prepare($db, "UPDATE activities SET title=?, badge_text=?, status=?, description=? WHERE id=?");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "ssssi", $title, $badge_text, $status, $description, $id);
                }
            }
            if ($stmt) {
                if (!mysqli_stmt_execute($stmt)) {
                    db_log_error($db, "update activity execute");
                }
                mysqli_stmt_close($stmt);
            } else {
                db_log_error($db, "update activity prepare");
            }
        } else {
            // Insert Query
            $stmt = mysqli_prepare($db, "INSERT INTO activities (title, badge_text, image, status, description) VALUES (?, ?, ?, ?, ?)");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "sssss", $title, $badge_text, $image_name, $status, $description);
                if (!mysqli_stmt_execute($stmt)) {
                    db_log_error($db, "insert activity execute");
                }
                mysqli_stmt_close($stmt);
            } else {
                db_log_error($db, "insert activity prepare");
            }
        }

        if (ob_get_length()) ob_end_clean();
        header("Location: dashboard.php?page=projects");
        exit;
    }

    // ----------------------------------------------------
    // Activity Delete প্রসেসিং (FIXED)
    // ----------------------------------------------------
    if ($_POST['action_type'] === 'delete_activity') {
        $delete_id = isset($_POST['delete_id']) ? intval($_POST['delete_id']) : 0;

        if ($delete_id > 0) {
            // ১. ছবি খুঁজে বের করে ডিলিট করা
            $stmt = mysqli_prepare($db, "SELECT image FROM activities WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $delete_id);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);

                if ($res && ($row = mysqli_fetch_assoc($res))) {
                    $img_path = $row['image'];
                    if (!empty($img_path) && file_exists($img_path)) {
                        @unlink($img_path);
                    }
                }
                mysqli_stmt_close($stmt);
            } else {
                db_log_error($db, "delete image select prepare");
            }

            // ২. রেকর্ড ডিলিট করা
            $del_stmt = mysqli_prepare($db, "DELETE FROM activities WHERE id = ?");
            if ($del_stmt) {
                mysqli_stmt_bind_param($del_stmt, "i", $delete_id);
                if (!mysqli_stmt_execute($del_stmt)) {
                    db_log_error($db, "delete activity execute");
                }
                mysqli_stmt_close($del_stmt);
            } else {
                db_log_error($db, "delete activity prepare");
            }
        } else {
            error_log("[Activities Page] delete_activity called with invalid delete_id: " . ($_POST['delete_id'] ?? 'not set'));
        }

        if (ob_get_length()) ob_end_clean();
        header("Location: dashboard.php?page=projects");
        exit;
    }
}

// ২. এনালিটিক্স কাউন্ট কুয়েরি
$total_count  = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as total FROM activities"))['total'] ?? 0;
$active_count = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as active FROM activities WHERE status='active'"))['active'] ?? 0;
$draft_count  = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as draft FROM activities WHERE status='draft'"))['draft'] ?? 0;

// ৩. সার্চ এবং পেজিনেশন সেটআপ
$search = isset($_GET['search']) ? mysqli_real_escape_string($db, trim($_GET['search'])) : '';
$where_clause = $search ? "WHERE title LIKE '%$search%' OR description LIKE '%$search%' OR badge_text LIKE '%$search%'" : "";

$limit = 20;
$page = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;

$filtered_total_q = mysqli_query($db, "SELECT COUNT(*) as count FROM activities $where_clause");
$filtered_total = mysqli_fetch_assoc($filtered_total_q)['count'] ?? 0;

$total_pages = ceil($filtered_total / $limit);
if ($total_pages > 0 && $page > $total_pages) $page = $total_pages;

$offset = ($page - 1) * $limit;

// ৪. মূল ডাটা কুয়েরি
$activities_result = mysqli_query($db, "SELECT * FROM activities $where_clause ORDER BY id DESC LIMIT $limit OFFSET $offset");
?>

<div class="p-4 sm:p-6 lg:p-8">
    <!-- Page Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Running Activities</h2>
            <p class="text-sm text-slate-500">Manage section titles, subtitles, and individual running programs for bishwas.org</p>
        </div>
        <div>
            <button type="button" onclick="openActivityModal()"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-4 py-2.5 rounded-lg shadow transition-all">
                <i class="fa-solid fa-plus"></i>
                Add New Activity
            </button>
        </div>
    </div>

    <!-- Analytics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Total Activities</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1"><?= sprintf("%02d", $total_count) ?></h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-list-check"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Active Programs</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1"><?= sprintf("%02d", $active_count) ?></h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-circle-play"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Draft Items</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1"><?= sprintf("%02d", $draft_count) ?></h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-file-pen"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Featured Category</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">নিয়মিত</h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-star"></i>
            </div>
        </div>
    </div>

    <!-- Table Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-8">
        <!-- Search Controls -->
        <div class="p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <h3 class="font-bold text-slate-800">All Program Activities</h3>
                <span class="text-xs bg-emerald-100 text-emerald-800 font-semibold px-2.5 py-1 rounded-full">Active Section</span>
            </div>

            <form method="GET" action="" class="flex items-center gap-3">
                <input type="hidden" name="page" value="projects">
                <div class="relative w-full sm:w-64">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400 text-xs">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search activity..."
                        class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg pl-8 pr-3 py-2 text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </form>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5">Image & Title</th>
                        <th class="px-6 py-3.5">Badge / Tag</th>
                        <th class="px-6 py-3.5">Short Description</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">

                    <?php if ($activities_result && mysqli_num_rows($activities_result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($activities_result)): ?>
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <img src="<?= htmlspecialchars($row['image']) ?>" alt="<?= htmlspecialchars($row['title']) ?>" class="w-12 h-12 rounded-lg object-cover border border-slate-200 shrink-0">
                                        <span class="font-semibold text-slate-800 line-clamp-1"><?= htmlspecialchars($row['title']) ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="bg-amber-50 text-amber-700 border border-amber-200 text-xs px-2.5 py-1 rounded-md font-medium">
                                        <i class="fa-solid fa-pushpin text-[10px] mr-1"></i><?= htmlspecialchars($row['badge_text']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <p class="text-xs text-slate-500 line-clamp-2 max-w-xs"><?= htmlspecialchars($row['description']) ?></p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($row['status'] === 'active'): ?>
                                        <span class="text-emerald-600 bg-emerald-50 border border-emerald-200 text-xs font-semibold px-2.5 py-1 rounded-full">Active</span>
                                    <?php else: ?>
                                        <span class="text-amber-600 bg-amber-50 border border-amber-200 text-xs font-semibold px-2.5 py-1 rounded-full">Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                                    <button type="button"
                                        class="edit-btn text-slate-400 hover:text-emerald-600 p-1.5 transition-colors"
                                        title="Edit"
                                        data-activity='<?= json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>'>
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button type="button"
                                        class="delete-btn text-slate-400 hover:text-rose-600 p-1.5 transition-colors"
                                        title="Delete"
                                        data-id="<?= (int)$row['id'] ?>"
                                        data-title="<?= htmlspecialchars($row['title'], ENT_QUOTES) ?>">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-400">কোনো কার্যক্রম পাওয়া যায়নি।</td>
                        </tr>
                    <?php endif; ?>

                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div class="p-4 border-t border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
            <div>
                Showing <b><?= $filtered_total > 0 ? min($offset + 1, $filtered_total) : 0 ?></b> to <b><?= min($offset + $limit, $filtered_total) ?></b> of <b><?= $filtered_total ?></b> entries
            </div>

            <?php if ($total_pages > 1): ?>
                <div class="flex items-center gap-1">
                    <?php if ($page > 1): ?>
                        <a href="?page=projects&p=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                           class="px-2.5 py-1 rounded border border-slate-200 bg-white hover:bg-slate-100 text-slate-600 transition-colors">
                            <i class="fa-solid fa-chevron-left text-[10px]"></i>
                        </a>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=projects&p=<?= $i ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                           class="px-3 py-1 rounded border <?= $i === $page ? 'bg-emerald-600 text-white border-emerald-600 font-bold' : 'bg-white text-slate-600 border-slate-200 hover:bg-slate-100' ?> transition-colors">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=projects&p=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>"
                           class="px-2.5 py-1 rounded border border-slate-200 bg-white hover:bg-slate-100 text-slate-600 transition-colors">
                            <i class="fa-solid fa-chevron-right text-[10px]"></i>
                        </a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Add / Edit Modal -->
<div id="activityModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xl w-full max-w-2xl overflow-hidden">
        <div class="p-5 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
            <h3 id="modalTitle" class="font-bold text-slate-800 text-lg flex items-center gap-2">
                <i class="fa-solid fa-square-plus text-emerald-600"></i>
                Add New Activity
            </h3>
            <button type="button" onclick="closeActivityModal()" class="text-slate-400 hover:text-slate-600 p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form action="" method="POST" enctype="multipart/form-data" class="p-6 space-y-5">
            <input type="hidden" name="action_type" value="save_activity">
            <input type="hidden" name="activity_id" id="activity_id" value="">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Activity Title</label>
                    <input type="text" name="title" id="form_title" required placeholder="যেমন: বৃক্ষরোপণ"
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Badge Text</label>
                    <input type="text" name="badge_text" id="form_badge_text" value="নিয়মিত কার্যক্রম"
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Image Upload</label>
                    <input type="file" name="image" id="form_image"
                        class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-slate-200 rounded-lg bg-slate-50 cursor-pointer">
                    <small id="image_help" class="text-[11px] text-slate-400 mt-1 block hidden">আপডেট করতে নতুন ছবি দিন, অন্যথায় খালি রাখুন।</small>
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
                <textarea name="description" id="form_description" rows="3" required placeholder="কার্যক্রমের বিস্তারিত বিবরণ দিন..."
                    class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <button type="button" onclick="closeActivityModal()"
                    class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                    Cancel
                </button>
                <button type="submit"
                    class="px-5 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow transition-all flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save Activity
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
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

<script>
    function openActivityModal() {
        document.getElementById('activity_id').value = '';
        document.getElementById('form_title').value = '';
        document.getElementById('form_badge_text').value = 'নিয়মিত কার্যক্রম';
        document.getElementById('form_status').value = 'active';
        document.getElementById('form_description').value = '';
        document.getElementById('form_image').required = true;
        document.getElementById('image_help').classList.add('hidden');
        document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-square-plus text-emerald-600"></i> Add New Activity';
        document.getElementById('activityModal').classList.remove('hidden');
    }

    function editActivity(data) {
        document.getElementById('activity_id').value = data.id;
        document.getElementById('form_title').value = data.title;
        document.getElementById('form_badge_text').value = data.badge_text;
        document.getElementById('form_status').value = data.status;
        document.getElementById('form_description').value = data.description;
        document.getElementById('form_image').required = false;
        document.getElementById('image_help').classList.remove('hidden');
        document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square text-emerald-600"></i> Edit Activity';
        document.getElementById('activityModal').classList.remove('hidden');
    }

    function closeActivityModal() {
        document.getElementById('activityModal').classList.add('hidden');
    }

    function openDeleteModal(id, title) {
        document.getElementById('delete_id_input').value = id;
        document.getElementById('deleteItemTitle').textContent = title;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }

    // ---- Bind Edit buttons (data-attribute based, safe from quote-breaking) ----
    document.querySelectorAll('.edit-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            try {
                var data = JSON.parse(btn.getAttribute('data-activity'));
                editActivity(data);
            } catch (e) {
                console.error('Failed to parse activity data', e);
            }
        });
    });

    // ---- Bind Delete buttons (data-attribute based, safe from quote-breaking) ----
    document.querySelectorAll('.delete-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var id = btn.getAttribute('data-id');
            var title = btn.getAttribute('data-title');
            openDeleteModal(id, title);
        });
    });
</script>



