<?php 
include 'include/header.php'; 

// মেসেজ স্টোর করার জন্য ভেরিয়েবল
$success_message = "";
$error_message = "";

// ফর্ম সাবমিট হলে প্রসেস শুরু
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // ফর্ম ডাটা সংগ্রহ ও ফিল্টার
    $name           = trim($_POST['name']);
    $email          = trim($_POST['email']);
    $phone          = trim($_POST['phone']);
    $payment_method = trim($_POST['payment_method']);
    $amount         = floatval($_POST['amount']);
    $transaction_id = trim($_POST['transaction_id']);
    
    // ডিফল্ট ভ্যালুসমূহ
    $type            = "Public";      // Public / Member / Volunteer
    $donor_id        = NULL;          // লগইন করা ইউজার থাকলে তার ID বসবে
    $donation_type   = "General";
    $fund            = "General Fund";
    $payment_status  = "Pending";
    $donation_status = "Pending";
    
    // ফাইল (স্ক্রিনশট/স্লিপ) আপলোড প্রসেস
    $receipt_filename = "";
    if (isset($_FILES['payment_slip']) && $_FILES['payment_slip']['error'] == 0) {$upload_dir = 'portal/uploads/receipts/';
        
        // ফোল্ডার না থাকলে তৈরি করা
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['payment_slip']['name'], PATHINFO_EXTENSION);
        $receipt_filename = time() . '_' . rand(1000, 9999) . '.' . $file_extension;
        $target_file_path = $upload_dir .$receipt_filename;
        
        move_uploaded_file($_FILES['payment_slip']['tmp_name'],$target_file_path);
    }

    // ডাটাবেজে ইনসার্ট প্রসেস
    try {
        if ($db instanceof PDO) {
            // PDO ডাটাবেজ ব্যবহার করলে
            $sql = "INSERT INTO donations (donor_id, type, name, email, phone, amount, donation_type, fund, payment_method, transaction_id, payment_status, donation_status, receipt) 
                    VALUES (:donor_id, :type, :name, :email, :phone, :amount, :donation_type, :fund, :payment_method, :transaction_id, :payment_status, :donation_status, :receipt)";
            
            $stmt =$db->prepare($sql);$stmt->execute([
                ':donor_id'       => $donor_id,
                ':type'           => $type,
                ':name'           => $name,
                ':email'          => $email,
                ':phone'          => $phone,
                ':amount'         => $amount,
                ':donation_type'  => $donation_type,
                ':fund'           => $fund,
                ':payment_method' => $payment_method,
                ':transaction_id' => $transaction_id,
                ':payment_status'  => $payment_status,
                ':donation_status' => $donation_status,
                ':receipt'        => $receipt_filename
            ]);
        } elseif ($db instanceof mysqli) {
            // MySQLi ডাটাবেজ ব্যবহার করলে
            $sql = "INSERT INTO donations (donor_id, type, name, email, phone, amount, donation_type, fund, payment_method, transaction_id, payment_status, donation_status, receipt) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt =$db->prepare($sql);$stmt->bind_param("issssdsssssss", $donor_id,$type, $name,$email, $phone,$amount, $donation_type,$fund, $payment_method,$transaction_id, $payment_status,$donation_status, $receipt_filename);$stmt->execute();
        }

        // সফলভাবে সেভ হলে মেসেজ সেট
        $success_message = "আপনার পেমেন্ট সফলভাবে জমা হয়েছে। এটি বর্তমানে পেন্ডিং আছে, এডমিন ভেরিফাই করে ফাইনাল এপ্রুভাল দেবেন। ধন্যবাদ!";

    } catch (Exception $e) {
        $error_message = "ডাটা সেভ করতে সমস্যা হয়েছে: " . $e->getMessage();
    }
}
?>

