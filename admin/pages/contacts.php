<div class="p-4 sm:p-6 lg:p-8">
    
    <!-- Page Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Contact Us Settings</h2>
            <p class="text-sm text-slate-500">Manage contact details, office address, map location, and section titles</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Single Section Page
            </span>
        </div>
    </div>

    <!-- Analytics / Information Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Office Location</p>
                <h3 class="text-base font-bold text-slate-800 mt-1">মীরবাগ, হাতিরঝিল, ঢাকা</h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-location-dot"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Primary Phone</p>
                <h3 class="text-base font-bold text-slate-800 mt-1">+৮৮০ ১৭১৫-৪৮২৩৬৩</h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-phone"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Official Email</p>
                <h3 class="text-base font-bold text-slate-800 mt-1">info@bishwas.org</h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-purple-100 text-purple-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-envelope"></i>
            </div>
        </div>
    </div>

    <!-- Main Content Form Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-8">
        <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-address-book text-emerald-600"></i>
                Edit Contact Information & Content
            </h3>
            <span class="text-xs bg-emerald-100 text-emerald-800 font-semibold px-2.5 py-1 rounded-full">Frontend Display</span>
        </div>

        <form action="update_contact_section.php" method="POST" class="p-6 space-y-6">
            
            <!-- Section Title & Subtitle -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Section Title</label>
                    <input type="text" name="section_title" value="আমাদের সাথে যোগাযোগ করুন" required 
                        placeholder="যেমন: আমাদের সাথে যোগাযোগ করুন" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Section Subtitle / Description</label>
                    <textarea name="section_subtitle" rows="2" required 
                        placeholder="বর্ণনা লিখুন..." 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">আপনার যেকোনো জিজ্ঞাসা, পরামর্শ বা মতামতের জন্য আমাদের মেসেজ পাঠাতে পারেন। আমাদের প্রতিনিধি দ্রুত আপনার সাথে যোগাযোগ করবেন।</textarea>
                </div>
            </div>

            <!-- Contact Information Grid -->
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-4">
                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-circle-info text-emerald-600"></i>
                    Contact Card Details
                </h4>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Office Address -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">
                            <i class="fa-solid fa-location-dot text-emerald-600 mr-1"></i> কার্যালয়ের ঠিকানা
                        </label>
                        <textarea name="office_address" rows="3" required 
                            placeholder="ঠিকানা লিখুন..." 
                            class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">৬/জি/৫০/১, মীরবাগ হাতিরঝিল, নতুন রাস্তা, ৩ নং লেন, ঢাকা-১২১৭, বাংলাদেশ</textarea>
                    </div>

                    <!-- Direct Phone -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">
                            <i class="fa-solid fa-phone text-emerald-600 mr-1"></i> সরাসরি ফোন করুন
                        </label>
                        <input type="text" name="phone_number" value="+৮৮০ ১৭১৫-৪৮২৩৬৩" required 
                            placeholder="যেমন: +৮৮০ ১৭১৫-৪৮২৩৬৩" 
                            class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 mb-3">
                        
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">সাপোর্ট ঘণ্টা (Optional)</label>
                        <input type="text" name="support_hours" value="সকাল ৯টা - সন্ধ্যা ৬টা" 
                            placeholder="যেমন: সকাল ৯টা - সন্ধ্যা ৬টা" 
                            class="w-full text-xs bg-white border border-slate-200 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>

                    <!-- Email Address -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">
                            <i class="fa-solid fa-envelope text-emerald-600 mr-1"></i> ইমেইল করুন
                        </label>
                        <input type="email" name="email_address" value="info@bishwas.org" required 
                            placeholder="যেমন: info@bishwas.org" 
                            class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 mb-3">

                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1">বিকল্প ইমেইল (Optional)</label>
                        <input type="email" name="alt_email_address" value="support@bishwas.org" 
                            placeholder="যেমন: support@bishwas.org" 
                            class="w-full text-xs bg-white border border-slate-200 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>
            </div>

            <!-- Google Map Embed & Form Options -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">
                        <i class="fa-solid fa-map-location-dot text-emerald-600 mr-1"></i> Google Map Embed Iframe URL / Source
                    </label>
                    <textarea name="google_map_url" rows="3" required 
                        placeholder="Google Maps Embed URL..." 
                        class="w-full text-xs bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 font-mono">https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3651.5367... (Google Map Embed Link)</textarea>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Form Notification Email</label>
                    <input type="email" name="form_receiver_email" value="info@bishwas.org" required 
                        placeholder="যে ইমেইলে ফর্মের মেসেজগুলো যাবে" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 mb-2">
                    <p class="text-xs text-slate-400">ইউজাররা যোগাযোগের ফর্মে কোনো মেসেজ পাঠালে এই ইমেইলে তা ফরওয়ার্ড হবে।</p>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end pt-4 border-t border-slate-200">
                <button type="submit" 
                    class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-6 py-2.5 rounded-lg shadow text-sm transition-all">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save Contact Info
                </button>
            </div>

        </form>
    </div>
</div>