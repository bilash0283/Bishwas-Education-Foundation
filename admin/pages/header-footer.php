<div class="p-4 sm:p-6 lg:p-8 space-y-8">
    
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Simplified Header & Footer Settings</h2>
            <p class="text-sm text-slate-500">Manage site logo, button label, about info, and social media links</p>
        </div>
    </div>

    <!-- 1. HEADER SETTINGS FORM -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-window-maximize text-emerald-600"></i>
                Header Settings (Logo & Button Name Only)
            </h3>
            <span class="text-xs bg-emerald-100 text-emerald-800 font-semibold px-2.5 py-1 rounded-full">Header Section</span>
        </div>

        <form action="update_header_settings.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Donate Button Text Only -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Header Button Text</label>
                    <input type="text" name="donate_btn_text" value="অনুদান দিন" required 
                        placeholder="যেমন: অনুদান দিন"
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <!-- <p class="text-xs text-slate-400 mt-1">শুধু বাটনের নাম পরিবর্তন হবে (কোনো লিঙ্ক বা লিঙ্ক ইনপুট থাকবে না)।</p> -->
                </div>
            </div>

            <!-- Header Save Button -->
            <div class="flex justify-end pt-4 border-t border-slate-200">
                <button type="submit" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-6 py-2 rounded-lg shadow text-sm transition-all">
                    <i class="fa-solid fa-floppy-disk"></i> Save Header
                </button>
            </div>
        </form>
    </div>

    <!-- 2. FOOTER SETTINGS FORM -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-dock-bottom text-emerald-600"></i>
                Footer Settings (Left Info & Right Social Media Only)
            </h3>
            <span class="text-xs bg-slate-800 text-white font-semibold px-2.5 py-1 rounded-full">Footer Section</span>
        </div>

        <form action="update_footer_settings.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Left Side: Logo & Description -->
                <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-4">
                    <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider border-b border-slate-200 pb-2">
                        <i class="fa-solid fa-align-left text-emerald-600 mr-1"></i> Left Side: Logo & Info
                    </h4>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Footer Text / Description</label>
                        <textarea name="footer_about_text" rows="3" required class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">একটি স্বচ্ছ, নির্ভরযোগ্য ও অলাভজনক দাতব্য প্রতিষ্ঠান, যা মানবতার কল্যাণ ও ইসলামের সুমহান আদর্শ প্রসারে কাজ করছে।</textarea>
                    </div>
                </div>

                <!-- Right Side: Social Media Links -->
                <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-4">
                    <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider border-b border-slate-200 pb-2">
                        <i class="fa-solid fa-share-nodes text-emerald-600 mr-1"></i> Right Side: Social Media Links
                    </h4>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">
                            <i class="fa-brands fa-facebook text-blue-600 mr-1"></i> Facebook URL
                        </label>
                        <input type="url" name="facebook_url" value="https://facebook.com/bishwas" placeholder="https://..." class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 mb-3">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">
                            <i class="fa-brands fa-youtube text-red-600 mr-1"></i> YouTube URL
                        </label>
                        <input type="url" name="youtube_url" value="https://youtube.com/bishwas" placeholder="https://..." class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 mb-3">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">
                            <i class="fa-brands fa-x-twitter text-slate-800 mr-1"></i> Twitter / X URL
                        </label>
                        <input type="url" name="twitter_url" value="https://twitter.com/bishwas" placeholder="https://..." class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>
            </div>

            <!-- Footer Save Button -->
            <div class="flex justify-end pt-4 border-t border-slate-200">
                <button type="submit" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-6 py-2 rounded-lg shadow text-sm transition-all">
                    <i class="fa-solid fa-floppy-disk"></i> Save Footer
                </button>
            </div>

        </form>
    </div>

</div>