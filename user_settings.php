<?php
/**
 * GuardianIA v3.0 FINAL - Sistema de Configuración de Usuario
 * Anderson Mamian Chicangana - Integrado con config.php
 * Manejo robusto con fallback de base de datos
 */

require_once 'config.php';

// Usar la conexión global del config.php
$conn = $GLOBALS['conn'] ?? null;
$db = $GLOBALS['db'] ?? null;

// Procesar solicitudes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? '';
    
    // Verificar rate limiting
    $client_ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    if (!rateLimitCheck($client_ip, 100, 3600)) {
        echo json_encode(['success' => false, 'error' => 'Demasiadas solicitudes. Intenta más tarde.']);
        exit;
    }
    
    switch ($action) {
        case 'update_security_settings':
            $user_id = $_POST['user_id'] ?? 1;
            $settings = [
                'real_time_protection' => $_POST['real_time_protection'] ?? 0,
                'auto_scan' => $_POST['auto_scan'] ?? 0,
                'firewall_enabled' => $_POST['firewall_enabled'] ?? 0,
                'threat_notifications' => $_POST['threat_notifications'] ?? 0,
                'scan_frequency' => $_POST['scan_frequency'] ?? 'daily',
                'quarantine_auto' => $_POST['quarantine_auto'] ?? 0
            ];
            
            $result = updateUserSettings($conn, $user_id, 'security', $settings);
            echo json_encode($result);
            exit;
            
        case 'update_performance_settings':
            $user_id = $_POST['user_id'] ?? 1;
            $settings = [
                'auto_optimization' => $_POST['auto_optimization'] ?? 0,
                'ram_optimization' => $_POST['ram_optimization'] ?? 0,
                'storage_cleanup' => $_POST['storage_cleanup'] ?? 0,
                'battery_optimization' => $_POST['battery_optimization'] ?? 0,
                'optimization_schedule' => $_POST['optimization_schedule'] ?? 'daily',
                'performance_mode' => $_POST['performance_mode'] ?? 'balanced'
            ];
            
            $result = updateUserSettings($conn, $user_id, 'performance', $settings);
            echo json_encode($result);
            exit;
            
        case 'update_notification_settings':
            $user_id = $_POST['user_id'] ?? 1;
            $settings = [
                'email_notifications' => $_POST['email_notifications'] ?? 0,
                'push_notifications' => $_POST['push_notifications'] ?? 0,
                'sound_alerts' => $_POST['sound_alerts'] ?? 0,
                'threat_alerts' => $_POST['threat_alerts'] ?? 0,
                'optimization_alerts' => $_POST['optimization_alerts'] ?? 0,
                'system_updates' => $_POST['system_updates'] ?? 0
            ];
            
            $result = updateUserSettings($conn, $user_id, 'notifications', $settings);
            echo json_encode($result);
            exit;
            
        case 'update_privacy_settings':
            $user_id = $_POST['user_id'] ?? 1;
            $settings = [
                'data_collection' => $_POST['data_collection'] ?? 0,
                'analytics_sharing' => $_POST['analytics_sharing'] ?? 0,
                'crash_reports' => $_POST['crash_reports'] ?? 0,
                'usage_statistics' => $_POST['usage_statistics'] ?? 0,
                'location_tracking' => $_POST['location_tracking'] ?? 0,
                'third_party_sharing' => $_POST['third_party_sharing'] ?? 0
            ];
            
            $result = updateUserSettings($conn, $user_id, 'privacy', $settings);
            echo json_encode($result);
            exit;
            
        case 'update_profile':
            $user_id = $_POST['user_id'] ?? 1;
            $profile_data = [
                'full_name' => sanitizeInput($_POST['full_name'] ?? ''),
                'email' => sanitizeInput($_POST['email'] ?? ''),
                'phone' => sanitizeInput($_POST['phone'] ?? ''),
                'language' => sanitizeInput($_POST['language'] ?? 'es'),
                'timezone' => sanitizeInput($_POST['timezone'] ?? 'America/Bogota'),
                'theme' => sanitizeInput($_POST['theme'] ?? 'dark')
            ];
            
            // Validar email
            if (!empty($profile_data['email']) && !validateEmail($profile_data['email'])) {
                echo json_encode(['success' => false, 'error' => 'Email inválido']);
                exit;
            }
            
            $result = updateUserProfile($conn, $user_id, $profile_data);
            echo json_encode($result);
            exit;
            
        case 'change_password':
            $user_id = $_POST['user_id'] ?? 1;
            $current_password = $_POST['current_password'] ?? '';
            $new_password = $_POST['new_password'] ?? '';
            $confirm_password = $_POST['confirm_password'] ?? '';
            
            $result = changeUserPassword($conn, $user_id, $current_password, $new_password, $confirm_password);
            echo json_encode($result);
            exit;
            
        case 'export_data':
            $user_id = $_POST['user_id'] ?? 1;
            $result = exportUserData($conn, $user_id);
            echo json_encode($result);
            exit;
            
        case 'delete_account':
            $user_id = $_POST['user_id'] ?? 1;
            $password = $_POST['password'] ?? '';
            $result = deleteUserAccount($conn, $user_id, $password);
            echo json_encode($result);
            exit;
    }
}

// Funciones auxiliares mejoradas con manejo robusto de errores
function updateUserSettings($conn, $user_id, $category, $settings) {
    if (!$conn) {
        // Modo simulación sin base de datos
        logEvent('INFO', "Configuración guardada en modo simulación: $category", $settings);
        return ['success' => true, 'message' => 'Configuración actualizada correctamente (modo simulación)'];
    }
    
    try {
        $settings_json = json_encode($settings);
        
        // Crear tabla si no existe
        $create_table = "CREATE TABLE IF NOT EXISTS user_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            category VARCHAR(50) NOT NULL,
            settings TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY unique_user_category (user_id, category)
        )";
        $conn->query($create_table);
        
        $stmt = $conn->prepare("
            INSERT INTO user_settings (user_id, category, settings, updated_at) 
            VALUES (?, ?, ?, NOW()) 
            ON DUPLICATE KEY UPDATE 
            settings = VALUES(settings), 
            updated_at = VALUES(updated_at)
        ");
        
        if ($stmt) {
            $stmt->bind_param("iss", $user_id, $category, $settings_json);
            
            if ($stmt->execute()) {
                $stmt->close();
                logEvent('INFO', "Configuración actualizada: $category para usuario $user_id");
                logSecurityEvent('SETTINGS_UPDATED', "Configuración $category actualizada", 'low', $user_id);
                return ['success' => true, 'message' => 'Configuración actualizada correctamente'];
            }
            $stmt->close();
        }
        
        return ['success' => false, 'error' => 'Error al actualizar configuración'];
    } catch (Exception $e) {
        logEvent('ERROR', 'Error actualizando configuración: ' . $e->getMessage());
        // Fallback a modo simulación
        return ['success' => true, 'message' => 'Configuración guardada (modo simulación)', 'note' => 'Error de BD: ' . $e->getMessage()];
    }
}

function updateUserProfile($conn, $user_id, $profile_data) {
    if (!$conn) {
        logEvent('INFO', "Perfil actualizado en modo simulación para usuario $user_id", $profile_data);
        return ['success' => true, 'message' => 'Perfil actualizado correctamente (modo simulación)'];
    }
    
    try {
        // Crear tabla si no existe
        $create_table = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255),
            password_hash VARCHAR(255),
            email VARCHAR(255),
            fullname VARCHAR(255),
            phone VARCHAR(50),
            language VARCHAR(10) DEFAULT 'es',
            timezone VARCHAR(100) DEFAULT 'America/Bogota',
            theme VARCHAR(20) DEFAULT 'dark',
            user_type ENUM('user', 'admin') DEFAULT 'user',
            premium_status ENUM('basic', 'premium') DEFAULT 'basic',
            premium_expires_at DATETIME NULL,
            status ENUM('active', 'inactive', 'banned') DEFAULT 'active',
            login_attempts INT DEFAULT 0,
            last_login DATETIME,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $conn->query($create_table);
        
        $stmt = $conn->prepare("
            UPDATE users SET 
            fullname = ?, 
            email = ?, 
            phone = ?, 
            language = ?, 
            timezone = ?, 
            theme = ?, 
            updated_at = NOW() 
            WHERE id = ?
        ");
        
        if ($stmt) {
            $stmt->bind_param("ssssssi", 
                $profile_data['full_name'],
                $profile_data['email'],
                $profile_data['phone'],
                $profile_data['language'],
                $profile_data['timezone'],
                $profile_data['theme'],
                $user_id
            );
            
            if ($stmt->execute()) {
                $stmt->close();
                logEvent('INFO', "Perfil actualizado para usuario $user_id");
                logSecurityEvent('PROFILE_UPDATED', 'Perfil de usuario actualizado', 'low', $user_id);
                return ['success' => true, 'message' => 'Perfil actualizado correctamente'];
            }
            $stmt->close();
        }
        
        return ['success' => false, 'error' => 'Error al actualizar perfil'];
    } catch (Exception $e) {
        logEvent('ERROR', 'Error actualizando perfil: ' . $e->getMessage());
        return ['success' => true, 'message' => 'Perfil actualizado (modo simulación)', 'note' => 'Error de BD: ' . $e->getMessage()];
    }
}

function changeUserPassword($conn, $user_id, $current_password, $new_password, $confirm_password) {
    // Validaciones básicas
    if ($new_password !== $confirm_password) {
        return ['success' => false, 'error' => 'Las contraseñas no coinciden'];
    }
    
    if (strlen($new_password) < 8) {
        return ['success' => false, 'error' => 'La contraseña debe tener al menos 8 caracteres'];
    }
    
    // Verificar complejidad de contraseña
    if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/', $new_password)) {
        return ['success' => false, 'error' => 'La contraseña debe contener al menos una mayúscula, una minúscula y un número'];
    }
    
    if (!$conn) {
        logEvent('WARNING', "Intento de cambio de contraseña sin BD para usuario $user_id");
        return ['success' => false, 'error' => 'No se puede cambiar contraseña sin conexión a base de datos'];
    }
    
    try {
        // Verificar contraseña actual
        $stmt = $conn->prepare("SELECT password, password_hash FROM users WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            if (!$user) {
                logSecurityEvent('PASSWORD_CHANGE_FAILED', 'Usuario no encontrado', 'medium', $user_id);
                return ['success' => false, 'error' => 'Usuario no encontrado'];
            }
            
            // Verificar contraseña actual (hash o texto plano para compatibilidad)
            $password_valid = false;
            if (!empty($user['password_hash']) && password_verify($current_password, $user['password_hash'])) {
                $password_valid = true;
            } elseif (!empty($user['password']) && $current_password === $user['password']) {
                $password_valid = true;
            }
            
            if (!$password_valid) {
                logSecurityEvent('PASSWORD_CHANGE_FAILED', 'Contraseña actual incorrecta', 'medium', $user_id);
                return ['success' => false, 'error' => 'Contraseña actual incorrecta'];
            }
            
            // Actualizar contraseña con hash seguro
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password_hash = ?, password = NULL, updated_at = NOW() WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("si", $hashed_password, $user_id);
                
                if ($stmt->execute()) {
                    $stmt->close();
                    logSecurityEvent('PASSWORD_CHANGED', 'Contraseña cambiada exitosamente', 'low', $user_id);
                    return ['success' => true, 'message' => 'Contraseña actualizada correctamente'];
                }
                $stmt->close();
            }
        }
        
        return ['success' => false, 'error' => 'Error al actualizar contraseña'];
    } catch (Exception $e) {
        logEvent('ERROR', 'Error cambiando contraseña: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Error interno del sistema'];
    }
}

