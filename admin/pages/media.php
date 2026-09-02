<div class="p-4 sm:p-6 lg:p-8">
    
    <!-- Page Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Photo Gallery Management</h2>
            <p class="text-sm text-slate-500">Manage section headers and photo gallery items for bishwas.org</p>
        </div>
        <div>
            <button onclick="openGalleryModal()"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-4 py-2.5 rounded-lg shadow transition-all">
                <i class="fa-solid fa-plus"></i>
                Add New Photo
            </button>
        </div>
    </div>

    <!-- Analytics / Counter Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Total Gallery Photos</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">08</h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-images"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Active Published</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">08</h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Featured Category</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">ত্রাণ ও ইভেন্ট</h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-camera"></i>
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

    <!-- 1. Section Header & Subtitle Settings Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-8">
        <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-heading text-emerald-600"></i>
                Section Header & Subtitle Settings
            </h3>
            <span class="text-xs bg-emerald-100 text-emerald-800 font-semibold px-2.5 py-1 rounded-full">Frontend Headings</span>
        </div>
        <form action="update_gallery_header.php" method="POST" class="p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Section Title</label>
                    <input type="text" name="section_title" value="আমাদের কার্যক্রমের চিত্রশালা" required 
                        placeholder="যেমন: আমাদের কার্যক্রমের চিত্রশালা" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Section Subtitle / Description</label>
                    <textarea name="section_subtitle" rows="2" required 
                        placeholder="যেমন: আমাদের বিভিন্ন প্রজেক্ট, ত্রাণ বিতরণ এবং মানবিক উদ্যোগের..." 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">আমাদের বিভিন্ন প্রজেক্ট, ত্রাণ বিতরণ এবং মানবিক উদ্যোগের কিছু বাস্তব চিত্র নিচে তুলে ধরা হলো।</textarea>
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

    <!-- 2. Gallery Images Table Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-8">
        
        <!-- Table Controls -->
        <div class="p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <h3 class="font-bold text-slate-800">All Gallery Photos</h3>
                <span class="text-xs bg-emerald-100 text-emerald-800 font-semibold px-2.5 py-1 rounded-full">Active Items</span>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="relative w-full sm:w-64">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400 text-xs">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" placeholder="Search photo caption..." 
                        class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg pl-8 pr-3 py-2 text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5">Image Preview</th>
                        <th class="px-6 py-3.5">Caption / Title</th>
                        <th class="px-6 py-3.5">Category</th>
                        <th class="px-6 py-3.5">Status</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    
                    <!-- Row 1 -->
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-6 py-4">
                            <img src="uploads/gallery1.jpg" alt="International Women's Day" class="w-16 h-12 rounded-lg object-cover border border-slate-200 shrink-0">
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-semibold text-slate-800 line-clamp-1">আন্তর্জাতিক নারী দিবস-২০১৮ অনুষ্ঠান</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="bg-slate-100 text-slate-700 text-xs px-2.5 py-1 rounded-md font-medium border border-slate-200">ইভেন্ট</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-emerald-600 bg-emerald-50 border border-emerald-200 text-xs font-semibold px-2.5 py-1 rounded-full">Active</span>
                        </td>
                        <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                            <button class="text-slate-400 hover:text-emerald-600 p-1.5 transition-colors" title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button onclick="openDeleteModal('gallery1')" class="text-slate-400 hover:text-rose-600 p-1.5 transition-colors" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>

                    <!-- Row 2 -->
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-6 py-4">
                            <img src="uploads/gallery2.jpg" alt="Winter Clothes" class="w-16 h-12 rounded-lg object-cover border border-slate-200 shrink-0">
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-semibold text-slate-800 line-clamp-1">অসহায় মানুষের মাঝে শীতবস্ত্র বিতরণ</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="bg-slate-100 text-slate-700 text-xs px-2.5 py-1 rounded-md font-medium border border-slate-200">ত্রাণ ও সাহায্য</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-emerald-600 bg-emerald-50 border border-emerald-200 text-xs font-semibold px-2.5 py-1 rounded-full">Active</span>
                        </td>
                        <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                            <button class="text-slate-400 hover:text-emerald-600 p-1.5 transition-colors" title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button onclick="openDeleteModal('gallery2')" class="text-slate-400 hover:text-rose-600 p-1.5 transition-colors" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>

                    <!-- Row 3 -->
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-6 py-4">
                            <img src="uploads/gallery3.jpg" alt="Madrasa Students" class="w-16 h-12 rounded-lg object-cover border border-slate-200 shrink-0">
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-semibold text-slate-800 line-clamp-1">সুবিধাবঞ্চিত শিশু ও শিক্ষার্থীদের কুরআন বিতরণ</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="bg-slate-100 text-slate-700 text-xs px-2.5 py-1 rounded-md font-medium border border-slate-200">শিক্ষা</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="text-emerald-600 bg-emerald-50 border border-emerald-200 text-xs font-semibold px-2.5 py-1 rounded-full">Active</span>
                        </td>
                        <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                            <button class="text-slate-400 hover:text-emerald-600 p-1.5 transition-colors" title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button onclick="openDeleteModal('gallery3')" class="text-slate-400 hover:text-rose-600 p-1.5 transition-colors" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

        <!-- Table Pagination Footer -->
        <div class="p-4 border-t border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
            <span>Showing 1 to 3 of 8 entries</span>
            <div class="flex items-center gap-1">
                <button class="px-3 py-1.5 rounded border border-slate-200 bg-white hover:bg-slate-100 text-slate-600 disabled:opacity-50">Previous</button>
                <button class="px-3 py-1.5 rounded border border-emerald-500 bg-emerald-600 text-white font-semibold">1</button>
                <button class="px-3 py-1.5 rounded border border-slate-200 bg-white hover:bg-slate-100 text-slate-600">2</button>
                <button class="px-3 py-1.5 rounded border border-slate-200 bg-white hover:bg-slate-100 text-slate-600">Next</button>
            </div>
        </div>

    </div>
