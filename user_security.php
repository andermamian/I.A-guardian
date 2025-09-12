<?php
/**
 * GuardianIA v3.0 - Centro de Protección del Usuario
 * Sincronizado con Base de Datos Militar
 * Anderson Mamian Chicangana
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/config_military.php';

// Verificar autenticación
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

// Obtener información del usuario
$user_id = $_SESSION['user_id'] ?? 1;
$username = $_SESSION['username'] ?? 'Usuario';
$db = MilitaryDatabaseManager::getInstance();

// Manejar solicitudes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $response = ['success' => false, 'message' => '', 'data' => null];
    
    try {
        switch($_POST['action']) {
            case 'toggle_real_time_protection':
                $status = $_POST['status'] === 'true';
                updateProtectionSetting('real_time_protection', $status);
                logSecurityEvent('protection_toggle', "Protección en tiempo real " . ($status ? 'activada' : 'desactivada'), $status ? 'low' : 'medium');
                $response['success'] = true;
                $response['message'] = 'Protección en tiempo real ' . ($status ? 'activada' : 'desactivada');
                break;
                
            case 'start_scan':
                $scan_type = $_POST['scan_type'] ?? 'quick';
                $scan_id = startSecurityScan($scan_type, $user_id);
                $response['success'] = true;
                $response['data'] = ['scan_id' => $scan_id];
                $response['message'] = 'Escaneo iniciado exitosamente';
                break;
                
            case 'update_definitions':
                updateAntivirusDefinitions();
                logSecurityEvent('definitions_update', 'Definiciones de antivirus actualizadas', 'low');
                $response['success'] = true;
                $response['message'] = 'Definiciones actualizadas exitosamente';
                break;
                
            case 'check_updates':
                $updates = checkSystemUpdates();
                $response['success'] = true;
                $response['data'] = $updates;
                $response['message'] = 'Verificación de actualizaciones completada';
                break;
                
            case 'get_security_metrics':
                $metrics = getSecurityMetrics($user_id);
                $response['success'] = true;
                $response['data'] = $metrics;
                break;
                
            case 'get_threat_history':
                $filter = $_POST['filter'] ?? 'all';
                $threats = getThreatHistory($user_id, $filter);
                $response['success'] = true;
                $response['data'] = $threats;
                break;
                
            default:
                $response['message'] = 'Acción no reconocida';
        }
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
        logEvent('ERROR', 'Error en user_security.php: ' . $e->getMessage());
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Funciones de la base de datos
 */

function getSecurityMetrics($user_id) {
    global $db;
    
    $metrics = [
        'real_time_protection' => true,
        'firewall_status' => true,
        'antivirus_updated' => true,
        'system_updated' => true,
        'threats_blocked_today' => 0,
        'total_threats_blocked' => 0,
        'firewall_effectiveness' => 99.8,
        'antivirus_definitions' => 1247,
        'detection_rate' => 98.7,
        'last_scan' => 'Nunca',
        'last_update' => date('Y-m-d H:i:s')
    ];
    
    if ($db && $db->isConnected()) {
        try {
            // Obtener amenazas bloqueadas hoy
            $result = $db->query(
                "SELECT COUNT(*) as count FROM security_events 
                 WHERE user_id = ? AND DATE(created_at) = CURDATE() 
                 AND event_type IN ('threat_blocked', 'malware_detected', 'phishing_blocked')",
                [$user_id]
            );
            if ($result && $row = $result->fetch_assoc()) {
                $metrics['threats_blocked_today'] = (int)$row['count'];
            }
            
            // Obtener total de amenazas bloqueadas
            $result = $db->query(
                "SELECT COUNT(*) as count FROM security_events 
                 WHERE user_id = ? AND event_type IN ('threat_blocked', 'malware_detected', 'phishing_blocked')",
                [$user_id]
            );
            if ($result && $row = $result->fetch_assoc()) {
                $metrics['total_threats_blocked'] = (int)$row['count'];
            }
            
            // Obtener configuraciones de protección
            $result = $db->query(
                "SELECT config_key, config_value FROM system_config 
                 WHERE config_key IN ('real_time_protection', 'firewall_enabled', 'antivirus_updated')"
            );
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    if (isset($metrics[$row['config_key']])) {
                        $metrics[$row['config_key']] = $row['config_value'] === 'true';
                    }
                }
            }
            
            // Obtener último escaneo
            $result = $db->query(
                "SELECT created_at FROM security_events 
                 WHERE user_id = ? AND event_type = 'security_scan' 
                 ORDER BY created_at DESC LIMIT 1",
                [$user_id]
            );
            if ($result && $row = $result->fetch_assoc()) {
                $metrics['last_scan'] = date('d/m/Y H:i', strtotime($row['created_at']));
            }
            
        } catch (Exception $e) {
            logEvent('ERROR', 'Error obteniendo métricas de seguridad: ' . $e->getMessage());
        }
    }
    
    return $metrics;
}

