<?php
// dashboard.php - DASHBOARD UNTUK USER BIASA
include 'config/database.php';
requireLogin();

$page_title = "Dashboard - Sistem Kredit";

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Get user statistics
$queries = [
    'total_approved' => "SELECT COALESCE(SUM(jumlah), 0) as total FROM pengajuan_kredit WHERE user_id = :user_id AND status = 'approved'",
    'total_pending' => "SELECT COUNT(*) as total FROM pengajuan_kredit WHERE user_id = :user_id AND status = 'pending'",
    'total_pengajuan' => "SELECT COUNT(*) as total FROM pengajuan_kredit WHERE user_id = :user_id",
    'total_rejected' => "SELECT COUNT(*) as total FROM pengajuan_kredit WHERE user_id = :user_id AND status = 'rejected'",
    'recent_pengajuan' => "SELECT pk.*, jk.nama_jenis, jk.bunga 
                          FROM pengajuan_kredit pk 
                          JOIN jenis_kredit jk ON pk.jenis_kredit_id = jk.id 
                          WHERE pk.user_id = :user_id 
                          ORDER BY pk.tanggal_pengajuan DESC 
                          LIMIT 5"
];

$stats = [];
foreach ($queries as $key => $query) {
    $stmt = $db->prepare($query);
    $stmt->bindParam(':user_id', $user_id);
    $stmt->execute();
    
    if ($key === 'recent_pengajuan') {
        $stats[$key] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats[$key] = $result['total'];
    }
}

