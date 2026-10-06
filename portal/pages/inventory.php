<?php
/* ==========================================================
   inventory_module.php  ->  index.php?page=inventory
   Foundation Inventory: Category, Item (image/price/stock), Stock In/Out,
   Edit / Delete, Low-stock alert. Table gulo auto-create hoy.
   ========================================================== */
if (session_status() === PHP_SESSION_NONE) { @session_start(); }
if (!function_exists('h')) { function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); } }
mysqli_set_charset($db, 'utf8mb4');

mysqli_query($db, "CREATE TABLE IF NOT EXISTS inventory_categories (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100) NOT NULL UNIQUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
mysqli_query($db, "CREATE TABLE IF NOT EXISTS inventory_items (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  category_id INT UNSIGNED NOT NULL,
  name VARCHAR(200) NOT NULL,
  sku VARCHAR(40) NOT NULL DEFAULT '',
  description TEXT NULL,
  image VARCHAR(255) NOT NULL DEFAULT '',
  unit VARCHAR(30) NOT NULL DEFAULT 'pcs',
  quantity DECIMAL(12,2) NOT NULL DEFAULT 0,
  min_stock DECIMAL(12,2) NOT NULL DEFAULT 0,
  unit_price DECIMAL(12,2) NOT NULL DEFAULT 0,
  location VARCHAR(150) NOT NULL DEFAULT '',
  supplier VARCHAR(150) NOT NULL DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  KEY idx_cat (category_id), KEY idx_name (name)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
mysqli_query($db, "CREATE TABLE IF NOT EXISTS inventory_movements (
  id INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
  item_id INT UNSIGNED NOT NULL,
  type ENUM('IN','OUT') NOT NULL,
  qty DECIMAL(12,2) NOT NULL,
  note VARCHAR(255) NOT NULL DEFAULT '',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  KEY idx_item (item_id)) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
if (!(int)mysqli_fetch_row(mysqli_query($db, "SELECT COUNT(*) FROM inventory_categories"))[0]) {
    foreach (['Food & Relief', 'Clothing & Blankets', 'Medical Supplies', 'Education Materials', 'Office Supplies', 'Equipment & Tools', 'Other'] as $c) {
        mysqli_query($db, "INSERT IGNORE INTO inventory_categories (name) VALUES ('" . mysqli_real_escape_string($db, $c) . "')");
    }
}
if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(16)); }

$img_dir = 'uploads/inventory/';
$base = 'index.php?page=inventory';

function inv_rows($db, $sql, $t = '', $p = []) {
    $st = mysqli_prepare($db, $sql);
    if (!$st) { return []; }
    if ($t !== '') { mysqli_stmt_bind_param($st, $t, ...$p); }
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st); $o = [];
    while ($res && $r = mysqli_fetch_assoc($res)) { $o[] = $r; }
    mysqli_stmt_close($st);
    return $o;
}
function inv_run($db, $sql, $t, $p) { $st = mysqli_prepare($db, $sql); mysqli_stmt_bind_param($st, $t, ...$p); $ok = mysqli_stmt_execute($st); $n = mysqli_stmt_affected_rows($st); mysqli_stmt_close($st); return $ok ? $n : -1; }
function inv_money($n) { return '৳' . number_format((float)$n, 2); }
function inv_qty($n) { return rtrim(rtrim(number_format((float)$n, 2, '.', ''), '0'), '.'); }
function inv_unlink($dir, $f) { if ($f !== '' && is_file($dir . basename($f))) { @unlink($dir . basename($f)); } }
function inv_upload($f, $dir, &$err) {
    if (!is_array($f) || $f['error'] === UPLOAD_ERR_NO_FILE) { return ''; }
    if ($f['error'] !== UPLOAD_ERR_OK) { $err = 'Image upload failed.'; return false; }
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp']) || !@getimagesize($f['tmp_name'])) { $err = 'Image must be JPG, PNG or WEBP.'; return false; }
    if ($f['size'] > 3 * 1024 * 1024) { $err = 'Image must be under 3MB.'; return false; }
    if (!is_dir($dir)) { mkdir($dir, 0755, true); }
    $name = 'inv_' . time() . '_' . random_int(1000, 9999) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], $dir . $name)) { $err = 'Could not save image (check folder permission).'; return false; }
    return $name;
}

