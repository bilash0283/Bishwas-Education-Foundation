<?php include 'includes/header.php'; ?>

    <!-- MAIN BODY WORKSPACE (INDEPENDENT SCROLL LAYOUT) -->
    <div class="flex-1 flex overflow-hidden relative">
        <!-- sidebar menu -->
        <?php include 'includes/sidebar.php'; ?>        

        <!-- 3. MAIN CONTENT AREA -->
        <main class="flex-1 overflow-y-auto flex flex-col justify-between bg-slate-50">
            <div class="p-4 sm:p-6 lg:p-8 space-y-6">
                <?php include 'pages/dashboard.php'; ?>
            </div>

            <!-- FOOTER SECTION -->
            <footer class="bg-white border-t border-slate-200/80 py-4 px-6 ">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
                    <p>© <?php echo date('Y'); ?> <span class="font-semibold text-slate-700"><a href="https://bishwas.org" target="_blank" class="hover:text-emerald-600 transition-colors"><?php echo $site_title; ?></a></span>. All rights reserved.</p>
                    <div class="flex items-center gap-4">
                        <a href="#" class="hover:text-emerald-600 transition-colors">Privacy Policy</a>
                        <a href="#" class="hover:text-emerald-600 transition-colors">Terms of Service</a>
                        <a href="#" class="hover:text-emerald-600 transition-colors">Support</a>
                    </div>
                </div>
            </footer>
        </main>
    </div>

<?php include 'includes/footer.php'; ?>