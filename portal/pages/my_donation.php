<?php 
if (!isset($_SESSION['user_type'])) {
    header('Location: index.php?page=dashboard');
    exit;
}
if(isset($_SESSION['user_id'])) {
    $user_idd = $_SESSION['user_id'];     
}else{
    echo '<section class="page-content max-w-xl mx-auto"><div class="p-6 bg-white rounded-2xl ring-1 ring-slate-200 text-center space-y-2"><i class="fa-solid fa-lock text-3xl text-slate-300"></i>
          <h3 class="font-bold text-slate-800">Access Restricted</h3><p class="text-xs text-slate-500">
          Your account does not have permission to access this page.</p></div>
          </section>';
    exit;
}

echo $user_idd
?>