/* ---------- ACTIONS ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && hash_equals($_SESSION['csrf'], $_POST['csrf'] ?? '')) {
    $do = $_POST['do'] ?? ''; $ok = true; $msg = '';
    $back = (strpos($_POST['back'] ?? '', 'index.php?page=inventory') === 0) ? $_POST['back'] : $base;

    if ($do === 'cat_add') {
        $n = trim($_POST['name'] ?? '');
        if ($n === '') { $ok = false; $msg = 'Category name required.'; }
        else { inv_run($db, "INSERT IGNORE INTO inventory_categories (name) VALUES (?)", 's', [$n]); $msg = 'Category saved.'; }
    } elseif ($do === 'cat_del') {
        $id = (int)$_POST['id'];
        if ((int)(inv_rows($db, "SELECT COUNT(*) c FROM inventory_items WHERE category_id = ?", 'i', [$id])[0]['c'] ?? 0) > 0) { $ok = false; $msg = 'Category has items. Move or delete them first.'; }
        else { inv_run($db, "DELETE FROM inventory_categories WHERE id = ?", 'i', [$id]); $msg = 'Category deleted.'; }
    } elseif ($do === 'del') {
        $id = (int)$_POST['id'];
        $o = inv_rows($db, "SELECT image FROM inventory_items WHERE id = ?", 'i', [$id]);
        if ($o) { inv_run($db, "DELETE FROM inventory_items WHERE id = ?", 'i', [$id]); inv_run($db, "DELETE FROM inventory_movements WHERE item_id = ?", 'i', [$id]); inv_unlink($img_dir, $o[0]['image']); $msg = 'Item deleted.'; }
    } elseif ($do === 'stock') {
        $id = (int)$_POST['id']; $qty = (float)($_POST['qty'] ?? 0); $type = ($_POST['type'] ?? '') === 'OUT' ? 'OUT' : 'IN'; $note = trim($_POST['note'] ?? '');
        if ($qty <= 0) { $ok = false; $msg = 'Enter a valid quantity.'; }
        else {
            $n = $type === 'IN' ? inv_run($db, "UPDATE inventory_items SET quantity = quantity + ? WHERE id = ?", 'di', [$qty, $id])
                                : inv_run($db, "UPDATE inventory_items SET quantity = quantity - ? WHERE id = ? AND quantity >= ?", 'did', [$qty, $id, $qty]);
            if ($n > 0) { inv_run($db, "INSERT INTO inventory_movements (item_id, type, qty, note) VALUES (?,?,?,?)", 'isds', [$id, $type, $qty, $note]); $msg = 'Stock updated.'; }
            else { $ok = false; $msg = $type === 'OUT' ? 'Not enough stock.' : 'Item not found.'; }
        }
    } elseif ($do === 'save') {
        $id = (int)($_POST['id'] ?? 0); $err = '';
        $cat = (int)($_POST['category_id'] ?? 0); $name = trim($_POST['name'] ?? ''); $sku = trim($_POST['sku'] ?? '');
        $desc = trim($_POST['description'] ?? ''); $unit = trim($_POST['unit'] ?? '') ?: 'pcs';
        $qty = max(0, (float)($_POST['quantity'] ?? 0)); $min = max(0, (float)($_POST['min_stock'] ?? 0)); $price = max(0, (float)($_POST['unit_price'] ?? 0));
        $loc = trim($_POST['location'] ?? ''); $sup = trim($_POST['supplier'] ?? '');
        if ($name === '' || $cat <= 0) { $ok = false; $msg = 'Item name and category are required.'; }
        else {
            $new = inv_upload($_FILES['image'] ?? null, $img_dir, $err);
            if ($new === false) { $ok = false; $msg = $err; }
            elseif ($id > 0) {
                $old = inv_rows($db, "SELECT image, quantity FROM inventory_items WHERE id = ?", 'i', [$id]);
                $img = $new !== '' ? $new : (!empty($_POST['remove_image']) ? '' : ($old[0]['image'] ?? ''));
                inv_run($db, "UPDATE inventory_items SET category_id=?, name=?, sku=?, description=?, image=?, unit=?, quantity=?, min_stock=?, unit_price=?, location=?, supplier=? WHERE id=?",
                    'isssssdddssi', [$cat, $name, $sku, $desc, $img, $unit, $qty, $min, $price, $loc, $sup, $id]);
                if ($old && $img !== $old[0]['image']) { inv_unlink($img_dir, $old[0]['image']); }
                if ($old && abs((float)$old[0]['quantity'] - $qty) > 0.001) { inv_run($db, "INSERT INTO inventory_movements (item_id, type, qty, note) VALUES (?,?,?,?)", 'isds', [$id, $qty > $old[0]['quantity'] ? 'IN' : 'OUT', abs($qty - $old[0]['quantity']), 'Manual edit']); }
                $msg = 'Item updated.';
            } else {
                inv_run($db, "INSERT INTO inventory_items (category_id, name, sku, description, image, unit, quantity, min_stock, unit_price, location, supplier) VALUES (?,?,?,?,?,?,?,?,?,?,?)",
                    'isssssdddss', [$cat, $name, $sku, $desc, $new, $unit, $qty, $min, $price, $loc, $sup]);
                $nid = mysqli_insert_id($db);
                inv_run($db, "UPDATE inventory_items SET sku = IF(sku = '', CONCAT('INV-', LPAD(id, 5, '0')), sku) WHERE id = ?", 'i', [$nid]);
                if ($qty > 0) { inv_run($db, "INSERT INTO inventory_movements (item_id, type, qty, note) VALUES (?,?,?,?)", 'isds', [$nid, 'IN', $qty, 'Opening stock']); }
                $msg = 'Item added.';
            }
        }
    }
    $_SESSION['inv_flash'] = [$ok, $msg];
    header('Location: ' . $back); exit;
}
$flash = $_SESSION['inv_flash'] ?? null; unset($_SESSION['inv_flash']);

/* ---------- FILTERS ---------- */
$q = trim($_GET['q'] ?? ''); $cat = (int)($_GET['cat'] ?? 0);
$stock = in_array($_GET['stock'] ?? '', ['in', 'low', 'out']) ? $_GET['stock'] : '';
$pg = max(1, (int)($_GET['pg'] ?? 1));
function inv_url($o = []) { global $q, $cat, $stock; return 'index.php?' . http_build_query(array_merge(['page' => 'inventory', 'q' => $q, 'cat' => $cat ?: '', 'stock' => $stock], $o)); }

