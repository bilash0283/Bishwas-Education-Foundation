<div class="p-4 sm:p-6 lg:p-8">
    
    <!-- Page Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Running Activities</h2>
            <p class="text-sm text-slate-500">Manage section titles, subtitles, and individual running programs for bishwas.org</p>
        </div>
        <div>
            <button onclick="openActivityModal()"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-4 py-2.5 rounded-lg shadow transition-all">
                <i class="fa-solid fa-plus"></i>
                Add New Activity
            </button>
        </div>
    </div>

    <!-- Analytics / Counter Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Total Activities</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">12</h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-list-check"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Active Programs</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">09</h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-circle-play"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Draft Items</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">03</h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-file-pen"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Featured Category</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">নিয়মিত</h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-star"></i>
            </div>
        </div>
    </div>

    <!-- 1. Section Header & Subtitle Settings Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-8">
        <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-heading text-emerald-600"></i>
                Section Header & Subtitle Settings
            </h3>
            <span class="text-xs bg-emerald-100 text-emerald-800 font-semibold px-2.5 py-1 rounded-full">Frontend Headings</span>
        </div>
        <form action="update_section_header.php" method="POST" class="p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Section Title</label>
                    <input type="text" name="section_title" value="চলমান কিছু কার্যক্রমসমূহ" required 
                        placeholder="যেমন: চলমান কিছু কার্যক্রমসমূহ" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Section Subtitle / Description</label>
                    <textarea name="section_subtitle" rows="2" required 
                        placeholder="যেমন: আপনার যাকাত, সদকাহ কিংবা সাধারণ অনুদান পৌঁছে যাবে..." 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">আপনার যাকাত, সদকাহ কিংবা সাধারণ অনুদান পৌঁছে যাবে সঠিক মানুষের হাতে। নিচের যেকোনো ফান্ডে সরাসরি অংশ নিন।</textarea>
                </div>
            </div>
            <div class="flex justify-end pt-2">
                <button type="submit" 
                    class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-4 py-2 rounded-lg shadow text-xs transition-all">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Update Section Header
                </button>
            </div>
        </form>
    </div>

    <!-- 2. Activities List Table Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-8">
        
        <!-- Table Header & Controls -->
        <div class="p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <h3 class="font-bold text-slate-800">All Program Activities</h3>
                <span class="text-xs bg-emerald-100 text-emerald-800 font-semibold px-2.5 py-1 rounded-full">Active Section</span>
            </div>
            
            <!-- Search & Filter Controls -->
            <div class="flex items-center gap-3">
                <div class="relative w-full sm:w-64">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400 text-xs">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" placeholder="Search activity..." 
                        class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg pl-8 pr-3 py-2 text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5">Image & Title</th>
                        <th class="px-6 py-3.5">Badge / Tag</th>
                        <th class="px-6 py-3.5">Short Description</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    
                    <!-- Row 1 -->
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <img src="uploads/tree.jpg" alt="Tree Plantation" class="w-12 h-12 rounded-lg object-cover border border-slate-200 shrink-0">
                                <span class="font-semibold text-slate-800 line-clamp-1">বৃক্ষরোপণ</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="bg-amber-50 text-amber-700 border border-amber-200 text-xs px-2.5 py-1 rounded-md font-medium">
                                <i class="fa-solid fa-pushpin text-[10px] mr-1"></i>নিয়মিত কার্যক্রম
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-xs text-slate-500 line-clamp-2 max-w-xs">গাছ লাগিয়ে সবুজ পৃথিবী গড়ার এই মহতী উদ্যোগে শামিল হতে পারেন আপনিও...</p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-emerald-600 bg-emerald-50 border border-emerald-200 text-xs font-semibold px-2.5 py-1 rounded-full">Active</span>
                        </td>
                        <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                            <button class="text-slate-400 hover:text-emerald-600 p-1.5 transition-colors" title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button onclick="openDeleteModal('বৃক্ষরোপণ')" class="text-slate-400 hover:text-rose-600 p-1.5 transition-colors" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>

                    <!-- Row 2 -->
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-3">
                                <img src="uploads/food.jpg" alt="Food & Clothes" class="w-12 h-12 rounded-lg object-cover border border-slate-200 shrink-0">
                                <span class="font-semibold text-slate-800 line-clamp-1">সবার জন্য খাদ্য ও বস্ত্র</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="bg-amber-50 text-amber-700 border border-amber-200 text-xs px-2.5 py-1 rounded-md font-medium">
                                <i class="fa-solid fa-pushpin text-[10px] mr-1"></i>নিয়মিত কার্যক্রম
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <p class="text-xs text-slate-500 line-clamp-2 max-w-xs">অসহায় ও সুবিধাবঞ্চিত মানুষের পাশে থেকে তাদের খাদ্য ও বস্ত্রের মৌলিক চাহিদা পুরণে...</p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-emerald-600 bg-emerald-50 border border-emerald-200 text-xs font-semibold px-2.5 py-1 rounded-full">Active</span>
                        </td>
                        <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                            <button class="text-slate-400 hover:text-emerald-600 p-1.5 transition-colors" title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button onclick="openDeleteModal('সবার জন্য খাদ্য ও বস্ত্র')" class="text-slate-400 hover:text-rose-600 p-1.5 transition-colors" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

        <!-- Table Pagination Footer -->
        <div class="p-4 border-t border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
            <span>Showing 1 to 2 of 12 entries</span>
            <div class="flex items-center gap-1">
                <button class="px-3 py-1.5 rounded border border-slate-200 bg-white hover:bg-slate-100 text-slate-600 disabled:opacity-50">Previous</button>
                <button class="px-3 py-1.5 rounded border border-emerald-500 bg-emerald-600 text-white font-semibold">1</button>
                <button class="px-3 py-1.5 rounded border border-slate-200 bg-white hover:bg-slate-100 text-slate-600">2</button>
                <button class="px-3 py-1.5 rounded border border-slate-200 bg-white hover:bg-slate-100 text-slate-600">Next</button>
            </div>
        </div>

    </div>
</div>

<!-- Add/Edit Activity Modal -->
<div id="activityModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xl w-full max-w-2xl overflow-hidden animate-in fade-in zoom-in duration-200">
        
        <!-- Modal Header -->
        <div class="p-5 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 text-lg flex items-center gap-2">
                <i class="fa-solid fa-square-plus text-emerald-600"></i>
                Add New Activity
            </h3>
            <button onclick="closeActivityModal()" class="text-slate-400 hover:text-slate-600 p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Modal Form -->
        <form action="save_activity.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-5">
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Activity Title</label>
                    <input type="text" name="title" required placeholder="যেমন: বৃক্ষরোপণ" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Badge Text</label>
                    <input type="text" name="badge_text" value="নিয়মিত কার্যক্রম" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Image Upload</label>
                    <input type="file" name="image" required 
                        class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-slate-200 rounded-lg bg-slate-50 cursor-pointer">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Status</label>
                    <select name="status" class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="active">Active (Publish)</option>
                        <option value="draft">Draft</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Short Description</label>
                <textarea name="description" rows="3" required placeholder="কার্যক্রমের বিস্তারিত বিবরণ দিন..." 
                    class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
            </div>

            <!-- Modal Footer Buttons -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <button type="button" onclick="closeActivityModal()" 
                    class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                    class="px-5 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow transition-all flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save Activity
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openActivityModal() {
        document.getElementById('activityModal').classList.remove('hidden');
    }
    function closeActivityModal() {
        document.getElementById('activityModal').classList.add('hidden');
    }
</script>