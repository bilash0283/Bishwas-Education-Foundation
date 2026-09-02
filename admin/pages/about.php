<?php
ob_start();

include '../database/db.php';

// DB Error Logger
function db_log_error($db, $label) {
    error_log("[About Settings Page] $label mysqli error: " . mysqli_error($db));
}

$message = '';
$msg_type = '';

// ডিফল্ট ভ্যালু ডিফাইন করা
$default_top_subtitle = 'আমাদের লক্ষ্য ও উদ্দেশ্য';
$default_main_title   = 'একটি আদর্শ ও আত্মনির্ভরশীল সমাজ বিনির্মাণ';
$default_description  = 'বিশ্বাস এডুকেশন ফাউন্ডেশন (Bishwas Education Foundation.) একটি সম্পূর্ণ অরাজনৈতিক ও জনকল্যাণমূলক সেবা সংস্থা। সমাজের অবহেলিত ও দরিদ্র শ্রেণীর মানুষের মৌলিক চাহিদা পূরণ এবং তাদের কারিগরি শিক্ষার মাধ্যমে স্বাবলম্বী করে তোলাই আমাদের মূল ব্রত।';
$default_point_1      = 'স্বচ্ছ ও জবাবদিহিতামূলক তহবিল বণ্টন ব্যবস্থা।';
$default_point_2      = 'জ্ঞান, নৈতিকতা ও মানবিক মূল্যবোধের বিকাশ।';
$default_point_3      = 'দক্ষতা বৃদ্ধি ও বেকারত্ব দূরীকরণে প্রশিক্ষণ ইনস্টিটিউট।';
$default_quote_badge  = 'মানব সেবাই ইসলামের মূল শিক্ষা।';

