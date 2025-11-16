<?php
// admin/pengajuan.php
include '../config/database.php';
requireAdmin();

$page_title = "Manajemen Pengajuan - Admin";

$database = new Database();
$db = $database->getConnection();

// Handle actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = $_GET['id'];
    $admin_id = $_SESSION['user_id'];
    
    switch ($action) {
        case 'approve':
            $query = "UPDATE pengajuan_kredit SET status = 'approved', tanggal_approval = NOW(), admin_id = :admin_id WHERE id = :id";
            $message = "Pengajuan berhasil disetujui!";
            break;
        case 'reject':
            $query = "UPDATE pengajuan_kredit SET status = 'rejected', tanggal_approval = NOW(), admin_id = :admin_id WHERE id = :id";
            $message = "Pengajuan berhasil ditolak!";
            break;
        default:
            header("Location: pengajuan.php");
            exit();
    }
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':admin_id', $admin_id);
    $stmt->bindParam(':id', $id);
    
    if ($stmt->execute()) {
        $success = $message;
    } else {
        $error = "Terjadi kesalahan!";
    }
}

// Get all pengajuan
$query = "SELECT pk.*, u.nama_lengkap, u.email, jk.nama_jenis, jk.bunga 
          FROM pengajuan_kredit pk 
          JOIN users u ON pk.user_id = u.id 
          JOIN jenis_kredit jk ON pk.jenis_kredit_id = jk.id 
          ORDER BY pk.tanggal_pengajuan DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$pengajuan = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                <a href="pengajuan.php" class="nav-link active">
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
                <h1 class="h2">Manajemen Pengajuan Kredit</h1>
            </div>

            <?php if(isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if(isset($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Daftar Pengajuan Kredit</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                            <thead>
                                <tr>
                                    <th>Nama User</th>
                                    <th>Jenis Kredit</th>
                                    <th>Jumlah</th>
                                    <th>Durasi</th>
                                    <th>Bunga</th>
                                    <th>Tanggal Pengajuan</th>
                                    <th>Status</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($pengajuan as $p): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo $p['nama_lengkap']; ?></strong><br>
                                        <small class="text-muted"><?php echo $p['email']; ?></small>
                                    </td>
                                    <td><?php echo $p['nama_jenis']; ?></td>
                                    <td><?php echo formatRupiah($p['jumlah']); ?></td>
                                    <td><?php echo $p['durasi']; ?> bulan</td>
                                    <td><?php echo $p['bunga']; ?>%</td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($p['tanggal_pengajuan'])); ?></td>
                                    <td>
                                        <span class="badge <?php echo getStatusBadge($p['status']); ?>">
                                            <?php echo ucfirst($p['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if($p['status'] == 'pending'): ?>
                                                <a href="pengajuan.php?action=approve&id=<?php echo $p['id']; ?>" 
                                                   class="btn btn-success" title="Setujui">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                                <a href="pengajuan.php?action=reject&id=<?php echo $p['id']; ?>" 
                                                   class="btn btn-danger" title="Tolak">
                                                    <i class="fas fa-times"></i>
                                                </a>
                                            <?php endif; ?>
                                            <button type="button" class="btn btn-info" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#detailModal<?php echo $p['id']; ?>"
                                                    title="Detail">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </div>

                                        <!-- Detail Modal -->
                                        <div class="modal fade" id="detailModal<?php echo $p['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Detail Pengajuan</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="row">
                                                            <div class="col-6">
                                                                <strong>Nama:</strong><br>
                                                                <?php echo $p['nama_lengkap']; ?>
                                                            </div>
                                                            <div class="col-6">
                                                                <strong>Email:</strong><br>
                                                                <?php echo $p['email']; ?>
                                                            </div>
                                                        </div>
                                                        <hr>
                                                        <div class="row">
                                                            <div class="col-6">
                                                                <strong>Jenis Kredit:</strong><br>
                                                                <?php echo $p['nama_jenis']; ?>
                                                            </div>
                                                            <div class="col-6">
                                                                <strong>Jumlah:</strong><br>
                                                                <?php echo formatRupiah($p['jumlah']); ?>
                                                            </div>
                                                        </div>
                                                        <div class="row mt-2">
                                                            <div class="col-6">
                                                                <strong>Durasi:</strong><br>
                                                                <?php echo $p['durasi']; ?> bulan
                                                            </div>
                                                            <div class="col-6">
                                                                <strong>Bunga:</strong><br>
                                                                <?php echo $p['bunga']; ?>%
                                                            </div>
                                                        </div>
                                                        <div class="row mt-2">
                                                            <div class="col-12">
                                                                <strong>Tujuan:</strong><br>
                                                                <?php echo $p['tujuan'] ?: '-'; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>