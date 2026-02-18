<?php
// frontend/employee/work-hours.php
require_once '../../backend/includes/auth.php';
require_once '../../backend/config/db.php';
requireEmploye();

$employee_id = $_SESSION['employee_id'];

// Traitement du pointage
if (isset($_GET['action'])) {
    $today = date('Y-m-d');
    $now = date('H:i:s');
    
    if ($_GET['action'] === 'clockin') {
        // Vérifier s'il n'y a pas déjà un pointage aujourd'hui
        $check = $pdo->prepare("SELECT id FROM work_hours WHERE employee_id = ? AND date = ?");
        $check->execute([$employee_id, $today]);
        
        if ($check->rowCount() == 0) {
            $stmt = $pdo->prepare("INSERT INTO work_hours (employee_id, date, clock_in, status) VALUES (?, ?, ?, 'in-progress')");
            $stmt->execute([$employee_id, $today, $now]);
            $_SESSION['success'] = "Entrée enregistrée à " . date('H:i');
        }
    }
    
    if ($_GET['action'] === 'clockout') {
        // Mettre à jour le pointage
        $stmt = $pdo->prepare("UPDATE work_hours SET clock_out = ?, status = 'complete' WHERE employee_id = ? AND date = ?");
        $stmt->execute([$now, $employee_id, $today]);
        
        // Calculer les heures
        $record = $pdo->prepare("SELECT clock_in FROM work_hours WHERE employee_id = ? AND date = ?");
        $record->execute([$employee_id, $today]);
        $rec = $record->fetch();
        
        if ($rec) {
            $clock_in = new DateTime($rec['clock_in']);
            $clock_out = new DateTime($now);
            $interval = $clock_in->diff($clock_out);
            $hours = $interval->h + ($interval->i / 60);
            
            $stmt = $pdo->prepare("UPDATE work_hours SET hours = ? WHERE employee_id = ? AND date = ?");
            $stmt->execute([$hours, $employee_id, $today]);
        }
        
        $_SESSION['success'] = "Sortie enregistrée à " . date('H:i');
    }
    
    header('Location: work-hours.php');
    exit();
}

// Récupérer l'historique
$stmt = $pdo->prepare("SELECT * FROM work_hours WHERE employee_id = ? ORDER BY date DESC");
$stmt->execute([$employee_id]);
$workHours = $stmt->fetchAll();

// Statistiques
$stats = $pdo->prepare("SELECT 
    SUM(hours) as total_hours,
    COUNT(DISTINCT date) as days_worked,
    AVG(hours) as avg_hours,
    MAX(hours) as max_hours
    FROM work_hours 
    WHERE employee_id = ? AND status = 'complete'");
$stats->execute([$employee_id]);
$stats = $stats->fetch();

// Pointage aujourd'hui
$today = date('Y-m-d');
$todayRecord = $pdo->prepare("SELECT * FROM work_hours WHERE employee_id = ? AND date = ?");
$todayRecord->execute([$employee_id, $today]);
$todayRecord = $todayRecord->fetch();

// Message flash
$success = $_SESSION['success'] ?? '';
unset($_SESSION['success']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Heures - Employé | GRH System</title>
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
            <a href="dashboard.php" class="nav-link"><i class="bi bi-grid-1x2-fill"></i><span>Tableau de bord</span></a>
            <a href="profile.php" class="nav-link"><i class="bi bi-person-circle"></i><span>Mon Profil</span></a>
            <a href="work-hours.php" class="nav-link active"><i class="bi bi-clock-history"></i><span>Mes Heures</span></a>
            <a href="leaves.php" class="nav-link"><i class="bi bi-calendar-check"></i><span>Mes Congés</span></a>
        </nav>
        <div class="sidebar-user">
            <div class="user-avatar"><?php echo getInitials($_SESSION['employee_name']); ?></div>
            <div class="user-info">
                <div class="user-name"><?php echo $_SESSION['employee_name']; ?></div>
                <div class="user-role">Employé</div>
            </div>
            <a href="../../backend/logout.php" class="btn btn-link text-muted p-0 ms-auto"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-navbar">
            <div class="d-flex align-items-center gap-3">
                <button class="sidebar-toggle"><i class="bi bi-list"></i></button>
                <h1 class="page-title">Mes <span>Heures</span></h1>
            </div>
        </div>

        <div class="page-content">
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Horloge et pointage -->
            <div class="row g-4 mb-4">
                <div class="col-lg-5">
                    <div class="glass-card slide-up p-4">
                        <div class="clock-display text-center mb-4">
                            <div class="time display-3 fw-bold" id="bigClock">00:00:00</div>
                            <div class="date h5 text-secondary" id="bigDate"></div>
                        </div>
                        
                        <div class="clock-actions text-center">
                            <?php if (!$todayRecord): ?>
                                <a href="?action=clockin" class="btn-clock-in w-100">
                                    <i class="bi bi-box-arrow-in-right me-2"></i>Pointer l'entrée
                                </a>
                            <?php elseif (!$todayRecord['clock_out']): ?>
                                <a href="?action=clockout" class="btn-clock-out w-100">
                                    <i class="bi bi-box-arrow-right me-2"></i>Pointer la sortie
                                </a>
                                <div class="mt-3 text-secondary">
                                    <i class="bi bi-info-circle"></i> Entrée à <?php echo $todayRecord['clock_in']; ?>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-success mb-0">
                                    <i class="bi bi-check-circle-fill"></i> Journée terminée - <?php echo number_format($todayRecord['hours'], 1); ?>h
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Statistiques -->
                <div class="col-lg-7">
                    <div class="row g-4">
                        <div class="col-sm-6">
                            <div class="glass-card stat-card slide-up">
                                <div class="stat-icon success"><i class="bi bi-clock-fill"></i></div>
                                <div class="stat-info">
                                    <div class="stat-label">Heures totales</div>
                                    <div class="stat-value"><?php echo number_format($stats['total_hours'] ?? 0, 1); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="glass-card stat-card slide-up">
                                <div class="stat-icon primary"><i class="bi bi-calendar-check"></i></div>
                                <div class="stat-info">
                                    <div class="stat-label">Jours travaillés</div>
                                    <div class="stat-value"><?php echo $stats['days_worked'] ?? 0; ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="glass-card stat-card slide-up">
                                <div class="stat-icon info"><i class="bi bi-calculator"></i></div>
                                <div class="stat-info">
                                    <div class="stat-label">Moyenne / jour</div>
                                    <div class="stat-value"><?php echo number_format($stats['avg_hours'] ?? 0, 1); ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="glass-card stat-card slide-up">
                                <div class="stat-icon warning"><i class="bi bi-arrow-up-circle"></i></div>
                                <div class="stat-info">
                                    <div class="stat-label">Max journée</div>
                                    <div class="stat-value"><?php echo number_format($stats['max_hours'] ?? 0, 1); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Historique -->
            <div class="glass-card data-table-wrapper slide-up">
                <div class="data-table-header">
                    <h5><i class="bi bi-table me-2" style="color: var(--primary-light);"></i>Historique des pointages</h5>
                </div>
                <div class="table-responsive">
                    <table class="table-dark-custom">
                        <thead>
                            <tr>
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
                                <td><?php echo formatDate($wh['date']); ?></td>
                                <td><span style="color:var(--success-light);"><?php echo $wh['clock_in']; ?></span></td>
                                <td><?php echo $wh['clock_out'] ? '<span style="color:var(--danger-light);">' . $wh['clock_out'] . '</span>' : '-'; ?></td>
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
    <script>
        function updateClock() {
            const now = new Date();
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