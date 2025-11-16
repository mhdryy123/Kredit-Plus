<?php
// config/database.php
class Database {
    private $host = "localhost";
    private $db_name = "sistem_kredit";
    private $username = "root";
    private $password = "";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8", 
                $this->username, 
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        return $this->conn;
    }
}

// Session start
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Helper functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: dashboard.php");
        exit();
    }
}

function formatRupiah($angka) {
    if ($angka == 0 || $angka == '') {
        return 'Rp 0';
    }
    return 'Rp ' . number_format($angka, 0, ',', '.');
}

function getStatusBadge($status) {
    $badges = array(
        'pending' => 'bg-warning',
        'approved' => 'bg-success',
        'rejected' => 'bg-danger',
        'completed' => 'bg-info'
    );
    
    // Menggunakan ternary operator sebagai pengganti ??
    if (array_key_exists($status, $badges)) {
        return $badges[$status];
    } else {
        return 'bg-secondary';
    }
}

// Additional helper functions
function redirect($url) {
    header("Location: " . $url);
    exit();
}

function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function showAlert($message, $type = 'info') {
    $alertClass = '';
    switch ($type) {
        case 'success':
            $alertClass = 'alert-success';
            break;
        case 'error':
        case 'danger':
            $alertClass = 'alert-danger';
            break;
        case 'warning':
            $alertClass = 'alert-warning';
            break;
        default:
            $alertClass = 'alert-info';
    }
    
    return '<div class="alert ' . $alertClass . ' alert-dismissible fade show" role="alert">
                ' . $message . '
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>';
}

// Check if database exists and create tables if not
function initializeDatabase() {
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        // Check if users table exists
        $checkTable = $db->query("SHOW TABLES LIKE 'users'");
        if ($checkTable->rowCount() == 0) {
            createDatabaseTables($db);
        }
    } catch (Exception $e) {
        // If database doesn't exist, create it
        createDatabaseAndTables();
    }
}

function createDatabaseAndTables() {
    $temp_conn = new PDO("mysql:host=localhost", "root", "");
    $temp_conn->exec("CREATE DATABASE IF NOT EXISTS sistem_kredit CHARACTER SET utf8 COLLATE utf8_general_ci");
    $temp_conn = null;
    
    $database = new Database();
    $db = $database->getConnection();
    createDatabaseTables($db);
}

function createDatabaseTables($db) {
    // SQL untuk membuat tabel
    $sql = array();
    
    // Table users
    $sql[] = "CREATE TABLE IF NOT EXISTS users (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nama_lengkap VARCHAR(100) NOT NULL,
        email VARCHAR(100) UNIQUE NOT NULL,
        password VARCHAR(255) NOT NULL,
        telepon VARCHAR(20),
        alamat TEXT,
        role ENUM('admin','user') DEFAULT 'user',
        foto_profil VARCHAR(255),
        tanggal_daftar DATETIME DEFAULT CURRENT_TIMESTAMP,
        status ENUM('active','inactive') DEFAULT 'active'
    )";
    
    // Table jenis_kredit
    $sql[] = "CREATE TABLE IF NOT EXISTS jenis_kredit (
        id INT PRIMARY KEY AUTO_INCREMENT,
        nama_jenis VARCHAR(100) NOT NULL,
        deskripsi TEXT,
        bunga DECIMAL(5,2) NOT NULL,
        maksimal_durasi INT NOT NULL,
        maksimal_jumlah DECIMAL(15,2) NOT NULL,
        syarat TEXT,
        status ENUM('active','inactive') DEFAULT 'active'
    )";
    
    // Table pengajuan_kredit
    $sql[] = "CREATE TABLE IF NOT EXISTS pengajuan_kredit (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        jenis_kredit_id INT NOT NULL,
        jumlah DECIMAL(15,2) NOT NULL,
        durasi INT NOT NULL,
        tujuan TEXT,
        status ENUM('pending','approved','rejected','completed') DEFAULT 'pending',
        tanggal_pengajuan DATETIME DEFAULT CURRENT_TIMESTAMP,
        tanggal_approval DATETIME NULL,
        admin_id INT NULL,
        catatan_admin TEXT,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (jenis_kredit_id) REFERENCES jenis_kredit(id),
        FOREIGN KEY (admin_id) REFERENCES users(id)
    )";
    
    // Table cicilan
    $sql[] = "CREATE TABLE IF NOT EXISTS cicilan (
        id INT PRIMARY KEY AUTO_INCREMENT,
        pengajuan_kredit_id INT NOT NULL,
        angsuran_ke INT NOT NULL,
        jumlah DECIMAL(15,2) NOT NULL,
        bunga DECIMAL(15,2) NOT NULL,
        total_bayar DECIMAL(15,2) NOT NULL,
        tanggal_jatuh_tempo DATE NOT NULL,
        tanggal_bayar DATETIME NULL,
        status ENUM('pending','paid','late') DEFAULT 'pending',
        denda DECIMAL(15,2) DEFAULT 0,
        FOREIGN KEY (pengajuan_kredit_id) REFERENCES pengajuan_kredit(id)
    )";
    
    // Table transaksi
    $sql[] = "CREATE TABLE IF NOT EXISTS transaksi (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        jenis ENUM('pembayaran','penarikan','topup') NOT NULL,
        jumlah DECIMAL(15,2) NOT NULL,
        keterangan TEXT,
        metode_pembayaran VARCHAR(50),
        status ENUM('pending','success','failed') DEFAULT 'pending',
        tanggal_transaksi DATETIME DEFAULT CURRENT_TIMESTAMP,
        bukti_pembayaran VARCHAR(255),
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    
    // Table notifikasi
    $sql[] = "CREATE TABLE IF NOT EXISTS notifikasi (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        judul VARCHAR(255) NOT NULL,
        pesan TEXT NOT NULL,
        jenis ENUM('info','warning','success','danger') DEFAULT 'info',
        dibaca BOOLEAN DEFAULT FALSE,
        tanggal_dibuat DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id)
    )";
    
    // Execute all SQL queries
    foreach ($sql as $query) {
        try {
            $db->exec($query);
        } catch (PDOException $e) {
            // Skip error if table already exists
            if (strpos($e->getMessage(), 'already exists') === false) {
                throw $e;
            }
        }
    }
    
    // Insert default data
    insertDefaultData($db);
}

