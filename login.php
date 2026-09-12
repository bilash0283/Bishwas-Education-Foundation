<?php 
ob_start(); 
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ১. আগেই চেক করা হচ্ছে ইউজার অলরেডি লগইন কি না
if (isset($_SESSION['user_id']) && isset($_SESSION['user_login_permission']) && $_SESSION['user_login_permission'] === true) {
    header("Location: portal/index.php");
    exit();
}

include 'include/header.php'; 
$conn = $db;
$error_message = "";
$identity_val = "";
$password_val = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $identity_val = trim($_POST['identity']);
    $password_val = trim($_POST['password']);

    if (!empty($identity_val) && !empty($password_val)) {
        
        $identity = mysqli_real_escape_string($conn, $identity_val);
        $hashed_password = md5($password_val);

        $check_user_query = "SELECT * FROM `users` WHERE (`email` = '$identity' OR `phone` = '$identity') LIMIT 1";
        $user_result = mysqli_query($conn, $check_user_query);

        if (mysqli_num_rows($user_result) === 1) {
            $user = mysqli_fetch_assoc($user_result);

            if ($user['password'] === $hashed_password) {
                
                if (strtolower($user['status']) === 'active') {
                    
                    $_SESSION['user_id']               = $user['id'];
                    $_SESSION['user_name']             = $user['name'];
                    $_SESSION['user_role']             = $user['role'];
                    $_SESSION['user_login_permission'] = true;

                    header("Location: portal/index.php");
                    exit();

                } else {
                    $status_title = ucfirst($user['status']);
                    $error_message = "আপনার অ্যাকাউন্টটি বর্তমানে <strong>{$status_title}</strong> অবস্থায় রয়েছে। অনুগ্রহ করে অ্যাডমিনের সাথে যোগাযোগ করুন।";
                }

            } else {
                $error_message = "পাসওয়ার্ড ভুল হয়েছে!";
            }

        } else {
            $error_message = "ইমেইল বা মোবাইল নম্বরটি পাওয়া যায়নি!";
        }

    } else {
        $error_message = "সকল ফিল্ড সঠিকভাবে পূরণ করুন!";
    }
}

?>

<div class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-4xl w-full bg-white rounded-3xl shadow-xl overflow-hidden grid grid-cols-1 md:grid-cols-2 border border-slate-100">
        
        <!-- Left Side: Login Form -->
        <div class="p-6 md:p-8 flex flex-col justify-center">
            <h2 class="text-xl md:text-2xl font-bold text-emerald-800 mb-6">
                আপনার অ্যাকাউন্টে লগইন করুন।
            </h2>

            <!-- Dynamic Error Notification Alert -->
            <?php if (!empty($error_message)): ?>
                <div class="mb-4 p-3.5 bg-rose-50 border border-rose-200 text-rose-600 rounded-xl text-xs font-semibold flex items-center gap-2 animate-fade-in">
                    <i class="fa-solid fa-circle-exclamation text-base shrink-0"></i>
                    <span><?php echo $error_message; ?></span>
                </div>
            <?php endif; ?>

            <form action="" method="POST" class="space-y-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">
                        ইমেইল / মোবাইল নম্বর <span class="text-red-500">*</span>
                    </label>
                    <input type="text" name="identity" placeholder="মোবাইল নম্বর / ইমেইল লিখুন" value="<?php echo htmlspecialchars($identity_val); ?>" required
                        class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:bg-white transition-all placeholder:text-slate-400">
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">
                        পাসওয়ার্ড <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input type="password" id="passwordInput" name="password" placeholder="পাসওয়ার্ড লিখুন" value="<?php echo htmlspecialchars($password_val); ?>" required
                            class="w-full px-3.5 py-2.5 pr-10 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:bg-white transition-all placeholder:text-slate-400">
                        
                        <button type="button" id="togglePasswordBtn" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-emerald-600 focus:outline-none transition-colors">
                            <i class="fa-solid fa-eye" id="toggleIcon"></i>
                        </button>
                    </div>
                </div>

                <div class="pt-2">
                    <button type="submit" name="login" class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-semibold rounded-lg shadow-md transition-all duration-200 cursor-pointer active:scale-95">
                        <span>লগইন</span>
                        <i class="fa-solid fa-arrow-right text-sm"></i>
                    </button>
                </div>
            </form>
        </div>

        <!-- Right Side: Cover Image -->
        <div class="relative w-full h-full min-h-[200px] md:min-h-full">
            <img src="public/assets/login_img.png" alt="Illustration" class="w-full h-full object-cover block" onerror="this.onerror=null; this.src='https://via.placeholder.com/400x300?text=Banner+Image';">
        </div>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const passwordInput = document.getElementById('passwordInput');
    const toggleBtn = document.getElementById('togglePasswordBtn');
    const toggleIcon = document.getElementById('toggleIcon');

    toggleBtn.addEventListener('click', function () {
        const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordInput.setAttribute('type', type);
        
        if (type === 'text') {
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    });
});
</script>

<?php include 'include/footer.php'; ?>