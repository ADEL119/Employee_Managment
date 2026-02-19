<?php
// frontend/admin/dashboard.php
require_once '../../backend/includes/auth.php';
require_once '../../backend/config/db.php';
requireAdmin();

// Statistiques
$totalEmployes = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$congesEnAttente = $pdo->query("SELECT COUNT(*) FROM leaves WHERE status = 'pending'")->fetchColumn();
$totalCongesApprouves = $pdo->query("SELECT COUNT(*) FROM leaves WHERE status = 'approved'")->fetchColumn();

// Dernières demandes de congé
$recentLeaves = $pdo->query("SELECT l.*, e.name as employee_name 
                             FROM leaves l 
                             JOIN employees e ON l.employee_id = e.id 
                             ORDER BY l.created_at DESC LIMIT 6")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Admin | GRH System</title>
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
            <a href="dashboard.php" class="nav-link active"><i class="bi bi-grid-1x2-fill"></i><span>Tableau de bord</span></a>
            <a href="employees.php" class="nav-link"><i class="bi bi-people"></i><span>Employés</span></a>
            
            <div class="nav-section-title">Gestion</div>
            <a href="leaves.php" class="nav-link"><i class="bi bi-calendar-check"></i><span>Congés</span></a>
        </nav>
        
        <div class="sidebar-user">
            <div class="user-avatar">AD</div>
            <div class="user-info">
                <div class="user-name">Admin</div>
                <div class="user-role">Administrateur</div>
            </div>
            <a href="../../backend/logout.php" class="btn btn-link text-muted p-0 ms-auto" title="Déconnexion">
                <i class="bi bi-box-arrow-right fs-5"></i>
            </a>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-navbar">
            <div class="d-flex align-items-center gap-3">
                <button class="sidebar-toggle"><i class="bi bi-list"></i></button>
                <h1 class="page-title">Tableau de <span>bord</span></h1>
            </div>
            
            <div class="navbar-actions">
                <button class="btn-icon" title="Notifications">
                    <i class="bi bi-bell"></i>
                    <span class="badge-dot"></span>
                </button>
            </div>
        </div>

        <div class="page-content">
            <div class="glass-card p-4 mb-4 slide-up">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h4 class="fw-bold mb-1">Bienvenue, Admin 👋</h4>
                        <p class="text-secondary mb-0">Voici un résumé de l'activité d'aujourd'hui.</p>
                    </div>
                    <div class="text-end">
                        <div class="fw-semibold" style="color: var(--accent);" id="dashDate"></div>
                        <small class="text-muted" id="dashTime"></small>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="glass-card stat-card slide-up">
                        <div class="stat-icon primary"><i class="bi bi-people-fill"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">Total Employés</div>
                            <div class="stat-value"><?php echo $totalEmployes; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card stat-card slide-up">
                        <div class="stat-icon warning"><i class="bi bi-hourglass-split"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">Congés en attente</div>
                            <div class="stat-value"><?php echo $congesEnAttente; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card stat-card slide-up">
                        <div class="stat-icon success"><i class="bi bi-calendar-check-fill"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">Congés approuvés</div>
                            <div class="stat-value"><?php echo $totalCongesApprouves; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="glass-card data-table-wrapper slide-up">
                <div class="data-table-header">
                    <h5><i class="bi bi-calendar-check me-2" style="color: var(--accent);"></i>Dernières demandes de congé</h5>
                    <a href="leaves.php" class="btn btn-outline-custom btn-sm">Voir tout</a>
                </div>
                <div class="table-responsive">
                    <table class="table-dark-custom">
                        <thead>
                            <tr><th>Employé</th><th>Type</th><th>Période</th><th>Jours</th><th>Statut</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentLeaves as $leave): ?>
                            <tr>
                                <td>
                                    <div class="employee-cell">
                                        <div class="emp-avatar"><?php echo getInitials($leave['employee_name']); ?></div>
                                        <div><?php echo $leave['employee_name']; ?></div>
                                    </div>
                                </td>
                                <td><?php echo $leave['type']; ?></td>
                                <td><?php echo formatDate($leave['start_date']); ?> - <?php echo formatDate($leave['end_date']); ?></td>
                                <td><?php echo $leave['days']; ?> j</td>
                                <td><?php echo getStatusBadge($leave['status']); ?></td>
                            </tr>
                            <?php
endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- BOUTON DE DÉCONNEXION FLOTTANT -->
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/app.js"></script>
    <script>
        function updateClock() {
            const now = new Date();
            document.getElementById('dashTime').textContent = now.toLocaleTimeString('fr-FR');
            document.getElementById('dashDate').textContent = now.toLocaleDateString('fr-FR', { 
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' 
            });
        }
        updateClock();
        setInterval(updateClock, 1000);
    </script>
</body>
</html>