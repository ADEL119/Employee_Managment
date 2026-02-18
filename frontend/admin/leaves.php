<?php
// frontend/admin/leaves.php
require_once '../../backend/includes/auth.php';
require_once '../../backend/config/db.php';
requireAdmin();

// Traitement des actions
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    $action = $_GET['action'];
    
    if ($action === 'approve' || $action === 'reject') {
        $status = ($action === 'approve') ? 'approved' : 'rejected';
        $stmt = $pdo->prepare("UPDATE leaves SET status = ? WHERE id = ?");
        $stmt->execute([$status, $id]);
        $_SESSION['success'] = "Demande " . ($action === 'approve' ? 'approuvée' : 'refusée') . " avec succès";
    }
    
    header('Location: leaves.php');
    exit();
}

// Filtres
$statusFilter = $_GET['status'] ?? '';
$typeFilter = $_GET['type'] ?? '';

$sql = "SELECT l.*, e.name as employee_name, e.department 
        FROM leaves l 
        JOIN employees e ON l.employee_id = e.id 
        WHERE 1=1";
$params = [];

if ($statusFilter) {
    $sql .= " AND l.status = ?";
    $params[] = $statusFilter;
}

if ($typeFilter) {
    $sql .= " AND l.type = ?";
    $params[] = $typeFilter;
}

$sql .= " ORDER BY l.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$leaves = $stmt->fetchAll();

// Statistiques
$stats = $pdo->query("SELECT 
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected,
    COUNT(*) as total
    FROM leaves")->fetch();

// Types de congés uniques
$types = $pdo->query("SELECT DISTINCT type FROM leaves ORDER BY type")->fetchAll();

// Messages flash
$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestion des Congés - Admin | GRH System</title>
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
            <a href="employees.php" class="nav-link"><i class="bi bi-people"></i><span>Employés</span></a>
            <div class="nav-section-title">Gestion</div>
            <a href="leaves.php" class="nav-link active"><i class="bi bi-calendar-check"></i><span>Congés</span></a>
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
                <h1 class="page-title">Gestion des <span>Congés</span></h1>
            </div>
        </div>

        <div class="page-content">
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="glass-card stat-card slide-up">
                        <div class="stat-icon warning"><i class="bi bi-hourglass-split"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">En attente</div>
                            <div class="stat-value"><?php echo $stats['pending'] ?? 0; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card stat-card slide-up">
                        <div class="stat-icon success"><i class="bi bi-check-circle"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">Approuvés</div>
                            <div class="stat-value"><?php echo $stats['approved'] ?? 0; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card stat-card slide-up">
                        <div class="stat-icon danger"><i class="bi bi-x-circle"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">Refusés</div>
                            <div class="stat-value"><?php echo $stats['rejected'] ?? 0; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="glass-card data-table-wrapper slide-up">
                <div class="filter-bar mb-3">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="">Tous les statuts</option>
                                <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>En attente</option>
                                <option value="approved" <?php echo $statusFilter === 'approved' ? 'selected' : ''; ?>>Approuvés</option>
                                <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Refusés</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <select name="type" class="form-select" onchange="this.form.submit()">
                                <option value="">Tous les types</option>
                                <?php foreach ($types as $t): ?>
                                <option value="<?php echo $t['type']; ?>" <?php echo $typeFilter === $t['type'] ? 'selected' : ''; ?>>
                                    <?php echo $t['type']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <a href="leaves.php" class="btn btn-outline-custom">Réinitialiser</a>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table-dark-custom">
                        <thead>
                            <tr>
                                <th>Employé</th>
                                <th>Département</th>
                                <th>Type</th>
                                <th>Période</th>
                                <th>Jours</th>
                                <th>Raison</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaves as $leave): ?>
                            <tr>
                                <td>
                                    <div class="employee-cell">
                                        <div class="emp-avatar"><?php echo getInitials($leave['employee_name']); ?></div>
                                        <div><?php echo $leave['employee_name']; ?></div>
                                    </div>
                                </td>
                                <td><?php echo $leave['department']; ?></td>
                                <td><?php echo $leave['type']; ?></td>
                                <td><?php echo formatDate($leave['start_date']); ?> - <?php echo formatDate($leave['end_date']); ?></td>
                                <td><?php echo $leave['days']; ?></td>
                                <td><?php echo substr($leave['reason'], 0, 50) . '...'; ?></td>
                                <td><?php echo getStatusBadge($leave['status']); ?></td>
                                <td>
                                    <div class="action-btns">
                                        <?php if ($leave['status'] === 'pending'): ?>
                                            <a href="?action=approve&id=<?php echo $leave['id']; ?>" class="btn-action success" title="Approuver" onclick="return confirm('Approuver cette demande ?')">
                                                <i class="bi bi-check-lg"></i>
                                            </a>
                                            <a href="?action=reject&id=<?php echo $leave['id']; ?>" class="btn-action danger" title="Refuser" onclick="return confirm('Refuser cette demande ?')">
                                                <i class="bi bi-x-lg"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">—</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/app.js"></script>
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