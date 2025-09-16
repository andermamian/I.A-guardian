<?php
/**
 * User Settings - GuardianIA v3.0 COMPLETO
 * Configuración de usuario con todas las funcionalidades
 * Anderson Mamian Chicangana
 * Versión completa con más de 3000 líneas
 */

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
$db = MilitaryDatabaseManager::getInstance();
$conn = $db->getConnection();

// Función para registrar cambios en la configuración
function logSettingsChange($setting_name, $old_value, $new_value, $user_id) {
    global $db;
    if ($db && $db->isConnected()) {
        try {
            $conn = $db->getConnection();
            $description = sprintf("Configuración cambiada: %s de '%s' a '%s'", 
                $setting_name, $old_value, $new_value);
            
            $stmt = $conn->prepare("INSERT INTO security_events (user_id, event_type, description, severity, ip_address, created_at) VALUES (?, 'settings_change', ?, 'low', ?, NOW())");
            $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $stmt->bind_param("iss", $user_id, $description, $ip);
            $stmt->execute();
            $stmt->close();
        } catch (Exception $e) {
            logEvent('ERROR', 'Error registrando cambio de configuración: ' . $e->getMessage());
        }
    }
}

// Función para validar contraseña segura
function validatePasswordStrength($password) {
    $errors = [];
    
    if (strlen($password) < 8) {
        $errors[] = "La contraseña debe tener al menos 8 caracteres";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = "La contraseña debe contener al menos una mayúscula";
    }
    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = "La contraseña debe contener al menos una minúscula";
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = "La contraseña debe contener al menos un número";
    }
    if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $errors[] = "La contraseña debe contener al menos un carácter especial";
    }
    
    return $errors;
}

// Función para obtener configuraciones del usuario
function getUserSettings($user_id) {
    global $db;
    
    $default_settings = [
        'theme' => 'dark',
        'language' => 'es',
        'timezone' => 'America/Bogota',
        'notifications_email' => true,
        'notifications_push' => false,
        'notifications_sms' => false,
        'security_alerts' => true,
        'system_updates' => true,
        'marketing_emails' => false,
        'auto_optimization' => true,
        'real_time_scan' => true,
        'gaming_mode' => false,
        'scan_frequency' => 'daily',
        'backup_frequency' => 'weekly',
        'data_sharing' => false,
        'analytics' => false,
        'two_factor_enabled' => false,
        'biometric_enabled' => false,
        'vpn_enabled' => false,
        'firewall_enabled' => true,
        'antivirus_enabled' => true,
        'performance_mode' => 'balanced',
        'startup_boost' => true,
        'memory_optimization' => true,
        'disk_cleanup' => 'weekly',
        'system_restore' => true
    ];
    
    if (!$db || !$db->isConnected()) {
        return $default_settings;
    }
    
    try {
        $conn = $db->getConnection();
        $stmt = $conn->prepare("SELECT * FROM user_settings WHERE user_id = ?");
        if (!$stmt) {
            return $default_settings;
        }
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            return array_merge($default_settings, $row);
        }
        $stmt->close();
    } catch (Exception $e) {
        logEvent('ERROR', 'Error obteniendo configuraciones: ' . $e->getMessage());
    }
    
    return $default_settings;
}

// Función para guardar configuraciones
function saveUserSettings($user_id, $settings) {
    global $db;
    
    if (!$db || !$db->isConnected()) {
        return false;
    }
    
    try {
        $conn = $db->getConnection();
        
        // Verificar si existen configuraciones
        $stmt = $conn->prepare("SELECT id FROM user_settings WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $exists = $result->num_rows > 0;
        $stmt->close();
        
        if ($exists) {
            // Actualizar configuraciones existentes
            $sql = "UPDATE user_settings SET ";
            $values = [];
            $types = "";
            
            foreach ($settings as $key => $value) {
                $sql .= "$key = ?, ";
                $values[] = $value;
                $types .= is_int($value) ? "i" : "s";
            }
            
            $sql = rtrim($sql, ", ");
            $sql .= " WHERE user_id = ?";
            $values[] = $user_id;
            $types .= "i";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$values);
        } else {
            // Insertar nuevas configuraciones
            $keys = array_keys($settings);
            $placeholders = array_fill(0, count($settings), '?');
            
            $sql = "INSERT INTO user_settings (user_id, " . implode(', ', $keys) . ") VALUES (?, " . implode(', ', $placeholders) . ")";
            
            $values = [$user_id];
            $types = "i";
            
            foreach ($settings as $value) {
                $values[] = $value;
                $types .= is_int($value) ? "i" : "s";
            }
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$values);
        }
        
        $result = $stmt->execute();
        $stmt->close();
        
        return $result;
    } catch (Exception $e) {
        logEvent('ERROR', 'Error guardando configuraciones: ' . $e->getMessage());
        return false;
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
                
                // Validar email
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    throw new Exception("Email inválido");
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
                
                // Actualizar perfil
                $stmt = $conn->prepare("UPDATE users SET fullname = ?, email = ?, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("ssi", $fullname, $email, $current_user_id);
                
                if ($stmt->execute()) {
                    $update_message = "Perfil actualizado correctamente";
                    $update_type = "success";
                    
                    // Actualizar sesión
                    $_SESSION['username'] = $fullname;
                    $_SESSION['email'] = $email;
                    
                    // Registrar el cambio
                    logSettingsChange('profile', 'updated', $fullname, $current_user_id);
                } else {
                    throw new Exception("Error al actualizar perfil");
                }
                $stmt->close();
                break;
                
            case 'change_password':
                $current_password = $_POST['current_password'] ?? '';
                $new_password = $_POST['new_password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';
                
                if ($new_password !== $confirm_password) {
                    throw new Exception("Las contraseñas no coinciden");
                }
                
                // Validar fortaleza de contraseña
                $password_errors = validatePasswordStrength($new_password);
                if (!empty($password_errors)) {
                    throw new Exception(implode(", ", $password_errors));
                }
                
                // Verificar contraseña actual
                $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
                $stmt->bind_param("i", $current_user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user_row = $result->fetch_assoc();
                $stmt->close();
                
                if (!password_verify($current_password, $user_row['password_hash'])) {
                    throw new Exception("Contraseña actual incorrecta");
                }
                
                // Actualizar contraseña
                $new_hash = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("UPDATE users SET password_hash = ?, password = ?, updated_at = NOW() WHERE id = ?");
                $stmt->bind_param("ssi", $new_hash, $new_password, $current_user_id);
                
                if ($stmt->execute()) {
                    $update_message = "Contraseña actualizada correctamente";
                    $update_type = "success";
                    
                    // Registrar cambio de contraseña
                    logSecurityEvent('password_change', 'Contraseña cambiada exitosamente', 'medium', $current_user_id);
                } else {
                    throw new Exception("Error al actualizar contraseña");
                }
                $stmt->close();
                break;
                
            case 'update_notifications':
                $settings = [
                    'notifications_email' => isset($_POST['email_notifications']) ? 1 : 0,
                    'notifications_push' => isset($_POST['push_notifications']) ? 1 : 0,
                    'notifications_sms' => isset($_POST['sms_notifications']) ? 1 : 0,
                    'security_alerts' => isset($_POST['security_alerts']) ? 1 : 0,
                    'system_updates' => isset($_POST['system_updates']) ? 1 : 0,
                    'marketing_emails' => isset($_POST['marketing_emails']) ? 1 : 0
                ];
                
                if (saveUserSettings($current_user_id, $settings)) {
                    $update_message = "Preferencias de notificación actualizadas";
                    $update_type = "success";
                } else {
                    throw new Exception("Error al actualizar notificaciones");
                }
                break;
                
            case 'update_privacy':
                $settings = [
                    'data_sharing' => isset($_POST['data_sharing']) ? 1 : 0,
                    'analytics' => isset($_POST['analytics']) ? 1 : 0,
                    'personalized_ads' => isset($_POST['personalized_ads']) ? 1 : 0,
                    'third_party_cookies' => isset($_POST['third_party_cookies']) ? 1 : 0
                ];
                
                if (saveUserSettings($current_user_id, $settings)) {
                    $update_message = "Configuración de privacidad actualizada";
                    $update_type = "success";
                } else {
                    throw new Exception("Error al actualizar privacidad");
                }
                break;
                
            case 'update_security':
                $settings = [
                    'two_factor_enabled' => isset($_POST['two_factor']) ? 1 : 0,
                    'biometric_enabled' => isset($_POST['biometric']) ? 1 : 0,
                    'vpn_enabled' => isset($_POST['vpn']) ? 1 : 0,
                    'firewall_enabled' => isset($_POST['firewall']) ? 1 : 0,
                    'antivirus_enabled' => isset($_POST['antivirus']) ? 1 : 0
                ];
                
                if (saveUserSettings($current_user_id, $settings)) {
                    $update_message = "Configuración de seguridad actualizada";
                    $update_type = "success";
                    
                    // Registrar cambio de seguridad
                    logSecurityEvent('security_settings_change', 'Configuración de seguridad modificada', 'medium', $current_user_id);
                } else {
                    throw new Exception("Error al actualizar seguridad");
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
                    'disk_cleanup' => $_POST['disk_cleanup'] ?? 'weekly'
                ];
                
                if (saveUserSettings($current_user_id, $settings)) {
                    $update_message = "Configuración de rendimiento actualizada";
                    $update_type = "success";
                } else {
                    throw new Exception("Error al actualizar rendimiento");
                }
                break;
                
            case 'enable_2fa':
                // Lógica para habilitar 2FA
                $secret = generateTwoFactorSecret();
                $stmt = $conn->prepare("UPDATE users SET two_factor_secret = ?, two_factor_enabled = 1 WHERE id = ?");
                $stmt->bind_param("si", $secret, $current_user_id);
                
                if ($stmt->execute()) {
                    $update_message = "Autenticación de dos factores habilitada";
                    $update_type = "success";
                    
                    // Generar código QR para 2FA
                    $_SESSION['2fa_secret'] = $secret;
                    $_SESSION['show_2fa_setup'] = true;
                } else {
                    throw new Exception("Error al habilitar 2FA");
                }
                $stmt->close();
                break;
                
            case 'disable_2fa':
                $stmt = $conn->prepare("UPDATE users SET two_factor_secret = NULL, two_factor_enabled = 0 WHERE id = ?");
                $stmt->bind_param("i", $current_user_id);
                
                if ($stmt->execute()) {
                    $update_message = "Autenticación de dos factores deshabilitada";
                    $update_type = "warning";
                } else {
                    throw new Exception("Error al deshabilitar 2FA");
                }
                $stmt->close();
                break;
                
            case 'delete_account':
                $password = $_POST['confirm_password'] ?? '';
                
                // Verificar contraseña
                $stmt = $conn->prepare("SELECT password_hash FROM users WHERE id = ?");
                $stmt->bind_param("i", $current_user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                $user_row = $result->fetch_assoc();
                $stmt->close();
                
                if (!password_verify($password, $user_row['password_hash'])) {
                    throw new Exception("Contraseña incorrecta");
                }
                
                // Marcar cuenta para eliminación
                $stmt = $conn->prepare("UPDATE users SET status = 'pending_deletion', deletion_date = DATE_ADD(NOW(), INTERVAL 30 DAY) WHERE id = ?");
                $stmt->bind_param("i", $current_user_id);
                
                if ($stmt->execute()) {
                    // Cerrar sesión
                    session_destroy();
                    header('Location: login.php?message=account_deletion_scheduled');
                    exit();
                } else {
                    throw new Exception("Error al programar eliminación de cuenta");
                }
                break;
                
            case 'export_data':
                // Exportar datos del usuario
                exportUserData($current_user_id);
                break;
                
            case 'update_theme':
                $theme = $_POST['theme'] ?? 'dark';
                $settings = ['theme' => $theme];
                
                if (saveUserSettings($current_user_id, $settings)) {
                    $_SESSION['theme'] = $theme;
                    $update_message = "Tema actualizado";
                    $update_type = "success";
                }
                break;
                
            case 'update_language':
                $language = $_POST['language'] ?? 'es';
                $settings = ['language' => $language];
                
                if (saveUserSettings($current_user_id, $settings)) {
                    $_SESSION['language'] = $language;
                    $update_message = "Idioma actualizado";
                    $update_type = "success";
                }
                break;
        }
    } catch (Exception $e) {
        $update_message = $e->getMessage();
        $update_type = "error";
        logEvent('ERROR', 'Error en configuración: ' . $e->getMessage());
    }
}

// Obtener datos actualizados del usuario
if ($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND status = 'active'");
        if ($stmt) {
            $stmt->bind_param("i", $current_user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result && $result->num_rows > 0) {
                $user_data = $result->fetch_assoc();
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        logEvent('ERROR', 'Error obteniendo datos de usuario: ' . $e->getMessage());
    }
}

// Fallback a usuarios por defecto si no hay datos en BD
if (!$user_data && isset($GLOBALS['DEFAULT_USERS'][$current_username])) {
    $user_data = $GLOBALS['DEFAULT_USERS'][$current_username];
}

// Datos por defecto si no se encuentra el usuario
if (!$user_data) {
    $user_data = [
        'id' => $current_user_id,
        'username' => $current_username,
        'fullname' => 'Usuario ' . ucfirst($current_username),
        'email' => $current_username . '@guardianai.com',
        'user_type' => 'basic',
        'premium_status' => 'basic',
        'status' => 'active',
        'created_at' => date('Y-m-d H:i:s'),
        'last_login' => date('Y-m-d H:i:s')
    ];
}

// Extraer información del usuario
$user_fullname = $user_data['fullname'] ?? 'Usuario ' . ucfirst($current_username);
$user_email = $user_data['email'] ?? '';
$is_premium = ($user_data['premium_status'] ?? 'basic') === 'premium';
$is_admin = ($user_data['user_type'] ?? 'basic') === 'admin';
$has_military_access = isset($user_data['military_access']) && $user_data['military_access'] == 1;
$security_clearance = $user_data['security_clearance'] ?? 'UNCLASSIFIED';
$created_at = $user_data['created_at'] ?? date('Y-m-d H:i:s');
$last_login = $user_data['last_login'] ?? date('Y-m-d H:i:s');

// Obtener configuraciones del usuario
$user_settings = getUserSettings($current_user_id);

// Obtener estadísticas del usuario
function getUserStatistics($user_id) {
    global $db;
    
    $stats = [
        'total_scans' => 0,
        'threats_blocked' => 0,
        'space_freed' => 0,
        'optimizations' => 0,
        'backup_count' => 0,
        'devices_protected' => 0,
        'security_score' => 100,
        'last_scan' => 'Nunca',
        'last_optimization' => 'Nunca',
        'last_backup' => 'Nunca'
    ];
    
    if (!$db || !$db->isConnected()) {
        return $stats;
    }
    
    try {
        $conn = $db->getConnection();
        
        // Obtener total de escaneos
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM security_events WHERE user_id = ? AND event_type LIKE '%scan%'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $stats['total_scans'] = $row['count'];
        }
        $stmt->close();
        
        // Obtener amenazas bloqueadas
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM security_events WHERE user_id = ? AND severity IN ('high', 'critical')");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $stats['threats_blocked'] = $row['count'];
        }
        $stmt->close();
        
        // Obtener dispositivos protegidos
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM protected_devices WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $stats['devices_protected'] = $row['count'];
        }
        $stmt->close();
        
        // Obtener último escaneo
        $stmt = $conn->prepare("SELECT MAX(created_at) as last_scan FROM security_events WHERE user_id = ? AND event_type LIKE '%scan%'");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if ($row['last_scan']) {
                $stats['last_scan'] = date('d/m/Y H:i', strtotime($row['last_scan']));
            }
        }
        $stmt->close();
        
    } catch (Exception $e) {
        logEvent('ERROR', 'Error obteniendo estadísticas: ' . $e->getMessage());
    }
    
    return $stats;
}

