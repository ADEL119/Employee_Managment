<?php
// frontend/login.php
session_start();

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_role'] === 'admin') {
        header('Location: admin/dashboard.php');
    } else {
        header('Location: employee/dashboard.php');
    }
    exit();
}

require_once '../backend/config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    
    // Vérifier que les champs ne sont pas vides
    if (empty($email) || empty($password)) {
        $error = 'Veuillez remplir tous les champs.';
    } else {
        // Requête pour trouver l'utilisateur
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        // Vérifier le mot de passe en CLAIR (pas de password_verify)
        if ($user && $password === $user['password']) {
            // Connexion réussie
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            
            // Si c'est un employé, récupérer ses informations
            if ($user['role'] === 'employee') {
                $stmt = $pdo->prepare("SELECT * FROM employees WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                $employee = $stmt->fetch();
                if ($employee) {
                    $_SESSION['employee_id'] = $employee['id'];
                    $_SESSION['employee_name'] = $employee['name'];
                }
            }
            
            // Redirection selon le rôle
            if ($user['role'] === 'admin') {
                header('Location: admin/dashboard.php');
            } else {
                header('Location: employee/dashboard.php');
            }
            exit();
        } else {
            $error = 'Email ou mot de passe incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - GRH System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="css/style.css" rel="stylesheet">
</head>
<body>
    <div class="bg-blobs">
        <div class="blob"></div>
        <div class="blob"></div>
        <div class="blob"></div>
    </div>

    <div class="login-wrapper">
        <div class="login-card glass-card">
            <div class="logo-area">
                <div class="logo-icon">
                    <i class="bi bi-people-fill"></i>
                </div>
                <h1>GRH System</h1>
                <p>Gestion des Ressources Humaines</p>
            </div>

            <form method="POST" action="">
                <div class="form-floating-custom">
                    <i class="bi bi-envelope input-icon"></i>
                    <input type="email" class="form-control" id="email" name="email" placeholder="Adresse email" value="admin@grh.com" required>
                </div>

                <div class="form-floating-custom">
                    <i class="bi bi-lock input-icon"></i>
                    <input type="password" class="form-control" id="password" name="password" placeholder="Mot de passe" value="admin123" required>
                    <button type="button" class="btn btn-link position-absolute end-0 top-50 translate-middle-y me-2 text-muted p-0 border-0" id="togglePassword">
                        <i class="bi bi-eye"></i>
                    </button>
                </div>

                <button type="submit" class="btn btn-primary-custom w-100 mb-3">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Se connecter
                </button>

                <?php if ($error): ?>
                <div class="alert alert-danger py-2">
                    <i class="bi bi-exclamation-triangle me-1"></i> <?php echo $error; ?>
                </div>
                <?php endif; ?>
            </form>

            <div class="text-center mt-3">
                <small class="text-muted">
                    <strong>Admin:</strong> admin@grh.com / admin123<br>
                    <strong>Adel Selmi:</strong> selmi.adel@grh.com / adel123<br>
                    <strong>Feki Mohamed:</strong> feki.mohamed@grh.com / feki123
                </small>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('togglePassword').addEventListener('click', function() {
            const pw = document.getElementById('password');
            const icon = this.querySelector('i');
            if (pw.type === 'password') {
                pw.type = 'text';
                icon.classList.replace('bi-eye', 'bi-eye-slash');
            } else {
                pw.type = 'password';
                icon.classList.replace('bi-eye-slash', 'bi-eye');
            }
        });
    </script>
</body>
</html>