$w = 'WHERE 1=1'; $t = ''; $p = [];
if ($q !== '')  { $w .= " AND (i.name LIKE ? OR i.sku LIKE ? OR i.supplier LIKE ? OR i.location LIKE ?)"; $l = '%' . addcslashes($q, '%_\\') . '%'; $t .= 'ssss'; array_push($p, $l, $l, $l, $l); }
if ($cat)       { $w .= " AND i.category_id = ?"; $t .= 'i'; $p[] = $cat; }
if ($stock === 'out') { $w .= " AND i.quantity <= 0"; }
if ($stock === 'low') { $w .= " AND i.quantity > 0 AND i.quantity <= i.min_stock"; }
if ($stock === 'in')  { $w .= " AND i.quantity > i.min_stock"; }
$total = (int)(inv_rows($db, "SELECT COUNT(*) c FROM inventory_items i $w", $t, $p)[0]['c'] ?? 0);
$pages = max(1, (int)ceil($total / 12)); $pg = min($pg, $pages);
$items = inv_rows($db, "SELECT i.*, c.name cat_name FROM inventory_items i LEFT JOIN inventory_categories c ON c.id = i.category_id $w ORDER BY i.id DESC LIMIT 12 OFFSET " . (($pg - 1) * 12), $t, $p);
foreach ($items as &$it) { $it['image_url'] = ($it['image'] !== '' && is_file($img_dir . basename($it['image']))) ? $img_dir . $it['image'] : ''; } unset($it);

