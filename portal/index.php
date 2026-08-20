<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bishwas Foundation - Admin Portal</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Custom Scrollbar Styling for Sidebar and Main Workspace */
        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(241, 245, 249, 0.5);
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #059669;
        }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased h-screen overflow-hidden flex flex-col">

    <!-- 1. FIXED TOP HEADER -->
    <header class="h-16 bg-white border-b border-slate-200/80 shadow-xs z-40 shrink-0 flex items-center justify-between px-4 lg:px-6">
        
        <!-- Left: Mobile Menu & Logo -->
        <div class="flex items-center gap-3">
            <button onclick="toggleMobileSidebar()" class="lg:hidden p-2 text-slate-600 hover:text-emerald-600 hover:bg-slate-100 rounded-lg transition-colors">
                <i class="fa-solid fa-bars text-lg"></i>
            </button>

            <a href="#" class="flex items-center gap-3 group">
                <div class="w-10 h-10 shrink-0 rounded-full overflow-hidden flex items-center justify-center border border-slate-200 shadow-xs group-hover:scale-105 transition-all">
                    <img src="../public/assets/logo.png" alt="Logo" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='https://via.placeholder.com/40?text=BF';">
                </div>
                <div class="hidden sm:block">
                    <h1 class="font-bold text-lg leading-none tracking-tight text-slate-800 group-hover:text-emerald-600 transition-colors">
                        bishwas<span class="text-emerald-600 font-black">.org</span>
                    </h1>
                    <span class="text-[10px] text-slate-400 font-semibold tracking-wider uppercase block mt-0.5">
                        Foundation Portal
                    </span>
                </div>
            </a>
        </div>

        <!-- Middle: Global Search -->
        <div class="hidden md:flex items-center flex-1 max-w-md mx-8">
            <div class="relative w-full">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </span>
                <input type="text" placeholder="Search records, volunteers, or transactions..." class="w-full pl-9 pr-4 py-2 bg-slate-100/70 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-emerald-500 focus:bg-white transition-all">
            </div>
        </div>

        <!-- Right: Actions & User Dropdown -->
        <div class="flex items-center gap-3">
            <button class="relative p-2 text-slate-500 hover:text-emerald-600 hover:bg-slate-100 rounded-xl transition-colors">
                <i class="fa-regular fa-bell text-lg"></i>
                <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-emerald-500 rounded-full ring-2 ring-white"></span>
            </button>

            <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>

            <div class="flex items-center gap-3 pl-1">
                <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-emerald-600 to-teal-500 text-white font-bold text-sm flex items-center justify-center shadow-md shadow-emerald-600/20">
                    A
                </div>
                <div class="hidden lg:block text-left">
                    <h4 class="text-xs font-bold text-slate-800 leading-tight">Admin User</h4>
                    <span class="text-[10px] text-emerald-600 font-semibold">Super Admin</span>
                </div>
            </div>
        </div>
    </header>

    <!-- MAIN BODY WORKSPACE (INDEPENDENT SCROLL LAYOUT) -->
    <div class="flex-1 flex overflow-hidden relative">
        <!-- Mobile Drawer Backdrop Overlay -->
        <div id="sidebarBackdrop" onclick="toggleMobileSidebar()" class="fixed inset-0 bg-slate-900/40 backdrop-blur-xs z-20 hidden lg:hidden"></div>

        <!-- 2. SIDEBAR NAVIGATION (INDEPENDENT SCROLL - SLATE LIGHT/PROFESSIONAL) -->
        <aside id="sidebar" class="fixed lg:static inset-y-0 left-0 z-30 w-64 bg-slate-900/95 lg:bg-slate-900 text-slate-300 border-r border-slate-800 transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out flex flex-col shrink-0 h-full">
            <!-- Sidebar Navigation Links with Independent Scroll -->
            <div class="flex-1 overflow-y-auto mt-16 lg:mt-0 px-3 py-4 space-y-1 text-xs font-medium">
                
                <div class="px-3 py-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Main Menu</div>

                <button onclick="switchTab('dashboard')" id="nav-dashboard" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl bg-emerald-600 text-white font-semibold shadow-sm transition-all">
                    <i class="fa-solid fa-chart-pie w-4 text-sm"></i> Dashboard Overview
                </button>

                <button onclick="switchTab('beneficiaries')" id="nav-beneficiaries" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-slate-200 transition-all">
                    <i class="fa-solid fa-hand-holding-heart w-4 text-sm text-emerald-400"></i> Service Beneficiaries
                </button>

                <button onclick="switchTab('volunteers')" id="nav-volunteers" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-slate-200 transition-all">
                    <i class="fa-solid fa-user-ninja w-4 text-sm text-teal-400"></i> Volunteers Team
                </button>

                <button onclick="switchTab('members')" id="nav-members" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-slate-200 transition-all">
                    <i class="fa-solid fa-users-rectangle w-4 text-sm text-sky-400"></i> Members & Donors
                </button>

                <div class="pt-4 px-3 py-2 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Management</div>

                <button onclick="switchTab('projects')" id="nav-projects" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-slate-200 transition-all">
                    <i class="fa-solid fa-folder-open w-4 text-sm text-amber-400"></i> Projects & Funds
                </button>

                <button onclick="switchTab('reports')" id="nav-reports" class="nav-item w-full flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-slate-400 hover:bg-slate-800 hover:text-slate-200 transition-all">
                    <i class="fa-solid fa-file-invoice-dollar w-4 text-sm text-rose-400"></i> Reports & Audits
                </button>
            </div>

            <!-- Sidebar Bottom Logout Footer -->
            <div class="p-3 border-t border-slate-800 shrink-0">
                <a href="#" class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-rose-400 hover:bg-rose-500/10 transition-colors text-xs font-semibold">
                    <i class="fa-solid fa-right-from-bracket w-4 text-sm"></i> Sign Out
                </a>
            </div>
        </aside>

        <!-- 3. MAIN CONTENT AREA (INDEPENDENT SCROLL) -->
        <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8 bg-slate-50">
            <!-- PAGE 1: DASHBOARD OVERVIEW PAGE -->
            <section id="page-dashboard" class="page-content space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Executive Dashboard</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Real-time stats and foundation activity metrics.</p>
                    </div>
                    <button onclick="openAddModal('Beneficiary')" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-emerald-600/20 flex items-center gap-2 transition-all self-start sm:self-auto">
                        <i class="fa-solid fa-plus"></i> Add New Record
                    </button>
                </div>

                <!-- Stats Grid -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="p-5 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0"><i class="fa-solid fa-hand-holding-heart"></i></div>
                        <div>
                            <p class="text-[11px] font-bold text-slate-400 uppercase">Beneficiaries</p>
                            <h3 class="text-2xl font-bold text-slate-800 mt-0.5">1,700+</h3>
                        </div>
                    </div>
                    <div class="p-5 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl shrink-0"><i class="fa-solid fa-user-ninja"></i></div>
                        <div>
                            <p class="text-[11px] font-bold text-slate-400 uppercase">Active Volunteers</p>
                            <h3 class="text-2xl font-bold text-slate-800 mt-0.5">100+</h3>
                        </div>
                    </div>
                    <div class="p-5 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl shrink-0"><i class="fa-solid fa-users"></i></div>
                        <div>
                            <p class="text-[11px] font-bold text-slate-400 uppercase">Active Donors</p>
                            <h3 class="text-2xl font-bold text-slate-800 mt-0.5">450</h3>
                        </div>
                    </div>
                    <div class="p-5 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
                        <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shrink-0"><i class="fa-solid fa-box-archive"></i></div>
                        <div>
                            <p class="text-[11px] font-bold text-slate-400 uppercase">Active Projects</p>
                            <h3 class="text-2xl font-bold text-slate-800 mt-0.5">10+</h3>
                        </div>
                    </div>
                </div>

                <!-- Recent Submissions Table -->
                <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="font-bold text-slate-800 text-sm">Recent Activity & Submissions</h3>
                        <span class="text-xs text-emerald-600 font-medium">Live Updates</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600">
                            <thead class="bg-slate-50 text-slate-700 uppercase font-bold border-b border-slate-200">
                                <tr>
                                    <th class="p-4">Applicant / Member</th>
                                    <th class="p-4">Category</th>
                                    <th class="p-4">Contact</th>
                                    <th class="p-4">Status</th>
                                    <th class="p-4 text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                <tr class="hover:bg-slate-50/80">
                                    <td class="p-4 font-semibold text-slate-800">Billal Hossain <span class="block text-[10px] font-normal text-slate-400">Mirpur, Dhaka</span></td>
                                    <td class="p-4"><span class="px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 font-bold text-[10px]">Beneficiary</span></td>
                                    <td class="p-4">+8801700000000</td>
                                    <td class="p-4"><span class="px-2.5 py-1 rounded-md bg-amber-50 text-amber-700 font-bold text-[10px]">Pending</span></td>
                                    <td class="p-4 text-right space-x-1">
                                        <button onclick="openDeleteModal('Billal Hossain')" class="p-1.5 text-slate-400 hover:text-rose-600 transition-colors"><i class="fa-solid fa-trash"></i></button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <!-- PAGE 2: BENEFICIARIES PAGE -->
            <section id="page-beneficiaries" class="page-content hidden space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800">Service Beneficiaries</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Manage individuals and families receiving support.</p>
                    </div>
                    <button onclick="openAddModal('Beneficiary')" class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-emerald-600/20 flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i> Add Beneficiary
                    </button>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
                    <p class="text-xs text-slate-500">Beneficiary Management Directory loaded.</p>
                </div>
            </section>

            <!-- PAGE 3: VOLUNTEERS PAGE -->
            <section id="page-volunteers" class="page-content hidden space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800">Volunteers Roster</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Active volunteer management and task assignments.</p>
                    </div>
                    <button onclick="openAddModal('Volunteer')" class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-teal-600/20 flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i> Register Volunteer
                    </button>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
                    <p class="text-xs text-slate-500">Volunteers team records and deployment details.</p>
                </div>
            </section>

            <!-- PAGE 4: MEMBERS & DONORS PAGE -->
            <section id="page-members" class="page-content hidden space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800">Members & Donors</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Foundation sponsors, recurring donors and members.</p>
                    </div>
                    <button onclick="openAddModal('Member')" class="px-4 py-2.5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-sky-600/20 flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i> Add New Donor
                    </button>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
                    <p class="text-xs text-slate-500">Donors directory and subscription history.</p>
                </div>
            </section>

            <!-- PAGE 5: PROJECTS PAGE -->
            <section id="page-projects" class="page-content hidden space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800">Projects & Sector Funds</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Orphanage support, Tree planting, Emergency relief.</p>
                    </div>
                    <button onclick="openAddModal('Project')" class="px-4 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-amber-600/20 flex items-center gap-2">
                        <i class="fa-solid fa-plus"></i> Create New Project
                    </button>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
                    <p class="text-xs text-slate-500">Active campaigns and allocated budget track.</p>
                </div>
            </section>

            <!-- PAGE 6: REPORTS PAGE -->
            <section id="page-reports" class="page-content hidden space-y-6">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800">Financial Reports & Audit</h2>
                        <p class="text-xs text-slate-500 mt-0.5">Generate transparent donation statements and operational audits.</p>
                    </div>
                </div>
                <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
                    <p class="text-xs text-slate-500">Exportable CSV & PDF financial statements.</p>
                </div>
            </section>

        </main>
    </div>

    <!-- 4. ADD / CREATE RECORD MODAL -->
    <div id="addModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center p-4 z-50">
        <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden border border-slate-100">
            <div class="p-5 bg-slate-900 text-white flex justify-between items-center">
                <h3 class="font-bold text-sm flex items-center gap-2">
                    <i class="fa-solid fa-square-plus text-emerald-400"></i>
                    <span id="modalTypeTitle">Add Record</span>
                </h3>
                <button onclick="closeAddModal()" class="text-slate-400 hover:text-white"><i class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <form class="p-6 space-y-4" onsubmit="event.preventDefault(); closeAddModal();">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Full Name</label>
                    <input type="text" placeholder="Enter complete name" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-emerald-500">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Phone Number</label>
                        <input type="text" placeholder="+8801XXXXXXXXX" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Category / Role</label>
                        <select class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                            <option value="Beneficiary">Beneficiary</option>
                            <option value="Volunteer">Volunteer</option>
                            <option value="Member">Donor / Member</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Address / Note</label>
                    <textarea rows="3" placeholder="Enter location details..." class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500"></textarea>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" onclick="closeAddModal()" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                    <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700 shadow-sm">Save Entry</button>
                </div>
            </form>
        </div>
    </div>

    <!-- 5. DELETE CONFIRMATION MODAL -->
    <div id="deleteModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center p-4 z-50">
        <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl p-6 text-center border border-slate-100">
            <div class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center text-xl mx-auto mb-4">
                <i class="fa-solid fa-triangle-exclamation"></i>
            </div>
            <h3 class="font-bold text-slate-800 text-base">Confirm Deletion</h3>
            <p class="text-xs text-slate-500 mt-1">Are you sure you want to remove <span id="deleteTargetName" class="font-bold text-slate-700"></span>? This action cannot be undone.</p>
            <div class="flex justify-center gap-3 mt-6">
                <button onclick="closeDeleteModal()" class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                <button onclick="closeDeleteModal()" class="px-4 py-2 bg-rose-600 text-white rounded-xl text-xs font-semibold hover:bg-rose-700 shadow-sm">Delete</button>
            </div>
        </div>
    </div>

    <!-- CLIENT INTERACTIVE JAVASCRIPT -->
    <script>
        // SPA Page Switcher Logic
        function switchTab(tabId) {
            // Hide all pages
            document.querySelectorAll('.page-content').forEach(page => page.classList.add('hidden'));
            
            // Remove active classes from navigation items
            document.querySelectorAll('.nav-item').forEach(btn => {
                btn.classList.remove('bg-emerald-600', 'text-white', 'font-semibold', 'shadow-sm');
                btn.classList.add('text-slate-400', 'hover:bg-slate-800', 'hover:text-slate-200');
            });

            // Show current target page
            document.getElementById(`page-${tabId}`).classList.remove('hidden');

            // Set current nav tab style
            const activeNav = document.getElementById(`nav-${tabId}`);
            activeNav.classList.add('bg-emerald-600', 'text-white', 'font-semibold', 'shadow-sm');
            activeNav.classList.remove('text-slate-400', 'hover:bg-slate-800', 'hover:text-slate-200');

            // Auto-close drawer on mobile
            if (window.innerWidth < 1024) {
                toggleMobileSidebar();
            }
        }

        // Mobile Responsive Navigation Drawer Toggle
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            sidebar.classList.toggle('-translate-x-full');
            backdrop.classList.toggle('hidden');
        }

        // Modal Helpers
        function openAddModal(type) {
            document.getElementById('modalTypeTitle').innerText = `Add New ${type}`;
            const modal = document.getElementById('addModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeAddModal() {
            const modal = document.getElementById('addModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }

        function openDeleteModal(name) {
            document.getElementById('deleteTargetName').innerText = name;
            const modal = document.getElementById('deleteModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeDeleteModal() {
            const modal = document.getElementById('deleteModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
        }
    </script>
</body>
</html>