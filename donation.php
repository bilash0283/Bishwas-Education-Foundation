<?php include 'include/header.php'; ?>

<div class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-4xl w-full bg-white rounded-3xl shadow-xl overflow-hidden grid grid-cols-1 md:grid-cols-2 border border-slate-100">
        
        <div class="p-6 md:p-8 bg-slate-50 flex flex-col justify-center items-center border-b md:border-b-0 md:border-r border-slate-100">
            <h3 class="text-lg font-bold text-slate-800 mb-2 text-center">
                Bangla QR দিয়ে পেমেন্ট করুন
            </h3>
            <p class="text-xs text-slate-500 mb-4 text-center">
                যেকোনো ব্যাংক অ্যাপ বা MFS (bKash, Nagad, Rocket) অ্যাপ দিয়ে স্ক্যান করুন
            </p>
            <div class="bg-white p-3 rounded-2xl shadow-sm border border-slate-200 w-full max-w-[280px]">
                <img src="public/assets/bangla_qr.JPG" alt="Rupali Bank QR Code" class="w-full h-auto rounded-lg object-contain">
            </div>
        </div>

        <div class="p-6 md:p-8 flex flex-col justify-center">
            <h2 class="text-xl md:text-2xl font-bold text-emerald-800 mb-6">
                আপনার অনুদান জমা দিন
            </h2>

            <form action="#" method="POST" enctype="multipart/form-data" class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">
                        টাকার পরিমাণ (BDT) <span class="text-red-500">*</span>
                    </label>
                    <input type="number" name="amount" placeholder="অনুদানের পরিমাণ লিখুন" required min="1"
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:bg-white transition-all placeholder:text-slate-400">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">
                        ট্রানজেকশন আইডি (TrxID) <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="transaction_id" placeholder="যেমন: 9J7A6K8L9M" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:bg-white transition-all placeholder:text-slate-400">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">
                        পেমেন্ট স্লিপ / স্ক্রিনশট আপলোড করুন <span class="text-red-500">*</span>
                    </label>
                    <input type="file" name="payment_slip" accept="image/*,.pdf" required
                        class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-slate-200 rounded-lg bg-slate-50 cursor-pointer">
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-semibold rounded-lg shadow-md transition-all duration-200">
                        <span>সাবমিট করুন</span>
                        <i class="fa-solid fa-paper-plane text-sm"></i>
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<?php include 'include/footer.php'; ?>