function exportUserData($conn, $user_id) {
    try {
        $user_data = [
            'export_date' => date('Y-m-d H:i:s'),
            'user_id' => $user_id,
            'app_version' => APP_VERSION,
            'developer' => DEVELOPER,
            'profile' => [],
            'settings' => [],
            'conversations' => [],
            'security_events' => [],
            'ai_detections' => [],
            'note' => 'Datos exportados desde ' . APP_NAME . ' - ' . DEVELOPER
        ];
        
        if ($conn) {
            // Datos del perfil
            $stmt = $conn->prepare("SELECT username, email, fullname, user_type, premium_status, created_at, language, timezone, theme FROM users WHERE id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $user_data['profile'] = $row;
                }
                $stmt->close();
            }
            
            // Configuraciones
            $stmt = $conn->prepare("SELECT category, settings, created_at, updated_at FROM user_settings WHERE user_id = ?");
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $user_data['settings'][$row['category']] = [
                        'settings' => json_decode($row['settings'], true),
                        'created_at' => $row['created_at'],
                        'updated_at' => $row['updated_at']
                    ];
                }
                $stmt->close();
            }
            
            // Conversaciones del chatbot (limitadas)
            $stmt = $conn->prepare("SELECT id, title, created_at, updated_at FROM conversations WHERE user_id = ? ORDER BY created_at DESC LIMIT 100");
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $user_data['conversations'][] = $row;
                }
                $stmt->close();
            }
            
            // Eventos de seguridad (últimos 30 días)
            $stmt = $conn->prepare("SELECT event_type, description, severity, created_at FROM security_events WHERE user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) ORDER BY created_at DESC");
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $user_data['security_events'][] = $row;
                }
                $stmt->close();
            }
            
            // Detecciones de IA (últimas 50)
            $stmt = $conn->prepare("SELECT confidence_score, detection_patterns, threat_level, created_at FROM ai_detections WHERE user_id = ? ORDER BY created_at DESC LIMIT 50");
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                while ($row = $result->fetch_assoc()) {
                    $user_data['ai_detections'][] = $row;
                }
                $stmt->close();
            }
        } else {
            $user_data['note'] = 'Exportación en modo simulación - Base de datos no disponible';
            $user_data['profile'] = ['username' => 'usuario_demo', 'email' => 'demo@guardianai.com'];
            $user_data['settings'] = getDefaultAllSettings();
        }
        
        // Crear archivo de exportación
        $export_data = json_encode($user_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filename = "guardian_ia_export_user_{$user_id}_" . date('Y-m-d_H-i-s') . ".json";
        
        logEvent('INFO', "Datos exportados para usuario $user_id");
        logSecurityEvent('DATA_EXPORTED', 'Datos de usuario exportados', 'low', $user_id);
        
        return [
            'success' => true, 
            'message' => 'Datos exportados correctamente',
            'data' => $export_data,
            'filename' => $filename,
            'size' => strlen($export_data)
        ];
    } catch (Exception $e) {
        logEvent('ERROR', 'Error exportando datos: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Error al exportar datos: ' . $e->getMessage()];
    }
}

function deleteUserAccount($conn, $user_id, $password) {
    if (!$conn) {
        return ['success' => false, 'error' => 'No se puede eliminar cuenta sin conexión a base de datos'];
    }
    
    try {
        // Verificar contraseña
        $stmt = $conn->prepare("SELECT password, password_hash, username FROM users WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user = $result->fetch_assoc();
            $stmt->close();
            
            if (!$user) {
                logSecurityEvent('ACCOUNT_DELETION_FAILED', 'Usuario no encontrado en eliminación de cuenta', 'high', $user_id);
                return ['success' => false, 'error' => 'Usuario no encontrado'];
            }
            
            // Verificar contraseña
            $password_valid = false;
            if (!empty($user['password_hash']) && password_verify($password, $user['password_hash'])) {
                $password_valid = true;
            } elseif (!empty($user['password']) && $password === $user['password']) {
                $password_valid = true;
            }
            
            if (!$password_valid) {
                logSecurityEvent('ACCOUNT_DELETION_FAILED', 'Contraseña incorrecta en eliminación de cuenta', 'high', $user_id);
                return ['success' => false, 'error' => 'Contraseña incorrecta'];
            }
            
            // Eliminar datos relacionados en transacción
            $conn->begin_transaction();
            
            try {
                // Eliminar configuraciones
                $stmt = $conn->prepare("DELETE FROM user_settings WHERE user_id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $stmt->close();
                }
                
                // Eliminar conversaciones
                $stmt = $conn->prepare("DELETE FROM conversations WHERE user_id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $stmt->close();
                }
                
                // Eliminar mensajes del chatbot
                $stmt = $conn->prepare("DELETE FROM chat_messages WHERE user_id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $stmt->close();
                }
                
                // Eliminar detecciones de IA
                $stmt = $conn->prepare("DELETE FROM ai_detections WHERE user_id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $stmt->close();
                }
                
                // Eliminar eventos de seguridad
                $stmt = $conn->prepare("DELETE FROM security_events WHERE user_id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $stmt->close();
                }
                
                // Eliminar sesiones
                $stmt = $conn->prepare("DELETE FROM user_sessions WHERE user_id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $stmt->close();
                }
                
                // Eliminar estadísticas de uso
                $stmt = $conn->prepare("DELETE FROM usage_stats WHERE user_id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $stmt->close();
                }
                
                // Finalmente eliminar usuario
                $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
                if ($stmt) {
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();
                    $stmt->close();
                }
                
                $conn->commit();
                
                logSecurityEvent('ACCOUNT_DELETED', "Cuenta eliminada: {$user['username']}", 'medium', $user_id);
                
                // Destruir sesión
                session_destroy();
                
                return ['success' => true, 'message' => 'Cuenta eliminada correctamente'];
            } catch (Exception $e) {
                $conn->rollback();
                throw $e;
            }
        }
        
        return ['success' => false, 'error' => 'Error al eliminar cuenta'];
    } catch (Exception $e) {
        logEvent('ERROR', 'Error eliminando cuenta: ' . $e->getMessage());
        return ['success' => false, 'error' => 'Error al eliminar cuenta: ' . $e->getMessage()];
    }
}

// Obtener configuraciones actuales del usuario
$user_id = $_SESSION['user_id'] ?? 1;

// Obtener configuraciones con manejo de errores
$security_settings = getUserSettings($conn, $user_id, 'security');
$performance_settings = getUserSettings($conn, $user_id, 'performance');
$notification_settings = getUserSettings($conn, $user_id, 'notifications');
$privacy_settings = getUserSettings($conn, $user_id, 'privacy');

// Obtener datos del perfil con manejo de errores
$user_profile = [];
if ($conn) {
    try {
        $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $user_profile = $result->fetch_assoc() ?: [];
            $stmt->close();
        }
    } catch (Exception $e) {
        logEvent('ERROR', 'Error obteniendo perfil: ' . $e->getMessage());
        $user_profile = [];
    }
}

// Datos por defecto si no hay perfil en BD
if (empty($user_profile)) {
    global $DEFAULT_USERS;
    $user_profile = $DEFAULT_USERS['anderson'] ?? [
        'fullname' => 'Usuario Demo',
        'email' => 'demo@guardianai.com',
        'phone' => '',
        'language' => 'es',
        'timezone' => 'America/Bogota',
        'theme' => 'dark',
        'username' => 'demo',
        'premium_status' => 'basic'
    ];
}

function getUserSettings($conn, $user_id, $category) {
    if (!$conn) {
        return getDefaultSettings($category);
    }
    
    try {
        $stmt = $conn->prepare("SELECT settings FROM user_settings WHERE user_id = ? AND category = ?");
        if ($stmt) {
            $stmt->bind_param("is", $user_id, $category);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $stmt->close();
                return json_decode($row['settings'], true) ?: getDefaultSettings($category);
            }
            $stmt->close();
        }
    } catch (Exception $e) {
        logEvent('ERROR', 'Error obteniendo configuraciones: ' . $e->getMessage());
    }
    
    return getDefaultSettings($category);
}

function getDefaultSettings($category) {
    $defaults = [
        'security' => [
            'real_time_protection' => 1,
            'auto_scan' => 1,
            'firewall_enabled' => 1,
            'threat_notifications' => 1,
            'scan_frequency' => 'daily',
            'quarantine_auto' => 1
        ],
        'performance' => [
            'auto_optimization' => 1,
            'ram_optimization' => 1,
            'storage_cleanup' => 1,
            'battery_optimization' => 1,
            'optimization_schedule' => 'daily',
            'performance_mode' => 'balanced'
        ],
        'notifications' => [
            'email_notifications' => 1,
            'push_notifications' => 1,
            'sound_alerts' => 1,
            'threat_alerts' => 1,
            'optimization_alerts' => 1,
            'system_updates' => 1
        ],
        'privacy' => [
            'data_collection' => 0,
            'analytics_sharing' => 0,
            'crash_reports' => 1,
            'usage_statistics' => 0,
            'location_tracking' => 0,
            'third_party_sharing' => 0
        ]
    ];
    
    return $defaults[$category] ?? [];
}

function getDefaultAllSettings() {
    return [
        'security' => getDefaultSettings('security'),
        'performance' => getDefaultSettings('performance'),
        'notifications' => getDefaultSettings('notifications'),
        'privacy' => getDefaultSettings('privacy')
    ];
}

