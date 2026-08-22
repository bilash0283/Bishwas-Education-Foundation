<?php 
    include_once '../component/session_manage.php';

    if (isset($_SESSION['cms_admin_id']) && $_SESSION['cms_admin_id'] === true) {
        header("Location: dashboard.php");
        exit();
    }

    $error_message = "";

    if(isset($_POST['cms_login'])) {
        $email = trim($_POST['email']);
        $password = $_POST['password'];

        // Validate credentials
        if ($email === 'admin@example.com' && $password === '12345678') {
            $_SESSION['cms_admin_id'] = true; // Set session variable to indicate successful login
            header("Location: dashboard.php?page=dashboard");
            exit();
        } else {
            $error_message = "Invalid email or password.";
        }
    }
?>


<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - Bishwas Education Foundation</title>
    <link rel="icon" href="../public/assets/logo.png" type="image/png">
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-100 min-h-screen flex items-center justify-center p-4">

    <!-- Login Card Container -->
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl overflow-hidden border border-slate-200">
        
        <!-- Header Banner -->
        <div class="bg-emerald-600 p-6 text-center text-white relative">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-white/10 rounded-full mb-3 backdrop-blur-sm overflow-hidden p-2">
                <img src="../public/assets/logo.png" alt="Logo" class="w-full h-full object-cover rounded-full">
            </div>
            <h1 class="text-2xl font-bold tracking-wide">Welcome to Admin Panel</h1>
        </div>

        <!-- Form Section -->
        <form action="#" method="POST" class="p-8 space-y-5">

            <?php if (!empty($error_message)): ?>
                <div class="flex items-center gap-2 bg-red-50 border border-red-300 text-red-700 text-sm rounded-lg px-4 py-3">
                    <i class="fa-solid fa-circle-exclamation"></i>
                    <span><?php echo htmlspecialchars($error_message); ?></span>
                </div>
            <?php endif; ?>
            
            <!-- Email / Username Field -->
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">Email</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <i class="fa-regular fa-envelope"></i>
                    </span>
                    <input type="email" id="email" name="email" required
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-200"
                        placeholder="example@gmail.com">
                </div>
            </div>

            <!-- Password Field -->
            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1">Password</label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <i class="fa-solid fa-lock"></i>
                    </span>
                    <input type="password" id="password" name="password" required
                        class="w-full pl-10 pr-4 py-2.5 bg-slate-50 border border-slate-300 rounded-lg text-slate-800 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all duration-200"
                        placeholder="••••••••">
                </div>
            </div>

            <!-- Remember Me & Forgot Password -->
            <div class="flex items-center justify-between text-sm">
                <a href="#" class="text-emerald-600 hover:text-emerald-700 font-medium transition-colors">Forgot Password ?</a>
            </div>

            <!-- Submit Button -->
            <button type="submit" name="cms_login" 
                class="w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-lg shadow-md hover:shadow-lg focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:ring-offset-2 transition-all duration-200">
                Login
            </button>
        </form>

        <!-- Footer -->
        <div class="bg-slate-50 border-t border-slate-100 py-4 text-center">
            <p class="text-xs text-slate-500">&copy; Bishwas Education Foundation. All rights reserved.</p>
        </div>

    </div>

</body>
</html>
<?php if (function_exists('ob_end_flush') && ob_get_level() > 0) ob_end_flush(); ?>