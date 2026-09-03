<?php
ob_start();

// ড্যাটাবেজ কানেকশন
include '../database/db.php';

// ---------- Helper: Log DB Errors ----------
function db_log_error($db, $label) {
    error_log("[Blog Page] $label mysqli error: " . mysqli_error($db));
}

// ---------- Helper: English Date to Bengali Date String ----------
function get_bengali_date() {
    $en_months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
    $bn_months = ['জানুয়ারি', 'ফেব্রুয়ারি', 'মার্চ', 'এপ্রিল', 'মে', 'জুন', 'জুলাই', 'আগস্ট', 'সেপ্টেম্বর', 'অক্টোবর', 'নভেম্বর', 'ডিসেম্বর'];
    $en_numbers = ['0', '1', '2', '3', '4', '5', '6', '7', '8', '9'];
    $bn_numbers = ['০', '১', '২', '৩', '৪', '৫', '৬', '৭', '৮', '৯'];

    $day   = date('j');
    $month = date('F');
    $year  = date('Y');

    $day_bn   = str_replace($en_numbers, $bn_numbers, $day);
    $month_bn = str_replace($en_months, $bn_months, $month);
    $year_bn  = str_replace($en_numbers, $bn_numbers, $year);

    return $day_bn . ' ' . $month_bn . ', ' . $year_bn; // যেমন: ৩ সেপ্টেম্বর, ২০২৬
}

// ১. Blog Add / Update / Delete Processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {

    // --- Save or Update Blog Post ---
    if ($_POST['action_type'] === 'save_blog') {
        $id                = isset($_POST['blog_id']) ? (int)$_POST['blog_id'] : 0;
        $blog_title        = mysqli_real_escape_string($db, trim($_POST['blog_title'] ?? ''));
        $category          = mysqli_real_escape_string($db, trim($_POST['category'] ?? 'ব্লগ'));
        $publish_date      = get_bengali_date(); // কারেন্ট তারিখ অটোমেটিক তৈরি হবে
        $short_description = mysqli_real_escape_string($db, trim($_POST['short_description'] ?? ''));
        $full_content      = mysqli_real_escape_string($db, trim($_POST['full_content'] ?? ''));
        $status            = mysqli_real_escape_string($db, trim($_POST['status'] ?? 'active'));

        $image_name = '';

        // ইমেজ আপলোড প্রসেসিং
        if (isset($_FILES['blog_image']) && $_FILES['blog_image']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = 'public/blogs_img/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_ext   = pathinfo($_FILES['blog_image']['name'], PATHINFO_EXTENSION);
            $image_name = $upload_dir . time() . '_' . uniqid() . '.' . $file_ext;

            if (!move_uploaded_file($_FILES['blog_image']['tmp_name'], $image_name)) {
                error_log("[Blog Page] move_uploaded_file failed for: " . $_FILES['blog_image']['name']);
                $image_name = '';
            }

            // আপডেট করার সময় আগের ছবি মুছে ফেলা
            if ($id > 0 && !empty($image_name)) {
                $stmt = mysqli_prepare($db, "SELECT blog_image FROM blogs WHERE id = ?");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "i", $id);
                    mysqli_stmt_execute($stmt);
                    $res = mysqli_stmt_get_result($stmt);
                    if ($res && ($old_row = mysqli_fetch_assoc($res))) {
                        if (!empty($old_row['blog_image']) && file_exists($old_row['blog_image'])) {
                            @unlink($old_row['blog_image']);
                        }
                    }
                    mysqli_stmt_close($stmt);
                }
            }
        }

        if ($id > 0) {
            // Update Query
            if (!empty($image_name)) {
                $stmt = mysqli_prepare($db, "UPDATE blogs SET blog_title=?, category=?, blog_image=?, short_description=?, full_content=?, status=? WHERE id=?");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "ssssssi", $blog_title, $category, $image_name, $short_description, $full_content, $status, $id);
                }
            } else {
                $stmt = mysqli_prepare($db, "UPDATE blogs SET blog_title=?, category=?, short_description=?, full_content=?, status=? WHERE id=?");
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "sssssi", $blog_title, $category, $short_description, $full_content, $status, $id);
                }
            }

            if ($stmt) {
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        } else {
            // Insert Query (কারেন্ট পব্লিশ ডেট সেভ হবে)
            $stmt = mysqli_prepare($db, "INSERT INTO blogs (blog_title, category, publish_date, blog_image, short_description, full_content, status) VALUES (?, ?, ?, ?, ?, ?, ?)");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "sssssss", $blog_title, $category, $publish_date, $image_name, $short_description, $full_content, $status);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);
            }
        }

        if (ob_get_length()) ob_end_clean();
        header("Location: dashboard.php?page=blogs");
        exit;
    }



    //  Delete প্রসেসিং (FIXED)
    if (isset($_POST['action_type'])) {  $delete_id = isset($_POST['delete_id']) ? intval($_POST['delete_id']) : 0;
        if ($delete_id > 0) {
            // ১. ছবি খুঁজে বের করে ডিলিট করা
            $stmt = mysqli_prepare($db, "SELECT blog_image FROM blogs WHERE id = ?");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $delete_id);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);

                if ($res && ($row = mysqli_fetch_assoc($res))) {
                    $img_path = $row['blog_image'];
                    if (!empty($img_path) && file_exists($img_path)) {
                        @unlink($img_path);
                    }
                }
                mysqli_stmt_close($stmt);
            } else {
                db_log_error($db, "delete image select prepare");
            }

            // ২. রেকর্ড ডিলিট করা
            $del_stmt = mysqli_prepare($db, "DELETE FROM blogs WHERE id = ?");
            if ($del_stmt) {
                mysqli_stmt_bind_param($del_stmt, "i", $delete_id);
                if (!mysqli_stmt_execute($del_stmt)) {
                    db_log_error($db, "delete blog execute");
                }
                mysqli_stmt_close($del_stmt);
            } else {
                db_log_error($db, "delete blog prepare");
            }
        } else {
            error_log("[Blogs Page] delete_blog called with invalid delete_id: " . ($_POST['delete_id'] ?? 'not set'));
        }

        if (ob_get_length()) ob_end_clean();
        header("Location: dashboard.php?page=blogs");
        exit;
    }
}

