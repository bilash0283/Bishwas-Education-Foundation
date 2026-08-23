<div class="p-4 sm:p-6 lg:p-8">
    
    <!-- Page Header -->
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">About & Vision Settings</h2>
            <p class="text-sm text-slate-500">Manage 'আমাদের লক্ষ্য ও উদ্দেশ্য' section content and image for bishwas.org</p>
        </div>
        <div class="flex items-center gap-2">
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800 border border-emerald-200">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                Single Section Page
            </span>
        </div>
    </div>

    <!-- Analytics / Status Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Section Name</p>
                <h3 class="text-lg font-bold text-slate-800 mt-1">আমাদের লক্ষ্য ও উদ্দেশ্য</h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-bullseye"></i>
            </div>
        </div>
        <div class="bg-white p-5 rounded-xl border border-slate-200 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-semibold text-slate-400 uppercase">Feature List Items</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-1">03 Points</h3>
            </div>
            <div class="w-12 h-12 rounded-lg bg-blue-100 text-blue-600 flex items-center justify-center text-xl">
                <i class="fa-solid fa-circle-check"></i>
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

    <!-- Main Content Form Card -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden mb-8">
        <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 flex items-center gap-2">
                <i class="fa-solid fa-pen-to-square text-emerald-600"></i>
                Edit Vision & Mission Content
            </h3>
            <span class="text-xs bg-emerald-100 text-emerald-800 font-semibold px-2.5 py-1 rounded-full">Frontend Display</span>
        </div>

        <form action="update_about_section.php" method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
            
            <!-- Grid 1: Top Subtitle & Main Title -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Top Subtitle</label>
                    <input type="text" name="top_subtitle" value="আমাদের লক্ষ্য ও উদ্দেশ্য" required 
                        placeholder="যেমন: আমাদের লক্ষ্য ও উদ্দেশ্য" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Main Heading / Title</label>
                    <input type="text" name="main_title" value="একটি আদর্শ ও আত্মনির্ভরশীল সমাজ বিনির্মাণ" required 
                        placeholder="যেমন: একটি আদর্শ ও আত্মনির্ভরশীল সমাজ বিনির্মাণ" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <!-- Grid 2: Description Paragraph -->
            <div>
                <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Main Description Paragraph</label>
                <textarea name="description" rows="3" required 
                    placeholder="বিবরণ লিখুন..." 
                    class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">বিশ্বাস এডুকেশন ফাউন্ডেশন (Bishwas Education Foundation.) একটি সম্পূর্ণ অরাজনৈতিক ও জনকল্যাণমূলক সেবা সংস্থা। সমাজের অবহেলিত ও দরিদ্র শ্রেণীর মানুষের মৌলিক চাহিদা পূরণ এবং তাদের কারিগরি শিক্ষার মাধ্যমে স্বাবলম্বী করে তোলাই আমাদের মূল ব্রত।</textarea>
            </div>

            <!-- Grid 3: Checklist / Key Points -->
            <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-4">
                <h4 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                    <i class="fa-solid fa-list-check text-emerald-600"></i>
                    Key Feature Checklist Points
                </h4>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Point 1</label>
                        <input type="text" name="point_1" value="স্বচ্ছ ও জবাবদিহিতামূলক তহবিল বণ্টন ব্যবস্থা।" required 
                            class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Point 2</label>
                        <input type="text" name="point_2" value="জ্ঞান, নৈতিকতা ও মানবিক মূল্যবোধের বিকাশ।" required 
                            class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 mb-1">Point 3</label>
                        <input type="text" name="point_3" value="দক্ষতা বৃদ্ধি ও বেকারত্ব দূরীকরণে প্রশিক্ষণ ইনস্টিটিউট।" required 
                            class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    </div>
                </div>
            </div>

            <!-- Grid 4: Left Side Image & Overlay Quote Badge -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-2">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Section Left Image</label>
                    <div class="flex items-center gap-4">
                        <img src="uploads/vision-banner.jpg" alt="Current Image" class="w-20 h-16 rounded-lg object-cover border border-slate-200 shrink-0">
                        <input type="file" name="image" 
                            class="block w-full text-xs text-slate-500 file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-slate-200 rounded-lg bg-slate-50 cursor-pointer">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Image Overlay Quote Text</label>
                    <input type="text" name="quote_badge" value="&quot;মানবি সেবাই ইসলামের মূল শিক্ষা।&quot;" required 
                        placeholder="যেমন: &quot;মানব সেবাই ইসলামের মূল শিক্ষা।&quot;" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-end pt-4 border-t border-slate-200">
                <button type="submit" 
                    class="inline-flex items-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-6 py-2.5 rounded-lg shadow text-sm transition-all">
                    <i class="fa-solid fa-floppy-disk"></i>
                    Save Changes
                </button>
            </div>

        </form>
    </div>
</div>