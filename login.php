<?php include 'include/header.php'; ?>
<div class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden">
        <!-- Top Branding Header -->
        <div class="bg-gradient-to-r from-emerald-600 to-teal-700 p-6 text-center text-white">
            <div class="w-16 h-16 bg-white rounded-full p-2 mx-auto mb-3 shadow-md">
                <img src="../public/assets/logo.png" alt="Logo" class="w-full h-full object-cover rounded-full" onerror="this.onerror=null; this.src='https://via.placeholder.com/64?text=BEF';">
            </div>
            <h2 class="text-2xl font-bold">bishwas<span class="text-emerald-200">.org</span></h2>
            <p class="text-xs text-emerald-100 mt-1 uppercase tracking-wider font-semibold">Admin & Volunteer Portal</p>
        </div>

        <!-- Login Form -->
        <form class="p-6 space-y-4" action="#" method="POST">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Email or Phone Number</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <i class="fa-solid fa-envelope"></i>
                    </span>
                    <input type="text" placeholder="example@mail.com" class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-emerald-500 focus:bg-white transition-all">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-600 mb-1">Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input type="password" placeholder="••••••••" class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm focus:outline-none focus:border-emerald-500 focus:bg-white transition-all">
                </div>
            </div>

            <div class="flex items-center justify-between text-xs">
                <label class="flex items-center text-slate-600 cursor-pointer">
                    <input type="checkbox" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 mr-2"> Remember me
                </label>
                <a href="#" class="text-emerald-600 font-semibold hover:underline">Forgot password?</a>
            </div>

            <button type="submit" class="w-full py-3 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg shadow-md shadow-emerald-600/20 transition-all duration-200 flex items-center justify-center gap-2">
                <span>Login</span>
                <i class="fa-solid fa-arrow-right text-xs"></i>
            </button>
        </form>
    </div>
</div>
<?php include 'include/footer.php'; ?>