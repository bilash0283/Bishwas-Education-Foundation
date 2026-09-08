<?php include 'includes/header.php'; ?>

    <!-- MAIN BODY WORKSPACE (INDEPENDENT SCROLL LAYOUT) -->
    <div class="flex-1 flex overflow-hidden relative">
        <!-- sidebar menu -->
        <?php include 'includes/sidebar.php'; ?>        

        <!-- 3. MAIN CONTENT AREA -->
        <main class="flex-1 overflow-y-auto flex flex-col justify-between bg-slate-50">
            <div class="p-4 sm:p-6 lg:p-8 space-y-6">
                <?php include 'pages/dashboard.php'; ?>
            </div>

            <!-- FOOTER SECTION -->
            <footer class="bg-white border-t border-slate-200/80 py-4 px-6 mt-8">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
                    <p>© <?php echo date('Y'); ?> <span class="font-semibold text-slate-700"><a href="https://bishwas.org" target="_blank" class="hover:text-emerald-600 transition-colors"><?php echo $site_title; ?></a></span>. All rights reserved.</p>
                    <div class="flex items-center gap-4">
                        <a href="#" class="hover:text-emerald-600 transition-colors">Privacy Policy</a>
                        <a href="#" class="hover:text-emerald-600 transition-colors">Terms of Service</a>
                        <a href="#" class="hover:text-emerald-600 transition-colors">Support</a>
                    </div>
                </div>
            </footer>
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
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Full Name</label>
                    <input type="text" placeholder="Enter complete name" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-emerald-500">
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Phone Number</label>
                        <input type="text" placeholder="+8801XXXXXXXXX" required class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Category / Role</label>
                        <select class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                            <option value="Beneficiary">Beneficiary</option>
                            <option value="Volunteer">Volunteer</option>
                            <option value="Member">Donor / Member</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Address / Note</label>
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

<?php include 'includes/footer.php'; ?>