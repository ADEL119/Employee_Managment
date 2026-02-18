<?php
// backend/includes/auth.php
session_start();

function estConnecte() {
    return isset($_SESSION['user_id']);
}

function estAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

function estEmploye() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'employee';
}

function requireConnexion() {
    if (!estConnecte()) {
        header('Location: ../login.php');
        exit();
    }
}

function requireAdmin() {
    requireConnexion();
    if (!estAdmin()) {
        header('Location: ../employee/dashboard.php');
        exit();
    }
}

function requireEmploye() {
    requireConnexion();
    if (!estEmploye()) {
        header('Location: ../admin/dashboard.php');
        exit();
    }
}
?>