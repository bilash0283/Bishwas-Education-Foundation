<?php
/* ==========================================================
   report.php  (PHP 7.2 – 8.4 compatible)
   Live server fix:
   - session আগে চালু হয়, তারপর user_type চেক
   - mysqli_stmt_get_result() (mysqlnd) না থাকলে fallback আছে
   ========================================================== */
if (session_status() === PHP_SESSION_NONE) { @session_start(); }

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'Admin') {
    header('Location: index.php?page=dashboard');
    exit;
}

if (!function_exists('h')) { function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
function tk($n) { return '৳' . number_format((float)$n, 2); }

$errors = [];

/* ---------- 1) Module setting (page name/edit link ekhane change korben) ---------- */
$MODS = [
    'don' => ['label' => 'Donation',  'table' => 'donations', 'date' => 'donation_date', 'amt' => 'amount',      'st' => 'payment_status',
              'title' => 'name',  'sub' => 'fund',     'search' => ['name', 'email', 'phone', 'transaction_id', 'fund'],
              'statuses' => ['pending', 'paid', 'failed', 'rejected'],       'link' => 'index.php?page=donation&edit=',
              'badge' => 'bg-emerald-50 text-emerald-700', 'icon' => 'fa-hand-holding-heart'],
    'evt' => ['label' => 'Events', 'table' => 'events', 'date' => 'start_date', 'amt' => 'actual_cost', 'st' => 'status',
              'title' => 'title', 'sub' => 'category', 'search' => ['title', 'location', 'organizer', 'category'],
              'statuses' => ['Upcoming', 'Ongoing', 'Completed', 'Cancelled'], 'link' => 'index.php?page=events&edit=',
              'badge' => 'bg-sky-50 text-sky-700', 'icon' => 'fa-calendar-days'],
    'exp' => ['label' => 'Expenses', 'table' => 'expenses', 'date' => 'expense_date', 'amt' => 'amount', 'st' => 'status',
              'title' => 'title', 'sub' => 'category', 'search' => ['voucher_no', 'title', 'paid_to', 'category'],
              'statuses' => ['Pending', 'Complete'],                         'link' => 'index.php?page=expense&edit=',
              'badge' => 'bg-rose-50 text-rose-700', 'icon' => 'fa-receipt'],
];

/* ---------- 2) Filter neya (URL theke) ---------- */
$period = isset($_GET['period']) ? $_GET['period'] : 'all';
if (!in_array($period, ['all', 'daily', 'weekly', 'monthly', 'yearly', 'custom'])) { $period = 'all'; }
$date = (!empty($_GET['date']) && strtotime($_GET['date'])) ? date('Y-m-d', strtotime($_GET['date'])) : date('Y-m-d');
$df   = isset($_GET['df']) ? $_GET['df'] : '';
$dt   = isset($_GET['dt']) ? $_GET['dt'] : '';
$tab  = isset($_GET['tab']) ? $_GET['tab'] : 'all';
if ($tab !== 'all' && !isset($MODS[$tab])) { $tab = 'all'; }
$status = isset($_GET['status']) ? $_GET['status'] : '';
if ($tab === 'all' || !in_array($status, $MODS[$tab]['statuses'])) { $status = ''; }
$q  = trim(isset($_GET['q']) ? $_GET['q'] : '');
$pg = max(1, (int)(isset($_GET['pg']) ? $_GET['pg'] : 1));

// link banano: current filter + kichu change
function rep_url($over = []) {
    global $period, $date, $df, $dt, $tab, $status, $q;
    $p = ['page' => 'report', 'period' => $period, 'date' => $date, 'df' => $df, 'dt' => $dt, 'tab' => $tab, 'status' => $status, 'q' => $q];
    return 'index.php?' . http_build_query(array_merge($p, $over));
}

/* ---------- 3) Tarikh range ber kora (shoptaho = Saturday theke Friday) ---------- */
function rep_range($period, $date, $nav, $df, $dt) {
    $a = strtotime($date);
    switch ($period) {
        case 'daily':
            $a = strtotime("$nav day", $a); $s = $e = $a; $label = date('l, d M Y', $a); break;
        case 'weekly':
            $a = strtotime(($nav * 7) . ' day', $a);
            $s = strtotime('-' . ((date('w', $a) + 1) % 7) . ' day', $a); $e = strtotime('+6 day', $s);
            $label = date('d M', $s) . ' – ' . date('d M Y', $e); break;
        case 'monthly':
            $a = strtotime("$nav month", strtotime(date('Y-m-01', $a)));
            $s = $a; $e = strtotime(date('Y-m-t', $a)); $label = date('F Y', $a); break;
        case 'yearly':
            $y = (int)date('Y', $a) + $nav; $a = $s = strtotime("$y-01-01"); $e = strtotime("$y-12-31"); $label = (string)$y; break;
        case 'all':
            $s = strtotime('2000-01-01'); $e = strtotime('2099-12-31'); $label = 'All Time'; break;
        default:
            $s = strtotime($df) ?: $a; $e = strtotime($dt) ?: $s;
            if ($e < $s) { $x = $s; $s = $e; $e = $x; }
            $label = date('d M Y', $s) . ' – ' . date('d M Y', $e);
    }
    return [date('Y-m-d', $s), date('Y-m-d', $e), $label, date('Y-m-d', $a)];
}
list($from, $to, $label) = rep_range($period, $date, 0, $df, $dt);
$prevR = rep_range($period, $date, -1, $df, $dt);
$nextR = rep_range($period, $date, 1, $df, $dt);
$prevDate = $prevR[3];
$nextDate = $nextR[3];

/* ---------- 4) Database helper ---------- */
function rep_rows($db, $sql, $types = '', $params = []) {
    global $errors;
    $st = mysqli_prepare($db, $sql);
    if (!$st) { $errors[] = mysqli_error($db); return []; }

    if ($types !== '') {
        // reference diye bind (shob PHP version e safe)
        $refs = [];
        foreach ($params as $k => $v) { $refs[$k] = &$params[$k]; }
        array_unshift($refs, $types);
        array_unshift($refs, $st);
        call_user_func_array('mysqli_stmt_bind_param', $refs);
    }
    if (!mysqli_stmt_execute($st)) { $errors[] = mysqli_stmt_error($st); return []; }

    $out = [];
    if (function_exists('mysqli_stmt_get_result')) {
        // mysqlnd ache
        $res = mysqli_stmt_get_result($st);
        while ($res && ($r = mysqli_fetch_assoc($res))) { $out[] = $r; }
    } else {
        // mysqlnd nai: bind_result diye
        mysqli_stmt_store_result($st);
        $meta = mysqli_stmt_result_metadata($st);
        if ($meta) {
            $row = []; $bind = [];
            while ($f = mysqli_fetch_field($meta)) { $row[$f->name] = null; $bind[] = &$row[$f->name]; }
            mysqli_free_result($meta);
            array_unshift($bind, $st);
            call_user_func_array('mysqli_stmt_bind_result', $bind);
            while (mysqli_stmt_fetch($st)) {
                $copy = [];
                foreach ($row as $k => $v) { $copy[$k] = $v; }
                $out[] = $copy;
            }
        }
    }
    mysqli_stmt_close($st);
    return $out;
}

// prothom row (na thakle default)
function rep_first($db, $sql, $types, $params, $default) {
    $rows = rep_rows($db, $sql, $types, $params);
    return $rows ? $rows[0] : $default;
}

/* ---------- 5) Top totals (shudhu tarikh range er upor) ---------- */
$d = rep_first($db, "SELECT COUNT(*) AS c,
        COALESCE(SUM(CASE WHEN payment_status='paid' THEN amount ELSE 0 END),0) AS paid,
        COALESCE(SUM(CASE WHEN payment_status='pending' THEN amount ELSE 0 END),0) AS pend
        FROM donations WHERE donation_date BETWEEN ? AND ?", 'ss', [$from, $to], ['c' => 0, 'paid' => 0, 'pend' => 0]);

$v = rep_first($db, "SELECT COUNT(*) AS c, COALESCE(SUM(CASE WHEN status='Completed' THEN 1 ELSE 0 END),0) AS done_c, COALESCE(SUM(actual_cost),0) AS cost, COALESCE(SUM(budget),0) AS budget
        FROM events WHERE start_date BETWEEN ? AND ? AND status <> 'Cancelled'", 'ss', [$from, $to], ['c' => 0, 'done_c' => 0, 'cost' => 0, 'budget' => 0]);

$x = rep_first($db, "SELECT COUNT(*) AS c,
        COALESCE(SUM(CASE WHEN status='Complete' THEN amount ELSE 0 END),0) AS done,
        COALESCE(SUM(CASE WHEN status='Pending' THEN amount ELSE 0 END),0) AS pend
        FROM expenses WHERE expense_date BETWEEN ? AND ?", 'ss', [$from, $to], ['c' => 0, 'done' => 0, 'pend' => 0]);

$totalDonation = (float)$d['paid'];
$totalEvent    = (float)$v['cost'];
$totalExpense  = (float)$x['done'];
$balance       = $totalDonation - $totalEvent - $totalExpense;
$barMax        = max($totalDonation, $totalEvent, $totalExpense, 1);

/* ---------- 6) Record list (filter shoho) ---------- */
$list = [];
foreach ($MODS as $k => $m) {
    if ($tab !== 'all' && $tab !== $k) { continue; }
    $sql = "SELECT id, {$m['date']} AS dt, {$m['title']} AS title, {$m['sub']} AS sub, {$m['amt']} AS amt, {$m['st']} AS st
            FROM {$m['table']} WHERE {$m['date']} BETWEEN ? AND ?";
    $types = 'ss'; $params = [$from, $to];
    if ($tab === $k && $status !== '') { $sql .= " AND {$m['st']} = ?"; $types .= 's'; $params[] = $status; }
    if ($q !== '') {
        $sql .= ' AND (' . implode(' OR ', array_map(function ($c) { return "$c LIKE ?"; }, $m['search'])) . ')';
        $like = '%' . $q . '%';
        foreach ($m['search'] as $unused) { $types .= 's'; $params[] = $like; }
    }
    foreach (rep_rows($db, $sql, $types, $params) as $r) { $r['ty'] = $k; $list[] = $r; }
}
usort($list, function ($a, $b) {
    $c = strcmp($b['dt'], $a['dt']);
    return $c !== 0 ? $c : ((int)$b['id'] - (int)$a['id']);
});

$total = count($list);
$listSum = $total ? array_sum(array_column($list, 'amt')) : 0;
$perPage = 15;
$pages = max(1, (int)ceil($total / $perPage));
$pg = min($pg, $pages);
$show = array_slice($list, ($pg - 1) * $perPage, $perPage);

$badge = ['paid' => 'bg-emerald-100 text-emerald-700', 'complete' => 'bg-emerald-100 text-emerald-700', 'completed' => 'bg-slate-200 text-slate-700',
          'pending' => 'bg-amber-100 text-amber-700', 'upcoming' => 'bg-sky-100 text-sky-700', 'ongoing' => 'bg-teal-100 text-teal-700',
          'failed' => 'bg-orange-100 text-orange-700', 'rejected' => 'bg-rose-100 text-rose-700', 'cancelled' => 'bg-rose-100 text-rose-700'];
$inp = 'w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-emerald-500';
?>
<style>@media print{.no-print{display:none!important}*{-webkit-print-color-adjust:exact;print-color-adjust:exact}}</style>

<section class=" space-y-5 max-w-7xl mx-auto">

    <!-- Header -->
    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-800">Reports <small class="normal-case font-medium text-slate-400">/ রিপোর্ট</small></h2>
            <p class="text-xs text-slate-500 mt-0.5">Donation, event and finance expense summary in one place. / অনুদান, ইভেন্ট ও আর্থিক খরচের সারসংক্ষেপ</p>
        </div>
        <button type="button" onclick="window.print()" class="no-print px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold">
            <i class="fa-solid fa-print mr-1.5"></i> Print
        </button>
    </div>

    <?php if ($errors) { ?>
    <div class="p-3 bg-red-50 border border-red-200 text-red-700 text-xs rounded-lg">
        <b>Database error:</b><br><?php echo h(implode(' | ', array_unique($errors))); ?>
    </div>
    <?php } ?>

    <!-- Step 1: Somoy bachai -->
    <div class="no-print bg-white rounded-2xl border border-slate-200/80 p-4 space-y-3">
        <div class="flex flex-wrap items-center gap-2">
            <div class="flex flex-wrap gap-1 p-1 bg-slate-100 rounded-xl">
                <?php foreach (['all' => 'All Time', 'daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'yearly' => 'Yearly', 'custom' => 'Custom'] as $k => $l) { ?>
                <a href="<?php echo h(rep_url(['period' => $k, 'pg' => 1])); ?>"
                   class="px-3.5 py-2 rounded-lg text-xs font-bold <?php echo $period === $k ? 'bg-white text-emerald-700 shadow-sm' : 'text-slate-500 hover:text-slate-800'; ?>"><?php echo $l; ?></a>
                <?php } ?>
            </div>
            <?php if (!in_array($period, ['custom', 'all'])) { ?>
            <div class="flex items-center gap-1.5 sm:ml-auto">
                <a href="<?php echo h(rep_url(['date' => $prevDate, 'pg' => 1])); ?>" class="w-9 h-9 flex items-center justify-center rounded-lg border border-slate-200 hover:bg-slate-50"><i class="fa-solid fa-chevron-left"></i></a>
                <span class="min-w-44 text-center text-sm font-bold text-slate-800"><?php echo h($label); ?></span>
                <a href="<?php echo h(rep_url(['date' => $nextDate, 'pg' => 1])); ?>" class="w-9 h-9 flex items-center justify-center rounded-lg border border-slate-200 hover:bg-slate-50"><i class="fa-solid fa-chevron-right"></i></a>
                <a href="<?php echo h(rep_url(['date' => date('Y-m-d'), 'pg' => 1])); ?>" class="px-3 h-9 flex items-center rounded-lg bg-emerald-50 text-emerald-700 text-xs font-bold hover:bg-emerald-100">Today</a>
            </div>
            <?php } ?>
        </div>
        <?php if ($period === 'custom') { ?>
        <form method="GET" action="index.php" class="flex flex-wrap items-end gap-2">
            <input type="hidden" name="page" value="report"><input type="hidden" name="period" value="custom">
            <div><label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">From</label><input type="date" name="df" value="<?php echo h($df ?: $from); ?>" class="<?php echo $inp; ?>"></div>
            <div><label class="block text-[11px] font-bold uppercase text-slate-500 mb-1">To</label><input type="date" name="dt" value="<?php echo h($dt ?: $to); ?>" class="<?php echo $inp; ?>"></div>
            <button class="px-4 py-2.5 bg-slate-800 text-white rounded-xl text-xs font-semibold">Show</button>
        </form>
        <?php } ?>
    </div>

    <!-- Step 2: Mot hishab -->
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="p-5 bg-white rounded-2xl ring-1 ring-slate-200/80">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg"><i class="fa-solid fa-hand-holding-heart"></i></div>
                <div><p class="text-[11px] font-bold uppercase text-slate-500">Total Donation <small class="normal-case font-medium text-slate-400">/ মোট অনুদান</small></p><p class="text-xl font-bold text-emerald-700"><?php echo tk($totalDonation); ?></p></div>
            </div>
            <p class="text-[11px] text-slate-400 mt-3"><?php echo (int)$d['c']; ?> records • Pending: <b class="text-amber-600"><?php echo tk($d['pend']); ?></b></p>
        </div>
        <div class="p-5 bg-white rounded-2xl ring-1 ring-slate-200/80">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-lg"><i class="fa-solid fa-calendar-days"></i></div>
                <div><p class="text-[11px] font-bold uppercase text-slate-500">Event Expenses <small class="normal-case font-medium text-slate-400">/ ইভেন্ট খরচ</small></p><p class="text-xl font-bold text-sky-700"><?php echo tk($totalEvent); ?></p></div>
            </div>
            <p class="text-[11px] text-slate-400 mt-3"><?php echo (int)$v['c']; ?> events • <b class="text-sky-700"><?php echo (int)$v['done_c']; ?> completed</b> • Budget: <b><?php echo tk($v['budget']); ?></b></p>
        </div>
        <div class="p-5 bg-white rounded-2xl ring-1 ring-slate-200/80">
            <div class="flex items-center gap-3">
                <div class="w-11 h-11 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center text-lg"><i class="fa-solid fa-receipt"></i></div>
                <div><p class="text-[11px] font-bold uppercase text-slate-500">Finance & Expenses <small class="normal-case font-medium text-slate-400">/ আর্থিক খরচ</small></p><p class="text-xl font-bold text-rose-700"><?php echo tk($totalExpense); ?></p></div>
            </div>
            <p class="text-[11px] text-slate-400 mt-3"><?php echo (int)$x['c']; ?> records • Pending: <b class="text-amber-600"><?php echo tk($x['pend']); ?></b></p>
        </div>
        <div class="p-5 rounded-2xl text-white bg-gradient-to-br <?php echo $balance >= 0 ? 'from-emerald-700 to-teal-600' : 'from-rose-700 to-red-500'; ?>">
            <p class="text-[11px] font-bold uppercase text-white/80">Balance <small class="normal-case font-medium text-white/70">/ অবশিষ্ট</small></p>
            <p class="text-2xl font-bold mt-1"><?php echo ($balance < 0 ? '-' : '') . tk(abs($balance)); ?></p>
            <p class="text-[11px] text-white/80 mt-3">Donation − Event Expenses − Finance Expenses</p>
        </div>
    </div>

    <!-- Tulona bar -->
    <div class="bg-white rounded-2xl ring-1 ring-slate-200/80 p-5 space-y-3">
        <h3 class="text-sm font-bold text-slate-800">Comparison / তুলনা (<?php echo h($label); ?>)</h3>
        <?php foreach ([['Donation', $totalDonation, 'bg-emerald-500'], ['Event Expenses', $totalEvent, 'bg-sky-500'], ['Finance Expenses', $totalExpense, 'bg-rose-500']] as $b) { ?>
        <div>
            <div class="flex justify-between text-xs mb-1"><span class="font-semibold text-slate-600"><?php echo $b[0]; ?></span><span class="font-bold text-slate-800"><?php echo tk($b[1]); ?></span></div>
            <div class="h-3 rounded-full bg-slate-100 overflow-hidden"><div class="h-full rounded-full <?php echo $b[2]; ?>" style="width:<?php echo round($b[1] / $barMax * 100); ?>%"></div></div>
        </div>
        <?php } ?>
    </div>

    <!-- Step 3: Details table -->
    <div class="bg-white rounded-2xl ring-1 ring-slate-200/80 overflow-hidden">
        <div class="p-4 space-y-3 border-b border-slate-100 no-print">
            <div class="flex flex-wrap gap-1.5">
                <a href="<?php echo h(rep_url(['tab' => 'all', 'status' => '', 'pg' => 1])); ?>" class="px-3.5 py-2 rounded-xl text-xs font-bold <?php echo $tab === 'all' ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>">All</a>
                <?php foreach ($MODS as $k => $m) { ?>
                <a href="<?php echo h(rep_url(['tab' => $k, 'status' => '', 'pg' => 1])); ?>" class="px-3.5 py-2 rounded-xl text-xs font-bold <?php echo $tab === $k ? 'bg-slate-900 text-white' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'; ?>"><?php echo h($m['label']); ?></a>
                <?php } ?>
            </div>
            <form method="GET" action="index.php" class="grid grid-cols-2 md:grid-cols-12 gap-2">
                <?php foreach (['period' => $period, 'date' => $date, 'df' => $df, 'dt' => $dt, 'tab' => $tab] as $n => $val) { echo '<input type="hidden" name="' . $n . '" value="' . h($val) . '">'; } ?>
                <input type="hidden" name="page" value="report">
                <input type="text" name="q" value="<?php echo h($q); ?>" placeholder="Search name, title, phone, voucher... / খুঁজুন" class="col-span-2 md:col-span-6 <?php echo $inp; ?>">
                <select name="status" onchange="this.form.submit()" class="md:col-span-3 <?php echo $inp; ?>" <?php echo $tab === 'all' ? 'disabled' : ''; ?>>
                    <option value=""><?php echo $tab === 'all' ? 'Status (select a tab first)' : 'All Status'; ?></option>
                    <?php if ($tab !== 'all') { foreach ($MODS[$tab]['statuses'] as $s) { echo '<option value="' . h($s) . '"' . ($status === $s ? ' selected' : '') . '>' . h(ucfirst($s)) . '</option>'; } } ?>
                </select>
                <button class="md:col-span-2 px-3 py-2.5 bg-slate-800 text-white rounded-xl text-xs font-semibold"><i class="fa-solid fa-magnifying-glass mr-1"></i>Search</button>
                <a href="<?php echo h('index.php?page=report'); ?>" class="md:col-span-1 px-3 py-2.5 text-center border border-rose-200 text-rose-600 bg-rose-50 rounded-xl text-xs font-semibold" title="Reset"><i class="fa-solid fa-rotate-left"></i></a>
            </form>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-700 uppercase font-bold border-b border-slate-200">
                    <tr><th class="p-4">Date <small class="normal-case font-medium text-slate-400">/ তারিখ</small></th><th class="p-4">Details <small class="normal-case font-medium text-slate-400">/ বিবরণ</small></th><th class="p-4 hidden sm:table-cell">Type <small class="normal-case font-medium text-slate-400">/ ধরন</small></th><th class="p-4">Amount <small class="normal-case font-medium text-slate-400">/ পরিমাণ</small></th><th class="p-4">Status <small class="normal-case font-medium text-slate-400">/ অবস্থা</small></th><th class="p-4 text-right no-print">Action</th></tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    <?php if (!$show) { ?>
                    <tr><td colspan="6" class="p-10 text-center text-slate-400"><i class="fa-regular fa-folder-open text-3xl mb-2"></i><p>No records found for this period. / এই সময়ে কোনো রেকর্ড নেই।</p></td></tr>
                    <?php } ?>
                    <?php foreach ($show as $r) {
                        $m = $MODS[$r['ty']];
                        $link = $m['link'] . (int)$r['id'];
                        $stKey = strtolower((string)$r['st']);
                        $stCls = isset($badge[$stKey]) ? $badge[$stKey] : 'bg-slate-100 text-slate-600';
                    ?>
                    <tr class="hover:bg-slate-50/80">
                        <td class="p-4 whitespace-nowrap"><?php echo h(date('d M Y', strtotime($r['dt']))); ?></td>
                        <td class="p-4">
                            <a href="<?php echo h($link); ?>" class="font-semibold text-slate-800 hover:text-emerald-600 hover:underline"><?php echo h($r['title']); ?></a>
                            <span class="block text-[10px] text-slate-400"><?php echo h($r['sub']); ?></span>
                        </td>
                        <td class="p-4 hidden sm:table-cell"><span class="px-2 py-1 rounded-md text-[10px] font-bold <?php echo $m['badge']; ?>"><i class="fa-solid <?php echo $m['icon']; ?> mr-1"></i><?php echo h($m['label']); ?></span></td>
                        <td class="p-4 font-bold text-slate-800 whitespace-nowrap"><?php echo tk($r['amt']); ?></td>
                        <td class="p-4"><span class="px-2.5 py-1 rounded-md text-[10px] font-bold <?php echo $stCls; ?>"><?php echo h(ucfirst($r['st'])); ?></span></td>
                        <td class="p-4 text-right no-print">
                            <a href="<?php echo h($link); ?>" class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg bg-sky-50 text-sky-700 font-semibold hover:bg-sky-100"><i class="fa-solid fa-pen-to-square"></i> Edit</a>
                        </td>
                    </tr>
                    <?php } ?>
                </tbody>
                <?php if ($total) { ?>
                <tfoot class="bg-slate-50 font-bold text-slate-800">
                    <tr><td colspan="3" class="p-4 text-right">Total (<?php echo $total; ?> records) / মোট</td><td class="p-4"><?php echo tk($listSum); ?></td><td colspan="2"></td></tr>
                </tfoot>
                <?php } ?>
            </table>
        </div>

        <div class="p-4 border-t border-slate-100 flex items-center justify-between gap-3 no-print">
            <span class="text-xs text-slate-500">Page <?php echo $pg; ?> / <?php echo $pages; ?></span>
            <div class="flex gap-2">
                <?php if ($pg > 1) { ?><a href="<?php echo h(rep_url(['pg' => $pg - 1])); ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold hover:bg-slate-50"><i class="fa-solid fa-chevron-left"></i> Prev</a><?php } ?>
                <?php if ($pg < $pages) { ?><a href="<?php echo h(rep_url(['pg' => $pg + 1])); ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 text-xs font-semibold hover:bg-slate-50">Next <i class="fa-solid fa-chevron-right"></i></a><?php } ?>
            </div>
        </div>
    </div>
</section>