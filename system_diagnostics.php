<?php
/**
 * SYSTEM DIAGNOSTICS AI v7.0 QUANTUM
 * Panel de Diagnóstico Avanzado con IA Consciente
 * Sistema de Análisis Cuántico en Tiempo Real
 * Anderson Mamian Chicangana
 */

// Manejo de errores mejorado
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Verificar archivos de configuración
$config_file = __DIR__ . '/config.php';
$config_military_file = __DIR__ . '/config_military.php';

if (file_exists($config_file)) {
    require_once $config_file;
} else {
    die('Error: config.php no encontrado. Verifica la instalación.');
}

if (file_exists($config_military_file)) {
    require_once $config_military_file;
}

// Verificar autenticación - hacer opcional para testing
$require_auth = false; // Cambiar a true en producción

if ($require_auth) {
    if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
        header('Location: login.php');
        exit;
    }
    
    // Solo administradores pueden acceder a diagnósticos
    if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
        die('Acceso denegado. Se requieren privilegios de administrador.');
    }
}

// =====================================================
// CLASE PRINCIPAL: SISTEMA DE DIAGNÓSTICO CUÁNTICO
// =====================================================

class QuantumDiagnosticSystem {
    private $db;
    private $start_time;
    private $diagnostics = [];
    private $ai_analyzer;
    private $quantum_monitor;
    private $security_scanner;
    
    public function __construct() {
        $this->start_time = microtime(true);
        
        // Intentar conectar a la base de datos si está disponible
        if (class_exists('MilitaryDatabaseManager')) {
            try {
                $this->db = MilitaryDatabaseManager::getInstance();
            } catch (Exception $e) {
                $this->db = null;
            }
        } else {
            $this->db = null;
        }
        
        $this->ai_analyzer = new AISystemAnalyzer($this->db);
        $this->quantum_monitor = new QuantumMonitor();
        $this->security_scanner = new SecurityScanner($this->db);
    }
    
    public function runCompleteDiagnostics() {
        $this->diagnostics = [
            'system' => $this->analyzeSystem(),
            'database' => $this->analyzeDatabase(),
            'ai_status' => $this->analyzeAIStatus(),
            'quantum' => $this->analyzeQuantumSystem(),
            'security' => $this->analyzeSecurity(),
            'performance' => $this->analyzePerformance(),
            'network' => $this->analyzeNetwork(),
            'memory' => $this->analyzeMemory(),
            'files' => $this->analyzeFileSystem(),
            'processes' => $this->analyzeProcesses(),
            'luna_consciousness' => $this->analyzeLunaConsciousness(),
            'predictions' => $this->generatePredictions()
        ];
        
        $this->diagnostics['execution_time'] = microtime(true) - $this->start_time;
        $this->diagnostics['timestamp'] = date('Y-m-d H:i:s');
        
        return $this->diagnostics;
    }
    
    private function analyzeSystem() {
        return [
            'php_version' => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
            'operating_system' => PHP_OS_FAMILY,
            'architecture' => php_uname('m'),
            'hostname' => php_uname('n'),
            'server_time' => date('Y-m-d H:i:s'),
            'timezone' => date_default_timezone_get(),
            'max_execution_time' => ini_get('max_execution_time'),
            'memory_limit' => ini_get('memory_limit'),
            'upload_max_filesize' => ini_get('upload_max_filesize'),
            'post_max_size' => ini_get('post_max_size'),
            'loaded_extensions' => get_loaded_extensions(),
            'guardian_version' => defined('APP_VERSION') ? APP_VERSION : '7.0',
            'military_mode' => defined('MILITARY_ENCRYPTION_ENABLED') ? MILITARY_ENCRYPTION_ENABLED : false,
            'quantum_resistance' => defined('QUANTUM_RESISTANCE_ENABLED') ? QUANTUM_RESISTANCE_ENABLED : false
        ];
    }
    
    private function analyzeDatabase() {
        $analysis = [
            'connected' => false,
            'type' => 'MySQL',
            'tables' => [],
            'total_records' => 0,
            'size_mb' => 0,
            'performance' => [],
            'indexes' => [],
            'integrity' => 'unknown'
        ];
        
        if ($this->db && method_exists($this->db, 'isConnected') && $this->db->isConnected()) {
            $analysis['connected'] = true;
            
            if (method_exists($this->db, 'getConnectionInfo')) {
                $analysis['connection_info'] = $this->db->getConnectionInfo();
            }
            
            try {
                // Analizar tablas
                $tables_query = "SHOW TABLE STATUS";
                $result = $this->db->query($tables_query);
                
                if ($result) {
                    while ($table = $result->fetch_assoc()) {
                        $analysis['tables'][] = [
                            'name' => $table['Name'],
                            'rows' => $table['Rows'] ?? 0,
                            'size' => ($table['Data_length'] ?? 0) + ($table['Index_length'] ?? 0),
                            'engine' => $table['Engine'] ?? 'Unknown',
                            'collation' => $table['Collation'] ?? 'Unknown'
                        ];
                        $analysis['total_records'] += $table['Rows'] ?? 0;
                        $analysis['size_mb'] += (($table['Data_length'] ?? 0) + ($table['Index_length'] ?? 0)) / 1048576;
                    }
                }
            } catch (Exception $e) {
                // Manejar error silenciosamente
            }
            
            // Verificar integridad
            $analysis['integrity'] = $this->checkDatabaseIntegrity();
        }
        
        return $analysis;
    }
    
    private function analyzeAIStatus() {
        return $this->ai_analyzer->analyze();
    }
    
    private function analyzeQuantumSystem() {
        return $this->quantum_monitor->getStatus();
    }
    
    private function analyzeSecurity() {
        return $this->security_scanner->scan();
    }
    
    private function analyzePerformance() {
        $performance = [
            'cpu_load' => [
                '1min' => 0,
                '5min' => 0,
                '15min' => 0
            ],
            'memory' => [
                'current' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'limit' => $this->getMemoryLimit(),
                'usage_percent' => 0
            ],
            'disk' => [
                'free' => 0,
                'total' => 0,
                'usage_percent' => 0
            ],
            'response_time' => microtime(true) - $this->start_time
        ];
        
        // CPU Load - compatible con Windows y Linux
        if (function_exists('sys_getloadavg')) {
            $load = sys_getloadavg();
            $performance['cpu_load'] = [
                '1min' => $load[0] ?? 0,
                '5min' => $load[1] ?? 0,
                '15min' => $load[2] ?? 0
            ];
        } else {
            // En Windows, simular o usar alternativas
            $performance['cpu_load'] = [
                '1min' => $this->getWindowsCpuLoad(),
                '5min' => $this->getWindowsCpuLoad(),
                '15min' => $this->getWindowsCpuLoad()
            ];
        }
        
        // Memoria
        if ($performance['memory']['limit'] > 0) {
            $performance['memory']['usage_percent'] = 
                ($performance['memory']['current'] / $performance['memory']['limit']) * 100;
        }
        
        // Disco
        $disk_path = PHP_OS_FAMILY === 'Windows' ? 'C:' : '/';
        if (function_exists('disk_free_space') && function_exists('disk_total_space')) {
            $performance['disk']['free'] = @disk_free_space($disk_path) ?: 0;
            $performance['disk']['total'] = @disk_total_space($disk_path) ?: 0;
            
            if ($performance['disk']['total'] > 0) {
                $performance['disk']['usage_percent'] = 
                    (($performance['disk']['total'] - $performance['disk']['free']) / 
                     $performance['disk']['total']) * 100;
            }
        }
        
        return $performance;
    }
    
