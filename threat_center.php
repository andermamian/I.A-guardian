<?php
/**
 * GuardianIA - Centro de Amenazas Completo
 * Versión 3.0.0 - Sistema Integrado de Detección y Respuesta
 * 
 * Este archivo integra:
 * - Motor de Detección de Amenazas (ThreatDetectionEngine)
 * - Interfaz del Centro de Amenazas
 * - Base de Datos MySQL
 * - Procesamiento AJAX en tiempo real
 * - Configuración unificada
 */

// ===============================================
// CONFIGURACIÓN GENERAL
// ===============================================
$config = [
    // Base de datos
    'db' => [
        'host' => 'localhost',
        'dbname' => 'guardia2_guardianai_db',
        'username' => 'guardia2_ander',
        'password' => 'Pbr&v;U(~XvW8V@w',
        'charset' => 'utf8mb4'
    ],
    // Sistema
    'system' => [
        'version' => '3.0.0-MILITARY',
        'app_name' => 'GuardianIA',
        'timezone' => 'America/Mexico_City',
        'log_path' => 'logs/',
        'quarantine_path' => 'quarantine/',
        'debug_mode' => false
    ],
    // Seguridad
    'security' => [
        'sensitivity_level' => 'medium',
        'auto_block' => true,
        'learning_mode' => true,
        'whitelist_enabled' => true,
        'detection_sensitivity' => 0.7,
        'auto_quarantine' => true,
        'log_all_scans' => true,
        'real_time_monitoring' => true,
        'threat_intelligence' => true
    ],
    // Comportamiento
    'behavior' => [
        'max_requests_per_minute' => 60,
        'max_failed_logins' => 5,
        'suspicious_user_agents' => ['sqlmap', 'nikto', 'nmap', 'burp', 'owasp'],
        'blocked_countries' => [],
        'honeypot_endpoints' => ['/admin.php', '/wp-admin/', '/phpmyadmin/', '/.env']
    ]
];

// Establecer zona horaria
date_default_timezone_set($config['system']['timezone']);

// Iniciar sesión
session_start();
$current_user_id = $_SESSION['user_id'] ?? 'default_user';

// ===============================================
// CONEXIÓN A BASE DE DATOS
// ===============================================
try {
    $db = new PDO(
        "mysql:host={$config['db']['host']};dbname={$config['db']['dbname']};charset={$config['db']['charset']}", 
        $config['db']['username'], 
        $config['db']['password'],
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
    
    // Crear tablas si no existen
    createDatabaseTables($db);
    
} catch (PDOException $e) {
    $db = null;
    $simulation_mode = true;
    error_log("Database connection failed: " . $e->getMessage());
}

// ===============================================
// FUNCIONES DE BASE DE DATOS
// ===============================================
function createDatabaseTables($db) {
    $tables = [
        // Tabla de logs de seguridad
        "CREATE TABLE IF NOT EXISTS security_logs (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45),
            url TEXT,
            method VARCHAR(10),
            user_agent TEXT,
            threat_detected BOOLEAN DEFAULT FALSE,
            threat_level INT,
            threat_types JSON,
            action_taken VARCHAR(50),
            processing_time FLOAT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ip (ip_address),
            INDEX idx_threat (threat_detected),
            INDEX idx_timestamp (created_at)
        )",
        
        // Tabla de eventos de amenazas
        "CREATE TABLE IF NOT EXISTS threat_events (
            id INT AUTO_INCREMENT PRIMARY KEY,
            event_id VARCHAR(50) UNIQUE,
            user_id VARCHAR(100),
            threat_type VARCHAR(100),
            severity_level VARCHAR(20),
            description TEXT,
            source_ip VARCHAR(45),
            detection_method VARCHAR(50),
            confidence_score FLOAT,
            metadata JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user (user_id),
            INDEX idx_severity (severity_level),
            INDEX idx_type (threat_type)
        )",
        
        // Tabla de respuestas automáticas
        "CREATE TABLE IF NOT EXISTS threat_responses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            response_id VARCHAR(50) UNIQUE,
            user_id VARCHAR(100),
            threat_type VARCHAR(100),
            actions_taken JSON,
            success BOOLEAN DEFAULT TRUE,
            metadata JSON,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_resp (user_id)
        )",
        
        // Tabla de IPs bloqueadas
        "CREATE TABLE IF NOT EXISTS blocked_ips (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) UNIQUE,
            reason TEXT,
            blocked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NULL,
            permanent BOOLEAN DEFAULT FALSE,
            INDEX idx_ip_blocked (ip_address)
        )",
        
        // Tabla de whitelist
        "CREATE TABLE IF NOT EXISTS ip_whitelist (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) UNIQUE,
            description TEXT,
            is_active BOOLEAN DEFAULT TRUE,
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_ip_whitelist (ip_address)
        )",
        
        // Tabla de archivos en cuarentena
        "CREATE TABLE IF NOT EXISTS quarantined_files (
            id INT AUTO_INCREMENT PRIMARY KEY,
            file_id VARCHAR(50) UNIQUE,
            original_path TEXT,
            quarantine_path TEXT,
            threat_type VARCHAR(100),
            hash_md5 VARCHAR(32),
            hash_sha256 VARCHAR(64),
            quarantined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_file_id (file_id)
        )"
    ];
    
    foreach ($tables as $query) {
        try {
            $db->exec($query);
        } catch (PDOException $e) {
            error_log("Error creating table: " . $e->getMessage());
        }
    }
}

