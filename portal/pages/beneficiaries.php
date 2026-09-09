<!-- PAGE 1: DASHBOARD OVERVIEW PAGE -->
<section class="page-content space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Beneficiaries</h2>
            <p class="text-xs text-slate-500 mt-0.5">Real-time stats and foundation activity metrics.</p>
        </div>
         <button onclick="openModal('volunteerAddModal')"
            class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-emerald-600/20 flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Add Volunteer
        </button>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-5 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div
                class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-hand-holding-heart"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase">Beneficiaries</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-0.5">1,700+</h3>
            </div>
        </div>
        <div class="p-5 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div
                class="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-user-ninja"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase">Active Volunteers</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-0.5">100+</h3>
            </div>
        </div>
        <div class="p-5 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase">Active Donors</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-0.5">450</h3>
            </div>
        </div>
        <div class="p-5 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div
                class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-box-archive"></i>
            </div>
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

<!-- VOLUNTEER ADD MODAL (Unique ID) -->
<div id="volunteerAddModal"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center p-4 z-50">
    <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden border border-slate-100">
        <div class="p-5 bg-slate-900 text-white flex justify-between items-center">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <i class="fa-solid fa-square-plus text-emerald-400"></i>
                <span>Add Beneficiary</span>
            </h3>
            <button onclick="closeModal('volunteerAddModal')" class="text-slate-400 hover:text-white"><i
                    class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <form class="p-6 space-y-4" onsubmit="event.preventDefault(); closeModal('volunteerAddModal');">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Full Name</label>
                <input type="text" placeholder="Enter complete name" required
                    class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-emerald-500">
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Phone Number</label>
                    <input type="text" placeholder="+8801XXXXXXXXX" required
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Category / Role</label>
                    <select
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                        <option value="Beneficiary">Beneficiary</option>
                        <option value="Volunteer">Volunteer</option>
                        <option value="Member">Donor / Member</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Address / Note</label>
                <textarea rows="3" placeholder="Enter location details..."
                    class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500"></textarea>
            </div>
            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('volunteerAddModal')"
                    class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="submit"
                    class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700 shadow-sm">Save
                    Entry</button>
            </div>
        </form>
    </div>
</div>

<!-- VOLUNTEER DELETE MODAL (Unique ID) -->
<div id="volunteerDeleteModal"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center p-4 z-50">
    <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl p-6 text-center border border-slate-100">
        <div
            class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center text-xl mx-auto mb-4">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h3 class="font-bold text-slate-800 text-base">Confirm Deletion</h3>
        <p class="text-xs text-slate-500 mt-1">Are you sure you want to remove <span id="volunteerDeleteTargetName"
                class="font-bold text-slate-700"></span>? This action cannot be undone.</p>
        <div class="flex justify-center gap-3 mt-6">
            <button onclick="closeModal('volunteerDeleteModal')"
                class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
            <button onclick="closeModal('volunteerDeleteModal')"
                class="px-4 py-2 bg-rose-600 text-white rounded-xl text-xs font-semibold hover:bg-rose-700 shadow-sm">Delete</button>
        </div>
    </div>
</div>

<script>
    // Dynamic open function for any modal
    function openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }
    }

    // Dynamic close function for any modal
    function closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('flex');
            modal.classList.add('hidden');
        }
    }

    // Dedicated delete modal handler
    function openDeleteModal(modalId, targetName) {
        const targetElement = document.getElementById('volunteerDeleteTargetName');
        if (targetElement) {
            targetElement.innerText = targetName;
        }
        openModal(modalId);
    }
</script>
