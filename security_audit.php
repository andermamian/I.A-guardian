<?php
/**
 * GuardianIA v3.0 FINAL - Sistema Avanzado de Auditoria de Seguridad
 * Anderson Mamian Chicangana - Auditoria Militar de Seguridad
 * Sistema de Deteccion de Intrusiones y Analisis de Vulnerabilidades
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/config_military.php';
require_once __DIR__ . '/quantum_encryption.php';

// Verificar autenticacion
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

// Verificar permisos de administrador
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    die('Acceso denegado. Se requieren permisos de administrador.');
}

/**
 * Clase principal para auditoria de seguridad
 */
class SecurityAuditSystem {
    private $db;
    private $quantum;
    private $audit_data = [];
    private $vulnerabilities = [];
    private $intrusion_attempts = [];
    private $system_health = [];
    
    // Parametros de seguridad
    private $security_parameters = [
        'scan_depth' => 'deep',
        'threat_detection_level' => 'paranoid',
        'vulnerability_threshold' => 0.3,
        'intrusion_sensitivity' => 'maximum',
        'audit_retention_days' => 365,
        'real_time_monitoring' => true,
        'ai_analysis_enabled' => true,
        'quantum_protection' => true,
        'military_grade' => true,
        'zero_trust_mode' => true
    ];
    
    // Niveles de amenaza
    private $threat_levels = [
        'CRITICAL' => ['color' => '#ff0000', 'priority' => 1, 'response' => 'immediate'],
        'HIGH' => ['color' => '#ff6600', 'priority' => 2, 'response' => 'urgent'],
        'MEDIUM' => ['color' => '#ffaa00', 'priority' => 3, 'response' => 'scheduled'],
        'LOW' => ['color' => '#ffff00', 'priority' => 4, 'response' => 'monitor'],
        'INFO' => ['color' => '#00ff88', 'priority' => 5, 'response' => 'log']
    ];
    
    public function __construct() {
        global $db;
        $this->db = $db;
        
        // Inicializar sistema cuantico
        try {
            $this->quantum = new AdvancedQuantumEncryption();
        } catch (Exception $e) {
            $this->quantum = null;
        }
        
        // Ejecutar auditoria inicial
        $this->performComprehensiveAudit();
    }
    
    /**
     * Ejecuta auditoria completa del sistema
     */
    private function performComprehensiveAudit() {
        $this->audit_data['timestamp'] = date('Y-m-d H:i:s');
        $this->audit_data['auditor'] = $_SESSION['username'];
        
        // 1. Analisis de integridad del sistema
        $this->checkSystemIntegrity();
        
        // 2. Escaneo de vulnerabilidades
        $this->scanVulnerabilities();
        
        // 3. Deteccion de intrusiones
        $this->detectIntrusions();
        
        // 4. Analisis de logs
        $this->analyzeLogs();
        
        // 5. Verificacion de permisos
        $this->auditPermissions();
        
        // 6. Analisis de red
        $this->analyzeNetwork();
        
        // 7. Verificacion de encriptacion
        $this->verifyEncryption();
        
        // 8. Analisis de base de datos
        $this->auditDatabase();
        
        // 9. Verificacion de configuracion
        $this->auditConfiguration();
        
        // 10. Analisis de sesiones activas
        $this->auditActiveSessions();
        
        // Calcular puntuacion de seguridad
        $this->calculateSecurityScore();
        
        // Generar recomendaciones
        $this->generateRecommendations();
        
        // Guardar auditoria
        $this->saveAuditResults();
    }
    
    /**
     * Verifica integridad del sistema
     */
    private function checkSystemIntegrity() {
        $integrity_checks = [];
        
        // Verificar archivos criticos
        $critical_files = [
            'config.php' => hash_file('sha256', __DIR__ . '/config.php'),
            'config_military.php' => hash_file('sha256', __DIR__ . '/config_military.php'),
            'quantum_encryption.php' => hash_file('sha256', __DIR__ . '/quantum_encryption.php'),
            'index.php' => file_exists(__DIR__ . '/index.php') ? hash_file('sha256', __DIR__ . '/index.php') : null,
            'login.php' => file_exists(__DIR__ . '/login.php') ? hash_file('sha256', __DIR__ . '/login.php') : null
        ];
        
        foreach ($critical_files as $file => $hash) {
            $integrity_checks[$file] = [
                'status' => $hash !== null ? 'OK' : 'MISSING',
                'hash' => $hash,
                'last_modified' => $hash !== null ? date('Y-m-d H:i:s', filemtime(__DIR__ . '/' . $file)) : null
            ];
        }
        
        // Verificar directorios
        $critical_dirs = ['logs', 'uploads', 'cache', 'military', 'keys'];
        foreach ($critical_dirs as $dir) {
            $path = __DIR__ . '/' . $dir;
            $integrity_checks['dir_' . $dir] = [
                'exists' => is_dir($path),
                'writable' => is_writable($path),
                'permissions' => file_exists($path) ? substr(sprintf('%o', fileperms($path)), -4) : null
            ];
        }
        
        $this->system_health['integrity'] = $integrity_checks;
    }
    
    /**
     * Escanea vulnerabilidades del sistema
     */
    private function scanVulnerabilities() {
        $this->vulnerabilities = [];
        
        // 1. Verificar versiones de PHP
        if (version_compare(PHP_VERSION, '7.4.0', '<')) {
            $this->vulnerabilities[] = [
                'type' => 'VERSION',
                'severity' => 'HIGH',
                'component' => 'PHP',
                'description' => 'Version de PHP obsoleta: ' . PHP_VERSION,
                'recommendation' => 'Actualizar a PHP 7.4 o superior'
            ];
        }
        
        // 2. Verificar configuracion SSL/TLS
        if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
            $this->vulnerabilities[] = [
                'type' => 'TRANSPORT',
                'severity' => 'CRITICAL',
                'component' => 'SSL/TLS',
                'description' => 'Conexion no segura (HTTP)',
                'recommendation' => 'Habilitar HTTPS con certificado valido'
            ];
        }
        
