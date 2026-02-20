<?php
// frontend/employee/leaves.php
require_once '../../backend/includes/auth.php';
require_once '../../backend/config/db.php';
requireEmploye();

$employee_id = $_SESSION['employee_id'];

// Traitement d'une nouvelle demande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $type = $_POST['type'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $reason = $_POST['reason'] ?? '';

    if ($type && $start_date && $end_date) {
        // Calculer le nombre de jours
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $interval = $start->diff($end);
        $days = $interval->days + 1;

        $stmt = $pdo->prepare("INSERT INTO leaves (employee_id, type, start_date, end_date, days, reason, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$employee_id, $type, $start_date, $end_date, $days, $reason]);

        $_SESSION['success'] = "Demande de congé soumise avec succès";
        header('Location: leaves.php');
        exit();
    }
}

// Traitement de la modification d'une demande
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $id = $_POST['id'] ?? 0;
    $type = $_POST['type'] ?? '';
    $start_date = $_POST['start_date'] ?? '';
    $end_date = $_POST['end_date'] ?? '';
    $reason = $_POST['reason'] ?? '';

    // Vérifier que la demande appartient bien à l'employé et est en attente
    $check = $pdo->prepare("SELECT id FROM leaves WHERE id = ? AND employee_id = ? AND status = 'pending'");
    $check->execute([$id, $employee_id]);
    
    if ($check->rowCount() > 0 && $type && $start_date && $end_date) {
        // Calculer le nombre de jours
        $start = new DateTime($start_date);
        $end = new DateTime($end_date);
        $interval = $start->diff($end);
        $days = $interval->days + 1;

        $stmt = $pdo->prepare("UPDATE leaves SET type = ?, start_date = ?, end_date = ?, days = ?, reason = ? WHERE id = ?");
        $stmt->execute([$type, $start_date, $end_date, $days, $reason, $id]);

        $_SESSION['success'] = "Demande de congé modifiée avec succès";
        header('Location: leaves.php');
        exit();
    }
}