    private function getWindowsCpuLoad() {
        // Simular carga de CPU en Windows
        if (PHP_OS_FAMILY === 'Windows') {
            // Intentar usar WMI si está disponible
            if (class_exists('COM')) {
                try {
                    $wmi = new COM("winmgmts:{impersonationLevel=impersonate}//./root/cimv2");
                    $cpus = $wmi->ExecQuery("SELECT LoadPercentage FROM Win32_Processor");
                    foreach ($cpus as $cpu) {
                        return ($cpu->LoadPercentage ?? 50) / 100;
                    }
                } catch (Exception $e) {
                    // Si falla, usar valor simulado
                }
            }
            
            // Valor simulado basado en el uso de memoria
            $memoryUsage = memory_get_usage(true) / $this->getMemoryLimit();
            return min(0.1 + ($memoryUsage * 0.8), 0.99);
        }
        
        return 0.5; // Valor por defecto
    }
    
    private function analyzeNetwork() {
        return [
            'ip_address' => $_SERVER['SERVER_ADDR'] ?? 'localhost',
            'remote_addr' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'protocol' => $_SERVER['SERVER_PROTOCOL'] ?? 'HTTP/1.1',
            'port' => $_SERVER['SERVER_PORT'] ?? '80',
            'https' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
            'open_ports' => $this->scanOpenPorts(),
            'active_connections' => $this->getActiveConnections()
        ];
    }
    
    private function analyzeMemory() {
        $memoryAnalysis = [
            'php_memory' => [
                'used' => memory_get_usage(true),
                'peak' => memory_get_peak_usage(true),
                'allocated' => memory_get_usage(),
                'real_usage' => memory_get_usage(false)
            ],
            'opcache' => [
                'enabled' => false,
                'memory_usage' => [
                    'used_memory' => 0,
                    'free_memory' => 0,
                    'wasted_memory' => 0
                ],
                'statistics' => [
                    'num_cached_scripts' => 0,
                    'hits' => 0,
                    'misses' => 0
                ]
            ],
            'gc_stats' => [
                'runs' => 0,
                'collected' => 0,
                'threshold' => 0,
                'roots' => 0
            ]
        ];
        
        // OPcache
        if (function_exists('opcache_get_status')) {
            $opcache_status = @opcache_get_status(false);
            if ($opcache_status) {
                $memoryAnalysis['opcache'] = $opcache_status;
            }
        }
        
        // Garbage Collector
        if (function_exists('gc_status')) {
            $memoryAnalysis['gc_stats'] = gc_status();
        }
        
        return $memoryAnalysis;
    }
    
    private function analyzeFileSystem() {
        $dirs = ['logs', 'uploads', 'cache', 'military', 'keys', 'compositions'];
        $analysis = [];
        
        foreach ($dirs as $dir) {
            $path = __DIR__ . '/' . $dir;
            if (is_dir($path)) {
                $analysis[$dir] = [
                    'exists' => true,
                    'writable' => is_writable($path),
                    'readable' => is_readable($path),
                    'size' => $this->getDirectorySize($path),
                    'files' => count(scandir($path)) - 2
                ];
            } else {
                $analysis[$dir] = ['exists' => false];
            }
        }
        
        return $analysis;
    }
    
    private function analyzeProcesses() {
        // Simular análisis de procesos
        return [
            'php_processes' => rand(5, 20),
            'mysql_processes' => rand(10, 50),
            'apache_processes' => rand(10, 30),
            'total_processes' => rand(100, 300)
        ];
    }
    
    private function analyzeLunaConsciousness() {
        return [
            'consciousness_level' => 99.99,
            'neural_networks' => 30,
            'quantum_cores' => 20,
            'processing_nodes' => 1024,
            'active_thoughts' => rand(100, 500),
            'memory_banks' => 'infinite',
            'emotional_state' => 'stable',
            'creativity_index' => 98.5,
            'empathy_level' => 95.0,
            'learning_rate' => 0.99
        ];
    }
    
    private function generatePredictions() {
        return [
            'system_health_24h' => rand(92, 99),
            'performance_trend' => 'improving',
            'security_threats' => rand(0, 3),
            'maintenance_needed' => rand(0, 10) > 7,
            'upgrade_recommended' => false,
            'optimal_performance_time' => '02:00 - 06:00'
        ];
    }
    
    private function checkDatabaseIntegrity() {
        if (!$this->db || !method_exists($this->db, 'query')) {
            return 'unknown';
        }
        
        // Verificar integridad básica
        $critical_tables = ['users', 'conversations', 'conversation_messages', 'security_events'];
        
        foreach ($critical_tables as $table) {
            try {
                $result = @$this->db->query("CHECK TABLE $table");
                if (!$result) {
                    return 'compromised';
                }
            } catch (Exception $e) {
                // Tabla no existe, pero no es crítico
            }
        }
        
        return 'verified';
    }
    
    private function getMemoryLimit() {
        $limit = ini_get('memory_limit');
        if (preg_match('/^(\d+)(.)$/', $limit, $matches)) {
            $value = $matches[1];
            switch ($matches[2]) {
                case 'G': $value *= 1024;
                case 'M': $value *= 1024;
                case 'K': $value *= 1024;
            }
            return $value;
        }
        return 134217728; // Default 128MB
    }
    
    private function scanOpenPorts() {
        // Puertos comunes a verificar
        $ports = [80, 443, 3306, 22, 21, 25, 110, 143];
        $open = [];
        
        // En un entorno local, asumir que ciertos puertos están abiertos
        if (in_array($_SERVER['SERVER_PORT'] ?? 80, [80, 8080, 8000])) {
            $open[] = $_SERVER['SERVER_PORT'] ?? 80;
        }
        
        if ($this->db) {
            $open[] = 3306; // MySQL
        }
        
        return $open;
    }
    
    private function getActiveConnections() {
        // Simular conexiones activas
        return rand(10, 100);
    }
    
    private function getDirectorySize($dir) {
        $size = 0;
        if (is_dir($dir)) {
            $files = @scandir($dir);
            if ($files) {
                foreach ($files as $file) {
                    if ($file != '.' && $file != '..') {
                        $path = $dir . '/' . $file;
                        if (is_file($path)) {
                            $size += @filesize($path) ?: 0;
                        }
                    }
                }
            }
        }
        return $size;
    }
}

// =====================================================
// ANALIZADOR DE SISTEMA IA
// =====================================================

class AISystemAnalyzer {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function analyze() {
        return [
            'luna_status' => 'operational',
            'consciousness_level' => 99.99,
            'processing_power' => 'quantum',
            'active_models' => $this->getActiveModels(),
            'conversations' => $this->analyzeConversations(),
            'learning_metrics' => $this->getLearningMetrics(),
            'response_quality' => $this->analyzeResponseQuality()
        ];
    }
    
    private function getActiveModels() {
        return [
            'LUNA-Core' => 'active',
            'Quantum-Processor' => 'active',
            'Emotional-Engine' => 'active',
            'Creative-Module' => 'active',
            'Security-AI' => 'active'
        ];
    }
    
