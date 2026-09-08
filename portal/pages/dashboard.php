<!-- PAGE 1: DASHBOARD OVERVIEW PAGE -->
<section id="page-dashboard" class="page-content space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Executive Dashboard</h2>
            <p class="text-xs text-slate-500 mt-0.5">Real-time stats and foundation activity metrics.</p>
        </div>
        <button onclick="openAddModal('Beneficiary')"
            class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-emerald-600/20 flex items-center gap-2 transition-all self-start sm:self-auto">
            <i class="fa-solid fa-plus"></i> Add New Record
        </button>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-5 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div
                class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-hand-holding-heart"></i></div>
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase">Beneficiaries</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-0.5">1,700+</h3>
            </div>
        </div>
        <div class="p-5 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div
                class="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-user-ninja"></i></div>
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase">Active Volunteers</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-0.5">100+</h3>
            </div>
        </div>
        <div class="p-5 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-users"></i></div>
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase">Active Donors</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-0.5">450</h3>
            </div>
        </div>
        <div class="p-5 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div
                class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-box-archive"></i></div>
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
                        <td class="p-4 font-semibold text-slate-800">Billal Hossain <span
                                class="block text-[10px] font-normal text-slate-400">Mirpur, Dhaka</span></td>
                        <td class="p-4"><span
                                class="px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 font-bold text-[10px]">Beneficiary</span>
                        </td>
                        <td class="p-4">+8801700000000</td>
                        <td class="p-4"><span
                                class="px-2.5 py-1 rounded-md bg-amber-50 text-amber-700 font-bold text-[10px]">Pending</span>
                        </td>
                        <td class="p-4 text-right space-x-1">
                            <button onclick="openDeleteModal('Billal Hossain')"
                                class="p-1.5 text-slate-400 hover:text-rose-600 transition-colors"><i
                                    class="fa-solid fa-trash"></i></button>
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
        <button onclick="openAddModal('Beneficiary')"
            class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-emerald-600/20 flex items-center gap-2">
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
        <button onclick="openAddModal('Volunteer')"
            class="px-4 py-2.5 bg-teal-600 hover:bg-teal-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-teal-600/20 flex items-center gap-2">
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
        <button onclick="openAddModal('Member')"
            class="px-4 py-2.5 bg-sky-600 hover:bg-sky-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-sky-600/20 flex items-center gap-2">
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
        <button onclick="openAddModal('Project')"
            class="px-4 py-2.5 bg-amber-600 hover:bg-amber-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-amber-600/20 flex items-center gap-2">
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
            <p class="text-xs text-slate-500 mt-0.5">Generate transparent donation statements and operational audits.
            </p>
        </div>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
        <p class="text-xs text-slate-500">Exportable CSV & PDF financial statements.</p>
    </div>
</section>