$S = inv_rows($db, "SELECT COUNT(*) n, COALESCE(SUM(quantity*unit_price),0) val, COALESCE(SUM(quantity<=0),0) outc, COALESCE(SUM(quantity>0 AND quantity<=min_stock),0) lowc FROM inventory_items")[0];
$cats = inv_rows($db, "SELECT c.id, c.name, COUNT(i.id) n FROM inventory_categories c LEFT JOIN inventory_items i ON i.category_id = c.id GROUP BY c.id, c.name ORDER BY c.name");
$moves = inv_rows($db, "SELECT m.*, i.name, i.unit FROM inventory_movements m JOIN inventory_items i ON i.id = m.item_id ORDER BY m.id DESC LIMIT 8");
$units = ['pcs', 'kg', 'liter', 'box', 'packet', 'set', 'bag', 'bottle', 'meter'];
$palette = ['bg-emerald-50 text-emerald-700', 'bg-sky-50 text-sky-700', 'bg-rose-50 text-rose-700', 'bg-violet-50 text-violet-700', 'bg-amber-50 text-amber-700', 'bg-teal-50 text-teal-700', 'bg-orange-50 text-orange-700'];
$inp = 'w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-emerald-500 focus:ring-4 focus:ring-emerald-500/10';
$lbl = 'block text-[11px] font-bold uppercase text-slate-500 mb-1';
$csrf = '<input type="hidden" name="csrf" value="' . h($_SESSION['csrf']) . '"><input type="hidden" name="back" value="' . h(inv_url(['pg' => $pg])) . '">';
?>
<section class="page-content space-y-5 max-w-7xl mx-auto">

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
        <div>
            <h2 class="text-xl sm:text-2xl font-bold text-slate-800">Inventory <small class="text-xs font-medium text-slate-400">/ মালামাল ভান্ডার</small></h2>
            <p class="text-xs text-slate-500 mt-0.5">Track relief goods, supplies and equipment with stock and value.</p>
        </div>
        <div class="grid grid-cols-2 sm:flex gap-2">
            <button type="button" onclick="invModal('invCat', true)" class="px-4 py-3 sm:py-2.5 border border-slate-200 bg-white rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50"><i class="fa-solid fa-tags mr-1.5"></i>Categories</button>
            <button type="button" onclick="invForm()" class="px-4 py-3 sm:py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-emerald-600/20"><i class="fa-solid fa-plus mr-1.5"></i>Add Item</button>
        </div>
    </div>

    <?php if ($flash) { ?><div class="p-3 rounded-xl text-xs font-semibold border <?= $flash[0] ? 'bg-emerald-50 border-emerald-200 text-emerald-700' : 'bg-rose-50 border-rose-200 text-rose-700' ?>"><?= h($flash[1]) ?></div><?php } ?>

    <!-- Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
        <?php foreach ([['Total Items', 'মোট আইটেম', (int)$S['n'], 'emerald', 'fa-boxes-stacked', ''], ['Stock Value', 'মোট মূল্য', inv_money($S['val']), 'sky', 'fa-sack-dollar', ''],
                        ['Low Stock', 'কম স্টক', (int)$S['lowc'], 'amber', 'fa-triangle-exclamation', 'low'], ['Out of Stock', 'স্টক শেষ', (int)$S['outc'], 'rose', 'fa-ban', 'out']] as $c) { ?>
        <a href="<?= $c[5] ? h(inv_url(['stock' => $c[5], 'pg' => 1])) : h($base) ?>" class="p-4 sm:p-5 bg-white rounded-2xl ring-1 ring-slate-200/80 flex items-center gap-3 hover:shadow-md transition">
            <div class="w-11 h-11 rounded-xl bg-<?= $c[3] ?>-50 text-<?= $c[3] ?>-600 flex items-center justify-center text-lg shrink-0"><i class="fa-solid <?= $c[4] ?>"></i></div>
            <div class="min-w-0"><p class="text-[10px] font-bold uppercase text-slate-400 truncate"><?= $c[0] ?> <span class="normal-case font-medium">/ <?= $c[1] ?></span></p><p class="text-lg sm:text-xl font-bold text-slate-800 truncate"><?= $c[2] ?></p></div>
        </a>
        <?php } ?>
    </div>

    <!-- Category chips -->
    <div class="flex gap-2 overflow-x-auto pb-1">
        <a href="<?= h(inv_url(['cat' => '', 'pg' => 1])) ?>" class="shrink-0 px-3.5 py-2 rounded-xl text-xs font-bold <?= !$cat ? 'bg-slate-900 text-white' : 'bg-white ring-1 ring-slate-200 text-slate-600 hover:bg-slate-50' ?>">All</a>
        <?php foreach ($cats as $c) { ?>
        <a href="<?= h(inv_url(['cat' => $c['id'], 'pg' => 1])) ?>" class="shrink-0 px-3.5 py-2 rounded-xl text-xs font-bold <?= $cat == $c['id'] ? 'bg-slate-900 text-white' : 'bg-white ring-1 ring-slate-200 text-slate-600 hover:bg-slate-50' ?>"><?= h($c['name']) ?> <span class="opacity-60"><?= (int)$c['n'] ?></span></a>
        <?php } ?>
    </div>

    <!-- Filter -->
    <form method="GET" action="index.php" class="bg-white rounded-2xl border border-slate-200/80 p-4 grid grid-cols-2 md:grid-cols-12 gap-2">
        <input type="hidden" name="page" value="inventory"><input type="hidden" name="cat" value="<?= $cat ?: '' ?>">
        <input type="text" name="q" value="<?= h($q) ?>" placeholder="Search item, SKU, supplier, location... / খুঁজুন" class="col-span-2 md:col-span-7 <?= $inp ?>">
        <select name="stock" onchange="this.form.submit()" class="col-span-2 md:col-span-3 <?= $inp ?>">
            <option value="">All Stock Status</option>
            <?php foreach (['in' => 'In Stock', 'low' => 'Low Stock', 'out' => 'Out of Stock'] as $k => $l) { echo '<option value="' . $k . '"' . ($stock === $k ? ' selected' : '') . '>' . $l . '</option>'; } ?>
        </select>
        <button class="md:col-span-1 px-3 py-2.5 bg-slate-800 text-white rounded-xl text-xs font-semibold"><i class="fa-solid fa-magnifying-glass"></i></button>
        <a href="<?= h($base) ?>" class="md:col-span-1 px-3 py-2.5 text-center border border-rose-200 text-rose-600 bg-rose-50 rounded-xl text-xs font-semibold" title="Reset"><i class="fa-solid fa-rotate-left"></i></a>
    </form>

    <p class="text-xs text-slate-500"><?= $total ?> items</p>

    <!-- Item cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <?php foreach ($items as $it) {
            $qn = (float)$it['quantity']; $mn = (float)$it['min_stock'];
            $st = $qn <= 0 ? ['Out of stock', 'bg-rose-100 text-rose-700', 'bg-rose-500'] : ($qn <= $mn ? ['Low stock', 'bg-amber-100 text-amber-700', 'bg-amber-500'] : ['In stock', 'bg-emerald-100 text-emerald-700', 'bg-emerald-500']);
            $pct = $mn > 0 ? min(100, round($qn / ($mn * 3) * 100)) : ($qn > 0 ? 100 : 0); ?>
        <div class="bg-white rounded-2xl ring-1 ring-slate-200/80 overflow-hidden flex flex-col hover:shadow-lg transition">
            <div class="relative h-40 bg-slate-100 cursor-pointer" onclick="invView(<?= (int)$it['id'] ?>)">
                <?php if ($it['image_url']) { ?><img src="<?= h($it['image_url']) ?>" loading="lazy" class="w-full h-full object-cover"><?php }
                else { ?><div class="w-full h-full bg-gradient-to-br from-emerald-500 via-teal-600 to-slate-800 flex items-center justify-center text-white/80 text-4xl"><i class="fa-solid fa-box-open"></i></div><?php } ?>
                <span class="absolute top-3 left-3 px-2.5 py-1 rounded-full text-[10px] font-bold <?= $st[1] ?>"><?= $st[0] ?></span>
                <span class="absolute top-3 right-3 px-2.5 py-1 rounded-full text-[10px] font-semibold bg-slate-900/70 text-white backdrop-blur"><?= h($it['cat_name']) ?></span>
            </div>
            <div class="p-4 flex-1 flex flex-col gap-3">
                <div><h3 class="font-bold text-sm text-slate-800 leading-snug line-clamp-2"><?= h($it['name']) ?></h3><p class="text-[10px] text-slate-400 mt-0.5"><?= h($it['sku']) ?></p></div>
                <div class="grid grid-cols-2 gap-2 text-[11px]">
                    <div class="p-2.5 rounded-xl bg-slate-50"><p class="text-slate-400 font-semibold uppercase text-[9px]">Unit Price</p><p class="font-bold text-slate-800"><?= inv_money($it['unit_price']) ?></p></div>
                    <div class="p-2.5 rounded-xl bg-slate-50"><p class="text-slate-400 font-semibold uppercase text-[9px]">Total Value</p><p class="font-bold text-emerald-700"><?= inv_money($qn * $it['unit_price']) ?></p></div>
                </div>
                <div>
                    <div class="flex justify-between text-xs"><span class="text-slate-500">Stock</span><b class="text-slate-800"><?= inv_qty($qn) ?> <?= h($it['unit']) ?></b></div>
                    <div class="h-1.5 rounded-full bg-slate-100 mt-1.5 overflow-hidden"><div class="h-full rounded-full <?= $st[2] ?>" style="width:<?= $pct ?>%"></div></div>
                </div>
                <div class="mt-auto grid grid-cols-4 gap-1.5">
                    <button type="button" onclick="invView(<?= (int)$it['id'] ?>)" title="Details" class="py-2 rounded-xl border border-slate-200 text-slate-600 text-xs hover:bg-slate-50"><i class="fa-solid fa-eye"></i></button>
                    <button type="button" onclick="invStock(<?= (int)$it['id'] ?>)" title="Stock In/Out" class="py-2 rounded-xl bg-emerald-50 text-emerald-700 text-xs hover:bg-emerald-100"><i class="fa-solid fa-right-left"></i></button>
                    <button type="button" onclick="invForm(<?= (int)$it['id'] ?>)" title="Edit" class="py-2 rounded-xl bg-sky-50 text-sky-700 text-xs hover:bg-sky-100"><i class="fa-solid fa-pen"></i></button>
                    <form method="POST" action="" onsubmit="return confirm('Delete this item? / আইটেমটি মুছে ফেলবেন?')"><?= $csrf ?><input type="hidden" name="do" value="del"><input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                        <button title="Delete" class="w-full py-2 rounded-xl bg-rose-50 text-rose-600 text-xs hover:bg-rose-100"><i class="fa-solid fa-trash"></i></button></form>
                </div>
            </div>
        </div>
        <?php } if (!$items) { echo '<p class="col-span-full py-14 text-center text-slate-400 text-sm"><i class="fa-solid fa-boxes-stacked text-4xl mb-3 block"></i>No items found. Click “Add Item” to start.</p>'; } ?>
    </div>

    <?php if ($pages > 1) { ?>
    <div class="flex items-center justify-between">
        <span class="text-xs text-slate-500">Page <?= $pg ?> / <?= $pages ?></span>
        <div class="flex gap-2">
            <?php if ($pg > 1) { ?><a href="<?= h(inv_url(['pg' => $pg - 1])) ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-xs font-semibold">‹ Prev</a><?php } ?>
            <?php if ($pg < $pages) { ?><a href="<?= h(inv_url(['pg' => $pg + 1])) ?>" class="px-3 py-1.5 rounded-lg border border-slate-200 bg-white text-xs font-semibold">Next ›</a><?php } ?>
        </div>
    </div>
    <?php } ?>

    <!-- Recent stock activity -->
    <div class="bg-white rounded-2xl ring-1 ring-slate-200/80 p-4 sm:p-5">
        <h3 class="text-sm font-bold text-slate-800 mb-3">Recent Stock Activity <small class="text-xs font-medium text-slate-400">/ সাম্প্রতিক লেনদেন</small></h3>
        <div class="divide-y divide-slate-100">
            <?php foreach ($moves as $m) { $in = $m['type'] === 'IN'; ?>
            <div class="py-2.5 flex items-center gap-3 text-xs">
                <span class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0 <?= $in ? 'bg-emerald-50 text-emerald-600' : 'bg-rose-50 text-rose-600' ?>"><i class="fa-solid <?= $in ? 'fa-arrow-down' : 'fa-arrow-up' ?>"></i></span>
                <div class="min-w-0 flex-1"><p class="font-semibold text-slate-800 truncate"><?= h($m['name']) ?></p><p class="text-[10px] text-slate-400 truncate"><?= h($m['note'] ?: ($in ? 'Stock in' : 'Stock out')) ?> • <?= h(date('d M Y, h:i A', strtotime($m['created_at']))) ?></p></div>
                <b class="<?= $in ? 'text-emerald-600' : 'text-rose-600' ?> whitespace-nowrap"><?= $in ? '+' : '−' ?><?= inv_qty($m['qty']) ?> <?= h($m['unit']) ?></b>
            </div>
            <?php } if (!$moves) { echo '<p class="text-xs text-slate-400 py-4 text-center">No activity yet.</p>'; } ?>
        </div>
    </div>
