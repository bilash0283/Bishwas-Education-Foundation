<?php
// ১. Database Connection
include '../database/db.php'; 

// Database Connection variable auto-check (db বা conn যাই থাকুক তা handle করবে)
$connection = isset($db) ? $db : (isset($conn) ? $conn : null);

if (!$connection) {
    die("Database Connection Error! Check your db.php file.");
}

// UTF-8 Bangla encoding setup
mysqli_set_charset($connection, "utf8mb4");

// ২. Handle Form Submission (POST Request)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Collect & sanitize input data
    $section_title    = mysqli_real_escape_string($connection, $_POST['section_title']);
    $section_subtitle = mysqli_real_escape_string($connection, $_POST['section_subtitle']);
    $office_address   = mysqli_real_escape_string($connection, $_POST['office_address']);
    $phone_number     = mysqli_real_escape_string($connection, $_POST['phone_number']);
    $email_address    = mysqli_real_escape_string($connection, $_POST['email_address']);
    $google_map_url   = mysqli_real_escape_string($connection, $_POST['google_map_url']);

    // Check if ID 1 exists
    $check_sql = "SELECT id FROM contact_settings WHERE id = 1";
    $check_res = mysqli_query($connection, $check_sql);

    if ($check_res && mysqli_num_rows($check_res) > 0) {
        // Update query for ID = 1
        $sql = "UPDATE contact_settings SET 
                section_title = '$section_title',
                section_subtitle = '$section_subtitle',
                office_address = '$office_address',
                phone_number = '$phone_number',
                email_address = '$email_address',
                google_map_url = '$google_map_url'
                WHERE id = 1";
    } else {
        // Insert query if row 1 does not exist
        $sql = "INSERT INTO contact_settings (id, section_title, section_subtitle, office_address, phone_number, email_address, google_map_url) 
                VALUES (1, '$section_title', '$section_subtitle', '$office_address', '$phone_number', '$email_address', '$google_map_url')";
    }

    if (mysqli_query($connection, $sql)) {
        header("Location: dashboard.php?page=contacts&status=success");
        exit();
    } else {
        echo "Error Updating Record: " . mysqli_error($connection);
    }
}

// ৩. Fetch Current Settings from Database (ID = 1)
$sql = "SELECT * FROM contact_settings WHERE id = 1 LIMIT 1";
$result = mysqli_query($connection, $sql);
$data = ($result) ? mysqli_fetch_assoc($result) : null;

// Default fallback values if DB has no data
$section_title   = $data['section_title'] ?? 'আমাদের সাথে যোগাযোগ করুন';
$section_subtitle= $data['section_subtitle'] ?? 'আপনার যেকোনো জিজ্ঞাসা, পরামর্শ বা মতামতের জন্য আমাদের মেসেজ পাঠাতে পারেন।';
$office_address  = $data['office_address'] ?? '১/জি/১০/১, মীরবাগ হাতিরঝিল, নতুন রাস্তা, ৩ নং লেন, ঢাকা-১২১৭, বাংলাদেশ';
$phone_number    = $data['phone_number'] ?? '+৮৮০ ১৭১৫-৪৮২৩৬৩';
$email_address   = $data['email_address'] ?? 'info@bishwas.org';
$google_map_url  = $data['google_map_url'] ?? 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d241.79815276802313!2d90.4128057552314!3d23.76047066860609!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3755b9e214dcf989%3A0x38ba85b6e6cbed80!2sBag%20Abdul!5e0!3m2!1sen!2sbd!4v1784616353959!5m2!1sen!2sbd';
?>

