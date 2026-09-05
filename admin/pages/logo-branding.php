<?php
// ১. ডাটাবেস কানেকশন ইনক্লুড
include '../database/db.php'; 

$connection = isset($db) ? $db : (isset($conn) ? $conn : null);

if (!$connection) {
    die("Database Connection Error! Check your db.php file.");
}

// UTF-8 বাংলা এনকোডিং সেটআপ
mysqli_set_charset($connection, "utf8mb4");

// আপলোড ফোল্ডার পাথ নির্দেশ করা
$upload_dir = "../public/assets/";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

// ২. ডাটাবেস থেকে বর্তমান সেটিংস লোড করা (ID = 1)
$sql = "SELECT * FROM branding_settings WHERE id = 1 LIMIT 1";
$result = mysqli_query($connection, $sql);
$data = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result) : null;

// ডিফল্ট ভ্যালু সেটআপ
$site_title   = $data['site_title'] ?? 'Bishwas Education Foundation';
$site_logo    = !empty($data['site_logo']) ? $data['site_logo'] : 'logo_BG.png';
$favicon_icon = !empty($data['favicon_icon']) ? $data['favicon_icon'] : 'logo.png';

// ৩. ফর্ম সাবমিশন প্রসেসিং (POST Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $site_title = mysqli_real_escape_string($connection, $_POST['site_title']);
    
    $updated_logo    = $site_logo;
    $updated_favicon = $favicon_icon;

    // ক) লোগো ইমেজ আপলোড হ্যান্ডলার
    if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
        $logo_tmp_name = $_FILES['site_logo']['tmp_name'];
        $logo_ext      = pathinfo($_FILES['site_logo']['name'], PATHINFO_EXTENSION);
        $new_logo_name = "logo_" . time() . "." . $logo_ext;
        $target_logo   = $upload_dir . $new_logo_name;

        if (move_uploaded_file($logo_tmp_name, $target_logo)) {
            // নতুন ফাইল আপলোড হলে পুরোনো ফাইলটি মুছে ফেলা হবে (যদি সেটি ডিফল্ট ফাইল না হয়)
            $old_logo_path = $upload_dir . $site_logo;
            if (!empty($site_logo) && $site_logo !== 'logo_BG.png' && file_exists($old_logo_path)) {
                unlink($old_logo_path);
            }
            
            $updated_logo = $new_logo_name;
        }
    }

    // খ) ফেভিকন আইকন আপলোড হ্যান্ডলার
    if (isset($_FILES['favicon_icon']) && $_FILES['favicon_icon']['error'] === UPLOAD_ERR_OK) {
        $fav_tmp_name = $_FILES['favicon_icon']['tmp_name'];
        $fav_ext      = pathinfo($_FILES['favicon_icon']['name'], PATHINFO_EXTENSION);
        $new_fav_name = "favicon_" . time() . "." . $fav_ext;
        $target_fav   = $upload_dir . $new_fav_name;

        if (move_uploaded_file($fav_tmp_name, $target_fav)) {
            // নতুন ফাইল আপলোড হলে পুরোনো ফেভিকন মুছে ফেলা হবে (যদি সেটি ডিফল্ট ফাইল না হয়)
            $old_fav_path = $upload_dir . $favicon_icon;
            if (!empty($favicon_icon) && $favicon_icon !== 'logo.png' && file_exists($old_fav_path)) {
                unlink($old_fav_path);
            }

            $updated_favicon = $new_fav_name;
        }
    }

    // গ) ডাটাবেসে আপডেট বা ইনসার্ট ক্যোয়ারি
    if ($data) {
        $sql_update = "UPDATE branding_settings SET 
                        site_title = '$site_title',
                        site_logo = '$updated_logo',
                        favicon_icon = '$updated_favicon'
                        WHERE id = 1";
    } else {
        $sql_update = "INSERT INTO branding_settings (id, site_title, site_logo, favicon_icon) 
                        VALUES (1, '$site_title', '$updated_logo', '$updated_favicon')";
    }

    if (mysqli_query($connection, $sql_update)) {
        header("Location: dashboard.php?page=logo-branding&status=success");
        exit();
    } else {
        $error_message = "Error updating record: " . mysqli_error($connection);
    }
}
?>


<div class="p-4 sm:p-6 lg:p-8 space-y-8">
    <!-- Success Message Alert -->
    <?php if (isset($_GET['status']) && $_GET['status'] === 'success'): ?>
        <div class="p-4 text-sm text-emerald-800 bg-emerald-100 rounded-lg border border-emerald-200 flex items-center gap-2">
            <i class="fa-solid fa-circle-check"></i>
            <span>Logo and branding settings updated successfully!</span>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="p-4 text-sm text-red-800 bg-red-100 rounded-lg border border-red-200 flex items-center gap-2">
            <i class="fa-solid fa-circle-exclamation"></i>
            <span><?php echo $error_message; ?></span>
        </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Logo & Branding Settings</h2>
            <p class="text-sm text-slate-500">Manage site logo, brand name, tags, and favicon icon</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Brand Identity
            </span>
        </div>
    </div>

    <!-- LOGO & BRANDING FORM CARD -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-shapes text-emerald-600"></i>
                Logo & Tag Configuration
            </h3>
            <span class="text-xs bg-slate-800 text-white font-semibold px-2.5 py-1 rounded-full">Branding</span>
        </div>

        <form action="" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            
            <!-- Brand Name & Tag / Tagline Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Organization / Brand Name</label>
                    <input type="text" name="site_title" value="<?php echo htmlspecialchars($site_title); ?>" required 
                        placeholder="যেমন: Bishwas Education Foundation" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <!-- Single Logo & Favicon Assets -->
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-4">
                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-image text-emerald-600"></i>
                    Logo & Favicon Images
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Single Main Logo -->
                    <div class="bg-white p-4 rounded-lg border border-slate-200 flex flex-col justify-between">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Website Logo (Main)</label>
                            <p class="text-[11px] text-slate-400 mb-3">সমগ্র ওয়েবসাইটে ব্যবহারের জন্য একটিমাত্র লোগো আপলোড করুন (PNG/SVG)</p>
                            
                            <div class="h-24 w-full bg-slate-100 rounded-lg border border-dashed border-slate-300 flex items-center justify-center p-3 mb-3">
                                <img src="../public/assets/<?php echo htmlspecialchars($site_logo); ?>" alt="Website Logo" class="max-h-full max-w-full object-contain">
                            </div>
                        </div>
                        <input type="file" name="site_logo" accept="image/*" 
                            class="text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                    </div>

                    <!-- Favicon Icon -->
                    <div class="bg-white p-4 rounded-lg border border-slate-200 flex flex-col justify-between">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Favicon Icon</label>
                            <p class="text-[11px] text-slate-400 mb-3">ব্রাউজার ট্যাবে প্রদর্শনের জন্য ছোট আইকন (32x32px .png/.ico)</p>
                            
                            <div class="h-24 w-full bg-slate-100 rounded-lg border border-dashed border-slate-300 flex items-center justify-center p-3 mb-3">
                                <img src="../public/assets/<?php echo htmlspecialchars($favicon_icon); ?>" alt="Favicon Icon" class="w-10 h-10 object-contain">
                            </div>
                        </div>
                        <input type="file" name="favicon_icon" accept="image/x-icon,image/png" 
                            class="text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                    </div>

                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end pt-4 border-t border-slate-200">
                <button type="submit" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-6 py-2.5 rounded-lg shadow text-sm transition-all">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save Branding Settings
                </button>
            </div>

        </form>
    </div>

</div>