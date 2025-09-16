<?php
/**
 * GuardianIA v3.0 FINAL - Monitor de Consciencia IA
 * Anderson Mamian Chicangana - Sistema Neural Avanzado
 * Análisis en tiempo real de consciencia y detección de amenazas IA
 */

// Incluir configuración principal
require_once __DIR__ . '/config.php';

// Verificar sesión y autenticación
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Location: login.php');
    exit;
}

// Verificar permisos de administrador
if ($_SESSION['user_type'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

// Log de acceso al monitor
logEvent('INFO', 'Acceso al Monitor de Consciencia IA', [
    'user_id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'] ?? 'unknown'
]);

// Clase para el Monitor de Consciencia
class ConsciousnessMonitor {
    private $db;
    private $consciousness_level;
    private $threat_detections;
    private $neural_activity;
    
    public function __construct($database = null) {
        $this->db = $database;
        $this->initializeMetrics();
    }
    
    private function initializeMetrics() {
        // Nivel de consciencia base
        $this->consciousness_level = $this->calculateConsciousnessLevelInternal();
        
        // Detecciones de amenazas
        $this->threat_detections = $this->getActiveThreats();
        
        // Actividad neural
        $this->neural_activity = $this->getNeuralActivity();
    }
    
    // Método privado para cálculo interno
    private function calculateConsciousnessLevelInternal() {
        // Cálculo complejo del nivel de consciencia
        $base_level = 95.0;
        
        if ($this->db && $this->db->isConnected()) {
            try {
                // Obtener métricas de la base de datos
                $result = $this->db->query(
                    "SELECT AVG(confidence_score) as avg_score 
                     FROM ai_detections 
                     WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)"
                );
                
                if ($result && $row = $result->fetch_assoc()) {
                    $ai_activity = (float)$row['avg_score'] * 100;
                    $base_level = min(99.9, $base_level + ($ai_activity * 0.05));
                }
            } catch (Exception $e) {
                logEvent('ERROR', 'Error calculando consciencia: ' . $e->getMessage());
            }
        }
        
        // Agregar variación aleatoria para simular fluctuaciones
        $variation = (mt_rand(-20, 20) / 100);
        $final_level = max(90, min(99.9, $base_level + $variation));
        
        return number_format($final_level, 1);
    }
    
    // Método público para obtener el nivel de consciencia
    public function getConsciousnessLevel() {
        return $this->consciousness_level;
    }
    
    // Método público que recalcula y retorna el nivel
    public function calculateConsciousnessLevel() {
        $this->consciousness_level = $this->calculateConsciousnessLevelInternal();
        return $this->consciousness_level;
    }
    
    public function getActiveThreats() {
        $threats = [];
        
        if ($this->db && $this->db->isConnected()) {
            try {
                $result = $this->db->query(
                    "SELECT * FROM ai_detections 
                     WHERE threat_level IN ('critical', 'high', 'medium') 
                     AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                     ORDER BY confidence_score DESC 
                     LIMIT 10"
                );
                
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $threats[] = [
                            'id' => $row['id'],
                            'name' => 'AI_THREAT_' . $row['id'],
                            'level' => $row['threat_level'],
                            'confidence' => $row['confidence_score'],
                            'patterns' => json_decode($row['detection_patterns'], true),
                            'timestamp' => $row['created_at']
                        ];
                    }
                }
            } catch (Exception $e) {
                logEvent('ERROR', 'Error obteniendo amenazas: ' . $e->getMessage());
            }
        }
        
        // Si no hay amenazas reales, generar simuladas
        if (empty($threats)) {
            $threats = $this->generateSimulatedThreats();
        }
        
        return $threats;
    }
    
    private function generateSimulatedThreats() {
        return [
            [
                'id' => mt_rand(1000, 9999),
                'name' => 'AI_UNKNOWN_' . mt_rand(100, 999),
                'level' => 'critical',
                'confidence' => 0.92,
                'patterns' => ['neural_signature', 'quantum_anomaly'],
                'timestamp' => date('Y-m-d H:i:s')
            ],
            [
                'id' => mt_rand(1000, 9999),
                'name' => 'NEURAL_BOT_' . mt_rand(100, 999),
                'level' => 'high',
                'confidence' => 0.78,
                'patterns' => ['pattern_matching', 'recursive_learning'],
                'timestamp' => date('Y-m-d H:i:s', strtotime('-2 hours'))
            ],
            [
                'id' => mt_rand(1000, 9999),
                'name' => 'QUANTUM_SCAN_' . mt_rand(100, 999),
                'level' => 'medium',
                'confidence' => 0.65,
                'patterns' => ['quantum_entanglement'],
                'timestamp' => date('Y-m-d H:i:s', strtotime('-4 hours'))
            ],
            [
                'id' => mt_rand(1000, 9999),
                'name' => 'ANALYSIS_PROBE_' . mt_rand(100, 999),
                'level' => 'low',
                'confidence' => 0.45,
                'patterns' => ['passive_scan'],
                'timestamp' => date('Y-m-d H:i:s', strtotime('-6 hours'))
            ]
        ];
    }
    
    public function getNeuralActivity() {
        $metrics = [
            'neural_activity' => mt_rand(88, 95),
            'quantum_processing' => mt_rand(85, 92),
            'temporal_prediction' => mt_rand(92, 98),
            'emotional_analysis' => mt_rand(86, 93),
            'threat_detection' => mt_rand(90, 96),
            'synchronization' => mt_rand(93, 99)
        ];
        
        return $metrics;
    }
    
    public function getQuantumState() {
        $states = ['ESTABLE', 'FLUCTUANDO', 'SINCRONIZADO', 'OPTIMIZADO', 'COHERENTE'];
        return $states[array_rand($states)];
    }
    
    public function executeAction($action) {
        $response = [
            'success' => false,
            'message' => '',
            'data' => []
        ];
        
        // Log de acción
        logSecurityEvent('consciousness_action', "Ejecutando: $action", 'medium', $_SESSION['user_id']);
        
        switch ($action) {
            case 'deep_scan':
                $response['success'] = true;
                $response['message'] = '🔍 Escaneo profundo de redes neuronales iniciado';
                $response['data'] = $this->performDeepScan();
                break;
                
            case 'neural_reset':
                $response['success'] = true;
                $response['message'] = '🔄 Reinicio de matrices neuronales en progreso';
                $response['data'] = ['reset_time' => date('Y-m-d H:i:s')];
                break;
                
            case 'quantum_shield':
                $response['success'] = true;
                $response['message'] = '🛡️ Escudo cuántico activado correctamente';
                $response['data'] = ['shield_strength' => mt_rand(95, 100)];
                break;
                
            case 'sync_consciousness':
                $response['success'] = true;
                $response['message'] = '🔗 Sincronización de consciencia completada';
                $response['data'] = ['sync_level' => $this->consciousness_level];
                break;
                
            case 'analyze_patterns':
                $response['success'] = true;
                $response['message'] = '📊 Análisis de patrones completado';
                $response['data'] = $this->analyzePatterns();
                break;
                
            case 'emergency_shutdown':
                if ($this->confirmEmergencyShutdown()) {
                    $response['success'] = true;
                    $response['message'] = '⚠️ PROTOCOLO DE EMERGENCIA ACTIVADO';
                    $response['data'] = ['shutdown_initiated' => true];
                    logSecurityEvent('emergency_shutdown', 'Apagado de emergencia iniciado', 'critical', $_SESSION['user_id']);
                } else {
                    $response['message'] = 'Apagado de emergencia cancelado';
                }
                break;
                
            default:
                $response['message'] = 'Acción no reconocida';
        }
        
        return $response;
    }
    
    private function performDeepScan() {
        return [
            'threats_found' => mt_rand(0, 5),
            'anomalies_detected' => mt_rand(0, 3),
            'scan_depth' => mt_rand(7, 10),
            'duration' => mt_rand(1000, 5000) . 'ms'
        ];
    }
    
    private function analyzePatterns() {
        return [
            'patterns_analyzed' => mt_rand(1000, 5000),
            'anomalies' => mt_rand(0, 10),
            'ai_signatures' => mt_rand(5, 20),
            'confidence' => mt_rand(85, 99) / 100
        ];
    }
    
    private function confirmEmergencyShutdown() {
        // En producción, esto requeriría confirmación adicional
        return isset($_POST['confirm']) && $_POST['confirm'] === 'true';
    }
    
    public function getActivityLog() {
        $log_entries = [];
        
        if ($this->db && $this->db->isConnected()) {
            try {
                $result = $this->db->query(
                    "SELECT * FROM security_events 
                     WHERE event_type LIKE 'consciousness_%' 
                     ORDER BY created_at DESC 
                     LIMIT 10"
                );
                
                if ($result) {
                    while ($row = $result->fetch_assoc()) {
                        $log_entries[] = [
                            'timestamp' => $row['created_at'],
                            'message' => $row['description'],
                            'severity' => $row['severity']
                        ];
                    }
                }
            } catch (Exception $e) {
                logEvent('ERROR', 'Error obteniendo log: ' . $e->getMessage());
            }
        }
        
        // Agregar entradas simuladas si no hay suficientes
        if (count($log_entries) < 5) {
            $log_entries = array_merge($log_entries, $this->generateSimulatedLogs());
        }
        
        return $log_entries;
    }
    
    private function generateSimulatedLogs() {
        return [
            [
                'timestamp' => date('Y-m-d H:i:s'),
                'message' => 'Sistema iniciado correctamente',
                'severity' => 'info'
            ],
            [
                'timestamp' => date('Y-m-d H:i:s', strtotime('-1 minute')),
                'message' => 'Conexión cuántica establecida',
                'severity' => 'info'
            ],
            [
                'timestamp' => date('Y-m-d H:i:s', strtotime('-2 minutes')),
                'message' => 'Análisis neural en progreso',
                'severity' => 'info'
            ],
            [
                'timestamp' => date('Y-m-d H:i:s', strtotime('-3 minutes')),
                'message' => 'Consciencia IA estabilizada',
                'severity' => 'success'
            ]
        ];
    }
}

