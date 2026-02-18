<?php
// frontend/employee/dashboard.php
require_once '../../backend/includes/auth.php';
require_once '../../backend/config/db.php';
requireEmploye();

$employee_id = $_SESSION['employee_id'];

// Statistiques de l'employé
$heures = $pdo->prepare("SELECT SUM(hours) as total, COUNT(*) as jours 
                         FROM work_hours 
                         WHERE employee_id = ? AND status = 'complete'");
$heures->execute([$employee_id]);
$stats = $heures->fetch();

$congesEnAttente = $pdo->prepare("SELECT COUNT(*) FROM leaves WHERE employee_id = ? AND status = 'pending'");
$congesEnAttente->execute([$employee_id]);
$congesEnAttente = $congesEnAttente->fetchColumn();

// Derniers congés
$mesConges = $pdo->prepare("SELECT * FROM leaves WHERE employee_id = ? ORDER BY created_at DESC LIMIT 4");
$mesConges->execute([$employee_id]);
$mesConges = $mesConges->fetchAll();

// Pointage aujourd'hui
$today = date('Y-m-d');
$pointage = $pdo->prepare("SELECT * FROM work_hours WHERE employee_id = ? AND date = ?");
$pointage->execute([$employee_id, $today]);
$pointage = $pointage->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Employé | GRH System</title>
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
                <div class="brand-sub">Espace Employé</div>
            </div>
        </div>
        <nav class="sidebar-nav">
            <div class="nav-section-title">Navigation</div>
            <a href="dashboard.php" class="nav-link active"><i class="bi bi-grid-1x2-fill"></i><span>Tableau de bord</span></a>
            <a href="profile.php" class="nav-link"><i class="bi bi-person-circle"></i><span>Mon Profil</span></a>
            <a href="work-hours.php" class="nav-link"><i class="bi bi-clock-history"></i><span>Mes Heures</span></a>
            <a href="leaves.php" class="nav-link"><i class="bi bi-calendar-check"></i><span>Mes Congés</span></a>
        </nav>
        <div class="sidebar-user">
            <div class="user-avatar"><?php echo getInitials($_SESSION['employee_name']); ?></div>
            <div class="user-info">
                <div class="user-name"><?php echo $_SESSION['employee_name']; ?></div>
                <div class="user-role">Employé</div>
            </div>
            <a href="../../backend/logout.php" class="btn btn-link text-muted p-0 ms-auto">
                <i class="bi bi-box-arrow-right fs-5"></i>
            </a>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-navbar">
            <div class="d-flex align-items-center gap-3">
                <button class="sidebar-toggle"><i class="bi bi-list"></i></button>
                <h1 class="page-title">Mon <span>Espace</span></h1>
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
                        <h4 class="fw-bold mb-1">Bonjour, <?php echo explode(' ', $_SESSION['employee_name'])[0]; ?> 👋</h4>
                        <p class="text-secondary mb-0">Bienvenue dans votre espace personnel.</p>
                    </div>
                    <div class="text-end">
                        <div class="fw-semibold" style="color: var(--accent);" id="empDate"></div>
                        <small class="text-muted" id="empTime"></small>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="glass-card stat-card slide-up">
                        <div class="stat-icon success"><i class="bi bi-clock-fill"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">Heures totales</div>
                            <div class="stat-value"><?php echo number_format($stats['total'] ?? 0, 1); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card stat-card slide-up">
                        <div class="stat-icon warning"><i class="bi bi-calendar-check"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">Jours travaillés</div>
                            <div class="stat-value"><?php echo $stats['jours'] ?? 0; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="glass-card stat-card slide-up">
                        <div class="stat-icon primary"><i class="bi bi-hourglass-split"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">Congés en cours</div>
                            <div class="stat-value"><?php echo $congesEnAttente; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-5">
                    <div class="glass-card slide-up">
                        <div class="clock-display">
                            <div class="time" id="bigClock">00:00:00</div>
                            <div class="date" id="bigDate"></div>
                        </div>
                        <div class="clock-actions text-center mb-3">
                            <?php if (!$pointage): ?>
                                <a href="work-hours.php?action=clockin" class="btn-clock-in">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Pointer l'entrée
                                </a>
                            <?php elseif (!$pointage['clock_out']): ?>
                                <a href="work-hours.php?action=clockout" class="btn-clock-out">
                                    <i class="bi bi-box-arrow-right me-2"></i>Pointer la sortie
                                </a>
                                <div class="mt-2 text-muted">
                                    <small>Entrée à <?php echo $pointage['clock_in']; ?></small>
                                </div>
                            <?php else: ?>
                                <div class="text-success">
                                    <i class="bi bi-check-circle-fill"></i> Journée terminée
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="glass-card data-table-wrapper slide-up">
                        <div class="data-table-header">
                            <h5><i class="bi bi-calendar-check me-2" style="color:var(--accent);"></i>Mes derniers congés</h5>
                            <a href="leaves.php" class="btn btn-outline-custom btn-sm">Voir tout</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table-dark-custom">
                                <thead>
                                    <tr><th>Type</th><th>Période</th><th>Statut</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($mesConges as $conge): ?>
                                    <tr>
                                        <td><?php echo $conge['type']; ?></td>
                                        <td><?php echo formatDate($conge['start_date']); ?> - <?php echo formatDate($conge['end_date']); ?></td>
                                        <td><?php echo getStatusBadge($conge['status']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/app.js"></script>
    <script>
        function updateClock() {
            const now = new Date();
            document.getElementById('empTime').textContent = now.toLocaleTimeString('fr-FR');
            document.getElementById('empDate').textContent = now.toLocaleDateString('fr-FR', { 
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' 
            });
            document.getElementById('bigClock').textContent = now.toLocaleTimeString('fr-FR');
            document.getElementById('bigDate').textContent = now.toLocaleDateString('fr-FR', { 
                weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' 
            });
        }
        updateClock();
        setInterval(updateClock, 1000);
    </script>
</body>
</html>