<div class="bg-slate-100 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-4xl w-full bg-white rounded-3xl shadow-xl overflow-hidden grid grid-cols-1 md:grid-cols-2 border border-slate-100">
        
        <!-- বাম পাশের QR অংশ -->
        <div class="p-6 md:p-8 bg-slate-50 flex flex-col justify-center items-center border-b md:border-b-0 md:border-r border-slate-100">
            <h3 class="text-lg font-bold text-slate-800 mb-2 text-center">
                Bangla QR দিয়ে পেমেন্ট করুন
            </h3>
            <p class="text-xs text-slate-500 mb-4 text-center">
                যেকোনো ব্যাংক অ্যাপ বা MFS (bKash, Nagad, Rocket) অ্যাপ দিয়ে স্ক্যান করুন
            </p>
            <div class="bg-white p-3 rounded-2xl shadow-sm border border-slate-200 w-full max-w-[280px]">
                <img src="public/assets/bangla_qr.JPG" alt="Rupali Bank QR Code" class="w-full h-auto rounded-lg object-contain">
            </div>
        </div>

        <!-- ডান পাশের ফর্ম অংশ -->
        <div class="p-6 md:p-8 flex flex-col justify-center">
            <h2 class="text-xl md:text-2xl font-bold text-emerald-800 mb-6">
                আপনার অনুদান জমা দিন
            </h2>

            <!-- সফল মেসেজ (Success Alert Box with Close Button) -->
            <?php if (!empty($success_message)): ?>
                <div id="alert-box" class="mb-5 p-4 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-800 flex items-start justify-between gap-3 shadow-sm">
                    <div class="flex items-start gap-2">
                        <i class="fa-solid fa-circle-check text-emerald-600 mt-1"></i>
                        <p class="text-xs md:text-sm font-medium leading-relaxed"><?php echo $success_message; ?></p>
                    </div>
                    <button onclick="document.getElementById('alert-box').remove()" class="text-slate-400 hover:text-slate-700 text-lg font-bold leading-none focus:outline-none" aria-label="Close">
                        &times;
                    </button>
                </div>
            <?php endif; ?>

            <!-- এরর মেসেজ -->
            <?php if (!empty($error_message)): ?>
                <div id="alert-box-error" class="mb-5 p-4 rounded-xl bg-red-50 border border-red-200 text-red-800 flex items-start justify-between gap-3 shadow-sm">
                    <div class="flex items-start gap-2">
                        <i class="fa-solid fa-circle-exclamation text-red-600 mt-1"></i>
                        <p class="text-xs md:text-sm font-medium leading-relaxed"><?php echo $error_message; ?></p>
                    </div>
                    <button onclick="document.getElementById('alert-box-error').remove()" class="text-slate-400 hover:text-slate-700 text-lg font-bold leading-none focus:outline-none" aria-label="Close">
                        &times;
                    </button>
                </div>
            <?php endif; ?>

            <form action="" method="POST" enctype="multipart/form-data" class="space-y-4">

                <div class="flex flex-col md:flex-row gap-4">
                    <div class="w-full">
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">
                            নাম <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" placeholder="আপনার নাম" required
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:bg-white transition-all placeholder:text-slate-400">
                    </div>

                    <div class="w-full">
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">
                            ইমেইল <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="email" placeholder="আপনার ইমেইল লিখুন" required
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:bg-white transition-all placeholder:text-slate-400">
                    </div>
                </div>

                <div class="flex flex-col md:flex-row gap-4">
                    <div class="w-full">
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">
                            ফোন <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="phone" placeholder="আপনার ফোন নম্বর" required
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:bg-white transition-all placeholder:text-slate-400">
                    </div>

                    <div class="w-full">
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">
                            পেমেন্ট মাধ্যম <span class="text-red-500">*</span>
                        </label>
                        <select name="payment_method" required
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:bg-white transition-all">
                            <option value="">পেমেন্ট মাধ্যম নির্বাচন করুন</option>
                            <option value="bKash">bKash</option>
                            <option value="Nagad">Nagad</option>
                            <option value="Rocket">Rocket</option>
                            <option value="Bank Transfer">Bank Transfer</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex flex-col md:flex-row gap-4">
                    <div class="w-full">
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">
                            টাকার পরিমাণ (BDT) <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="amount" step="any" placeholder="অনুদানের পরিমাণ লিখুন" required min="1"
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:bg-white transition-all placeholder:text-slate-400">
                    </div>

                    <div class="w-full">
                        <label class="block text-sm font-bold text-slate-700 mb-1.5">
                            ট্রানজেকশন আইডি (TrxID) <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="transaction_id" placeholder="যেমন: 9J7A6K8L9M" required
                            class="w-full px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-lg text-sm text-slate-800 focus:outline-none focus:border-emerald-600 focus:bg-white transition-all placeholder:text-slate-400">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1.5">
                        পেমেন্ট স্লিপ / স্ক্রিনশট আপলোড করুন <span class="text-red-500">*</span>
                    </label>
                    <input type="file" name="payment_slip" accept="image/*,.pdf" required
                        class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-emerald-50 file:text-emerald-700 hover:file:bg-emerald-100 border border-slate-200 rounded-lg bg-slate-50 cursor-pointer">
                </div>

                <div class="pt-2">
                    <button type="submit" class="w-full md:w-auto inline-flex items-center justify-center gap-2 px-6 py-2.5 bg-emerald-700 hover:bg-emerald-800 text-white font-semibold rounded-lg shadow-md transition-all duration-200">
                        <span>সাবমিট করুন</span>
                        <i class="fa-solid fa-paper-plane text-sm"></i>
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>

<?php include 'include/footer.php'; ?>