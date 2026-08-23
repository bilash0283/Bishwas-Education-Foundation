<div class="p-4 sm:p-6 lg:p-8 space-y-8">
    
    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Logo & Branding Settings</h2>
            <p class="text-sm text-slate-500">Manage site logo, brand name, tags, and favicon icon</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Brand Identity
            </span>
        </div>
    </div>

    <!-- LOGO & BRANDING FORM CARD -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-shapes text-emerald-600"></i>
                Logo & Tag Configuration
            </h3>
            <span class="text-xs bg-slate-800 text-white font-semibold px-2.5 py-1 rounded-full">Branding</span>
        </div>

        <form action="update_branding_settings.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            
            <!-- Brand Name & Tag / Tagline Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Organization / Brand Name</label>
                    <input type="text" name="site_title" value="Bishwas Education Foundation" required 
                        placeholder="যেমন: Bishwas Education Foundation" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <!-- Single Logo & Favicon Assets -->
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-4">
                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-image text-emerald-600"></i>
                    Logo & Favicon Images
                </h4>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    
                    <!-- Single Main Logo -->
                    <div class="bg-white p-4 rounded-lg border border-slate-200 flex flex-col justify-between">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Website Logo (Main)</label>
                            <p class="text-[11px] text-slate-400 mb-3">সমগ্র ওয়েবসাইটে ব্যবহারের জন্য একটিমাত্র লোগো আপলোড করুন (PNG/SVG)</p>
                            
                            <div class="h-24 w-full bg-slate-100 rounded-lg border border-dashed border-slate-300 flex items-center justify-center p-3 mb-3">
                                <img src="assets/images/logo.png" alt="Website Logo" class="max-h-full max-w-full object-contain">
                            </div>
                        </div>
                        <input type="file" name="site_logo" accept="image/*" 
                            class="text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                    </div>

                    <!-- Favicon Icon -->
                    <div class="bg-white p-4 rounded-lg border border-slate-200 flex flex-col justify-between">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1">Favicon Icon</label>
                            <p class="text-[11px] text-slate-400 mb-3">ব্রাউজার ট্যাবে প্রদর্শনের জন্য ছোট আইকন (32x32px .png/.ico)</p>
                            
                            <div class="h-24 w-full bg-slate-100 rounded-lg border border-dashed border-slate-300 flex items-center justify-center p-3 mb-3">
                                <img src="assets/images/favicon.png" alt="Favicon Icon" class="w-10 h-10 object-contain">
                            </div>
                        </div>
                        <input type="file" name="favicon_icon" accept="image/x-icon,image/png" 
                            class="text-xs text-slate-500 file:mr-2 file:py-1.5 file:px-3 file:rounded-md file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100">
                    </div>

                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end pt-4 border-t border-slate-200">
                <button type="submit" class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-6 py-2.5 rounded-lg shadow text-sm transition-all">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save Branding Settings
                </button>
            </div>

        </form>
    </div>

</div>