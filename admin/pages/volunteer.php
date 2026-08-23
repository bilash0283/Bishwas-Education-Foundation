<div class="p-4 sm:p-6 lg:p-8">
    
    <!-- Page Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Volunteer CTA Banner Settings</h2>
            <p class="text-sm text-slate-500">Manage heading, description, and action button for the volunteer call-to-action banner</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Single Banner Section
            </span>
        </div>
    </div>

    <!-- Analytics / Status Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Section Type</p>
                <h3 class="text-lg font-bold text-slate-800 mt-1">Volunteer Banner</h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-user-plus"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Target Action</p>
                <h3 class="text-lg font-bold text-slate-800 mt-1">ভলান্টিয়ার রেজিস্ট্রেশন</h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-link"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Status</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">Published</h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-globe"></i>
            </div>
        </div>
    </div>

    <!-- Main Form Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-8">
        <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-emerald-600"></i>
                Edit Volunteer Call-To-Action Banner
            </h3>
            <span class="text-xs bg-emerald-100 text-emerald-800 font-semibold px-2.5 py-1 rounded-full">Frontend Display</span>
        </div>

        <form action="update_volunteer_banner.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            
            <!-- Main Title -->
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Banner Title / Heading</label>
                <input type="text" name="banner_title" value="আপনিও হতে পারেন আমাদের একজন গর্বিত ভলান্টিয়ার" required 
                    placeholder="যেমন: আপনিও হতে পারেন আমাদের একজন গর্বিত ভলান্টিয়ার" 
                    class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <!-- Description -->
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Banner Subtitle / Description</label>
                <textarea name="banner_description" rows="3" required 
                    placeholder="ব্যানারের সংক্ষিপ্ত বিবরণ লিখুন..." 
                    class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">আপনার মেধা, সময় ও শ্রম দিয়ে মানবতার সেবায় অবদান রাখুন। দেশব্যাপী আমাদের বিভিন্ন সামাজিক ও ধর্মীয় উদ্যোগে স্বেচ্ছাসেবক হিসেবে কাজ করতে আজই নিবন্ধ করুন।</textarea>
            </div>

            <!-- Button Options -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Button Label</label>
                    <input type="text" name="button_text" value="ভলান্টিয়ার হিসেবে যোগ দিন" required 
                        placeholder="যেমন: ভলান্টিয়ার হিসেবে যোগ দিন" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Button Link / URL</label>
                    <input type="text" name="button_link" value="/volunteer-register" required 
                        placeholder="যেমন: /volunteer-register" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Button Icon Class</label>
                    <input type="text" name="button_icon" value="fa-solid fa-user-plus" 
                        placeholder="যেমন: fa-solid fa-user-plus" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <!-- Optional Custom Background Color or Image -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Background Color Theme</label>
                    <select name="bg_theme" class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                        <option value="emerald" selected>Primary Green (Emerald)</option>
                        <option value="blue">Blue Theme</option>
                        <option value="dark">Dark Slate Theme</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Optional Background Image (If any)</label>
                    <input type="file" name="bg_image" 
                        class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-slate-200 rounded-lg bg-slate-50 cursor-pointer">
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end pt-4 border-t border-slate-200">
                <button type="submit" 
                    class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-6 py-2.5 rounded-lg shadow text-sm transition-all">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save Banner Settings
                </button>
            </div>

        </form>
    </div>
</div>