<?php
// admin/laporan.php
include '../config/database.php';
requireAdmin();

$page_title = "Laporan - Admin";

$database = new Database();
$db = $database->getConnection();

// Get report data
$queries = [
    'total_kredit' => "SELECT COALESCE(SUM(jumlah), 0) as total FROM pengajuan_kredit WHERE status = 'approved'",
    'total_pengajuan_bulan' => "SELECT COUNT(*) as total FROM pengajuan_kredit WHERE MONTH(tanggal_pengajuan) = MONTH(CURRENT_DATE())",
    'kredit_per_jenis' => "SELECT jk.nama_jenis, COUNT(pk.id) as jumlah, COALESCE(SUM(pk.jumlah), 0) as total 
                          FROM jenis_kredit jk 
                          LEFT JOIN pengajuan_kredit pk ON jk.id = pk.jenis_kredit_id AND pk.status = 'approved'
                          GROUP BY jk.id, jk.nama_jenis",
    'status_distribution' => "SELECT status, COUNT(*) as jumlah FROM pengajuan_kredit GROUP BY status"
];

$reports = [];
foreach ($queries as $key => $query) {
    $stmt = $db->prepare($query);
    $stmt->execute();
    $reports[$key] = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
                <a href="laporan.php" class="nav-link active">
                    <i class="fas fa-chart-bar me-2"></i>Laporan
                </a>
                
            </div>
        </div>

        <!-- Main Content -->
        <div class="col-md-9 col-lg-10 ms-sm-auto px-4">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                <h1 class="h2">Laporan & Analytics</h1>
                <div class="btn-toolbar mb-2 mb-md-0">
                    <button class="btn btn-primary" onclick="window.print()">
                        <i class="fas fa-print me-2"></i>Print Laporan
                    </button>
                </div>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Total Kredit Disetujui</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo formatRupiah($reports['total_kredit'][0]['total']); ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
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
                                        Pengajuan Bulan Ini</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">
                                        <?php echo $reports['total_pengajuan_bulan'][0]['total']; ?>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts and Reports -->
            <div class="row">
                <!-- Kredit per Jenis -->
                <div class="col-xl-6 col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Kredit per Jenis</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Jenis Kredit</th>
                                            <th>Jumlah Pengajuan</th>
                                            <th>Total Nilai</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach($reports['kredit_per_jenis'] as $report): ?>
                                        <tr>
                                            <td><?php echo $report['nama_jenis']; ?></td>
                                            <td><?php echo $report['jumlah']; ?></td>
                                            <td><?php echo formatRupiah($report['total']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Status Distribution -->
                <div class="col-xl-6 col-lg-6">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Distribusi Status Pengajuan</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Status</th>
                                            <th>Jumlah</th>
                                            <th>Persentase</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $total = array_sum(array_column($reports['status_distribution'], 'jumlah'));
                                        foreach($reports['status_distribution'] as $report): 
                                            $percentage = $total > 0 ? ($report['jumlah'] / $total) * 100 : 0;
                                        ?>
                                        <tr>
                                            <td>
                                                <span class="badge <?php echo getStatusBadge($report['status']); ?>">
                                                    <?php echo ucfirst($report['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $report['jumlah']; ?></td>
                                            <td>
                                                <div class="progress">
                                                    <div class="progress-bar" role="progressbar" 
                                                         style="width: <?php echo $percentage; ?>%">
                                                        <?php echo round($percentage, 1); ?>%
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

            <!-- Detailed Report -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Laporan Detail Pengajuan</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" id="dataTable">
                                    <thead>
                                        <tr>
                                            <th>Nama User</th>
                                            <th>Jenis Kredit</th>
                                            <th>Jumlah</th>
                                            <th>Durasi</th>
                                            <th>Tanggal Pengajuan</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $query = "SELECT pk.*, u.nama_lengkap, jk.nama_jenis 
                                                 FROM pengajuan_kredit pk 
                                                 JOIN users u ON pk.user_id = u.id 
                                                 JOIN jenis_kredit jk ON pk.jenis_kredit_id = jk.id 
                                                 ORDER BY pk.tanggal_pengajuan DESC";
                                        $stmt = $db->prepare($query);
                                        $stmt->execute();
                                        $all_pengajuan = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                        
                                        foreach($all_pengajuan as $p): 
                                        ?>
                                        <tr>
                                            <td><?php echo $p['nama_lengkap']; ?></td>
                                            <td><?php echo $p['nama_jenis']; ?></td>
                                            <td><?php echo formatRupiah($p['jumlah']); ?></td>
                                            <td><?php echo $p['durasi']; ?> bulan</td>
                                            <td><?php echo date('d/m/Y', strtotime($p['tanggal_pengajuan'])); ?></td>
                                            <td>
                                                <span class="badge <?php echo getStatusBadge($p['status']); ?>">
                                                    <?php echo ucfirst($p['status']); ?>
                                                </span>
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
    </div>
</div>

<?php include '../includes/footer.php'; ?>