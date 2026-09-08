<!-- PAGE 2: BENEFICIARIES PAGE -->
<section id="page-beneficiaries" class="page-content hidden space-y-6">
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Service Beneficiaries</h2>
            <p class="text-xs text-slate-500 mt-0.5">Manage individuals and families receiving support.</p>
        </div>
        <!-- UNIQUE CLICK HANDLER -->
        <button onclick="openModal('beneficiaryAddModal')"
            class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-emerald-600/20 flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Add Beneficiary
        </button>
    </div>
    <div class="bg-white rounded-2xl border border-slate-200/80 p-5 shadow-xs">
        <p class="text-xs text-slate-500">Beneficiary Management Directory loaded.</p>

        <!-- Example Trigger for Delete Modal -->
        <button onclick="openDeleteModal('beneficiaryDeleteModal', 'John Doe')"
            class="mt-3 px-3 py-1.5 bg-rose-50 text-rose-600 border border-rose-200 rounded-lg text-xs font-semibold">
            Delete Example
        </button>
    </div>
</section>

<!-- BENEFICIARY ADD MODAL (Unique ID) -->
<div id="beneficiaryAddModal"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center p-4 z-50">
    <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden border border-slate-100">
        <div class="p-5 bg-slate-900 text-white flex justify-between items-center">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <i class="fa-solid fa-square-plus text-emerald-400"></i>
                <span>Add Beneficiary</span>
            </h3>
            <button onclick="closeModal('beneficiaryAddModal')" class="text-slate-400 hover:text-white"><i
                    class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <form class="p-6 space-y-4" onsubmit="event.preventDefault(); closeModal('beneficiaryAddModal');">
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
                <button type="button" onclick="closeModal('beneficiaryAddModal')"
                    class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="submit"
                    class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700 shadow-sm">Save
                    Entry</button>
            </div>
        </form>
    </div>
</div>

<!-- BENEFICIARY DELETE MODAL (Unique ID) -->
<div id="beneficiaryDeleteModal"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center p-4 z-50">
    <div class="bg-white w-full max-w-sm rounded-2xl shadow-2xl p-6 text-center border border-slate-100">
        <div
            class="w-12 h-12 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center text-xl mx-auto mb-4">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <h3 class="font-bold text-slate-800 text-base">Confirm Deletion</h3>
        <p class="text-xs text-slate-500 mt-1">Are you sure you want to remove <span id="beneficiaryDeleteTargetName"
                class="font-bold text-slate-700"></span>? This action cannot be undone.</p>
        <div class="flex justify-center gap-3 mt-6">
            <button onclick="closeModal('beneficiaryDeleteModal')"
                class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
            <button onclick="closeModal('beneficiaryDeleteModal')"
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
        const targetElement = document.getElementById('beneficiaryDeleteTargetName');
        if (targetElement) {
            targetElement.innerText = targetName;
        }
        openModal(modalId);
    }
</script>