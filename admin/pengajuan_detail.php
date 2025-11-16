<?php
// admin/pengajuan_detail.php - DETAIL PENGAJUAN UNTUK ADMIN
include '../config/database.php';
requireAdmin();

$page_title = "Detail Pengajuan - Admin";

$database = new Database();
$db = $database->getConnection();

// Get pengajuan ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: pengajuan.php");
    exit();
}

$pengajuan_id = $_GET['id'];

// Get pengajuan details with user and jenis kredit info
$query = "SELECT pk.*, 
          u.nama_lengkap, u.email, u.telepon, u.alamat, u.tanggal_daftar,
          jk.nama_jenis, jk.bunga, jk.maksimal_jumlah, jk.maksimal_durasi, jk.syarat,
          admin.nama_lengkap as admin_name
          FROM pengajuan_kredit pk 
          JOIN users u ON pk.user_id = u.id 
          JOIN jenis_kredit jk ON pk.jenis_kredit_id = jk.id 
          LEFT JOIN users admin ON pk.admin_id = admin.id
          WHERE pk.id = :id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $pengajuan_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: pengajuan.php");
    exit();
}

$pengajuan = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $new_status = $_POST['status'];
    $catatan_admin = $_POST['catatan_admin'];
    $admin_id = $_SESSION['user_id'];
    
    $update_query = "UPDATE pengajuan_kredit 
              SET status = :status, 
                  catatan_admin = :catatan_admin,
                  admin_id = :admin_id,
                  tanggal_approval = NOW()
              WHERE id = :id";
    
    $update_stmt = $db->prepare($update_query);
    $update_stmt->bindParam(':status', $new_status);
    $update_stmt->bindParam(':catatan_admin', $catatan_admin);
    $update_stmt->bindParam(':admin_id', $admin_id);
    $update_stmt->bindParam(':id', $pengajuan_id);
    
    if ($update_stmt->execute()) {
        $success = "Status pengajuan berhasil diperbarui menjadi " . ucfirst($new_status) . "!";
        // Refresh data dengan query yang sama
        $refresh_stmt = $db->prepare($query);
        $refresh_stmt->bindParam(':id', $pengajuan_id);
        $refresh_stmt->execute();
        $pengajuan = $refresh_stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $error = "Terjadi kesalahan saat memperbarui status!";
    }
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3 col-lg-2 sidebar">
            <div class="d-flex flex-column">
                <a href="dashboard.php" class="nav-link">
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
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <div>
                    <h1 class="h2">Detail Pengajuan Kredit</h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="breadcrumb-item"><a href="pengajuan.php">Pengajuan Kredit</a></li>
                            <li class="breadcrumb-item active">Detail</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <a href="pengajuan.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Kembali
                    </a>
                </div>
            </div>

            <?php if(isset($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Informasi Pengajuan -->
                <div class="col-lg-8">
                    <div class="card shadow mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>Informasi Pengajuan
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr>
                                            <td width="40%"><strong>ID Pengajuan</strong></td>
                                            <td>KR-<?php echo str_pad($pengajuan['id'], 4, '0', STR_PAD_LEFT); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Jenis Kredit</strong></td>
                                            <td><?php echo htmlspecialchars($pengajuan['nama_jenis']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Jumlah</strong></td>
                                            <td class="fw-bold text-primary"><?php echo formatRupiah($pengajuan['jumlah']); ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Durasi</strong></td>
                                            <td><?php echo $pengajuan['durasi']; ?> bulan</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-sm">
                                        <tr>
                                            <td width="40%"><strong>Bunga</strong></td>
                                            <td><?php echo $pengajuan['bunga']; ?>% per tahun</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status</strong></td>
                                            <td>
                                                <span class="badge <?php echo getStatusBadge($pengajuan['status']); ?> fs-6">
                                                    <?php 
                                                    $status_text = $pengajuan['status'];
                                                    if ($status_text == 'approved') $status_text = 'Disetujui';
                                                    if ($status_text == 'pending') $status_text = 'Pending';
                                                    if ($status_text == 'rejected') $status_text = 'Ditolak';
                                                    if ($status_text == 'completed') $status_text = 'Selesai';
                                                    echo $status_text; 
                                                    ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Tanggal Pengajuan</strong></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($pengajuan['tanggal_pengajuan'])); ?></td>
                                        </tr>
                                        <?php if(!empty($pengajuan['tanggal_approval'])): ?>
                                        <tr>
                                            <td><strong>Tanggal Persetujuan</strong></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($pengajuan['tanggal_approval'])); ?></td>
                                        </tr>
                                        <?php endif; ?>
                                    </table>
                                </div>
                            </div>

                            <!-- Tujuan Penggunaan -->
                            <div class="mt-4">
                                <h6><i class="fas fa-bullseye me-2"></i>Tujuan Penggunaan</h6>
                                <div class="border p-3 rounded bg-light">
                                    <?php echo nl2br(htmlspecialchars($pengajuan['tujuan'])); ?>
                                </div>
                            </div>

                            <!-- Catatan Admin -->
                            <?php if(!empty($pengajuan['catatan_admin'])): ?>
                            <div class="mt-4">
                                <h6><i class="fas fa-sticky-note me-2"></i>Catatan Admin</h6>
                                <div class="border p-3 rounded bg-warning bg-opacity-10">
                                    <?php echo nl2br(htmlspecialchars($pengajuan['catatan_admin'])); ?>
                                    <?php if(!empty($pengajuan['admin_name'])): ?>
                                        <div class="text-end mt-2">
                                            <small class="text-muted">
                                                - <?php echo htmlspecialchars($pengajuan['admin_name']); ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Kalkulator Angsuran -->
                    <div class="card shadow mb-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-calculator me-2"></i>Kalkulator Angsuran
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php
                            // Calculate angsuran
                            $jumlah = $pengajuan['jumlah'];
                            $durasi = $pengajuan['durasi'];
                            $bunga_tahun = $pengajuan['bunga'];
                            
                            $bunga_bulan = $bunga_tahun / 100 / 12;
                            if ($bunga_bulan > 0 && $durasi > 0) {
                                $angsuran_per_bulan = $jumlah * ($bunga_bulan * pow(1 + $bunga_bulan, $durasi)) / 
                                                    (pow(1 + $bunga_bulan, $durasi) - 1);
                                $total_bayar = $angsuran_per_bulan * $durasi;
                                $total_bunga = $total_bayar - $jumlah;
                            } else {
                                $angsuran_per_bulan = $jumlah / $durasi;
                                $total_bayar = $jumlah;
                                $total_bunga = 0;
                            }
                            ?>
                            <div class="row text-center">
                                <div class="col-md-3">
                                    <div class="border rounded p-3 bg-light">
                                        <h6 class="text-muted">Angsuran per Bulan</h6>
                                        <h4 class="text-success fw-bold"><?php echo formatRupiah(round($angsuran_per_bulan)); ?></h4>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded p-3 bg-light">
                                        <h6 class="text-muted">Total Pembayaran</h6>
                                        <h5 class="fw-bold"><?php echo formatRupiah(round($total_bayar)); ?></h5>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded p-3 bg-light">
                                        <h6 class="text-muted">Total Bunga</h6>
                                        <h5 class="text-warning fw-bold"><?php echo formatRupiah(round($total_bunga)); ?></h5>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="border rounded p-3 bg-light">
                                        <h6 class="text-muted">Bunga Efektif</h6>
                                        <h5 class="text-info fw-bold">
                                            <?php 
                                            if ($jumlah > 0) {
                                                echo number_format(($total_bunga / $jumlah) * 100, 2);
                                            } else {
                                                echo '0.00';
                                            }
                                            ?>%
                                        </h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Informasi User & Aksi -->
                <div class="col-lg-4">
                    <!-- Informasi User -->
                    <div class="card shadow mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-user me-2"></i>Informasi Pemohon
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center" 
                                     style="width: 80px; height: 80px; font-size: 2rem;">
                                    <?php echo strtoupper(substr($pengajuan['nama_lengkap'], 0, 1)); ?>
                                </div>
                                <h5 class="mt-2 mb-1"><?php echo htmlspecialchars($pengajuan['nama_lengkap']); ?></h5>
                                <p class="text-muted mb-0"><?php echo htmlspecialchars($pengajuan['email']); ?></p>
                            </div>
                            
                            <table class="table table-sm">
                                <tr>
                                    <td><strong>Telepon</strong></td>
                                    <td><?php echo !empty($pengajuan['telepon']) ? $pengajuan['telepon'] : '-'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Alamat</strong></td>
                                    <td><?php echo !empty($pengajuan['alamat']) ? nl2br(htmlspecialchars($pengajuan['alamat'])) : '-'; ?></td>
                                </tr>
                                <tr>
                                    <td><strong>Bergabung</strong></td>
                                    <td><?php echo date('d/m/Y', strtotime($pengajuan['tanggal_daftar'])); ?></td>
                                </tr>
                            </table>
                            
                            <div class="text-center mt-3">
                                <a href="users.php" class="btn btn-outline-success btn-sm">
                                    <i class="fas fa-users me-1"></i>Lihat Semua User
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Aksi Admin -->
                    <?php if($pengajuan['status'] == 'pending'): ?>
                    <div class="card shadow">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0">
                                <i class="fas fa-cog me-2"></i>Aksi Admin
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <div class="mb-3">
                                    <label for="status" class="form-label">Update Status</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="pending" <?php echo $pengajuan['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="approved" <?php echo $pengajuan['status'] == 'approved' ? 'selected' : ''; ?>>Setujui</option>
                                        <option value="rejected" <?php echo $pengajuan['status'] == 'rejected' ? 'selected' : ''; ?>>Tolak</option>
                                    </select>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="catatan_admin" class="form-label">Catatan Admin</label>
                                    <textarea class="form-control" id="catatan_admin" name="catatan_admin" 
                                              rows="3" placeholder="Berikan catatan untuk user..."><?php echo !empty($pengajuan['catatan_admin']) ? htmlspecialchars($pengajuan['catatan_admin']) : ''; ?></textarea>
                                    <div class="form-text">
                                        Catatan akan ditampilkan ke user
                                    </div>
                                </div>
                                
                                <button type="submit" name="update_status" class="btn btn-warning w-100">
                                    <i class="fas fa-save me-2"></i>Update Status
                                </button>
                            </form>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="card shadow">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>Status Saat Ini
                            </h5>
                        </div>
                        <div class="card-body text-center">
                            <div class="mb-3">
                                <span class="badge <?php echo getStatusBadge($pengajuan['status']); ?> fs-6 p-3">
                                    <?php 
                                    $status_text = $pengajuan['status'];
                                    if ($status_text == 'approved') $status_text = 'DISETUJUI';
                                    if ($status_text == 'pending') $status_text = 'PENDING';
                                    if ($status_text == 'rejected') $status_text = 'DITOLAK';
                                    if ($status_text == 'completed') $status_text = 'SELESAI';
                                    echo $status_text; 
                                    ?>
                                </span>
                            </div>
                            <?php if(!empty($pengajuan['tanggal_approval'])): ?>
                                <p class="text-muted mb-0">
                                    <small>
                                        Diproses pada:<br>
                                        <?php echo date('d/m/Y H:i', strtotime($pengajuan['tanggal_approval'])); ?>
                                    </small>
                                </p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>