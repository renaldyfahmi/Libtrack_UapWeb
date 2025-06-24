<?php
$page_title = "Kelola Anggota";
require_once '../includes/header.php';
requireAdmin();

$database = new Database();
$db = $database->getConnection();

$message = '';
$message_type = '';

// Handle form submissions
if ($_POST) {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $member_code = sanitize($_POST['member_code']);
                $username = sanitize($_POST['username']);
                $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $full_name = sanitize($_POST['full_name']);
                $email = sanitize($_POST['email']);
                $phone = sanitize($_POST['phone']);
                $address = sanitize($_POST['address']);
                
                // Check if member code, username, or email already exists
                $check_query = "SELECT id FROM members WHERE member_code = :member_code OR username = :username OR email = :email";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->bindParam(":member_code", $member_code);
                $check_stmt->bindParam(":username", $username);
                $check_stmt->bindParam(":email", $email);
                $check_stmt->execute();
                
                if ($check_stmt->rowCount() > 0) {
                    $message = "Kode anggota, username, atau email sudah ada!";
                    $message_type = "danger";
                } else {
                    $query = "INSERT INTO members (member_code, username, password, full_name, email, phone, address) 
                              VALUES (:member_code, :username, :password, :full_name, :email, :phone, :address)";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(":member_code", $member_code);
                    $stmt->bindParam(":username", $username);
                    $stmt->bindParam(":password", $password);
                    $stmt->bindParam(":full_name", $full_name);
                    $stmt->bindParam(":email", $email);
                    $stmt->bindParam(":phone", $phone);
                    $stmt->bindParam(":address", $address);
                    
                    if ($stmt->execute()) {
                        $message = "Anggota berhasil ditambahkan!";
                        $message_type = "success";
                    } else {
                        $message = "Gagal menambahkan anggota!";
                        $message_type = "danger";
                    }
                }
                break;
                
            case 'edit':
                $id = (int)$_POST['id'];
                $full_name = sanitize($_POST['full_name']);
                $email = sanitize($_POST['email']);
                $phone = sanitize($_POST['phone']);
                $address = sanitize($_POST['address']);
                $status = sanitize($_POST['status']);
                
                // Check if email already exists (except current)
                $check_query = "SELECT id FROM members WHERE email = :email AND id != :id";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->bindParam(":email", $email);
                $check_stmt->bindParam(":id", $id);
                $check_stmt->execute();
                
                if ($check_stmt->rowCount() > 0) {
                    $message = "Email sudah digunakan oleh anggota lain!";
                    $message_type = "danger";
                } else {
                    $query = "UPDATE members SET full_name = :full_name, email = :email, phone = :phone, 
                              address = :address, status = :status WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(":full_name", $full_name);
                    $stmt->bindParam(":email", $email);
                    $stmt->bindParam(":phone", $phone);
                    $stmt->bindParam(":address", $address);
                    $stmt->bindParam(":status", $status);
                    $stmt->bindParam(":id", $id);
                    
                    if ($stmt->execute()) {
                        $message = "Data anggota berhasil diupdate!";
                        $message_type = "success";
                    } else {
                        $message = "Gagal mengupdate data anggota!";
                        $message_type = "danger";
                    }
                }
                break;
                
            case 'reset_password':
                $id = (int)$_POST['id'];
                $new_password = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
                
                $query = "UPDATE members SET password = :password WHERE id = :id";
                $stmt = $db->prepare($query);
                $stmt->bindParam(":password", $new_password);
                $stmt->bindParam(":id", $id);
                
                if ($stmt->execute()) {
                    $message = "Password berhasil direset!";
                    $message_type = "success";
                } else {
                    $message = "Gagal mereset password!";
                    $message_type = "danger";
                }
                break;
                
            case 'delete':
                $id = (int)$_POST['id'];
                
                // Check if member has active loans
                $check_query = "SELECT COUNT(*) as count FROM loans WHERE member_id = :id AND status IN ('pending', 'approved')";
                $check_stmt = $db->prepare($check_query);
                $check_stmt->bindParam(":id", $id);
                $check_stmt->execute();
                $check = $check_stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($check['count'] > 0) {
                    $message = "Tidak dapat menghapus anggota yang masih memiliki peminjaman aktif!";
                    $message_type = "danger";
                } else {
                    $query = "DELETE FROM members WHERE id = :id";
                    $stmt = $db->prepare($query);
                    $stmt->bindParam(":id", $id);
                    
                    if ($stmt->execute()) {
                        $message = "Anggota berhasil dihapus!";
                        $message_type = "success";
                    } else {
                        $message = "Gagal menghapus anggota!";
                        $message_type = "danger";
                    }
                }
                break;
        }
    }
}