function insertDefaultData($db) {
    // Insert default admin user
    $checkAdmin = $db->query("SELECT COUNT(*) FROM users WHERE email = 'admin@sistemkredit.com'");
    if ($checkAdmin->fetchColumn() == 0) {
        $db->exec("INSERT INTO users (nama_lengkap, email, password, telepon, role) 
                  VALUES ('Administrator', 'admin@sistemkredit.com', 'admin123', '081234567890', 'admin')");
    }
    
    // Insert default user for demo
    $checkUser = $db->query("SELECT COUNT(*) FROM users WHERE email = 'user@demo.com'");
    if ($checkUser->fetchColumn() == 0) {
        $db->exec("INSERT INTO users (nama_lengkap, email, password, telepon, alamat) 
                  VALUES ('User Demo', 'user@demo.com', 'user123', '081234567891', 'Alamat demo user')");
    }
    
    // Insert jenis kredit
    $checkJenis = $db->query("SELECT COUNT(*) FROM jenis_kredit");
    if ($checkJenis->fetchColumn() == 0) {
        $jenisKredit = array(
            array('Kredit Pemilikan Rumah', 'Kredit untuk pembelian rumah pertama atau kedua', 8.5, 240, 5000000000, 'Usia 21-55 tahun, penghasilan minimal Rp 5.000.000/bulan'),
            array('Kredit Kendaraan Bermotor', 'Kredit untuk pembelian mobil atau motor baru', 7.5, 60, 500000000, 'Usia 21-60 tahun, penghasilan minimal Rp 3.000.000/bulan'),
            array('Kredit Multiguna', 'Kredit untuk berbagai keperluan dengan agunan', 10.5, 36, 100000000, 'Memiliki agunan properti atau kendaraan'),
            array('Kredit Mikro', 'Kredit untuk usaha kecil dengan proses cepat', 12.0, 24, 50000000, 'Memiliki usaha minimal 6 bulan, penghasilan stabil'),
            array('Kredit Pendidikan', 'Kredit untuk biaya pendidikan formal', 6.5, 48, 200000000, 'Siswa/mahasiswa aktif, memiliki penjamin')
        );
        
        $stmt = $db->prepare("INSERT INTO jenis_kredit (nama_jenis, deskripsi, bunga, maksimal_durasi, maksimal_jumlah, syarat) 
                             VALUES (?, ?, ?, ?, ?, ?)");
        
        foreach ($jenisKredit as $data) {
            $stmt->execute($data);
        }
    }
}

// Initialize database when this file is included
initializeDatabase();
?>