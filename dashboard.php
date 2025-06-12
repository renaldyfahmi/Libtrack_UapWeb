<?php
session_start();
require_once 'config/database.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$total_books_query = "SELECT COUNT(*) as total FROM books";
$total_stmt = $pdo->prepare($total_books_query);
$total_stmt->execute();
$total_books = $total_stmt->fetch(PDO::FETCH_ASSOC)['total'];

$recent_books_query = "SELECT * FROM books ORDER BY created_at DESC LIMIT 5";
$recent_stmt = $pdo->prepare($recent_books_query);
$recent_stmt->execute();
$recent_books = $recent_stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Libtrack</title>
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
        }
        .sidebar .nav-link:hover,
        .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255,255,255,0.1);
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <nav class="col-md-3 col-lg-2 d-md-block sidebar collapse">
                <div class="position-sticky pt-3">
                    <div class="text-center mb-4">
                        <h4 class="text-white">
                            <i class="fas fa-book"></i> Libtrack
                        </h4>
                        <small class="text-white-50">Perpustakaan Mini</small>
                    </div>
                    
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="books.php">
                                <i class="fas fa-book"></i> Kelola Buku
                            </a>
                        </li>
                    </ul>
                    
                    <hr class="text-white-50">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link text-white-50" href="logout.php">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>

           
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <span class="badge bg-info">Selamat datang, <?php echo $_SESSION['full_name']; ?>!</span>
                    </div>
                </div>

                
                <div class="row mb-4">
                    <div class="col-md-4">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <div class="d-flex justify-content-between">
                                    <div>
                                        <h4><?php echo $total_books; ?></h4>
                                        <small>Total Buku</small>
                                    </div>
                                    <i class="fas fa-book fa-2x opacity-50"></i>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="books.php" class="text-white text-decoration-none">
                                    <small>Lihat semua <i class="fas fa-arrow-right"></i></small>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5><i class="fas fa-clock"></i> Buku Terbaru</h5>
                        <a href="books.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
                    </div>
                    <div class="card-body">
                        <?php if (count($recent_books) > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Judul</th>
                                            <th>Penulis</th>
                                            <th>Tahun</th>
                                            <th>Ditambahkan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_books as $book): ?>
                                            <tr>
                                                <td><strong><?php echo $book['title']; ?></strong></td>
                                                <td><?php echo $book['author']; ?></td>
                                                <td><?php echo $book['year']; ?></td>
                                                <td><?php echo formatDate($book['created_at']); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted">
                                <i class="fas fa-book fa-3x mb-3"></i>
                                <p>Belum ada buku</p>
                                <a href="books.php" class="btn btn-primary">Tambah Buku Pertama</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>