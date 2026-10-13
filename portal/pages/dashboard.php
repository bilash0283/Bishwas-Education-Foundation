<?php
/* ==========================================================
   dashboard.php  (PHP 7.2 – 8.4 compatible, live server: PHP 7.4)

   Live server e data na ashar karon + fix:
   1. mysqli_stmt_get_result() (mysqlnd) live server e nai
      -> d_rows() try/catch e error chepe [] ferot dito, tai sob 0 / khali dekhato.
      Ekhon bind_result fallback ache, mysqlnd chara-o data ashbe.
   2. bind_param(...$p) bodle reference diye bind
   3. $_SESSION check session_start() er AGE hocchilo -> ekhon porer
   4. redirect: header already sent hole JS diye
   5. json_encode fail hole "var X = ;" hoye chart gulo ashto na -> safe fallback
   6. mb_strtoupper / mb_substr (mbstring) na thakle fatal error -> fallback
   7. GROUP BY alias (GROUP BY m / k) -> GROUP BY expression (purono MySQL e safe)
   8. mysqli_fetch_row(mysqli_query()) query fail hole fatal error -> guard
   9. Query error ager moto chape rakha hoy na: Admin er jonno page er niche
      "Query warnings" box e dekhay, tai live e kon query fail korche bujha jabe.
   ========================================================== */

if (!headers_sent()) {
    ob_start();
}
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}
if (!defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
    define('JSON_INVALID_UTF8_SUBSTITUTE', 0);
}
if (!function_exists('h')) {
    function h($s)
    {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }
}

// redirect: header jodi already pathano hoye jay (live server) tahole JS diye
function d_go($url)
{
    if (!headers_sent()) {
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Location: ' . $url);
    } else {
        echo '<script>window.location.href=' . json_encode($url) . ';</script>';
    }
    exit;
}

if (!isset($_SESSION['user_type'])) {
    d_go('index.php?page=dashboard');
}
if (!isset($db) || !$db) {
    echo 'Database connection not found.';
    return;
}
if (!@mysqli_set_charset($db, 'utf8mb4')) {
    @mysqli_set_charset($db, 'utf8');
}

$d_errors = array();

/* ---------- Role ---------- */
$me = 0;
foreach (array('user_id', 'id', 'uid', 'member_id') as $sk) {
    if (isset($_SESSION[$sk]) && (int) $_SESSION[$sk] > 0) {
        $me = (int) $_SESSION[$sk];
        break;
    }
}
$utype = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '';
if ($me > 0) {
    try {
        $ures = mysqli_query($db, "SELECT user_type FROM users WHERE id = " . $me . " LIMIT 1");
        $ur = $ures ? mysqli_fetch_row($ures) : null;
        if ($ur) {
            $utype = $ur[0];
        }
    } catch (Throwable $e) {
        $d_errors[] = 'role: ' . $e->getMessage();
    }
}
$isAdmin = ($utype === 'Admin');

/* ---------- Safe helpers ---------- */
function d_js($v, $fallback)
{
    $j = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    return ($j === false) ? $fallback : $j;
}
function d_initial($s)
{
    $s = (string) $s;
    if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
        return mb_strtoupper(mb_substr($s, 0, 1, 'UTF-8'), 'UTF-8');
    }
    return strtoupper(substr($s, 0, 1));
}
// bind_param er jonno reference toiri
function d_bind($st, $t, &$p)
{
    if ($t === '' || !count($p)) {
        return;
    }
    $args = array($st, $t);
    foreach ($p as $k => $v) {
        $args[] = &$p[$k];
    }
    call_user_func_array('mysqli_stmt_bind_param', $args);
}
// SELECT: sob row array hishebe (mysqlnd lage na). Error hole $d_errors e joma hoy.
function d_rows($db, $sql, $t = '', $p = array())
{
    global $d_errors;
    try {
        $st = mysqli_prepare($db, $sql);
        if (!$st) {
            $d_errors[] = mysqli_error($db) . ' → ' . substr(preg_replace('/\s+/', ' ', $sql), 0, 90);
            return array();
        }
        d_bind($st, $t, $p);
        if (!mysqli_stmt_execute($st)) {
            $d_errors[] = mysqli_stmt_error($st) . ' → ' . substr(preg_replace('/\s+/', ' ', $sql), 0, 90);
            mysqli_stmt_close($st);
            return array();
        }
        $o = array();
        if (function_exists('mysqli_stmt_get_result')) {
            $res = mysqli_stmt_get_result($st);
            while ($res && ($r = mysqli_fetch_assoc($res))) {
                $o[] = $r;
            }
        } else {
            mysqli_stmt_store_result($st);
            $meta = mysqli_stmt_result_metadata($st);
            if ($meta) {
                $row = array();
                $bind = array($st);
                while ($f = mysqli_fetch_field($meta)) {
                    $row[$f->name] = null;
                    $bind[] = &$row[$f->name];
                }
                mysqli_free_result($meta);
                call_user_func_array('mysqli_stmt_bind_result', $bind);
                while (mysqli_stmt_fetch($st)) {
                    $copy = array();
                    foreach ($row as $k => $v) {
                        $copy[$k] = $v;
                    }
                    $o[] = $copy;
                }
            }
        }
        mysqli_stmt_close($st);
        return $o;
    } catch (Throwable $e) {
        $d_errors[] = $e->getMessage();
        return array();
    }
}
function d_one($db, $sql, $t = '', $p = array())
{
    $r = d_rows($db, $sql, $t, $p);
    return isset($r[0]) ? $r[0] : array();
}
function d_val($row, $key, $default = 0)
{
    return isset($row[$key]) ? $row[$key] : $default;
}
function d_has($db, $tb)
{
    try {
        $r = mysqli_query($db, "SELECT 1 FROM $tb LIMIT 1");
        if ($r) {
            mysqli_free_result($r);
            return true;
        }
        return false;
    } catch (Throwable $e) {
        return false;
    }
}
function d_tk($n)
{
    return '৳' . number_format((float) $n);
}
function d_pct($now, $prev)
{
    return $prev > 0 ? (int) round(($now - $prev) / $prev * 100) : ($now > 0 ? 100 : 0);
}
function d_col($arr, $k)
{
    $o = array();
    foreach ($arr as $r) {
        $o[] = $r[$k];
    }
    return $o;
}