// ১. Form Submission Processing
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    
    // ডাটাবেসে বিদ্যমান ডাটা চেক
    $check_q = mysqli_query($db, "SELECT * FROM about_vision ORDER BY id ASC LIMIT 1");
    $existing_data = mysqli_fetch_assoc($check_q);
    
    $image_name = $existing_data['image'] ?? '';

    // কাস্টম সেভ নাকি ডিফল্ট রিসেট প্রসেস করা হবে তা নির্ধারণ
    if ($_POST['action_type'] === 'reset_to_default') {
        // ইমেজ ঠিক রেখে বাকিগুলো ডিফল্ট ভ্যালু দিয়ে সেট হবে
        $top_subtitle = $default_top_subtitle;
        $main_title   = $default_main_title;
        $description  = $default_description;
        $point_1      = $default_point_1;
        $point_2      = $default_point_2;
        $point_3      = $default_point_3;
        $quote_badge  = $default_quote_badge;
        $action_label = "Reset to Default";
    } else {
        // ফর্ম থেকে ইউজার ইনপুট নেওয়া
        $top_subtitle = mysqli_real_escape_string($db, trim($_POST['top_subtitle'] ?? ''));
        $main_title   = mysqli_real_escape_string($db, trim($_POST['main_title'] ?? ''));
        $description  = mysqli_real_escape_string($db, trim($_POST['description'] ?? ''));
        $point_1      = mysqli_real_escape_string($db, trim($_POST['point_1'] ?? ''));
        $point_2      = mysqli_real_escape_string($db, trim($_POST['point_2'] ?? ''));
        $point_3      = mysqli_real_escape_string($db, trim($_POST['point_3'] ?? ''));
        $quote_badge  = mysqli_real_escape_string($db, trim($_POST['quote_badge'] ?? ''));
        $action_label = "Saved";
    }

    // ইমেজ আপলোড প্রসেসিং (নতুন ফাইল আপলোড করা হলে সেটি পরিবর্তন হবে)
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'public/about/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_ext = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $new_image_name = $upload_dir . 'vision_' . time() . '_' . uniqid() . '.' . $file_ext;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $new_image_name)) {
            if (!empty($image_name) && file_exists($image_name)) {
                @unlink($image_name);
            }
            $image_name = $new_image_name;
        } else {
            error_log("[About Settings Page] move_uploaded_file failed for: " . $_FILES['image']['name']);
        }
    }

    if ($existing_data) {
        // Update Query
        $stmt = mysqli_prepare($db, "UPDATE about_vision SET top_subtitle=?, main_title=?, description=?, point_1=?, point_2=?, point_3=?, quote_badge=?, image=? WHERE id=?");
        if ($stmt) {
            $id = (int)$existing_data['id'];
            mysqli_stmt_bind_param($stmt, "ssssssssi", $top_subtitle, $main_title, $description, $point_1, $point_2, $point_3, $quote_badge, $image_name, $id);
            if (mysqli_stmt_execute($stmt)) {
                $message = "About & Vision Settings updated successfully ({$action_label})!";
                $msg_type = "success";
            } else {
                db_log_error($db, "Update settings execute");
                $message = "Failed to update settings.";
                $msg_type = "error";
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        // Insert Query (প্রথমবার সেভ করার সময়)
        $stmt = mysqli_prepare($db, "INSERT INTO about_vision (top_subtitle, main_title, description, point_1, point_2, point_3, quote_badge, image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Published')");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssssssss", $top_subtitle, $main_title, $description, $point_1, $point_2, $point_3, $quote_badge, $image_name);
            if (mysqli_stmt_execute($stmt)) {
                $message = "About & Vision Settings saved successfully!";
                $msg_type = "success";
            } else {
                db_log_error($db, "Insert settings execute");
                $message = "Failed to save settings.";
                $msg_type = "error";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// ২. ডাটাবেস থেকে বর্তমান সেটিংস রিড করা
$about_query  = mysqli_query($db, "SELECT * FROM about_vision ORDER BY id ASC LIMIT 1");
$about_data   = mysqli_fetch_assoc($about_query);

// ডিফল্ট ডাটা ফলব্যাক (যদি ডাটাবেসে তথ্য না থাকে)
$top_subtitle = $about_data['top_subtitle'] ?? $default_top_subtitle;
$main_title   = $about_data['main_title']   ?? $default_main_title;
$description  = $about_data['description']  ?? $default_description;
$point_1      = $about_data['point_1']      ?? $default_point_1;
$point_2      = $about_data['point_2']      ?? $default_point_2;
$point_3      = $about_data['point_3']      ?? $default_point_3;
$quote_badge  = $about_data['quote_badge']  ?? $default_quote_badge;
$current_img  = !empty($about_data['image']) ? $about_data['image'] : 'uploads/vision-banner.jpg';
$status       = $about_data['status']       ?? 'Published';
?>

<div class="p-4 sm:p-6 lg:p-8">
    
    <!-- Page Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">About & Vision Settings</h2>
            <p class="text-sm text-slate-500">Manage 'আমাদের লক্ষ্য ও উদ্দেশ্য' section content and image for bishwas.org</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Single Section Page
            </span>
        </div>
    </div>

    <!-- Feedback Notification -->
    <?php if (!empty($message)): ?>
        <div class="mb-6 p-4 rounded-lg text-sm font-medium <?= $msg_type === 'success' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-rose-50 text-rose-700 border border-rose-200' ?>">
            <i class="fa-solid <?= $msg_type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation' ?> mr-2"></i>
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <!-- Analytics / Status Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Section Name</p>
                <h3 class="text-lg font-bold text-slate-800 mt-1"><?= htmlspecialchars($top_subtitle) ?></h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-bullseye"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Feature List Items</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">03 Points</h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Status</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1"><?= htmlspecialchars($status) ?></h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-globe"></i>
            </div>
        </div>
    </div>

    <!-- Main Content Form Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-8">
        <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-emerald-600"></i>
                Edit Vision & Mission Content
            </h3>
            <span class="text-xs bg-emerald-100 text-emerald-800 font-semibold px-2.5 py-1 rounded-full">Frontend Display</span>
        </div>

        <form action="" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            
            <!-- Grid 1: Top Subtitle & Main Title -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Top Subtitle</label>
                    <input type="text" name="top_subtitle" value="<?= htmlspecialchars($top_subtitle) ?>" required 
                        placeholder="যেমন: আমাদের লক্ষ্য ও উদ্দেশ্য" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Main Heading / Title</label>
                    <input type="text" name="main_title" value="<?= htmlspecialchars($main_title) ?>" required 
                        placeholder="যেমন: একটি আদর্শ ও আত্মনির্ভরশীল সমাজ বিনির্মাণ" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <!-- Grid 2: Description Paragraph -->
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Main Description Paragraph</label>
                <textarea name="description" rows="3" required 
                    placeholder="বিবরণ লিখুন..." 
                    class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"><?= htmlspecialchars($description) ?></textarea>
            </div>

            <!-- Grid 3: Checklist / Key Points -->
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-4">
                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-emerald-600"></i>
                    Key Feature Checklist Points
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Point 1</label>
                        <input type="text" name="point_1" value="<?= htmlspecialchars($point_1) ?>" required 
                            class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Point 2</label>
                        <input type="text" name="point_2" value="<?= htmlspecialchars($point_2) ?>" required 
                            class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Point 3</label>
                        <input type="text" name="point_3" value="<?= htmlspecialchars($point_3) ?>" required 
                            class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>
            </div>

            <!-- Grid 4: Left Side Image & Overlay Quote Badge -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Section Left Image</label>
                    <div class="flex items-center gap-4">
                        <img src="<?= htmlspecialchars($current_img) ?>" alt="Current Image" class="w-20 h-16 rounded-lg object-cover border border-slate-200 shrink-0">
                        <input type="file" name="image" 
                            class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-slate-200 rounded-lg bg-slate-50 cursor-pointer">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Image Overlay Quote Text</label>
                    <input type="text" name="quote_badge" value="<?= htmlspecialchars($quote_badge) ?>" required 
                        placeholder="যেমন: মানব সেবাই ইসলামের মূল শিক্ষা।" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <!-- Submit Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <button type="submit" name="action_type" value="reset_to_default" 
                    onclick="return confirm('Are you sure you want to reset all field values without the image?');"
                    class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white font-medium px-5 py-2.5 rounded-lg shadow text-sm transition-all cursor-pointer">
                    <i class="fa-solid fa-rotate-left"></i>
                    Reset to Default
                </button>
                <button type="submit" name="action_type" value="save_about_settings" 
                    class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-6 py-2.5 rounded-lg shadow text-sm transition-all cursor-pointer">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save Changes
                </button>
            </div>

        </form>
    </div>
</div>