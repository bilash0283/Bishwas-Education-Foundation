<?php
mysqli_set_charset($db, "utf8mb4");

// ---------- Message (action file theke ashbe) ----------
$success_msg = isset($_GET['success']) ? $_GET['success'] : '';
$error_msg   = isset($_GET['error']) ? $_GET['error'] : '';

// ---------- Stats count ----------
function count_users($db, $type = '', $status = '') {
    $sql = "SELECT COUNT(*) AS total FROM users WHERE 1=1";
    if ($type != '')   { $sql .= " AND user_type = '" . mysqli_real_escape_string($db, $type) . "'"; }
    if ($status != '') { $sql .= " AND status = '" . mysqli_real_escape_string($db, $status) . "'"; }
    $res = mysqli_query($db, $sql);
    $row = mysqli_fetch_assoc($res);
    return (int)$row['total'];
}

$total_beneficiary = count_users($db, 'Beneficiary');
$total_volunteer   = count_users($db, 'Volunteer');
$total_donor       = count_users($db, 'Donor');
$total_pending     = count_users($db, '', 'Pending');

// ---------- Latest 20 user ----------
$list = mysqli_query($db, "SELECT id, member_name, mobile_no, present_address, user_type, status
                           FROM users ORDER BY id DESC LIMIT 20");
?>

<section class="page-content space-y-6">

    <?php if ($success_msg != '') { ?>
        <div class="p-3 rounded-xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-semibold">
            <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($success_msg); ?>
        </div>
    <?php } ?>

    <?php if ($error_msg != '') { ?>
        <div class="p-3 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-semibold">
            <i class="fa-solid fa-circle-exclamation"></i> <?php echo htmlspecialchars($error_msg); ?>
        </div>
    <?php } ?>

    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 tracking-tight">Beneficiaries</h2>
            <p class="text-xs text-slate-500 mt-0.5">Real-time stats and foundation activity metrics.</p>
        </div>
        <button onclick="openModal('volunteersAddModal')"
            class="px-4 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-xs font-semibold shadow-md shadow-emerald-600/20 flex items-center gap-2">
            <i class="fa-solid fa-plus"></i> Add Volunteer
        </button>
    </div>

    <!-- Stats Grid (database theke) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="p-5 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-hand-holding-heart"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase">Beneficiaries</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-0.5"><?php echo $total_beneficiary; ?></h3>
            </div>
        </div>
        <div class="p-5 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-teal-50 text-teal-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-user-ninja"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase">Volunteers</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-0.5"><?php echo $total_volunteer; ?></h3>
            </div>
        </div>
        <div class="p-5 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-users"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase">Donors</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-0.5"><?php echo $total_donor; ?></h3>
            </div>
        </div>
        <div class="p-5 bg-white rounded-2xl border border-slate-200/80 shadow-xs flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl shrink-0">
                <i class="fa-solid fa-clock"></i>
            </div>
            <div>
                <p class="text-[11px] font-bold text-slate-400 uppercase">Pending Approval</p>
                <h3 class="text-2xl font-bold text-slate-800 mt-0.5"><?php echo $total_pending; ?></h3>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-5 border-b border-slate-100 flex items-center justify-between">
            <h3 class="font-bold text-slate-800 text-sm">Recent Activity & Submissions</h3>
            <span class="text-xs text-emerald-600 font-medium">Live Updates</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs text-slate-600">
                <thead class="bg-slate-50 text-slate-700 uppercase font-bold border-b border-slate-200">
                    <tr>
                        <th class="p-4">Applicant / Member</th>
                        <th class="p-4">Category</th>
                        <th class="p-4">Contact</th>
                        <th class="p-4">Status</th>
                        <th class="p-4 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">

                    <?php if ($list && mysqli_num_rows($list) > 0) { ?>
                        <?php while ($row = mysqli_fetch_assoc($list)) { ?>
                            <tr class="hover:bg-slate-50/80">
                                <td class="p-4 font-semibold text-slate-800">
                                    <?php echo htmlspecialchars($row['member_name']); ?>
                                    <span class="block text-[10px] font-normal text-slate-400">
                                        <?php echo htmlspecialchars(mb_strimwidth((string)$row['present_address'], 0, 40, '...')); ?>
                                    </span>
                                </td>
                                <td class="p-4">
                                    <span class="px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 font-bold text-[10px]">
                                        <?php echo htmlspecialchars($row['user_type']); ?>
                                    </span>
                                </td>
                                <td class="p-4">+88<?php echo htmlspecialchars($row['mobile_no']); ?></td>
                                <td class="p-4">
                                    <?php if ($row['status'] == 'Active') { ?>
                                        <span class="px-2.5 py-1 rounded-md bg-emerald-50 text-emerald-700 font-bold text-[10px]">Active</span>
                                    <?php } else { ?>
                                        <span class="px-2.5 py-1 rounded-md bg-amber-50 text-amber-700 font-bold text-[10px]">Pending</span>
                                    <?php } ?>
                                </td>
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-1">

                                        <?php if ($row['status'] != 'Active') { ?>
                                            <form action="volunteer_action.php" method="POST" class="inline">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="id" value="<?php echo (int)$row['id']; ?>">
                                                <button type="submit" title="Approve"
                                                    class="p-1.5 text-slate-400 hover:text-emerald-600 transition-colors">
                                                    <i class="fa-solid fa-circle-check"></i>
                                                </button>
                                            </form>
                                        <?php } ?>

                                        <button type="button"
                                            onclick="openDeleteModal('volunteerDeleteModal', <?php echo (int)$row['id']; ?>, '<?php echo htmlspecialchars(addslashes($row['member_name']), ENT_QUOTES); ?>')"
                                            class="p-1.5 text-slate-400 hover:text-rose-600 transition-colors">
                                            <i class="fa-solid fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php } ?>
                    <?php } else { ?>
                        <tr>
                            <td colspan="5" class="p-6 text-center text-slate-400">Kono data pawa jayni.</td>
                        </tr>
                    <?php } ?>

                </tbody>
            </table>
        </div>
    </div>
</section>

<!-- ADD MODAL -->
<div id="volunteersAddModal"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center p-4 z-50">
    <div class="bg-white w-full max-w-lg rounded-2xl shadow-2xl overflow-hidden border border-slate-100">
        <div class="p-5 bg-slate-900 text-white flex justify-between items-center">
            <h3 class="font-bold text-sm flex items-center gap-2">
                <i class="fa-solid fa-square-plus text-emerald-400"></i>
                <span>Add Volunteer</span>
            </h3>
            <button type="button" onclick="closeModal('volunteersAddModal')" class="text-slate-400 hover:text-white">
                <i class="fa-solid fa-xmark text-lg"></i>
            </button>
        </div>

        <form class="p-6 space-y-4" action="volunteer_action.php" method="POST">
            <input type="hidden" name="action" value="add">

            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Full Name</label>
                <input type="text" name="member_name" placeholder="Enter complete name" required
                    class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-xl text-xs focus:outline-none focus:border-emerald-500">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Phone Number</label>
                    <input type="text" name="mobile_no" placeholder="01XXXXXXXXX" required pattern="[0-9]{11}" maxlength="11"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                </div>
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Category / Role</label>
                    <select name="user_type"
                        class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500">
                        <option value="Beneficiary">Beneficiary</option>
                        <option value="Volunteer" selected>Volunteer</option>
                        <option value="Donor">Donor / Member</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-500 mb-1">Address / Note</label>
                <textarea name="present_address" rows="3" placeholder="Enter location details..."
                    class="w-full p-2.5 bg-slate-50 border border-slate-200 rounded-lg text-xs focus:outline-none focus:border-emerald-500"></textarea>
            </div>

            <div class="flex justify-end gap-2 pt-2">
                <button type="button" onclick="closeModal('volunteersAddModal')"
                    class="px-4 py-2 border border-slate-200 rounded-xl text-xs font-semibold text-slate-600 hover:bg-slate-50">Cancel</button>
                <button type="submit"
                    class="px-4 py-2 bg-emerald-600 text-white rounded-xl text-xs font-semibold hover:bg-emerald-700 shadow-sm">Save Entry</button>
            </div>
        </form>
    </div>
</div>

<!-- DELETE MODAL -->
<div id="volunteerDeleteModal"
    class="fixed inset-0 bg-slate-900/50 backdrop-blur-xs hidden items-center justify-center p-4 z-50">
    <div class="bg-white w-full max-w-sm