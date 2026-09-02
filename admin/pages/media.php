<?php
ob_start();
include '../database/db.php';

// DB Helper function
function db_log_error($db, $label) {
    error_log("[Gallery Page] $label mysqli error: " . mysqli_error($db));
}

// ----------------------------------------------------
// ১. Add / Update / Delete Processing
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {

    // --- Save (Add or Update) Photo ---
    if ($_POST['action_type'] === 'save_gallery') {
        $id = isset($_POST['gallery_id']) ? (int)$_POST['gallery_id'] : 0;
        $caption = mysqli_real_escape_string($db, trim($_POST['caption'] ?? ''));
        $category = mysqli_real_escape_string($db, trim($_POST['category'] ?? ''));
        $status = mysqli_real_escape_string($db, trim($_POST['status'] ?? 'active'));

        $image_name = '';

        // Image Upload Handle
        if (isset($_FILES['gallery_image']) && $_FILES['gallery_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'public/gallery/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_ext = pathinfo($_FILES['gallery_image']['name'], PATHINFO_EXTENSION);
            $image_name = $upload_dir . time() . '_' . uniqid() . '.' . $file_ext;

            if (!move_uploaded_file($_FILES['gallery_image']['tmp_name'], $image_name)) {
                error_log("[Gallery Page] Upload failed for: " . $_FILES['gallery_image']['name']);
                $image_name = '';
            }

            // Update mode: Delete old image if new image uploaded successfully
            if ($id > 0 && !empty($image_name)) {
                $stmt = mysqli_prepare($db, "SELECT image FROM galleries WHERE id = ?");
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
                }
            }
        }

        if ($id > 0) {
            // Update Query
            if (!empty($image_name)) {
                $stmt = mysqli_prepare($db, "UPDATE galleries SET caption=?, category=?, image=?, status=? WHERE id=?");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "ssssi", $caption, $category, $image_name, $status, $id);
                }
            } else {
                $stmt = mysqli_prepare($db, "UPDATE galleries SET caption=?, category=?, status=? WHERE id=?");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "sssi", $caption, $category, $status, $id);
                }
            }
            if ($stmt) {
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        } else {
            // Insert Query
            if (!empty($image_name)) {
                $stmt = mysqli_prepare($db, "INSERT INTO galleries (caption, category, image, status) VALUES (?, ?, ?, ?)");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "ssss", $caption, $category, $image_name, $status);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_close($stmt);
                }
            }
        }

        if (ob_get_length()) ob_end_clean();
        header("Location: dashboard.php?page=media");
        exit;
    }

    // --- Delete Photo ---
    if ($_POST['action_type'] === 'delete_gallery') {
        $delete_id = isset($_POST['delete_id']) ? intval($_POST['delete_id']) : 0;
        if ($delete_id > 0) {
            // Remove image from folder
            $stmt = mysqli_prepare($db, "SELECT image FROM galleries WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $delete_id);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                if ($res && ($row = mysqli_fetch_assoc($res))) {
                    if (!empty($row['image']) && file_exists($row['image'])) {
                        @unlink($row['image']);
                    }
                }
                mysqli_stmt_close($stmt);
            }

            // Remove row from DB
            $del_stmt = mysqli_prepare($db, "DELETE FROM galleries WHERE id = ?");
            if ($del_stmt) {
                mysqli_stmt_bind_param($del_stmt, "i", $delete_id);
                mysqli_stmt_execute($del_stmt);
                mysqli_stmt_close($del_stmt);
            }
        }

        if (ob_get_length()) ob_end_clean();
        header("Location: dashboard.php?page=gallery");
        exit;
    }
}

// ----------------------------------------------------
// ২. Analytics Counters Query
// ----------------------------------------------------
$total_count  = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as total FROM galleries"))['total'] ?? 0;
$active_count = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as active FROM galleries WHERE status='active'"))['active'] ?? 0;

// Top Featured Category
$top_cat_q = mysqli_query($db, "SELECT category, COUNT(*) as cat_cnt FROM galleries GROUP BY category ORDER BY cat_cnt DESC LIMIT 1");
$top_category = ($top_cat_q && $row = mysqli_fetch_assoc($top_cat_q)) ? $row['category'] : 'N/A';

// ----------------------------------------------------
// ৩. Search and Pagination Logic
// ----------------------------------------------------
$search = isset($_GET['search']) ? mysqli_real_escape_string($db, trim($_GET['search'])) : '';
$where_clause = $search ? "WHERE caption LIKE '%$search%' OR category LIKE '%$search%'" : "";

$limit = 10;
$page = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;