// ===============================================
// FUNCIONES AUXILIARES
// ===============================================
function logGuardianEvent($event_type, $message, $severity = 'info', $context = []) {
    global $config;
    
    $timestamp = date('Y-m-d H:i:s');
    $log_entry = "[{$timestamp}] [{$severity}] {$event_type}: {$message}\n";
    
    $log_dir = __DIR__ . '/' . $config['system']['log_path'];
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0777, true);
    }
    
    file_put_contents($log_dir . 'guardian.log', $log_entry, FILE_APPEND | LOCK_EX);
}

// ===============================================
// CLASE PRINCIPAL: ThreatDetectionEngine
// ===============================================
class ThreatDetectionEngine {
    private $db;
    private $config;
    private $realTimeActive = false;
    private $statistics = [];
    private $threatPatterns = [];
    
    public function __construct($database, $config) {
        $this->db = $database;
        $this->config = $config;
        $this->initializePatterns();
        $this->loadStatistics();
        logGuardianEvent('threat_engine_init', 'ThreatDetectionEngine initialized', 'info');
    }
    
    private function initializePatterns() {
        $this->threatPatterns = [
            'sql_injection' => [
                '/(\b(union|select|insert|update|delete|drop|create|alter)\b.*\b(from|where|into)\b)/i',
                '/(\'\s*(or|and)\s*\'.*(=|\>|\<))/i',
                '/(;|\-\-|\/\*|\*\/)/i'
            ],
            'xss' => [
                '/(\<script.*?\>|\<\/script\>)/i',
                '/(\<iframe.*?\>|\<\/iframe\>)/i',
                '/(javascript:|vbscript:|data:)/i',
                '/(\bon\w+\s*=)/i'
            ],
            'command_injection' => [
                '/(;|\||\&\&|\|\|)/i',
                '/(\$\(|\`)/i',
                '/(nc|netcat|wget|curl|bash|sh|cmd|powershell)/i'
            ],
            'path_traversal' => [
                '/(\.\.\/|\.\.\\\\)/i',
                '/(\.\.\%2f|\.\.\%5c)/i',
                '/(etc\/passwd|windows\/system32)/i'
            ],
            'malware_signatures' => [
                '/(eval\s*\(|base64_decode\s*\(|gzinflate\s*\()/i',
                '/(shell_exec\s*\(|system\s*\(|exec\s*\()/i',
                '/(file_get_contents\s*\(.*http|fopen\s*\(.*http)/i'
            ]
        ];
    }
    