</div>

<!-- Add/Edit Gallery Photo Modal -->
<div id="galleryModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xl w-full max-w-xl overflow-hidden animate-in fade-in zoom-in duration-200">
        
        <!-- Modal Header -->
        <div class="p-5 border-b border-slate-200 bg-slate-50 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 text-lg flex items-center gap-2">
                <i class="fa-solid fa-cloud-arrow-up text-emerald-600"></i>
                Upload New Gallery Photo
            </h3>
            <button onclick="closeGalleryModal()" class="text-slate-400 hover:text-slate-600 p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Modal Form -->
        <form action="save_gallery_photo.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-5">
            
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Photo Image File</label>
                <input type="file" name="gallery_image" required 
                    class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-slate-200 rounded-lg bg-slate-50 cursor-pointer">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Caption / Title (Optional)</label>
                    <input type="text" name="caption" placeholder="যেমন: ত্রাণ বিতরণ কর্মসূচি" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Category</label>
                    <select name="category" class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="সাধারণ তহবিল">সাধারণ তহবিল</option>
                        <option value="নিয়মিত অনুদান তহবিল">নিয়মিত অনুদান তহবিল</option>
                        <option value="যাকাত তহবিল">যাকাত তহবিল</option>
                        <option value="জরুরি ত্রাণ তহবিল">জরুরি ত্রাণ তহবিল</option>
                        <option value="শিক্ষা তহবিল">শিক্ষা তহবিল</option>
                        <option value="অন্যান্য তহবিল">অন্যান্য তহবিল</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Status</label>
                <select name="status" class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <option value="active">Active (Publish)</option>
                    <option value="draft">Draft</option>
                </select>
            </div>

            <!-- Modal Footer Buttons -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <button type="button" onclick="closeGalleryModal()" 
                    class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                    class="px-5 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow transition-all flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save Photo
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openGalleryModal() {
        document.getElementById('galleryModal').classList.remove('hidden');
    }
    function closeGalleryModal() {
        document.getElementById('galleryModal').classList.add('hidden');
    }
</script>