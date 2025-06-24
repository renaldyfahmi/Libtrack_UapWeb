<?php
$page_title = "Dashboard Admin";
require_once '../includes/header.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();

// Get statistics
$stats = [];

// Total books
$query = "SELECT COUNT(*) as total FROM books";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_books'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Available books
$query = "SELECT SUM(available_stock) as total FROM books";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['available_books'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'] ?: 0;

// Total members
$query = "SELECT COUNT(*) as total FROM members WHERE status = 'active'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['total_members'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Pending loans
$query = "SELECT COUNT(*) as total FROM loans WHERE status = 'pending'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['pending_loans'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Active loans
$query = "SELECT COUNT(*) as total FROM loans WHERE status = 'approved'";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['active_loans'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Overdue loans
$query = "SELECT COUNT(*) as total FROM loans WHERE status = 'approved' AND due_date < CURDATE()";
$stmt = $db->prepare($query);
$stmt->execute();
$stats['overdue_loans'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

// Recent loans
$query = "SELECT l.*, m.full_name as member_name, b.title as book_title 
          FROM loans l 
          JOIN members m ON l.member_id = m.id 
          JOIN books b ON l.book_id = b.id 
          ORDER BY l.created_at DESC 
          LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$recent_loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Low stock books
$query = "SELECT * FROM books WHERE available_stock <= 1 ORDER BY available_stock ASC LIMIT 5";
$stmt = $db->prepare($query);
$stmt->execute();
$low_stock_books = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center border-bottom mb-4">
    <h1 class="h2"><i class="fas fa-tachometer-alt"></i> Dashboard Admin</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <span class="badge bg-info">Selamat datang, <?php echo $_SESSION['full_name']; ?>!</span>
    </div>
</div>

<!-- Statistics Cards -->
<div class="row mb-4">
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $stats['total_books']; ?></h4>
                        <small>Total Buku</small>
                    </div>
                    <i class="fas fa-book fa-2x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer">
                <a href="books.php" class="text-white text-decoration-none">
                    <small>Lihat detail <i class="fas fa-arrow-right"></i></small>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-success">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $stats['available_books']; ?></h4>
                        <small>Buku Tersedia</small>
                    </div>
                    <i class="fas fa-check-circle fa-2x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer">
                <a href="books.php" class="text-white text-decoration-none">
                    <small>Lihat detail <i class="fas fa-arrow-right"></i></small>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-info">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $stats['total_members']; ?></h4>
                        <small>Anggota Aktif</small>
                    </div>
                    <i class="fas fa-users fa-2x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer">
                <a href="members.php" class="text-white text-decoration-none">
                    <small>Lihat detail <i class="fas fa-arrow-right"></i></small>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-3 mb-3">
        <div class="card text-white bg-warning">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $stats['pending_loans']; ?></h4>
                        <small>Menunggu Persetujuan</small>
                    </div>
                    <i class="fas fa-clock fa-2x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer">
                <a href="loans.php?status=pending" class="text-white text-decoration-none">
                    <small>Lihat detail <i class="fas fa-arrow-right"></i></small>
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Additional Stats -->
<div class="row mb-4">
    <div class="col-md-6">
        <div class="card text-white bg-secondary">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $stats['active_loans']; ?></h4>
                        <small>Peminjaman Aktif</small>
                    </div>
                    <i class="fas fa-handshake fa-2x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer">
                <a href="loans.php?status=approved" class="text-white text-decoration-none">
                    <small>Lihat detail <i class="fas fa-arrow-right"></i></small>
                </a>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card text-white bg-danger">
            <div class="card-body">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $stats['overdue_loans']; ?></h4>
                        <small>Peminjaman Terlambat</small>
                    </div>
                    <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                </div>
            </div>
            <div class="card-footer">
                <a href="loans.php" class="text-white text-decoration-none">
                    <small>Lihat detail <i class="fas fa-arrow-right"></i></small>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Recent Loans -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-history"></i> Peminjaman Terbaru</h5>
                <a href="loans.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
            </div>
            <div class="card-body">
                <?php if (count($recent_loans) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Anggota</th>
                                    <th>Buku</th>
                                    <th>Tanggal</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recent_loans as $loan): ?>
                                    <tr>
                                        <td><?php echo $loan['member_name']; ?></td>
                                        <td><?php echo $loan['book_title']; ?></td>
                                        <td><?php echo formatDate($loan['created_at']); ?></td>
                                        <td>
                                            <?php
                                            $status_class = '';
                                            $status_text = '';
                                            switch ($loan['status']) {
                                                case 'pending':
                                                    $status_class = 'bg-warning';
                                                    $status_text = 'Menunggu';
                                                    break;
                                                case 'approved':
                                                    $status_class = 'bg-success';
                                                    $status_text = 'Disetujui';
                                                    break;
                                                case 'returned':
                                                    $status_class = 'bg-info';
                                                    $status_text = 'Dikembalikan';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>"><?php echo $status_text; ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted">
                        <i class="fas fa-history fa-3x mb-3"></i>
                        <p>Belum ada peminjaman</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Low Stock Alert -->
    <div class="col-md-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-exclamation-triangle text-warning"></i> Stok Rendah</h5>
                <a href="books.php" class="btn btn-sm btn-outline-warning">Lihat Semua</a>
            </div>
            <div class="card-body">
                <?php if (count($low_stock_books) > 0): ?>
                    <?php foreach ($low_stock_books as $book): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                            <div>
                                <strong><?php echo $book['title']; ?></strong><br>
                                <small class="text-muted"><?php echo $book['code']; ?></small>
                            </div>
                            <span class="badge bg-<?php echo $book['available_stock'] == 0 ? 'danger' : 'warning'; ?>">
                                <?php echo $book['available_stock']; ?> tersisa
                            </span>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center text-muted">
                        <i class="fas fa-check-circle fa-2x mb-2 text-success"></i>
                        <p>Semua buku stoknya aman</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>