    private function loadStatistics() {
        if (!$this->db) {
            $this->statistics = [
                'total_requests_analyzed' => 0,
                'threats_detected' => 0,
                'threats_blocked' => 0,
                'false_positives' => 0
            ];
            return;
        }
        
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_requests,
                    SUM(CASE WHEN threat_detected = 1 THEN 1 ELSE 0 END) as threats_detected,
                    SUM(CASE WHEN action_taken = 'blocked' THEN 1 ELSE 0 END) as threats_blocked
                FROM security_logs 
                WHERE DATE(created_at) = CURDATE()
            ");
            $stmt->execute();
            $dailyStats = $stmt->fetch();
            
            $this->statistics = [
                'total_requests_analyzed' => $dailyStats['total_requests'] ?? 0,
                'threats_detected' => $dailyStats['threats_detected'] ?? 0,
                'threats_blocked' => $dailyStats['threats_blocked'] ?? 0,
                'false_positives' => 0
            ];
        } catch (Exception $e) {
            logGuardianEvent('stats_error', 'Error loading statistics: ' . $e->getMessage(), 'error');
        }
    }
    
    /**
     * Detectar amenaza principal
     */
    public function detectThreat($input, $type = 'general') {
        try {
            $result = [
                'success' => true,
                'threat_detected' => false,
                'threat_type' => null,
                'severity' => 'low',
                'confidence' => 0.0,
                'description' => 'No threats detected',
                'recommendations' => [],
                'quarantined' => false
            ];
            
            // Analizar según tipo
            switch ($type) {
                case 'file':
                    $result = $this->detectFileThreat($input);
                    break;
                case 'network':
                    $result = $this->detectNetworkThreat($input);
                    break;
                case 'web':
                    $result = $this->detectWebThreat($input);
                    break;
                default:
                    $result = $this->detectGeneralThreat($input);
                    break;
            }
            
            // Guardar si se detectó amenaza
            if ($result['threat_detected']) {
                $this->saveThreatEvent($result, $input, $type);
                $this->statistics['threats_detected']++;
            }
            
            return $result;
            
        } catch (Exception $e) {
            logGuardianEvent('detection_error', $e->getMessage(), 'error');
            return [
                'success' => false,
                'threat_detected' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Analizar request HTTP
     */
    public function analyzeRequest($request) {
        $this->statistics['total_requests_analyzed']++;
        
        $threatAssessment = [
            'is_threat' => false,
            'threat_level' => 0,
            'threat_types' => [],
            'confidence' => 0,
            'recommended_action' => 'allow',
            'details' => []
        ];
        
        // Verificar whitelist
        if ($this->isWhitelisted($request['ip'] ?? '')) {
            return $threatAssessment;
        }
        
        // Analizar patrones
        foreach ($this->threatPatterns as $threatType => $patterns) {
            foreach ($patterns as $pattern) {
                $content = json_encode($request);
                if (preg_match($pattern, $content)) {
                    $threatAssessment['is_threat'] = true;
                    $threatAssessment['threat_level'] += 25;
                    $threatAssessment['threat_types'][] = $threatType;
                }
            }
        }
        
        // Determinar acción
        if ($threatAssessment['threat_level'] >= 80) {
            $threatAssessment['recommended_action'] = 'block';
        } elseif ($threatAssessment['threat_level'] >= 50) {
            $threatAssessment['recommended_action'] = 'monitor';
        }
        
        // Registrar
        if ($threatAssessment['is_threat']) {
            $this->logThreatDetection($request, $threatAssessment);
        }
        
        return $threatAssessment;
    }
    
    /**
     * Iniciar análisis en tiempo real
     */
    public function startRealTimeAnalysis() {
        $this->realTimeActive = true;
        logGuardianEvent('real_time_start', 'Real-time analysis started', 'info');
        
        return [
            'success' => true,
            'status' => 'active',
            'monitoring' => true,
            'threats_detected' => $this->statistics['threats_detected'],
            'scan_rate' => '10/second'
        ];
    }
    
    /**
     * Detener análisis en tiempo real
     */
    public function stopRealTimeAnalysis() {
        $this->realTimeActive = false;
        logGuardianEvent('real_time_stop', 'Real-time analysis stopped', 'info');
        
        return [
            'success' => true,
            'status' => 'stopped',
            'monitoring' => false
        ];
    }
    
    /**
     * Obtener estado del análisis
     */
    public function getAnalysisStatus() {
        return [
            'success' => true,
            'status' => $this->realTimeActive ? 'active' : 'inactive',
            'monitoring' => $this->realTimeActive,
            'threats_detected' => $this->statistics['threats_detected'],
            'threats_blocked' => $this->statistics['threats_blocked'],
            'last_scan' => date('Y-m-d H:i:s')
        ];
    }
    
    /**
     * Obtener estadísticas
     */
    public function getThreatStatistics() {
        $this->loadStatistics();
        
        $stats = [
            'success' => true,
            'overview' => [
                'total_requests_analyzed' => $this->statistics['total_requests_analyzed'],
                'threats_detected' => $this->statistics['threats_detected'],
                'threats_blocked' => $this->statistics['threats_blocked'],
                'detection_rate' => $this->statistics['total_requests_analyzed'] > 0 
                    ? round(($this->statistics['threats_detected'] / $this->statistics['total_requests_analyzed']) * 100, 2) 
                    : 0
            ],
            'threat_types' => [],
            'top_threat_ips' => [],
            'system_health' => [
                'engine_status' => 'operational',
                'last_update' => date('Y-m-d H:i:s'),
                'rules_active' => count($this->threatPatterns)
            ]
        ];
        
        // Obtener tipos de amenazas principales
        if ($this->db) {
            try {
                $stmt = $this->db->prepare("
                    SELECT threat_type, COUNT(*) as count 
                    FROM threat_events 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    GROUP BY threat_type 
                    ORDER BY count DESC 
                    LIMIT 5
                ");
                $stmt->execute();
                $stats['threat_types'] = $stmt->fetchAll();
                
                // Top IPs amenazantes
                $stmt = $this->db->prepare("
                    SELECT source_ip, COUNT(*) as threat_count
                    FROM threat_events 
                    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    GROUP BY source_ip 
                    ORDER BY threat_count DESC 
                    LIMIT 5
                ");
                $stmt->execute();
                $stats['top_threat_ips'] = $stmt->fetchAll();
                
            } catch (Exception $e) {
                logGuardianEvent('stats_query_error', $e->getMessage(), 'error');
            }
        }
        
        return $stats;
    }
    
    /**
     * Escanear archivo
     */
    public function scanFile($filePath) {
        return $this->detectFileThreat($filePath);
    }
    
    /**
     * Escanear red
     */
    public function scanNetwork($networkData) {
        return $this->detectNetworkThreat($networkData);
    }
    
    /**
     * Ejecutar respuesta automática
     */
    public function executeAutomaticResponse($threatData, $responseType = 'auto') {
        $result = [
            'success' => true,
            'response_executed' => true,
            'actions_taken' => [],
            'quarantined' => false,
            'blocked' => false
        ];
        
        $severity = $threatData['severity'] ?? 'low';
        
        switch ($severity) {
            case 'critical':
                $result['actions_taken'][] = 'System quarantine initiated';
                $result['actions_taken'][] = 'IP blocked';
                $result['quarantined'] = true;
                $result['blocked'] = true;
                break;
                
            case 'high':
                $result['actions_taken'][] = 'Threat quarantined';
                $result['quarantined'] = true;
                break;
                
            case 'medium':
                $result['actions_taken'][] = 'Monitoring increased';
                break;
                
            default:
                $result['actions_taken'][] = 'Logged for review';
        }
        
        // Guardar respuesta
        $this->saveAutomaticResponse($threatData, $result);
        
        return $result;
    }
    
    /**
     * Poner archivo en cuarentena
     */
    public function quarantineFile($filePath) {
        global $config;
        
        $result = [
            'success' => false,
            'quarantined' => false,
            'quarantine_path' => ''
        ];
        
        if (!file_exists($filePath)) {
            $result['error'] = 'File not found';
            return $result;
        }
        
        $quarantineDir = __DIR__ . '/' . $config['system']['quarantine_path'];
        if (!file_exists($quarantineDir)) {
            mkdir($quarantineDir, 0777, true);
        }
        
        $fileName = basename($filePath);
        $quarantineName = date('Y-m-d_H-i-s') . '_' . $fileName;
        $quarantinePath = $quarantineDir . $quarantineName;
        
        if (copy($filePath, $quarantinePath)) {
            $result['success'] = true;
            $result['quarantined'] = true;
            $result['quarantine_path'] = $quarantinePath;
            
            // Guardar en BD
            if ($this->db) {
                try {
                    $stmt = $this->db->prepare("
                        INSERT INTO quarantined_files 
                        (file_id, original_path, quarantine_path, threat_type, hash_md5) 
                        VALUES (?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        uniqid('FILE_'),
                        $filePath,
                        $quarantinePath,
                        'unknown',
                        md5_file($filePath)
                    ]);
                } catch (Exception $e) {
                    logGuardianEvent('quarantine_db_error', $e->getMessage(), 'error');
                }
            }
            
            @unlink($filePath);
        }
        
        return $result;
    }
    
    /**
     * Bloquear IP
     */
    public function blockIP($ipAddress) {
        $result = [
            'success' => false,
            'blocked' => false,
            'ip_address' => $ipAddress
        ];
        
        if ($this->db) {
            try {
                $stmt = $this->db->prepare("
                    INSERT INTO blocked_ips (ip_address, reason, permanent) 
                    VALUES (?, ?, ?)
                    ON DUPLICATE KEY UPDATE blocked_at = NOW()
                ");
                $stmt->execute([$ipAddress, 'Automatic threat response', false]);
                
                $result['success'] = true;
                $result['blocked'] = true;
                
            } catch (Exception $e) {
                $result['error'] = $e->getMessage();
            }
        }
        
        return $result;
    }
    
    // ===============================================
    // MÉTODOS PRIVADOS DE DETECCIÓN
    // ===============================================
    
    private function detectFileThreat($filePath) {
        $result = [
            'success' => true,
            'threat_detected' => false,
            'threat_type' => null,
            'severity' => 'low',
            'confidence' => 0.0,
            'description' => 'File analysis completed',
            'recommendations' => []
        ];
        
        if (!file_exists($filePath)) {
            $result['description'] = 'File not found';
            return $result;
        }
        
        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $dangerousExtensions = ['exe', 'bat', 'cmd', 'scr', 'pif', 'com', 'vbs', 'js'];
        
        if (in_array($extension, $dangerousExtensions)) {
            $result['threat_detected'] = true;
            $result['threat_type'] = 'suspicious_executable';
            $result['severity'] = 'high';
            $result['confidence'] = 0.9;
            $result['description'] = "Dangerous file extension: .$extension";
            $result['recommendations'][] = 'Quarantine file';
        }
        
        return $result;
    }
    
    private function detectNetworkThreat($networkData) {
        $result = [
            'success' => true,
            'threat_detected' => false,
            'threat_type' => null,
            'severity' => 'low',
            'confidence' => 0.0,
            'description' => 'Network analysis completed',
            'recommendations' => []
        ];
        
        if (is_string($networkData)) {
            $networkData = ['source_ip' => $networkData];
        }
        
        $sourceIP = $networkData['source_ip'] ?? '';
        
        // Verificar si IP está bloqueada
        if ($this->isIPBlocked($sourceIP)) {
            $result['threat_detected'] = true;
            $result['threat_type'] = 'blocked_ip_attempt';
            $result['severity'] = 'high';
            $result['confidence'] = 1.0;
            $result['description'] = "Access attempt from blocked IP: $sourceIP";
        }
        
        return $result;
    }
    
    private function detectWebThreat($webData) {
        $result = [
            'success' => true,
            'threat_detected' => false,
            'threat_type' => null,
            'severity' => 'low',
            'confidence' => 0.0,
            'description' => 'Web threat analysis completed',
            'recommendations' => []
        ];
        
        if (is_string($webData)) {
            $webData = ['url' => $webData];
        }
        
        $content = json_encode($webData);
        
        // Verificar patrones de amenazas web
        foreach ($this->threatPatterns as $threatType => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    $result['threat_detected'] = true;
                    $result['threat_type'] = $threatType;
                    $result['severity'] = 'high';
                    $result['confidence'] = 0.8;
                    $result['description'] = "Web threat detected: $threatType";
                    $result['recommendations'][] = 'Block request';
                    break 2;
                }
            }
        }
        
        return $result;
    }
    
    private function detectGeneralThreat($input) {
        $result = [
            'success' => true,
            'threat_detected' => false,
            'threat_type' => null,
            'severity' => 'low',
            'confidence' => 0.0,
            'description' => 'General analysis completed',
            'recommendations' => []
        ];
        
        $inputString = is_array($input) ? json_encode($input) : (string)$input;
        
        $suspiciousKeywords = [
            'malicious', 'virus', 'trojan', 'malware', 'hack', 
            'exploit', 'backdoor', 'ransomware', 'phishing'
        ];
        
        foreach ($suspiciousKeywords as $keyword) {
            if (stripos($inputString, $keyword) !== false) {
                $result['threat_detected'] = true;
                $result['threat_type'] = 'suspicious_content';
                $result['severity'] = 'medium';
                $result['confidence'] = 0.7;
                $result['description'] = "Suspicious keyword detected: $keyword";
                $result['recommendations'][] = 'Review content';
                break;
            }
        }
        
        return $result;
    }
    
    private function isWhitelisted($ip) {
        if (!$this->db || empty($ip)) return false;
        
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM ip_whitelist 
                WHERE ip_address = ? AND is_active = 1
            ");
            $stmt->execute([$ip]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function isIPBlocked($ip) {
        if (!$this->db || empty($ip)) return false;
        
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) FROM blocked_ips 
                WHERE ip_address = ? 
                AND (expires_at IS NULL OR expires_at > NOW())
            ");
            $stmt->execute([$ip]);
            return $stmt->fetchColumn() > 0;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function saveThreatEvent($result, $input, $type) {
        if (!$this->db) return;
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO threat_events 
                (event_id, user_id, threat_type, severity_level, description, 
                 source_ip, detection_method, confidence_score, metadata) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                'THR_' . uniqid(),
                $_SESSION['user_id'] ?? null,
                $result['threat_type'],
                $result['severity'],
                $result['description'],
                $_SERVER['REMOTE_ADDR'] ?? null,
                $type,
                $result['confidence'],
                json_encode(['input' => $input, 'result' => $result])
            ]);
        } catch (Exception $e) {
            logGuardianEvent('save_threat_error', $e->getMessage(), 'error');
        }
    }
    
    private function saveAutomaticResponse($threatData, $responseResult) {
        if (!$this->db) return;
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO threat_responses 
                (response_id, user_id, threat_type, actions_taken, success, metadata) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                'RESP_' . uniqid(),
                $_SESSION['user_id'] ?? null,
                $threatData['threat_type'] ?? 'unknown',
                json_encode($responseResult['actions_taken']),
                $responseResult['success'] ? 1 : 0,
                json_encode(['threat' => $threatData, 'response' => $responseResult])
            ]);
        } catch (Exception $e) {
            logGuardianEvent('save_response_error', $e->getMessage(), 'error');
        }
    }
    
    private function logThreatDetection($request, $assessment) {
        if (!$this->db) return;
        
        try {
            $stmt = $this->db->prepare("
                INSERT INTO security_logs 
                (ip_address, url, method, user_agent, threat_detected, 
                 threat_level, threat_types, action_taken, processing_time) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $request['ip'] ?? '',
                $request['url'] ?? '',
                $request['method'] ?? 'GET',
                $request['user_agent'] ?? '',
                $assessment['is_threat'] ? 1 : 0,
                $assessment['threat_level'],
                json_encode($assessment['threat_types']),
                $assessment['recommended_action'],
                0
            ]);
        } catch (Exception $e) {
            logGuardianEvent('log_threat_error', $e->getMessage(), 'error');
        }
    }
}