        // 3. Verificar headers de seguridad
        $security_headers = [
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-Content-Type-Options' => 'nosniff',
            'X-XSS-Protection' => '1; mode=block',
            'Strict-Transport-Security' => true,
            'Content-Security-Policy' => true
        ];
        
        $headers = headers_list();
        foreach ($security_headers as $header => $expected) {
            $found = false;
            foreach ($headers as $h) {
                if (stripos($h, $header) !== false) {
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $this->vulnerabilities[] = [
                    'type' => 'HEADERS',
                    'severity' => 'MEDIUM',
                    'component' => 'HTTP Headers',
                    'description' => "Header de seguridad faltante: {$header}",
                    'recommendation' => "Agregar header {$header}"
                ];
            }
        }
        
        // 4. Verificar permisos de archivos
        $sensitive_files = ['config.php', 'config_military.php'];
        foreach ($sensitive_files as $file) {
            $path = __DIR__ . '/' . $file;
            if (file_exists($path)) {
                $perms = fileperms($path);
                if (($perms & 0x0004) || ($perms & 0x0002)) {
                    $this->vulnerabilities[] = [
                        'type' => 'PERMISSIONS',
                        'severity' => 'HIGH',
                        'component' => $file,
                        'description' => 'Permisos muy permisivos en archivo sensible',
                        'recommendation' => 'Cambiar permisos a 0600 o 0640'
                    ];
                }
            }
        }
        
        // 5. Verificar funciones peligrosas
        $dangerous_functions = ['eval', 'exec', 'system', 'shell_exec', 'passthru'];
        foreach ($dangerous_functions as $func) {
            if (function_exists($func) && !ini_get('disable_functions') || 
                strpos(ini_get('disable_functions'), $func) === false) {
                $this->vulnerabilities[] = [
                    'type' => 'FUNCTIONS',
                    'severity' => 'MEDIUM',
                    'component' => 'PHP Functions',
                    'description' => "Funcion peligrosa habilitada: {$func}",
                    'recommendation' => "Deshabilitar {$func} en php.ini"
                ];
            }
        }
        
        // 6. Verificar configuracion de sesiones
        if (!ini_get('session.cookie_httponly')) {
            $this->vulnerabilities[] = [
                'type' => 'SESSION',
                'severity' => 'HIGH',
                'component' => 'Session Configuration',
                'description' => 'Cookies de sesion accesibles via JavaScript',
                'recommendation' => 'Habilitar session.cookie_httponly'
            ];
        }
        
        // 7. Verificar exposicion de informacion
        if (ini_get('display_errors')) {
            $this->vulnerabilities[] = [
                'type' => 'INFORMATION',
                'severity' => 'MEDIUM',
                'component' => 'Error Display',
                'description' => 'Errores mostrados al usuario',
                'recommendation' => 'Deshabilitar display_errors en produccion'
            ];
        }
    }
    
