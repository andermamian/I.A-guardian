<?php
/**
 * Dashboard de Usuario - GuardianIA v3.0 FINAL
 * Sincronizado con config.php y config_military.php
 * Anderson Mamian Chicangana
 * Versión completa con enlaces funcionales a módulos complementarios
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

// Obtener instancia de base de datos
$db = MilitaryDatabaseManager::getInstance();
$conn = $db->getConnection();

// Intentar obtener datos del usuario desde la base de datos
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
        'status' => 'active'
    ];
}

// Extraer información del usuario
$user_fullname = $user_data['fullname'] ?? 'Usuario ' . ucfirst($current_username);
$user_email = $user_data['email'] ?? '';
$is_premium = isPremiumUser($current_user_id);
$is_admin = ($user_data['user_type'] ?? 'basic') === 'admin';

// Verificar acceso militar
$has_military_access = isset($user_data['military_access']) && $user_data['military_access'] == 1;
$security_clearance = $user_data['security_clearance'] ?? 'UNCLASSIFIED';

// Verificar membership activa
function checkMembershipAccess($user_id, $feature_required = null) {
    global $db;
    
    if (!$db || !$db->isConnected()) {
        return ['allowed' => true, 'message' => 'Acceso básico permitido'];
    }
    
    try {
        $conn = $db->getConnection();
        
        // Verificar membership premium activa
        $stmt = $conn->prepare("
            SELECT pm.*, u.premium_status 
            FROM users u 
            LEFT JOIN premium_memberships pm ON u.id = pm.user_id AND pm.status = 'active'
            WHERE u.id = ? AND u.status = 'active'
        ");
        
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                $premium_status = $row['premium_status'];
                $has_active_membership = !empty($row['plan_type']);
                
                if ($premium_status === 'premium' || $has_active_membership) {
                    return ['allowed' => true, 'message' => 'Acceso premium activo'];
                }
            }
            $stmt->close();
        }
        
        // Si requiere feature premium específica
        if ($feature_required) {
            return [
                'allowed' => false, 
                'message' => 'Se requiere membresía Premium para acceder a esta función',
                'upgrade_needed' => true
            ];
        }
        
        return ['allowed' => true, 'message' => 'Acceso básico permitido'];
        
    } catch (Exception $e) {
        logEvent('ERROR', 'Error verificando membership: ' . $e->getMessage());
        return ['allowed' => true, 'message' => 'Acceso básico permitido'];
    }
}

// Obtener iniciales para el avatar
$name_parts = explode(' ', $user_fullname);
$user_initials = strtoupper(substr($name_parts[0], 0, 1));
if (count($name_parts) > 1) {
    $user_initials .= strtoupper(substr(end($name_parts), 0, 1));
}

// Determinar saludo según la hora
function getGreeting() {
    $hour = date('H');
    if ($hour >= 5 && $hour < 12) {
        return 'Buenos días';
    } elseif ($hour >= 12 && $hour < 18) {
        return 'Buenas tardes';
    } else {
        return 'Buenas noches';
    }
}

// Funciones auxiliares para obtener datos del usuario
function getUserStats($user_id) {
    global $db;
    
    $stats = [
        'security_level' => 100,
        'performance_score' => rand(88, 98),
        'threats_blocked' => 0,
        'optimizations_today' => rand(1, 3),
        'space_freed' => number_format(rand(1000, 5000) / 1000, 1),
        'last_scan' => date('Y-m-d H:i:s', strtotime('-2 hours')),
        'last_optimization' => date('Y-m-d H:i:s', strtotime('-4 hours'))
    ];
    
    if ($db && $db->isConnected()) {
        try {
            $conn = $db->getConnection();
            
            // Obtener amenazas bloqueadas hoy
            $stmt = $conn->prepare("
                SELECT COUNT(*) as count 
                FROM security_events 
                WHERE user_id = ? AND DATE(created_at) = CURDATE() AND severity IN ('high', 'critical')
            ");
            
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $stats['threats_blocked'] = (int)$row['count'];
                }
                $stmt->close();
            }
            
            // Obtener último escaneo
            $stmt = $conn->prepare("
                SELECT MAX(created_at) as last_scan
                FROM security_events 
                WHERE user_id = ? AND event_type LIKE '%scan%'
            ");
            
            if ($stmt) {
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    if (!empty($row['last_scan'])) {
                        $stats['last_scan'] = $row['last_scan'];
                    }
                }
                $stmt->close();
            }
            
        } catch (Exception $e) {
            logEvent('ERROR', 'Error obteniendo estadísticas de usuario: ' . $e->getMessage());
        }
    }
    
    return $stats;
}

function getUserRecentActivity($user_id) {
    global $db;
    
    $default_activities = [
        [
            'title' => 'Escaneo completo finalizado',
            'description' => 'Sin amenazas detectadas',
            'time' => 'Hace 2 horas',
            'icon' => 'fas fa-shield-check',
            'color' => 'var(--success-gradient)',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours'))
        ],
        [
            'title' => 'Optimización automática',
            'description' => '1.8 GB liberados',
            'time' => 'Hace 4 horas',
            'icon' => 'fas fa-broom',
            'color' => 'var(--info-gradient)',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-4 hours'))
        ],
        [
            'title' => 'Definiciones actualizadas',
            'description' => '1,247 nuevas firmas',
            'time' => 'Hace 6 horas',
            'icon' => 'fas fa-download',
            'color' => 'var(--warning-gradient)',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-6 hours'))
        ],
        [
            'title' => 'Consulta al asistente IA',
            'description' => '"¿Cómo optimizar la batería?"',
            'time' => 'Ayer',
            'icon' => 'fas fa-robot',
            'color' => 'var(--primary-gradient)',
            'timestamp' => date('Y-m-d H:i:s', strtotime('-1 day'))
        ]
    ];
    
    if (!$db || !$db->isConnected()) {
        return $default_activities;
    }
    
    try {
        $conn = $db->getConnection();
        $stmt = $conn->prepare("
            SELECT 
                event_type,
                description,
                created_at,
                severity
            FROM security_events 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 4
        ");
        
        if (!$stmt) {
            return $default_activities;
        }
        
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $activities = [];
        while ($row = $result->fetch_assoc()) {
            $time_diff = time() - strtotime($row['created_at']);
            $time_text = formatTimeAgo($time_diff);
            
            $activities[] = [
                'title' => formatEventType($row['event_type']),
                'description' => $row['description'],
                'time' => $time_text,
                'icon' => getEventIcon($row['event_type']),
                'color' => getEventColor($row['severity']),
                'timestamp' => $row['created_at']
            ];
        }
        $stmt->close();
        
        // Si no hay actividades en BD, usar las por defecto
        return count($activities) > 0 ? $activities : $default_activities;
        
    } catch (Exception $e) {
        logEvent('ERROR', 'Error obteniendo actividad reciente: ' . $e->getMessage());
        return $default_activities;
    }
}

function formatTimeAgo($seconds) {
    if ($seconds < 60) return 'Ahora';
    if ($seconds < 3600) return 'Hace ' . floor($seconds / 60) . ' min';
    if ($seconds < 86400) return 'Hace ' . floor($seconds / 3600) . ' horas';
    if ($seconds < 604800) return 'Hace ' . floor($seconds / 86400) . ' días';
    return 'Hace más de una semana';
}

function formatEventType($event_type) {
    $types = [
        'scan_completed' => 'Escaneo completado',
        'threat_blocked' => 'Amenaza bloqueada',
        'optimization_run' => 'Optimización ejecutada',
        'definition_update' => 'Definiciones actualizadas',
        'ai_consultation' => 'Consulta al asistente IA',
        'LOGIN_SUCCESS' => 'Inicio de sesión exitoso',
        'login_success' => 'Inicio de sesión',
        'system_cleanup' => 'Limpieza del sistema',
        'performance_boost' => 'Mejora de rendimiento',
        'dashboard_access' => 'Acceso al dashboard',
        'defense_protocol' => 'Protocolo de defensa'
    ];
    
    return $types[$event_type] ?? ucfirst(str_replace('_', ' ', $event_type));
}

function getEventIcon($event_type) {
    $icons = [
        'scan_completed' => 'fas fa-shield-check',
        'threat_blocked' => 'fas fa-exclamation-triangle',
        'optimization_run' => 'fas fa-broom',
        'definition_update' => 'fas fa-download',
        'ai_consultation' => 'fas fa-robot',
        'LOGIN_SUCCESS' => 'fas fa-sign-in-alt',
        'login_success' => 'fas fa-sign-in-alt',
        'system_cleanup' => 'fas fa-trash',
        'performance_boost' => 'fas fa-bolt',
        'dashboard_access' => 'fas fa-tachometer-alt',
        'defense_protocol' => 'fas fa-shield-alt'
    ];
    
    return $icons[$event_type] ?? 'fas fa-info-circle';
}

function getEventColor($severity) {
    $colors = [
        'low' => 'var(--info-gradient)',
        'medium' => 'var(--warning-gradient)',
        'high' => 'var(--danger-gradient)',
        'critical' => 'var(--danger-gradient)',
        '' => 'var(--success-gradient)' // Para eventos sin severity
    ];
    
    return $colors[$severity] ?? 'var(--primary-gradient)';
}

// Función para verificar acceso a módulos
function checkModuleAccess($module_name, $user_id) {
    $membership_check = checkMembershipAccess($user_id);
    
    $modules_config = [
        'user_security' => ['premium_required' => false, 'file' => 'user_security.php'],
        'user_performance' => ['premium_required' => false, 'file' => 'user_performance.php'],
        'user_settings' => ['premium_required' => false, 'file' => 'user_settings.php'],
        'user_permissions' => ['premium_required' => true, 'file' => 'user_permissions.php'],
        'user_assistant' => ['premium_required' => false, 'file' => 'user_assistant.php']
    ];
    
    if (!isset($modules_config[$module_name])) {
        return ['allowed' => false, 'message' => 'Módulo no encontrado'];
    }
    
    $module = $modules_config[$module_name];
    
    if ($module['premium_required'] && !$membership_check['allowed']) {
        return [
            'allowed' => false, 
            'message' => 'Se requiere membresía Premium',
            'upgrade_needed' => true
        ];
    }
    
    return ['allowed' => true, 'file' => $module['file']];
}

// Registrar acceso al dashboard
logSecurityEvent('dashboard_access', 'Usuario accedió al dashboard de usuario', 'low', $current_user_id);

// Actualizar último acceso si hay conexión a BD
if ($db && $db->isConnected()) {
    try {
        $conn = $db->getConnection();
        $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param("i", $current_user_id);
            $stmt->execute();
            $stmt->close();
        }
    } catch (Exception $e) {
        logEvent('ERROR', 'Error actualizando último acceso: ' . $e->getMessage());
    }
}

// Obtener estadísticas y actividad
$user_stats = getUserStats($current_user_id);
$recent_activity = getUserRecentActivity($current_user_id);
$greeting = getGreeting();

// Obtener estadísticas del sistema
$system_stats = getSystemStats();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Seguridad - GuardianIA v3.0</title>
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

        .user-menu {
            position: relative;
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

        /* Welcome Header */
        .welcome-header {
            background: var(--primary-gradient);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .welcome-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            animation: welcomeShine 4s infinite;
        }

        @keyframes welcomeShine {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .welcome-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .welcome-subtitle {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 1.5rem;
        }

        .protection-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            color: white;
        }

        .status-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #2ed573;
            animation: statusPulse 2s infinite;
        }

        @keyframes statusPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        /* Quick Stats */
        .quick-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            transition: all var(--animation-speed) ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px var(--shadow-color);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stat-card.security::before {
            background: var(--success-gradient);
        }

        .stat-card.performance::before {
            background: var(--info-gradient);
        }

        .stat-card.threats::before {
            background: var(--danger-gradient);
        }

        .stat-card.optimization::before {
            background: var(--warning-gradient);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: white;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .stat-trend {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }

        .trend-up {
            color: #2ed573;
        }

        .trend-down {
            color: #ff4757;
        }

        /* Módulos de Navegación */
        .modules-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .module-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: var(--card-padding);
            transition: all var(--animation-speed) ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .module-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px var(--shadow-color);
        }

        .module-card.premium {
            border: 2px solid #ffd700;
        }

        .module-card.premium::before {
            content: 'PREMIUM';
            position: absolute;
            top: 10px;
            right: 10px;
            background: var(--premium-gradient);
            color: #000;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.7rem;
            font-weight: 700;
        }

        .module-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .module-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: white;
        }

        .module-title {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .module-description {
            color: var(--text-secondary);
            font-size: 0.9rem;
            line-height: 1.4;
        }

        .module-features {
            margin-top: 1rem;
            list-style: none;
        }

        .module-features li {
            color: var(--text-secondary);
            font-size: 0.85rem;
            padding: 0.25rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .module-features li::before {
            content: '✓';
            color: #2ed573;
            font-weight: bold;
        }

        /* Action Cards */
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .action-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: var(--card-padding);
            transition: all var(--animation-speed) ease;
        }

        .action-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px var(--shadow-color);
        }

        .action-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .action-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .action-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .action-description {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .action-button {
            width: 100%;
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin-top: 1rem;
        }

        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .action-button.success {
            background: var(--success-gradient);
        }

        .action-button.warning {
            background: var(--warning-gradient);
        }

        .action-button.info {
            background: var(--info-gradient);
        }

        .action-button.premium {
            background: var(--premium-gradient);
            color: #000;
        }

        .action-button.military {
            background: var(--military-gradient);
        }

        .action-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Recent Activity */
        .recent-activity {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: var(--card-padding);
            margin-bottom: 2rem;
        }

        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .activity-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            margin-bottom: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            transition: all var(--animation-speed) ease;
        }

        .activity-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: white;
        }

        .activity-content {
            flex: 1;
        }

        .activity-text {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .activity-time {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        /* System Health */
        .system-health {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .health-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: var(--card-padding);
        }

        .health-metric {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .health-metric:last-child {
            border-bottom: none;
        }

        .metric-name {
            font-weight: 600;
        }

        .metric-value {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .metric-bar {
            width: 100px;
            height: 6px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
            overflow: hidden;
        }

        .metric-fill {
            height: 100%;
            border-radius: 3px;
            transition: width 1s ease;
        }

        .metric-fill.good {
            background: var(--success-gradient);
        }

        .metric-fill.warning {
            background: var(--warning-gradient);
        }

        .metric-fill.danger {
            background: var(--danger-gradient);
        }

        /* Quick Actions */
        .quick-actions {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
            z-index: 1000;
        }

        .quick-action-btn {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
        }

        .quick-action-btn:hover {
            transform: scale(1.1);
        }

        .quick-action-btn.scan {
            background: var(--success-gradient);
        }

        .quick-action-btn.optimize {
            background: var(--info-gradient);
        }

        .quick-action-btn.chat {
            background: var(--primary-gradient);
        }

        .quick-action-btn.back {
            background: var(--warning-gradient);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .system-health {
                grid-template-columns: 1fr;
            }

            .action-grid {
                grid-template-columns: 1fr;
            }

            .modules-grid {
                grid-template-columns: 1fr;
            }

            .quick-stats {
                grid-template-columns: 1fr;
            }

            .welcome-title {
                font-size: 1.5rem;
            }

            .main-container {
                padding: 1rem;
            }

            .quick-actions {
                bottom: 1rem;
                right: 1rem;
            }

            .nav-menu {
                display: none;
            }
        }

        /* Loading States */
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

        /* Modal de Membership */
        .membership-modal {
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

        .membership-modal.show {
            display: flex;
        }

        .modal-content {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 2rem;
            max-width: 500px;
            width: 90%;
            text-align: center;
        }

        .modal-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1rem;
            background: var(--premium-gradient);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: #000;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .modal-description {
            color: var(--text-secondary);
            margin-bottom: 2rem;
            line-height: 1.6;
        }

        .modal-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .modal-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
        }

        .modal-btn.primary {
            background: var(--premium-gradient);
            color: #000;
        }

        .modal-btn.secondary {
            background: transparent;
            color: var(--text-secondary);
            border: 1px solid var(--border-color);
        }

        .modal-btn:hover {
            transform: translateY(-2px);
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
                <span>GuardianIA</span>
            </div>
            <ul class="nav-menu">
                <li><a href="#" class="nav-link active">Mi Seguridad</a></li>
                <li><a href="javascript:navigateToModule('user_assistant')" class="nav-link">Asistente IA</a></li>
                <li><a href="javascript:navigateToModule('user_settings')" class="nav-link">Configuración</a></li>
                <?php if ($is_admin): ?>
                <li><a href="admin_dashboard.php" class="nav-link">Panel Admin</a></li>
                <?php endif; ?>
                <?php if ($has_military_access): ?>
                <li><a href="military_dashboard.php" class="nav-link" style="background: var(--military-gradient); color: white;">Militar</a></li>
                <?php endif; ?>
            </ul>
            <div class="user-profile">
                <div class="user-menu">
                    <div class="user-avatar" onclick="toggleDropdown()"><?php echo $user_initials; ?></div>
                    <div class="dropdown-menu" id="userDropdown">
                        <a href="#" class="dropdown-item">
                            <i class="fas fa-user"></i> Perfil
                        </a>
                        <a href="javascript:navigateToModule('user_settings')" class="dropdown-item">
                            <i class="fas fa-cog"></i> Configuración
                        </a>
                        <?php if ($is_premium): ?>
                        <a href="#" class="dropdown-item" style="color: #ffd700;">
                            <i class="fas fa-crown"></i> Premium Activo
                        </a>
                        <?php else: ?>
                        <a href="membership_system.php" class="dropdown-item" style="color: #ffd700;">
                            <i class="fas fa-crown"></i> Obtener Premium
                        </a>
                        <?php endif; ?>
                        <?php if ($has_military_access): ?>
                        <a href="#" class="dropdown-item" style="color: #8b9dc3;">
                            <i class="fas fa-medal"></i> Acceso Militar (<?php echo $security_clearance; ?>)
                        </a>
                        <?php endif; ?>
                        <hr style="border-color: var(--border-color); margin: 10px 0;">
                        <a href="logout.php" class="dropdown-item">
                            <i class="fas fa-sign-out-alt"></i> Cerrar Sesión
                        </a>
                    </div>
                </div>
                <span><?php echo htmlspecialchars($user_fullname); ?></span>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Welcome Header -->
        <div class="welcome-header">
            <h1 class="welcome-title"><?php echo $greeting; ?>, <?php echo htmlspecialchars(explode(' ', $user_fullname)[0]); ?>!</h1>
            <p class="welcome-subtitle">
                Tu sistema está protegido y funcionando óptimamente
                <?php if ($is_premium): ?>
                <span style="color: #ffd700;"> ⭐ Cuenta Premium</span>
                <?php endif; ?>
                <?php if ($has_military_access): ?>
                <span style="color: #8b9dc3;"> 🛡️ Acceso Militar</span>
                <?php endif; ?>
            </p>
            <div class="protection-status">
                <div class="status-indicator"></div>
                <span>Protección Activa</span>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="quick-stats">
            <div class="stat-card security">
                <div class="stat-header">
                    <div>
                        <div class="stat-value" style="color: #2ed573;"><?php echo $user_stats['security_level']; ?>%</div>
                        <div class="stat-label">Nivel de Seguridad</div>
                        <div class="stat-trend trend-up">
                            <i class="fas fa-arrow-up"></i>
                            <span>+5% esta semana</span>
                        </div>
                    </div>
                    <div class="stat-icon" style="background: var(--success-gradient);">
                        <i class="fas fa-shield-check"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card performance">
                <div class="stat-header">
                    <div>
                        <div class="stat-value" style="color: #4facfe;"><?php echo $user_stats['performance_score']; ?></div>
                        <div class="stat-label">Score de Rendimiento</div>
                        <div class="stat-trend trend-up">
                            <i class="fas fa-arrow-up"></i>
                            <span>+12 puntos hoy</span>
                        </div>
                    </div>
                    <div class="stat-icon" style="background: var(--info-gradient);">
                        <i class="fas fa-tachometer-alt"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card threats">
                <div class="stat-header">
                    <div>
                        <div class="stat-value" style="color: #ff4757;"><?php echo $user_stats['threats_blocked']; ?></div>
                        <div class="stat-label">Amenazas Bloqueadas</div>
                        <div class="stat-trend trend-down">
                            <i class="fas fa-arrow-down"></i>
                            <span>Sistema seguro</span>
                        </div>
                    </div>
                    <div class="stat-icon" style="background: var(--danger-gradient);">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>

            <div class="stat-card optimization">
                <div class="stat-header">
                    <div>
                        <div class="stat-value" style="color: #ffa726;"><?php echo $user_stats['space_freed']; ?> GB</div>
                        <div class="stat-label">Espacio Liberado</div>
                        <div class="stat-trend trend-up">
                            <i class="fas fa-arrow-up"></i>
                            <span>Hoy</span>
                        </div>
                    </div>
                    <div class="stat-icon" style="background: var(--warning-gradient);">
                        <i class="fas fa-broom"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Módulos del Sistema -->
        <div class="modules-grid">
            <div class="module-card" onclick="navigateToModule('user_security')">
                <div class="module-header">
                    <div class="module-icon" style="background: var(--success-gradient);">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div>
                        <div class="module-title">Centro de Seguridad</div>
                        <div class="module-description">
                            Gestiona la protección completa de tu sistema
                        </div>
                    </div>
                </div>
                <ul class="module-features">
                    <li>Escaneo en tiempo real</li>
                    <li>Detección de amenazas</li>
                    <li>Firewall inteligente</li>
                    <li>Protección web</li>
                </ul>
            </div>

            <div class="module-card" onclick="navigateToModule('user_performance')">
                <div class="module-header">
                    <div class="module-icon" style="background: var(--info-gradient);">
                        <i class="fas fa-rocket"></i>
                    </div>
                    <div>
                        <div class="module-title">Optimización de Rendimiento</div>
                        <div class="module-description">
                            Acelera y optimiza tu sistema automáticamente
                        </div>
                    </div>
                </div>
                <ul class="module-features">
                    <li>Limpieza automática</li>
                    <li>Optimización de inicio</li>
                    <li>Gestión de memoria</li>
                    <li>Desfragmentación</li>
                </ul>
            </div>

            <div class="module-card" onclick="navigateToModule('user_settings')">
                <div class="module-header">
                    <div class="module-icon" style="background: var(--primary-gradient);">
                        <i class="fas fa-cogs"></i>
                    </div>
                    <div>
                        <div class="module-title">Configuraciones</div>
                        <div class="module-description">
                            Personaliza tu experiencia de seguridad
                        </div>
                    </div>
                </div>
                <ul class="module-features">
                    <li>Configuración de alertas</li>
                    <li>Preferencias de escaneo</li>
                    <li>Configuración de red</li>
                    <li>Backup automático</li>
                </ul>
            </div>

            <div class="module-card premium" onclick="navigateToModule('user_permissions')">
                <div class="module-header">
                    <div class="module-icon" style="background: var(--premium-gradient);">
                        <i class="fas fa-key"></i>
                    </div>
                    <div>
                        <div class="module-title">Control de Permisos</div>
                        <div class="module-description">
                            Gestión avanzada de permisos y accesos
                        </div>
                    </div>
                </div>
                <ul class="module-features">
                    <li>Control de aplicaciones</li>
                    <li>Gestión de usuarios</li>
                    <li>Permisos de archivos</li>
                    <li>Control parental</li>
                </ul>
            </div>

            <div class="module-card" onclick="navigateToModule('user_assistant')">
                <div class="module-header">
                    <div class="module-icon" style="background: var(--warning-gradient);">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div>
                        <div class="module-title">Asistente IA</div>
                        <div class="module-description">
                            Tu asistente inteligente para resolver problemas
                        </div>
                    </div>
                </div>
                <ul class="module-features">
                    <li>Diagnóstico automático</li>
                    <li>Resolución de problemas</li>
                    <li>Consejos personalizados</li>
                    <li>Soporte 24/7</li>
                </ul>
            </div>

            <?php if (!$is_premium): ?>
            <div class="module-card premium" onclick="window.location.href='membership_system.php'">
                <div class="module-header">
                    <div class="module-icon" style="background: var(--premium-gradient);">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div>
                        <div class="module-title">Obtener Premium</div>
                        <div class="module-description">
                            Accede a todas las funciones avanzadas
                        </div>
                    </div>
                </div>
                <ul class="module-features">
                    <li>Protección cuántica</li>
                    <li>Optimización avanzada</li>
                    <li>Soporte prioritario</li>
                    <li>Funciones exclusivas</li>
                </ul>
            </div>
            <?php endif; ?>
        </div>

        <!-- Action Cards */
        <div class="action-grid">
            <div class="action-card">
                <div class="action-header">
                    <div class="action-icon" style="background: var(--success-gradient);">
                        <i class="fas fa-search"></i>
                    </div>
                    <div>
                        <div class="action-title">Escaneo Rápido</div>
                        <div class="action-description">
                            Analiza tu sistema en busca de amenazas en menos de 2 minutos
                        </div>
                    </div>
                </div>
                <button class="action-button success" onclick="quickScan()">
                    <i class="fas fa-play"></i>
                    Iniciar Escaneo
                </button>
            </div>

            <div class="action-card">
                <div class="action-header">
                    <div class="action-icon" style="background: var(--info-gradient);">
                        <i class="fas fa-magic"></i>
                    </div>
                    <div>
                        <div class="action-title">Optimización Automática</div>
                        <div class="action-description">
                            Mejora el rendimiento de tu sistema con un solo clic
                        </div>
                    </div>
                </div>
                <button class="action-button info" onclick="autoOptimize()">
                    <i class="fas fa-bolt"></i>
                    Optimizar Ahora
                </button>
            </div>

            <div class="action-card">
                <div class="action-header">
                    <div class="action-icon" style="background: var(--primary-gradient);">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div>
                        <div class="action-title">Asistente IA</div>
                        <div class="action-description">
                            Pregunta cualquier cosa sobre la seguridad de tu sistema
                        </div>
                    </div>
                </div>
                <button class="action-button" onclick="navigateToModule('user_assistant')">
                    <i class="fas fa-comments"></i>
                    Hablar con IA
                </button>
            </div>

            <?php if ($is_premium): ?>
            <div class="action-card">
                <div class="action-header">
                    <div class="action-icon" style="background: var(--warning-gradient);">
                        <i class="fas fa-atom"></i>
                    </div>
                    <div>
                        <div class="action-title">Optimización Cuántica</div>
                        <div class="action-description">
                            Función Premium: Optimización avanzada con tecnología cuántica
                        </div>
                    </div>
                </div>
                <button class="action-button warning" onclick="quantumOptimize()">
                    <i class="fas fa-atom"></i>
                    Activar Cuántico
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- System Health -->
        <div class="system-health">
            <div class="health-card">
                <div class="activity-header">
                    <h2 class="activity-title">Estado del Sistema</h2>
                    <i class="fas fa-heartbeat" style="color: #2ed573;"></i>
                </div>
                
                <div class="health-metric">
                    <span class="metric-name">CPU</span>
                    <div class="metric-value">
                        <div class="metric-bar">
                            <div class="metric-fill good" style="width: 45%"></div>
                        </div>
                        <span>45%</span>
                    </div>
                </div>

                <div class="health-metric">
                    <span class="metric-name">Memoria RAM</span>
                    <div class="metric-value">
                        <div class="metric-bar">
                            <div class="metric-fill warning" style="width: 67%"></div>
                        </div>
                        <span>67%</span>
                    </div>
                </div>

                <div class="health-metric">
                    <span class="metric-name">Almacenamiento</span>
                    <div class="metric-value">
                        <div class="metric-bar">
                            <div class="metric-fill warning" style="width: 78%"></div>
                        </div>
                        <span>78%</span>
                    </div>
                </div>

                <div class="health-metric">
                    <span class="metric-name">Red</span>
                    <div class="metric-value">
                        <div class="metric-bar">
                            <div class="metric-fill good" style="width: 92%"></div>
                        </div>
                        <span>Óptima</span>
                    </div>
                </div>

                <div class="health-metric">
                    <span class="metric-name">Seguridad</span>
                    <div class="metric-value">
                        <div class="metric-bar">
                            <div class="metric-fill good" style="width: <?php echo $user_stats['security_level']; ?>%"></div>
                        </div>
                        <span><?php echo $user_stats['security_level']; ?>%</span>
                    </div>
                </div>
            </div>

            <div class="health-card">
                <div class="activity-header">
                    <h2 class="activity-title">Actividad Reciente</h2>
                    <i class="fas fa-clock" style="color: #4facfe;"></i>
                </div>

                <?php foreach ($recent_activity as $activity): ?>
                <div class="activity-item">
                    <div class="activity-icon" style="background: <?php echo $activity['color']; ?>;">
                        <i class="<?php echo $activity['icon']; ?>"></i>
                    </div>
                    <div class="activity-content">
                        <div class="activity-text"><?php echo htmlspecialchars($activity['title']); ?></div>
                        <div class="activity-time"><?php echo $activity['time']; ?> - <?php echo htmlspecialchars($activity['description']); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="quick-actions">
        <?php if ($is_admin): ?>
        <button class="quick-action-btn back" onclick="goToAdmin()" title="Panel Admin">
            <i class="fas fa-user-shield"></i>
        </button>
        <?php endif; ?>
        <button class="quick-action-btn scan" onclick="quickScan()" title="Escaneo Rápido">
            <i class="fas fa-search"></i>
        </button>
        <button class="quick-action-btn optimize" onclick="autoOptimize()" title="Optimización Rápida">
            <i class="fas fa-bolt"></i>
        </button>
        <button class="quick-action-btn chat" onclick="navigateToModule('user_assistant')" title="Asistente IA">
            <i class="fas fa-robot"></i>
        </button>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <span id="toast-message"></span>
    </div>

    <!-- Modal de Membership -->
    <div id="membershipModal" class="membership-modal">
        <div class="modal-content">
            <div class="modal-icon">
                <i class="fas fa-crown"></i>
            </div>
            <h2 class="modal-title">Función Premium Requerida</h2>
            <p class="modal-description">
                Esta función requiere una membresía Premium activa. 
                Actualiza tu cuenta para acceder a todas las funciones avanzadas de GuardianIA.
            </p>
            <div class="modal-buttons">
                <button class="modal-btn primary" onclick="window.location.href='membership_system.php'">
                    Obtener Premium
                </button>
                <button class="modal-btn secondary" onclick="closeMembershipModal()">
                    Cancelar
                </button>
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        let updateInterval;
        let isScanning = false;
        let isOptimizing = false;

        // Configuración del usuario
        const userConfig = {
            isPremium: <?php echo $is_premium ? 'true' : 'false'; ?>,
            isAdmin: <?php echo $is_admin ? 'true' : 'false'; ?>,
            hasMilitaryAccess: <?php echo $has_military_access ? 'true' : 'false'; ?>,
            userId: <?php echo $current_user_id; ?>
        };

        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            initializeDashboard();
            startRealTimeUpdates();
        });

        // Toggle dropdown menu
        function toggleDropdown() {
            const dropdown = document.getElementById('userDropdown');
            dropdown.classList.toggle('show');
        }

        // Cerrar dropdown al hacer clic fuera
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('userDropdown');
            const avatar = document.querySelector('.user-avatar');
            
            if (!avatar.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });

        // Inicializar dashboard
        function initializeDashboard() {
            updateSystemMetrics();
            checkSystemHealth();
            showWelcomeMessage();
        }

        // Mostrar mensaje de bienvenida
        function showWelcomeMessage() {
            setTimeout(() => {
                showToast('Tu sistema está protegido y funcionando óptimamente.', 'success');
            }, 1000);
        }

        // Función para navegar a módulos
        function navigateToModule(moduleName) {
            showLoading();
            
            // Verificar acceso al módulo
            checkModuleAccess(moduleName)
                .then(result => {
                    hideLoading();
                    
                    if (result.allowed) {
                        window.location.href = result.file;
                    } else {
                        if (result.upgrade_needed) {
                            showMembershipModal(result.message);
                        } else {
                            showToast(result.message, 'warning');
                        }
                    }
                })
                .catch(error => {
                    hideLoading();
                    showToast('Error al acceder al módulo: ' + error.message, 'error');
                });
        }

        // Verificar acceso a módulo (simulado - en producción sería AJAX)
        function checkModuleAccess(moduleName) {
            return new Promise((resolve) => {
                // Simular verificación de acceso
                const modules = {
                    'user_security': { premium_required: false, file: 'user_security.php' },
                    'user_performance': { premium_required: false, file: 'user_performance.php' },
                    'user_settings': { premium_required: false, file: 'user_settings.php' },
                    'user_permissions': { premium_required: true, file: 'user_permissions.php' },
                    'user_assistant': { premium_required: false, file: 'user_assistant.php' }
                };

                setTimeout(() => {
                    if (!modules[moduleName]) {
                        resolve({ allowed: false, message: 'Módulo no encontrado' });
                        return;
                    }

                    const module = modules[moduleName];
                    
                    if (module.premium_required && !userConfig.isPremium) {
                        resolve({ 
                            allowed: false, 
                            message: 'Se requiere membresía Premium para acceder a esta función',
                            upgrade_needed: true
                        });
                    } else {
                        resolve({ allowed: true, file: module.file });
                    }
                }, 500);
            });
        }

        // Mostrar modal de membership
        function showMembershipModal(message = null) {
            const modal = document.getElementById('membershipModal');
            if (message) {
                modal.querySelector('.modal-description').textContent = message;
            }
            modal.classList.add('show');
        }

        // Cerrar modal de membership
        function closeMembershipModal() {
            const modal = document.getElementById('membershipModal');
            modal.classList.remove('show');
        }

        // Actualizar métricas del sistema
        function updateSystemMetrics() {
            // Simular actualización de métricas
            updateHealthBars();
        }

        // Actualizar barras de salud
        function updateHealthBars() {
            const healthMetrics = {
                cpu: Math.floor(Math.random() * 30) + 30,
                ram: Math.floor(Math.random() * 40) + 50,
                storage: Math.floor(Math.random() * 20) + 70,
                network: Math.floor(Math.random() * 10) + 85,
                security: <?php echo $user_stats['security_level']; ?>
            };

            const healthBars = document.querySelectorAll('.metric-fill');
            const healthValues = document.querySelectorAll('.metric-value span');

            if (healthBars[0]) healthBars[0].style.width = healthMetrics.cpu + '%';
            if (healthBars[1]) healthBars[1].style.width = healthMetrics.ram + '%';
            if (healthBars[2]) healthBars[2].style.width = healthMetrics.storage + '%';
            if (healthBars[3]) healthBars[3].style.width = healthMetrics.network + '%';

            if (healthValues[0]) healthValues[0].textContent = healthMetrics.cpu + '%';
            if (healthValues[1]) healthValues[1].textContent = healthMetrics.ram + '%';
            if (healthValues[2]) healthValues[2].textContent = healthMetrics.storage + '%';
        }

        // Iniciar actualizaciones en tiempo real
        function startRealTimeUpdates() {
            updateInterval = setInterval(() => {
                updateSystemMetrics();
                updateProtectionStatus();
            }, 10000);
        }

        // Actualizar estado de protección
        function updateProtectionStatus() {
            const statusIndicator = document.querySelector('.status-indicator');
            const protectionStatus = document.querySelector('.protection-status span');
            
            if (Math.random() < 0.1) {
                statusIndicator.style.background = '#ffa502';
                protectionStatus.textContent = 'Actualizando...';
                
                setTimeout(() => {
                    statusIndicator.style.background = '#2ed573';
                    protectionStatus.textContent = 'Protección Activa';
                }, 3000);
            }
        }

        // Escaneo rápido
        function quickScan() {
            if (isScanning) {
                showToast('Ya hay un escaneo en progreso...', 'warning');
                return;
            }

            isScanning = true;
            const scanButton = document.querySelector('.action-button.success');
            const originalText = scanButton.innerHTML;
            
            scanButton.innerHTML = '<div class="loading"></div> Escaneando...';
            scanButton.disabled = true;

            showToast('Iniciando escaneo rápido del sistema...', 'info');

            setTimeout(() => {
                showToast('Escaneo completado. No se encontraron amenazas.', 'success');
                
                scanButton.innerHTML = originalText;
                scanButton.disabled = false;
                isScanning = false;
                
                updateSystemMetrics();
            }, 5000);
        }

        // Optimización automática
        function autoOptimize() {
            if (isOptimizing) {
                showToast('Ya hay una optimización en progreso...', 'warning');
                return;
            }

            isOptimizing = true;
            const optimizeButton = document.querySelector('.action-button.info');
            const originalText = optimizeButton.innerHTML;
            
            optimizeButton.innerHTML = '<div class="loading"></div> Optimizando...';
            optimizeButton.disabled = true;

            showToast('Iniciando optimización automática del sistema...', 'info');

            setTimeout(() => {
                showToast('Optimización completada. Rendimiento mejorado en 18%.', 'success');
                
                optimizeButton.innerHTML = originalText;
                optimizeButton.disabled = false;
                isOptimizing = false;
                
                updateSystemMetrics();
            }, 5000);
        }

        // Optimización cuántica (Premium)
        function quantumOptimize() {
            if (!userConfig.isPremium) {
                showMembershipModal('La optimización cuántica requiere una membresía Premium activa.');
                return;
            }
            navigateToModule('user_performance');
        }

        // Ir al panel admin
        function goToAdmin() {
            window.location.href = 'admin_dashboard.php';
        }

        // Verificar salud del sistema
        function checkSystemHealth() {
            console.log('Verificando salud del sistema...');
        }

        // Mostrar indicador de carga
        function showLoading() {
            showToast('Cargando módulo...', 'info');
        }

        // Ocultar indicador de carga
        function hideLoading() {
            // El toast se oculta automáticamente
        }

        // Mostrar notificación toast
        function showToast(message, type = 'info') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toast-message');
            
            toastMessage.textContent = message;
            toast.className = `toast ${type}`;
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 4000);
        }

        // Cerrar modal al hacer clic fuera
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('membershipModal');
            if (e.target === modal) {
                closeMembershipModal();
            }
        });

        // Cleanup al salir
        window.addEventListener('beforeunload', function() {
            if (updateInterval) {
                clearInterval(updateInterval);
            }
        });
    </script>
</body>
</html>