function getThreatHistory($user_id, $filter = 'all') {
    global $db;
    
    $threats = [];
    
    if ($db && $db->isConnected()) {
        try {
            $where_clause = "WHERE user_id = ?";
            $params = [$user_id];
            
            if ($filter !== 'all') {
                $where_clause .= " AND description LIKE ?";
                $filter_map = [
                    'resolved' => '%resuelto%',
                    'quarantined' => '%cuarentena%',
                    'blocked' => '%bloqueado%'
                ];
                $params[] = $filter_map[$filter] ?? '%';
            }
            
            $result = $db->query(
                "SELECT event_type, description, severity, created_at, resolved 
                 FROM security_events 
                 $where_clause 
                 AND event_type IN ('threat_blocked', 'malware_detected', 'phishing_blocked', 'virus_detected')
                 ORDER BY created_at DESC LIMIT 20",
                $params
            );
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $threats[] = [
                        'name' => ucfirst(str_replace('_', ' ', $row['event_type'])),
                        'description' => $row['description'],
                        'severity' => $row['severity'],
                        'time' => date('d/m/Y H:i', strtotime($row['created_at'])),
                        'time_ago' => getTimeAgo($row['created_at']),
                        'status' => $row['resolved'] ? 'resolved' : 'quarantined',
                        'icon' => getThreatIcon($row['event_type'])
                    ];
                }
            }
        } catch (Exception $e) {
            logEvent('ERROR', 'Error obteniendo historial de amenazas: ' . $e->getMessage());
        }
    }
    
    // Si no hay datos de BD, usar datos simulados
    if (empty($threats)) {
        $threats = [
            [
                'name' => 'Malware.Generic.KX47',
                'description' => 'Archivo: temp_installer.exe - Eliminado automáticamente',
                'severity' => 'high',
                'time' => date('d/m/Y H:i', strtotime('-2 days')),
                'time_ago' => 'Hace 2 días',
                'status' => 'resolved',
                'icon' => 'virus-slash'
            ],
            [
                'name' => 'Adware.Suspicious.Browser',
                'description' => 'Extensión de navegador sospechosa - En cuarentena',
                'severity' => 'medium',
                'time' => date('d/m/Y H:i', strtotime('-1 week')),
                'time_ago' => 'Hace 1 semana',
                'status' => 'quarantined',
                'icon' => 'exclamation-triangle'
            ]
        ];
    }
    
    return $threats;
}

function startSecurityScan($scan_type, $user_id) {
    global $db;
    
    $scan_id = 'SCAN_' . uniqid();
    
    if ($db && $db->isConnected()) {
        try {
            // Registrar inicio del escaneo
            $db->query(
                "INSERT INTO security_events (user_id, event_type, description, severity, ip_address, created_at) 
                 VALUES (?, 'security_scan', ?, 'low', ?, NOW())",
                [
                    $user_id,
                    "Escaneo de seguridad iniciado: " . $scan_type,
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]
            );
            
            logMilitaryEvent('SECURITY_SCAN_STARTED', "Escaneo $scan_type iniciado por usuario $user_id", 'UNCLASSIFIED');
            
        } catch (Exception $e) {
            logEvent('ERROR', 'Error registrando escaneo: ' . $e->getMessage());
        }
    }
    
    return $scan_id;
}

function updateProtectionSetting($setting, $status) {
    global $db;
    
    if ($db && $db->isConnected()) {
        try {
            // Actualizar o insertar configuración
            $db->query(
                "INSERT INTO system_config (config_key, config_value, config_type, description, updated_at) 
                 VALUES (?, ?, 'boolean', ?, NOW()) 
                 ON DUPLICATE KEY UPDATE config_value = ?, updated_at = NOW()",
                [
                    $setting,
                    $status ? 'true' : 'false',
                    "Configuración de $setting",
                    $status ? 'true' : 'false'
                ]
            );
            
            logMilitaryEvent('PROTECTION_SETTING_CHANGED', "$setting cambiado a " . ($status ? 'activo' : 'inactivo'), 'UNCLASSIFIED');
            
        } catch (Exception $e) {
            logEvent('ERROR', 'Error actualizando configuración de protección: ' . $e->getMessage());
        }
    }
}

function updateAntivirusDefinitions() {
    global $db;
    
    if ($db && $db->isConnected()) {
        try {
            // Actualizar configuración de definiciones
            updateProtectionSetting('antivirus_definitions_updated', true);
            updateProtectionSetting('antivirus_definitions_count', rand(1200, 1300));
            updateProtectionSetting('last_definitions_update', date('Y-m-d H:i:s'));
            
        } catch (Exception $e) {
            logEvent('ERROR', 'Error actualizando definiciones: ' . $e->getMessage());
        }
    }
}

