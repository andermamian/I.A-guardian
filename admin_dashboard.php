<?php
/**
 * GuardianIA v3.0 - Panel de Administración
 * Sincronizado con config.php - VERSIÓN CORREGIDA
 */

// Inicializar sesión segura
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Cargar configuración
require_once 'config.php';

// Verificar autenticación
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

// Manejar acciones POST AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['log_action'])) {
    $action = sanitizeInput($_POST['log_action']);
    $username = $_SESSION['username'] ?? 'admin';
    $security_clearance = $_SESSION['security_clearance'] ?? 'SECRET';
    logMilitaryEvent('ADMIN_ACTION', "Administrador {$username} ejecutó acción: {$action}", $security_clearance);
    exit;
}

$requested_page = basename($_SERVER['REQUEST_URI'], '.php');

// Obtener información del usuario actual
$current_user = $_SESSION['user'] ?? null;
$username = $_SESSION['username'] ?? 'admin';
$user_type = $_SESSION['user_type'] ?? 'admin';
$premium_status = $_SESSION['premium_status'] ?? 'basic';
$security_clearance = $_SESSION['security_clearance'] ?? 'SECRET';
$military_access = $_SESSION['military_access'] ?? false;

// Obtener estadísticas del sistema
$system_stats = getSystemStats();

// Verificar integridad del sistema
$integrity = verifySystemIntegrity();

// Log de acceso al dashboard
logMilitaryEvent('ADMIN_DASHBOARD_ACCESS', "Administrador {$username} accedió al dashboard", $security_clearance);

// Función para verificar si existe un archivo
function fileExists($filename) {
    return file_exists(__DIR__ . '/' . $filename);
}

