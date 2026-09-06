<?php
include '../database/db.php';

if (!function_exists('getTableCount')) {
    function getTableCount($db_conn, $table_name) {
        $sql = "SELECT COUNT(*) as total FROM `$table_name`";
        $res = mysqli_query($db_conn, $sql);
        if ($res && $row = mysqli_fetch_assoc($res)) {
            return (int)$row['total'];
        }
        return 0;
    }
}

// ২. সকল টেবিলের ডাইনামিক কাউন্ট
$total_projects   = getTableCount($db, 'activities'); // Projects / Activities
$total_blogs      = getTableCount($db, 'blogs');
$total_donations  = getTableCount($db, 'donation_sectors');
$total_messages   = getTableCount($db, 'contact_messages');
$total_gallery    = getTableCount($db, 'galleries');

// ৪. সাম্প্রতিক যোগাযোগের মেসেজসমূহ (Contact Messages)
$recent_msg_query = "SELECT * FROM contact_messages ORDER BY id DESC LIMIT 4";
$recent_msg_result = mysqli_query($db, $recent_msg_query);
?>

<!-- Chart.js CDN Library Injection -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="p-4 sm:p-6 lg:p-8 space-y-8">

    <!-- Page Header -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Overview Dashboard</h2>
            <p class="text-sm text-slate-500 mt-1">Real-time statistics & dynamic content management for Bishwas Education Foundation</p>
        </div>
        <div class="flex items-center gap-3">
            <a href="?page=projects" class="inline-flex items-center justify-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium px-4 py-2.5 rounded-xl transition-all text-sm">
                <i class="fa-solid fa-layer-group"></i>New Project
            </a>
            <a href="?page=blogs" class="inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-4 py-2.5 rounded-xl shadow-md shadow-emerald-600/20 transition-all text-sm">
                <i class="fa-solid fa-pen-to-square"></i>New Blog
            </a>
        </div>
    </div>

    <!-- Dynamic Analytics Cards Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Card 1: Total Activities/Projects -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Projects</p>
                <h3 class="text-3xl font-extrabold text-slate-800 mt-2"><?php echo sprintf("%02d", $total_projects); ?></h3>
                <span class="text-[11px] text-emerald-600 font-medium inline-flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-arrow-up-right"></i> Active Initiatives
                </span>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-emerald-50 border border-emerald-100 text-emerald-600 flex items-center justify-center text-2xl shadow-inner">
                <i class="fa-solid fa-hand-holding-heart"></i>
            </div>
        </div>

        <!-- Card 2: Published Blogs -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Published Blogs</p>
                <h3 class="text-3xl font-extrabold text-slate-800 mt-2"><?php echo sprintf("%02d", $total_blogs); ?></h3>
                <span class="text-[11px] text-blue-600 font-medium inline-flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-newspaper"></i> Articles & News
                </span>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-blue-50 border border-blue-100 text-blue-600 flex items-center justify-center text-2xl shadow-inner">
                <i class="fa-solid fa-blog"></i>
            </div>
        </div>

        <!-- Card 3: Donation Sectors -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Donation Sectors</p>
                <h3 class="text-3xl font-extrabold text-slate-800 mt-2"><?php echo sprintf("%02d", $total_donations); ?></h3>
                <span class="text-[11px] text-amber-600 font-medium inline-flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-circle-dollar-to-slot"></i> Active Causes
                </span>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-amber-50 border border-amber-100 text-amber-600 flex items-center justify-center text-2xl shadow-inner">
                <i class="fa-solid fa-sack-dollar"></i>
            </div>
        </div>

        <!-- Card 4: Contact Messages -->
        <div class="bg-white p-5 rounded-2xl border border-slate-200/80 shadow-sm hover:shadow-md transition-shadow flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">User Enquiries</p>
                <h3 class="text-3xl font-extrabold text-slate-800 mt-2"><?php echo sprintf("%02d", $total_messages); ?></h3>
                <span class="text-[11px] text-purple-600 font-medium inline-flex items-center gap-1 mt-1">
                    <i class="fa-solid fa-envelope"></i> Total Messages
                </span>
            </div>
            <div class="w-14 h-14 rounded-2xl bg-purple-50 border border-purple-100 text-purple-600 flex items-center justify-center text-2xl shadow-inner">
                <i class="fa-solid fa-comments"></i>
            </div>
        </div>
    </div>

    <!-- Analytics Chart & Dynamic Quick Stats Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Visual Overview Chart -->
        <div class="lg:col-span-2 bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <h3 class="font-bold text-slate-800 text-lg">System Content Analytics</h3>
                    <p class="text-xs text-slate-500">Distribution of content types across database</p>
                </div>
                <span class="text-xs bg-slate-100 text-slate-600 font-semibold px-3 py-1 rounded-full border border-slate-200">
                    Live Database Summary
                </span>
            </div>
            <div class="h-64 relative w-full">
                <canvas id="foundationAnalyticsChart"></canvas>
            </div>
        </div>

        <!-- Media & Secondary Details Panel -->
        <div class="bg-white p-6 rounded-2xl border border-slate-200/80 shadow-sm flex flex-col justify-between">
            <div>
                <h3 class="font-bold text-slate-800 text-lg mb-4">Gallery & Assets</h3>
                <div class="space-y-4">
                    <div class="flex items-center justify-between p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-indigo-100 text-indigo-600 flex items-center justify-center">
                                <i class="fa-solid fa-images"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-700">Gallery Items</p>
                                <p class="text-xs text-slate-400">Total Uploaded Photos</p>
                            </div>
                        </div>
                        <span class="text-base font-bold text-slate-800"><?php echo sprintf("%02d", $total_gallery); ?></span>
                    </div>

                    <div class="flex items-center justify-between p-3.5 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-teal-100 text-teal-600 flex items-center justify-center">
                                <i class="fa-solid fa-bullhorn"></i>
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-slate-700">Activities</p>
                                <p class="text-xs text-slate-400">Social Programs</p>
                            </div>
                        </div>
                        <span class="text-base font-bold text-slate-800"><?php echo sprintf("%02d", $total_projects); ?></span>
                    </div>
                </div>
            </div>

            <div class="mt-6 pt-4 border-t border-slate-100">
                <a href="?page=media" class="w-full inline-flex items-center justify-center gap-2 text-xs font-semibold text-emerald-600 hover:text-emerald-700 hover:bg-emerald-50 py-2.5 rounded-xl transition-colors border border-emerald-200">
                    <i class="fa-solid fa-photo-film"></i> Manage Media Gallery
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Chart Initialization Script -->
<script>
    document.addEventListener("DOMContentLoaded", function () {
        const ctx = document.getElementById('foundationAnalyticsChart').getContext('2d');
        
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Projects', 'Blogs', 'Donations', 'Gallery', 'Messages'],
                datasets: [{
                    label: 'Total Items in System',
                    data: [
                        <?php echo $total_projects; ?>,
                        <?php echo $total_blogs; ?>,
                        <?php echo $total_donations; ?>,
                        <?php echo $total_gallery; ?>,
                        <?php echo $total_messages; ?>
                    ],
                    backgroundColor: [
                        'rgba(5, 150, 105, 0.85)',
                        'rgba(37, 99, 235, 0.85)',
                        'rgba(217, 119, 6, 0.85)',
                        'rgba(79, 70, 229, 0.85)',
                        'rgba(147, 51, 234, 0.85)'
                    ],
                    borderRadius: 8,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(226, 232, 240, 0.6)'
                        },
                        ticks: {
                            precision: 0
                        }
                    },
                    x: {
                        grid: {
                            display: false
                        }
                    }
                }
            }
        });
    });
</script>