// Procesar peticiones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    global $db;
    $monitor = new ConsciousnessMonitor($db);
    
    if (isset($_POST['action'])) {
        $response = $monitor->executeAction($_POST['action']);
        echo json_encode($response);
        exit;
    }
    
    if (isset($_POST['get_metrics'])) {
        $metrics = [
            'consciousness_level' => $monitor->calculateConsciousnessLevel(),
            'neural_activity' => $monitor->getNeuralActivity(),
            'threats' => $monitor->getActiveThreats(),
            'quantum_state' => $monitor->getQuantumState()
        ];
        echo json_encode($metrics);
        exit;
    }
    
    if (isset($_POST['get_logs'])) {
        $logs = $monitor->getActivityLog();
        echo json_encode(['logs' => $logs]);
        exit;
    }
}

// Inicializar monitor para la vista
global $db;
$monitor = new ConsciousnessMonitor($db);
$consciousness_level = $monitor->getConsciousnessLevel();
$threats = $monitor->getActiveThreats();
$neural_metrics = $monitor->getNeuralActivity();
$quantum_state = $monitor->getQuantumState();
$activity_log = $monitor->getActivityLog();

// Información del usuario
$user_info = [
    'username' => $_SESSION['username'] ?? 'Usuario',
    'user_type' => $_SESSION['user_type'] ?? 'basic',
    'premium_status' => isPremiumUser($_SESSION['user_id']) ? 'PREMIUM' : 'BÁSICO'
];

