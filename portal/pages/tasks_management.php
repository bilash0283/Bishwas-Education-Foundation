<?php
/* ==========================================================
   tasks.php  (PHP 7.2 – 8.4 compatible)

   Live server fix list:
   1. session_start() age, tarpor $_SESSION check
   2. mysqli_stmt_get_result() (mysqlnd) na thakle bind_result fallback
   3. bind_param(...$p) bodle reference diye bind
   4. redirect: header already sent hole (live e output_buffering off) JS diye redirect
   5. mb_substr / mb_strtoupper (mbstring) na thakle fallback
   6. json_encode fail hole "var TASKS = ;" hoye JS bhenge jeto -> safe fallback
   7. mysqli_fetch_row(mysqli_query()) query fail hole fatal error -> guard
   8. INSERT ... SELECT ?, id -> simple INSERT (user ache kina alada check)
   9. foreach by-reference ($tk) soriye index diye likha
   ========================================================== */

if (!headers_sent()) {
    ob_start();
}
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

// redirect: header jodi already pathano hoye jay (live server) tahole JS diye
function tk_go($url)
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

// only admin and volunteers can visit
if (!isset($_SESSION['user_type']) || !in_array($_SESSION['user_type'], array('Admin', 'Volunteer Member'))) {
    tk_go('index.php?page=dashboard');
}

if (!function_exists('h')) {
    function h($s)
    {
        return htmlspecialchars((string) $s, ENT_QUOTES, 'UTF-8');
    }
}
if (!defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
    define('JSON_INVALID_UTF8_SUBSTITUTE', 0);
}
if (!@mysqli_set_charset($db, 'utf8mb4')) {
    @mysqli_set_charset($db, 'utf8');
}

