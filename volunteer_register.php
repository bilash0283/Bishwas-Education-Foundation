<?php
include 'include/header.php';

?>

<script>
  tailwind.config = {
    theme: {
      extend: {
        colors: {
          brand: {
            50: '#f0f9ff',
            100: '#e0f2fe',
            500: '#0284c7',
            600: '#0369a1',
            700: '#1d4ed8',
            800: '#1e40af',
          }
        }
      }
    }
  }
</script>
<link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>
    body {
        font-family: 'Inter', 'Hind Siliguri', sans-serif;
    }
</style>

<main class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto space-y-8">
        
        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-slate-800 to-brand-600 text-white shadow-xl p-6 sm:p-10">
            <!-- Background Decorative Blur Rings -->
            <div class="absolute -right-10 -top-10 h-64 w-64 rounded-full bg-emerald-500/20 blur-3xl pointer-events-none"></div>
            <div class="absolute right-1/3 -bottom-10 h-48 w-48 rounded-full bg-brand-500/30 blur-2xl pointer-events-none"></div>

            <div class="relative z-10 flex flex-col md:flex-row items-center gap-6">
                <!-- Foundation SVG Logo Wrapper -->
                <div class="flex-shrink-0 bg-white p-2 rounded-2xl shadow-lg">
                    <img 
                        src="public/assets/<?php echo $favicon_icon; ?>" 
                        alt="Logo"
                        class="w-16 h-16 sm:w-20 sm:h-20 object-contain rounded-full"
                    >
                </div>

                <!-- Text Header Details -->
                <div class="text-center md:text-left space-y-2">
                    <div class="inline-flex items-center gap-2 bg-white/10 backdrop-blur-md px-3 py-1 rounded-full text-xs font-semibold tracking-wide text-brand-100 border border-white/10">
                        <i class="fa-solid fa-shield-halved text-emerald-400"></i>
                        Official Member Portal
                    </div>
                    <h1 class="text-2xl sm:text-3xl uppercase font-extrabold tracking-tight text-white">
                        <?php echo $site_title; ?>
                    </h1>
                    <p class="text-sm sm:text-base text-slate-200 font-medium leading-relaxed max-w-2xl">
                        নারী, পুরুষ, শিশু ও প্রতিবন্ধীদের অধিকার আদায় ও কল্যাণে কাজ করাই আমাদের উদ্দেশ্য ও লক্ষ্য।
                    </p>
                    <div class="flex flex-wrap justify-center md:justify-start gap-4 pt-2 text-xs text-slate-300">
                        <span class="flex items-center gap-1.5"><i class="fa-solid fa-location-dot text-brand-400"></i> 249, Moghbazar Chowrasta, Dhaka-1217</span>
                        <span class="flex items-center gap-1.5"><i class="fa-solid fa-phone text-brand-400"></i> 01836615662, 01715482363</span>
                        <span class="flex items-center gap-1.5"><i class="fa-solid fa-envelope text-brand-400"></i> info.bishwas@gmail.com</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-200/80 p-6 sm:p-10">
            
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-100 mb-8">
                <div>
                    <h2 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                        Member Registration Form / সদস্য আবেদন পত্র
                    </h2>
                    <p class="text-xs sm:text-sm text-slate-500 mt-1">Please fill in all required information accurately.</p>
                </div>
                <div class="text-xs font-medium text-slate-400 bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-lg w-max">
                    Form Ref: BEF-MEM-2026
                </div>
            </div>

            <!-- Main PHP Registration Form -->
            <form id="memberRegistrationForm" action="process_member.php" method="POST" enctype="multipart/form-data" class="space-y-8" novalidate>
                
                <div class="space-y-6">
                    <div class="flex items-center gap-3 text-brand-600 border-b border-brand-50 pb-3">
                        <div class="w-8 h-8 rounded-lg bg-brand-50 flex items-center justify-center text-brand-600 font-bold text-sm">
                            <i class="fa-solid fa-user"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-900">Personal Details / ব্যক্তিগত তথ্য</h3>
                    </div>

                    <!-- Top Row: Photo Upload & Serial Number -->
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-start">
                        
                        <!-- Photo Drag & Drop Container -->
                        <div class="md:col-span-4 flex flex-col items-center">
                            <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">
                                Applicant Photo / ছবি
                            </label>
                            <div id="dropZone" onclick="document.getElementById('photoInput').click();" class="relative group w-36 h-44 rounded-2xl border-2 border-dashed border-slate-300 hover:border-brand-500 bg-slate-50 hover:bg-brand-50/30 transition-all cursor-pointer flex flex-col items-center justify-center text-center p-3 overflow-hidden shadow-inner">
                                <div id="uploadPlaceholder" class="flex flex-col items-center gap-2 transition-transform group-hover:scale-105">
                                    <div class="w-10 h-10 rounded-full bg-white shadow-sm flex items-center justify-center text-slate-400 group-hover:text-brand-600">
                                        <i class="fa-solid fa-camera text-lg"></i>
                                    </div>
                                    <span class="text-xs font-semibold text-slate-600">Click to Upload</span>
                                    <span class="text-[10px] text-slate-400">JPG, PNG (Max 2MB)</span>
                                </div>
                                <img id="avatarPreview" class="hidden absolute inset-0 w-full h-full object-cover rounded-2xl" alt="Preview">
                                <button type="button" id="removePhotoBtn" onclick="removeAvatar(event)" class="hidden absolute top-2 right-2 bg-rose-500 text-white w-6 h-6 rounded-full flex items-center justify-center text-xs shadow-md hover:bg-rose-600 z-10">
                                    <i class="fa-solid fa-xmark"></i>
                                </button>
                            </div>
                            <input type="file" id="photoInput" name="photo" accept="image/*" class="hidden" onchange="previewAvatar(this)">
                        </div>

                        <!-- Serial & Main Names -->
                        <div class="md:col-span-8 space-y-4">
                            <div>
                                <label for="serial_no" class="block text-xs font-semibold text-slate-700 mb-1">
                                    Serial No. / ক্রমিক নং
                                </label>
                                <input type="text" id="serial_no" name="serial_no" placeholder="Auto-assigned or leave blank" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 bg-slate-50/50 text-slate-800 text-sm focus:bg-white focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 transition-all outline-none">
                            </div>

                            <div>
                                <label for="member_name" class="block text-xs font-semibold text-slate-700 mb-1">
                                    Member's Full Name / সদস্যের পূর্ণ নাম <span class="text-rose-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 text-sm">
                                        <i class="fa-regular fa-user"></i>
                                    </div>
                                    <input type="text" id="member_name" name="member_name" required placeholder="Enter full name" class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-slate-800 text-sm focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 transition-all outline-none">
                                </div>
                                <p id="member_name_err" class="hidden text-xs text-rose-500 mt-1">Full name is required.</p>
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label for="mother_name" class="block text-xs font-semibold text-slate-700 mb-1">
                                        Mother's Name / মাতার নাম <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="text" id="mother_name" name="mother_name" required placeholder="Mother's name" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-slate-800 text-sm focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 transition-all outline-none">
                                </div>
                                <div>
                                    <label for="father_husband_name" class="block text-xs font-semibold text-slate-700 mb-1">
                                        Father / Husband's Name / পিতা/স্বামীর নাম <span class="text-rose-500">*</span>
                                    </label>
                                    <input type="text" id="father_husband_name" name="father_husband_name" required placeholder="Father or Husband's name" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-slate-800 text-sm focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 transition-all outline-none">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Additional Personal Metadata Grid -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4">
                        <div>
                            <label for="dob" class="block text-xs font-semibold text-slate-700 mb-1">
                                Date of Birth / জন্ম তারিখ <span class="text-rose-500">*</span>
                            </label>
                            <input type="date" id="dob" name="dob" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-slate-800 text-sm focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 transition-all outline-none">
                        </div>

                        <!-- Gender Selector Cards -->
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-semibold text-slate-700 mb-1">
                                Gender / লিঙ্গ <span class="text-rose-500">*</span>
                            </label>
                            <div class="grid grid-cols-3 gap-2">
                                <label class="relative flex items-center justify-center p-2.5 rounded-xl border border-slate-200 bg-white cursor-pointer hover:border-brand-500 transition-all has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50/50 has-[:checked]:text-brand-600 font-medium text-xs text-slate-600 shadow-sm">
                                    <input type="radio" name="gender" value="Male" required class="sr-only">
                                    <i class="fa-solid fa-mars mr-1.5 text-slate-400 group-has-[:checked]:text-brand-600"></i> Male / পুরুষ
                                </label>
                                <label class="relative flex items-center justify-center p-2.5 rounded-xl border border-slate-200 bg-white cursor-pointer hover:border-brand-500 transition-all has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50/50 has-[:checked]:text-brand-600 font-medium text-xs text-slate-600 shadow-sm">
                                    <input type="radio" name="gender" value="Female" class="sr-only">
                                    <i class="fa-solid fa-venus mr-1.5 text-slate-400 group-has-[:checked]:text-brand-600"></i> Female / মহিলা
                                </label>
                                <label class="relative flex items-center justify-center p-2.5 rounded-xl border border-slate-200 bg-white cursor-pointer hover:border-brand-500 transition-all has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50/50 has-[:checked]:text-brand-600 font-medium text-xs text-slate-600 shadow-sm">
                                    <input type="radio" name="gender" value="Other" class="sr-only">
                                    Other / অন্যান্য
                                </label>
                            </div>
                        </div>

                        <div>
                            <label for="nid_birth" class="block text-xs font-semibold text-slate-700 mb-1">
                                National ID / Birth Cert No
                            </label>
                            <input type="text" id="nid_birth" name="nid_birth" placeholder="e.g. 1998765432109" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-slate-800 text-sm focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 transition-all outline-none">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="qualification" class="block text-xs font-semibold text-slate-700 mb-1">
                                Qualification / শিক্ষাগত যোগ্যতা
                            </label>
                            <input type="text" id="qualification" name="qualification" placeholder="e.g. Master's / Bachelor / HSC" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-slate-800 text-sm focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 transition-all outline-none">
                        </div>
                    </div>
                </div>

                <div class="space-y-6 pt-4 border-t border-slate-100">
                    <div class="flex items-center gap-3 text-brand-600 border-b border-brand-50 pb-3">
                        <div class="w-8 h-8 rounded-lg bg-brand-50 flex items-center justify-center text-brand-600 font-bold text-sm">
                            <i class="fa-solid fa-address-book"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-900">Contact & Address / যোগাযোগের তথ্য ও ঠিকানা</h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="mobile_no" class="block text-xs font-semibold text-slate-700 mb-1">
                                Mobile Number / মোবাইল নম্বর <span class="text-rose-500">*</span>
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-xs font-bold pointer-events-none">+88</span>
                                <input type="tel" id="mobile_no" name="mobile_no" required pattern="[0-9]{11}" placeholder="01700000000" class="w-full pl-12 pr-4 py-2.5 rounded-xl border border-slate-200 text-slate-800 text-sm focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 transition-all outline-none">
                            </div>
                        </div>

                        <div>
                            <label for="email" class="block text-xs font-semibold text-slate-700 mb-1">
                                Email Address / ই-মেইল
                            </label>
                            <div class="relative">
                                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center text-slate-400 text-sm pointer-events-none"><i class="fa-regular fa-envelope"></i></span>
                                <input type="email" id="email" name="email" placeholder="name@example.com" class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 text-slate-800 text-sm focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 transition-all outline-none">
                            </div>
                        </div>
                    </div>

                    <!-- Present Address -->
                    <div>
                        <label for="present_address" class="block text-xs font-semibold text-slate-700 mb-1">
                            Present Address / বর্তমান ঠিকানা <span class="text-rose-500">*</span>
                        </label>
                        <textarea id="present_address" name="present_address" rows="2" required placeholder="House/Road/Thana/District" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-slate-800 text-sm focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 transition-all outline-none"></textarea>
                    </div>

                    <!-- Permanent Address with Sync Option -->
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <label for="permanent_address" class="block text-xs font-semibold text-slate-700">
                                Permanent Address / স্থায়ী ঠিকানা <span class="text-rose-500">*</span>
                            </label>
                            <label class="flex items-center gap-1.5 cursor-pointer text-xs text-brand-600 hover:text-brand-700 font-semibold select-none">
                                <input type="checkbox" id="syncAddressCheck" onchange="syncAddressToggle()" class="rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                                Same as Present Address
                            </label>
                        </div>
                        <textarea id="permanent_address" name="permanent_address" rows="2" required placeholder="House/Road/Thana/District" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-slate-800 text-sm focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 transition-all outline-none"></textarea>
                    </div>

                    <div>
                        <label for="other_info" class="block text-xs font-semibold text-slate-700 mb-1">
                            Other's Information / অন্যান্য তথ্য (Occupation / Interests)
                        </label>
                        <input type="text" id="other_info" name="other_info" placeholder="Occupation, special skills or extra info" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-slate-800 text-sm focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 transition-all outline-none">
                    </div>
                </div>

                <div class="space-y-6 pt-4 border-t border-slate-100">
                    <div class="flex items-center gap-3 text-brand-600 border-b border-brand-50 pb-3">
                        <div class="w-8 h-8 rounded-lg bg-brand-50 flex items-center justify-center text-brand-600 font-bold text-sm">
                            <i class="fa-solid fa-id-card"></i>
                        </div>
                        <h3 class="text-base font-bold text-slate-900">Membership Category / সদস্যের ধরন <span class="text-rose-500">*</span></h3>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        
                        <!-- General Member Card -->
                        <label class="relative group flex flex-col items-center p-4 rounded-2xl border-2 border-slate-200 bg-white cursor-pointer hover:border-brand-500 hover:shadow-md transition-all has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50/40">
                            <input type="radio" name="membership_status" value="General Member" required checked class="sr-only">
                            <div class="w-12 h-12 rounded-xl bg-slate-100 group-has-[:checked]:bg-brand-500 text-slate-500 group-has-[:checked]:text-white flex items-center justify-center text-xl mb-2 transition-colors">
                                <i class="fa-solid fa-user-check"></i>
                            </div>
                            <span class="text-sm font-bold text-slate-800 group-has-[:checked]:text-brand-700">General Member</span>
                            <span class="text-xs text-slate-500">সাধারণ সদস্য</span>
                        </label>

                        <!-- Associate Member Card -->
                        <label class="relative group flex flex-col items-center p-4 rounded-2xl border-2 border-slate-200 bg-white cursor-pointer hover:border-brand-500 hover:shadow-md transition-all has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50/40">
                            <input type="radio" name="membership_status" value="Associate Member" class="sr-only">
                            <div class="w-12 h-12 rounded-xl bg-slate-100 group-has-[:checked]:bg-brand-500 text-slate-500 group-has-[:checked]:text-white flex items-center justify-center text-xl mb-2 transition-colors">
                                <i class="fa-solid fa-user-gear"></i>
                            </div>
                            <span class="text-sm font-bold text-slate-800 group-has-[:checked]:text-brand-700">Associate Member</span>
                            <span class="text-xs text-slate-500">সহকারী সদস্য</span>
                        </label>

                        <!-- Life Member Card -->
                        <label class="relative group flex flex-col items-center p-4 rounded-2xl border-2 border-slate-200 bg-white cursor-pointer hover:border-brand-500 hover:shadow-md transition-all has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50/40">
                            <input type="radio" name="membership_status" value="Life Member" class="sr-only">
                            <div class="w-12 h-12 rounded-xl bg-slate-100 group-has-[:checked]:bg-brand-500 text-slate-500 group-has-[:checked]:text-white flex items-center justify-center text-xl mb-2 transition-colors">
                                <i class="fa-solid fa-crown"></i>
                            </div>
                            <span class="text-sm font-bold text-slate-800 group-has-[:checked]:text-brand-700">Life Member</span>
                            <span class="text-xs text-slate-500">আজীবন সদস্য</span>
                        </label>

                        <!-- Volunteer Member Card -->
                        <label class="relative group flex flex-col items-center p-4 rounded-2xl border-2 border-slate-200 bg-white cursor-pointer hover:border-brand-500 hover:shadow-md transition-all has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50/40">
                            <input type="radio" name="membership_status" value="Volunteer Member" class="sr-only">
                            <div class="w-12 h-12 rounded-xl bg-slate-100 group-has-[:checked]:bg-brand-500 text-slate-500 group-has-[:checked]:text-white flex items-center justify-center text-xl mb-2 transition-colors">
                                <i class="fa-solid fa-hand-holding-heart"></i>
                            </div>
                            <span class="text-sm font-bold text-slate-800 group-has-[:checked]:text-brand-700">Volunteer Member</span>
                            <span class="text-xs text-slate-500">স্বেচ্ছাসেবক সদস্য</span>
                        </label>
                    </div>
                </div>

                <div class="pt-4 border-t border-slate-100 space-y-4">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label for="form_date" class="block text-xs font-semibold text-slate-700 mb-1">
                                Form Submission Date / তারিখ
                            </label>
                            <input type="date" id="form_date" name="form_date" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-slate-800 text-sm focus:border-brand-500 focus:ring-4 focus:ring-brand-500/10 transition-all outline-none">
                        </div>
                    </div>

                    <div class="flex items-start gap-2.5 pt-2">
                        <input type="checkbox" id="termsCheck" required class="mt-0.5 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        <label for="termsCheck" class="text-xs text-slate-600 leading-relaxed cursor-pointer select-none">
                            I hereby declare that all information provided above is true and accurate to the best of my knowledge. I agree to abide by the rules and regulations of Bishwas Education Foundation.
                        </label>
                    </div>
                </div>

                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 pt-6 border-t border-slate-100">
                    <div class="text-xs text-slate-400 flex items-center gap-1.5">
                        <i class="fa-solid fa-lock text-emerald-500"></i> Encrypted & Secure Portal
                    </div>

                    <button type="submit" id="submitBtn" class="w-full sm:w-auto px-8 py-3.5 rounded-xl bg-gradient-to-r from-brand-600 to-brand-500 hover:from-brand-700 hover:to-brand-600 text-white font-bold text-sm shadow-lg shadow-brand-500/25 transition-all transform hover:-translate-y-0.5 active:translate-y-0 flex items-center justify-center gap-2">
                        <span>Complete Registration</span>
                        <i class="fa-solid fa-arrow-right text-xs"></i>
                    </button>
                </div>

            </form>
        </div>
    </div>