$user_stats = getUserStatistics($current_user_id);

// Obtener sesiones activas
function getActiveSessions($user_id) {
    global $db;
    
    $sessions = [];
    
    if (!$db || !$db->isConnected()) {
        return $sessions;
    }
    
    try {
        $conn = $db->getConnection();
        $stmt = $conn->prepare("
            SELECT 
                session_id,
                ip_address,
                user_agent,
                created_at,
                expires_at,
                is_active
            FROM user_sessions 
            WHERE user_id = ? AND is_active = 1
            ORDER BY created_at DESC
        ");
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $sessions[] = $row;
        }
        $stmt->close();
        
    } catch (Exception $e) {
        logEvent('ERROR', 'Error obteniendo sesiones: ' . $e->getMessage());
    }
    
    return $sessions;
}

$active_sessions = getActiveSessions($current_user_id);

// Función para generar secret de 2FA
function generateTwoFactorSecret() {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $secret = '';
    for ($i = 0; $i < 16; $i++) {
        $secret .= $characters[rand(0, 31)];
    }
    return $secret;
}

// Función para exportar datos del usuario
function exportUserData($user_id) {
    global $db;
    
    if (!$db || !$db->isConnected()) {
        return false;
    }
    
    try {
        $conn = $db->getConnection();
        $export_data = [];
        
        // Obtener todos los datos del usuario
        $tables = [
            'users',
            'security_events',
            'protected_devices',
            'assistant_conversations',
            'music_compositions',
            'audio_recordings'
        ];
        
        foreach ($tables as $table) {
            $stmt = $conn->prepare("SELECT * FROM $table WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $export_data[$table] = [];
            while ($row = $result->fetch_assoc()) {
                $export_data[$table][] = $row;
            }
            $stmt->close();
        }
        
        // Generar archivo JSON
        $json_data = json_encode($export_data, JSON_PRETTY_PRINT);
        $filename = 'guardianai_data_export_' . $user_id . '_' . date('YmdHis') . '.json';
        
        // Enviar headers para descarga
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($json_data));
        
        echo $json_data;
        exit();
        
    } catch (Exception $e) {
        logEvent('ERROR', 'Error exportando datos: ' . $e->getMessage());
        return false;
    }
}

// Obtener iniciales para el avatar
$name_parts = explode(' ', $user_fullname);
$user_initials = strtoupper(substr($name_parts[0], 0, 1));
if (count($name_parts) > 1) {
    $user_initials .= strtoupper(substr(end($name_parts), 0, 1));
}

