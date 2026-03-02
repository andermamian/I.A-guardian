<?php
/**
 * User Settings - GuardianIA v3.0 FINAL CORREGIDO
 * Configuración de usuario con todas las funcionalidades
 * Anderson Mamian Chicangana
 * Versión final sin errores de campos
 */

// Verificar si la sesión ya está activa antes de iniciarla
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir configuraciones
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/config_military.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit();
}

// Obtener información del usuario actual
$current_user_id = $_SESSION['user_id'] ?? 0;
$current_username = $_SESSION['username'] ?? 'guest';
$user_data = null;
$update_message = '';
$update_type = '';

// Obtener instancia de base de datos
$db = null;
$conn = null;

try {
    if (class_exists('MilitaryDatabaseManager')) {
        $db = MilitaryDatabaseManager::getInstance();
        if ($db && method_exists($db, 'getConnection')) {
            $conn = $db->getConnection();
        }
    }
} catch (Exception $e) {
    error_log("Error conectando a BD: " . $e->getMessage());
}

// Función para manejar subida de foto de perfil
function handleProfilePictureUpload($user_id) {
    $upload_dir = __DIR__ . '/uploads/profile_pictures/';
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $max_size = 5 * 1024 * 1024; // 5MB
    
    // Crear directorio si no existe
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error en la subida del archivo');
    }
    
    $file = $_FILES['profile_picture'];
    
    // Validar tipo de archivo
    if (!in_array($file['type'], $allowed_types)) {
        throw new Exception('Tipo de archivo no permitido. Solo se permiten: JPG, PNG, GIF, WEBP');
    }
    
    // Validar tamaño
    if ($file['size'] > $max_size) {
        throw new Exception('El archivo es demasiado grande. Máximo 5MB');
    }
    
    // Validar que es una imagen real
    $image_info = getimagesize($file['tmp_name']);
    if ($image_info === false) {
        throw new Exception('El archivo no es una imagen válida');
    }
    
    // Generar nombre único
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'profile_' . $user_id . '_' . time() . '.' . $extension;
    $filepath = $upload_dir . $filename;
    
    // Eliminar foto anterior si existe
    global $db, $conn;
    if ($db && method_exists($db, 'isConnected') && $db->isConnected() && $conn) {
        try {
            $stmt = $conn->prepare("SELECT profile_picture FROM user_settings WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($row = $result->fetch_assoc() && !empty($row['profile_picture'])) {
                $old_file = $upload_dir . basename($row['profile_picture']);
                if (file_exists($old_file)) {
                    unlink($old_file);
                }
            }
            $stmt->close();
        } catch (Exception $e) {
            error_log('Error eliminando foto anterior: ' . $e->getMessage());
        }
    }
    
    // Mover archivo
    if (!move_uploaded_file($file['tmp_name'], $filepath)) {
        throw new Exception('Error al guardar el archivo');
    }
    
    // Redimensionar imagen si es necesario
    resizeImage($filepath, 300, 300);
    
    return 'uploads/profile_pictures/' . $filename;
}

