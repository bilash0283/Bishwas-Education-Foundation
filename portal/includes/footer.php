    <!-- CLIENT INTERACTIVE JAVASCRIPT -->
    <script>
        // SPA Page Switcher Logic
        function switchTab(tabId) {
            // Hide all pages
            // document.querySelectorAll('.page-content').forEach(page => page.classList.add('hidden'));
            
            // Remove active classes from navigation items
            // document.querySelectorAll('.nav-item').forEach(btn => {
            //     btn.classList.remove('bg-emerald-600', 'text-white', 'font-semibold', 'shadow-sm');
            //     btn.classList.add('text-slate-600', 'hover:bg-slate-200/70', 'hover:text-slate-900');
            // });

            // Show current target page
            // document.getElementById(`page-${tabId}`).classList.remove('hidden');

            // // Set current nav tab style
            // const activeNav = document.getElementById(`nav-${tabId}`);
            // activeNav.classList.add('bg-emerald-600', 'text-white', 'font-semibold', 'shadow-sm');
            // activeNav.classList.remove('text-slate-600', 'hover:bg-slate-200/70', 'hover:text-slate-900');

            // Auto-close drawer on mobile
            // if (window.innerWidth < 1024) {
            //     toggleMobileSidebar();
            // }
        }

        // Mobile Responsive Navigation Drawer Toggle
        function toggleMobileSidebar() {
            const sidebar = document.getElementById('sidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            sidebar.classList.toggle('-translate-x-full');
            backdrop.classList.toggle('hidden');
        }

       
    </script>
</body>
</html>