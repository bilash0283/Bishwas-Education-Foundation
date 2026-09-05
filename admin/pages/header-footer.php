<?php
// ১. ডাটাবেস কানেকশন
include '../database/db.php'; 

$connection = isset($db) ? $db : (isset($conn) ? $conn : null);

if (!$connection) {
    die("Database Connection Error! Please check your db.php file.");
}

// UTF-8 বাংলা ক্যারেক্টার সাপোর্ট এনকোডিং
mysqli_set_charset($connection, "utf8mb4");

// ২. ডিফল্ট মানসমূহ (Reset & Initial Fallback values)
$default_donate_btn_text   = "অনুদান দিন";
$default_footer_about_text = "একটি স্বচ্ছ, নির্ভরযোগ্য ও অলাভজনক দাতব্য প্রতিষ্ঠান, যা মানবতার কল্যাণ ও ইসলামের সুমহান আদর্শ প্রসারে কাজ করছে।";
$default_footer_social_title = "আমাদের কাজের সর্বশেষ আপডেট জানতে যুক্ত থাকুন।";
$default_facebook_url      = "https://facebook.com/bishwas";
$default_youtube_url       = "https://youtube.com/bishwas";
$default_twitter_url       = "https://twitter.com/bishwas";
$default_linkedin_url      = "https://linkedin.com/company/bishwas";

// ৩. হ্যান্ডেল ফর্ম সাবমিশন (Save Header / Save Footer / Reset Default)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_type = $_POST['action_type'] ?? '';

    // ক) Reset Default Button Action
    if ($action_type === 'reset_default') {
        $sql = "UPDATE site_settings SET 
                donate_btn_text = '$default_donate_btn_text',
                footer_about_text = '$default_footer_about_text',
                footer_social_title = '$default_footer_social_title',
                facebook_url = '$default_facebook_url',
                youtube_url = '$default_youtube_url',
                twitter_url = '$default_twitter_url',
                linkedin_url = '$default_linkedin_url'
                WHERE id = 1";
                
        if (mysqli_query($connection, $sql)) {
            header("Location: dashboard.php?page=header-footer&status=reset_success");
            exit();
        }
    }

    // খ) Header Settings Save
    if ($action_type === 'update_header') {
        $donate_btn_text = mysqli_real_escape_string($connection, $_POST['donate_btn_text']);
        
        $sql = "UPDATE site_settings SET donate_btn_text = '$donate_btn_text' WHERE id = 1";
        if (mysqli_query($connection, $sql)) {
            header("Location: dashboard.php?page=header-footer&status=header_success");
            exit();
        }
    }

    // গ) Footer Settings Save
    if ($action_type === 'update_footer') {
        $footer_about_text   = mysqli_real_escape_string($connection, $_POST['footer_about_text']);
        $footer_social_title = mysqli_real_escape_string($connection, $_POST['footer_social_title']);
        $facebook_url        = mysqli_real_escape_string($connection, $_POST['facebook_url']);
        $youtube_url         = mysqli_real_escape_string($connection, $_POST['youtube_url']);
        $twitter_url         = mysqli_real_escape_string($connection, $_POST['twitter_url']);
        $linkedin_url        = mysqli_real_escape_string($connection, $_POST['linkedin_url']);

        $sql = "UPDATE site_settings SET 
                footer_about_text = '$footer_about_text',
                footer_social_title = '$footer_social_title',
                facebook_url = '$facebook_url',
                youtube_url = '$youtube_url',
                twitter_url = '$twitter_url',
                linkedin_url = '$linkedin_url'
                WHERE id = 1";

        if (mysqli_query($connection, $sql)) {
            header("Location: dashboard.php?page=header-footer&status=footer_success");
            exit();
        }
    }
}

// ৪. ডাটাবেস থেকে বর্তমান তথ্য লোড করা (ID = 1)
$sql = "SELECT * FROM site_settings WHERE id = 1 LIMIT 1";
$result = mysqli_query($connection, $sql);
$data = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result) : null;

// ইনপুট ফিল্ডের ভ্যালু সেট করা (ডাটা না থাকলে ডিফল্ট মান ব্যবহার হবে)
$donate_btn_text   = $data['donate_btn_text'] ?? $default_donate_btn_text;
$footer_about_text = $data['footer_about_text'] ?? $default_footer_about_text;
$footer_social_title = $data['footer_social_title'] ?? $default_footer_social_title;
$facebook_url      = $data['facebook_url'] ?? $default_facebook_url;
$youtube_url       = $data['youtube_url'] ?? $default_youtube_url;
$twitter_url       = $data['twitter_url'] ?? $default_twitter_url;
$linkedin_url      = $data['linkedin_url'] ?? $default_linkedin_url;
?>