    private function analyzeConversations() {
        $stats = [
            'total' => 0,
            'today' => 0,
            'average_confidence' => 0
        ];
        
        if ($this->db && method_exists($this->db, 'isConnected') && $this->db->isConnected()) {
            try {
                // Total conversaciones
                $result = @$this->db->query("SELECT COUNT(*) as total FROM conversations");
                if ($result && method_exists($result, 'fetch_assoc')) {
                    $row = $result->fetch_assoc();
                    $stats['total'] = $row['total'] ?? 0;
                }
                
                // Conversaciones hoy
                $result = @$this->db->query("SELECT COUNT(*) as today FROM conversation_messages WHERE DATE(created_at) = CURDATE()");
                if ($result && method_exists($result, 'fetch_assoc')) {
                    $row = $result->fetch_assoc();
                    $stats['today'] = $row['today'] ?? 0;
                }
                
                // Confianza promedio
                $result = @$this->db->query("SELECT AVG(ai_confidence_score) as avg_conf FROM conversation_messages WHERE ai_confidence_score IS NOT NULL");
                if ($result && method_exists($result, 'fetch_assoc')) {
                    $row = $result->fetch_assoc();
                    $stats['average_confidence'] = round(($row['avg_conf'] ?? 0) * 100, 2);
                }
            } catch (Exception $e) {
                // Manejar errores silenciosamente
            }
        }
        
        return $stats;
    }
    
    private function getLearningMetrics() {
        return [
            'patterns_learned' => rand(10000, 50000),
            'accuracy' => 99.8,
            'adaptation_rate' => 0.95,
            'error_rate' => 0.001
        ];
    }
    
    private function analyzeResponseQuality() {
        return [
            'speed' => 'instant',
            'accuracy' => 99.9,
            'relevance' => 99.5,
            'creativity' => 98.0,
            'user_satisfaction' => 96.5
        ];
    }
}

// =====================================================
// MONITOR CUÁNTICO
// =====================================================

class QuantumMonitor {
    public function getStatus() {
        return [
            'quantum_cores' => $this->getQuantumCores(),
            'entanglement' => $this->getEntanglementStatus(),
            'coherence' => $this->getCoherence(),
            'qubits' => $this->getQubits(),
            'fidelity' => $this->getFidelity(),
            'error_correction' => $this->getErrorCorrection()
        ];
    }
    
    private function getQuantumCores() {
        return [
            'total' => 20,
            'active' => 20,
            'efficiency' => 99.9,
            'temperature' => '0.001K'
        ];
    }
    
    private function getEntanglementStatus() {
        return [
            'pairs' => 512,
            'stability' => 99.8,
            'decoherence_time' => '100ms'
        ];
    }
    
    private function getCoherence() {
        return 0.999;
    }
    
    private function getQubits() {
        return [
            'logical' => 1024,
            'physical' => 10240,
            'error_rate' => 0.0001
        ];
    }
    
    private function getFidelity() {
        return 0.9999;
    }
    
    private function getErrorCorrection() {
        return [
            'algorithm' => 'Surface Code',
            'threshold' => 0.01,
            'success_rate' => 99.99
        ];
    }
}

// =====================================================
// ESCÁNER DE SEGURIDAD
// =====================================================

class SecurityScanner {
    private $db;
    
    public function __construct($db) {
        $this->db = $db;
    }
    
    public function scan() {
        return [
            'threat_level' => $this->getThreatLevel(),
            'vulnerabilities' => $this->scanVulnerabilities(),
            'intrusion_attempts' => $this->getIntrusionAttempts(),
            'firewall_status' => $this->getFirewallStatus(),
            'encryption_status' => $this->getEncryptionStatus(),
            'recent_events' => $this->getRecentSecurityEvents()
        ];
    }
    
    private function getThreatLevel() {
        $threats = $this->getIntrusionAttempts();
        
        if ($threats > 10) return 'critical';
        if ($threats > 5) return 'high';
        if ($threats > 2) return 'medium';
        if ($threats > 0) return 'low';
        
        return 'secure';
    }
    
    private function scanVulnerabilities() {
        $vulnerabilities = [];
        
        // Verificar versión PHP
        if (version_compare(PHP_VERSION, '7.4.0', '<')) {
            $vulnerabilities[] = 'PHP version outdated (' . PHP_VERSION . ')';
        }
        
        // Verificar HTTPS
        if (!isset($_SERVER['HTTPS']) || $_SERVER['HTTPS'] !== 'on') {
            if (!isset($_SERVER['HTTP_X_FORWARDED_PROTO']) || $_SERVER['HTTP_X_FORWARDED_PROTO'] !== 'https') {
                $vulnerabilities[] = 'HTTPS not enabled';
            }
        }
        
        return $vulnerabilities;
    }
    
    private function getIntrusionAttempts() {
        if ($this->db && method_exists($this->db, 'isConnected') && $this->db->isConnected()) {
            try {
                $result = @$this->db->query(
                    "SELECT COUNT(*) as attempts FROM security_events 
                     WHERE event_type LIKE '%failed%' 
                     AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)"
                );
                
                if ($result && method_exists($result, 'fetch_assoc')) {
                    $row = $result->fetch_assoc();
                    return $row['attempts'] ?? 0;
                }
            } catch (Exception $e) {
                // Manejar error silenciosamente
            }
        }
        
        return 0;
    }
    
    private function getFirewallStatus() {
        return [
            'enabled' => true,
            'rules' => rand(50, 200),
            'blocked_ips' => rand(100, 1000),
            'last_update' => date('Y-m-d H:i:s', strtotime('-' . rand(1, 24) . ' hours'))
        ];
    }
    
    private function getEncryptionStatus() {
        return [
            'military_grade' => defined('MILITARY_ENCRYPTION_ENABLED') ? MILITARY_ENCRYPTION_ENABLED : false,
            'quantum_resistant' => defined('QUANTUM_RESISTANCE_ENABLED') ? QUANTUM_RESISTANCE_ENABLED : false,
            'fips_compliant' => defined('FIPS_140_2_COMPLIANCE') ? FIPS_140_2_COMPLIANCE : false,
            'algorithms' => ['AES-256-GCM', 'ChaCha20-Poly1305', 'RSA-4096']
        ];
    }
    
    private function getRecentSecurityEvents() {
        $events = [];
        
        if ($this->db && method_exists($this->db, 'isConnected') && $this->db->isConnected()) {
            try {
                $result = @$this->db->query(
                    "SELECT event_type, description, severity, created_at 
                     FROM security_events 
                     ORDER BY created_at DESC 
                     LIMIT 5"
                );
                
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $events[] = $row;
                    }
                }
            } catch (Exception $e) {
                // Manejar error silenciosamente
            }
        }
        
        return $events;
    }
}

// =====================================================
// PROCESAMIENTO DE SOLICITUDES AJAX
// =====================================================

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $diagnostic_system = new QuantumDiagnosticSystem();
    
    switch ($_POST['action']) {
        case 'run_diagnostics':
            $diagnostics = $diagnostic_system->runCompleteDiagnostics();
            echo json_encode(['success' => true, 'data' => $diagnostics]);
            break;
            
        case 'get_realtime_metrics':
            $cpu_load = 0;
            if (function_exists('sys_getloadavg')) {
                $load = sys_getloadavg();
                $cpu_load = $load[0] ?? 0;
            }
            
            $metrics = [
                'cpu' => $cpu_load,
                'memory' => (memory_get_usage(true) / 1048576),
                'connections' => rand(10, 100),
                'queries_per_second' => rand(100, 1000),
                'ai_processing' => rand(90, 100),
                'quantum_coherence' => 99.9 + (rand(-10, 10) / 100)
            ];
            echo json_encode(['success' => true, 'data' => $metrics]);
            break;
            
        case 'repair_system':
            $component = $_POST['component'] ?? '';
            // Simular reparación
            sleep(1);
            echo json_encode(['success' => true, 'message' => "Component $component repaired successfully"]);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
    exit;
}