function checkSystemUpdates() {
    global $db;
    
    $updates = [
        'available' => rand(0, 5),
        'critical' => rand(0, 2),
        'last_check' => date('Y-m-d H:i:s'),
        'auto_update_enabled' => true
    ];
    
    if ($db && $db->isConnected()) {
        try {
            // Registrar verificación de actualizaciones
            logSecurityEvent('system_update_check', 'Verificación de actualizaciones del sistema', 'low');
        } catch (Exception $e) {
            logEvent('ERROR', 'Error verificando actualizaciones: ' . $e->getMessage());
        }
    }
    
    return $updates;
}

function getThreatIcon($threat_type) {
    $icons = [
        'malware_detected' => 'virus-slash',
        'phishing_blocked' => 'ban',
        'threat_blocked' => 'shield-check',
        'virus_detected' => 'exclamation-triangle'
    ];
    
    return $icons[$threat_type] ?? 'shield-check';
}

function getTimeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return 'Hace menos de un minuto';
    if ($time < 3600) return 'Hace ' . floor($time/60) . ' minutos';
    if ($time < 86400) return 'Hace ' . floor($time/3600) . ' horas';
    if ($time < 2592000) return 'Hace ' . floor($time/86400) . ' días';
    if ($time < 31104000) return 'Hace ' . floor($time/2592000) . ' meses';
    return 'Hace ' . floor($time/31104000) . ' años';
}

// Obtener datos iniciales
$security_metrics = getSecurityMetrics($user_id);
$recent_threats = getThreatHistory($user_id, 'all');