<div class="p-4 sm:p-6 lg:p-8 space-y-8">
    
    <!-- Success Alert Messages -->
    <?php if (isset($_GET['status'])): ?>
        <?php if ($_GET['status'] == 'header_success'): ?>
            <div class="p-4 text-sm text-emerald-800 bg-emerald-100 rounded-lg border border-emerald-200 flex items-center gap-2">
                <i class="fa-solid fa-circle-check"></i>
                <span>Header settings updated successfully!</span>
            </div>
        <?php elseif ($_GET['status'] == 'footer_success'): ?>
            <div class="p-4 text-sm text-emerald-800 bg-emerald-100 rounded-lg border border-emerald-200 flex items-center gap-2">
                <i class="fa-solid fa-circle-check"></i>
                <span>Footer settings updated successfully!</span>
            </div>
        <?php elseif ($_GET['status'] == 'reset_success'): ?>
            <div class="p-4 text-sm text-blue-800 bg-blue-100 rounded-lg border border-blue-200 flex items-center gap-2">
                <i class="fa-solid fa-rotate-left"></i>
                <span>All settings have been reset to their default values!</span>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <!-- Page Header & Default Reset Button -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Header & Footer Settings</h2>
            <p class="text-sm text-slate-500">Manage site logo, button label, about info, and social media links</p>
        </div>
        
        <!-- Default Reset Form (Direct Update on Click) -->
        <form action="" method="POST" onsubmit="return confirm('Are you sure you want to reset all settings to their default values?');">
            <input type="hidden" name="action_type" value="reset_default">
            <button type="submit" class="inline-flex items-center gap-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-medium px-4 py-2.5 rounded-lg text-sm transition-all shadow-sm">
                <i class="fa-solid fa-rotate-left"></i>
                Restore Default Values
            </button>
        </form>
    </div>

    <!-- 1. HEADER SETTINGS FORM -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-window-maximize text-emerald-600"></i>
                Header Settings (Logo & Button Name Only)
            </h3>
            <span class="text-xs bg-emerald-100 text-emerald-800 font-semibold px-2.5 py-1 rounded-full">Header Section</span>
        </div>

        <form action="" method="POST" class="p-6 space-y-6">
            <input type="hidden" name="action_type" value="update_header">

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Donate Button Text Only -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Header Button Text</label>
                    <input type="text" name="donate_btn_text" value="<?php echo htmlspecialchars($donate_btn_text); ?>" required 
                        placeholder="যেমন: অনুদান দিন"
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <!-- Header Save Button -->
            <div class="flex justify-end pt-4 border-t border-slate-200">
                <button type="submit" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-6 py-2 rounded-lg shadow text-sm transition-all">
                    <i class="fa-solid fa-floppy-disk"></i> Save Header
                </button>
            </div>
        </form>
    </div>

    <!-- 2. FOOTER SETTINGS FORM -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-dock-bottom text-emerald-600"></i>
                Footer Settings (Left Info & Right Social Media Only)
            </h3>
            <span class="text-xs bg-slate-800 text-white font-semibold px-2.5 py-1 rounded-full">Footer Section</span>
        </div>

        <form action="" method="POST" class="p-6 space-y-6">
            <input type="hidden" name="action_type" value="update_footer">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Left Side: Logo & Description -->
                <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-4">
                    <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider border-b border-slate-200 pb-2">
                        <i class="fa-solid fa-align-left text-emerald-600 mr-1"></i> Left Side: Logo & Info
                    </h4>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Footer Text / Description</label>
                        <textarea name="footer_about_text" rows="3" required class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"><?php echo htmlspecialchars($footer_about_text); ?></textarea>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Footer Text / Social Media Title</label>
                        <textarea name="footer_social_title" rows="3" required class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"><?php echo htmlspecialchars($footer_social_title); ?></textarea>
                    </div>

                </div>

                <!-- Right Side: Social Media Links -->
                <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-4">
                    <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider border-b border-slate-200 pb-2">
                        <i class="fa-solid fa-share-nodes text-emerald-600 mr-1"></i> Right Side: Social Media Links
                    </h4>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">
                            <i class="fa-brands fa-facebook text-blue-600 mr-1"></i> Facebook URL
                        </label>
                        <input type="url" name="facebook_url" value="<?php echo htmlspecialchars($facebook_url); ?>" placeholder="https://..." class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 mb-3">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">
                            <i class="fa-brands fa-youtube text-red-600 mr-1"></i> YouTube URL
                        </label>
                        <input type="url" name="youtube_url" value="<?php echo htmlspecialchars($youtube_url); ?>" placeholder="https://..." class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 mb-3">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">
                            <i class="fa-brands fa-x-twitter text-slate-800 mr-1"></i> Twitter URL
                        </label>
                        <input type="url" name="twitter_url" value="<?php echo htmlspecialchars($twitter_url); ?>" placeholder="https://..." class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 mb-3">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">
                            <i class="fa-brands fa-linkedin text-blue-600 mr-1"></i> Linkedin
                        </label>
                        <input type="url" name="linkedin_url" value="<?php echo htmlspecialchars($linkedin_url); ?>" placeholder="https://..." class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>

                </div>
            </div>

            <!-- Footer Save Button -->
            <div class="flex justify-end pt-4 border-t border-slate-200">
                <button type="submit" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-6 py-2 rounded-lg shadow text-sm transition-all">
                    <i class="fa-solid fa-floppy-disk"></i> Save Footer
                </button>
            </div>

        </form>
    </div>

</div>