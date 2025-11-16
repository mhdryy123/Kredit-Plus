<?php
// includes/header.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title : 'Sistem Kredit'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --success: #27ae60;
            --warning: #f39c12;
            --danger: #e74c3c;
        }
        
        .navbar-brand {
            font-weight: 700;
        }
        
        .sidebar {
            min-height: calc(100vh - 56px);
            background: var(--primary);
        }
        
        .sidebar .nav-link {
            color: #fff;
            padding: 12px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.1);
        }
        
        .sidebar .nav-link.active {
            background: var(--secondary);
        }
        
        .stat-card {
            border-left: 4px solid var(--secondary);
            transition: transform 0.2s;
        }
        
        .stat-card:hover {
            transform: translateY(-2px);
        }
        
        .user-role-badge {
            font-size: 0.7em;
            margin-left: 5px;
        }
        
        .navbar-profile-name {
            max-width: 150px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            display: inline-block;
            vertical-align: middle;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-hand-holding-usd me-2"></i>
                KreditPlus
            </a>
            
            <div class="navbar-nav ms-auto">
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a class="nav-link" href="dashboard.php">Dashboard</a>
                    
                    <!-- Dropdown Profile untuk Admin dan User -->
                    <div class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-user-circle me-1"></i>
                            <span class="navbar-profile-name">
                                <?php 
                                // Tampilkan hanya nama depan atau username saja
                                $nama = $_SESSION['nama'];
                                $nama_parts = explode(' ', $nama);
                                echo htmlspecialchars($nama_parts[0]); // Hanya ambil kata pertama
                                ?>
                            </span>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li class="dropdown-header text-muted small">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user me-2"></i>
                                    <div>
                                        <div class="fw-bold"><?php echo htmlspecialchars($_SESSION['nama']); ?></div>
                                        <small>
                                            <?php if($_SESSION['role'] == 'admin'): ?>
                                                <span class="badge bg-warning">Admin</span>
                                            <?php else: ?>
                                                <span class="badge bg-info">User</span>
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            
                            <?php if($_SESSION['role'] == 'admin'): ?>
                            
                            <?php endif; ?>
                            <li><hr class="dropdown-divider my-1"></li>
                            <li>
                                <a class="dropdown-item text-danger" href="logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a class="nav-link" href="login.php">Login</a>
                    <a class="nav-link" href="register.php">Daftar</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>