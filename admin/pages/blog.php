<div class="p-4 sm:p-6 lg:p-8">
    
    <!-- Page Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Blog & Diary Management</h2>
            <p class="text-sm text-slate-500">Manage blog articles, field updates, videos, and section headers</p>
        </div>
        <div>
            <button onclick="openBlogModal()"
                class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-4 py-2.5 rounded-lg shadow transition-all">
                <i class="fa-solid fa-plus"></i>
                Create New Blog Post
            </button>
        </div>
    </div>

    <!-- Analytics / Counter Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Total Articles</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">03</h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-newspaper"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Published Posts</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">03</h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Categories</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">ব্লগ, ডায়রি</h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-amber-100 text-amber-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-layer-group"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Status</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">Active</h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-globe"></i>
            </div>
        </div>
    </div>

    <!-- 1. Section Header Settings Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-8">
        <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-heading text-emerald-600"></i>
                Section Header & Subtitle Settings
            </h3>
            <span class="text-xs bg-emerald-100 text-emerald-800 font-semibold px-2.5 py-1 rounded-full">Frontend Display</span>
        </div>
        <form action="update_blog_header.php" method="POST" class="p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Section Title</label>
                    <input type="text" name="section_title" value="আমাদের ব্লগসমূহ ও ডায়রি" required 
                        placeholder="যেমন: আমাদের ব্লগসমূহ ও ডায়রি" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Section Subtitle / Description</label>
                    <textarea name="section_subtitle" rows="2" required 
                        placeholder="যেমন: আমাদের মাঠপর্যায়ের কাজের আপডেট..." 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">আমাদের মাঠপর্যায়ের কাজের আপডেট, ডকুমেন্টারি এবং সচেতনতামূলক বিভিন্ন ভিডিও ও নিবন্ধগুলো নিচে দেখে নিন।</textarea>
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

    <!-- 2. Blog Posts Table Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-8">
        
        <!-- Table Controls -->
        <div class="p-5 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-3">
                <h3 class="font-bold text-slate-800">All Articles & Diary Posts</h3>
                <span class="text-xs bg-emerald-100 text-emerald-800 font-semibold px-2.5 py-1 rounded-full">Published</span>
            </div>
            
            <div class="flex items-center gap-3">
                <div class="relative w-full sm:w-64">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none text-slate-400 text-xs">
                        <i class="fa-solid fa-magnifying-glass"></i>
                    </span>
                    <input type="text" placeholder="Search title or category..." 
                        class="w-full text-xs bg-slate-50 border border-slate-200 rounded-lg pl-8 pr-3 py-2 text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs text-slate-500 uppercase tracking-wider border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-3.5">Thumbnail</th>
                        <th class="px-6 py-3.5">Post Details</th>
                        <th class="px-6 py-3.5">Category</th>
                        <th class="px-6 py-3.5">Publish Date</th>
                        <th class="px-6 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    
                    <!-- Row 1 -->
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-6 py-4">
                            <img src="uploads/blog1.jpg" alt="Winter Relief" class="w-16 h-12 rounded-lg object-cover border border-slate-200 shrink-0">
                        </td>
                        <td class="px-6 py-4">
                            <h4 class="font-bold text-slate-800 line-clamp-1">শীতের উষ্ণতা পৌঁছে যাক সবার জীবনে</h4>
                            <p class="text-xs text-slate-500 line-clamp-1 mt-0.5">শীতের তীব্রতায় কষ্ট পাওয়া দুস্থ, অসহায় ও সুবিধাবঞ্চিত মানুষের পাশে দাঁড়িয়ে...</p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs px-2.5 py-1 rounded-md font-semibold">ব্লগ</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">
                            ১০ জুলাই, ২০২৩
                        </td>
                        <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                            <button class="text-slate-400 hover:text-emerald-600 p-1.5 transition-colors" title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button onclick="openDeleteModal('blog1')" class="text-slate-400 hover:text-rose-600 p-1.5 transition-colors" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>

                    <!-- Row 2 -->
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-6 py-4">
                            <img src="uploads/blog2.jpg" alt="Orphanage Project" class="w-16 h-12 rounded-lg object-cover border border-slate-200 shrink-0">
                        </td>
                        <td class="px-6 py-4">
                            <h4 class="font-bold text-slate-800 line-clamp-1">একটি শিশুর ভবিষ্যৎ গড়ার আনন্দ: এতিমখানা প্রজেক্টের গল্প</h4>
                            <p class="text-xs text-slate-500 line-clamp-1 mt-0.5">সমাজের অবহেলিত শিশুদের দ্বীনি ও আধুনিক শিক্ষায় শিক্ষিত করে তুলতে...</p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="bg-blue-50 text-blue-700 border border-blue-200 text-xs px-2.5 py-1 rounded-md font-semibold">ব্লগ</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">
                            ০৫ জুলাই, ২০২৩
                        </td>
                        <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                            <button class="text-slate-400 hover:text-emerald-600 p-1.5 transition-colors" title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button onclick="openDeleteModal('blog2')" class="text-slate-400 hover:text-rose-600 p-1.5 transition-colors" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>

                    <!-- Row 3 -->
                    <tr class="hover:bg-slate-50/50">
                        <td class="px-6 py-4">
                            <img src="uploads/blog3.jpg" alt="Food & Water Relief" class="w-16 h-12 rounded-lg object-cover border border-slate-200 shrink-0">
                        </td>
                        <td class="px-6 py-4">
                            <h4 class="font-bold text-slate-800 line-clamp-1">মানবতার সেবায় খাদ্য ও বিশুদ্ধ পানির সহায়তা</h4>
                            <p class="text-xs text-slate-500 line-clamp-1 mt-0.5">অসহায়, দুঃস্থ ও অবহেলিত মানুষের মৌলিক চাহিদা পূরণে খাদ্য ও বিশুদ্ধ পানি...</p>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs px-2.5 py-1 rounded-md font-semibold">ব্লগ</span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-xs text-slate-500">
                            ২২ জুন, ২০২৩
                        </td>
                        <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                            <button class="text-slate-400 hover:text-emerald-600 p-1.5 transition-colors" title="Edit">
                                <i class="fa-solid fa-pen"></i>
                            </button>
                            <button onclick="openDeleteModal('blog3')" class="text-slate-400 hover:text-rose-600 p-1.5 transition-colors" title="Delete">
                                <i class="fa-solid fa-trash"></i>
                            </button>
                        </td>
                    </tr>

                </tbody>
            </table>
        </div>

        <!-- Table Pagination Footer -->
        <div class="p-4 border-t border-slate-200 bg-slate-50/50 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
            <span>Showing 1 to 3 of 3 entries</span>
            <div class="flex items-center gap-1">
                <button class="px-3 py-1.5 rounded border border-slate-200 bg-white text-slate-400 cursor-not-allowed">Previous</button>
                <button class="px-3 py-1.5 rounded border border-emerald-500 bg-emerald-600 text-white font-semibold">1</button>
                <button class="px-3 py-1.5 rounded border border-slate-200 bg-white text-slate-400 cursor-not-allowed">Next</button>
            </div>
        </div>

    </div>