$has = array();
foreach (array('donations', 'expenses', 'events', 'users', 'service_recipients', 'activities', 'tasks', 'meetings', 'notices', 'inventory_items', 'certificates', 'contact_messages') as $tb) {
    $has[$tb] = d_has($db, $tb);
}

$thisM = date('Y-m-01');
$lastM = date('Y-m-01', strtotime('-1 month'));
$from12 = date('Y-m-01', strtotime('-11 month'));
$vis = "FIND_IN_SET(?, audience) > 0";

/* ---------- Headline stats ---------- */
$S = array('recipients' => 0, 'volunteers' => 0, 'members' => 0, 'donors' => 0, 'projects' => 0);
if ($has['service_recipients']) {
    $S['recipients'] = (int) d_val(d_one($db, "SELECT COUNT(*) c FROM service_recipients"), 'c');
}
if ($has['users']) {
    $S['members'] = (int) d_val(d_one($db, "SELECT COUNT(*) c FROM users"), 'c');
    $S['volunteers'] = (int) d_val(d_one($db, "SELECT COUNT(*) c FROM users WHERE user_type = 'Volunteer Member'"), 'c');
}
if ($has['donations']) {
    $S['donors'] = (int) d_val(d_one($db, "SELECT COUNT(DISTINCT donor_id) c FROM donations WHERE payment_status = 'paid'"), 'c');
}
if ($has['activities']) {
    $S['projects'] = (int) d_val(d_one($db, "SELECT COUNT(*) c FROM activities WHERE status = 'active'"), 'c');
}

