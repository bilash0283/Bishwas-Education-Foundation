<?php

$connection = isset($db) ? $db : (isset($conn) ? $conn : null);
mysqli_set_charset($connection, "utf8mb4");

$upload_dir  = 'public/uploads/members/';
$back_page   = 'index.php?page=volunteers';     // list page (filter session a thakbe)

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$id  = (int)($_GET['id'] ?? 0);
$st  = mysqli_prepare($connection, "SELECT * FROM users WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($st, 'i', $id);
mysqli_stmt_execute($st);
$u = mysqli_fetch_assoc(mysqli_stmt_get_result($st));

$photo = ($u && $u['photo'] != '' && is_file($upload_dir . basename($u['photo']))) ? $upload_dir . $u['photo'] : '';

function row($label, $value, $icon) {
    $v = trim((string)$value) === '' ? '<span class="text-slate-300">—</span>' : nl2br(h($value));
    return '<div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
        <p class="text-[10px] font-bold uppercase text-slate-400 flex items-center gap-1.5"><i class="fa-solid ' . $icon . '"></i> ' . $label . '</p>
        <p class="text-sm font-semibold text-slate-800 mt-1 break-words">' . $v . '</p></div>';
}
?>
<main class="min-h-screen bg-slate-50 py-8 px-4 sm:px-6 lg:px-8">
    <div class="max-w-4xl mx-auto space-y-6">

        <div class="flex items-center justify-between print:hidden">
            <a href="<?php echo h($back_page); ?>" class="text-xs font-semibold text-slate-600 hover:text-emerald-600 flex items-center gap-2">
                <i class="fa-solid fa-arrow-left"></i> Back to list
            </a>
            <?php if ($u) { ?>
            <button onclick="window.print()" class="px-4 py-2 bg-slate-900 text-white rounded-xl text-xs font-semibold flex items-center gap-2">
                <i class="fa-solid fa-print"></i> Print
            </button>
            <?php } ?>
        </div>

        <?php if (!$u) { ?>
            <div class="p-6 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-sm font-semibold">
                <i class="fa-solid fa-circle-exclamation"></i> User পাওয়া যায়নি।
            </div>
        <?php } else { ?>

        <div class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-slate-800 to-emerald-700 text-white shadow-xl p-6 sm:p-10">
            <div class="flex flex-col sm:flex-row items-center gap-6">
                <?php if ($photo) { ?>
                    <img src="<?php echo h($photo); ?>" class="w-28 h-32 rounded-2xl object-cover border-4 border-white/20 shadow-lg" alt="Photo">
                <?php } else { ?>
                    <div class="w-28 h-32 rounded-2xl bg-white/10 border-4 border-white/20 flex items-center justify-center text-4xl font-bold">
                        <?php echo h(mb_strtoupper(mb_substr($u['member_name'], 0, 1))); ?>
                    </div>
                <?php } ?>
                <div class="text-center sm:text-left space-y-2">
                    <h1 class="text-2xl sm:text-3xl font-extrabold"><?php echo h($u['member_name']); ?></h1>
                    <div class="flex flex-wrap justify-center sm:justify-start gap-2">
                        <span class="px-3 py-1 rounded-full bg-white/15 text-xs font-semibold"><?php echo h($u['user_type']); ?></span>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?php echo $u['status'] == 'Active' ? 'bg-emerald-400/30 text-emerald-100' : 'bg-amber-400/30 text-amber-100'; ?>">
                            <?php echo h($u['status'] ?: 'Pending'); ?>
                        </span>
                        <span class="px-3 py-1 rounded-full bg-white/10 text-xs">ID #<?php echo (int)$u['id']; ?></span>
                    </div>
                    <p class="text-sm text-slate-200"><i class="fa-solid fa-phone mr-1.5"></i>+88<?php echo h($u['mobile_no']); ?>
                        <?php if ($u['email'] != '') { ?> &nbsp;•&nbsp; <i class="fa-solid fa-envelope mr-1.5"></i><?php echo h($u['email']); ?><?php } ?></p>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-200/80 p-6 sm:p-8 space-y-6">
            <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Personal Details / ব্যক্তিগত তথ্য</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php
                echo row('Full Name', $u['member_name'], 'fa-user');
                echo row("Mother's Name", $u['mother_name'], 'fa-person-dress');
                echo row("Father / Husband", $u['father_husband_name'], 'fa-person');
                echo row('Date of Birth', $u['dob'] ? date('d M Y', strtotime($u['dob'])) : '', 'fa-cake-candles');
                echo row('Gender', $u['gender'], 'fa-venus-mars');
                echo row($u['id_type'] ?: 'ID Type', $u['id_number'], 'fa-id-card');
                echo row('Qualification', $u['qualification'], 'fa-graduation-cap');
                echo row('Category', $u['user_type'], 'fa-tag');
                ?>
            </div>

            <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3 pt-2">Contact & Address / যোগাযোগ ও ঠিকানা</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <?php
                echo row('Mobile', '+88' . $u['mobile_no'], 'fa-phone');
                echo row('Email', $u['email'], 'fa-envelope');
                echo row('Present Address', $u['present_address'], 'fa-location-dot');
                echo row('Permanent Address', $u['permanent_address'], 'fa-house');
                ?>
            </div>
            <?php echo row('Other Information', $u['other_info'], 'fa-circle-info'); ?>

            <?php if (!empty($u['created_at'])) { ?>
                <p class="text-[11px] text-slate-400">Registered: <?php echo h(date('d M Y, h:i A', strtotime($u['created_at']))); ?></p>
            <?php } ?>
        </div>

        <?php } ?>
    </div>
</main>