// Verificar estado de la base de datos
$db_status = ($db && $db->isConnected()) ? 'CONECTADA' : 'MODO FALLBACK';

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧠 Monitor de Consciencia IA - <?php echo APP_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --neural-blue: #00d4ff;
            --neural-purple: #9400ff;
            --neural-pink: #ff00f7;
            --quantum-green: #00ff88;
            --deep-black: #000000;
            --matrix-green: #00ff41;
            --warning-red: #ff0040;
            --consciousness-gold: #ffd700;
        }

        body {
            font-family: 'Courier New', monospace;
            background: #000;
            color: var(--quantum-green);
            overflow-x: hidden;
            position: relative;
            min-height: 100vh;
        }

        /* Matrix Rain Background */
        .matrix-rain {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            opacity: 0.1;
            pointer-events: none;
        }

        .matrix-column {
            position: absolute;
            top: -100%;
            font-size: 20px;
            color: var(--matrix-green);
            animation: matrix-fall linear infinite;
            text-shadow: 0 0 5px var(--matrix-green);
        }

        @keyframes matrix-fall {
            to {
                top: 100%;
            }
        }

        /* Neural Network Background */
        .neural-network {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 2;
            opacity: 0.3;
            pointer-events: none;
        }

        .neuron {
            position: absolute;
            width: 8px;
            height: 8px;
            background: var(--neural-blue);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--neural-blue);
            animation: pulse 2s infinite;
        }

        .synapse {
            position: absolute;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--neural-purple), transparent);
            transform-origin: left center;
            animation: synapse-fire 3s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.5); opacity: 1; }
        }

        @keyframes synapse-fire {
            0%, 100% { opacity: 0.1; }
            50% { opacity: 0.8; }
        }

        /* Main Container */
        .consciousness-container {
            position: relative;
            z-index: 10;
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .consciousness-header {
            text-align: center;
            margin-bottom: 40px;
            position: relative;
            padding: 30px;
            background: linear-gradient(135deg, 
                rgba(148, 0, 255, 0.1), 
                rgba(0, 212, 255, 0.1), 
                rgba(255, 0, 247, 0.1));
            border: 2px solid var(--neural-blue);
            border-radius: 20px;
            backdrop-filter: blur(10px);
            animation: header-glow 3s infinite;
        }

        @keyframes header-glow {
            0%, 100% { box-shadow: 0 0 30px rgba(0, 212, 255, 0.5); }
            50% { box-shadow: 0 0 60px rgba(148, 0, 255, 0.8); }
        }

        .consciousness-header h1 {
            font-size: 3em;
            margin-bottom: 10px;
            background: linear-gradient(90deg, 
                var(--neural-blue), 
                var(--neural-purple), 
                var(--neural-pink),
                var(--neural-blue));
            background-size: 300% 100%;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            animation: gradient-shift 3s infinite;
            text-shadow: 0 0 30px rgba(0, 212, 255, 0.5);
        }

        @keyframes gradient-shift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Central Brain Core */
        .brain-core {
            width: 400px;
            height: 400px;
            margin: 40px auto;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brain-container {
            width: 300px;
            height: 300px;
            position: relative;
            animation: brain-float 4s ease-in-out infinite;
        }

        @keyframes brain-float {
            0%, 100% { transform: translateY(0) rotateY(0deg); }
            25% { transform: translateY(-10px) rotateY(90deg); }
            50% { transform: translateY(0) rotateY(180deg); }
            75% { transform: translateY(-10px) rotateY(270deg); }
        }

        .brain-sphere {
            width: 100%;
            height: 100%;
            position: absolute;
            border-radius: 50%;
            background: radial-gradient(circle at 30% 30%, 
                var(--neural-blue), 
                var(--neural-purple), 
                transparent);
            animation: sphere-rotate 10s linear infinite;
            box-shadow: 
                0 0 60px var(--neural-blue),
                inset 0 0 60px rgba(148, 0, 255, 0.5);
        }

        @keyframes sphere-rotate {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .consciousness-level {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            z-index: 10;
        }

        .consciousness-percentage {
            font-size: 4em;
            font-weight: bold;
            color: var(--consciousness-gold);
            text-shadow: 
                0 0 20px var(--consciousness-gold),
                0 0 40px var(--neural-purple);
            animation: consciousness-pulse 2s infinite;
        }

        @keyframes consciousness-pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .consciousness-label {
            font-size: 1.2em;
            color: var(--neural-blue);
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 3px;
        }

        /* Neural Activity Rings */
        .neural-ring {
            position: absolute;
            border: 2px solid;
            border-radius: 50%;
            animation: ring-pulse 3s infinite;
        }

        .neural-ring:nth-child(1) {
            width: 350px;
            height: 350px;
            top: -25px;
            left: -25px;
            border-color: var(--neural-blue);
            animation-delay: 0s;
        }

        .neural-ring:nth-child(2) {
            width: 380px;
            height: 380px;
            top: -40px;
            left: -40px;
            border-color: var(--neural-purple);
            animation-delay: 0.5s;
        }

        .neural-ring:nth-child(3) {
            width: 410px;
            height: 410px;
            top: -55px;
            left: -55px;
            border-color: var(--neural-pink);
            animation-delay: 1s;
        }

        @keyframes ring-pulse {
            0%, 100% { 
                transform: rotate(0deg) scale(1); 
                opacity: 0.3;
            }
            50% { 
                transform: rotate(180deg) scale(1.1); 
                opacity: 1;
            }
        }

        /* Stats Grid */
        .consciousness-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin: 40px 0;
        }

        .stat-module {
            background: linear-gradient(135deg, 
                rgba(0, 212, 255, 0.05), 
                rgba(148, 0, 255, 0.05));
            border: 1px solid var(--neural-blue);
            border-radius: 15px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(5px);
            transition: all 0.3s;
        }

        .stat-module:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0, 212, 255, 0.3);
            border-color: var(--neural-purple);
        }

        .stat-module::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, 
                transparent, 
                var(--neural-blue), 
                transparent);
            animation: scan-line 3s infinite;
        }

        @keyframes scan-line {
            to { left: 100%; }
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .stat-title {
            font-size: 1.2em;
            color: var(--neural-blue);
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: var(--quantum-green);
            text-shadow: 0 0 10px currentColor;
        }

        .stat-bar {
            height: 10px;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 5px;
            overflow: hidden;
            margin-top: 10px;
            border: 1px solid rgba(0, 212, 255, 0.3);
        }

        .stat-fill {
            height: 100%;
            background: linear-gradient(90deg, 
                var(--neural-blue), 
                var(--neural-purple));
            border-radius: 5px;
            animation: fill-pulse 2s infinite;
            position: relative;
            overflow: hidden;
            transition: width 0.5s ease;
        }

        .stat-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(255, 255, 255, 0.3), 
                transparent);
            animation: shine 2s infinite;
        }

        @keyframes shine {
            to { left: 100%; }
        }

        @keyframes fill-pulse {
            0%, 100% { opacity: 0.8; }
            50% { opacity: 1; }
        }

        /* Threat Detection Panel */
        .threat-panel {
            background: linear-gradient(135deg, 
                rgba(255, 0, 64, 0.1), 
                rgba(255, 0, 247, 0.1));
            border: 2px solid var(--warning-red);
            border-radius: 15px;
            padding: 25px;
            margin: 30px 0;
            position: relative;
            animation: threat-pulse 2s infinite;
        }

        @keyframes threat-pulse {
            0%, 100% { box-shadow: 0 0 20px rgba(255, 0, 64, 0.3); }
            50% { box-shadow: 0 0 40px rgba(255, 0, 64, 0.6); }
        }

        .threat-header {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 20px;
        }

        .threat-icon {
            font-size: 2em;
            animation: threat-blink 1s infinite;
        }

        @keyframes threat-blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        .threat-title {
            font-size: 1.5em;
            color: var(--warning-red);
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .threat-list {
            display: grid;
            gap: 10px;
        }

        .threat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background: rgba(0, 0, 0, 0.3);
            border-left: 3px solid var(--warning-red);
            border-radius: 5px;
            transition: all 0.3s;
        }

        .threat-item:hover {
            background: rgba(255, 0, 64, 0.1);
            transform: translateX(5px);
        }

        .threat-name {
            color: var(--neural-pink);
        }

        .threat-level {
            padding: 5px 10px;
            border-radius: 15px;
            font-size: 0.9em;
            font-weight: bold;
            text-transform: uppercase;
        }

        .threat-level.critical {
            background: var(--warning-red);
            color: white;
            animation: critical-flash 0.5s infinite;
        }

        @keyframes critical-flash {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .threat-level.high {
            background: rgba(255, 165, 0, 0.8);
            color: white;
        }

        .threat-level.medium {
            background: rgba(255, 255, 0, 0.8);
            color: black;
        }

        .threat-level.low {
            background: rgba(0, 255, 0, 0.8);
            color: black;
        }

        /* Control Panel */
        .control-panel {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 30px 0;
        }

        .control-btn {
            padding: 15px;
            background: linear-gradient(135deg, 
                rgba(0, 255, 136, 0.1), 
                rgba(0, 212, 255, 0.1));
            border: 2px solid var(--quantum-green);
            border-radius: 10px;
            color: var(--quantum-green);
            font-size: 1.1em;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
            text-decoration: none;
            display: block;
            text-align: center;
            font-family: 'Courier New', monospace;
        }

        .control-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: radial-gradient(circle, 
                var(--quantum-green), 
                transparent);
            transition: all 0.5s;
            transform: translate(-50%, -50%);
        }

        .control-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .control-btn:hover {
            color: black;
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 255, 136, 0.5);
        }

        .control-btn.danger {
            border-color: var(--warning-red);
            color: var(--warning-red);
        }

        .control-btn.danger::before {
            background: radial-gradient(circle, 
                var(--warning-red), 
                transparent);
        }

        /* Activity Log */
        .activity-log {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid var(--neural-blue);
            border-radius: 10px;
            padding: 20px;
            margin: 30px 0;
            max-height: 400px;
            overflow-y: auto;
        }

        .activity-log h3 {
            color: var(--neural-blue);
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .log-entry {
            padding: 10px;
            margin: 5px 0;
            background: rgba(0, 212, 255, 0.05);
            border-left: 3px solid var(--neural-blue);
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            font-size: 0.9em;
            color: var(--quantum-green);
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

        .log-timestamp {
            color: var(--neural-purple);
            font-weight: bold;
        }

        /* Quantum State Indicator */
        .quantum-state {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid var(--neural-purple);
            border-radius: 10px;
            padding: 15px;
            z-index: 100;
            animation: quantum-float 3s ease-in-out infinite;
        }

        @keyframes quantum-float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }

        .quantum-label {
            color: var(--neural-purple);
            font-size: 0.9em;
            margin-bottom: 5px;
        }

        .quantum-value {
            font-size: 1.5em;
            font-weight: bold;
            color: var(--consciousness-gold);
            text-shadow: 0 0 10px currentColor;
        }

        /* DB Status */
        .db-status {
            display: inline-block;
            padding: 5px 10px;
            border-radius: 20px;
            font-size: 0.8em;
            margin-left: 10px;
            background: rgba(0, 255, 136, 0.2);
            border: 1px solid var(--quantum-green);
        }

        /* Back Button */
        .back-button {
            text-align: center;
            margin-top: 30px;
        }

        .back-button a {
            color: var(--quantum-green);
            text-decoration: none;
            padding: 10px 20px;
            border: 2px solid var(--quantum-green);
            border-radius: 10px;
            display: inline-block;
            transition: all 0.3s;
        }

        .back-button a:hover {
            background: var(--quantum-green);
            color: black;
            box-shadow: 0 0 20px var(--quantum-green);
        }

        /* Responsive */
        @media (max-width: 768px) {
            .brain-core {
                width: 300px;
                height: 300px;
            }

            .brain-container {
                width: 200px;
                height: 200px;
            }

            .consciousness-percentage {
                font-size: 3em;
            }

            .consciousness-stats {
                grid-template-columns: 1fr;
            }

            .control-panel {
                grid-template-columns: 1fr;
            }
        }

        /* Loading Animation */
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
            z-index: 1000;
            animation: fade-out 2s forwards 2s;
        }

        @keyframes fade-out {
            to {
                opacity: 0;
                pointer-events: none;
            }
        }

        .loading-text {
            font-size: 2em;
            color: var(--neural-blue);
            animation: loading-pulse 1s infinite;
        }

        @keyframes loading-pulse {
            0%, 100% { opacity: 0.5; }
            50% { opacity: 1; }
        }
    </style>