// Get members with loan statistics
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? sanitize($_GET['status']) : '';

$query = "SELECT m.*, 
          COUNT(l.id) as total_loans,
          COUNT(CASE WHEN l.status = 'approved' THEN 1 END) as active_loans,
          COUNT(CASE WHEN l.status = 'approved' AND l.due_date < CURDATE() THEN 1 END) as overdue_loans
          FROM members m 
          LEFT JOIN loans l ON m.id = l.member_id 
          WHERE 1=1";

if (!empty($search)) {
    $query .= " AND (m.full_name LIKE :search OR m.member_code LIKE :search OR m.email LIKE :search)";
}

if (!empty($status_filter)) {
    $query .= " AND m.status = :status_filter";
}

$query .= " GROUP BY m.id ORDER BY m.created_at DESC";

$stmt = $db->prepare($query);

if (!empty($search)) {
    $search_param = "%$search%";
    $stmt->bindParam(":search", $search_param);
}

if (!empty($status_filter)) {
    $stmt->bindParam(":status_filter", $status_filter);
}

$stmt->execute();
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get registration statistics for today
$today_registrations_query = "SELECT COUNT(*) as count FROM members WHERE DATE(created_at) = CURDATE()";
$today_stmt = $db->prepare($today_registrations_query);
$today_stmt->execute();
$today_registrations = $today_stmt->fetch(PDO::FETCH_ASSOC)['count'];
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center border-bottom mb-4">
    <h1 class="h2"><i class="fas fa-users"></i> Kelola Anggota</h1>
    <div class="btn-toolbar mb-2 mb-md-0">
        <div class="me-2">
            <span class="badge bg-info">Registrasi hari ini: <?php echo $today_registrations; ?></span>
        </div>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addMemberModal">
            <i class="fas fa-plus"></i> Tambah Anggota
        </button>
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
            <div class="col-md-6">
                <input type="text" class="form-control" name="search" placeholder="Cari berdasarkan nama, kode anggota, atau email..." 
                       value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="col-md-4">
                <select class="form-select" name="status">
                    <option value="">Semua Status</option>
                    <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>Aktif</option>
                    <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>Tidak Aktif</option>
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

<!-- Members Table -->
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Nama Lengkap</th>
                        <th>Email</th>
                        <th>Telepon</th>
                        <th>Status</th>
                        <th>Statistik</th>
                        <th>Terdaftar</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($members) > 0): ?>
                        <?php foreach ($members as $member): ?>
                            <tr>
                                <td>
                                    <strong><?php echo $member['member_code']; ?></strong><br>
                                    <small class="text-muted"><?php echo $member['username']; ?></small>
                                </td>
                                <td>
                                    <strong><?php echo $member['full_name']; ?></strong>
                                    <?php if ($member['address']): ?>
                                        <br><small class="text-muted"><?php echo substr($member['address'], 0, 30) . (strlen($member['address']) > 30 ? '...' : ''); ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $member['email']; ?></td>
                                <td><?php echo $member['phone'] ?: '-'; ?></td>
                                <td>
                                    <?php if ($member['status'] === 'active'): ?>
                                        <span class="badge bg-success">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger">Tidak Aktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <small>
                                        <div><i class="fas fa-history"></i> <?php echo $member['total_loans']; ?> total</div>
                                        <div><i class="fas fa-book-open text-success"></i> <?php echo $member['active_loans']; ?> aktif</div>
                                        <?php if ($member['overdue_loans'] > 0): ?>
                                            <div><i class="fas fa-exclamation-triangle text-danger"></i> <?php echo $member['overdue_loans']; ?> terlambat</div>
                                        <?php endif; ?>
                                    </small>
                                </td>
                                <td>
                                    <?php echo formatDate($member['created_at']); ?>
                                    <?php if (date('Y-m-d', strtotime($member['created_at'])) === date('Y-m-d')): ?>
                                        <br><span class="badge bg-success">Baru</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group-vertical btn-group-sm">
                                        <button type="button" class="btn btn-outline-primary btn-sm" 
                                                onclick="editMember(<?php echo htmlspecialchars(json_encode($member)); ?>)">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-warning btn-sm" 
                                                onclick="resetPassword(<?php echo $member['id']; ?>, '<?php echo htmlspecialchars($member['full_name']); ?>')">
                                            <i class="fas fa-key"></i>
                                        </button>
                                        <?php if ($member['active_loans'] == 0): ?>
                                            <form method="POST" class="d-inline" onsubmit="return confirmDelete('Apakah Anda yakin ingin menghapus anggota ini?')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="id" value="<?php echo $member['id']; ?>">
                                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="8" class="text-center text-muted">Tidak ada data anggota</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Add Member Modal -->
