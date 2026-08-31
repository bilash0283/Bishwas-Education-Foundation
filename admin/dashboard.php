<?php 
    include_once '../component/session_manage.php';
    if (!isset($_SESSION['cms_admin_id']) || $_SESSION['cms_admin_id'] !== true) {
        header("Location: index.php");
        exit();
    }
?>

<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Bishwas Education Foundation</title>
    <link rel="icon" href="../public/assets/logo.png" type="image/png">
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
                                src="../public/assets/logo.png" 
                                alt="Logo" 
                                class="w-full h-full object-cover filter drop-shadow-sm transition-transform duration-300 group-hover:scale-110" 
                                onerror="this.onerror=null; this.src='https://via.placeholder.com/44?text=BF';"
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
                        $allowed_pages = ['dashboard', 'hero_section', 'donation', 'about', 'volunteer', 'blog', 'projects', 'media', 'contacts', 'header-footer', 'logo-branding', 'profile-settings', 'site-settings'];
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
                    <p>&copy; 2026 bishwas.org - All Rights Reserved. Powered by Bishwas Foundation.</p>
                </footer>
            </main>
        </div>
    </div>

    <!-- ================= ADD CONTENT MODAL ================= -->
    <div id="contentModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm hidden p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl overflow-hidden">
            
            <div class="bg-slate-50 px-6 py-4 border-b border-slate-200 flex items-center justify-between">
                <h3 class="font-bold text-lg text-slate-800"><i class="fa-solid fa-pen-to-square text-emerald-600 mr-2"></i>Add New Content</h3>
                <button onclick="closeContentModal()" class="text-slate-400 hover:text-slate-600 text-lg p-1">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form action="#" method="POST" class="p-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Content Title *</label>
                    <input type="text" placeholder="Enter content title..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Type *</label>
                        <select class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="blog">Blog Post</option>
                            <option value="project">Project</option>
                            <option value="event">Event</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Status *</label>
                        <select class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                            <option value="published">Published</option>
                            <option value="draft">Draft</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Featured Image *</label>
                    <input type="file" class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
                    <textarea rows="4" placeholder="Write content details..." class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
                </div>

                <div class="pt-4 border-t border-slate-100 flex items-center justify-end gap-3">
                    <button type="button" onclick="closeContentModal()" class="px-4 py-2.5 rounded-lg text-sm font-medium text-slate-600 hover:bg-slate-100">Cancel</button>
                    <button type="submit" class="px-5 py-2.5 rounded-lg text-sm font-semibold bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm">Save & Publish</button>
                </div>
            </form>
        </div>
    </div>

    <!-- ================= DELETE CONFIRMATION MODAL ================= -->
    <div id="deleteModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm hidden p-4">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-md p-6 text-center transform transition-all">
            
            <!-- Warning Icon -->
            <div class="w-14 h-14 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>

            <h3 class="text-lg font-bold text-slate-800">Confirm Delete</h3>
            <p class="text-sm text-slate-500 mt-2">Are you sure you want to delete <span id="deleteItemTitle" class="font-semibold text-slate-700">this item</span>? This action cannot be undone.</p>

            <!-- Actions -->
            <div class="mt-6 flex items-center justify-center gap-3">
                <button type="button" onclick="closeDeleteModal()" class="w-1/2 py-2.5 border border-slate-300 rounded-lg text-sm font-medium text-slate-700 hover:bg-slate-50 transition-colors">
                    Cancel
                </button>
                <button type="button" onclick="confirmDeleteAction()" class="w-1/2 py-2.5 bg-rose-600 hover:bg-rose-700 text-white rounded-lg text-sm font-semibold shadow-sm transition-colors">
                    Yes, Delete
                </button>
            </div>
        </div>
    </div>

    <!-- JavaScript Controls -->
    <script>
        // Sidebar Controls
        const sidebar = document.getElementById('sidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        const sidebarToggle = document.getElementById('sidebarToggle');

        function toggleSidebar() {
            sidebar.classList.toggle('-translate-x-full');
            sidebarOverlay.classList.toggle('hidden');
        }
        sidebarToggle.addEventListener('click', toggleSidebar);

        // Content Modal Controls
        const contentModal = document.getElementById('contentModal');
        function openContentModal() { contentModal.classList.remove('hidden'); }
        function closeContentModal() { contentModal.classList.add('hidden'); }

        // Delete Modal Controls
        const deleteModal = document.getElementById('deleteModal');
        const deleteItemTitle = document.getElementById('deleteItemTitle');

        function openDeleteModal(title) {
            deleteItemTitle.innerText = `"${title}"`;
            deleteModal.classList.remove('hidden');
        }

        function closeDeleteModal() {
            deleteModal.classList.add('hidden');
        }

        function confirmDeleteAction() {
            alert('Item successfully deleted.');
            closeDeleteModal();
        }
    </script>
</body>
</html>