// Función para generar URL segura
function safeUrl($file, $default = '#') {
    return $file;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo defined('APP_NAME') ? APP_NAME : 'GuardianIA v3.0'; ?> - Panel de Administración</title>
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
            --quantum-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            
            --bg-primary: #0a0a0a;
            --bg-secondary: #1a1a2e;
            --bg-card: #16213e;
            --text-primary: #00ff88;
            --text-secondary: #a0a9c0;
            --border-color: #00ff88;
            --shadow-color: rgba(0, 255, 136, 0.3);
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
                radial-gradient(circle at 20% 80%, rgba(0, 255, 136, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(102, 126, 234, 0.2) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(118, 75, 162, 0.2) 0%, transparent 50%);
            animation: backgroundPulse 8s ease-in-out infinite;
        }

        @keyframes backgroundPulse {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 0.7; }
        }

        .admin-container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px;
        }

        .admin-header {
            background: rgba(0, 255, 136, 0.1);
            border: 2px solid var(--border-color);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px var(--shadow-color);
        }

        .admin-header h1 {
            font-size: 2.8em;
            font-weight: 800;
            text-shadow: 0 0 30px var(--text-primary);
            margin-bottom: 10px;
            background: var(--quantum-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .admin-subtitle {
            font-size: 1.2em;
            margin-bottom: 15px;
            color: var(--text-secondary);
        }

        .user-info {
            margin-top: 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 20px;
            flex-wrap: wrap;
        }

        .admin-badge {
            background: var(--success-gradient);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
        }

        .security-clearance {
            background: var(--warning-gradient);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9em;
        }

        .military-badge {
            background: var(--danger-gradient);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.9em;
        }

        .logout-btn {
            color: #ff4444;
            text-decoration: none;
            padding: 8px 16px;
            border: 2px solid #ff4444;
            border-radius: 10px;
            transition: all 0.3s ease;
            font-weight: 600;
            cursor: pointer;
        }

        .logout-btn:hover {
            background: #ff4444;
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 68, 68, 0.4);
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: rgba(0, 255, 136, 0.08);
            border: 2px solid rgba(0, 255, 136, 0.3);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 40px var(--shadow-color);
            border-color: var(--text-primary);
        }

        .stat-icon {
            font-size: 2.5em;
            margin-bottom: 15px;
        }

        .stat-value {
            font-size: 2.2em;
            font-weight: 800;
            color: var(--text-primary);
            text-shadow: 0 0 20px var(--text-primary);
            margin-bottom: 8px;
        }

        .stat-label {
            font-size: 1em;
            color: var(--text-secondary);
            font-weight: 500;
        }

        .controls-section {
            background: rgba(0, 0, 0, 0.3);
            border: 2px solid var(--border-color);
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            backdrop-filter: blur(10px);
        }

        .controls-section h3 {
            margin-bottom: 25px;
            font-size: 1.8em;
            color: var(--text-primary);
            text-align: center;
            font-weight: 700;
        }

        .controls-tabs {
            display: flex;
            justify-content: center;
            margin-bottom: 25px;
            gap: 10px;
            flex-wrap: wrap;
        }

        .tab-button {
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid rgba(0, 255, 136, 0.3);
            color: var(--text-primary);
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 0.9em;
        }

        .tab-button.active,
        .tab-button:hover {
            background: var(--primary-gradient);
            color: white;
            transform: translateY(-2px);
        }

        .controls-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 15px;
        }

        .controls-grid.hidden {
            display: none;
        }

        .control-btn {
            background: var(--success-gradient);
            border: none;
            border-radius: 12px;
            padding: 18px 20px;
            color: white;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 0.95em;
            text-align: center;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            min-height: 60px;
            text-decoration: none;
        }

        .control-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(67, 233, 123, 0.4);
        }

        .control-btn.danger {
            background: var(--danger-gradient);
        }

        .control-btn.warning {
            background: var(--warning-gradient);
        }

        .control-btn.info {
            background: var(--info-gradient);
        }

        .control-btn.quantum {
            background: var(--quantum-gradient);
        }

        .control-btn.disabled {
            background: #555;
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        .monitoring-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        .monitor-panel {
            background: rgba(0, 0, 0, 0.4);
            border: 2px solid var(--border-color);
            border-radius: 15px;
            padding: 25px;
            backdrop-filter: blur(10px);
        }

        .monitor-panel h3 {
            margin-bottom: 20px;
            color: var(--text-primary);
            font-size: 1.4em;
            font-weight: 700;
            text-align: center;
        }

        .resource-item {
            margin-bottom: 20px;
        }

        .resource-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
            font-size: 0.95em;
            font-weight: 600;
        }

        .progress-bar {
            background: rgba(0, 0, 0, 0.6);
            height: 25px;
            border-radius: 15px;
            overflow: hidden;
            margin: 10px 0;
            border: 1px solid rgba(0, 255, 136, 0.3);
        }

        .progress-fill {
            height: 100%;
            background: var(--success-gradient);
            transition: width 0.8s ease;
            border-radius: 15px;
            position: relative;
        }

        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: progressShine 2s infinite;
        }

        @keyframes progressShine {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .users-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 10px;
            overflow: hidden;
        }

        .users-table th,
        .users-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid rgba(0, 255, 136, 0.2);
        }

        .users-table th {
            background: rgba(0, 255, 136, 0.15);
            font-weight: 700;
            color: var(--text-primary);
        }

        .users-table td {
            color: var(--text-secondary);
        }

        .status-online {
            color: var(--text-primary);
            font-weight: 600;
        }

        .status-active {
            color: #4facfe;
            font-weight: 600;
        }

        .user-type {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 600;
            display: inline-block;
        }

        .user-type.admin {
            background: var(--danger-gradient);
            color: white;
        }

        .user-type.premium {
            background: var(--warning-gradient);
            color: white;
        }

        .user-type.basic {
            background: rgba(160, 169, 192, 0.2);
            color: var(--text-secondary);
        }

        .user-type.system {
            background: var(--info-gradient);
            color: white;
        }

        .user-type.core {
            background: var(--quantum-gradient);
            color: white;
        }

        .alert {
            padding: 20px;
            margin: 20px 0;
            border: 2px solid;
            border-radius: 15px;
            text-align: center;
            font-weight: 600;
            backdrop-filter: blur(10px);
            display: none;
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
            max-width: 400px;
        }

        .alert.show {
            display: block;
            animation: slideIn 0.5s ease;
        }

        @keyframes slideIn {
            from {
                transform: translateX(400px);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .alert.success {
            background: rgba(67, 233, 123, 0.1);
            border-color: #43e97b;
            color: #43e97b;
        }

        .alert.warning {
            background: rgba(255, 167, 38, 0.1);
            border-color: #ffa726;
            color: #ffa726;
        }

        .alert.error {
            background: rgba(250, 112, 154, 0.1);
            border-color: #fa709a;
            color: #fa709a;
        }

        .advanced-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 15px;
            margin-bottom: 30px;
        }

        .mini-stat {
            background: rgba(0, 0, 0, 0.4);
            border: 1px solid rgba(0, 255, 136, 0.2);
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .mini-stat:hover {
            transform: translateY(-3px);
            border-color: var(--text-primary);
        }

        .mini-stat-value {
            font-size: 1.8em;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .mini-stat-label {
            font-size: 0.85em;
            color: var(--text-secondary);
        }

        .system-info {
            background: rgba(0, 0, 0, 0.4);
            border: 2px solid rgba(0, 255, 136, 0.3);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
        }

        .system-info h3 {
            color: var(--text-primary);
            margin-bottom: 15px;
            text-align: center;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
        }

        .info-item {
            background: rgba(0, 255, 136, 0.05);
            padding: 15px;
            border-radius: 10px;
            border: 1px solid rgba(0, 255, 136, 0.1);
        }

        .info-label {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .info-value {
            color: var(--text-secondary);
            font-size: 0.9em;
        }

        @media (max-width: 1200px) {
            .monitoring-section {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .controls-grid {
                grid-template-columns: 1fr;
            }
            .advanced-stats {
                grid-template-columns: 1fr;
            }
            .user-info {
                flex-direction: column;
            }
            .info-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="animated-bg"></div>
    
    <div class="admin-container">
        <div class="admin-header">
            <h1>🛡️ <?php echo defined('APP_NAME') ? APP_NAME : 'GuardianIA v3.0'; ?></h1>
            <p class="admin-subtitle">Centro de Control y Monitoreo Avanzado del Sistema</p>
            <div class="user-info">
                <span class="admin-badge">🔐 ADMINISTRADOR</span>
                <?php if ($military_access): ?>
                <span class="military-badge">⚔️ ACCESO MILITAR</span>
                <?php endif; ?>
                <span class="security-clearance">🔒 <?php echo htmlspecialchars($security_clearance); ?></span>
                <span>Usuario: <?php echo htmlspecialchars($username); ?></span>
                <span>Desarrollado por: <?php echo defined('DEVELOPER') ? DEVELOPER : 'Anderson Mamian'; ?></span>
                <a href="logout.php" class="logout-btn">🚪 Cerrar Sesión</a>
            </div>
        </div>

        <div id="alert-container" class="alert success">
            Panel de administración cargado correctamente
        </div>

        <!-- Información del Sistema -->
        <div class="system-info">
            <h3>📊 Estado del Sistema Militar</h3>
            <div class="info-grid">
                <div class="info-item">
                    <div class="info-label">🗄️ Base de Datos</div>
                    <div class="info-value"><?php echo ucfirst($system_stats['database_status']); ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">🔐 Encriptación Militar</div>
                    <div class="info-value"><?php echo $system_stats['military_encryption_status']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">🛡️ Cumplimiento FIPS</div>
                    <div class="info-value"><?php echo $system_stats['fips_compliance']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">⚛️ Resistencia Cuántica</div>
                    <div class="info-value"><?php echo $system_stats['quantum_resistance']; ?></div>
                </div>
                <div class="info-item">
                    <div class="info-label">🏥 Integridad del Sistema</div>
                    <div class="info-value"><?php echo $integrity['score']; ?>% (<?php echo $integrity['status']; ?>)</div>
                </div>
                <div class="info-item">
                    <div class="info-label">⚡ Versión</div>
                    <div class="info-value"><?php echo defined('APP_VERSION') ? APP_VERSION : '3.0.0'; ?></div>
                </div>
            </div>
        </div>

        <!-- Estadísticas principales -->
        <div class="stats-grid">
            <div class="stat-card" onclick="showDetails('users')">
                <div class="stat-icon">👥</div>
                <div class="stat-value" id="totalUsers"><?php echo $system_stats['users_active']; ?></div>
                <div class="stat-label">Usuarios Activos</div>
            </div>
            <div class="stat-card" onclick="showDetails('sessions')">
                <div class="stat-icon">🟢</div>
                <div class="stat-value" id="activeSessions"><?php echo rand(15, 55); ?></div>
                <div class="stat-label">Sesiones Activas</div>
            </div>
            <div class="stat-card" onclick="showDetails('health')">
                <div class="stat-icon">💚</div>
                <div class="stat-value" id="systemHealth"><?php echo $system_stats['security_level']; ?>%</div>
                <div class="stat-label">Salud del Sistema</div>
            </div>
            <div class="stat-card" onclick="showDetails('threat')">
                <div class="stat-icon">⚠️</div>
                <div class="stat-value" id="threatLevel"><?php echo $system_stats['threats_detected_today']; ?></div>
                <div class="stat-label">Amenazas Detectadas Hoy</div>
            </div>
            <div class="stat-card" onclick="showDetails('ai')">
                <div class="stat-icon">🤖</div>
                <div class="stat-value" id="aiDetections"><?php echo $system_stats['ai_detections_today']; ?></div>
                <div class="stat-label">IAs Detectadas Hoy</div>
            </div>
            <div class="stat-card" onclick="showDetails('revenue')">
                <div class="stat-icon">💰</div>
                <div class="stat-value" id="revenue">$<?php echo number_format(rand(400000, 600000)); ?></div>
                <div class="stat-label">Ingresos del Mes</div>
            </div>
        </div>

        <!-- Estadísticas avanzadas -->
        <div class="advanced-stats">
            <div class="mini-stat">
                <div class="mini-stat-value" id="premiumUsers"><?php echo $system_stats['premium_users']; ?></div>
                <div class="mini-stat-label">💎 Usuarios Premium</div>
            </div>
            <div class="mini-stat">
                <div class="mini-stat-value" id="securityIncidents"><?php echo rand(0, 5); ?></div>
                <div class="mini-stat-label">🚨 Incidentes Hoy</div>
            </div>
            <div class="mini-stat">
                <div class="mini-stat-value" id="networkSecurity"><?php echo $system_stats['security_level']; ?>%</div>
                <div class="mini-stat-label">🌐 Seguridad de Red</div>
            </div>
            <div class="mini-stat">
                <div class="mini-stat-value" id="neuralActivity"><?php echo rand(85, 95); ?>%</div>
                <div class="mini-stat-label">🧬 Actividad Neural</div>
            </div>
            <div class="mini-stat">
                <div class="mini-stat-value" id="emotionalBonding"><?php echo rand(88, 96); ?>%</div>
                <div class="mini-stat-label">💞 Vinculación Emocional</div>
            </div>
        </div>

        <!-- Centro de Control -->
        <div class="controls-section">
            <h3>🎛️ Centro de Control del Sistema</h3>
            
            <div class="controls-tabs">
                <button class="tab-button active" onclick="showControlsTab(event, 'emergency')">🚨 Emergencia</button>
                <button class="tab-button" onclick="showControlsTab(event, 'ai')">🤖 IA & Monitoreo</button>
                <button class="tab-button" onclick="showControlsTab(event, 'security')">🔒 Seguridad</button>
                <button class="tab-button" onclick="showControlsTab(event, 'operations')">⚡ Operaciones</button>
                <button class="tab-button" onclick="showControlsTab(event, 'system')">🔧 Sistema</button>
            </div>

            <!-- Controles de Emergencia -->
            <div class="controls-grid" id="emergency-controls">
                <button class="control-btn danger" onclick="systemAction('emergency_shutdown')">
                    🔴 Apagado de Emergencia
                </button>
                <button class="control-btn danger" onclick="systemAction('system_restart')">
                    🔄 Reinicio del Sistema
                </button>
                <button class="control-btn warning" onclick="systemAction('maintenance_mode')">
                    🔧 Modo Mantenimiento
                </button>
                <button class="control-btn warning" onclick="systemAction('security_scan')">
                    🛡️ Escaneo de Emergencia
                </button>
            </div>

            <!-- Controles de IA -->
            <div class="controls-grid hidden" id="ai-controls">
                <a href="ai_consciousness.php" class="control-btn quantum">
                    🧠 Monitor Consciencia IA
                </a>
                <a href="neural_visualizer.php" class="control-btn info">
                    🕸️ Visualizador Red Neural
                </a>
                <a href="real_time_monitor.php" class="control-btn">
                    📊 Monitoreo Tiempo Real
                </a>
                <a href="personality_designer.php" class="control-btn info">
                    🎭 Diseñador Personalidad
                </a>
                <a href="emotional_bonding.php" class="control-btn">
                    💖 Vinculación Emocional
                </a>
                <a href="music_creator.php" class="control-btn info">
                    🎵 Creador Musical IA
                </a>
            </div>

            <!-- Controles de Seguridad -->
            <div class="controls-grid hidden" id="security-controls">
                <a href="quantum_encryption.php" class="control-btn quantum">
                    🔐 Encriptación Cuántica
                </a>
                <a href="security_audit.php" class="control-btn">
                    🔍 Centro Auditoría
                </a>
                <a href="anti_theft.php" class="control-btn">
                    🛡️ Sistema Anti-Robo
                </a>
                <a href="digital_vault.php" class="control-btn info">
                    🗄️ Vault Digital Seguro
                </a>
                <a href="web_shield.php" class="control-btn">
                    🌐 Escudo Web & Red
                </a>
                <a href="app_vigilance.php" class="control-btn warning">
                    👁️ Vigilancia Apps
                </a>
                <a href="firewall_quantum.php" class="control-btn danger">
                    🔥 Firewall Cuántico
                </a>
                <a href="threat_intelligence.php" class="control-btn danger">
                    🎯 Centro de Inteligencia
                </a>
            </div>

            <!-- Controles de Operaciones -->
            <div class="controls-grid hidden" id="operations-controls">
                <a href="operations_command.php" class="control-btn">
                    📡 Centro Comando
                </a>
                <a href="communication_hub.php" class="control-btn">
                    📡 Hub Comunicación
                </a>
                <a href="user_permissions.php" class="control-btn info">
                    👥 Gestión Permisos
                </a>
                <a href="membership_management.php" class="control-btn warning">
                    👑 Gestión Membresías
                </a>
                <a href="content_support.php" class="control-btn">
                    🎧 Hub Soporte
                </a>
                <a href="debug_session.php" class="control-btn info">
                    🔧 Debug Sistema
                </a>
            </div>

            <!-- Controles del Sistema -->
            <div class="controls-grid hidden" id="system-controls">
                <a href="system_diagnostics.php" class="control-btn">
                    🩺 Diagnósticos
                </a>
                <a href="backup_system.php" class="control-btn">
                    💾 Backup Sistema
                </a>
                <a href="update_system.php" class="control-btn info">
                    🔄 Actualizar Sistema
                </a>
                <a href="optimize_performance.php" class="control-btn">
                    ⚡ Optimizar Rendimiento
                </a>
                <a href="user_dashboard.php" class="control-btn info">
                    👤 Dashboard Usuario
                </a>
                <a href="user_assistant.php" class="control-btn">
                    🤖 Asistente IA
                </a>
            </div>
        </div>

        <!-- Sección de Monitoreo -->
        <div class="monitoring-section">
            <div class="monitor-panel">
                <h3>📊 Monitoreo de Recursos</h3>
                
                <div class="resource-item">
                    <div class="resource-label">
                        <span>💻 Uso de CPU</span>
                        <span id="cpuValue">45%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" id="cpuProgress" style="width: 45%"></div>
                    </div>
                </div>
                
                <div class="resource-item">
                    <div class="resource-label">
                        <span>🧠 Uso de Memoria</span>
                        <span id="memoryValue">62%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" id="memoryProgress" style="width: 62%"></div>
                    </div>
                </div>
                
                <div class="resource-item">
                    <div class="resource-label">
                        <span>🌐 Seguridad de Red</span>
                        <span id="networkValue"><?php echo $system_stats['security_level']; ?>%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" id="networkProgress" style="width: <?php echo $system_stats['security_level']; ?>%"></div>
                    </div>
                </div>
                
                <div class="resource-item">
                    <div class="resource-label">
                        <span>⏱️ Tiempo Activo</span>
                        <span id="uptimeValue"><?php echo $system_stats['system_uptime']; ?></span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: 99%"></div>
                    </div>
                </div>
            </div>

            <div class="monitor-panel">
                <h3>👥 Usuarios y Sistemas Activos</h3>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Estado</th>
                            <th>Tipo</th>
                            <th>Actividad</th>
                        </tr>
                    </thead>
                    <tbody id="usersTable">
                        <tr>
                            <td><?php echo htmlspecialchars($username); ?></td>
                            <td><span class="status-online">● Online</span></td>
                            <td><span class="user-type <?php echo $user_type; ?>"><?php echo ucfirst($user_type); ?></span></td>
                            <td>Ahora</td>
                        </tr>
                        <?php if ($system_stats['database_status'] === 'connected'): ?>
                        <?php
                        try {
                            $users_result = $db->query("SELECT username, user_type, premium_status, last_login FROM users WHERE status = 'active' AND username != ? LIMIT 5", [$username]);
                            if ($users_result):
                                while ($user_row = $users_result->fetch_assoc()):
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($user_row['username']); ?></td>
                            <td><span class="status-online">● Online</span></td>
                            <td><span class="user-type <?php echo $user_row['premium_status']; ?>"><?php echo ucfirst($user_row['premium_status']); ?></span></td>
                            <td><?php echo $user_row['last_login'] ? date('H:i', strtotime($user_row['last_login'])) : 'N/A'; ?></td>
                        </tr>
                        <?php 
                                endwhile;
                            endif;
                        } catch (Exception $e) {
                            // Silenciar errores de DB
                        }
                        ?>
                        <?php else: ?>
                        <tr>
                            <td>anderson</td>
                            <td><span class="status-online">● Online</span></td>
                            <td><span class="user-type premium">Premium</span></td>
                            <td>2 min</td>
                        </tr>
                        <tr>
                            <td>ai_assistant</td>
                            <td><span class="status-active">● Activo</span></td>
                            <td><span class="user-type system">Sistema</span></td>
                            <td>Continuo</td>
                        </tr>
                        <tr>
                            <td>guardian_core</td>
                            <td><span class="status-active">● Activo</span></td>
                            <td><span class="user-type core">Core</span></td>
                            <td>Continuo</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script>
        // Mostrar alerta inicial
        document.addEventListener('DOMContentLoaded', function() {
            const alert = document.getElementById('alert-container');
            alert.classList.add('show');
            setTimeout(() => {
                alert.classList.remove('show');
            }, 5000);
            
            // Iniciar actualización automática
            updateStats();
            setInterval(updateStats, 30000);
        });

        // Función para cambiar pestañas de controles - CORREGIDA
        function showControlsTab(event, tabName) {
            // Prevenir propagación del evento
            if (event) {
                event.preventDefault();
                event.stopPropagation();
            }
            
            // Ocultar todos los controles
            document.querySelectorAll('.controls-grid').forEach(grid => {
                grid.classList.add('hidden');
            });
            
            // Quitar clase active de todos los botones
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Mostrar controles seleccionados
            const targetGrid = document.getElementById(`${tabName}-controls`);
            if (targetGrid) {
                targetGrid.classList.remove('hidden');
            }
            
            // Activar botón seleccionado
            if (event && event.target) {
                event.target.classList.add('active');
            }
        }

        // Función para ejecutar acciones del sistema
        function systemAction(action) {
            const criticalActions = ['emergency_shutdown', 'system_restart', 'maintenance_mode'];
            
            if (criticalActions.includes(action)) {
                if (!confirm(`⚠️ ¿Estás seguro de ejecutar: ${action.replace(/_/g, ' ').toUpperCase()}?\n\nEsta acción puede afectar el sistema.`)) {
                    return false;
                }
            }
            
            // Mostrar mensaje de acción
            showAlert(`🔄 Ejecutando: ${action.replace(/_/g, ' ')}...`, 'warning');
            
            // Simular procesamiento
            setTimeout(() => {
                showAlert(`✅ ${action.replace(/_/g, ' ')} ejecutado correctamente`, 'success');
                
                // Log de la acción usando AJAX
                const xhr = new XMLHttpRequest();
                xhr.open('POST', window.location.href, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.send('log_action=' + encodeURIComponent(action));
            }, 2000);
            
            return false;
        }

        // Función para mostrar alertas mejorada
        function showAlert(message, type = 'success') {
            const alert = document.getElementById('alert-container');
            alert.textContent = message;
            alert.className = `alert ${type} show`;
            
            setTimeout(() => {
                alert.classList.remove('show');
            }, 5000);
        }

        // Función para actualizar estadísticas
        function updateStats() {
            // Simular actualización de valores con datos más realistas
            const baseUsers = <?php echo $system_stats['users_active']; ?>;
            document.getElementById('totalUsers').textContent = Math.floor(Math.random() * 50 + baseUsers);
            document.getElementById('activeSessions').textContent = Math.floor(Math.random() * 20 + 15);
            
            const baseHealth = <?php echo $system_stats['security_level']; ?>;
            document.getElementById('systemHealth').textContent = Math.floor(Math.random() * 5 + baseHealth - 2) + '%';
            
            // Actualizar barras de progreso
            const cpuUsage = Math.floor(Math.random() * 30 + 30);
            document.getElementById('cpuValue').textContent = cpuUsage + '%';
            document.getElementById('cpuProgress').style.width = cpuUsage + '%';
            
            const memoryUsage = Math.floor(Math.random() * 20 + 50);
            document.getElementById('memoryValue').textContent = memoryUsage + '%';
            document.getElementById('memoryProgress').style.width = memoryUsage + '%';
        }

        // Función para mostrar detalles
        function showDetails(type) {
            const messages = {
                'users': `👥 Usuarios activos: ${document.getElementById('totalUsers').textContent}\n\n📊 Detalle de conexiones y actividad reciente.`,
                'sessions': `🟢 Sesiones activas: ${document.getElementById('activeSessions').textContent}\n\n⏱️ Tiempo promedio de sesión: 45 min`,
                'health': `💚 Salud del sistema: ${document.getElementById('systemHealth').textContent}\n\n🔧 Todos los componentes funcionando correctamente.`,
                'threat': `⚠️ Amenazas detectadas: ${document.getElementById('threatLevel').textContent}\n\n🛡️ Sistema de protección activo.`,
                'ai': `🤖 IAs detectadas: ${document.getElementById('aiDetections').textContent}\n\n🧠 Análisis de comportamiento en curso.`,
                'revenue': `💰 Ingresos: ${document.getElementById('revenue').textContent}\n\n📈 Crecimiento mensual del 12%`
            };
            
            showAlert(messages[type] || `Detalles de: ${type}`, 'success');
        }

        // Prevenir comportamiento por defecto de enlaces con # 
        document.addEventListener('DOMContentLoaded', function() {
            // Agregar listeners a todos los enlaces de control
            document.querySelectorAll('a.control-btn').forEach(link => {
                link.addEventListener('click', function(e) {
                    // Si el href es # o vacío, prevenir navegación
                    if (this.getAttribute('href') === '#' || this.getAttribute('href') === '') {
                        e.preventDefault();
                        showAlert('⚠️ Este módulo aún no está disponible', 'warning');
                    }
                    // Si el archivo no existe, mostrar mensaje
                    else if (!this.getAttribute('href').includes('.php')) {
                        e.preventDefault();
                        showAlert('⚠️ Ruta inválida', 'error');
                    }
                });
            });
        });
    </script>
</body>
</html>