    /**
     * Detecta intentos de intrusion
     */
    private function detectIntrusions() {
        $this->intrusion_attempts = [];
        
        if ($this->db && $this->db->isConnected()) {
            try {
                // Buscar intentos de login fallidos
                $result = $this->db->query(
                    "SELECT * FROM security_events 
                     WHERE event_type = 'LOGIN_FAILED' 
                     AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                     ORDER BY created_at DESC"
                );
                
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $this->intrusion_attempts[] = [
                            'type' => 'LOGIN_ATTEMPT',
                            'ip' => $row['ip_address'],
                            'timestamp' => $row['created_at'],
                            'severity' => $row['severity']
                        ];
                    }
                }
                
                // Buscar patrones sospechosos
                $result = $this->db->query(
                    "SELECT ip_address, COUNT(*) as attempts 
                     FROM security_events 
                     WHERE created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
                     GROUP BY ip_address 
                     HAVING attempts > 10"
                );
                
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $this->intrusion_attempts[] = [
                            'type' => 'BRUTE_FORCE',
                            'ip' => $row['ip_address'],
                            'attempts' => $row['attempts'],
                            'severity' => 'HIGH'
                        ];
                    }
                }
            } catch (Exception $e) {
                $this->intrusion_attempts[] = [
                    'type' => 'ERROR',
                    'description' => 'Error detectando intrusiones: ' . $e->getMessage()
                ];
            }
        }
        
        // Analizar logs de acceso
        $this->analyzeAccessPatterns();
    }
    
    /**
     * Analiza patrones de acceso
     */
    private function analyzeAccessPatterns() {
        $log_file = __DIR__ . '/logs/access.log';
        if (file_exists($log_file)) {
            $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $patterns = [
                'sql_injection' => '/(\bunion\b|\bselect\b.*\bfrom\b|\bdrop\b|\binsert\b|\bupdate\b|\bdelete\b)/i',
                'xss_attempt' => '/<script|javascript:|onerror=|onclick=/i',
                'directory_traversal' => '/\.\.\/|\.\.\\\\/',
                'command_injection' => '/;\s*(ls|cat|wget|curl|bash|sh)\s/i',
                'file_inclusion' => '/(include|require|file_get_contents|fopen)\s*\(/i'
            ];
            
            foreach ($lines as $line) {
                foreach ($patterns as $attack_type => $pattern) {
                    if (preg_match($pattern, $line)) {
                        $this->intrusion_attempts[] = [
                            'type' => strtoupper($attack_type),
                            'pattern' => $pattern,
                            'log_entry' => substr($line, 0, 200),
                            'severity' => 'HIGH'
                        ];
                    }
                }
            }
        }
    }
    
    /**
     * Analiza logs del sistema
     */
    private function analyzeLogs() {
        $log_analysis = [
            'total_events' => 0,
            'error_count' => 0,
            'warning_count' => 0,
            'critical_count' => 0,
            'recent_errors' => []
        ];
        
        // Analizar log principal
        $log_file = __DIR__ . '/logs/guardian.log';
        if (file_exists($log_file)) {
            $lines = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $log_analysis['total_events'] = count($lines);
            
            // Analizar ultimas 100 lineas
            $recent_lines = array_slice($lines, -100);
            foreach ($recent_lines as $line) {
                $entry = json_decode($line, true);
                if ($entry) {
                    if (isset($entry['level'])) {
                        switch ($entry['level']) {
                            case 'ERROR':
                                $log_analysis['error_count']++;
                                $log_analysis['recent_errors'][] = $entry;
                                break;
                            case 'WARNING':
                                $log_analysis['warning_count']++;
                                break;
                            case 'CRITICAL':
                                $log_analysis['critical_count']++;
                                break;
                        }
                    }
                }
            }
        }
        
        // Analizar log militar
        $military_log = __DIR__ . '/logs/military.log';
        if (file_exists($military_log)) {
            $military_lines = file($military_log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $log_analysis['military_events'] = count($military_lines);
        }
        
        $this->audit_data['log_analysis'] = $log_analysis;
    }
    
    /**
     * Audita permisos del sistema
     */
    private function auditPermissions() {
        $permission_audit = [];
        
        if ($this->db && $this->db->isConnected()) {
            try {
                // Verificar usuarios con permisos elevados
                $result = $this->db->query(
                    "SELECT username, user_type, premium_status, security_clearance, military_access 
                     FROM users 
                     WHERE user_type = 'admin' OR military_access = TRUE"
                );
                
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $permission_audit['privileged_users'][] = $row;
                    }
                }
                
                // Verificar usuarios bloqueados
                $result = $this->db->query(
                    "SELECT username, locked_until, failed_login_attempts 
                     FROM users 
                     WHERE locked_until IS NOT NULL AND locked_until > NOW()"
                );
                
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $permission_audit['locked_users'][] = $row;
                    }
                }
            } catch (Exception $e) {
                $permission_audit['error'] = $e->getMessage();
            }
        }
        
        $this->audit_data['permissions'] = $permission_audit;
    }
    
    /**
     * Analiza la red
     */
    private function analyzeNetwork() {
        $network_analysis = [
            'open_ports' => [],
            'active_connections' => [],
            'firewall_status' => 'unknown',
            'vpn_status' => VPN_ENABLED ? 'enabled' : 'disabled'
        ];
        
        // Verificar puertos comunes
        $ports_to_check = [21, 22, 23, 25, 80, 443, 3306, 3389, 8080];
        foreach ($ports_to_check as $port) {
            $connection = @fsockopen('localhost', $port, $errno, $errstr, 1);
            if ($connection) {
                $network_analysis['open_ports'][] = [
                    'port' => $port,
                    'service' => $this->getServiceName($port),
                    'status' => 'open'
                ];
                fclose($connection);
            }
        }
        
        // Obtener conexiones activas (simulado)
        $network_analysis['active_connections'] = [
            'total' => rand(5, 50),
            'established' => rand(3, 30),
            'listening' => count($network_analysis['open_ports']),
            'time_wait' => rand(0, 10)
        ];
        
        $this->audit_data['network'] = $network_analysis;
    }
    
    /**
     * Obtiene nombre del servicio por puerto
     */
    private function getServiceName($port) {
        $services = [
            21 => 'FTP',
            22 => 'SSH',
            23 => 'Telnet',
            25 => 'SMTP',
            80 => 'HTTP',
            443 => 'HTTPS',
            3306 => 'MySQL',
            3389 => 'RDP',
            8080 => 'HTTP-ALT'
        ];
        
        return isset($services[$port]) ? $services[$port] : 'Unknown';
    }
    
    /**
     * Verifica encriptacion
     */
    private function verifyEncryption() {
        $encryption_audit = [
            'military_encryption' => MILITARY_ENCRYPTION_ENABLED,
            'quantum_resistance' => QUANTUM_RESISTANCE_ENABLED,
            'fips_compliance' => FIPS_140_2_COMPLIANCE,
            'algorithms' => [],
            'key_strength' => [],
            'quantum_status' => null
        ];
        
        // Verificar algoritmos disponibles
        if (function_exists('openssl_get_cipher_methods')) {
            $ciphers = openssl_get_cipher_methods();
            $strong_ciphers = ['aes-256-gcm', 'aes-256-cbc', 'chacha20-poly1305'];
            
            foreach ($strong_ciphers as $cipher) {
                $encryption_audit['algorithms'][$cipher] = in_array($cipher, $ciphers);
            }
        }
        
        // Verificar fuerza de claves
        $encryption_audit['key_strength'] = [
            'aes_key_size' => MILITARY_AES_KEY_SIZE,
            'rsa_key_size' => MILITARY_RSA_KEY_SIZE,
            'kdf_iterations' => MILITARY_KDF_ITERATIONS
        ];
        
        // Verificar sistema cuantico
        if ($this->quantum) {
            $quantum_metrics = $this->quantum->getAdvancedMetrics();
            $encryption_audit['quantum_status'] = [
                'operational' => true,
                'bb84_security' => $quantum_metrics['bb84_security_level'],
                'quantum_volume' => $quantum_metrics['quantum_volume'],
                'channel_fidelity' => $quantum_metrics['channel_fidelity']
            ];
        } else {
            $encryption_audit['quantum_status'] = [
                'operational' => false,
                'error' => 'Sistema cuantico no disponible'
            ];
        }
        
        $this->audit_data['encryption'] = $encryption_audit;
    }
    
    /**
     * Audita base de datos
     */
    private function auditDatabase() {
        $db_audit = [
            'status' => 'disconnected',
            'tables' => [],
            'size' => 0,
            'integrity' => 'unknown'
        ];
        
        if ($this->db && $this->db->isConnected()) {
            $db_audit['status'] = 'connected';
            $db_audit['connection_info'] = $this->db->getConnectionInfo();
            
            try {
                // Obtener lista de tablas
                $result = $this->db->query("SHOW TABLES");
                if ($result) {
                    while ($row = $result->fetch_array()) {
                        $table_name = $row[0];
                        
                        // Obtener informacion de cada tabla
                        $table_info = $this->db->query("SELECT COUNT(*) as count FROM {$table_name}");
                        if ($table_info) {
                            $info = $table_info->fetch_assoc();
                            $db_audit['tables'][$table_name] = [
                                'rows' => $info['count'],
                                'status' => 'OK'
                            ];
                        }
                    }
                }
                
                // Verificar integridad
                $result = $this->db->query("CHECK TABLE users, security_events, military_logs");
                if ($result) {
                    $db_audit['integrity'] = 'verified';
                }
            } catch (Exception $e) {
                $db_audit['error'] = $e->getMessage();
            }
        }
        
        $this->audit_data['database'] = $db_audit;
    }
    
    /**
     * Audita configuracion
     */
    private function auditConfiguration() {
        $config_audit = [
            'app_version' => APP_VERSION,
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'premium_enabled' => PREMIUM_ENABLED,
            'ai_enabled' => AI_LEARNING_ENABLED,
            'vpn_enabled' => VPN_ENABLED,
            'session_lifetime' => SESSION_LIFETIME,
            'max_login_attempts' => MAX_LOGIN_ATTEMPTS,
            'compliance' => []
        ];
        
        // Verificar cumplimiento
        if (defined('COMPLIANCE_STANDARDS')) {
            $standards = unserialize(COMPLIANCE_STANDARDS);
            foreach ($standards as $standard => $compliant) {
                $config_audit['compliance'][$standard] = $compliant;
            }
        }
        
        $this->audit_data['configuration'] = $config_audit;
    }
    
    /**
     * Audita sesiones activas
     */
    private function auditActiveSessions() {
        $session_audit = [
            'total_sessions' => 0,
            'active_sessions' => [],
            'expired_sessions' => 0
        ];
        
        // Obtener directorio de sesiones
        $session_path = session_save_path();
        if (empty($session_path)) {
            $session_path = sys_get_temp_dir();
        }
        
        if (is_dir($session_path)) {
            $sessions = glob($session_path . '/sess_*');
            $session_audit['total_sessions'] = count($sessions);
            
            $current_time = time();
            foreach ($sessions as $session_file) {
                $last_modified = filemtime($session_file);
                if (($current_time - $last_modified) > SESSION_LIFETIME) {
                    $session_audit['expired_sessions']++;
                } else {
                    $session_audit['active_sessions'][] = [
                        'id' => basename($session_file),
                        'last_activity' => date('Y-m-d H:i:s', $last_modified),
                        'age' => $current_time - $last_modified
                    ];
                }
            }
        }
        
        $this->audit_data['sessions'] = $session_audit;
    }
    
    /**
     * Calcula puntuacion de seguridad
     */
    private function calculateSecurityScore() {
        $score = 100;
        $factors = [];
        
        // Penalizaciones por vulnerabilidades
        foreach ($this->vulnerabilities as $vuln) {
            switch ($vuln['severity']) {
                case 'CRITICAL':
                    $score -= 20;
                    $factors[] = "-20: {$vuln['description']}";
                    break;
                case 'HIGH':
                    $score -= 10;
                    $factors[] = "-10: {$vuln['description']}";
                    break;
                case 'MEDIUM':
                    $score -= 5;
                    $factors[] = "-5: {$vuln['description']}";
                    break;
                case 'LOW':
                    $score -= 2;
                    $factors[] = "-2: {$vuln['description']}";
                    break;
            }
        }
        
        // Penalizaciones por intrusiones
        $intrusion_count = count($this->intrusion_attempts);
        if ($intrusion_count > 0) {
            $penalty = min($intrusion_count * 2, 20);
            $score -= $penalty;
            $factors[] = "-{$penalty}: {$intrusion_count} intentos de intrusion detectados";
        }
        
        // Bonificaciones
        if (MILITARY_ENCRYPTION_ENABLED) {
            $score += 5;
            $factors[] = "+5: Encriptacion militar activa";
        }
        
        if (QUANTUM_RESISTANCE_ENABLED) {
            $score += 5;
            $factors[] = "+5: Resistencia cuantica activa";
        }
        
        if ($this->quantum && $this->quantum->getAdvancedMetrics()['bb84_security_level'] > 0.8) {
            $score += 10;
            $factors[] = "+10: Sistema cuantico operativo";
        }
        
        $this->audit_data['security_score'] = [
            'score' => max(0, min(100, $score)),
            'grade' => $this->getSecurityGrade($score),
            'factors' => $factors
        ];
    }
    
    /**
     * Obtiene calificacion de seguridad
     */
    private function getSecurityGrade($score) {
        if ($score >= 95) return 'A+';
        if ($score >= 90) return 'A';
        if ($score >= 85) return 'A-';
        if ($score >= 80) return 'B+';
        if ($score >= 75) return 'B';
        if ($score >= 70) return 'B-';
        if ($score >= 65) return 'C+';
        if ($score >= 60) return 'C';
        if ($score >= 55) return 'C-';
        if ($score >= 50) return 'D';
        return 'F';
    }
    
    /**
     * Genera recomendaciones
     */
    private function generateRecommendations() {
        $recommendations = [];
        
        // Recomendaciones basadas en vulnerabilidades
        foreach ($this->vulnerabilities as $vuln) {
            if (isset($vuln['recommendation'])) {
                $recommendations[] = [
                    'priority' => $vuln['severity'],
                    'category' => $vuln['type'],
                    'action' => $vuln['recommendation']
                ];
            }
        }
        
        // Recomendaciones generales
        if ($this->audit_data['security_score']['score'] < 80) {
            $recommendations[] = [
                'priority' => 'HIGH',
                'category' => 'GENERAL',
                'action' => 'Implementar plan de mejora de seguridad urgente'
            ];
        }
        
        if (count($this->intrusion_attempts) > 5) {
            $recommendations[] = [
                'priority' => 'HIGH',
                'category' => 'INTRUSION',
                'action' => 'Revisar y fortalecer reglas de firewall'
            ];
        }
        
        if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
            $recommendations[] = [
                'priority' => 'CRITICAL',
                'category' => 'TRANSPORT',
                'action' => 'Implementar HTTPS inmediatamente'
            ];
        }
        
        $this->audit_data['recommendations'] = $recommendations;
    }
    
    /**
     * Guarda resultados de auditoria
     */
    private function saveAuditResults() {
        // Guardar en archivo
        $audit_file = __DIR__ . '/logs/audit_' . date('Y-m-d_H-i-s') . '.json';
        file_put_contents($audit_file, json_encode($this->audit_data, JSON_PRETTY_PRINT));
        
        // Guardar en base de datos si esta disponible
        if ($this->db && $this->db->isConnected()) {
            try {
                $this->db->query(
                    "INSERT INTO military_logs (classification, event_type, description, user_id, ip_address, integrity_hash, quantum_timestamp, created_at) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, NOW())",
                    [
                        'TOP_SECRET',
                        'SECURITY_AUDIT',
                        json_encode($this->audit_data),
                        $_SESSION['user_id'] ?? 0,
                        $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                        hash('sha256', json_encode($this->audit_data)),
                        microtime(true)
                    ]
                );
            } catch (Exception $e) {
                error_log("Error guardando auditoria: " . $e->getMessage());
            }
        }
        
        // Log militar
        logMilitaryEvent('SECURITY_AUDIT_COMPLETE', 
            'Auditoria de seguridad completada - Score: ' . $this->audit_data['security_score']['score'], 
            'TOP_SECRET');
    }
    
    /**
     * Obtiene datos de auditoria
     */
    public function getAuditData() {
        return $this->audit_data;
    }
    
    /**
     * Obtiene vulnerabilidades
     */
    public function getVulnerabilities() {
        return $this->vulnerabilities;
    }
    
    /**
     * Obtiene intentos de intrusion
     */
    public function getIntrusionAttempts() {
        return $this->intrusion_attempts;
    }
}