// Traitement de l'annulation d'une demande
if (isset($_GET['cancel']) && isset($_GET['id'])) {
    $id = $_GET['id'];
    
    // Vérifier que la demande appartient bien à l'employé et est en attente
    $check = $pdo->prepare("SELECT id FROM leaves WHERE id = ? AND employee_id = ? AND status = 'pending'");
    $check->execute([$id, $employee_id]);
    
    if ($check->rowCount() > 0) {
        $stmt = $pdo->prepare("DELETE FROM leaves WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = "Demande de congé annulée avec succès";
    } else {
        $_SESSION['error'] = "Impossible d'annuler cette demande";
    }
    
    header('Location: leaves.php');
    exit();
}

// Récupérer les congés de l'employé
$stmt = $pdo->prepare("SELECT * FROM leaves WHERE employee_id = ? ORDER BY created_at DESC");
$stmt->execute([$employee_id]);
$leaves = $stmt->fetchAll();

// Statistiques
$stats = $pdo->prepare("SELECT 
    SUM(CASE WHEN status = 'approved' THEN days ELSE 0 END) as approved_days,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count
    FROM leaves WHERE employee_id = ?");
$stats->execute([$employee_id]);
$stats = $stats->fetch();

// Message flash
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mes Congés - Employé | GRH System</title>
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
            <a href="leaves.php" class="nav-link active"><i class="bi bi-calendar-check"></i><span>Mes Congés</span></a>
            <a href="work-hours.php" class="nav-link"><i class="bi bi-clock-history"></i><span>Mes Heures</span></a>
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
                <h1 class="page-title">Mes <span>Congés</span></h1>
            </div>
        </div>

        <div class="page-content">
            <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php endif; ?>

            <!-- Stats -->
            <div class="row g-4 mb-4">
                <div class="col-md-3 col-sm-6">
                    <div class="glass-card stat-card slide-up">
                        <div class="stat-icon primary"><i class="bi bi-calendar3"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">Jours pris</div>
                            <div class="stat-value"><?php echo $stats['approved_days'] ?? 0; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="glass-card stat-card slide-up">
                        <div class="stat-icon success"><i class="bi bi-check-circle"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">Approuvés</div>
                            <div class="stat-value"><?php echo $stats['approved_count'] ?? 0; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="glass-card stat-card slide-up">
                        <div class="stat-icon warning"><i class="bi bi-hourglass-split"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">En attente</div>
                            <div class="stat-value"><?php echo $stats['pending_count'] ?? 0; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="glass-card stat-card slide-up">
                        <div class="stat-icon danger"><i class="bi bi-x-circle"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">Refusés</div>
                            <div class="stat-value"><?php echo $stats['rejected_count'] ?? 0; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Nouvelle demande -->
            <div class="d-flex justify-content-end mb-3">
                <button class="btn btn-primary-custom" data-bs-toggle="modal" data-bs-target="#leaveModal" onclick="resetModal()">
                    <i class="bi bi-plus-lg me-2"></i>Nouvelle demande
                </button>
            </div>

            <!-- Liste des congés -->
            <div class="glass-card data-table-wrapper slide-up">
                <div class="data-table-header">
                    <h5><i class="bi bi-list-check me-2" style="color: var(--accent);"></i>Mes demandes de congé</h5>
                </div>
                <div class="table-responsive">
                    <table class="table-dark-custom">
                        <thead>
                            <tr>
                                <th>Type</th>
                                <th>Période</th>
                                <th>Jours</th>
                                <th>Raison</th>
                                <th>Statut</th>
                                <th>Date demande</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($leaves as $leave): ?>
                            <tr>
                                <td><?php echo $leave['type']; ?></td>
                                <td><?php echo formatDate($leave['start_date']); ?> - <?php echo formatDate($leave['end_date']); ?></td>
                                <td><?php echo $leave['days']; ?></td>
                                <td><?php echo $leave['reason'] ?: '-'; ?></td>
                                <td><?php echo getStatusBadge($leave['status']); ?></td>
                                <td><?php echo formatDate($leave['created_at']); ?></td>
                                <td>
                                    <?php if ($leave['status'] === 'pending'): ?>
                                    <div class="action-btns">
                                        <button class="btn-action" title="Modifier" onclick='editLeave(<?php echo json_encode($leave, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'>
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <a href="?cancel=1&id=<?php echo $leave['id']; ?>" class="btn-action danger" title="Annuler" onclick="return confirm('Êtes-vous sûr de vouloir annuler cette demande ?')">
                                            <i class="bi bi-x-lg"></i>
                                        </a>
                                    </div>
                                    <?php else: ?>
                                    <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal Nouvelle demande / Modification -->
    <div class="modal fade" id="leaveModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitle">Nouvelle demande de congé</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="leaveForm">
                    <input type="hidden" name="action" id="formAction" value="add">
                    <input type="hidden" name="id" id="leaveId">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Type de congé</label>
                            <select class="form-select" name="type" id="leaveType" required>
                                <option value="">Sélectionner</option>
                                <option>Annuel</option>
                                <option>Maladie</option>
                                <option>Personnel</option>
                                <option>Familial</option>
                                <option>Sans solde</option>
                            </select>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-6">
                                <label class="form-label">Date début</label>
                                <input type="date" class="form-control" name="start_date" id="leaveStart" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label">Date fin</label>
                                <input type="date" class="form-control" name="end_date" id="leaveEnd" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Raison</label>
                            <textarea class="form-control" name="reason" id="leaveReason" rows="3" placeholder="Décrivez la raison de votre demande..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-custom" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-primary-custom" id="submitBtn">
                            <i class="bi bi-send me-1"></i><span id="submitText">Soumettre</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

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
        // Fonction pour réinitialiser le modal (nouvelle demande)
        function resetModal() {
            document.getElementById('modalTitle').textContent = 'Nouvelle demande de congé';
            document.getElementById('formAction').value = 'add';
            document.getElementById('submitText').textContent = 'Soumettre';
            document.getElementById('leaveId').value = '';
            document.getElementById('leaveType').value = '';
            document.getElementById('leaveStart').value = '';
            document.getElementById('leaveEnd').value = '';
            document.getElementById('leaveReason').value = '';
        }

        // Fonction pour éditer une demande (remplit le formulaire)
        function editLeave(leave) {
            // Changer le titre
            document.getElementById('modalTitle').textContent = 'Modifier la demande de congé';
            
            // Changer l'action
            document.getElementById('formAction').value = 'edit';
            
            // Changer le texte du bouton
            document.getElementById('submitText').textContent = 'Modifier';
            
            // Remplir tous les champs avec les données
            document.getElementById('leaveId').value = leave.id;
            document.getElementById('leaveType').value = leave.type;
            document.getElementById('leaveStart').value = leave.start_date;
            document.getElementById('leaveEnd').value = leave.end_date;
            document.getElementById('leaveReason').value = leave.reason;
            
            // Ouvrir le modal
            new bootstrap.Modal(document.getElementById('leaveModal')).show();
        }

        // Empêcher la réinitialisation quand on ouvre le modal pour modification
        document.getElementById('leaveModal').addEventListener('show.bs.modal', function(event) {
            // Si le modal est ouvert sans passer par editLeave, on ne fait rien
            // car editLeave a déjà rempli les champs
        });
    </script>
</body>
</html>