// Función para redimensionar imagen
function resizeImage($filepath, $max_width, $max_height) {
    $image_info = getimagesize($filepath);
    $width = $image_info[0];
    $height = $image_info[1];
    $type = $image_info[2];
    
    // Si la imagen ya es del tamaño correcto, no hacer nada
    if ($width <= $max_width && $height <= $max_height) {
        return;
    }
    
    // Calcular nuevas dimensiones manteniendo proporción
    $ratio = min($max_width / $width, $max_height / $height);
    $new_width = intval($width * $ratio);
    $new_height = intval($height * $ratio);
    
    // Crear imagen desde archivo
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($filepath);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($filepath);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($filepath);
            break;
        case IMAGETYPE_WEBP:
            $source = imagecreatefromwebp($filepath);
            break;
        default:
            return;
    }
    
    // Crear nueva imagen
    $destination = imagecreatetruecolor($new_width, $new_height);
    
    // Preservar transparencia para PNG y GIF
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($destination, false);
        imagesavealpha($destination, true);
        $transparent = imagecolorallocatealpha($destination, 255, 255, 255, 127);
        imagefilledrectangle($destination, 0, 0, $new_width, $new_height, $transparent);
    }
    
    // Redimensionar
    imagecopyresampled($destination, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    // Guardar imagen redimensionada
    switch ($type) {
        case IMAGETYPE_JPEG:
            imagejpeg($destination, $filepath, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($destination, $filepath, 9);
            break;
        case IMAGETYPE_GIF:
            imagegif($destination, $filepath);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($destination, $filepath, 90);
            break;
    }
    
    // Limpiar memoria
    imagedestroy($source);
    imagedestroy($destination);
}

// Función para obtener datos del usuario
function getUserData($user_id) {
    global $db, $conn;
    
    if (!$db || !method_exists($db, 'isConnected') || !$db->isConnected() || !$conn) {
        return null;
    }
    
    try {
        $stmt = $conn->prepare("SELECT u.*, us.profile_picture FROM users u LEFT JOIN user_settings us ON u.id = us.user_id WHERE u.id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user_data = $result->fetch_assoc();
        $stmt->close();
        
        return $user_data;
    } catch (Exception $e) {
        error_log('Error obteniendo datos del usuario: ' . $e->getMessage());
        return null;
    }
}

// Función para guardar configuraciones de usuario
function saveUserSettings($user_id, $settings) {
    global $db, $conn;
    
    if (!$db || !method_exists($db, 'isConnected') || !$db->isConnected() || !$conn) {
        return false;
    }
    
    try {
        // Construir la consulta dinámicamente
        $fields = [];
        $values = [];
        $update_fields = [];
        $params = [$user_id];
        $types = 'i';
        
        foreach ($settings as $key => $value) {
            $fields[] = $key;
            $values[] = '?';
            $update_fields[] = "$key = VALUES($key)";
            $params[] = $value;
            $types .= is_int($value) ? 'i' : 's';
        }
        
        $sql = "INSERT INTO user_settings (user_id, " . implode(', ', $fields) . ") VALUES (?, " . implode(', ', $values) . ") ON DUPLICATE KEY UPDATE " . implode(', ', $update_fields) . ", updated_at = CURRENT_TIMESTAMP";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (Exception $e) {
        error_log('Error guardando configuraciones: ' . $e->getMessage());
        return false;
    }
}

// Función para obtener configuraciones de usuario
function getUserSettings($user_id) {
    global $db, $conn;
    
    if (!$db || !method_exists($db, 'isConnected') || !$db->isConnected() || !$conn) {
        return [];
    }
    
    try {
        $stmt = $conn->prepare("SELECT * FROM user_settings WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $settings = $result->fetch_assoc();
        $stmt->close();
        
        return $settings ?: [];
    } catch (Exception $e) {
        error_log('Error obteniendo configuraciones: ' . $e->getMessage());
        return [];
    }
}

// Procesar actualizaciones del formulario
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $conn) {
    $action = $_POST['action'] ?? '';
    
    try {
        switch($action) {
            case 'update_profile':
                $fullname = trim($_POST['fullname'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $phone = trim($_POST['phone'] ?? '');
                $bio = trim($_POST['bio'] ?? '');
                $company = trim($_POST['company'] ?? '');
                $position = trim($_POST['position'] ?? '');
                $location = trim($_POST['location'] ?? '');
                $website = trim($_POST['website'] ?? '');
                $linkedin = trim($_POST['linkedin'] ?? '');
                $twitter = trim($_POST['twitter'] ?? '');
                $github = trim($_POST['github'] ?? '');
                
                // Validar campos requeridos
                if (empty($fullname)) {
                    throw new Exception("El nombre completo es requerido");
                }
                
                if (empty($email)) {
                    throw new Exception("El email es requerido");
                }
                
                // Validar email
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Email inválido");
                }
                
                // Validar URL del sitio web si se proporciona
                if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
                    throw new Exception("URL del sitio web inválida");
                }
                
                // Verificar si el email ya existe
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->bind_param("si", $email, $current_user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    throw new Exception("Este email ya está en uso");
                }
                $stmt->close();
                
                // Manejar subida de foto de perfil
                $profile_picture_path = null;
                if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
                    $profile_picture_path = handleProfilePictureUpload($current_user_id);
                }
                
                // Actualizar perfil en tabla users
                $stmt = $conn->prepare("UPDATE users SET fullname = ?, email = ?, phone = ?, bio = ?, company = ?, position = ?, location = ?, website = ?, linkedin = ?, twitter = ?, github = ?, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("sssssssssssi", $fullname, $email, $phone, $bio, $company, $position, $location, $website, $linkedin, $twitter, $github, $current_user_id);
                
                if (!$stmt->execute()) {
                    throw new Exception("Error al actualizar perfil: " . $stmt->error);
                }
                $stmt->close();
                
                // Actualizar foto de perfil si se subió una nueva
                if ($profile_picture_path) {
                    $settings = ['profile_picture' => $profile_picture_path];
                    if (!saveUserSettings($current_user_id, $settings)) {
                        throw new Exception("Error al actualizar foto de perfil");
                    }
                }
                
                $update_message = "Perfil actualizado correctamente";
                $update_type = "success";
                
                // Actualizar sesión
                $_SESSION['username'] = $fullname;
                $_SESSION['email'] = $email;
                
                // Registrar el cambio
                if (function_exists('logSettingsChange')) {
                    logSettingsChange('profile', 'updated', $fullname, $current_user_id);
                }
                break;
                
            case 'change_password':
                $current_password = $_POST['current_password'] ?? '';
                $new_password = $_POST['new_password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                
                if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
                    throw new Exception("Todos los campos de contraseña son requeridos");
                }
                
                if ($new_password !== $confirm_password) {
                    throw new Exception("Las contraseñas nuevas no coinciden");
                }
                
                // Validar fortaleza de contraseña
                if (function_exists('validatePasswordStrength')) {
                    $password_errors = validatePasswordStrength($new_password);
                    if (!empty($password_errors)) {
                        throw new Exception(implode(", ", $password_errors));
                    }
                }
                
                // Verificar contraseña actual
                $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
                $stmt->bind_param("i", $current_user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user_row = $result->fetch_assoc();
                $stmt->close();
                
                if (!$user_row || !password_verify($current_password, $user_row['password_hash'])) {
                    throw new Exception("Contraseña actual incorrecta");
                }
                
                // Actualizar contraseña
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password_hash = ?, password = ?, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("ssi", $new_hash, $new_password, $current_user_id);
                
                if (!$stmt->execute()) {
                    throw new Exception("Error al actualizar contraseña: " . $stmt->error);
                }
                $stmt->close();
                
                $update_message = "Contraseña actualizada correctamente";
                $update_type = "success";
                
                // Registrar cambio de contraseña
                if (function_exists('logSecurityEvent')) {
                    logSecurityEvent('password_change', 'Contraseña cambiada exitosamente', 'medium', $current_user_id);
                }
                break;
                
            case 'update_notifications':
                $settings = [
                    'notifications_email' => isset($_POST['email_notifications']) ? 1 : 0,
                    'notifications_push' => isset($_POST['push_notifications']) ? 1 : 0,
                    'notifications_sms' => isset($_POST['sms_notifications']) ? 1 : 0,
                    'security_alerts' => isset($_POST['security_alerts']) ? 1 : 0,
                    'system_updates' => isset($_POST['system_updates']) ? 1 : 0,
                    'marketing_emails' => isset($_POST['marketing_emails']) ? 1 : 0,
                    'newsletter' => isset($_POST['newsletter']) ? 1 : 0,
                    'product_updates' => isset($_POST['product_updates']) ? 1 : 0,
                    'weekly_reports' => isset($_POST['weekly_reports']) ? 1 : 0,
                    'monthly_summary' => isset($_POST['monthly_summary']) ? 1 : 0
                ];
                
                if (saveUserSettings($current_user_id, $settings)) {
                    $update_message = "Preferencias de notificación actualizadas correctamente";
                    $update_type = "success";
                    if (function_exists('logSettingsChange')) {
                        logSettingsChange('notifications', 'updated', json_encode($settings), $current_user_id);
                    }
                } else {
                    throw new Exception("Error al actualizar notificaciones");
                }
                break;
                
            case 'update_privacy':
                $settings = [
                    'data_sharing' => isset($_POST['data_sharing']) ? 1 : 0,
                    'analytics' => isset($_POST['analytics']) ? 1 : 0,
                    'cookies' => isset($_POST['cookies']) ? 1 : 0,
                    'tracking' => isset($_POST['tracking']) ? 1 : 0,
                    'profile_visibility' => $_POST['profile_visibility'] ?? 'private',
                    'search_visibility' => isset($_POST['search_visibility']) ? 1 : 0,
                    'activity_status' => isset($_POST['activity_status']) ? 1 : 0,
                    'contact_info_visible' => isset($_POST['contact_info_visible']) ? 1 : 0
                ];
                
                if (saveUserSettings($current_user_id, $settings)) {
                    $update_message = "Configuración de privacidad actualizada correctamente";
                    $update_type = "success";
                    if (function_exists('logSettingsChange')) {
                        logSettingsChange('privacy', 'updated', json_encode($settings), $current_user_id);
                    }
                } else {
                    throw new Exception("Error al actualizar configuración de privacidad");
                }
                break;
                
            case 'update_security':
                $settings = [
                    'two_factor_enabled' => isset($_POST['two_factor']) ? 1 : 0,
                    'biometric_enabled' => isset($_POST['biometric']) ? 1 : 0,
                    'vpn_enabled' => isset($_POST['vpn']) ? 1 : 0,
                    'firewall_enabled' => isset($_POST['firewall']) ? 1 : 0,
                    'antivirus_enabled' => isset($_POST['antivirus']) ? 1 : 0,
                    'auto_logout' => isset($_POST['auto_logout']) ? 1 : 0,
                    'session_timeout' => intval($_POST['session_timeout'] ?? 30),
                    'login_alerts' => isset($_POST['login_alerts']) ? 1 : 0,
                    'suspicious_activity_alerts' => isset($_POST['suspicious_activity_alerts']) ? 1 : 0,
                    'device_management' => isset($_POST['device_management']) ? 1 : 0
                ];
                
                if (saveUserSettings($current_user_id, $settings)) {
                    $update_message = "Configuración de seguridad actualizada correctamente";
                    $update_type = "success";
                    if (function_exists('logSecurityEvent')) {
                        logSecurityEvent('security_settings_change', 'Configuración de seguridad modificada', 'medium', $current_user_id);
                    }
                } else {
                    throw new Exception("Error al actualizar configuración de seguridad");
                }
                break;
                
            case 'update_performance':
                $settings = [
                    'auto_optimization' => isset($_POST['auto_optimization']) ? 1 : 0,
                    'real_time_scan' => isset($_POST['real_time_scan']) ? 1 : 0,
                    'gaming_mode' => isset($_POST['gaming_mode']) ? 1 : 0,
                    'startup_boost' => isset($_POST['startup_boost']) ? 1 : 0,
                    'memory_optimization' => isset($_POST['memory_optimization']) ? 1 : 0,
                    'performance_mode' => $_POST['performance_mode'] ?? 'balanced',
                    'scan_frequency' => $_POST['scan_frequency'] ?? 'daily',
                    'disk_cleanup' => $_POST['disk_cleanup'] ?? 'weekly',
                    'cache_management' => isset($_POST['cache_management']) ? 1 : 0,
                    'background_apps' => isset($_POST['background_apps']) ? 1 : 0,
                    'cpu_priority' => $_POST['cpu_priority'] ?? 'normal',
                    'network_optimization' => isset($_POST['network_optimization']) ? 1 : 0
                ];
                
                if (saveUserSettings($current_user_id, $settings)) {
                    $update_message = "Configuración de rendimiento actualizada correctamente";
                    $update_type = "success";
                    if (function_exists('logSettingsChange')) {
                        logSettingsChange('performance', 'updated', json_encode($settings), $current_user_id);
                    }
                } else {
                    throw new Exception("Error al actualizar configuración de rendimiento");
                }
                break;
                
            case 'update_appearance':
                $settings = [
                    'theme' => $_POST['theme'] ?? 'dark',
                    'language' => $_POST['language'] ?? 'es',
                    'timezone' => $_POST['timezone'] ?? 'America/Bogota',
                    'font_size' => $_POST['font_size'] ?? 'medium',
                    'color_scheme' => $_POST['color_scheme'] ?? 'default',
                    'sidebar_collapsed' => isset($_POST['sidebar_collapsed']) ? 1 : 0,
                    'animations_enabled' => isset($_POST['animations_enabled']) ? 1 : 0,
                    'sound_effects' => isset($_POST['sound_effects']) ? 1 : 0,
                    'high_contrast' => isset($_POST['high_contrast']) ? 1 : 0,
                    'reduced_motion' => isset($_POST['reduced_motion']) ? 1 : 0
                ];
                
                if (saveUserSettings($current_user_id, $settings)) {
                    $update_message = "Configuración de apariencia actualizada correctamente";
                    $update_type = "success";
                    if (function_exists('logSettingsChange')) {
                        logSettingsChange('appearance', 'updated', json_encode($settings), $current_user_id);
                    }
                } else {
                    throw new Exception("Error al actualizar configuración de apariencia");
                }
                break;
                
            case 'delete_account':
                $confirm_password = $_POST['confirm_password'] ?? '';
                $confirmation_text = $_POST['confirmation_text'] ?? '';
                
                if ($confirmation_text !== 'ELIMINAR MI CUENTA') {
                    throw new Exception("Debe escribir exactamente 'ELIMINAR MI CUENTA' para confirmar");
                }
                
                // Verificar contraseña
                $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
                $stmt->bind_param("i", $current_user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user_row = $result->fetch_assoc();
                $stmt->close();
                
                if (!$user_row || !password_verify($confirm_password, $user_row['password_hash'])) {
                    throw new Exception("Contraseña incorrecta");
                }
                
                // Eliminar foto de perfil
                $user_settings = getUserSettings($current_user_id);
                if (!empty($user_settings['profile_picture'])) {
                    $photo_path = __DIR__ . '/' . $user_settings['profile_picture'];
                    if (file_exists($photo_path)) {
                        unlink($photo_path);
                    }
                }
                
                // Eliminar configuraciones del usuario
                $stmt = $conn->prepare("DELETE FROM user_settings WHERE user_id = ?");
                $stmt->bind_param("i", $current_user_id);
                $stmt->execute();
                $stmt->close();
                
                // Marcar usuario como eliminado (soft delete)
                $stmt = $conn->prepare("UPDATE users SET status = 'inactive', deleted_at = NOW() WHERE id = ?");
                $stmt->bind_param("i", $current_user_id);
                $stmt->execute();
                $stmt->close();
                
                // Registrar evento
                if (function_exists('logSecurityEvent')) {
                    logSecurityEvent('account_deleted', 'Cuenta eliminada por el usuario', 'high', $current_user_id);
                }
                
                // Cerrar sesión
                session_destroy();
                header('Location: login.php?message=account_deleted');
                exit();
                break;
                
            default:
                throw new Exception("Acción no válida");
        }
    } catch (Exception $e) {
        $update_message = $e->getMessage();
        $update_type = "error";
        
        // Log del error
        error_log("Error en user_settings.php: " . $e->getMessage());
        if (function_exists('logSecurityEvent')) {
            logSecurityEvent('settings_error', 'Error en configuración: ' . $e->getMessage(), 'low', $current_user_id);
        }
    }
}

// Obtener datos del usuario y configuraciones
$user_data = getUserData($current_user_id);
$user_settings = getUserSettings($current_user_id);

// Si no hay configuraciones, usar valores por defecto
if (empty($user_settings)) {
    $user_settings = [
        'theme' => 'dark',
        'language' => 'es',
        'timezone' => 'America/Bogota',
        'notifications_email' => 1,
        'notifications_push' => 0,
        'notifications_sms' => 0,
        'security_alerts' => 1,
        'system_updates' => 1,
        'marketing_emails' => 0,
        'auto_optimization' => 1,
        'real_time_scan' => 1,
        'gaming_mode' => 0,
        'startup_boost' => 1,
        'memory_optimization' => 1,
        'performance_mode' => 'balanced',
        'scan_frequency' => 'daily',
        'disk_cleanup' => 'weekly',
        'two_factor_enabled' => 0,
        'biometric_enabled' => 0,
        'vpn_enabled' => 0,
        'firewall_enabled' => 1,
        'antivirus_enabled' => 1,
        'data_sharing' => 0,
        'analytics' => 1,
        'profile_picture' => null
    ];
}

// Si no hay datos del usuario, usar valores por defecto
if (!$user_data) {
    $user_data = [
        'id' => $current_user_id,
        'username' => $current_username,
        'fullname' => $current_username,
        'email' => '',
        'phone' => '',
        'bio' => '',
        'company' => '',
        'position' => '',
        'location' => '',
        'website' => '',
        'linkedin' => '',
        'twitter' => '',
        'github' => '',
        'created_at' => date('Y-m-d H:i:s'),
        'profile_picture' => $user_settings['profile_picture'] ?? null
    ];
}

// Función para generar token CSRF
if (!function_exists('generateCSRFToken')) {
    function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
}

$csrf_token = generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración de Usuario - GuardianIA v3.0</title>
    <meta name="description" content="Configuración completa de usuario - GuardianIA v3.0">
    <meta name="author" content="Anderson Mamian Chicangana">
    
    <!-- Configuración de seguridad -->
    <meta http-equiv="X-Content-Type-Options" content="nosniff">
    <meta http-equiv="X-Frame-Options" content="DENY">
    <meta http-equiv="X-XSS-Protection" content="1; mode=block">
    <meta http-equiv="Strict-Transport-Security" content="max-age=31536000; includeSubDomains">
    
    <!-- CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --accent-color: #00ff88;
            --background-dark: #0a0a0a;
            --background-light: #1a1a2e;
            --surface-color: rgba(255, 255, 255, 0.05);
            --text-primary: #ffffff;
            --text-secondary: rgba(255, 255, 255, 0.7);
            --text-muted: rgba(255, 255, 255, 0.5);
            --border-color: rgba(255, 255, 255, 0.1);
            --success-color: #00ff88;
            --warning-color: #ffa500;
            --error-color: #ff6b6b;
            --shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
            --border-radius: 12px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, var(--background-dark) 0%, var(--background-light) 100%);
            color: var(--text-primary);
            min-height: 100vh;
            line-height: 1.6;
        }
        
        /* Efecto de partículas de fondo */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 50%, rgba(102, 126, 234, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(118, 75, 162, 0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 80%, rgba(0, 255, 136, 0.05) 0%, transparent 50%);
            pointer-events: none;
            z-index: -1;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .header {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
            backdrop-filter: blur(20px);
            box-shadow: var(--shadow);
        }
        
        .header h1 {
            font-size: 2.5rem;
            font-weight: 700;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
        }
        
        .header p {
            color: var(--text-secondary);
            font-size: 1.1rem;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-top: 20px;
            padding: 20px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: var(--border-radius);
            border: 1px solid var(--border-color);
        }
        
        .user-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: 600;
            color: white;
            overflow: hidden;
            border: 3px solid var(--accent-color);
            box-shadow: 0 4px 20px rgba(0, 255, 136, 0.3);
        }
        
        .user-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .user-details h3 {
            font-size: 1.3rem;
            margin-bottom: 5px;
            color: var(--text-primary);
        }
        
        .user-details p {
            color: var(--text-secondary);
            margin-bottom: 3px;
        }
        
        .alert {
            padding: 15px 20px;
            border-radius: var(--border-radius);
            margin-bottom: 20px;
            border: 1px solid;
            backdrop-filter: blur(10px);
            display: flex;
            align-items: center;
            gap: 10px;
            animation: slideIn 0.3s ease-out;
        }
        
        .alert.success {
            background: rgba(0, 255, 136, 0.1);
            border-color: var(--success-color);
            color: var(--success-color);
        }
        
        .alert.error {
            background: rgba(255, 107, 107, 0.1);
            border-color: var(--error-color);
            color: var(--error-color);
        }
        
        .settings-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
            align-items: start;
        }
        
        .settings-nav {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 20px;
            backdrop-filter: blur(20px);
            box-shadow: var(--shadow);
            position: sticky;
            top: 20px;
        }
        
        .settings-nav h3 {
            font-size: 1.2rem;
            margin-bottom: 20px;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .nav-item {
            display: block;
            padding: 12px 15px;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 8px;
            margin-bottom: 5px;
            transition: var(--transition);
            cursor: pointer;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            font-size: 0.95rem;
        }
        
        .nav-item:hover,
        .nav-item.active {
            background: rgba(102, 126, 234, 0.2);
            color: var(--primary-color);
            transform: translateX(5px);
        }
        
        .nav-item i {
            width: 20px;
            margin-right: 10px;
        }
        
        .settings-content {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 30px;
            backdrop-filter: blur(20px);
            box-shadow: var(--shadow);
        }
        
        .settings-section {
            display: none;
        }
        
        .settings-section.active {
            display: block;
            animation: fadeIn 0.3s ease-out;
        }
        
        .section-header {
            margin-bottom: 30px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .section-header h2 {
            font-size: 1.8rem;
            color: var(--text-primary);
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .section-header p {
            color: var(--text-secondary);
            font-size: 0.95rem;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }
        
        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--text-primary);
            font-size: 0.95rem;
        }
        
        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 12px 15px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 0.95rem;
            transition: var(--transition);
            backdrop-filter: blur(10px);
        }
        
        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            background: rgba(255, 255, 255, 0.08);
        }
        
        .form-textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .form-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            cursor: pointer;
        }
        
        .form-checkbox input[type="checkbox"] {
            width: 18px;
            height: 18px;
            accent-color: var(--primary-color);
        }
        
        .form-checkbox label {
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 0.95rem;
        }
        
        .file-upload {
            position: relative;
            display: inline-block;
            cursor: pointer;
            width: 100%;
        }
        
        .file-upload input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }
        
        .file-upload-label {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 40px 20px;
            border: 2px dashed var(--border-color);
            border-radius: var(--border-radius);
            background: rgba(255, 255, 255, 0.02);
            color: var(--text-secondary);
            transition: var(--transition);
            text-align: center;
            flex-direction: column;
        }
        
        .file-upload:hover .file-upload-label,
        .file-upload.dragover .file-upload-label {
            border-color: var(--primary-color);
            background: rgba(102, 126, 234, 0.1);
            color: var(--primary-color);
        }
        
        .current-photo {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: var(--border-radius);
            border: 1px solid var(--border-color);
        }
        
        .current-photo img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid var(--accent-color);
        }
        
        .photo-info h4 {
            color: var(--text-primary);
            margin-bottom: 5px;
        }
        
        .photo-info p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 24px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn.btn-danger {
            background: linear-gradient(135deg, var(--error-color), #e55353);
        }
        
        .btn.btn-danger:hover {
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.3);
        }
        
        .btn.btn-secondary {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }
        
        .btn.btn-secondary:hover {
            background: rgba(255, 255, 255, 0.15);
            box-shadow: 0 8px 25px rgba(255, 255, 255, 0.1);
        }
        
        .btn-group {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid var(--border-color);
        }
        
        .danger-zone {
            background: rgba(255, 107, 107, 0.05);
            border: 1px solid rgba(255, 107, 107, 0.2);
            border-radius: var(--border-radius);
            padding: 25px;
            margin-top: 30px;
        }
        
        .danger-zone h3 {
            color: var(--error-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .danger-zone p {
            color: var(--text-secondary);
            margin-bottom: 20px;
            line-height: 1.6;
        }
        
        .password-strength {
            margin-top: 10px;
        }
        
        .strength-bar {
            height: 4px;
            background: var(--border-color);
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 5px;
        }
        
        .strength-fill {
            height: 100%;
            transition: var(--transition);
            border-radius: 2px;
        }
        
        .strength-text {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }
        
        .loading {
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        
        .spinner {
            width: 16px;
            height: 16px;
            border: 2px solid transparent;
            border-top: 2px solid currentColor;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(10px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: var(--surface-color);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 30px;
            max-width: 500px;
            width: 90%;
            backdrop-filter: blur(20px);
            box-shadow: var(--shadow);
        }
        
        .modal-header {
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        
        .modal-header h3 {
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .modal-body {
            margin-bottom: 25px;
        }
        
        .modal-footer {
            display: flex;
            gap: 15px;
            justify-content: flex-end;
        }
        
        /* Animaciones */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        @keyframes slideIn {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }
            
            .settings-container {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .settings-nav {
                position: static;
                order: 2;
            }
            
            .settings-content {
                order: 1;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .btn-group {
                flex-direction: column;
            }
            
            .modal-content {
                margin: 20px;
                width: calc(100% - 40px);
            }
            
            .header h1 {
                font-size: 2rem;
            }
            
            .user-info {
                flex-direction: column;
                text-align: center;
            }
        }
        
        @media (max-width: 480px) {
            .header {
                padding: 20px;
            }
            
            .settings-content {
                padding: 20px;
            }
            
            .user-avatar {
                width: 60px;
                height: 60px;
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-user-cog"></i> Configuración de Usuario</h1>
            <p>Personaliza tu experiencia en GuardianIA v3.0</p>
            
            <div class="user-info">
                <div class="user-avatar">
                    <?php if (!empty($user_data['profile_picture']) && file_exists(__DIR__ . '/' . $user_data['profile_picture'])): ?>
                        <img src="<?php echo htmlspecialchars($user_data['profile_picture']); ?>" alt="Foto de perfil">
                    <?php else: ?>
                        <?php echo strtoupper(substr($user_data['fullname'] ?? $user_data['username'], 0, 1)); ?>
                    <?php endif; ?>
                </div>
                <div class="user-details">
                    <h3><?php echo htmlspecialchars($user_data['fullname'] ?? $user_data['username']); ?></h3>
                    <p><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user_data['email'] ?? 'No especificado'); ?></p>
                    <p><i class="fas fa-calendar"></i> Miembro desde <?php echo date('d/m/Y', strtotime($user_data['created_at'] ?? 'now')); ?></p>
                    <p><i class="fas fa-shield-alt"></i> <?php echo htmlspecialchars($_SESSION['user_type'] ?? 'Usuario'); ?></p>
                </div>
            </div>
        </div>

        <!-- Alertas -->
        <?php if (!empty($update_message)): ?>
            <div class="alert <?php echo $update_type; ?>">
                <i class="fas fa-<?php echo $update_type === 'success' ? 'check-circle' : 'exclamation-triangle'; ?>"></i>
                <?php echo htmlspecialchars($update_message); ?>
            </div>
        <?php endif; ?>

        <!-- Contenido principal -->
        <div class="settings-container">
            <!-- Navegación lateral -->
            <div class="settings-nav">
                <h3><i class="fas fa-cogs"></i> Configuraciones</h3>
                
                <button class="nav-item active" data-section="profile">
                    <i class="fas fa-user"></i> Perfil Personal
                </button>
                
                <button class="nav-item" data-section="security">
                    <i class="fas fa-shield-alt"></i> Seguridad
                </button>
                
                <button class="nav-item" data-section="notifications">
                    <i class="fas fa-bell"></i> Notificaciones
                </button>
                
                <button class="nav-item" data-section="privacy">
                    <i class="fas fa-user-secret"></i> Privacidad
                </button>
                
                <button class="nav-item" data-section="performance">
                    <i class="fas fa-tachometer-alt"></i> Rendimiento
                </button>
                
                <button class="nav-item" data-section="appearance">
                    <i class="fas fa-palette"></i> Apariencia
                </button>
                
                <button class="nav-item" data-section="account">
                    <i class="fas fa-user-times"></i> Cuenta
                </button>
            </div>

            <!-- Contenido de configuraciones -->
            <div class="settings-content">
                <!-- Sección: Perfil Personal -->
                <div class="settings-section active" id="profile">
                    <div class="section-header">
                        <h2><i class="fas fa-user"></i> Perfil Personal</h2>
                        <p>Actualiza tu información personal y foto de perfil</p>
                    </div>

                    <form method="POST" enctype="multipart/form-data" id="profileForm">
                        <input type="hidden" name="action" value="update_profile">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <!-- Foto de perfil -->
                        <div class="form-group">
                            <label class="form-label">
                                <i class="fas fa-camera"></i> Foto de Perfil
                            </label>
                            
                            <?php if (!empty($user_data['profile_picture']) && file_exists(__DIR__ . '/' . $user_data['profile_picture'])): ?>
                                <div class="current-photo">
                                    <img src="<?php echo htmlspecialchars($user_data['profile_picture']); ?>" alt="Foto actual">
                                    <div class="photo-info">
                                        <h4>Foto actual</h4>
                                        <p>Selecciona una nueva imagen para cambiarla</p>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="file-upload">
                                <input type="file" name="profile_picture" accept="image/*" id="profilePicture">
                                <label for="profilePicture" class="file-upload-label">
                                    <i class="fas fa-cloud-upload-alt"></i>
                                    <span>Seleccionar nueva foto de perfil</span>
                                    <small>JPG, PNG, GIF o WEBP - Máximo 5MB</small>
                                </label>
                            </div>
                        </div>

                        <!-- Información básica -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="fullname" class="form-label">
                                    <i class="fas fa-user"></i> Nombre Completo *
                                </label>
                                <input type="text" id="fullname" name="fullname" class="form-input" 
                                       value="<?php echo htmlspecialchars($user_data['fullname'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email" class="form-label">
                                    <i class="fas fa-envelope"></i> Email *
                                </label>
                                <input type="email" id="email" name="email" class="form-input" 
                                       value="<?php echo htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone" class="form-label">
                                    <i class="fas fa-phone"></i> Teléfono
                                </label>
                                <input type="tel" id="phone" name="phone" class="form-input" 
                                       value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="location" class="form-label">
                                    <i class="fas fa-map-marker-alt"></i> Ubicación
                                </label>
                                <input type="text" id="location" name="location" class="form-input" 
                                       value="<?php echo htmlspecialchars($user_data['location'] ?? ''); ?>">
                            </div>
                        </div>

                        <!-- Información profesional -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="company" class="form-label">
                                    <i class="fas fa-building"></i> Empresa
                                </label>
                                <input type="text" id="company" name="company" class="form-input" 
                                       value="<?php echo htmlspecialchars($user_data['company'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="position" class="form-label">
                                    <i class="fas fa-briefcase"></i> Cargo
                                </label>
                                <input type="text" id="position" name="position" class="form-input" 
                                       value="<?php echo htmlspecialchars($user_data['position'] ?? ''); ?>">
                            </div>
                        </div>

                        <!-- Biografía -->
                        <div class="form-group">
                            <label for="bio" class="form-label">
                                <i class="fas fa-quote-left"></i> Biografía
                            </label>
                            <textarea id="bio" name="bio" class="form-textarea" 
                                      placeholder="Cuéntanos un poco sobre ti..."><?php echo htmlspecialchars($user_data['bio'] ?? ''); ?></textarea>
                        </div>

                        <!-- Redes sociales -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="website" class="form-label">
                                    <i class="fas fa-globe"></i> Sitio Web
                                </label>
                                <input type="url" id="website" name="website" class="form-input" 
                                       value="<?php echo htmlspecialchars($user_data['website'] ?? ''); ?>" 
                                       placeholder="https://ejemplo.com">
                            </div>
                            
                            <div class="form-group">
                                <label for="linkedin" class="form-label">
                                    <i class="fab fa-linkedin"></i> LinkedIn
                                </label>
                                <input type="url" id="linkedin" name="linkedin" class="form-input" 
                                       value="<?php echo htmlspecialchars($user_data['linkedin'] ?? ''); ?>" 
                                       placeholder="https://linkedin.com/in/usuario">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="twitter" class="form-label">
                                    <i class="fab fa-twitter"></i> Twitter
                                </label>
                                <input type="url" id="twitter" name="twitter" class="form-input" 
                                       value="<?php echo htmlspecialchars($user_data['twitter'] ?? ''); ?>" 
                                       placeholder="https://twitter.com/usuario">
                            </div>
                            
                            <div class="form-group">
                                <label for="github" class="form-label">
                                    <i class="fab fa-github"></i> GitHub
                                </label>
                                <input type="url" id="github" name="github" class="form-input" 
                                       value="<?php echo htmlspecialchars($user_data['github'] ?? ''); ?>" 
                                       placeholder="https://github.com/usuario">
                            </div>
                        </div>

                        <div class="btn-group">
                            <button type="submit" class="btn">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                            <button type="reset" class="btn btn-secondary">
                                <i class="fas fa-undo"></i> Restablecer
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Sección: Seguridad -->
                <div class="settings-section" id="security">
                    <div class="section-header">
                        <h2><i class="fas fa-shield-alt"></i> Configuración de Seguridad</h2>
                        <p>Protege tu cuenta con configuraciones de seguridad avanzadas</p>
                    </div>

                    <!-- Cambio de contraseña -->
                    <form method="POST" id="passwordForm">
                        <input type="hidden" name="action" value="change_password">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <h3 style="margin-bottom: 20px; color: var(--text-primary);">
                            <i class="fas fa-key"></i> Cambiar Contraseña
                        </h3>

                        <div class="form-group">
                            <label for="current_password" class="form-label">
                                <i class="fas fa-lock"></i> Contraseña Actual *
                            </label>
                            <input type="password" id="current_password" name="current_password" class="form-input" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="new_password" class="form-label">
                                    <i class="fas fa-key"></i> Nueva Contraseña *
                                </label>
                                <input type="password" id="new_password" name="new_password" class="form-input" required>
                                <div class="password-strength">
                                    <div class="strength-bar">
                                        <div class="strength-fill" id="strengthFill"></div>
                                    </div>
                                    <div class="strength-text" id="strengthText">Ingresa una contraseña</div>
                                </div>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password" class="form-label">
                                    <i class="fas fa-check"></i> Confirmar Contraseña *
                                </label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                            </div>
                        </div>

                        <div class="btn-group">
                            <button type="submit" class="btn">
                                <i class="fas fa-save"></i> Cambiar Contraseña
                            </button>
                        </div>
                    </form>

                    <!-- Configuraciones de seguridad -->
                    <form method="POST" style="margin-top: 40px;" id="securityForm">
                        <input type="hidden" name="action" value="update_security">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <h3 style="margin-bottom: 20px; color: var(--text-primary);">
                            <i class="fas fa-cogs"></i> Configuraciones de Seguridad
                        </h3>

                        <div class="form-checkbox">
                            <input type="checkbox" id="two_factor" name="two_factor" 
                                   <?php echo !empty($user_settings['two_factor_enabled']) ? 'checked' : ''; ?>>
                            <label for="two_factor">
                                <strong>Autenticación de dos factores (2FA)</strong><br>
                                <small>Agrega una capa extra de seguridad a tu cuenta</small>
                            </label>
                        </div>

                        <div class="form-checkbox">
                            <input type="checkbox" id="biometric" name="biometric" 
                                   <?php echo !empty($user_settings['biometric_enabled']) ? 'checked' : ''; ?>>
                            <label for="biometric">
                                <strong>Autenticación biométrica</strong><br>
                                <small>Usa huella dactilar o reconocimiento facial</small>
                            </label>
                        </div>

                        <div class="form-checkbox">
                            <input type="checkbox" id="vpn" name="vpn" 
                                   <?php echo !empty($user_settings['vpn_enabled']) ? 'checked' : ''; ?>>
                            <label for="vpn">
                                <strong>VPN automática</strong><br>
                                <small>Conectar automáticamente a VPN en redes públicas</small>
                            </label>
                        </div>

                        <div class="form-checkbox">
                            <input type="checkbox" id="firewall" name="firewall" 
                                   <?php echo !empty($user_settings['firewall_enabled']) ? 'checked' : ''; ?>>
                            <label for="firewall">
                                <strong>Firewall avanzado</strong><br>
                                <small>Protección contra amenazas de red</small>
                            </label>
                        </div>

                        <div class="form-checkbox">
                            <input type="checkbox" id="antivirus" name="antivirus" 
                                   <?php echo !empty($user_settings['antivirus_enabled']) ? 'checked' : ''; ?>>
                            <label for="antivirus">
                                <strong>Antivirus en tiempo real</strong><br>
                                <small>Escaneo continuo de amenazas</small>
                            </label>
                        </div>

                        <div class="btn-group">
                            <button type="submit" class="btn">
                                <i class="fas fa-save"></i> Guardar Configuración
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Sección: Notificaciones -->
                <div class="settings-section" id="notifications">
                    <div class="section-header">
                        <h2><i class="fas fa-bell"></i> Preferencias de Notificación</h2>
                        <p>Controla cómo y cuándo recibes notificaciones</p>
                    </div>

                    <form method="POST" id="notificationsForm">
                        <input type="hidden" name="action" value="update_notifications">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <div class="form-checkbox">
                            <input type="checkbox" id="email_notifications" name="email_notifications" 
                                   <?php echo !empty($user_settings['notifications_email']) ? 'checked' : ''; ?>>
                            <label for="email_notifications">
                                <strong>Notificaciones por email</strong><br>
                                <small>Recibir notificaciones importantes por correo electrónico</small>
                            </label>
                        </div>

                        <div class="form-checkbox">
                            <input type="checkbox" id="push_notifications" name="push_notifications" 
                                   <?php echo !empty($user_settings['notifications_push']) ? 'checked' : ''; ?>>
                            <label for="push_notifications">
                                <strong>Notificaciones push</strong><br>
                                <small>Recibir notificaciones instantáneas en el navegador</small>
                            </label>
                        </div>

                        <div class="form-checkbox">
                            <input type="checkbox" id="security_alerts" name="security_alerts" 
                                   <?php echo !empty($user_settings['security_alerts']) ? 'checked' : ''; ?>>
                            <label for="security_alerts">
                                <strong>Alertas de seguridad</strong><br>
                                <small>Notificaciones sobre eventos de seguridad críticos</small>
                            </label>
                        </div>

                        <div class="btn-group">
                            <button type="submit" class="btn">
                                <i class="fas fa-save"></i> Guardar Preferencias
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Sección: Privacidad -->
                <div class="settings-section" id="privacy">
                    <div class="section-header">
                        <h2><i class="fas fa-user-secret"></i> Configuración de Privacidad</h2>
                        <p>Controla cómo se comparten y utilizan tus datos</p>
                    </div>

                    <form method="POST" id="privacyForm">
                        <input type="hidden" name="action" value="update_privacy">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <div class="form-checkbox">
                            <input type="checkbox" id="data_sharing" name="data_sharing" 
                                   <?php echo !empty($user_settings['data_sharing']) ? 'checked' : ''; ?>>
                            <label for="data_sharing">
                                <strong>Compartir datos anónimos</strong><br>
                                <small>Ayudar a mejorar el producto compartiendo datos anónimos de uso</small>
                            </label>
                        </div>

                        <div class="form-checkbox">
                            <input type="checkbox" id="analytics" name="analytics" 
                                   <?php echo !empty($user_settings['analytics']) ? 'checked' : ''; ?>>
                            <label for="analytics">
                                <strong>Análisis de uso</strong><br>
                                <small>Permitir el análisis de cómo usas la aplicación para mejoras</small>
                            </label>
                        </div>

                        <div class="btn-group">
                            <button type="submit" class="btn">
                                <i class="fas fa-save"></i> Guardar Configuración
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Sección: Rendimiento -->
                <div class="settings-section" id="performance">
                    <div class="section-header">
                        <h2><i class="fas fa-tachometer-alt"></i> Configuración de Rendimiento</h2>
                        <p>Optimiza el rendimiento del sistema según tus necesidades</p>
                    </div>

                    <form method="POST" id="performanceForm">
                        <input type="hidden" name="action" value="update_performance">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <div class="form-checkbox">
                            <input type="checkbox" id="auto_optimization" name="auto_optimization" 
                                   <?php echo !empty($user_settings['auto_optimization']) ? 'checked' : ''; ?>>
                            <label for="auto_optimization">
                                <strong>Optimización automática</strong><br>
                                <small>Optimizar automáticamente el rendimiento del sistema</small>
                            </label>
                        </div>

                        <div class="form-checkbox">
                            <input type="checkbox" id="real_time_scan" name="real_time_scan" 
                                   <?php echo !empty($user_settings['real_time_scan']) ? 'checked' : ''; ?>>
                            <label for="real_time_scan">
                                <strong>Escaneo en tiempo real</strong><br>
                                <small>Monitoreo continuo de amenazas y vulnerabilidades</small>
                            </label>
                        </div>

                        <div class="btn-group">
                            <button type="submit" class="btn">
                                <i class="fas fa-save"></i> Guardar Configuración
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Sección: Apariencia -->
                <div class="settings-section" id="appearance">
                    <div class="section-header">
                        <h2><i class="fas fa-palette"></i> Configuración de Apariencia</h2>
                        <p>Personaliza la interfaz según tus preferencias</p>
                    </div>

                    <form method="POST" id="appearanceForm">
                        <input type="hidden" name="action" value="update_appearance">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

                        <div class="form-group">
                            <label for="theme" class="form-label">
                                <i class="fas fa-moon"></i> Tema
                            </label>
                            <select id="theme" name="theme" class="form-select">
                                <option value="dark" <?php echo ($user_settings['theme'] ?? 'dark') == 'dark' ? 'selected' : ''; ?>>Oscuro</option>
                                <option value="light" <?php echo ($user_settings['theme'] ?? 'dark') == 'light' ? 'selected' : ''; ?>>Claro</option>
                                <option value="auto" <?php echo ($user_settings['theme'] ?? 'dark') == 'auto' ? 'selected' : ''; ?>>Automático</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="language" class="form-label">
                                <i class="fas fa-globe"></i> Idioma
                            </label>
                            <select id="language" name="language" class="form-select">
                                <option value="es" <?php echo ($user_settings['language'] ?? 'es') == 'es' ? 'selected' : ''; ?>>Español</option>
                                <option value="en" <?php echo ($user_settings['language'] ?? 'es') == 'en' ? 'selected' : ''; ?>>English</option>
                            </select>
                        </div>

                        <div class="btn-group">
                            <button type="submit" class="btn">
                                <i class="fas fa-save"></i> Guardar Configuración
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Sección: Cuenta -->
                <div class="settings-section" id="account">
                    <div class="section-header">
                        <h2><i class="fas fa-user-times"></i> Gestión de Cuenta</h2>
                        <p>Opciones avanzadas para la gestión de tu cuenta</p>
                    </div>

                    <!-- Información de la cuenta -->
                    <div style="margin-bottom: 30px; padding: 20px; background: rgba(0, 0, 0, 0.2); border-radius: var(--border-radius); border: 1px solid var(--border-color);">
                        <h3 style="color: var(--text-primary); margin-bottom: 15px;">
                            <i class="fas fa-info-circle"></i> Información de la Cuenta
                        </h3>
                        <div style="display: grid; gap: 10px;">
                            <div><strong>ID de Usuario:</strong> <?php echo htmlspecialchars($current_user_id); ?></div>
                            <div><strong>Nombre de Usuario:</strong> <?php echo htmlspecialchars($user_data['username'] ?? 'N/A'); ?></div>
                            <div><strong>Tipo de Cuenta:</strong> <?php echo htmlspecialchars($_SESSION['user_type'] ?? 'Usuario'); ?></div>
                            <div><strong>Estado:</strong> <?php echo htmlspecialchars($user_data['status'] ?? 'Activo'); ?></div>
                            <div><strong>Último Acceso:</strong> <?php echo $user_data['last_login'] ? date('d/m/Y H:i', strtotime($user_data['last_login'])) : 'Nunca'; ?></div>
                        </div>
                    </div>

                    <!-- Zona de peligro -->
                    <div class="danger-zone">
                        <h3><i class="fas fa-exclamation-triangle"></i> Zona de Peligro</h3>
                        <p>
                            Las acciones en esta sección son irreversibles. Una vez que elimines tu cuenta, 
                            no podrás recuperar tus datos, configuraciones o historial.
                        </p>
                        
                        <button type="button" class="btn btn-danger" onclick="showDeleteAccountModal()">
                            <i class="fas fa-trash-alt"></i> Eliminar Cuenta Permanentemente
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de confirmación para eliminar cuenta -->
    <div class="modal" id="deleteAccountModal">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-exclamation-triangle"></i> Confirmar Eliminación de Cuenta</h3>
            </div>
            <div class="modal-body">
                <p style="color: var(--error-color); margin-bottom: 20px;">
                    <strong>¡ADVERTENCIA!</strong> Esta acción es irreversible.
                </p>
                
                <form method="POST" id="deleteAccountForm">
                    <input type="hidden" name="action" value="delete_account">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-group">
                        <label for="confirm_password_delete" class="form-label">
                            <i class="fas fa-lock"></i> Confirma tu contraseña
                        </label>
                        <input type="password" id="confirm_password_delete" name="confirm_password" class="form-input" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirmation_text" class="form-label">
                            <i class="fas fa-keyboard"></i> Escribe "ELIMINAR MI CUENTA" para confirmar
                        </label>
                        <input type="text" id="confirmation_text" name="confirmation_text" class="form-input" required>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="hideDeleteAccountModal()">
                    <i class="fas fa-times"></i> Cancelar
                </button>
                <button type="submit" form="deleteAccountForm" class="btn btn-danger">
                    <i class="fas fa-trash-alt"></i> Eliminar Cuenta
                </button>
            </div>
        </div>
    </div>

    <!-- JavaScript -->
    <script>
        // Variables globales
        let currentSection = 'profile';
        
        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            initializeNavigation();
            initializeFileUpload();
            initializePasswordStrength();
            initializeFormValidation();
            
            // Auto-hide alerts after 5 seconds
            setTimeout(() => {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    alert.style.animation = 'slideOut 0.3s ease-in';
                    setTimeout(() => alert.remove(), 300);
                });
            }, 5000);
        });
        
        // Navegación entre secciones
        function initializeNavigation() {
            const navItems = document.querySelectorAll('.nav-item');
            const sections = document.querySelectorAll('.settings-section');
            
            navItems.forEach(item => {
                item.addEventListener('click', function() {
                    const sectionId = this.dataset.section;
                    
                    // Update navigation
                    navItems.forEach(nav => nav.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Update sections
                    sections.forEach(section => section.classList.remove('active'));
                    document.getElementById(sectionId).classList.add('active');
                    
                    currentSection = sectionId;
                });
            });
        }
        
        // Manejo de subida de archivos
        function initializeFileUpload() {
            const fileInput = document.getElementById('profilePicture');
            const fileUpload = document.querySelector('.file-upload');
            const fileLabel = document.querySelector('.file-upload-label span');
            
            if (!fileInput || !fileUpload) return;
            
            // Drag and drop
            fileUpload.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dragover');
            });
            
            fileUpload.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
            });
            
            fileUpload.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files;
                    handleFileSelect(files[0]);
                }
            });
            
            // File input change
            fileInput.addEventListener('change', function(e) {
                if (this.files.length > 0) {
                    handleFileSelect(this.files[0]);
                }
            });
            
            function handleFileSelect(file) {
                // Validate file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!allowedTypes.includes(file.type)) {
                    showAlert('Tipo de archivo no válido. Solo se permiten: JPG, PNG, GIF, WEBP', 'error');
                    return;
                }
                
                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    showAlert('El archivo es demasiado grande. Máximo 5MB', 'error');
                    return;
                }
                
                // Update label
                fileLabel.textContent = `Archivo seleccionado: ${file.name}`;
                
                // Preview image
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Create or update preview
                    let preview = document.querySelector('.file-preview');
                    if (!preview) {
                        preview = document.createElement('div');
                        preview.className = 'file-preview';
                        preview.style.cssText = `
                            margin-top: 15px;
                            padding: 15px;
                            background: rgba(0, 0, 0, 0.2);
                            border-radius: var(--border-radius);
                            border: 1px solid var(--border-color);
                            display: flex;
                            align-items: center;
                            gap: 15px;
                        `;
                        fileUpload.parentNode.appendChild(preview);
                    }
                    
                    preview.innerHTML = `
                        <img src="${e.target.result}" alt="Vista previa" style="
                            width: 60px;
                            height: 60px;
                            border-radius: 50%;
                            object-fit: cover;
                            border: 2px solid var(--accent-color);
                        ">
                        <div>
                            <h4 style="color: var(--text-primary); margin-bottom: 5px;">Vista previa</h4>
                            <p style="color: var(--text-secondary); font-size: 0.9rem;">
                                ${file.name} (${(file.size / 1024 / 1024).toFixed(2)} MB)
                            </p>
                        </div>
                    `;
                };
                reader.readAsDataURL(file);
            }
        }
        
        // Validación de fortaleza de contraseña
        function initializePasswordStrength() {
            const passwordInput = document.getElementById('new_password');
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            if (!passwordInput || !strengthFill || !strengthText) return;
            
            passwordInput.addEventListener('input', function() {
                const password = this.value;
                const strength = calculatePasswordStrength(password);
                
                // Update strength bar
                strengthFill.style.width = `${strength.percentage}%`;
                strengthFill.style.backgroundColor = strength.color;
                strengthText.textContent = strength.text;
                strengthText.style.color = strength.color;
            });
        }
        
        function calculatePasswordStrength(password) {
            let score = 0;
            let feedback = [];
            
            if (password.length >= 8) score += 20;
            else feedback.push('al menos 8 caracteres');
            
            if (/[a-z]/.test(password)) score += 20;
            else feedback.push('minúsculas');
            
            if (/[A-Z]/.test(password)) score += 20;
            else feedback.push('mayúsculas');
            
            if (/[0-9]/.test(password)) score += 20;
            else feedback.push('números');
            
            if (/[^A-Za-z0-9]/.test(password)) score += 20;
            else feedback.push('símbolos');
            
            let strength = {
                percentage: score,
                color: '#ff6b6b',
                text: 'Muy débil'
            };
            
            if (score >= 80) {
                strength.color = '#00ff88';
                strength.text = 'Muy fuerte';
            } else if (score >= 60) {
                strength.color = '#ffa500';
                strength.text = 'Fuerte';
            } else if (score >= 40) {
                strength.color = '#ffed4e';
                strength.text = 'Moderada';
            } else if (score >= 20) {
                strength.color = '#ff9f43';
                strength.text = 'Débil';
            }
            
            if (feedback.length > 0 && password.length > 0) {
                strength.text += ` (falta: ${feedback.join(', ')})`;
            }
            
            return strength;
        }
        
        // Validación de formularios
        function initializeFormValidation() {
            const forms = document.querySelectorAll('form');
            
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    const submitBtn = this.querySelector('button[type="submit"]');
                    
                    // Show loading state
                    if (submitBtn) {
                        const originalText = submitBtn.innerHTML;
                        submitBtn.innerHTML = '<div class="loading"><div class="spinner"></div> Guardando...</div>';
                        submitBtn.disabled = true;
                        
                        // Reset after 5 seconds if form doesn't submit
                        setTimeout(() => {
                            submitBtn.innerHTML = originalText;
                            submitBtn.disabled = false;
                        }, 5000);
                    }
                });
            });
        }
        
        // Modal de eliminación de cuenta
        function showDeleteAccountModal() {
            document.getElementById('deleteAccountModal').classList.add('active');
            document.body.style.overflow = 'hidden';
        }
        
        function hideDeleteAccountModal() {
            document.getElementById('deleteAccountModal').classList.remove('active');
            document.body.style.overflow = '';
            
            // Reset form
            document.getElementById('deleteAccountForm').reset();
        }
        
        // Función para mostrar alertas
        function showAlert(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert ${type}`;
            alertDiv.innerHTML = `
                <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-triangle' : 'info-circle'}"></i>
                ${message}
            `;
            
            // Insert at the top of the container
            const container = document.querySelector('.container');
            const header = container.querySelector('.header');
            if (header && header.nextSibling) {
                container.insertBefore(alertDiv, header.nextSibling);
            } else {
                container.appendChild(alertDiv);
            }
            
            // Auto-remove after 5 seconds
            setTimeout(() => {
                alertDiv.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => {
                    if (alertDiv.parentNode) {
                        alertDiv.parentNode.removeChild(alertDiv);
                    }
                }, 300);
            }, 5000);
        }
        
        // Cerrar modal al hacer clic fuera
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('deleteAccountModal');
            if (e.target === modal) {
                hideDeleteAccountModal();
            }
        });
        
        // Cerrar modal con tecla Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                const modal = document.getElementById('deleteAccountModal');
                if (modal.classList.contains('active')) {
                    hideDeleteAccountModal();
                }
            }
        });
        
        console.log('GuardianIA v3.0 - User Settings loaded successfully');
    </script>
</body>
</html>
<?php
// Cerrar conexión a base de datos al final de forma segura
if ($db && method_exists($db, 'isConnected') && $db->isConnected()) {
    // No llamar close() directamente, dejar que el destructor lo maneje
    $db = null;
}

// Log de fin de ejecución
if (function_exists('logEvent')) {
    logEvent('INFO', 'Configuración de usuario cargada exitosamente para usuario: ' . $current_user_id);
} else {
    error_log('GuardianIA: Configuración de usuario cargada para usuario: ' . $current_user_id);
}
?>
