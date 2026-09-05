<?php
    include 'database/db.php';

    $connection = isset($db) ? $db : (isset($conn) ? $conn : null);

    if ($connection) {
        mysqli_set_charset($connection, "utf8mb4");
        
        // ২. ডাটাবেস থেকে কনট্যাক্ট ইনফরমেশন ফেচ করা (ID = 1)
        $sql = "SELECT * FROM contact_settings WHERE id = 1 LIMIT 1";
        $result = mysqli_query($connection, $sql);
        $contact_data = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result) : null;
    }

    // ৩. ডাটা না পাওয়া গেলে ফলব্যাক ডিফল্ট ভ্যালু
    $office_address = $contact_data['office_address'] ?? '১/জি/১০/১, মীরবাগ হাতিরঝিল, নতুন রাস্তা, ৩ নং লেন, ঢাকা-১২১৭, বাংলাদেশ';
    $phone_number   = $contact_data['phone_number'] ?? '+৮৮০ ১৭১৫-৪৮২৩৬৩';
    $email_address  = $contact_data['email_address'] ?? 'info@bishwas.org';
    $google_map_url = $contact_data['google_map_url'] ?? 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d241.79815276802313!2d90.4128057552314!3d23.76047066860609!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3755b9e214dcf989%3A0x38ba85b6e6cbed80!2sBag%20Abdul!5e0!3m2!1sen!2sbd!4v1784616353959!5m2!1sen!2sbd';

    // footer text 
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

<?php
    // ২. ডাটাবেস থেকে বর্তমান সেটিংস লোড করা (ID = 1)
    $sql = "SELECT * FROM branding_settings WHERE id = 1 LIMIT 1";
    $result = mysqli_query($db, $sql);
    $data = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result) : null;

    // ডিফল্ট ভ্যালু সেটআপ
    $site_title   = $data['site_title'] ?? 'Bishwas Education Foundation';
    $site_logo    = !empty($data['site_logo']) ? $data['site_logo'] : 'public/assets/logo_BG.png';
    $favicon_icon = !empty($data['favicon_icon']) ? $data['favicon_icon'] : 'public/assets/logo.png';
?>

<!-- Footer -->
<footer id="contact" class="bg-gray-900 lg:px-10 px-4 text-gray-400 pt-16 pb-8 border-t border-gray-800">
    <div
        class="container mx-auto px-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-12 pb-12 border-b border-gray-800">
        <div class="space-y-4">
            <a href="#" class="text-xl font-bold text-white flex items-center space-x-2">
                <img src="public/assets/<?php echo htmlspecialchars($site_logo); ?>" alt="Logo" class="h-12 rounded-md border border-gray-700">
            </a>
            <p class="text-sm leading-relaxed">
                <?php echo htmlspecialchars($footer_about_text); ?>
            </p>
        </div>

        <div class="space-y-4">
            <h4 class="text-white font-semibold uppercase tracking-wider text-sm">গুরুত্বপূর্ণ লিঙ্ক</h4>
            <ul class="space-y-2 text-sm">
                <li><a href="index.php#about" class="hover:text-emerald-500 transition">আমাদের সম্পর্কে</a></li>
                <li><a href="index.php#ongoing-activities" class="hover:text-emerald-500 transition">চলমান প্রকল্প</a></li>
                <li><a href="index.php#volunteer" class="hover:text-emerald-500 transition">ভলান্টিয়ার হওয়ার জন্য</a></li>
                <li><a href="index.php#photo-gallery" class="hover:text-emerald-500 transition">গ্যালারি ও ভিডিও</a></li>
            </ul>
        </div>

        <div class="space-y-4">
            <h4 class="text-white font-semibold uppercase tracking-wider text-sm">যোগাযোগ করুন</h4>
            <ul class="space-y-2 text-sm">
                <li class="flex items-start space-x-2"><i class="fa-solid fa-location-dot mt-1 text-emerald-500"></i>
                    <span><?php echo htmlspecialchars($office_address); ?></span>
                </li>
                <li class="flex items-center space-x-2"><i class="fa-solid fa-phone text-emerald-500"></i>
                    <span><?php echo htmlspecialchars($phone_number); ?></span>
                </li>
                <li class="flex items-center space-x-2"><i class="fa-solid fa-envelope text-emerald-500"></i>
                    <span><?php echo htmlspecialchars($email_address); ?></span>
                </li>
            </ul>
        </div>

        <div class="space-y-4">
            <h4 class="text-white font-semibold uppercase tracking-wider text-sm">সোশ্যাল মিডিয়া</h4>
            <p class="text-sm"><?php echo htmlspecialchars($footer_social_title); ?></p>
            <div class="flex space-x-4 text-xl">
                <a href="<?php echo htmlspecialchars($facebook_url); ?>" target="_blank"
                    class="hover:text-white text-gray-500 transition"><i class="fa-brands fa-facebook"></i></a>
                <a href="<?php echo htmlspecialchars($youtube_url); ?>" class="hover:text-white text-gray-500 transition"><i class="fa-brands fa-youtube"></i></a>
                <a href="<?php echo htmlspecialchars($twitter_url); ?>" class="hover:text-white text-gray-500 transition"><i class="fa-brands fa-twitter"></i></a>
                <a href="<?php echo htmlspecialchars($linkedin_url); ?>" class="hover:text-white text-gray-500 transition"><i class="fa-brands fa-linkedin"></i></a>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 pt-8 text-center text-sm text-gray-600">
        <p>&copy; 2026 <?php echo htmlspecialchars($site_title); ?>.</p>
    </div>
</footer>
<script src="./assets/main.js"></script>
</body>
</html>