</section>

<datalist id="dl_unit"><?php foreach ($units as $u) { echo '<option value="' . $u . '">'; } ?></datalist>

<!-- ADD / EDIT -->
<div id="invFormModal" class="fixed inset-0 bg-slate-900/60 hidden items-end sm:items-center justify-center sm:p-4 z-50">
    <div class="bg-white w-full sm:max-w-2xl max-h-[94vh] flex flex-col rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-4 bg-slate-900 text-white flex justify-between items-center shrink-0">
            <h3 class="font-bold text-sm"><i class="fa-solid fa-box-open text-emerald-400 mr-2"></i><span id="invFormTitle">Add Item</span></h3>
            <button type="button" onclick="invModal('invFormModal', false)" class="text-slate-400 hover:text-white p-1"><i class="fa-solid fa-xmark text-lg"></i></button>
        </div>
        <form method="POST" action="" enctype="multipart/form-data" class="p-4 sm:p-6 space-y-4 overflow-y-auto" autocomplete="off">
            <?= $csrf ?><input type="hidden" name="do" value="save"><input type="hidden" name="id" id="f_id" value="0">
            <div class="flex items-center gap-3">
                <div class="w-24 h-20 rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 flex items-center justify-center overflow-hidden shrink-0"><i class="fa-solid fa-image text-slate-400 text-xl" id="f_icon"></i><img id="f_prev" class="hidden w-full h-full object-cover"></div>
                <div class="min-w-0"><label class="<?= $lbl ?>">Item Image <small class="normal-case">/ ছবি (max 3MB)</small></label>
                    <input type="file" name="image" id="f_image" accept="image/png,image/jpeg,image/webp" class="text-xs w-full">
                    <label id="f_rm" class="hidden mt-1 items-center gap-1.5 text-[11px] text-rose-600 font-semibold cursor-pointer"><input type="checkbox" name="remove_image" value="1"> Remove current image</label></div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div class="sm:col-span-2"><label class="<?= $lbl ?>">Item Name * <small class="normal-case">/ নাম</small></label><input type="text" name="name" id="f_name" required class="<?= $inp ?>" placeholder="e.g. Rice (Premium)"></div>
                <div><label class="<?= $lbl ?>">Category * <small class="normal-case">/ ক্যাটাগরি</small></label>
                    <select name="category_id" id="f_category_id" required class="<?= $inp ?>"><option value="">Select category</option><?php foreach ($cats as $c) { echo '<option value="' . (int)$c['id'] . '">' . h($c['name']) . '</option>'; } ?></select></div>
                <div><label class="<?= $lbl ?>">SKU / Code</label><input type="text" name="sku" id="f_sku" class="<?= $inp ?>" placeholder="Auto if empty"></div>
                <div><label class="<?= $lbl ?>">Unit Price (৳) <small class="normal-case">/ একক দাম</small></label><input type="number" min="0" step="0.01" name="unit_price" id="f_unit_price" value="0" class="<?= $inp ?>"></div>
                <div><label class="<?= $lbl ?>">Unit <small class="normal-case">/ একক</small></label><input type="text" name="unit" id="f_unit" list="dl_unit" value="pcs" class="<?= $inp ?>"></div>
                <div><label class="<?= $lbl ?>">Quantity <small class="normal-case">/ পরিমাণ</small></label><input type="number" min="0" step="0.01" name="quantity" id="f_quantity" value="0" class="<?= $inp ?>"></div>
                <div><label class="<?= $lbl ?>">Low-stock Alert At <small class="normal-case">/ সতর্কতা</small></label><input type="number" min="0" step="0.01" name="min_stock" id="f_min_stock" value="0" class="<?= $inp ?>"></div>
                <div><label class="<?= $lbl ?>">Storage Location</label><input type="text" name="location" id="f_location" class="<?= $inp ?>" placeholder="Main store, Rack A"></div>
                <div><label class="<?= $lbl ?>">Supplier</label><input type="text" name="supplier" id="f_supplier" class="<?= $inp ?>"></div>
                <div class="sm:col-span-2"><label class="<?= $lbl ?>">Details <small class="normal-case">/ বিবরণ</small></label><textarea name="description" id="f_description" rows="3" class="<?= $inp ?>" placeholder="Brand, size, condition, usage..."></textarea></div>
            </div>
            <div class="flex gap-2 pt-1">
                <button type="button" onclick="invModal('invFormModal', false)" class="flex-1 sm:flex-none px-5 py-3 sm:py-2.5 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600">Cancel</button>
                <button class="flex-1 sm:flex-none sm:ml-auto px-6 py-3 sm:py-2.5 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700">Save Item</button>
            </div>
        </form>
    </div>