// ----------------------------------------------------
// ২. Analytics & Header Statistics
// ----------------------------------------------------
$total_count     = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as total FROM blogs"))['total'] ?? 0;
$published_count = mysqli_fetch_assoc(mysqli_query($db, "SELECT COUNT(*) as active FROM blogs WHERE status='active'"))['active'] ?? 0;

// ক্যাটাগরি তালিকা ফিল্টার
$cat_query  = mysqli_query($db, "SELECT DISTINCT category FROM blogs LIMIT 3");
$cat_list   = [];
while ($c   = mysqli_fetch_assoc($cat_query)) {
    $cat_list[] = $c['category'];
}
$categories_str = !empty($cat_list) ? implode(', ', $cat_list) : 'ব্লগ, ডায়রি';

// ----------------------------------------------------
// ৩. Search & Pagination Controls
// ----------------------------------------------------
$search = isset($_GET['search']) ? mysqli_real_escape_string($db, trim($_GET['search'])) : '';
$where_clause = $search ? "WHERE blog_title LIKE '%$search%' OR category LIKE '%$search%' OR short_description LIKE '%$search%'" : "";

$limit = 10;
$page  = isset($_GET['p']) && is_numeric($_GET['p']) ? (int)$_GET['p'] : 1;
if ($page < 1) $page = 1;

$filtered_total_q = mysqli_query($db, "SELECT COUNT(*) as count FROM blogs $where_clause");
$filtered_total   = mysqli_fetch_assoc($filtered_total_q)['count'] ?? 0;

$total_pages = ceil($filtered_total / $limit);
if ($total_pages > 0 && $page > $total_pages) $page = $total_pages;

$offset = ($page - 1) * $limit;

// ----------------------------------------------------
// ৪. Main Table Data Fetch
// ----------------------------------------------------
$blogs_result = mysqli_query($db, "SELECT * FROM blogs $where_clause ORDER BY id DESC LIMIT $limit OFFSET $offset");
?>

