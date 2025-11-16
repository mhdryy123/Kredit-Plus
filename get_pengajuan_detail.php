<?php
// get_pengajuan_detail.php - VERSI SIMPLE FIXED
session_start();

// Koneksi database langsung tanpa class
try {
    $host = "localhost";
    $db_name = "sistem_kredit";
    $username = "root";
    $password = "";
    
    $db = new PDO("mysql:host=$host;dbname=$db_name;charset=utf8", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo '<div class="alert alert-danger">Database Error: ' . $e->getMessage() . '</div>';
    exit();
}

// Cek login
if (!isset($_SESSION['user_id'])) {
    echo '<div class="alert alert-danger">Silakan login terlebih dahulu.</div>';
    exit();
}

$pengajuan_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

if ($pengajuan_id == 0) {
    echo '<div class="alert alert-danger">ID pengajuan tidak valid.</div>';
    exit();
}

// Query data dengan pengecekan tabel yang lebih aman
try {
    // Cek dulu apakah tabel ada
    $check_table = $db->query("SHOW TABLES LIKE 'pengajuan_kredit'");
    if ($check_table->rowCount() == 0) {
        echo '<div class="alert alert-danger">Tabel pengajuan_kredit tidak ditemukan.</div>';
        exit();
    }
    
    $query = "SELECT pk.*, jk.nama_jenis, jk.bunga, u.nama_lengkap
              FROM pengajuan_kredit pk 
              JOIN jenis_kredit jk ON pk.jenis_kredit_id = jk.id 
              JOIN users u ON pk.user_id = u.id 
              WHERE pk.id = :pengajuan_id AND pk.user_id = :user_id";
              
    $stmt = $db->prepare($query);
    $stmt->bindParam(':pengajuan_id', $pengajuan_id, PDO::PARAM_INT);
    $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    $stmt->execute();
    
    $pengajuan = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pengajuan) {
        echo '<div class="alert alert-warning">Data pengajuan tidak ditemukan atau Anda tidak memiliki akses.</div>';
        exit();
    }
} catch (Exception $e) {
    echo '<div class="alert alert-danger">Error mengambil data: ' . $e->getMessage() . '</div>';
    exit();
}

// Helper functions
function formatRupiah($angka) {
    if (empty($angka) || !is_numeric($angka)) {
        return 'Rp 0';
    }
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function getStatusBadge($status) {
    $badges = [
        'pending' => 'bg-warning',
        'approved' => 'bg-success', 
        'rejected' => 'bg-danger',
        'completed' => 'bg-info'
    ];
    return isset($badges[$status]) ? $badges[$status] : 'bg-secondary';
}

// Status text dalam Bahasa Indonesia
$status_text = [
    'pending' => 'menunggu',
    'approved' => 'disetujui',
    'rejected' => 'ditolak',
    'completed' => 'selesai'
];

$current_status = $pengajuan['status'];
$status_display = isset($status_text[$current_status]) ? $status_text[$current_status] : $current_status;
?>

<div class="row">
    <div class="col-md-6">
        <h6><i class="fas fa-info-circle me-2"></i>Informasi Pengajuan</h6>
        <table class="table table-sm table-bordered">
            <tr>
                <td width="40%"><strong>Jenis Kredit:</strong></td>
                <td><?php echo htmlspecialchars($pengajuan['nama_jenis']); ?></td>
            </tr>
            <tr>
                <td><strong>Jumlah Pinjaman:</strong></td>
                <td class="fw-bold text-success"><?php echo formatRupiah($pengajuan['jumlah']); ?></td>
            </tr>
            <tr>
                <td><strong>Durasi:</strong></td>
                <td><?php echo htmlspecialchars($pengajuan['durasi']); ?> bulan</td>
            </tr>
            <tr>
                <td><strong>Bunga:</strong></td>
                <td><?php echo htmlspecialchars($pengajuan['bunga']); ?>% per tahun</td>
            </tr>
            <tr>
                <td><strong>Status:</strong></td>
                <td>
                    <span class="badge <?php echo getStatusBadge($current_status); ?>">
                        <?php echo ucfirst($status_display); ?>
                    </span>
                </td>
            </tr>
        </table>
    </div>
    <div class="col-md-6">
        <h6><i class="fas fa-calendar me-2"></i>Informasi Waktu</h6>
        <table class="table table-sm table-bordered">
            <tr>
                <td width="40%"><strong>Tanggal Pengajuan:</strong></td>
                <td><?php echo date('d/m/Y H:i', strtotime($pengajuan['tanggal_pengajuan'])); ?></td>
            </tr>
            <?php if(!empty($pengajuan['tanggal_approval'])): ?>
            <tr>
                <td><strong>Tanggal Persetujuan:</strong></td>
                <td><?php echo date('d/m/Y H:i', strtotime($pengajuan['tanggal_approval'])); ?></td>
            </tr>
            <?php endif; ?>
        </table>
        
        <h6 class="mt-3"><i class="fas fa-bullseye me-2"></i>Tujuan Penggunaan</h6>
        <div class="border p-3 rounded bg-light">
            <?php 
            if (!empty($pengajuan['tujuan'])) {
                echo nl2br(htmlspecialchars($pengajuan['tujuan']));
            } else {
                echo '<em class="text-muted">Tidak ada tujuan yang dicantumkan</em>';
            }
            ?>
        </div>
        
        <?php if(!empty($pengajuan['catatan_admin'])): ?>
        <h6 class="mt-3"><i class="fas fa-sticky-note me-2"></i>Catatan Admin</h6>
        <div class="border p-3 rounded bg-warning bg-opacity-10">
            <?php echo nl2br(htmlspecialchars($pengajuan['catatan_admin'])); ?>
        </div>
        <?php endif; ?>
    </div>
</div>