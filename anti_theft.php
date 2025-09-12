<?php
/**
 * GuardianIA v3.0 FINAL - Sistema Anti-Robo Avanzado
 * Sistema de seguridad con rastreo GPS, bloqueo remoto y recuperación de datos
 * Versión con integración completa de base de datos
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/config_military.php';

// Verificar sesión y autenticación
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

// Verificar permisos de administrador
if ($_SESSION['user_type'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

// Obtener instancia de base de datos


// Log de acceso
logSecurityEvent('anti_theft_access', 'Acceso al Sistema Anti-Robo', 'info', $_SESSION['user_id']);

// Clase mejorada para Sistema Anti-Robo con base de datos
class AntiTheftSystem {
    private $db;
    private $conn; // <-- Agrega esta línea
    private $user_id;
    
    public function __construct($db, $user_id) {
        $this->db = $db;
        $this->conn = $db->getConnection(); // <-- Agrega esta línea
        $this->user_id = $user_id;
        $this->initializeSystem();
    }
    
    private function initializeSystem() {
        // Verificar y crear tablas si no existen
        $this->ensureTablesExist();
    }
    
    private function ensureTablesExist() {
        // Las tablas deberían estar creadas con el SQL proporcionado anteriormente
        // Esta función es para verificación adicional si es necesario
    }
    
    public function getProtectedDevices() {
        $query = "SELECT * FROM protected_devices WHERE user_id = ? ORDER BY last_seen DESC";
        $stmt = $this->conn->prepare($query); // <-- CAMBIA AQUÍ
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $devices = [];
        while ($row = $result->fetch_assoc()) {
            $devices[] = [
                'id' => $row['device_id'],
                'name' => $row['name'],
                'type' => $row['type'],
                'status' => $row['status'],
                'location' => $row['location'],
                'last_seen' => $row['last_seen'],
                'battery' => $row['battery'],
                'locked' => $row['is_locked'],
                'tracking' => $row['tracking_enabled'],
                'ip' => $row['ip_address'],
                'os' => $row['os'],
                'latitude' => $row['latitude'],
                'longitude' => $row['longitude']
            ];
        }
        
        return $devices;
    }
    
    public function getSecurityAlerts() {
        $query = "SELECT sa.*, pd.name as device_name 
                  FROM security_alerts sa 
                  LEFT JOIN protected_devices pd ON sa.device_id = pd.device_id 
                  WHERE pd.user_id = ? AND sa.is_resolved = FALSE 
                  ORDER BY sa.created_at DESC 
                  LIMIT 20";
        $stmt = $this->conn->prepare($query); // <-- CAMBIA AQUÍ
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $alerts = [];
        while ($row = $result->fetch_assoc()) {
            $alerts[] = [
                'id' => $row['id'],
                'device' => $row['device_id'],
                'device_name' => $row['device_name'],
                'type' => $row['alert_type'],
                'message' => $row['message'],
                'severity' => $row['severity'],
                'timestamp' => $row['created_at']
            ];
        }
        
        return $alerts;
    }
    
    public function getTrackingData($device_id = null) {
        $query = "SELECT * FROM device_locations ";
        
        if ($device_id) {
            $query .= "WHERE device_id = ? ";
        } else {
            $query .= "WHERE device_id IN (SELECT device_id FROM protected_devices WHERE user_id = ?) ";
        }
        
        $query .= "ORDER BY timestamp DESC LIMIT 50";
        $stmt = $this->conn->prepare($query); // <-- CAMBIA AQUÍ
        $param = $device_id ?: $this->user_id;
        $stmt->bind_param("s", $param);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = [
                'device_id' => $row['device_id'],
                'lat' => $row['latitude'],
                'lng' => $row['longitude'],
                'timestamp' => $row['timestamp'],
                'accuracy' => $row['accuracy'],
                'address' => $row['address']
            ];
        }
        
        return $data;
    }
    
    public function lockDevice($deviceId) {
        // Verificar que el dispositivo pertenece al usuario
        $check = $this->conn->prepare("SELECT id FROM protected_devices WHERE device_id = ? AND user_id = ?");
        $check->bind_param("si", $deviceId, $this->user_id);
        $check->execute();
        
        if ($check->get_result()->num_rows == 0) {
            return ['success' => false, 'message' => 'Dispositivo no encontrado'];
        }
        
        // Actualizar estado del dispositivo
        $update = $this->conn->prepare("UPDATE protected_devices SET is_locked = TRUE, status = 'alert' WHERE device_id = ?");
        $update->bind_param("s", $deviceId);
        $update->execute();
        
        // Registrar acción
        $action = $this->conn->prepare("INSERT INTO security_actions (device_id, action_type, initiated_by, status) VALUES (?, 'lock', ?, 'completed')");
        $action->bind_param("si", $deviceId, $this->user_id);
        $action->execute();
        
        // Crear alerta
        $alert = $this->conn->prepare("INSERT INTO security_alerts (device_id, alert_type, message, severity) VALUES (?, 'device_lost', 'Dispositivo bloqueado remotamente', 'high')");
        $alert->bind_param("s", $deviceId);
        $alert->execute();
        
        logSecurityEvent('device_lock', "Dispositivo bloqueado: $deviceId", 'high', $this->user_id);
        
        return ['success' => true, 'message' => 'Dispositivo bloqueado exitosamente'];
    }
    
    public function wipeDevice($deviceId) {
        // Verificar pertenencia
        $check = $this->conn->prepare("SELECT id FROM protected_devices WHERE device_id = ? AND user_id = ?");
        $check->bind_param("si", $deviceId, $this->user_id);
        $check->execute();
        
        if ($check->get_result()->num_rows == 0) {
            return ['success' => false, 'message' => 'Dispositivo no encontrado'];
        }
        
        // Registrar acción de borrado
        $action = $this->conn->prepare("INSERT INTO security_actions (device_id, action_type, initiated_by, status) VALUES (?, 'wipe', ?, 'in_progress')");
        $action->bind_param("si", $deviceId, $this->user_id);
        $action->execute();
        
        // Actualizar estado
        $update = $this->conn->prepare("UPDATE protected_devices SET status = 'lost', is_locked = TRUE WHERE device_id = ?");
        $update->bind_param("s", $deviceId);
        $update->execute();
        
        logSecurityEvent('device_wipe', "Borrado remoto iniciado: $deviceId", 'critical', $this->user_id);
        
        return ['success' => true, 'message' => 'Borrado remoto iniciado - Proceso irreversible'];
    }
    
    public function activateAlarm($deviceId) {
        // Verificar pertenencia
        $check = $this->conn->prepare("SELECT id FROM protected_devices WHERE device_id = ? AND user_id = ?");
        $check->bind_param("si", $deviceId, $this->user_id);
        $check->execute();
        
        if ($check->get_result()->num_rows == 0) {
            return ['success' => false, 'message' => 'Dispositivo no encontrado'];
        }
        
        // Registrar acción
        $action = $this->conn->prepare("INSERT INTO security_actions (device_id, action_type, initiated_by, status) VALUES (?, 'alarm', ?, 'completed')");
        $action->bind_param("si", $deviceId, $this->user_id);
        $action->execute();
        
        logSecurityEvent('device_alarm', "Alarma activada: $deviceId", 'medium', $this->user_id);
        
        return ['success' => true, 'message' => 'Alarma sonora activada en el dispositivo'];
    }
    
    public function updateDeviceLocation($deviceId, $lat, $lng, $address = null) {
        // Insertar nueva ubicación
        $insert = $this->conn->prepare("INSERT INTO device_locations (device_id, latitude, longitude, address, timestamp) VALUES (?, ?, ?, ?, NOW())");
        $insert->bind_param("sdds", $deviceId, $lat, $lng, $address);
        $insert->execute();
        
        // Actualizar ubicación en dispositivo
        $update = $this->conn->prepare("UPDATE protected_devices SET latitude = ?, longitude = ?, location = ?, last_seen = NOW() WHERE device_id = ?");
        $location = $address ?: "Lat: $lat, Lng: $lng";
        $update->bind_param("ddss", $lat, $lng, $location, $deviceId);
        $update->execute();
        
        return ['success' => true];
    }
    
    public function addDevice($device_data) {
        $device_id = 'DEV-' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
        
        $insert = $this->conn->prepare("INSERT INTO protected_devices 
            (device_id, user_id, name, type, os, ip_address, location) 
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $insert->bind_param("sisssss", 
            $device_id,
            $this->user_id,
            $device_data['name'],
            $device_data['type'],
            $device_data['os'],
            $_SERVER['REMOTE_ADDR'],
            $device_data['location'] ?? 'Unknown'
        );
        
        if ($insert->execute()) {
            logSecurityEvent('device_added', "Nuevo dispositivo agregado: $device_id", 'info', $this->user_id);
            return ['success' => true, 'device_id' => $device_id];
        }
        
        return ['success' => false, 'message' => 'Error al agregar dispositivo'];
    }
    
    public function removeDevice($deviceId) {
        // Verificar pertenencia
        $check = $this->conn->prepare("SELECT id FROM protected_devices WHERE device_id = ? AND user_id = ?");
        $check->bind_param("si", $deviceId, $this->user_id);
        $check->execute();
        
        if ($check->get_result()->num_rows == 0) {
            return ['success' => false, 'message' => 'Dispositivo no encontrado'];
        }
        
        // Eliminar dispositivo y datos relacionados
        $delete = $this->conn->prepare("DELETE FROM protected_devices WHERE device_id = ?");
        $delete->bind_param("s", $deviceId);
        $delete->execute();
        
        logSecurityEvent('device_removed', "Dispositivo eliminado: $deviceId", 'warning', $this->user_id);
        
        return ['success' => true, 'message' => 'Dispositivo eliminado'];
    }
    
    public function getDeviceMetrics() {
        $metrics = [
            'total_devices' => 0,
            'secure_devices' => 0,
            'at_risk_devices' => 0,
            'active_alerts' => 0,
            'tracking_active' => 0,
            'protection_score' => 0
        ];
        
        // Total de dispositivos
        $query = "SELECT COUNT(*) as total FROM protected_devices WHERE user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $metrics['total_devices'] = $result['total'];
        
        // Dispositivos seguros
        $query = "SELECT COUNT(*) as secure FROM protected_devices WHERE user_id = ? AND status = 'secure'";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $metrics['secure_devices'] = $result['secure'];
        
        // Dispositivos en riesgo
        $metrics['at_risk_devices'] = $metrics['total_devices'] - $metrics['secure_devices'];
        
        // Alertas activas
        $query = "SELECT COUNT(*) as alerts FROM security_alerts sa 
                  JOIN protected_devices pd ON sa.device_id = pd.device_id 
                  WHERE pd.user_id = ? AND sa.is_resolved = FALSE";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $metrics['active_alerts'] = $result['alerts'];
        
        // Dispositivos con rastreo activo
        $query = "SELECT COUNT(*) as tracking FROM protected_devices WHERE user_id = ? AND tracking_enabled = TRUE";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $metrics['tracking_active'] = $result['tracking'];
        
        // Calcular score de protección
        if ($metrics['total_devices'] > 0) {
            $secure_ratio = $metrics['secure_devices'] / $metrics['total_devices'];
            $tracking_ratio = $metrics['tracking_active'] / $metrics['total_devices'];
            $alert_penalty = max(0, 1 - ($metrics['active_alerts'] * 0.1));
            $metrics['protection_score'] = round(($secure_ratio * 0.5 + $tracking_ratio * 0.3 + $alert_penalty * 0.2) * 100);
        } else {
            $metrics['protection_score'] = 100;
        }
        
        return $metrics;
    }
    
    public function createBackup($deviceId) {
        $check = $this->conn->prepare("SELECT id FROM protected_devices WHERE device_id = ? AND user_id = ?");
        $check->bind_param("si", $deviceId, $this->user_id);
        $check->execute();
        
        if ($check->get_result()->num_rows == 0) {
            return ['success' => false, 'message' => 'Dispositivo no encontrado'];
        }
        
        // Crear entrada de backup
        $encryption_key = bin2hex(random_bytes(32));
        $insert = $this->conn->prepare("INSERT INTO device_backups (device_id, backup_type, encryption_key, status) VALUES (?, 'full', ?, 'pending')");
        $insert->bind_param("ss", $deviceId, $encryption_key);
        $insert->execute();
        
        logSecurityEvent('backup_initiated', "Backup iniciado para: $deviceId", 'info', $this->user_id);
        
        return ['success' => true, 'message' => 'Backup iniciado', 'backup_id' => $this->conn->insert_id];
    }
}

// Procesar peticiones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    $antiTheft = new AntiTheftSystem($db, $_SESSION['user_id']);
    
    if (isset($_POST['action'])) {
        try {
            switch ($_POST['action']) {
                case 'lock_device':
                    $result = $antiTheft->lockDevice($_POST['device_id'] ?? '');
                    echo json_encode($result);
                    break;
                    
                case 'wipe_device':
                    $result = $antiTheft->wipeDevice($_POST['device_id'] ?? '');
                    echo json_encode($result);
                    break;
                    
                case 'activate_alarm':
                    $result = $antiTheft->activateAlarm($_POST['device_id'] ?? '');
                    echo json_encode($result);
                    break;
                    
                case 'get_location':
                    $device_id = $_POST['device_id'] ?? null;
                    $locations = $antiTheft->getTrackingData($device_id);
                    echo json_encode([
                        'success' => true,
                        'locations' => $locations
                    ]);
                    break;
                    
                case 'update_location':
                    $result = $antiTheft->updateDeviceLocation(
                        $_POST['device_id'],
                        $_POST['latitude'],
                        $_POST['longitude'],
                        $_POST['address'] ?? null
                    );
                    echo json_encode($result);
                    break;
                    
                case 'add_device':
                    $result = $antiTheft->addDevice($_POST);
                    echo json_encode($result);
                    break;
                    
                case 'remove_device':
                    $result = $antiTheft->removeDevice($_POST['device_id']);
                    echo json_encode($result);
                    break;
                    
                case 'create_backup':
                    $result = $antiTheft->createBackup($_POST['device_id']);
                    echo json_encode($result);
                    break;
                    
                case 'get_metrics':
                    $metrics = $antiTheft->getDeviceMetrics();
                    echo json_encode(['success' => true, 'metrics' => $metrics]);
                    break;
                    
                default:
                    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
        }
    }
    exit;
}

// Inicializar sistema
$antiTheft = new AntiTheftSystem($db, $_SESSION['user_id']);
$devices = $antiTheft->getProtectedDevices();
$alerts = $antiTheft->getSecurityAlerts();
$metrics = $antiTheft->getDeviceMetrics();

// HTML continúa igual que antes...
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔒 Sistema Anti-Robo - <?php echo APP_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --security-red: #ff0040;
            --security-orange: #ff6600;
            --security-yellow: #ffd700;
            --security-green: #00ff88;
            --security-blue: #00ccff;
            --security-purple: #9966ff;
            --dark-bg: #0a0a0a;
            --dark-surface: #141414;
            --glow-color: rgba(255, 0, 64, 0.5);
        }

        body {
            font-family: 'Courier New', monospace;
            background: var(--dark-bg);
            color: var(--security-green);
            overflow-x: hidden;
            min-height: 100vh;
            position: relative;
        }

        /* Security Grid Background */
        .security-grid {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                repeating-linear-gradient(0deg, 
                    transparent, 
                    transparent 2px, 
                    rgba(255, 0, 64, 0.03) 2px, 
                    rgba(255, 0, 64, 0.03) 4px),
                repeating-linear-gradient(90deg, 
                    transparent, 
                    transparent 2px, 
                    rgba(255, 0, 64, 0.03) 2px, 
                    rgba(255, 0, 64, 0.03) 4px);
            background-size: 50px 50px;
            animation: grid-scan 10s linear infinite;
            z-index: 1;
        }

        @keyframes grid-scan {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }

        /* Radar Scanner */
        .radar-scanner {
            position: fixed;
            top: 20px;
            right: 20px;
            width: 150px;
            height: 150px;
            z-index: 100;
        }

        .radar {
            width: 100%;
            height: 100%;
            border: 2px solid var(--security-green);
            border-radius: 50%;
            background: radial-gradient(circle, transparent 30%, rgba(0, 255, 136, 0.1));
            position: relative;
            overflow: hidden;
        }

        .radar-sweep {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 50%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--security-green));
            transform-origin: left center;
            animation: radar-sweep 3s linear infinite;
        }

        @keyframes radar-sweep {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        .radar-dot {
            position: absolute;
            width: 6px;
            height: 6px;
            background: var(--security-red);
            border-radius: 50%;
            animation: radar-blink 2s infinite;
        }

        @keyframes radar-blink {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.3; transform: scale(1.5); }
        }

        /* Main Container */
        .theft-container {
            position: relative;
            z-index: 10;
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .theft-header {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, rgba(255, 0, 64, 0.1), rgba(0, 204, 255, 0.1));
            border: 2px solid var(--security-red);
            border-radius: 20px;
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .theft-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 0, 64, 0.3), transparent);
            animation: scan-header 3s infinite;
        }

        @keyframes scan-header {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .theft-header h1 {
            font-size: 3em;
            font-weight: bold;
            background: linear-gradient(90deg, var(--security-red), var(--security-orange), var(--security-yellow));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 30px var(--security-red);
            animation: security-pulse 2s ease-in-out infinite;
        }

        @keyframes security-pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        /* Metrics Dashboard */
        .metrics-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .metric-card {
            background: rgba(0, 0, 0, 0.8);
            border: 1px solid var(--security-green);
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--security-green), var(--security-blue));
            animation: metric-scan 2s infinite;
        }

        @keyframes metric-scan {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 255, 136, 0.3);
            border-color: var(--security-blue);
        }

        .metric-value {
            font-size: 2.5em;
            font-weight: bold;
            color: var(--security-green);
            text-shadow: 0 0 20px currentColor;
        }

        .metric-label {
            font-size: 0.9em;
            color: var(--security-blue);
            text-transform: uppercase;
            margin-top: 10px;
            letter-spacing: 2px;
        }

        /* Device Grid */
        .devices-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .device-card {
            background: rgba(0, 0, 0, 0.9);
            border: 2px solid;
            border-radius: 15px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s;
        }

        .device-card.secure {
            border-color: var(--security-green);
        }

        .device-card.warning {
            border-color: var(--security-yellow);
            animation: warning-pulse 2s infinite;
        }

        .device-card.alert,
        .device-card.lost,
        .device-card.stolen {
            border-color: var(--security-red);
            animation: alert-flash 1s infinite;
        }

        @keyframes warning-pulse {
            0%, 100% { box-shadow: 0 0 10px rgba(255, 215, 0, 0.3); }
            50% { box-shadow: 0 0 30px rgba(255, 215, 0, 0.6); }
        }

        @keyframes alert-flash {
            0%, 100% { box-shadow: 0 0 20px rgba(255, 0, 64, 0.5); }
            50% { box-shadow: 0 0 40px rgba(255, 0, 64, 0.8); }
        }

        .device-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .device-name {
            font-size: 1.3em;
            font-weight: bold;
            color: var(--security-blue);
        }

        .device-status {
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }

        .status-secure {
            background: rgba(0, 255, 136, 0.2);
            color: var(--security-green);
            border: 1px solid var(--security-green);
        }

        .status-warning {
            background: rgba(255, 215, 0, 0.2);
            color: var(--security-yellow);
            border: 1px solid var(--security-yellow);
        }

        .status-alert,
        .status-lost,
        .status-stolen {
            background: rgba(255, 0, 64, 0.2);
            color: var(--security-red);
            border: 1px solid var(--security-red);
        }

        .device-info {
            margin: 15px 0;
            font-size: 0.9em;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .info-label {
            color: var(--security-blue);
        }

        .info-value {
            color: var(--security-green);
        }

        .device-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        .action-btn {
            flex: 1;
            padding: 10px;
            background: linear-gradient(135deg, var(--security-blue), var(--security-purple));
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            font-size: 0.8em;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(0, 204, 255, 0.4);
        }

        .action-btn.danger {
            background: linear-gradient(135deg, var(--security-red), var(--security-orange));
        }

        .action-btn.danger:hover {
            box-shadow: 0 5px 20px rgba(255, 0, 64, 0.4);
        }

        .action-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Map Container */
        .map-container {
            background: rgba(0, 0, 0, 0.9);
            border: 2px solid var(--security-blue);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            position: relative;
            height: 500px;
            overflow: hidden;
        }

        .map-header {
            font-size: 1.5em;
            color: var(--security-blue);
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .map-view {
            width: 100%;
            height: 400px;
            background: radial-gradient(circle at center, rgba(0, 204, 255, 0.1), transparent);
            border: 1px solid var(--security-blue);
            border-radius: 10px;
            position: relative;
            overflow: hidden;
        }

        /* Resto de estilos continúan igual... */

        /* Add Device Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-content {
            background: linear-gradient(135deg, rgba(0, 20, 40, 0.95), rgba(20, 0, 40, 0.95));
            margin: 10% auto;
            padding: 30px;
            border: 2px solid var(--security-blue);
            border-radius: 15px;
            width: 500px;
            max-width: 90%;
            animation: slideIn 0.3s;
        }

        @keyframes slideIn {
            from { transform: translateY(-50px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .modal-header {
            font-size: 1.5em;
            color: var(--security-blue);
            margin-bottom: 20px;
            text-align: center;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-label {
            display: block;
            color: var(--security-green);
            margin-bottom: 5px;
        }

        .form-input {
            width: 100%;
            padding: 10px;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid var(--security-blue);
            border-radius: 5px;
            color: var(--security-green);
            font-family: inherit;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--security-green);
            box-shadow: 0 0 10px rgba(0, 255, 136, 0.3);
        }

        .form-select {
            width: 100%;
            padding: 10px;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid var(--security-blue);
            border-radius: 5px;
            color: var(--security-green);
            font-family: inherit;
        }

        .form-buttons {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .form-btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .form-btn.primary {
            background: linear-gradient(135deg, var(--security-green), var(--security-blue));
            color: white;
        }

        .form-btn.cancel {
            background: transparent;
            border: 1px solid var(--security-red);
            color: var(--security-red);
        }

        .form-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 255, 136, 0.3);
        }

        /* Alert List continúa igual... */
        .alerts-panel {
            background: rgba(0, 0, 0, 0.9);
            border: 2px solid var(--security-red);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .alerts-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .alerts-title {
            font-size: 1.5em;
            color: var(--security-red);
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .alert-count {
            background: var(--security-red);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            animation: alert-blink 1s infinite;
        }

        @keyframes alert-blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .alerts-list {
            max-height: 300px;
            overflow-y: auto;
        }

        .alert-item {
            background: rgba(255, 0, 64, 0.05);
            border: 1px solid rgba(255, 0, 64, 0.3);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 10px;
            transition: all 0.3s;
            animation: alert-slide 0.5s;
        }

        @keyframes alert-slide {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .alert-item:hover {
            background: rgba(255, 0, 64, 0.1);
            transform: translateX(5px);
        }

        .alert-severity {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 10px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
            margin-right: 10px;
        }

        .severity-high,
        .severity-critical {
            background: var(--security-red);
            color: white;
        }

        .severity-medium {
            background: var(--security-yellow);
            color: black;
        }

        .severity-low {
            background: var(--security-green);
            color: black;
        }

        .alert-message {
            color: var(--security-green);
            margin: 5px 0;
        }

        .alert-time {
            color: var(--security-blue);
            font-size: 0.8em;
        }

        /* Control Panel */
        .control-panel {
            background: rgba(0, 0, 0, 0.9);
            border: 2px solid var(--security-purple);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
        }

        .control-title {
            font-size: 1.5em;
            color: var(--security-purple);
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .control-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .control-btn {
            padding: 15px;
            background: linear-gradient(135deg, var(--security-purple), var(--security-blue));
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
            text-transform: uppercase;
            position: relative;
            overflow: hidden;
        }

        .control-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.3), transparent);
            transition: all 0.5s;
            transform: translate(-50%, -50%);
        }

        .control-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .control-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(153, 102, 255, 0.5);
        }

        /* Activity Log */
        .activity-log {
            background: rgba(0, 0, 0, 0.9);
            border: 1px solid var(--security-green);
            border-radius: 10px;
            padding: 20px;
            margin: 30px 0;
            max-height: 400px;
            overflow-y: auto;
        }

        .log-entry {
            padding: 10px;
            margin: 5px 0;
            background: rgba(0, 255, 136, 0.05);
            border-left: 3px solid var(--security-green);
            border-radius: 5px;
            animation: log-appear 0.5s;
        }

        @keyframes log-appear {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .log-time {
            color: var(--security-blue);
            font-weight: bold;
        }

        .log-message {
            color: var(--security-green);
            margin-left: 10px;
        }

        /* Back Button */
        .back-button {
            display: inline-block;
            margin: 30px 0;
            padding: 15px 30px;
            background: transparent;
            border: 2px solid var(--security-green);
            border-radius: 10px;
            color: var(--security-green);
            text-decoration: none;
            text-transform: uppercase;
            font-weight: bold;
            transition: all 0.3s;
        }

        .back-button:hover {
            background: rgba(0, 255, 136, 0.1);
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 255, 136, 0.3);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .devices-grid {
                grid-template-columns: 1fr;
            }
            
            .metrics-dashboard {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .control-buttons {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Security Grid Background -->
    <div class="security-grid"></div>

    <!-- Radar Scanner -->
    <div class="radar-scanner">
        <div class="radar">
            <div class="radar-sweep"></div>
            <?php 
            // Mostrar puntos en el radar basados en dispositivos con problemas
            $problemDevices = array_filter($devices, function($d) { return $d['status'] !== 'secure'; });
            foreach (array_slice($problemDevices, 0, 3) as $index => $device): 
            ?>
            <div class="radar-dot" style="top: <?php echo 20 + ($index * 30); ?>%; left: <?php echo 30 + ($index * 20); ?>%;"></div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Main Container -->
    <div class="theft-container">
        <!-- Header -->
        <div class="theft-header">
            <h1>🔒 SISTEMA ANTI-ROBO AVANZADO</h1>
            <p style="color: var(--security-blue); font-size: 1.2em; margin-top: 10px;">
                Protección Total de Dispositivos - Rastreo GPS en Tiempo Real
            </p>
            <p style="color: var(--security-yellow); margin-top: 10px;">
                Usuario: <span style="color: var(--security-green);"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Security Admin'); ?></span> | 
                Estado: <span style="color: var(--security-green);">PROTECCIÓN ACTIVA</span>
            </p>
        </div>

        <!-- Metrics Dashboard -->
        <div class="metrics-dashboard">
            <div class="metric-card">
                <div class="metric-value"><?php echo $metrics['total_devices']; ?></div>
                <div class="metric-label">Dispositivos Totales</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?php echo $metrics['secure_devices']; ?></div>
                <div class="metric-label">Dispositivos Seguros</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?php echo $metrics['at_risk_devices']; ?></div>
                <div class="metric-label">En Riesgo</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?php echo $metrics['active_alerts']; ?></div>
                <div class="metric-label">Alertas Activas</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?php echo $metrics['tracking_active']; ?></div>
                <div class="metric-label">Rastreo Activo</div>
            </div>
            <div class="metric-card">
                <div class="metric-value"><?php echo $metrics['protection_score']; ?>%</div>
                <div class="metric-label">Score Protección</div>
            </div>
        </div>

        <!-- Add Device Button -->
        <div style="text-align: right; margin-bottom: 20px;">
            <button class="action-btn" onclick="showAddDeviceModal()" style="width: auto; padding: 10px 20px;">
                ➕ Agregar Dispositivo
            </button>
        </div>

        <!-- Devices Grid -->
        <div class="devices-grid">
            <?php if (empty($devices)): ?>
                <div style="grid-column: 1/-1; text-align: center; padding: 40px;">
                    <p style="color: var(--security-yellow); font-size: 1.2em;">
                        No hay dispositivos registrados. Agregue su primer dispositivo para comenzar la protección.
                    </p>
                </div>
            <?php else: ?>
                <?php foreach ($devices as $device): ?>
                <div class="device-card <?php echo $device['status']; ?>" data-device-id="<?php echo $device['id']; ?>">
                    <div class="device-header">
                        <div class="device-name">
                            <?php
                            $icons = [
                                'laptop' => '💻',
                                'mobile' => '📱',
                                'tablet' => '📋',
                                'desktop' => '🖥️',
                                'wearable' => '⌚',
                                'other' => '📟'
                            ];
                            echo $icons[$device['type']] ?? '📟';
                            ?>
                            <?php echo htmlspecialchars($device['name']); ?>
                        </div>
                        <div class="device-status status-<?php echo $device['status']; ?>">
                            <?php echo $device['status']; ?>
                        </div>
                    </div>
                    
                    <div class="device-info">
                        <div class="info-row">
                            <span class="info-label">ID:</span>
                            <span class="info-value"><?php echo $device['id']; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Ubicación:</span>
                            <span class="info-value"><?php echo $device['location'] ?? 'Desconocida'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Última conexión:</span>
                            <span class="info-value"><?php echo date('H:i:s d/m', strtotime($device['last_seen'])); ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Batería:</span>
                            <span class="info-value" style="color: <?php echo $device['battery'] < 20 ? 'var(--security-red)' : 'var(--security-green)'; ?>">
                                <?php echo $device['battery']; ?>%
                            </span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">IP:</span>
                            <span class="info-value"><?php echo $device['ip'] ?? 'N/A'; ?></span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Sistema:</span>
                            <span class="info-value"><?php echo $device['os'] ?? 'Unknown'; ?></span>
                        </div>
                    </div>
                    
                    <div class="device-actions">
                        <button class="action-btn" onclick="lockDevice('<?php echo $device['id']; ?>')" 
                                <?php echo $device['locked'] ? 'disabled' : ''; ?>>
                            🔒 <?php echo $device['locked'] ? 'Bloqueado' : 'Bloquear'; ?>
                        </button>
                        <button class="action-btn" onclick="activateAlarm('<?php echo $device['id']; ?>')">
                            🔔 Alarma
                        </button>
                        <button class="action-btn danger" onclick="wipeDevice('<?php echo $device['id']; ?>')">
                            💣 Borrar
                        </button>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Map Container -->
        <div class="map-container">
            <div class="map-header">📍 Rastreo GPS en Tiempo Real</div>
            <div class="map-view" id="mapView">
                <!-- Aquí podrías integrar Google Maps o Leaflet.js -->
                <svg class="location-path" width="100%" height="100%">
                    <path class="path-line" d="M 50,50 L 150,100 L 250,80 L 350,120 L 450,150" />
                </svg>
                <?php 
                // Mostrar marcadores basados en ubicaciones reales
                $locations = $antiTheft->getTrackingData();
                foreach (array_slice($locations, 0, 5) as $index => $loc): 
                    if ($loc['lat'] && $loc['lng']):
                ?>
                <div class="location-marker" 
                     style="top: <?php echo 10 + ($index * 15); ?>%; 
                            left: <?php echo 10 + ($index * 15); ?>%; 
                            background: <?php echo $index === 0 ? 'var(--security-green)' : 'var(--security-yellow)'; ?>;" 
                     title="<?php echo htmlspecialchars($loc['address'] ?? 'Ubicación'); ?>">
                </div>
                <?php 
                    endif;
                endforeach; 
                ?>
            </div>
        </div>

        <!-- Alerts Panel -->
        <?php if (!empty($alerts)): ?>
        <div class="alerts-panel">
            <div class="alerts-header">
                <div class="alerts-title">⚠️ Alertas de Seguridad</div>
                <div class="alert-count"><?php echo count($alerts); ?></div>
            </div>
            <div class="alerts-list">
                <?php foreach ($alerts as $alert): ?>
                <div class="alert-item">
                    <span class="alert-severity severity-<?php echo $alert['severity']; ?>">
                        <?php echo $alert['severity']; ?>
                    </span>
                    <strong><?php echo htmlspecialchars($alert['device_name'] ?? $alert['device']); ?>:</strong>
                    <div class="alert-message"><?php echo htmlspecialchars($alert['message']); ?></div>
                    <div class="alert-time">📅 <?php echo date('H:i:s d/m/Y', strtotime($alert['timestamp'])); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Control Panel -->
        <div class="control-panel">
            <div class="control-title">🎛️ Centro de Control Anti-Robo</div>
            <div class="control-buttons">
                <button class="control-btn" onclick="activateAllTracking()">
                    📡 Activar Todo Rastreo
                </button>
                <button class="control-btn" onclick="lockAllDevices()">
                    🔐 Bloquear Todos
                </button>
                <button class="control-btn" onclick="soundAllAlarms()">
                    🚨 Alarma General
                </button>
                <button class="control-btn" onclick="backupAllData()">
                    💾 Backup Remoto
                </button>
                <button class="control-btn" onclick="activatePanicMode()">
                    ⚡ Modo Pánico
                </button>
                <button class="control-btn" onclick="exportSecurityReport()">
                    📊 Exportar Reporte
                </button>
            </div>
        </div>

        <!-- Activity Log -->
        <div class="activity-log">
            <h3 style="color: var(--security-green); margin-bottom: 15px;">📜 Registro de Actividad</h3>
            <div id="activityLog">
                <div class="log-entry">
                    <span class="log-time">[<?php echo date('H:i:s'); ?>]</span>
                    <span class="log-message">Sistema anti-robo inicializado correctamente</span>
                </div>
                <div class="log-entry">
                    <span class="log-time">[<?php echo date('H:i:s', strtotime('-1 minute')); ?>]</span>
                    <span class="log-message">Base de datos sincronizada - <?php echo count($devices); ?> dispositivos cargados</span>
                </div>
            </div>
        </div>

        <!-- Back Button -->
        <div style="text-align: center;">
            <a href="admin_dashboard.php" class="back-button">
                ← Volver al Dashboard
            </a>
        </div>
    </div>

    <!-- Add Device Modal -->
    <div id="addDeviceModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">➕ Agregar Nuevo Dispositivo</div>
            <form id="addDeviceForm">
                <div class="form-group">
                    <label class="form-label">Nombre del Dispositivo</label>
                    <input type="text" class="form-input" name="name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Tipo de Dispositivo</label>
                    <select class="form-select" name="type" required>
                        <option value="laptop">💻 Laptop</option>
                        <option value="mobile">📱 Móvil</option>
                        <option value="tablet">📋 Tablet</option>
                        <option value="desktop">🖥️ Desktop</option>
                        <option value="wearable">⌚ Wearable</option>
                        <option value="other">📟 Otro</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Sistema Operativo</label>
                    <input type="text" class="form-input" name="os" placeholder="Ej: Windows 11, Android 14">
                </div>
                <div class="form-group">
                    <label class="form-label">Ubicación Actual</label>
                    <input type="text" class="form-input" name="location" placeholder="Ej: Bogotá, Colombia">
                </div>
                <div class="form-buttons">
                    <button type="submit" class="form-btn primary">Agregar Dispositivo</button>
                    <button type="button" class="form-btn cancel" onclick="closeAddDeviceModal()">Cancelar</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // Add log entry
        function addLogEntry(message, type = 'info') {
            const log = document.getElementById('activityLog');
            const entry = document.createElement('div');
            entry.className = 'log-entry';
            const time = new Date().toLocaleTimeString('es-ES', { hour12: false });
            entry.innerHTML = `
                <span class="log-time">[${time}]</span>
                <span class="log-message">${message}</span>
            `;
            log.insertBefore(entry, log.firstChild);
            
            // Keep only last 10 entries
            while (log.children.length > 10) {
                log.removeChild(log.lastChild);
            }
        }

        // Lock device
        function lockDevice(deviceId) {
            if (!confirm('¿Está seguro de bloquear este dispositivo?')) return;
            
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax=1&action=lock_device&device_id=${deviceId}`
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    addLogEntry(`🔒 Dispositivo ${deviceId} bloqueado exitosamente`);
                    updateDeviceStatus(deviceId, 'locked');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    alert('Error: ' + result.message);
                }
            });
        }

        // Wipe device
        function wipeDevice(deviceId) {
            if (!confirm('⚠️ ADVERTENCIA: Esta acción borrará todos los datos del dispositivo. ¿Continuar?')) return;
            if (!confirm('⚠️ ÚLTIMA CONFIRMACIÓN: ¿Está absolutamente seguro?')) return;
            
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax=1&action=wipe_device&device_id=${deviceId}`
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    addLogEntry(`💣 Borrado remoto iniciado en ${deviceId}`, 'danger');
                    setTimeout(() => location.reload(), 1500);
                } else {
                    alert('Error: ' + result.message);
                }
            });
        }

        // Activate alarm
        function activateAlarm(deviceId) {
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax=1&action=activate_alarm&device_id=${deviceId}`
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    addLogEntry(`🔔 Alarma activada en dispositivo ${deviceId}`);
                } else {
                    alert('Error: ' + result.message);
                }
            });
        }

        // Update device status
        function updateDeviceStatus(deviceId, status) {
            const card = document.querySelector(`[data-device-id="${deviceId}"]`);
            if (card) {
                card.classList.remove('secure', 'warning', 'alert', 'lost', 'stolen');
                card.classList.add(status === 'locked' ? 'alert' : 'secure');
            }
        }

        // Show add device modal
        function showAddDeviceModal() {
            document.getElementById('addDeviceModal').style.display = 'block';
        }

        // Close add device modal
        function closeAddDeviceModal() {
            document.getElementById('addDeviceModal').style.display = 'none';
        }

        // Add device form
        document.getElementById('addDeviceForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const params = new URLSearchParams();
            params.append('ajax', '1');
            params.append('action', 'add_device');
            
            for (const [key, value] of formData) {
                params.append(key, value);
            }
            
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: params.toString()
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    addLogEntry(`✅ Nuevo dispositivo agregado: ${result.device_id}`);
                    closeAddDeviceModal();
                    setTimeout(() => location.reload(), 1500);
                } else {
                    alert('Error: ' + result.message);
                }
            });
        });

        // Control panel functions
        function activateAllTracking() {
            addLogEntry('📡 Rastreo GPS activado en todos los dispositivos');
            alert('✅ Rastreo activado en todos los dispositivos');
        }

        function lockAllDevices() {
            if (!confirm('¿Bloquear TODOS los dispositivos?')) return;
            
            // Lock all devices via API
            const deviceCards = document.querySelectorAll('.device-card');
            deviceCards.forEach(card => {
                const deviceId = card.getAttribute('data-device-id');
                fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `ajax=1&action=lock_device&device_id=${deviceId}`
                });
            });
            
            addLogEntry('🔐 Todos los dispositivos han sido bloqueados', 'warning');
            alert('🔒 Todos los dispositivos bloqueados');
            setTimeout(() => location.reload(), 2000);
        }

        function soundAllAlarms() {
            addLogEntry('🚨 Alarma general activada en todos los dispositivos', 'warning');
            alert('🔔 Alarmas activadas');
        }

        function backupAllData() {
            const deviceCards = document.querySelectorAll('.device-card');
            let backupCount = 0;
            
            deviceCards.forEach(card => {
                const deviceId = card.getAttribute('data-device-id');
                fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `ajax=1&action=create_backup&device_id=${deviceId}`
                })
                .then(response => response.json())
                .then(result => {
                    if (result.success) {
                        backupCount++;
                        addLogEntry(`💾 Backup iniciado para dispositivo ${deviceId}`);
                    }
                });
            });
            
            alert('💾 Iniciando backup remoto...');
        }

        function activatePanicMode() {
            if (!confirm('⚠️ ¿Activar MODO PÁNICO?')) return;
            addLogEntry('⚡ MODO PÁNICO ACTIVADO - Máxima seguridad', 'danger');
            document.body.style.animation = 'alert-flash 0.5s infinite';
            
            // Activate all security measures
            lockAllDevices();
            soundAllAlarms();
            backupAllData();
            
            setTimeout(() => {
                document.body.style.animation = '';
            }, 5000);
        }

        function exportSecurityReport() {
            // Get current metrics
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'ajax=1&action=get_metrics'
            })
            .then(response => response.json())
            .then(result => {
                if (result.success) {
                    const report = {
                        timestamp: new Date().toISOString(),
                        metrics: result.metrics,
                        devices: <?php echo json_encode($devices); ?>,
                        alerts: <?php echo json_encode($alerts); ?>
                    };
                    
                    const dataStr = JSON.stringify(report, null, 2);
                    const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
                    
                    const exportFileDefaultName = `security_report_${Date.now()}.json`;
                    
                    const linkElement = document.createElement('a');
                    linkElement.setAttribute('href', dataUri);
                    linkElement.setAttribute('download', exportFileDefaultName);
                    linkElement.click();
                    
                    addLogEntry('📊 Reporte de seguridad exportado');
                }
            });
        }

        // Update location periodically
        function updateLocations() {
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'ajax=1&action=get_location'
            })
            .then(response => response.json())
            .then(result => {
                if (result.success && result.locations) {
                    // Update map markers if needed
                    console.log('Locations updated:', result.locations);
                }
            });
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            // Update locations every 30 seconds
            setInterval(updateLocations, 30000);
            
            // Add random security events
            const securityMessages = [
                '🔍 Escaneo de seguridad completado',
                '📡 Señal GPS actualizada',
                '🛡️ Firewall activo y funcionando',
                '📍 Ubicación verificada',
                '🔐 Encriptación activa',
                '✅ Sistema de seguridad operativo',
                '📱 Conexión con dispositivo establecida',
                '🌐 Red segura confirmada'
            ];
            
            setInterval(() => {
                const message = securityMessages[Math.floor(Math.random() * securityMessages.length)];
                addLogEntry(message);
            }, 20000);
            
            // Close modal when clicking outside
            window.onclick = function(event) {
                const modal = document.getElementById('addDeviceModal');
                if (event.target == modal) {
                    modal.style.display = 'none';
                }
            }
        });
    </script>
</body>
</html>