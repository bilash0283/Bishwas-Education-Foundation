<section class="min-h-[80vh] flex items-center justify-center p-4">
    <div class="max-w-md w-full text-center space-y-6">
        
        <div class="relative w-full max-w-xs mx-auto flex justify-center">
            <svg class="w-64 h-64 text-emerald-600 animate-pulse" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round">
                <circle cx="12" cy="12" r="10" class="text-slate-200" stroke="currentColor" stroke-width="1.5" />
                <path d="m21 21-4.3-4.3" class="text-emerald-500" stroke-width="2" />
                <circle cx="11" cy="11" r="6" class="text-emerald-500" stroke-width="2" />
                <line x1="11" y1="8" x2="11" y2="11" class="text-rose-500" stroke-width="2" />
                <line x1="11" y1="13" x2="11.01" y2="13" class="text-rose-500" stroke-width="3" />
            </svg>
            
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 bg-white/90 backdrop-blur-md border border-slate-200/80 px-4 py-2 rounded-2xl shadow-lg animate-bounce">
                <span class="text-4xl font-extrabold text-slate-800 tracking-wider">404</span>
            </div>
        </div>

        <div class="space-y-2">
            <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Page Not Found</h2>
            <p class="text-xs text-slate-500 max-w-xs mx-auto leading-relaxed">
                Oops! The page you are looking for doesn't exist, has been removed, or is temporarily unavailable.
            </p>
        </div>

        <div class="flex items-center justify-center gap-3 pt-2">
            <button onclick="history.back()" 
                class="px-4 py-2.5 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors flex items-center gap-2">
                <i class="fa-solid fa-arrow-left"></i> Go Back
            </button>
            <a href="index.php" 
                class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-emerald-600/20 transition-colors flex items-center gap-2">
                <i class="fa-solid fa-house"></i> Return Home
            </a>
        </div>

    </div>
</section>