<div class="modal fade" id="addMemberModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Tambah Anggota Baru</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="member_code" class="form-label">Kode Anggota *</label>
                                <input type="text" class="form-control" name="member_code" required 
                                       placeholder="Contoh: M001" value="<?php echo generateCode('M', 3); ?>">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username *</label>
                                <input type="text" class="form-control" name="username" required>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password *</label>
                        <input type="password" class="form-control" name="password" required minlength="6">
                        <small class="text-muted">Minimal 6 karakter</small>
                    </div>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Nama Lengkap *</label>
                        <input type="text" class="form-control" name="full_name" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="phone" class="form-label">Telepon</label>
                                <input type="text" class="form-control" name="phone">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="address" class="form-label">Alamat</label>
                        <textarea class="form-control" name="address" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Member Modal -->
<div class="modal fade" id="editMemberModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Anggota</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Kode Anggota</label>
                                <input type="text" class="form-control" id="edit_member_code" readonly>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" id="edit_username" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_full_name" class="form-label">Nama Lengkap *</label>
                        <input type="text" class="form-control" name="full_name" id="edit_full_name" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_email" class="form-label">Email *</label>
                                <input type="email" class="form-control" name="email" id="edit_email" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="edit_phone" class="form-label">Telepon</label>
                                <input type="text" class="form-control" name="phone" id="edit_phone">
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="edit_status" class="form-label">Status *</label>
                        <select class="form-select" name="status" id="edit_status" required>
                            <option value="active">Aktif</option>
                            <option value="inactive">Tidak Aktif</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_address" class="form-label">Alamat</label>
                        <textarea class="form-control" name="address" id="edit_address" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="modal fade" id="resetPasswordModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reset Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="reset_password">
                    <input type="hidden" name="id" id="reset_id">
                    <p>Reset password untuk: <strong id="reset_member_name"></strong></p>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Password Baru *</label>
                        <input type="password" class="form-control" name="new_password" required minlength="6">
                        <small class="text-muted">Minimal 6 karakter</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-warning">Reset Password</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editMember(member) {
    document.getElementById('edit_id').value = member.id;
    document.getElementById('edit_member_code').value = member.member_code;
    document.getElementById('edit_username').value = member.username;
    document.getElementById('edit_full_name').value = member.full_name;
    document.getElementById('edit_email').value = member.email;
    document.getElementById('edit_phone').value = member.phone || '';
    document.getElementById('edit_status').value = member.status;
    document.getElementById('edit_address').value = member.address || '';
    
    new bootstrap.Modal(document.getElementById('editMemberModal')).show();
}

function resetPassword(memberId, memberName) {
    document.getElementById('reset_id').value = memberId;
    document.getElementById('reset_member_name').textContent = memberName;
    
    new bootstrap.Modal(document.getElementById('resetPasswordModal')).show();
}
</script>

<?php require_once '../includes/footer.php'; ?>