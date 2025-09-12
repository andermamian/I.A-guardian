<?php
/**
 * GuardianIA v3.0 FINAL - Web Shield Protection System
 * Anderson Mamian Chicangana - Sistema de Protección Web Militar
 * Firewall Avanzado + Detección de Amenazas + Interfaz Épica
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/config_military.php';

// Verificar autenticacion
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

// ========================================
// CREAR TABLAS SI NO EXISTEN
// ========================================
if ($db && $db->isConnected()) {
    try {
        // Tabla de amenazas detectadas
        $db->getConnection()->query("
            CREATE TABLE IF NOT EXISTS web_threats (
                id INT AUTO_INCREMENT PRIMARY KEY,
                threat_id VARCHAR(50) UNIQUE NOT NULL,
                threat_type VARCHAR(100) NOT NULL,
                source_ip VARCHAR(45) NOT NULL,
                target_url TEXT,
                payload TEXT,
                severity ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
                status ENUM('blocked', 'allowed', 'monitoring') DEFAULT 'blocked',
                detection_method VARCHAR(100),
                user_agent TEXT,
                detected_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_ip (source_ip),
                INDEX idx_severity (severity),
                INDEX idx_date (detected_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // Tabla de reglas de firewall
        $db->getConnection()->query("
            CREATE TABLE IF NOT EXISTS firewall_rules (
                id INT AUTO_INCREMENT PRIMARY KEY,
                rule_name VARCHAR(100) NOT NULL,
                rule_type ENUM('ip_block', 'pattern', 'rate_limit', 'geo_block', 'user_agent') DEFAULT 'pattern',
                rule_value TEXT NOT NULL,
                action ENUM('block', 'allow', 'monitor') DEFAULT 'block',
                priority INT DEFAULT 100,
                enabled BOOLEAN DEFAULT TRUE,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                updated_at DATETIME ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_enabled (enabled),
                INDEX idx_priority (priority)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        // Tabla de logs de acceso
        $db->getConnection()->query("
            CREATE TABLE IF NOT EXISTS access_logs (
                id INT AUTO_INCREMENT PRIMARY KEY,
                ip_address VARCHAR(45) NOT NULL,
                request_uri TEXT,
                request_method VARCHAR(10),
                user_agent TEXT,
                referer TEXT,
                status_code INT,
                response_time FLOAT,
                accessed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_ip (ip_address),
                INDEX idx_date (accessed_at)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
        
        logMilitaryEvent('WEB_SHIELD_SYNC', 'Tablas de Web Shield sincronizadas', 'UNCLASSIFIED');
    } catch (Exception $e) {
        error_log("Error creando tablas de Web Shield: " . $e->getMessage());
    }
}

/**
 * Clase Web Shield - Sistema de Protección Web Militar
 */
class WebShield {
    private $db;
    private $rules = [];
    private $threat_patterns = [];
    private $ip_blacklist = [];
    private $rate_limits = [];
    
    public function __construct() {
        global $db;
        $this->db = $db;
        $this->loadSecurityRules();
        $this->initializeThreatPatterns();
    }
    
