<?php
include '../database/db.php';

// বাংলা ফ্রন্ট সাপোর্টের জন্য
mysqli_set_charset($db, "utf8mb4");

// ==========================================
// ২. ডিফল্ট ব্যাকআপ ডাটা (ফাঁকা রাখলে যা বসবে)
// ==========================================
$default = [
    'badge_text'         => 'মানবসেবায় একটি বিশ্বস্তযোগ্য প্রতিষ্ঠান',
    'heading_title'      => 'জন স্বার্থে,',
    'heading_highlight'  => 'বিশ্বাস ও আস্থার সাথে।',
    'description'        => 'বিশ্বাস এডুকেশন ফাউন্ডেশন একটি অলাভজনক ও সম্পূর্ণ দাতব্য সংস্থা যা মানুষের কল্যাণ, শিক্ষা বিস্তার, ও দুস্থদের কর্মসংস্থান তৈরিতে নিরলসভাবে কাজ করে যাচ্ছে। আপনার একটি ছোট অনুদান বদলে দিতে পারে একটি অসহায় পরিবারের ভাগ্য।',
    'cta_primary_text'   => 'আজই শরীক হোন',
    'cta_secondary_text' => 'আমাদের লক্ষ্য জানুন',
    'stat_1_number'      => '১৭০০+',
    'stat_1_label'       => 'উপকারভোগী মানুষ',
    'stat_2_number'      => '১০+',
    'stat_2_label'       => 'সক্রিয় প্রজেক্ট',
    'stat_3_number'      => '১০০%',
    'stat_3_label'       => 'স্বচ্ছতা ও আমানত',
    'stat_4_number'      => '১০০+',
    'stat_4_label'       => 'নিবন্ধিত ভলান্টিয়ার'
];

$message = "";

// ==========================================
// ৩. সেভ ও রিসেট ফর্ম হ্যান্ডলিং
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // ক) Reset Default বাটন চাপলে
    if (isset($_POST['action_reset'])) {
        $badge_text         = $default['badge_text'];
        $heading_title      = $default['heading_title'];
        $heading_highlight  = $default['heading_highlight'];
        $description        = $default['description'];
        $cta_primary_text   = $default['cta_primary_text'];
        $cta_secondary_text = $default['cta_secondary_text'];
        $stat_1_number      = $default['stat_1_number'];
        $stat_1_label       = $default['stat_1_label'];
        $stat_2_number      = $default['stat_2_number'];
        $stat_2_label       = $default['stat_2_label'];
        $stat_3_number      = $default['stat_3_number'];
        $stat_3_label       = $default['stat_3_label'];
        $stat_4_number      = $default['stat_4_number'];
        $stat_4_label       = $default['stat_4_label'];

        $message = "All information has been reset to default!";
    } 
    // খ) Save Changes বাটন চাপলে
    else if (isset($_POST['action_save'])) {
        $badge_text         = !empty(trim($_POST['badge_text'])) ? trim($_POST['badge_text']) : $default['badge_text'];
        $heading_title      = !empty(trim($_POST['heading_title'])) ? trim($_POST['heading_title']) : $default['heading_title'];
        $heading_highlight  = !empty(trim($_POST['heading_highlight'])) ? trim($_POST['heading_highlight']) : $default['heading_highlight'];
        $description        = !empty(trim($_POST['description'])) ? trim($_POST['description']) : $default['description'];
        $cta_primary_text   = !empty(trim($_POST['cta_primary_text'])) ? trim($_POST['cta_primary_text']) : $default['cta_primary_text'];
        $cta_secondary_text = !empty(trim($_POST['cta_secondary_text'])) ? trim($_POST['cta_secondary_text']) : $default['cta_secondary_text'];
        
        $stat_1_number      = !empty(trim($_POST['stat_1_number'])) ? trim($_POST['stat_1_number']) : $default['stat_1_number'];
        $stat_1_label       = !empty(trim($_POST['stat_1_label'])) ? trim($_POST['stat_1_label']) : $default['stat_1_label'];
        $stat_2_number      = !empty(trim($_POST['stat_2_number'])) ? trim($_POST['stat_2_number']) : $default['stat_2_number'];
        $stat_2_label       = !empty(trim($_POST['stat_2_label'])) ? trim($_POST['stat_2_label']) : $default['stat_2_label'];
        $stat_3_number      = !empty(trim($_POST['stat_3_number'])) ? trim($_POST['stat_3_number']) : $default['stat_3_number'];
        $stat_3_label       = !empty(trim($_POST['stat_3_label'])) ? trim($_POST['stat_3_label']) : $default['stat_3_label'];
        $stat_4_number      = !empty(trim($_POST['stat_4_number'])) ? trim($_POST['stat_4_number']) : $default['stat_4_number'];
        $stat_4_label       = !empty(trim($_POST['stat_4_label'])) ? trim($_POST['stat_4_label']) : $default['stat_4_label'];

        $message = "Information updated successfully!";
    }

    // SQL আপডেট কোয়েরি
    $update_query = "UPDATE hero_settings SET 
        badge_text = '$badge_text',
        heading_title = '$heading_title',
        heading_highlight = '$heading_highlight',
        description = '$description',
        cta_primary_text = '$cta_primary_text',
        cta_secondary_text = '$cta_secondary_text',
        stat_1_number = '$stat_1_number',
        stat_1_label = '$stat_1_label',
        stat_2_number = '$stat_2_number',
        stat_2_label = '$stat_2_label',
        stat_3_number = '$stat_3_number',
        stat_3_label = '$stat_3_label',
        stat_4_number = '$stat_4_number',
        stat_4_label = '$stat_4_label'
        WHERE id = 1";

    mysqli_query($db, $update_query);
}

