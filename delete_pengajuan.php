<?php
// delete_pengajuan.php - HAPUS PENGAJUAN UNTUK USER
include 'config/database.php';
requireLogin();

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
$query = "SELECT status FROM pengajuan_kredit WHERE id = :id AND user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $pengajuan_id);
$stmt->bindParam(':user_id', $user_id);
$stmt->execute();

if ($stmt->rowCount() == 0) {
    $_SESSION['error'] = "Pengajuan tidak ditemukan!";
    header("Location: dashboard.php");
    exit();
}

$pengajuan = $stmt->fetch(PDO::FETCH_ASSOC);

// Check if pengajuan can be deleted (only pending status)
if ($pengajuan['status'] != 'pending') {
    $_SESSION['error'] = "Pengajuan tidak dapat dihapus karena status sudah " . ucfirst($pengajuan['status']);
    header("Location: dashboard.php");
    exit();
}

// Delete pengajuan
$query = "DELETE FROM pengajuan_kredit WHERE id = :id AND user_id = :user_id";
$stmt = $db->prepare($query);
$stmt->bindParam(':id', $pengajuan_id);
$stmt->bindParam(':user_id', $user_id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Pengajuan kredit berhasil dihapus!";
} else {
    $_SESSION['error'] = "Terjadi kesalahan saat menghapus pengajuan.";
}

header("Location: dashboard.php");
exit();
?>