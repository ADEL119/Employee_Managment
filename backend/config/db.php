<?php
// backend/config/db.php

$host = 'localhost';
$dbname = 'grh_system';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Erreur de connexion à la base de données: " . $e->getMessage());
}

// Fonctions utilitaires
function formatDate($date) {
    return date('d/m/Y', strtotime($date));
}

function formatDateYMD($date) {
    return date('Y-m-d', strtotime($date));
}

function getInitials($name) {
    $words = explode(' ', $name);
    $initials = '';
    foreach ($words as $word) {
        $initials .= strtoupper(substr($word, 0, 1));
    }
    return substr($initials, 0, 2);
}

function getStatusBadge($status) {
    $classes = [
        'pending' => 'warning',
        'approved' => 'success',
        'rejected' => 'danger',
        'active' => 'success',
        'inactive' => 'secondary',
        'in-progress' => 'info',
        'complete' => 'success'
    ];
    
    $labels = [
        'pending' => 'En attente',
        'approved' => 'Approuvé',
        'rejected' => 'Refusé',
        'active' => 'Actif',
        'inactive' => 'Inactif',
        'in-progress' => 'En cours',
        'complete' => 'Terminé'
    ];
    
    $class = $classes[$status] ?? 'secondary';
    $label = $labels[$status] ?? $status;
    
    return "<span class='status-badge $class'>$label</span>";
}

function calculateDays($start, $end) {
    $start_dt = new DateTime($start);
    $end_dt = new DateTime($end);
    $interval = $start_dt->diff($end_dt);
    return $interval->days + 1;
}

function showToast($message, $type = 'success') {
    $_SESSION['toast'] = ['message' => $message, 'type' => $type];
}
?>
