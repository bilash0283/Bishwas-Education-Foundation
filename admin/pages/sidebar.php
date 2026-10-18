<?php
    // main file থেকে $current_page আসে; সরাসরি include হলেও যেন error না হয়
    if (!isset($current_page)) {
        $current_page = (isset($_GET['page']) && !empty($_GET['page'])) ? $_GET['page'] : 'dashboard';
    }
    $active   = 'bg-emerald-50 text-emerald-700 font-semibold';
    $inactive = 'text-slate-600 hover:bg-slate-100 font-medium';
?>
<!-- Independent Scrollable Sidebar -->
<aside id="sidebar"
    class="fixed lg:static inset-y-0 left-0 top-[65px] z-40 w-64 shrink-0 bg-white border-r border-slate-200 shadow-xl lg:shadow-none transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out flex flex-col justify-between h-[calc(100vh-65px)]">
    <div class="p-4 space-y-6 overflow-y-auto flex-1 custom-scrollbar">

        <!-- Main Menu -->
        <div>
            <p class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Main Menu</p>
            <nav class="space-y-1">
                <a href="?page=dashboard"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors <?php echo $current_page === 'dashboard' ? $active : $inactive; ?>">
                    <i class="fa-solid fa-chart-pie w-5 text-center"></i>
                    Dashboard
                </a>

                <a href="?page=hero_section"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors <?php echo $current_page === 'hero_section' ? $active : $inactive; ?>">
                    <i class="fa-solid fa-newspaper w-5 text-center"></i>
                    Hero Section
                </a>

                <a href="?page=projects"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors <?php echo $current_page === 'projects' ? $active : $inactive; ?>">
                    <i class="fa-solid fa-diagram-project w-5 text-center"></i>
                    Projects
                </a>

                <a href="?page=donation"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors <?php echo $current_page === 'donation' ? $active : $inactive; ?>">
                    <i class="fa-solid fa-hand-holding-heart w-5 text-center"></i>
                    Donation
                </a>

                <a href="?page=about"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors <?php echo $current_page === 'about' ? $active : $inactive; ?>">
                    <i class="fa-solid fa-circle-info w-5 text-center"></i>
                    About Us
                </a>

                <a href="?page=volunteer"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors <?php echo $current_page === 'volunteer' ? $active : $inactive; ?>">
                    <i class="fa-solid fa-hands-helping w-5 text-center"></i>
                    Members
                </a>

                <a href="?page=media"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors <?php echo $current_page === 'media' ? $active : $inactive; ?>">
                    <i class="fa-solid fa-images w-5 text-center"></i>
                    Media Gallery
                </a>

                <a href="?page=blogs"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors <?php echo $current_page === 'blogs' ? $active : $inactive; ?>">
                    <i class="fa-solid fa-newspaper w-5 text-center"></i>
                    Blog Posts
                </a>

                <a href="?page=contacts"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors <?php echo $current_page === 'contacts' ? $active : $inactive; ?>">
                    <i class="fa-solid fa-address-book w-5 text-center"></i>
                    Contacts
                </a>
            </nav>
        </div>

        <!-- Management Section -->
        <div>
            <p class="px-3 text-xs font-semibold text-slate-400 uppercase tracking-wider mb-2">Settings & Control</p>
            <nav class="space-y-1">
                <a href="?page=header-footer"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors <?php echo $current_page === 'header-footer' ? $active : $inactive; ?>">
                    <i class="fa-solid fa-circle-info w-5 text-center"></i>
                    Header & Footer
                </a>
                <a href="?page=logo-branding"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors <?php echo $current_page === 'logo-branding' ? $active : $inactive; ?>">
                    <i class="fa-solid fa-image w-5 text-center"></i>
                    Logo & Branding
                </a>
                <!--
                <a href="?page=profile-settings"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors <?php echo $current_page === 'profile-settings' ? $active : $inactive; ?>">
                    <i class="fa-solid fa-user-gear w-5 text-center"></i>
                    Profile Settings
                </a>
                <a href="?page=site-settings"
                    class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm transition-colors <?php echo $current_page === 'site-settings' ? $active : $inactive; ?>">
                    <i class="fa-solid fa-sliders w-5 text-center"></i>
                    Site Settings
                </a>
                -->
            </nav>
        </div>

    </div>

    <!-- Sidebar Logout -->
    <div class="p-4 border-t border-slate-200 bg-white">
        <a href="../component/logout.php"
            class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-rose-600 hover:bg-rose-50 font-medium text-sm transition-colors">
            <i class="fa-solid fa-right-from-bracket w-5 text-center"></i>
            Logout
        </a>
    </div>
</aside>