$filtered_total_q = mysqli_query($db, "SELECT COUNT(*) as count FROM galleries $where_clause");
$filtered_total = mysqli_fetch_assoc($filtered_total_q)['count'] ?? 0;

$total_pages = ceil($filtered_total / $limit);
if ($total_pages > 0 && $page > $total_pages) $page = $total_pages;

$offset = ($page - 1) * $limit;

// Fetch Main Data
$gallery_result = mysqli_query($db, "SELECT * FROM galleries $where_clause ORDER BY id DESC LIMIT $limit OFFSET $offset");
?>

<div class="p-4 sm:p-6 lg:p-8">

    <!-- Page Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Photo Gallery Management</h2>
            <p class="text-sm text-slate-500">Manage photo gallery items for bishwas.org</p>
        </div>
        <div>
            <button type="button" onclick="openGalleryModal()"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-4 py-2.5 rounded-lg shadow transition-all">
                <i class="fa-solid fa-plus"></i> Add New Photo
            </button>
        </div>
    </div>

    <!-- Analytics Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Total Gallery Photos</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1"><?= sprintf("%02d", $total_count) ?></h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-images"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Active Published</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1"><?= sprintf("%02d", $active_count) ?></h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Featured Category</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1"><?= htmlspecialchars($top_category) ?></h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-camera"></i>
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

    <!-- Gallery Images Table Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-8">

        <!-- Table Controls -->
        <div class="p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <h3 class="font-bold text-slate-800">All Gallery Photos</h3>
                <span class="text-xs bg-emerald-100 text-emerald-800 font-semibold px-2.5 py-1 rounded-full">Active Items</span>
            </div>

            <form method="GET" action="" class="flex items-center gap-3">
                <input type="hidden" name="page" value="gallery">
                <div class="relative w-full sm:w-64">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400 text-xs">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search photo caption..."
                        class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg pl-8 pr-3 py-2 text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </form>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5">Image Preview</th>
                        <th class="px-6 py-3.5">Caption / Title</th>
                        <th class="px-6 py-3.5">Category</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">

                    <?php if ($gallery_result && mysqli_num_rows($gallery_result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($gallery_result)): ?>
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-4">
                                    <img src="<?= htmlspecialchars($row['image']) ?>" alt="Photo" class="w-16 h-12 rounded-lg object-cover border border-slate-200 shrink-0">
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-semibold text-slate-800 line-clamp-1">
                                        <?= !empty($row['caption']) ? htmlspecialchars($row['caption']) : '<i class="text-slate-400 font-normal">No Caption</i>' ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="bg-slate-100 text-slate-700 text-xs px-2.5 py-1 rounded-md font-medium border border-slate-200">
                                        <?= htmlspecialchars($row['category']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php if ($row['status'] === 'active'): ?>
                                        <span class="text-emerald-600 bg-emerald-50 border border-emerald-200 text-xs font-semibold px-2.5 py-1 rounded-full">Active</span>
                                    <?php else: ?>
                                        <span class="text-amber-600 bg-amber-50 border border-amber-200 text-xs font-semibold px-2.5 py-1 rounded-full">Draft</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                                    <button type="button" class="text-slate-400 hover:text-emerald-600 p-1.5 transition-colors" title="Edit"
                                        onclick='editGalleryItem(<?= json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button type="button" class="text-slate-400 hover:text-rose-600 p-1.5 transition-colors" title="Delete"
                                        onclick="openDeleteModal(<?= (int)$row['id'] ?>)">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-400">Image not found</td>
                        </tr>
                    <?php endif; ?>

                </tbody>
            </table>
        </div>

        <!-- Pagination Footer -->
        <div class="p-4 border-t border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
            <span>
                Showing <b><?= $filtered_total > 0 ? $offset + 1 : 0 ?></b> to <b><?= min($offset + $limit, $filtered_total) ?></b> of <b><?= $filtered_total ?></b> entries
            </span>
            <?php if ($total_pages > 1): ?>
                <div class="flex items-center gap-1">
                    <!-- Prev -->
                    <?php if ($page > 1): ?>
                        <a href="?page=gallery&p=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1.5 rounded border border-slate-200 bg-white hover:bg-slate-100 text-slate-600">Previous</a>
                    <?php else: ?>
                        <button disabled class="px-3 py-1.5 rounded border border-slate-200 bg-white text-slate-300 opacity-50 cursor-not-allowed">Previous</button>
                    <?php endif; ?>

                    <!-- Number Links -->
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=gallery&p=<?= $i ?>&search=<?= urlencode($search) ?>" 
                           class="px-3 py-1.5 rounded border <?= $i === $page ? 'border-emerald-500 bg-emerald-600 text-white font-semibold' : 'border-slate-200 bg-white hover:bg-slate-100 text-slate-600' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <!-- Next -->
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=gallery&p=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1.5 rounded border border-slate-200 bg-white hover:bg-slate-100 text-slate-600">Next</a>
                    <?php else: ?>
                        <button disabled class="px-3 py-1.5 rounded border border-slate-200 bg-white text-slate-300 opacity-50 cursor-not-allowed">Next</button>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- Add/Edit Gallery Modal -->
<div id="galleryModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xl w-full max-w-xl overflow-hidden">
        
        <!-- Modal Header -->
        <div class="p-5 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
            <h3 id="modalTitle" class="font-bold text-slate-800 text-lg flex items-center gap-2">
                <i class="fa-solid fa-cloud-arrow-up text-emerald-600"></i> Upload New Gallery Photo
            </h3>
            <button type="button" onclick="closeGalleryModal()" class="text-slate-400 hover:text-slate-600 p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Modal Form -->
        <form action="" method="POST" enctype="multipart/form-data" class="p-6 space-y-5">
            <input type="hidden" name="action_type" value="save_gallery">
            <input type="hidden" name="gallery_id" id="gallery_id" value="0">
            
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Photo Image File</label>
                <input type="file" name="gallery_image" id="gallery_image_input"
                    class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-slate-200 rounded-lg bg-slate-50 cursor-pointer">
                <small id="image_required_note" class="text-slate-400 text-[11px] mt-1 block">নতুন আপডেটের ক্ষেত্রে ছবি সিলেক্ট না করলে আগের ছবিই থাকবে।</small>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Caption / Title (Optional)</label>
                    <input type="text" name="caption" id="caption_input" placeholder="যেমন: ত্রাণ বিতরণ কর্মসূচি" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Category</label>
                    <select name="category" id="category_input" class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="সাধারণ তহবিল">সাধারণ তহবিল</option>
                        <option value="নিয়মিত অনুদান তহবিল">নিয়মিত অনুদান তহবিল</option>
                        <option value="যাকাত তহবিল">যাকাত তহবিল</option>
                        <option value="জরুরি ত্রাণ তহবিল">জরুরি ত্রাণ তহবিল</option>
                        <option value="শিক্ষা তহবিল">শিক্ষা তহবিল</option>
                        <option value="অন্যান্য তহবিল">অন্যান্য তহবিল</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Status</label>
                <select name="status" id="status_input" class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <option value="active">Active (Publish)</option>
                    <option value="draft">Draft</option>
                </select>
            </div>

            <!-- Footer Buttons -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <button type="button" onclick="closeGalleryModal()" 
                    class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                    class="px-5 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow transition-all flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i> Save Photo
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xl w-full max-w-md p-6 text-center">
        <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-4 text-xl">
            <i class="fa-solid fa-trash"></i>
        </div>
        <h3 class="font-bold text-slate-800 text-lg mb-2">Are you sure?</h3>
        <p class="text-xs text-slate-500 mb-6">আপনি কি সত্যিই এই ছবিটি গ্যালারি থেকে মুছে ফেলতে চান?</p>
        
        <form method="POST" action="" class="flex items-center justify-center gap-3">
            <input type="hidden" name="action_type" value="delete_gallery">
            <input type="hidden" name="delete_id" id="delete_id_input" value="0">
            <button type="button" onclick="closeDeleteModal()" class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg">Cancel</button>
            <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 rounded-lg">Delete</button>
        </form>
    </div>
</div>

<script>
    function openGalleryModal() {
        document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-cloud-arrow-up text-emerald-600"></i> Upload New Gallery Photo';
        document.getElementById('gallery_id').value = '0';
        document.getElementById('caption_input').value = '';
        document.getElementById('category_input').value = 'সাধারণ তহবিল';
        document.getElementById('status_input').value = 'active';
        document.getElementById('gallery_image_input').required = true;
        document.getElementById('image_required_note').classList.add('hidden');
        document.getElementById('galleryModal').classList.remove('hidden');
    }

    function editGalleryItem(data) {
        document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-pen text-emerald-600"></i> Edit Gallery Photo';
        document.getElementById('gallery_id').value = data.id;
        document.getElementById('caption_input').value = data.caption || '';
        document.getElementById('category_input').value = data.category;
        document.getElementById('status_input').value = data.status;
        document.getElementById('gallery_image_input').required = false;
        document.getElementById('image_required_note').classList.remove('hidden');
        document.getElementById('galleryModal').classList.remove('hidden');
    }

    function closeGalleryModal() {
        document.getElementById('galleryModal').classList.add('hidden');
    }

    function openDeleteModal(id) {
        document.getElementById('delete_id_input').value = id;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }
</script>