<div class="p-4 sm:p-6 lg:p-8">
    <!-- Success Message Alert -->
    <?php if (isset($_GET['status']) && $_GET['status'] == 'success'): ?>
        <div class="mb-4 p-4 text-sm text-emerald-800 bg-emerald-100 rounded-lg border border-emerald-200 flex items-center gap-2">
            <i class="fa-solid fa-circle-check"></i>
            <span>All Updates Successful!</span>
        </div>
    <?php endif; ?>

    <!-- Page Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Contact Us Settings</h2>
            <p class="text-sm text-slate-500">Manage contact details, office address, map location, and section titles</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Single Section Page
            </span>
        </div>
    </div>

    <!-- Analytics / Dynamic Info Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Office Location</p>
                <h3 class="text-sm font-bold text-slate-800 mt-1 line-clamp-2"><?php echo htmlspecialchars($office_address); ?></h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-location-dot"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Primary Phone</p>
                <h3 class="text-base font-bold text-slate-800 mt-1"><?php echo htmlspecialchars($phone_number); ?></h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-phone"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Official Email</p>
                <h3 class="text-base font-bold text-slate-800 mt-1"><?php echo htmlspecialchars($email_address); ?></h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-envelope"></i>
            </div>
        </div>
    </div>

    <!-- Main Form Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-8">
        <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-address-book text-emerald-600"></i>
                Edit Contact Information & Content
            </h3>
            <span class="text-xs bg-emerald-100 text-emerald-800 font-semibold px-2.5 py-1 rounded-full">Frontend Display</span>
        </div>

        <form action="" method="POST" id="contactForm" class="p-6 space-y-6">
            
            <!-- Section Title & Subtitle -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Section Title</label>
                    <input type="text" id="section_title" name="section_title" value="<?php echo htmlspecialchars($section_title); ?>" required 
                        placeholder="যেমন: আমাদের সাথে যোগাযোগ করুন" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Section Subtitle / Description</label>
                    <textarea id="section_subtitle" name="section_subtitle" rows="2" required 
                        placeholder="বর্ণনা লিখুন..." 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"><?php echo htmlspecialchars($section_subtitle); ?></textarea>
                </div>
            </div>

            <!-- Contact Information Grid -->
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-4">
                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-circle-info text-emerald-600"></i>
                    Contact Card Details
                </h4>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Office Address -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">
                            <i class="fa-solid fa-location-dot text-emerald-600 mr-1"></i> কার্যালয়ের ঠিকানা
                        </label>
                        <textarea id="office_address" name="office_address" rows="3" required 
                            placeholder="ঠিকানা লিখুন..." 
                            class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"><?php echo htmlspecialchars($office_address); ?></textarea>
                    </div>

                    <!-- Direct Phone -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">
                            <i class="fa-solid fa-phone text-emerald-600 mr-1"></i> সরাসরি ফোন করুন
                        </label>
                        <input type="text" id="phone_number" name="phone_number" value="<?php echo htmlspecialchars($phone_number); ?>" required 
                            placeholder="যেমন: +৮৮০ ১৭১৫-৪৮২৩৬৩" 
                            class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 mb-3">
                    </div>

                    <!-- Email Address -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">
                            <i class="fa-solid fa-envelope text-emerald-600 mr-1"></i> ইমেইল করুন
                        </label>
                        <input type="email" id="email_address" name="email_address" value="<?php echo htmlspecialchars($email_address); ?>" required 
                            placeholder="যেমন: info@bishwas.org" 
                            class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 mb-3">
                    </div>
                </div>
            </div>

            <!-- Google Map Embed -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">
                        <i class="fa-solid fa-map-location-dot text-emerald-600 mr-1"></i> Google Map Embed Iframe URL / Source
                    </label>
                    <textarea id="google_map_url" name="google_map_url" rows="3" required 
                        placeholder="Google Maps Embed URL..." 
                        class="w-full text-xs bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 font-mono"><?php echo htmlspecialchars($google_map_url); ?></textarea>
                </div>
            </div>

            <!-- Action Buttons (Reset Default + Submit) -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <button type="button" onclick="loadDefaultValues()" 
                    class="inline-flex items-center gap-2 bg-slate-200 hover:bg-slate-300 text-slate-700 font-medium px-4 py-2.5 rounded-lg text-sm transition-all">
                    <i class="fa-solid fa-rotate-left"></i>
                    Reset Default
                </button>
                <button type="submit" 
                    class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-6 py-2.5 rounded-lg shadow text-sm transition-all">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save Contact
                </button>
            </div>

        </form>
    </div>
</div>

<!-- JS Script to set default values -->
<script>
function loadDefaultValues() {
    if (confirm("Are you sure you want to reset the input fields with default values?")) {
        document.getElementById('section_title').value = "আমাদের সাথে যোগাযোগ করুন";
        document.getElementById('section_subtitle').value = "আপনার যেকোনো জিজ্ঞাসা, পরামর্শ বা মতামতের জন্য আমাদের মেসেজ পাঠাতে পারেন। আমাদের প্রতিনিধি দ্রুত আপনার সাথে যোগাযোগ করবেন।";
        document.getElementById('office_address').value = "১/জি/১০/১, মীরবাগ হাতিরঝিল, নতুন রাস্তা, ৩ নং লেন, ঢাকা-১২১৭, বাংলাদেশ";
        document.getElementById('phone_number').value = "+৮৮০ ১৭১৫-৪৮২৩৬৩";
        document.getElementById('email_address').value = "info@bishwas.org";
        document.getElementById('google_map_url').value = "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d241.79815276802313!2d90.4128057552314!3d23.76047066860609!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3755b9e214dcf989%3A0x38ba85b6e6cbed80!2sBag%20Abdul!5e0!3m2!1sen!2sbd!4v1784616353959!5m2!1sen!2sbd";
    }
}
</script>