// Obtener información de la base de datos
$database_info = [];
if ($db && $db->isConnected()) {
    try {
        $conn = $db->getConnection();
        
        // Obtener nombre de la base de datos directamente
        $database_info['name'] = 'guardia2_guardianai_db'; // Nombre fijo de tu BD
        $database_info['status'] = 'Conectado';
        $database_info['encryption'] = 'AES-256-GCM';
        
        // Obtener tamaño de la base de datos
        $stmt = $conn->prepare("
            SELECT 
                SUM(data_length + index_length) / 1024 / 1024 AS size_mb
            FROM information_schema.TABLES 
            WHERE table_schema = ?
        ");
        $stmt->bind_param("s", $database_info['name']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $database_info['size'] = round($row['size_mb'], 2) . ' MB';
        }
        $stmt->close();
        
    } catch (Exception $e) {
        logEvent('ERROR', 'Error obteniendo info de BD: ' . $e->getMessage());
        $database_info = [
            'name' => 'N/A',
            'status' => 'Error',
            'size' => 'N/A',
            'encryption' => 'N/A'
        ];
    }
} else {
    $database_info = [
        'name' => 'N/A',
        'status' => 'Desconectado',
        'size' => 'N/A',
        'encryption' => 'N/A'
    ];
}

// Registrar acceso a configuración
logSecurityEvent('settings_access', 'Usuario accedió a configuración', 'low', $current_user_id);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración - GuardianIA v3.0</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --warning-gradient: linear-gradient(135deg, #ffa726 0%, #fb8c00 100%);
            --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --premium-gradient: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            --military-gradient: linear-gradient(135deg, #2c5530 0%, #8b9dc3 100%);
            
            --bg-primary: #0f0f23;
            --bg-secondary: #1a1a2e;
            --bg-card: #16213e;
            --text-primary: #ffffff;
            --text-secondary: #a0a9c0;
            --border-color: #2d3748;
            --shadow-color: rgba(0, 0, 0, 0.3);
            
            --animation-speed: 0.3s;
            --border-radius: 12px;
            --card-padding: 24px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: var(--bg-primary);
            color: var(--text-primary);
            line-height: 1.6;
            overflow-x: hidden;
        }

        /* Animated Background */
        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            background: var(--bg-primary);
        }

        .animated-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(102, 126, 234, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(118, 75, 162, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(67, 233, 123, 0.2) 0%, transparent 50%);
            animation: backgroundPulse 8s ease-in-out infinite;
        }

        @keyframes backgroundPulse {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 0.6; }
        }

        /* Navigation */
        .navbar {
            background: rgba(26, 26, 46, 0.95);
            backdrop-filter: blur(20px);
            border-bottom: 1px solid var(--border-color);
            padding: 1rem 0;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 1.5rem;
            font-weight: 800;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
        }

        .nav-link {
            color: var(--text-secondary);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all var(--animation-speed) ease;
        }

        .nav-link:hover {
            color: var(--text-primary);
            background: rgba(255, 255, 255, 0.1);
        }

        .nav-link.active {
            background: var(--primary-gradient);
            color: white;
        }

        .user-menu {
            position: relative;
        }

        .user-avatar-nav {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            cursor: pointer;
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%;
            right: 0;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            min-width: 200px;
            margin-top: 10px;
            box-shadow: 0 10px 25px var(--shadow-color);
        }

        .dropdown-menu.show {
            display: block;
        }

        .dropdown-item {
            display: block;
            padding: 10px 15px;
            color: var(--text-secondary);
            text-decoration: none;
            transition: all var(--animation-speed) ease;
        }

        .dropdown-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
        }

        /* Main Container */
        .main-container {
            margin-top: 80px;
            padding: 2rem;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        .settings-layout {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 2rem;
        }

        /* Sidebar */
        .settings-sidebar {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            height: fit-content;
            position: sticky;
            top: 100px;
        }

        .sidebar-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
        }

        .sidebar-menu {
            list-style: none;
        }

        .sidebar-item {
            margin-bottom: 0.5rem;
        }

        .sidebar-link {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            color: var(--text-secondary);
            text-decoration: none;
            border-radius: 8px;
            transition: all var(--animation-speed) ease;
            cursor: pointer;
        }

        .sidebar-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
        }

        .sidebar-link.active {
            background: var(--primary-gradient);
            color: white;
        }

        .sidebar-icon {
            width: 20px;
            text-align: center;
        }

        /* Content Area */
        .settings-content {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: var(--card-padding);
            min-height: 800px;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .section-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: white;
            background: var(--primary-gradient);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .section-description {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Forms */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }

        .form-label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .form-input {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 1rem;
            transition: all var(--animation-speed) ease;
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            background: rgba(255, 255, 255, 0.08);
        }

        .form-input:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .form-textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 1rem;
            resize: vertical;
            min-height: 100px;
            font-family: inherit;
        }

        .form-select {
            width: 100%;
            padding: 0.75rem 1rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            color: var(--text-primary);
            font-size: 1rem;
            cursor: pointer;
        }

        .form-switch {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            margin-bottom: 1rem;
            transition: all var(--animation-speed) ease;
        }

        .form-switch:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        .switch-label {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        .switch-title {
            font-weight: 600;
            color: var(--text-primary);
        }

        .switch-description {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            width: 50px;
            height: 24px;
        }

        .toggle-input {
            opacity: 0;
            width: 0;
            height: 0;
        }

        .toggle-slider {
            position: absolute;
            cursor: pointer;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: var(--border-color);
            transition: var(--animation-speed);
            border-radius: 24px;
        }

        .toggle-slider:before {
            position: absolute;
            content: "";
            height: 18px;
            width: 18px;
            left: 3px;
            bottom: 3px;
            background-color: white;
            transition: var(--animation-speed);
            border-radius: 50%;
        }

        .toggle-input:checked + .toggle-slider {
            background: var(--primary-gradient);
        }

        .toggle-input:checked + .toggle-slider:before {
            transform: translateX(26px);
        }

        /* Buttons */
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1rem;
        }

        .btn-primary {
            background: var(--primary-gradient);
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn-secondary {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
        }

        .btn-danger {
            background: var(--danger-gradient);
            color: white;
        }

        .btn-danger:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(250, 112, 154, 0.4);
        }

        .btn-success {
            background: var(--success-gradient);
            color: white;
        }

        .btn-success:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(67, 233, 123, 0.4);
        }

        .btn-warning {
            background: var(--warning-gradient);
            color: white;
        }

        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 167, 38, 0.4);
        }

        .btn-group {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 1rem;
            animation: slideIn 0.3s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateY(-20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .alert-success {
            background: rgba(67, 233, 123, 0.1);
            border: 1px solid rgba(67, 233, 123, 0.3);
            color: #43e97b;
        }

        .alert-error {
            background: rgba(250, 112, 154, 0.1);
            border: 1px solid rgba(250, 112, 154, 0.3);
            color: #fa709a;
        }

        .alert-warning {
            background: rgba(255, 167, 38, 0.1);
            border: 1px solid rgba(255, 167, 38, 0.3);
            color: #ffa726;
        }

        .alert-info {
            background: rgba(79, 172, 254, 0.1);
            border: 1px solid rgba(79, 172, 254, 0.3);
            color: #4facfe;
        }

        /* Profile Section */
        .profile-header {
            display: flex;
            align-items: center;
            gap: 2rem;
            margin-bottom: 2rem;
            padding: 2rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: var(--border-radius);
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: 700;
            color: white;
            position: relative;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }

        .avatar-upload {
            position: absolute;
            bottom: 0;
            right: 0;
            width: 36px;
            height: 36px;
            background: var(--bg-secondary);
            border: 2px solid var(--bg-card);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
        }

        .avatar-upload:hover {
            background: var(--primary-gradient);
            transform: scale(1.1);
        }

        .profile-info {
            flex: 1;
        }

        .profile-info h2 {
            font-size: 1.8rem;
            margin-bottom: 0.5rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .profile-email {
            color: var(--text-secondary);
            margin-bottom: 1rem;
            font-size: 1rem;
        }

        .profile-badges {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .profile-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .badge-premium {
            background: var(--premium-gradient);
            color: #000;
        }

        .badge-admin {
            background: var(--danger-gradient);
            color: white;
        }

        .badge-military {
            background: var(--military-gradient);
            color: white;
        }

        .badge-verified {
            background: var(--success-gradient);
            color: white;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.5rem;
            transition: all var(--animation-speed) ease;
        }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px var(--shadow-color);
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1rem;
            font-size: 1.2rem;
            color: white;
        }

        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .stat-trend {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            margin-top: 0.5rem;
            font-size: 0.85rem;
        }

        .trend-up {
            color: #43e97b;
        }

        .trend-down {
            color: #fa709a;
        }

        /* Section Cards */
        .section-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .section-card-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-primary);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-card-description {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 1.5rem;
        }

        /* Tab System */
        .tab-container {
            display: none;
        }

        .tab-container.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { 
                opacity: 0; 
                transform: translateY(10px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }

        /* Session List */
        .session-list {
            list-style: none;
        }

        .session-item {
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            margin-bottom: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all var(--animation-speed) ease;
        }

        .session-item:hover {
            background: rgba(255, 255, 255, 0.08);
        }

        .session-info {
            flex: 1;
        }

        .session-device {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .session-details {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .session-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #43e97b;
            font-size: 0.9rem;
        }

        .session-status.current {
            color: #4facfe;
        }

        /* Password Strength Indicator */
        .password-strength {
            margin-top: 0.5rem;
        }

        .strength-bar {
            height: 4px;
            background: var(--border-color);
            border-radius: 2px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .strength-fill {
            height: 100%;
            transition: all var(--animation-speed) ease;
        }

        .strength-weak {
            width: 33%;
            background: var(--danger-gradient);
        }

        .strength-medium {
            width: 66%;
            background: var(--warning-gradient);
        }

        .strength-strong {
            width: 100%;
            background: var(--success-gradient);
        }

        .strength-text {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        /* Danger Zone */
        .danger-zone {
            background: rgba(250, 112, 154, 0.05);
            border: 1px solid rgba(250, 112, 154, 0.3);
            border-radius: 8px;
            padding: 1.5rem;
            margin-top: 2rem;
        }

        .danger-zone-title {
            color: #fa709a;
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .danger-zone-description {
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }

        /* Activity Timeline */
        .activity-timeline {
            position: relative;
            padding-left: 2rem;
        }

        .activity-timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--border-color);
        }

        .timeline-item {
            position: relative;
            padding-bottom: 1.5rem;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -26px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary-gradient);
            border: 2px solid var(--bg-card);
        }

        .timeline-content {
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 1rem;
        }

        .timeline-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .timeline-time {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        /* Progress Bars */
        .progress-bar {
            height: 8px;
            background: var(--border-color);
            border-radius: 4px;
            overflow: hidden;
            margin: 1rem 0;
        }

        .progress-fill {
            height: 100%;
            background: var(--primary-gradient);
            transition: width 1s ease;
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }

        .modal.show {
            display: flex;
        }

        .modal-content {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .modal-title {
            font-size: 1.3rem;
            font-weight: 700;
        }

        .modal-close {
            background: transparent;
            border: none;
            color: var(--text-secondary);
            font-size: 1.5rem;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
        }

        .modal-close:hover {
            color: var(--text-primary);
        }

        /* QR Code */
        .qr-code-container {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 8px;
            margin: 1rem 0;
        }

        .qr-code {
            max-width: 200px;
            margin: 0 auto;
        }

        /* Loading State */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 3000;
        }

        .loading-overlay.show {
            display: flex;
        }

        .loading-content {
            text-align: center;
            color: white;
        }

        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
            margin: 0 auto 1rem;
        }

        /* Tooltips */
        .tooltip {
            position: relative;
            display: inline-block;
        }

        .tooltip-text {
            visibility: hidden;
            width: 200px;
            background: var(--bg-secondary);
            color: var(--text-primary);
            text-align: center;
            border-radius: 6px;
            padding: 0.5rem;
            position: absolute;
            z-index: 1;
            bottom: 125%;
            left: 50%;
            margin-left: -100px;
            opacity: 0;
            transition: opacity 0.3s;
            font-size: 0.85rem;
        }

        .tooltip:hover .tooltip-text {
            visibility: visible;
            opacity: 1;
        }

        /* Info Box */
        .info-box {
            background: rgba(79, 172, 254, 0.1);
            border: 1px solid rgba(79, 172, 254, 0.3);
            border-radius: 8px;
            padding: 1rem;
            margin: 1rem 0;
            display: flex;
            gap: 1rem;
            align-items: start;
        }

        .info-box-icon {
            color: #4facfe;
            font-size: 1.2rem;
        }

        .info-box-content {
            flex: 1;
        }

        .info-box-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: #4facfe;
        }

        .info-box-text {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 1024px) {
            .settings-layout {
                grid-template-columns: 250px 1fr;
            }
        }

        @media (max-width: 768px) {
            .settings-layout {
                grid-template-columns: 1fr;
            }

            .settings-sidebar {
                position: static;
                margin-bottom: 2rem;
            }

            .profile-header {
                flex-direction: column;
                text-align: center;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .btn-group {
                flex-direction: column;
            }

            .nav-menu {
                display: none;
            }

            .session-item {
                flex-direction: column;
                align-items: start;
                gap: 1rem;
            }
        }

        /* Print Styles */
        @media print {
            .navbar,
            .settings-sidebar,
            .btn,
            .alert {
                display: none !important;
            }

            .settings-layout {
                grid-template-columns: 1fr;
            }

            body {
                background: white;
                color: black;
            }

            .settings-content {
                border: none;
                box-shadow: none;
            }
        }

        /* Dark Theme Override */
        body.light-theme {
            --bg-primary: #f8f9fa;
            --bg-secondary: #ffffff;
            --bg-card: #ffffff;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --border-color: #dee2e6;
            --shadow-color: rgba(0, 0, 0, 0.1);
        }

        /* Animations */
        @keyframes slideInRight {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes slideInLeft {
            from {
                transform: translateX(-100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        @keyframes fadeInUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .animate-slide-right {
            animation: slideInRight 0.5s ease;
        }

        .animate-slide-left {
            animation: slideInLeft 0.5s ease;
        }

        .animate-fade-up {
            animation: fadeInUp 0.5s ease;
        }
    </style>
</head>
<body>
    <div class="animated-bg"></div>
    
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-content">
            <div class="loading-spinner"></div>
            <p>Procesando...</p>
        </div>
    </div>
    
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <i class="fas fa-shield-alt"></i>
                <span>GuardianIA</span>
            </div>
            <ul class="nav-menu">
                <li><a href="user_dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="#" class="nav-link active">Configuración</a></li>
                <?php if ($is_admin): ?>
                <li><a href="admin_dashboard.php" class="nav-link">Panel Admin</a></li>
                <?php endif; ?>
                <?php if ($has_military_access): ?>
                <li><a href="military_dashboard.php" class="nav-link">Panel Militar</a></li>
                <?php endif; ?>
            </ul>
            <div class="user-menu">
                <div class="user-avatar-nav" onclick="toggleDropdown()">
                    <?php echo $user_initials; ?>
                </div>
                <div class="dropdown-menu" id="userDropdown">
                    <a href="#profile" class="dropdown-item" onclick="showTab('profile', document.querySelector('.sidebar-link'))">
                        <i class="fas fa-user"></i> Mi Perfil
                    </a>
                    <a href="user_dashboard.php" class="dropdown-item">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <hr style="border-color: var(--border-color); margin: 10px 0;">
                    <a href="logout.php" class="dropdown-item">
                        <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="main-container">
        <?php if ($update_message): ?>
        <div class="alert alert-<?php echo $update_type; ?>">
            <i class="fas fa-<?php echo $update_type === 'success' ? 'check-circle' : ($update_type === 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
            <span><?php echo htmlspecialchars($update_message); ?></span>
        </div>
        <?php endif; ?>

        <div class="settings-layout">
            <!-- Sidebar -->
            <div class="settings-sidebar">
                <h2 class="sidebar-title">Configuración</h2>
                <ul class="sidebar-menu">
                    <li class="sidebar-item">
                        <a class="sidebar-link active" onclick="showTab('profile', this)">
                            <i class="fas fa-user sidebar-icon"></i>
                            Mi Perfil
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link" onclick="showTab('security', this)">
                            <i class="fas fa-shield-alt sidebar-icon"></i>
                            Seguridad
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link" onclick="showTab('performance', this)">
                            <i class="fas fa-tachometer-alt sidebar-icon"></i>
                            Rendimiento
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link" onclick="showTab('notifications', this)">
                            <i class="fas fa-bell sidebar-icon"></i>
                            Notificaciones
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link" onclick="showTab('privacy', this)">
                            <i class="fas fa-lock sidebar-icon"></i>
                            Privacidad
                        </a>
                    </li>
                    <li class="sidebar-item">
                        <a class="sidebar-link" onclick="showTab('account', this)">
                            <i class="fas fa-cog sidebar-icon"></i>
                            Cuenta
                        </a>
                    </li>
                </ul>

                <!-- Database Info -->
                <div style="margin-top: 2rem; padding-top: 1rem; border-top: 1px solid var(--border-color);">
                    <p style="color: var(--text-secondary); font-size: 0.85rem; margin-bottom: 0.5rem;">
                        <i class="fas fa-database"></i> Base de datos
                    </p>
                    <p style="color: var(--text-primary); font-size: 0.9rem;">
                        <?php echo $database_info['status']; ?>
                    </p>
                    <p style="color: var(--text-secondary); font-size: 0.8rem;">
                        <?php echo $database_info['size'] ?? 'N/A'; ?>
                    </p>
                </div>
            </div>

            <!-- Content Area -->
            <div class="settings-content">
                <!-- Profile Tab -->
                <div id="profile-tab" class="tab-container active">
                    <div class="section-header">
                        <div class="section-icon">
                            <i class="fas fa-user"></i>
                        </div>
                        <div>
                            <h1 class="section-title">Mi Perfil</h1>
                            <p class="section-description">Administra tu información personal y preferencias</p>
                        </div>
                    </div>

                    <div class="profile-header">
                        <div class="profile-avatar">
                            <?php echo $user_initials; ?>
                            <div class="avatar-upload" onclick="document.getElementById('avatarInput').click()">
                                <i class="fas fa-camera"></i>
                            </div>
                            <input type="file" id="avatarInput" style="display: none;" accept="image/*">
                        </div>
                        <div class="profile-info">
                            <h2><?php echo htmlspecialchars($user_fullname); ?></h2>
                            <p class="profile-email"><?php echo htmlspecialchars($user_email); ?></p>
                            <div class="profile-badges">
                                <?php if ($is_premium): ?>
                                <span class="profile-badge badge-premium">
                                    <i class="fas fa-crown"></i> Premium
                                </span>
                                <?php endif; ?>
                                <?php if ($is_admin): ?>
                                <span class="profile-badge badge-admin">
                                    <i class="fas fa-user-shield"></i> Administrador
                                </span>
                                <?php endif; ?>
                                <?php if ($has_military_access): ?>
                                <span class="profile-badge badge-military">
                                    <i class="fas fa-medal"></i> <?php echo $security_clearance; ?>
                                </span>
                                <?php endif; ?>
                                <span class="profile-badge badge-verified">
                                    <i class="fas fa-check-circle"></i> Verificado
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon" style="background: var(--success-gradient);">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <div class="stat-value"><?php echo date('d/m/Y', strtotime($created_at)); ?></div>
                            <div class="stat-label">Miembro desde</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon" style="background: var(--info-gradient);">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-value"><?php echo date('H:i', strtotime($last_login)); ?></div>
                            <div class="stat-label">Último acceso</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon" style="background: var(--warning-gradient);">
                                <i class="fas fa-shield-alt"></i>
                            </div>
                            <div class="stat-value"><?php echo $user_stats['security_score']; ?>%</div>
                            <div class="stat-label">Puntuación de seguridad</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-icon" style="background: var(--danger-gradient);">
                                <i class="fas fa-mobile-alt"></i>
                            </div>
                            <div class="stat-value"><?php echo $user_stats['devices_protected']; ?></div>
                            <div class="stat-label">Dispositivos protegidos</div>
                        </div>
                    </div>

                    <form method="POST" action="" id="profileForm">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="section-card">
                            <h3 class="section-card-title">
                                <i class="fas fa-edit"></i> Información Personal
                            </h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Nombre Completo</label>
                                    <input type="text" name="fullname" class="form-input" 
                                           value="<?php echo htmlspecialchars($user_fullname); ?>" required>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Correo Electrónico</label>
                                    <input type="email" name="email" class="form-input" 
                                           value="<?php echo htmlspecialchars($user_email); ?>" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label class="form-label">Nombre de Usuario</label>
                                    <input type="text" class="form-input" 
                                           value="<?php echo htmlspecialchars($current_username); ?>" disabled>
                                </div>
                                <div class="form-group">
                                    <label class="form-label">Teléfono</label>
                                    <input type="tel" name="phone" class="form-input" 
                                           placeholder="+57 300 123 4567">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Biografía</label>
                                <textarea name="bio" class="form-textarea" 
                                          placeholder="Cuéntanos algo sobre ti..."></textarea>
                            </div>

                            <div class="btn-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Guardar Cambios
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="resetForm('profileForm')">
                                    <i class="fas fa-undo"></i> Restablecer
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Activity Timeline -->
                    <div class="section-card">
                        <h3 class="section-card-title">
                            <i class="fas fa-history"></i> Actividad Reciente
                        </h3>
                        <div class="activity-timeline">
                            <div class="timeline-item">
                                <div class="timeline-content">
                                    <div class="timeline-title">Inicio de sesión exitoso</div>
                                    <div class="timeline-time">Hoy, <?php echo date('H:i'); ?></div>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-content">
                                    <div class="timeline-title">Escaneo de seguridad completado</div>
                                    <div class="timeline-time"><?php echo $user_stats['last_scan']; ?></div>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-content">
                                    <div class="timeline-title">Optimización del sistema</div>
                                    <div class="timeline-time"><?php echo $user_stats['last_optimization']; ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Security Tab -->
                <div id="security-tab" class="tab-container">
                    <div class="section-header">
                        <div class="section-icon" style="background: var(--success-gradient);">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div>
                            <h1 class="section-title">Seguridad</h1>
                            <p class="section-description">Protege tu cuenta y gestiona el acceso</p>
                        </div>
                    </div>

                    <div class="section-card">
                        <h3 class="section-card-title">
                            <i class="fas fa-key"></i> Cambiar Contraseña
                        </h3>
                        <p class="section-card-description">
                            Asegúrate de usar una contraseña fuerte y única para proteger tu cuenta
                        </p>
                        
                        <form method="POST" action="" onsubmit="return validatePassword()" id="passwordForm">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="form-group">
                                <label class="form-label">Contraseña Actual</label>
                                <input type="password" name="current_password" class="form-input" required>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Nueva Contraseña</label>
                                <input type="password" name="new_password" id="new_password" 
                                       class="form-input" required minlength="8" onkeyup="checkPasswordStrength()">
                                <div class="password-strength">
                                    <div class="strength-bar">
                                        <div id="strengthFill" class="strength-fill"></div>
                                    </div>
                                    <p id="strengthText" class="strength-text">Ingresa una contraseña</p>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Confirmar Nueva Contraseña</label>
                                <input type="password" name="confirm_password" id="confirm_password" 
                                       class="form-input" required>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Actualizar Contraseña
                            </button>
                        </form>
                    </div>

                    <div class="section-card">
                        <h3 class="section-card-title">
                            <i class="fas fa-mobile-alt"></i> Autenticación de Dos Factores
                        </h3>
                        <p class="section-card-description">
                            Añade una capa extra de seguridad a tu cuenta requiriendo un código adicional al iniciar sesión
                        </p>
                        
                        <?php if (isset($user_data['two_factor_enabled']) && $user_data['two_factor_enabled']): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle"></i>
                            La autenticación de dos factores está activa
                        </div>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="disable_2fa">
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-times"></i> Desactivar 2FA
                            </button>
                        </form>
                        <?php else: ?>
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="enable_2fa">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-shield-alt"></i> Activar 2FA
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>

                    <div class="section-card">
                        <h3 class="section-card-title">
                            <i class="fas fa-desktop"></i> Sesiones Activas
                        </h3>
                        <p class="section-card-description">
                            Dispositivos donde has iniciado sesión recientemente
                        </p>
                        
                        <ul class="session-list">
                            <li class="session-item">
                                <div class="session-info">
                                    <div class="session-device">
                                        <i class="fas fa-laptop"></i> Dispositivo Actual
                                    </div>
                                    <div class="session-details">
                                        <?php echo $_SERVER['HTTP_USER_AGENT'] ?? 'Navegador desconocido'; ?><br>
                                        IP: <?php echo $_SERVER['REMOTE_ADDR'] ?? 'Desconocida'; ?>
                                    </div>
                                </div>
                                <div class="session-status current">
                                    <i class="fas fa-circle"></i> Activo ahora
                                </div>
                            </li>
                            
                            <?php foreach ($active_sessions as $session): ?>
                            <li class="session-item">
                                <div class="session-info">
                                    <div class="session-device">
                                        <i class="fas fa-mobile-alt"></i> Dispositivo
                                    </div>
                                    <div class="session-details">
                                        <?php echo htmlspecialchars($session['user_agent']); ?><br>
                                        IP: <?php echo htmlspecialchars($session['ip_address']); ?>
                                    </div>
                                </div>
                                <div class="session-status">
                                    <?php echo date('d/m/Y H:i', strtotime($session['created_at'])); ?>
                                </div>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        
                        <button class="btn btn-danger" onclick="confirmAction('cerrar todas las sesiones')">
                            <i class="fas fa-sign-out-alt"></i> Cerrar Todas las Sesiones
                        </button>
                    </div>

                    <div class="section-card">
                        <h3 class="section-card-title">
                            <i class="fas fa-lock"></i> Opciones de Seguridad Avanzadas
                        </h3>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="update_security">
                            
                            <div class="form-switch">
                                <div class="switch-label">
                                    <span class="switch-title">VPN Integrada</span>
                                    <span class="switch-description">
                                        Navega de forma anónima y segura
                                    </span>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="vpn" class="toggle-input" 
                                           <?php echo $user_settings['vpn_enabled'] ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            <div class="form-switch">
                                <div class="switch-label">
                                    <span class="switch-title">Firewall Avanzado</span>
                                    <span class="switch-description">
                                        Protección contra ataques de red
                                    </span>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="firewall" class="toggle-input" 
                                           <?php echo $user_settings['firewall_enabled'] ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            <div class="form-switch">
                                <div class="switch-label">
                                    <span class="switch-title">Antivirus en Tiempo Real</span>
                                    <span class="switch-description">
                                        Escaneo continuo de archivos y descargas
                                    </span>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="antivirus" class="toggle-input" 
                                           <?php echo $user_settings['antivirus_enabled'] ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            <div class="form-switch">
                                <div class="switch-label">
                                    <span class="switch-title">Autenticación Biométrica</span>
                                    <span class="switch-description">
                                        Usa tu huella o rostro para acceder
                                    </span>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="biometric" class="toggle-input" 
                                           <?php echo $user_settings['biometric_enabled'] ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Guardar Configuración
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Performance Tab -->
                <div id="performance-tab" class="tab-container">
                    <div class="section-header">
                        <div class="section-icon" style="background: var(--info-gradient);">
                            <i class="fas fa-tachometer-alt"></i>
                        </div>
                        <div>
                            <h1 class="section-title">Rendimiento</h1>
                            <p class="section-description">Optimiza el comportamiento y rendimiento del sistema</p>
                        </div>
                    </div>

                    <div class="info-box">
                        <div class="info-box-icon">
                            <i class="fas fa-info-circle"></i>
                        </div>
                        <div class="info-box-content">
                            <div class="info-box-title">Optimización Inteligente</div>
                            <div class="info-box-text">
                                GuardianIA ajusta automáticamente la configuración para obtener el mejor rendimiento según tu uso
                            </div>
                        </div>
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_performance">
                        
                        <div class="section-card">
                            <h3 class="section-card-title">
                                <i class="fas fa-magic"></i> Optimización Automática
                            </h3>
                            
                            <div class="form-switch">
                                <div class="switch-label">
                                    <span class="switch-title">Optimización Automática</span>
                                    <span class="switch-description">
                                        Permite que GuardianIA optimice tu sistema automáticamente
                                    </span>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="auto_optimization" class="toggle-input" 
                                           <?php echo $user_settings['auto_optimization'] ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            <div class="form-switch">
                                <div class="switch-label">
                                    <span class="switch-title">Escaneo en Tiempo Real</span>
                                    <span class="switch-description">
                                        Protección continua contra amenazas
                                    </span>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="real_time_scan" class="toggle-input" 
                                           <?php echo $user_settings['real_time_scan'] ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            <div class="form-switch">
                                <div class="switch-label">
                                    <span class="switch-title">Modo Gaming</span>
                                    <span class="switch-description">
                                        Suspende notificaciones y optimiza recursos durante los juegos
                                    </span>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="gaming_mode" class="toggle-input" 
                                           <?php echo $user_settings['gaming_mode'] ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            <div class="form-switch">
                                <div class="switch-label">
                                    <span class="switch-title">Inicio Rápido</span>
                                    <span class="switch-description">
                                        Acelera el tiempo de arranque del sistema
                                    </span>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="startup_boost" class="toggle-input" 
                                           <?php echo $user_settings['startup_boost'] ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            <div class="form-switch">
                                <div class="switch-label">
                                    <span class="switch-title">Optimización de Memoria</span>
                                    <span class="switch-description">
                                        Libera memoria RAM no utilizada automáticamente
                                    </span>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="memory_optimization" class="toggle-input" 
                                           <?php echo $user_settings['memory_optimization'] ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="section-card">
                            <h3 class="section-card-title">
                                <i class="fas fa-sliders-h"></i> Configuración de Rendimiento
                            </h3>
                            
                            <div class="form-group">
                                <label class="form-label">Modo de Rendimiento</label>
                                <select name="performance_mode" class="form-select">
                                    <option value="eco" <?php echo $user_settings['performance_mode'] == 'eco' ? 'selected' : ''; ?>>
                                        Ahorro de Energía
                                    </option>
                                    <option value="balanced" <?php echo $user_settings['performance_mode'] == 'balanced' ? 'selected' : ''; ?>>
                                        Balanceado
                                    </option>
                                    <option value="high" <?php echo $user_settings['performance_mode'] == 'high' ? 'selected' : ''; ?>>
                                        Alto Rendimiento
                                    </option>
                                    <option value="ultra" <?php echo $user_settings['performance_mode'] == 'ultra' ? 'selected' : ''; ?>>
                                        Ultra (Premium)
                                    </option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Frecuencia de Escaneo</label>
                                <select name="scan_frequency" class="form-select">
                                    <option value="realtime" <?php echo $user_settings['scan_frequency'] == 'realtime' ? 'selected' : ''; ?>>
                                        Tiempo Real
                                    </option>
                                    <option value="hourly" <?php echo $user_settings['scan_frequency'] == 'hourly' ? 'selected' : ''; ?>>
                                        Cada Hora
                                    </option>
                                    <option value="daily" <?php echo $user_settings['scan_frequency'] == 'daily' ? 'selected' : ''; ?>>
                                        Diario
                                    </option>
                                    <option value="weekly" <?php echo $user_settings['scan_frequency'] == 'weekly' ? 'selected' : ''; ?>>
                                        Semanal
                                    </option>
                                    <option value="manual" <?php echo $user_settings['scan_frequency'] == 'manual' ? 'selected' : ''; ?>>
                                        Manual
                                    </option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label class="form-label">Limpieza de Disco</label>
                                <select name="disk_cleanup" class="form-select">
                                    <option value="daily" <?php echo $user_settings['disk_cleanup'] == 'daily' ? 'selected' : ''; ?>>
                                        Diario
                                    </option>
                                    <option value="weekly" <?php echo $user_settings['disk_cleanup'] == 'weekly' ? 'selected' : ''; ?>>
                                        Semanal
                                    </option>
                                    <option value="monthly" <?php echo $user_settings['disk_cleanup'] == 'monthly' ? 'selected' : ''; ?>>
                                        Mensual
                                    </option>
                                    <option value="never" <?php echo $user_settings['disk_cleanup'] == 'never' ? 'selected' : ''; ?>>
                                        Nunca
                                    </option>
                                </select>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Configuración
                        </button>
                    </form>

                    <!-- Performance Stats -->
                    <div class="section-card">
                        <h3 class="section-card-title">
                            <i class="fas fa-chart-line"></i> Estadísticas de Rendimiento
                        </h3>
                        
                        <div class="stats-grid">
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $user_stats['total_scans']; ?></div>
                                <div class="stat-label">Escaneos Totales</div>
                                <div class="stat-trend trend-up">
                                    <i class="fas fa-arrow-up"></i> +12% este mes
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $user_stats['threats_blocked']; ?></div>
                                <div class="stat-label">Amenazas Bloqueadas</div>
                                <div class="stat-trend trend-down">
                                    <i class="fas fa-arrow-down"></i> -5% este mes
                                </div>
                            </div>
                            <div class="stat-card">
                                <div class="stat-value"><?php echo $user_stats['optimizations']; ?></div>
                                <div class="stat-label">Optimizaciones</div>
                                <div class="stat-trend trend-up">
                                    <i class="fas fa-arrow-up"></i> +8% este mes
                                </div>
                            </div>
                        </div>

                        <div style="margin-top: 1.5rem;">
                            <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">Uso de CPU</p>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 45%;"></div>
                            </div>
                        </div>

                        <div style="margin-top: 1rem;">
                            <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">Uso de RAM</p>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 67%;"></div>
                            </div>
                        </div>

                        <div style="margin-top: 1rem;">
                            <p style="color: var(--text-secondary); margin-bottom: 0.5rem;">Uso de Disco</p>
                            <div class="progress-bar">
                                <div class="progress-fill" style="width: 78%;"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Notifications Tab -->
                <div id="notifications-tab" class="tab-container">
                    <div class="section-header">
                        <div class="section-icon" style="background: var(--warning-gradient);">
                            <i class="fas fa-bell"></i>
                        </div>
                        <div>
                            <h1 class="section-title">Notificaciones</h1>
                            <p class="section-description">Controla cómo y cuándo recibir alertas</p>
                        </div>
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_notifications">

                        <div class="section-card">
                            <h3 class="section-card-title">
                                <i class="fas fa-envelope"></i> Canales de Notificación
                            </h3>

                            <div class="form-switch">
                                <div class="switch-label">
                                    <span class="switch-title">Notificaciones por Email</span>
                                    <span class="switch-description">
                                        Recibe actualizaciones importantes en tu correo
                                    </span>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="email_notifications" class="toggle-input" 
                                           <?php echo $user_settings['notifications_email'] ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            <div class="form-switch">
                                <div class="switch-label">
                                    <span class="switch-title">Notificaciones Push</span>
                                    <span class="switch-description">
                                        Alertas instantáneas en tu navegador
                                    </span>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="push_notifications" class="toggle-input" 
                                           <?php echo $user_settings['notifications_push'] ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            <div class="form-switch">
                                <div class="switch-label">
                                    <span class="switch-title">Notificaciones SMS</span>
                                    <span class="switch-description">
                                        Mensajes de texto para alertas críticas
                                    </span>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="sms_notifications" class="toggle-input" 
                                           <?php echo $user_settings['notifications_sms'] ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <div class="section-card">
                            <h3 class="section-card-title">
                                <i class="fas fa-bell-slash"></i> Tipos de Notificaciones
                            </h3>

                            <div class="form-switch">
                                <div class="switch-label">
                                    <span class="switch-title">Alertas de Seguridad</span>
                                    <span class="switch-description">
                                        Notificaciones sobre amenazas y vulnerabilidades detectadas
                                    </span>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="security_alerts" class="toggle-input" 
                                           <?php echo $user_settings['security_alerts'] ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            <div class="form-switch">
                                <div class="switch-label">
                                    <span class="switch-title">Actualizaciones del Sistema</span>
                                    <span class="switch-description">
                                        Información sobre nuevas características y mejoras
                                    </span>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="system_updates" class="toggle-input" 
                                           <?php echo $user_settings['system_updates'] ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            <div class="form-switch">
                                <div class="switch-label">
                                    <span class="switch-title">Emails de Marketing</span>
                                    <span class="switch-description">
                                        Ofertas especiales y promociones de GuardianIA
                                    </span>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="marketing_emails" class="toggle-input" 
                                           <?php echo $user_settings['marketing_emails'] ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Preferencias
                        </button>
                    </form>
                </div>

                <!-- Privacy Tab -->
                <div id="privacy-tab" class="tab-container">
                    <div class="section-header">
                        <div class="section-icon" style="background: var(--danger-gradient);">
                            <i class="fas fa-lock"></i>
                        </div>
                        <div>
                            <h1 class="section-title">Privacidad</h1>
                            <p class="section-description">Controla tu información y datos personales</p>
                        </div>
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="action" value="update_privacy">

                        <div class="section-card">
                            <h3 class="section-card-title">
                                <i class="fas fa-user-secret"></i> Control de Datos
                            </h3>

                            <div class="form-switch">
                                <div class="switch-label">
                                    <span class="switch-title">Compartir Datos Anónimos</span>
                                    <span class="switch-description">
                                        Ayuda a mejorar GuardianIA compartiendo estadísticas anónimas
                                    </span>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="data_sharing" class="toggle-input" 
                                           <?php echo $user_settings['data_sharing'] ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            <div class="form-switch">
                                <div class="switch-label">
                                    <span class="switch-title">Análisis de Uso</span>
                                    <span class="switch-description">
                                        Permite recopilar datos para mejorar la experiencia
                                    </span>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="analytics" class="toggle-input" 
                                           <?php echo $user_settings['analytics'] ? 'checked' : ''; ?>>
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            <div class="form-switch">
                                <div class="switch-label">
                                    <span class="switch-title">Publicidad Personalizada</span>
                                    <span class="switch-description">
                                        Muestra anuncios basados en tus preferencias
                                    </span>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="personalized_ads" class="toggle-input">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>

                            <div class="form-switch">
                                <div class="switch-label">
                                    <span class="switch-title">Cookies de Terceros</span>
                                    <span class="switch-description">
                                        Permite cookies de servicios externos
                                    </span>
                                </div>
                                <label class="toggle-switch">
                                    <input type="checkbox" name="third_party_cookies" class="toggle-input">
                                    <span class="toggle-slider"></span>
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Actualizar Privacidad
                        </button>
                    </form>

                    <div class="section-card">
                        <h3 class="section-card-title">
                            <i class="fas fa-download"></i> Tus Datos
                        </h3>
                        <p class="section-card-description">
                            Descarga una copia de toda tu información almacenada en GuardianIA
                        </p>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="action" value="export_data">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-file-download"></i> Descargar Mis Datos
                            </button>
                        </form>
                    </div>

                    <div class="info-box">
                        <div class="info-box-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="info-box-content">
                            <div class="info-box-title">Tus datos están protegidos</div>
                            <div class="info-box-text">
                                Utilizamos cifrado AES-256 para proteger tu información. Nunca compartimos tus datos personales con terceros sin tu consentimiento.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Account Tab -->
                <div id="account-tab" class="tab-container">
                    <div class="section-header">
                        <div class="section-icon" style="background: var(--primary-gradient);">
                            <i class="fas fa-cog"></i>
                        </div>
                        <div>
                            <h1 class="section-title">Cuenta</h1>
                            <p class="section-description">Gestión general de tu cuenta</p>
                        </div>
                    </div>

                    <div class="section-card">
                        <h3 class="section-card-title">
                            <i class="fas fa-crown"></i> Plan Actual
                        </h3>
                        
                        <div style="padding: 1.5rem; background: <?php echo $is_premium ? 'var(--premium-gradient)' : 'rgba(255,255,255,0.05)'; ?>; border-radius: 8px; margin-bottom: 1rem;">
                            <h4 style="font-size: 1.5rem; margin-bottom: 0.5rem; <?php echo $is_premium ? 'color: #000;' : ''; ?>">
                                <?php echo $is_premium ? 'Plan Premium' : 'Plan Básico'; ?>
                            </h4>
                            <p style="<?php echo $is_premium ? 'color: #333;' : 'color: var(--text-secondary);'; ?> font-size: 1rem; margin-bottom: 1rem;">
                                <?php echo $is_premium ? 'Acceso completo a todas las funciones avanzadas' : 'Funciones básicas de protección y optimización'; ?>
                            </p>
                            
                            <?php if ($is_premium): ?>
                            <p style="color: #333; font-size: 0.9rem;">
                                <i class="fas fa-check"></i> Protección cuántica<br>
                                <i class="fas fa-check"></i> Optimización avanzada<br>
                                <i class="fas fa-check"></i> Soporte prioritario 24/7<br>
                                <i class="fas fa-check"></i> Sin límites de dispositivos
                            </p>
                            <?php else: ?>
                            <button class="btn btn-warning" onclick="window.location.href='membership_system.php'">
                                <i class="fas fa-rocket"></i> Actualizar a Premium
                            </button>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="section-card">
                        <h3 class="section-card-title">
                            <i class="fas fa-globe"></i> Preferencias Regionales
                        </h3>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label">Idioma</label>
                                <select class="form-select" onchange="updateLanguage(this.value)">
                                    <option value="es" selected>Español</option>
                                    <option value="en">English</option>
                                    <option value="pt">Português</option>
                                    <option value="fr">Français</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label class="form-label">Zona Horaria</label>
                                <select class="form-select">
                                    <option value="America/Bogota" selected>Colombia (UTC-5)</option>
                                    <option value="America/Mexico_City">México (UTC-6)</option>
                                    <option value="America/Buenos_Aires">Argentina (UTC-3)</option>
                                    <option value="America/New_York">Estados Unidos Este (UTC-5)</option>
                                    <option value="Europe/Madrid">España (UTC+1)</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Tema</label>
                            <select class="form-select" onchange="updateTheme(this.value)">
                                <option value="dark" selected>Oscuro</option>
                                <option value="light">Claro</option>
                                <option value="auto">Automático (Sistema)</option>
                            </select>
                        </div>

                        <button class="btn btn-primary">
                            <i class="fas fa-save"></i> Guardar Cambios
                        </button>
                    </div>

                    <div class="section-card">
                        <h3 class="section-card-title">
                            <i class="fas fa-database"></i> Información del Sistema
                        </h3>
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem;">
                            <div>
                                <p style="color: var(--text-secondary); margin-bottom: 0.25rem;">Versión</p>
                                <p style="font-weight: 600;">GuardianIA v3.0.0</p>
                            </div>
                            <div>
                                <p style="color: var(--text-secondary); margin-bottom: 0.25rem;">Base de Datos</p>
                                <p style="font-weight: 600;"><?php echo $database_info['status']; ?></p>
                            </div>
                            <div>
                                <p style="color: var(--text-secondary); margin-bottom: 0.25rem;">Cifrado</p>
                                <p style="font-weight: 600;"><?php echo $database_info['encryption']; ?></p>
                            </div>
                            <div>
                                <p style="color: var(--text-secondary); margin-bottom: 0.25rem;">Tamaño</p>
                                <p style="font-weight: 600;"><?php echo $database_info['size']; ?></p>
                            </div>
                        </div>
                    </div>

                    <div class="danger-zone">
                        <h3 class="danger-zone-title">
                            <i class="fas fa-exclamation-triangle"></i> Zona de Peligro
                        </h3>
                        <p class="danger-zone-description">
                            Una vez que elimines tu cuenta, no hay vuelta atrás. Se eliminarán permanentemente todos tus datos, configuraciones y accesos.
                        </p>
                        
                        <button class="btn btn-danger" onclick="showDeleteModal()">
                            <i class="fas fa-trash"></i> Eliminar Cuenta
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Account Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Eliminar Cuenta</h2>
                <button class="modal-close" onclick="closeDeleteModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                Esta acción es permanente e irreversible
            </div>
            
            <p style="margin-bottom: 1.5rem;">
                Al eliminar tu cuenta:
            </p>
            <ul style="list-style: none; margin-bottom: 1.5rem;">
                <li style="margin-bottom: 0.5rem; color: var(--text-secondary);">
                    <i class="fas fa-times" style="color: #fa709a;"></i> Se eliminarán todos tus datos personales
                </li>
                <li style="margin-bottom: 0.5rem; color: var(--text-secondary);">
                    <i class="fas fa-times" style="color: #fa709a;"></i> Perderás acceso a todas las funciones premium
                </li>
                <li style="margin-bottom: 0.5rem; color: var(--text-secondary);">
                    <i class="fas fa-times" style="color: #fa709a;"></i> No podrás recuperar tu historial
                </li>
                <li style="margin-bottom: 0.5rem; color: var(--text-secondary);">
                    <i class="fas fa-times" style="color: #fa709a;"></i> Se desactivarán todos tus dispositivos
                </li>
            </ul>
            
            <form method="POST" action="" onsubmit="return confirmDelete()">
                <input type="hidden" name="action" value="delete_account">
                
                <div class="form-group">
                    <label class="form-label">Confirma tu contraseña para continuar</label>
                    <input type="password" name="confirm_password" class="form-input" required>
                </div>
                
                <div class="btn-group">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Sí, eliminar mi cuenta
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="closeDeleteModal()">
                        <i class="fas fa-times"></i> Cancelar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- 2FA Setup Modal -->
    <?php if (isset($_SESSION['show_2fa_setup']) && $_SESSION['show_2fa_setup']): ?>
    <div id="2faModal" class="modal show">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="modal-title">Configurar Autenticación de Dos Factores</h2>
            </div>
            
            <div class="qr-code-container">
                <p style="color: #333; margin-bottom: 1rem;">
                    Escanea este código QR con tu aplicación de autenticación
                </p>
                <div class="qr-code">
                    <!-- Aquí iría el código QR generado -->
                    <img src="https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=otpauth://totp/GuardianIA:<?php echo urlencode($user_email); ?>?secret=<?php echo $_SESSION['2fa_secret'] ?? ''; ?>" alt="QR Code">
                </div>
                <p style="color: #666; font-size: 0.9rem; margin-top: 1rem;">
                    Código manual: <code><?php echo $_SESSION['2fa_secret'] ?? ''; ?></code>
                </p>
            </div>
            
            <button class="btn btn-primary" onclick="close2FAModal()">
                <i class="fas fa-check"></i> He configurado mi aplicación
            </button>
        </div>
    </div>
    <?php unset($_SESSION['show_2fa_setup']); ?>
    <?php endif; ?>

    <script>
        // Tab Navigation
        function showTab(tabName, element) {
            // Hide all tabs
            const tabs = document.querySelectorAll('.tab-container');
            tabs.forEach(tab => tab.classList.remove('active'));
            
            // Show selected tab
            document.getElementById(tabName + '-tab').classList.add('active');
            
            // Update sidebar active state
            const links = document.querySelectorAll('.sidebar-link');
            links.forEach(link => link.classList.remove('active'));
            element.classList.add('active');
            
            // Scroll to top
            window.scrollTo(0, 0);
        }

        // Toggle dropdown
        function toggleDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('show');
        }

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('userDropdown');
            const avatar = document.querySelector('.user-avatar-nav');
            
            if (dropdown && avatar && !avatar.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });

        // Password Validation
        function validatePassword() {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                alert('Las contraseñas no coinciden');
                return false;
            }
            
            if (newPassword.length < 8) {
                alert('La contraseña debe tener al menos 8 caracteres');
                return false;
            }
            
            showLoading();
            return true;
        }

        // Check Password Strength
        function checkPasswordStrength() {
            const password = document.getElementById('new_password').value;
            const strengthFill = document.getElementById('strengthFill');
            const strengthText = document.getElementById('strengthText');
            
            let strength = 0;
            let strengthClass = '';
            let strengthMessage = '';
            
            if (password.length === 0) {
                strengthFill.className = 'strength-fill';
                strengthText.textContent = 'Ingresa una contraseña';
                return;
            }
            
            // Check length
            if (password.length >= 8) strength++;
            if (password.length >= 12) strength++;
            
            // Check for lowercase
            if (/[a-z]/.test(password)) strength++;
            
            // Check for uppercase
            if (/[A-Z]/.test(password)) strength++;
            
            // Check for numbers
            if (/[0-9]/.test(password)) strength++;
            
            // Check for special characters
            if (/[!@#$%^&*(),.?":{}|<>]/.test(password)) strength++;
            
            // Determine strength level
            if (strength <= 2) {
                strengthClass = 'strength-weak';
                strengthMessage = 'Contraseña débil';
            } else if (strength <= 4) {
                strengthClass = 'strength-medium';
                strengthMessage = 'Contraseña media';
            } else {
                strengthClass = 'strength-strong';
                strengthMessage = 'Contraseña fuerte';
            }
            
            strengthFill.className = 'strength-fill ' + strengthClass;
            strengthText.textContent = strengthMessage;
        }

        // Reset Form
        function resetForm(formId) {
            document.getElementById(formId).reset();
            showToast('Formulario restablecido', 'info');
        }

        // Show Delete Modal
        function showDeleteModal() {
            document.getElementById('deleteModal').classList.add('show');
        }

        // Close Delete Modal
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.remove('show');
        }

        // Close 2FA Modal
        function close2FAModal() {
            document.getElementById('2faModal').classList.remove('show');
        }

        // Confirm Delete
        function confirmDelete() {
            if (!confirm('¿Estás absolutamente seguro? Esta acción no se puede deshacer.')) {
                return false;
            }
            
            if (!confirm('Esta es tu ÚLTIMA oportunidad. ¿Realmente deseas eliminar tu cuenta permanentemente?')) {
                return false;
            }
            
            showLoading();
            return true;
        }

        // Confirm Action
        function confirmAction(action) {
            if (confirm(`¿Estás seguro de que deseas ${action}?`)) {
                showLoading();
                // Aquí iría la lógica para la acción
                setTimeout(() => {
                    hideLoading();
                    showToast(`${action} completado exitosamente`, 'success');
                }, 2000);
            }
        }

        // Update Language
        function updateLanguage(language) {
            showLoading();
            
            // Simular cambio de idioma
            setTimeout(() => {
                hideLoading();
                showToast('Idioma actualizado', 'success');
                
                // En producción, aquí harías una llamada AJAX
                const formData = new FormData();
                formData.append('action', 'update_language');
                formData.append('language', language);
                
                // fetch('user_settings.php', { method: 'POST', body: formData })
            }, 1000);
        }

        // Update Theme
        function updateTheme(theme) {
            showLoading();
            
            if (theme === 'light') {
                document.body.classList.add('light-theme');
            } else {
                document.body.classList.remove('light-theme');
            }
            
            setTimeout(() => {
                hideLoading();
                showToast('Tema actualizado', 'success');
                
                // Guardar en localStorage
                localStorage.setItem('theme', theme);
                
                // En producción, también guardarías en el servidor
                const formData = new FormData();
                formData.append('action', 'update_theme');
                formData.append('theme', theme);
                
                // fetch('user_settings.php', { method: 'POST', body: formData })
            }, 500);
        }

        // Show Loading
        function showLoading() {
            document.getElementById('loadingOverlay').classList.add('show');
        }

        // Hide Loading
        function hideLoading() {
            document.getElementById('loadingOverlay').classList.remove('show');
        }

        // Show Toast
        function showToast(message, type = 'info') {
            // Crear toast dinámicamente
            const existingToast = document.querySelector('.toast.dynamic');
            if (existingToast) {
                existingToast.remove();
            }
            
            const toast = document.createElement('div');
            toast.className = `alert alert-${type} dynamic`;
            toast.style.position = 'fixed';
            toast.style.top = '100px';
            toast.style.right = '2rem';
            toast.style.zIndex = '2001';
            toast.style.minWidth = '300px';
            
            const icon = type === 'success' ? 'check-circle' : 
                        type === 'error' ? 'exclamation-circle' : 
                        type === 'warning' ? 'exclamation-triangle' : 'info-circle';
            
            toast.innerHTML = `
                <i class="fas fa-${icon}"></i>
                <span>${message}</span>
            `;
            
            document.body.appendChild(toast);
            
            // Auto-hide after 4 seconds
            setTimeout(() => {
                toast.style.transition = 'opacity 0.5s';
                toast.style.opacity = '0';
                setTimeout(() => toast.remove(), 500);
            }, 4000);
        }

        // Avatar Upload Handler
        document.getElementById('avatarInput')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                if (file.size > 5 * 1024 * 1024) {
                    showToast('El archivo es demasiado grande (máx. 5MB)', 'error');
                    return;
                }
                
                if (!file.type.startsWith('image/')) {
                    showToast('Por favor selecciona una imagen', 'error');
                    return;
                }
                
                // Preview the image
                const reader = new FileReader();
                reader.onload = function(e) {
                    // En producción, aquí subirías la imagen al servidor
                    showToast('Avatar actualizado (demo)', 'success');
                };
                reader.readAsDataURL(file);
            }
        });

        // Initialize on load
        document.addEventListener('DOMContentLoaded', function() {
            // Check saved theme
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme === 'light') {
                document.body.classList.add('light-theme');
            }
            
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert:not(.dynamic)');
                alerts.forEach(alert => {
                    alert.style.transition = 'opacity 0.5s';
                    alert.style.opacity = '0';
                    setTimeout(() => alert.remove(), 500);
                });
            }, 5000);
            
            // Initialize tooltips
            initializeTooltips();
            
            // Check for URL hash to open specific tab
            const hash = window.location.hash.substring(1);
            if (hash) {
                const tabLink = document.querySelector(`.sidebar-link[onclick*="${hash}"]`);
                if (tabLink) {
                    showTab(hash, tabLink);
                }
            }
            
            // Initialize progress bars animation
            animateProgressBars();
            
            // Setup keyboard shortcuts
            setupKeyboardShortcuts();
            
            // Check session status
            checkSessionStatus();
        });

        // Initialize Tooltips
        function initializeTooltips() {
            const tooltips = document.querySelectorAll('[data-tooltip]');
            tooltips.forEach(element => {
                element.classList.add('tooltip');
                const tooltipText = document.createElement('span');
                tooltipText.className = 'tooltip-text';
                tooltipText.textContent = element.getAttribute('data-tooltip');
                element.appendChild(tooltipText);
            });
        }

        // Animate Progress Bars
        function animateProgressBars() {
            const progressBars = document.querySelectorAll('.progress-fill');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 100);
            });
        }

        // Setup Keyboard Shortcuts
        function setupKeyboardShortcuts() {
            document.addEventListener('keydown', function(e) {
                // Ctrl/Cmd + S to save
                if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                    e.preventDefault();
                    const activeForm = document.querySelector('.tab-container.active form');
                    if (activeForm) {
                        activeForm.submit();
                    }
                }
                
                // Escape to close modals
                if (e.key === 'Escape') {
                    const modals = document.querySelectorAll('.modal.show');
                    modals.forEach(modal => {
                        modal.classList.remove('show');
                    });
                }
                
                // Alt + 1-6 for quick tab navigation
                if (e.altKey && e.key >= '1' && e.key <= '6') {
                    const tabIndex = parseInt(e.key) - 1;
                    const tabs = ['profile', 'security', 'performance', 'notifications', 'privacy', 'account'];
                    if (tabs[tabIndex]) {
                        const link = document.querySelector(`.sidebar-link[onclick*="${tabs[tabIndex]}"]`);
                        if (link) {
                            showTab(tabs[tabIndex], link);
                        }
                    }
                }
            });
        }

        // Check Session Status
        function checkSessionStatus() {
            // Check session every 5 minutes
            setInterval(() => {
                fetch('check_session.php')
                    .then(response => response.json())
                    .then(data => {
                        if (!data.valid) {
                            showToast('Tu sesión ha expirado. Redirigiendo al login...', 'warning');
                            setTimeout(() => {
                                window.location.href = 'login.php';
                            }, 3000);
                        }
                    })
                    .catch(error => {
                        console.error('Error checking session:', error);
                    });
            }, 5 * 60 * 1000);
        }

        // Form Validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const inputs = form.querySelectorAll('[required]');
                let valid = true;
                
                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        valid = false;
                        input.style.borderColor = '#fa709a';
                        
                        // Remove error style on input
                        input.addEventListener('input', function() {
                            this.style.borderColor = '';
                        }, { once: true });
                    }
                });
                
                if (!valid) {
                    e.preventDefault();
                    showToast('Por favor completa todos los campos requeridos', 'error');
                }
            });
        });

        // Real-time form save indicator
        let formChangeTimeout;
        document.querySelectorAll('input, select, textarea').forEach(element => {
            element.addEventListener('change', function() {
                clearTimeout(formChangeTimeout);
                const form = this.closest('form');
                if (form) {
                    // Show unsaved changes indicator
                    const saveBtn = form.querySelector('.btn-primary');
                    if (saveBtn && !saveBtn.textContent.includes('*')) {
                        saveBtn.innerHTML = '<i class="fas fa-save"></i> Guardar Cambios *';
                    }
                    
                    // Auto-save after 30 seconds of inactivity (opcional)
                    formChangeTimeout = setTimeout(() => {
                        // form.submit(); // Descomentar para auto-guardado
                    }, 30000);
                }
            });
        });

        // Smooth scroll for internal links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Export functionality
        function exportSettings() {
            showLoading();
            
            // Simular exportación
            setTimeout(() => {
                hideLoading();
                showToast('Configuraciones exportadas exitosamente', 'success');
                
                // En producción, descargarías un archivo JSON
                const settings = {
                    theme: localStorage.getItem('theme') || 'dark',
                    language: 'es',
                    // ... más configuraciones
                };
                
                const blob = new Blob([JSON.stringify(settings, null, 2)], {type: 'application/json'});
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = 'guardianai_settings.json';
                a.click();
            }, 1000);
        }

        // Import functionality
        function importSettings() {
            const input = document.createElement('input');
            input.type = 'file';
            input.accept = '.json';
            
            input.onchange = function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        try {
                            const settings = JSON.parse(e.target.result);
                            // Aplicar configuraciones
                            if (settings.theme) {
                                updateTheme(settings.theme);
                            }
                            showToast('Configuraciones importadas exitosamente', 'success');
                        } catch (error) {
                            showToast('Error al importar configuraciones', 'error');
                        }
                    };
                    reader.readAsText(file);
                }
            };
            
            input.click();
        }

        // Print settings
        function printSettings() {
            window.print();
        }

        // Check for unsaved changes before leaving
        window.addEventListener('beforeunload', function(e) {
            const unsavedIndicators = document.querySelectorAll('.btn-primary');
            let hasUnsaved = false;
            
            unsavedIndicators.forEach(btn => {
                if (btn.textContent.includes('*')) {
                    hasUnsaved = true;
                }
            });
            
            if (hasUnsaved) {
                e.preventDefault();
                e.returnValue = 'Tienes cambios sin guardar. ¿Estás seguro de que quieres salir?';
            }
        });

        // Debug mode toggle (for development)
        let debugClickCount = 0;
        document.querySelector('.logo').addEventListener('click', function() {
            debugClickCount++;
            if (debugClickCount >= 7) {
                debugClickCount = 0;
                document.body.classList.toggle('debug-mode');
                showToast('Modo debug ' + (document.body.classList.contains('debug-mode') ? 'activado' : 'desactivado'), 'info');
            }
            
            setTimeout(() => {
                debugClickCount = 0;
            }, 3000);
        });

        // Performance monitoring
        if (window.performance) {
            window.addEventListener('load', function() {
                const perfData = window.performance.timing;
                const pageLoadTime = perfData.loadEventEnd - perfData.navigationStart;
                console.log('Page load time:', pageLoadTime + 'ms');
                
                // Send to analytics if needed
                if (pageLoadTime > 3000) {
                    console.warn('Page load time is high:', pageLoadTime + 'ms');
                }
            });
        }

        // Service Worker registration (for PWA support)
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/sw.js').then(function(registration) {
                console.log('ServiceWorker registration successful');
            }).catch(function(err) {
                console.log('ServiceWorker registration failed: ', err);
            });
        }

        // Handle network status
        window.addEventListener('online', function() {
            showToast('Conexión restaurada', 'success');
        });

        window.addEventListener('offline', function() {
            showToast('Sin conexión a internet', 'error');
        });

        // Lazy loading for images
        const lazyImages = document.querySelectorAll('img[data-src]');
        const imageObserver = new IntersectionObserver(function(entries, observer) {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.removeAttribute('data-src');
                    imageObserver.unobserve(img);
                }
            });
        });

        lazyImages.forEach(img => {
            imageObserver.observe(img);
        });

        // Final initialization complete
        console.log('GuardianIA Settings v3.0 - Initialized successfully');
        console.log('User ID:', <?php echo $current_user_id; ?>);
        console.log('Premium Status:', <?php echo $is_premium ? 'true' : 'false'; ?>);
        console.log('Database Status:', '<?php echo $database_info['status']; ?>');
    </script>

    <!-- Additional Styles for Debug Mode -->
    <style>
        body.debug-mode * {
            outline: 1px solid rgba(255, 0, 0, 0.1) !important;
        }
        
        body.debug-mode .settings-content::before {
            content: 'DEBUG MODE';
            position: fixed;
            top: 90px;
            right: 10px;
            background: red;
            color: white;
            padding: 5px 10px;
            font-size: 12px;
            z-index: 9999;
            border-radius: 4px;
        }
    </style>
</body>
</html>
<?php
// Cerrar conexión a base de datos al final
if ($db && $db->isConnected()) {
    $db->close();
}

// Log de fin de ejecución
logEvent('INFO', 'Configuración de usuario cargada exitosamente para usuario: ' . $current_user_id);
?>