<?php
// edit_pengajuan.php - EDIT PENGAJUAN UNTUK USER
include 'config/database.php';
requireLogin();

$page_title = "Edit Pengajuan - Sistem Kredit";

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Get pengajuan ID from URL
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: dashboard.php");
    exit();
}

$pengajuan_id = $_GET['id'];

// Check if pengajuan exists and belongs to user
$query = "SELECT pk.*, jk.nama_jenis, jk.maksimal_jumlah, jk.maksimal_durasi, jk.bunga 
          FROM pengajuan_kredit pk 
          JOIN jenis_kredit jk ON pk.jenis_kredit_id = jk.id 
          WHERE pk.id = :id AND pk.user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $pengajuan_id);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    header("Location: dashboard.php");
    exit();
}

$pengajuan = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if pengajuan can be edited (only pending status)
if ($pengajuan['status'] != 'pending') {
    $_SESSION['error'] = "Pengajuan tidak dapat diedit karena status sudah " . ucfirst($pengajuan['status']);
    header("Location: dashboard.php");
    exit();
}

// Get jenis kredit
$query = "SELECT * FROM jenis_kredit WHERE status = 'active'";
$stmt = $db->prepare($query);
$stmt->execute();
$jenis_kredit = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $jenis_kredit_id = $_POST['jenis_kredit_id'];
    $jumlah = $_POST['jumlah'];
    $durasi = $_POST['durasi'];
    $tujuan = $_POST['tujuan'];
    
    // Validasi input
    $errors = [];
    
    // Validate jumlah
    $query = "SELECT maksimal_jumlah, maksimal_durasi FROM jenis_kredit WHERE id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':id', $jenis_kredit_id);
    $stmt->execute();
    $jenis_kredit_data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($jumlah > $jenis_kredit_data['maksimal_jumlah']) {
        $errors[] = "Jumlah kredit melebihi batas maksimal (Rp " . number_format($jenis_kredit_data['maksimal_jumlah'], 0, ',', '.') . ")";
    }
    
    if ($durasi > $jenis_kredit_data['maksimal_durasi']) {
        $errors[] = "Durasi kredit melebihi batas maksimal (" . $jenis_kredit_data['maksimal_durasi'] . " bulan)";
    }
    
    if ($jumlah < 1000000) {
        $errors[] = "Jumlah kredit minimal Rp 1.000.000";
    }
    
    if ($durasi < 6) {
        $errors[] = "Durasi kredit minimal 6 bulan";
    }
    
    if (empty($tujuan)) {
        $errors[] = "Tujuan penggunaan harus diisi";
    }
    
    if (empty($errors)) {
        // Update pengajuan
        $query = "UPDATE pengajuan_kredit 
                  SET jenis_kredit_id = :jenis_kredit_id, 
                      jumlah = :jumlah, 
                      durasi = :durasi, 
                      tujuan = :tujuan,
                      tanggal_pengajuan = NOW() 
                  WHERE id = :id AND user_id = :user_id";
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':jenis_kredit_id', $jenis_kredit_id);
        $stmt->bindParam(':jumlah', $jumlah);
        $stmt->bindParam(':durasi', $durasi);
        $stmt->bindParam(':tujuan', $tujuan);
        $stmt->bindParam(':id', $pengajuan_id);
        $stmt->bindParam(':user_id', $user_id);
        
        if ($stmt->execute()) {
            $_SESSION['success'] = "Pengajuan kredit berhasil diperbarui! Status: <strong>Menunggu Persetujuan</strong>";
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Terjadi kesalahan saat memperbarui pengajuan. Silakan coba lagi.";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-edit me-2"></i>Edit Pengajuan Kredit
                        </h4>
                        <a href="dashboard.php" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Kembali
                        </a>
                    </div>
                </div>
                <div class="card-body p-4">
                    <!-- Current Pengajuan Info -->
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle me-2"></i>Informasi Pengajuan Saat Ini</h6>
                        <div class="row mt-2">
                            <div class="col-md-3">
                                <strong>Jenis Kredit:</strong><br>
                                <?php echo htmlspecialchars($pengajuan['nama_jenis']); ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Jumlah:</strong><br>
                                <?php echo formatRupiah($pengajuan['jumlah']); ?>
                            </div>
                            <div class="col-md-3">
                                <strong>Durasi:</strong><br>
                                <?php echo $pengajuan['durasi']; ?> bulan
                            </div>
                            <div class="col-md-3">
                                <strong>Status:</strong><br>
                                <span class="badge bg-warning">Pending</span>
                            </div>
                        </div>
                    </div>

                    <?php if(isset($error)): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?php echo $error; ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="editPengajuanForm">
                        <div class="mb-3">
                            <label for="jenis_kredit_id" class="form-label">Jenis Kredit *</label>
                            <select class="form-select" id="jenis_kredit_id" name="jenis_kredit_id" required>
                                <option value="">Pilih Jenis Kredit</option>
                                <?php foreach($jenis_kredit as $jk): ?>
                                    <option value="<?php echo $jk['id']; ?>" 
                                            data-max-amount="<?php echo $jk['maksimal_jumlah']; ?>"
                                            data-max-duration="<?php echo $jk['maksimal_durasi']; ?>"
                                            data-bunga="<?php echo $jk['bunga']; ?>"
                                            <?php echo ($pengajuan['jenis_kredit_id'] == $jk['id']) ? 'selected' : ''; ?>>
                                        <?php echo $jk['nama_jenis']; ?> 
                                        (Bunga: <?php echo $jk['bunga']; ?>%)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text" id="jenisKreditInfo">
                                Pilih jenis kredit untuk melihat detail persyaratan
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="jumlah" class="form-label">Jumlah Kredit (Rp) *</label>
                                    <input type="number" class="form-control" id="jumlah" name="jumlah" 
                                           value="<?php echo isset($_POST['jumlah']) ? $_POST['jumlah'] : $pengajuan['jumlah']; ?>" 
                                           required min="1000000" step="100000">
                                    <div class="form-text" id="maxAmountText">
                                        Maksimal: -
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="durasi" class="form-label">Durasi (Bulan) *</label>
                                    <input type="number" class="form-control" id="durasi" name="durasi" 
                                           value="<?php echo isset($_POST['durasi']) ? $_POST['durasi'] : $pengajuan['durasi']; ?>" 
                                           required min="6" max="240">
                                    <div class="form-text" id="maxDurationText">
                                        Maksimal: - bulan
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="tujuan" class="form-label">Tujuan Penggunaan Kredit *</label>
                            <textarea class="form-control" id="tujuan" name="tujuan" rows="4" 
                                      placeholder="Jelaskan secara detail untuk apa kredit ini akan digunakan..." 
                                      required><?php echo isset($_POST['tujuan']) ? $_POST['tujuan'] : htmlspecialchars($pengajuan['tujuan']); ?></textarea>
                            <div class="form-text">
                                Penjelasan yang jelas akan membantu proses persetujuan
                            </div>
                        </div>

                        <!-- Kalkulator Angsuran -->
                        <div class="card mb-4" id="kalkulatorSection">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-calculator me-2"></i>Kalkulator Angsuran</h6>
                            </div>
                            <div class="card-body">
                                <div class="row text-center">
                                    <div class="col-md-4">
                                        <strong>Jumlah Pinjaman:</strong><br>
                                        <span id="displayJumlah"><?php echo formatRupiah($pengajuan['jumlah']); ?></span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Bunga per Tahun:</strong><br>
                                        <span id="displayBunga"><?php echo $pengajuan['bunga']; ?></span>%
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Estimasi Angsuran/bulan:</strong><br>
                                        <span id="displayAngsuran" class="text-success fw-bold">
                                            <?php
                                            // Calculate current estimation
                                            $bungaPerBulan = $pengajuan['bunga'] / 100 / 12;
                                            $angsuran = $pengajuan['jumlah'] * ($bungaPerBulan * pow(1 + $bungaPerBulan, $pengajuan['durasi'])) / 
                                                       (pow(1 + $bungaPerBulan, $pengajuan['durasi']) - 1);
                                            echo formatRupiah(round($angsuran));
                                            ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <a href="dashboard.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Batal
                                </a>
                            </div>
                            <div>
                                <button type="button" class="btn btn-danger me-2" data-bs-toggle="modal" data-bs-target="#deleteModal">
                                    <i class="fas fa-trash me-2"></i>Hapus
                                </button>
                                <button type="submit" class="btn btn-warning">
                                    <i class="fas fa-save me-2"></i>Perbarui Pengajuan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Hapus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda yakin ingin menghapus pengajuan kredit ini?</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Tindakan ini tidak dapat dibatalkan!
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <a href="delete_pengajuan.php?id=<?php echo $pengajuan_id; ?>" class="btn btn-danger">
                    <i class="fas fa-trash me-2"></i>Ya, Hapus
                </a>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const jenisKreditSelect = document.getElementById('jenis_kredit_id');
    const jumlahInput = document.getElementById('jumlah');
    const durasiInput = document.getElementById('durasi');
    const maxAmountText = document.getElementById('maxAmountText');
    const maxDurationText = document.getElementById('maxDurationText');
    const kalkulatorSection = document.getElementById('kalkulatorSection');
    const displayJumlah = document.getElementById('displayJumlah');
    const displayBunga = document.getElementById('displayBunga');
    const displayAngsuran = document.getElementById('displayAngsuran');

    function updateInfo() {
        const selectedOption = jenisKreditSelect.options[jenisKreditSelect.selectedIndex];
        
        if (selectedOption.value) {
            const maxAmount = selectedOption.getAttribute('data-max-amount');
            const maxDuration = selectedOption.getAttribute('data-max-duration');
            const bunga = selectedOption.getAttribute('data-bunga');
            
            // Update max values and texts
            maxAmountText.textContent = 'Maksimal: Rp ' + parseInt(maxAmount).toLocaleString('id-ID');
            maxDurationText.textContent = 'Maksimal: ' + maxDuration + ' bulan';
            
            jumlahInput.max = maxAmount;
            durasiInput.max = maxDuration;
            
            // Update kalkulator
            updateKalkulator();
        }
    }

    function updateKalkulator() {
        const selectedOption = jenisKreditSelect.options[jenisKreditSelect.selectedIndex];
        const jumlah = parseFloat(jumlahInput.value) || 0;
        const durasi = parseInt(durasiInput.value) || 0;
        const bunga = parseFloat(selectedOption.getAttribute('data-bunga')) || 0;
        
        if (jumlah > 0 && durasi > 0 && bunga > 0) {
            // Hitung angsuran
            const bungaPerBulan = bunga / 100 / 12;
            const angsuran = jumlah * (bungaPerBulan * Math.pow(1 + bungaPerBulan, durasi)) / 
                            (Math.pow(1 + bungaPerBulan, durasi) - 1);
            
            displayJumlah.textContent = 'Rp ' + jumlah.toLocaleString('id-ID');
            displayBunga.textContent = bunga;
            displayAngsuran.textContent = 'Rp ' + Math.round(angsuran).toLocaleString('id-ID');
        }
    }

    // Event listeners
    jenisKreditSelect.addEventListener('change', updateInfo);
    jumlahInput.addEventListener('input', updateKalkulator);
    durasiInput.addEventListener('input', updateKalkulator);

    // Initialize
    updateInfo();
});

// Format number input
document.getElementById('jumlah').addEventListener('blur', function(e) {
    const value = parseFloat(e.target.value);
    if (!isNaN(value)) {
        e.target.value = value;
    }
});
</script>

<?php include 'includes/footer.php'; ?>