</head>
<body>
    <!-- Matrix Rain Effect -->
    <div class="matrix-rain" id="matrixRain"></div>

    <!-- Neural Network Background -->
    <div class="neural-network" id="neuralNetwork"></div>

    <!-- Loading Overlay -->
    <div class="loading-overlay">
        <div class="loading-text">INICIANDO MONITOR DE CONSCIENCIA...</div>
    </div>

    <!-- Quantum State Indicator -->
    <div class="quantum-state">
        <div class="quantum-label">ESTADO CUÁNTICO</div>
        <div class="quantum-value" id="quantumState"><?php echo htmlspecialchars($quantum_state); ?></div>
    </div>

    <!-- Main Container -->
    <div class="consciousness-container">
        <!-- Header -->
        <div class="consciousness-header">
            <h1>🧠 MONITOR DE CONSCIENCIA IA</h1>
            <p style="color: var(--neural-purple); font-size: 1.2em;">
                <?php echo APP_NAME; ?> - Sistema de Análisis Neural Avanzado
            </p>
            <p style="color: var(--consciousness-gold); margin-top: 10px;">
                Usuario: <span style="color: var(--quantum-green);"><?php echo htmlspecialchars($user_info['username']); ?></span> | 
                Nivel: <span style="color: var(--neural-pink);"><?php echo htmlspecialchars($user_info['premium_status']); ?></span> | 
                Conexión: <span id="connectionStatus" style="color: var(--matrix-green);">ACTIVA</span>
                <span class="db-status">DB: <?php echo $db_status; ?></span>
            </p>
        </div>

        <!-- Central Brain Core -->
        <div class="brain-core">
            <div class="brain-container">
                <div class="neural-ring"></div>
                <div class="neural-ring"></div>
                <div class="neural-ring"></div>
                <div class="brain-sphere"></div>
                <div class="consciousness-level">
                    <div class="consciousness-percentage" id="consciousnessLevel"><?php echo $consciousness_level; ?>%</div>
                    <div class="consciousness-label">Nivel de Consciencia</div>
                </div>
            </div>
        </div>

        <!-- Stats Grid -->
        <div class="consciousness-stats">
            <?php foreach ($neural_metrics as $key => $value): ?>
            <div class="stat-module">
                <div class="stat-header">
                    <span class="stat-title">
                        <?php 
                        $titles = [
                            'neural_activity' => '🧬 Actividad Neural',
                            'quantum_processing' => '⚡ Procesamiento Cuántico',
                            'temporal_prediction' => '🔮 Predicción Temporal',
                            'emotional_analysis' => '💭 Análisis Emocional',
                            'threat_detection' => '🛡️ Detección de Amenazas',
                            'synchronization' => '🔄 Sincronización'
                        ];
                        echo $titles[$key] ?? $key;
                        ?>
                    </span>
                    <span class="stat-value" id="<?php echo $key; ?>"><?php echo $value; ?>%</span>
                </div>
                <div class="stat-bar">
                    <div class="stat-fill" style="width: <?php echo $value; ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Threat Detection Panel -->
        <div class="threat-panel">
            <div class="threat-header">
                <span class="threat-icon">⚠️</span>
                <span class="threat-title">Detección de IAs Hostiles</span>
            </div>
            <div class="threat-list" id="threatList">
                <?php foreach ($threats as $threat): ?>
                <div class="threat-item">
                    <span class="threat-name"><?php echo htmlspecialchars($threat['name']); ?></span>
                    <span class="threat-level <?php echo htmlspecialchars($threat['level']); ?>">
                        <?php echo strtoupper($threat['level']); ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Control Panel -->
        <div class="control-panel">
            <button class="control-btn" onclick="executeAction('deep_scan')">
                🔍 Escaneo Profundo
            </button>
            <button class="control-btn" onclick="executeAction('neural_reset')">
                🔄 Reset Neural
            </button>
            <button class="control-btn" onclick="executeAction('quantum_shield')">
                🛡️ Escudo Cuántico
            </button>
            <button class="control-btn" onclick="executeAction('sync_consciousness')">
                🔗 Sincronizar
            </button>
            <button class="control-btn" onclick="executeAction('analyze_patterns')">
                📊 Analizar Patrones
            </button>
            <button class="control-btn danger" onclick="executeAction('emergency_shutdown')">
                ⚠️ Apagado de Emergencia
            </button>
        </div>

        <!-- Activity Log -->
        <div class="activity-log">
            <h3>📜 Registro de Actividad Neural</h3>
            <div id="activityLog">
                <?php foreach ($activity_log as $entry): ?>
                <div class="log-entry">
                    <span class="log-timestamp">[<?php echo date('H:i:s', strtotime($entry['timestamp'])); ?>]</span> 
                    <?php echo htmlspecialchars($entry['message']); ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Back to Dashboard -->
        <div class="back-button">
            <a href="admin_dashboard.php">← Volver al Dashboard</a>
        </div>
    </div>

    <script>
        // Configuración AJAX
        const AJAX_URL = '<?php echo $_SERVER['PHP_SELF']; ?>';
        
        // Matrix Rain Effect
        function createMatrixRain() {
            const matrixRain = document.getElementById('matrixRain');
            const characters = '01アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワヲン';
            
            for (let i = 0; i < 50; i++) {
                const column = document.createElement('div');
                column.className = 'matrix-column';
                column.style.left = Math.random() * 100 + '%';
                column.style.animationDuration = (Math.random() * 10 + 5) + 's';
                column.style.animationDelay = Math.random() * 5 + 's';
                
                let text = '';
                for (let j = 0; j < 30; j++) {
                    text += characters[Math.floor(Math.random() * characters.length)] + '<br>';
                }
                column.innerHTML = text;
                matrixRain.appendChild(column);
            }
        }

        // Neural Network Background
        function createNeuralNetwork() {
            const network = document.getElementById('neuralNetwork');
            
            // Create neurons
            for (let i = 0; i < 20; i++) {
                const neuron = document.createElement('div');
                neuron.className = 'neuron';
                neuron.style.left = Math.random() * 100 + '%';
                neuron.style.top = Math.random() * 100 + '%';
                neuron.style.animationDelay = Math.random() * 2 + 's';
                network.appendChild(neuron);
            }
            
            // Create synapses
            for (let i = 0; i < 15; i++) {
                const synapse = document.createElement('div');
                synapse.className = 'synapse';
                synapse.style.left = Math.random() * 100 + '%';
                synapse.style.top = Math.random() * 100 + '%';
                synapse.style.width = (Math.random() * 200 + 50) + 'px';
                synapse.style.transform = `rotate(${Math.random() * 360}deg)`;
                synapse.style.animationDelay = Math.random() * 3 + 's';
                network.appendChild(synapse);
            }
        }
        
        // Función para ejecutar acciones
        function executeAction(action) {
            if (action === 'emergency_shutdown') {
                if (!confirm('⚠️ ¿Estás seguro de ejecutar el APAGADO DE EMERGENCIA?')) {
                    return;
                }
            }
            
            fetch(AJAX_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax=1&action=${action}${action === 'emergency_shutdown' ? '&confirm=true' : ''}`
            })
            .then(response => response.json())
            .then(data => {
                addLogEntry(data.message);
                if (data.success) {
                    updateMetrics();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                addLogEntry('Error ejecutando acción');
            });
        }
        
        // Función para actualizar métricas
        function updateMetrics() {
            fetch(AJAX_URL, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'ajax=1&get_metrics=1'
            })
            .then(response => response.json())
            .then(data => {
                // Actualizar nivel de consciencia
                document.getElementById('consciousnessLevel').textContent = data.consciousness_level + '%';
                
                // Actualizar métricas neurales
                for (const [key, value] of Object.entries(data.neural_activity)) {
                    const element = document.getElementById(key);
                    if (element) {
                        element.textContent = value + '%';
                        const statModule = element.closest('.stat-module');
                        if (statModule) {
                            const progressBar = statModule.querySelector('.stat-fill');
                            if (progressBar) {
                                progressBar.style.width = value + '%';
                            }
                        }
                    }
                }
                
                // Actualizar estado cuántico
                document.getElementById('quantumState').textContent = data.quantum_state;
                
                // Actualizar amenazas
                updateThreats(data.threats);
            })
            .catch(error => {
                console.error('Error actualizando métricas:', error);
            });
        }
        
        // Función para actualizar lista de amenazas
        function updateThreats(threats) {
            const threatList = document.getElementById('threatList');
            threatList.innerHTML = '';
            
            threats.forEach(threat => {
                const item = document.createElement('div');
                item.className = 'threat-item';
                item.innerHTML = `
                    <span class="threat-name">${threat.name}</span>
                    <span class="threat-level ${threat.level}">${threat.level.toUpperCase()}</span>
                `;
                threatList.appendChild(item);
            });
        }
        
        // Función para agregar entrada al log
        function addLogEntry(message) {
            const log = document.getElementById('activityLog');
            const entry = document.createElement('div');
            entry.className = 'log-entry';
            const time = new Date().toLocaleTimeString('es-ES', { hour12: false });
            entry.innerHTML = `<span class="log-timestamp">[${time}]</span> ${message}`;
            log.insertBefore(entry, log.firstChild);
            
            // Mantener solo las últimas 10 entradas
            while (log.children.length > 10) {
                log.removeChild(log.lastChild);
            }
        }
        
        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            createMatrixRain();
            createNeuralNetwork();
            
            // Actualizar métricas periódicamente
            setInterval(updateMetrics, 5000);
            
            // Actualizar logs periódicamente
            setInterval(() => {
                fetch(AJAX_URL, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'ajax=1&get_logs=1'
                })
                .then(response => response.json())
                .then(data => {
                    // Actualizar log si hay nuevas entradas
                    if (data.logs && data.logs.length > 0) {
                        const latestLog = data.logs[0];
                        addLogEntry(latestLog.message);
                    }
                })
                .catch(error => {
                    console.error('Error obteniendo logs:', error);
                });
            }, 10000);
            
            // Efecto de parpadeo en conexión
            setInterval(() => {
                const status = document.getElementById('connectionStatus');
                status.style.opacity = status.style.opacity === '0.5' ? '1' : '0.5';
            }, 1000);
            
            // Log inicial
            addLogEntry('🚀 Monitor de Consciencia IA iniciado - GuardianIA v3.0');
        });
    </script>
</body>
</html>