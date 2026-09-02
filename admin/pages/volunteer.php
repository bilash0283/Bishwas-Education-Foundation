<?php
ob_start();

// ডাটাবেস কানেকশন ফাইল ইনক্লুড করুন (আপনার পাথ অনুযায়ী অ্যাডজাস্ট করুন)
include '../database/db.php';

// DB Error Logger
function db_log_error($db, $label) {
    error_log("[Volunteer CTA Settings Page] $label mysqli error: " . mysqli_error($db));
}

$message = '';
$msg_type = '';

// কোডে ডিফল্ট ভ্যালু ডিফাইন করা
$default_banner_title       = 'আপনিও হতে পারেন আমাদের একজন গর্বিত ভলান্টিয়ার';
$default_banner_description = 'আপনার মেধা, সময় ও শ্রম দিয়ে মানবতার সেবায় অবদান রাখুন। দেশব্যাপী আমাদের বিভিন্ন সামাজিক ও ধর্মীয় উদ্যোগে স্বেচ্ছাসেবক হিসেবে কাজ করতে আজই নিবন্ধ করুন।';
$default_button_text        = 'ভলান্টিয়ার হিসেবে যোগ দিন';

// ১. Form Submission Processing (Save Changes & Reset to Default)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_type'])) {
    
    // ডাটাবেসে বিদ্যমান ডাটা চেক করা
    $check_q = mysqli_query($db, "SELECT * FROM volunteer_cta_settings ORDER BY id ASC LIMIT 1");
    $existing_data = mysqli_fetch_assoc($check_q);

    // রিসেট নাকি সেভ অ্যাকশন তা নির্ধারণ
    if ($_POST['action_type'] === 'reset_to_default') {
        $banner_title       = $default_banner_title;
        $banner_description = $default_banner_description;
        $button_text        = $default_button_text;
        $action_label       = "Reset to Default";
    } else {
        // ইউজার ইনপুট নেওয়া এবং ফিল্টার করা
        $banner_title       = mysqli_real_escape_string($db, trim($_POST['banner_title'] ?? ''));
        $banner_description = mysqli_real_escape_string($db, trim($_POST['banner_description'] ?? ''));
        $button_text        = mysqli_real_escape_string($db, trim($_POST['button_text'] ?? ''));
        $action_label       = "Saved";
    }

    if ($existing_data) {
        // Update Query (প্রিপেয়ার্ড স্টেটমেন্ট)
        $stmt = mysqli_prepare($db, "UPDATE volunteer_cta_settings SET banner_title=?, banner_description=?, button_text=? WHERE id=?");
        if ($stmt) {
            $id = (int)$existing_data['id'];
            mysqli_stmt_bind_param($stmt, "sssi", $banner_title, $banner_description, $button_text, $id);
            if (mysqli_stmt_execute($stmt)) {
                $message = "Volunteer CTA Settings updated successfully ({$action_label})!";
                $msg_type = "success";
            } else {
                db_log_error($db, "Update CTA Settings execute");
                $message = "Failed to update settings.";
                $msg_type = "error";
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        // Insert Query (প্রথমবার সেভ করার জন্য)
        $stmt = mysqli_prepare($db, "INSERT INTO volunteer_cta_settings (banner_title, banner_description, button_text, status) VALUES (?, ?, ?, 'Published')");
        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "sss", $banner_title, $banner_description, $button_text);
            if (mysqli_stmt_execute($stmt)) {
                $message = "Volunteer CTA Settings saved successfully!";
                $msg_type = "success";
            } else {
                db_log_error($db, "Insert CTA Settings execute");
                $message = "Failed to save settings.";
                $msg_type = "error";
            }
            mysqli_stmt_close($stmt);
        }
    }
}

// ২. ডাটাবেস থেকে বর্তমান সেটিংস রিড করা
$cta_query  = mysqli_query($db, "SELECT * FROM volunteer_cta_settings ORDER BY id ASC LIMIT 1");
$cta_data   = mysqli_fetch_assoc($cta_query);

// ডাটাবেসে ডাটা না থাকলে কোডের ডিফল্ট মান ব্যবহার হবে
$banner_title       = $cta_data['banner_title']       ?? $default_banner_title;
$banner_description = $cta_data['banner_description'] ?? $default_banner_description;
$button_text        = $cta_data['button_text']        ?? $default_button_text;
$status             = $cta_data['status']             ?? 'Published';
?>

<div class="p-4 sm:p-6 lg:p-8">
    
    <!-- Page Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Volunteer CTA Banner Settings</h2>
            <p class="text-sm text-slate-500">Manage heading, description, and action button for the volunteer call-to-action banner</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Single Banner Section
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
                <p class="text-xs font-semibold text-slate-400 uppercase">Section Type</p>
                <h3 class="text-lg font-bold text-slate-800 mt-1">Volunteer Banner</h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-user-plus"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Target Action</p>
                <h3 class="text-lg font-bold text-slate-800 mt-1"><?= htmlspecialchars($button_text) ?></h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-link"></i>
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

    <!-- Main Form Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-8">
        <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-emerald-600"></i>
                Edit Volunteer Call-To-Action Banner
            </h3>
            <span class="text-xs bg-emerald-100 text-emerald-800 font-semibold px-2.5 py-1 rounded-full">Frontend Display</span>
        </div>

        <form action="" method="POST" class="p-6 space-y-6">
            
            <!-- Main Title -->
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Banner Title / Heading</label>
                <input type="text" name="banner_title" value="<?= htmlspecialchars($banner_title) ?>" required 
                    placeholder="যেমন: আপনিও হতে পারেন আমাদের একজন গর্বিত ভলান্টিয়ার" 
                    class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <!-- Description -->
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Banner Subtitle / Description</label>
                <textarea name="banner_description" rows="3" required 
                    placeholder="ব্যানারের সংক্ষিপ্ত বিবরণ লিখুন..." 
                    class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"><?= htmlspecialchars($banner_description) ?></textarea>
            </div>

            <!-- Button Options -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Button Label</label>
                    <input type="text" name="button_text" value="<?= htmlspecialchars($button_text) ?>" required 
                        placeholder="যেমন: ভলান্টিয়ার হিসেবে যোগ দিন" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <!-- Submit & Reset Action Buttons -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <button type="submit" name="action_type" value="reset_to_default" 
                    onclick="return confirm('Are you sure you want to reset all field values?');"
                    class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white font-medium px-5 py-2.5 rounded-lg shadow text-sm transition-all cursor-pointer">
                    <i class="fa-solid fa-rotate-left"></i>
                    Reset to Default
                </button>
                
                <button type="submit" name="action_type" value="save_cta_settings" 
                    class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-6 py-2.5 rounded-lg shadow text-sm transition-all cursor-pointer">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save Changes
                </button>
            </div>

        </form>
    </div>
</div>