<?php include 'include/header.php'; ?>

    <!-- Hero Section -->
    <?php 
        $query = "SELECT * FROM hero_settings WHERE id = 1 LIMIT 1";
        $result = mysqli_query($db, $query);

        $hero_row = mysqli_fetch_assoc($result) ?? [];

        $badge_text         = $hero_row['badge_text'] ?? '';
        $heading_title      = $hero_row['heading_title'] ?? '';
        $heading_highlight  = $hero_row['heading_highlight'] ?? '';
        $description        = $hero_row['description'] ?? '';
        $cta_primary_text   = $hero_row['cta_primary_text'] ?? '';
        $cta_secondary_text = $hero_row['cta_secondary_text'] ?? '';
        $stat_1_number      = $hero_row['stat_1_number'] ?? '0';
        $stat_1_label       = $hero_row['stat_1_label'] ?? '';
        $stat_2_number      = $hero_row['stat_2_number'] ?? '0';
        $stat_2_label       = $hero_row['stat_2_label'] ?? '';
        $stat_3_number      = $hero_row['stat_3_number'] ?? '0';
        $stat_3_label       = $hero_row['stat_3_label'] ?? '';
        $stat_4_number      = $hero_row['stat_4_number'] ?? '0';
        $stat_4_label       = $hero_row['stat_4_label'] ?? '';
    ?>
    <section class="relative bg-gradient-to-r lg:px-10 px-4 from-blue-900 to-teal-950 text-white py-20 lg:py-32 overflow-hidden">
        <div class="absolute inset-0 opacity-20 bg-[url('https://images.unsplash.com/photo-1488521787991-ed7bbaae773c?auto=format&fit=crop&q=80&w=1200')] bg-cover bg-center">
        </div>
        <div class="container mx-auto px-4 relative z-10 grid md:grid-cols-2 gap-12 items-center lg:mt-[-60px]">
            <div class="space-y-6">
                <span class="bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 px-4 py-1.5 rounded-full text-sm font-semibold tracking-wider block w-fit">
                    <?= htmlspecialchars($badge_text) ?>
                </span>
                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold leading-tight">
                    <?= htmlspecialchars($heading_title) ?> <br><span class="text-emerald-400"><?= htmlspecialchars($heading_highlight) ?></span> 
                </h1>
                <p class="text-gray-300 text-lg leading-relaxed">
                    <?= htmlspecialchars($description) ?>
                </p>
                <div class="flex flex-wrap gap-4 pt-2">
                    <a href="#projects" class="bg-emerald-500 hover:bg-emerald-600 text-white font-bold px-8 py-3.5 rounded-xl shadow-lg transition duration-300 transform hover:-translate-y-0.5">
                        <?= htmlspecialchars($cta_primary_text) ?> <i class="fa-solid fa-arrow-right ml-2"></i>
                    </a>
                    <a href="#about" class="bg-transparent border-2 border-white/40 hover:border-white hover:bg-white/10 text-white font-bold px-8 py-3.5 rounded-xl transition duration-300">
                        <?= htmlspecialchars($cta_secondary_text) ?>
                    </a>
                </div>
            </div>
            <div class="bg-white/10 backdrop-blur-md border border-white/10 p-8 rounded-2xl grid grid-cols-2 gap-6 text-center shadow-2xl">
                <div class="p-4 bg-white/5 rounded-xl">
                    <h3 class="text-3xl font-bold text-emerald-400">
                        <span class="counter" data-target="<?= htmlspecialchars($stat_1_number) ?>">০</span>+
                    </h3>
                    <p class="text-sm text-gray-300 mt-1"><?= htmlspecialchars($stat_1_label) ?></p>
                </div>

                <div class="p-4 bg-white/5 rounded-xl">
                    <h3 class="text-3xl font-bold text-emerald-400">
                        <span class="counter" data-target="<?= htmlspecialchars($stat_2_number) ?>">০</span>+
                    </h3>
                    <p class="text-sm text-gray-300 mt-1"><?= htmlspecialchars($stat_2_label) ?></p>
                </div>

                <div class="p-4 bg-white/5 rounded-xl">
                    <h3 class="text-3xl font-bold text-emerald-400">
                        <span class="counter" data-target="<?= htmlspecialchars($stat_3_number) ?>" data-suffix="%">০</span>
                    </h3>
                    <p class="text-sm text-gray-300 mt-1"><?= htmlspecialchars($stat_3_label) ?></p>
                </div>

                <div class="p-4 bg-white/5 rounded-xl">
                    <h3 class="text-3xl font-bold text-emerald-400">
                        <span class="counter" data-target="<?= htmlspecialchars($stat_4_number) ?>">০</span>+
                    </h3>
                    <p class="text-sm text-gray-300 mt-1"><?= htmlspecialchars($stat_4_label) ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- চলমান কার্যক্রমসমূহ সেকশন (Horizontal Auto-Scroll Slider) -->
    <section id="ongoing-activities" class="py-20 lg:px-10 px-4 bg-white overflow-hidden">
        <div class="container mx-auto px-4 relative">

            <!-- সেকশন হেডার -->
            <div class="text-center max-w-2xl mx-auto mb-16 space-y-3">
                <h2 class="text-2xl md:text-4xl font-bold text-gray-900">চলমান কিছু কার্যক্রমসমূহ</h2>
                <div class="h-1 w-20 bg-emerald-600 mx-auto rounded"></div>
                <p class="text-gray-600">আপনার যাকাত, সদকাহ কিংবা সাধারণ অনুদান পৌঁছে যাবে সঠিক মানুষের হাতে। নিচের
                    যেকোনো ফান্ডে সরাসরি অংশ নিন।</p>
            </div>

            <!-- স্লাইডার কন্ট্রোল বাটন (Left & Right Arrows) -->
            <button id="slide-prev"
                class="absolute left-2 md:left-6 top-[55%] -translate-y-1/2 w-12 h-12 rounded-full bg-white shadow-md border border-gray-100 flex items-center justify-center text-emerald-600 hover:bg-emerald-50 transition z-10 focus:outline-none">
                <i class="fa-solid fa-chevron-left text-lg"></i>
            </button>
            <button id="slide-next"
                class="absolute right-2 md:right-6 top-[55%] -translate-y-1/2 w-12 h-12 rounded-full bg-white shadow-md border border-gray-100 flex items-center justify-center text-emerald-600 hover:bg-emerald-50 transition z-10 focus:outline-none">
                <i class="fa-solid fa-chevron-right text-lg"></i>
            </button>

            <!-- স্ক্রোলযোগ্য কন্টেইনার -->
            <div id="slider-container"
                class="flex overflow-x-auto gap-6 scroll-smooth snap-x snap-mandatory pb-6 px-4 no-scrollbar"
                style="scrollbar-width: none; -ms-overflow-style: none;">

                <?php
                    $query = "SELECT * FROM activities WHERE status = 'active' ORDER BY id DESC";
                    $result = mysqli_query($db, $query);

                    if ($result && mysqli_num_rows($result) > 0) {
                        while ($row = mysqli_fetch_assoc($result)) {
                            
                            // ২. প্রতিটি কলামকে ভেরিয়েবলে সাজানো (XSS Protection সহ)
                            $id          = (int)$row['id'];
                            $title       = htmlspecialchars($row['title'] ?? '', ENT_QUOTES, 'UTF-8');
                            $badge_text  = htmlspecialchars($row['badge_text'] ?? '', ENT_QUOTES, 'UTF-8');
                            $status      = htmlspecialchars($row['status'] ?? 'active', ENT_QUOTES, 'UTF-8');
                            $description = htmlspecialchars($row['description'] ?? '', ENT_QUOTES, 'UTF-8');
                            $created_at  = htmlspecialchars($row['created_at'] ?? '', ENT_QUOTES, 'UTF-8'); // যদি টেবিলে থাকে
                            
                            // ৩. ইমেজের জন্য ডিফল্ট হ্যান্ডলিং
                            $raw_image   = $row['image'] ?? '';
                            $image       = (!empty($raw_image) && file_exists('admin/'.$raw_image)) 
                                            ? htmlspecialchars('admin/'.$raw_image, ENT_QUOTES, 'UTF-8') 
                                            : 'admin/public/project_img/default.jpg'; // ডিফল্ট ছবি

                            // ৪. স্ট্যাটাস অনুযায়ী ব্যাজের রঙ/স্টাইল ভেরিয়েবল (ঐচ্ছিক UI সাজানোর জন্য)
                            $status_badge_class = ($status === 'active') 
                                                ? 'bg-emerald-100 text-emerald-700' 
                                                : 'bg-rose-100 text-rose-700';
                    

                ?>
                <!-- কার্ড ১ -->
                <div
                    class="min-w-[290px] mouse sm:min-w-[350px] md:min-w-[380px] max-w-[380px] bg-white rounded-3xl border border-gray-100 shadow-sm overflow-hidden snap-start flex flex-col justify-between">
                    <div>
                        <div class="relative aspect-[4/3] overflow-hidden">
                            <img src="<?= $image ?>"
                                alt="<?= $badge_text ?? '' ?>" class="w-full h-full object-cover">
                        </div>
                        <div class="p-6 space-y-3">
                            <div class="flex items-center space-x-1 space-x-reverse text-amber-600 text-sm font-medium">
                                <i class="fa-solid fa-rocket text-xs mr-1"></i>
                                <span><?= $badge_text ?? '' ?></span>
                            </div>
                            <h3 class="text-2xl font-bold text-slate-800"><?= htmlspecialchars($row['title'] ?? '', ENT_QUOTES, 'UTF-8') ?></h3>
                            <p class="text-gray-500 text-base leading-relaxed">
                                <?= htmlspecialchars(mb_strimwidth($description ?? '', 0, 100, '...'), ENT_QUOTES, 'UTF-8') ?>
                            </p>
                        </div>
                    </div>
                </div>
                <?php  
                        } // while loop end
                    } else {
                        echo '<p class="text-gray-500 text-center w-full">কোনো কার্যক্রম পাওয়া যায়নি।</p>';
                    }
                ?>
            </div>
        </div>
    </section>

    <!-- Running Fund -->
    <section id="projects" class="py-20 lg:px-10 px-4 container mx-auto">
        <div class="text-center max-w-2xl mx-auto mb-16 space-y-3">
            <h2 class="text-2xl md:text-4xl font-bold text-gray-900">অনুদানের প্রধান খাতসমূহ</h2>
            <div class="h-1 w-20 bg-emerald-600 mx-auto rounded"></div>
            <p class="text-gray-600">আপনার যাকাত, সদকাহ কিংবা সাধারণ অনুদান পৌঁছে যাবে সঠিক মানুষের হাতে। নিচের যেকোনো
                ফান্ডে সরাসরি অংশ নিন।</p>
        </div>

        <div class="grid sm:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- কার্ড ১ -->
            <div
                class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-xl transition duration-300 flex flex-col justify-between overflow-hidden group">
                <div class="p-6 space-y-4">
                    <div
                        class="w-12 h-12 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center text-xl font-bold shadow-sm">
                        <i class="fa-solid fa-kit-medical"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 group-hover:text-emerald-600 transition">জরুরি ত্রাণ
                        তহবিল</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">বন্যা, ঝড় কিংবা যেকোনো প্রাকৃতিক দুর্যোগে
                        ক্ষতিগ্রস্ত অসহায় মানুষের পাশে তাৎক্ষণিক খাবার ও চিকিৎসা সহায়তা নিয়ে দাঁড়ানোর তহবিল।</p>
                </div>
                <div class="p-6 pt-0">
                    <button
                        class="w-full bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white font-bold py-2.5 rounded-xl transition duration-300 flex items-center justify-center space-x-2">
                        <span>অনুদানে শরীক হোন</span>
                    </button>
                </div>
            </div>

            <!-- কার্ড ২ -->
            <div
                class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-xl transition duration-300 flex flex-col justify-between overflow-hidden group">
                <div class="p-6 space-y-4">
                    <div
                        class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl font-bold shadow-sm">
                        <i class="fa-solid fa-coins"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 group-hover:text-emerald-600 transition">যাকাত তহবিল</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">সম্পূর্ণ শরীয়াহ সম্মত উপায়ে আপনার যাকাত সংগ্রহ
                        করে তা দরিদ্র ও বেকার পরিবারের স্বাবলম্বী করার প্রজেক্টে ব্যয় করা হয়।</p>
                </div>
                <div class="p-6 pt-0">
                    <button
                        class="w-full bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white font-bold py-2.5 rounded-xl transition duration-300 flex items-center justify-center space-x-2">
                        <span>যাকাত দিন</span>
                    </button>
                </div>
            </div>

            <!-- কার্ড ৩ -->
            <div
                class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-xl transition duration-300 flex flex-col justify-between overflow-hidden group">
                <div class="p-6 space-y-4">
                    <div
                        class="w-12 h-12 rounded-xl bg-blue-100 text-blue-600 flex items-center justify-center text-xl font-bold shadow-sm">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 group-hover:text-emerald-600 transition">নিয়মিত অনুদান
                        তহবিল</h3>
                    <p class="text-gray-600 text-sm leading-relaxed">প্রতি মাসে বা সপ্তাহে নির্দিষ্ট অংকের টাকা
                        স্বয়ংক্রিয়ভাবে দেওয়ার সুবিধা, যা ফাউন্ডেশনের স্থায়ী প্রজেক্টগুলোকে সচল রাখে।</p>
                </div>
                <div class="p-6 pt-0">
                    <button
                        class="w-full bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white font-bold py-2.5 rounded-xl transition duration-300 flex items-center justify-center space-x-2">
                        <span>নিয়মিত দাতা হোন</span>
                    </button>
                </div>
            </div>

            <!-- কার্ড ৪ -->
            <div
                class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-xl transition duration-300 flex flex-col justify-between overflow-hidden group">
                <div class="p-6 space-y-4">
                    <div
                        class="w-12 h-12 rounded-xl bg-purple-100 text-purple-600 flex items-center justify-center text-xl font-bold shadow-sm">
                        <i class="fa-solid fa-box-open"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900 group-hover:text-emerald-600 transition">সাধারণ তহবিল
                    </h3>
                    <p class="text-gray-600 text-sm leading-relaxed">ফাউন্ডেশনের প্রশাসনিক খরচ, জনকল্যাণমূলক বহুমুখী
                        প্রজেক্ট পরিচালনা এবং যেকোনো ভালো কাজের তাৎক্ষণিক সহায়তায় এই ফান্ড ব্যবহৃত হয়।</p>
                </div>
                <div class="p-6 pt-0">
                    <button
                        class="w-full bg-emerald-50 text-emerald-700 hover:bg-emerald-600 hover:text-white font-bold py-2.5 rounded-xl transition duration-300 flex items-center justify-center space-x-2">
                        <span>সাধারণ অনুদান</span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <?php
    // ডাটাবেস থেকে সেটিং তুলে আনা
    $about_query = mysqli_query($db, "SELECT * FROM about_vision ORDER BY id ASC LIMIT 1");
    $about_data  = mysqli_fetch_assoc($about_query);

    // ডাটাবেসে তথ্য না থাকলে ডিফল্ট ডাটা ফলব্যাক হিসেবে ব্যবহার হবে
    $top_subtitle = !empty($about_data['top_subtitle']) ? $about_data['top_subtitle'] : 'আমাদের লক্ষ্য ও উদ্দেশ্য';
    $main_title   = !empty($about_data['main_title']) ? $about_data['main_title'] : 'একটি আদর্শ ও আত্মনির্ভরশীল সমাজ বিনির্মাণ';
    $description  = !empty($about_data['description']) ? $about_data['description'] : 'বিশ্বাস এডুকেশন ফাউন্ডেশন (Bishwas Education Foundation.) একটি সম্পূর্ণ অরাজনৈতিক ও জনকল্যাণমূলক সেবা সংস্থা। সমাজের অবহেলিত ও দরিদ্র শ্রেণির মানুষের মৌলিক চাহিদা পূরণ এবং তাদের কারিগরি শিক্ষার মাধ্যমে স্বাবলম্বী করে তোলাই আমাদের মূল ব্রত।';
    $point_1      = !empty($about_data['point_1']) ? $about_data['point_1'] : 'স্বচ্ছ ও জবাবদিহিতামূলক তহবিল বণ্টন ব্যবস্থা।';
    $point_2      = !empty($about_data['point_2']) ? $about_data['point_2'] : 'জ্ঞান, নৈতিকতা ও মানবিক মূল্যবোধের বিকাশ।';
    $point_3      = !empty($about_data['point_3']) ? $about_data['point_3'] : 'দক্ষতা বৃদ্ধি ও বেকারত্ব দূরীকরণে প্রশিক্ষণ ইনস্টিটিউট।';
    $quote_badge  = !empty($about_data['quote_badge']) ? $about_data['quote_badge'] : '"মানব সেবাই ইসলামের মূল শিক্ষা।"';
    $image_src    = !empty($about_data['image']) ? $about_data['image'] : 'public/assets/gallery_img/img-1.jpg';
    ?>

    <!-- About Me Section -->
    <section id="about" class="bg-gray-100 lg:px-10 px-4 py-20">
        <div class="container mx-auto px-4 grid md:grid-cols-2 gap-12 items-center">
            <div class="relative">
                <img src="admin/<?= htmlspecialchars($image_src) ?>" alt="<?= htmlspecialchars($main_title) ?>"
                    class="rounded-2xl shadow-xl w-full object-cover h-[400px]">
                <?php if (!empty($quote_badge)): ?>
                    <div class="absolute -bottom-6 -right-6 bg-emerald-600 text-white p-6 rounded-2xl hidden lg:block shadow-lg max-w-[250px]">
                        <p class="text-xl font-bold">" <?= htmlspecialchars($quote_badge) ?> "</p>
                    </div>
                <?php endif; ?>
            </div>
            <div class="space-y-6">
                <div class="space-y-2">
                    <h3 class="text-emerald-600 font-bold uppercase tracking-wider text-sm">
                        <?= htmlspecialchars($top_subtitle) ?>
                    </h3>
                    <h2 class="text-3xl md:text-4xl font-bold text-gray-900">
                        <?= htmlspecialchars($main_title) ?>
                    </h2>
                </div>
                <p class="text-gray-700 leading-relaxed">
                    <?= nl2br(htmlspecialchars($description)) ?>
                </p>
                <ul class="space-y-3 font-medium text-gray-800">
                    <?php if (!empty($point_1)): ?>
                        <li class="flex items-center space-x-3">
                            <i class="fa-solid fa-circle-check text-emerald-600"></i>
                            <span><?= htmlspecialchars($point_1) ?></span>
                        </li>
                    <?php endif; ?>
                    
                    <?php if (!empty($point_2)): ?>
                        <li class="flex items-center space-x-3">
                            <i class="fa-solid fa-circle-check text-emerald-600"></i>
                            <span><?= htmlspecialchars($point_2) ?></span>
                        </li>
                    <?php endif; ?>
                    
                    <?php if (!empty($point_3)): ?>
                        <li class="flex items-center space-x-3">
                            <i class="fa-solid fa-circle-check text-emerald-600"></i>
                            <span><?= htmlspecialchars($point_3) ?></span>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </section>

    <!-- Volunter Section -->
    <section id="volunteer" class="py-20 lg:px-10 px-4 bg-emerald-600 text-white text-center relative overflow-hidden">
        <div class="container mx-auto px-4 max-w-3xl space-y-6 relative z-10">
            <h2 class="text-3xl md:text-4xl font-bold">আপনিও হতে পারেন আমাদের একজন গর্বিত ভলান্টিয়ার</h2>
            <p class="text-emerald-100 text-lg leading-relaxed">
                আপনার মেধা, সময় ও শ্রম দিয়ে মানবতার সেবায় অবদান রাখুন। দেশব্যাপী আমাদের বিভিন্ন সামাজিক ও ধর্মীয়
                উদ্যোগে স্বেচ্ছাসেবক হিসেবে কাজ করতে আজই নিবন্ধন করুন।
            </p>
            <div class="pt-4">
                <a href="#"
                    class="bg-white text-emerald-700 hover:bg-gray-100 font-bold px-8 py-3.5 rounded-xl shadow-lg transition duration-300 inline-flex items-center space-x-2">
                    <i class="fa-solid fa-user-plus"></i>
                    <span>ভলান্টিয়ার হিসেবে যোগ দিন</span>
                </a>
            </div>
        </div>
    </section>

    <!-- ফটো গ্যালারি সেকশন (Photo Gallery) -->
    <section id="photo-gallery" class="py-20 lg:px-10 px-4 bg-gray-50">
        <div class="container mx-auto px-4">
            <!-- সেকশন হেডার -->
            <div class="text-center max-w-2xl mx-auto mb-16 space-y-3">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900">আমাদের কার্যক্রমের চিত্রশালা</h2>
                <div class="h-1 w-20 bg-emerald-600 mx-auto rounded"></div>
                <p class="text-gray-600">আমাদের বিভিন্ন প্রজেক্ট, ত্রাণ বিতরণ এবং মানবিক উদ্যোগের কিছু বাস্তব চিত্র নিচে তুলে ধরা হলো।</p>
            </div>

            <!-- গ্যালারি গ্রিড -->
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">

                <!-- ছবি ১ -->
                <div class="gallery-item group relative overflow-hidden rounded-2xl bg-white shadow-sm border border-gray-100 aspect-[4/3] cursor-pointer"
                    data-src="public/assets/gallery_img/5.jpg">
                    <img src="public/assets/gallery_img/5.jpg" alt="গ্যালারি ইমেজ" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                </div>

                <!-- ছবি ২ -->
                <div class="gallery-item group relative overflow-hidden rounded-2xl bg-white shadow-sm border border-gray-100 aspect-[4/3] cursor-pointer"
                    data-src="public/assets/gallery_img/6.jpg">
                    <img src="public/assets/gallery_img/6.jpg" alt="গ্যালারি ইমেজ" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                </div>

                <!-- ছবি ৩ -->
                <div class="gallery-item group relative overflow-hidden rounded-2xl bg-white shadow-sm border border-gray-100 aspect-[4/3] cursor-pointer"
                    data-src="public/assets/gallery_img/7.jpg">
                    <img src="public/assets/gallery_img/7.jpg" alt="গ্যালারি ইমেজ" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                </div>

                <!-- ছবি ৪ -->
                <div class="gallery-item group relative overflow-hidden rounded-2xl bg-white shadow-sm border border-gray-100 aspect-[4/3] cursor-pointer"
                    data-src="public/assets/gallery_img/8.jpg">
                    <img src="public/assets/gallery_img/8.jpg" alt="গ্যালারি ইমেজ" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                </div>

                <!-- ছবি ৫ -->
                <div class="gallery-item group relative overflow-hidden rounded-2xl bg-white shadow-sm border border-gray-100 aspect-[4/3] cursor-pointer"
                    data-src="public/assets/gallery_img/16.jpg">
                    <img src="public/assets/gallery_img/16.jpg" alt="গ্যালারি ইমেজ" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                </div>

                <!-- ছবি ৬ -->
                <div class="gallery-item group relative overflow-hidden rounded-2xl bg-white shadow-sm border border-gray-100 aspect-[4/3] cursor-pointer"
                    data-src="public/assets/gallery_img/100.jpeg">
                    <img src="public/assets/gallery_img/100.jpeg" alt="গ্যালারি ইমেজ" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                </div>

                <!-- ছবি ৭ -->
                <div class="gallery-item group relative overflow-hidden rounded-2xl bg-white shadow-sm border border-gray-100 aspect-[4/3] cursor-pointer"
                    data-src="public/assets/gallery_img/18.jpg">
                    <img src="public/assets/gallery_img/18.jpg" alt="গ্যালারি ইমেজ" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                </div>

                <!-- ছবি ৮ -->
                <div class="gallery-item group relative overflow-hidden rounded-2xl bg-white shadow-sm border border-gray-100 aspect-[4/3] cursor-pointer"
                    data-src="public/assets/gallery_img/300.jpeg">
                    <img src="public/assets/gallery_img/300.jpeg" alt="গ্যালারি ইমেজ" class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                </div>

            </div>
        </div>
    </section>

    <!-- ফটো গ্যালারি লাইটবক্স মডাল (Pure Tailwind Lightbox Modal) -->
    <div id="lightbox" class="fixed inset-0 z-50 pointer-events-none opacity-0 flex items-center justify-center bg-black/80 backdrop-blur-md transition-opacity duration-300">
        <!-- ক্লোজ বাটন -->
        <button id="lightbox-close" class="absolute top-6 right-6 text-white hover:text-emerald-400 text-3xl font-bold p-2 transition-colors z-50 focus:outline-none">
            &times;
        </button>

        <!-- ইমেজের নির্দিষ্ট সাইজের কন্টেইনার (Fixed Width & Height Box) -->
        <div id="lightbox-box" class="relative w-[90vw] max-w-3xl aspect-[16/9] transform scale-90 transition-transform duration-300">
            <img id="lightbox-img" class="w-full h-full object-cover rounded-2xl shadow-2xl border border-white/10" src="" alt="Popup Image">
        </div>
    </div>

    <!-- Vlog/Blog Section -->
    <section id="vlogs" class="py-20 lg:px-10 px-4 bg-white">
        <div class="container mx-auto px-4">
            <!-- সেকশন হেডার -->
            <div class="text-center max-w-2xl mx-auto mb-16 space-y-3">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900">আমাদের ব্লগসমূহ ও ডায়েরি</h2>
                <div class="h-1 w-20 bg-emerald-600 mx-auto rounded"></div>
                <p class="text-gray-600">আমাদের মাঠপর্যায়ের কাজের আপডেট, ডকুমেন্টারি এবং সচেতনতামূলক বিভিন্ন ভিডিও ও
                    নিবন্ধগুলো নিচে দেখে নিন।</p>
            </div>

            <!-- ব্লগ গ্রিড লেআউট -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- কার্ড ১ -->
                <div
                    class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col overflow-hidden group transform hover:-translate-y-1">
                    <!-- থাম্বনেইল ইমেজ এবং ভিডিও ব্যাজ -->
                    <div class="relative aspect-[16/9] overflow-hidden bg-gray-100">
                        <img src="public/assets/gallery_img/8.jpg"
                            alt="ত্রাণ বিতরণের ডকুমেন্টারি"
                            class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                    </div>
                    <!-- কন্টেন্ট -->
                    <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                        <div class="space-y-2">
                            <div
                                class="flex items-center space-x-2 space-x-reverse text-xs font-semibold text-gray-400">
                                <span class="bg-emerald-50 text-emerald-700 px-2.5 py-1 rounded-full">ব্লগ</span>
                                <span>•</span>
                                <span>১০ জুলাই, ২০২৬</span>
                            </div>
                            <h3
                                class="text-xl font-bold text-gray-900 line-clamp-2 group-hover:text-emerald-600 transition">
                                শীতের উষ্ণতা পৌঁছে যাক সবার জীবনে
                            </h3>
                            <p class="text-gray-600 text-sm line-clamp-3 leading-relaxed">
                                শীতের তীব্রতায় কষ্ট পাওয়া দুঃস্থ, অসহায় ও সুবিধাবঞ্চিত মানুষের পাশে দাঁড়িয়ে তাদের মাঝে শীতবস্ত্র ও প্রয়োজনীয় সহায়তা পৌঁছে দেওয়ার মাধ্যমে মানবতা, ভালোবাসা ও সহমর্মিতার উষ্ণতা ছড়িয়ে দেওয়াই আমাদের অঙ্গীকার
                            </p>
                        </div>
                        <a href="#"
                            class="inline-flex items-center space-x-2 space-x-reverse text-sm font-bold text-emerald-600 hover:text-emerald-700 transition pt-2">
                            <span>বিস্তারিত পড়ুন</span>
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </a>
                    </div>
                </div>

                <!-- কার্ড ২ -->
                <div
                    class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col overflow-hidden group transform hover:-translate-y-1">
                    <!-- থাম্বনেইল ইমেজ -->
                    <div class="relative aspect-[16/9] overflow-hidden bg-gray-100">
                        <img src="public/assets/gallery_img/3.jpg"
                            alt="এতিম শিশুদের গল্প"
                            class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                    </div>
                    <!-- কন্টেন্ট -->
                    <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                        <div class="space-y-2">
                            <div
                                class="flex items-center space-x-2 space-x-reverse text-xs font-semibold text-gray-400">
                                <span class="bg-blue-50 text-blue-700 px-2.5 py-1 rounded-full">ব্লগ</span>
                                <span>•</span>
                                <span>০৫ জুলাই, ২০২৬</span>
                            </div>
                            <h3
                                class="text-xl font-bold text-gray-900 line-clamp-2 group-hover:text-emerald-600 transition">
                                একটি শিশুর ভবিষ্যৎ গড়ার আনন্দ: এতিমখানা প্রজেক্টের গল্প
                            </h3>
                            <p class="text-gray-600 text-sm line-clamp-3 leading-relaxed">
                                সমাজের অবহেলিত শিশুদের দ্বীনি ও আধুনিক শিক্ষায় শিক্ষিত করে তুলতে আমাদের দীর্ঘমেয়াদী
                                পরিকল্পনার রূপরেখা এবং আপনার দায়িত্ব।
                            </p>
                        </div>
                        <a href="#"
                            class="inline-flex items-center space-x-2 space-x-reverse text-sm font-bold text-emerald-600 hover:text-emerald-700 transition pt-2">
                            <span>বিস্তারিত পড়ুন</span>
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </a>
                    </div>
                </div>

                <!-- কার্ড ৩ -->
                <div
                    class="bg-white rounded-2xl border border-gray-100 shadow-sm hover:shadow-xl transition-all duration-300 flex flex-col overflow-hidden group transform hover:-translate-y-1">
                    <!-- থাম্বনেইল ইমেজ এবং ভিডিও ব্যাজ -->
                    <div class="relative aspect-[16/9] overflow-hidden bg-gray-100">
                        <img src="public/assets/gallery_img/19.jpg"
                            alt="বিশুদ্ধ পানির প্রজেক্ট ডকুমেন্টারি"
                            class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-105">
                    </div>
                    <!-- কন্টেন্ট -->
                    <div class="p-6 flex-1 flex flex-col justify-between space-y-4">
                        <div class="space-y-2">
                            <div
                                class="flex items-center space-x-2 space-x-reverse text-xs font-semibold text-gray-400">
                                <span class="bg-emerald-50 text-emerald-700 px-2.5 py-1 rounded-full">ব্লগ</span>
                                <span>•</span>
                                <span>২২ জুন, ২০২৬</span>
                            </div>
                            <h3
                                class="text-xl font-bold text-gray-900 line-clamp-2 group-hover:text-emerald-600 transition">
                                মানবতার সেবায় খাদ্য ও বিশুদ্ধ পানির সহায়তা
                            </h3>
                            <p class="text-gray-600 text-sm line-clamp-3 leading-relaxed">
                                অসহায়, দুঃস্থ ও অবহেলিত মানুষের মৌলিক চাহিদা পূরণে খাদ্য ও বিশুদ্ধ পানি পৌঁছে দিয়ে তাদের জীবনে স্বস্তি, নিরাপত্তা ও মানবিক সহায়তার বার্তা ছড়িয়ে দেওয়াই আমাদের অঙ্গীকার।
                            </p>
                        </div>
                        <a href="#"
                            class="inline-flex items-center space-x-2 space-x-reverse text-sm font-bold text-emerald-600 hover:text-emerald-700 transition pt-2">
                            <span>বিস্তারিত পড়ুন</span>
                            <i class="fa-solid fa-arrow-right text-xs"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Us -->
    <section id="contact" class="py-20 lg:px-10 px-4 bg-white">
        <div class="container mx-auto px-4">
            <!-- সেকশন হেডার -->
            <div class="text-center max-w-2xl mx-auto mb-16 space-y-3">
                <h2 class="text-2xl md:text-4xl font-bold text-gray-900">আমাদের সাথে যোগাযোগ করুন</h2>
                <div class="h-1 w-20 bg-emerald-600 mx-auto rounded"></div>
                <p class="text-gray-600">আপনার যেকোনো জিজ্ঞাসা, পরামর্শ বা মতামতের জন্য আমাদের মেসেজ পাঠাতে পারেন।
                    আমাদের প্রতিনিধি দ্রুত আপনার সাথে যোগাযোগ করবেন।</p>
            </div>

            <div class="grid lg:grid-cols-3 gap-10 items-start">
                <!-- বাম পাশ: যোগাযোগের তথ্য (২ কলাম জুড়ে বড় স্ক্রিনে দেখাতে পারেন বা ১ কলাম) -->
                <div class="lg:col-span-1 space-y-6">
                    <div
                        class="bg-gray-50 border border-gray-100 p-6 rounded-2xl flex items-start space-x-4 gap-4 space-x-reverse">
                        <div
                            class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>
                        <div class="space-y-1">
                            <h4 class="text-lg font-bold text-gray-900">কার্যালয়ের ঠিকানা</h4>
                            <p class="text-gray-600 text-sm leading-relaxed">১/জি/১০/১, মীরবাগ হাতিরঝিল, নতুন রাস্তা, ৩ নং লেন, ঢাকা-১২১৭, বাংলাদেশ
                            </p>
                        </div>
                    </div>

                    <div
                        class="bg-gray-50 border border-gray-100 p-6 rounded-2xl flex items-start space-x-4 gap-4 space-x-reverse">
                        <div
                            class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                            <i class="fa-solid fa-phone"></i>
                        </div>
                        <div class="space-y-1">
                            <h4 class="text-lg font-bold text-gray-900">সরাসরি ফোন করুন</h4>
                            <p class="text-gray-600 text-sm">+৮৮০ ১৭১৫-৪৮২৩৬৩</p>
                        </div>
                    </div>

                    <div
                        class="bg-gray-50 border border-gray-100 p-6 rounded-2xl flex items-start space-x-4 gap-4 space-x-reverse">
                        <div
                            class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                        <div class="space-y-1">
                            <h4 class="text-lg font-bold text-gray-900">ইমেইল করুন</h4>
                            <p class="text-gray-600 text-sm">info@bishwas.org</p>
                        </div>
                    </div>

                    <!-- গুগল ম্যাপের জন্য ডামি প্লেসহোল্ডার (আপনি চাইলে আসল ম্যাপ ইমবেড করতে পারেন) -->
                    <div
                        class="bg-gray-100 rounded-2xl h-48 overflow-hidden relative shadow-inner border border-gray-200 group">
                        <div
                            class="absolute inset-0 bg-emerald-950/10 z-10 pointer-events-none group-hover:bg-transparent transition duration-300">
                        </div>
                        <iframe class="w-full h-full border-0"
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d241.79815276802313!2d90.4128057552314!3d23.76047066860609!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3755b9e214dcf989%3A0x38ba85b6e6cbed80!2sBag%20Abdul!5e0!3m2!1sen!2sbd!4v1784616353959!5m2!1sen!2sbd" width="600" height="450" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"
                            allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                    </div>
                </div>

                <!-- ডান পাশ: কন্টাক্ট ফর্ম -->
                <div class="lg:col-span-2 bg-gray-50 border border-gray-100 p-8 rounded-3xl shadow-sm">
                    <form action="#" method="POST" class="space-y-6">
                        <div class="grid sm:grid-cols-2 gap-6">
                            <!-- নাম -->
                            <div class="space-y-2">
                                <label for="name" class="text-sm font-semibold text-gray-700">আপনার পুরো নাম</label>
                                <input type="text" id="name" name="name" placeholder="যেমন: বিল্লাল হোসেন" required
                                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition bg-white">
                            </div>
                            <!-- ইমেইল -->
                            <div class="space-y-2">
                                <label for="email" class="text-sm font-semibold text-gray-700">ইমেইল ঠিকানা</label>
                                <input type="email" id="email" name="email" placeholder="example@mail.com" required
                                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition bg-white">
                            </div>
                        </div>

                        <div class="grid sm:grid-cols-2 gap-6">
                            <!-- ফোন নাম্বার -->
                            <div class="space-y-2">
                                <label for="phone" class="text-sm font-semibold text-gray-700">ফোন নাম্বার</label>
                                <input type="tel" id="phone" name="phone" placeholder="০১৬XXXXXXXX" required
                                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition bg-white">
                            </div>
                            <!-- বিষয় -->
                            <div class="space-y-2">
                                <label for="subject" class="text-sm font-semibold text-gray-700">যোগাযোগের বিষয়</label>
                                <select id="subject" name="subject" required
                                    class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition bg-white text-gray-600">
                                    <option value="" disabled selected>একটি বিষয় নির্বাচন করুন</option>
                                    <option value="donation">অনুদান সংক্রান্ত</option>
                                    <option value="zakat">যাকাত ফান্ড</option>
                                    <option value="volunteer">ভলান্টিয়ার হতে চাই</option>
                                    <option value="other">অন্যান্য জিজ্ঞাসা</option>
                                </select>
                            </div>
                        </div>

                        <!-- মেসেজ বক্স -->
                        <div class="space-y-2">
                            <label for="message" class="text-sm font-semibold text-gray-700">আপনার বার্তা লিখুন</label>
                            <textarea id="message" name="message" rows="7" placeholder="এখানে বিস্তারিত লিখুন..."
                                required
                                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:outline-none focus:border-emerald-500 focus:ring-2 focus:ring-emerald-500/20 transition bg-white resize-none"></textarea>
                        </div>

                        <!-- সাবমিট বাটন -->
                        <button type="submit"
                            class="w-full sm:w-auto bg-emerald-600 hover:bg-emerald-700 text-white font-bold px-8 py-3.5 rounded-xl shadow-md hover:shadow-lg transition duration-300 flex items-center justify-center space-x-2 transform hover:-translate-y-0.5">
                            <i class="fa-solid fa-paper-plane"></i>
                            <span>মেসেজ পাঠান</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </section>
        
<?php include 'include/footer.php'; ?>