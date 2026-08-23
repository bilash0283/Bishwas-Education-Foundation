<!-- Hero Section Settings Container -->
<div class="p-4 sm:p-6 lg:p-8">
  
  <!-- Section Header -->
  <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-5">
    <div>
      <h2 class="text-2xl font-bold text-slate-800">Manage Hero Section</h2>
      <p class="text-sm text-slate-500">Update main homepage hero banners, texts, CTA buttons, and counter statistics</p>
    </div>
    <div class="flex items-center gap-3">
      <button type="reset" form="heroForm" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium px-4 py-2.5 rounded-lg border border-slate-200 transition-all">
        <i class="fa-solid fa-rotate-left"></i>
        Reset
      </button>
      <button type="submit" form="heroForm" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-5 py-2.5 rounded-lg shadow transition-all">
        <i class="fa-solid fa-floppy-disk"></i>
        Save Changes
      </button>
    </div>
  </div>

  <form id="heroForm" action="update_hero.php" method="POST" enctype="multipart/form-data" class="space-y-6">

    <!-- Card 1: Background & Overlay Settings -->
    <!-- <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
        <h3 class="font-bold text-slate-800 flex items-center gap-2">
          <i class="fa-solid fa-image text-emerald-600"></i>
          1. Background & Overlay Settings
        </h3>
        <span class="text-xs bg-emerald-100 text-emerald-800 font-semibold px-2.5 py-1 rounded-full">Media</span>
      </div>
      <div class="p-5 grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Background Image</label>
          <input type="file" name="bg_image" class="block w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-slate-200 rounded-lg bg-slate-50 cursor-pointer">
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Overlay Color</label>
          <div class="flex items-center gap-3">
            <input type="color" name="overlay_color" value="#0d2229" class="h-10 w-12 bg-white border border-slate-200 rounded-lg cursor-pointer p-1">
            <input type="text" value="#0d2229" class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-2">Overlay Opacity (0.1 - 1.0)</label>
          <input type="number" step="0.05" min="0" max="1" name="overlay_opacity" value="0.85" class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2 text-slate-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
        </div>
      </div>
    </div> -->

    <!-- Card 2: Main Hero Texts -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
        <h3 class="font-bold text-slate-800 flex items-center gap-2">
          <i class="fa-solid fa-heading text-emerald-600"></i>
          2. Hero Content & Headlines
        </h3>
        <span class="text-xs bg-slate-100 text-slate-700 font-semibold px-2.5 py-1 rounded-full">Text Data</span>
      </div>
      <div class="p-5 space-y-5">
        <div>
          <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Top Badge Text</label>
          <input type="text" name="badge_text" value="মানবসেবায় একটি বিশ্বস্তযোগ্য প্রতিষ্ঠান" class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Main Title (White)</label>
            <input type="text" name="heading_title" value="জন স্বার্থে," class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Title Highlight (Green)</label>
            <input type="text" name="heading_highlight" value="বিশ্বাস ও আস্থার সাথে।" class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Description</label>
          <textarea name="description" rows="3" class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">বিশ্বাস এডুকেশন ফাউন্ডেশন একটি অলাভজনক ও সম্পূর্ণ দাতব্য সংস্থা যা মানুষের কল্যাণ, শিক্ষা বিস্তার, ও দুস্থদের কর্মসংস্থান তৈরিতে নিরলসভাবে কাজ করে যাচ্ছে। আপনার একটি ছোট অনুদান বদলে দিতে পারে একটি অসহায় পরিবারের ভাগ্য।</textarea>
        </div>
      </div>
    </div>

    <!-- Card 3: Action Buttons -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
        <h3 class="font-bold text-slate-800 flex items-center gap-2">
          <i class="fa-solid fa-link text-emerald-600"></i>
          3. Call-To-Action Buttons
        </h3>
        <span class="text-xs bg-slate-100 text-slate-700 font-semibold px-2.5 py-1 rounded-full">Buttons</span>
      </div>
      <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Primary Button -->
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-4">
          <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider block">Primary Button (Solid Green)</span>
          <div>
            <label class="block text-xs text-slate-500 mb-1 font-medium">Button Label</label>
            <input type="text" name="cta_primary_text" value="আজই শরীক হোন" class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <!-- <div>
            <label class="block text-xs text-slate-500 mb-1 font-medium">Button Target Link</label>
            <input type="text" name="cta_primary_link" value="/donate" class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div> -->
        </div>
        <!-- Secondary Button -->
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-4">
          <span class="text-xs font-bold text-slate-600 uppercase tracking-wider block">Secondary Button (Outlined)</span>
          <div>
            <label class="block text-xs text-slate-500 mb-1 font-medium">Button Label</label>
            <input type="text" name="cta_secondary_text" value="আমাদের লক্ষ্য জানুন" class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <!-- <div>
            <label class="block text-xs text-slate-500 mb-1 font-medium">Button Target Link</label>
            <input type="text" name="cta_secondary_link" value="/about-us" class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div> -->
        </div>
      </div>
    </div>

    <!-- Card 4: Right Side Counter Cards -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
        <h3 class="font-bold text-slate-800 flex items-center gap-2">
          <i class="fa-solid fa-chart-simple text-emerald-600"></i>
          4. Dynamic Statistics Cards (Right Side)
        </h3>
        <span class="text-xs bg-amber-100 text-amber-800 font-semibold px-2.5 py-1 rounded-full">4 Items</span>
      </div>
      <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Stat 1 -->
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
          <span class="text-xs font-bold text-slate-400 uppercase">Stat Box 1</span>
          <div>
            <label class="block text-xs text-slate-500 mb-1">Number / Value</label>
            <input type="text" name="stat_1_number" value="১৭০০+" class="w-full text-sm font-bold text-emerald-600 bg-white border border-slate-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-xs text-slate-500 mb-1">Label Text</label>
            <input type="text" name="stat_1_label" value="উপকারভোগী মানুষ" class="w-full text-xs text-slate-700 bg-white border border-slate-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
        </div>

        <!-- Stat 2 -->
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
          <span class="text-xs font-bold text-slate-400 uppercase">Stat Box 2</span>
          <div>
            <label class="block text-xs text-slate-500 mb-1">Number / Value</label>
            <input type="text" name="stat_2_number" value="১০+" class="w-full text-sm font-bold text-emerald-600 bg-white border border-slate-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-xs text-slate-500 mb-1">Label Text</label>
            <input type="text" name="stat_2_label" value="সক্রিয় প্রজেক্ট" class="w-full text-xs text-slate-700 bg-white border border-slate-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
        </div>

        <!-- Stat 3 -->
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
          <span class="text-xs font-bold text-slate-400 uppercase">Stat Box 3</span>
          <div>
            <label class="block text-xs text-slate-500 mb-1">Number / Value</label>
            <input type="text" name="stat_3_number" value="১০০%" class="w-full text-sm font-bold text-emerald-600 bg-white border border-slate-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-xs text-slate-500 mb-1">Label Text</label>
            <input type="text" name="stat_3_label" value="স্বচ্ছতা ও আমানত" class="w-full text-xs text-slate-700 bg-white border border-slate-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
        </div>

        <!-- Stat 4 -->
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
          <span class="text-xs font-bold text-slate-400 uppercase">Stat Box 4</span>
          <div>
            <label class="block text-xs text-slate-500 mb-1">Number / Value</label>
            <input type="text" name="stat_4_number" value="১০০+" class="w-full text-sm font-bold text-emerald-600 bg-white border border-slate-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-xs text-slate-500 mb-1">Label Text</label>
            <input type="text" name="stat_4_label" value="নিবন্ধিত ভলান্টিয়ার" class="w-full text-xs text-slate-700 bg-white border border-slate-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
        </div>

      </div>
    </div>

  </form>
</div>