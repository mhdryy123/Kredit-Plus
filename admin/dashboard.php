<?php
// admin/dashboard.php
include '../config/database.php';
requireAdmin();

$page_title = "Dashboard Admin - Sistem Kredit";

$database = new Database();
$db = $database->getConnection();

// Get statistics for admin
$queries = [
    'total_users' => "SELECT COUNT(*) as total FROM users WHERE role = 'user' AND status = 'active'",
    'total_pengajuan' => "SELECT COUNT(*) as total FROM pengajuan_kredit",
    'pengajuan_pending' => "SELECT COUNT(*) as total FROM pengajuan_kredit WHERE status = 'pending'",
    'total_approved' => "SELECT COALESCE(SUM(jumlah), 0) as total FROM pengajuan_kredit WHERE status = 'approved'",
    'recent_pengajuan' => "SELECT pk.*, u.nama_lengkap, jk.nama_jenis 
                          FROM pengajuan_kredit pk 
                          JOIN users u ON pk.user_id = u.id 
                          JOIN jenis_kredit jk ON pk.jenis_kredit_id = jk.id 
                          ORDER BY pk.tanggal_pengajuan DESC LIMIT 5"
];

$stats = [];
foreach ($queries as $key => $query) {
    $stmt = $db->prepare($query);
    $stmt->execute();
    if ($key === 'recent_pengajuan') {
        $stats[$key] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats[$key] = $result['total'];
    }
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 sidebar">
            <div class="d-flex flex-column">
                <a href="dashboard.php" class="nav-link active">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a>
                <a href="pengajuan.php" class="nav-link">
                    <i class="fas fa-file-alt me-2"></i>Pengajuan Kredit
                </a>
                <a href="users.php" class="nav-link">
                    <i class="fas fa-users me-2"></i>Manajemen User
                </a>
                <a href="laporan.php" class="nav-link">
                    <i class="fas fa-chart-bar me-2"></i>Laporan
                </a>
               
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 ms-sm-auto px-4">
            <!-- Hapus bagian Admin Panel di sini -->
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Dashboard</h1>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        USERS</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_users']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-users fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        TOTAL PENGAJUAN</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['total_pengajuan']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        PENDING</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $stats['pengajuan_pending']; ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clock fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        TOTAL DISETUJUI</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo formatRupiah($stats['total_approved']); ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Pengajuan -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Pengajuan Terbaru</h6>
                            <a href="pengajuan.php" class="btn btn-primary btn-sm">Lihat Semua</a>
                        </div>
                        <div class="card-body">
                            <?php if(count($stats['recent_pengajuan']) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-bordered" width="100%" cellspacing="0">
                                        <thead>
                                            <tr>
                                                <th>Nama</th>
                                                <th>Jenis Kredit</th>
                                                <th>Jumlah</th>
                                                <th>Tanggal</th>
                                                <th>Status</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach($stats['recent_pengajuan'] as $p): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($p['nama_lengkap']); ?></td>
                                                <td><?php echo htmlspecialchars($p['nama_jenis']); ?></td>
                                                <td><?php echo formatRupiah($p['jumlah']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($p['tanggal_pengajuan'])); ?></td>
                                                <td>
                                                    <span class="badge <?php echo getStatusBadge($p['status']); ?>">
                                                        <?php 
                                                        // Convert status to Indonesian
                                                        $status_text = $p['status'];
                                                        if ($status_text == 'approved') $status_text = 'disetujui';
                                                        if ($status_text == 'pending') $status_text = 'pending';
                                                        if ($status_text == 'rejected') $status_text = 'ditolak';
                                                        echo ucfirst($status_text); 
                                                        ?>
                                                    </span>
                                                </td>
                                               <!-- Di admin/dashboard.php, bagian aksi tabel: -->
<td>
    <a href="pengajuan_detail.php?id=<?php echo $p['id']; ?>" 
       class="btn btn-sm btn-info">Detail</a>
</td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                    <p class="text-muted">Belum ada pengajuan kredit</p>
                                </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>