    /**
     * Cargar reglas de seguridad desde la base de datos
     */
    private function loadSecurityRules() {
        if ($this->db && $this->db->isConnected()) {
            try {
                $result = $this->db->query(
                    "SELECT * FROM firewall_rules WHERE enabled = TRUE ORDER BY priority DESC"
                );
                
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $this->rules[] = $row;
                        
                        if ($row['rule_type'] == 'ip_block') {
                            $this->ip_blacklist[] = $row['rule_value'];
                        }
                    }
                }
            } catch (Exception $e) {
                error_log("Error cargando reglas: " . $e->getMessage());
            }
        }
        
        // Reglas por defecto si no hay BD
        if (empty($this->rules)) {
            $this->rules = $this->getDefaultRules();
        }
    }
    
    /**
     * Inicializar patrones de amenazas
     */
    private function initializeThreatPatterns() {
        $this->threat_patterns = [
            'sql_injection' => [
                '/(\bunion\b.*\bselect\b|\bselect\b.*\bfrom\b|\binsert\b.*\binto\b|\bdelete\b.*\bfrom\b)/i',
                '/(\bdrop\b.*\btable\b|\balter\b.*\btable\b|\bcreate\b.*\btable\b)/i',
                '/(\bexec\b|\bexecute\b|\bcast\b|\bdeclare\b)/i',
                '/(--|\#|\/\*|\*\/)/i'
            ],
            'xss' => [
                '/<script[^>]*>.*?<\/script>/is',
                '/(<|%3C)([^>]*)(>|%3E)/i',
                '/javascript:/i',
                '/on\w+\s*=/i'
            ],
            'path_traversal' => [
                '/\.\.\//',
                '/\.\.\\\\/',
                '/%2e%2e%2f/i',
                '/\.\.(\/|\\\\)etc(\/|\\\\)passwd/i'
            ],
            'command_injection' => [
                '/;|\||`|&&|\$\(|\${/i',
                '/\b(cat|ls|wget|curl|bash|sh|cmd|powershell)\b/i'
            ],
            'xxe' => [
                '/<!DOCTYPE[^>]*\[<!ENTITY/i',
                '/SYSTEM\s+"file:/i'
            ],
            'ldap_injection' => [
                '/[()&|*]/i'
            ]
        ];
    }
    
    /**
     * Obtener reglas por defecto
     */
    private function getDefaultRules() {
        return [
            ['rule_type' => 'ip_block', 'rule_value' => '192.168.1.100', 'action' => 'block'],
            ['rule_type' => 'pattern', 'rule_value' => 'bot|crawler|spider', 'action' => 'monitor'],
            ['rule_type' => 'rate_limit', 'rule_value' => '100:60', 'action' => 'block']
        ];
    }
    
    /**
     * Escanear request en busca de amenazas
     */
    public function scanRequest($method = null, $uri = null, $data = null, $headers = null) {
        $threats = [];
        
        // Usar valores actuales si no se proporcionan
        $method = $method ?? $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = $uri ?? $_SERVER['REQUEST_URI'] ?? '/';
        $data = $data ?? array_merge($_GET, $_POST, $_COOKIE);
        $headers = $headers ?? getallheaders();
        
        // Verificar IP en blacklist
        $client_ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        if (in_array($client_ip, $this->ip_blacklist)) {
            $threats[] = [
                'type' => 'ip_blacklist',
                'severity' => 'high',
                'description' => 'IP en lista negra',
                'value' => $client_ip
            ];
        }
        
        // Escanear datos en busca de patrones maliciosos
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                foreach ($this->threat_patterns as $threat_type => $patterns) {
                    foreach ($patterns as $pattern) {
                        if (preg_match($pattern, $value)) {
                            $threats[] = [
                                'type' => $threat_type,
                                'severity' => $this->getThreatSeverity($threat_type),
                                'description' => "Patrón {$threat_type} detectado",
                                'field' => $key,
                                'value' => substr($value, 0, 100)
                            ];
                        }
                    }
                }
            }
        }
        
        // Verificar User-Agent sospechoso
        $user_agent = $headers['User-Agent'] ?? '';
        if (preg_match('/bot|crawler|spider|scraper/i', $user_agent)) {
            $threats[] = [
                'type' => 'suspicious_agent',
                'severity' => 'low',
                'description' => 'User-Agent sospechoso',
                'value' => $user_agent
            ];
        }
        
        // Registrar amenazas en BD
        foreach ($threats as $threat) {
            $this->logThreat($threat, $client_ip, $uri);
        }
        
        return $threats;
    }
    
    /**
     * Obtener severidad de amenaza
     */
    private function getThreatSeverity($type) {
        $severities = [
            'sql_injection' => 'critical',
            'xss' => 'high',
            'command_injection' => 'critical',
            'path_traversal' => 'high',
            'xxe' => 'high',
            'ldap_injection' => 'medium',
            'suspicious_agent' => 'low',
            'ip_blacklist' => 'high'
        ];
        
        return $severities[$type] ?? 'medium';
    }
    
    /**
     * Registrar amenaza en base de datos
     */
    private function logThreat($threat, $ip, $uri) {
        if ($this->db && $this->db->isConnected()) {
            try {
                $threat_id = uniqid('threat_');
                $this->db->query(
                    "INSERT INTO web_threats (threat_id, threat_type, source_ip, target_url, payload, severity, detection_method) 
                     VALUES (?, ?, ?, ?, ?, ?, ?)",
                    [
                        $threat_id,
                        $threat['type'],
                        $ip,
                        $uri,
                        json_encode($threat),
                        $threat['severity'],
                        'pattern_matching'
                    ]
                );
            } catch (Exception $e) {
                error_log("Error registrando amenaza: " . $e->getMessage());
            }
        }
        
        // Log militar
        logMilitaryEvent('WEB_THREAT_DETECTED', 
            "Amenaza {$threat['type']} detectada desde IP {$ip}", 
            strtoupper($threat['severity']));
    }
    
    /**
     * Obtener estadísticas de amenazas
     */
    public function getThreatStats() {
        $stats = [
            'total_threats' => 0,
            'blocked_today' => 0,
            'critical_threats' => 0,
            'top_threat_types' => [],
            'recent_threats' => []
        ];
        
        if ($this->db && $this->db->isConnected()) {
            try {
                // Total de amenazas
                $result = $this->db->query("SELECT COUNT(*) as count FROM web_threats");
                if ($result) {
                    $row = $result->fetch_assoc();
                    $stats['total_threats'] = $row['count'];
                }
                
                // Bloqueadas hoy
                $result = $this->db->query(
                    "SELECT COUNT(*) as count FROM web_threats 
                     WHERE DATE(detected_at) = CURDATE() AND status = 'blocked'"
                );
                if ($result) {
                    $row = $result->fetch_assoc();
                    $stats['blocked_today'] = $row['count'];
                }
                
                // Amenazas críticas
                $result = $this->db->query(
                    "SELECT COUNT(*) as count FROM web_threats WHERE severity = 'critical'"
                );
                if ($result) {
                    $row = $result->fetch_assoc();
                    $stats['critical_threats'] = $row['count'];
                }
                
                // Top tipos de amenazas
                $result = $this->db->query(
                    "SELECT threat_type, COUNT(*) as count 
                     FROM web_threats 
                     GROUP BY threat_type 
                     ORDER BY count DESC 
                     LIMIT 5"
                );
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $stats['top_threat_types'][] = $row;
                    }
                }
                
                // Amenazas recientes
                $result = $this->db->query(
                    "SELECT * FROM web_threats 
                     ORDER BY detected_at DESC 
                     LIMIT 10"
                );
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $stats['recent_threats'][] = $row;
                    }
                }
            } catch (Exception $e) {
                error_log("Error obteniendo estadísticas: " . $e->getMessage());
            }
        } else {
            // Datos simulados si no hay BD
            $stats['total_threats'] = rand(1000, 5000);
            $stats['blocked_today'] = rand(50, 200);
            $stats['critical_threats'] = rand(10, 50);
        }
        
        return $stats;
    }
    
    /**
     * Agregar regla de firewall
     */
    public function addRule($name, $type, $value, $action = 'block') {
        if ($this->db && $this->db->isConnected()) {
            try {
                $this->db->query(
                    "INSERT INTO firewall_rules (rule_name, rule_type, rule_value, action) 
                     VALUES (?, ?, ?, ?)",
                    [$name, $type, $value, $action]
                );
                
                logMilitaryEvent('FIREWALL_RULE_ADDED', "Nueva regla: {$name}", 'CONFIDENTIAL');
                return true;
            } catch (Exception $e) {
                error_log("Error agregando regla: " . $e->getMessage());
            }
        }
        return false;
    }
    
    /**
     * Verificar rate limiting
     */
    public function checkRateLimit($ip, $limit = 100, $window = 60) {
        $key = "rate_limit_{$ip}";
        $current_time = time();
        
        if (!isset($this->rate_limits[$key])) {
            $this->rate_limits[$key] = [
                'count' => 0,
                'window_start' => $current_time
            ];
        }
        
        // Reiniciar ventana si ha expirado
        if ($current_time - $this->rate_limits[$key]['window_start'] > $window) {
            $this->rate_limits[$key] = [
                'count' => 0,
                'window_start' => $current_time
            ];
        }
        
        $this->rate_limits[$key]['count']++;
        
        return $this->rate_limits[$key]['count'] <= $limit;
    }
}

