<div class="p-4 sm:p-6 lg:p-8">
    
    <!-- Page Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Donation Sectors</h2>
            <p class="text-sm text-slate-500">Manage section headers and donation categories for bishwas.org</p>
        </div>
        <div>
            <button onclick="openSectorModal()"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-4 py-2.5 rounded-lg shadow transition-all">
                <i class="fa-solid fa-plus"></i>
                Add New Sector
            </button>
        </div>
    </div>

    <!-- Analytics / Counter Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Total Sectors</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">04</h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-hand-holding-dollar"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Active Funds</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">04</h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Primary CTA</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">জরুরি ত্রাণ</h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-kit-medical"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Status</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">Live</h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-globe"></i>
            </div>
        </div>
    </div>

    <!-- 2. Donation Sectors Table Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-8">
        
        <!-- Table Header Controls -->
        <div class="p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <h3 class="font-bold text-slate-800">All Donation Sectors</h3>
                <span class="text-xs bg-emerald-100 text-emerald-800 font-semibold px-2.5 py-1 rounded-full">Active Section</span>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="relative w-full sm:w-64">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400 text-xs">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" placeholder="Search sector..." 
                        class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg pl-8 pr-3 py-2 text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5">Icon & Title</th>
                        <th class="px-6 py-3.5">Description</th>
                        <th class="px-6 py-3.5">Button Text & Link</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    
                    <!-- Row 1 -->
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-orange-100 text-orange-600 flex items-center justify-center text-lg shrink-0">
                                    <i class="fa-solid fa-kit-medical"></i>
                                </div>
                                <span class="font-semibold text-slate-800 line-clamp-1">জরুরি ত্রাণ তহবিল</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-xs text-slate-500 line-clamp-2 max-w-xs">বন্যা, ঝড় কিংবা যেকোনো প্রাকৃতিক দুর্যোগে ক্ষতিগ্রস্ত অসহায় মানুষের পাশে...</p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="bg-slate-100 text-slate-700 text-xs font-medium px-2.5 py-1 rounded border border-slate-200">
                                অনুদানে শরীক হোন <span class="text-slate-400">(/donate-relief)</span>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-emerald-600 bg-emerald-50 border border-emerald-200 text-xs font-semibold px-2.5 py-1 rounded-full">Active</span>
                        </td>
                        <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                            <button class="text-slate-400 hover:text-emerald-600 p-1.5 transition-colors" title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button onclick="openDeleteModal('জরুরি ত্রাণ তহবিল')" class="text-slate-400 hover:text-rose-600 p-1.5 transition-colors" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>

                    <!-- Row 2 -->
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center text-lg shrink-0">
                                    <i class="fa-solid fa-coins"></i>
                                </div>
                                <span class="font-semibold text-slate-800 line-clamp-1">যাকাত তহবিল</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-xs text-slate-500 line-clamp-2 max-w-xs">সম্পূর্ণ শরীয়াহ সম্মত উপায়ে আপনার যাকাত সংগ্রহ করে তা দরিদ্র পরিবারের...</p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="bg-slate-100 text-slate-700 text-xs font-medium px-2.5 py-1 rounded border border-slate-200">
                                যাকাত দিন <span class="text-slate-400">(/zakat)</span>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-emerald-600 bg-emerald-50 border border-emerald-200 text-xs font-semibold px-2.5 py-1 rounded-full">Active</span>
                        </td>
                        <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                            <button class="text-slate-400 hover:text-emerald-600 p-1.5 transition-colors" title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button onclick="openDeleteModal('যাকাত তহবিল')" class="text-slate-400 hover:text-rose-600 p-1.5 transition-colors" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>

                    <!-- Row 3 -->
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-lg shrink-0">
                                    <i class="fa-solid fa-calendar-check"></i>
                                </div>
                                <span class="font-semibold text-slate-800 line-clamp-1">নিয়মিত অনুদান তহবিল</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-xs text-slate-500 line-clamp-2 max-w-xs">প্রতি মাসে বা সপ্তাহে নির্দিষ্ট অংকের টাকা স্বয়ংক্রিয়ভাবে দেওয়ার সুবিধা...</p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="bg-slate-100 text-slate-700 text-xs font-medium px-2.5 py-1 rounded border border-slate-200">
                                নিয়মিত দাত হন <span class="text-slate-400">(/monthly-donor)</span>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-emerald-600 bg-emerald-50 border border-emerald-200 text-xs font-semibold px-2.5 py-1 rounded-full">Active</span>
                        </td>
                        <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                            <button class="text-slate-400 hover:text-emerald-600 p-1.5 transition-colors" title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button onclick="openDeleteModal('নিয়মিত অনুদান তহবিল')" class="text-slate-400 hover:text-rose-600 p-1.5 transition-colors" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>

                    <!-- Row 4 -->
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center text-lg shrink-0">
                                    <i class="fa-solid fa-box-archive"></i>
                                </div>
                                <span class="font-semibold text-slate-800 line-clamp-1">সাধারণ তহবিল</span>
                            </div>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-xs text-slate-500 line-clamp-2 max-w-xs">ফাউন্ডেশনের প্রশাসনিক খরচ, জনকল্যাণমূলক বহুমুখী প্রজেক্ট পরিচালনায়...</p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="bg-slate-100 text-slate-700 text-xs font-medium px-2.5 py-1 rounded border border-slate-200">
                                সাধারণ অনুদান <span class="text-slate-400">(/general-donate)</span>
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-emerald-600 bg-emerald-50 border border-emerald-200 text-xs font-semibold px-2.5 py-1 rounded-full">Active</span>
                        </td>
                        <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                            <button class="text-slate-400 hover:text-emerald-600 p-1.5 transition-colors" title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button onclick="openDeleteModal('সাধারণ তহবিল')" class="text-slate-400 hover:text-rose-600 p-1.5 transition-colors" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

        <!-- Table Footer -->
        <div class="p-4 border-t border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
            <span>Showing 1 to 4 of 4 entries</span>
            <div class="flex items-center gap-1">
                <button class="px-3 py-1.5 rounded border border-slate-200 bg-white hover:bg-slate-100 text-slate-600 disabled:opacity-50">Previous</button>
                <button class="px-3 py-1.5 rounded border border-emerald-500 bg-emerald-600 text-white font-semibold">1</button>
                <button class="px-3 py-1.5 rounded border border-slate-200 bg-white hover:bg-slate-100 text-slate-600">Next</button>
            </div>
        </div>

    </div>