</div>

<!-- STOCK IN / OUT -->
<div id="invStockModal" class="fixed inset-0 bg-slate-900/60 hidden items-end sm:items-center justify-center sm:p-4 z-50">
    <div class="bg-white w-full sm:max-w-sm rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-4 bg-slate-900 text-white flex justify-between items-center"><h3 class="font-bold text-sm truncate" id="s_title">Stock</h3><button type="button" onclick="invModal('invStockModal', false)" class="text-slate-400 hover:text-white p-1"><i class="fa-solid fa-xmark text-lg"></i></button></div>
        <form method="POST" action="" class="p-4 sm:p-5 space-y-3">
            <?= $csrf ?><input type="hidden" name="do" value="stock"><input type="hidden" name="id" id="s_id">
            <p class="text-xs text-slate-500">Current stock: <b id="s_cur" class="text-slate-800"></b></p>
            <div class="grid grid-cols-2 gap-2">
                <label class="p-2.5 rounded-xl border border-slate-200 text-center text-xs font-bold text-slate-600 cursor-pointer has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 has-[:checked]:text-emerald-700"><input type="radio" name="type" value="IN" class="sr-only" checked><i class="fa-solid fa-arrow-down mr-1"></i>Stock In</label>
                <label class="p-2.5 rounded-xl border border-slate-200 text-center text-xs font-bold text-slate-600 cursor-pointer has-[:checked]:border-rose-500 has-[:checked]:bg-rose-50 has-[:checked]:text-rose-700"><input type="radio" name="type" value="OUT" class="sr-only"><i class="fa-solid fa-arrow-up mr-1"></i>Stock Out</label>
            </div>
            <div><label class="<?= $lbl ?>">Quantity *</label><input type="number" min="0.01" step="0.01" name="qty" required class="<?= $inp ?>"></div>
            <div><label class="<?= $lbl ?>">Note <small class="normal-case">/ মন্তব্য</small></label><input type="text" name="note" class="<?= $inp ?>" placeholder="Purchased / Distributed to flood victims..."></div>
            <button class="w-full py-3 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700">Update Stock</button>
        </form>
    </div>
