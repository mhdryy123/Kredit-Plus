<?php
// index.php
$page_title = "Sistem Kredit - Solusi Keuangan Terpercaya";
include 'includes/header.php';
?>

<!-- Hero Section - Sederhana -->
<section class="bg-primary text-white py-5" style="position: relative; z-index: 1;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <h1 class="display-4 fw-bold mb-4">Solusi Kredit Terbaik untuk Masa Depan Anda</h1>
                <p class="lead mb-4">Akses mudah ke berbagai produk kredit dengan proses cepat, bunga kompetitif, dan pelayanan terbaik.</p>
                <div class="d-flex gap-3 flex-wrap">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="dashboard.php" class="btn btn-light btn-lg px-4 py-2">Dashboard Saya</a>
                    <?php else: ?>
                        <a href="register.php" class="btn btn-light btn-lg px-4 py-2">Daftar Sekarang</a>
                        <a href="login.php" class="btn btn-outline-light btn-lg px-4 py-2">Login</a>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col-lg-6 text-center">
                <i class="fas fa-piggy-bank fa-10x text-light opacity-50"></i>
            </div>
        </div>
    </div>
</section>

<?php include 'includes/footer.php'; ?>