// Get user info
$query = "SELECT nama_lengkap, email, tanggal_daftar FROM users WHERE id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user_info = $stmt->fetch(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="container mt-4">
    <!-- Welcome Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card dashboard-welcome shadow" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                <div class="card-body">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h3 class="text-white">Selamat datang, <?php echo htmlspecialchars($_SESSION['nama']); ?>! 👋</h3>
                            <p class="text-white mb-0">Kelola kredit dan pantau pengajuan Anda di sini.</p>
                            <small class="text-white-50">
                                Bergabung sejak: <?php echo date('d F Y', strtotime($user_info['tanggal_daftar'])); ?>
                            </small>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="pengajuan.php" class="btn btn-light btn-lg">
                                <i class="fas fa-plus me-2"></i>Ajukan Kredit Baru
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Total Disetujui</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo formatRupiah($stats['total_approved']); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Menunggu Persetujuan</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $stats['total_pending']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Total Pengajuan</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $stats['total_pengajuan']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card stat-card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Ditolak</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php echo $stats['total_rejected']; ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-times-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Recent Applications -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history me-2 text-primary"></i>Pengajuan Terbaru
                    </h5>
                    <div>
                        <a href="pengajuan.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus me-1"></i>Ajukan Baru
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <?php if(count($stats['recent_pengajuan']) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Jenis Kredit</th>
                                        <th>Jumlah</th>
                                        <th>Durasi</th>
                                        <th>Tanggal</th>
                                        <th>Status</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($stats['recent_pengajuan'] as $p): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($p['nama_jenis']); ?></strong>
                                            <br>
                                            <small class="text-muted">Bunga: <?php echo $p['bunga']; ?>%</small>
                                        </td>
                                        <td>
                                            <strong><?php echo formatRupiah($p['jumlah']); ?></strong>
                                        </td>
                                        <td><?php echo $p['durasi']; ?> bulan</td>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($p['tanggal_pengajuan'])); ?>
                                            <br>
                                            <small class="text-muted">
                                                <?php echo date('H:i', strtotime($p['tanggal_pengajuan'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo getStatusBadge($p['status']); ?>">
                                                <i class="fas 
                                                    <?php 
                                                    switch($p['status']) {
                                                        case 'approved': echo 'fa-check'; break;
                                                        case 'pending': echo 'fa-clock'; break;
                                                        case 'rejected': echo 'fa-times'; break;
                                                        default: echo 'fa-info';
                                                    }
                                                    ?> 
                                                    me-1">
                                                </i>
                                                <?php 
                                                // Convert status to Indonesian
                                                $status_text = $p['status'];
                                                if ($status_text == 'approved') $status_text = 'disetujui';
                                                if ($status_text == 'pending') $status_text = 'menunggu';
                                                if ($status_text == 'rejected') $status_text = 'ditolak';
                                                echo ucfirst($status_text); 
                                                ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group btn-group-sm">
                                                <button type="button" class="btn btn-outline-info btn-detail" 
                                                        data-pengajuan-id="<?php echo $p['id']; ?>"
                                                        title="Detail">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if($p['status'] == 'pending'): ?>
                                                    <a href="edit_pengajuan.php?id=<?php echo $p['id']; ?>" 
                                                       class="btn btn-outline-warning" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-file-alt fa-4x text-muted mb-3"></i>
                            <h5 class="text-muted">Belum ada pengajuan kredit</h5>
                            <p class="text-muted">Mulai ajukan kredit pertama Anda untuk melihat riwayat di sini</p>
                            <a href="pengajuan.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Ajukan Kredit Pertama
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Quick Actions & Info -->
        <div class="col-lg-4">
            <!-- Quick Actions -->
            <div class="card shadow mb-4">
                <div class="card-header bg-primary text-white">
                    <h6 class="mb-0"><i class="fas fa-bolt me-2"></i>Aksi Cepat</h6>
                </div>
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="pengajuan.php" class="btn btn-success">
                            <i class="fas fa-file-alt me-2"></i>Ajukan Kredit Baru
                        </a>
                        <a href="profile.php" class="btn btn-outline-primary">
                            <i class="fas fa-user me-2"></i>Edit Profile
                        </a>
                        <?php if(isAdmin()): ?>
                            <a href="admin/dashboard.php" class="btn btn-warning">
                                <i class="fas fa-cog me-2"></i>Panel Admin
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Status Info -->
            <div class="card shadow">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Status</h6>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <span class="badge bg-warning me-2">●</span>
                        <small><strong>Menunggu:</strong> Menunggu persetujuan admin</small>
                    </div>
                    <div class="mb-3">
                        <span class="badge bg-success me-2">●</span>
                        <small><strong>Disetujui:</strong> Pengajuan disetujui</small>
                    </div>
                    <div class="mb-3">
                        <span class="badge bg-danger me-2">●</span>
                        <small><strong>Ditolak:</strong> Pengajuan ditolak</small>
                    </div>
                    <div class="mb-3">
                        <span class="badge bg-info me-2">●</span>
                        <small><strong>Selesai:</strong> Kredit telah lunas</small>
                    </div>
                    <hr>
                    <div class="small text-muted">
                        <i class="fas fa-clock me-1"></i>
                        Proses persetujuan biasanya memakan waktu 1-3 hari kerja
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Global Detail Modal -->
<div class="modal fade" id="globalDetailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Pengajuan Kredit</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="modalDetailContent">
                <!-- Content will be loaded via AJAX -->
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Memuat detail pengajuan...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
// JavaScript untuk menangani modal detail
document.addEventListener('DOMContentLoaded', function() {
    // Event listener untuk tombol detail
    document.querySelectorAll('.btn-detail').forEach(button => {
        button.addEventListener('click', function() {
            const pengajuanId = this.getAttribute('data-pengajuan-id');
            showDetailModal(pengajuanId);
        });
    });

    function showDetailModal(pengajuanId) {
        // Tampilkan modal
        const modal = new bootstrap.Modal(document.getElementById('globalDetailModal'));
        
        // Load content via AJAX
        fetch(`get_pengajuan_detail.php?id=${pengajuanId}`)
            .then(response => response.text())
            .then(data => {
                document.getElementById('modalDetailContent').innerHTML = data;
                modal.show();
            })
            .catch(error => {
                document.getElementById('modalDetailContent').innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Gagal memuat detail pengajuan. Silakan coba lagi.
                    </div>
                `;
                modal.show();
            });
    }
});
</script>

<?php include 'includes/footer.php'; ?>