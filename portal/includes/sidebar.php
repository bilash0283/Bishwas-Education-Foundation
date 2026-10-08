<?php
    $currentPage = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
    // Active Class যোগ করার জন্য হেলপার ফাংশন
    function getNavClass($pageName, $currentPage) {
        if ($currentPage === $pageName) {
            return 'bg-emerald-600 text-white font-semibold shadow-sm';
        }
        return 'text-slate-600 hover:bg-slate-200/70 hover:text-slate-900';
    }

    function getIconClass($pageName, $currentPage, $defaultColorClass) {
        if ($currentPage === $pageName) {
            return 'text-white';
        }
        return $defaultColorClass;
    }
?>

<div id="sidebarBackdrop" onclick="toggleMobileSidebar()" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-20 hidden lg:hidden"></div>

<!-- SIDEBAR NAVIGATION -->
<aside id="sidebar" class="fixed lg:static inset-y-0 left-0 z-30 w-64 bg-[#F8FAFC] border-r border-slate-200 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out flex flex-col shrink-0 h-full">
    
    <!-- Sidebar for Admin  -->
    <?php if(isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Admin') { ?>
        <div class="flex-1 overflow-y-auto mt-16 lg:mt-0 px-3 py-4 space-y-1 text-xs font-medium">
            <div class="px-3 py-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Main Menu</div>
            
            <a href="?page=dashboard" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('dashboard', $currentPage); ?>">
                <i class="fa-solid fa-chart-pie w-4 text-sm <?php echo getIconClass('dashboard', $currentPage, 'text-emerald-500'); ?>"></i> Dashboard
            </a>

            <a href="?page=view_member&id=<?php echo (int)$_SESSION['user_id']; ?>" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('view_member', $currentPage); ?>">
                <i class="fa-solid fa-user w-4 text-sm <?php echo getIconClass('view_member', $currentPage, 'text-sky-500'); ?>"></i> My Profile
            </a>

            <a href="?page=volunteers" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('volunteers', $currentPage); ?>">
                <i class="fa-solid fa-user-ninja w-4 text-sm <?php echo getIconClass('volunteers', $currentPage, 'text-teal-500'); ?>"></i> Members & Volunteers
            </a>

            <div class="pt-4 px-3 py-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Accounts</div>

            <a href="?page=donation" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('donation', $currentPage); ?>">
                <i class="fa-solid fa-hand-holding-heart w-4 text-sm <?php echo getIconClass('donation', $currentPage, 'text-emerald-500'); ?>"></i> Donation 
            </a>

            <a href="?page=events" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('events', $currentPage); ?>">
                <i class="fa-solid fa-calendar-days w-4 text-sm <?php echo getIconClass('events', $currentPage, 'text-sky-500'); ?>"></i> Events
            </a>

            <a href="?page=expense" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('expense', $currentPage); ?>">
                <i class="fa-solid fa-file-invoice-dollar w-4 text-sm <?php echo getIconClass('expense', $currentPage, 'text-amber-500'); ?>"></i> Service Recipients
            </a>

            <a href="?page=report" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('report', $currentPage); ?>">
                <i class="fa-solid fa-file-invoice-dollar w-4 text-sm <?php echo getIconClass('report', $currentPage, 'text-rose-500'); ?>"></i> Reports
            </a>

            <div class="pt-4 px-3 py-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Management</div>

            <a href="?page=inventory" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('inventory', $currentPage); ?>">
                <i class="fa-solid fa-boxes-stacked w-4 text-sm <?php echo getIconClass('inventory', $currentPage, 'text-indigo-500'); ?>"></i> Inventory Management
            </a>

            <a href="?page=tasks" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('tasks', $currentPage); ?>">
                <i class="fa-solid fa-file-invoice w-4 text-sm <?php echo getIconClass('tasks', $currentPage, 'text-fuchsia-500'); ?>"></i> Tasks Management
            </a>  

            <div class="pt-4 px-3 py-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Others</div>

            <a href="?page=certificates" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('certificates', $currentPage); ?>">
                <i class="fa-solid fa-id-card w-4 text-sm <?php echo getIconClass('certificates', $currentPage, 'text-indigo-500'); ?>"></i> Certificates & ID Cards
            </a>

            <a href="?page=notice_board" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('notice_board', $currentPage); ?>">
                <i class="fa-solid fa-bullhorn w-4 text-sm <?php echo getIconClass('notice_board', $currentPage, 'text-orange-500'); ?>"></i> Notice Board
            </a>

            <a href="?page=meetings" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('meetings', $currentPage); ?>">
                <i class="fa-solid fa-handshake w-4 text-sm <?php echo getIconClass('meetings', $currentPage, 'text-cyan-500'); ?>"></i> Meetings 
            </a>

            <a href="?page=settings" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('settings', $currentPage); ?>">
                <i class="fa-solid fa-gear w-4 text-sm <?php echo getIconClass('settings', $currentPage, 'text-slate-500'); ?>"></i> Settings
            </a>

        </div>
    <?php } ?>

    <!-- Sidebar for Volunteer Member  -->
    <?php if(isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'Volunteer Member') { ?>
        <div class="flex-1 overflow-y-auto mt-16 lg:mt-0 px-3 py-4 space-y-1 text-xs font-medium">
            <div class="px-3 py-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Main Menu</div>
            
            <a href="?page=dashboard" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('dashboard', $currentPage); ?>">
                <i class="fa-solid fa-chart-pie w-4 text-sm <?php echo getIconClass('dashboard', $currentPage, 'text-emerald-500'); ?>"></i> Dashboard
            </a>

            <a href="?page=view_member&id=<?php echo (int)$_SESSION['user_id']; ?>" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('view_member', $currentPage); ?>">
                <i class="fa-solid fa-user w-4 text-sm <?php echo getIconClass('view_member', $currentPage, 'text-sky-500'); ?>"></i> My Profile
            </a>

            <!-- <a href="?page=volunteers" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('volunteers', $currentPage); ?>">
                <i class="fa-solid fa-user-ninja w-4 text-sm <?php echo getIconClass('volunteers', $currentPage, 'text-teal-500'); ?>"></i> Members & Volunteers
            </a> -->

            <div class="pt-4 px-3 py-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Accounts</div>

            <!-- <a href="?page=donation" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('donation', $currentPage); ?>">
                <i class="fa-solid fa-hand-holding-heart w-4 text-sm <?php echo getIconClass('donation', $currentPage, 'text-emerald-500'); ?>"></i> Donation 
            </a> -->

            <a href="?page=events" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('events', $currentPage); ?>">
                <i class="fa-solid fa-calendar-days w-4 text-sm <?php echo getIconClass('events', $currentPage, 'text-sky-500'); ?>"></i> Events
            </a>

            <a href="?page=expense" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('expense', $currentPage); ?>">
                <i class="fa-solid fa-file-invoice-dollar w-4 text-sm <?php echo getIconClass('expense', $currentPage, 'text-amber-500'); ?>"></i> Service Recipients
            </a>

            <!-- <a href="?page=report" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('report', $currentPage); ?>">
                <i class="fa-solid fa-file-invoice-dollar w-4 text-sm <?php echo getIconClass('report', $currentPage, 'text-rose-500'); ?>"></i> Reports
            </a> -->

            <div class="pt-4 px-3 py-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Management</div>

            <a href="?page=inventory" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('inventory', $currentPage); ?>">
                <i class="fa-solid fa-boxes-stacked w-4 text-sm <?php echo getIconClass('inventory', $currentPage, 'text-indigo-500'); ?>"></i> Inventory Management
            </a>

            <a href="?page=tasks" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('tasks', $currentPage); ?>">
                <i class="fa-solid fa-file-invoice w-4 text-sm <?php echo getIconClass('tasks', $currentPage, 'text-fuchsia-500'); ?>"></i> Tasks Management
            </a>  

            <div class="pt-4 px-3 py-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Others</div>

            <a href="?page=certificates" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('certificates', $currentPage); ?>">
                <i class="fa-solid fa-id-card w-4 text-sm <?php echo getIconClass('certificates', $currentPage, 'text-indigo-500'); ?>"></i> Certificates & ID Cards
            </a>

            <a href="?page=notice_board" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('notice_board', $currentPage); ?>">
                <i class="fa-solid fa-bullhorn w-4 text-sm <?php echo getIconClass('notice_board', $currentPage, 'text-orange-500'); ?>"></i> Notice Board
            </a>

            <a href="?page=meetings" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('meetings', $currentPage); ?>">
                <i class="fa-solid fa-handshake w-4 text-sm <?php echo getIconClass('meetings', $currentPage, 'text-cyan-500'); ?>"></i> Meetings 
            </a>

            <a href="?page=settings" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('settings', $currentPage); ?>">
                <i class="fa-solid fa-gear w-4 text-sm <?php echo getIconClass('settings', $currentPage, 'text-slate-500'); ?>"></i> Settings
            </a>

        </div>
    <?php } ?>

    <!-- Sidebar for General Member , Associate Member , Life Member ,   -->
    <?php if(isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'General Member' || $_SESSION['user_type'] === 'Associate Member' || $_SESSION['user_type'] === 'Life Member') { ?>
        <div class="flex-1 overflow-y-auto mt-16 lg:mt-0 px-3 py-4 space-y-1 text-xs font-medium">
            <div class="px-3 py-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Main Menu</div>
            
            <a href="?page=dashboard" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('dashboard', $currentPage); ?>">
                <i class="fa-solid fa-chart-pie w-4 text-sm <?php echo getIconClass('dashboard', $currentPage, 'text-emerald-500'); ?>"></i> Dashboard
            </a>

            <a href="?page=view_member&id=<?php echo (int)$_SESSION['user_id']; ?>" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('view_member', $currentPage); ?>">
                <i class="fa-solid fa-user w-4 text-sm <?php echo getIconClass('view_member', $currentPage, 'text-sky-500'); ?>"></i> My Profile
            </a>

            <div class="pt-4 px-3 py-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Accounts</div>

            <a href="?page=donation" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('donation', $currentPage); ?>">
                <i class="fa-solid fa-hand-holding-heart w-4 text-sm <?php echo getIconClass('donation', $currentPage, 'text-emerald-500'); ?>"></i> Donation 
            </a>

            <a href="?page=events" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('events', $currentPage); ?>">
                <i class="fa-solid fa-calendar-days w-4 text-sm <?php echo getIconClass('events', $currentPage, 'text-sky-500'); ?>"></i> Events
            </a>

            <a href="?page=expense" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('expense', $currentPage); ?>">
                <i class="fa-solid fa-file-invoice-dollar w-4 text-sm <?php echo getIconClass('expense', $currentPage, 'text-amber-500'); ?>"></i> Service Recipients
            </a>

            <a href="?page=report" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('report', $currentPage); ?>">
                <i class="fa-solid fa-file-invoice-dollar w-4 text-sm <?php echo getIconClass('report', $currentPage, 'text-rose-500'); ?>"></i> Reports
            </a>

            <div class="pt-4 px-3 py-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Management</div>

            <a href="?page=inventory" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('inventory', $currentPage); ?>">
                <i class="fa-solid fa-boxes-stacked w-4 text-sm <?php echo getIconClass('inventory', $currentPage, 'text-indigo-500'); ?>"></i> Inventory Management
            </a>

            <a href="?page=tasks" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('tasks', $currentPage); ?>">
                <i class="fa-solid fa-file-invoice w-4 text-sm <?php echo getIconClass('tasks', $currentPage, 'text-fuchsia-500'); ?>"></i> Tasks Management
            </a>  

            <div class="pt-4 px-3 py-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Others</div>

            <a href="?page=certificates" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('certificates', $currentPage); ?>">
                <i class="fa-solid fa-id-card w-4 text-sm <?php echo getIconClass('certificates', $currentPage, 'text-indigo-500'); ?>"></i> Certificates & ID Cards
            </a>

            <a href="?page=notice_board" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('notice_board', $currentPage); ?>">
                <i class="fa-solid fa-bullhorn w-4 text-sm <?php echo getIconClass('notice_board', $currentPage, 'text-orange-500'); ?>"></i> Notice Board
            </a>

            <a href="?page=meetings" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('meetings', $currentPage); ?>">
                <i class="fa-solid fa-handshake w-4 text-sm <?php echo getIconClass('meetings', $currentPage, 'text-cyan-500'); ?>"></i> Meetings 
            </a>

            <a href="?page=settings" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl transition-all <?php echo getNavClass('settings', $currentPage); ?>">
                <i class="fa-solid fa-gear w-4 text-sm <?php echo getIconClass('settings', $currentPage, 'text-slate-500'); ?>"></i> Settings
            </a>

        </div>
    <?php } ?>

    <!-- Sidebar Bottom Logout Footer -->
    <div class="p-3 border-t border-slate-200 shrink-0">
        <a href="?page=logout" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-rose-600 hover:bg-rose-500/10 transition-colors text-xs font-semibold">
            <i class="fa-solid fa-right-from-bracket w-4 text-sm"></i> Sign Out
        </a>
    </div>
</aside>