// ==========================================
// ৪. ডাটাবেজ থেকে বর্তমান ডেটা নিয়ে আসা
// ==========================================
$result = mysqli_query($db, "SELECT * FROM hero_settings WHERE id = 1");
$hero = mysqli_fetch_assoc($result);

if (!$hero) {
    $hero = $default;
}
?>

<!DOCTYPE html>
<html lang="bn">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Hero Section</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- FontAwesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="bg-slate-50 text-slate-800">

<!-- Main Hero Settings Container -->
<div class="p-4 sm:p-6 lg:p-8 max-w-7xl mx-auto">
  
  <!-- Success Notification Message -->
  <?php if (!empty($message)): ?>
    <div class="mb-6 p-4 bg-emerald-100 border border-emerald-300 text-emerald-800 rounded-lg flex items-center justify-between shadow-sm">
      <div class="flex items-center gap-2">
        <i class="fa-solid fa-circle-check text-emerald-600 text-lg"></i>
        <span class="font-medium"><?php echo htmlspecialchars($message); ?></span>
      </div>
      <button onclick="this.parentElement.remove()" class="text-emerald-700 hover:text-emerald-900">&times;</button>
    </div>
  <?php endif; ?>

  <!-- Top Header Section with Action Buttons -->
  <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 border-b border-slate-200 pb-5">
    <div>
      <h2 class="text-2xl font-bold text-slate-800">Manage Hero Section</h2>
      <p class="text-sm text-slate-500">Update main homepage hero banners, texts, CTA buttons, and counter statistics</p>
    </div>
    <div class="flex items-center gap-3">
      <!-- Reset Button -->
      <button type="submit" form="heroForm" name="action_reset" value="1" onclick="return confirm('Are you sure you want to reset all information to default?');" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium px-4 py-2.5 rounded-lg border border-slate-200 transition-all cursor-pointer">
        <i class="fa-solid fa-rotate-left"></i>
        Reset Default
      </button>

      <!-- Save Button -->
      <button type="submit" form="heroForm" name="action_save" value="1" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium px-5 py-2.5 rounded-lg shadow transition-all cursor-pointer">
        <i class="fa-solid fa-floppy-disk"></i>
        Save Changes
      </button>
    </div>
  </div>

  <form id="heroForm" action="" method="POST" class="space-y-6">

    <!-- Card 2: Main Hero Texts -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
        <h3 class="font-bold text-slate-800 flex items-center gap-2">
          <i class="fa-solid fa-heading text-emerald-600"></i>
          2. Hero Content & Headlines
        </h3>
        <span class="text-xs bg-slate-100 text-slate-700 font-semibold px-2.5 py-1 rounded-full">Text Data</span>
      </div>
      <div class="p-5 space-y-5">
        <div>
          <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Top Badge Text</label>
          <input type="text" name="badge_text" value="<?php echo htmlspecialchars($hero['badge_text']); ?>" class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Main Title (White)</label>
            <input type="text" name="heading_title" value="<?php echo htmlspecialchars($hero['heading_title']); ?>" class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
          </div>
          <div>
            <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Title Highlight (Green)</label>
            <input type="text" name="heading_highlight" value="<?php echo htmlspecialchars($hero['heading_highlight']); ?>" class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500">
          </div>
        </div>
        <div>
          <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Description</label>
          <textarea name="description" rows="3" class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3.5 py-2.5 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-emerald-500"><?php echo htmlspecialchars($hero['description']); ?></textarea>
        </div>
      </div>
    </div>

    <!-- Card 3: Action Buttons -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
        <h3 class="font-bold text-slate-800 flex items-center gap-2">
          <i class="fa-solid fa-link text-emerald-600"></i>
          3. Call-To-Action Buttons
        </h3>
        <span class="text-xs bg-slate-100 text-slate-700 font-semibold px-2.5 py-1 rounded-full">Buttons</span>
      </div>
      <div class="p-5 grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Primary Button -->
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-4">
          <span class="text-xs font-bold text-emerald-700 uppercase tracking-wider block">Primary Button (Solid Green)</span>
          <div>
            <label class="block text-xs text-slate-500 mb-1 font-medium">Button Label</label>
            <input type="text" name="cta_primary_text" value="<?php echo htmlspecialchars($hero['cta_primary_text']); ?>" class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
        </div>
        <!-- Secondary Button -->
        <div class="p-4 rounded-xl bg-slate-50 border border-slate-200 space-y-4">
          <span class="text-xs font-bold text-slate-600 uppercase tracking-wider block">Secondary Button (Outlined)</span>
          <div>
            <label class="block text-xs text-slate-500 mb-1 font-medium">Button Label</label>
            <input type="text" name="cta_secondary_text" value="<?php echo htmlspecialchars($hero['cta_secondary_text']); ?>" class="w-full text-sm bg-white border border-slate-200 rounded-lg px-3 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
        </div>
      </div>
    </div>

    <!-- Card 4: Right Side Counter Cards -->
    <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
      <div class="p-5 border-b border-slate-200 bg-slate-50/50 flex items-center justify-between">
        <h3 class="font-bold text-slate-800 flex items-center gap-2">
          <i class="fa-solid fa-chart-simple text-emerald-600"></i>
          4. Dynamic Statistics Cards (Right Side)
        </h3>
        <span class="text-xs bg-amber-100 text-amber-800 font-semibold px-2.5 py-1 rounded-full">4 Items</span>
      </div>
      <div class="p-5 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        
        <!-- Stat 1 -->
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
          <span class="text-xs font-bold text-slate-400 uppercase">Stat Box 1</span>
          <div>
            <label class="block text-xs text-slate-500 mb-1">Number / Value</label>
            <input type="text" name="stat_1_number" value="<?php echo htmlspecialchars($hero['stat_1_number']); ?>" class="w-full text-sm font-bold text-emerald-600 bg-white border border-slate-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-xs text-slate-500 mb-1">Label Text</label>
            <input type="text" name="stat_1_label" value="<?php echo htmlspecialchars($hero['stat_1_label']); ?>" class="w-full text-xs text-slate-700 bg-white border border-slate-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
        </div>

        <!-- Stat 2 -->
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
          <span class="text-xs font-bold text-slate-400 uppercase">Stat Box 2</span>
          <div>
            <label class="block text-xs text-slate-500 mb-1">Number / Value</label>
            <input type="text" name="stat_2_number" value="<?php echo htmlspecialchars($hero['stat_2_number']); ?>" class="w-full text-sm font-bold text-emerald-600 bg-white border border-slate-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-xs text-slate-500 mb-1">Label Text</label>
            <input type="text" name="stat_2_label" value="<?php echo htmlspecialchars($hero['stat_2_label']); ?>" class="w-full text-xs text-slate-700 bg-white border border-slate-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
        </div>

        <!-- Stat 3 -->
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
          <span class="text-xs font-bold text-slate-400 uppercase">Stat Box 3</span>
          <div>
            <label class="block text-xs text-slate-500 mb-1">Number / Value</label>
            <input type="text" name="stat_3_number" value="<?php echo htmlspecialchars($hero['stat_3_number']); ?>" class="w-full text-sm font-bold text-emerald-600 bg-white border border-slate-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-xs text-slate-500 mb-1">Label Text</label>
            <input type="text" name="stat_3_label" value="<?php echo htmlspecialchars($hero['stat_3_label']); ?>" class="w-full text-xs text-slate-700 bg-white border border-slate-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
        </div>

        <!-- Stat 4 -->
        <div class="p-4 bg-slate-50 border border-slate-200 rounded-xl space-y-3">
          <span class="text-xs font-bold text-slate-400 uppercase">Stat Box 4</span>
          <div>
            <label class="block text-xs text-slate-500 mb-1">Number / Value</label>
            <input type="text" name="stat_4_number" value="<?php echo htmlspecialchars($hero['stat_4_number']); ?>" class="w-full text-sm font-bold text-emerald-600 bg-white border border-slate-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-xs text-slate-500 mb-1">Label Text</label>
            <input type="text" name="stat_4_label" value="<?php echo htmlspecialchars($hero['stat_4_label']); ?>" class="w-full text-xs text-slate-700 bg-white border border-slate-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-emerald-500">
          </div>
        </div>

      </div>
    </div>

  </form>
</div>

</body>
</html>