<div class="p-4 sm:p-6 lg:p-8">
    
    <!-- Page Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Blog & Diary Management</h2>
            <p class="text-sm text-slate-500">Manage blog articles, field updates, videos, and section headers</p>
        </div>
        <div>
            <button type="button" onclick="openBlogModal()"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-4 py-2.5 rounded-lg shadow transition-all">
                <i class="fa-solid fa-plus"></i>
                Create New Blog Post
            </button>
        </div>
    </div>

    <!-- Analytics / Counter Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Total Articles</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1"><?= sprintf("%02d", $total_count) ?></h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-newspaper"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Published Posts</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1"><?= sprintf("%02d", $published_count) ?></h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Categories</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1 line-clamp-1"><?= htmlspecialchars($categories_str) ?></h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-layer-group"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Status</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">Active</h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-globe"></i>
            </div>
        </div>
    </div>

    <!-- Blog Posts Table Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-8">
        
        <!-- Table Controls & Search Form -->
        <div class="p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <h3 class="font-bold text-slate-800">All Articles & Diary Posts</h3>
                <span class="text-xs bg-emerald-100 text-emerald-800 font-semibold px-2.5 py-1 rounded-full">Published</span>
            </div>
            
            <form method="GET" action="" class="flex items-center gap-3">
                <input type="hidden" name="page" value="blogs">
                <div class="relative w-full sm:w-64">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400 text-xs">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" placeholder="Search title or category..." 
                        class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg pl-8 pr-3 py-2 text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </form>
        </div>

        <!-- Dynamic Table View -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5">Thumbnail</th>
                        <th class="px-6 py-3.5">Post Details</th>
                        <th class="px-6 py-3.5">Category</th>
                        <th class="px-6 py-3.5">Publish Date</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php if ($blogs_result && mysqli_num_rows($blogs_result) > 0): ?>
                        <?php while ($row = mysqli_fetch_assoc($blogs_result)): ?>
                            <tr class="hover:bg-slate-50/50">
                                <td class="px-6 py-4">
                                    <img src="<?= !empty($row['blog_image']) ? htmlspecialchars($row['blog_image']) : 'https://placehold.co/100x80?text=No+Img' ?>" 
                                         alt="<?= htmlspecialchars($row['blog_title']) ?>" 
                                         class="w-16 h-12 rounded-lg object-cover border border-slate-200 shrink-0">
                                </td>
                                <td class="px-6 py-4">
                                    <h4 class="font-bold text-slate-800 line-clamp-1"><?= htmlspecialchars($row['blog_title']) ?></h4>
                                    <p class="text-xs text-slate-500 line-clamp-1 mt-0.5"><?= htmlspecialchars($row['short_description']) ?></p>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs px-2.5 py-1 rounded-md font-semibold">
                                        <?= htmlspecialchars($row['category']) ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">
                                    <?= htmlspecialchars($row['publish_date']) ?>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                                    <button type="button" 
                                            class="edit-blog-btn text-slate-400 hover:text-emerald-600 p-1.5 transition-colors" 
                                            title="Edit"
                                            data-blog='<?= json_encode($row, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>'>
                                        <i class="fa-solid fa-pen"></i>
                                    </button>
                                    <button type="button"
                                        class="delete-btn text-slate-400 hover:text-rose-600 p-1.5 transition-colors"
                                        title="Delete"
                                        data-id="<?= (int)$row['id'] ?>"
                                        data-title="<?= htmlspecialchars($row['blog_title'], ENT_QUOTES) ?>">
                                        <i class="fa-solid fa-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-slate-400">
                                কোনো ব্লগ পোস্ট পাওয়া যায়নি।
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Table Pagination Footer -->
        <div class="p-4 border-t border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
            <span>Showing <?= $filtered_total > 0 ? $offset + 1 : 0 ?> to <?= min($offset + $limit, $filtered_total) ?> of <?= $filtered_total ?> entries</span>
            <div class="flex items-center gap-1">
                <?php if ($page > 1): ?>
                    <a href="?page=blogs&p=<?= $page - 1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1.5 rounded border border-slate-200 bg-white text-slate-600 hover:bg-slate-100">Previous</a>
                <?php else: ?>
                    <span class="px-3 py-1.5 rounded border border-slate-200 bg-white text-slate-400 cursor-not-allowed">Previous</span>
                <?php endif; ?>

                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="?page=blogs&p=<?= $i ?>&search=<?= urlencode($search) ?>" class="px-3 py-1.5 rounded border <?= $i === $page ? 'border-emerald-500 bg-emerald-600 text-white font-semibold' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-100' ?>">
                        <?= $i ?>
                    </a>
                <?php endfor; ?>

                <?php if ($page < $total_pages): ?>
                    <a href="?page=blogs&p=<?= $page + 1 ?>&search=<?= urlencode($search) ?>" class="px-3 py-1.5 rounded border border-slate-200 bg-white text-slate-600 hover:bg-slate-100">Next</a>
                <?php else: ?>
                    <span class="px-3 py-1.5 rounded border border-slate-200 bg-white text-slate-400 cursor-not-allowed">Next</span>
                <?php endif; ?>
            </div>
        </div>

    </div>