</div>

<!-- Add/Edit Donation Sector Modal -->
<div id="sectorModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xl w-full max-w-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        
        <!-- Modal Header -->
        <div class="p-5 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 text-lg flex items-center gap-2">
                <i class="fa-solid fa-square-plus text-emerald-600"></i>
                Add New Donation Sector
            </h3>
            <button onclick="closeSectorModal()" class="text-slate-400 hover:text-slate-600 p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Modal Form -->
        <form action="save_sector.php" method="POST" class="p-6 space-y-5">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Sector Title</label>
                    <input type="text" name="title" required placeholder="যেমন: জরুরি ত্রাণ তহবিল" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">FontAwesome Icon Class</label>
                    <input type="text" name="icon_class" placeholder="যেমন: fa-solid fa-kit-medical" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Button Label</label>
                    <input type="text" name="button_text" required placeholder="যেমন: অনুদানে শরীক হোন" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Button Target Link</label>
                    <input type="text" name="button_link" required placeholder="যেমন: /donate-relief" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Short Description</label>
                <textarea name="description" rows="3" required placeholder="খাতের সংক্ষিপ্ত বিবরণ লিখুন..." 
                    class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
            </div>

            <!-- Modal Footer Buttons -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <button type="button" onclick="closeSectorModal()" 
                    class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                    class="px-5 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow transition-all flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save Sector
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openSectorModal() {
        document.getElementById('sectorModal').classList.remove('hidden');
    }
    function closeSectorModal() {
        document.getElementById('sectorModal').classList.add('hidden');
    }
</script>