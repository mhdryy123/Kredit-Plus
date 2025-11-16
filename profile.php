<?php
// profile.php
include 'config/database.php';
requireLogin();

$page_title = "Profile - Sistem Kredit";

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Get user data
$query = "SELECT * FROM users WHERE id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_lengkap = $_POST['nama_lengkap'];
    $telepon = $_POST['telepon'];
    $alamat = $_POST['alamat'];
    
    $query = "UPDATE users SET nama_lengkap = :nama_lengkap, telepon = :telepon, alamat = :alamat WHERE id = :user_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':nama_lengkap', $nama_lengkap);
    $stmt->bindParam(':telepon', $telepon);
    $stmt->bindParam(':alamat', $alamat);
    $stmt->bindParam(':user_id', $user_id);
    
    if ($stmt->execute()) {
        $_SESSION['nama'] = $nama_lengkap;
        $success = "Profile berhasil diperbarui!";
        // Refresh user data
        $stmt = $db->prepare("SELECT * FROM users WHERE id = :user_id");
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $error = "Terjadi kesalahan saat memperbarui profile!";
    }
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-user me-2"></i>Profile Saya</h4>
                </div>
                <div class="card-body p-4">
                    <?php if(isset($success)): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                    <?php endif; ?>
                    
                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="nama_lengkap" class="form-label">Nama Lengkap *</label>
                                    <input type="text" class="form-control" id="nama_lengkap" name="nama_lengkap" 
                                           value="<?php echo htmlspecialchars($user['nama_lengkap']); ?>" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" readonly>
                                    <small class="text-muted">Email tidak dapat diubah</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="telepon" class="form-label">Nomor Telepon</label>
                                    <input type="tel" class="form-control" id="telepon" name="telepon" 
                                           value="<?php echo htmlspecialchars(isset($user['telepon']) ? $user['telepon'] : ''); ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Role</label>
                                    <input type="text" class="form-control" 
                                           value="<?php echo ucfirst($user['role']); ?>" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="alamat" class="form-label">Alamat</label>
                            <textarea class="form-control" id="alamat" name="alamat" rows="3"><?php echo htmlspecialchars(isset($user['alamat']) ? $user['alamat'] : ''); ?></textarea>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Tanggal Daftar</label>
                            <input type="text" class="form-control" 
                                   value="<?php echo date('d/m/Y H:i', strtotime($user['tanggal_daftar'])); ?>" readonly>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Kembali
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-2"></i>Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Account Info Card -->
            <div class="card shadow mt-4">
                <div class="card-header bg-info text-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Akun</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Statistik Kredit</h6>
                            <?php
                            $query = "SELECT status, COUNT(*) as total FROM pengajuan_kredit WHERE user_id = :user_id GROUP BY status";
                            $stmt = $db->prepare($query);
                            $stmt->bindParam(':user_id', $user_id);
                            $stmt->execute();
                            $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>
                            <ul class="list-unstyled">
                                <?php foreach($stats as $stat): ?>
                                <li>
                                    <span class="badge <?php echo getStatusBadge($stat['status']); ?> me-2">
                                        <?php echo ucfirst($stat['status']); ?>
                                    </span>
                                    : <?php echo $stat['total']; ?> pengajuan
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>Akun Demo</h6>
                            <p class="small text-muted">
                                Fitur perubahan password belum tersedia dalam versi demo. 
                                Untuk aplikasi produksi, implementasikan sistem enkripsi password.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>