</main>

<script>
    // Set default date to today
    document.getElementById('form_date').valueAsDate = new Date();

    // Photo Preview Logic
    function previewAvatar(input) {
        const preview = document.getElementById('avatarPreview');
        const placeholder = document.getElementById('uploadPlaceholder');
        const removeBtn = document.getElementById('removePhotoBtn');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function (e) {
                preview.src = e.target.result;
                preview.classList.remove('hidden');
                placeholder.classList.add('hidden');
                removeBtn.classList.remove('hidden');
            };
            reader.readAsDataURL(input.files[0]);
        }
    }

    function removeAvatar(e) {
        e.stopPropagation();
        const input = document.getElementById('photoInput');
        const preview = document.getElementById('avatarPreview');
        const placeholder = document.getElementById('uploadPlaceholder');
        const removeBtn = document.getElementById('removePhotoBtn');
        
        input.value = '';
        preview.src = '';
        preview.classList.add('hidden');
        placeholder.classList.remove('hidden');
        removeBtn.classList.add('hidden');
    }

    // Address Sync Logic
    function syncAddressToggle() {
        const isChecked = document.getElementById('syncAddressCheck').checked;
        const present = document.getElementById('present_address').value;
        const permanent = document.getElementById('permanent_address');
        
        if (isChecked) {
            permanent.value = present;
        }
    }

    // Keep permanent address synced if checkbox is active
    document.getElementById('present_address').addEventListener('input', function() {
        if (document.getElementById('syncAddressCheck').checked) {
            document.getElementById('permanent_address').value = this.value;
        }
    });

    // Form Client Validation & Loading Indicator
    document.getElementById('memberRegistrationForm').addEventListener('submit', function(e) {
        const requiredFields = this.querySelectorAll('[required]');
        let isValid = true;

        requiredFields.forEach(field => {
            if (!field.value.trim() && field.type !== 'radio' && field.type !== 'checkbox') {
                isValid = false;
                field.classList.add('border-rose-500', 'bg-rose-50/30');
            } else {
                field.classList.remove('border-rose-500', 'bg-rose-50/30');
            }
        });

        if (isValid) {
            const btn = document.getElementById('submitBtn');
            btn.disabled = true;
            btn.innerHTML = `<i class="fa-solid fa-circle-notch fa-spin text-sm"></i> Submitting...`;
        }
    });
</script>

<?php include 'include/footer.php'; ?>