/* ---------- Finance (admin only) ---------- */
$F = array('don' => 0, 'don_m' => 0, 'don_l' => 0, 'don_pend' => 0, 'don_pend_n' => 0, 'exp' => 0, 'exp_m' => 0, 'exp_pend' => 0, 'exp_pend_n' => 0, 'evt_cost' => 0, 'balance' => 0);
$months = array();
for ($i = 11; $i >= 0; $i--) {
    $k = date('Y-m', strtotime("-$i month", strtotime($thisM)));
    $months[$k] = date("M 'y", strtotime($k . '-01'));
}
$trend = array('don' => array(), 'exp' => array(), 'evt' => array());
$byFund = array();
$byCat = array();
$byMethod = array();
$topDonors = array();
$recentDon = array();
if ($isAdmin) {
    if ($has['donations']) {
        $r = d_one($db, "SELECT COALESCE(SUM(CASE WHEN payment_status='paid' THEN amount END),0) a,
                COALESCE(SUM(CASE WHEN payment_status='paid' AND donation_date>=? THEN amount END),0) b,
                COALESCE(SUM(CASE WHEN payment_status='paid' AND donation_date>=? AND donation_date<? THEN amount END),0) c,
                COALESCE(SUM(CASE WHEN payment_status='pending' THEN amount END),0) d, COALESCE(SUM(payment_status='pending'),0) e FROM donations", 'sss', array($thisM, $lastM, $thisM));
        $F['don'] = (float) d_val($r, 'a');
        $F['don_m'] = (float) d_val($r, 'b');
        $F['don_l'] = (float) d_val($r, 'c');
        $F['don_pend'] = (float) d_val($r, 'd');
        $F['don_pend_n'] = (int) d_val($r, 'e');
        foreach (d_rows($db, "SELECT DATE_FORMAT(donation_date,'%Y-%m') m, SUM(amount) s FROM donations WHERE payment_status='paid' AND donation_date>=? GROUP BY DATE_FORMAT(donation_date,'%Y-%m')", 's', array($from12)) as $x) {
            $trend['don'][$x['m']] = (float) $x['s'];
        }
        $byFund = d_rows($db, "SELECT IF(fund='','General',fund) k, SUM(amount) v FROM donations WHERE payment_status='paid' GROUP BY IF(fund='','General',fund) ORDER BY v DESC LIMIT 6");
        $byMethod = d_rows($db, "SELECT payment_method k, SUM(amount) v FROM donations WHERE payment_status='paid' GROUP BY payment_method ORDER BY v DESC LIMIT 6");
        $topDonors = d_rows($db, "SELECT name, SUM(amount) s, COUNT(*) c FROM donations WHERE payment_status='paid' GROUP BY donor_id, name ORDER BY s DESC LIMIT 5");
        $recentDon = d_rows($db, "SELECT name, amount, fund, payment_status, donation_date FROM donations ORDER BY id DESC LIMIT 6");
    }
    if ($has['expenses']) {
        $r = d_one($db, "SELECT COALESCE(SUM(CASE WHEN status='Complete' THEN amount END),0) a, COALESCE(SUM(CASE WHEN status='Complete' AND expense_date>=? THEN amount END),0) b,
                COALESCE(SUM(CASE WHEN status='Pending' THEN amount END),0) c, COALESCE(SUM(status='Pending'),0) d FROM expenses", 's', array($thisM));
        $F['exp'] = (float) d_val($r, 'a');
        $F['exp_m'] = (float) d_val($r, 'b');
        $F['exp_pend'] = (float) d_val($r, 'c');
        $F['exp_pend_n'] = (int) d_val($r, 'd');
        foreach (d_rows($db, "SELECT DATE_FORMAT(expense_date,'%Y-%m') m, SUM(amount) s FROM expenses WHERE status='Complete' AND expense_date>=? GROUP BY DATE_FORMAT(expense_date,'%Y-%m')", 's', array($from12)) as $x) {
            $trend['exp'][$x['m']] = (float) $x['s'];
        }
        $byCat = d_rows($db, "SELECT category k, SUM(amount) v FROM expenses WHERE status='Complete' GROUP BY category ORDER BY v DESC LIMIT 7");
    }
    if ($has['events']) {
        $F['evt_cost'] = (float) d_val(d_one($db, "SELECT COALESCE(SUM(actual_cost),0) c FROM events WHERE status <> 'Cancelled'"), 'c');
        foreach (d_rows($db, "SELECT DATE_FORMAT(start_date,'%Y-%m') m, SUM(actual_cost) s FROM events WHERE status <> 'Cancelled' AND start_date>=? GROUP BY DATE_FORMAT(start_date,'%Y-%m')", 's', array($from12)) as $x) {
            $trend['evt'][$x['m']] = (float) $x['s'];
        }
    }
    $F['balance'] = $F['don'] - $F['exp'] - $F['evt_cost'];
}
$memberTypes = ($isAdmin && $has['users']) ? d_rows($db, "SELECT user_type k, COUNT(*) v FROM users GROUP BY user_type ORDER BY v DESC") : array();

/* ---------- Tasks ---------- */
$T = array('open' => 0, 'over' => 0, 'done' => 0);
$taskStatus = array();
$taskList = array();
if ($has['tasks'] && ($isAdmin || $me > 0)) {
    $sc = $isAdmin ? '' : " AND t.id IN (SELECT task_id FROM task_assignees WHERE user_id = " . $me . ")";
    foreach (d_rows($db, "SELECT t.status k, COUNT(*) v FROM tasks t WHERE 1=1 $sc GROUP BY t.status") as $x) {
        $taskStatus[] = $x;
        if ($x['k'] === 'Completed') {
            $T['done'] = (int) $x['v'];
        } elseif ($x['k'] !== 'Cancelled') {
            $T['open'] += (int) $x['v'];
        }
    }
    $T['over'] = (int) d_val(d_one($db, "SELECT COUNT(*) c FROM tasks t WHERE t.due_date < CURDATE() AND t.status NOT IN ('Completed','Cancelled') $sc"), 'c');
    $taskList = d_rows($db, "SELECT t.id, t.title, t.status, t.priority, t.progress, t.due_date FROM tasks t WHERE t.status NOT IN ('Completed','Cancelled') $sc ORDER BY t.due_date IS NULL, t.due_date LIMIT 5");
}

/* ---------- Events / Meetings / Notices / Inventory ---------- */
$events = $has['events'] ? d_rows($db, "SELECT title, category, start_date, location, status FROM events WHERE end_date >= CURDATE() AND status IN ('Upcoming','Ongoing') ORDER BY start_date LIMIT 4") : array();
$evCount = $has['events'] ? d_one($db, "SELECT COUNT(*) t, COALESCE(SUM(status='Upcoming'),0) u, COALESCE(SUM(status='Completed'),0) c FROM events") : array();
$meetings = array();
$meetToday = 0;
if ($has['meetings']) {
    $w = $isAdmin ? '' : " AND $vis";
    $t = $isAdmin ? '' : 's';
    $p = $isAdmin ? array() : array($utype);
    $meetings = d_rows($db, "SELECT title, meeting_date, start_time, mtype, status FROM meetings WHERE meeting_date >= CURDATE() AND status NOT IN ('Cancelled','Completed') $w ORDER BY meeting_date, start_time LIMIT 4", $t, $p);
    $meetToday = (int) d_val(d_one($db, "SELECT COUNT(*) c FROM meetings WHERE meeting_date = CURDATE() AND status <> 'Cancelled' $w", $t, $p), 'c');
}
$notices = array();
if ($has['notices']) {
    $w = $isAdmin ? '' : " AND status='Published' AND publish_date <= CURDATE() AND (expire_date IS NULL OR expire_date >= CURDATE()) AND $vis";
    $t = $isAdmin ? '' : 's';
    $p = $isAdmin ? array() : array($utype);
    $notices = d_rows($db, "SELECT title, category, priority, publish_date, is_pinned FROM notices WHERE 1=1 $w ORDER BY is_pinned DESC, publish_date DESC, id DESC LIMIT 4", $t, $p);
}
$inv = array('n' => 0, 'val' => 0, 'low' => 0);
$lowItems = array();
if ($isAdmin && $has['inventory_items']) {
    $iv = d_one($db, "SELECT COUNT(*) n, COALESCE(SUM(quantity*unit_price),0) val, COALESCE(SUM(quantity<=min_stock),0) low FROM inventory_items");
    if ($iv) {
        $inv = $iv;
    }
    $lowItems = d_rows($db, "SELECT name, quantity, min_stock, unit FROM inventory_items WHERE quantity <= min_stock ORDER BY quantity LIMIT 5");
}
$certN = ($isAdmin && $has['certificates']) ? (int) d_val(d_one($db, "SELECT COUNT(*) c FROM certificates"), 'c') : 0;
$newMembers = ($isAdmin && $has['users']) ? d_rows($db, "SELECT member_name, user_type, photo FROM users ORDER BY id DESC LIMIT 5") : array();

// Notun contact message (contacts page thakle)
$newContacts = 0;
if ($has['contact_messages']) {
    if ($isAdmin) {
        $newContacts = (int) d_val(d_one($db, "SELECT COUNT(*) c FROM contact_messages WHERE status = 'New'"), 'c');
    } elseif ($me > 0) {
        $newContacts = (int) d_val(d_one($db, "SELECT COUNT(*) c FROM contact_messages WHERE status = 'New' AND (assigned_to = 0 OR assigned_to = ?)", 'i', array($me)), 'c');
    }
}

/* ---------- Alerts ---------- */
$alerts = array();
if ($isAdmin) {
    if ($F['don_pend_n']) {
        $alerts[] = array('fa-hand-holding-heart', $F['don_pend_n'] . ' pending donation(s)', 'index.php?page=donation', 'amber');
    }
    if ($F['exp_pend_n']) {
        $alerts[] = array('fa-receipt', $F['exp_pend_n'] . ' pending expense(s)', 'index.php?page=expense', 'orange');
    }
    if ($inv['low']) {
        $alerts[] = array('fa-boxes-stacked', $inv['low'] . ' low-stock item(s)', 'index.php?page=inventory&stock=low', 'rose');
    }
}
if ($newContacts) {
    $alerts[] = array('fa-envelope-open-text', $newContacts . ' new contact message(s)', 'index.php?page=contacts', 'emerald');
}
if ($T['over']) {
    $alerts[] = array('fa-list-check', $T['over'] . ' overdue task(s)', 'index.php?page=tasks&due=overdue', 'rose');
}
if ($meetToday) {
    $alerts[] = array('fa-people-group', $meetToday . ' meeting(s) today', 'index.php?page=meetings', 'sky');
}

$PR = array('Urgent' => 'bg-rose-100 text-rose-700', 'High' => 'bg-amber-100 text-amber-700', 'Medium' => 'bg-sky-50 text-sky-700', 'Low' => 'bg-slate-100 text-slate-600', 'Important' => 'bg-amber-100 text-amber-700', 'Normal' => 'bg-slate-100 text-slate-600');
$ST = array('paid' => 'bg-emerald-100 text-emerald-700', 'pending' => 'bg-amber-100 text-amber-700', 'failed' => 'bg-orange-100 text-orange-700', 'rejected' => 'bg-rose-100 text-rose-700');
$delta = d_pct($F['don_m'], $F['don_l']);
$hr = (int) date('G');
$greet = $hr < 12 ? 'Good morning' : ($hr < 17 ? 'Good afternoon' : 'Good evening');
$card = 'bg-white rounded-2xl border border-slate-200/80 shadow-xs';

// Chart data (JS e pathanor jonno)
$trendDon = array();
$trendExp = array();
$trendEvt = array();
foreach (array_keys($months) as $k) {
    $trendDon[] = isset($trend['don'][$k]) ? $trend['don'][$k] : 0;
    $trendExp[] = isset($trend['exp'][$k]) ? $trend['exp'][$k] : 0;
    $trendEvt[] = isset($trend['evt'][$k]) ? $trend['evt'][$k] : 0;
}
$fl = function ($arr) {
    $o = array();
    foreach ($arr as $v) {
        $o[] = (float) $v; }return $o; };
$il = function ($arr) {
    $o = array();
    foreach ($arr as $v) {
        $o[] = (int) $v; }return $o; };
?>
<section class="page-content space-y-6 max-w-7xl mx-auto">

    <!-- Hero -->
    <div
        class="relative overflow-hidden rounded-3xl bg-gradient-to-r from-slate-900 via-slate-800 to-emerald-700 text-white p-5 sm:p-7">
        <div class="absolute -right-10 -top-10 h-56 w-56 rounded-full bg-emerald-400/20 blur-3xl pointer-events-none">
        </div>
        <div class="relative flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div>
                <p class="text-xs text-emerald-200 font-semibold"><i
                        class="fa-regular fa-calendar mr-1"></i><?php echo h(date('l, d F Y')); ?></p>
                <h2 class="text-2xl font-bold tracking-tight mt-1">Executive <b
                        class="text-emerald-300"><?php echo h($utype); ?></b> Dashboard</h2>
                <p class="text-xs text-slate-300 mt-0.5"><?php echo $greet; ?>! Real-time stats and foundation activity
                    metrics.</p>
            </div>
            <?php if ($isAdmin) { ?>
                <div class="flex flex-wrap gap-2">
                    <?php foreach (array(array('donation', 'Donation', 'fa-hand-holding-heart'), array('expense', 'Expense', 'fa-receipt'), array('events', 'Event', 'fa-calendar-days'), array('tasks', 'Task', 'fa-list-check'), array('meetings', 'Meeting', 'fa-people-group'), array('notice_board', 'Notice', 'fa-bullhorn'), array('contacts', 'Contacts', 'fa-envelope-open-text')) as $q) { ?>
                        <a href="index.php?page=<?php echo $q[0]; ?>"
                            class="px-3 py-2 rounded-xl bg-white/10 hover:bg-white/20 border border-white/10 text-[11px] font-semibold backdrop-blur"><i
                                class="fa-solid <?php echo $q[2]; ?> mr-1.5 text-emerald-300"></i><?php echo $q[1]; ?></a>
                    <?php } ?>
                </div>
            <?php } ?>
        </div>
    </div>

    <?php if ($alerts) { ?>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($alerts as $a) { ?><a href="<?php echo h($a[2]); ?>"
                    class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-<?php echo $a[3]; ?>-50 border border-<?php echo $a[3]; ?>-200 text-<?php echo $a[3]; ?>-700 text-xs font-semibold hover:shadow-md transition"><i
                        class="fa-solid <?php echo $a[0]; ?>"></i><?php echo h($a[1]); ?></a><?php } ?>
        </div>
    <?php } ?>

    <!-- Foundation at a glance -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <?php foreach (array(array('Beneficiaries', $S['recipients'], 'emerald', 'fa-hand-holding-heart'), array('Active Volunteers', $S['volunteers'], 'teal', 'fa-user-ninja'), array('Active Donors', $S['donors'], 'sky', 'fa-users'), array('Active Projects', $S['projects'], 'amber', 'fa-box-archive')) as $c) { ?>
            <div class="p-4 sm:p-5 <?php echo $card; ?> flex items-center gap-3 sm:gap-4">
                <div
                    class="w-11 h-11 sm:w-12 sm:h-12 rounded-xl bg-<?php echo $c[2]; ?>-50 text-<?php echo $c[2]; ?>-600 flex items-center justify-center text-lg sm:text-xl shrink-0">
                    <i class="fa-solid <?php echo $c[3]; ?>"></i></div>
                <div class="min-w-0">
                    <p class="text-[10px] sm:text-[11px] font-bold text-slate-400 uppercase truncate"><?php echo $c[0]; ?>
                    </p>
                    <h3 class="text-xl sm:text-2xl font-bold text-slate-800 mt-0.5"><?php echo number_format($c[1]); ?></h3>
                </div>
            </div>
        <?php } ?>
    </div>

    <?php if ($isAdmin) { ?>
        <!-- Finance -->
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
            <div class="p-4 sm:p-5 rounded-2xl bg-gradient-to-br from-emerald-600 to-teal-600 text-white">
                <p class="text-[10px] font-bold uppercase text-white/80">Total Donation</p>
                <h3 class="text-xl sm:text-2xl font-bold mt-1"><?php echo d_tk($F['don']); ?></h3>
                <p class="text-[11px] mt-2 text-white/90"><i
                        class="fa-solid fa-arrow-<?php echo $delta >= 0 ? 'trend-up' : 'trend-down'; ?> mr-1"></i><?php echo abs($delta); ?>%
                    <?php echo $delta >= 0 ? 'up' : 'down'; ?> vs last month</p>
            </div>
            <div class="p-4 sm:p-5 <?php echo $card; ?>">
                <p class="text-[10px] font-bold uppercase text-slate-400">This Month Donation</p>
                <h3 class="text-xl sm:text-2xl font-bold text-emerald-700 mt-1"><?php echo d_tk($F['don_m']); ?></h3>
                <p class="text-[11px] mt-2 text-slate-400">Pending: <b
                        class="text-amber-600"><?php echo d_tk($F['don_pend']); ?></b></p>
            </div>
            <div class="p-4 sm:p-5 <?php echo $card; ?>">
                <p class="text-[10px] font-bold uppercase text-slate-400">Expenses (Finance + Events)</p>
                <h3 class="text-xl sm:text-2xl font-bold text-rose-600 mt-1"><?php echo d_tk($F['exp'] + $F['evt_cost']); ?>
                </h3>
                <p class="text-[11px] mt-2 text-slate-400">Finance <?php echo d_tk($F['exp']); ?> • Events
                    <?php echo d_tk($F['evt_cost']); ?></p>
            </div>
            <div
                class="p-4 sm:p-5 rounded-2xl text-white bg-gradient-to-br <?php echo $F['balance'] >= 0 ? 'from-slate-800 to-slate-600' : 'from-rose-700 to-red-500'; ?>">
                <p class="text-[10px] font-bold uppercase text-white/70">Balance</p>
                <h3 class="text-xl sm:text-2xl font-bold mt-1">
                    <?php echo ($F['balance'] < 0 ? '-' : '') . d_tk(abs($F['balance'])); ?></h3>
                <p class="text-[11px] mt-2 text-white/70">Donation − all expenses</p>
            </div>
        </div>

        <!-- Charts row 1 -->
        <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">
            <div class="xl:col-span-2 <?php echo $card; ?> p-4 sm:p-5">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="font-bold text-sm text-slate-800">Income vs Expenses <small
                            class="font-medium text-slate-400">· last 12 months</small></h3><a href="index.php?page=reports"
                        class="text-[11px] font-bold text-emerald-600">Full report →</a>
                </div>
                <div class="h-64"><canvas id="c_trend"></canvas></div>
            </div>
            <div class="<?php echo $card; ?> p-4 sm:p-5">
                <h3 class="font-bold text-sm text-slate-800 mb-3">Donation by Fund</h3>
                <?php if ($byFund) { ?>
                    <div class="h-64"><canvas id="c_fund"></canvas></div>
                <?php } else {
                    echo '<p class="h-64 flex items-center justify-center text-xs text-slate-400">No paid donations yet.</p>';
                } ?>
            </div>
        </div>

        <!-- Charts row 2 -->
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
            <div class="<?php echo $card; ?> p-4 sm:p-5">
                <h3 class="font-bold text-sm text-slate-800 mb-3">Expense by Category</h3>
                <?php if ($byCat) { ?>
                    <div class="h-56"><canvas id="c_cat"></canvas></div>
                <?php } else {
                    echo '<p class="h-56 flex items-center justify-center text-xs text-slate-400">No completed expenses.</p>';
                } ?>
            </div>
            <div class="<?php echo $card; ?> p-4 sm:p-5">
                <h3 class="font-bold text-sm text-slate-800 mb-3">Payment Methods</h3>
                <?php if ($byMethod) { ?>
                    <div class="h-56"><canvas id="c_method"></canvas></div>
                <?php } else {
                    echo '<p class="h-56 flex items-center justify-center text-xs text-slate-400">No data.</p>';
                } ?>
            </div>
            <div class="<?php echo $card; ?> p-4 sm:p-5">
                <h3 class="font-bold text-sm text-slate-800 mb-3">Members by Type</h3>
                <?php if ($memberTypes) { ?>
                    <div class="h-56"><canvas id="c_members"></canvas></div>
                <?php } else {
                    echo '<p class="h-56 flex items-center justify-center text-xs text-slate-400">No members.</p>';
                } ?>
            </div>
            <div class="<?php echo $card; ?> p-4 sm:p-5">
                <h3 class="font-bold text-sm text-slate-800 mb-3">Task Status</h3>
                <?php if ($taskStatus) { ?>
                    <div class="h-56"><canvas id="c_task"></canvas></div>
                <?php } else {
                    echo '<p class="h-56 flex items-center justify-center text-xs text-slate-400">No tasks yet.</p>';
                } ?>
            </div>
        </div>
    <?php } ?>

    <!-- Operations stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <?php $ops = array(array('Events', (int) d_val($evCount, 't'), (int) d_val($evCount, 'u') . ' upcoming', 'violet', 'fa-calendar-days', 'events'), array('Open Tasks', $T['open'], $T['over'] . ' overdue', 'rose', 'fa-list-check', 'tasks'));
        if ($isAdmin) {
            $ops[] = array('Inventory Value', d_tk($inv['val']), (int) $inv['n'] . ' items • ' . (int) $inv['low'] . ' low', 'amber', 'fa-boxes-stacked', 'inventory');
            $ops[] = array('Certificates Issued', $certN, 'ID cards & certificates', 'sky', 'fa-award', 'certificates');
        } else {
            $ops[] = array('Meetings Today', $meetToday, 'for you', 'sky', 'fa-people-group', 'meetings');
            $ops[] = array('Completed Tasks', $T['done'], 'well done', 'emerald', 'fa-circle-check', 'tasks');
        }
        foreach ($ops as $c) { ?>
            <a href="index.php?page=<?php echo $c[5]; ?>" class="p-4 <?php echo $card; ?> hover:shadow-md transition">
                <div class="flex items-center justify-between">
                    <p class="text-[10px] font-bold uppercase text-slate-400"><?php echo $c[0]; ?></p><span
                        class="w-8 h-8 rounded-lg bg-<?php echo $c[3]; ?>-50 text-<?php echo $c[3]; ?>-600 flex items-center justify-center text-sm"><i
                            class="fa-solid <?php echo $c[4]; ?>"></i></span>
                </div>
                <h3 class="text-xl font-bold text-slate-800 mt-1">
                    <?php echo is_numeric($c[1]) ? number_format($c[1]) : h($c[1]); ?></h3>
                <p class="text-[11px] text-slate-400 mt-0.5"><?php echo h($c[2]); ?></p>
            </a>
        <?php } ?>
    </div>

    <!-- Lists -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

        <div class="<?php echo $card; ?> overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-sm text-slate-800"><i
                        class="fa-solid fa-bullhorn text-emerald-600 mr-2"></i>Latest Notices</h3><a
                    href="index.php?page=notice_board" class="text-[11px] font-bold text-emerald-600">View all</a>
            </div>
            <div class="divide-y divide-slate-100">
                <?php foreach ($notices as $n) { ?>
                    <div class="p-3.5 flex items-start gap-3"><span
                            class="w-9 h-9 rounded-lg bg-emerald-50 text-emerald-600 flex items-center justify-center shrink-0"><i
                                class="fa-solid <?php echo $n['is_pinned'] ? 'fa-thumbtack' : 'fa-bullhorn'; ?> text-xs"></i></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold text-slate-800 truncate"><?php echo h($n['title']); ?></p>
                            <p class="text-[10px] text-slate-400 mt-0.5"><?php echo h($n['category']); ?> •
                                <?php echo h(date('d M Y', strtotime($n['publish_date']))); ?></p>
                        </div>
                        <span
                            class="px-2 py-0.5 rounded-full text-[10px] font-bold <?php echo isset($PR[$n['priority']]) ? $PR[$n['priority']] : ''; ?>"><?php echo h($n['priority']); ?></span>
                    </div>
                <?php }
                if (!$notices) {
                    echo '<p class="p-6 text-center text-xs text-slate-400">No notices.</p>';
                } ?>
            </div>
        </div>

        <div class="<?php echo $card; ?> overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-sm text-slate-800"><i
                        class="fa-solid fa-people-group text-sky-600 mr-2"></i>Upcoming Meetings</h3><a
                    href="index.php?page=meetings" class="text-[11px] font-bold text-emerald-600">View all</a>
            </div>
            <div class="divide-y divide-slate-100">
                <?php foreach ($meetings as $m) {
                    $d = strtotime($m['meeting_date']); ?>
                    <div class="p-3.5 flex items-center gap-3">
                        <div class="w-11 shrink-0 rounded-lg overflow-hidden text-center ring-1 ring-slate-200">
                            <div class="bg-sky-600 text-white text-[9px] font-bold py-0.5 uppercase">
                                <?php echo date('M', $d); ?></div>
                            <div class="text-base font-bold text-slate-800 py-0.5"><?php echo date('d', $d); ?></div>
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold text-slate-800 truncate"><?php echo h($m['title']); ?></p>
                            <p class="text-[10px] text-slate-400 mt-0.5">
                                <?php echo $m['start_time'] ? h(date('h:i A', strtotime($m['start_time']))) : 'Time not set'; ?>
                                • <?php echo h($m['mtype']); ?></p>
                        </div>
                    </div>
                <?php }
                if (!$meetings) {
                    echo '<p class="p-6 text-center text-xs text-slate-400">No upcoming meetings.</p>';
                } ?>
            </div>
        </div>

        <div class="<?php echo $card; ?> overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-sm text-slate-800"><i
                        class="fa-solid fa-calendar-days text-violet-600 mr-2"></i>Upcoming Events</h3><a
                    href="index.php?page=events" class="text-[11px] font-bold text-emerald-600">View all</a>
            </div>
            <div class="divide-y divide-slate-100">
                <?php foreach ($events as $e) { ?>
                    <div class="p-3.5 flex items-center gap-3"><span
                            class="w-9 h-9 rounded-lg bg-violet-50 text-violet-600 flex items-center justify-center shrink-0"><i
                                class="fa-solid fa-calendar-check text-xs"></i></span>
                        <div class="min-w-0 flex-1">
                            <p class="text-xs font-bold text-slate-800 truncate"><?php echo h($e['title']); ?></p>
                            <p class="text-[10px] text-slate-400 mt-0.5 truncate">
                                <?php echo h(date('d M Y', strtotime($e['start_date']))); ?> •
                                <?php echo h($e['location']); ?></p>
                        </div>
                        <span
                            class="px-2 py-0.5 rounded-full text-[10px] font-bold <?php echo $e['status'] === 'Ongoing' ? 'bg-emerald-100 text-emerald-700' : 'bg-sky-100 text-sky-700'; ?>"><?php echo h($e['status']); ?></span>
                    </div>
                <?php }
                if (!$events) {
                    echo '<p class="p-6 text-center text-xs text-slate-400">No upcoming events.</p>';
                } ?>
            </div>
        </div>

        <div class="<?php echo $card; ?> overflow-hidden">
            <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                <h3 class="font-bold text-sm text-slate-800"><i
                        class="fa-solid fa-list-check text-rose-600 mr-2"></i><?php echo $isAdmin ? 'Open Tasks' : 'My Tasks'; ?>
                </h3><a href="index.php?page=tasks" class="text-[11px] font-bold text-emerald-600">View all</a>
            </div>
            <div class="divide-y divide-slate-100">
                <?php foreach ($taskList as $k) {
                    $ov = $k['due_date'] && $k['due_date'] < date('Y-m-d'); ?>
                    <div class="p-3.5 space-y-1.5">
                        <div class="flex items-center justify-between gap-2">
                            <p class="text-xs font-bold text-slate-800 truncate"><?php echo h($k['title']); ?></p><span
                                class="px-2 py-0.5 rounded-full text-[10px] font-bold shrink-0 <?php echo isset($PR[$k['priority']]) ? $PR[$k['priority']] : ''; ?>"><?php echo h($k['priority']); ?></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="h-1.5 flex-1 rounded-full bg-slate-100 overflow-hidden">
                                <div class="h-full rounded-full bg-emerald-500"
                                    style="width:<?php echo (int) $k['progress']; ?>%"></div>
                            </div><span
                                class="text-[10px] font-bold text-slate-500"><?php echo (int) $k['progress']; ?>%</span>
                            <span
                                class="text-[10px] <?php echo $ov ? 'text-rose-600 font-bold' : 'text-slate-400'; ?>"><?php echo $k['due_date'] ? h(date('d M', strtotime($k['due_date']))) : '—'; ?></span>
                        </div>
                    </div>
                <?php }
                if (!$taskList) {
                    echo '<p class="p-6 text-center text-xs text-slate-400">No open tasks.</p>';
                } ?>
            </div>
        </div>

        <?php if ($isAdmin) { ?>
            <div class="<?php echo $card; ?> overflow-hidden lg:col-span-2">
                <div class="p-4 border-b border-slate-100 flex items-center justify-between">
                    <h3 class="font-bold text-sm text-slate-800">Recent Donations</h3><a href="index.php?page=donation"
                        class="text-[11px] font-bold text-emerald-600">View all</a>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs text-slate-600">
                        <thead class="bg-slate-50 text-slate-700 uppercase font-bold border-b border-slate-200">
                            <tr>
                                <th class="p-3.5">Donor</th>
                                <th class="p-3.5">Fund</th>
                                <th class="p-3.5">Amount</th>
                                <th class="p-3.5">Status</th>
                                <th class="p-3.5">Date</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            <?php foreach ($recentDon as $d) {
                                $sk = strtolower($d['payment_status']); ?>
                                <tr class="hover:bg-slate-50/80">
                                    <td class="p-3.5 font-semibold text-slate-800"><?php echo h($d['name']); ?></td>
                                    <td class="p-3.5"><?php echo h($d['fund'] ? $d['fund'] : 'General'); ?></td>
                                    <td class="p-3.5 font-bold text-slate-800"><?php echo d_tk($d['amount']); ?></td>
                                    <td class="p-3.5"><span
                                            class="px-2.5 py-1 rounded-md font-bold text-[10px] <?php echo isset($ST[$sk]) ? $ST[$sk] : 'bg-slate-100 text-slate-600'; ?>"><?php echo h(ucfirst($d['payment_status'])); ?></span>
                                    </td>
                                    <td class="p-3.5 whitespace-nowrap">
                                        <?php echo h(date('d M Y', strtotime($d['donation_date']))); ?></td>
                                </tr>
                            <?php }
                            if (!$recentDon) {
                                echo '<tr><td colspan="5" class="p-6 text-center text-slate-400">No donations yet.</td></tr>';
                            } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="<?php echo $card; ?> p-4 sm:p-5">
                <h3 class="font-bold text-sm text-slate-800 mb-3"><i class="fa-solid fa-trophy text-amber-500 mr-2"></i>Top
                    Donors</h3>
                <?php $mx = $topDonors ? max(array_column($topDonors, 's')) : 1;
                if ($mx <= 0) {
                    $mx = 1;
                }
                foreach ($topDonors as $i => $d) { ?>
                    <div class="mb-3">
                        <div class="flex justify-between text-xs mb-1"><span
                                class="font-semibold text-slate-700 truncate"><?php echo ($i + 1) . '. ' . h($d['name']); ?>
                                <span class="text-slate-400 font-normal">(<?php echo (int) $d['c']; ?>x)</span></span><b
                                class="text-emerald-700"><?php echo d_tk($d['s']); ?></b></div>
                        <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                            <div class="h-full rounded-full bg-gradient-to-r from-emerald-500 to-teal-500"
                                style="width:<?php echo round($d['s'] / $mx * 100); ?>%"></div>
                        </div>
                    </div>
                <?php }
                if (!$topDonors) {
                    echo '<p class="text-xs text-slate-400 text-center py-6">No data.</p>';
                } ?>
            </div>

            <div class="<?php echo $card; ?> p-4 sm:p-5 space-y-5">
                <div>
                    <h3 class="font-bold text-sm text-slate-800 mb-3"><i
                            class="fa-solid fa-user-plus text-sky-500 mr-2"></i>Newest Members</h3>
                    <?php foreach ($newMembers as $m) {
                        $ph = (!empty($m['photo']) && is_file('public/uploads/members/' . basename($m['photo']))) ? 'public/uploads/members/' . $m['photo'] : ''; ?>
                        <div class="flex items-center gap-3 mb-2.5">
                            <?php echo $ph ? '<img src="' . h($ph) . '" class="w-8 h-8 rounded-full object-cover">' : '<div class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center text-xs font-bold">' . h(d_initial($m['member_name'])) . '</div>'; ?>
                            <div class="min-w-0">
                                <p class="text-xs font-semibold text-slate-800 truncate"><?php echo h($m['member_name']); ?></p>
                                <p class="text-[10px] text-slate-400"><?php echo h($m['user_type']); ?></p>
                            </div>
                        </div>
                    <?php }
                    if (!$newMembers) {
                        echo '<p class="text-xs text-slate-400">No members.</p>';
                    } ?>
                </div>
                <?php if ($lowItems) { ?>
                    <div>
                        <h3 class="font-bold text-sm text-slate-800 mb-3"><i
                                class="fa-solid fa-triangle-exclamation text-rose-500 mr-2"></i>Low Stock</h3>
                        <?php foreach ($lowItems as $i) { ?>
                            <div class="flex justify-between text-xs mb-1.5"><span
                                    class="text-slate-700 truncate"><?php echo h($i['name']); ?></span><b
                                    class="text-rose-600 shrink-0 ml-2"><?php echo rtrim(rtrim(number_format((float) $i['quantity'], 2, '.', ''), '0'), '.'); ?>
                                    <?php echo h($i['unit']); ?></b></div><?php } ?>
                    </div><?php } ?>
            </div>
        <?php } ?>
    </div>

    <?php if ($isAdmin && $d_errors) { ?>
        <details class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs text-amber-800">
            <summary class="font-bold cursor-pointer"><i class="fa-solid fa-triangle-exclamation mr-1.5"></i>Query warnings
                (<?php echo count($d_errors); ?>) — only Admin sees this</summary>
            <ul class="mt-2 space-y-1 list-disc pl-5 break-words">
                <?php foreach (array_unique($d_errors) as $er) {
                    echo '<li>' . h($er) . '</li>';
                } ?></ul>
        </details>
    <?php } ?>
</section>

<?php if ($isAdmin) { ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script>
        (function () {
            if (!window.Chart) { return; }
            var $ = function (id) { return document.getElementById(id); };
            Chart.defaults.font.family = "Inter, 'Hind Siliguri', sans-serif"; Chart.defaults.font.size = 11; Chart.defaults.color = '#64748b';
            var PAL = ['#10b981', '#0ea5e9', '#f59e0b', '#8b5cf6', '#f43f5e', '#14b8a6', '#f97316', '#64748b'];
            var TASKC = { 'To Do': '#94a3b8', 'In Progress': '#0ea5e9', 'In Review': '#8b5cf6', 'On Hold': '#f59e0b', 'Completed': '#10b981', 'Cancelled': '#f43f5e' };
            function tk(v) { return '৳' + Number(v || 0).toLocaleString('en-US'); }
            function short(v) { return v >= 100000 ? (v / 100000) + 'L' : (v >= 1000 ? (v / 1000) + 'K' : v); }

            new Chart($('c_trend'), {
                type: 'bar', data: {
                    labels: <?php echo d_js(array_values($months), '[]'); ?>, datasets: [
                        { label: 'Donation', backgroundColor: '#10b981', borderRadius: 6, data: <?php echo d_js($fl($trendDon), '[]'); ?> },
                        { label: 'Expenses', backgroundColor: '#f43f5e', borderRadius: 6, data: <?php echo d_js($fl($trendExp), '[]'); ?> },
                        { label: 'Events', backgroundColor: '#0ea5e9', borderRadius: 6, data: <?php echo d_js($fl($trendEvt), '[]'); ?> }]
                },
                options: {
                    responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false }, plugins: { legend: { position: 'bottom', labels: { usePointStyle: true } }, tooltip: { callbacks: { label: function (c) { return c.dataset.label + ': ' + tk(c.parsed.y); } } } },
                    scales: { y: { beginAtZero: true, ticks: { callback: short }, grid: { color: '#f1f5f9' } }, x: { grid: { display: false } } }
                }
            });

            function donut(id, labels, data, colors) {
                var el = $(id); if (!el) { return; }
                new Chart(el, {
                    type: 'doughnut', data: { labels: labels, datasets: [{ data: data, backgroundColor: colors || PAL, borderWidth: 2, borderColor: '#fff' }] },
                    options: { responsive: true, maintainAspectRatio: false, cutout: '62%', plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } }, tooltip: { callbacks: { label: function (c) { return c.label + ': ' + (id === 'c_members' || id === 'c_task' ? c.parsed : tk(c.parsed)); } } } } }
                });
            }
            donut('c_fund', <?php echo d_js(d_col($byFund, 'k'), '[]'); ?>, <?php echo d_js($fl(d_col($byFund, 'v')), '[]'); ?>);
            donut('c_cat', <?php echo d_js(d_col($byCat, 'k'), '[]'); ?>, <?php echo d_js($fl(d_col($byCat, 'v')), '[]'); ?>);
            donut('c_members', <?php echo d_js(d_col($memberTypes, 'k'), '[]'); ?>, <?php echo d_js($il(d_col($memberTypes, 'v')), '[]'); ?>);
            var tl = <?php echo d_js(d_col($taskStatus, 'k'), '[]'); ?>; donut('c_task', tl, <?php echo d_js($il(d_col($taskStatus, 'v')), '[]'); ?>, tl.map(function (s) { return TASKC[s] || '#64748b'; }));

            if ($('c_method')) {
                new Chart($('c_method'), {
                    type: 'bar', data: { labels: <?php echo d_js(d_col($byMethod, 'k'), '[]'); ?>, datasets: [{ data: <?php echo d_js($fl(d_col($byMethod, 'v')), '[]'); ?>, backgroundColor: PAL, borderRadius: 6 }] },
                    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return tk(c.parsed.x); } } } }, scales: { x: { ticks: { callback: short }, grid: { color: '#f1f5f9' } }, y: { grid: { display: false } } } }
                });
            }
        })();
    </script>
<?php } ?>