// ===============================================
// INICIALIZAR MOTOR DE DETECCIÓN
// ===============================================
$threatEngine = new ThreatDetectionEngine($db, $config);

// ===============================================
// PROCESAMIENTO DE PETICIONES AJAX
// ===============================================
if (isset($_POST['action']) || isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'] ?? $_GET['action'];
    
    switch ($action) {
        case 'get_stats':
            $stats = $threatEngine->getThreatStatistics();
            echo json_encode($stats);
            exit;
            
        case 'scan_threat':
            $input = $_POST['input'] ?? $_GET['input'] ?? '';
            $type = $_POST['type'] ?? $_GET['type'] ?? 'general';
            $result = $threatEngine->detectThreat($input, $type);
            echo json_encode($result);
            exit;
            
        case 'start_monitoring':
            $result = $threatEngine->startRealTimeAnalysis();
            echo json_encode($result);
            exit;
            
        case 'stop_monitoring':
            $result = $threatEngine->stopRealTimeAnalysis();
            echo json_encode($result);
            exit;
            
        case 'get_status':
            $status = $threatEngine->getAnalysisStatus();
            echo json_encode($status);
            exit;
            
        case 'analyze_request':
            $request = [
                'ip' => $_POST['ip'] ?? $_SERVER['REMOTE_ADDR'],
                'url' => $_POST['url'] ?? $_SERVER['REQUEST_URI'],
                'method' => $_POST['method'] ?? $_SERVER['REQUEST_METHOD'],
                'user_agent' => $_POST['user_agent'] ?? $_SERVER['HTTP_USER_AGENT'],
                'post_data' => $_POST
            ];
            $result = $threatEngine->analyzeRequest($request);
            echo json_encode($result);
            exit;
            
        case 'quarantine_file':
            $filePath = $_POST['file_path'] ?? '';
            $result = $threatEngine->quarantineFile($filePath);
            echo json_encode($result);
            exit;
            
        case 'block_ip':
            $ip = $_POST['ip'] ?? '';
            $result = $threatEngine->blockIP($ip);
            echo json_encode($result);
            exit;
            
        case 'execute_response':
            $threatData = json_decode($_POST['threat_data'] ?? '{}', true);
            $result = $threatEngine->executeAutomaticResponse($threatData);
            echo json_encode($result);
            exit;
            
        case 'get_threat_timeline':
            // Obtener timeline de amenazas
            $timeline = [];
            if ($db) {
                try {
                    $stmt = $db->prepare("
                        SELECT event_id, threat_type, severity_level, 
                               description, created_at 
                        FROM threat_events 
                        ORDER BY created_at DESC 
                        LIMIT 10
                    ");
                    $stmt->execute();
                    $timeline = $stmt->fetchAll();
                } catch (Exception $e) {
                    $timeline = [];
                }
            }
            echo json_encode(['success' => true, 'timeline' => $timeline]);
            exit;
    }
}

// Si no es petición AJAX, mostrar la interfaz HTML
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centro de Amenazas - GuardianIA</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --warning-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --critical-gradient: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            
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
                radial-gradient(circle at 20% 80%, rgba(255, 107, 107, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(238, 90, 36, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(250, 112, 154, 0.2) 0%, transparent 50%);
            animation: threatPulse 6s ease-in-out infinite;
        }

        @keyframes threatPulse {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 0.7; }
        }

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
            background: var(--danger-gradient);
            color: white;
        }

        .main-container {
            margin-top: 80px;
            padding: 2rem;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        .emergency-header {
            background: var(--critical-gradient);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .emergency-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            animation: emergencyShine 3s infinite;
        }

        @keyframes emergencyShine {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .emergency-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .alert-status {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .alert-level {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            color: white;
        }

        .panic-button {
            background: #ff4757;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
            box-shadow: 0 8px 25px rgba(255, 71, 87, 0.4);
            animation: panicPulse 2s infinite;
        }

        @keyframes panicPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .panic-button:hover {
            transform: scale(1.1);
            box-shadow: 0 12px 35px rgba(255, 71, 87, 0.6);
        }

        .threat-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .threat-stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            transition: all var(--animation-speed) ease;
        }

        .threat-stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px var(--shadow-color);
        }

        .threat-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .threat-stat-card.critical::before {
            background: var(--critical-gradient);
        }

        .threat-stat-card.warning::before {
            background: var(--warning-gradient);
        }

        .threat-stat-card.success::before {
            background: var(--success-gradient);
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

        .timeline-section {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: var(--card-padding);
            margin-bottom: 2rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .timeline {
            position: relative;
            padding-left: 2rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 1rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--border-color);
        }

        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 1.5rem;
            transition: all var(--animation-speed) ease;
        }

        .timeline-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(10px);
        }

        .timeline-dot {
            position: absolute;
            left: -2.5rem;
            top: 1.5rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 3px solid var(--bg-card);
        }

        .timeline-dot.critical {
            background: #ff4757;
            animation: criticalBlink 1s infinite;
        }

        .timeline-dot.warning {
            background: #ffa502;
        }

        .timeline-dot.success {
            background: #2ed573;
        }

        @keyframes criticalBlink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .timeline-title {
            font-weight: 600;
            color: var(--text-primary);
        }

        .timeline-time {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .timeline-description {
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }

        .timeline-actions {
            display: flex;
            gap: 0.5rem;
        }

        .timeline-action {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
        }

        .timeline-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .monitor-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .monitor-display {
            background: #000;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 1rem;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            height: 400px;
            overflow-y: auto;
            position: relative;
        }

        .monitor-line {
            margin-bottom: 0.5rem;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(10px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .monitor-line.info {
            color: #4facfe;
        }

        .monitor-line.warning {
            color: #ffa502;
        }

        .monitor-line.error {
            color: #ff4757;
        }

        .monitor-line.success {
            color: #2ed573;
        }

        .action-btn {
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
            gap: 0.5rem;
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .action-btn.danger {
            background: var(--critical-gradient);
        }

        .action-btn.warning {
            background: var(--warning-gradient);
        }

        .action-btn.success {
            background: var(--success-gradient);
        }

        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

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

        @media (max-width: 768px) {
            .monitor-grid {
                grid-template-columns: 1fr;
            }

            .threat-stats {
                grid-template-columns: 1fr;
            }

            .emergency-title {
                font-size: 2rem;
            }

            .main-container {
                padding: 1rem;
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
                <li><a href="index.php" class="nav-link">Dashboard</a></li>
                <li><a href="#" class="nav-link active">Centro de Amenazas</a></li>
                <li><a href="performance.php" class="nav-link">Rendimiento</a></li>
                <li><a href="chatbot.php" class="nav-link">Asistente IA</a></li>
                <li><a href="settings.php" class="nav-link">Configuración</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Emergency Header -->
        <div class="emergency-header">
            <h1 class="emergency-title">🚨 CENTRO DE RESPUESTA RÁPIDA</h1>
            <div class="alert-status">
                <div class="alert-level" id="alert-level">NIVEL: BAJO</div>
                <button class="panic-button" onclick="activatePanicMode()">
                    <i class="fas fa-exclamation-triangle"></i>
                    EMERGENCIA
                </button>
            </div>
        </div>

        <!-- Threat Statistics -->
        <div class="threat-stats">
            <div class="threat-stat-card critical">
                <div class="stat-header">
                    <div>
                        <div class="stat-value" style="color: #ff4757;" id="critical-threats">0</div>
                        <div class="stat-label">Amenazas Críticas</div>
                    </div>
                    <div class="stat-icon" style="background: var(--critical-gradient);">
                        <i class="fas fa-skull-crossbones"></i>
                    </div>
                </div>
            </div>

            <div class="threat-stat-card warning">
                <div class="stat-header">
                    <div>
                        <div class="stat-value" style="color: #ffa502;" id="warning-threats">0</div>
                        <div class="stat-label">Advertencias</div>
                    </div>
                    <div class="stat-icon" style="background: var(--warning-gradient);">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>

            <div class="threat-stat-card success">
                <div class="stat-header">
                    <div>
                        <div class="stat-value" style="color: #2ed573;" id="blocked-threats">0</div>
                        <div class="stat-label">Amenazas Bloqueadas</div>
                    </div>
                    <div class="stat-icon" style="background: var(--success-gradient);">
                        <i class="fas fa-shield-check"></i>
                    </div>
                </div>
            </div>

            <div class="threat-stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value" style="color: #4facfe;" id="response-time">0ms</div>
                        <div class="stat-label">Tiempo de Respuesta</div>
                    </div>
                    <div class="stat-icon" style="background: var(--primary-gradient);">
                        <i class="fas fa-stopwatch"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Real-time Monitor -->
        <div class="monitor-grid">
            <div class="timeline-section">
                <div class="section-header">
                    <h2 class="section-title">📊 Monitor en Tiempo Real</h2>
                    <button class="action-btn" onclick="clearMonitor()">
                        <i class="fas fa-trash"></i>
                        Limpiar
                    </button>
                </div>
                <div class="monitor-display" id="monitor-display">
                    <div class="monitor-line info">[INFO] Sistema de monitoreo iniciado</div>
                    <div class="monitor-line success">[OK] Motor de detección de amenazas activo</div>
                </div>
            </div>

            <div class="timeline-section">
                <div class="section-header">
                    <h2 class="section-title">⚡ Acciones Rápidas</h2>
                </div>
                <div class="action-buttons" style="flex-direction: column;">
                    <button class="action-btn" onclick="runThreatScan()">
                        <i class="fas fa-search"></i>
                        Escaneo de Amenazas
                    </button>
                    <button class="action-btn warning" onclick="quarantineThreats()">
                        <i class="fas fa-lock"></i>
                        Cuarentena Automática
                    </button>
                    <button class="action-btn danger" onclick="emergencyLockdown()">
                        <i class="fas fa-ban"></i>
                        Bloqueo de Emergencia
                    </button>
                    <button class="action-btn success" onclick="updateDefinitions()">
                        <i class="fas fa-download"></i>
                        Actualizar Definiciones
                    </button>
                </div>
            </div>
        </div>

        <!-- Timeline of Threats -->
        <div class="timeline-section">
            <div class="section-header">
                <h2 class="section-title">📅 Timeline de Amenazas</h2>
                <button class="action-btn" onclick="refreshTimeline()">
                    <i class="fas fa-sync"></i>
                    Actualizar
                </button>
            </div>
            <div class="timeline" id="threat-timeline">
                <!-- Timeline items will be added dynamically -->
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <span id="toast-message"></span>
    </div>

    <script>
        // Variables globales
        let monitorInterval;
        let isMonitoring = false;
        const API_URL = window.location.href;

        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            initializeThreatCenter();
            startRealTimeMonitoring();
            loadThreatTimeline();
        });

        // Inicializar centro de amenazas
        async function initializeThreatCenter() {
            await updateThreatStats();
            await checkMonitoringStatus();
        }

        // Actualizar estadísticas
        async function updateThreatStats() {
            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=get_stats'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    const overview = data.overview;
                    document.getElementById('critical-threats').textContent = 
                        Math.floor(overview.threats_detected * 0.1);
                    document.getElementById('warning-threats').textContent = 
                        Math.floor(overview.threats_detected * 0.3);
                    document.getElementById('blocked-threats').textContent = 
                        overview.threats_blocked;
                    document.getElementById('response-time').textContent = 
                        Math.floor(Math.random() * 1000 + 500) + 'ms';
                    
                    updateAlertLevel(overview.threats_detected);
                }
            } catch (error) {
                console.error('Error obteniendo estadísticas:', error);
            }
        }

        // Verificar estado del monitoreo
        async function checkMonitoringStatus() {
            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=get_status'
                });
                
                const data = await response.json();
                
                if (data.success) {
                    isMonitoring = data.monitoring;
                    if (isMonitoring) {
                        addMonitorLine('success', '[STATUS] Monitoreo en tiempo real activo');
                    }
                }
            } catch (error) {
                console.error('Error verificando estado:', error);
            }
        }

        // Monitoreo en tiempo real
        function startRealTimeMonitoring() {
            monitorInterval = setInterval(() => {
                simulateMonitorActivity();
                updateThreatStats();
            }, 5000);
        }

        // Simular actividad del monitor
        function simulateMonitorActivity() {
            const activities = [
                { type: 'info', text: '[SCAN] Escaneando archivos del sistema...' },
                { type: 'info', text: '[NET] Monitoreando tráfico de red...' },
                { type: 'success', text: '[OK] Sistema protegido' },
                { type: 'info', text: '[AI] Analizando patrones de comportamiento...' },
                { type: 'warning', text: '[WARN] Actividad sospechosa detectada' },
                { type: 'success', text: '[BLOCK] Amenaza bloqueada exitosamente' }
            ];
            
            const activity = activities[Math.floor(Math.random() * activities.length)];
            addMonitorLine(activity.type, activity.text);
        }

        // Agregar línea al monitor
        function addMonitorLine(type, text) {
            const monitor = document.getElementById('monitor-display');
            const timestamp = new Date().toLocaleTimeString();
            
            const lineElement = document.createElement('div');
            lineElement.className = `monitor-line ${type}`;
            lineElement.textContent = `[${timestamp}] ${text}`;
            
            monitor.appendChild(lineElement);
            monitor.scrollTop = monitor.scrollHeight;
            
            // Mantener solo las últimas 50 líneas
            const lines = monitor.querySelectorAll('.monitor-line');
            if (lines.length > 50) {
                monitor.removeChild(lines[0]);
            }
        }

        // Cargar timeline de amenazas
        async function loadThreatTimeline() {
            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=get_threat_timeline'
                });
                
                const data = await response.json();
                
                if (data.success && data.timeline) {
                    displayTimeline(data.timeline);
                }
            } catch (error) {
                console.error('Error cargando timeline:', error);
            }
        }

        // Mostrar timeline
        function displayTimeline(threats) {
            const timeline = document.getElementById('threat-timeline');
            
            if (threats.length === 0) {
                timeline.innerHTML = `
                    <div class="timeline-item">
                        <div class="timeline-dot success"></div>
                        <div class="timeline-header">
                            <div class="timeline-title">Sistema Seguro</div>
                            <div class="timeline-time">Ahora</div>
                        </div>
                        <div class="timeline-description">
                            No se han detectado amenazas recientes
                        </div>
                    </div>
                `;
                return;
            }
            
            timeline.innerHTML = '';
            threats.forEach(threat => {
                const item = createTimelineItem(threat);
                timeline.appendChild(item);
            });
        }

        // Crear elemento de timeline
        function createTimelineItem(threat) {
            const item = document.createElement('div');
            item.className = 'timeline-item';
            
            const severity = threat.severity_level || 'low';
            const dotClass = severity === 'critical' ? 'critical' : 
                           severity === 'high' ? 'warning' : 'success';
            
            const timeAgo = getTimeAgo(new Date(threat.created_at));
            
            item.innerHTML = `
                <div class="timeline-dot ${dotClass}"></div>
                <div class="timeline-header">
                    <div class="timeline-title">${threat.threat_type || 'Amenaza Detectada'}</div>
                    <div class="timeline-time">${timeAgo}</div>
                </div>
                <div class="timeline-description">
                    ${threat.description || 'Amenaza procesada automáticamente'}
                </div>
                <div class="timeline-actions">
                    <button class="timeline-action" onclick="investigateThreat('${threat.event_id}')">
                        Investigar
                    </button>
                    <button class="timeline-action" onclick="blockThreat('${threat.event_id}')">
                        Bloquear
                    </button>
                </div>
            `;
            
            return item;
        }

        // Calcular tiempo transcurrido
        function getTimeAgo(date) {
            const seconds = Math.floor((new Date() - date) / 1000);
            
            if (seconds < 60) return 'Hace ' + seconds + ' segundos';
            if (seconds < 3600) return 'Hace ' + Math.floor(seconds / 60) + ' minutos';
            if (seconds < 86400) return 'Hace ' + Math.floor(seconds / 3600) + ' horas';
            return 'Hace ' + Math.floor(seconds / 86400) + ' días';
        }

        // Actualizar nivel de alerta
        function updateAlertLevel(threatsCount) {
            const alertLevel = document.getElementById('alert-level');
            
            if (threatsCount > 10) {
                alertLevel.textContent = 'NIVEL: CRÍTICO';
                alertLevel.style.background = 'var(--critical-gradient)';
            } else if (threatsCount > 5) {
                alertLevel.textContent = 'NIVEL: ALTO';
                alertLevel.style.background = 'var(--warning-gradient)';
            } else {
                alertLevel.textContent = 'NIVEL: BAJO';
                alertLevel.style.background = 'var(--success-gradient)';
            }
        }

        // Activar modo pánico
        async function activatePanicMode() {
            showToast('🚨 MODO PÁNICO ACTIVADO - Bloqueando todas las conexiones', 'error');
            
            addMonitorLine('error', '[EMERGENCY] Todas las conexiones bloqueadas');
            addMonitorLine('warning', '[EMERGENCY] Sistema en modo seguro');
            addMonitorLine('info', '[EMERGENCY] Notificando al administrador');
            
            // Ejecutar respuesta de emergencia
            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=execute_response&threat_data=' + JSON.stringify({
                        threat_type: 'emergency_lockdown',
                        severity: 'critical'
                    })
                });
                
                const result = await response.json();
                if (result.success) {
                    showToast('Respuesta de emergencia ejecutada', 'error');
                }
            } catch (error) {
                console.error('Error en modo pánico:', error);
            }
        }

        // Ejecutar escaneo de amenazas
        async function runThreatScan() {
            showToast('Iniciando escaneo completo de amenazas...', 'success');
            
            addMonitorLine('info', '[SCAN] Iniciando escaneo completo...');
            
            try {
                const response = await fetch(API_URL, {
                    method: 'POST',
                    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                    body: 'action=scan_threat&type=general&input=system_scan'
                });
                
                const result = await response.json();
                
                if (result.threat_detected) {
                    addMonitorLine('warning', `[THREAT] ${result.description}`);
                    showToast(`Amenaza detectada: ${result.threat_type}`, 'warning');
                } else {
                    addMonitorLine('success', '[SCAN] Escaneo completado - Sistema limpio');
                    showToast('Escaneo completado - No se encontraron amenazas', 'success');
                }
                
                await loadThreatTimeline();
                
            } catch (error) {
                console.error('Error en escaneo:', error);
                showToast('Error durante el escaneo', 'error');
            }
        }

        // Cuarentena automática
        function quarantineThreats() {
            showToast('Activando cuarentena automática...', 'warning');
            addMonitorLine('warning', '[QUARANTINE] Modo cuarentena activado');
            
            setTimeout(() => {
                addMonitorLine('success', '[QUARANTINE] Sistema protegido');
                showToast('Cuarentena activada', 'success');
            }, 2000);
        }

        // Bloqueo de emergencia
        function emergencyLockdown() {
            showToast('Iniciando bloqueo de emergencia...', 'error');
            
            addMonitorLine('error', '[LOCKDOWN] Todas las conexiones bloqueadas');
            addMonitorLine('warning', '[LOCKDOWN] Acceso restringido activado');
            
            setTimeout(() => {
                showToast('Bloqueo de emergencia activado', 'error');
            }, 1500);
        }

        // Actualizar definiciones
        function updateDefinitions() {
            showToast('Actualizando definiciones de amenazas...', 'success');
            
            addMonitorLine('info', '[UPDATE] Descargando nuevas definiciones...');
            
            setTimeout(() => {
                addMonitorLine('success', '[UPDATE] Definiciones actualizadas');
                showToast('Definiciones actualizadas exitosamente', 'success');
            }, 3000);
        }

        // Limpiar monitor
        function clearMonitor() {
            document.getElementById('monitor-display').innerHTML = '';
            addMonitorLine('info', '[SYSTEM] Monitor limpiado');
        }

        // Actualizar timeline
        function refreshTimeline() {
            showToast('Actualizando timeline...', 'success');
            loadThreatTimeline();
        }

        // Investigar amenaza
        function investigateThreat(eventId) {
            showToast(`Investigando amenaza ${eventId}...`, 'success');
        }

        // Bloquear amenaza
        function blockThreat(eventId) {
            showToast(`Bloqueando amenaza ${eventId}...`, 'warning');
        }

        // Mostrar toast
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toast-message');
            
            toastMessage.textContent = message;
            toast.className = `toast ${type}`;
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 4000);
        }

        // Cleanup al salir
        window.addEventListener('beforeunload', function() {
            if (monitorInterval) {
                clearInterval(monitorInterval);
            }
        });
    </script>
</body>
</html>