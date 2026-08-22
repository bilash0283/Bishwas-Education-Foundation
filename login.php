<?php include 'include/header.php'; ?>

<div class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-4xl w-full bg-white rounded-3xl shadow-xl overflow-hidden grid grid-cols-1 md:grid-cols-2 border border-slate-100">
        
        <!-- Left Side: Login Form -->
        <div class="p-6 md:p-8 flex flex-col justify-center">
            <h2 class="text-xl md:text-2xl font-bold text-emerald-800 mb-6">
                আপনার অ্যাকাউন্টে লগইন করুন।
            </h2>

            <form action="#" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">
                        ইমেইল <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="identity" placeholder="মোবাইল নম্বর / ইমেইল লিখুন" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:bg-white transition-all placeholder:text-slate-400">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">
                        পাসওয়ার্ড <span class="text-red-500">*</span>
                    </label>
                    <input type="password" name="password" placeholder="পাসওয়ার্ড লিখুন" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:bg-white transition-all placeholder:text-slate-400">
                </div>

                <div class="pt-2">
                    <button type="submit" class="inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-semibold rounded-lg shadow-md transition-all duration-200">
                        <span>লগইন</span>
                        <i class="fa-solid fa-arrow-right text-sm"></i>
                    </button>
                </div>
            </form>
        </div>

        <!-- Right Side: Full Cover Image (No margin/padding) -->
        <div class="relative w-full h-full min-h-[200px] md:min-h-full">
            <img src="public/assets/login_img.png" alt="Illustration" class="w-full h-full object-cover block" onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300?text=Banner+Image';">
        </div>

    </div>
</div>

<?php include 'include/footer.php'; ?>