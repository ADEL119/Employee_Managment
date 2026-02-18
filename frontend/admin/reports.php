<?php
// frontend/admin/reports.php
require_once '../../backend/includes/auth.php';
require_once '../../backend/config/db.php';
requireAdmin();

// Statistiques générales
$totalEmployees = $pdo->query("SELECT COUNT(*) FROM employees WHERE status = 'active'")->fetchColumn();
$totalHours = $pdo->query("SELECT SUM(hours) FROM work_hours WHERE status = 'complete'")->fetchColumn() ?: 0;
$totalLeaves = $pdo->query("SELECT COUNT(*) FROM leaves")->fetchColumn();
$totalDepartments = $pdo->query("SELECT COUNT(DISTINCT department) FROM employees")->fetchColumn();

// Heures par employé
$hoursByEmployee = $pdo->query("SELECT 
    e.id, e.name, e.department,
    COUNT(DISTINCT w.date) as days_worked,
    SUM(w.hours) as total_hours,
    AVG(w.hours) as avg_hours
    FROM employees e
    LEFT JOIN work_hours w ON e.id = w.employee_id AND w.status = 'complete'
    WHERE e.status = 'active'
    GROUP BY e.id
    ORDER BY total_hours DESC
    LIMIT 10")->fetchAll();

// Congés par statut
$leavesByStatus = $pdo->query("SELECT 
    status, COUNT(*) as count
    FROM leaves
    GROUP BY status")->fetchAll();

// Employés par département
$employeesByDept = $pdo->query("SELECT 
    department, COUNT(*) as count
    FROM employees
    WHERE status = 'active'
    GROUP BY department
    ORDER BY count DESC")->fetchAll();

// Présence mensuelle (simulée pour les derniers 30 jours)
$monthlyAttendance = $pdo->query("SELECT 
    DATE_FORMAT(date, '%Y-%m-%d') as day,
    COUNT(*) as present_count,
    SUM(hours) as total_hours
    FROM work_hours
    WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY date
    ORDER BY date")->fetchAll();

// Format pour Chart.js
$chartDays = [];
$chartPresent = [];
$chartHours = [];

foreach ($monthlyAttendance as $day) {
    $chartDays[] = date('d/m', strtotime($day['day']));
    $chartPresent[] = $day['present_count'];
    $chartHours[] = round($day['total_hours'], 1);
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapports - Admin | GRH System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
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
            <a href="work-hours.php" class="nav-link"><i class="bi bi-clock-history"></i><span>Heures de travail</span></a>
            <a href="reports.php" class="nav-link active"><i class="bi bi-bar-chart-line"></i><span>Rapports</span></a>
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
                <h1 class="page-title"><span>Rapports</span> & Statistiques</h1>
            </div>
            <div class="navbar-actions">
                <button class="btn btn-primary-custom btn-sm" onclick="window.print()">
                    <i class="bi bi-printer me-1"></i>Imprimer
                </button>
            </div>
        </div>

        <div class="page-content">
            <!-- Stats Cards -->
            <div class="row g-4 mb-4">
                <div class="col-lg-3 col-sm-6">
                    <div class="glass-card stat-card slide-up">
                        <div class="stat-icon primary"><i class="bi bi-people-fill"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">Employés actifs</div>
                            <div class="stat-value"><?php echo $totalEmployees; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="glass-card stat-card slide-up">
                        <div class="stat-icon success"><i class="bi bi-clock-fill"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">Heures totales</div>
                            <div class="stat-value"><?php echo number_format($totalHours, 1); ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="glass-card stat-card slide-up">
                        <div class="stat-icon warning"><i class="bi bi-calendar-check"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">Congés totaux</div>
                            <div class="stat-value"><?php echo $totalLeaves; ?></div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-3 col-sm-6">
                    <div class="glass-card stat-card slide-up">
                        <div class="stat-icon info"><i class="bi bi-building"></i></div>
                        <div class="stat-info">
                            <div class="stat-label">Départements</div>
                            <div class="stat-value"><?php echo $totalDepartments; ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Graphiques -->
            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <div class="glass-card chart-card slide-up">
                        <h6><i class="bi bi-bar-chart me-2" style="color: var(--accent);"></i>Présence - 30 derniers jours</h6>
                        <canvas id="attendanceChart" height="300"></canvas>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="glass-card chart-card slide-up">
                        <h6><i class="bi bi-pie-chart me-2" style="color: var(--primary-light);"></i>Employés par département</h6>
                        <canvas id="deptChart" height="300"></canvas>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="glass-card chart-card slide-up">
                        <h6><i class="bi bi-graph-up me-2" style="color: var(--success);"></i>Statut des congés</h6>
                        <canvas id="leaveChart" height="250"></canvas>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="glass-card data-table-wrapper slide-up">
                        <div class="data-table-header">
                            <h5><i class="bi bi-list-check me-2" style="color: var(--warning);"></i>Top 10 - Heures travaillées</h5>
                        </div>
                        <div class="table-responsive">
                            <table class="table-dark-custom">
                                <thead>
                                    <tr>
                                        <th>Employé</th>
                                        <th>Département</th>
                                        <th>Jours</th>
                                        <th>Heures</th>
                                        <th>Moyenne</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($hoursByEmployee as $emp): ?>
                                    <tr>
                                        <td>
                                            <div class="employee-cell">
                                                <div class="emp-avatar"><?php echo getInitials($emp['name']); ?></div>
                                                <div><?php echo $emp['name']; ?></div>
                                            </div>
                                        </td>
                                        <td><?php echo $emp['department']; ?></td>
                                        <td><?php echo $emp['days_worked'] ?? 0; ?></td>
                                        <td><span class="fw-bold"><?php echo number_format($emp['total_hours'] ?? 0, 1); ?>h</span></td>
                                        <td><?php echo number_format($emp['avg_hours'] ?? 0, 1); ?>h</td>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/app.js"></script>
    <script>
        // Graphique de présence
        new Chart(document.getElementById('attendanceChart'), {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chartDays); ?>,
                datasets: [{
                    label: 'Présents',
                    data: <?php echo json_encode($chartPresent); ?>,
                    borderColor: 'rgb(99, 102, 241)',
                    backgroundColor: 'rgba(99, 102, 241, 0.1)',
                    tension: 0.4,
                    fill: true
                }, {
                    label: 'Heures',
                    data: <?php echo json_encode($chartHours); ?>,
                    borderColor: 'rgb(34, 211, 238)',
                    backgroundColor: 'rgba(34, 211, 238, 0.1)',
                    tension: 0.4,
                    fill: true,
                    yAxisID: 'y1'
                }]
            },
            options: {
                responsive: true,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { labels: { color: '#94a3b8' } } },
                scales: {
                    x: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(148,163,184,0.1)' } },
                    y: { 
                        ticks: { color: '#94a3b8' }, 
                        grid: { color: 'rgba(148,163,184,0.1)' },
                        title: { display: true, text: 'Nombre de présents', color: '#94a3b8' }
                    },
                    y1: {
                        position: 'right',
                        ticks: { color: '#94a3b8' },
                        grid: { drawOnChartArea: false },
                        title: { display: true, text: 'Heures', color: '#94a3b8' }
                    }
                }
            }
        });

        // Graphique des départements
        new Chart(document.getElementById('deptChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($employeesByDept, 'department')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($employeesByDept, 'count')); ?>,
                    backgroundColor: [
                        'rgba(99,102,241,0.8)',
                        'rgba(34,211,238,0.8)',
                        'rgba(16,185,129,0.8)',
                        'rgba(245,158,11,0.8)',
                        'rgba(239,68,68,0.8)'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#94a3b8', padding: 12, font: { size: 11 } }
                    }
                }
            }
        });

        // Graphique des congés
        new Chart(document.getElementById('leaveChart'), {
            type: 'doughnut',
            data: {
                labels: <?php echo json_encode(array_column($leavesByStatus, 'status')); ?>,
                datasets: [{
                    data: <?php echo json_encode(array_column($leavesByStatus, 'count')); ?>,
                    backgroundColor: [
                        'rgba(245,158,11,0.8)',
                        'rgba(16,185,129,0.8)',
                        'rgba(239,68,68,0.8)'
                    ],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                cutout: '65%',
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { color: '#94a3b8', padding: 12, font: { size: 11 } }
                    }
                }
            }
        });
    </script>
</body>
</html>