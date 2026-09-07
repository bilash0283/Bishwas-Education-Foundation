<?php 
    include_once '../component/session_manage.php';
    if (!isset($_SESSION['cms_admin_id']) || $_SESSION['cms_admin_id'] !== true) {
        header("Location: index.php");
        exit();
    }

    include '../database/db.php';
    // ২. ডাটাবেস থেকে বর্তমান সেটিংস লোড করা (ID = 1)
    $sql = "SELECT * FROM branding_settings WHERE id = 1 LIMIT 1";
    $result = mysqli_query($db, $sql);
    $data = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result) : null;

    // ডিফল্ট ভ্যালু সেটআপ
    $site_title   = $data['site_title'] ?? 'Bishwas Education Foundation';
    $site_logo    = !empty($data['site_logo']) ? $data['site_logo'] : 'public/assets/logo_BG.png';
    $favicon_icon = !empty($data['favicon_icon']) ? $data['favicon_icon'] : 'public/assets/logo.png';
?>

<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo htmlspecialchars($site_title); ?></title>
    <link rel="icon" href="../public/assets/<?php echo $favicon_icon; ?>" type="image/png">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Google Font (Inter) -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
    </style>
</head>
<body class="h-full overflow-hidden text-slate-800 bg-slate-50">
    <!-- Main Wrapper -->
    <div class="h-full flex flex-col">
        <!-- Fixed Top Header -->
        <header class="bg-white/95 backdrop-blur-md border-b border-slate-200/80 fixed top-0 left-0 right-0 z-50 shadow-sm h-[65px]">
            <div class="px-4 sm:px-6 lg:px-8 h-full flex items-center justify-between">
                <!-- Left Header: Mobile Toggle & Enhanced Logo -->
                <div class="flex items-center gap-3">
                    <button id="sidebarToggle" class="p-2 rounded-lg text-slate-600 hover:bg-slate-100 lg:hidden focus:outline-none transition-colors">
                        <i class="fa-solid fa-bars text-xl"></i>
                    </button>
                    
                    <a href="?page=dashboard" class="flex items-center gap-3.5 group py-1">
                        <!-- Clean Rounded Logo Container Without Active Signal -->
                        <div class="w-11 h-11 shrink-0 rounded-full overflow-hidden flex items-center justify-center border border-slate-200/60 shadow-sm transition-all duration-300 group-hover:scale-105 group-hover:shadow-md group-hover:border-emerald-500/40">
                            <img 
                                src="../public/assets/<?php echo $favicon_icon; ?>" 
                                alt="Logo" 
                                class="w-full h-full object-cover filter drop-shadow-sm transition-transform duration-300 group-hover:scale-110" 
                                onerror="this.onerror=null; this.src='../public/assets/<?php echo $favicon_icon; ?>';"
                            >
                        </div>

                        <!-- Typography & Branding -->
                        <div class="hidden sm:flex flex-col justify-center">
                            <h1 class="font-bold text-xl leading-none tracking-tight text-slate-800 group-hover:text-emerald-600 transition-colors duration-200">
                                bishwas<span class="text-emerald-600 font-black">.org</span>
                            </h1>
                            <span class="text-[10px] text-slate-400 font-semibold tracking-widest uppercase mt-1">
                                Foundation Admin
                            </span>
                        </div>
                    </a>
                </div>

                <!-- Right Header: Actions & User Profile -->
                <div class="flex items-center gap-4">
                    <!-- Global Add Button -->
                    <!-- <button onclick="openContentModal()" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-medium px-4 py-2 rounded-lg shadow-sm transition-all duration-200">
                        <i class="fa-solid fa-plus text-xs"></i>
                        <span class="hidden sm:inline">Add Content</span>
                    </button> -->
                    <!-- User Profile -->
                    <div class="flex items-center gap-3 pl-3 border-l border-slate-200">
                        <img class="w-9 h-9 rounded-full object-cover border-2 border-emerald-500 shadow-sm" src="https://ui-avatars.com/api/?name=Bilash+Vai&background=059669&color=fff" alt="User Avatar">
                        <div class="hidden md:block text-left">
                            <p class="text-sm font-semibold leading-none text-slate-700">Admin</p>
                            <p class="text-xs text-slate-500 mt-0.5">Super Admin</p>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Body Layout (Offset for fixed header) -->
        <div class="flex-1 flex pt-[65px] h-full overflow-hidden">
            <!-- Sidebar Overlay for Mobile -->
            <div id="sidebarOverlay" onclick="toggleSidebar()" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm z-40 hidden lg:hidden"></div>
            <!-- sidebar  -->
            <?php include_once 'pages/sidebar.php'; ?>
            <!-- main content area start-->
             
            <main class="flex-1 overflow-y-auto h-[calc(100vh-65px)] flex flex-col justify-between">
                <?php 
                    if(isset($_GET['page']) && !empty($_GET['page'])) {
                        $page = $_GET['page'];
                        $allowed_pages = ['dashboard', 'hero_section', 'donation', 'about', 'volunteer', 'blogs', 'projects', 'media', 'contacts', 'header-footer', 'logo-branding', 'profile-settings', 'site-settings'];
                        if (in_array($page, $allowed_pages)) {
                            include_once "pages/{$page}.php";
                        } else {
                            echo "<main class='flex-1 overflow-y-auto h-[calc(100vh-65px)] flex flex-col justify-center items-center text-slate-500'>
                                    <h2 class='text-lg font-semibold'>404 - Page Not Found</h2>
                                    <p class='mt-2'>The requested page does not exist.</p>
                                </main>";
                        }
                    } else {
                        include_once "pages/dashboard.php?page=dashboard";
                    }
                ?>

                <!-- Dashboard Footer inside main scroll area -->
                <footer class="bg-white border-t border-slate-200 py-4 px-6 text-center text-xs text-slate-500 mt-auto">
                    <p>&copy; 2026 <?php echo htmlspecialchars($site_title); ?> - All Rights Reserved.</p>
                </footer>
            </main>
        </div>
    </div>
</body>
</html>