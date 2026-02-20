<?php
// frontend/admin/work-hours.php
require_once '../../backend/includes/auth.php';
require_once '../../backend/config/db.php';
requireAdmin();

// Filtres
$dateFilter = $_GET['date'] ?? date('Y-m-d');
$employeeFilter = $_GET['employee_id'] ?? '';

// Récupérer tous les employés pour le filtre
$employees = $pdo->query("SELECT id, name FROM employees WHERE status = 'active' ORDER BY name")->fetchAll();

// Construire la requête
$sql = "SELECT w.*, e.name as employee_name, e.department 
        FROM work_hours w 
        JOIN employees e ON w.employee_id = e.id 
        WHERE 1=1";
$params = [];

if ($dateFilter) {
    $sql .= " AND w.date = ?";
    $params[] = $dateFilter;
}

if ($employeeFilter) {
    $sql .= " AND w.employee_id = ?";
    $params[] = $employeeFilter;
}

$sql .= " ORDER BY w.date DESC, w.clock_in";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$workHours = $stmt->fetchAll();

// Statistiques du jour
$today = date('Y-m-d');
$statsToday = $pdo->prepare("SELECT 
    COUNT(*) as present,
    SUM(CASE WHEN status = 'in-progress' THEN 1 ELSE 0 END) as in_progress,
    SUM(hours) as total_hours
    FROM work_hours WHERE date = ?");
$statsToday->execute([$today]);
$stats = $statsToday->fetch();

// Moyenne générale
$avgHours = $pdo->query("SELECT AVG(hours) as avg FROM work_hours WHERE status = 'complete'")->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Heures de Travail - Admin | GRH System</title>
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
            <a href="leaves.php" class="nav-link"><i class="bi bi-calendar-check"></i><span>Congés</span></a>
            <a href="work-hours.php" class="nav-link active"><i class="bi bi-clock-history"></i><span>Heures de travail</span></a>
        
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
                <h1 class="page-title">Heures de <span>Travail</span></h1>
            </div>
        </div>

        <div class="page-content">
            <!-- Stats -->
            <div class="row g-4 mb-4">
                <div class="col-md-3 col-sm-6">
                    <div class="glass-card stat-card slide-up">
                        <div class="stat-icon primary"><i class="bi bi-people-fill"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">Présents aujourd'hui</div>
                            <div class="stat-value"><?php echo $stats['present'] ?? 0; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="glass-card stat-card slide-up">
                        <div class="stat-icon success"><i class="bi bi-clock-fill"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">Heures aujourd'hui</div>
                            <div class="stat-value"><?php echo number_format($stats['total_hours'] ?? 0, 1); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="glass-card stat-card slide-up">
                        <div class="stat-icon warning"><i class="bi bi-hourglass-split"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">En cours</div>
                            <div class="stat-value"><?php echo $stats['in_progress'] ?? 0; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="glass-card stat-card slide-up">
                        <div class="stat-icon info"><i class="bi bi-calculator"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">Moyenne générale</div>
                            <div class="stat-value"><?php echo number_format($avgHours['avg'] ?? 0, 1); ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtres -->
            <div class="glass-card data-table-wrapper slide-up">
                <div class="filter-bar mb-3">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label text-secondary">Date</label>
                            <input type="date" name="date" class="form-control" value="<?php echo $dateFilter; ?>" onchange="this.form.submit()">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label text-secondary">Employé</label>
                            <select name="employee_id" class="form-select" onchange="this.form.submit()">
                                <option value="">Tous les employés</option>
                                <?php foreach ($employees as $emp): ?>
                                <option value="<?php echo $emp['id']; ?>" <?php echo $employeeFilter == $emp['id'] ? 'selected' : ''; ?>>
                                    <?php echo $emp['name']; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <a href="work-hours.php" class="btn btn-outline-custom w-100">Réinitialiser</a>
                        </div>
                    </form>
                </div>

                <div class="table-responsive">
                    <table class="table-dark-custom">
                        <thead>
                            <tr>
                                <th>Employé</th>
                                <th>Département</th>
                                <th>Date</th>
                                <th>Entrée</th>
                                <th>Sortie</th>
                                <th>Heures</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($workHours as $wh): ?>
                            <tr>
                                <td>
                                    <div class="employee-cell">
                                        <div class="emp-avatar"><?php echo getInitials($wh['employee_name']); ?></div>
                                        <div><?php echo $wh['employee_name']; ?></div>
                                    </div>
                                </td>
                                <td><?php echo $wh['department']; ?></td>
                                <td><?php echo formatDate($wh['date']); ?></td>
                                <td><span style="color:var(--success-light);"><?php echo $wh['clock_in']; ?></span></td>
                                <td><?php echo $wh['clock_out'] ? '<span style="color:var(--danger-light);">' . $wh['clock_out'] . '</span>' : '<span class="text-muted">—</span>'; ?></td>
                                <td><span class="fw-bold"><?php echo $wh['hours'] ? number_format($wh['hours'], 1) . 'h' : 'En cours'; ?></span></td>
                                <td><?php echo getStatusBadge($wh['status']); ?></td>
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