/* ---------- Small helpers ---------- */
function tk_sub($s, $start, $len)
{
    return function_exists('mb_substr') ? mb_substr($s, $start, $len, 'UTF-8') : substr($s, $start, $len);
}
function tk_initial($s)
{
    $s = (string) $s;
    if (function_exists('mb_substr') && function_exists('mb_strtoupper')) {
        return mb_strtoupper(mb_substr($s, 0, 1, 'UTF-8'), 'UTF-8');
    }
    return strtoupper(substr($s, 0, 1));
}
function tk_js($v, $fallback)
{
    $j = json_encode($v, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    return ($j === false) ? $fallback : $j;
}
function tk_first($rows, $key, $default = 0)
{
    return isset($rows[0][$key]) ? $rows[0][$key] : $default;
}

/* ---------- Role ---------- */
$me = 0;
foreach (array('user_id', 'id', 'uid', 'member_id') as $sk) {
    if (isset($_SESSION[$sk]) && (int) $_SESSION[$sk] > 0) {
        $me = (int) $_SESSION[$sk];
        break;
    }
}
$utype = isset($_SESSION['user_type']) ? $_SESSION['user_type'] : '';
if ($me > 0) {   // role database (users table) theke verify kora hoy
    $ures = mysqli_query($db, "SELECT user_type FROM users WHERE id = " . $me . " LIMIT 1");
    $ur = $ures ? mysqli_fetch_row($ures) : null;
    if ($ur) {
        $utype = $ur[0];
    }
}
$isAdmin = ($utype === 'Admin');
$isVol = ($utype === 'Volunteer Member');
if (!$isAdmin && !($isVol && $me > 0)) {
    echo '<section class="page-content max-w-xl mx-auto"><div class="p-6 bg-white rounded-2xl ring-1 ring-slate-200 text-center space-y-2">
          <i class="fa-solid fa-lock text-3xl text-slate-300"></i><h3 class="font-bold text-slate-800">Access Restricted</h3>
          <p class="text-xs text-slate-500">Only Admin and Volunteer members can open Task Management. / শুধু অ্যাডমিন ও ভলান্টিয়ার এই পেজ দেখতে পারবেন।</p></div></section>';
    return;
}

/* ---------- Database table check (tasks.sql age import korte hobe) ---------- */
$dbReady = true;
try {
    foreach (array('tasks', 'task_assignees', 'task_updates') as $tb) {
        if (!mysqli_query($db, "SELECT 1 FROM $tb LIMIT 1")) {
            $dbReady = false;
        }
    }
} catch (Throwable $e) {
    $dbReady = false;
}
if (!$dbReady) {
    echo '<section class="page-content max-w-xl mx-auto"><div class="p-6 bg-amber-50 border border-amber-200 rounded-2xl text-center space-y-2">
          <i class="fa-solid fa-database text-3xl text-amber-400"></i><h3 class="font-bold text-slate-800">Database tables not found</h3>
          <p class="text-xs text-slate-600">Please import <b>tasks.sql</b> in phpMyAdmin first. / আগে phpMyAdmin-এ tasks.sql ইম্পোর্ট করুন।</p></div></section>';
    return;
}

if (empty($_SESSION['csrf'])) {
    $_SESSION['csrf'] = function_exists('random_bytes') ? bin2hex(random_bytes(16)) : md5(uniqid((string) mt_rand(), true));
}

$page = isset($_GET['page']) ? $_GET['page'] : 'tasks';
$base = 'index.php?page=' . urlencode($page);
$member_dir = 'public/uploads/members/';
$STATUS = array(
    'To Do' => 'bg-slate-100 text-slate-700',
    'In Progress' => 'bg-sky-100 text-sky-700',
    'In Review' => 'bg-violet-100 text-violet-700',
    'On Hold' => 'bg-amber-100 text-amber-700',
    'Completed' => 'bg-emerald-100 text-emerald-700',
    'Cancelled' => 'bg-rose-100 text-rose-700'
);
$BAR = array('To Do' => 'bg-slate-400', 'In Progress' => 'bg-sky-500', 'In Review' => 'bg-violet-500', 'On Hold' => 'bg-amber-500', 'Completed' => 'bg-emerald-500', 'Cancelled' => 'bg-rose-400');
$PRIO = array('Low' => 'bg-slate-100 text-slate-600', 'Medium' => 'bg-sky-50 text-sky-700', 'High' => 'bg-amber-100 text-amber-700', 'Urgent' => 'bg-rose-100 text-rose-700');

// bind_param er jonno reference toiri
function tk_bind($st, $t, &$p)
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

// SELECT: sob row array hishebe (mysqlnd lage na)
function tk_rows($db, $sql, $t = '', $p = array())
{
    $st = mysqli_prepare($db, $sql);
    if (!$st) {
        return array();
    }
    tk_bind($st, $t, $p);
    if (!mysqli_stmt_execute($st)) {
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
}

// INSERT/UPDATE/DELETE: affected rows (fail hole -1)
function tk_run($db, $sql, $t, $p)
{
    $st = mysqli_prepare($db, $sql);
    if (!$st) {
        return -1;
    }
    tk_bind($st, $t, $p);
    $ok = mysqli_stmt_execute($st);
    $n = mysqli_stmt_affected_rows($st);
    mysqli_stmt_close($st);
    return $ok ? $n : -1;
}
function tk_photo($f, $dir)
{
    return ($f != '' && is_file($dir . basename($f))) ? $dir . $f : '';
}

/* ---------- ACTIONS ---------- */
$post_csrf = isset($_POST['csrf']) ? (string) $_POST['csrf'] : '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($_SESSION['csrf'], $post_csrf)) {
    $do = isset($_POST['do']) ? $_POST['do'] : '';
    $ok = true;
    $msg = '';
    $back_in = isset($_POST['back']) ? $_POST['back'] : '';
    $back = (strpos($back_in, $base) === 0) ? $back_in : $base;
    $id = (int) (isset($_POST['id']) ? $_POST['id'] : 0);

    if ($do === 'update') {                                   // Admin + assigned Volunteer
        $status = isset($_POST['status']) ? $_POST['status'] : '';
        $prog = max(0, min(100, (int) (isset($_POST['progress']) ? $_POST['progress'] : 0)));
        $note = tk_sub(trim(isset($_POST['note']) ? $_POST['note'] : ''), 0, 500);
        $allowed = $isAdmin || tk_rows($db, "SELECT 1 x FROM task_assignees WHERE task_id = ? AND user_id = ?", 'ii', array($id, $me));
        if (!$allowed || !isset($STATUS[$status])) {
            $ok = false;
            $msg = 'You cannot update this task.';
        } elseif (!$isAdmin && $status === 'Cancelled') {
            $ok = false;
            $msg = 'Only admin can cancel a task.';
        } else {
            if ($status === 'Completed') {
                $prog = 100;
            }
            tk_run($db, "UPDATE tasks SET status = ?, progress = ? WHERE id = ?", 'sii', array($status, $prog, $id));
            tk_run($db, "UPDATE tasks SET completed_at = IF(status = 'Completed', COALESCE(completed_at, NOW()), NULL) WHERE id = ?", 'i', array($id));
            tk_run($db, "INSERT INTO task_updates (task_id, user_id, status, progress, note) VALUES (?,?,?,?,?)", 'iisis', array($id, $me ? $me : null, $status, $prog, $note));
            $msg = 'Task updated.';
        }
    } elseif ($isAdmin && $do === 'delete') {
        foreach (array('task_assignees', 'task_updates') as $tb) {
            tk_run($db, "DELETE FROM $tb WHERE task_id = ?", 'i', array($id));
        }
        tk_run($db, "DELETE FROM tasks WHERE id = ?", 'i', array($id));
        $msg = 'Task deleted.';
    } elseif ($isAdmin && $do === 'save') {
        $title = trim(isset($_POST['title']) ? $_POST['title'] : '');
        $desc = trim(isset($_POST['description']) ? $_POST['description'] : '');
        $proj = (int) (isset($_POST['project_id']) ? $_POST['project_id'] : 0);
        if (!$proj) {
            $proj = null;
        }
        $prio_in = isset($_POST['priority']) ? $_POST['priority'] : '';
        $prio = isset($PRIO[$prio_in]) ? $prio_in : 'Medium';
        $st_in = isset($_POST['status']) ? $_POST['status'] : '';
        $status = isset($STATUS[$st_in]) ? $st_in : 'To Do';
        $prog = $status === 'Completed' ? 100 : max(0, min(100, (int) (isset($_POST['progress']) ? $_POST['progress'] : 0)));
        $sd = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $dd = !empty($_POST['due_date']) ? $_POST['due_date'] : null;
        $vol_raw = isset($_POST['assignees']) ? (array) $_POST['assignees'] : array();
        $vols = array_values(array_unique(array_filter(array_map('intval', $vol_raw))));
        if ($title === '') {
            $ok = false;
            $msg = 'Task title is required.';
        } elseif ($sd && $dd && $dd < $sd) {
            $ok = false;
            $msg = 'Due date cannot be before start date.';
        } else {
            if ($id > 0) {
                tk_run(
                    $db,
                    "UPDATE tasks SET title=?, description=?, project_id=?, priority=?, status=?, progress=?, start_date=?, due_date=? WHERE id=?",
                    'ssississi',
                    array($title, $desc, $proj, $prio, $status, $prog, $sd, $dd, $id)
                );
                $msg = 'Task updated.';
            } else {
                tk_run(
                    $db,
                    "INSERT INTO tasks (title, description, project_id, priority, status, progress, start_date, due_date, created_by) VALUES (?,?,?,?,?,?,?,?,?)",
                    'ssississi',
                    array($title, $desc, $proj, $prio, $status, $prog, $sd, $dd, $me ? $me : null)
                );
                $id = mysqli_insert_id($db);
                $msg = 'Task created.';
                tk_run($db, "INSERT INTO task_updates (task_id, user_id, status, progress, note) VALUES (?,?,?,?,?)", 'iisis', array($id, $me ? $me : null, $status, $prog, 'Task created'));
            }
            tk_run($db, "UPDATE tasks SET completed_at = IF(status = 'Completed', COALESCE(completed_at, NOW()), NULL) WHERE id = ?", 'i', array($id));
            tk_run($db, "DELETE FROM task_assignees WHERE task_id = ?", 'i', array($id));
            foreach ($vols as $u) {
                if (tk_rows($db, "SELECT id FROM users WHERE id = ? LIMIT 1", 'i', array($u))) {
                    tk_run($db, "INSERT IGNORE INTO task_assignees (task_id, user_id) VALUES (?, ?)", 'ii', array($id, $u));
                }
            }
        }
    }
    $_SESSION['tk_flash'] = array($ok, $msg);
    tk_go($back);
}
$flash = isset($_SESSION['tk_flash']) ? $_SESSION['tk_flash'] : null;
unset($_SESSION['tk_flash']);

/* ---------- FILTERS ---------- */
$q = trim(isset($_GET['q']) ? $_GET['q'] : '');
$g_status = isset($_GET['status']) ? $_GET['status'] : '';
$g_prio = isset($_GET['prio']) ? $_GET['prio'] : '';
$fs = isset($STATUS[$g_status]) ? $g_status : '';
$fp = isset($PRIO[$g_prio]) ? $g_prio : '';
$fj = (int) (isset($_GET['proj']) ? $_GET['proj'] : 0);
if (!$fj) {
    $fj = '';
}
$fa = '';
if ($isAdmin) {
    $fa = (int) (isset($_GET['who']) ? $_GET['who'] : 0);
    if (!$fa) {
        $fa = '';
    }
}
$od = ((isset($_GET['due']) ? $_GET['due'] : '') === 'overdue') ? 'overdue' : '';
$pg = max(1, (int) (isset($_GET['pg']) ? $_GET['pg'] : 1));
function tk_url($o = array())
{
    global $base, $q, $fs, $fp, $fj, $fa, $od;
    return $base . '&' . http_build_query(array_merge(array('q' => $q, 'status' => $fs, 'prio' => $fp, 'proj' => $fj, 'who' => $fa, 'due' => $od), $o));
}

// Scope: volunteer shudhu nijer assigned task dekhbe
$scope = 'WHERE 1=1';
$st_ = '';
$sp = array();
if (!$isAdmin) {
    $scope .= " AND t.id IN (SELECT task_id FROM task_assignees WHERE user_id = ?)";
    $st_ .= 'i';
    $sp[] = $me;
}

$w = $scope;
$t = $st_;
$p = $sp;
if ($q !== '') {
    $w .= " AND (t.title LIKE ? OR t.description LIKE ?)";
    $l = '%' . addcslashes($q, '%_\\') . '%';
    $t .= 'ss';
    $p[] = $l;
    $p[] = $l;
}
if ($fs !== '') {
    $w .= " AND t.status = ?";
    $t .= 's';
    $p[] = $fs;
}
if ($fp !== '') {
    $w .= " AND t.priority = ?";
    $t .= 's';
    $p[] = $fp;
}
if ($fj) {
    $w .= " AND t.project_id = ?";
    $t .= 'i';
    $p[] = $fj;
}
if ($fa) {
    $w .= " AND t.id IN (SELECT task_id FROM task_assignees WHERE user_id = ?)";
    $t .= 'i';
    $p[] = $fa;
}
if ($od) {
    $w .= " AND t.due_date < CURDATE() AND t.status NOT IN ('Completed','Cancelled')";
}

$total = (int) tk_first(tk_rows($db, "SELECT COUNT(*) c FROM tasks t $w", $t, $p), 'c');
$pages = max(1, (int) ceil($total / 12));
$pg = min($pg, $pages);
$tasks = tk_rows($db, "SELECT t.*, a.title project_title FROM tasks t LEFT JOIN activities a ON a.id = t.project_id $w
                       ORDER BY (t.status IN ('Completed','Cancelled')), FIELD(t.priority,'Urgent','High','Medium','Low'), t.due_date IS NULL, t.due_date, t.id DESC
                       LIMIT 12 OFFSET " . (($pg - 1) * 12), $t, $p);

// assignees + history for visible tasks
$ids = array_map('intval', array_column($tasks, 'id'));
$in = $ids ? implode(',', $ids) : '0';
$asg = array();
$hist = array();
foreach (tk_rows($db, "SELECT ta.task_id, u.id, u.member_name, u.photo FROM task_assignees ta JOIN users u ON u.id = ta.user_id WHERE ta.task_id IN ($in) ORDER BY ta.id") as $r) {
    $asg[$r['task_id']][] = array('id' => (int) $r['id'], 'name' => $r['member_name'], 'photo' => tk_photo($r['photo'], $member_dir));
}
foreach (tk_rows($db, "SELECT tu.*, u.member_name FROM task_updates tu LEFT JOIN users u ON u.id = tu.user_id WHERE tu.task_id IN ($in) ORDER BY tu.id DESC") as $r) {
    $cur = isset($hist[$r['task_id']]) ? count($hist[$r['task_id']]) : 0;
    if ($cur < 10) {
        $hist[$r['task_id']][] = array(
            'by' => $r['member_name'] ? $r['member_name'] : 'System',
            'status' => $r['status'],
            'progress' => (int) $r['progress'],
            'note' => $r['note'],
            'at' => date('d M Y, h:i A', strtotime($r['created_at']))
        );
    }
}
$today = date('Y-m-d');
foreach ($tasks as $i => $row) {
    $tasks[$i]['assignees'] = isset($asg[$row['id']]) ? $asg[$row['id']] : array();
    $tasks[$i]['history'] = isset($hist[$row['id']]) ? $hist[$row['id']] : array();
    $tasks[$i]['overdue'] = ($row['due_date'] && $row['due_date'] < $today && !in_array($row['status'], array('Completed', 'Cancelled')));
}

// stats (role scope)
$stat = array('total' => 0, 'over' => 0);
foreach ($STATUS as $k => $unused) {
    $stat[$k] = 0;
}
foreach (tk_rows($db, "SELECT t.status, COUNT(*) c FROM tasks t $scope GROUP BY t.status", $st_, $sp) as $r) {
    $stat[$r['status']] = (int) $r['c'];
    $stat['total'] += (int) $r['c'];
}
$stat['over'] = (int) tk_first(tk_rows($db, "SELECT COUNT(*) c FROM tasks t $scope AND t.due_date < CURDATE() AND t.status NOT IN ('Completed','Cancelled')", $st_, $sp), 'c');

$projects = tk_rows($db, "SELECT id, title FROM activities ORDER BY id DESC");
$VOLS = array();
if ($isAdmin) {
    foreach (tk_rows($db, "SELECT id, member_name, mobile_no, photo, status FROM users WHERE user_type = 'Volunteer Member' ORDER BY member_name") as $r) {
        $VOLS[] = array('id' => (int) $r['id'], 'name' => $r['member_name'], 'phone' => $r['mobile_no'], 'photo' => tk_photo($r['photo'], $member_dir));
    }
}
$inp = 'w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10';
$lbl = 'block text-[11px] font-bold uppercase text-slate-500 mb-1';
$csrf = '<input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '"><input type="hidden" name="back" value="' . h(tk_url(array('pg' => $pg))) . '">';
?>
<section class="page-content space-y-5 max-w-7xl mx-auto">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-800">Task Management <small
                    class="text-xs font-medium text-slate-400">/ কাজের ব্যবস্থাপনা</small></h2>
            <p class="text-xs text-slate-500 mt-0.5">
                <?php echo $isAdmin ? 'Admin view — create, assign and monitor every task.' : 'My Tasks — update your progress and status.'; ?>
            </p>
        </div>
        <?php if ($isAdmin) { ?><button type="button" onclick="tkForm()"
                class="px-4 py-3 sm:py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-emerald-600/20"><i
                    class="fa-solid fa-plus mr-1.5"></i>New Task</button>
        <?php } ?>
    </div>

    <?php if ($flash) { ?>
        <div
            class="p-3 rounded-xl text-xs font-semibold border <?php echo $flash[0] ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-rose-50 border-rose-200 text-rose-700'; ?>">
            <?php echo h($flash[1]); ?>
        </div>
    <?php } ?>

    <!-- Stats -->
    <div class="grid grid-cols-2 sm:grid-cols-4 xl:grid-cols-8 gap-3">
        <a href="<?php echo h(tk_url(array('status' => '', 'due' => '', 'pg' => 1))); ?>"
            class="p-3.5 bg-slate-900 text-white rounded-2xl">
            <p class="text-[10px] font-bold uppercase text-slate-300">Total</p>
            <p class="text-2xl font-bold">
                <?php echo $stat['total']; ?>
            </p>
        </a>
        <?php foreach ($STATUS as $k => $cls) { ?>
            <a href="<?php echo h(tk_url(array('status' => $k, 'due' => '', 'pg' => 1))); ?>"
                class="p-3.5 bg-white rounded-2xl ring-1 <?php echo $fs === $k ? 'ring-2 ring-emerald-500' : 'ring-slate-200/80'; ?> hover:shadow-md transition">
                <p class="text-[10px] font-bold uppercase text-slate-400 truncate">
                    <?php echo $k; ?>
                </p>
                <p class="text-2xl font-bold text-slate-800">
                    <?php echo $stat[$k]; ?>
                </p>
                <span class="block h-1 rounded-full mt-1 <?php echo $BAR[$k]; ?>"></span>
            </a>
        <?php } ?>
        <a href="<?php echo h(tk_url(array('status' => '', 'due' => 'overdue', 'pg' => 1))); ?>"
            class="p-3.5 bg-rose-50 rounded-2xl ring-1 <?php echo $od ? 'ring-2 ring-rose-500' : 'ring-rose-200'; ?>">
            <p class="text-[10px] font-bold uppercase text-rose-500">Overdue</p>
            <p class="text-2xl font-bold text-rose-600">
                <?php echo $stat['over']; ?>
            </p>
        </a>
    </div>

    <!-- Filters -->
    <form method="GET" action="index.php"
        class="bg-white rounded-2xl border border-slate-200/80 p-4 grid grid-cols-2 md:grid-cols-12 gap-2">
        <input type="hidden" name="page" value="<?php echo h($page); ?>">
        <input type="text" name="q" value="<?php echo h($q); ?>" placeholder="Search task... / খুঁজুন"
            class="col-span-2 md:col-span-3 <?php echo $inp; ?>">
        <select name="status" onchange="this.form.submit()" class="md:col-span-2 <?php echo $inp; ?>">
            <option value="">All Status</option>
            <?php foreach ($STATUS as $k => $unused) {
                echo '<option' . ($fs === $k ? ' selected' : '') . '>' . $k . '</option>';
            } ?>
        </select>
        <select name="prio" onchange="this.form.submit()" class="md:col-span-2 <?php echo $inp; ?>">
            <option value="">All Priority</option>
            <?php foreach ($PRIO as $k => $unused) {
                echo '<option' . ($fp === $k ? ' selected' : '') . '>' . $k . '</option>';
            } ?>
        </select>
        <select name="proj" onchange="this.form.submit()"
            class="col-span-2 md:col-span-<?php echo $isAdmin ? 2 : 4; ?> <?php echo $inp; ?>">
            <option value="">All Projects</option>
            <?php foreach ($projects as $r) {
                echo '<option value="' . (int) $r['id'] . '"' . ($fj == $r['id'] ? ' selected' : '') . '>' . h($r['title']) . '</option>';
            } ?>
        </select>
        <?php if ($isAdmin) { ?><select name="who" onchange="this.form.submit()"
                class="col-span-2 md:col-span-2 <?php echo $inp; ?>">
                <option value="">All Volunteers</option>
                <?php foreach ($VOLS as $v) {
                    echo '<option value="' . $v['id'] . '"' . ($fa == $v['id'] ? ' selected' : '') . '>' . h($v['name']) . '</option>';
                } ?>
            </select>
        <?php } ?>
        <button class="md:col-span-1 px-3 py-2.5 bg-slate-800 text-white rounded-xl text-xs font-semibold"><i
                class="fa-solid fa-magnifying-glass"></i></button>
    </form>
    <p class="text-xs text-slate-500">
        <?php echo $total; ?> tasks <a href="<?php echo h($base); ?>" class="ml-2 text-rose-600 font-semibold">Reset</a>
    </p>

    <!-- Task cards -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
        <?php foreach ($tasks as $tk) {
            $n = count($tk['assignees']); ?>
            <div
                class="bg-white rounded-2xl ring-1 <?php echo $tk['overdue'] ? 'ring-rose-300' : 'ring-slate-200/80'; ?> p-4 flex flex-col gap-3 hover:shadow-lg transition">
                <div class="flex flex-wrap gap-1.5">
                    <span
                        class="px-2.5 py-1 rounded-full text-[10px] font-bold <?php echo isset($STATUS[$tk['status']]) ? $STATUS[$tk['status']] : ''; ?>">
                        <?php echo h($tk['status']); ?>
                    </span>
                    <span
                        class="px-2.5 py-1 rounded-full text-[10px] font-bold <?php echo isset($PRIO[$tk['priority']]) ? $PRIO[$tk['priority']] : ''; ?>"><i
                            class="fa-solid fa-flag mr-1"></i>
                        <?php echo h($tk['priority']); ?>
                    </span>
                    <?php if ($tk['overdue']) { ?><span
                            class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-600 text-white">Overdue</span>
                    <?php } ?>
                </div>
                <div class="cursor-pointer" onclick="tkView(<?php echo (int) $tk['id']; ?>)">
                    <h3 class="font-bold text-sm text-slate-800 leading-snug line-clamp-2 hover:text-emerald-600">
                        <?php echo h($tk['title']); ?>
                    </h3>
                    <p class="text-[11px] text-slate-500 mt-1"><i
                            class="fa-solid fa-diagram-project text-teal-600 mr-1"></i>
                        <?php echo $tk['project_title'] ? h($tk['project_title']) : 'General task'; ?>
                    </p>
                </div>
                <div>
                    <div class="flex justify-between text-[11px] mb-1"><span
                            class="text-slate-400 font-semibold">Progress</span><b class="text-slate-700">
                            <?php echo (int) $tk['progress']; ?>%
                        </b></div>
                    <div class="h-2 rounded-full bg-slate-100 overflow-hidden">
                        <div class="h-full rounded-full <?php echo isset($BAR[$tk['status']]) ? $BAR[$tk['status']] : 'bg-slate-400'; ?>"
                            style="width:<?php echo (int) $tk['progress']; ?>%"></div>
                    </div>
                </div>
                <div class="flex items-center justify-between gap-2 mt-auto">
                    <div class="flex -space-x-2 items-center min-w-0">
                        <?php foreach (array_slice($tk['assignees'], 0, 4) as $a) {
                            echo $a['photo'] ? '<img src="' . h($a['photo']) . '" title="' . h($a['name']) . '" class="w-7 h-7 rounded-full object-cover border-2 border-white">'
                                : '<div title="' . h($a['name']) . '" class="w-7 h-7 rounded-full bg-emerald-100 text-emerald-700 border-2 border-white flex items-center justify-center text-[10px] font-bold">' . h(tk_initial($a['name'])) . '</div>';
                        } ?>
                        <?php if ($n > 4) {
                            echo '<div class="w-7 h-7 rounded-full bg-slate-800 text-white border-2 border-white flex items-center justify-center text-[10px] font-bold">+' . ($n - 4) . '</div>';
                        }
                        if (!$n) {
                            echo '<span class="text-[11px] text-slate-400">Unassigned</span>';
                        } ?>
                    </div>
                    <span
                        class="text-[11px] font-semibold whitespace-nowrap <?php echo $tk['overdue'] ? 'text-rose-600' : 'text-slate-500'; ?>"><i
                            class="fa-regular fa-calendar mr-1"></i>
                        <?php echo $tk['due_date'] ? h(date('d M Y', strtotime($tk['due_date']))) : 'No due date'; ?>
                    </span>
                </div>
                <div class="grid <?php echo $isAdmin ? 'grid-cols-3' : 'grid-cols-1'; ?> gap-2">
                    <button type="button" onclick="tkView(<?php echo (int) $tk['id']; ?>)"
                        class="py-2 rounded-xl bg-emerald-50 text-emerald-700 text-[11px] font-bold hover:bg-emerald-100"><i
                            class="fa-solid fa-<?php echo $isAdmin ? 'eye' : 'pen-to-square'; ?> mr-1"></i>
                        <?php echo $isAdmin ? 'View' : 'View & Update'; ?>
                    </button>
                    <?php if ($isAdmin) { ?>
                        <button type="button" onclick="tkForm(<?php echo (int) $tk['id']; ?>)"
                            class="py-2 rounded-xl bg-sky-50 text-sky-700 text-[11px] font-bold hover:bg-sky-100"><i
                                class="fa-solid fa-pen mr-1"></i>Edit</button>
                        <form method="POST" action="" onsubmit="return confirm('Delete this task? / টাস্কটি মুছে ফেলবেন?')">
                            <?php echo $csrf; ?><input type="hidden" name="do" value="delete"><input type="hidden" name="id"
                                value="<?php echo (int) $tk['id']; ?>">
                            <button
                                class="w-full py-2 rounded-xl bg-rose-50 text-rose-600 text-[11px] font-bold hover:bg-rose-100"><i
                                    class="fa-solid fa-trash mr-1"></i>Delete</button>
                        </form>
                    <?php } ?>
                </div>
            </div>
        <?php }
        if (!$tasks) {
            echo '<p class="col-span-full py-14 text-center text-slate-400 text-sm"><i class="fa-solid fa-list-check text-4xl mb-3 block"></i>' . ($isAdmin ? 'No tasks found. Click “New Task”.' : 'No task assigned to you yet.') . '</p>';
        } ?>
    </div>

    <?php if ($pages > 1) { ?>
        <div class="flex items-center justify-between"><span class="text-xs text-slate-500">Page
                <?php echo $pg; ?> /
                <?php echo $pages; ?>
            </span>
            <div class="flex gap-2">
                <?php if ($pg > 1) { ?><a href="<?php echo h(tk_url(array('pg' => $pg - 1))); ?>"
                        class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-xs font-semibold">‹ Prev</a>
                <?php } ?>
                <?php if ($pg < $pages) { ?><a href="<?php echo h(tk_url(array('pg' => $pg + 1))); ?>"
                        class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-xs font-semibold">Next ›</a>
                <?php } ?>
            </div>
        </div>
    <?php } ?>
</section>

<!-- VIEW + UPDATE MODAL -->
<div id="tkViewModal" class="fixed inset-0 bg-slate-900/60 hidden items-end sm:items-center justify-center sm:p-4 z-50">
    <div
        class="bg-white w-full sm:max-w-2xl max-h-[94vh] flex flex-col rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-4 bg-slate-900 text-white flex justify-between items-center shrink-0">
            <h3 class="font-bold text-sm"><i class="fa-solid fa-list-check text-emerald-400 mr-2"></i>Task Details</h3>
            <button type="button" onclick="tkModal('tkViewModal', false)" class="text-slate-400 hover:text-white p-1"><i
                    class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <div class="overflow-y-auto p-4 sm:p-6 space-y-5">
            <div id="tkViewBody" class="space-y-4"></div>
            <form method="POST" action="" class="rounded-2xl border border-emerald-200 bg-emerald-50/40 p-4 space-y-3">
                <?php echo $csrf; ?><input type="hidden" name="do" value="update"><input type="hidden" name="id"
                    id="u_id">
                <p class="text-xs font-bold text-emerald-800"><i class="fa-solid fa-pen-to-square mr-1.5"></i>Update
                    Progress <small class="font-normal">/ অগ্রগতি আপডেট</small></p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div><label class="<?php echo $lbl; ?>">Status</label><select name="status" id="u_status"
                            class="<?php echo $inp; ?>">
                            <?php foreach ($STATUS as $k => $unused) {
                                if ($k === 'Cancelled' && !$isAdmin) {
                                    continue;
                                }
                                echo '<option>' . $k . '</option>';
                            } ?>
                        </select></div>
                    <div><label class="<?php echo $lbl; ?>">Progress: <b id="u_pv">0%</b></label><input type="range"
                            name="progress" id="u_prog" min="0" max="100" step="5"
                            class="w-full accent-emerald-600 mt-2"></div>
                </div>
                <div><label class="<?php echo $lbl; ?>">Note <small class="normal-case">/ মন্তব্য</small></label><input
                        type="text" name="note" maxlength="500" class="<?php echo $inp; ?>"
                        placeholder="What did you do? Any problem?"></div>
                <button
                    class="w-full sm:w-auto px-6 py-3 sm:py-2.5 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700">Save
                    Update</button>
            </form>
        </div>
    </div>
</div>

<?php if ($isAdmin) { ?>
    <!-- CREATE / EDIT (admin) -->
    <div id="tkFormModal" class="fixed inset-0 bg-slate-900/60 hidden items-end sm:items-center justify-center sm:p-4 z-50">
        <div
            class="bg-white w-full sm:max-w-3xl max-h-[94vh] flex flex-col rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden">
            <div class="p-4 bg-slate-900 text-white flex justify-between items-center shrink-0">
                <h3 class="font-bold text-sm"><i class="fa-solid fa-square-plus text-emerald-400 mr-2"></i><span
                        id="tkFormTitle">New Task</span></h3>
                <button type="button" onclick="tkModal('tkFormModal', false)" class="text-slate-400 hover:text-white p-1"><i
                        class="fa-solid fa-xmark text-lg"></i></button>
            </div>
            <form method="POST" action="" class="p-4 sm:p-6 space-y-4 overflow-y-auto" autocomplete="off">
                <?php echo $csrf; ?><input type="hidden" name="do" value="save"><input type="hidden" name="id" id="f_id"
                    value="0">
                <div><label class="<?php echo $lbl; ?>">Task Title * <small class="normal-case">/
                            শিরোনাম</small></label><input type="text" name="title" id="f_title" required
                        class="<?php echo $inp; ?>" placeholder="e.g. Distribute relief packages in Ward 5"></div>
                <div><label class="<?php echo $lbl; ?>">Description <small class="normal-case">/
                            বিবরণ</small></label><textarea name="description" id="f_description" rows="3"
                        class="<?php echo $inp; ?>" placeholder="Goal, steps, instructions..."></textarea></div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <div class="col-span-2 sm:col-span-3"><label class="<?php echo $lbl; ?>">Project / কার্যক্রম <small
                                class="normal-case">(optional)</small></label>
                        <select name="project_id" id="f_project_id" class="<?php echo $inp; ?>">
                            <option value="">— General task (no project) —</option>
                            <?php foreach ($projects as $r) {
                                echo '<option value="' . (int) $r['id'] . '">' . h($r['title']) . '</option>';
                            } ?>
                        </select>
                    </div>
                    <div><label class="<?php echo $lbl; ?>">Priority</label><select name="priority" id="f_priority"
                            class="<?php echo $inp; ?>">
                            <?php foreach ($PRIO as $k => $unused) {
                                echo '<option' . ($k === 'Medium' ? ' selected' : '') . '>' . $k . '</option>';
                            } ?>
                        </select></div>
                    <div><label class="<?php echo $lbl; ?>">Status</label><select name="status" id="f_status"
                            class="<?php echo $inp; ?>">
                            <?php foreach ($STATUS as $k => $unused) {
                                echo '<option>' . $k . '</option>';
                            } ?>
                        </select></div>
                    <div class="col-span-2 sm:col-span-1"><label class="<?php echo $lbl; ?>">Progress: <b
                                id="f_pv">0%</b></label><input type="range" name="progress" id="f_progress" min="0"
                            max="100" step="5" value="0" class="w-full accent-emerald-600 mt-2"></div>
                    <div><label class="<?php echo $lbl; ?>">Start Date</label><input type="date" name="start_date"
                            id="f_start_date" class="<?php echo $inp; ?>"></div>
                    <div><label class="<?php echo $lbl; ?>">Due Date</label><input type="date" name="due_date"
                            id="f_due_date" class="<?php echo $inp; ?>"></div>
                </div>
                <div class="space-y-2">
                    <div class="flex items-center justify-between"><label class="<?php echo $lbl; ?> mb-0">Assign Volunteers
                            <small class="normal-case">/ দায়িত্ব দিন</small></label><span id="f_cnt"
                            class="text-[11px] font-bold px-2.5 py-1 rounded-full bg-teal-50 text-teal-700">0
                            selected</span></div>
                    <div class="flex gap-2"><input type="text" id="f_vsearch" placeholder="Search volunteer..."
                            class="<?php echo $inp; ?>">
                        <button type="button" id="f_all"
                            class="px-3 rounded-xl bg-emerald-50 text-emerald-700 text-[11px] font-bold shrink-0">All</button>
                        <button type="button" id="f_none"
                            class="px-3 rounded-xl bg-rose-50 text-rose-600 text-[11px] font-bold shrink-0">Clear</button>
                    </div>
                    <div id="f_vlist"
                        class="max-h-60 overflow-y-auto rounded-xl border border-slate-200 divide-y divide-slate-100"></div>
                    <p class="text-[11px] text-slate-400">
                        <?php echo count($VOLS); ?> volunteers available. Select one for a single assignee or many for a
                        team task.
                    </p>
                </div>
                <div class="flex gap-2 pt-1">
                    <button type="button" onclick="tkModal('tkFormModal', false)"
                        class="flex-1 sm:flex-none px-5 py-3 sm:py-2.5 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600">Cancel</button>
                    <button
                        class="flex-1 sm:flex-none sm:ml-auto px-6 py-3 sm:py-2.5 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700">Save
                        Task</button>
                </div>
            </form>
        </div>
    </div>
<?php } ?>

<script>
    (function () {
        var TASKS = <?php echo tk_js($tasks, '[]'); ?>, VOLS = <?php echo tk_js($VOLS, '[]'); ?>;
        var ST = <?php echo tk_js($STATUS, '{}'); ?>, PR = <?php echo tk_js($PRIO, '{}'); ?>, ADMIN = <?php echo $isAdmin ? 'true' : 'false'; ?>, $ = function (id) { return document.getElementById(id); };
        function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
        function find(id) { return TASKS.filter(function (x) { return +x.id === +id; })[0]; }
        function fd(s) { if (!s) { return '—'; } var d = new Date(s + 'T00:00:00'); return d.toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }); }
        function av(a, c) { c = c || 'w-9 h-9'; return a.photo ? '<img src="' + esc(a.photo) + '" class="' + c + ' rounded-full object-cover shrink-0">' : '<div class="' + c + ' rounded-full bg-emerald-100 text-emerald-700 flex items-center justify-center font-bold text-xs shrink-0">' + esc((a.name || '?').charAt(0).toUpperCase()) + '</div>'; }
        window.tkModal = function (id, on) { var m = $(id); m.classList.toggle('hidden', !on); m.classList.toggle('flex', on); document.body.style.overflow = on ? 'hidden' : ''; };

        /* ---------- View + update ---------- */
        window.tkView = function (id) {
            var t = find(id), cell = function (l, v) { return '<div class="p-3 rounded-xl bg-slate-50 border border-slate-100"><p class="text-[10px] font-bold uppercase text-slate-400">' + l + '</p><p class="text-xs font-semibold text-slate-800 mt-0.5 break-words">' + (v || '—') + '</p></div>'; };
            var team = t.assignees.length ? t.assignees.map(function (a) { return '<div class="flex items-center gap-2.5 p-2.5 rounded-xl border border-slate-100 bg-white">' + av(a) + '<p class="text-xs font-semibold text-slate-800 truncate">' + esc(a.name) + '</p></div>'; }).join('') : '<p class="text-xs text-slate-400">No volunteer assigned.</p>';
            var hist = t.history.length ? t.history.map(function (h) {
                return '<div class="flex gap-3 text-xs"><span class="w-2 h-2 mt-1.5 rounded-full bg-emerald-500 shrink-0"></span><div><p class="font-semibold text-slate-700">' + esc(h.by) + ' <span class="px-1.5 py-0.5 rounded text-[10px] ' + (ST[h.status] || '') + '">' + esc(h.status) + '</span> <b>' + h.progress + '%</b></p>' +
                    (h.note ? '<p class="text-slate-500">' + esc(h.note) + '</p>' : '') + '<p class="text-[10px] text-slate-400">' + esc(h.at) + '</p></div></div>';
            }).join('') : '<p class="text-xs text-slate-400">No updates yet.</p>';
            $('tkViewBody').innerHTML =
                '<div><div class="flex flex-wrap gap-1.5 mb-2"><span class="px-2.5 py-1 rounded-full text-[10px] font-bold ' + (ST[t.status] || '') + '">' + esc(t.status) + '</span><span class="px-2.5 py-1 rounded-full text-[10px] font-bold ' + (PR[t.priority] || '') + '">' + esc(t.priority) + '</span>' + (t.overdue ? '<span class="px-2.5 py-1 rounded-full text-[10px] font-bold bg-rose-600 text-white">Overdue</span>' : '') + '</div>' +
                '<h3 class="text-lg font-bold text-slate-800 leading-snug">' + esc(t.title) + '</h3></div>' +
                (t.description ? '<p class="text-xs text-slate-600 leading-relaxed whitespace-pre-line">' + esc(t.description) + '</p>' : '') +
                '<div class="grid grid-cols-2 sm:grid-cols-3 gap-2">' + cell('Project', esc(t.project_title || 'General task')) + cell('Start', fd(t.start_date)) + cell('Due', fd(t.due_date)) + '</div>' +
                '<div><div class="flex justify-between text-[11px] mb-1"><span class="font-bold text-slate-500 uppercase">Progress</span><b>' + t.progress + '%</b></div><div class="h-2.5 rounded-full bg-slate-100 overflow-hidden"><div class="h-full rounded-full bg-emerald-500" style="width:' + t.progress + '%"></div></div></div>' +
                '<div><h4 class="text-xs font-bold text-slate-800 mb-2"><i class="fa-solid fa-user-group text-teal-600 mr-1.5"></i>Assigned (' + t.assignees.length + ')</h4><div class="grid grid-cols-1 sm:grid-cols-2 gap-2">' + team + '</div></div>' +
                '<div><h4 class="text-xs font-bold text-slate-800 mb-2"><i class="fa-solid fa-clock-rotate-left text-sky-600 mr-1.5"></i>Update History</h4><div class="space-y-2.5">' + hist + '</div></div>';
            $('u_id').value = t.id; $('u_status').value = t.status; $('u_prog').value = t.progress; $('u_pv').textContent = t.progress + '%';
            tkModal('tkViewModal', true);
        };
        $('u_prog').addEventListener('input', function () { $('u_pv').textContent = this.value + '%'; });
        $('u_status').addEventListener('change', function () { if (this.value === 'Completed') { $('u_prog').value = 100; $('u_pv').textContent = '100%'; } });

        if (!ADMIN) { return; }

        /* ---------- Admin form + volunteer picker ---------- */
        var selected = new Set();
        function count() { $('f_cnt').textContent = selected.size + ' selected'; }
        function renderVols() {
            $('f_vlist').innerHTML = VOLS.map(function (v) {
                return '<label data-n="' + esc((v.name + ' ' + v.phone).toLowerCase()) + '" class="flex items-center gap-3 p-2.5 cursor-pointer hover:bg-slate-50"><input type="checkbox" name="assignees[]" value="' + v.id + '" class="rounded border-slate-300 w-4 h-4"' + (selected.has(v.id) ? ' checked' : '') + '>' + av(v) +
                    '<div class="min-w-0"><p class="text-xs font-semibold text-slate-800 truncate">' + esc(v.name) + '</p><p class="text-[10px] text-slate-400">' + esc(v.phone || '') + '</p></div></label>';
            }).join('') || '<p class="p-4 text-center text-xs text-slate-400">No volunteer found. Set a member\'s type to “Volunteer Member”.</p>';
            count();
        }
        $('f_vlist').addEventListener('change', function (e) { var id = +e.target.value; e.target.checked ? selected.add(id) : selected.delete(id); count(); });
        $('f_vsearch').addEventListener('input', function () { var q = this.value.trim().toLowerCase(); document.querySelectorAll('#f_vlist label').forEach(function (l) { l.style.display = l.dataset.n.indexOf(q) === -1 ? 'none' : ''; }); });
        function bulk(on) { document.querySelectorAll('#f_vlist label').forEach(function (l) { if (l.style.display === 'none') { return; } var c = l.querySelector('input'); c.checked = on; on ? selected.add(+c.value) : selected.delete(+c.value); }); count(); }
        $('f_all').addEventListener('click', function () { bulk(true); }); $('f_none').addEventListener('click', function () { bulk(false); });
        $('f_progress').addEventListener('input', function () { $('f_pv').textContent = this.value + '%'; });
        $('f_status').addEventListener('change', function () { if (this.value === 'Completed') { $('f_progress').value = 100; $('f_pv').textContent = '100%'; } });

        window.tkForm = function (id) {
            var t = id ? find(id) : null; selected = new Set(t ? t.assignees.map(function (a) { return +a.id; }) : []);
            $('f_id').value = t ? t.id : 0; $('tkFormTitle').textContent = t ? 'Edit Task' : 'New Task'; $('f_vsearch').value = '';
            $('f_title').value = t ? t.title : ''; $('f_description').value = t ? (t.description || '') : '';
            $('f_project_id').value = t && t.project_id ? t.project_id : ''; $('f_priority').value = t ? t.priority : 'Medium'; $('f_status').value = t ? t.status : 'To Do';
            $('f_progress').value = t ? t.progress : 0; $('f_pv').textContent = (t ? t.progress : 0) + '%';
            $('f_start_date').value = t ? (t.start_date || '') : ''; $('f_due_date').value = t ? (t.due_date || '') : '';
            renderVols(); tkModal('tkFormModal', true);
        };
        ['tkFormModal', 'tkViewModal'].forEach(function (id) { $(id).addEventListener('mousedown', function (e) { if (e.target === this) { tkModal(id, false); } }); });
    })();
</script>