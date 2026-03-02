<?php
/**
 * Dashboard de Usuario - GuardianIA v3.0 FINAL ACTUALIZADO
 * Separación Free/Premium y sin funciones de administrador
 * Anderson Mamian Chicangana
 * Versión con control de acceso por membresía
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

// Obtener nivel de membresía específico
function getUserMembershipLevel($user_id) {
    global $db;
    
    if (!$db || !$db->isConnected()) {
        return 'RECRUIT'; // Plan gratuito por defecto
    }
    
    try {
        $conn = $db->getConnection();
        $stmt = $conn->prepare("
            SELECT pm.membership_type, pm.status, u.premium_status 
            FROM users u 
            LEFT JOIN premium_memberships pm ON u.id = pm.user_id AND pm.status = 'active'
            WHERE u.id = ? AND u.status = 'active'
        ");
        
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($row = $result->fetch_assoc()) {
                if ($row['membership_type']) {
                    return strtoupper($row['membership_type']);
                } elseif ($row['premium_status'] === 'premium') {
                    return 'TACTICAL_PRO'; // Premium básico
                }
            }
        }
    } catch (Exception $e) {
        logEvent('ERROR', 'Error obteniendo nivel de membresía: ' . $e->getMessage());
    }
    
    return 'RECRUIT';
}

// Verificar acceso a funciones según membresía
function checkFeatureAccess($feature, $user_membership_level) {
    $feature_requirements = [
        // Funciones FREE (disponibles para RECRUIT)
        'user_security' => 'RECRUIT',
        'user_performance' => 'RECRUIT', 
        'user_settings' => 'RECRUIT',
        'quick_scan' => 'RECRUIT',
        'basic_optimization' => 'RECRUIT',
        
        // Funciones PREMIUM
        'user_permissions' => 'OPERATIVE',
        'user_assistant' => 'OPERATIVE',
        'chatbot' => 'OPERATIVE', // Chatbot ahora requiere membresía pagada
        'quantum_optimization' => 'TACTICAL_PRO',
        'ai_vpn' => 'TACTICAL_PRO',
        'real_time_monitoring' => 'CYBER_COMMAND',
        'quantum_encryption' => 'CYBER_COMMAND',
        'military_access' => 'QUANTUM_ELITE',
        'unlimited_features' => 'QUANTUM_ELITE'
    ];
    
    $membership_hierarchy = [
        'RECRUIT' => 0,
        'OPERATIVE' => 1,
        'TACTICAL_PRO' => 2,
        'CYBER_COMMAND' => 3,
        'QUANTUM_ELITE' => 4
    ];
    
    $required_level = $feature_requirements[$feature] ?? 'QUANTUM_ELITE';
    $user_level = $membership_hierarchy[$user_membership_level] ?? 0;
    $required_level_num = $membership_hierarchy[$required_level] ?? 4;
    
    return $user_level >= $required_level_num;
}

$user_membership_level = getUserMembershipLevel($current_user_id);

// Generar saludo basado en la hora
$hour = date('H');
if ($hour < 12) {
    $greeting = "Buenos días";
} elseif ($hour < 18) {
    $greeting = "Buenas tardes";
} else {
    $greeting = "Buenas noches";
}

// Generar iniciales del usuario
$names = explode(' ', $user_fullname);
$user_initials = strtoupper(substr($names[0], 0, 1) . (isset($names[1]) ? substr($names[1], 0, 1) : ''));

// Estadísticas del usuario
$user_stats = [
    'security_level' => rand(85, 98),
    'performance_score' => rand(750, 950),
    'threats_blocked' => rand(0, 15),
    'space_freed' => rand(1, 8)
];

// Actividad reciente
$recent_activity = [
    [
        'icon' => 'fas fa-shield-check',
        'title' => 'Escaneo de seguridad completado',
        'description' => 'Sistema limpio, sin amenazas detectadas',
        'time' => '2 min',
        'color' => 'var(--success-gradient)'
    ],
    [
        'icon' => 'fas fa-broom',
        'title' => 'Optimización automática',
        'description' => 'Liberados ' . $user_stats['space_freed'] . ' GB de espacio',
        'time' => '15 min',
        'color' => 'var(--info-gradient)'
    ],
    [
        'icon' => 'fas fa-update',
        'title' => 'Definiciones actualizadas',
        'description' => 'Base de datos de amenazas actualizada',
        'time' => '1 hora',
        'color' => 'var(--warning-gradient)'
    ],
    [
        'icon' => 'fas fa-user-check',
        'title' => 'Inicio de sesión exitoso',
        'description' => 'Acceso desde ubicación verificada',
        'time' => '2 horas',
        'color' => 'var(--primary-gradient)'
    ]
];

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GuardianIA - Dashboard Usuario</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --success-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --warning-gradient: linear-gradient(135deg, #ffa726 0%, #fb8c00 100%);
            --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --info-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --premium-gradient: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            
            --bg-primary: #0a0a0a;
            --bg-secondary: #1a1a2e;
            --bg-card: #16213e;
            --text-primary: #ffffff;
            --text-secondary: #a0a9c0;
            --border-color: rgba(255, 255, 255, 0.1);
            --shadow-color: rgba(0, 0, 0, 0.3);
            
            --border-radius: 12px;
            --card-padding: 1.5rem;
            --animation-speed: 0.3s;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--bg-primary) 0%, var(--bg-secondary) 50%, var(--bg-card) 100%);
            color: var(--text-primary);
            min-height: 100vh;
            position: relative;
        }

        .animated-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: 
                radial-gradient(circle at 20% 80%, rgba(120, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255, 119, 198, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(120, 219, 255, 0.2) 0%, transparent 50%);
            z-index: -1;
            animation: bgShift 20s ease-in-out infinite;
        }

        @keyframes bgShift {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.1) rotate(180deg); }
        }

        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(22, 33, 62, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid var(--border-color);
            z-index: 1000;
            height: 80px;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            height: 100%;
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
            cursor: pointer;
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

        .membership-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            color: white;
            margin-right: 1rem;
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

        .module-card.locked {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .module-card.locked::after {
            content: '🔒';
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 3rem;
            opacity: 0.3;
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
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .module-description {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .module-features {
            list-style: none;
            margin-top: 1rem;
        }

        .module-features li {
            padding: 0.25rem 0;
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .module-features li::before {
            content: '✓';
            color: #2ed573;
            margin-right: 0.5rem;
            font-weight: bold;
        }

        /* Action Cards */
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
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
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: white;
        }

        .action-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .action-description {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .action-button {
            width: 100%;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .action-button:hover {
            transform: translateY(-2px);
        }

        .action-button.success {
            background: var(--success-gradient);
            color: white;
        }

        .action-button.info {
            background: var(--info-gradient);
            color: white;
        }

        .action-button.warning {
            background: var(--warning-gradient);
            color: white;
        }

        /* System Health */
        .system-health {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .health-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: var(--card-padding);
        }

        .activity-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .activity-title {
            font-size: 1.2rem;
            font-weight: 700;
        }

        .health-metric {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .metric-name {
            font-weight: 600;
            color: var(--text-secondary);
        }

        .metric-value {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .metric-bar {
            width: 100px;
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
        }

        .metric-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.5s ease;
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

        .activity-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.9rem;
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
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 1.3rem;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
            box-shadow: 0 4px 15px var(--shadow-color);
        }

        .quick-action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px var(--shadow-color);
        }

        .quick-action-btn.scan {
            background: var(--success-gradient);
        }

        .quick-action-btn.optimize {
            background: var(--info-gradient);
        }

        .quick-action-btn.back {
            background: var(--primary-gradient);
        }

        /* Toast Notification */
        .toast {
            position: fixed;
            top: 100px;
            right: 2rem;
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            padding: 1rem 1.5rem;
            color: var(--text-primary);
            box-shadow: 0 10px 25px var(--shadow-color);
            transform: translateX(400px);
            transition: transform var(--animation-speed) ease;
            z-index: 2000;
            max-width: 350px;
        }

        .toast.show {
            transform: translateX(0);
        }

        .toast.success {
            border-left: 4px solid #2ed573;
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

        /* Responsive */
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }
            
            .welcome-title {
                font-size: 2rem;
            }
            
            .quick-stats {
                grid-template-columns: 1fr;
            }
            
            .modules-grid {
                grid-template-columns: 1fr;
            }
            
            .action-grid {
                grid-template-columns: 1fr;
            }
            
            .system-health {
                grid-template-columns: 1fr;
            }
            
            .nav-menu {
                display: none;
            }
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
                <li><a href="performance.php" class="nav-link">Rendimiento</a></li>
                <li><a href="threat_center.php" class="nav-link">Centro de Amenazas</a></li>
                <li><a href="javascript:navigateToModule('user_settings')" class="nav-link">Configuración</a></li>
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
                            <i class="fas fa-crown"></i> <?php echo $user_membership_level; ?> Activo
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
            </p>
            <div style="margin-bottom: 1rem;">
                <div class="membership-badge">
                    <?php if ($user_membership_level === 'RECRUIT'): ?>
                        🎖️ Plan Gratuito
                    <?php elseif ($user_membership_level === 'OPERATIVE'): ?>
                        🎯 Operative
                    <?php elseif ($user_membership_level === 'TACTICAL_PRO'): ?>
                        ⚔️ Tactical Pro
                    <?php elseif ($user_membership_level === 'CYBER_COMMAND'): ?>
                        🛡️ Cyber Command
                    <?php elseif ($user_membership_level === 'QUANTUM_ELITE'): ?>
                        👑 Quantum Elite
                    <?php endif; ?>
                </div>
                <?php if ($has_military_access): ?>
                <div class="membership-badge" style="background: rgba(139, 157, 195, 0.3);">
                    🛡️ Acceso Militar
                </div>
                <?php endif; ?>
            </div>
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
            <!-- FUNCIONES FREE -->
            <div class="module-card" onclick="navigateToModule('user_security')">
                <div class="module-header">
                    <div class="module-icon" style="background: var(--success-gradient);">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div>
                        <div class="module-title">Centro de Seguridad</div>
                        <div class="module-description">
                            Gestiona la protección básica de tu sistema
                        </div>
                    </div>
                </div>
                <ul class="module-features">
                    <li>Escaneo básico</li>
                    <li>Detección de amenazas</li>
                    <li>Firewall básico</li>
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
                            Optimización básica de tu sistema
                        </div>
                    </div>
                </div>
                <ul class="module-features">
                    <li>Limpieza básica</li>
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
                            Configuraciones básicas del sistema
                        </div>
                    </div>
                </div>
                <ul class="module-features">
                    <li>Configuración de alertas</li>
                    <li>Preferencias básicas</li>
                    <li>Configuración de red</li>
                    <li>Backup básico</li>
                </ul>
            </div>

            <!-- FUNCIONES PREMIUM -->
            <?php if (checkFeatureAccess('user_permissions', $user_membership_level)): ?>
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
            <?php else: ?>
            <div class="module-card premium locked" onclick="showMembershipModal('Control de Permisos', 'OPERATIVE')">
                <div class="module-header">
                    <div class="module-icon" style="background: var(--premium-gradient);">
                        <i class="fas fa-key"></i>
                    </div>
                    <div>
                        <div class="module-title">Control de Permisos</div>
                        <div class="module-description">
                            Requiere plan Operative o superior
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
            <?php endif; ?>

            <?php if (checkFeatureAccess('user_assistant', $user_membership_level)): ?>
            <div class="module-card premium" onclick="navigateToModule('user_assistant')">
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
                    <li>Soporte avanzado</li>
                </ul>
            </div>
            <?php else: ?>
            <div class="module-card premium locked" onclick="showMembershipModal('Asistente IA', 'OPERATIVE')">
                <div class="module-header">
                    <div class="module-icon" style="background: var(--warning-gradient);">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div>
                        <div class="module-title">Asistente IA</div>
                        <div class="module-description">
                            Requiere plan Operative o superior
                        </div>
                    </div>
                </div>
                <ul class="module-features">
                    <li>Diagnóstico automático</li>
                    <li>Resolución de problemas</li>
                    <li>Consejos personalizados</li>
                    <li>Soporte avanzado</li>
                </ul>
            </div>
            <?php endif; ?>

            <!-- CHATBOT PREMIUM -->
            <?php if (checkFeatureAccess('chatbot', $user_membership_level)): ?>
            <div class="module-card premium" onclick="navigateToModule('chatbot')">
                <div class="module-header">
                    <div class="module-icon" style="background: var(--info-gradient);">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div>
                        <div class="module-title">Chat IA Avanzado</div>
                        <div class="module-description">
                            Conversaciones inteligentes con IA avanzada
                        </div>
                    </div>
                </div>
                <ul class="module-features">
                    <li>Conversaciones ilimitadas</li>
                    <li>IA especializada</li>
                    <li>Múltiples personalidades</li>
                    <li>Análisis avanzado</li>
                </ul>
            </div>
            <?php else: ?>
            <div class="module-card premium locked" onclick="showMembershipModal('Chat IA Avanzado', 'OPERATIVE')">
                <div class="module-header">
                    <div class="module-icon" style="background: var(--info-gradient);">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div>
                        <div class="module-title">Chat IA Avanzado</div>
                        <div class="module-description">
                            Requiere plan Operative o superior
                        </div>
                    </div>
                </div>
                <ul class="module-features">
                    <li>Conversaciones ilimitadas</li>
                    <li>IA especializada</li>
                    <li>Múltiples personalidades</li>
                    <li>Análisis avanzado</li>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Funciones de nivel superior -->
            <?php if (checkFeatureAccess('quantum_optimization', $user_membership_level)): ?>
            <div class="module-card premium" onclick="navigateToModule('quantum_encryption')">
                <div class="module-header">
                    <div class="module-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);">
                        <i class="fas fa-atom"></i>
                    </div>
                    <div>
                        <div class="module-title">Optimización Cuántica</div>
                        <div class="module-description">
                            Tecnología cuántica avanzada
                        </div>
                    </div>
                </div>
                <ul class="module-features">
                    <li>Encriptación cuántica</li>
                    <li>Optimización avanzada</li>
                    <li>Resistencia cuántica</li>
                    <li>Análisis predictivo</li>
                </ul>
            </div>
            <?php else: ?>
            <div class="module-card premium locked" onclick="showMembershipModal('Optimización Cuántica', 'TACTICAL_PRO')">
                <div class="module-header">
                    <div class="module-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);">
                        <i class="fas fa-atom"></i>
                    </div>
                    <div>
                        <div class="module-title">Optimización Cuántica</div>
                        <div class="module-description">
                            Requiere plan Tactical Pro o superior
                        </div>
                    </div>
                </div>
                <ul class="module-features">
                    <li>Encriptación cuántica</li>
                    <li>Optimización avanzada</li>
                    <li>Resistencia cuántica</li>
                    <li>Análisis predictivo</li>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Promoción de upgrade si es usuario gratuito -->
            <?php if ($user_membership_level === 'RECRUIT'): ?>
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
                    <li>Chat IA ilimitado</li>
                    <li>Protección cuántica</li>
                    <li>Optimización avanzada</li>
                    <li>Soporte prioritario</li>
                </ul>
            </div>
            <?php endif; ?>
        </div>

        <!-- Action Cards -->
        <div class="action-grid">
            <div class="action-card">
                <div class="action-header">
                    <div class="action-icon" style="background: var(--success-gradient);">
                        <i class="fas fa-search"></i>
                    </div>
                    <div>
                        <div class="action-title">Escaneo Rápido</div>
                        <div class="action-description">
                            Analiza tu sistema en busca de amenazas básicas
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
                        <div class="action-title">Optimización Básica</div>
                        <div class="action-description">
                            Mejora básica del rendimiento de tu sistema
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
                    <div class="action-icon" style="background: var(--danger-gradient);">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <div>
                        <div class="action-title">Centro de Amenazas</div>
                        <div class="action-description">
                            Monitorea amenazas en tiempo real
                        </div>
                    </div>
                </div>
                <button class="action-button" onclick="window.location.href='threat_center.php'" style="background: var(--danger-gradient);">
                    <i class="fas fa-exclamation-triangle"></i>
                    Ir al Centro
                </button>
            </div>

            <?php if (checkFeatureAccess('quantum_optimization', $user_membership_level)): ?>
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
        <button class="quick-action-btn scan" onclick="quickScan()" title="Escaneo Rápido">
            <i class="fas fa-search"></i>
        </button>
        <button class="quick-action-btn optimize" onclick="autoOptimize()" title="Optimización Rápida">
            <i class="fas fa-bolt"></i>
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
            <p class="modal-description" id="modalDescription">
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
            membershipLevel: '<?php echo $user_membership_level; ?>',
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
                showToast('¡Bienvenido a GuardianIA! Tu sistema está protegido.', 'success');
            }, 1000);
        }

        // Navegación a módulos
        function navigateToModule(module) {
            // Verificar si el módulo requiere premium
            const premiumModules = ['user_permissions', 'user_assistant', 'chatbot', 'quantum_encryption'];
            
            if (premiumModules.includes(module) && !userConfig.isPremium) {
                showMembershipModal(module, 'OPERATIVE');
                return;
            }
            
            // Redirigir al módulo
            window.location.href = module + '.php';
        }

        // Mostrar modal de membresía
        function showMembershipModal(featureName, requiredLevel) {
            const modal = document.getElementById('membershipModal');
            const description = document.getElementById('modalDescription');
            
            const levelNames = {
                'OPERATIVE': 'Operative',
                'TACTICAL_PRO': 'Tactical Pro',
                'CYBER_COMMAND': 'Cyber Command',
                'QUANTUM_ELITE': 'Quantum Elite'
            };
            
            description.innerHTML = `
                La función <strong>${featureName}</strong> requiere un plan <strong>${levelNames[requiredLevel] || requiredLevel}</strong> o superior.<br><br>
                Tu plan actual: <strong>${userConfig.membershipLevel}</strong><br><br>
                Actualiza tu membresía para acceder a esta y otras funciones avanzadas.
            `;
            
            modal.classList.add('show');
        }

        // Cerrar modal de membresía
        function closeMembershipModal() {
            const modal = document.getElementById('membershipModal');
            modal.classList.remove('show');
        }

        // Escaneo rápido
        function quickScan() {
            if (isScanning) return;
            
            isScanning = true;
            showToast('Iniciando escaneo de seguridad...', 'info');
            
            // Simular escaneo
            setTimeout(() => {
                showToast('Escaneo completado. Sistema limpio.', 'success');
                isScanning = false;
            }, 3000);
        }

        // Optimización automática
        function autoOptimize() {
            if (isOptimizing) return;
            
            isOptimizing = true;
            showToast('Iniciando optimización del sistema...', 'info');
            
            // Simular optimización
            setTimeout(() => {
                const spaceFreed = Math.floor(Math.random() * 5) + 1;
                showToast(`Optimización completada. ${spaceFreed} GB liberados.`, 'success');
                isOptimizing = false;
            }, 4000);
        }

        // Optimización cuántica (solo premium)
        function quantumOptimize() {
            if (!userConfig.isPremium) {
                showMembershipModal('Optimización Cuántica', 'TACTICAL_PRO');
                return;
            }
            
            showToast('Activando optimización cuántica...', 'info');
            
            setTimeout(() => {
                showToast('Optimización cuántica completada. Rendimiento mejorado significativamente.', 'success');
            }, 5000);
        }

        // Mostrar toast
        function showToast(message, type = 'info') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toast-message');
            
            toastMessage.textContent = message;
            toast.className = `toast ${type} show`;
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 5000);
        }

        // Actualizar métricas del sistema
        function updateSystemMetrics() {
            // Simular actualización de métricas
            const metrics = document.querySelectorAll('.metric-fill');
            metrics.forEach(metric => {
                const currentWidth = parseInt(metric.style.width);
                const variation = Math.floor(Math.random() * 10) - 5;
                const newWidth = Math.max(20, Math.min(95, currentWidth + variation));
                metric.style.width = newWidth + '%';
            });
        }

        // Verificar salud del sistema
        function checkSystemHealth() {
            // Lógica de verificación de salud
            console.log('Verificando salud del sistema...');
        }

        // Iniciar actualizaciones en tiempo real
        function startRealTimeUpdates() {
            updateInterval = setInterval(() => {
                updateSystemMetrics();
            }, 30000); // Actualizar cada 30 segundos
        }

        // Cerrar modal al hacer clic fuera
        document.addEventListener('click', function(e) {
            const modal = document.getElementById('membershipModal');
            if (e.target === modal) {
                closeMembershipModal();
            }
        });
    </script>
</body>
</html>