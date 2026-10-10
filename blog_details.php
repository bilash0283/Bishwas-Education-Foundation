<?php
include 'include/header.php';  

if(isset($_GET['id']) && !empty($_GET['id'])){
    $blog_id = $_GET['id'];
} else {
    header("Location: index.php");
    exit();
}

// নির্দিষ্ট ব্লগের ডাটা ফ্যাচ করা
$stmt = mysqli_query($db,"SELECT * FROM blogs WHERE id = $blog_id AND status = 'active'");
$result = mysqli_fetch_assoc($stmt);

// থাম্বনেইল ইমেজ পাথ সেটআপ
$image_src = !empty($result['blog_image']) ? 'admin/' . htmlspecialchars($result['blog_image']) : 'public/assets/gallery_img/8.jpg';


?>

<!-- Blog Details Container -->
<div class="bg-slate-50 min-h-screen py-12 px-4 sm:px-6 lg:px-10">
    <div class="max-w-6xl mx-auto">
        
        <!-- Breadcrumb Navigation -->
        <div class="mb-6 flex items-center gap-2 text-xs font-semibold text-slate-500">
            <a href="index.php" class="hover:text-emerald-700 transition">হোম</a>
            <span>/</span>
            <span class="text-emerald-700"><?= htmlspecialchars($result['category'] ?? 'ব্লগ') ?></span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            
            <!-- Left Side: Blog Content -->
            <article class="lg:col-span-2 bg-white rounded-3xl p-6 md:p-8 shadow-sm border border-slate-100 flex flex-col justify-between">
                <div>
                    <!-- Meta Tag & Date -->
                    <div class="flex items-center gap-3 text-xs font-semibold text-slate-400 mb-4">
                        <span class="bg-emerald-50 text-emerald-700 px-3 py-1 rounded-full border border-emerald-100">
                            <?= htmlspecialchars($result['category'] ?? 'ব্লগ') ?>
                        </span>
                        <span>•</span>
                        <span class="flex items-center gap-1.5">
                            <i class="fa-regular fa-calendar text-emerald-600"></i>
                            <?= htmlspecialchars($result['publish_date']) ?>
                        </span>
                    </div>

                    <!-- Title -->
                    <h1 class="text-2xl md:text-3xl font-bold text-slate-900 leading-snug mb-6">
                        <?= htmlspecialchars($result['blog_title']) ?>
                    </h1>

                    <!-- Image Cover -->
                    <div class="relative aspect-[16/9] overflow-hidden rounded-2xl mb-8 bg-slate-100">
                        <img src="<?= $image_src ?>" 
                             alt="<?= htmlspecialchars($result['blog_title']) ?>" 
                             class="w-full h-full object-cover">
                    </div>

                    <!-- Short Description / Highlight -->
                    <?php
                        $desc = $result['short_description'];

                        // literal "\r\n" text ke asol newline e convert kora
                        $desc = str_replace(['\r\n', '\n', '\r'], "\n", $desc);

                        // ekdom beshi faka line komiye dewa (3+ newline -> 2)
                        $desc = preg_replace("/\n{3,}/", "\n\n", trim($desc));
                        ?>
                        
                        <div class="p-4 bg-emerald-50/60 border-l-4 border-emerald-600 text-slate-700 font-medium text-sm md:text-base rounded-r-xl mb-6 leading-relaxed whitespace-pre-line">
                            <?= htmlspecialchars($desc) ?>
                        </div>

                    <!-- Detailed Content -->
                    <div class="prose max-w-none">
                        <?= htmlspecialchars($result['detailed_content']) ?>
                    </div>
                </div>

                <!-- Footer/Share Options -->
                <div class="pt-8 mt-8 border-t border-slate-100 flex flex-wrap items-center justify-between gap-4">
                    <a href="index.php#vlogs" class="inline-flex items-center gap-2 text-sm font-bold text-slate-600 hover:text-emerald-700 transition">
                        <i class="fa-solid fa-arrow-left text-xs"></i>
                        <span>সব ব্লগে ফিরে যান</span>
                    </a>
                    
                    <div class="flex items-center gap-2">
                        <span class="text-xs font-bold text-slate-400 mr-2">শেয়ার করুন:</span>
                        <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]") ?>" target="_blank" class="w-8 h-8 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center hover:bg-emerald-600 hover:text-white transition">
                            <i class="fa-brands fa-facebook-f text-xs"></i>
                        </a>
                        <a href="https://api.whatsapp.com/send?text=<?= urlencode("http://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]") ?>" target="_blank" class="w-8 h-8 rounded-full bg-slate-100 text-slate-600 flex items-center justify-center hover:bg-emerald-600 hover:text-white transition">
                            <i class="fa-brands fa-whatsapp text-xs"></i>
                        </a>
                    </div>
                </div>
            </article>

            <!-- Right Side: Sidebar -->
            <aside class="space-y-6">
                
                <!-- Recent Blogs Widget -->
                <div class="bg-white p-6 rounded-3xl shadow-sm border border-slate-100">
                    <h3 class="text-lg font-bold text-slate-900 mb-4 pb-2 border-b border-slate-100 flex items-center justify-between">
                        <span>সাম্প্রতিক পোস্টসমূহ</span>
                        <span class="h-1.5 w-6 bg-emerald-600 rounded-full inline-block"></span>
                    </h3>

                    <div class="space-y-4">
                        <?php 
                            //recent blogs fetch
                            $recent_blogs_query = mysqli_query($db, "SELECT * FROM blogs WHERE status = 'active' AND id != $blog_id ORDER BY publish_date DESC LIMIT 5");
                      
                        ?>
                            
                            <?php while($recent = mysqli_fetch_assoc($recent_blogs_query)){ ?>
                            <?php
                                $r_image = !empty($recent['blog_image']) ? 'admin/' . htmlspecialchars($recent['blog_image']) : 'public/assets/gallery_img/8.jpg';
                            ?>
                                <a href="blog_details.php?id=<?= $recent['id'] ?>" class="flex items-center gap-3 group">
                                    <div class="w-20 h-16 rounded-xl overflow-hidden bg-slate-100 flex-shrink-0">
                                        <img src="<?= $r_image ?>" 
                                             alt="<?= htmlspecialchars($recent['blog_title']) ?>" 
                                             class="w-full h-full object-cover group-hover:scale-105 transition duration-300">
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-xs md:text-sm font-bold text-slate-800 group-hover:text-emerald-600 transition line-clamp-2 leading-snug">
                                            <?= htmlspecialchars($recent['blog_title']) ?>
                                        </h4>
                                        <span class="text-[11px] text-slate-400 mt-1 block">
                                            <?= htmlspecialchars($recent['publish_date']) ?>
                                        </span>
                                    </div>
                                </a>
                            <?php }  ?>
                    </div>
                </div>

                <!-- Call To Action Widget -->
                <div class="bg-gradient-to-br from-emerald-800 to-emerald-900 text-white p-6 rounded-3xl shadow-md space-y-3">
                    <h3 class="text-lg font-bold">আমাদের প্রজেক্টে যুক্ত হন</h3>
                    <p class="text-xs text-emerald-100 leading-relaxed">
                        আপনার ক্ষুদ্র সহযোগিতাই সুবিধাবঞ্চিত শিশুদের মুখে হাসি ফোটাতে পারে।
                    </p>
                    <a href="donation.php" class="inline-block w-full text-center bg-white text-emerald-800 font-bold py-2.5 px-4 rounded-xl hover:bg-emerald-50 transition text-sm shadow-sm">
                        সহযোগিতা করুন
                    </a>
                </div>

            </aside>

        </div>
    </div>
</div>

<?php include 'include/footer.php'; ?>