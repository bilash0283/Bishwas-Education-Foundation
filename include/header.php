<!DOCTYPE html>
<html lang="bn" class="scroll-smooth">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bishwas Education Foundation.</title>
    <link rel="icon" href="public/assets/logo.png" type="image/png">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/style.css">
</head>

<body class="bg-gray-50 text-gray-800 scroll-smooth">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50">
        <div class="container mx-auto lg:px-10 px-4 py-4 flex justify-between items-center">
            <!-- Logo -->
            <div class="flex items-center space-x-3 space-x-reverse">
                <a href="index.php" class="text-2xl font-bold text-emerald-600 flex items-center space-x-2">
                    <img src="public/assets/logo_BG.png" alt="Logo" class="h-12">
                </a>
            </div>

            <!-- Desktop Navigation Menu -->
            <nav class="hidden md:flex space-x-8 font-medium text-gray-600">
                <a href="#"
                    class="hover:text-emerald-600 transition duration-300 text-emerald-600 font-semibold">মূলপাতা</a>
                <a href="#about" class="hover:text-emerald-600 transition duration-300">আমাদের সম্পর্কে</a>
                <a href="#ongoing-activities" class="hover:text-emerald-600 transition duration-300">চলমান প্রকল্প</a>
                <a href="#volunteer"
                    class="hover:text-emerald-600 transition duration-300 font-semibold">ভলান্টিয়ার</a>
                <a href="#contact" class="hover:text-emerald-600 transition duration-300">যোগাযোগ</a>
            </nav>

            <!-- Right Side: Donation + Login/Profile + Mobile Menu -->
            <div class="flex items-center space-x-3 sm:space-x-4">
                <a href="#projects"
                    class="bg-emerald-600 hover:bg-emerald-700 text-white px-5 py-2.5 rounded-full font-semibold shadow-md transition duration-300 flex items-center space-x-2">
                    <i class="fa-solid fa-heart"></i>
                    <span class="hidden sm:inline">অনুদান দিন</span>
                </a>

                <!-- Login / Profile Icon -->
                <a href="login.php" id="profile-btn" title="লগইন / প্রোফাইল"
                    class="w-10 h-10 flex items-center justify-center rounded-full bg-emerald-50 text-emerald-600 hover:bg-emerald-100 border border-emerald-200 transition duration-300">
                    <i class="fa-solid fa-user text-lg"></i>
                </a>

                <!-- Mobile Menu Button -->
                <button id="menu-btn"
                    class="text-gray-700 hover:text-emerald-600 focus:outline-none md:hidden text-2xl">
                    <i class="fa-solid fa-bars"></i>
                </button>
            </div>
        </div>
    </header>

    <!-- mobile sidebar collaps -->
    <div id="sidebar-overlay"
        class="fixed inset-0 bg-black/50 z-50 opacity-0 pointer-events-none transition-opacity duration-300 md:hidden">
    </div>
    <div id="mobile-menu"
        class="fixed top-0 left-0 bottom-0 w-72 bg-white z-50 shadow-2xl transform -translate-x-full transition-transform duration-300 ease-in-out md:hidden flex flex-col">
        <!-- Mobile Menu Header -->
        <div class="p-4 border-b flex justify-between items-center">
            <div class="flex items-center space-x-3 space-x-reverse">
                <a href="#" class="text-2xl font-bold text-emerald-600 flex items-center space-x-2">
                    <img src="public/assets/logo_BG.png" alt="Logo" class="h-12">
                </a>
            </div>
            <button id="close-btn" class="text-gray-500 hover:text-red-600 text-2xl focus:outline-none">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <!-- Mobile Menu Links -->
        <nav class="flex flex-col p-6 space-y-4 text-lg font-medium text-gray-700">
            <a href="#"
                class="mobile-link hover:text-emerald-600 transition p-2 rounded-lg hover:bg-emerald-50 text-emerald-600 font-semibold">মূলপাতা</a>
            <a href="#about"
                class="mobile-link hover:text-emerald-600 transition p-2 rounded-lg hover:bg-emerald-50">আমাদের
                সম্পর্কে</a>
            <a href="#ongoing-activities"
                class="mobile-link hover:text-emerald-600 transition p-2 rounded-lg hover:bg-emerald-50">চলমান
                প্রকল্প</a>
            <a href="#volunteer"
                class="mobile-link hover:text-emerald-600 transition p-2 rounded-lg hover:bg-emerald-50">ভলান্টিয়ার</a>
            <a href="#contact"
                class="mobile-link hover:text-emerald-600 transition p-2 rounded-lg hover:bg-emerald-50">যোগাযোগ</a>

            <!-- Login / Profile link in mobile menu -->
            <a href="login.php"
                class="mobile-link hover:text-emerald-600 transition p-2 rounded-lg hover:bg-emerald-50 flex items-center gap-2 border-t pt-4 mt-2">
                <i class="fa-solid fa-user"></i>
                <span>লগইন / প্রোফাইল</span>
            </a>
        </nav>
    </div>





    
    