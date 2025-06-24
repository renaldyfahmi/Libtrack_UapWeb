<?php
session_start();
require_once __DIR__ . '/../config/database.php';

// Authentication functions
function requireAdmin() {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
        header("Location: ../auth/login.php");
        exit();
    }
}

function requireMember() {
    if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'member') {
        header("Location: ../auth/login.php");
        exit();
    }
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserType() {
    return $_SESSION['user_type'] ?? null;
}

$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Perpustakaan Mini</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .sidebar {
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            border-radius: 8px;
            margin: 2px 0;
            transition: all 0.3s;
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
            transform: translateX(5px);
        }
        .main-content {
            background-color: #f8f9fa;
            min-height: 100vh;
        }
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card {
            border: none;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-radius: 10px;
        }
        .btn {
            border-radius: 8px;
        }
        .table {
            border-radius: 10px;
            overflow: hidden;
        }
        .badge {
            border-radius: 6px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <?php if (isLoggedIn()): ?>
                <!-- Sidebar -->
                <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                    <div class="position-sticky pt-3">
                        <div class="text-center mb-4">
                            <h4 class="text-white">
                                <i class="fas fa-book"></i> Perpustakaan
                            </h4>
                            <small class="text-white-50">Mini Library System</small>
                        </div>
                        
                        <ul class="nav flex-column">
                            <?php if (getUserType() === 'admin'): ?>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="../admin/dashboard.php">
                                        <i class="fas fa-tachometer-alt"></i> Dashboard
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $current_page === 'books.php' ? 'active' : ''; ?>" href="../admin/books.php">
                                        <i class="fas fa-book"></i> Kelola Buku
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $current_page === 'categories.php' ? 'active' : ''; ?>" href="../admin/categories.php">
                                        <i class="fas fa-tags"></i> Kelola Kategori
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $current_page === 'members.php' ? 'active' : ''; ?>" href="../admin/members.php">
                                        <i class="fas fa-users"></i> Kelola Anggota
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $current_page === 'loans.php' ? 'active' : ''; ?>" href="../admin/loans.php">
                                        <i class="fas fa-handshake"></i> Kelola Peminjaman
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $current_page === 'reports.php' ? 'active' : ''; ?>" href="../admin/reports.php">
                                        <i class="fas fa-chart-bar"></i> Laporan
                                    </a>
                                </li>
                            <?php else: ?>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>" href="../member/dashboard.php">
                                        <i class="fas fa-tachometer-alt"></i> Dashboard
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $current_page === 'browse.php' ? 'active' : ''; ?>" href="../member/browse.php">
                                        <i class="fas fa-search"></i> Cari Buku
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $current_page === 'my-loans.php' ? 'active' : ''; ?>" href="../member/my-loans.php">
                                        <i class="fas fa-history"></i> Riwayat Peminjaman
                                    </a>
                                </li>
                                <li class="nav-item">
                                    <a class="nav-link <?php echo $current_page === 'profile.php' ? 'active' : ''; ?>" href="../member/profile.php">
                                        <i class="fas fa-user"></i> Profil Saya
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                        
                        <hr class="text-white-50">
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link text-white-50" href="../auth/logout.php">
                                    <i class="fas fa-sign-out-alt"></i> Logout
                                </a>
                            </li>
                        </ul>
                    </div>
                </nav>

                <!-- Main content -->
                <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 main-content">
                    <!-- Top navbar -->
                    <nav class="navbar navbar-expand-lg navbar-light bg-white mb-4">
                        <div class="container-fluid">
                            <button class="navbar-toggler d-md-none" type="button" data-bs-toggle="collapse" data-bs-target="#sidebarMenu">
                                <span class="navbar-toggler-icon"></span>
                            </button>
                            <div class="navbar-nav ms-auto">
                                <div class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                                        <i class="fas fa-user-circle"></i> <?php echo $_SESSION['full_name']; ?>
                                    </a>
                                    <ul class="dropdown-menu">
                                        <?php if (getUserType() === 'member'): ?>
                                            <li><a class="dropdown-item" href="../member/profile.php"><i class="fas fa-user"></i> Profil</a></li>
                                        <?php endif; ?>
                                        <li><hr class="dropdown-divider"></li>
                                        <li><a class="dropdown-item" href="../auth/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </nav>

                    <!-- Page content -->
                    <div class="container-fluid">
            <?php else: ?>
                <!-- Login page layout -->
                <div class="col-12">
            <?php endif; ?>