<?php
$page_title = "Dashboard Anggota";
require_once '../config/database.php';
require_once '../includes/header.php';
requireMember();

$database = new Database();
$db = $database->getConnection();

$member_id = $_SESSION['user_id'];

$stats = [];

$query = "SELECT COUNT(*) as total FROM loans WHERE member_id = :member_id";
$stmt = $db->prepare($query);
$stmt->bindParam(":member_id", $member_id);
$stmt->execute();
$stats['total_loans'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$query = "SELECT COUNT(*) as total FROM loans WHERE member_id = :member_id AND status = 'approved'";
$stmt = $db->prepare($query);
$stmt->bindParam(":member_id", $member_id);
$stmt->execute();
$stats['active_loans'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$query = "SELECT COUNT(*) as total FROM loans WHERE member_id = :member_id AND status = 'pending'";
$stmt = $db->prepare($query);
$stmt->bindParam(":member_id", $member_id);
$stmt->execute();
$stats['pending_loans'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$query = "SELECT COUNT(*) as total FROM loans WHERE member_id = :member_id AND status = 'approved' AND due_date < CURDATE()";
$stmt = $db->prepare($query);
$stmt->bindParam(":member_id", $member_id);
$stmt->execute();
$stats['overdue_loans'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];

$query = "SELECT l.*, b.title as book_title, b.author, b.code as book_code 
          FROM loans l 
          JOIN books b ON l.book_id = b.id 
          WHERE l.member_id = :member_id AND l.status = 'approved'
          ORDER BY l.due_date ASC";
$stmt = $db->prepare($query);
$stmt->bindParam(":member_id", $member_id);
$stmt->execute();
$active_loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

$query = "SELECT b.*, c.name as category_name, COUNT(l.id) as loan_count
          FROM books b 
          LEFT JOIN categories c ON b.category_id = c.id
          LEFT JOIN loans l ON b.id = l.book_id
          WHERE b.available_stock > 0
          GROUP BY b.id
          ORDER BY loan_count DESC, b.created_at DESC
          LIMIT 6";
$stmt = $db->prepare($query);
$stmt->execute();
$popular_books = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center border-bottom mb-4">
    <h1 class="h2"><i class="fas fa-tachometer-alt"></i> Dashboard Anggota</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="btn-group me-2">
            <a href="browse.php" class="btn btn-primary" style="background: linear-gradient(135deg,rgb(0, 0, 0) 0%, #764ba2 100%);">
                <i class="fas fa-search"></i> Cari Buku
            </a>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-3">
        <div class="card text-white bg-primary">
            <div class="card-body rounded" style="background: linear-gradient(135deg,rgb(0, 0, 0) 0%, #764ba2 100%);">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $stats['total_loans']; ?></h4>
                        <small>Total Peminjaman</small>
                    </div>
                    <i class="fas fa-history fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-white bg-success">
            <div class="card-body rounded" style="background: linear-gradient(135deg,rgb(0, 0, 0) 0%, #764ba2 100%);">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $stats['active_loans']; ?></h4>
                        <small>Sedang Dipinjam</small>
                    </div>
                    <i class="fas fa-book-open fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-white bg-warning">
            <div class="card-body rounded" style="background: linear-gradient(135deg,rgb(0, 0, 0) 0%, #764ba2 100%);">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $stats['pending_loans']; ?></h4>
                        <small>Menunggu Persetujuan</small>
                    </div>
                    <i class="fas fa-clock fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-3">
        <div class="card text-white bg-danger">
            <div class="card-body rounded" style="background: linear-gradient(135deg,rgb(0, 0, 0) 0%, #764ba2 100%);">
                <div class="d-flex justify-content-between">
                    <div>
                        <h4><?php echo $stats['overdue_loans']; ?></h4>
                        <small>Terlambat</small>
                    </div>
                    <i class="fas fa-exclamation-triangle fa-2x opacity-50"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header" >
                <h5><i class="fas fa-book-open"></i> Buku yang Sedang Dipinjam</h5>
            </div>
            <div class="card-body">
                <?php if (count($active_loans) > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Kode</th>
                                    <th>Judul Buku</th>
                                    <th>Penulis</th>
                                    <th>Tgl Pinjam</th>
                                    <th>Jatuh Tempo</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($active_loans as $loan): ?>
                                    <tr>
                                        <td><?php echo $loan['book_code']; ?></td>
                                        <td><?php echo $loan['book_title']; ?></td>
                                        <td><?php echo $loan['author']; ?></td>
                                        <td><?php echo formatDate($loan['loan_date']); ?></td>
                                        <td>
                                            <?php 
                                            $due_date = $loan['due_date'];
                                            $is_overdue = strtotime($due_date) < strtotime(date('Y-m-d'));
                                            ?>
                                            <span class="<?php echo $is_overdue ? 'text-danger fw-bold' : ''; ?>">
                                                <?php echo formatDate($due_date); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (strtotime($loan['due_date']) < strtotime(date('Y-m-d'))): ?>
                                                <span class="badge bg-danger">Terlambat</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Aktif</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted">
                        <i class="fas fa-book fa-3x mb-3"></i>
                        <p>Anda belum meminjam buku apapun</p>
                        <a href="browse.php" class="btn btn-primary" style="background: linear-gradient(135deg,rgb(0, 0, 0) 0%, #764ba2 100%);">
                            <i class="fas fa-search"></i> Cari Buku untuk Dipinjam
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5><i class="fas fa-star"></i> Buku Populer</h5>
            </div>
            <div class="card-body">
                <?php if (count($popular_books) > 0): ?>
                    <?php foreach ($popular_books as $book): ?>
                        <div class="mb-3 p-3 border rounded">
                            <h6 class="mb-1"><?php echo $book['title']; ?></h6>
                            <small class="text-muted">
                                <?php echo $book['author']; ?>
                                <?php if ($book['category_name']): ?>
                                    • <?php echo $book['category_name']; ?>
                                <?php endif; ?>
                            </small>
                            <div class="mt-2">
                                <span class="badge bg-success">Tersedia: <?php echo $book['available_stock']; ?></span>
                                <?php if ($book['loan_count'] > 0): ?>
                                    <span class="badge bg-info"><?php echo $book['loan_count']; ?>x dipinjam</span>
                                <?php endif; ?>
                            </div>
                            <div class="mt-2">
                                <a href="browse.php?search=<?php echo urlencode($book['title']); ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye"></i> Lihat Detail
                                </a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    <div class="text-center">
                        <a href="browse.php" class="btn btn-sm btn-primary" style="background: linear-gradient(135deg,rgb(0, 0, 0) 0%, #764ba2 100%);">
                            <i class="fas fa-search"></i> Lihat Semua Buku
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted">
                        <i class="fas fa-book fa-2x mb-2"></i>
                        <p>Belum ada data buku</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>