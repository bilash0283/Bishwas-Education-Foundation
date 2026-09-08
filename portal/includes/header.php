<?php 
    include '../database/db.php';
    $sql = "SELECT * FROM branding_settings WHERE id = 1 LIMIT 1";
    $result = mysqli_query($db, $sql);
    $data = ($result && mysqli_num_rows($result) > 0) ? mysqli_fetch_assoc($result) : null;

    $site_title   = $data['site_title'] ?? 'Bishwas Education Foundation';
    $site_logo    = !empty($data['site_logo']) ? $data['site_logo'] : 'public/assets/logo_BG.png';
    $favicon_icon = !empty($data['favicon_icon']) ? $data['favicon_icon'] : 'public/assets/logo.png';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_title; ?></title>
    <link rel="icon" href="../public/assets/<?php echo $favicon_icon; ?>" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Custom Scrollbar Styling for Sidebar and Main Workspace */
        ::-webkit-scrollbar {
            width: 5px;
            height: 5px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(241, 245, 249, 0.5);
        }
        ::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 10px;
        }
        ::-webkit-scrollbar-thumb:hover {
            background: #059669;
        }
    </style>
</head>
<body class="bg-slate-50 font-sans text-slate-800 antialiased h-screen overflow-hidden flex flex-col">

    <!-- 1. FIXED TOP HEADER -->
    <header class="h-16 bg-white border-b border-slate-200/80 shadow-xs z-40 shrink-0 flex items-center justify-between px-4 lg:px-6">
        <!-- Left: Mobile Menu & Logo -->
        <div class="flex items-center gap-3">
            <button onclick="toggleMobileSidebar()" class="lg:hidden p-2 text-slate-600 hover:text-emerald-600 hover:bg-slate-100 rounded-lg transition-colors">
                <i class="fa-solid fa-bars text-lg"></i>
            </button>
            <a href="#" class="flex items-center gap-3 group">
                <div class="w-10 h-10 shrink-0 rounded-full overflow-hidden flex items-center justify-center border border-slate-200 shadow-xs group-hover:scale-105 transition-all">
                    <img src="../public/assets/<?php echo $favicon_icon; ?>" alt="Logo" class="w-full h-full object-cover" onerror="this.onerror=null; this.src='https://via.placeholder.com/40?text=BF';">
                </div>
                <div class="hidden sm:block">
                    <h1 class="font-bold text-lg leading-none tracking-tight text-slate-800 group-hover:text-emerald-600 transition-colors">
                        bishwas<span class="text-emerald-600 font-black">.org</span>
                    </h1>
                    <span class="text-[10px] text-slate-500 font-semibold tracking-wider uppercase block mt-0.5">
                        Foundation Portal
                    </span>
                </div>
            </a>
        </div>

        <!-- Middle: Global Search -->
        <div class="hidden md:flex items-center flex-1 max-w-md mx-8">
            <div class="relative w-full">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400">
                    <i class="fa-solid fa-magnifying-glass text-xs"></i>
                </span>
                <input type="text" placeholder="Search records, volunteers, or transactions..." class="w-full pl-9 pr-4 py-2 bg-slate-100/70 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-emerald-500 focus:bg-white transition-all">
            </div>
        </div>

        <!-- Right: Actions & User Dropdown -->
        <div class="flex items-center gap-3">
            <button class="relative p-2 text-slate-500 hover:text-emerald-600 hover:bg-slate-100 rounded-xl transition-colors">
                <i class="fa-regular fa-bell text-lg"></i>
                <span class="absolute top-1.5 right-1.5 w-2 h-2 bg-emerald-500 rounded-full ring-2 ring-white"></span>
            </button>

            <div class="h-6 w-px bg-slate-200 hidden sm:block"></div>

            <div class="flex items-center gap-3 pl-1">
                <div class="w-9 h-9 rounded-full bg-gradient-to-tr from-emerald-600 to-teal-500 text-white font-bold text-sm flex items-center justify-center shadow-md shadow-emerald-600/20">
                    A
                </div>
                <div class="hidden lg:block text-left">
                    <h4 class="text-xs font-bold text-slate-800 leading-tight">Admin User</h4>
                    <span class="text-[10px] text-emerald-600 font-semibold">Super Admin</span>
                </div>
            </div>
        </div>
    </header>