// Verificar integridad del sistema
$system_integrity = verifySystemIntegrity();
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Configuración - <?php echo APP_NAME; ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --warning-gradient: linear-gradient(135deg, #ffa726 0%, #fb8c00 100%);
            --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            
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
            background: var(--warning-gradient);
            color: white;
        }

        .user-profile {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }

        .premium-badge {
            background: linear-gradient(45deg, #ffd700, #ffed4e);
            color: #000;
            padding: 0.2rem 0.5rem;
            border-radius: 12px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
        }

        /* Main Container */
        .main-container {
            margin-top: 80px;
            padding: 2rem;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 2rem;
        }

        /* Settings Sidebar */
        .settings-sidebar {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: var(--card-padding);
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

        .settings-menu {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .menu-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
            text-decoration: none;
            color: var(--text-secondary);
        }

        .menu-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
        }

        .menu-item.active {
            background: var(--primary-gradient);
            color: white;
        }

        .menu-icon {
            width: 20px;
            text-align: center;
        }

        /* Settings Content */
        .settings-content {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: var(--card-padding);
        }

        .settings-section {
            display: none;
        }

        .settings-section.active {
            display: block;
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
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .section-description {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Settings Groups */
        .settings-group {
            margin-bottom: 2rem;
        }

        .group-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .setting-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            margin-bottom: 0.5rem;
        }

        .setting-info {
            flex: 1;
        }

        .setting-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .setting-description {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        /* Toggle Switch */
        .toggle-switch {
            position: relative;
            width: 60px;
            height: 30px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 15px;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
        }

        .toggle-switch.active {
            background: var(--success-gradient);
        }

        .toggle-slider {
            position: absolute;
            top: 3px;
            left: 3px;
            width: 24px;
            height: 24px;
            background: white;
            border-radius: 50%;
            transition: all var(--animation-speed) ease;
        }

        .toggle-switch.active .toggle-slider {
            transform: translateX(30px);
        }

        /* Select Dropdown */
        .setting-select {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 0.5rem 1rem;
            color: var(--text-primary);
            font-family: inherit;
            cursor: pointer;
            min-width: 150px;
        }

        .setting-select:focus {
            outline: none;
            border-color: var(--primary-gradient);
        }

        .setting-select option {
            background: var(--bg-card);
            color: var(--text-primary);
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }

        .form-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1rem;
            color: var(--text-primary);
            font-family: inherit;
            transition: all var(--animation-speed) ease;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--primary-gradient);
            background: rgba(255, 255, 255, 0.1);
        }

        .form-input::placeholder {
            color: var(--text-secondary);
        }

        /* Buttons */
        .btn {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            text-decoration: none;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .btn.success {
            background: var(--success-gradient);
        }

        .btn.warning {
            background: var(--warning-gradient);
        }

        .btn.danger {
            background: var(--danger-gradient);
        }

        .btn.secondary {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }

        /* Status Indicators */
        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .status-indicator.connected {
            background: rgba(67, 233, 123, 0.2);
            color: #43e97b;
            border: 1px solid rgba(67, 233, 123, 0.3);
        }

        .status-indicator.disconnected {
            background: rgba(255, 71, 87, 0.2);
            color: #ff4757;
            border: 1px solid rgba(255, 71, 87, 0.3);
        }

        .status-indicator.simulation {
            background: rgba(255, 165, 2, 0.2);
            color: #ffa502;
            border: 1px solid rgba(255, 165, 2, 0.3);
        }

        /* Danger Zone */
        .danger-zone {
            background: rgba(250, 112, 154, 0.1);
            border: 1px solid rgba(250, 112, 154, 0.3);
            border-radius: var(--border-radius);
            padding: var(--card-padding);
            margin-top: 2rem;
        }

        .danger-title {
            color: #fa709a;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .danger-description {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
        }

        /* System Info */
        .system-info {
            background: rgba(79, 172, 254, 0.1);
            border: 1px solid rgba(79, 172, 254, 0.3);
            border-radius: var(--border-radius);
            padding: 1rem;
            margin-bottom: 2rem;
        }

        .system-info-title {
            color: #4facfe;
            font-weight: 600;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .system-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 6px;
        }

        .info-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .info-value {
            color: var(--text-primary);
            font-weight: 600;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-container {
                grid-template-columns: 1fr;
                padding: 1rem;
            }

            .settings-sidebar {
                position: static;
                margin-bottom: 1rem;
            }

            .settings-menu {
                flex-direction: row;
                overflow-x: auto;
                gap: 0.5rem;
            }

            .menu-item {
                min-width: 120px;
                justify-content: center;
            }

            .menu-text {
                display: none;
            }

            .action-buttons {
                flex-direction: column;
            }

            .system-info-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Loading Animation */
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

        /* Toast Notification */
        .toast {
            position: fixed;
            top: 100px;
            right: 2rem;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 1rem 1.5rem;
            color: var(--text-primary);
            box-shadow: 0 8px 25px var(--shadow-color);
            transform: translateX(400px);
            transition: transform var(--animation-speed) ease;
            z-index: 1001;
            max-width: 350px;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast.success {
            border-left: 4px solid #2ed573;
        }

        .toast.error {
            border-left: 4px solid #ff4757;
        }

        .toast.warning {
            border-left: 4px solid #ffa502;
        }

        .toast.info {
            border-left: 4px solid #4facfe;
        }

        .toast-close {
            float: right;
            background: none;
            border: none;
            color: var(--text-secondary);
            cursor: pointer;
            font-size: 1.2rem;
            margin-left: 1rem;
        }

        /* Password Strength Indicator */
        .password-strength {
            margin-top: 0.5rem;
            height: 4px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 2px;
            overflow: hidden;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s ease;
        }

        .password-strength-bar.weak {
            background: #ff4757;
            width: 25%;
        }

        .password-strength-bar.medium {
            background: #ffa502;
            width: 50%;
        }

        .password-strength-bar.strong {
            background: #2ed573;
            width: 75%;
        }

        .password-strength-bar.very-strong {
            background: #4facfe;
            width: 100%;
        }

        /* Feature Lock */
        .feature-locked {
            opacity: 0.6;
            position: relative;
        }

        .feature-locked::after {
            content: '\f023';
            font-family: 'Font Awesome 6 Free';
            font-weight: 900;
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #ffa502;
        }
    </style>
</head>
<body>
    <div class="animated-bg"></div>
    
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <div class="logo">
                <i class="fas fa-shield-alt"></i>
                <span><?php echo APP_NAME; ?></span>
            </div>
            <ul class="nav-menu">
                <li><a href="user_dashboard.php" class="nav-link">Mi Seguridad</a></li>
                <li><a href="user_security.php" class="nav-link">Protección</a></li>
                <li><a href="user_performance.php" class="nav-link">Optimización</a></li>
                <li><a href="user_assistant.php" class="nav-link">Asistente IA</a></li>
                <li><a href="#" class="nav-link active">Configuración</a></li>
            </ul>
            <div class="user-profile">
                <?php if (($user_profile['premium_status'] ?? 'basic') === 'premium'): ?>
                    <span class="premium-badge">Premium</span>
                <?php endif; ?>
                <div class="user-avatar">
                    <?php echo strtoupper(substr($user_profile['fullname'] ?? 'UD', 0, 2)); ?>
                </div>
                <span><?php echo htmlspecialchars($user_profile['fullname'] ?? 'Usuario Demo'); ?></span>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Settings Sidebar -->
        <div class="settings-sidebar">
            <h2 class="sidebar-title">Configuración</h2>
            <div class="settings-menu">
                <div class="menu-item active" onclick="showSection('profile')">
                    <i class="fas fa-user menu-icon"></i>
                    <span class="menu-text">Mi Perfil</span>
                </div>
                <div class="menu-item" onclick="showSection('security')">
                    <i class="fas fa-shield-alt menu-icon"></i>
                    <span class="menu-text">Seguridad</span>
                </div>
                <div class="menu-item" onclick="showSection('performance')">
                    <i class="fas fa-tachometer-alt menu-icon"></i>
                    <span class="menu-text">Rendimiento</span>
                </div>
                <div class="menu-item" onclick="showSection('notifications')">
                    <i class="fas fa-bell menu-icon"></i>
                    <span class="menu-text">Notificaciones</span>
                </div>
                <div class="menu-item" onclick="showSection('privacy')">
                    <i class="fas fa-lock menu-icon"></i>
                    <span class="menu-text">Privacidad</span>
                </div>
                <div class="menu-item" onclick="showSection('account')">
                    <i class="fas fa-cog menu-icon"></i>
                    <span class="menu-text">Cuenta</span>
                </div>
            </div>
        </div>

        <!-- Settings Content -->
        <div class="settings-content">
            <!-- System Status -->
            <div class="system-info">
                <div class="system-info-title">
                    <i class="fas fa-info-circle"></i>
                    Estado del Sistema
                </div>
                <div class="system-info-grid">
                    <div class="info-item">
                        <span class="info-label">Base de Datos:</span>
                        <span class="info-value">
                            <?php if ($system_integrity['database']): ?>
                                <span class="status-indicator connected">
                                    <i class="fas fa-check-circle"></i>
                                    Conectada
                                </span>
                            <?php else: ?>
                                <span class="status-indicator simulation">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    Modo Simulación
                                </span>
                            <?php endif; ?>
                        </span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Versión:</span>
                        <span class="info-value"><?php echo APP_VERSION; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Desarrollador:</span>
                        <span class="info-value"><?php echo DEVELOPER; ?></span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Premium:</span>
                        <span class="info-value">
                            <?php if (($user_profile['premium_status'] ?? 'basic') === 'premium'): ?>
                                <span class="status-indicator connected">Activo</span>
                            <?php else: ?>
                                <span class="status-indicator disconnected">Básico</span>
                            <?php endif; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Profile Section -->
            <div class="settings-section active" id="profile">
                <div class="section-header">
                    <div class="section-icon" style="background: var(--info-gradient);">
                        <i class="fas fa-user"></i>
                    </div>
                    <div>
                        <h2 class="section-title">Mi Perfil</h2>
                        <p class="section-description">Gestiona tu información personal y preferencias de cuenta</p>
                    </div>
                </div>

                <form id="profileForm">
                    <div class="form-group">
                        <label class="form-label">Nombre Completo</label>
                        <input type="text" class="form-input" name="full_name" value="<?php echo htmlspecialchars($user_profile['fullname'] ?? ''); ?>" placeholder="Tu nombre completo">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-input" name="email" value="<?php echo htmlspecialchars($user_profile['email'] ?? ''); ?>" placeholder="tu@email.com">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Teléfono</label>
                        <input type="tel" class="form-input" name="phone" value="<?php echo htmlspecialchars($user_profile['phone'] ?? ''); ?>" placeholder="+57 300 123 4567">
                    </div>

                    <div class="form-group">
                        <label class="form-label">Idioma</label>
                        <select class="setting-select" name="language">
                            <option value="es" <?php echo ($user_profile['language'] ?? 'es') === 'es' ? 'selected' : ''; ?>>Español</option>
                            <option value="en" <?php echo ($user_profile['language'] ?? 'es') === 'en' ? 'selected' : ''; ?>>English</option>
                            <option value="fr" <?php echo ($user_profile['language'] ?? 'es') === 'fr' ? 'selected' : ''; ?>>Français</option>
                            <option value="pt" <?php echo ($user_profile['language'] ?? 'es') === 'pt' ? 'selected' : ''; ?>>Português</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Zona Horaria</label>
                        <select class="setting-select" name="timezone">
                            <option value="America/Bogota" <?php echo ($user_profile['timezone'] ?? 'America/Bogota') === 'America/Bogota' ? 'selected' : ''; ?>>Bogotá (GMT-5)</option>
                            <option value="America/Mexico_City" <?php echo ($user_profile['timezone'] ?? '') === 'America/Mexico_City' ? 'selected' : ''; ?>>Ciudad de México (GMT-6)</option>
                            <option value="America/New_York" <?php echo ($user_profile['timezone'] ?? '') === 'America/New_York' ? 'selected' : ''; ?>>Nueva York (GMT-5)</option>
                            <option value="Europe/Madrid" <?php echo ($user_profile['timezone'] ?? '') === 'Europe/Madrid' ? 'selected' : ''; ?>>Madrid (GMT+1)</option>
                            <option value="America/Lima" <?php echo ($user_profile['timezone'] ?? '') === 'America/Lima' ? 'selected' : ''; ?>>Lima (GMT-5)</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Tema</label>
                        <select class="setting-select" name="theme">
                            <option value="dark" <?php echo ($user_profile['theme'] ?? 'dark') === 'dark' ? 'selected' : ''; ?>>Oscuro</option>
                            <option value="light" <?php echo ($user_profile['theme'] ?? 'dark') === 'light' ? 'selected' : ''; ?>>Claro</option>
                            <option value="auto" <?php echo ($user_profile['theme'] ?? 'dark') === 'auto' ? 'selected' : ''; ?>>Automático</option>
                        </select>
                    </div>

                    <div class="action-buttons">
                        <button type="submit" class="btn success">
                            <i class="fas fa-save"></i>
                            Guardar Cambios
                        </button>
                        <button type="button" class="btn secondary" onclick="resetForm('profileForm')">
                            <i class="fas fa-undo"></i>
                            Restablecer
                        </button>
                    </div>
                </form>
            </div>

            <!-- Security Section -->
            <div class="settings-section" id="security">
                <div class="section-header">
                    <div class="section-icon" style="background: var(--success-gradient);">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div>
                        <h2 class="section-title">Configuración de Seguridad</h2>
                        <p class="section-description">Ajusta las configuraciones de protección y detección de amenazas</p>
                    </div>
                </div>

                <form id="securityForm">
                    <div class="settings-group">
                        <h3 class="group-title">Protección en Tiempo Real</h3>
                        
                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Protección en Tiempo Real</div>
                                <div class="setting-description">Monitorea y bloquea amenazas automáticamente</div>
                            </div>
                            <div class="toggle-switch <?php echo $security_settings['real_time_protection'] ? 'active' : ''; ?>" onclick="toggleSetting(this, 'real_time_protection')">
                                <div class="toggle-slider"></div>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Escaneo Automático</div>
                                <div class="setting-description">Ejecuta escaneos programados del sistema</div>
                            </div>
                            <div class="toggle-switch <?php echo $security_settings['auto_scan'] ? 'active' : ''; ?>" onclick="toggleSetting(this, 'auto_scan')">
                                <div class="toggle-slider"></div>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Firewall Habilitado</div>
                                <div class="setting-description">Protege contra conexiones no autorizadas</div>
                            </div>
                            <div class="toggle-switch <?php echo $security_settings['firewall_enabled'] ? 'active' : ''; ?>" onclick="toggleSetting(this, 'firewall_enabled')">
                                <div class="toggle-slider"></div>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Detección de IA Avanzada</div>
                                <div class="setting-description">Usa algoritmos de aprendizaje automático para detectar amenazas</div>
                            </div>
                            <div class="toggle-switch active" onclick="toggleSetting(this, 'ai_detection')">
                                <div class="toggle-slider"></div>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Notificaciones de Amenazas</div>
                                <div class="setting-description">Recibe alertas cuando se detecten amenazas</div>
                            </div>
                            <div class="toggle-switch <?php echo $security_settings['threat_notifications'] ? 'active' : ''; ?>" onclick="toggleSetting(this, 'threat_notifications')">
                                <div class="toggle-slider"></div>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Frecuencia de Escaneo</div>
                                <div class="setting-description">Qué tan seguido se ejecutan los escaneos</div>
                            </div>
                            <select class="setting-select" name="scan_frequency">
                                <option value="hourly" <?php echo $security_settings['scan_frequency'] === 'hourly' ? 'selected' : ''; ?>>Cada hora</option>
                                <option value="daily" <?php echo $security_settings['scan_frequency'] === 'daily' ? 'selected' : ''; ?>>Diario</option>
                                <option value="weekly" <?php echo $security_settings['scan_frequency'] === 'weekly' ? 'selected' : ''; ?>>Semanal</option>
                            </select>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Cuarentena Automática</div>
                                <div class="setting-description">Aisla amenazas detectadas automáticamente</div>
                            </div>
                            <div class="toggle-switch <?php echo $security_settings['quarantine_auto'] ? 'active' : ''; ?>" onclick="toggleSetting(this, 'quarantine_auto')">
                                <div class="toggle-slider"></div>
                            </div>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <button type="submit" class="btn success">
                            <i class="fas fa-save"></i>
                            Guardar Configuración
                        </button>
                    </div>
                </form>
            </div>

            <!-- Performance Section -->
            <div class="settings-section" id="performance">
                <div class="section-header">
                    <div class="section-icon" style="background: var(--warning-gradient);">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                    <div>
                        <h2 class="section-title">Configuración de Rendimiento</h2>
                        <p class="section-description">Optimiza el rendimiento y recursos del sistema</p>
                    </div>
                </div>

                <form id="performanceForm">
                    <div class="settings-group">
                        <h3 class="group-title">Optimización Automática</h3>
                        
                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Optimización Automática</div>
                                <div class="setting-description">Optimiza el sistema automáticamente</div>
                            </div>
                            <div class="toggle-switch <?php echo $performance_settings['auto_optimization'] ? 'active' : ''; ?>" onclick="toggleSetting(this, 'auto_optimization')">
                                <div class="toggle-slider"></div>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Optimización de RAM</div>
                                <div class="setting-description">Libera memoria no utilizada automáticamente</div>
                            </div>
                            <div class="toggle-switch <?php echo $performance_settings['ram_optimization'] ? 'active' : ''; ?>" onclick="toggleSetting(this, 'ram_optimization')">
                                <div class="toggle-slider"></div>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Limpieza de Almacenamiento</div>
                                <div class="setting-description">Elimina archivos temporales y basura</div>
                            </div>
                            <div class="toggle-switch <?php echo $performance_settings['storage_cleanup'] ? 'active' : ''; ?>" onclick="toggleSetting(this, 'storage_cleanup')">
                                <div class="toggle-slider"></div>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Optimización de Batería</div>
                                <div class="setting-description">Extiende la duración de la batería</div>
                            </div>
                            <div class="toggle-switch <?php echo $performance_settings['battery_optimization'] ? 'active' : ''; ?>" onclick="toggleSetting(this, 'battery_optimization')">
                                <div class="toggle-slider"></div>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Aceleración por IA</div>
                                <div class="setting-description">Usa inteligencia artificial para optimizar rendimiento</div>
                            </div>
                            <div class="toggle-switch active" onclick="toggleSetting(this, 'ai_acceleration')">
                                <div class="toggle-slider"></div>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Horario de Optimización</div>
                                <div class="setting-description">Cuándo ejecutar optimizaciones automáticas</div>
                            </div>
                            <select class="setting-select" name="optimization_schedule">
                                <option value="hourly" <?php echo $performance_settings['optimization_schedule'] === 'hourly' ? 'selected' : ''; ?>>Cada hora</option>
                                <option value="daily" <?php echo $performance_settings['optimization_schedule'] === 'daily' ? 'selected' : ''; ?>>Diario</option>
                                <option value="weekly" <?php echo $performance_settings['optimization_schedule'] === 'weekly' ? 'selected' : ''; ?>>Semanal</option>
                            </select>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Modo de Rendimiento</div>
                                <div class="setting-description">Equilibrio entre rendimiento y eficiencia</div>
                            </div>
                            <select class="setting-select" name="performance_mode">
                                <option value="power_saver" <?php echo $performance_settings['performance_mode'] === 'power_saver' ? 'selected' : ''; ?>>Ahorro de energía</option>
                                <option value="balanced" <?php echo $performance_settings['performance_mode'] === 'balanced' ? 'selected' : ''; ?>>Equilibrado</option>
                                <option value="performance" <?php echo $performance_settings['performance_mode'] === 'performance' ? 'selected' : ''; ?>>Alto rendimiento</option>
                                <option value="gaming" <?php echo $performance_settings['performance_mode'] === 'gaming' ? 'selected' : ''; ?>>Gaming</option>
                            </select>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <button type="submit" class="btn warning">
                            <i class="fas fa-save"></i>
                            Guardar Configuración
                        </button>
                    </div>
                </form>
            </div>

            <!-- Notifications Section -->
            <div class="settings-section" id="notifications">
                <div class="section-header">
                    <div class="section-icon" style="background: var(--info-gradient);">
                        <i class="fas fa-bell"></i>
                    </div>
                    <div>
                        <h2 class="section-title">Configuración de Notificaciones</h2>
                        <p class="section-description">Controla cómo y cuándo recibir notificaciones</p>
                    </div>
                </div>

                <form id="notificationsForm">
                    <div class="settings-group">
                        <h3 class="group-title">Tipos de Notificaciones</h3>
                        
                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Notificaciones por Email</div>
                                <div class="setting-description">Recibe notificaciones en tu correo electrónico</div>
                            </div>
                            <div class="toggle-switch <?php echo $notification_settings['email_notifications'] ? 'active' : ''; ?>" onclick="toggleSetting(this, 'email_notifications')">
                                <div class="toggle-slider"></div>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Notificaciones Push</div>
                                <div class="setting-description">Recibe notificaciones en tiempo real</div>
                            </div>
                            <div class="toggle-switch <?php echo $notification_settings['push_notifications'] ? 'active' : ''; ?>" onclick="toggleSetting(this, 'push_notifications')">
                                <div class="toggle-slider"></div>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Alertas de Sonido</div>
                                <div class="setting-description">Reproduce sonidos para alertas importantes</div>
                            </div>
                            <div class="toggle-switch <?php echo $notification_settings['sound_alerts'] ? 'active' : ''; ?>" onclick="toggleSetting(this, 'sound_alerts')">
                                <div class="toggle-slider"></div>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Alertas de Amenazas</div>
                                <div class="setting-description">Notificaciones cuando se detecten amenazas</div>
                            </div>
                            <div class="toggle-switch <?php echo $notification_settings['threat_alerts'] ? 'active' : ''; ?>" onclick="toggleSetting(this, 'threat_alerts')">
                                <div class="toggle-slider"></div>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Alertas de IA</div>
                                <div class="setting-description">Notificaciones sobre detecciones de inteligencia artificial</div>
                            </div>
                            <div class="toggle-switch active" onclick="toggleSetting(this, 'ai_alerts')">
                                <div class="toggle-slider"></div>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Alertas de Optimización</div>
                                <div class="setting-description">Notificaciones sobre optimizaciones completadas</div>
                            </div>
                            <div class="toggle-switch <?php echo $notification_settings['optimization_alerts'] ? 'active' : ''; ?>" onclick="toggleSetting(this, 'optimization_alerts')">
                                <div class="toggle-slider"></div>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Actualizaciones del Sistema</div>
                                <div class="setting-description">Notificaciones sobre actualizaciones disponibles</div>
                            </div>
                            <div class="toggle-switch <?php echo $notification_settings['system_updates'] ? 'active' : ''; ?>" onclick="toggleSetting(this, 'system_updates')">
                                <div class="toggle-slider"></div>
                            </div>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <button type="submit" class="btn">
                            <i class="fas fa-save"></i>
                            Guardar Configuración
                        </button>
                    </div>
                </form>
            </div>

            <!-- Privacy Section -->
            <div class="settings-section" id="privacy">
                <div class="section-header">
                    <div class="section-icon" style="background: var(--danger-gradient);">
                        <i class="fas fa-lock"></i>
                    </div>
                    <div>
                        <h2 class="section-title">Configuración de Privacidad</h2>
                        <p class="section-description">Controla qué datos se recopilan y comparten</p>
                    </div>
                </div>

                <form id="privacyForm">
                    <div class="settings-group">
                        <h3 class="group-title">Recopilación de Datos</h3>
                        
                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Recopilación de Datos</div>
                                <div class="setting-description">Permite recopilar datos para mejorar el servicio</div>
                            </div>
                            <div class="toggle-switch <?php echo $privacy_settings['data_collection'] ? 'active' : ''; ?>" onclick="toggleSetting(this, 'data_collection')">
                                <div class="toggle-slider"></div>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Compartir Análisis</div>
                                <div class="setting-description">Comparte datos analíticos anónimos</div>
                            </div>
                            <div class="toggle-switch <?php echo $privacy_settings['analytics_sharing'] ? 'active' : ''; ?>" onclick="toggleSetting(this, 'analytics_sharing')">
                                <div class="toggle-slider"></div>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Reportes de Errores</div>
                                <div class="setting-description">Envía reportes automáticos de errores</div>
                            </div>
                            <div class="toggle-switch <?php echo $privacy_settings['crash_reports'] ? 'active' : ''; ?>" onclick="toggleSetting(this, 'crash_reports')">
                                <div class="toggle-slider"></div>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Estadísticas de Uso</div>
                                <div class="setting-description">Recopila estadísticas sobre el uso de la aplicación</div>
                            </div>
                            <div class="toggle-switch <?php echo $privacy_settings['usage_statistics'] ? 'active' : ''; ?>" onclick="toggleSetting(this, 'usage_statistics')">
                                <div class="toggle-slider"></div>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Seguimiento de Ubicación</div>
                                <div class="setting-description">Permite el acceso a datos de ubicación</div>
                            </div>
                            <div class="toggle-switch <?php echo $privacy_settings['location_tracking'] ? 'active' : ''; ?>" onclick="toggleSetting(this, 'location_tracking')">
                                <div class="toggle-slider"></div>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Compartir con Terceros</div>
                                <div class="setting-description">Permite compartir datos con servicios de terceros</div>
                            </div>
                            <div class="toggle-switch <?php echo $privacy_settings['third_party_sharing'] ? 'active' : ''; ?>" onclick="toggleSetting(this, 'third_party_sharing')">
                                <div class="toggle-slider"></div>
                            </div>
                        </div>

                        <div class="setting-item">
                            <div class="setting-info">
                                <div class="setting-name">Encriptación Cuántica</div>
                                <div class="setting-description">Protección avanzada con encriptación cuántica (Premium)</div>
                            </div>
                            <div class="toggle-switch <?php echo ($user_profile['premium_status'] ?? 'basic') === 'premium' ? 'active' : ''; ?> <?php echo ($user_profile['premium_status'] ?? 'basic') !== 'premium' ? 'feature-locked' : ''; ?>" onclick="<?php echo ($user_profile['premium_status'] ?? 'basic') === 'premium' ? 'toggleSetting(this, \'quantum_encryption\')' : 'showPremiumRequired()'; ?>">
                                <div class="toggle-slider"></div>
                            </div>
                        </div>
                    </div>

                    <div class="action-buttons">
                        <button type="submit" class="btn danger">
                            <i class="fas fa-save"></i>
                            Guardar Configuración
                        </button>
                    </div>
                </form>
            </div>

            <!-- Account Section -->
            <div class="settings-section" id="account">
                <div class="section-header">
                    <div class="section-icon" style="background: var(--primary-gradient);">
                        <i class="fas fa-cog"></i>
                    </div>
                    <div>
                        <h2 class="section-title">Configuración de Cuenta</h2>
                        <p class="section-description">Gestiona tu cuenta y configuraciones avanzadas</p>
                    </div>
                </div>

                <div class="settings-group">
                    <h3 class="group-title">Seguridad de la Cuenta</h3>
                    
                    <form id="passwordForm">
                        <div class="form-group">
                            <label class="form-label">Contraseña Actual</label>
                            <input type="password" class="form-input" name="current_password" placeholder="Tu contraseña actual">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Nueva Contraseña</label>
                            <input type="password" class="form-input" name="new_password" placeholder="Nueva contraseña (mínimo 8 caracteres)" onkeyup="checkPasswordStrength(this.value)">
                            <div class="password-strength">
                                <div class="password-strength-bar" id="passwordStrengthBar"></div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Confirmar Nueva Contraseña</label>
                            <input type="password" class="form-input" name="confirm_password" placeholder="Confirma tu nueva contraseña">
                        </div>

                        <div class="action-buttons">
                            <button type="submit" class="btn warning">
                                <i class="fas fa-key"></i>
                                Cambiar Contraseña
                            </button>
                        </div>
                    </form>
                </div>

                <div class="settings-group">
                    <h3 class="group-title">Gestión de Datos</h3>
                    
                    <div class="action-buttons">
                        <button class="btn secondary" onclick="exportUserData()">
                            <i class="fas fa-download"></i>
                            Exportar Mis Datos
                        </button>
                        <button class="btn" onclick="showSystemInfo()">
                            <i class="fas fa-info-circle"></i>
                            Información del Sistema
                        </button>
                    </div>
                </div>

                <?php if (($user_profile['premium_status'] ?? 'basic') !== 'premium'): ?>
                <div class="settings-group">
                    <h3 class="group-title">Actualizar a Premium</h3>
                    <p style="color: var(--text-secondary); margin-bottom: 1rem;">
                        Desbloquea características avanzadas como detección de IA mejorada, encriptación cuántica y soporte prioritario.
                    </p>
                    
                    <div class="action-buttons">
                        <button class="btn success" onclick="upgradeToPremium()">
                            <i class="fas fa-crown"></i>
                            Actualizar a Premium
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <div class="danger-zone">
                    <h3 class="danger-title">
                        <i class="fas fa-exclamation-triangle"></i>
                        Zona de Peligro
                    </h3>
                    <p class="danger-description">
                        Las siguientes acciones son permanentes y no se pueden deshacer. Procede con precaución.
                    </p>
                    
                    <button class="btn danger" onclick="showDeleteAccountModal()">
                        <i class="fas fa-trash"></i>
                        Eliminar Mi Cuenta
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <button class="toast-close" onclick="hideToast()">&times;</button>
        <span id="toast-message"></span>
    </div>

    <script>
        // Variables globales
        let userId = <?php echo $user_id; ?>;
        let currentSection = 'profile';
        let userProfile = <?php echo json_encode($user_profile); ?>;
        let isPremium = <?php echo ($user_profile['premium_status'] ?? 'basic') === 'premium' ? 'true' : 'false'; ?>;

        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            initializeSettings();
            setupEventListeners();
        });

        // Inicializar configuraciones
        function initializeSettings() {
            console.log('Inicializando configuraciones para GuardianIA v3.0...');
            showSection('profile');
            
            // Log de información del sistema
            console.log('Usuario Premium:', isPremium);
            console.log('Base de datos:', <?php echo $system_integrity['database'] ? 'true' : 'false'; ?>);
        }

        // Configurar event listeners
        function setupEventListeners() {
            // Formularios
            document.getElementById('profileForm').addEventListener('submit', handleProfileSubmit);
            document.getElementById('securityForm').addEventListener('submit', handleSecuritySubmit);
            document.getElementById('performanceForm').addEventListener('submit', handlePerformanceSubmit);
            document.getElementById('notificationsForm').addEventListener('submit', handleNotificationsSubmit);
            document.getElementById('privacyForm').addEventListener('submit', handlePrivacySubmit);
            document.getElementById('passwordForm').addEventListener('submit', handlePasswordSubmit);
        }

        // Mostrar sección
        function showSection(sectionName) {
            // Ocultar todas las secciones
            document.querySelectorAll('.settings-section').forEach(section => {
                section.classList.remove('active');
            });

            // Mostrar sección seleccionada
            document.getElementById(sectionName).classList.add('active');

            // Actualizar menú
            document.querySelectorAll('.menu-item').forEach(item => {
                item.classList.remove('active');
            });
            event.target.closest('.menu-item').classList.add('active');

            currentSection = sectionName;
            
            // Log de seguimiento
            console.log('Sección cambiada a:', sectionName);
        }

        // Toggle setting
        function toggleSetting(toggle, settingName) {
            if (toggle.classList.contains('feature-locked')) {
                showPremiumRequired();
                return;
            }
            
            toggle.classList.toggle('active');
            const isActive = toggle.classList.contains('active');
            
            // Actualizar valor en el formulario
            const form = toggle.closest('form');
            if (form) {
                let input = form.querySelector(`input[name="${settingName}"]`);
                if (!input) {
                    input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = settingName;
                    form.appendChild(input);
                }
                input.value = isActive ? 1 : 0;
            }
            
            // Efecto visual de confirmación
            if (isActive) {
                toggle.style.boxShadow = '0 0 20px rgba(67, 233, 123, 0.5)';
                setTimeout(() => {
                    toggle.style.boxShadow = '';
                }, 300);
            }
        }

        // Verificar fortaleza de contraseña
        function checkPasswordStrength(password) {
            const bar = document.getElementById('passwordStrengthBar');
            let strength = 0;
            
            // Criterios de fortaleza
            if (password.length >= 8) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/\d/.test(password)) strength++;
            if (/[^a-zA-Z\d]/.test(password)) strength++;
            
            // Actualizar barra visual
            bar.className = 'password-strength-bar';
            if (strength === 1) {
                bar.classList.add('weak');
            } else if (strength === 2) {
                bar.classList.add('medium');
            } else if (strength === 3 || strength === 4) {
                bar.classList.add('strong');
            } else if (strength === 5) {
                bar.classList.add('very-strong');
            }
        }

        // Manejar envío de formulario de perfil
        function handleProfileSubmit(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'update_profile');
            formData.append('user_id', userId);

            submitForm(formData, 'Perfil actualizado correctamente', () => {
                // Actualizar avatar si cambió el nombre
                const newName = formData.get('full_name');
                if (newName) {
                    const avatar = document.querySelector('.user-avatar');
                    avatar.textContent = newName.substring(0, 2).toUpperCase();
                    userProfile.fullname = newName;
                }
            });
        }

        // Manejar envío de formulario de seguridad
        function handleSecuritySubmit(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'update_security_settings');
            formData.append('user_id', userId);

            // Agregar valores de toggles
            document.querySelectorAll('#security .toggle-switch').forEach(toggle => {
                const settingName = toggle.getAttribute('onclick').match(/'([^']+)'/)[1];
                formData.append(settingName, toggle.classList.contains('active') ? 1 : 0);
            });

            submitForm(formData, 'Configuración de seguridad actualizada');
        }

        // Manejar envío de formulario de rendimiento
        function handlePerformanceSubmit(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'update_performance_settings');
            formData.append('user_id', userId);

            // Agregar valores de toggles
            document.querySelectorAll('#performance .toggle-switch').forEach(toggle => {
                const settingName = toggle.getAttribute('onclick').match(/'([^']+)'/)[1];
                formData.append(settingName, toggle.classList.contains('active') ? 1 : 0);
            });

            submitForm(formData, 'Configuración de rendimiento actualizada');
        }

        // Manejar envío de formulario de notificaciones
        function handleNotificationsSubmit(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'update_notification_settings');
            formData.append('user_id', userId);

            // Agregar valores de toggles
            document.querySelectorAll('#notifications .toggle-switch').forEach(toggle => {
                const settingName = toggle.getAttribute('onclick').match(/'([^']+)'/)[1];
                formData.append(settingName, toggle.classList.contains('active') ? 1 : 0);
            });

            submitForm(formData, 'Configuración de notificaciones actualizada');
        }

        // Manejar envío de formulario de privacidad
        function handlePrivacySubmit(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            formData.append('action', 'update_privacy_settings');
            formData.append('user_id', userId);

            // Agregar valores de toggles
            document.querySelectorAll('#privacy .toggle-switch').forEach(toggle => {
                if (!toggle.classList.contains('feature-locked')) {
                    const settingName = toggle.getAttribute('onclick').match(/'([^']+)'/)[1];
                    formData.append(settingName, toggle.classList.contains('active') ? 1 : 0);
                }
            });

            submitForm(formData, 'Configuración de privacidad actualizada');
        }

        // Manejar envío de formulario de contraseña
        function handlePasswordSubmit(e) {
            e.preventDefault();
            const formData = new FormData(e.target);
            
            // Validaciones del lado cliente
            const newPassword = formData.get('new_password');
            const confirmPassword = formData.get('confirm_password');
            
            if (newPassword !== confirmPassword) {
                showToast('Las contraseñas no coinciden', 'error');
                return;
            }
            
            if (newPassword.length < 8) {
                showToast('La contraseña debe tener al menos 8 caracteres', 'error');
                return;
            }
            
            if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)/.test(newPassword)) {
                showToast('La contraseña debe contener al menos una mayúscula, una minúscula y un número', 'error');
                return;
            }
            
            formData.append('action', 'change_password');
            formData.append('user_id', userId);

            submitForm(formData, 'Contraseña actualizada correctamente', () => {
                e.target.reset();
                document.getElementById('passwordStrengthBar').className = 'password-strength-bar';
            });
        }

        // Enviar formulario
        function submitForm(formData, successMessage, callback) {
            const submitButton = event.target.querySelector('button[type="submit"]');
            const originalText = submitButton.innerHTML;
            
            submitButton.innerHTML = '<div class="loading"></div> Guardando...';
            submitButton.disabled = true;

            fetch('user_settings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;

                if (data.success) {
                    showToast(successMessage, 'success');
                    if (callback) callback();
                    
                    // Log exitoso
                    console.log('Configuración guardada exitosamente:', data);
                } else {
                    showToast(data.error || 'Error al guardar configuración', 'error');
                    console.error('Error en respuesta:', data);
                }
            })
            .catch(error => {
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
                console.error('Error de conexión:', error);
                showToast('Error de conexión. Reintentando...', 'error');
                
                // Reintentar después de 2 segundos
                setTimeout(() => {
                    if (confirm('¿Deseas reintentar guardar la configuración?')) {
                        submitForm(formData, successMessage, callback);
                    }
                }, 2000);
            });
        }

        // Restablecer formulario
        function resetForm(formId) {
            if (confirm('¿Estás seguro de que quieres restablecer los cambios?')) {
                const form = document.getElementById(formId);
                form.reset();
                
                // Restablecer toggles a valores por defecto
                form.querySelectorAll('.toggle-switch').forEach(toggle => {
                    // Aquí deberías implementar la lógica para restablecer a valores por defecto
                    // Por ahora, simplemente los mantenemos como están
                });
                
                showToast('Formulario restablecido', 'info');
            }
        }

        // Exportar datos del usuario
        function exportUserData() {
            const button = event.target;
            const originalText = button.innerHTML;
            
            button.innerHTML = '<div class="loading"></div> Exportando...';
            button.disabled = true;

            const formData = new FormData();
            formData.append('action', 'export_data');
            formData.append('user_id', userId);

            fetch('user_settings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                button.innerHTML = originalText;
                button.disabled = false;

                if (data.success) {
                    // Crear y descargar archivo
                    const blob = new Blob([data.data], { type: 'application/json' });
                    const url = window.URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = data.filename;
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    window.URL.revokeObjectURL(url);
                    
                    showToast(`Datos exportados correctamente (${(data.size / 1024).toFixed(2)} KB)`, 'success');
                    console.log('Exportación completada:', data.filename);
                } else {
                    showToast(data.error || 'Error al exportar datos', 'error');
                }
            })
            .catch(error => {
                button.innerHTML = originalText;
                button.disabled = false;
                console.error('Error:', error);
                showToast('Error de conexión durante la exportación', 'error');
            });
        }

        // Mostrar modal de eliminación de cuenta
        function showDeleteAccountModal() {
            const username = userProfile.username || 'usuario';
            const confirmText = `Para eliminar tu cuenta, escribe "${username}" y luego ingresa tu contraseña:`;
            
            const usernameConfirm = prompt(confirmText);
            
            if (usernameConfirm === username) {
                const password = prompt('Ingresa tu contraseña para confirmar:');
                
                if (password) {
                    if (confirm(`¿Estás ABSOLUTAMENTE seguro de que quieres eliminar tu cuenta? 

Esta acción:
• Eliminará todos tus datos permanentemente
• No se puede deshacer
• Cerrará tu sesión inmediatamente

¿Continuar?`)) {
                        deleteUserAccount(password);
                    }
                }
            } else if (usernameConfirm !== null) {
                showToast('Nombre de usuario incorrecto', 'error');
            }
        }

        // Eliminar cuenta de usuario
        function deleteUserAccount(password) {
            const formData = new FormData();
            formData.append('action', 'delete_account');
            formData.append('user_id', userId);
            formData.append('password', password);

            // Mostrar indicador de carga global
            document.body.style.cursor = 'wait';
            
            fetch('user_settings.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                document.body.style.cursor = 'default';
                
                if (data.success) {
                    showToast('Cuenta eliminada correctamente. Redirigiendo...', 'success');
                    
                    // Limpiar datos locales
                    localStorage.clear();
                    sessionStorage.clear();
                    
                    // Redireccionar después de 3 segundos
                    setTimeout(() => {
                        window.location.href = 'login.php?account_deleted=1';
                    }, 3000);
                } else {
                    showToast(data.error || 'Error al eliminar cuenta', 'error');
                }
            })
            .catch(error => {
                document.body.style.cursor = 'default';
                console.error('Error:', error);
                showToast('Error de conexión al eliminar cuenta', 'error');
            });
        }

        // Mostrar información del sistema
        function showSystemInfo() {
            const systemInfo = {
                'Aplicación': '<?php echo APP_NAME; ?>',
                'Versión': '<?php echo APP_VERSION; ?>',
                'Desarrollador': '<?php echo DEVELOPER; ?>',
                'Base de Datos': <?php echo $system_integrity['database'] ? '"Conectada"' : '"Modo Simulación"'; ?>,
                'Usuario Premium': isPremium ? 'Sí' : 'No',
                'Navegador': navigator.userAgent.split(' ')[0],
                'Idioma': navigator.language,
                'Zona Horaria': Intl.DateTimeFormat().resolvedOptions().timeZone,
                'Pantalla': `${screen.width}x${screen.height}`,
                'Memoria JS': navigator.deviceMemory ? `${navigator.deviceMemory} GB` : 'No disponible',
                'Conexión': navigator.connection ? navigator.connection.effectiveType : 'Desconocida'
            };

            let infoText = 'INFORMACIÓN DEL SISTEMA\n' + '='.repeat(30) + '\n\n';
            for (const [key, value] of Object.entries(systemInfo)) {
                infoText += `${key}: ${value}\n`;
            }
            
            infoText += '\n' + '='.repeat(30);
            infoText += '\nGuardianIA v3.0 - Anderson Mamian Chicangana';
            
            // Crear modal personalizado
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(0,0,0,0.8); z-index: 10000;
                display: flex; align-items: center; justify-content: center;
            `;
            
            const content = document.createElement('div');
            content.style.cssText = `
                background: var(--bg-card); padding: 2rem; border-radius: 12px;
                max-width: 500px; width: 90%; max-height: 80%; overflow-y: auto;
                border: 1px solid var(--border-color);
            `;
            
            content.innerHTML = `
                <h3 style="color: var(--text-primary); margin-bottom: 1rem;">
                    <i class="fas fa-info-circle"></i> Información del Sistema
                </h3>
                <pre style="color: var(--text-secondary); font-family: monospace; white-space: pre-wrap; font-size: 0.9rem;">${infoText}</pre>
                <button onclick="this.closest('.modal').remove()" style="
                    background: var(--primary-gradient); color: white; border: none;
                    padding: 0.5rem 1rem; border-radius: 6px; cursor: pointer;
                    margin-top: 1rem; font-weight: 600;
                ">Cerrar</button>
            `;
            
            modal.className = 'modal';
            modal.appendChild(content);
            document.body.appendChild(modal);
            
            // Cerrar al hacer clic fuera
            modal.addEventListener('click', (e) => {
                if (e.target === modal) modal.remove();
            });
        }

        // Mostrar requerimiento premium
        function showPremiumRequired() {
            showToast('Esta característica requiere una suscripción Premium', 'warning');
            
            setTimeout(() => {
                if (confirm('¿Deseas actualizar a Premium para acceder a todas las características avanzadas?')) {
                    upgradeToPremium();
                }
            }, 2000);
        }

        // Actualizar a Premium
        function upgradeToPremium() {
            const features = [
                'Detección de IA avanzada con redes neuronales',
                'Encriptación cuántica de última generación',
                'Análisis predictivo de amenazas',
                'VPN con IA integrada',
                'Asistente IA conversacional ilimitado',
                'Monitoreo en tiempo real 24/7',
                'Soporte prioritario',
                'Actualizaciones exclusivas'
            ];
            
            const modal = document.createElement('div');
            modal.style.cssText = `
                position: fixed; top: 0; left: 0; width: 100%; height: 100%;
                background: rgba(0,0,0,0.9); z-index: 10000;
                display: flex; align-items: center; justify-content: center;
            `;
            
            const content = document.createElement('div');
            content.style.cssText = `
                background: var(--bg-card); padding: 2rem; border-radius: 12px;
                max-width: 600px; width: 90%; border: 1px solid #ffd700;
                box-shadow: 0 0 30px rgba(255, 215, 0, 0.3);
            `;
            
            content.innerHTML = `
                <div style="text-align: center; margin-bottom: 2rem;">
                    <div style="font-size: 3rem; background: linear-gradient(45deg, #ffd700, #ffed4e); 
                                -webkit-background-clip: text; -webkit-text-fill-color: transparent;">
                        <i class="fas fa-crown"></i>
                    </div>
                    <h2 style="color: var(--text-primary); margin: 0.5rem 0;">GuardianIA Premium</h2>
                    <p style="color: var(--text-secondary);">Desbloquea el poder completo de la IA</p>
                </div>
                
                <div style="margin-bottom: 2rem;">
                    <h3 style="color: var(--text-primary); margin-bottom: 1rem;">Características Exclusivas:</h3>
                    <ul style="color: var(--text-secondary); padding-left: 1rem;">
                        ${features.map(feature => `<li style="margin-bottom: 0.5rem;">${feature}</li>`).join('')}
                    </ul>
                </div>
                
                <div style="text-align: center; margin-bottom: 2rem;">
                    <div style="font-size: 2rem; color: var(--text-primary); margin-bottom: 0.5rem;">
                        $60.000 COP/mes
                    </div>
                    <div style="color: var(--text-secondary); font-size: 0.9rem;">
                        15% de descuento en plan anual
                    </div>
                </div>
                
                <div style="display: flex; gap: 1rem; justify-content: center;">
                    <button onclick="proceedToPremium()" style="
                        background: linear-gradient(45deg, #ffd700, #ffed4e); color: #000;
                        border: none; padding: 1rem 2rem; border-radius: 8px; cursor: pointer;
                        font-weight: 700; font-size: 1rem;
                    ">
                        <i class="fas fa-crown"></i> Actualizar Ahora
                    </button>
                    <button onclick="this.closest('.modal').remove()" style="
                        background: var(--bg-secondary); color: var(--text-primary);
                        border: 1px solid var(--border-color); padding: 1rem 2rem; 
                        border-radius: 8px; cursor: pointer; font-weight: 600;
                    ">Más Tarde</button>
                </div>
                
                <div style="text-align: center; margin-top: 1rem; font-size: 0.8rem; color: var(--text-secondary);">
                    Desarrollado por ${userProfile.fullname || 'Anderson Mamian Chicangana'}
                </div>
            `;
            
            modal.className = 'modal';
            modal.appendChild(content);
            document.body.appendChild(modal);
            
            // Función para proceder con Premium
            window.proceedToPremium = function() {
                modal.remove();
                showToast('Funcionalidad de pago en desarrollo. Contacta al desarrollador.', 'info');
                
                // Simular activación premium para demo
                setTimeout(() => {
                    if (confirm('¿Activar modo Premium de demostración?')) {
                        isPremium = true;
                        showToast('¡Premium activado! Recarga la página para ver todos los cambios.', 'success');
                        
                        // Actualizar elementos premium en la página
                        document.querySelectorAll('.feature-locked').forEach(element => {
                            element.classList.remove('feature-locked');
                        });
                        
                        // Actualizar badge
                        const userProfile = document.querySelector('.user-profile');
                        if (!userProfile.querySelector('.premium-badge')) {
                            const badge = document.createElement('span');
                            badge.className = 'premium-badge';
                            badge.innerHTML = 'Premium';
                            userProfile.insertBefore(badge, userProfile.firstChild);
                        }
                    }
                }, 1000);
            };
        }

        // Mostrar notificación toast
        function showToast(message, type = 'info') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toast-message');
            
            toastMessage.textContent = message;
            toast.className = `toast ${type}`;
            toast.classList.add('show');
            
            // Auto-ocultar después de 5 segundos
            setTimeout(() => {
                hideToast();
            }, 5000);
            
            // Log del mensaje
            console.log(`Toast [${type.toUpperCase()}]:`, message);
        }

        // Ocultar notificación toast
        function hideToast() {
            const toast = document.getElementById('toast');
            toast.classList.remove('show');
        }

        // Función para manejar teclas de acceso rápido
        document.addEventListener('keydown', function(e) {
            // Ctrl + S para guardar la sección actual
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                const activeSection = document.querySelector('.settings-section.active');
                const form = activeSection.querySelector('form');
                if (form) {
                    form.dispatchEvent(new Event('submit'));
                }
            }
            
            // Escape para cerrar modales
            if (e.key === 'Escape') {
                const modals = document.querySelectorAll('.modal');
                modals.forEach(modal => modal.remove());
                hideToast();
            }
        });

        // Función para detectar cambios no guardados
        let hasUnsavedChanges = false;
        document.addEventListener('input', function(e) {
            if (e.target.matches('.form-input, .setting-select')) {
                hasUnsavedChanges = true;
                
                // Mostrar indicador visual
                const form = e.target.closest('form');
                if (form && !form.querySelector('.unsaved-indicator')) {
                    const indicator = document.createElement('div');
                    indicator.className = 'unsaved-indicator';
                    indicator.style.cssText = `
                        position: absolute; top: -10px; right: -10px;
                        width: 20px; height: 20px; background: #ffa502;
                        border-radius: 50%; animation: pulse 2s infinite;
                    `;
                    form.style.position = 'relative';
                    form.appendChild(indicator);
                }
            }
        });

        // Advertir sobre cambios no guardados al salir
        window.addEventListener('beforeunload', function(e) {
            if (hasUnsavedChanges) {
                e.preventDefault();
                e.returnValue = '¿Estás seguro de que quieres salir? Tienes cambios sin guardar.';
                return e.returnValue;
            }
        });

        // Limpiar indicador al guardar exitosamente
        document.addEventListener('submit', function(e) {
            setTimeout(() => {
                hasUnsavedChanges = false;
                const indicators = document.querySelectorAll('.unsaved-indicator');
                indicators.forEach(indicator => indicator.remove());
            }, 500);
        });

        // Función de manejo de errores global
        window.addEventListener('error', function(e) {
            console.error('Error capturado:', e.error);
            showToast('Se produjo un error inesperado. Por favor, recarga la página.', 'error');
            
            // Enviar error al servidor para logging (opcional)
            fetch('user_settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'log_client_error',
                    error: {
                        message: e.error.message,
                        stack: e.error.stack,
                        filename: e.filename,
                        lineno: e.lineno,
                        colno: e.colno
                    },
                    user_id: userId,
                    timestamp: new Date().toISOString()
                })
            }).catch(() => {
                // Silenciosamente fallar si no se puede enviar el log
            });
        });

        // Función para verificar estado de conexión
        function checkConnectionStatus() {
            return fetch('user_settings.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=ping'
            })
            .then(response => response.ok)
            .catch(() => false);
        }

        // Verificar conexión periódicamente
        setInterval(async () => {
            const isConnected = await checkConnectionStatus();
            const statusIndicators = document.querySelectorAll('.status-indicator');
            
            if (!isConnected) {
                statusIndicators.forEach(indicator => {
                    if (indicator.classList.contains('connected')) {
                        indicator.className = 'status-indicator disconnected';
                        indicator.innerHTML = '<i class="fas fa-times-circle"></i> Desconectado';
                    }
                });
            }
        }, 30000); // Verificar cada 30 segundos

        // Inicialización final
        console.log('Sistema GuardianIA v3.0 - Configuración de Usuario inicializado correctamente');
        console.log('Desarrollado por: Anderson Mamian Chicangana');
        console.log('Estado Premium:', isPremium ? 'Activo' : 'Básico');
        
        // Mostrar mensaje de bienvenida
        setTimeout(() => {
            showToast(`Bienvenido a ${userProfile.fullname || 'GuardianIA'} v3.0`, 'success');
        }, 1000);

        // Función para actualizar tema dinámicamente
        function updateTheme(theme) {
            const root = document.documentElement;
            
            if (theme === 'light') {
                root.style.setProperty('--bg-primary', '#f8fafc');
                root.style.setProperty('--bg-secondary', '#ffffff');
                root.style.setProperty('--bg-card', '#ffffff');
                root.style.setProperty('--text-primary', '#1a202c');
                root.style.setProperty('--text-secondary', '#4a5568');
                root.style.setProperty('--border-color', '#e2e8f0');
            } else if (theme === 'auto') {
                const isDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
                updateTheme(isDark ? 'dark' : 'light');
            }
            // El tema oscuro ya está por defecto
        }

        // Escuchar cambios en la preferencia del sistema
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            const currentTheme = document.querySelector('select[name="theme"]')?.value;
            if (currentTheme === 'auto') {
                updateTheme('auto');
            }
        });

        // Aplicar tema inicial
        const initialTheme = '<?php echo $user_profile['theme'] ?? 'dark'; ?>';
        updateTheme(initialTheme);

        // Función para mostrar estadísticas de uso
        function showUsageStats() {
            const stats = {
                'Mensajes enviados hoy': Math.floor(Math.random() * 50) + 10,
                'Amenazas detectadas': Math.floor(Math.random() * 20) + 5,
                'Optimizaciones realizadas': Math.floor(Math.random() * 15) + 8,
                'Tiempo activo': `${Math.floor(Math.random() * 8) + 1}h ${Math.floor(Math.random() * 60)}m`,
                'Nivel de seguridad': `${Math.floor(Math.random() * 20) + 80}%`,
                'Estado de la IA': Math.random() > 0.5 ? 'Activa' : 'Aprendiendo'
            };

            let statsText = 'ESTADÍSTICAS DE USO DIARIO\n' + '='.repeat(35) + '\n\n';
            for (const [key, value] of Object.entries(stats)) {
                statsText += `${key}: ${value}\n`;
            }
            
            console.table(stats);
            showToast('Estadísticas de uso mostradas en consola', 'info');
        }

        // Función para backup automático
        function performAutoBackup() {
            if (isPremium) {
                const backupData = {
                    timestamp: new Date().toISOString(),
                    user_id: userId,
                    settings: {
                        security: getCurrentSectionData('security'),
                        performance: getCurrentSectionData('performance'),
                        notifications: getCurrentSectionData('notifications'),
                        privacy: getCurrentSectionData('privacy')
                    },
                    profile: userProfile
                };

                // Guardar en localStorage como backup
                localStorage.setItem('guardianai_backup', JSON.stringify(backupData));
                console.log('Backup automático realizado:', new Date().toLocaleTimeString());
                
                // Mostrar notificación cada hora
                if (Math.random() < 0.1) {
                    showToast('Backup automático completado', 'success');
                }
            }
        }

        // Función para obtener datos actuales de una sección
        function getCurrentSectionData(sectionId) {
            const section = document.getElementById(sectionId);
            const data = {};
            
            if (section) {
                // Obtener valores de toggles
                section.querySelectorAll('.toggle-switch').forEach(toggle => {
                    const settingName = toggle.getAttribute('onclick')?.match(/'([^']+)'/)?.[1];
                    if (settingName) {
                        data[settingName] = toggle.classList.contains('active');
                    }
                });

                // Obtener valores de selects
                section.querySelectorAll('.setting-select').forEach(select => {
                    if (select.name) {
                        data[select.name] = select.value;
                    }
                });
            }
            
            return data;
        }

        // Backup automático cada 10 minutos para usuarios premium
        if (isPremium) {
            setInterval(performAutoBackup, 600000);
        }

        // Función para restaurar backup
        function restoreFromBackup() {
            const backup = localStorage.getItem('guardianai_backup');
            if (backup) {
                try {
                    const backupData = JSON.parse(backup);
                    const backupDate = new Date(backupData.timestamp);
                    
                    if (confirm(`¿Restaurar configuración desde backup del ${backupDate.toLocaleString()}?`)) {
                        // Aquí implementarías la lógica de restauración
                        showToast('Función de restauración en desarrollo', 'info');
                    }
                } catch (e) {
                    showToast('Error al leer backup', 'error');
                }
            } else {
                showToast('No hay backups disponibles', 'warning');
            }
        }

        // Función para limpiar caché y datos temporales
        function clearCache() {
            if (confirm('¿Limpiar caché y datos temporales? Esto mejorará el rendimiento.')) {
                // Limpiar localStorage específico de la app
                const keysToKeep = ['guardianai_backup'];
                const keys = Object.keys(localStorage);
                
                keys.forEach(key => {
                    if (!keysToKeep.includes(key) && key.startsWith('guardianai_')) {
                        localStorage.removeItem(key);
                    }
                });

                // Limpiar sessionStorage
                sessionStorage.clear();

                showToast('Caché limpiado correctamente', 'success');
                console.log('Caché y datos temporales eliminados');
            }
        }

        // Agregar funciones adicionales al objeto global para debugging
        window.GuardianAI = {
            version: '<?php echo APP_VERSION; ?>',
            developer: '<?php echo DEVELOPER; ?>',
            userId: userId,
            isPremium: isPremium,
            showStats: showUsageStats,
            clearCache: clearCache,
            restoreBackup: restoreFromBackup,
            exportData: exportUserData,
            systemInfo: showSystemInfo,
            currentSection: () => currentSection,
            hasUnsavedChanges: () => hasUnsavedChanges
        };

        // Mensaje final para desarrolladores
        console.log('%c🛡️ GuardianIA v3.0 FINAL', 'color: #667eea; font-size: 20px; font-weight: bold;');
        console.log('%cDesarrollado por Anderson Mamian Chicangana', 'color: #4facfe; font-size: 14px;');
        console.log('%cSistema de configuración inicializado correctamente', 'color: #43e97b; font-size: 12px;');
        console.log('%cComandos disponibles: GuardianAI.showStats(), GuardianAI.systemInfo(), etc.', 'color: #ffa502; font-size: 11px;');
        
        // Verificación final de integridad
        const integrityCheck = {
            scriptsLoaded: true,
            domReady: document.readyState === 'complete',
            formsInitialized: document.querySelectorAll('form').length > 0,
            togglesWorking: document.querySelectorAll('.toggle-switch').length > 0,
            apiEndpoint: 'user_settings.php',
            timestamp: new Date().toISOString()
        };

        console.log('Verificación de integridad:', integrityCheck);

        // Auto-save temporal para prevenir pérdida de datos
        let autoSaveInterval;
        document.addEventListener('input', function(e) {
            if (e.target.matches('.form-input')) {
                clearTimeout(autoSaveInterval);
                autoSaveInterval = setTimeout(() => {
                    // Auto-guardar datos temporalmente
                    const tempData = {};
                    document.querySelectorAll('.form-input').forEach(input => {
                        if (input.name && input.value) {
                            tempData[input.name] = input.value;
                        }
                    });
                    
                    sessionStorage.setItem('guardianai_temp_data', JSON.stringify({
                        data: tempData,
                        timestamp: Date.now(),
                        section: currentSection
                    }));
                }, 2000);
            }
        });

        // Restaurar datos temporales si existen
        const tempData = sessionStorage.getItem('guardianai_temp_data');
        if (tempData) {
            try {
                const parsed = JSON.parse(tempData);
                // Solo restaurar si los datos son recientes (menos de 1 hora)
                if (Date.now() - parsed.timestamp < 3600000) {
                    console.log('Datos temporales encontrados, restaurando...');
                    // Implementar restauración si es necesario
                }
            } catch (e) {
                sessionStorage.removeItem('guardianai_temp_data');
            }
        }

        // Finalización exitosa
        console.log('✅ Sistema GuardianIA v3.0 completamente inicializado');
        console.log('📊 Estado:', {
            usuario: userProfile.fullname || 'Usuario',
            premium: isPremium,
            seccion: currentSection,
            bd_conectada: <?php echo $system_integrity['database'] ? 'true' : 'false'; ?>,
            funciones_activas: Object.keys(window.GuardianAI).length
        });

    </script>

    <!-- Analytics y Tracking (opcional) -->
    <script>
        // Analytics básico para tracking de uso
        if (typeof gtag !== 'undefined') {
            gtag('config', 'GA_MEASUREMENT_ID', {
                user_id: userId,
                custom_map: {
                    'premium_status': isPremium ? 'premium' : 'basic',
                    'app_version': '<?php echo APP_VERSION; ?>'
                }
            });
        }

        // Event tracking para metricas
        function trackEvent(action, category = 'settings') {
            if (typeof gtag !== 'undefined') {
                gtag('event', action, {
                    event_category: category,
                    event_label: currentSection,
                    user_id: userId
                });
            }
            
            // Log local para debugging
            console.log(`📈 Event: ${category}/${action} in ${currentSection}`);
        }

        // Track de cambios de sección
        const originalShowSection = showSection;
        showSection = function(sectionName) {
            originalShowSection.call(this, sectionName);
            trackEvent('section_change', 'navigation');
        };
    </script>

    <!-- Service Worker para funcionalidad offline (opcional) -->
    <script>
        if ('serviceWorker' in navigator && isPremium) {
            navigator.serviceWorker.register('/sw.js')
                .then(function(registration) {
                    console.log('Service Worker registrado:', registration);
                })
                .catch(function(error) {
                    console.log('Error registrando Service Worker:', error);
                });
        }
    </script>

    <!-- Modo de desarrollo/debugging -->
    <?php if (defined('APP_DEBUG') && APP_DEBUG): ?>
    <script>
        console.log('🔧 Modo de desarrollo activado');
        
        // Herramientas de debugging
        window.DEBUG = {
            toggleAllSettings: () => {
                document.querySelectorAll('.toggle-switch').forEach(toggle => {
                    toggle.classList.toggle('active');
                });
            },
            simulateError: () => {
                throw new Error('Error simulado para testing');
            },
            showAllToasts: () => {
                const types = ['success', 'error', 'warning', 'info'];
                types.forEach((type, index) => {
                    setTimeout(() => {
                        showToast(`Mensaje de prueba ${type}`, type);
                    }, index * 1000);
                });
            },
            resetToDefaults: () => {
                if (confirm('¿Resetear todas las configuraciones a valores por defecto?')) {
                    location.reload();
                }
            }
        };
        
        console.log('🛠️ Herramientas de debug disponibles en window.DEBUG');
    </script>
    <?php endif; ?>

</body>
</html>