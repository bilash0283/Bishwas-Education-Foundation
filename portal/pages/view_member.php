<?php
$connection = isset($db) ? $db : (isset($conn) ? $conn : null);
if ($connection) {
    mysqli_set_charset($connection, "utf8mb4");
}

$upload_dir = 'public/uploads/members/';
$back_page  = 'index.php?page=volunteers';

if (!function_exists('h')) {
    function h($s) { return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8'); }
}

/* ---------------- ইউজারের তথ্য ---------------- */
$id = (int) ($_GET['id'] ?? 0);
$st = mysqli_prepare($connection, "SELECT * FROM users WHERE id = ? LIMIT 1");
mysqli_stmt_bind_param($st, 'i', $id);
mysqli_stmt_execute($st);
$u = mysqli_fetch_assoc(mysqli_stmt_get_result($st));
mysqli_stmt_close($st);

$photo = ($u && !empty($u['photo']) && is_file($upload_dir . basename($u['photo'])))
    ? $upload_dir . basename($u['photo']) : '';

/* ---------------- Donation History ----------------
   ID অথবা ইমেইল অথবা ফোনের শেষ ১০ সংখ্যা, যেকোনো একটা মিললেই আসবে */
$donations     = [];
$total_paid    = 0;
$total_pending = 0;
$paid_count    = 0;   // <-- নতুন: paid donation এর সংখ্যা

if ($u) {
    $email  = trim((string) $u['email']);
    $digits = preg_replace('/\D/', '', (string) $u['mobile_no']);
    $last10 = strlen($digits) >= 10 ? substr($digits, -10) : '';

    $sql = "SELECT id, amount, donation_type, fund, payment_method, transaction_id,
                   payment_status, donation_date
            FROM donations
            WHERE donor_id = ?
               OR (? <> '' AND email = ?)
               OR (? <> '' AND RIGHT(phone, 10) = ?)
            ORDER BY donation_date DESC, id DESC";

    $ds = mysqli_prepare($connection, $sql);
    mysqli_stmt_bind_param($ds, 'issss', $id, $email, $email, $last10, $last10);
    mysqli_stmt_execute($ds);
    $dres = mysqli_stmt_get_result($ds);

    while ($r = mysqli_fetch_assoc($dres)) {
        $donations[] = $r;
        $s = strtolower($r['payment_status']);

        if ($s === 'paid') {
            $total_paid += (float) $r['amount'];
            $paid_count++;
        }
        if ($s === 'pending') {
            $total_pending += (float) $r['amount'];
        }
    }
    mysqli_stmt_close($ds);
}

function info_row($label, $value, $icon) {
    $v = trim((string) $value) === ''
        ? '<span class="text-slate-300">—</span>'
        : nl2br(h($value));
    return '<div class="p-4 rounded-xl bg-slate-50 border border-slate-100">
        <p class="text-[10px] font-bold uppercase tracking-wider text-slate-400 flex items-center gap-1.5">
            <i class="fa-solid ' . $icon . ' text-emerald-600"></i> ' . h($label) . '
        </p>
        <p class="text-sm font-semibold text-slate-800 mt-1 break-words">' . $v . '</p>
    </div>';
}

function status_badge_class($status) {
    $map = [
        'paid'     => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'pending'  => 'bg-amber-50 text-amber-700 border-amber-200',
        'failed'   => 'bg-sky-50 text-sky-700 border-sky-200',
        'rejected' => 'bg-red-50 text-red-700 border-red-200',
    ];
    return $map[strtolower($status)] ?? 'bg-slate-100 text-slate-600 border-slate-200';
}

$dob_text = '';
if ($u && !empty($u['dob']) && $u['dob'] !== '0000-00-00') {
    $dob_text = date('d M Y', strtotime($u['dob']));
}
?>

<div class="max-w-4xl mx-auto space-y-6">
    <!-- উপরের বাটন -->
    <div class="flex items-center justify-between print:hidden">
        <button onclick="history.back()" 
            class="px-4 py-2.5 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50 transition-colors flex items-center gap-2">
            <i class="fa-solid fa-arrow-left"></i> Go Back
        </button>
        <?php if ($u) { ?>
            <div class="flex items-center gap-2">
                <button onclick="openModal('memberDonationModal')"
                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-hand-holding-heart"></i>
                    Donation History
                    <span class="px-1.5 py-0.5 rounded bg-white/20 text-[10px]"><?= count($donations) ?></span>
                </button>
                <button onclick="window.print()"
                    class="px-4 py-2 bg-slate-900 hover:bg-slate-800 text-white rounded-xl text-xs font-semibold flex items-center gap-2 shadow-sm">
                    <i class="fa-solid fa-print"></i> Print
                </button>
            </div>
        <?php } ?>
    </div>

    <?php if (!$u) { ?>

        <div class="p-6 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-sm font-semibold">
            <i class="fa-solid fa-circle-exclamation"></i> User পাওয়া যায়নি।
        </div>

    <?php } else { ?>

    <!-- প্রোফাইল হেডার -->
    <div class="relative overflow-hidden rounded-3xl bg-gradient-to-br from-slate-900 via-slate-800 to-emerald-800 text-white shadow-xl [print-color-adjust:exact] [-webkit-print-color-adjust:exact]">
        <div class="absolute -top-16 -right-16 w-56 h-56 rounded-full bg-emerald-400/10"></div>
        <div class="absolute -bottom-20 -left-10 w-48 h-48 rounded-full bg-white/5"></div>

        <div class="relative p-6 sm:p-10 flex flex-col lg:flex-row lg:items-center gap-6">

            <!-- ছবি + তথ্য -->
            <div class="flex flex-col sm:flex-row items-center gap-6 flex-1 min-w-0">
                <?php if ($photo) { ?>
                    <img src="<?= h($photo) ?>" alt="Photo"
                        class="w-28 h-32 rounded-2xl object-cover border-4 border-white/20 shadow-lg shrink-0">
                <?php } else { ?>
                    <div class="w-28 h-32 rounded-2xl bg-white/10 border-4 border-white/20 flex items-center justify-center text-4xl font-bold shrink-0">
                        <?= h(mb_strtoupper(mb_substr($u['member_name'], 0, 1))) ?>
                    </div>
                <?php } ?>

                <div class="text-center sm:text-left space-y-3 min-w-0">
                    <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight break-words"><?= h($u['member_name']) ?></h1>

                    <div class="flex flex-wrap justify-center sm:justify-start gap-2">
                        <span class="px-3 py-1 rounded-full bg-white/15 text-xs font-semibold"><?= h($u['user_type']) ?></span>
                        <span class="px-3 py-1 rounded-full text-xs font-semibold <?= $u['status'] == 'Active' ? 'bg-emerald-400/30 text-emerald-100' : 'bg-amber-400/30 text-amber-100' ?>">
                            <?= h($u['status'] ?: 'Pending') ?>
                        </span>
                        <span class="px-3 py-1 rounded-full bg-white/10 text-xs">ID #<?= (int) $u['id'] ?></span>
                    </div>

                    <div class="flex flex-col sm:flex-row sm:flex-wrap gap-x-5 gap-y-1 text-sm text-slate-200">
                        <span><i class="fa-solid fa-phone mr-1.5 text-emerald-300"></i>+88<?= h($u['mobile_no']) ?></span>
                        <?php if ($u['email'] != '') { ?>
                            <span class="break-all"><i class="fa-solid fa-envelope mr-1.5 text-emerald-300"></i><?= h($u['email']) ?></span>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <!-- Total Paid কার্ড (ডান পাশ) -->
            <button type="button" onclick="openModal('memberDonationModal')"
                class="shrink-0 w-full lg:w-64 text-left rounded-2xl bg-white/10 hover:bg-white/15 border border-white/20 backdrop-blur-sm p-5 transition-colors print:hidden">
                <div class="flex items-center gap-2 text-emerald-300 text-[11px] font-bold uppercase tracking-widest">
                    <i class="fa-solid fa-circle-check"></i> Total Paid
                </div>
                <div class="text-3xl font-extrabold mt-2 break-words">
                    ৳ <?= number_format($total_paid, 2) ?>
                </div>
                <div class="text-[11px] text-slate-300 mt-2">
                    <?= (int) $paid_count ?> paid donation(s)
                </div>
                <div class="text-[10px] text-emerald-300 mt-3 flex items-center gap-1.5">
                    <i class="fa-solid fa-clock-rotate-left"></i> View history
                </div>
            </button>
        </div>
    </div>

    <!-- ব্যক্তিগত তথ্য -->
    <div class="bg-white rounded-3xl shadow-sm border border-slate-200/80 p-6 sm:p-8 space-y-6">

        <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
            <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                <i class="fa-solid fa-user"></i>
            </span>
            <h2 class="text-base font-bold text-slate-900">Personal Details / ব্যক্তিগত তথ্য</h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php
            echo info_row('Full Name', $u['member_name'], 'fa-user');
            echo info_row("Mother's Name", $u['mother_name'], 'fa-person-dress');
            echo info_row('Father / Husband', $u['father_husband_name'], 'fa-person');
            echo info_row('Date of Birth', $dob_text, 'fa-cake-candles');
            echo info_row('Gender', $u['gender'], 'fa-venus-mars');
            echo info_row($u['id_type'] ?: 'ID Type', $u['id_number'], 'fa-id-card');
            echo info_row('Qualification', $u['qualification'], 'fa-graduation-cap');
            echo info_row('Category', $u['user_type'], 'fa-tag');
            ?>
        </div>

        <div class="flex items-center gap-3 border-b border-slate-100 pb-3 pt-2">
            <span class="w-8 h-8 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center text-sm">
                <i class="fa-solid fa-location-dot"></i>
            </span>
            <h2 class="text-base font-bold text-slate-900">Contact & Address / যোগাযোগ ও ঠিকানা</h2>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <?php
            echo info_row('Mobile', '+88' . $u['mobile_no'], 'fa-phone');
            echo info_row('Email', $u['email'], 'fa-envelope');
            echo info_row('Present Address', $u['present_address'], 'fa-location-dot');
            echo info_row('Permanent Address', $u['permanent_address'], 'fa-house');
            ?>
        </div>

        <?php echo info_row('Other Information', $u['other_info'], 'fa-circle-info'); ?>

        <?php if (!empty($u['created_at'])) { ?>
            <p class="text-[11px] text-slate-400">
                Registered: <?= h(date('d M Y, h:i A', strtotime($u['created_at']))) ?>
            </p>
        <?php } ?>
    </div>
    <?php } ?>
</div>

<?php if ($u) { ?>
<!-- ===================== DONATION HISTORY MODAL ===================== -->
<div id="memberDonationModal"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center p-4 z-50 print:hidden">
    <div class="bg-white w-full max-w-3xl rounded-2xl shadow-2xl overflow-hidden border border-slate-100 max-h-[92vh] overflow-y-auto">

        <div class="p-5 bg-slate-900 text-white flex justify-between items-start gap-3 sticky top-0 z-10">
            <div class="min-w-0">
                <h3 class="font-bold text-sm flex items-center gap-2">
                    <i class="fa-solid fa-hand-holding-heart text-emerald-400"></i>
                    <span>Donation History</span>
                </h3>
                <p class="text-xs text-slate-300 mt-1 break-words">
                    <?= h($u['member_name']) ?> • ID #<?= (int) $u['id'] ?> • +88<?= h($u['mobile_no']) ?>
                </p>
            </div>
            <button onclick="closeModal('memberDonationModal')" class="text-slate-400 hover:text-white shrink-0">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <div class="p-5 space-y-4">

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4">
                    <p class="text-[10px] font-bold uppercase text-emerald-700">Total Paid</p>
                    <p class="text-lg font-bold text-emerald-800">৳ <?= number_format($total_paid, 2) ?></p>
                </div>
                <div class="rounded-xl bg-amber-50 border border-amber-200 p-4">
                    <p class="text-[10px] font-bold uppercase text-amber-700">Pending</p>
                    <p class="text-lg font-bold text-amber-800">৳ <?= number_format($total_pending, 2) ?></p>
                </div>
                <div class="rounded-xl bg-sky-50 border border-sky-200 p-4">
                    <p class="text-[10px] font-bold uppercase text-sky-700">Total Donations</p>
                    <p class="text-lg font-bold text-sky-800"><?= count($donations) ?></p>
                </div>
            </div>

            <div class="overflow-x-auto border border-slate-200 rounded-xl">
                <table class="w-full text-left text-xs text-slate-600">
                    <thead class="bg-slate-50 text-slate-700 uppercase font-bold border-b border-slate-200">
                        <tr>
                            <th class="p-3">#</th>
                            <th class="p-3">Date</th>
                            <th class="p-3">Amount</th>
                            <th class="p-3">Payment Method</th>
                            <th class="p-3">Transaction ID</th>
                            <th class="p-3">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <?php if (count($donations) === 0) { ?>
                            <tr><td colspan="6" class="p-8 text-center text-slate-400">
                                <i class="fa-regular fa-folder-open text-3xl mb-2 block"></i>
                                এই ইউজারের কোনো donation পাওয়া যায়নি।
                            </td></tr>
                        <?php } ?>

                        <?php foreach ($donations as $i => $d) { ?>
                            <tr class="hover:bg-slate-50/80">
                                <td class="p-3 text-slate-400"><?= $i + 1 ?></td>
                                <td class="p-3 whitespace-nowrap"><?= h(date('d M Y', strtotime($d['donation_date']))) ?></td>
                                <td class="p-3 font-bold text-slate-800 whitespace-nowrap">৳ <?= number_format((float) $d['amount'], 2) ?></td>
                                <td class="p-3"><?= h($d['payment_method']) ?></td>
                                <td class="p-3 break-all"><?= h($d['transaction_id'] ?: '—') ?></td>
                                <td class="p-3">
                                    <span class="inline-block px-2.5 py-1 rounded-md border font-bold text-[10px] <?= status_badge_class($d['payment_status']) ?>">
                                        <?= h(ucfirst($d['payment_status'])) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>

                    <?php if (count($donations) > 0) { ?>
                        <tfoot class="bg-emerald-50 border-t-2 border-emerald-200">
                            <tr>
                                <td colspan="2" class="p-3 text-right font-bold uppercase text-emerald-800">Total Paid</td>
                                <td colspan="4" class="p-3 font-extrabold text-base text-emerald-800">৳ <?= number_format($total_paid, 2) ?></td>
                            </tr>
                        </tfoot>
                    <?php } ?>
                </table>
            </div>

            <div class="flex justify-end">
                <button type="button" onclick="closeModal('memberDonationModal')"
                    class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
    function openModal(id) {
        const m = document.getElementById(id);
        if (m) { m.classList.remove('hidden'); m.classList.add('flex'); }
    }
    function closeModal(id) {
        const m = document.getElementById(id);
        if (m) { m.classList.remove('flex'); m.classList.add('hidden'); }
    }
</script>
<?php } ?>