</div>

<!-- Add/Edit Blog Post Modal -->
<div id="blogModal" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-xl border border-slate-200 shadow-2xl w-full max-w-2xl overflow-hidden animate-in fade-in zoom-in duration-200 max-h-[90vh] flex flex-col">
        
        <!-- Modal Header -->
        <div class="p-5 border-b border-slate-200 bg-slate-50 flex items-center justify-between shrink-0">
            <h3 class="font-bold text-slate-800 text-lg flex items-center gap-2">
                <i class="fa-solid fa-pen-nib text-emerald-600"></i>
                Add New Blog / Article
            </h3>
            <button onclick="closeBlogModal()" class="text-slate-400 hover:text-slate-600 p-1">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <!-- Modal Form Body (Scrollable) -->
        <form action="save_blog_post.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-5 overflow-y-auto">
            
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Article Title</label>
                <input type="text" name="blog_title" required placeholder="যেমন: শীতের উষ্ণতা পৌঁছে যাক সবার জীবনে" 
                    class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Category Tag</label>
                    <input type="text" name="category" value="ব্লগ" required placeholder="যেমন: ব্লগ, ডায়রি, খবর" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Publish Date</label>
                    <input type="text" name="publish_date" placeholder="যেমন: ১০ জুলাই, ২০২৩" required 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Feature Image / Thumbnail</label>
                <input type="file" name="blog_image" required 
                    class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-slate-200 rounded-lg bg-slate-50 cursor-pointer">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Short Description (Excerpt)</label>
                <textarea name="short_description" rows="3" required placeholder="সংক্ষিপ্ত বিবরণ লিখুন যা গার্ড কার্ডে দেখানো হবে..." 
                    class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Full Article Content (Optional Detail)</label>
                <textarea name="full_content" rows="4" placeholder="বিস্তারিত মূল লেখা লিখুন..." 
                    class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500"></textarea>
            </div>

            <!-- Modal Footer Buttons -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                <button type="button" onclick="closeBlogModal()" 
                    class="px-4 py-2 text-sm font-medium text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition-colors">
                    Cancel
                </button>
                <button type="submit" 
                    class="px-5 py-2 text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg shadow transition-all flex items-center gap-2">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save Blog Post
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openBlogModal() {
        document.getElementById('blogModal').classList.remove('hidden');
    }
    function closeBlogModal() {
        document.getElementById('blogModal').classList.add('hidden');
    }
</script>