<?php
$page_title = "Kelola Peminjaman";
require_once '../config/database.php';
require_once '../includes/header.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$message = '';
$message_type = '';

// Handle loan actions
if ($_POST) {
    if (isset($_POST['action'])) {
        $loan_id = (int)$_POST['loan_id'];
        $admin_id = $_SESSION['user_id'];
        
        switch ($_POST['action']) {
            case 'approve':
                // Update loan status and reduce available stock
                $db->beginTransaction();
                try {
                    // Update loan
                    $update_query = "UPDATE loans SET status = 'approved', approved_by = :admin_id WHERE id = :loan_id";
                    $update_stmt = $db->prepare($update_query);
                    $update_stmt->bindParam(":admin_id", $admin_id);
                    $update_stmt->bindParam(":loan_id", $loan_id);
                    $update_stmt->execute();
                    
                    // Reduce available stock
                    $stock_query = "UPDATE books b 
                                   JOIN loans l ON b.id = l.book_id 
                                   SET b.available_stock = b.available_stock - 1 
                                   WHERE l.id = :loan_id";
                    $stock_stmt = $db->prepare($stock_query);
                    $stock_stmt->bindParam(":loan_id", $loan_id);
                    $stock_stmt->execute();
                    
                    $db->commit();
                    $message = "Peminjaman berhasil disetujui!";
                    $message_type = "success";
                } catch (Exception $e) {
                    $db->rollback();
                    $message = "Gagal menyetujui peminjaman!";
                    $message_type = "danger";
                }
                break;
                
            case 'reject':
                $notes = sanitize($_POST['notes']);
                $update_query = "UPDATE loans SET status = 'returned', notes = :notes, return_date = CURDATE() WHERE id = :loan_id";
                $update_stmt = $db->prepare($update_query);
                $update_stmt->bindParam(":notes", $notes);
                $update_stmt->bindParam(":loan_id", $loan_id);
                
                if ($update_stmt->execute()) {
                    $message = "Peminjaman berhasil ditolak!";
                    $message_type = "success";
                } else {
                    $message = "Gagal menolak peminjaman!";
                    $message_type = "danger";
                }
                break;
                
            case 'return':
                // Mark as returned and increase available stock
                $db->beginTransaction();
                try {
                    // Update loan
                    $update_query = "UPDATE loans SET status = 'returned', return_date = CURDATE() WHERE id = :loan_id";
                    $update_stmt = $db->prepare($update_query);
                    $update_stmt->bindParam(":loan_id", $loan_id);
                    $update_stmt->execute();
                    
                    // Increase available stock
                    $stock_query = "UPDATE books b 
                                   JOIN loans l ON b.id = l.book_id 
                                   SET b.available_stock = b.available_stock + 1 
                                   WHERE l.id = :loan_id";
                    $stock_stmt = $db->prepare($stock_query);
                    $stock_stmt->bindParam(":loan_id", $loan_id);
                    $stock_stmt->execute();
                    
                    $db->commit();
                    $message = "Buku berhasil dikembalikan!";
                    $message_type = "success";
                } catch (Exception $e) {
                    $db->rollback();
                    $message = "Gagal memproses pengembalian!";
                    $message_type = "danger";
                }
                break;
        }
    }
}

// Get loans with member and book info
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';

$query = "SELECT l.*, m.full_name as member_name, m.member_code, 
          b.title as book_title, b.author, b.code as book_code,
          a.full_name as approved_by_name
          FROM loans l 
          JOIN members m ON l.member_id = m.id 
          JOIN books b ON l.book_id = b.id 
          LEFT JOIN admins a ON l.approved_by = a.id
          WHERE 1=1";

if (!empty($status_filter)) {
    $query .= " AND l.status = :status_filter";
}

if (!empty($search)) {
    $query .= " AND (m.full_name LIKE :search OR b.title LIKE :search OR l.loan_code LIKE :search)";
}

$query .= " ORDER BY 
            CASE 
                WHEN l.status = 'pending' THEN 1
                WHEN l.status = 'approved' AND l.due_date < CURDATE() THEN 2
                WHEN l.status = 'approved' THEN 3
                ELSE 4
            END,
            l.created_at DESC";

$stmt = $db->prepare($query);

if (!empty($status_filter)) {
    $stmt->bindParam(":status_filter", $status_filter);
}