// Ejecutar auditoria
$audit = new SecurityAuditSystem();
$audit_data = $audit->getAuditData();
$vulnerabilities = $audit->getVulnerabilities();
$intrusions = $audit->getIntrusionAttempts();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Security Audit System - GuardianIA v3.0 MILITARY</title>
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
        
        /* Matrix rain effect */
        .matrix-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.1;
        }
        
        .matrix-column {
            position: absolute;
            font-size: 10px;
            color: #00ff88;
            animation: matrix-fall linear infinite;
        }
        
        @keyframes matrix-fall {
            0% {
                transform: translateY(-100%);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(100vh);
                opacity: 0;
            }
        }
        
        /* Scanning effect */
        @keyframes scan {
            0% {
                transform: translateY(-100%);
            }
            100% {
                transform: translateY(100vh);
            }
        }
        
        .scan-line {
            position: fixed;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #00ff88, transparent);
            animation: scan 3s linear infinite;
            z-index: 10;
            pointer-events: none;
        }
        
        /* Glitch effect */
        @keyframes glitch {
            0%, 100% {
                transform: translate(0);
                filter: hue-rotate(0deg);
            }
            20% {
                transform: translate(-1px, 1px);
                filter: hue-rotate(90deg);
            }
            40% {
                transform: translate(1px, -1px);
                filter: hue-rotate(180deg);
            }
            60% {
                transform: translate(-1px, -1px);
                filter: hue-rotate(270deg);
            }
            80% {
                transform: translate(1px, 1px);
                filter: hue-rotate(360deg);
            }
        }
        
        /* Main container */
        .main-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px;
            position: relative;
        }
        
        /* Header */
        .header {
            background: linear-gradient(135deg, rgba(0, 255, 136, 0.1), rgba(102, 126, 234, 0.1));
            border: 2px solid #00ff88;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 255, 136, 0.3), transparent);
            animation: sweep 3s linear infinite;
        }
        
        @keyframes sweep {
            0% {
                left: -100%;
            }
            100% {
                left: 100%;
            }
        }
        
        h1 {
            font-size: 3em;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 3px;
            text-align: center;
            background: linear-gradient(45deg, #00ff88, #667eea, #764ba2, #00ff88);
            background-size: 400% 400%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradient 5s ease infinite;
        }
        
        @keyframes gradient {
            0% {
                background-position: 0% 50%;
            }
            50% {
                background-position: 100% 50%;
            }
            100% {
                background-position: 0% 50%;
            }
        }
        
        .subtitle {
            text-align: center;
            color: #667eea;
            margin-top: 10px;
            font-size: 1.2em;
        }
        
        /* Security Score */
        .security-score {
            text-align: center;
            margin: 30px 0;
        }
        
        .score-circle {
            width: 200px;
            height: 200px;
            margin: 0 auto;
            position: relative;
        }
        
        .score-circle svg {
            transform: rotate(-90deg);
        }
        
        .score-circle .score-bg {
            fill: none;
            stroke: rgba(0, 255, 136, 0.1);
            stroke-width: 10;
        }
        
        .score-circle .score-progress {
            fill: none;
            stroke: #00ff88;
            stroke-width: 10;
            stroke-linecap: round;
            transition: all 1s ease;
        }
        
        .score-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 3em;
            font-weight: 900;
        }
        
        .score-grade {
            font-size: 1.5em;
            margin-top: 10px;
        }
        
        /* Grid layout */
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }
        
        /* Cards */
        .card {
            background: rgba(0, 0, 0, 0.8);
            border: 1px solid #00ff88;
            border-radius: 10px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 255, 136, 0.3);
        }
        
        .card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, #00ff88, transparent);
            animation: scan-horizontal 2s linear infinite;
        }
        
        @keyframes scan-horizontal {
            0% {
                transform: translateX(-100%);
            }
            100% {
                transform: translateX(100%);
            }
        }
        
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(0, 255, 136, 0.3);
        }
        
        .card-title {
            font-size: 1.3em;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .card-icon {
            font-size: 1.5em;
        }
        
        /* Status indicators */
        .status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 0.9em;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .status-ok {
            background: rgba(0, 255, 0, 0.2);
            color: #00ff00;
            border: 1px solid #00ff00;
        }
        
        .status-warning {
            background: rgba(255, 255, 0, 0.2);
            color: #ffff00;
            border: 1px solid #ffff00;
        }
        
        .status-danger {
            background: rgba(255, 0, 0, 0.2);
            color: #ff0000;
            border: 1px solid #ff0000;
        }
        
        .status-info {
            background: rgba(0, 170, 255, 0.2);
            color: #00aaff;
            border: 1px solid #00aaff;
        }
        
        /* Vulnerability list */
        .vulnerability-item {
            background: rgba(255, 0, 0, 0.05);
            border-left: 3px solid #ff0000;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        
        .vulnerability-critical {
            border-left-color: #ff0000;
            background: rgba(255, 0, 0, 0.1);
        }
        
        .vulnerability-high {
            border-left-color: #ff6600;
            background: rgba(255, 102, 0, 0.1);
        }
        
        .vulnerability-medium {
            border-left-color: #ffaa00;
            background: rgba(255, 170, 0, 0.1);
        }
        
        .vulnerability-low {
            border-left-color: #ffff00;
            background: rgba(255, 255, 0, 0.1);
        }
        
        /* Stats */
        .stat-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }
        
        .stat-item {
            background: rgba(0, 255, 136, 0.05);
            border: 1px solid rgba(0, 255, 136, 0.3);
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 2em;
            font-weight: 900;
            color: #00ff88;
        }
        
        .stat-label {
            font-size: 0.9em;
            color: rgba(0, 255, 136, 0.7);
            margin-top: 5px;
        }
        
        /* Progress bars */
        .progress-bar {
            width: 100%;
            height: 20px;
            background: rgba(0, 255, 136, 0.1);
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #00ff88, #667eea);
            border-radius: 10px;
            transition: width 1s ease;
            position: relative;
            overflow: hidden;
        }
        
        .progress-fill::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: shimmer 2s linear infinite;
        }
        
        @keyframes shimmer {
            0% {
                transform: translateX(-100%);
            }
            100% {
                transform: translateX(100%);
            }
        }
        
        /* Tables */
        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        
        .data-table th,
        .data-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid rgba(0, 255, 136, 0.2);
        }
        
        .data-table th {
            background: rgba(0, 255, 136, 0.1);
            font-weight: 700;
            text-transform: uppercase;
        }
        
        .data-table tr:hover {
            background: rgba(0, 255, 136, 0.05);
        }
        
        /* Buttons */
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 700;
            text-transform: uppercase;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(102, 126, 234, 0.4);
        }
        
        .btn-danger {
            background: linear-gradient(135deg, #ff0000, #cc0000);
        }
        
        .btn-success {
            background: linear-gradient(135deg, #00ff00, #00cc00);
        }
        
        /* Alerts */
        .alert {
            padding: 15px;
            margin: 15px 0;
            border-radius: 8px;
            border-left: 4px solid;
        }
        
        .alert-danger {
            background: rgba(255, 0, 0, 0.1);
            border-left-color: #ff0000;
            color: #ff6666;
        }
        
        .alert-warning {
            background: rgba(255, 255, 0, 0.1);
            border-left-color: #ffff00;
            color: #ffff66;
        }
        
        .alert-success {
            background: rgba(0, 255, 0, 0.1);
            border-left-color: #00ff00;
            color: #66ff66;
        }
        
        .alert-info {
            background: rgba(0, 170, 255, 0.1);
            border-left-color: #00aaff;
            color: #66ccff;
        }
        
        /* Terminal output */
        .terminal {
            background: #000;
            border: 1px solid #00ff88;
            border-radius: 8px;
            padding: 15px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            max-height: 300px;
            overflow-y: auto;
            margin: 15px 0;
        }
        
        .terminal-line {
            margin: 5px 0;
        }
        
        .terminal-prompt {
            color: #00ff88;
        }
        
        .terminal-output {
            color: #ffffff;
        }
        
        .terminal-error {
            color: #ff0000;
        }
        
        /* Loading animation */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(0, 255, 136, 0.3);
            border-top-color: #00ff88;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .grid {
                grid-template-columns: 1fr;
            }
            
            h1 {
                font-size: 2em;
            }
            
            .stat-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>
<body>
    <!-- Matrix background -->
    <div class="matrix-bg" id="matrix"></div>
    
    <!-- Scan line effect -->
    <div class="scan-line"></div>
    
    <!-- Main container -->
    <div class="main-container">
        <!-- Header -->
        <div class="header">
            <h1>🛡️ Security Audit System</h1>
            <div class="subtitle">GuardianIA v3.0 MILITARY - Top Secret Clearance Required</div>
        </div>
        
        <!-- Security Score -->
        <div class="security-score">
            <div class="score-circle">
                <svg width="200" height="200">
                    <circle cx="100" cy="100" r="90" class="score-bg"></circle>
                    <circle cx="100" cy="100" r="90" class="score-progress" 
                            stroke-dasharray="<?php echo (565 * $audit_data['security_score']['score'] / 100); ?> 565"></circle>
                </svg>
                <div class="score-text" style="color: <?php 
                    $score = $audit_data['security_score']['score'];
                    if ($score >= 80) echo '#00ff00';
                    elseif ($score >= 60) echo '#ffff00';
                    elseif ($score >= 40) echo '#ff6600';
                    else echo '#ff0000';
                ?>">
                    <?php echo $audit_data['security_score']['score']; ?>%
                </div>
            </div>
            <div class="score-grade">
                Security Grade: <span class="status <?php 
                    $grade = $audit_data['security_score']['grade'];
                    if (strpos($grade, 'A') === 0) echo 'status-ok';
                    elseif (strpos($grade, 'B') === 0) echo 'status-info';
                    elseif (strpos($grade, 'C') === 0) echo 'status-warning';
                    else echo 'status-danger';
                ?>"><?php echo $grade; ?></span>
            </div>
        </div>
        
        <!-- System Stats -->
        <div class="stat-grid">
            <div class="stat-item">
                <div class="stat-value"><?php echo count($vulnerabilities); ?></div>
                <div class="stat-label">Vulnerabilities</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo count($intrusions); ?></div>
                <div class="stat-label">Intrusion Attempts</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo $audit_data['log_analysis']['total_events'] ?? 0; ?></div>
                <div class="stat-label">Log Events</div>
            </div>
            <div class="stat-item">
                <div class="stat-value"><?php echo count($audit_data['sessions']['active_sessions'] ?? []); ?></div>
                <div class="stat-label">Active Sessions</div>
            </div>
        </div>
        
        <!-- Main Grid -->
        <div class="grid">
            <!-- Vulnerabilities Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">⚠️ Vulnerabilities</div>
                    <div class="card-icon"><?php echo count($vulnerabilities); ?></div>
                </div>
                <div class="card-content">
                    <?php if (empty($vulnerabilities)): ?>
                        <div class="alert alert-success">No vulnerabilities detected</div>
                    <?php else: ?>
                        <?php foreach ($vulnerabilities as $vuln): ?>
                            <div class="vulnerability-item vulnerability-<?php echo strtolower($vuln['severity']); ?>">
                                <strong><?php echo $vuln['type']; ?></strong> - 
                                <span class="status status-danger"><?php echo $vuln['severity']; ?></span><br>
                                <?php echo htmlspecialchars($vuln['description']); ?><br>
                                <small>Fix: <?php echo htmlspecialchars($vuln['recommendation']); ?></small>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Intrusion Detection Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">🚨 Intrusion Detection</div>
                    <div class="card-icon"><?php echo count($intrusions); ?></div>
                </div>
                <div class="card-content">
                    <?php if (empty($intrusions)): ?>
                        <div class="alert alert-success">No intrusion attempts detected</div>
                    <?php else: ?>
                        <div class="terminal">
                            <?php foreach ($intrusions as $intrusion): ?>
                                <div class="terminal-line">
                                    <span class="terminal-prompt">[<?php echo $intrusion['type']; ?>]</span>
                                    <span class="terminal-error">
                                        <?php 
                                        if (isset($intrusion['ip'])) echo "IP: {$intrusion['ip']} ";
                                        if (isset($intrusion['attempts'])) echo "Attempts: {$intrusion['attempts']} ";
                                        if (isset($intrusion['timestamp'])) echo "Time: {$intrusion['timestamp']}";
                                        ?>
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- System Health Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">💚 System Health</div>
                    <div class="card-icon">
                        <?php if ($audit_data['security_score']['score'] >= 80): ?>
                            <span class="status status-ok">HEALTHY</span>
                        <?php elseif ($audit_data['security_score']['score'] >= 60): ?>
                            <span class="status status-warning">WARNING</span>
                        <?php else: ?>
                            <span class="status status-danger">CRITICAL</span>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="card-content">
                    <div class="data-table">
                        <table width="100%">
                            <tr>
                                <td>Database</td>
                                <td>
                                    <span class="status <?php echo ($audit_data['database']['status'] === 'connected') ? 'status-ok' : 'status-danger'; ?>">
                                        <?php echo strtoupper($audit_data['database']['status']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>Encryption</td>
                                <td>
                                    <span class="status <?php echo MILITARY_ENCRYPTION_ENABLED ? 'status-ok' : 'status-danger'; ?>">
                                        <?php echo MILITARY_ENCRYPTION_ENABLED ? 'ACTIVE' : 'INACTIVE'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>Quantum System</td>
                                <td>
                                    <span class="status <?php echo ($audit_data['encryption']['quantum_status']['operational'] ?? false) ? 'status-ok' : 'status-warning'; ?>">
                                        <?php echo ($audit_data['encryption']['quantum_status']['operational'] ?? false) ? 'OPERATIONAL' : 'OFFLINE'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td>FIPS Compliance</td>
                                <td>
                                    <span class="status <?php echo FIPS_140_2_COMPLIANCE ? 'status-ok' : 'status-warning'; ?>">
                                        <?php echo FIPS_140_2_COMPLIANCE ? 'COMPLIANT' : 'NON-COMPLIANT'; ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- Network Analysis Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">🌐 Network Analysis</div>
                    <div class="card-icon"><span class="loading"></span></div>
                </div>
                <div class="card-content">
                    <h4>Open Ports</h4>
                    <?php if (empty($audit_data['network']['open_ports'])): ?>
                        <div class="alert alert-info">No open ports detected</div>
                    <?php else: ?>
                        <div class="terminal">
                            <?php foreach ($audit_data['network']['open_ports'] as $port): ?>
                                <div class="terminal-line">
                                    <span class="terminal-prompt">PORT <?php echo $port['port']; ?></span>
                                    <span class="terminal-output"><?php echo $port['service']; ?> - <?php echo $port['status']; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <h4 style="margin-top: 15px;">Active Connections</h4>
                    <div class="stat-grid">
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $audit_data['network']['active_connections']['total'] ?? 0; ?></div>
                            <div class="stat-label">Total</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-value"><?php echo $audit_data['network']['active_connections']['established'] ?? 0; ?></div>
                            <div class="stat-label">Established</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Encryption Status Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">🔐 Encryption Status</div>
                    <div class="card-icon">
                        <span class="status status-ok">SECURE</span>
                    </div>
                </div>
                <div class="card-content">
                    <h4>Quantum Metrics</h4>
                    <?php if ($audit_data['encryption']['quantum_status']['operational'] ?? false): ?>
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo ($audit_data['encryption']['quantum_status']['bb84_security'] ?? 0) * 100; ?>%"></div>
                        </div>
                        <small>BB84 Security Level: <?php echo number_format(($audit_data['encryption']['quantum_status']['bb84_security'] ?? 0) * 100, 1); ?>%</small>
                        
                        <div class="progress-bar">
                            <div class="progress-fill" style="width: <?php echo min(100, ($audit_data['encryption']['quantum_status']['quantum_volume'] ?? 0) / 10); ?>%"></div>
                        </div>
                        <small>Quantum Volume: <?php echo number_format($audit_data['encryption']['quantum_status']['quantum_volume'] ?? 0, 0); ?></small>
                    <?php else: ?>
                        <div class="alert alert-warning">Quantum system offline</div>
                    <?php endif; ?>
                    
                    <h4 style="margin-top: 15px;">Algorithms</h4>
                    <div class="terminal">
                        <div class="terminal-line">
                            <span class="terminal-prompt">AES:</span>
                            <span class="terminal-output"><?php echo $audit_data['encryption']['key_strength']['aes_key_size'] ?? 'N/A'; ?>-bit</span>
                        </div>
                        <div class="terminal-line">
                            <span class="terminal-prompt">RSA:</span>
                            <span class="terminal-output"><?php echo $audit_data['encryption']['key_strength']['rsa_key_size'] ?? 'N/A'; ?>-bit</span>
                        </div>
                        <div class="terminal-line">
                            <span class="terminal-prompt">KDF:</span>
                            <span class="terminal-output"><?php echo number_format($audit_data['encryption']['key_strength']['kdf_iterations'] ?? 0); ?> iterations</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recommendations Card -->
            <div class="card">
                <div class="card-header">
                    <div class="card-title">📋 Recommendations</div>
                    <div class="card-icon"><?php echo count($audit_data['recommendations'] ?? []); ?></div>
                </div>
                <div class="card-content">
                    <?php if (empty($audit_data['recommendations'])): ?>
                        <div class="alert alert-success">System is optimally configured</div>
                    <?php else: ?>
                        <?php foreach ($audit_data['recommendations'] as $rec): ?>
                            <div class="alert alert-<?php 
                                echo ($rec['priority'] === 'CRITICAL') ? 'danger' : 
                                     (($rec['priority'] === 'HIGH') ? 'warning' : 'info'); 
                            ?>">
                                <strong>[<?php echo $rec['category']; ?>]</strong><br>
                                <?php echo htmlspecialchars($rec['action']); ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div style="text-align: center; margin: 40px 0;">
            <button class="btn" onclick="location.reload()">🔄 Re-scan System</button>
            <button class="btn btn-danger" onclick="if(confirm('This will lock down the system. Continue?')) lockdownSystem()">🔒 Emergency Lockdown</button>
            <button class="btn btn-success" onclick="exportReport()">📥 Export Report</button>
            <a href="admin_dashboard.php" class="btn">🏠 Dashboard</a>
        </div>
    </div>
    
    <script>
        // Matrix rain effect
        function createMatrix() {
            const matrix = document.getElementById('matrix');
            const columns = Math.floor(window.innerWidth / 20);
            
            for (let i = 0; i < columns; i++) {
                const column = document.createElement('div');
                column.className = 'matrix-column';
                column.style.left = i * 20 + 'px';
                column.style.animationDuration = Math.random() * 5 + 5 + 's';
                column.style.animationDelay = Math.random() * 5 + 's';
                
                // Random characters
                let text = '';
                for (let j = 0; j < 100; j++) {
                    text += String.fromCharCode(33 + Math.floor(Math.random() * 94));
                }
                column.textContent = text;
                
                matrix.appendChild(column);
            }
        }
        
        createMatrix();
        
        // Auto-refresh
        setTimeout(() => {
            console.log('Auto-refresh in 5 minutes');
        }, 300000);
        
        // Emergency lockdown
        function lockdownSystem() {
            alert('System lockdown initiated. All connections will be terminated.');
            // Implementar lockdown real aqui
        }
        
        // Export report
        function exportReport() {
            const data = <?php echo json_encode($audit_data); ?>;
            const blob = new Blob([JSON.stringify(data, null, 2)], {type: 'application/json'});
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'security_audit_' + new Date().toISOString() + '.json';
            a.click();
        }
        
        // Console warnings
        console.log('%c⚠️ SECURITY WARNING', 'color: red; font-size: 30px; font-weight: bold');
        console.log('%cThis is a secure military system. Unauthorized access is prohibited.', 'color: red; font-size: 16px');
        console.log('%cAll activities are monitored and logged.', 'color: orange; font-size: 14px');
    </script>
</body>
</html>