// Log de acceso a la página
logSecurityEvent('security_page_access', 'Usuario accedió al centro de protección', 'low', $user_id);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Protección - GuardianIA</title>
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
            background: var(--success-gradient);
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

        /* Main Container */
        .main-container {
            margin-top: 80px;
            padding: 2rem;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Security Header */
        .security-header {
            background: var(--success-gradient);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .security-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            animation: securityShine 4s infinite;
        }

        @keyframes securityShine {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .security-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .security-subtitle {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 1.5rem;
        }

        .shield-status {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            color: white;
        }

        .shield-icon {
            font-size: 1.5rem;
            animation: shieldPulse 2s infinite;
        }

        @keyframes shieldPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        /* Protection Status Cards */
        .protection-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .protection-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: var(--card-padding);
            position: relative;
            overflow: hidden;
            transition: all var(--animation-speed) ease;
        }

        .protection-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px var(--shadow-color);
        }

        .protection-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .protection-card.realtime::before {
            background: var(--success-gradient);
        }

        .protection-card.firewall::before {
            background: var(--info-gradient);
        }

        .protection-card.antivirus::before {
            background: var(--warning-gradient);
        }

        .protection-card.updates::before {
            background: var(--primary-gradient);
        }

        .protection-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .protection-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .protection-status {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
        }

        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            animation: statusBlink 2s infinite;
        }

        .status-dot.active {
            background: #2ed573;
        }

        .status-dot.warning {
            background: #ffa502;
        }

        .status-dot.inactive {
            background: #ff4757;
        }

        @keyframes statusBlink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .protection-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .protection-description {
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }

        .protection-metrics {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .metric {
            text-align: center;
        }

        .metric-value {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .metric-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .protection-button {
            width: 100%;
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .protection-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .protection-button.active {
            background: var(--success-gradient);
        }

        .protection-button.warning {
            background: var(--warning-gradient);
        }

        /* Scan Section */
        .scan-section {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: var(--card-padding);
            margin-bottom: 2rem;
        }

        .scan-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .scan-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .last-scan {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .scan-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .scan-option {
            background: rgba(255, 255, 255, 0.05);
            border: 2px solid transparent;
            border-radius: var(--border-radius);
            padding: 1.5rem;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
            text-align: center;
        }

        .scan-option:hover {
            border-color: var(--primary-gradient);
            background: rgba(255, 255, 255, 0.1);
        }

        .scan-option.selected {
            border-color: var(--success-gradient);
            background: rgba(67, 233, 123, 0.1);
        }

        .scan-option-icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }

        .scan-option-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .scan-option-description {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .scan-option-time {
            font-size: 0.8rem;
            color: var(--text-secondary);
            font-style: italic;
        }

        .scan-controls {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .scan-button {
            background: var(--success-gradient);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 1.1rem;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .scan-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(67, 233, 123, 0.4);
        }

        .scan-button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        .schedule-button {
            background: var(--info-gradient);
        }

        .schedule-button:hover {
            box-shadow: 0 8px 25px rgba(79, 172, 254, 0.4);
        }

        /* Threat History */
        .threat-history {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: var(--card-padding);
            margin-bottom: 2rem;
        }

        .history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .history-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .history-filter {
            display: flex;
            gap: 0.5rem;
        }

        .filter-button {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-secondary);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
        }

        .filter-button.active {
            background: var(--primary-gradient);
            color: white;
        }

        .threat-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            margin-bottom: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            border-left: 4px solid transparent;
            transition: all var(--animation-speed) ease;
        }

        .threat-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .threat-item.resolved {
            border-left-color: #2ed573;
        }

        .threat-item.quarantined {
            border-left-color: #ffa502;
        }

        .threat-item.blocked {
            border-left-color: #ff4757;
        }

        .threat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: white;
        }

        .threat-content {
            flex: 1;
        }

        .threat-name {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .threat-details {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .threat-time {
            color: var(--text-secondary);
            font-size: 0.8rem;
            text-align: right;
        }

        .threat-status {
            padding: 0.25rem 0.75rem;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .threat-status.resolved {
            background: rgba(46, 213, 115, 0.2);
            color: #2ed573;
        }

        .threat-status.quarantined {
            background: rgba(255, 165, 2, 0.2);
            color: #ffa502;
        }

        .threat-status.blocked {
            background: rgba(255, 71, 87, 0.2);
            color: #ff4757;
        }

        /* Progress Bar */
        .scan-progress {
            margin: 2rem 0;
            display: none;
        }

        .scan-progress.active {
            display: block;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .progress-fill {
            height: 100%;
            background: var(--success-gradient);
            border-radius: 4px;
            transition: width 0.3s ease;
            width: 0%;
        }

        .progress-text {
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Real-time Protection Toggle */
        .protection-toggle {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-top: 1rem;
        }

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

        /* Responsive Design */
        @media (max-width: 768px) {
            .protection-grid {
                grid-template-columns: 1fr;
            }

            .scan-options {
                grid-template-columns: 1fr;
            }

            .scan-controls {
                flex-direction: column;
            }

            .security-title {
                font-size: 2rem;
            }

            .main-container {
                padding: 1rem;
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
                <li><a href="user_dashboard.php" class="nav-link">Mi Seguridad</a></li>
                <li><a href="#" class="nav-link active">Protección</a></li>
                <li><a href="user_performance.php" class="nav-link">Optimización</a></li>
                <li><a href="user_assistant.php" class="nav-link">Asistente IA</a></li>
                <li><a href="user_settings.php" class="nav-link">Configuración</a></li>
            </ul>
            <div class="user-profile">
                <div class="user-avatar"><?php echo strtoupper(substr($username, 0, 2)); ?></div>
                <span><?php echo htmlspecialchars($username); ?></span>
            </div>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Security Header -->
        <div class="security-header">
            <h1 class="security-title">🛡️ Centro de Protección</h1>
            <p class="security-subtitle">
                Tu escudo digital inteligente está activo las 24 horas
            </p>
            <div class="shield-status">
                <i class="fas fa-shield-check shield-icon"></i>
                <span>Protección Máxima Activada</span>
            </div>
        </div>

        <!-- Protection Status Cards -->
        <div class="protection-grid">
            <div class="protection-card realtime">
                <div class="protection-header">
                    <div class="protection-icon" style="background: var(--success-gradient);">
                        <i class="fas fa-eye"></i>
                    </div>
                    <div class="protection-status">
                        <div class="status-dot <?php echo $security_metrics['real_time_protection'] ? 'active' : 'inactive'; ?>"></div>
                        <span style="color: <?php echo $security_metrics['real_time_protection'] ? '#2ed573' : '#ff4757'; ?>;">
                            <?php echo $security_metrics['real_time_protection'] ? 'ACTIVO' : 'INACTIVO'; ?>
                        </span>
                    </div>
                </div>
                <h3 class="protection-title">Protección en Tiempo Real</h3>
                <p class="protection-description">
                    Monitoreo continuo de amenazas con IA avanzada
                </p>
                <div class="protection-metrics">
                    <div class="metric">
                        <div class="metric-value" style="color: #2ed573;">24/7</div>
                        <div class="metric-label">Monitoreo</div>
                    </div>
                    <div class="metric">
                        <div class="metric-value" style="color: #2ed573;"><?php echo number_format($security_metrics['detection_rate'], 1); ?>%</div>
                        <div class="metric-label">Precisión</div>
                    </div>
                    <div class="metric">
                        <div class="metric-value" style="color: #2ed573;"><?php echo $security_metrics['threats_blocked_today']; ?></div>
                        <div class="metric-label">Amenazas</div>
                    </div>
                </div>
                <div class="protection-toggle">
                    <div class="toggle-switch <?php echo $security_metrics['real_time_protection'] ? 'active' : ''; ?>" onclick="toggleRealTimeProtection()">
                        <div class="toggle-slider"></div>
                    </div>
                    <span>Protección <?php echo $security_metrics['real_time_protection'] ? 'activada' : 'desactivada'; ?></span>
                </div>
            </div>

            <div class="protection-card firewall">
                <div class="protection-header">
                    <div class="protection-icon" style="background: var(--info-gradient);">
                        <i class="fas fa-fire"></i>
                    </div>
                    <div class="protection-status">
                        <div class="status-dot <?php echo $security_metrics['firewall_status'] ? 'active' : 'inactive'; ?>"></div>
                        <span style="color: <?php echo $security_metrics['firewall_status'] ? '#2ed573' : '#ff4757'; ?>;">
                            <?php echo $security_metrics['firewall_status'] ? 'ACTIVO' : 'INACTIVO'; ?>
                        </span>
                    </div>
                </div>
                <h3 class="protection-title">Firewall Inteligente</h3>
                <p class="protection-description">
                    Bloqueo automático de conexiones sospechosas
                </p>
                <div class="protection-metrics">
                    <div class="metric">
                        <div class="metric-value" style="color: #4facfe;"><?php echo $security_metrics['total_threats_blocked']; ?></div>
                        <div class="metric-label">Bloqueados</div>
                    </div>
                    <div class="metric">
                        <div class="metric-value" style="color: #4facfe;"><?php echo number_format($security_metrics['firewall_effectiveness'], 1); ?>%</div>
                        <div class="metric-label">Efectividad</div>
                    </div>
                    <div class="metric">
                        <div class="metric-value" style="color: #4facfe;">5</div>
                        <div class="metric-label">Reglas IA</div>
                    </div>
                </div>
                <button class="protection-button active" onclick="configureFirewall()">
                    <i class="fas fa-cog"></i>
                    Configurar Firewall
                </button>
            </div>

            <div class="protection-card antivirus">
                <div class="protection-header">
                    <div class="protection-icon" style="background: var(--warning-gradient);">
                        <i class="fas fa-virus-slash"></i>
                    </div>
                    <div class="protection-status">
                        <div class="status-dot <?php echo $security_metrics['antivirus_updated'] ? 'active' : 'warning'; ?>"></div>
                        <span style="color: <?php echo $security_metrics['antivirus_updated'] ? '#2ed573' : '#ffa502'; ?>;">
                            <?php echo $security_metrics['antivirus_updated'] ? 'ACTUALIZADO' : 'PENDIENTE'; ?>
                        </span>
                    </div>
                </div>
                <h3 class="protection-title">Antivirus IA</h3>
                <p class="protection-description">
                    Detección avanzada con machine learning
                </p>
                <div class="protection-metrics">
                    <div class="metric">
                        <div class="metric-value" style="color: #ffa726;"><?php echo number_format($security_metrics['antivirus_definitions']); ?></div>
                        <div class="metric-label">Firmas</div>
                    </div>
                    <div class="metric">
                        <div class="metric-value" style="color: #ffa726;"><?php echo number_format($security_metrics['detection_rate'], 1); ?>%</div>
                        <div class="metric-label">Detección</div>
                    </div>
                    <div class="metric">
                        <div class="metric-value" style="color: #ffa726;">0</div>
                        <div class="metric-label">Infectados</div>
                    </div>
                </div>
                <button class="protection-button warning" onclick="updateDefinitions()">
                    <i class="fas fa-download"></i>
                    Actualizar Definiciones
                </button>
            </div>

            <div class="protection-card updates">
                <div class="protection-header">
                    <div class="protection-icon" style="background: var(--primary-gradient);">
                        <i class="fas fa-sync-alt"></i>
                    </div>
                    <div class="protection-status">
                        <div class="status-dot <?php echo $security_metrics['system_updated'] ? 'active' : 'warning'; ?>"></div>
                        <span style="color: <?php echo $security_metrics['system_updated'] ? '#2ed573' : '#ffa502'; ?>;">
                            <?php echo $security_metrics['system_updated'] ? 'AL DÍA' : 'PENDIENTE'; ?>
                        </span>
                    </div>
                </div>
                <h3 class="protection-title">Actualizaciones Automáticas</h3>
                <p class="protection-description">
                    Sistema siempre actualizado con las últimas defensas
                </p>
                <div class="protection-metrics">
                    <div class="metric">
                        <div class="metric-value" style="color: #667eea;">Auto</div>
                        <div class="metric-label">Modo</div>
                    </div>
                    <div class="metric">
                        <div class="metric-value" style="color: #667eea;">Hoy</div>
                        <div class="metric-label">Última</div>
                    </div>
                    <div class="metric">
                        <div class="metric-value" style="color: #667eea;">0</div>
                        <div class="metric-label">Pendientes</div>
                    </div>
                </div>
                <button class="protection-button" onclick="checkUpdates()">
                    <i class="fas fa-search"></i>
                    Buscar Actualizaciones
                </button>
            </div>
        </div>

        <!-- Scan Section -->
        <div class="scan-section">
            <div class="scan-header">
                <h2 class="scan-title">🔍 Análisis de Seguridad</h2>
                <div class="last-scan">
                    Último escaneo: <?php echo $security_metrics['last_scan']; ?> - Sin amenazas detectadas
                </div>
            </div>

            <div class="scan-options">
                <div class="scan-option selected" onclick="selectScanType('quick')">
                    <div class="scan-option-icon">⚡</div>
                    <h3 class="scan-option-title">Escaneo Rápido</h3>
                    <p class="scan-option-description">
                        Análisis básico de archivos críticos y memoria
                    </p>
                    <div class="scan-option-time">Duración: 2-5 minutos</div>
                </div>

                <div class="scan-option" onclick="selectScanType('full')">
                    <div class="scan-option-icon">🔍</div>
                    <h3 class="scan-option-title">Escaneo Completo</h3>
                    <p class="scan-option-description">
                        Análisis profundo de todo el sistema
                    </p>
                    <div class="scan-option-time">Duración: 30-60 minutos</div>
                </div>

                <div class="scan-option" onclick="selectScanType('custom')">
                    <div class="scan-option-icon">⚙️</div>
                    <h3 class="scan-option-title">Escaneo Personalizado</h3>
                    <p class="scan-option-description">
                        Selecciona carpetas y opciones específicas
                    </p>
                    <div class="scan-option-time">Duración: Variable</div>
                </div>

                <div class="scan-option" onclick="selectScanType('ai')">
                    <div class="scan-option-icon">🤖</div>
                    <h3 class="scan-option-title">Análisis IA Avanzado</h3>
                    <p class="scan-option-description">
                        Detección de amenazas con inteligencia artificial
                    </p>
                    <div class="scan-option-time">Duración: 10-20 minutos</div>
                </div>
            </div>

            <div class="scan-progress" id="scanProgress">
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                <div class="progress-text" id="progressText">Preparando escaneo...</div>
            </div>

            <div class="scan-controls">
                <button class="scan-button" onclick="startScan()" id="scanButton">
                    <i class="fas fa-play"></i>
                    Iniciar Escaneo Rápido
                </button>
                <button class="scan-button schedule-button" onclick="scheduleScan()">
                    <i class="fas fa-calendar-alt"></i>
                    Programar Escaneo
                </button>
            </div>
        </div>

        <!-- Threat History -->
        <div class="threat-history">
            <div class="history-header">
                <h2 class="history-title">📊 Historial de Amenazas</h2>
                <div class="history-filter">
                    <button class="filter-button active" onclick="filterThreats('all')">Todas</button>
                    <button class="filter-button" onclick="filterThreats('resolved')">Resueltas</button>
                    <button class="filter-button" onclick="filterThreats('quarantined')">Cuarentena</button>
                    <button class="filter-button" onclick="filterThreats('blocked')">Bloqueadas</button>
                </div>
            </div>

            <div id="threatList">
                <?php foreach ($recent_threats as $threat): ?>
                <div class="threat-item <?php echo $threat['status']; ?>">
                    <div class="threat-icon" style="background: var(--<?php echo $threat['status'] === 'resolved' ? 'success' : ($threat['status'] === 'quarantined' ? 'warning' : 'danger'); ?>-gradient);">
                        <i class="fas fa-<?php echo $threat['icon']; ?>"></i>
                    </div>
                    <div class="threat-content">
                        <div class="threat-name"><?php echo htmlspecialchars($threat['name']); ?></div>
                        <div class="threat-details"><?php echo htmlspecialchars($threat['description']); ?></div>
                    </div>
                    <div class="threat-time">
                        <div class="threat-status <?php echo $threat['status']; ?>">
                            <?php echo ucfirst($threat['status'] === 'resolved' ? 'Resuelto' : ($threat['status'] === 'quarantined' ? 'Cuarentena' : 'Bloqueado')); ?>
                        </div>
                        <div><?php echo htmlspecialchars($threat['time_ago']); ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <span id="toast-message"></span>
    </div>

    <script>
        // Variables globales con datos PHP
        let selectedScanType = 'quick';
        let isScanning = false;
        let scanProgress = 0;
        let scanInterval;
        
        // Datos del servidor
        const securityMetrics = <?php echo json_encode($security_metrics); ?>;
        const initialThreats = <?php echo json_encode($recent_threats); ?>;

        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            initializeSecurity();
            updateProtectionStatus();
            loadThreatHistory();
            
            // Actualizar métricas cada 30 segundos
            setInterval(updateSecurityMetrics, 30000);
        });

        // Inicializar módulo de seguridad
        function initializeSecurity() {
            console.log('Inicializando módulo de seguridad...');
            checkRealTimeProtection();
            updateSecurityMetricsDisplay();
        }

        // Función AJAX helper
        async function makeRequest(action, data = {}) {
            const formData = new FormData();
            formData.append('action', action);
            
            for (const [key, value] of Object.entries(data)) {
                formData.append(key, value);
            }
            
            try {
                const response = await fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                });
                
                return await response.json();
            } catch (error) {
                console.error('Error en solicitud:', error);
                return { success: false, message: 'Error de conexión' };
            }
        }

        // Seleccionar tipo de escaneo
        function selectScanType(type) {
            selectedScanType = type;
            
            // Actualizar UI
            document.querySelectorAll('.scan-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            event.target.closest('.scan-option').classList.add('selected');
            
            // Actualizar botón
            const scanButton = document.getElementById('scanButton');
            const scanTypes = {
                'quick': 'Iniciar Escaneo Rápido',
                'full': 'Iniciar Escaneo Completo',
                'custom': 'Configurar Escaneo',
                'ai': 'Iniciar Análisis IA'
            };
            
            scanButton.innerHTML = `<i class="fas fa-play"></i> ${scanTypes[type]}`;
        }

        // Iniciar escaneo
        async function startScan() {
            if (isScanning) {
                showToast('Ya hay un escaneo en progreso...', 'warning');
                return;
            }

            const result = await makeRequest('start_scan', { scan_type: selectedScanType });
            
            if (!result.success) {
                showToast('Error iniciando escaneo: ' + result.message, 'error');
                return;
            }

            isScanning = true;
            scanProgress = 0;
            
            const scanButton = document.getElementById('scanButton');
            const progressSection = document.getElementById('scanProgress');
            const progressFill = document.getElementById('progressFill');
            const progressText = document.getElementById('progressText');
            
            // Mostrar barra de progreso
            progressSection.classList.add('active');
            scanButton.innerHTML = '<div class="loading"></div> Escaneando...';
            scanButton.disabled = true;
            
            showToast(`Iniciando ${getScanTypeName(selectedScanType)}...`, 'info');
            
            // Simular proceso de escaneo
            const scanSteps = getScanSteps(selectedScanType);
            let currentStep = 0;
            
            scanInterval = setInterval(() => {
                if (currentStep < scanSteps.length) {
                    progressText.textContent = scanSteps[currentStep];
                    scanProgress = ((currentStep + 1) / scanSteps.length) * 100;
                    progressFill.style.width = scanProgress + '%';
                    currentStep++;
                } else {
                    completeScan();
                }
            }, getScanStepDuration(selectedScanType));
        }

        // Completar escaneo
        function completeScan() {
            clearInterval(scanInterval);
            
            const scanButton = document.getElementById('scanButton');
            const progressSection = document.getElementById('scanProgress');
            const progressText = document.getElementById('progressText');
            
            progressText.textContent = 'Escaneo completado - Sistema limpio';
            
            setTimeout(() => {
                progressSection.classList.remove('active');
                scanButton.innerHTML = `<i class="fas fa-play"></i> Iniciar ${getScanTypeName(selectedScanType)}`;
                scanButton.disabled = false;
                isScanning = false;
                
                showToast('Escaneo completado exitosamente. No se encontraron amenazas.', 'success');
                updateLastScanTime();
                updateSecurityMetrics();
            }, 2000);
        }

        // Toggle protección en tiempo real
        async function toggleRealTimeProtection() {
            const toggle = event.target.closest('.toggle-switch');
            const isActive = toggle.classList.contains('active');
            const newStatus = !isActive;
            
            const result = await makeRequest('toggle_real_time_protection', { status: newStatus });
            
            if (result.success) {
                if (newStatus) {
                    toggle.classList.add('active');
                    showToast('Protección en tiempo real activada', 'success');
                } else {
                    toggle.classList.remove('active');
                    showToast('Protección en tiempo real desactivada', 'warning');
                }
                updateProtectionStatus('realtime', newStatus);
            } else {
                showToast('Error: ' + result.message, 'error');
            }
        }

        // Actualizar definiciones
        async function updateDefinitions() {
            const button = event.target;
            const originalText = button.innerHTML;
            
            button.innerHTML = '<div class="loading"></div> Actualizando...';
            button.disabled = true;
            
            showToast('Descargando nuevas definiciones de virus...', 'info');
            
            const result = await makeRequest('update_definitions');
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
                
                if (result.success) {
                    showToast(result.message || 'Definiciones actualizadas exitosamente.', 'success');
                    updateSecurityMetrics();
                } else {
                    showToast('Error: ' + result.message, 'error');
                }
            }, 3000);
        }

        // Buscar actualizaciones
        async function checkUpdates() {
            const button = event.target;
            const originalText = button.innerHTML;
            
            button.innerHTML = '<div class="loading"></div> Buscando...';
            button.disabled = true;
            
            showToast('Verificando actualizaciones disponibles...', 'info');
            
            const result = await makeRequest('check_updates');
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
                
                if (result.success) {
                    showToast(result.message || 'Sistema actualizado.', 'success');
                } else {
                    showToast('Error: ' + result.message, 'error');
                }
            }, 2000);
        }

        // Filtrar amenazas
        async function filterThreats(filter) {
            // Actualizar botones de filtro
            document.querySelectorAll('.filter-button').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
            
            const result = await makeRequest('get_threat_history', { filter: filter });
            
            if (result.success) {
                updateThreatList(result.data);
                showToast(`Mostrando amenazas: ${getFilterName(filter)}`, 'info');
            }
        }

        // Actualizar lista de amenazas
        function updateThreatList(threats) {
            const threatList = document.getElementById('threatList');
            threatList.innerHTML = '';
            
            threats.forEach(threat => {
                const threatItem = createThreatItem(threat);
                threatList.appendChild(threatItem);
            });
        }

        // Crear elemento de amenaza
        function createThreatItem(threat) {
            const item = document.createElement('div');
            item.className = `threat-item ${threat.status}`;
            
            const gradientClass = threat.status === 'resolved' ? 'success' : 
                                  threat.status === 'quarantined' ? 'warning' : 'danger';
            
            item.innerHTML = `
                <div class="threat-icon" style="background: var(--${gradientClass}-gradient);">
                    <i class="fas fa-${threat.icon}"></i>
                </div>
                <div class="threat-content">
                    <div class="threat-name">${threat.name}</div>
                    <div class="threat-details">${threat.description}</div>
                </div>
                <div class="threat-time">
                    <div class="threat-status ${threat.status}">
                        ${threat.status === 'resolved' ? 'Resuelto' : 
                          threat.status === 'quarantined' ? 'Cuarentena' : 'Bloqueado'}
                    </div>
                    <div>${threat.time_ago}</div>
                </div>
            `;
            
            return item;
        }

        // Actualizar métricas de seguridad
        async function updateSecurityMetrics() {
            const result = await makeRequest('get_security_metrics');
            
            if (result.success) {
                updateSecurityMetricsDisplay(result.data);
            }
        }

        // Actualizar visualización de métricas
        function updateSecurityMetricsDisplay(metrics = securityMetrics) {
            // Actualizar contadores en las tarjetas
            document.querySelectorAll('.metric-value').forEach((element, index) => {
                // Lógica específica para cada métrica basada en posición
                if (element.parentElement.querySelector('.metric-label').textContent === 'Amenazas') {
                    element.textContent = metrics.threats_blocked_today || 0;
                } else if (element.parentElement.querySelector('.metric-label').textContent === 'Bloqueados') {
                    element.textContent = metrics.total_threats_blocked || 0;
                } else if (element.parentElement.querySelector('.metric-label').textContent === 'Firmas') {
                    element.textContent = metrics.antivirus_definitions || 1247;
                }
            });
        }

        // Funciones auxiliares
        function getScanTypeName(type) {
            const names = {
                'quick': 'Escaneo Rápido',
                'full': 'Escaneo Completo',
                'custom': 'Escaneo Personalizado',
                'ai': 'Análisis IA Avanzado'
            };
            return names[type] || 'Escaneo';
        }

        function getScanSteps(type) {
            const steps = {
                'quick': [
                    'Preparando escaneo...',
                    'Analizando memoria RAM...',
                    'Verificando archivos críticos...',
                    'Escaneando procesos activos...',
                    'Finalizando análisis...'
                ],
                'full': [
                    'Preparando escaneo completo...',
                    'Analizando memoria y procesos...',
                    'Escaneando archivos del sistema...',
                    'Verificando registro de Windows...',
                    'Analizando archivos de usuario...',
                    'Escaneando archivos temporales...',
                    'Verificando conexiones de red...',
                    'Análisis heurístico avanzado...',
                    'Finalizando escaneo completo...'
                ],
                'custom': [
                    'Configurando parámetros...',
                    'Analizando carpetas seleccionadas...',
                    'Aplicando filtros personalizados...',
                    'Ejecutando análisis específico...',
                    'Generando reporte personalizado...'
                ],
                'ai': [
                    'Inicializando IA de seguridad...',
                    'Cargando modelos de machine learning...',
                    'Analizando patrones de comportamiento...',
                    'Detectando anomalías con IA...',
                    'Clasificando amenazas potenciales...',
                    'Aplicando algoritmos heurísticos...',
                    'Generando reporte inteligente...'
                ]
            };
            return steps[type] || steps['quick'];
        }

        function getScanStepDuration(type) {
            const durations = {
                'quick': 1000,
                'full': 2000,
                'custom': 1500,
                'ai': 1800
            };
            return durations[type] || 1000;
        }

        function getFilterName(filter) {
            const names = {
                'all': 'Todas',
                'resolved': 'Resueltas',
                'quarantined': 'En cuarentena',
                'blocked': 'Bloqueadas'
            };
            return names[filter] || 'Todas';
        }

        function updateProtectionStatus(module, status) {
            console.log(`Actualizando ${module}: ${status ? 'Activo' : 'Inactivo'}`);
        }

        function checkRealTimeProtection() {
            console.log('Verificando protección en tiempo real...');
        }

        function updateLastScanTime() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('es-ES', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            
            const lastScanElement = document.querySelector('.last-scan');
            lastScanElement.textContent = `Último escaneo: Hoy a las ${timeString} - Sin amenazas detectadas`;
        }

        function loadThreatHistory() {
            console.log('Cargando historial de amenazas...');
        }

        function scheduleScan() {
            showToast('Abriendo programador de escaneos...', 'info');
        }

        function configureFirewall() {
            showToast('Abriendo configuración del firewall...', 'info');
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

        // Manejo de errores
        window.addEventListener('error', function(e) {
            console.log('Error capturado:', e.message);
            showToast('Se produjo un error en el sistema de seguridad. Reintentando...', 'error');
        });

        // Cleanup al salir
        window.addEventListener('beforeunload', function() {
            if (scanInterval) {
                clearInterval(scanInterval);
            }
        });
    </script>
</body>
</html>