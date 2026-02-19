<?php
// frontend/admin/employees.php
require_once '../../backend/includes/auth.php';
require_once '../../backend/config/db.php';
requireAdmin();

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Ajout d'un employé
        if ($_POST['action'] === 'add') {
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $department = $_POST['department'] ?? '';
            $position = $_POST['position'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $join_date = $_POST['join_date'] ?? date('Y-m-d');

            // Vérifier si l'email existe déjà
            $check = $pdo->prepare("SELECT id FROM employees WHERE email = ?");
            $check->execute([$email]);

            if ($check->rowCount() == 0) {
                // Créer d'abord un utilisateur
                $password = password_hash('password123', PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (?, ?, 'employee')");
                $stmt->execute([$email, $password]);
                $user_id = $pdo->lastInsertId();

                // Ajouter l'employé
                $stmt = $pdo->prepare("INSERT INTO employees (user_id, name, email, department, position, phone, join_date, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
                $stmt->execute([$user_id, $name, $email, $department, $position, $phone, $join_date]);

                $_SESSION['success'] = "Employé ajouté avec succès";
            }
            else {
                $_SESSION['error'] = "Cet email existe déjà";
            }
            header('Location: employees.php');
            exit();
        }

        // Modification d'un employé
        if ($_POST['action'] === 'edit') {
            $id = $_POST['id'] ?? 0;
            $name = $_POST['name'] ?? '';
            $email = $_POST['email'] ?? '';
            $department = $_POST['department'] ?? '';
            $position = $_POST['position'] ?? '';
            $phone = $_POST['phone'] ?? '';
            $join_date = $_POST['join_date'] ?? '';
            $status = $_POST['status'] ?? 'active';

            $stmt = $pdo->prepare("UPDATE employees SET name=?, email=?, department=?, position=?, phone=?, join_date=?, status=? WHERE id=?");
            $stmt->execute([$name, $email, $department, $position, $phone, $join_date, $status, $id]);

            $_SESSION['success'] = "Employé modifié avec succès";
            header('Location: employees.php');
            exit();
        }
    }
}

// Suppression d'un employé
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    // Récupérer l'user_id avant de supprimer
    $stmt = $pdo->prepare("SELECT user_id FROM employees WHERE id = ?");
    $stmt->execute([$id]);
    $emp = $stmt->fetch();

    if ($emp) {
        // Supprimer l'employé (la suppression de l'user se fera par CASCADE)
        $stmt = $pdo->prepare("DELETE FROM employees WHERE id = ?");
        $stmt->execute([$id]);

        // Supprimer l'utilisateur associé
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$emp['user_id']]);
    }

    $_SESSION['success'] = "Employé supprimé avec succès";
    header('Location: employees.php');
    exit();
}

// Récupérer tous les employés
$employees = $pdo->query("SELECT * FROM employees ORDER BY name")->fetchAll();

// Messages flash
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Employés - Admin | GRH System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <div class="bg-blobs">
        <div class="blob"></div>
        <div class="blob"></div>
        <div class="blob"></div>
    </div>
    <div class="sidebar-overlay"></div>

    <aside class="sidebar">
        <div class="sidebar-brand">
            <div class="brand-icon"><i class="bi bi-people-fill"></i></div>
            <div>
                <div class="brand-text">GRH System</div>
                <div class="brand-sub">Administration</div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section-title">Principal</div>
            <a href="dashboard.php" class="nav-link"><i class="bi bi-grid-1x2-fill"></i><span>Tableau de bord</span></a>
            <a href="employees.php" class="nav-link active"><i class="bi bi-people"></i><span>Employés</span></a>
            <div class="nav-section-title">Gestion</div>
            <a href="leaves.php" class="nav-link"><i class="bi bi-calendar-check"></i><span>Congés</span></a>
            <a href="work-hours.php" class="nav-link"><i class="bi bi-clock-history"></i><span>Heures de travail</span></a>
            <a href="reports.php" class="nav-link"><i class="bi bi-bar-chart-line"></i><span>Rapports</span></a>
        </nav>
        <div class="sidebar-user">
            <div class="user-avatar">AD</div>
            <div class="user-info">
                <div class="user-name">Admin</div>
                <div class="user-role">Administrateur</div>
            </div>
            <a href="../../backend/logout.php" class="btn btn-link text-muted p-0 ms-auto"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-navbar">
            <div class="d-flex align-items-center gap-3">
                <button class="sidebar-toggle"><i class="bi bi-list"></i></button>
                <h1 class="page-title">Gestion des <span>Employés</span></h1>
            </div>
        </div>

        <div class="page-content">
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php
endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php
endif; ?>

            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                <div class="search-box">
                    <i class="bi bi-search"></i>
                    <input type="text" id="searchEmployees" placeholder="Rechercher un employé...">
                </div>
                <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#employeeModal">
                    <i class="bi bi-plus-lg me-2"></i>Ajouter un employé
                </button>
            </div>

            <div class="glass-card data-table-wrapper slide-up">
                <div class="data-table-header">
                    <h5><i class="bi bi-people me-2" style="color: var(--primary-light);"></i>Liste des employés</h5>
                    <span class="text-muted" id="empCount"><?php echo count($employees); ?> employé(s)</span>
                </div>
                <div class="table-responsive">
                    <table class="table-dark-custom">
                        <thead>
                            <tr>
                                <th>Employé</th>
                                <th>Email</th>
                                <th>Département</th>
                                <th>Poste</th>
                                <th>Téléphone</th>
                                <th>Date d'embauche</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="employeesTable">
                            <?php foreach ($employees as $emp): ?>
                            <tr>
                                <td>
                                    <div class="employee-cell">
                                        <div class="emp-avatar"><?php echo getInitials($emp['name']); ?></div>
                                        <div>
                                            <div class="emp-name"><?php echo $emp['name']; ?></div>
                                            <div class="emp-role">#<?php echo $emp['id']; ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo $emp['email']; ?></td>
                                <td><?php echo $emp['department']; ?></td>
                                <td><?php echo $emp['position']; ?></td>
                                <td><?php echo $emp['phone']; ?></td>
                                <td><?php echo formatDate($emp['join_date']); ?></td>
                                <td><?php echo getStatusBadge($emp['status']); ?></td>
                                <td>
                                    <div class="action-btns">
                                        <button class="btn-action" title="Modifier" onclick="editEmployee(<?php echo htmlspecialchars(json_encode($emp)); ?>)">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="?delete=<?php echo $emp['id']; ?>" class="btn-action danger" title="Supprimer" onclick="return confirm('Êtes-vous sûr de vouloir supprimer cet employé ?')">
                                            <i class="bi bi-trash3"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                            <?php
endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Ajout/Modification -->
    <div class="modal fade" id="employeeModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Ajouter un employé</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="employeeForm" method="POST">
                        <input type="hidden" name="action" id="formAction" value="add">
                        <input type="hidden" name="id" id="empId">
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Nom complet</label>
                                <input type="text" class="form-control" name="name" id="empName" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" class="form-control" name="email" id="empEmail" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Département</label>
                                <select class="form-select" name="department" id="empDept" required>
                                    <option value="">Sélectionner</option>
                                    <option>Informatique</option>
                                    <option>Marketing</option>
                                    <option>Ressources Humaines</option>
                                    <option>Finance</option>
                                    <option>Production</option>
                                    <option>Commercial</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Poste</label>
                                <input type="text" class="form-control" name="position" id="empPosition" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" name="phone" id="empPhone">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Date d'embauche</label>
                                <input type="date" class="form-control" name="join_date" id="empJoinDate" required>
                            </div>
                            <div class="col-md-6" id="statusField" style="display:none;">
                                <label class="form-label">Statut</label>
                                <select class="form-select" name="status" id="empStatus">
                                    <option value="active">Actif</option>
                                    <option value="inactive">Inactif</option>
                                </select>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-custom" data-bs-dismiss="modal">Annuler</button>
                    <button type="button" class="btn btn-primary-custom" onclick="document.getElementById('employeeForm').submit()">
                        <i class="bi bi-check-lg me-1"></i>Enregistrer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/app.js"></script>
    <script>
        // Recherche en temps réel
        document.getElementById('searchEmployees').addEventListener('input', function(e) {
            const search = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('#employeesTable tr');
            let count = 0;
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(search)) {
                    row.style.display = '';
                    count++;
                } else {
                    row.style.display = 'none';
                }
            });
            
            document.getElementById('empCount').textContent = count + ' employé(s)';
        });

        // Flag pour savoir si on est en mode édition
        let isEditing = false;

        // Édition d'un employé
        function editEmployee(emp) {
            isEditing = true;
            document.getElementById('modalTitle').textContent = 'Modifier l\'employé';
            document.getElementById('formAction').value = 'edit';
            document.getElementById('empId').value = emp.id;
            document.getElementById('empName').value = emp.name;
            document.getElementById('empEmail').value = emp.email;
            document.getElementById('empDept').value = emp.department;
            document.getElementById('empPosition').value = emp.position;
            document.getElementById('empPhone').value = emp.phone;
            document.getElementById('empJoinDate').value = emp.join_date;
            document.getElementById('empStatus').value = emp.status;
            document.getElementById('statusField').style.display = 'block';
            
            new bootstrap.Modal(document.getElementById('employeeModal')).show();
        }

        // Réinitialisation du modal d'ajout (uniquement si pas en mode édition)
        document.getElementById('employeeModal').addEventListener('show.bs.modal', function() {
            if (!isEditing) {
                document.getElementById('modalTitle').textContent = 'Ajouter un employé';
                document.getElementById('formAction').value = 'add';
                document.getElementById('empId').value = '';
                document.getElementById('employeeForm').reset();
                document.getElementById('statusField').style.display = 'none';
            }
        });

        // Réinitialiser le flag après fermeture du modal
        document.getElementById('employeeModal').addEventListener('hidden.bs.modal', function() {
            isEditing = false;
        });
    </script>
    <!-- BOUTON DE DÉCONNEXION FLOTTANT - SOLUTION 100% FONCTIONNELLE -->
<a href="../../backend/logout.php" 
   class="btn btn-danger position-fixed" 
   style="bottom: 30px; right: 30px; width: 60px; height: 60px; border-radius: 50%; 
          display: flex; align-items: center; justify-content: center; 
          background: linear-gradient(135deg, #ef4444, #dc2626);
          border: none; box-shadow: 0 4px 15px rgba(239,68,68,0.5);
          z-index: 9999;"
   title="Déconnexion">
    <i class="bi bi-box-arrow-right fs-3"></i>
</a>
</body>
</html>