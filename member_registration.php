<?php
// header a HTML output ache, tai buffer diye chapa dhore rakhi jate redirect kaj kore
ob_start();
include 'include/header.php';   // ekhan theke $db ashbe
ob_end_clean();

// Shudhu POST hole kaj korbe
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: index.php");
    exit;
}

mysqli_set_charset($db, "utf8mb4");

// ---------- 1. Form theke data neya ----------
$member_name         = trim($_POST['member_name'] ?? '');
$mother_name         = trim($_POST['mother_name'] ?? '');
$father_husband_name = trim($_POST['father_husband_name'] ?? '');
$dob                 = trim($_POST['dob'] ?? '');
$gender              = trim($_POST['gender'] ?? '');
$id_type             = trim($_POST['id_type'] ?? '');
$id_number           = trim($_POST['id_number'] ?? '');
$qualification       = trim($_POST['qualification'] ?? '');
$mobile_no           = trim($_POST['mobile_no'] ?? '');
$email               = trim($_POST['email'] ?? '');
$present_address     = trim($_POST['present_address'] ?? '');
$permanent_address   = trim($_POST['permanent_address'] ?? '');
$other_info          = trim($_POST['other_info'] ?? '');
$membership_status   = trim($_POST['membership_status'] ?? '');

// Password form theke ashbe na. Default 12345, md5 kore rakhbo
$password = md5('12345');

// Je page theke ashche sei page a ferot jabe (nam alada hole ekhane change koro)
$back_page = 'volunteer_register.php';

// ---------- 2. Validation ----------
if ($member_name == '' || $mother_name == '' || $father_husband_name == '' || $dob == '' ||
    $gender == '' || $id_type == '' || $id_number == '' || $mobile_no == '' ||
    $present_address == '' || $permanent_address == '' || $membership_status == '') {
    header("Location: $back_page?error=" . urlencode('সব প্রয়োজনীয় তথ্য পূরণ করুন।'));
    exit;
}

if (!preg_match('/^[0-9]{11}$/', $mobile_no)) {
    header("Location: $back_page?error=" . urlencode('মোবাইল নম্বর ১১ ডিজিটের হতে হবে।'));
    exit;
}

if ($email != '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    header("Location: $back_page?error=" . urlencode('সঠিক ইমেইল দিন।'));
    exit;
}

// Mobile number age thekei ache kina check
$check = mysqli_prepare($db, "SELECT id FROM users WHERE mobile_no = ? LIMIT 1");
mysqli_stmt_bind_param($check, "s", $mobile_no);
mysqli_stmt_execute($check);
mysqli_stmt_store_result($check);
if (mysqli_stmt_num_rows($check) > 0) {
    header("Location: $back_page?error=" . urlencode('এই মোবাইল নম্বর দিয়ে আগেই আবেদন করা হয়েছে।'));
    exit;
}
mysqli_stmt_close($check);

// ---------- 3. Photo upload ----------
$photo_name = '';

if (isset($_FILES['photo']) && $_FILES['photo']['error'] === 0) {

    $allowed_ext = array('jpg', 'jpeg', 'png');
    $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed_ext)) {
        header("Location: $back_page?error=" . urlencode('ছবি শুধু JPG বা PNG হতে হবে।'));
        exit;
    }

    if ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
        header("Location: $back_page?error=" . urlencode('ছবির সাইজ সর্বোচ্চ 2MB।'));
        exit;
    }

    $upload_dir = 'public/uploads/members/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $photo_name = 'member_' . time() . '_' . rand(1000, 9999) . '.' . $ext;

    if (!move_uploaded_file($_FILES['photo']['tmp_name'], $upload_dir . $photo_name)) {
        header("Location: $back_page?error=" . urlencode('ছবি আপলোড করা যায়নি।'));
        exit;
    }
}

// ---------- 4. Database a save ----------
$sql = "INSERT INTO users
        (photo, member_name, mother_name, father_husband_name, dob, gender, id_type, id_number,
         qualification, mobile_no, email, present_address, permanent_address, other_info,
         membership_status, password)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

$stmt = mysqli_prepare($db, $sql);

mysqli_stmt_bind_param(
    $stmt,
    "ssssssssssssssss",
    $photo_name, $member_name, $mother_name, $father_husband_name, $dob, $gender,
    $id_type, $id_number, $qualification, $mobile_no, $email, $present_address,
    $permanent_address, $other_info, $membership_status, $password
);

if (mysqli_stmt_execute($stmt)) {
    header("Location: $back_page?success=1");
} else {
    header("Location: $back_page?error=" . urlencode('ডাটাবেসে সেভ করতে সমস্যা হয়েছে।'));
}
exit;
?>