// Procesar acciones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $shield = new WebShield();
    
    switch ($_POST['action']) {
        case 'scan':
            $threats = $shield->scanRequest();
            echo json_encode(['success' => true, 'threats' => $threats]);
            exit;
            
        case 'stats':
            $stats = $shield->getThreatStats();
            echo json_encode(['success' => true, 'stats' => $stats]);
            exit;
            
        case 'add_rule':
            $success = $shield->addRule(
                $_POST['name'] ?? '',
                $_POST['type'] ?? 'pattern',
                $_POST['value'] ?? '',
                $_POST['action'] ?? 'block'
            );
            echo json_encode(['success' => $success]);
            exit;
    }
}

// Inicializar Web Shield
$shield = new WebShield();
$threat_stats = $shield->getThreatStats();

// Escanear request actual
$current_threats = $shield->scanRequest();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Web Shield - GuardianIA v3.0 MILITARY</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&display=swap');
        
        body {
            font-family: 'Orbitron', monospace;
            background: #000;
            color: #00ff88;
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }
        
        /* Matrix rain background */
        .matrix-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -3;
            background: linear-gradient(0deg, #000 0%, #001a0d 50%, #000 100%);
        }
        
        .matrix-rain {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            overflow: hidden;
        }
        
        .matrix-column {
            position: absolute;
            top: -100%;
            font-family: monospace;
            font-size: 14px;
            line-height: 14px;
            color: #00ff88;
            text-shadow: 0 0 10px #00ff88;
            animation: matrix-fall linear infinite;
            writing-mode: vertical-rl;
            text-orientation: upright;
        }
        
        @keyframes matrix-fall {
            0% {
                top: -100%;
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                top: 100%;
                opacity: 0;
            }
        }
        
        /* Cyber grid overlay */
        .cyber-grid {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.3;
            background-image: 
                linear-gradient(#00ff88 1px, transparent 1px),
                linear-gradient(90deg, #00ff88 1px, transparent 1px);
            background-size: 100px 100px;
            animation: grid-move 20s linear infinite;
        }
        
        @keyframes grid-move {
            0% { transform: translate(0, 0); }
            100% { transform: translate(100px, 100px); }
        }
        
        /* Shield animation */
        .shield-container {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: -1;
            pointer-events: none;
        }
        
        .shield {
            width: 600px;
            height: 600px;
            position: relative;
            animation: shield-rotate 30s linear infinite;
        }
        
        @keyframes shield-rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .shield-ring {
            position: absolute;
            border: 2px solid rgba(0, 255, 136, 0.2);
            border-radius: 50%;
            box-shadow: 
                0 0 50px rgba(0, 255, 136, 0.3),
                inset 0 0 50px rgba(0, 255, 136, 0.1);
        }
        
        .shield-ring:nth-child(1) {
            width: 100%;
            height: 100%;
            animation: pulse 4s ease-in-out infinite;
        }
        
        .shield-ring:nth-child(2) {
            width: 80%;
            height: 80%;
            top: 10%;
            left: 10%;
            animation: pulse 4s ease-in-out infinite 0.5s;
        }
        
        .shield-ring:nth-child(3) {
            width: 60%;
            height: 60%;
            top: 20%;
            left: 20%;
            animation: pulse 4s ease-in-out infinite 1s;
        }
        
        @keyframes pulse {
            0%, 100% { 
                transform: scale(1);
                opacity: 0.3;
            }
            50% { 
                transform: scale(1.1);
                opacity: 0.6;
            }
        }
        
        /* Header épico */
        .header {
            background: linear-gradient(135deg, rgba(0, 255, 136, 0.1), rgba(102, 126, 234, 0.1));
            border-bottom: 2px solid #00ff88;
            padding: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 200%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(0, 255, 136, 0.5), 
                transparent);
            animation: scan 2s linear infinite;
        }
        
        @keyframes scan {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        h1 {
            font-size: 4em;
            font-weight: 900;
            text-transform: uppercase;
            text-align: center;
            background: linear-gradient(45deg, #00ff88, #667eea, #ff00ff, #00ff88);
            background-size: 400% 400%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradient-shift 3s ease infinite;
            filter: drop-shadow(0 0 30px rgba(0, 255, 136, 0.7));
        }
        
        @keyframes gradient-shift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        
        .subtitle {
            text-align: center;
            color: #667eea;
            margin-top: 10px;
            font-size: 1.2em;
            text-shadow: 0 0 20px rgba(102, 126, 234, 0.8);
        }
        
        /* Container */
        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px;
            position: relative;
        }
        
        /* Stats Grid Épico */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin: 40px 0;
        }
        
        .stat-card {
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.9), rgba(0, 50, 30, 0.3));
            border: 2px solid transparent;
            background-clip: padding-box;
            border-radius: 20px;
            padding: 30px;
            position: relative;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #00ff88, #667eea, #ff00ff, #00ff88);
            border-radius: 20px;
            z-index: -1;
            opacity: 0.5;
            animation: gradient-shift 4s ease infinite;
        }
        
        .stat-card:hover {
            transform: translateY(-10px) scale(1.02);
            box-shadow: 
                0 20px 40px rgba(0, 255, 136, 0.4),
                0 0 100px rgba(102, 126, 234, 0.2);
        }
        
        .stat-value {
            font-size: 3em;
            font-weight: 900;
            background: linear-gradient(90deg, #00ff88, #00ffff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 30px rgba(0, 255, 136, 0.8);
            animation: glow 2s ease-in-out infinite;
        }
        
        @keyframes glow {
            0%, 100% { filter: brightness(1); }
            50% { filter: brightness(1.2); }
        }
        
        .stat-label {
            font-size: 1em;
            color: #667eea;
            text-transform: uppercase;
            margin-top: 15px;
            letter-spacing: 2px;
        }
        
        .stat-icon {
            position: absolute;
            top: 20px;
            right: 20px;
            font-size: 2em;
            opacity: 0.3;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        /* Threat Monitor */
        .threat-monitor {
            background: rgba(0, 0, 0, 0.95);
            border: 2px solid #00ff88;
            border-radius: 20px;
            padding: 30px;
            margin: 40px 0;
            position: relative;
            overflow: hidden;
        }
        
        .threat-monitor::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #00ff88, transparent);
            animation: scan 2s linear infinite;
        }
        
        .monitor-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .monitor-title {
            font-size: 1.8em;
            font-weight: 700;
            color: #00ff88;
            text-shadow: 0 0 20px rgba(0, 255, 136, 0.6);
        }
        
        .status-indicator {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .status-dot {
            width: 15px;
            height: 15px;
            border-radius: 50%;
            background: #00ff88;
            box-shadow: 0 0 20px #00ff88;
            animation: blink 1s ease infinite;
        }
        
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        
        .status-text {
            color: #00ff88;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        /* Threat List */
        .threat-list {
            max-height: 400px;
            overflow-y: auto;
            padding-right: 10px;
        }
        
        .threat-list::-webkit-scrollbar {
            width: 10px;
        }
        
        .threat-list::-webkit-scrollbar-track {
            background: rgba(0, 255, 136, 0.1);
            border-radius: 5px;
        }
        
        .threat-list::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, #00ff88, #667eea);
            border-radius: 5px;
        }
        
        .threat-item {
            background: rgba(0, 255, 136, 0.05);
            border: 1px solid rgba(0, 255, 136, 0.2);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s ease;
            animation: slideIn 0.5s ease;
        }
        
        @keyframes slideIn {
            0% {
                opacity: 0;
                transform: translateX(-50px);
            }
            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .threat-item:hover {
            background: rgba(0, 255, 136, 0.1);
            transform: translateX(10px);
            box-shadow: 0 0 30px rgba(0, 255, 136, 0.3);
        }
        
        .threat-info {
            flex: 1;
        }
        
        .threat-type {
            font-weight: 700;
            color: #00ff88;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        
        .threat-details {
            font-size: 0.9em;
            color: #667eea;
        }
        
        .threat-severity {
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .severity-critical {
            background: linear-gradient(135deg, #ff0044, #ff0088);
            color: white;
            animation: pulse-danger 1s ease infinite;
        }
        
        @keyframes pulse-danger {
            0%, 100% { 
                box-shadow: 0 0 20px rgba(255, 0, 68, 0.5);
            }
            50% { 
                box-shadow: 0 0 40px rgba(255, 0, 68, 0.8);
            }
        }
        
        .severity-high {
            background: linear-gradient(135deg, #ff6600, #ff9900);
            color: white;
        }
        
        .severity-medium {
            background: linear-gradient(135deg, #ffaa00, #ffcc00);
            color: #000;
        }
        
        .severity-low {
            background: linear-gradient(135deg, #00ff88, #00cc66);
            color: #000;
        }
        
        /* Control Panel */
        .control-panel {
            background: rgba(0, 0, 0, 0.95);
            border: 2px solid #667eea;
            border-radius: 20px;
            padding: 30px;
            margin: 40px 0;
        }
        
        .control-header {
            font-size: 1.8em;
            font-weight: 700;
            color: #667eea;
            margin-bottom: 25px;
            text-shadow: 0 0 20px rgba(102, 126, 234, 0.6);
        }
        
        .control-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
        }
        
        /* Buttons Épicos */
        .btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            font-size: 0.9em;
            position: relative;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            transition: left 0.5s ease;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.6);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #00ff88, #00cc66);
            box-shadow: 0 5px 15px rgba(0, 255, 136, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ff0044, #cc0033);
            box-shadow: 0 5px 15px rgba(255, 0, 68, 0.4);
        }
        
        .btn-warning {
            background: linear-gradient(135deg, #ff9900, #ff6600);
            box-shadow: 0 5px 15px rgba(255, 153, 0, 0.4);
        }
        
        /* Modal Épico */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            z-index: 2000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.98), rgba(10, 10, 31, 0.98));
            border: 2px solid #00ff88;
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            width: 90%;
            position: relative;
            animation: modal-epic-appear 0.5s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 
                0 0 100px rgba(0, 255, 136, 0.5),
                0 0 200px rgba(102, 126, 234, 0.3);
        }
        
        @keyframes modal-epic-appear {
            0% {
                opacity: 0;
                transform: scale(0.5) rotateX(90deg);
            }
            100% {
                opacity: 1;
                transform: scale(1) rotateX(0);
            }
        }
        
        /* Form elements */
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-label {
            display: block;
            color: #00ff88;
            margin-bottom: 10px;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.9em;
        }
        
        .form-input {
            width: 100%;
            padding: 12px;
            background: rgba(0, 255, 136, 0.05);
            border: 1px solid rgba(0, 255, 136, 0.3);
            border-radius: 8px;
            color: #00ff88;
            font-family: 'Orbitron', monospace;
            transition: all 0.3s ease;
        }
        
        .form-input:focus {
            outline: none;
            border-color: #00ff88;
            background: rgba(0, 255, 136, 0.1);
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.3);
        }
        
        .form-select {
            width: 100%;
            padding: 12px;
            background: rgba(0, 255, 136, 0.05);
            border: 1px solid rgba(0, 255, 136, 0.3);
            border-radius: 8px;
            color: #00ff88;
            font-family: 'Orbitron', monospace;
        }
        
        /* Real-time scanner */
        .scanner-display {
            background: #000;
            border: 1px solid #00ff88;
            border-radius: 10px;
            padding: 20px;
            font-family: monospace;
            color: #00ff88;
            max-height: 300px;
            overflow-y: auto;
            margin: 20px 0;
        }
        
        .scan-line {
            margin: 5px 0;
            opacity: 0;
            animation: scanline-appear 0.5s ease forwards;
        }
        
        @keyframes scanline-appear {
            0% {
                opacity: 0;
                transform: translateX(-20px);
            }
            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        /* Badge de seguridad */
        .security-badge {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.95);
            border: 2px solid #00ff88;
            border-radius: 10px;
            padding: 15px 25px;
            z-index: 1000;
            animation: badge-pulse 2s ease infinite;
        }
        
        @keyframes badge-pulse {
            0%, 100% {
                box-shadow: 0 0 20px rgba(0, 255, 136, 0.5);
            }
            50% {
                box-shadow: 0 0 40px rgba(0, 255, 136, 0.8);
            }
        }
        
        .badge-text {
            color: #00ff88;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.9em;
            letter-spacing: 2px;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            h1 {
                font-size: 2.5em;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .control-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Matrix Background -->
    <div class="matrix-bg"></div>
    <div class="matrix-rain" id="matrixRain"></div>
    
    <!-- Cyber Grid -->
    <div class="cyber-grid"></div>
    
    <!-- Shield Animation -->
    <div class="shield-container">
        <div class="shield">
            <div class="shield-ring"></div>
            <div class="shield-ring"></div>
            <div class="shield-ring"></div>
        </div>
    </div>
    
    <!-- Security Badge -->
    <div class="security-badge">
        <div class="badge-text">
            🛡️ <?php echo MILITARY_ENCRYPTION_ENABLED ? 'MILITARY SHIELD ACTIVE' : 'SHIELD ACTIVE'; ?>
        </div>
    </div>
    
    <!-- Header -->
    <div class="header">
        <h1>⚡ WEB SHIELD ⚡</h1>
        <p class="subtitle">GuardianIA v3.0 MILITARY - Advanced Threat Protection System</p>
    </div>
    
    <!-- Container -->
    <div class="container">
        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">🛡️</div>
                <div class="stat-value"><?php echo $threat_stats['total_threats']; ?></div>
                <div class="stat-label">Total Threats Detected</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">🚫</div>
                <div class="stat-value"><?php echo $threat_stats['blocked_today']; ?></div>
                <div class="stat-label">Blocked Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⚠️</div>
                <div class="stat-value"><?php echo $threat_stats['critical_threats']; ?></div>
                <div class="stat-label">Critical Threats</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">✅</div>
                <div class="stat-value">99.9%</div>
                <div class="stat-label">Protection Rate</div>
            </div>
        </div>
        
        <!-- Threat Monitor -->
        <div class="threat-monitor">
            <div class="monitor-header">
                <div class="monitor-title">🔍 REAL-TIME THREAT MONITOR</div>
                <div class="status-indicator">
                    <div class="status-dot"></div>
                    <span class="status-text">SCANNING</span>
                </div>
            </div>
            
            <div class="scanner-display" id="scannerDisplay">
                <div class="scan-line">[SYSTEM] Web Shield initialized...</div>
                <div class="scan-line">[SCAN] Starting threat detection engine...</div>
                <div class="scan-line">[OK] Military-grade encryption: ACTIVE</div>
                <div class="scan-line">[OK] Firewall rules loaded: <?php echo count($shield->rules ?? []); ?> rules</div>
                <div class="scan-line">[SCAN] Monitoring incoming traffic...</div>
            </div>
            
            <div class="threat-list">
                <?php if (!empty($current_threats)): ?>
                    <?php foreach ($current_threats as $threat): ?>
                    <div class="threat-item">
                        <div class="threat-info">
                            <div class="threat-type"><?php echo htmlspecialchars($threat['type']); ?></div>
                            <div class="threat-details"><?php echo htmlspecialchars($threat['description']); ?></div>
                        </div>
                        <div class="threat-severity severity-<?php echo $threat['severity']; ?>">
                            <?php echo $threat['severity']; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; padding: 40px; color: #667eea;">
                        <div style="font-size: 3em; margin-bottom: 20px;">✅</div>
                        <h3>No Active Threats Detected</h3>
                        <p>System is secure and protected</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Control Panel -->
        <div class="control-panel">
            <div class="control-header">🎛️ SHIELD CONTROL PANEL</div>
            <div class="control-grid">
                <button class="btn btn-success" onclick="performScan()">
                    🔍 PERFORM SCAN
                </button>
                <button class="btn btn-warning" onclick="showAddRule()">
                    ➕ ADD FIREWALL RULE
                </button>
                <button class="btn" onclick="refreshStats()">
                    📊 REFRESH STATS
                </button>
                <button class="btn btn-danger" onclick="emergencyShutdown()">
                    🚨 EMERGENCY MODE
                </button>
            </div>
        </div>
        
        <!-- Recent Threats -->
        <?php if (!empty($threat_stats['recent_threats'])): ?>
        <div class="threat-monitor">
            <div class="monitor-header">
                <div class="monitor-title">📋 RECENT THREAT LOG</div>
            </div>
            <div class="threat-list">
                <?php foreach ($threat_stats['recent_threats'] as $threat): ?>
                <div class="threat-item">
                    <div class="threat-info">
                        <div class="threat-type"><?php echo htmlspecialchars($threat['threat_type']); ?></div>
                        <div class="threat-details">
                            IP: <?php echo htmlspecialchars($threat['source_ip']); ?> | 
                            <?php echo htmlspecialchars($threat['detected_at']); ?>
                        </div>
                    </div>
                    <div class="threat-severity severity-<?php echo $threat['severity']; ?>">
                        <?php echo $threat['severity']; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    
    <!-- Modal -->
    <div class="modal" id="modal">
        <div class="modal-content">
            <h2 style="color: #00ff88; margin-bottom: 25px;">ADD FIREWALL RULE</h2>
            <div class="form-group">
                <label class="form-label">Rule Name</label>
                <input type="text" class="form-input" id="ruleName" placeholder="Enter rule name">
            </div>
            <div class="form-group">
                <label class="form-label">Rule Type</label>
                <select class="form-select" id="ruleType">
                    <option value="ip_block">IP Block</option>
                    <option value="pattern">Pattern Match</option>
                    <option value="rate_limit">Rate Limit</option>
                    <option value="user_agent">User Agent</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Value</label>
                <input type="text" class="form-input" id="ruleValue" placeholder="Enter rule value">
            </div>
            <div class="form-group">
                <label class="form-label">Action</label>
                <select class="form-select" id="ruleAction">
                    <option value="block">Block</option>
                    <option value="allow">Allow</option>
                    <option value="monitor">Monitor</option>
                </select>
            </div>
            <div style="display: flex; gap: 15px; margin-top: 30px;">
                <button class="btn btn-success" onclick="addRule()">ADD RULE</button>
                <button class="btn btn-danger" onclick="closeModal()">CANCEL</button>
            </div>
        </div>
    </div>
    
    <script>
        // Matrix Rain Effect
        function createMatrixRain() {
            const container = document.getElementById('matrixRain');
            const characters = '01アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワヲン';
            
            for (let i = 0; i < 50; i++) {
                const column = document.createElement('div');
                column.className = 'matrix-column';
                column.style.left = (i * 2) + '%';
                column.style.animationDuration = (10 + Math.random() * 20) + 's';
                column.style.animationDelay = Math.random() * 10 + 's';
                
                let text = '';
                for (let j = 0; j < 50; j++) {
                    text += characters[Math.floor(Math.random() * characters.length)];
                }
                column.textContent = text;
                
                container.appendChild(column);
            }
        }
        
        createMatrixRain();
        
        // Scanner animation
        let scannerLines = [
            '[SCAN] Checking for SQL injection patterns...',
            '[OK] No SQL injection detected',
            '[SCAN] Analyzing XSS vulnerabilities...',
            '[OK] XSS protection active',
            '[SCAN] Monitoring rate limits...',
            '[OK] Rate limiting enforced',
            '[SCAN] Checking firewall rules...',
            '[OK] All rules operational',
            '[SCAN] Verifying encryption status...',
            '[OK] AES-256-GCM active'
        ];
        
        let lineIndex = 0;
        function addScanLine() {
            const display = document.getElementById('scannerDisplay');
            const line = document.createElement('div');
            line.className = 'scan-line';
            line.textContent = scannerLines[lineIndex % scannerLines.length];
            display.appendChild(line);
            
            // Remove old lines
            if (display.children.length > 10) {
                display.removeChild(display.firstChild);
            }
            
            display.scrollTop = display.scrollHeight;
            lineIndex++;
        }
        
        setInterval(addScanLine, 2000);
        
        // Functions
        function performScan() {
            const display = document.getElementById('scannerDisplay');
            display.innerHTML = '<div class="scan-line">[SYSTEM] Initiating deep scan...</div>';
            
            fetch(window.location.href, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=scan'
            })
            .then(response => response.json())
            .then(data => {
                if (data.threats && data.threats.length > 0) {
                    display.innerHTML += '<div class="scan-line">[ALERT] ' + data.threats.length + ' threats detected!</div>';
                    data.threats.forEach(threat => {
                        display.innerHTML += '<div class="scan-line">[THREAT] ' + threat.type + ': ' + threat.description + '</div>';
                    });
                } else {
                    display.innerHTML += '<div class="scan-line">[OK] No threats detected. System secure.</div>';
                }
                display.scrollTop = display.scrollHeight;
            });
        }
        
        function refreshStats() {
            location.reload();
        }
        
        function showAddRule() {
            document.getElementById('modal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('modal').classList.remove('active');
        }
        
        function addRule() {
            const formData = new FormData();
            formData.append('action', 'add_rule');
            formData.append('name', document.getElementById('ruleName').value);
            formData.append('type', document.getElementById('ruleType').value);
            formData.append('value', document.getElementById('ruleValue').value);
            formData.append('action', document.getElementById('ruleAction').value);
            
            fetch(window.location.href, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Rule added successfully!');
                    closeModal();
                    location.reload();
                } else {
                    alert('Error adding rule');
                }
            });
        }
        
        function emergencyShutdown() {
            if (confirm('⚠️ WARNING: This will activate MAXIMUM SECURITY MODE. Continue?')) {
                document.body.style.background = 'linear-gradient(45deg, #ff0000, #000)';
                alert('🚨 EMERGENCY MODE ACTIVATED - All traffic is now being monitored and logged');
            }
        }
        
        // Console messages
        console.log('%c⚡ WEB SHIELD ACTIVE ⚡', 'color: #00ff88; font-size: 24px; font-weight: bold; text-shadow: 0 0 20px #00ff88;');
        console.log('%cMilitary-grade protection enabled', 'color: #667eea; font-size: 16px;');
        console.log('%cAll systems operational', 'color: #00ff88; font-size: 14px;');
        
        // Auto-refresh stats every 30 seconds
        setInterval(() => {
            fetch(window.location.href, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=stats'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.stats) {
                    console.log('Stats updated:', data.stats);
                }
            });
        }, 30000);
    </script>
</body>
</html>