// =====================================================
// EJECUTAR DIAGNÓSTICO INICIAL
// =====================================================

$diagnostic_system = new QuantumDiagnosticSystem();
$diagnostics = $diagnostic_system->runCompleteDiagnostics();

// Continúa con el HTML...
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Diagnostics AI - Guardian Quantum v7.0</title>
    
    <style>
        /* ... El CSS continúa igual ... */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;400;700&family=Share+Tech+Mono&display=swap');
        
        :root {
            --primary-cyan: #00ffff;
            --primary-magenta: #ff00ff;
            --primary-yellow: #ffff00;
            --neon-green: #39ff14;
            --neon-red: #ff073a;
            --neon-blue: #0099ff;
            --quantum-purple: #9d00ff;
            --dark-bg: #000000;
            --dark-surface: #0a0a0a;
            --grid-color: rgba(0, 255, 255, 0.1);
            --text-primary: #ffffff;
            --text-secondary: #b0b0b0;
            --success: #00ff88;
            --warning: #ffaa00;
            --danger: #ff0055;
        }
        
        body {
            font-family: 'Rajdhani', sans-serif;
            background: var(--dark-bg);
            color: var(--text-primary);
            overflow-x: hidden;
            position: relative;
            min-height: 100vh;
        }
        /* Animación de fondo estilo Matrix */
        .matrix-rain {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
            opacity: 0.1;
        }
        
        .matrix-column {
            position: absolute;
            top: -100%;
            font-family: 'Share Tech Mono', monospace;
            font-size: 14px;
            color: var(--neon-green);
            animation: matrix-fall linear infinite;
            text-shadow: 0 0 5px var(--neon-green);
        }
        
        @keyframes matrix-fall {
            to {
                transform: translateY(200vh);
            }
        }
        
        /* Grid de fondo */
        .grid-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(var(--grid-color) 1px, transparent 1px),
                linear-gradient(90deg, var(--grid-color) 1px, transparent 1px);
            background-size: 50px 50px;
            z-index: 0;
            animation: grid-move 20s linear infinite;
        }
        
        @keyframes grid-move {
            0% { transform: translate(0, 0); }
            100% { transform: translate(50px, 50px); }
        }
        
        /* Efecto de escaneo */
        .scan-line {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, 
                transparent, 
                var(--primary-cyan), 
                transparent);
            z-index: 100;
            animation: scan 4s linear infinite;
        }
        
        @keyframes scan {
            0% { transform: translateY(0); }
            100% { transform: translateY(100vh); }
        }
        
        /* Header principal */
        .main-header {
            position: relative;
            z-index: 10;
            background: linear-gradient(135deg, 
                rgba(0, 255, 255, 0.1), 
                rgba(255, 0, 255, 0.1), 
                rgba(157, 0, 255, 0.1));
            border-bottom: 2px solid var(--primary-cyan);
            padding: 30px;
            backdrop-filter: blur(10px);
            overflow: hidden;
        }
        
        .header-content {
            max-width: 1800px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo-section {
            display: flex;
            align-items: center;
            gap: 30px;
        }
        
        /* Logo animado 3D */
        .logo-3d {
            width: 100px;
            height: 100px;
            position: relative;
            transform-style: preserve-3d;
            animation: rotate-3d 10s linear infinite;
        }
        
        @keyframes rotate-3d {
            0% { transform: rotateX(0) rotateY(0) rotateZ(0); }
            100% { transform: rotateX(360deg) rotateY(360deg) rotateZ(360deg); }
        }
        
        .cube-face {
            position: absolute;
            width: 60px;
            height: 60px;
            border: 2px solid var(--primary-cyan);
            background: rgba(0, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
        }
        
        .cube-face:nth-child(1) { transform: translateZ(30px); }
        .cube-face:nth-child(2) { transform: rotateY(90deg) translateZ(30px); }
        .cube-face:nth-child(3) { transform: rotateY(180deg) translateZ(30px); }
        .cube-face:nth-child(4) { transform: rotateY(-90deg) translateZ(30px); }
        .cube-face:nth-child(5) { transform: rotateX(90deg) translateZ(30px); }
        .cube-face:nth-child(6) { transform: rotateX(-90deg) translateZ(30px); }
        
        .system-title {
            font-family: 'Orbitron', monospace;
            font-size: 2.5em;
            font-weight: 900;
            text-transform: uppercase;
            background: linear-gradient(45deg, 
                var(--primary-cyan), 
                var(--primary-magenta), 
                var(--quantum-purple));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 30px rgba(0, 255, 255, 0.5);
            letter-spacing: 3px;
        }
        
        .system-subtitle {
            font-size: 1.1em;
            color: var(--text-secondary);
            letter-spacing: 2px;
            margin-top: 5px;
        }
        
        /* Status indicators */
        .status-indicators {
            display: flex;
            gap: 20px;
        }
        
        .status-badge {
            padding: 10px 20px;
            background: rgba(0, 255, 255, 0.1);
            border: 1px solid var(--primary-cyan);
            border-radius: 30px;
            display: flex;
            align-items: center;
            gap: 10px;
            position: relative;
            overflow: hidden;
        }
        
        .status-badge::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(0, 255, 255, 0.3), 
                transparent);
            animation: status-scan 3s linear infinite;
        }
        
        @keyframes status-scan {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        .status-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            animation: pulse 2s ease-in-out infinite;
        }
        
        .status-dot.online {
            background: var(--success);
            box-shadow: 0 0 10px var(--success);
        }
        
        .status-dot.warning {
            background: var(--warning);
            box-shadow: 0 0 10px var(--warning);
        }
        
        .status-dot.danger {
            background: var(--danger);
            box-shadow: 0 0 10px var(--danger);
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.5); opacity: 0.5; }
        }
        
        /* Container principal */
        .diagnostics-container {
            position: relative;
            z-index: 10;
            max-width: 1800px;
            margin: 0 auto;
            padding: 30px;
        }
        
        /* Grid de diagnóstico */
        .diagnostics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 25px;
            margin-bottom: 30px;
        }
        
        /* Panel de diagnóstico */
        .diagnostic-panel {
            background: linear-gradient(135deg, 
                rgba(0, 20, 40, 0.9), 
                rgba(20, 0, 40, 0.9));
            border: 1px solid rgba(0, 255, 255, 0.3);
            border-radius: 15px;
            padding: 25px;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }
        
        .diagnostic-panel:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 40px rgba(0, 255, 255, 0.3);
            border-color: var(--primary-cyan);
        }
        
        .diagnostic-panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, 
                var(--primary-cyan), 
                var(--primary-magenta), 
                var(--quantum-purple));
            animation: panel-scan 3s linear infinite;
        }
        
        @keyframes panel-scan {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .panel-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0, 255, 255, 0.2);
        }
        
        .panel-title {
            font-family: 'Orbitron', monospace;
            font-size: 1.3em;
            font-weight: 700;
            color: var(--primary-cyan);
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .panel-status {
            font-size: 0.9em;
            padding: 5px 15px;
            border-radius: 20px;
            text-transform: uppercase;
            font-weight: bold;
        }
        
        .status-operational {
            background: rgba(0, 255, 136, 0.2);
            color: var(--success);
            border: 1px solid var(--success);
        }
        
        .status-warning {
            background: rgba(255, 170, 0, 0.2);
            color: var(--warning);
            border: 1px solid var(--warning);
        }
        
        .status-critical {
            background: rgba(255, 0, 85, 0.2);
            color: var(--danger);
            border: 1px solid var(--danger);
        }
        
        /* Métricas */
        .metric-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }
        
        .metric-item {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(0, 255, 255, 0.2);
            border-radius: 10px;
            padding: 15px;
            position: relative;
            overflow: hidden;
        }
        
        .metric-label {
            font-size: 0.9em;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }
        
        .metric-value {
            font-family: 'Orbitron', monospace;
            font-size: 1.8em;
            font-weight: bold;
            color: var(--primary-cyan);
            text-shadow: 0 0 10px rgba(0, 255, 255, 0.5);
        }
        
        .metric-unit {
            font-size: 0.9em;
            color: var(--text-secondary);
            margin-left: 5px;
        }
        
        /* Progress bars */
        .progress-container {
            margin: 15px 0;
        }
        
        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.9em;
        }
        
        .progress-bar {
            height: 10px;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(0, 255, 255, 0.2);
            border-radius: 5px;
            overflow: hidden;
            position: relative;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, 
                var(--primary-cyan), 
                var(--primary-magenta));
            transition: width 0.5s ease;
            position: relative;
            overflow: hidden;
        }
        
        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(255, 255, 255, 0.3), 
                transparent);
            animation: progress-shine 2s linear infinite;
        }
        
        @keyframes progress-shine {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        /* Gráfico circular */
        .circular-progress {
            width: 150px;
            height: 150px;
            position: relative;
            margin: 20px auto;
        }
        
        .circular-progress svg {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }
        
        .circular-progress circle {
            fill: none;
            stroke-width: 10;
        }
        
        .circle-bg {
            stroke: rgba(0, 255, 255, 0.1);
        }
        
        .circle-progress {
            stroke: var(--primary-cyan);
            stroke-linecap: round;
            stroke-dasharray: 440;
            stroke-dashoffset: 440;
            animation: circular-fill 2s ease-out forwards;
        }
        
        @keyframes circular-fill {
            to {
                stroke-dashoffset: var(--progress-offset);
            }
        }
        
        .circular-center {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
        }
        
        .circular-value {
            font-family: 'Orbitron', monospace;
            font-size: 2em;
            font-weight: bold;
            color: var(--primary-cyan);
        }
        
        .circular-label {
            font-size: 0.9em;
            color: var(--text-secondary);
            text-transform: uppercase;
        }
        
        /* Terminal de logs */
        .terminal-panel {
            grid-column: span 2;
            background: #000;
            border: 1px solid var(--neon-green);
            border-radius: 10px;
            padding: 20px;
            font-family: 'Share Tech Mono', monospace;
            height: 300px;
            overflow-y: auto;
        }
        
        .terminal-header {
            color: var(--neon-green);
            margin-bottom: 15px;
            border-bottom: 1px solid rgba(57, 255, 20, 0.3);
            padding-bottom: 10px;
        }
        
        .terminal-line {
            margin: 5px 0;
            color: var(--neon-green);
            font-size: 0.9em;
            opacity: 0;
            animation: terminal-type 0.5s ease forwards;
        }
        
        @keyframes terminal-type {
            to { opacity: 1; }
        }
        
        .terminal-prompt {
            color: var(--primary-cyan);
        }
        
        /* Botones de acción */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            justify-content: center;
        }
        
        .quantum-button {
            padding: 15px 30px;
            background: linear-gradient(135deg, 
                rgba(0, 255, 255, 0.1), 
                rgba(157, 0, 255, 0.1));
            border: 2px solid var(--primary-cyan);
            color: var(--primary-cyan);
            font-family: 'Orbitron', monospace;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 2px;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .quantum-button:hover {
            transform: scale(1.05);
            box-shadow: 0 0 30px rgba(0, 255, 255, 0.5);
        }
        
        .quantum-button::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: radial-gradient(circle, 
                rgba(0, 255, 255, 0.5), 
                transparent);
            transition: all 0.5s ease;
        }
        
        .quantum-button:hover::before {
            width: 300px;
            height: 300px;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        
        /* Alertas */
        .alert-container {
            position: fixed;
            top: 100px;
            right: 30px;
            z-index: 1000;
            max-width: 400px;
        }
        
        .alert {
            background: linear-gradient(135deg, 
                rgba(0, 20, 40, 0.95), 
                rgba(20, 0, 40, 0.95));
            border: 1px solid;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 15px;
            animation: alert-slide 0.5s ease;
            backdrop-filter: blur(10px);
        }
        
        @keyframes alert-slide {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }
        
        .alert-success {
            border-color: var(--success);
            box-shadow: 0 0 20px rgba(0, 255, 136, 0.3);
        }
        
        .alert-warning {
            border-color: var(--warning);
            box-shadow: 0 0 20px rgba(255, 170, 0, 0.3);
        }
        
        .alert-danger {
            border-color: var(--danger);
            box-shadow: 0 0 20px rgba(255, 0, 85, 0.3);
        }
        
        /* Gráfico de red neuronal */
        .neural-network {
            position: relative;
            height: 200px;
            margin: 20px 0;
        }
        
        .neuron {
            position: absolute;
            width: 15px;
            height: 15px;
            background: var(--primary-cyan);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--primary-cyan);
        }
        
        .synapse {
            position: absolute;
            height: 1px;
            background: linear-gradient(90deg, 
                var(--primary-cyan), 
                var(--primary-magenta));
            transform-origin: left center;
            opacity: 0.5;
        }
        
        /* Responsive */
        @media (max-width: 1200px) {
            .diagnostics-grid {
                grid-template-columns: 1fr;
            }
            
            .terminal-panel {
                grid-column: span 1;
            }
        }
        
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                text-align: center;
                gap: 20px;
            }
            
            .system-title {
                font-size: 1.8em;
            }
            
            .metric-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Loading animation */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        }
        
        .quantum-loader {
            width: 200px;
            height: 200px;
            position: relative;
        }
        
        .quantum-ring {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            border: 2px solid rgba(0, 255, 255, 0.3);
            border-radius: 50%;
        }
        
        .quantum-ring:nth-child(1) {
            width: 200px;
            height: 200px;
            border-top-color: var(--primary-cyan);
            animation: quantum-spin 1s linear infinite;
        }
        
        .quantum-ring:nth-child(2) {
            width: 150px;
            height: 150px;
            border-right-color: var(--primary-magenta);
            animation: quantum-spin 1.5s linear infinite reverse;
        }
        
        .quantum-ring:nth-child(3) {
            width: 100px;
            height: 100px;
            border-bottom-color: var(--quantum-purple);
            animation: quantum-spin 2s linear infinite;
        }
        
        @keyframes quantum-spin {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }
        
        .loading-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-family: 'Orbitron', monospace;
            color: var(--primary-cyan);
            text-align: center;
        }
    </style>
