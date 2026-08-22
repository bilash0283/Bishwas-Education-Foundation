<?php include 'include/header.php'; ?>

  <main class="max-w-6xl mx-auto px-4 py-8 md:py-12">
    <!-- Breadcrumb -->
    <nav class="flex text-sm text-gray-500 mb-6" aria-label="Breadcrumb">
      <ol class="inline-flex items-center space-x-1 md:space-x-2">
        <li class="inline-flex items-center">
          <a href="#" class="hover:text-amber-600 transition-colors">হোম</a>
        </li>
        <li>
          <div class="flex items-center">
            <i class="fa-solid fa-chevron-right text-xs text-gray-400 mx-2"></i>
            <a href="#" class="hover:text-amber-600 transition-colors">কার্যক্রম</a>
          </div>
        </li>
        <li aria-current="page">
          <div class="flex items-center">
            <i class="fa-solid fa-chevron-right text-xs text-gray-400 mx-2"></i>
            <span class="text-gray-900 font-medium truncate max-w-xs md:max-w-none">সুবিধাবঞ্চিত শিশুদের শিক্ষা</span>
          </div>
        </li>
      </ol>
    </nav>

    <!-- Content Grid Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      
      <!-- Left / Main Details Section -->
      <div class="lg:col-span-2 space-y-6">
        
        <!-- Image Container -->
        <div class="relative w-full h-[320px] sm:h-[420px] rounded-2xl overflow-hidden shadow-md">
          <img 
            src="public/assets/gallery_img/5.jpg" 
            alt=" নিয়মিত কার্যক্রম" 
            class="w-full h-full object-cover transform hover:scale-105 transition-transform duration-500"
          />
        </div>

        <!-- Detail Card Wrapper -->
        <div class="bg-white rounded-2xl p-6 md:p-8 shadow-sm border border-gray-100 space-y-6">
          
          <!-- Category Badge -->
          <div>
            <span class="inline-flex items-center gap-2 px-3.5 py-1.5 rounded-full text-sm font-semibold bg-amber-50 text-amber-700 border border-amber-200/60">
              <i class="fa-solid fa-rocket text-amber-600"></i>
              নিয়মিত কার্যক্রম
            </span>
          </div>

          <!-- Main Title -->
          <h1 class="text-2xl sm:text-3xl md:text-4xl font-bold text-gray-900 leading-tight">
            সুবিধাবঞ্চিত শিশুদের শিক্ষা
          </h1>

          <!-- Featured Intro -->
          <p class="text-lg text-gray-600 leading-relaxed font-normal border-l-4 border-amber-500 pl-4 py-1 bg-amber-50/30 rounded-r-lg">
            আলোর দিশারী হয়ে সুবিধাবঞ্চিত শিশুদের মাঝে বিনামূল্যে দ্বীনি ও সাধারণ শিক্ষার আলো ছড়িয়ে দেওয়ার চমৎকার একটি চলমান প্রজেক্ট।
          </p>

          <hr class="border-gray-100" />

          <!-- Long Description & Objectives -->
          <div class="space-y-4 text-gray-700 leading-relaxed">
            <h3 class="text-xl font-bold text-gray-900">প্রজেক্টের মূল উদ্দেশ্য</h3>
            <p>
              আমাদের সমাজে অনেক শিশু দারিদ্র্য বা দিকনির্দেশনার অভাবে মৌলিক শিক্ষা থেকে বঞ্চিত হয়। এই উদ্যোগের প্রধান উদ্দেশ্য হলো সুবিধাবঞ্চিত শিশুদের বিনামূল্যে প্রাতিষ্ঠানিক ও নীতি-নৈতিকতাভিত্তিক শিক্ষার সুব্যবস্থা করা।
            </p>
            
            <h3 class="text-xl font-bold text-gray-900 pt-2">যা কিছু প্রদান করা হয়:</h3>
            <ul class="space-y-3">
              <li class="flex items-start gap-3">
                <i class="fa-solid fa-circle-check text-amber-600 mt-1"></i>
                <span>বিনামূল্যে দ্বীনি ও মৌলিক নৈতিক শিক্ষা।</span>
              </li>
              <li class="flex items-start gap-3">
                <i class="fa-solid fa-circle-check text-amber-600 mt-1"></i>
                <span>সাধারণ শিক্ষার প্রয়োজনীয় বই, খাতা ও শিক্ষাসামগ্রী বিতরণ।</span>
              </li>
              <li class="flex items-start gap-3">
                <i class="fa-solid fa-circle-check text-amber-600 mt-1"></i>
                <span>শিশুদের মানসিকভাবে উৎসাহিত করার জন্য নিয়মিত বিশেষ সেশন।</span>
              </li>
            </ul>
          </div>
        </div>
      </div>

      <!-- Right Sidebar / CTA Section -->
      <div class="lg:col-span-1">
        <div class="sticky top-6 bg-white rounded-2xl p-6 shadow-sm border border-gray-100 space-y-6">
          <h2 class="text-lg font-bold text-gray-900 border-b border-gray-100 pb-3">
            প্রজেক্টের সংক্ষিপ্ত বিবরণ
          </h2>

          <div class="space-y-4">
            <div class="flex items-center gap-4">
              <div class="w-10 h-10 rounded-xl bg-amber-50 flex items-center justify-center text-amber-600 shrink-0">
                <i class="fa-solid fa-bars-staggered"></i>
              </div>
              <div>
                <p class="text-xs text-gray-400 font-medium">ক্যাটাগরি</p>
                <p class="text-sm font-semibold text-gray-800">নিয়মিত কার্যক্রম</p>
              </div>
            </div>

            <div class="flex items-center gap-4">
              <div class="w-10 h-10 rounded-xl bg-green-50 flex items-center justify-center text-green-600 shrink-0">
                <i class="fa-solid fa-signal"></i>
              </div>
              <div>
                <p class="text-xs text-gray-400 font-medium">স্ট্যাটাস</p>
                <p class="text-sm font-semibold text-gray-800">সক্রিয় প্রজেক্ট</p>
              </div>
            </div>

            <div class="flex items-center gap-4">
              <div class="w-10 h-10 rounded-xl bg-blue-50 flex items-center justify-center text-blue-600 shrink-0">
                <i class="fa-solid fa-heart"></i>
              </div>
              <div>
                <p class="text-xs text-gray-400 font-medium">লক্ষ্য</p>
                <p class="text-sm font-semibold text-gray-800">সুবিধাবঞ্চিত শিশুদের সহায়তা</p>
              </div>
            </div>
          </div>

          <div class="pt-2 space-y-3">
            <button class="w-full bg-amber-600 hover:bg-amber-700 text-white font-medium py-3 px-4 rounded-xl shadow-sm transition-all duration-200 flex items-center justify-center gap-2">
              <i class="fa-solid fa-hand-holding-heart"></i>
              <span>সহযোগিতা করুন</span>
            </button>
            <button class="w-full bg-gray-50 hover:bg-gray-100 text-gray-700 font-medium py-3 px-4 rounded-xl border border-gray-200 transition-all duration-200 flex items-center justify-center gap-2">
              <i class="fa-solid fa-share-nodes"></i>
              <span>শেয়ার করুন</span>
            </button>
          </div>
        </div>
      </div>

    </div>
  </main>

<?php include 'include/footer.php'; ?>