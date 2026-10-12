<?php
include 'database/db.php';
// ২. ডাটাবেস থেকে বর্তমান সেটিংস লোড করা (ID = 1)
$sql = "SELECT * FROM branding_settings WHERE id = 1 LIMIT 1";
$result = mysqli_query($db, $sql);
$data = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result) : null;

// ডিফল্ট ভ্যালু সেটআপ
$site_title = $data['site_title'] ?? 'Bishwas Education Foundation';
$site_logo = !empty($data['site_logo']) ? $data['site_logo'] : 'public/assets/logo_BG.png';
$favicon_icon = !empty($data['favicon_icon']) ? $data['favicon_icon'] : 'public/assets/logo.png';


//header button text 
$sql = "SELECT * FROM site_settings WHERE id = 1 LIMIT 1";
$result = mysqli_query($db, $sql);
$data = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result) : null;

// ইনপুট ফিল্ডের ভ্যালু সেট করা (ডাটা না থাকলে ডিফল্ট মান ব্যবহার হবে)
$donate_btn_text = $data['donate_btn_text'] ?? $default_donate_btn_text;
?>

<?php include 'database/db.php'; ?>
<!DOCTYPE html>
<html lang="bn" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($site_title); ?></title>
    <link rel="icon" href="public/assets/<?php echo htmlspecialchars($favicon_icon); ?>" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap"
        rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>