</div>

<!-- VIEW -->
<div id="invViewModal" class="fixed inset-0 bg-slate-900/60 hidden items-end sm:items-center justify-center sm:p-4 z-50">
    <div class="bg-white w-full sm:max-w-lg max-h-[94vh] overflow-y-auto rounded-t-3xl sm:rounded-2xl shadow-2xl relative">
        <button type="button" onclick="invModal('invViewModal', false)" class="absolute top-3 right-3 z-10 w-8 h-8 rounded-full bg-slate-900/70 text-white flex items-center justify-center"><i class="fa-solid fa-xmark"></i></button>
        <div id="invViewBody"></div>
    </div>
</div>

<!-- CATEGORIES -->
<div id="invCat" class="fixed inset-0 bg-slate-900/60 hidden items-end sm:items-center justify-center sm:p-4 z-50">
    <div class="bg-white w-full sm:max-w-md max-h-[90vh] flex flex-col rounded-t-3xl sm:rounded-2xl shadow-2xl overflow-hidden">
        <div class="p-4 bg-slate-900 text-white flex justify-between items-center shrink-0"><h3 class="font-bold text-sm"><i class="fa-solid fa-tags text-emerald-400 mr-2"></i>Manage Categories</h3><button type="button" onclick="invModal('invCat', false)" class="text-slate-400 hover:text-white p-1"><i class="fa-solid fa-xmark text-lg"></i></button></div>
        <div class="p-4 space-y-3 overflow-y-auto">
            <form method="POST" action="" class="flex gap-2"><?= $csrf ?><input type="hidden" name="do" value="cat_add">
                <input type="text" name="name" required placeholder="New category name" class="<?= $inp ?>"><button class="px-4 bg-emerald-600 text-white rounded-xl text-xs font-semibold shrink-0">Add</button></form>
            <div class="divide-y divide-slate-100 rounded-xl border border-slate-200">
                <?php foreach ($cats as $i => $c) { ?>
                <div class="flex items-center gap-2 p-2.5 text-xs">
                    <span class="px-2 py-1 rounded-md font-bold <?= $palette[$i % count($palette)] ?>"><?= (int)$c['n'] ?></span>
                    <span class="flex-1 font-semibold text-slate-700 truncate"><?= h($c['name']) ?></span>
                    <form method="POST" action="" onsubmit="return confirm('Delete category?')"><?= $csrf ?><input type="hidden" name="do" value="cat_del"><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><button class="p-1.5 text-slate-400 hover:text-rose-600"><i class="fa-solid fa-trash"></i></button></form>
                </div>
                <?php } ?>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var ITEMS = <?= json_encode($items, JSON_UNESCAPED_UNICODE) ?>, $ = function (id) { return document.getElementById(id); };
    function esc(s) { var d = document.createElement('div'); d.textContent = s == null ? '' : s; return d.innerHTML; }
    function money(n) { return '৳' + Number(n || 0).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
    function find(id) { return ITEMS.filter(function (x) { return +x.id === +id; })[0]; }
    window.invModal = function (id, on) { var m = $(id); m.classList.toggle('hidden', !on); m.classList.toggle('flex', on); document.body.style.overflow = on ? 'hidden' : ''; };
    function setPrev(src) { $('f_prev').classList.toggle('hidden', !src); $('f_icon').classList.toggle('hidden', !!src); $('f_prev').src = src || ''; }

    var F = ['name', 'category_id', 'sku', 'unit_price', 'unit', 'quantity', 'min_stock', 'location', 'supplier', 'description'];
    window.invForm = function (id) {
        var it = id ? find(id) : null;
        $('f_id').value = it ? it.id : 0; $('invFormTitle').textContent = it ? 'Edit Item' : 'Add Item';
        F.forEach(function (k) { $('f_' + k).value = it ? (it[k] == null ? '' : it[k]) : (k === 'unit' ? 'pcs' : (/price|quantity|min/.test(k) ? 0 : '')); });
        $('f_image').value = ''; setPrev(it ? it.image_url : '');
        var rm = $('f_rm'); rm.classList.toggle('hidden', !(it && it.image_url)); rm.classList.toggle('flex', !!(it && it.image_url));
        invModal('invFormModal', true);
    };
    $('f_image').addEventListener('change', function () {
        var f = this.files[0]; if (!f) { return; }
        if (f.size > 3 * 1024 * 1024) { alert('Image must be under 3MB.'); this.value = ''; return; }
        var r = new FileReader(); r.onload = function (e) { setPrev(e.target.result); }; r.readAsDataURL(f);
    });
    window.invStock = function (id) {
        var it = find(id); $('s_id').value = it.id; $('s_title').textContent = it.name;
        $('s_cur').textContent = Number(it.quantity) + ' ' + it.unit; invModal('invStockModal', true);
    };
    window.invView = function (id) {
        var it = find(id), q = Number(it.quantity), mn = Number(it.min_stock);
        var st = q <= 0 ? ['Out of stock', 'bg-rose-100 text-rose-700'] : (q <= mn ? ['Low stock', 'bg-amber-100 text-amber-700'] : ['In stock', 'bg-emerald-100 text-emerald-700']);
        var cover = it.image_url ? '<img src="' + esc(it.image_url) + '" class="w-full h-full object-cover">' : '<div class="w-full h-full bg-gradient-to-br from-emerald-500 via-teal-600 to-slate-800 flex items-center justify-center text-white/80 text-5xl"><i class="fa-solid fa-box-open"></i></div>';
        var cell = function (l, v) { return '<div class="p-3 rounded-xl bg-slate-50 border border-slate-100"><p class="text-[10px] font-bold uppercase text-slate-400">' + l + '</p><p class="text-xs font-semibold text-slate-800 mt-0.5 break-words">' + (v || '<span class="text-slate-300">—</span>') + '</p></div>'; };
        $('invViewBody').innerHTML = '<div class="h-52 relative">' + cover + '<div class="absolute inset-0 bg-gradient-to-t from-slate-900/80 to-transparent"></div>' +
            '<div class="absolute bottom-4 left-4 right-4 text-white"><div class="flex gap-2 mb-2"><span class="px-2.5 py-1 rounded-full text-[10px] font-bold ' + st[1] + '">' + st[0] + '</span><span class="px-2.5 py-1 rounded-full text-[10px] font-semibold bg-white/20 backdrop-blur">' + esc(it.cat_name) + '</span></div><h3 class="text-lg font-bold leading-snug">' + esc(it.name) + '</h3><p class="text-[11px] text-white/70">' + esc(it.sku) + '</p></div></div>' +
            '<div class="p-4 sm:p-5 space-y-4"><div class="grid grid-cols-3 gap-2 text-center"><div class="p-3 rounded-xl bg-slate-900 text-white"><p class="text-[9px] uppercase text-slate-300 font-bold">Unit Price</p><p class="text-xs font-bold">' + money(it.unit_price) + '</p></div>' +
            '<div class="p-3 rounded-xl bg-emerald-50 text-emerald-700"><p class="text-[9px] uppercase font-bold">Stock</p><p class="text-xs font-bold">' + q + ' ' + esc(it.unit) + '</p></div>' +
            '<div class="p-3 rounded-xl bg-sky-50 text-sky-700"><p class="text-[9px] uppercase font-bold">Total Value</p><p class="text-xs font-bold">' + money(q * it.unit_price) + '</p></div></div>' +
            '<div class="grid grid-cols-2 gap-2">' + cell('Low-stock Alert', mn + ' ' + esc(it.unit)) + cell('Location', esc(it.location)) + cell('Supplier', esc(it.supplier)) + cell('Last Updated', esc(String(it.updated_at || '').slice(0, 10))) + '</div>' +
            (it.description ? '<p class="text-xs text-slate-600 leading-relaxed whitespace-pre-line">' + esc(it.description) + '</p>' : '') +
            '<div class="grid grid-cols-2 gap-2"><button type="button" onclick="invModal(\'invViewModal\',false);invStock(' + it.id + ')" class="py-3 rounded-xl bg-emerald-600 text-white text-xs font-semibold"><i class="fa-solid fa-right-left mr-1"></i>Stock In/Out</button>' +
            '<button type="button" onclick="invModal(\'invViewModal\',false);invForm(' + it.id + ')" class="py-3 rounded-xl bg-sky-600 text-white text-xs font-semibold"><i class="fa-solid fa-pen mr-1"></i>Edit</button></div></div>';
        invModal('invViewModal', true);
    };
    ['invFormModal', 'invStockModal', 'invViewModal', 'invCat'].forEach(function (id) { $(id).addEventListener('mousedown', function (e) { if (e.target === this) { invModal(id, false); } }); });
})();
</script>