<?php
$page_title = "Laporan";
require_once '../config/database.php';
require_once '../includes/header.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();

// Get date range from form
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-01');
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');
$report_type = isset($_GET['report_type']) ? $_GET['report_type'] : 'summary';

// Summary Statistics
$stats = [];

// Total loans in period
$query = "SELECT COUNT(*) as total FROM loans WHERE DATE(created_at) BETWEEN :start_date AND :end_date";
$stmt = $db->prepare($query);
$stmt->bindParam(":start_date", $start_date);
$stmt->bindParam(":end_date", $end_date);
$stmt->execute();
$stats['total_loans'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Approved loans
$query = "SELECT COUNT(*) as total FROM loans WHERE status = 'approved' AND DATE(loan_date) BETWEEN :start_date AND :end_date";
$stmt = $db->prepare($query);
$stmt->bindParam(":start_date", $start_date);
$stmt->bindParam(":end_date", $end_date);
$stmt->execute();
$stats['approved_loans'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Returned books
$query = "SELECT COUNT(*) as total FROM loans WHERE status = 'returned' AND DATE(return_date) BETWEEN :start_date AND :end_date";
$stmt = $db->prepare($query);
$stmt->bindParam(":start_date", $start_date);
$stmt->bindParam(":end_date", $end_date);
$stmt->execute();
$stats['returned_books'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Overdue books
$query = "SELECT COUNT(*) as total FROM loans WHERE status = 'approved' AND due_date < CURDATE()";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['overdue_books'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Most borrowed books
$query = "SELECT b.title, b.author, b.code, COUNT(l.id) as loan_count
          FROM books b 
          JOIN loans l ON b.id = l.book_id 
          WHERE DATE(l.created_at) BETWEEN :start_date AND :end_date
          GROUP BY b.id 
          ORDER BY loan_count DESC 
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->bindParam(":start_date", $start_date);
$stmt->bindParam(":end_date", $end_date);
$stmt->execute();
$popular_books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Most active members
$query = "SELECT m.full_name, m.member_code, COUNT(l.id) as loan_count
          FROM members m 
          JOIN loans l ON m.id = l.member_id 
          WHERE DATE(l.created_at) BETWEEN :start_date AND :end_date
          GROUP BY m.id 
          ORDER BY loan_count DESC 
          LIMIT 10";
$stmt = $db->prepare($query);
$stmt->bindParam(":start_date", $start_date);
$stmt->bindParam(":end_date", $end_date);
$stmt->execute();
$active_members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Daily loan statistics
$query = "SELECT DATE(created_at) as loan_date, COUNT(*) as count
          FROM loans 
          WHERE DATE(created_at) BETWEEN :start_date AND :end_date
          GROUP BY DATE(created_at) 
          ORDER BY loan_date DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(":start_date", $start_date);
$stmt->bindParam(":end_date", $end_date);
$stmt->execute();
$daily_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Category statistics
$query = "SELECT c.name as category_name, COUNT(l.id) as loan_count
          FROM categories c 
          JOIN books b ON c.id = b.category_id
          JOIN loans l ON b.id = l.book_id 
          WHERE DATE(l.created_at) BETWEEN :start_date AND :end_date
          GROUP BY c.id 
          ORDER BY loan_count DESC";
$stmt = $db->prepare($query);
$stmt->bindParam(":start_date", $start_date);
$stmt->bindParam(":end_date", $end_date);
$stmt->execute();
$category_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center border-bottom mb-4">
    <h1 class="h2"><i class="fas fa-chart-bar"></i> Laporan Perpustakaan</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <button type="button" class="btn btn-outline-primary" onclick="window.print()">
            <i class="fas fa-print"></i> Cetak Laporan
        </button>
    </div>
</div>

<!-- Filter Form -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-3">
                <label for="start_date" class="form-label">Tanggal Mulai</label>
                <input type="date" class="form-control" name="start_date" value="<?php echo $start_date; ?>">
            </div>
            <div class="col-md-3">
                <label for="end_date" class="form-label">Tanggal Akhir</label>
                <input type="date" class="form-control" name="end_date" value="<?php echo $end_date; ?>">
            </div>
            <div class="col-md-3">
                <label for="report_type" class="form-label">Jenis Laporan</label>
                <select class="form-select" name="report_type">
                    <option value="summary" <?php echo $report_type === 'summary' ? 'selected' : ''; ?>>Ringkasan</option>
                    <option value="detailed" <?php echo $report_type === 'detailed' ? 'selected' : ''; ?>>Detail</option>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label">&nbsp;</label>
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i> Generate Laporan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Summary Statistics -->
<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $stats['total_loans']; ?></h4>
                        <small>Total Peminjaman</small>
                    </div>
                    <i class="fas fa-handshake fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $stats['approved_loans']; ?></h4>
                        <small>Disetujui</small>
                    </div>
                    <i class="fas fa-check fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $stats['returned_books']; ?></h4>
                        <small>Dikembalikan</small>
                    </div>
                    <i class="fas fa-undo fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $stats['overdue_books']; ?></h4>
                        <small>Terlambat</small>
                    </div>
                    <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Popular Books -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-star"></i> Buku Terpopuler</h5>
                <small class="text-muted">Periode: <?php echo formatDate($start_date); ?> - <?php echo formatDate($end_date); ?></small>
            </div>
            <div class="card-body">
                <?php if (count($popular_books) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Buku</th>
                                    <th>Penulis</th>
                                    <th>Dipinjam</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; foreach ($popular_books as $book): ?>
                                    <tr>
                                        <td><?php echo $rank++; ?></td>
                                        <td>
                                            <strong><?php echo $book['title']; ?></strong><br>
                                            <small class="text-muted"><?php echo $book['code']; ?></small>
                                        </td>
                                        <td><?php echo $book['author']; ?></td>
                                        <td><span class="badge bg-primary"><?php echo $book['loan_count']; ?>x</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted">
                        <i class="fas fa-book fa-2x mb-2"></i>
                        <p>Tidak ada data peminjaman</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Active Members -->
    <div class="col-md-6 mb-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-users"></i> Anggota Teraktif</h5>
                <small class="text-muted">Periode: <?php echo formatDate($start_date); ?> - <?php echo formatDate($end_date); ?></small>
            </div>
            <div class="card-body">
                <?php if (count($active_members) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Rank</th>
                                    <th>Anggota</th>
                                    <th>Kode</th>
                                    <th>Peminjaman</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php $rank = 1; foreach ($active_members as $member): ?>
                                    <tr>
                                        <td><?php echo $rank++; ?></td>
                                        <td><?php echo $member['full_name']; ?></td>
                                        <td><?php echo $member['member_code']; ?></td>
                                        <td><span class="badge bg-success"><?php echo $member['loan_count']; ?>x</span></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted">
                        <i class="fas fa-users fa-2x mb-2"></i>
                        <p>Tidak ada data peminjaman</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Daily Statistics -->
    <div class="col-md-8 mb-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-calendar"></i> Statistik Harian</h5>
            </div>
            <div class="card-body">
                <?php if (count($daily_stats) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Jumlah Peminjaman</th>
                                    <th>Grafik</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $max_count = max(array_column($daily_stats, 'count'));
                                foreach ($daily_stats as $stat): 
                                    $percentage = $max_count > 0 ? ($stat['count'] / $max_count) * 100 : 0;
                                ?>
                                    <tr>
                                        <td><?php echo formatDate($stat['loan_date']); ?></td>
                                        <td><span class="badge bg-info"><?php echo $stat['count']; ?></span></td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <div class="progress-bar" role="progressbar" 
                                                     style="width: <?php echo $percentage; ?>%" 
                                                     aria-valuenow="<?php echo $stat['count']; ?>" 
                                                     aria-valuemin="0" aria-valuemax="<?php echo $max_count; ?>">
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted">
                        <i class="fas fa-calendar fa-2x mb-2"></i>
                        <p>Tidak ada data untuk periode ini</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Category Statistics -->
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-tags"></i> Statistik Kategori</h5>
            </div>
            <div class="card-body">
                <?php if (count($category_stats) > 0): ?>
                    <?php foreach ($category_stats as $category): ?>
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span><?php echo $category['category_name']; ?></span>
                                <span class="badge bg-primary"><?php echo $category['loan_count']; ?></span>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar" role="progressbar" 
                                     style="width: <?php echo ($category['loan_count'] / $stats['total_loans']) * 100; ?>%">
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted">
                        <i class="fas fa-tags fa-2x mb-2"></i>
                        <p>Tidak ada data kategori</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Print Styles -->
<style>
@media print {
    .btn-toolbar, .card-header .btn, .sidebar, .navbar {
        display: none !important;
    }
    .main-content {
        margin: 0 !important;
        padding: 0 !important;
    }
    .card {
        border: 1px solid #000 !important;
        box-shadow: none !important;
    }
}
</style>

<?php require_once '../includes/footer.php'; ?>