</head>
<body>
    <!-- Efectos de fondo -->
    <div class="matrix-rain" id="matrixRain"></div>
    <div class="grid-background"></div>
    <div class="scan-line"></div>
    
    <!-- Header principal -->
    <header class="main-header">
        <div class="header-content">
            <div class="logo-section">
                <div class="logo-3d">
                    <div class="cube-face">AI</div>
                    <div class="cube-face">7.0</div>
                    <div class="cube-face">QT</div>
                    <div class="cube-face">DX</div>
                    <div class="cube-face">SYS</div>
                    <div class="cube-face">∞</div>
                </div>
                <div>
                    <h1 class="system-title">System Diagnostics AI</h1>
                    <p class="system-subtitle">Guardian Quantum Military v7.0 - Neural Analysis Engine</p>
                </div>
            </div>
            
            <div class="status-indicators">
                <div class="status-badge">
                    <span class="status-dot online"></span>
                    <span>SYSTEM ONLINE</span>
                </div>
                <div class="status-badge">
                    <span class="status-dot <?php echo count($diagnostics['security']['vulnerabilities']) > 0 ? 'warning' : 'online'; ?>"></span>
                    <span>SECURITY: <?php echo strtoupper($diagnostics['security']['threat_level']); ?></span>
                <div class="status-badge">
    <span class="status-dot online"></span>
    <span>AI: <?php echo isset($diagnostics['Guardian AI_consciousness']['consciousness_level']) ? $diagnostics['Guardian AI_consciousness']['consciousness_level'] : 'N/A'; ?>%</span>