</div>

<!-- Add/Edit Blog Post Modal -->
<div id="blogModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xl w-full max-w-2xl overflow-hidden animate-in fade-in zoom-in duration-200 max-h-[90vh] flex flex-col">
        
        <!-- Modal Header -->
        <div class="p-5 border-b border-slate-200 bg-slate-50 flex items-center justify-between shrink-0">
            <h3 id="modalTitle" class="font-bold text-slate-800 text-lg flex items-center gap-2">
                <i class="fa-solid fa-pen-nib text-emerald-600"></i>
                Add New Blog / Article
            </h3>
            <button type="button" onclick="closeBlogModal()" class="text-slate-400 hover:text-slate-600 p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Modal Form Body -->
        <form action="" method="POST" enctype="multipart/form-data" class="p-6 space-y-5 overflow-y-auto">
            <input type="hidden" name="action_type" value="save_blog">
            <input type="hidden" name="blog_id" id="blog_id" value="0">
            
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Article Title</label>
                <input type="text" name="blog_title" id="blog_title" required placeholder="যেমন: শীতের উষ্ণতা পৌঁছে যাক সবার জীবনে" 
                    class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Category Tag</label>
                <input type="text" name="category" id="category" value="ব্লগ" required placeholder="যেমন: ব্লগ, ডায়রি, খবর" 
                    class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Feature Image / Thumbnail</label>
                <input type="file" name="blog_image" id="blog_image" 
                    class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-slate-200 rounded-lg bg-slate-50 cursor-pointer">
                <p class="text-[11px] text-slate-400 mt-1" id="imageHelpText">নতুন ইমেজ নির্বাচন না করলে আগেরটি অপরিবর্তিত থাকবে।</p>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Short Description (Excerpt)</label>
                <textarea name="short_description" id="short_description" rows="3" required placeholder="সংক্ষিপ্ত বিবরণ লিখুন..." 
                    class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Full Article Content (Optional Detail)</label>
                <textarea name="full_content" id="full_content" rows="4" placeholder="বিস্তারিত মূল লেখা লিখুন..." 
                    class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
            </div>

            <!-- Footer Buttons -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <button type="button" onclick="closeBlogModal()" 
                    class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                    class="px-5 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow transition-all flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save Blog Post
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

    function openBlogModal() {
        document.getElementById('blog_id').value = 0;
        document.getElementById('blog_title').value = '';
        document.getElementById('category').value = 'ব্লগ';
        document.getElementById('short_description').value = '';
        document.getElementById('full_content').value = '';
        document.getElementById('blog_image').required = true;
        document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-pen-nib text-emerald-600"></i> Add New Blog / Article';
        document.getElementById('blogModal').classList.remove('hidden');
    }

    function closeBlogModal() {
        document.getElementById('blogModal').classList.add('hidden');
    }

    // Edit Button JavaScript Handler
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.edit-blog-btn').forEach(button => {
            button.addEventListener('click', function() {
                const data = JSON.parse(this.getAttribute('data-blog'));
                
                document.getElementById('blog_id').value = data.id;
                document.getElementById('blog_title').value = data.blog_title || '';
                document.getElementById('category').value = data.category || 'ব্লগ';
                document.getElementById('short_description').value = data.short_description || '';
                document.getElementById('full_content').value = data.full_content || '';
                
                // এডিট করার সময় ইমেজ সিলেক্ট করা বাধ্যতাবোধক নয়
                document.getElementById('blog_image').required = false;
                document.getElementById('modalTitle').innerHTML = '<i class="fa-solid fa-pen-to-square text-emerald-600"></i> Edit Blog Post';
                
                document.getElementById('blogModal').classList.remove('hidden');
            });
        });
    });
</script>