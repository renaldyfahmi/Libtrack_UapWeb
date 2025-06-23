<?php
$page_title = "Riwayat Peminjaman";
require_once '../config/database.php';
require_once '../includes/header.php';
requireMember();

$database = new Database();
$db = $database->getConnection();

$member_id = $_SESSION['user_id'];

$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

$query = "SELECT l.*, b.title as book_title, b.author, b.code as book_code, b.publisher
          FROM loans l 
          JOIN books b ON l.book_id = b.id 
          WHERE l.member_id = :member_id";

if (!empty($status_filter)) {
    $query .= " AND l.status = :status_filter";
}

$query .= " ORDER BY l.created_at DESC";

$stmt = $db->prepare($query);
$stmt->bindParam(":member_id", $member_id);

if (!empty($status_filter)) {
    $stmt->bindParam(":status_filter", $status_filter);
}

$stmt->execute();
$loans = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center border-bottom mb-4">
    <h1 class="h2"><i class="fas fa-history"></i> Riwayat Peminjaman</h1>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <select class="form-select" name="status">
                    <option value="">Semua Status</option>
                    <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Menunggu Persetujuan</option>
                    <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>Disetujui</option>
                    <option value="returned" <?php echo $status_filter === 'returned' ? 'selected' : ''; ?>>Dikembalikan</option>
                    <option value="overdue" <?php echo $status_filter === 'overdue' ? 'selected' : ''; ?>>Terlambat</option>
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100" style="background: linear-gradient(135deg,rgb(0, 0, 0) 0%, #764ba2 100%);">
                    <i class="fas fa-filter"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-body">
        <?php if (count($loans) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Kode Pinjam</th>
                            <th>Buku</th>
                            <th>Penulis</th>
                            <th>Tgl Pinjam</th>
                            <th>Jatuh Tempo</th>
                            <th>Tgl Kembali</th>
                            <th>Status</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loans as $loan): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $loan['loan_code']; ?></strong><br>
                                    <small class="text-muted"><?php echo $loan['book_code']; ?></small>
                                </td>
                                <td>
                                    <strong><?php echo $loan['book_title']; ?></strong>
                                    <?php if ($loan['publisher']): ?>
                                        <br><small class="text-muted"><?php echo $loan['publisher']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $loan['author']; ?></td>
                                <td><?php echo formatDate($loan['loan_date']); ?></td>
                                <td>
                                    <?php 
                                    $due_date = $loan['due_date'];
                                    $is_overdue = $loan['status'] === 'approved' && strtotime($due_date) < strtotime(date('Y-m-d'));
                                    ?>
                                    <span class="<?php echo $is_overdue ? 'text-danger fw-bold' : ''; ?>">
                                        <?php echo formatDate($due_date); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php echo $loan['return_date'] ? formatDate($loan['return_date']) : '-'; ?>
                                </td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    $status_text = '';
                                    $status_icon = '';
                                    
                                    if ($loan['status'] === 'approved' && strtotime($loan['due_date']) < strtotime(date('Y-m-d'))) {
                                        $status_class = 'bg-danger';
                                        $status_text = 'Terlambat';
                                        $status_icon = 'fas fa-exclamation-triangle';
                                    } else {
                                        switch ($loan['status']) {
                                            case 'pending':
                                                $status_class = 'bg-warning';
                                                $status_text = 'Menunggu';
                                                $status_icon = 'fas fa-clock';
                                                break;
                                            case 'approved':
                                                $status_class = 'bg-success';
                                                $status_text = 'Disetujui';
                                                $status_icon = 'fas fa-check';
                                                break;
                                            case 'returned':
                                                $status_class = 'bg-info';
                                                $status_text = 'Dikembalikan';
                                                $status_icon = 'fas fa-undo';
                                                break;
                                            case 'overdue':
                                                $status_class = 'bg-danger';
                                                $status_text = 'Terlambat';
                                                $status_icon = 'fas fa-exclamation-triangle';
                                                break;
                                        }
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <i class="<?php echo $status_icon; ?>"></i> <?php echo $status_text; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($loan['notes']): ?>
                                        <small><?php echo $loan['notes']; ?></small>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-history fa-3x mb-3"></i>
                <h4>Belum ada riwayat peminjaman</h4>
                <p>Anda belum pernah meminjam buku</p>
                <a href="browse.php" class="btn btn-primary" style="background: linear-gradient(135deg,rgb(0, 0, 0) 0%, #764ba2 100%);">
                    <i class="fas fa-search"></i> Cari Buku untuk Dipinjam
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>