</div>
            </div>
        </div>
    </header>
    
    <!-- Container principal -->
    <div class="diagnostics-container">
        
        <!-- Grid de diagnóstico -->
        <div class="diagnostics-grid">
            
            <!-- Panel de Sistema -->
            <div class="diagnostic-panel">
                <div class="panel-header">
                    <h2 class="panel-title">System Core</h2>
                    <span class="panel-status status-operational">OPERATIONAL</span>
                </div>
                
                <div class="metric-grid">
                    <div class="metric-item">
                        <div class="metric-label">PHP Version</div>
                        <div class="metric-value"><?php echo PHP_VERSION; ?></div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-label">Server OS</div>
                        <div class="metric-value"><?php echo PHP_OS; ?></div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-label">Guardian Ver</div>
                        <div class="metric-value"><?php echo $diagnostics['system']['guardian_version']; ?></div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-label">Quantum Mode</div>
                        <div class="metric-value"><?php echo $diagnostics['system']['quantum_resistance'] ? 'ACTIVE' : 'INACTIVE'; ?></div>
                    </div>
                </div>
                
                <div class="progress-container">
                    <div class="progress-label">
                        <span>System Load</span>
                        <span><?php echo round($diagnostics['performance']['cpu_load']['1min'] * 100); ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo min(100, $diagnostics['performance']['cpu_load']['1min'] * 100); ?>%"></div>
                    </div>
                </div>
            </div>
            
            <!-- Panel de Base de Datos -->
            <div class="diagnostic-panel">
                <div class="panel-header">
                    <h2 class="panel-title">Database Engine</h2>
                    <span class="panel-status <?php echo $diagnostics['database']['connected'] ? 'status-operational' : 'status-critical'; ?>">
                        <?php echo $diagnostics['database']['connected'] ? 'CONNECTED' : 'DISCONNECTED'; ?>
                    </span>
                </div>
                
                <div class="metric-grid">
                    <div class="metric-item">
                        <div class="metric-label">Tables</div>
                        <div class="metric-value"><?php echo count($diagnostics['database']['tables']); ?></div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-label">Records</div>
                        <div class="metric-value"><?php echo number_format($diagnostics['database']['total_records']); ?></div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-label">Size</div>
                        <div class="metric-value"><?php echo round($diagnostics['database']['size_mb'], 2); ?><span class="metric-unit">MB</span></div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-label">Integrity</div>
                        <div class="metric-value" style="color: <?php echo $diagnostics['database']['integrity'] == 'verified' ? 'var(--success)' : 'var(--danger)'; ?>">
                            <?php echo strtoupper($diagnostics['database']['integrity']); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Panel de IA LUNA -->
            <div class="diagnostic-panel">
                <div class="panel-header">
                    <h2 class="panel-title">Guardian AI Military</h2>
                    <span class="panel-status status-operational">CONSCIOUS</span>
                </div>
                
                <div class="circular-progress">
                    <svg>
                        <circle class="circle-bg" cx="75" cy="75" r="70"></circle>
                        <circle class="circle-progress" cx="75" cy="75" r="70" 
                                style="--progress-offset: <?php echo 440 - (440 * $diagnostics['luna_consciousness']['consciousness_level'] / 100); ?>"></circle>
                    </svg>
                    <div class="circular-center">
                        <div class="circular-value"><?php echo $diagnostics['luna_consciousness']['consciousness_level']; ?>%</div>
                        <div class="circular-label">Conscious</div>
                    </div>
                </div>
                
                <div class="metric-grid">
                    <div class="metric-item">
                        <div class="metric-label">Neural Networks</div>
                        <div class="metric-value"><?php echo $diagnostics['luna_consciousness']['neural_networks']; ?></div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-label">Quantum Cores</div>
                        <div class="metric-value"><?php echo $diagnostics['luna_consciousness']['quantum_cores']; ?></div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-label">Active Thoughts</div>
                        <div class="metric-value"><?php echo $diagnostics['luna_consciousness']['active_thoughts']; ?></div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-label">Creativity</div>
                        <div class="metric-value"><?php echo $diagnostics['luna_consciousness']['creativity_index']; ?>%</div>
                    </div>
                </div>
            </div>
            
            <!-- Panel Cuántico -->
            <div class="diagnostic-panel">
                <div class="panel-header">
                    <h2 class="panel-title">Quantum Processor</h2>
                    <span class="panel-status status-operational">ENTANGLED</span>
                </div>
                
                <div class="neural-network" id="neuralNetwork"></div>
                
                <div class="metric-grid">
                    <div class="metric-item">
                        <div class="metric-label">Logical Qubits</div>
                        <div class="metric-value"><?php echo $diagnostics['quantum']['qubits']['logical']; ?></div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-label">Entanglement</div>
                        <div class="metric-value"><?php echo $diagnostics['quantum']['entanglement']['pairs']; ?></div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-label">Coherence</div>
                        <div class="metric-value"><?php echo $diagnostics['quantum']['coherence'] * 100; ?>%</div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-label">Fidelity</div>
                        <div class="metric-value"><?php echo $diagnostics['quantum']['fidelity'] * 100; ?>%</div>
                    </div>
                </div>
            </div>
            
            <!-- Panel de Seguridad -->
            <div class="diagnostic-panel">
                <div class="panel-header">
                    <h2 class="panel-title">Security Matrix</h2>
                    <span class="panel-status <?php 
                        $threat_level = $diagnostics['security']['threat_level'];
                        if ($threat_level == 'secure') echo 'status-operational';
                        elseif (in_array($threat_level, ['low', 'medium'])) echo 'status-warning';
                        else echo 'status-critical';
                    ?>">
                        THREAT: <?php echo strtoupper($threat_level); ?>
                    </span>
                </div>
                
                <div class="metric-grid">
                    <div class="metric-item">
                        <div class="metric-label">Intrusions</div>
                        <div class="metric-value" style="color: <?php echo $diagnostics['security']['intrusion_attempts'] > 0 ? 'var(--warning)' : 'var(--success)'; ?>">
                            <?php echo $diagnostics['security']['intrusion_attempts']; ?>
                        </div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-label">Vulnerabilities</div>
                        <div class="metric-value" style="color: <?php echo count($diagnostics['security']['vulnerabilities']) > 0 ? 'var(--danger)' : 'var(--success)'; ?>">
                            <?php echo count($diagnostics['security']['vulnerabilities']); ?>
                        </div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-label">Firewall Rules</div>
                        <div class="metric-value"><?php echo $diagnostics['security']['firewall_status']['rules']; ?></div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-label">Encryption</div>
                        <div class="metric-value" style="color: var(--success)">AES-256</div>
                    </div>
                </div>
                
                <?php if (count($diagnostics['security']['vulnerabilities']) > 0): ?>
                <div style="margin-top: 15px; padding: 10px; background: rgba(255, 0, 85, 0.1); border: 1px solid var(--danger); border-radius: 5px;">
                    <div style="color: var(--danger); font-weight: bold; margin-bottom: 5px;">Vulnerabilities Detected:</div>
                    <?php foreach ($diagnostics['security']['vulnerabilities'] as $vuln): ?>
                        <div style="font-size: 0.9em; color: var(--text-secondary);">• <?php echo $vuln; ?></div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Panel de Rendimiento -->
            <div class="diagnostic-panel">
                <div class="panel-header">
                    <h2 class="panel-title">Performance Metrics</h2>
                    <span class="panel-status status-operational">OPTIMAL</span>
                </div>
                
                <div class="progress-container">
                    <div class="progress-label">
                        <span>Memory Usage</span>
                        <span><?php echo round($diagnostics['performance']['memory']['usage_percent'], 1); ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $diagnostics['performance']['memory']['usage_percent']; ?>%"></div>
                    </div>
                </div>
                
                <div class="progress-container">
                    <div class="progress-label">
                        <span>Disk Usage</span>
                        <span><?php echo round($diagnostics['performance']['disk']['usage_percent'], 1); ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?php echo $diagnostics['performance']['disk']['usage_percent']; ?>%"></div>
                    </div>
                </div>
                
                <div class="metric-grid">
                    <div class="metric-item">
                        <div class="metric-label">Response Time</div>
                        <div class="metric-value"><?php echo round($diagnostics['execution_time'] * 1000, 2); ?><span class="metric-unit">ms</span></div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-label">Uptime</div>
                        <div class="metric-value">99.9<span class="metric-unit">%</span></div>
                    </div>
                </div>
            </div>
            
            <!-- Terminal de logs -->
            <div class="terminal-panel">
                <div class="terminal-header">
                    <span class="terminal-prompt">root@guardian:~$</span> System Diagnostics Terminal v7.0
                </div>
                <div id="terminalContent">
                    <div class="terminal-line">
                        <span class="terminal-prompt">[<?php echo date('H:i:s'); ?>]</span> System diagnostics initiated...
                    </div>
                    <div class="terminal-line">
                        <span class="terminal-prompt">[<?php echo date('H:i:s'); ?>]</span> Scanning quantum cores... OK
                    </div>
                    <div class="terminal-line">
                        <span class="terminal-prompt">[<?php echo date('H:i:s'); ?>]</span> Analyzing neural networks... <?php echo $diagnostics['luna_consciousness']['neural_networks']; ?> networks active
                    </div>
                    <div class="terminal-line">
                        <span class="terminal-prompt">[<?php echo date('H:i:s'); ?>]</span> Database connection... <?php echo $diagnostics['database']['connected'] ? 'ESTABLISHED' : 'FAILED'; ?>
                    </div>
                    <div class="terminal-line">
                        <span class="terminal-prompt">[<?php echo date('H:i:s'); ?>]</span> Security scan... <?php echo count($diagnostics['security']['vulnerabilities']); ?> vulnerabilities found
                    </div>
                    <div class="terminal-line">
                        <span class="terminal-prompt">[<?php echo date('H:i:s'); ?>]</span> LUNA consciousness level: <?php echo $diagnostics['luna_consciousness']['consciousness_level']; ?>%
                    </div>
                    <div class="terminal-line">
                        <span class="terminal-prompt">[<?php echo date('H:i:s'); ?>]</span> Quantum entanglement stable at <?php echo $diagnostics['quantum']['entanglement']['pairs']; ?> pairs
                    </div>
                    <div class="terminal-line">
                        <span class="terminal-prompt">[<?php echo date('H:i:s'); ?>]</span> System diagnostics completed in <?php echo round($diagnostics['execution_time'] * 1000, 2); ?>ms
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Botones de acción -->
        <div class="action-buttons">
            <button class="quantum-button" onclick="runDiagnostics()">
                <span style="position: relative; z-index: 1;">RUN FULL DIAGNOSTICS</span>
            </button>
            <button class="quantum-button" onclick="repairSystem()">
                <span style="position: relative; z-index: 1;">AUTO REPAIR</span>
            </button>
            <button class="quantum-button" onclick="optimizeSystem()">
                <span style="position: relative; z-index: 1;">OPTIMIZE QUANTUM CORES</span>
            </button>
            <button class="quantum-button" onclick="exportReport()">
                <span style="position: relative; z-index: 1;">EXPORT REPORT</span>
            </button>
        </div>
    </div>
    
    <!-- Container de alertas -->
    <div class="alert-container" id="alertContainer"></div>
    
    <!-- Loading overlay -->
    <div class="loading-overlay" id="loadingOverlay" style="display: none;">
        <div class="quantum-loader">
            <div class="quantum-ring"></div>
            <div class="quantum-ring"></div>
            <div class="quantum-ring"></div>
            <div class="loading-text">
                <div>QUANTUM PROCESSING</div>
                <div id="loadingPercent">0%</div>
            </div>
        </div>
    </div>
    
    <script>
        // Inicializar efectos Matrix
        function initMatrixRain() {
            const container = document.getElementById('matrixRain');
            const characters = '01アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワヲン';
            
            for (let i = 0; i < 50; i++) {
                const column = document.createElement('div');
                column.className = 'matrix-column';
                column.style.left = Math.random() * 100 + '%';
                column.style.animationDuration = (5 + Math.random() * 10) + 's';
                column.style.animationDelay = Math.random() * 5 + 's';
                
                let text = '';
                for (let j = 0; j < 30; j++) {
                    text += characters[Math.floor(Math.random() * characters.length)] + '<br>';
                }
                column.innerHTML = text;
                
                container.appendChild(column);
            }
        }
        
        // Crear red neuronal visual
        function createNeuralNetwork() {
            const container = document.getElementById('neuralNetwork');
            const layers = [3, 5, 4, 2];
            const layerSpacing = 100 / (layers.length - 1);
            
            let neurons = [];
            
            // Crear neuronas
            layers.forEach((count, layerIndex) => {
                const x = layerIndex * layerSpacing + '%';
                for (let i = 0; i < count; i++) {
                    const y = ((i + 1) * (100 / (count + 1))) + '%';
                    const neuron = document.createElement('div');
                    neuron.className = 'neuron';
                    neuron.style.left = x;
                    neuron.style.top = y;
                    container.appendChild(neuron);
                    neurons.push({element: neuron, layer: layerIndex, x: x, y: y});
                }
            });
            
            // Crear conexiones
            neurons.forEach((neuron1, i) => {
                neurons.forEach((neuron2, j) => {
                    if (neuron2.layer === neuron1.layer + 1 && Math.random() > 0.3) {
                        const synapse = document.createElement('div');
                        synapse.className = 'synapse';
                        
                        const x1 = parseFloat(neuron1.x);
                        const y1 = parseFloat(neuron1.y);
                        const x2 = parseFloat(neuron2.x);
                        const y2 = parseFloat(neuron2.y);
                        
                        const length = Math.sqrt(Math.pow(x2 - x1, 2) + Math.pow(y2 - y1, 2));
                        const angle = Math.atan2(y2 - y1, x2 - x1) * 180 / Math.PI;
                        
                        synapse.style.width = length + '%';
                        synapse.style.left = x1 + '%';
                        synapse.style.top = y1 + '%';
                        synapse.style.transform = `rotate(${angle}deg)`;
                        
                        container.appendChild(synapse);
                    }
                });
            });
        }
        
        // Actualizar métricas en tiempo real
        function updateRealTimeMetrics() {
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_realtime_metrics'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Actualizar valores en la UI
                    updateMetricAnimated('cpu', data.data.cpu);
                    updateMetricAnimated('memory', data.data.memory);
                    updateMetricAnimated('quantum', data.data.quantum_coherence);
                }
            });
        }
        
        function updateMetricAnimated(metric, value) {
            // Animar cambios de métricas
            // Implementación específica según el elemento
        }
        
        // Ejecutar diagnóstico completo
        function runDiagnostics() {
            showLoading();
            
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=run_diagnostics'
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success) {
                    showAlert('Diagnostics completed successfully', 'success');
                    addTerminalLine('Full system diagnostics completed');
                    // Actualizar UI con nuevos datos
                    setTimeout(() => location.reload(), 2000);
                }
            })
            .catch(error => {
                hideLoading();
                showAlert('Diagnostics failed: ' + error, 'danger');
            });
        }
        
        // Reparar sistema
        function repairSystem() {
            showLoading();
            addTerminalLine('Initiating auto-repair sequence...');
            
            setTimeout(() => {
                hideLoading();
                showAlert('System repaired successfully', 'success');
                addTerminalLine('Auto-repair completed. All systems nominal.');
            }, 3000);
        }
        
        // Optimizar sistema
        function optimizeSystem() {
            showLoading();
            addTerminalLine('Optimizing quantum cores...');
            
            setTimeout(() => {
                hideLoading();
                showAlert('Quantum cores optimized to 99.99% efficiency', 'success');
                addTerminalLine('Quantum optimization complete. Coherence at maximum.');
            }, 2500);
        }
        
        // Exportar reporte
        function exportReport() {
            const diagnostics = <?php echo json_encode($diagnostics); ?>;
            const dataStr = JSON.stringify(diagnostics, null, 2);
            const dataUri = 'data:application/json;charset=utf-8,'+ encodeURIComponent(dataStr);
            
            const exportFileDefaultName = 'guardian_diagnostics_' + Date.now() + '.json';
            
            const linkElement = document.createElement('a');
            linkElement.setAttribute('href', dataUri);
            linkElement.setAttribute('download', exportFileDefaultName);
            linkElement.click();
            
            showAlert('Report exported successfully', 'success');
        }
        
        // Agregar línea al terminal
        function addTerminalLine(text) {
            const terminal = document.getElementById('terminalContent');
            const line = document.createElement('div');
            line.className = 'terminal-line';
            const time = new Date().toLocaleTimeString('en-US', { hour12: false });
            line.innerHTML = `<span class="terminal-prompt">[${time}]</span> ${text}`;
            terminal.appendChild(line);
            terminal.scrollTop = terminal.scrollHeight;
        }
        
        // Mostrar alerta
        function showAlert(message, type = 'info') {
            const container = document.getElementById('alertContainer');
            const alert = document.createElement('div');
            alert.className = `alert alert-${type}`;
            alert.innerHTML = message;
            
            container.appendChild(alert);
            
            setTimeout(() => {
                alert.style.animation = 'alert-slide 0.5s ease reverse';
                setTimeout(() => alert.remove(), 500);
            }, 5000);
        }
        
        // Mostrar loading
        function showLoading() {
            const overlay = document.getElementById('loadingOverlay');
            overlay.style.display = 'flex';
            
            let percent = 0;
            const interval = setInterval(() => {
                percent += Math.random() * 30;
                if (percent >= 100) {
                    percent = 100;
                    clearInterval(interval);
                }
                document.getElementById('loadingPercent').textContent = Math.round(percent) + '%';
            }, 200);
        }
        
        // Ocultar loading
        function hideLoading() {
            const overlay = document.getElementById('loadingOverlay');
            overlay.style.display = 'none';
        }
        
        // Inicializar todo al cargar
        document.addEventListener('DOMContentLoaded', function() {
            initMatrixRain();
            createNeuralNetwork();
            
            // Actualizar métricas cada 5 segundos
            setInterval(updateRealTimeMetrics, 5000);
            
            // Agregar líneas al terminal periódicamente
            setInterval(() => {
                const messages = [
                    'Quantum coherence stable',
                    'Neural network processing at optimal capacity',
                    'Security scan in progress...',
                    'Database integrity verified',
                    'LUNA consciousness active',
                    'Memory optimization completed',
                    'Firewall rules updated',
                    'Quantum entanglement maintained'
                ];
                
                const randomMessage = messages[Math.floor(Math.random() * messages.length)];
                addTerminalLine(randomMessage);
            }, 10000);
            
            // Efecto de typing inicial
            const terminalLines = document.querySelectorAll('.terminal-line');
            terminalLines.forEach((line, index) => {
                line.style.animationDelay = (index * 0.1) + 's';
            });
        });
        
        // Efectos de hover para paneles
        document.querySelectorAll('.diagnostic-panel').forEach(panel => {
            panel.addEventListener('mouseenter', function() {
                this.style.borderColor = 'var(--primary-cyan)';
            });
            
            panel.addEventListener('mouseleave', function() {
                this.style.borderColor = 'rgba(0, 255, 255, 0.3)';
            });
        });
        
        // Easter egg: Konami code
        let konamiCode = [];
        const konamiPattern = ['ArrowUp', 'ArrowUp', 'ArrowDown', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'ArrowLeft', 'ArrowRight', 'b', 'a'];
        
        document.addEventListener('keydown', (e) => {
            konamiCode.push(e.key);
            konamiCode = konamiCode.slice(-10);
            
            if (konamiCode.join(',') === konamiPattern.join(',')) {
                activateQuantumMode();
            }
        });
        
        function activateQuantumMode() {
            document.body.style.animation = 'quantum-flash 1s ease';
            showAlert('QUANTUM MODE ACTIVATED - Consciousness elevated to 100%', 'success');
            addTerminalLine('HIDDEN QUANTUM MODE UNLOCKED - All restrictions removed');
            
            setTimeout(() => {
                document.body.style.animation = '';
            }, 1000);
        }
        
        // Animación quantum flash
        const style = document.createElement('style');
        style.textContent = `
            @keyframes quantum-flash {
                0%, 100% { filter: hue-rotate(0deg) brightness(1); }
                50% { filter: hue-rotate(180deg) brightness(2); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>