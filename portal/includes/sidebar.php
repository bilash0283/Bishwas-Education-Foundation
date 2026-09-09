<div id="sidebarBackdrop" onclick="toggleMobileSidebar()" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-20 hidden lg:hidden"></div>
        <!-- 2. SIDEBAR NAVIGATION -->
        <aside id="sidebar" class="fixed lg:static inset-y-0 left-0 z-30 w-64 bg-[#F8FAFC] border-r border-slate-200 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out flex flex-col shrink-0 h-full">
            <!-- Sidebar Navigation Links -->
            <div class="flex-1 overflow-y-auto mt-16 lg:mt-0 px-3 py-4 space-y-1 text-xs font-medium">
                <div class="px-3 py-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Main Menu</div>
                
                <a href="?page=dashboard" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl bg-emerald-600 text-white font-semibold shadow-sm transition-all">
                    <i class="fa-solid fa-chart-pie w-4 text-sm"></i> Dashboard Overview
                </a>

                <a href="?page=beneficiaries" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-600 hover:bg-slate-200/70 hover:text-slate-900 transition-all">
                    <i class="fa-solid fa-hand-holding-heart w-4 text-sm text-emerald-500"></i> Service Beneficiaries
                </a>

                <a href="?page=volunteers" class="text-slate-600 hover:bg-slate-200/70 hover:text-slate-900 nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-600 hover:bg-slate-200/70 hover:text-slate-900 transition-all">
                    <i class="fa-solid fa-user-ninja w-4 text-sm text-teal-500"></i> Volunteers Team
                </a>

                <a href="?page=members" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-600 hover:bg-slate-200/70 hover:text-slate-900 transition-all">
                    <i class="fa-solid fa-users-rectangle w-4 text-sm text-sky-500"></i> Members & Donors
                </a>

                <div class="pt-4 px-3 py-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Management</div>

                <a href="?page=projects" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-600 hover:bg-slate-200/70 hover:text-slate-900 transition-all">
                    <i class="fa-solid fa-folder-open w-4 text-sm text-amber-500"></i> Projects & Funds
                </a>

                <a href="?page=reports" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-600 hover:bg-slate-200/70 hover:text-slate-900 transition-all">
                    <i class="fa-solid fa-file-invoice-dollar w-4 text-sm text-rose-500"></i> Reports & Audits
                </a>
            </div>

            <!-- Sidebar Bottom Logout Footer -->
            <div class="p-3 border-t border-slate-200 shrink-0">
                <a href="#" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-rose-600 hover:bg-rose-500/10 transition-colors text-xs font-semibold">
                    <i class="fa-solid fa-right-from-bracket w-4 text-sm"></i> Sign Out
                </a>
            </div>
        </aside>