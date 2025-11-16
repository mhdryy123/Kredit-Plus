<?php
// pengajuan.php - FILE UNTUK USER
include 'config/database.php';
requireLogin();

$page_title = "Ajukan Kredit - Sistem Kredit";

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

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
    
    if (empty($errors)) {
        // Insert pengajuan
        $query = "INSERT INTO pengajuan_kredit (user_id, jenis_kredit_id, jumlah, durasi, tujuan) 
                  VALUES (:user_id, :jenis_kredit_id, :jumlah, :durasi, :tujuan)";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':jenis_kredit_id', $jenis_kredit_id);
        $stmt->bindParam(':jumlah', $jumlah);
        $stmt->bindParam(':durasi', $durasi);
        $stmt->bindParam(':tujuan', $tujuan);
        
        if ($stmt->execute()) {
            $success = "Pengajuan kredit berhasil dikirim! Status: <strong>Menunggu Persetujuan</strong>";
            // Reset form
            $_POST = [];
        } else {
            $error = "Terjadi kesalahan saat mengajukan kredit. Silakan coba lagi.";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Get user's recent applications
$query = "SELECT pk.*, jk.nama_jenis, jk.bunga 
          FROM pengajuan_kredit pk 
          JOIN jenis_kredit jk ON pk.jenis_kredit_id = jk.id 
          WHERE pk.user_id = :user_id 
          ORDER BY pk.tanggal_pengajuan DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();
$recent_pengajuan = $stmt->fetchAll(PDO::FETCH_ASSOC);

include 'includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-file-alt me-2"></i>Ajukan Kredit Baru</h4>
                </div>
                <div class="card-body p-4">
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

                    <form method="POST" id="pengajuanForm">
                        <div class="mb-3">
                            <label for="jenis_kredit_id" class="form-label">Jenis Kredit *</label>
                            <select class="form-select" id="jenis_kredit_id" name="jenis_kredit_id" required>
                                <option value="">Pilih Jenis Kredit</option>
                                <?php foreach($jenis_kredit as $jk): ?>
                                    <option value="<?php echo $jk['id']; ?>" 
                                            data-max-amount="<?php echo $jk['maksimal_jumlah']; ?>"
                                            data-max-duration="<?php echo $jk['maksimal_durasi']; ?>"
                                            data-bunga="<?php echo $jk['bunga']; ?>"
                                            <?php echo (isset($_POST['jenis_kredit_id']) && $_POST['jenis_kredit_id'] == $jk['id']) ? 'selected' : ''; ?>>
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
                                           value="<?php echo isset($_POST['jumlah']) ? $_POST['jumlah'] : ''; ?>" 
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
                                           value="<?php echo isset($_POST['durasi']) ? $_POST['durasi'] : ''; ?>" 
                                           required min="6" max="240">
                                    <div class="form-text" id="maxDurationText">
                                        Maksimal: - bulan
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="tujuan" class="form-label">Tujuan Penggunaan Kredit *</label>
                            <textarea class="form-control" id="tujuan" name="tujuan" rows="3" 
                                      placeholder="Jelaskan secara detail untuk apa kredit ini akan digunakan..." 
                                      required><?php echo isset($_POST['tujuan']) ? $_POST['tujuan'] : ''; ?></textarea>
                            <div class="form-text">
                                Penjelasan yang jelas akan membantu proses persetujuan
                            </div>
                        </div>

                        <!-- Kalkulator Angsuran -->
                        <div class="card mb-4" id="kalkulatorSection" style="display: none;">
                            <div class="card-header bg-light">
                                <h6 class="mb-0"><i class="fas fa-calculator me-2"></i>Kalkulator Angsuran</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <strong>Jumlah Pinjaman:</strong><br>
                                        <span id="displayJumlah">-</span>
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Bunga per Tahun:</strong><br>
                                        <span id="displayBunga">-</span>%
                                    </div>
                                    <div class="col-md-4">
                                        <strong>Estimasi Angsuran/bulan:</strong><br>
                                        <span id="displayAngsuran" class="text-success fw-bold">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <a href="dashboard.php" class="btn btn-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Kembali ke Dashboard
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Ajukan Kredit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Sidebar - Recent Applications & Info -->
        <div class="col-lg-4">
            <!-- Info Panel -->
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white">
                    <h6 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Pengajuan</h6>
                </div>
                <div class="card-body">
                    <div class="small">
                        <p><strong>Proses Pengajuan:</strong></p>
                        <ol class="ps-3">
                            <li>Isi formulir pengajuan</li>
                            <li>Submit untuk review</li>
                            <li>Admin akan memverifikasi</li>
                            <li>Status akan diupdate</li>
                            <li>Notifikasi via email</li>
                        </ol>
                        
                        <p><strong>Dokumen yang mungkin diperlukan:</strong></p>
                        <ul class="ps-3">
                            <li>KTP</li>
                            <li>Slip gaji/penghasilan</li>
                            <li>Rekening koran 3 bulan</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Recent Applications -->
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h6 class="mb-0"><i class="fas fa-history me-2"></i>Pengajuan Terakhir</h6>
                </div>
                <div class="card-body">
                    <?php if(count($recent_pengajuan) > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach($recent_pengajuan as $p): ?>
                            <div class="list-group-item px-0">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?php echo $p['nama_jenis']; ?></h6>
                                    <small>
                                        <span class="badge <?php echo getStatusBadge($p['status']); ?>">
                                            <?php echo ucfirst($p['status']); ?>
                                        </span>
                                    </small>
                                </div>
                                <p class="mb-1 small">
                                    <?php echo formatRupiah($p['jumlah']); ?> • 
                                    <?php echo $p['durasi']; ?> bulan
                                </p>
                                <small class="text-muted">
                                    <?php echo date('d/m/Y', strtotime($p['tanggal_pengajuan'])); ?>
                                </small>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-muted text-center mb-0">
                            <i class="fas fa-file-alt fa-2x mb-2 d-block"></i>
                            Belum ada pengajuan
                        </p>
                    <?php endif; ?>
                </div>
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
            kalkulatorSection.style.display = 'block';
        } else {
            maxAmountText.textContent = 'Maksimal: -';
            maxDurationText.textContent = 'Maksimal: - bulan';
            kalkulatorSection.style.display = 'none';
        }
    }

    function updateKalkulator() {
        const selectedOption = jenisKreditSelect.options[jenisKreditSelect.selectedIndex];
        const jumlah = parseFloat(jumlahInput.value) || 0;
        const durasi = parseInt(durasiInput.value) || 0;
        const bunga = parseFloat(selectedOption.getAttribute('data-bunga')) || 0;
        
        if (jumlah > 0 && durasi > 0 && bunga > 0) {
            // Hitung angsuran (sederhana)
            const bungaPerBulan = bunga / 100 / 12;
            const angsuran = jumlah * (bungaPerBulan * Math.pow(1 + bungaPerBulan, durasi)) / 
                            (Math.pow(1 + bungaPerBulan, durasi) - 1);
            
            displayJumlah.textContent = 'Rp ' + jumlah.toLocaleString('id-ID');
            displayBunga.textContent = bunga;
            displayAngsuran.textContent = 'Rp ' + Math.round(angsuran).toLocaleString('id-ID');
        } else {
            displayJumlah.textContent = '-';
            displayBunga.textContent = '-';
            displayAngsuran.textContent = '-';
        }
    }

    // Event listeners
    jenisKreditSelect.addEventListener('change', updateInfo);
    jumlahInput.addEventListener('input', updateKalkulator);
    durasiInput.addEventListener('input', updateKalkulator);

    // Initialize
    updateInfo();
});

// Format number input with commas
document.getElementById('jumlah').addEventListener('blur', function(e) {
    const value = parseFloat(e.target.value);
    if (!isNaN(value)) {
        e.target.value = value;
    }
});
</script>

<?php include 'includes/footer.php'; ?>