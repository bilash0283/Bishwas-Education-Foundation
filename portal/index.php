<?php include 'includes/header.php'; ?>

    <!-- MAIN BODY WORKSPACE (INDEPENDENT SCROLL LAYOUT) -->
    <div class="flex-1 flex overflow-hidden relative">
        <?php include 'includes/sidebar.php'; ?>    

        <!-- MAIN CONTENT AREA -->
        <main class="flex-1 overflow-y-auto flex flex-col justify-between bg-slate-50">
            <div class="p-4 sm:p-6 lg:p-8 space-y-6">
                <?php
                // GET মেথড থেকে page প্যারামিটার নেওয়া (ডিফল্ট: dashboard)
                $page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

                // Allowed routes setup
                switch ($page) {
                    case 'dashboard':
                        $filePath = 'pages/dashboard.php';
                        break;
                    case 'beneficiaries':
                        $filePath = 'pages/beneficiaries.php';
                        break;
                    case 'volunteers':
                        $filePath = 'pages/volunteers.php';
                        break;
                    case 'members':
                        $filePath = 'pages/members.php';
                        break;
                    case 'projects':
                        $filePath = 'pages/projects.php';
                        break;
                    case 'reports':
                        $filePath = 'pages/reports.php';
                        break;
                    default:
                        $filePath = 'pages/404.php';
                        break;
                }

                // ফাইলটি ফোল্ডারে থাকলে include করবে, না থাকলে Error দেখাবে
                if (file_exists($filePath)) {
                    include $filePath;
                } else {
                    echo '<div class="p-6 bg-white rounded-2xl shadow-sm border border-slate-200 text-center">
                            <h2 class="text-xl font-bold text-red-800">404 - Page Not Found</h2>
                            <p class="text-xs text-slate-500 mt-1">The page file "<b>' . htmlspecialchars($filePath) . '</b>" does not exist in your project structure.</p>
                          </div>';
                }
                ?>
            </div>

            <!-- FOOTER SECTION -->
            <footer class="bg-white border-t border-slate-200/80 py-4 px-6">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-500">
                    <p>© <?php echo date('Y'); ?> <span class="font-semibold text-slate-700"><a href="https://bishwas.org" target="_blank" class="hover:text-emerald-600 transition-colors"><?php echo isset($site_title) ? $site_title : 'Bishwas'; ?></a></span>. All rights reserved.</p>
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