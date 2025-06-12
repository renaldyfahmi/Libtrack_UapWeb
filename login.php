<?php
session_start();
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';

if ($_POST) {
    $username = sanitize($_POST['username']);
    $password = $_POST['password'];
    
    $query = "SELECT id, username, password, full_name FROM users WHERE username = :username";
    $stmt = $pdo->prepare($query);
    $stmt->bindParam(":username", $username);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            
            header("Location: dashboard.php");
            exit();
        } else {
            $error = "Username atau password salah!";
        }
    } else {
        $error = "Username atau password salah!";
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Libtrack
    </title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="card">
                    <div class="card-body p-5">
                        <div class="text-center mb-4">
                            <i class="fas fa-book fa-3x text-primary mb-3"></i>
                            <h3>Libtrack</h3>
                            <p class="text-muted">Silakan login untuk melanjutkan</p>
                        </div>
                        
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                         <form method="POST" class="needs-validation" novalidate>
                            <div class="mb-3">
                                <label for="user_type" class="form-label">Login Sebagai</label>
                                <select class="form-select" name="user_type" required>
                                    <option value="">Pilih jenis pengguna</option>
                                    <option value="admin" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                                    <option value="member" <?php echo (isset($_POST['user_type']) && $_POST['user_type'] === 'member') ? 'selected' : ''; ?>>Anggota</option>
                                </select>
                                <div class="invalid-feedback">
                                    Pilih jenis pengguna.
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" name="username" 
                                           value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>" 
                                           required>
                                    <div class="invalid-feedback">
                                        Username harus diisi.
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" name="password" required>
                                    <div class="invalid-feedback">
                                        Password harus diisi.
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 mb-3">
                                <i class="fas fa-sign-in-alt"></i> Login
                            </button>
                        </form>

                        <div class="text-center mb-3">
                            <p class="mb-0">Belum punya akun? 
                                <a href="register.php" class="text-primary text-decoration-none">
                                    <strong>Daftar di sini</strong>
                                </a>
                            </p>
                        </div>
                        
                        <hr>
                        
                        
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                Demo Login:<br>
                             <strong>Admin</strong>: admin / password<br>
                             <strong>Member</strong>: member1 / password<br>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>