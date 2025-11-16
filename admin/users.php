<?php
// admin/users.php
include '../config/database.php';
requireAdmin();

$page_title = "Manajemen User - Admin";

$database = new Database();
$db = $database->getConnection();

// Handle user actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $action = $_GET['action'];
    $id = $_GET['id'];
    
    // Prevent admin from modifying themselves
    if ($id == $_SESSION['user_id'] && $action == 'delete') {
        $error = "Anda tidak dapat menghapus akun sendiri!";
    } else {
        switch ($action) {
            case 'activate':
                $query = "UPDATE users SET status = 'active' WHERE id = :id";
                $message = "User berhasil diaktifkan!";
                break;
            case 'deactivate':
                $query = "UPDATE users SET status = 'inactive' WHERE id = :id";
                $message = "User berhasil dinonaktifkan!";
                break;
            case 'delete':
                // Start transaction for delete operation
                $db->beginTransaction();
                try {
                    // First, delete related data from other tables
                    
                    // Delete from notifikasi
                    $query1 = "DELETE FROM notifikasi WHERE user_id = :user_id";
                    $stmt1 = $db->prepare($query1);
                    $stmt1->bindParam(':user_id', $id);
                    $stmt1->execute();
                    
                    // Delete from transaksi
                    $query2 = "DELETE FROM transaksi WHERE user_id = :user_id";
                    $stmt2 = $db->prepare($query2);
                    $stmt2->bindParam(':user_id', $id);
                    $stmt2->execute();
                    
                    // Delete cicilan related to user's pengajuan
                    $query3 = "DELETE c FROM cicilan c 
                              JOIN pengajuan_kredit pk ON c.pengajuan_kredit_id = pk.id 
                              WHERE pk.user_id = :user_id";
                    $stmt3 = $db->prepare($query3);
                    $stmt3->bindParam(':user_id', $id);
                    $stmt3->execute();
                    
                    // Delete pengajuan_kredit
                    $query4 = "DELETE FROM pengajuan_kredit WHERE user_id = :user_id";
                    $stmt4 = $db->prepare($query4);
                    $stmt4->bindParam(':user_id', $id);
                    $stmt4->execute();
                    
                    // Finally delete the user
                    $query5 = "DELETE FROM users WHERE id = :id AND role != 'admin'";
                    $stmt5 = $db->prepare($query5);
                    $stmt5->bindParam(':id', $id);
                    $stmt5->execute();
                    
                    // Commit transaction
                    $db->commit();
                    $message = "User dan semua data terkait berhasil dihapus!";
                    $success = $message;
                    
                } catch (Exception $e) {
                    // Rollback transaction if any error occurs
                    $db->rollBack();
                    $error = "Terjadi kesalahan saat menghapus user: " . $e->getMessage();
                }
                break;
            default:
                header("Location: users.php");
                exit();
        }
        
        // For activate/deactivate operations
        if ($action != 'delete') {
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
            
            if ($stmt->execute()) {
                $success = $message;
            } else {
                $error = "Terjadi kesalahan!";
            }
        }
    }
}