if (!empty($search)) {
    $search_param = "%$search%";
    $stmt->bindParam(":search", $search_param);
}

$stmt->execute();
$loans = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center border-bottom mb-4">
    <h1 class="h2"><i class="fas fa-handshake"></i> Kelola Peminjaman</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <span class="badge bg-info"><?php echo count($loans); ?> data ditemukan</span>
    </div>
</div>

<?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo $message; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- Filter -->
<div class="card mb-4">
    <div class="card-body">
        <form method="GET" class="row g-3">
            <div class="col-md-4">
                <input type="text" class="form-control" name="search" placeholder="Cari berdasarkan nama anggota, judul buku, atau kode..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
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
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search"></i> Cari
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Loans Table -->
<div class="card">
    <div class="card-body">
        <?php if (count($loans) > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Anggota</th>
                            <th>Buku</th>
                            <th>Tgl Pinjam</th>
                            <th>Jatuh Tempo</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loans as $loan): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $loan['loan_code']; ?></strong><br>
                                    <small class="text-muted"><?php echo formatDate($loan['created_at']); ?></small>
                                </td>
                                <td>
                                    <strong><?php echo $loan['member_name']; ?></strong><br>
                                    <small class="text-muted"><?php echo $loan['member_code']; ?></small>
                                </td>
                                <td>
                                    <strong><?php echo $loan['book_title']; ?></strong><br>
                                    <small class="text-muted">
                                        <?php echo $loan['book_code']; ?> • <?php echo $loan['author']; ?>
                                    </small>
                                </td>
                                <td><?php echo formatDate($loan['loan_date']); ?></td>
                                <td>
                                    <?php 
                                    $due_date = $loan['due_date'];
                                    $is_overdue = $loan['status'] === 'approved' && strtotime($due_date) < strtotime(date('Y-m-d'));
                                    ?>
                                    <span class="<?php echo $is_overdue ? 'text-danger fw-bold' : ''; ?>">
                                        <?php echo formatDate($due_date); ?>
                                    </span>
                                    <?php if ($is_overdue): ?>
                                        <br><small class="text-danger">
                                            <i class="fas fa-exclamation-triangle"></i> 
                                            <?php echo abs((strtotime(date('Y-m-d')) - strtotime($due_date)) / (60*60*24)); ?> hari terlambat
                                        </small>
                                    <?php endif; ?>
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
                                        }
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <i class="<?php echo $status_icon; ?>"></i> <?php echo $status_text; ?>
                                    </span>
                                    
                                    <?php if ($loan['approved_by_name']): ?>
                                        <br><small class="text-muted">oleh <?php echo $loan['approved_by_name']; ?></small>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($loan['status'] === 'pending'): ?>
                                        <div class="btn-group-vertical btn-group-sm">
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                                                <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Setujui peminjaman ini?')">
                                                    <i class="fas fa-check"></i> Setujui
                                                </button>
                                            </form>
                                            <button type="button" class="btn btn-danger btn-sm" 
                                                    onclick="rejectLoan(<?php echo $loan['id']; ?>)">
                                                <i class="fas fa-times"></i> Tolak
                                            </button>
                                        </div>
                                    <?php elseif ($loan['status'] === 'approved'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="return">
                                            <input type="hidden" name="loan_id" value="<?php echo $loan['id']; ?>">
                                            <button type="submit" class="btn btn-info btn-sm" onclick="return confirm('Tandai sebagai dikembalikan?')">
                                                <i class="fas fa-undo"></i> Kembalikan
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <div class="text-center text-muted py-5">
                <i class="fas fa-handshake fa-3x mb-3"></i>
                <h4>Tidak ada data peminjaman</h4>
                <p>Belum ada peminjaman yang perlu dikelola</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tolak Peminjaman</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" id="rejectForm">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reject">
                    <input type="hidden" name="loan_id" id="rejectLoanId">
                    <div class="mb-3">
                        <label for="notes" class="form-label">Alasan Penolakan</label>
                        <textarea class="form-control" name="notes" id="notes" rows="3" required 
                                  placeholder="Masukkan alasan penolakan..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-times"></i> Tolak Peminjaman
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function rejectLoan(loanId) {
    document.getElementById('rejectLoanId').value = loanId;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>