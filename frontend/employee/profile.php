<?php
// frontend/employee/profile.php
require_once '../../backend/includes/auth.php';
require_once '../../backend/config/db.php';
requireEmploye();

$employee_id = $_SESSION['employee_id'];

// Récupérer les informations de l'employé
$stmt = $pdo->prepare("SELECT * FROM employees WHERE id = ?");
$stmt->execute([$employee_id]);
$employee = $stmt->fetch();

// Statistiques congés
$leavesCount = $pdo->prepare("SELECT 
    SUM(CASE WHEN status = 'approved' THEN days ELSE 0 END) as approved_days,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
    COUNT(*) as total_requests
    FROM leaves 
    WHERE employee_id = ?");
$leavesCount->execute([$employee_id]);
$leaveStats = $leavesCount->fetch();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - Employé | GRH System</title>
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
            <a href="profile.php" class="nav-link active"><i class="bi bi-person-circle"></i><span>Mon Profil</span></a>
            <a href="leaves.php" class="nav-link"><i class="bi bi-calendar-check"></i><span>Mes Congés</span></a>
        </nav>
        <div class="sidebar-user">
            <div class="user-avatar"><?php echo getInitials($employee['name']); ?></div>
            <div class="user-info">
                <div class="user-name"><?php echo $employee['name']; ?></div>
                <div class="user-role">Employé</div>
            </div>
            <a href="../../backend/logout.php" class="btn btn-link text-muted p-0 ms-auto"><i class="bi bi-box-arrow-right"></i></a>
        </div>
    </aside>

    <main class="main-content">
        <div class="top-navbar">
            <div class="d-flex align-items-center gap-3">
                <button class="sidebar-toggle"><i class="bi bi-list"></i></button>
                <h1 class="page-title">Mon <span>Profil</span></h1>
            </div>
        </div>

        <div class="page-content">
            <div class="row g-4">
                <!-- Carte de profil -->
                <div class="col-lg-4">
                    <div class="glass-card slide-up">
                        <div class="profile-header text-center p-4">
                            <div class="profile-avatar mx-auto mb-3" style="width:100px;height:100px;font-size:2.5rem;">
                                <?php echo getInitials($employee['name']); ?>
                            </div>
                            <h3><?php echo $employee['name']; ?></h3>
                            <p class="text-secondary"><?php echo $employee['position']; ?></p>
                            <?php echo getStatusBadge($employee['status']); ?>
                        </div>
                        <div class="profile-info-list p-3">
                            <div class="info-item d-flex justify-content-between py-2 border-bottom">
                                <span class="text-secondary"><i class="bi bi-building me-2"></i>Département</span>
                                <span class="fw-semibold"><?php echo $employee['department']; ?></span>
                            </div>
                            <div class="info-item d-flex justify-content-between py-2 border-bottom">
                                <span class="text-secondary"><i class="bi bi-calendar3 me-2"></i>Date d'embauche</span>
                                <span class="fw-semibold"><?php echo formatDate($employee['join_date']); ?></span>
                            </div>
                            <div class="info-item d-flex justify-content-between py-2">
                                <span class="text-secondary"><i class="bi bi-hash me-2"></i>ID Employé</span>
                                <span class="fw-semibold">#<?php echo $employee['id']; ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Détails et statistiques -->
                <div class="col-lg-8">
                    <div class="glass-card slide-up mb-4">
                        <div class="data-table-header">
                            <h5><i class="bi bi-info-circle me-2" style="color: var(--primary-light);"></i>Informations personnelles</h5>
                        </div>
                        <div class="p-3">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="info-label text-secondary mb-1"><i class="bi bi-envelope me-2"></i>Email</div>
                                    <div class="fw-semibold"><?php echo $employee['email']; ?></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label text-secondary mb-1"><i class="bi bi-telephone me-2"></i>Téléphone</div>
                                    <div class="fw-semibold"><?php echo $employee['phone'] ?: 'Non renseigné'; ?></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label text-secondary mb-1"><i class="bi bi-briefcase me-2"></i>Poste</div>
                                    <div class="fw-semibold"><?php echo $employee['position']; ?></div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-label text-secondary mb-1"><i class="bi bi-building me-2"></i>Département</div>
                                    <div class="fw-semibold"><?php echo $employee['department']; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistiques congés -->
                    <div class="row g-4">
                        <div class="col-sm-6">
                            <div class="glass-card stat-card slide-up">
                                <div class="stat-icon primary"><i class="bi bi-calendar-check"></i></div>
                                <div class="stat-info">
                                    <div class="stat-label">Jours de congé pris</div>
                                    <div class="stat-value"><?php echo $leaveStats['approved_days'] ?? 0; ?></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="glass-card stat-card slide-up">
                                <div class="stat-icon warning"><i class="bi bi-hourglass-split"></i></div>
                                <div class="stat-info">
                                    <div class="stat-label">Demandes en attente</div>
                                    <div class="stat-value"><?php echo $leaveStats['pending_count'] ?? 0; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
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