// Get all users with additional info
$query = "SELECT u.*, 
          (SELECT COUNT(*) FROM pengajuan_kredit WHERE user_id = u.id) as total_pengajuan,
          (SELECT COUNT(*) FROM pengajuan_kredit WHERE user_id = u.id AND status = 'approved') as pengajuan_disetujui
          FROM users u 
          ORDER BY u.tanggal_daftar DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

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
                <a href="users.php" class="nav-link active">
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
                <h1 class="h2">Manajemen User</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <div class="btn-group me-2">
                        <span class="btn btn-outline-primary disabled">
                            Total: <?php echo count($users); ?> user
                        </span>
                    </div>
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

            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Daftar User</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover" width="100%" cellspacing="0">
                            <thead class="table-light">
                                <tr>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Telepon</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Pengajuan</th>
                                    <th>Tanggal Daftar</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($users as $user): ?>
                                <tr>
                                    <td>
                                        <div>
                                            <strong><?php echo htmlspecialchars($user['nama_lengkap']); ?></strong>
                                            <?php if($user['id'] == $_SESSION['user_id']): ?>
                                                <span class="badge bg-info">Anda</span>
                                            <?php endif; ?>
                                        </div>
                                        <?php if($user['alamat']): ?>
                                            <small class="text-muted"><?php echo substr($user['alamat'], 0, 50); ?>...</small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo $user['telepon'] ?: '-'; ?></td>
                                    <td>
                                        <span class="badge <?php echo $user['role'] == 'admin' ? 'bg-danger' : 'bg-secondary'; ?>">
                                            <i class="fas <?php echo $user['role'] == 'admin' ? 'fa-crown' : 'fa-user'; ?> me-1"></i>
                                            <?php echo ucfirst($user['role']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo $user['status'] == 'active' ? 'bg-success' : 'bg-warning'; ?>">
                                            <i class="fas <?php echo $user['status'] == 'active' ? 'fa-check' : 'fa-pause'; ?> me-1"></i>
                                            <?php echo ucfirst($user['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="text-center">
                                            <div class="fw-bold text-primary"><?php echo $user['total_pengajuan']; ?></div>
                                            <small class="text-muted">total</small>
                                            <?php if($user['pengajuan_disetujui'] > 0): ?>
                                                <br>
                                                <small class="text-success">
                                                    <?php echo $user['pengajuan_disetujui']; ?> disetujui
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y', strtotime($user['tanggal_daftar'])); ?>
                                        <br>
                                        <small class="text-muted">
                                            <?php echo date('H:i', strtotime($user['tanggal_daftar'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <?php if($user['role'] != 'admin' || $user['id'] == $_SESSION['user_id']): ?>
                                                <?php if($user['status'] == 'active' && $user['id'] != $_SESSION['user_id']): ?>
                                                    <a href="users.php?action=deactivate&id=<?php echo $user['id']; ?>" 
                                                       class="btn btn-warning" 
                                                       title="Nonaktifkan User"
                                                       onclick="return confirm('Nonaktifkan user <?php echo htmlspecialchars($user['nama_lengkap']); ?>?')">
                                                        <i class="fas fa-pause"></i>
                                                    </a>
                                                <?php elseif($user['status'] == 'inactive'): ?>
                                                    <a href="users.php?action=activate&id=<?php echo $user['id']; ?>" 
                                                       class="btn btn-success" 
                                                       title="Aktifkan User"
                                                       onclick="return confirm('Aktifkan user <?php echo htmlspecialchars($user['nama_lengkap']); ?>?')">
                                                        <i class="fas fa-play"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if($user['id'] != $_SESSION['user_id']): ?>
                                                    <button type="button" 
                                                            class="btn btn-danger" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#deleteModal<?php echo $user['id']; ?>"
                                                            title="Hapus User">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php else: ?>
                                                    <span class="btn btn-outline-secondary disabled" title="Tidak dapat mengubah akun sendiri">
                                                        <i class="fas fa-ban"></i>
                                                    </span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </div>

                                        <!-- Delete Confirmation Modal -->
                                        <?php if($user['id'] != $_SESSION['user_id'] && $user['role'] != 'admin'): ?>
                                        <div class="modal fade" id="deleteModal<?php echo $user['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title text-danger">
                                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                                            Konfirmasi Hapus User
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <div class="alert alert-danger">
                                                            <h6>PERINGATAN!</h6>
                                                            <p class="mb-2">Tindakan ini akan menghapus:</p>
                                                            <ul class="mb-2">
                                                                <li>User: <strong><?php echo htmlspecialchars($user['nama_lengkap']); ?></strong></li>
                                                                <li>Semua pengajuan kredit user (<?php echo $user['total_pengajuan']; ?> data)</li>
                                                                <li>Semua data transaksi terkait</li>
                                                                <li>Semua notifikasi terkait</li>
                                                            </ul>
                                                            <p class="mb-0 fw-bold">Tindakan ini tidak dapat dibatalkan!</p>
                                                        </div>
                                                        <p>Apakah Anda yakin ingin menghapus user ini?</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                        <a href="users.php?action=delete&id=<?php echo $user['id']; ?>" 
                                                           class="btn btn-danger">
                                                            <i class="fas fa-trash me-2"></i>Ya, Hapus
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Empty State -->
                    <?php if(count($users) == 0): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-users fa-4x text-muted mb-3"></i>
                        <h5 class="text-muted">Belum ada user terdaftar</h5>
                        <p class="text-muted">User akan muncul di sini setelah mendaftar melalui form registrasi</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Additional confirmation for delete actions
document.addEventListener('DOMContentLoaded', function() {
    // Add confirmation for activate/deactivate links
    const actionLinks = document.querySelectorAll('a[href*="action="]');
    actionLinks.forEach(link => {
        if (!link.onclick) {
            link.addEventListener('click', function(e) {
                const action = this.getAttribute('href').includes('action=activate') ? 'mengaktifkan' : 'menonaktifkan';
                const userName = this.closest('tr').querySelector('strong').textContent;
                
                if (!confirm(`Apakah Anda yakin ingin ${action} user "${userName}"?`)) {
                    e.preventDefault();
                }
            });
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>