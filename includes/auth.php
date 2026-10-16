<?php
// includes/auth.php
require_once 'config.php';

function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
}

function isPolice() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'police';
}

function isHospital() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'hospital';
}

function isINAS() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'inas';
}

function getUserTypeBadge($user_type) {
    $badges = [
        'admin' => '<span class="user-badge badge-admin">Administrador</span>',
        'police' => '<span class="user-badge badge-police">Polícia</span>',
        'hospital' => '<span class="user-badge badge-hospital">Hospital</span>',
        'inas' => '<span class="user-badge badge-inas">INAS</span>'
    ];
    return $badges[$user_type] ?? '<span class="user-badge">Desconhecido</span>';
}

function logout() {
    session_destroy();
    header('Location: login.php');
    exit();
}
?>