<body class="bg-gray-50 text-gray-800 scroll-smooth">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="container mx-auto lg:px-10 px-4 py-4 flex justify-between items-center">
            <!-- Logo -->
            <div class="flex items-center space-x-3 space-x-reverse">
                <a href="index.php" class="text-2xl font-bold text-emerald-600 flex items-center space-x-2">
                    <img src="public/assets/<?php echo htmlspecialchars($site_logo); ?>" alt="Logo" class="h-12">
                </a>
            </div>

            <!-- Desktop Navigation Menu -->
            <!-- <nav class="hidden md:flex space-x-8 font-medium text-gray-600">
                <a href="index.php"
                    class="hover:text-emerald-600 transition duration-300 text-emerald-600 font-semibold">মূলপাতা</a>
                <a href="index.php#about" class="hover:text-emerald-600 transition duration-300">আমাদের সম্পর্কে</a>
                <a href="index.php#ongoing-activities" class="hover:text-emerald-600 transition duration-300">চলমান প্রকল্প</a>
                <a href="index.php#volunteer"
                    class="hover:text-emerald-600 transition duration-300">সদস্য নিবন্ধন</a>
                <a href="index.php#contact" class="hover:text-emerald-600 transition duration-300">যোগাযোগ</a>
            </nav> -->

            
            <?php
            // $current_page = basename(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));
            $current_page = basename($_SERVER['SCRIPT_NAME']);
                if ($current_page === '') {
                    $current_page = 'index.php';
            }

            $current_hash = isset($_SERVER['QUERY_STRING'])
                ? ''
                : '';

            $menu_map = [
                'index.php' => [
                    'home',
                    'about',
                    'ongoing-activities',
                    'volunteer',
                    'contact'
                ],
                'volunteer_register.php' => ['volunteer'],
                'member_registration.php' => ['volunteer'],
                'donation.php' => ['donation'],
                'login.php' => ['login'],
                'blog_details.php' => ['about'],
                'project_details.php' => ['ongoing-activities']
            ];

            $active_sections = $menu_map[$current_page] ?? [];

            $donation_active = ($current_page === 'donation.php');
            $login_active = ($current_page === 'login.php');
            ?>

            <!-- Desktop Navigation Menu -->
            <nav id="desktop-nav"
                class="hidden md:flex space-x-8 font-medium text-gray-600">

                <a href="index.php"
                data-section="home"
                class="nav-link hover:text-emerald-600 transition duration-300">
                মূলপাতা
                </a>

                <a href="index.php#about"
                data-section="about"
                class="nav-link hover:text-emerald-600 transition duration-300">
                আমাদের সম্পর্কে
                </a>

                <a href="index.php#ongoing-activities"
                data-section="ongoing-activities"
                class="nav-link hover:text-emerald-600 transition duration-300">
                চলমান প্রকল্প
                </a>

                <a href="index.php#volunteer"
                data-section="volunteer"
                class="nav-link hover:text-emerald-600 transition duration-300">
                সদস্য নিবন্ধন
                </a>

                <a href="index.php#contact"
                data-section="contact"
                class="nav-link hover:text-emerald-600 transition duration-300">
                যোগাযোগ
                </a>
            </nav>

            <!-- Left Side Menu-->
            <div class="flex items-center space-x-3 sm:space-x-4">

                <a href="donation.php"
                title="অনুদান দিন"
                class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-full font-semibold shadow-md transition duration-300 flex items-center space-x-2
                <?php echo $donation_active ? 'ring-2 ring-emerald-300 ring-offset-2' : ''; ?>">

                    <i class="fa-solid fa-heart"></i>
                    <span class="hidden sm:inline">
                        <?php echo htmlspecialchars($donate_btn_text); ?>
                    </span>
                </a>

                <a href="login.php"
                id="profile-btn"
                title="লগইন / প্রোফাইল"
                class="w-10 h-10 flex items-center justify-center rounded-full border transition duration-300
                <?php echo $login_active
                    ? 'bg-emerald-600 text-white border-emerald-600'
                    : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100 border-emerald-200'; ?>">

                    <i class="fa-solid fa-user text-lg"></i>
                </a>

                <button id="menu-btn"
                        type="button"
                        aria-label="মেনু খুলুন"
                        aria-controls="mobile-menu"
                        aria-expanded="false"
                        class="text-gray-700 hover:text-emerald-600 focus:outline-none md:hidden text-2xl">

                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>

            <!-- Dynamic Active Menu -->
            <script>
            (function () {
                const navLinks = document.querySelectorAll('#desktop-nav .nav-link');

                const currentPage = <?php echo json_encode($current_page); ?>;
                const activeSections = <?php echo json_encode($active_sections); ?>;

                function updateActiveMenu() {
                    const hash = window.location.hash.replace('#', '');

                    let activeSection;

                    if (currentPage === 'index.php') {
                        activeSection = hash || 'home';
                    } else {
                        activeSection = activeSections[0] || '';
                    }

                    navLinks.forEach(function (link) {
                        const isActive = link.dataset.section === activeSection;

                        link.classList.toggle('text-emerald-600', isActive);
                        link.classList.toggle('font-semibold', isActive);

                        if (isActive) {
                            link.setAttribute('aria-current', 'page');
                        } else {
                            link.removeAttribute('aria-current');
                        }
                    });
                }

                updateActiveMenu();
                window.addEventListener('hashchange', updateActiveMenu);
            })();
            </script>

        </div>
    </header>

    <!-- mobile sidebar collaps -->
    <div id="sidebar-overlay"
        class="fixed inset-0 bg-black/50 z-50 opacity-0 pointer-events-none transition-opacity duration-300 md:hidden">
    </div>
    <div id="mobile-menu"
        class="fixed top-0 left-0 bottom-0 w-72 bg-white z-50 shadow-2xl transform -translate-x-full transition-transform duration-300 ease-in-out md:hidden flex flex-col">
        <!-- Mobile Menu Header -->
        <div class="p-4 border-b flex justify-between items-center">
            <div class="flex items-center space-x-3 space-x-reverse">
                <a href="#" class="text-2xl font-bold text-emerald-600 flex items-center space-x-2">
                    <img src="public/assets/<?php echo htmlspecialchars($site_logo); ?>" alt="Logo" class="h-12">
                </a>
            </div>
            <button id="close-btn" class="text-gray-500 hover:text-red-600 text-2xl focus:outline-none">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Mobile Menu Links -->
        <nav class="flex flex-col p-6 space-y-4 text-lg font-medium text-gray-700">
            <a href="index.php"
                class="mobile-link hover:text-emerald-600 transition p-2 rounded-lg hover:bg-emerald-50 text-emerald-600 font-semibold">মূলপাতা</a>
            <a href="index.php#about"
                class="mobile-link hover:text-emerald-600 transition p-2 rounded-lg hover:bg-emerald-50">আমাদের
                সম্পর্কে</a>
            <a href="index.php#ongoing-activities"
                class="mobile-link hover:text-emerald-600 transition p-2 rounded-lg hover:bg-emerald-50">চলমান
                প্রকল্প</a>
            <a href="index.php#volunteer"
                class="mobile-link hover:text-emerald-600 transition p-2 rounded-lg hover:bg-emerald-50">ভলান্টিয়ার</a>
            <a href="index.php#contact"
                class="mobile-link hover:text-emerald-600 transition p-2 rounded-lg hover:bg-emerald-50">যোগাযোগ</a>

            <!-- Login / Profile link in mobile menu -->
            <a href="login.php"
                class="mobile-link hover:text-emerald-600 transition p-2 rounded-lg hover:bg-emerald-50 flex items-center gap-2 border-t pt-4 mt-2">
                <i class="fa-solid fa-user"></i>
                <span>লগইন / প্রোফাইল</span>
            </a>
        </nav>
    </div>