<?php
/**
 * Sistema de Logout - GuardianIA v3.0
 * Anderson Mamian Chicangana - Membresía Premium
 */

require_once 'config.php';
// Esto ya inicializa la sesión de forma segura

session_start();

// Registrar actividad de logout si hay sesión activa
$logout_info = [];
if (isset($_SESSION['username'])) {
    $logout_info = [
        'username' => $_SESSION['username'],
        'user_type' => $_SESSION['user_type'] ?? 'unknown',
        'logout_time' => date('Y-m-d H:i:s'),
        'session_duration' => isset($_SESSION['login_time']) ? (time() - $_SESSION['login_time']) : 0
    ];
}

// Limpiar todas las variables de sesión
$_SESSION = array();

// Eliminar la cookie de sesión si existe
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destruir la sesión completamente
session_destroy();

// Opcional: Registrar el logout en un log (si tienes sistema de logs)
if (!empty($logout_info)) {
    error_log("GuardianIA Logout: " . json_encode($logout_info));
}

// Redirigir al login con mensaje de logout exitoso
header('Location: login.php?message=logout_success');
exit;
?>