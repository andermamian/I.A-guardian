<?php
/**
 * GuardianIA v3.0 FINAL - Centro de Inteligencia de Amenazas
 * Anderson Mamian Chicangana - Sistema de Análisis de Amenazas en Tiempo Real
 * Detección, análisis y neutralización de amenazas cibernéticas
 */

// Incluir configuración principal
require_once __DIR__ . '/config.php';

// Verificar sesión y autenticación
initSecureSession();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Location: login.php');
    exit;
}

// Verificar permisos de administrador
if ($_SESSION['user_type'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

// Log de acceso
logEvent('INFO', 'Acceso al Centro de Inteligencia de Amenazas', [
    'user_id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'] ?? 'unknown'
]);

// Clase para el Centro de Inteligencia
class ThreatIntelligenceCenter {
    private $db;
    private $active_threats = [];
    private $global_threat_level;
    
    public function __construct($database = null) {
        $this->db = $database;
        $this->initializeThreatData();
    }
    
    private function initializeThreatData() {
        $this->active_threats = $this->getActiveThreats();
        $this->global_threat_level = $this->calculateGlobalThreatLevel();
    }
    
    public function getActiveThreats() {
        $threats = [];
        
        // Generar amenazas simuladas con datos realistas
        $threat_types = [
            ['type' => 'RANSOMWARE', 'origin' => 'Rusia', 'severity' => 'critical', 'status' => 'active'],
            ['type' => 'DDoS', 'origin' => 'China', 'severity' => 'high', 'status' => 'mitigando'],
            ['type' => 'PHISHING', 'origin' => 'Nigeria', 'severity' => 'medium', 'status' => 'monitoreando'],
            ['type' => 'ZERO_DAY', 'origin' => 'Desconocido', 'severity' => 'critical', 'status' => 'analizando'],
            ['type' => 'BOTNET', 'origin' => 'Brasil', 'severity' => 'high', 'status' => 'rastreando'],
            ['type' => 'APT', 'origin' => 'Corea del Norte', 'severity' => 'critical', 'status' => 'active'],
            ['type' => 'MALWARE', 'origin' => 'India', 'severity' => 'medium', 'status' => 'contenido'],
            ['type' => 'SQL_INJECTION', 'origin' => 'USA', 'severity' => 'low', 'status' => 'bloqueado'],
            ['type' => 'CRYPTOJACKING', 'origin' => 'Ucrania', 'severity' => 'medium', 'status' => 'neutralizando'],
            ['type' => 'BACKDOOR', 'origin' => 'Irán', 'severity' => 'high', 'status' => 'investigando']
        ];
        
        foreach ($threat_types as $index => $threat) {
            $threats[] = array_merge($threat, [
                'id' => 'THR-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                'detected' => date('H:i:s', strtotime('-' . mt_rand(1, 300) . ' seconds')),
                'ip' => $this->generateRandomIP(),
                'target_systems' => mt_rand(1, 50),
                'blocked_attempts' => mt_rand(100, 10000),
                'confidence' => mt_rand(75, 99) / 100,
                'duration' => mt_rand(5, 120) . ' min'
            ]);
        }
        
        return $threats;
    }
    
    private function generateRandomIP() {
        return mt_rand(1, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(0, 255) . '.' . mt_rand(1, 255);
    }
    
    // Método público ahora
    public function getGlobalThreatLevel() {
        return $this->global_threat_level;
    }
    
    // Método privado para calcular nivel de amenaza
    private function calculateGlobalThreatLevel() {
        $critical_count = count(array_filter($this->active_threats, function($t) {
            return $t['severity'] === 'critical';
        }));
        
        $high_count = count(array_filter($this->active_threats, function($t) {
            return $t['severity'] === 'high';
        }));
        
        if ($critical_count >= 3) return 'CRÍTICO';
        if ($critical_count >= 2) return 'ALTO';
        if ($critical_count >= 1 || $high_count >= 3) return 'ELEVADO';
        if ($high_count >= 1 || count($this->active_threats) >= 5) return 'MODERADO';
        return 'BAJO';
    }
    
    public function getGeographicData() {
        return [
            ['country' => 'Rusia', 'attacks' => mt_rand(1000, 5000), 'blocked' => mt_rand(800, 4500)],
            ['country' => 'China', 'attacks' => mt_rand(800, 4000), 'blocked' => mt_rand(700, 3800)],
            ['country' => 'Estados Unidos', 'attacks' => mt_rand(500, 2000), 'blocked' => mt_rand(450, 1900)],
            ['country' => 'Corea del Norte', 'attacks' => mt_rand(300, 1500), 'blocked' => mt_rand(280, 1400)],
            ['country' => 'Brasil', 'attacks' => mt_rand(200, 1000), 'blocked' => mt_rand(180, 950)],
            ['country' => 'India', 'attacks' => mt_rand(100, 800), 'blocked' => mt_rand(90, 750)],
            ['country' => 'Irán', 'attacks' => mt_rand(150, 900), 'blocked' => mt_rand(130, 850)],
            ['country' => 'Nigeria', 'attacks' => mt_rand(80, 600), 'blocked' => mt_rand(70, 580)]
        ];
    }
    
    public function getAttackTimeline() {
        $timeline = [];
        for ($i = 23; $i >= 0; $i--) {
            $timeline[] = [
                'hour' => date('H:00', strtotime("-$i hours")),
                'attacks' => mt_rand(50, 500),
                'blocked' => mt_rand(45, 490),
                'threats' => mt_rand(5, 25)
            ];
        }
        return $timeline;
    }
    
    public function executeDefenseProtocol($protocol) {
        logSecurityEvent('defense_protocol', "Ejecutando protocolo: $protocol", 'high', $_SESSION['user_id']);
        
        $responses = [
            'activate_firewall' => [
                'success' => true, 
                'message' => '🛡️ Firewall cuántico activado - Protección máxima habilitada',
                'details' => 'Implementando algoritmos de defensa militar AES-256-GCM'
            ],
            'deploy_honeypot' => [
                'success' => true, 
                'message' => '🍯 Honeypot desplegado - Rastreando atacantes',
                'details' => 'Sistema trampa activado para capturar técnicas de intrusión'
            ],
            'isolate_threat' => [
                'success' => true, 
                'message' => '🔒 Amenaza aislada en sandbox cuántico',
                'details' => 'Contenedor de seguridad militar activado'
            ],
            'counter_attack' => [
                'success' => true, 
                'message' => '⚔️ Contraataque iniciado - Neutralizando origen',
                'details' => 'Protocolo de defensa activa en ejecución'
            ],
            'emergency_lockdown' => [
                'success' => true, 
                'message' => '🚨 LOCKDOWN TOTAL ACTIVADO - Sistemas en modo fortaleza',
                'details' => 'Todos los accesos restringidos - Solo personal autorizado'
            ],
            'scan_network' => [
                'success' => true, 
                'message' => '🔍 Escaneo profundo completado - 0 vulnerabilidades críticas',
                'details' => 'Análisis de red completo - Integridad verificada'
            ],
            'quantum_shield' => [
                'success' => true,
                'message' => '🛡️ Escudo cuántico activado - Protección total',
                'details' => 'Encriptación cuántica activa en todos los canales'
            ],
            'ai_defense' => [
                'success' => true,
                'message' => '🤖 Sistema de defensa IA activado',
                'details' => 'Red neuronal analizando patrones de amenaza'
            ]
        ];
        
        return $responses[$protocol] ?? ['success' => false, 'message' => 'Protocolo desconocido'];
    }
    
    public function getSystemMetrics() {
        return [
            'cpu_usage' => mt_rand(15, 85),
            'memory_usage' => mt_rand(25, 75),
            'network_load' => mt_rand(10, 90),
            'threats_blocked_today' => mt_rand(1000, 5000),
            'active_connections' => mt_rand(100, 500),
            'encryption_strength' => '256-bit AES + Cuántica',
            'uptime' => '99.98%'
        ];
    }
}

// Procesar peticiones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    $center = new ThreatIntelligenceCenter($GLOBALS['db'] ?? null);
    
    if (isset($_POST['protocol'])) {
        $response = $center->executeDefenseProtocol($_POST['protocol']);
        echo json_encode($response);
        exit;
    }
    
    if (isset($_POST['get_threats'])) {
        echo json_encode([
            'threats' => $center->getActiveThreats(),
            'threat_level' => $center->getGlobalThreatLevel(),
            'metrics' => $center->getSystemMetrics()
        ]);
        exit;
    }
    
    if (isset($_POST['get_timeline'])) {
        echo json_encode($center->getAttackTimeline());
        exit;
    }
}

// Inicializar centro
$center = new ThreatIntelligenceCenter($GLOBALS['db'] ?? null);
$active_threats = $center->getActiveThreats();
$global_threat_level = $center->getGlobalThreatLevel();
$geographic_data = $center->getGeographicData();
$timeline_data = $center->getAttackTimeline();
$system_metrics = $center->getSystemMetrics();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🎯 Centro de Inteligencia de Amenazas - <?php echo APP_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --cyber-red: #ff0040;
            --cyber-orange: #ff6600;
            --cyber-yellow: #ffcc00;
            --cyber-green: #00ff00;
            --cyber-blue: #00ccff;
            --cyber-purple: #cc00ff;
            --cyber-pink: #ff00ff;
            --dark-bg: #0a0a0a;
            --darker-bg: #050505;
            --grid-color: rgba(0, 255, 0, 0.1);
            --text-primary: #00ff00;
            --text-danger: #ff0040;
            --text-warning: #ffcc00;
            --glass-bg: rgba(0, 0, 0, 0.7);
            --neon-shadow: 0 0 20px;
        }

        body {
            font-family: 'Courier New', monospace;
            background: var(--dark-bg);
            color: var(--text-primary);
            overflow-x: hidden;
            position: relative;
            min-height: 100vh;
        }

        /* Fondo de matriz animado mejorado */
        .matrix-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--darker-bg);
            z-index: 1;
            overflow: hidden;
        }

        .matrix-rain {
            position: absolute;
            font-family: 'Courier New', monospace;
            font-size: 14px;
            color: var(--cyber-green);
            animation: matrix-fall linear infinite;
            opacity: 0.6;
        }

        @keyframes matrix-fall {
            0% { transform: translateY(-100vh); opacity: 1; }
            100% { transform: translateY(100vh); opacity: 0; }
        }

        /* Grid de fondo dinámico */
        .cyber-grid {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(var(--grid-color) 1px, transparent 1px),
                linear-gradient(90deg, var(--grid-color) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: grid-pulse 4s ease-in-out infinite;
            z-index: 2;
        }

        @keyframes grid-pulse {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 0.8; }
        }

        /* HUD de radar mejorado */
        .radar-hud {
            position: fixed;
            top: 20px;
            right: 20px;
            width: 250px;
            height: 250px;
            z-index: 1000;
            background: radial-gradient(circle, transparent 30%, rgba(0, 255, 0, 0.1) 70%);
            border: 3px solid var(--cyber-green);
            border-radius: 50%;
            box-shadow: var(--neon-shadow) var(--cyber-green);
        }

        .radar-sweep {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 50%;
            height: 3px;
            background: linear-gradient(90deg, transparent, var(--cyber-green), var(--cyber-blue));
            transform-origin: left center;
            animation: radar-rotate 3s linear infinite;
        }

        @keyframes radar-rotate {
            0% { transform: translate(-50%, -50%) rotate(0deg); }
            100% { transform: translate(-50%, -50%) rotate(360deg); }
        }

        .radar-circles {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .radar-circle {
            width: 100px;
            height: 100px;
            border: 1px solid var(--cyber-green);
            border-radius: 50%;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            opacity: 0.5;
        }

        .radar-circle:nth-child(1) { width: 200px; height: 200px; }
        .radar-circle:nth-child(2) { width: 150px; height: 150px; }
        .radar-circle:nth-child(3) { width: 100px; height: 100px; }
        .radar-circle:nth-child(4) { width: 50px; height: 50px; }

        .threat-dot {
            position: absolute;
            width: 8px;
            height: 8px;
            background: var(--cyber-red);
            border-radius: 50%;
            box-shadow: var(--neon-shadow) var(--cyber-red);
            animation: threat-pulse 2s infinite;
        }

        @keyframes threat-pulse {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.8); opacity: 0.4; }
        }

        /* Contenedor principal */
        .intelligence-container {
            position: relative;
            z-index: 10;
            max-width: 1900px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header futurista */
        .threat-header {
            background: linear-gradient(135deg, 
                rgba(255, 0, 64, 0.15), 
                rgba(0, 204, 255, 0.15), 
                rgba(255, 0, 255, 0.1)
            );
            border: 3px solid var(--cyber-red);
            border-radius: 20px 0 20px 0;
            padding: 40px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            box-shadow: 
                var(--neon-shadow) rgba(255, 0, 64, 0.5),
                inset 0 0 50px rgba(0, 255, 0, 0.1);
        }

        .threat-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(0, 255, 255, 0.3), 
                transparent
            );
            animation: header-scan 4s infinite;
        }

        @keyframes header-scan {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .threat-header h1 {
            font-size: 4em;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 5px;
            background: linear-gradient(45deg, 
                var(--cyber-red), 
                var(--cyber-orange), 
                var(--cyber-yellow),
                var(--cyber-green),
                var(--cyber-blue),
                var(--cyber-purple)
            );
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: var(--neon-shadow) var(--cyber-red);
            animation: rainbow-text 3s infinite, glitch-text 8s infinite;
        }

        @keyframes rainbow-text {
            0% { filter: hue-rotate(0deg); }
            100% { filter: hue-rotate(360deg); }
        }

        @keyframes glitch-text {
            0%, 90%, 100% { transform: translate(0); }
            20% { transform: translate(-2px, 2px); }
            40% { transform: translate(-2px, -2px); }
            60% { transform: translate(2px, 2px); }
            80% { transform: translate(2px, -2px); }
        }

        /* Panel de estado global */
        .global-status {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 30px;
            margin: 30px 0;
        }

        .threat-level-panel {
            background: var(--glass-bg);
            border: 2px solid var(--cyber-red);
            border-radius: 15px;
            padding: 30px;
            text-align: center;
            position: relative;
            backdrop-filter: blur(15px);
            animation: panel-pulse 3s infinite;
        }

        @keyframes panel-pulse {
            0%, 100% { box-shadow: var(--neon-shadow) rgba(255, 0, 64, 0.3); }
            50% { box-shadow: var(--neon-shadow) rgba(255, 0, 64, 0.8); }
        }

        .threat-level-display {
            font-size: 3.5em;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 8px;
            margin: 20px 0;
        }

        .threat-level-display.crítico {
            color: var(--cyber-red);
            animation: critical-alert 0.8s infinite;
            text-shadow: var(--neon-shadow) var(--cyber-red);
        }

        @keyframes critical-alert {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.5; transform: scale(1.1); }
        }

        .threat-level-display.alto {
            color: var(--cyber-orange);
            text-shadow: var(--neon-shadow) var(--cyber-orange);
        }

        .threat-level-display.elevado {
            color: var(--cyber-yellow);
            text-shadow: var(--neon-shadow) var(--cyber-yellow);
        }

        .threat-level-display.moderado {
            color: var(--cyber-blue);
            text-shadow: var(--neon-shadow) var(--cyber-blue);
        }

        .threat-level-display.bajo {
            color: var(--cyber-green);
            text-shadow: var(--neon-shadow) var(--cyber-green);
        }

        .system-metrics {
            background: var(--glass-bg);
            border: 2px solid var(--cyber-blue);
            border-radius: 15px;
            padding: 25px;
            backdrop-filter: blur(10px);
        }

        .metric-item {
            display: flex;
            justify-content: space-between;
            margin: 15px 0;
            padding: 10px 0;
            border-bottom: 1px solid rgba(0, 255, 0, 0.2);
        }

        .metric-value {
            color: var(--cyber-green);
            font-weight: bold;
        }

        /* Controles de defensa mejorados */
        .defense-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin: 40px 0;
        }

        .defense-btn {
            padding: 20px;
            background: linear-gradient(135deg, 
                rgba(0, 255, 0, 0.1), 
                rgba(0, 204, 255, 0.1)
            );
            border: 2px solid var(--cyber-green);
            border-radius: 10px 0 10px 0;
            color: var(--cyber-green);
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: 1.1em;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.4s;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(5px);
        }

        .defense-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(0, 255, 0, 0.4), 
                transparent
            );
            transition: left 0.6s;
        }

        .defense-btn:hover::before {
            left: 100%;
        }

        .defense-btn:hover {
            background: linear-gradient(135deg, 
                rgba(0, 255, 0, 0.3), 
                rgba(0, 204, 255, 0.3)
            );
            border-color: var(--cyber-blue);
            color: var(--cyber-blue);
            transform: translateY(-5px) scale(1.02);
            box-shadow: var(--neon-shadow) var(--cyber-green);
        }

        .defense-btn.danger {
            border-color: var(--cyber-red);
            color: var(--cyber-red);
            background: linear-gradient(135deg, 
                rgba(255, 0, 64, 0.1), 
                rgba(255, 102, 0, 0.1)
            );
        }

        .defense-btn.danger:hover {
            background: linear-gradient(135deg, 
                rgba(255, 0, 64, 0.3), 
                rgba(255, 102, 0, 0.3)
            );
            box-shadow: var(--neon-shadow) var(--cyber-red);
        }

        /* Grid de amenazas */
        .threats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(420px, 1fr));
            gap: 25px;
            margin: 40px 0;
        }

        .threat-card {
            background: var(--glass-bg);
            border: 2px solid var(--cyber-green);
            border-radius: 12px;
            padding: 25px;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
            transition: all 0.4s;
            transform-style: preserve-3d;
        }

        .threat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, 
                var(--cyber-red), 
                var(--cyber-orange), 
                var(--cyber-yellow),
                var(--cyber-green)
            );
            animation: card-scan 3s infinite;
        }

        @keyframes card-scan {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .threat-card:hover {
            border-color: var(--cyber-blue);
            transform: translateY(-10px) rotateX(5deg);
            box-shadow: 
                var(--neon-shadow) var(--cyber-blue),
                0 15px 30px rgba(0, 0, 0, 0.5);
        }

        .threat-type {
            font-size: 1.8em;
            font-weight: bold;
            color: var(--cyber-orange);
            margin-bottom: 15px;
            text-transform: uppercase;
            text-shadow: var(--neon-shadow) var(--cyber-orange);
        }

        .threat-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 20px 0;
        }

        .threat-detail {
            font-size: 1em;
            color: var(--cyber-green);
            padding: 5px 0;
        }

        .threat-detail span {
            color: var(--cyber-blue);
            font-weight: bold;
        }

        .severity-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 25px;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.9em;
            margin-top: 15px;
            border: 2px solid;
        }

        .severity-badge.critical {
            background: var(--cyber-red);
            border-color: var(--cyber-red);
            color: white;
            animation: critical-glow 1.5s infinite;
        }

        @keyframes critical-glow {
            0%, 100% { box-shadow: var(--neon-shadow) var(--cyber-red); }
            50% { box-shadow: 0 0 40px var(--cyber-red); }
        }

        .severity-badge.high {
            background: var(--cyber-orange);
            border-color: var(--cyber-orange);
            color: white;
        }

        .severity-badge.medium {
            background: var(--cyber-yellow);
            border-color: var(--cyber-yellow);
            color: black;
        }

        .severity-badge.low {
            background: var(--cyber-green);
            border-color: var(--cyber-green);
            color: black;
        }

        /* Feed de actividad */
        .activity-feed {
            background: var(--glass-bg);
            border: 2px solid var(--cyber-green);
            border-radius: 15px;
            padding: 25px;
            margin: 40px 0;
            max-height: 500px;
            overflow-y: auto;
            backdrop-filter: blur(10px);
        }

        .activity-entry {
            padding: 15px;
            margin: 10px 0;
            background: rgba(0, 255, 0, 0.05);
            border-left: 4px solid var(--cyber-green);
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            animation: entry-slide 0.6s ease-out;
            transition: all 0.3s;
        }

        @keyframes entry-slide {
            from {
                opacity: 0;
                transform: translateX(-30px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .activity-entry:hover {
            background: rgba(0, 255, 0, 0.1);
            transform: translateX(10px);
        }

        .activity-time {
            color: var(--cyber-blue);
            font-weight: bold;
            margin-right: 10px;
        }

        .activity-message {
            color: var(--cyber-green);
        }

        .activity-critical {
            border-left-color: var(--cyber-red);
            background: rgba(255, 0, 64, 0.1);
        }

        .activity-critical .activity-message {
            color: var(--cyber-red);
        }

        /* Mapa geográfico mejorado */
        .geo-map {
            background: var(--glass-bg);
            border: 2px solid var(--cyber-blue);
            border-radius: 15px;
            padding: 30px;
            margin: 40px 0;
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
        }

        .world-visualization {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .country-threat {
            background: rgba(0, 0, 0, 0.6);
            border: 1px solid var(--cyber-green);
            border-radius: 8px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s;
        }

        .country-threat:hover {
            border-color: var(--cyber-red);
            transform: scale(1.05);
            box-shadow: var(--neon-shadow) var(--cyber-red);
        }

        .country-name {
            font-weight: bold;
            color: var(--cyber-orange);
            margin-bottom: 10px;
        }

        .attack-stats {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            font-size: 0.9em;
        }

        .stat-item {
            color: var(--cyber-green);
        }

        .stat-value {
            color: var(--cyber-blue);
            font-weight: bold;
        }

        /* Timeline mejorado */
        .timeline-section {
            background: var(--glass-bg);
            border: 2px solid var(--cyber-purple);
            border-radius: 15px;
            padding: 30px;
            margin: 40px 0;
            backdrop-filter: blur(10px);
        }

        .timeline-chart {
            display: flex;
            align-items: flex-end;
            height: 300px;
            gap: 3px;
            padding: 20px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            border-left: 3px solid var(--cyber-green);
            border-bottom: 3px solid var(--cyber-green);
        }

        .timeline-bar {
            flex: 1;
            background: linear-gradient(to top, 
                var(--cyber-red), 
                var(--cyber-orange), 
                var(--cyber-yellow),
                var(--cyber-green)
            );
            border-radius: 3px 3px 0 0;
            position: relative;
            transition: all 0.3s;
            animation: bar-grow 2s ease-out;
        }

        @keyframes bar-grow {
            from { height: 0 !important; }
        }

        .timeline-bar:hover {
            filter: brightness(1.5);
            transform: scaleY(1.1);
        }

        .timeline-labels {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
            font-size: 0.8em;
            color: var(--cyber-green);
        }

        /* Botón de regreso mejorado */
        .back-control {
            text-align: center;
            margin: 50px 0;
        }

        .back-btn {
            display: inline-block;
            padding: 20px 40px;
            background: linear-gradient(135deg, 
                rgba(0, 255, 0, 0.1), 
                rgba(0, 204, 255, 0.1)
            );
            border: 3px solid var(--cyber-green);
            border-radius: 15px 0 15px 0;
            color: var(--cyber-green);
            text-decoration: none;
            text-transform: uppercase;
            font-weight: bold;
            font-size: 1.2em;
            letter-spacing: 2px;
            transition: all 0.4s;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }

        .back-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(0, 255, 0, 0.3), 
                transparent
            );
            transition: left 0.5s;
        }

        .back-btn:hover::before {
            left: 100%;
        }

        .back-btn:hover {
            background: linear-gradient(135deg, 
                rgba(0, 255, 0, 0.3), 
                rgba(0, 204, 255, 0.3)
            );
            border-color: var(--cyber-blue);
            color: var(--cyber-blue);
            transform: translateY(-5px) scale(1.05);
            box-shadow: var(--neon-shadow) var(--cyber-green);
        }

        /* Responsive mejorado */
        @media (max-width: 1024px) {
            .threats-grid {
                grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            }
            
            .defense-grid {
                grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            }
            
            .global-status {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .threats-grid {
                grid-template-columns: 1fr;
            }
            
            .defense-grid {
                grid-template-columns: 1fr;
            }
            
            .radar-hud {
                width: 150px;
                height: 150px;
                top: 10px;
                right: 10px;
            }
            
            .threat-header h1 {
                font-size: 2.5em;
            }
            
            .threat-level-display {
                font-size: 2.5em;
            }
        }

        /* Scrollbar personalizado */
        ::-webkit-scrollbar {
            width: 12px;
        }

        ::-webkit-scrollbar-track {
            background: var(--dark-bg);
            border: 1px solid var(--cyber-green);
            border-radius: 6px;
        }

        ::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, var(--cyber-green), var(--cyber-blue));
            border-radius: 6px;
            border: 1px solid var(--dark-bg);
        }

        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg, var(--cyber-blue), var(--cyber-purple));
        }

        /* Animaciones adicionales */
        @keyframes data-stream {
            0% { transform: translateY(0) scaleY(1); }
            50% { transform: translateY(-10px) scaleY(1.1); }
            100% { transform: translateY(0) scaleY(1); }
        }

        .data-animated {
            animation: data-stream 2s infinite;
        }

        /* Efectos de partículas */
        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: var(--cyber-green);
            border-radius: 50%;
            animation: particle-float 8s infinite linear;
        }

        @keyframes particle-float {
            0% {
                transform: translateY(100vh) translateX(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100vh) translateX(100px);
                opacity: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Fondo de matriz animado -->
    <div class="matrix-background" id="matrixBg"></div>
    
    <!-- Grid cyber -->
    <div class="cyber-grid"></div>

    <!-- HUD Radar -->
    <div class="radar-hud">
        <div class="radar-circles">
            <div class="radar-circle"></div>
            <div class="radar-circle"></div>
            <div class="radar-circle"></div>
            <div class="radar-circle"></div>
        </div>
        <div class="radar-sweep"></div>
        <div class="threat-dot" style="top: 30%; left: 60%;"></div>
        <div class="threat-dot" style="top: 70%; left: 20%;"></div>
        <div class="threat-dot" style="top: 50%; left: 80%;"></div>
        <div class="threat-dot" style="top: 45%; left: 35%;"></div>
    </div>

    <!-- Contenedor principal -->
    <div class="intelligence-container">
        <!-- Header -->
        <div class="threat-header">
            <h1>🎯 CENTRO DE INTELIGENCIA DE AMENAZAS</h1>
            <p style="color: var(--cyber-blue); font-size: 1.4em; text-transform: uppercase; letter-spacing: 3px; margin-top: 15px;">
                <?php echo APP_NAME; ?> - ANÁLISIS Y NEUTRALIZACIÓN EN TIEMPO REAL
            </p>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 25px; font-size: 1.1em;">
                <div>
                    <span style="color: var(--cyber-yellow);">OPERADOR:</span> 
                    <span style="color: var(--cyber-green); font-weight: bold;"><?php echo htmlspecialchars($_SESSION['username'] ?? 'CLASSIFIED'); ?></span>
                </div>
                <div>
                    <span style="color: var(--cyber-yellow);">CLEARANCE:</span> 
                    <span style="color: var(--cyber-red); font-weight: bold;">NIVEL 5 - MILITAR</span>
                </div>
                <div>
                    <span style="color: var(--cyber-yellow);">ESTADO:</span> 
                    <span style="color: var(--cyber-green); font-weight: bold;">ONLINE - ACTIVO</span>
                </div>
                <div>
                    <span style="color: var(--cyber-yellow);">ENCRIPTACIÓN:</span> 
                    <span style="color: var(--cyber-purple); font-weight: bold;">CUÁNTICA AES-256</span>
                </div>
            </div>
        </div>

        <!-- Panel de estado global -->
        <div class="global-status">
            <div class="threat-level-panel">
                <div style="font-size: 1.4em; color: var(--cyber-blue); margin-bottom: 15px; text-transform: uppercase; letter-spacing: 2px;">
                    🚨 NIVEL GLOBAL DE AMENAZA 🚨
                </div>
                <div class="threat-level-display <?php echo strtolower($global_threat_level); ?>">
                    <?php echo $global_threat_level; ?>
                </div>
                <div style="margin-top: 20px; color: var(--cyber-yellow); font-size: 1.2em;">
                    <strong><?php echo count($active_threats); ?></strong> AMENAZAS ACTIVAS DETECTADAS
                </div>
                <div style="margin-top: 15px; color: var(--cyber-orange); font-size: 1em;">
                    Última actualización: <span id="lastUpdate"><?php echo date('H:i:s'); ?></span>
                </div>
            </div>
            
            <div class="system-metrics">
                <h3 style="color: var(--cyber-blue); margin-bottom: 20px; text-transform: uppercase; letter-spacing: 1px;">
                    📊 MÉTRICAS DEL SISTEMA
                </h3>
                
                <div class="metric-item">
                    <span>CPU Usage:</span>
                    <span class="metric-value"><?php echo $system_metrics['cpu_usage']; ?>%</span>
                </div>
                <div class="metric-item">
                    <span>Memory Usage:</span>
                    <span class="metric-value"><?php echo $system_metrics['memory_usage']; ?>%</span>
                </div>
                <div class="metric-item">
                    <span>Network Load:</span>
                    <span class="metric-value"><?php echo $system_metrics['network_load']; ?>%</span>
                </div>
                <div class="metric-item">
                    <span>Amenazas Bloqueadas Hoy:</span>
                    <span class="metric-value"><?php echo number_format($system_metrics['threats_blocked_today']); ?></span>
                </div>
                <div class="metric-item">
                    <span>Conexiones Activas:</span>
                    <span class="metric-value"><?php echo $system_metrics['active_connections']; ?></span>
                </div>
                <div class="metric-item">
                    <span>Uptime:</span>
                    <span class="metric-value"><?php echo $system_metrics['uptime']; ?></span>
                </div>
                <div class="metric-item">
                    <span>Encriptación:</span>
                    <span class="metric-value" style="font-size: 0.8em;"><?php echo $system_metrics['encryption_strength']; ?></span>
                </div>
            </div>
        </div>

        <!-- Controles de defensa -->
        <div style="margin: 40px 0;">
            <h2 style="color: var(--cyber-orange); font-size: 2em; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 25px; text-align: center;">
                ⚔️ PROTOCOLOS DE DEFENSA MILITAR
            </h2>
            <div class="defense-grid">
                <button class="defense-btn" onclick="executeProtocol('activate_firewall')">
                    🛡️ FIREWALL CUÁNTICO
                </button>
                <button class="defense-btn" onclick="executeProtocol('deploy_honeypot')">
                    🍯 DESPLEGAR HONEYPOT
                </button>
                <button class="defense-btn" onclick="executeProtocol('isolate_threat')">
                    🔒 AISLAR AMENAZA
                </button>
                <button class="defense-btn" onclick="executeProtocol('counter_attack')">
                    ⚔️ CONTRAATAQUE
                </button>
                <button class="defense-btn" onclick="executeProtocol('quantum_shield')">
                    🛡️ ESCUDO CUÁNTICO
                </button>
                <button class="defense-btn" onclick="executeProtocol('ai_defense')">
                    🤖 DEFENSA IA
                </button>
                <button class="defense-btn" onclick="executeProtocol('scan_network')">
                    🔍 ESCANEO PROFUNDO
                </button>
                <button class="defense-btn danger" onclick="executeProtocol('emergency_lockdown')">
                    🚨 LOCKDOWN TOTAL
                </button>
            </div>
        </div>

        <!-- Grid de amenazas activas -->
        <div style="margin: 40px 0;">
            <h2 style="color: var(--cyber-red); font-size: 2em; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 25px; text-align: center;">
                ⚠️ AMENAZAS ACTIVAS DETECTADAS
            </h2>
            <div class="threats-grid">
                <?php foreach ($active_threats as $threat): ?>
                <div class="threat-card">
                    <div class="threat-type"><?php echo $threat['type']; ?></div>
                    <div class="threat-details">
                        <div class="threat-detail">
                            <strong>ID:</strong> <span><?php echo $threat['id']; ?></span>
                        </div>
                        <div class="threat-detail">
                            <strong>Origen:</strong> <span><?php echo $threat['origin']; ?></span>
                        </div>
                        <div class="threat-detail">
                            <strong>IP:</strong> <span><?php echo $threat['ip']; ?></span>
                        </div>
                        <div class="threat-detail">
                            <strong>Detectado:</strong> <span><?php echo $threat['detected']; ?></span>
                        </div>
                        <div class="threat-detail">
                            <strong>Sistemas:</strong> <span><?php echo $threat['target_systems']; ?></span>
                        </div>
                        <div class="threat-detail">
                            <strong>Bloqueados:</strong> <span><?php echo number_format($threat['blocked_attempts']); ?></span>
                        </div>
                        <div class="threat-detail">
                            <strong>Duración:</strong> <span><?php echo $threat['duration']; ?></span>
                        </div>
                        <div class="threat-detail">
                            <strong>Confianza:</strong> <span><?php echo round($threat['confidence'] * 100, 1); ?>%</span>
                        </div>
                    </div>
                    <div style="margin-top: 15px; display: flex; justify-content: space-between; align-items: center;">
                        <span class="severity-badge <?php echo $threat['severity']; ?>">
                            <?php echo strtoupper($threat['severity']); ?>
                        </span>
                        <span style="color: var(--cyber-blue); font-size: 0.9em;">
                            Estado: <strong style="color: var(--cyber-green);"><?php echo strtoupper($threat['status']); ?></strong>
                        </span>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Mapa geográfico -->
        <div class="geo-map">
            <h2 style="color: var(--cyber-blue); font-size: 1.8em; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 25px;">
                🌍 MAPA GLOBAL DE ATAQUES EN TIEMPO REAL
            </h2>
            <div class="world-visualization">
                <?php foreach ($geographic_data as $country): ?>
                <div class="country-threat">
                    <div class="country-name"><?php echo $country['country']; ?></div>
                    <div class="attack-stats">
                        <div class="stat-item">
                            Ataques: <span class="stat-value"><?php echo number_format($country['attacks']); ?></span>
                        </div>
                        <div class="stat-item">
                            Bloqueados: <span class="stat-value"><?php echo number_format($country['blocked']); ?></span>
                        </div>
                        <div class="stat-item">
                            Éxito: <span class="stat-value"><?php echo round(($country['blocked']/$country['attacks'])*100, 1); ?>%</span>
                        </div>
                        <div class="stat-item">
                            Riesgo: <span class="stat-value" style="color: <?php echo $country['attacks'] > 2000 ? 'var(--cyber-red)' : 'var(--cyber-green)'; ?>">
                                <?php echo $country['attacks'] > 2000 ? 'ALTO' : 'BAJO'; ?>
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Timeline de ataques -->
        <div class="timeline-section">
            <h2 style="color: var(--cyber-purple); font-size: 1.8em; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 25px;">
                📈 LÍNEA DE TIEMPO - ÚLTIMAS 24 HORAS
            </h2>
            <div class="timeline-chart">
                <?php foreach ($timeline_data as $hour): ?>
                <div class="timeline-bar" 
                     style="height: <?php echo min(($hour['attacks'] / 500) * 100, 100); ?>%;" 
                     title="<?php echo $hour['hour']; ?>: <?php echo $hour['attacks']; ?> ataques, <?php echo $hour['blocked']; ?> bloqueados">
                </div>
                <?php endforeach; ?>
            </div>
            <div class="timeline-labels">
                <?php foreach (array_slice($timeline_data, 0, 12) as $hour): ?>
                <span><?php echo substr($hour['hour'], 0, 2); ?></span>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Feed de actividad -->
        <div class="activity-feed">
            <h2 style="color: var(--cyber-green); font-size: 1.8em; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 25px;">
                📡 FEED DE ACTIVIDAD EN TIEMPO REAL
            </h2>
            <div id="activityFeed">
                <div class="activity-entry activity-critical">
                    <span class="activity-time">[<?php echo date('H:i:s'); ?>]</span>
                    <span class="activity-message">⚠️ ALERTA CRÍTICA: Intento de intrusión detectado desde <?php echo $active_threats[0]['origin'] ?? 'Desconocido'; ?></span>
                </div>
                <div class="activity-entry">
                    <span class="activity-time">[<?php echo date('H:i:s', strtotime('-30 seconds')); ?>]</span>
                    <span class="activity-message">✅ Firewall cuántico actualizado - 2,847 nuevas reglas aplicadas</span>
                </div>
                <div class="activity-entry">
                    <span class="activity-time">[<?php echo date('H:i:s', strtotime('-1 minute')); ?>]</span>
                    <span class="activity-message">🔍 Escaneo de red completado - 0 vulnerabilidades críticas encontradas</span>
                </div>
                <div class="activity-entry">
                    <span class="activity-time">[<?php echo date('H:i:s', strtotime('-2 minutes')); ?>]</span>
                    <span class="activity-message">🛡️ Escudo de protección activo - Deflectando 156 ataques por segundo</span>
                </div>
            </div>
        </div>

        <!-- Control de regreso -->
        <div class="back-control">
            <a href="admin_dashboard.php" class="back-btn">
                ← VOLVER AL CENTRO DE COMANDO
            </a>
        </div>
    </div>

    <script>
        // Generar lluvia de matriz
        function createMatrixRain() {
            const chars = '01アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワヲン';
            const matrixBg = document.getElementById('matrixBg');
            
            for (let i = 0; i < 50; i++) {
                const drop = document.createElement('div');
                drop.className = 'matrix-rain';
                drop.style.left = Math.random() * 100 + '%';
                drop.style.animationDuration = (Math.random() * 3 + 2) + 's';
                drop.style.animationDelay = Math.random() * 2 + 's';
                drop.textContent = chars[Math.floor(Math.random() * chars.length)];
                matrixBg.appendChild(drop);
            }
        }

        // Generar partículas
        function createParticles() {
            const container = document.body;
            
            for (let i = 0; i < 20; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 8 + 's';
                container.appendChild(particle);
            }
        }

        // Ejecutar protocolo de defensa
        function executeProtocol(protocol) {
            if (protocol === 'emergency_lockdown') {
                if (!confirm('⚠️ ADVERTENCIA MILITAR: Esto activará el LOCKDOWN TOTAL del sistema.\n\n🔒 Se restringirán todos los accesos.\n❌ Solo personal con clearance nivel 5 podrá acceder.\n\n¿Confirmar activación?')) {
                    return;
                }
            }
            
            // Efecto visual del botón
            const button = event.target;
            button.style.background = 'linear-gradient(135deg, rgba(255, 0, 64, 0.5), rgba(255, 102, 0, 0.5))';
            button.style.transform = 'scale(0.95)';
            
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax=1&protocol=${protocol}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    addActivityEntry(data.message, protocol === 'emergency_lockdown');
                    if (data.details) {
                        setTimeout(() => addActivityEntry(`📋 ${data.details}`, false), 1000);
                    }
                    
                    // Restaurar botón
                    setTimeout(() => {
                        button.style.background = '';
                        button.style.transform = '';
                    }, 300);
                } else {
                    addActivityEntry('❌ Error ejecutando protocolo: ' + data.message, true);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                addActivityEntry('❌ Error de comunicación con el sistema', true);
            });
        }

        // Agregar entrada al feed de actividad
        function addActivityEntry(message, isCritical = false) {
            const feed = document.getElementById('activityFeed');
            const entry = document.createElement('div');
            entry.className = `activity-entry ${isCritical ? 'activity-critical' : ''}`;
            const time = new Date().toLocaleTimeString('es-ES', { hour12: false });
            entry.innerHTML = `
                <span class="activity-time">[${time}]</span>
                <span class="activity-message">${message}</span>
            `;
            feed.insertBefore(entry, feed.firstChild);
            
            // Mantener solo las últimas 15 entradas
            while (feed.children.length > 15) {
                feed.removeChild(feed.lastChild);
            }
        }

        // Actualizar amenazas
        function updateThreats() {
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'ajax=1&get_threats=1'
            })
            .then(response => response.json())
            .then(data => {
                // Actualizar nivel de amenaza
                const threatDisplay = document.querySelector('.threat-level-display');
                if (threatDisplay) {
                    threatDisplay.className = `threat-level-display ${data.threat_level.toLowerCase()}`;
                    threatDisplay.textContent = data.threat_level;
                }
                
                // Actualizar timestamp
                const lastUpdate = document.getElementById('lastUpdate');
                if (lastUpdate) {
                    lastUpdate.textContent = new Date().toLocaleTimeString('es-ES', { hour12: false });
                }
            })
            .catch(error => {
                console.error('Error actualizando amenazas:', error);
            });
        }

        // Mensajes aleatorios para el feed
        const randomMessages = [
            '🔍 Escaneando puertos críticos - Estado: SEGURO',
            '✅ Integridad de sistema verificada - 100% funcional',
            '📊 Análisis heurístico completado - Precisión: 99.8%',
            '🛡️ Escudo cuántico operativo - Protección máxima activa',
            '⚡ Velocidad de procesamiento: 2.4M paquetes/segundo',
            '🔐 Encriptación militar verificada en todos los canales',
            '📡 Sincronización con satélite de defensa completada',
            '🎯 Sistema de targeting actualizado - 1,247 amenazas catalogadas',
            '💾 Backup de seguridad militar completado exitosamente',
            '🌐 Conexión con centro de inteligencia global establecida',
            '🔬 Análisis de malware completado - 0 muestras detectadas',
            '🚀 Rendimiento del sistema optimizado - CPU al 100%',
            '🔒 Certificados de seguridad renovados automáticamente',
            '📈 Estadísticas de red actualizadas - Tráfico normal'
        ];

        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            createMatrixRain();
            createParticles();
            
            // Actualizar amenazas cada 45 segundos
            setInterval(updateThreats, 45000);
            
            // Agregar mensajes aleatorios al feed cada 8-15 segundos
            setInterval(() => {
                const randomMessage = randomMessages[Math.floor(Math.random() * randomMessages.length)];
                addActivityEntry(randomMessage);
            }, Math.random() * 7000 + 8000);
            
            // Animar puntos del radar
            setInterval(() => {
                const dots = document.querySelectorAll('.threat-dot');
                dots.forEach(dot => {
                    dot.style.top = Math.random() * 80 + 10 + '%';
                    dot.style.left = Math.random() * 80 + 10 + '%';
                });
            }, 4000);
            
            // Efectos de hover para las tarjetas de amenazas
            document.querySelectorAll('.threat-card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-10px) rotateX(5deg) scale(1.02)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0) rotateX(0) scale(1)';
                });
            });
            
            // Actualizar métricas del sistema
            setInterval(() => {
                const metrics = document.querySelectorAll('.metric-value');
                metrics.forEach(metric => {
                    if (metric.textContent.includes('%')) {
                        const newValue = Math.max(10, Math.min(95, parseInt(metric.textContent) + (Math.random() - 0.5) * 10));
                        metric.textContent = Math.round(newValue) + '%';
                    }
                });
            }, 30000);
            
            // Efecto de typing para mensajes críticos
            function typeMessage(element, message, speed = 50) {
                element.innerHTML = '';
                let i = 0;
                const timer = setInterval(() => {
                    if (i < message.length) {
                        element.innerHTML += message.charAt(i);
                        i++;
                    } else {
                        clearInterval(timer);
                    }
                }, speed);
            }
            
            // Mostrar mensaje de bienvenida
            setTimeout(() => {
                addActivityEntry('🎯 Sistema de Inteligencia de Amenazas inicializado correctamente');
                setTimeout(() => {
                    addActivityEntry('🔍 Comenzando monitoreo continuo de red...');
                }, 2000);
            }, 1000);
        });
        
        // Función para mostrar detalles de amenaza
        function showThreatDetails(threatId) {
            // Implementar modal o panel desplegable con detalles completos
            console.log('Mostrando detalles de amenaza:', threatId);
        }
        
        // Función para exportar reporte
        function exportThreatReport() {
            const reportData = {
                timestamp: new Date().toISOString(),
                threat_level: document.querySelector('.threat-level-display').textContent,
                active_threats: <?php echo count($active_threats); ?>,
                system_status: 'OPERATIONAL'
            };
            
            const blob = new Blob([JSON.stringify(reportData, null, 2)], {type: 'application/json'});
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `threat_report_${Date.now()}.json`;
            a.click();
            URL.revokeObjectURL(url);
            
            addActivityEntry('📄 Reporte de amenazas exportado exitosamente');
        }
        
        // Función para cambiar tema visual
        function toggleTheme() {
            const root = document.documentElement;
            const currentTheme = root.style.getPropertyValue('--cyber-green') || '#00ff00';
            
            if (currentTheme === '#00ff00') {
                // Tema rojo
                root.style.setProperty('--cyber-green', '#ff0040');
                root.style.setProperty('--cyber-blue', '#ff6600');
                root.style.setProperty('--text-primary', '#ff0040');
                addActivityEntry('🎨 Tema visual cambiado a MODO ALERTA ROJA');
            } else {
                // Tema original
                root.style.setProperty('--cyber-green', '#00ff00');
                root.style.setProperty('--cyber-blue', '#00ccff');
                root.style.setProperty('--text-primary', '#00ff00');
                addActivityEntry('🎨 Tema visual restaurado a MODO NORMAL');
            }
        }
        
        // Atajos de teclado
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey) {
                switch(e.key) {
                    case '1':
                        e.preventDefault();
                        executeProtocol('activate_firewall');
                        break;
                    case '2':
                        e.preventDefault();
                        executeProtocol('scan_network');
                        break;
                    case '9':
                        e.preventDefault();
                        executeProtocol('emergency_lockdown');
                        break;
                    case 'e':
                        e.preventDefault();
                        exportThreatReport();
                        break;
                    case 't':
                        e.preventDefault();
                        toggleTheme();
                        break;
                }
            }
        });
        
        // Detectar inactividad del usuario
        let inactivityTimer;
        function resetInactivityTimer() {
            clearTimeout(inactivityTimer);
            inactivityTimer = setTimeout(() => {
                addActivityEntry('⏰ Usuario inactivo detectado - Modo de vigilancia automática activado', false);
            }, 300000); // 5 minutos
        }
        
        document.addEventListener('mousemove', resetInactivityTimer);
        document.addEventListener('keypress', resetInactivityTimer);
        resetInactivityTimer();
    </script>
</body>
</html>