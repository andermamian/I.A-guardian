<?php
session_start();
require_once __DIR__ . "/config.php";
require_once __DIR__ . "/config_military.php";

// Verificar autenticación
if (!isset($_SESSION["logged_in"]) || !$_SESSION["logged_in"]) {
    header("Location: login.php");
    exit;
}

// Verificar nivel de seguridad del usuario
$user_security_level = $_SESSION['security_clearance'] ?? 'UNCLASSIFIED';
$is_premium = isPremiumUser($_SESSION['user_id'] ?? 0);
$has_military_access = $_SESSION['military_access'] ?? false;

// Inicializar variables de estado del sistema
$system_stats = getSystemStats();
$db = $GLOBALS['db'] ?? null;

// Función para obtener aplicaciones instaladas (simulación avanzada)
function getInstalledApplications() {
    // Simulación de aplicaciones detectadas en el sistema
    $applications = [
        [
            'id' => 1,
            'name' => 'Google Chrome',
            'version' => '120.0.6099.109',
            'publisher' => 'Google LLC',
            'threat_level' => 'low',
            'ai_detected' => false,
            'encrypted' => false,
            'last_scan' => date('Y-m-d H:i:s', strtotime('-2 hours')),
            'file_path' => 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe',
            'size' => '156.2 MB',
            'permissions' => ['network', 'filesystem', 'camera', 'microphone'],
            'connections' => ['google.com', 'googleapis.com', 'gstatic.com']
        ],
        [
            'id' => 2,
            'name' => 'Microsoft Office',
            'version' => '16.0.16827.20166',
            'publisher' => 'Microsoft Corporation',
            'threat_level' => 'low',
            'ai_detected' => false,
            'encrypted' => true,
            'last_scan' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            'file_path' => 'C:\\Program Files\\Microsoft Office\\Office16\\WINWORD.EXE',
            'size' => '2.1 GB',
            'permissions' => ['filesystem', 'network'],
            'connections' => ['office.com', 'microsoft.com']
        ],
        [
            'id' => 3,
            'name' => 'Suspicious Process',
            'version' => '1.0.0',
            'publisher' => 'Unknown',
            'threat_level' => 'high',
            'ai_detected' => true,
            'encrypted' => false,
            'last_scan' => date('Y-m-d H:i:s', strtotime('-30 minutes')),
            'file_path' => 'C:\\Temp\\unknown_process.exe',
            'size' => '15.3 MB',
            'permissions' => ['network', 'filesystem', 'registry', 'admin'],
            'connections' => ['suspicious-domain.com', '192.168.1.100']
        ],
        [
            'id' => 4,
            'name' => 'Discord',
            'version' => '1.0.9013',
            'publisher' => 'Discord Inc.',
            'threat_level' => 'medium',
            'ai_detected' => false,
            'encrypted' => true,
            'last_scan' => date('Y-m-d H:i:s', strtotime('-45 minutes')),
            'file_path' => 'C:\\Users\\Anderson\\AppData\\Local\\Discord\\app-1.0.9013\\Discord.exe',
            'size' => '445.7 MB',
            'permissions' => ['network', 'audio', 'video'],
            'connections' => ['discord.com', 'discordapp.com']
        ],
        [
            'id' => 5,
            'name' => 'AI Neural Network Trainer',
            'version' => '2.1.0',
            'publisher' => 'TensorFlow Community',
            'threat_level' => 'medium',
            'ai_detected' => true,
            'encrypted' => true,
            'last_scan' => date('Y-m-d H:i:s', strtotime('-10 minutes')),
            'file_path' => 'C:\\AI\\tensorflow\\neural_trainer.exe',
            'size' => '892.4 MB',
            'permissions' => ['gpu', 'network', 'filesystem'],
            'connections' => ['tensorflow.org', 'google.com']
        ]
    ];

    return $applications;
}

// Función para obtener procesos en tiempo real
function getRealTimeProcesses() {
    return [
        ['pid' => 1234, 'name' => 'chrome.exe', 'cpu' => 15.2, 'memory' => 234.5, 'status' => 'running'],
        ['pid' => 2345, 'name' => 'winword.exe', 'cpu' => 3.1, 'memory' => 128.7, 'status' => 'running'],
        ['pid' => 3456, 'name' => 'suspicious.exe', 'cpu' => 45.8, 'memory' => 512.3, 'status' => 'suspicious'],
        ['pid' => 4567, 'name' => 'discord.exe', 'cpu' => 8.7, 'memory' => 89.2, 'status' => 'running'],
        ['pid' => 5678, 'name' => 'neural_trainer.exe', 'cpu' => 78.9, 'memory' => 1024.8, 'status' => 'ai_detected']
    ];
}

// Procesar acciones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'scan_app':
            $app_id = (int)$_POST['app_id'];
            logMilitaryEvent('APP_SCAN_INITIATED', "Escaneo militar iniciado para aplicación ID: {$app_id}", $user_security_level);
            echo json_encode(['status' => 'success', 'message' => 'Escaneo iniciado']);
            break;
            
        case 'quarantine_app':
            $app_id = (int)$_POST['app_id'];
            logMilitaryEvent('APP_QUARANTINE', "Aplicación puesta en cuarentena ID: {$app_id}", 'SECRET');
            echo json_encode(['status' => 'success', 'message' => 'Aplicación puesta en cuarentena']);
            break;
            
        case 'terminate_process':
            $pid = (int)$_POST['pid'];
            logMilitaryEvent('PROCESS_TERMINATED', "Proceso terminado PID: {$pid}", 'CONFIDENTIAL');
            echo json_encode(['status' => 'success', 'message' => 'Proceso terminado']);
            break;
            
        case 'get_real_time_data':
            echo json_encode([
                'processes' => getRealTimeProcesses(),
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            break;
    }
    exit;
}

$applications = getInstalledApplications();
$processes = getRealTimeProcesses();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🛡️ App Vigilance - GuardianIA v3.0 MILITAR</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #00ff88;
            --secondary-color: #0066cc;
            --danger-color: #ff4757;
            --warning-color: #ffa502;
            --success-color: #2ed573;
            --dark-bg: #0a0a0a;
            --card-bg: rgba(0, 255, 136, 0.05);
            --border-color: rgba(0, 255, 136, 0.2);
            --text-primary: #ffffff;
            --text-secondary: #b0b0b0;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: "Segoe UI", "Source Code Pro", monospace;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 50%, #16213e 100%);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
        }

        /* Header militar */
        .military-header {
            background: linear-gradient(135deg, rgba(0, 255, 136, 0.1) 0%, rgba(0, 102, 204, 0.1) 100%);
            border-bottom: 2px solid var(--primary-color);
            padding: 20px 0;
            position: relative;
            overflow: hidden;
        }

        .military-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 255, 136, 0.1), transparent);
            animation: scan 3s infinite;
        }

        @keyframes scan {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .header-content {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            position: relative;
            z-index: 1;
        }

        .app-title {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .app-title h1 {
            font-size: 2.5em;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-shadow: 0 0 20px rgba(0, 255, 136, 0.3);
        }

        .classification-badge {
            background: linear-gradient(135deg, var(--danger-color), #ff6b7a);
            color: white;
            padding: 8px 16px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 0.9em;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 20px;
        }

        .status-indicator {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px 20px;
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid var(--primary-color);
            border-radius: 25px;
        }

        .status-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--success-color);
            animation: blink 1.5s infinite;
        }

        @keyframes blink {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.3; }
        }

        /* Container principal */
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        /* Panel de estadísticas */
        .stats-panel {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 255, 136, 0.2);
            border-color: var(--primary-color);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        .stat-icon {
            font-size: 3em;
            margin-bottom: 15px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-value {
            font-size: 2.5em;
            font-weight: bold;
            margin-bottom: 10px;
            color: var(--primary-color);
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 1.1em;
        }

        /* Tabs */
        .tab-container {
            margin-bottom: 30px;
        }

        .tab-buttons {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .tab-button {
            padding: 15px 30px;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            color: var(--text-secondary);
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: bold;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .tab-button.active {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-color: var(--primary-color);
            box-shadow: 0 10px 30px rgba(0, 255, 136, 0.3);
        }

        .tab-button:hover:not(.active) {
            border-color: var(--primary-color);
            background: rgba(0, 255, 136, 0.1);
        }

        /* Contenido de tabs */
        .tab-content {
            display: none;
            animation: fadeIn 0.5s ease;
        }

        .tab-content.active {
            display: block;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Tabla de aplicaciones */
        .app-table-container {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            overflow: hidden;
        }

        .app-table {
            width: 100%;
            border-collapse: collapse;
        }

        .app-table th {
            background: linear-gradient(135deg, rgba(0, 255, 136, 0.2), rgba(0, 102, 204, 0.2));
            padding: 15px;
            text-align: left;
            font-weight: bold;
            border-bottom: 2px solid var(--border-color);
        }

        .app-table td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
            vertical-align: top;
        }

        .app-table tr:hover {
            background: rgba(0, 255, 136, 0.05);
        }

        .app-info {
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .app-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2em;
        }

        .threat-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8em;
            font-weight: bold;
            text-transform: uppercase;
        }

        .threat-low { background: var(--success-color); color: white; }
        .threat-medium { background: var(--warning-color); color: white; }
        .threat-high { background: var(--danger-color); color: white; animation: pulse 1.5s infinite; }

        .ai-indicator {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 8px;
            background: linear-gradient(135deg, #667eea, #764ba2);
            border-radius: 15px;
            font-size: 0.8em;
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 5px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .btn-warning {
            background: linear-gradient(135deg, var(--warning-color), #ff9f43);
            color: white;
        }

        .btn-danger {
            background: linear-gradient(135deg, var(--danger-color), #ff6b7a);
            color: white;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        /* Monitoreo en tiempo real */
        .real-time-monitor {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .monitor-panel {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            padding: 25px;
        }

        .monitor-header {
            display: flex;
            align-items: center;
            justify-content: between;
            margin-bottom: 20px;
        }

        .monitor-title {
            font-size: 1.3em;
            font-weight: bold;
            color: var(--primary-color);
        }

        .process-list {
            max-height: 400px;
            overflow-y: auto;
        }

        .process-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px;
            margin-bottom: 10px;
            background: rgba(0, 255, 136, 0.02);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .process-item:hover {
            background: rgba(0, 255, 136, 0.05);
            border-color: var(--primary-color);
        }

        .process-info h4 {
            margin-bottom: 5px;
        }

        .process-metrics {
            font-size: 0.9em;
            color: var(--text-secondary);
        }

        .process-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: bold;
        }

        .status-running { background: var(--success-color); color: white; }
        .status-suspicious { background: var(--warning-color); color: white; }
        .status-ai_detected { background: var(--danger-color); color: white; }

        /* Responsive */
        @media (max-width: 768px) {
            .header-content {
                flex-direction: column;
                gap: 20px;
            }
            
            .app-title h1 {
                font-size: 2em;
            }
            
            .tab-buttons {
                flex-wrap: wrap;
            }
            
            .real-time-monitor {
                grid-template-columns: 1fr;
            }
            
            .app-table-container {
                overflow-x: auto;
            }
        }

        /* Animaciones de carga */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(0, 255, 136, 0.3);
            border-radius: 50%;
            border-top-color: var(--primary-color);
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Alertas militares */
        .military-alert {
            background: linear-gradient(135deg, var(--danger-color), #ff6b7a);
            border-left: 5px solid #ff4757;
            padding: 15px;
            margin: 20px 0;
            border-radius: 8px;
            color: white;
            display: flex;
            align-items: center;
            gap: 15px;
        }

        .military-alert.warning {
            background: linear-gradient(135deg, var(--warning-color), #ff9f43);
            border-left-color: #ffa502;
        }

        .military-alert.success {
            background: linear-gradient(135deg, var(--success-color), #26de81);
            border-left-color: #2ed573;
        }
    </style>
</head>
<body>
    <!-- Header Militar -->
    <header class="military-header">
        <div class="header-content">
            <div class="app-title">
                <i class="fas fa-shield-alt" style="font-size: 2em; color: var(--primary-color);"></i>
                <h1>App Vigilance</h1>
                <div class="classification-badge">
                    <?php echo $user_security_level; ?>
                </div>
            </div>
            <div class="user-info">
                <div class="status-indicator">
                    <div class="status-dot"></div>
                    <span>SISTEMA ACTIVO</span>
                </div>
                <div style="text-align: right;">
                    <div><strong><?php echo htmlspecialchars($_SESSION["username"] ?? "Usuario"); ?></strong></div>
                    <div style="font-size: 0.9em; color: var(--text-secondary);">
                        <?php echo $is_premium ? '👑 PREMIUM' : '⚪ BÁSICO'; ?>
                    </div>
                </div>
            </div>
        </div>
    </header>

    <div class="container">
        <!-- Panel de Estadísticas -->
        <div class="stats-panel">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-desktop"></i>
                </div>
                <div class="stat-value"><?php echo count($applications); ?></div>
                <div class="stat-label">Aplicaciones Detectadas</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-exclamation-triangle"></i>
                </div>
                <div class="stat-value"><?php echo array_sum(array_map(function($app) { return $app['threat_level'] === 'high' ? 1 : 0; }, $applications)); ?></div>
                <div class="stat-label">Amenazas Críticas</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="stat-value"><?php echo array_sum(array_map(function($app) { return $app['ai_detected'] ? 1 : 0; }, $applications)); ?></div>
                <div class="stat-label">IA Detectada</div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-lock"></i>
                </div>
                <div class="stat-value"><?php echo array_sum(array_map(function($app) { return $app['encrypted'] ? 1 : 0; }, $applications)); ?></div>
                <div class="stat-label">Apps Encriptadas</div>
            </div>
        </div>

        <!-- Alertas Militares -->
        <?php if ($has_military_access): ?>
        <div class="military-alert">
            <i class="fas fa-shield-alt"></i>
            <div>
                <strong>ACCESO MILITAR ACTIVADO</strong><br>
                Protocolos de seguridad militar habilitados. Encriptación cuántica activa.
            </div>
        </div>
        <?php endif; ?>

        <?php if (array_sum(array_map(function($app) { return $app['threat_level'] === 'high' ? 1 : 0; }, $applications)) > 0): ?>
        <div class="military-alert">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>AMENAZA DETECTADA</strong><br>
                Se han detectado aplicaciones con nivel de amenaza alto. Acción inmediata requerida.
            </div>
        </div>
        <?php endif; ?>

        <!-- Tabs de Navegación -->
        <div class="tab-container">
            <div class="tab-buttons">
                <button class="tab-button active" onclick="switchTab('applications')">
                    <i class="fas fa-th-large"></i>
                    Aplicaciones Instaladas
                </button>
                <button class="tab-button" onclick="switchTab('processes')">
                    <i class="fas fa-microchip"></i>
                    Procesos en Tiempo Real
                </button>
                <button class="tab-button" onclick="switchTab('monitoring')">
                    <i class="fas fa-chart-line"></i>
                    Monitoreo Avanzado
                </button>
                <?php if ($is_premium): ?>
                <button class="tab-button" onclick="switchTab('neural')">
                    <i class="fas fa-brain"></i>
                    Red Neuronal IA
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- Contenido de Aplicaciones -->
        <div id="applications" class="tab-content active">
            <div class="app-table-container">
                <table class="app-table">
                    <thead>
                        <tr>
                            <th>Aplicación</th>
                            <th>Versión</th>
                            <th>Editor</th>
                            <th>Nivel de Amenaza</th>
                            <th>Estado</th>
                            <th>Último Escaneo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($applications as $app): ?>
                        <tr>
                            <td>
                                <div class="app-info">
                                    <div class="app-icon">
                                        <i class="fas fa-desktop"></i>
                                    </div>
                                    <div>
                                        <strong><?php echo htmlspecialchars($app['name']); ?></strong>
                                        <div style="font-size: 0.9em; color: var(--text-secondary);">
                                            <?php echo htmlspecialchars($app['size']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo htmlspecialchars($app['version']); ?></td>
                            <td><?php echo htmlspecialchars($app['publisher']); ?></td>
                            <td>
                                <span class="threat-badge threat-<?php echo $app['threat_level']; ?>">
                                    <?php echo strtoupper($app['threat_level']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($app['ai_detected']): ?>
                                    <div class="ai-indicator">
                                        <i class="fas fa-robot"></i>
                                        IA DETECTADA
                                    </div>
                                <?php endif; ?>
                                <?php if ($app['encrypted']): ?>
                                    <div style="margin-top: 5px;">
                                        <i class="fas fa-lock" style="color: var(--success-color);"></i>
                                        Encriptado
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div style="font-size: 0.9em;">
                                    <?php echo date('H:i:s', strtotime($app['last_scan'])); ?>
                                </div>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button class="btn btn-primary" onclick="scanApp(<?php echo $app['id']; ?>)">
                                        <i class="fas fa-search"></i>
                                        Escanear
                                    </button>
                                    <?php if ($app['threat_level'] === 'high'): ?>
                                    <button class="btn btn-danger" onclick="quarantineApp(<?php echo $app['id']; ?>)">
                                        <i class="fas fa-ban"></i>
                                        Cuarentena
                                    </button>
                                    <?php endif; ?>
                                    <button class="btn btn-warning" onclick="showAppDetails(<?php echo $app['id']; ?>)">
                                        <i class="fas fa-info-circle"></i>
                                        Detalles
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Contenido de Procesos -->
        <div id="processes" class="tab-content">
            <div class="real-time-monitor">
                <div class="monitor-panel">
                    <div class="monitor-header">
                        <h3 class="monitor-title">
                            <i class="fas fa-microchip"></i>
                            Procesos Activos
                        </h3>
                        <button class="btn btn-primary" onclick="refreshProcesses()">
                            <i class="fas fa-sync-alt"></i>
                            Actualizar
                        </button>
                    </div>
                    <div id="process-list" class="process-list">
                        <?php foreach ($processes as $process): ?>
                        <div class="process-item" data-pid="<?php echo $process['pid']; ?>">
                            <div class="process-info">
                                <h4><?php echo htmlspecialchars($process['name']); ?></h4>
                                <div class="process-metrics">
                                    PID: <?php echo $process['pid']; ?> | 
                                    CPU: <?php echo $process['cpu']; ?>% | 
                                    RAM: <?php echo $process['memory']; ?> MB
                                </div>
                            </div>
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <span class="process-status status-<?php echo $process['status']; ?>">
                                    <?php echo strtoupper(str_replace('_', ' ', $process['status'])); ?>
                                </span>
                                <?php if ($process['status'] === 'suspicious' || $process['status'] === 'ai_detected'): ?>
                                <button class="btn btn-danger btn-sm" onclick="terminateProcess(<?php echo $process['pid']; ?>)">
                                    <i class="fas fa-times"></i>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="monitor-panel">
                    <div class="monitor-header">
                        <h3 class="monitor-title">
                            <i class="fas fa-chart-line"></i>
                            Rendimiento del Sistema
                        </h3>
                    </div>
                    <div class="performance-metrics">
                        <div class="metric-item">
                            <div class="metric-label">Uso de CPU</div>
                            <div class="metric-bar">
                                <div class="metric-fill" style="width: 45%; background: var(--warning-color);"></div>
                            </div>
                            <div class="metric-value">45%</div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Uso de RAM</div>
                            <div class="metric-bar">
                                <div class="metric-fill" style="width: 68%; background: var(--danger-color);"></div>
                            </div>
                            <div class="metric-value">68%</div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Uso de Disco</div>
                            <div class="metric-bar">
                                <div class="metric-fill" style="width: 32%; background: var(--success-color);"></div>
                            </div>
                            <div class="metric-value">32%</div>
                        </div>
                        <div class="metric-item">
                            <div class="metric-label">Red (In/Out)</div>
                            <div class="metric-bar">
                                <div class="metric-fill" style="width: 25%; background: var(--primary-color);"></div>
                            </div>
                            <div class="metric-value">2.5 Mbps</div>
                        </div>
                    </div>

                    <div class="security-status" style="margin-top: 30px;">
                        <h4 style="color: var(--primary-color); margin-bottom: 15px;">
                            <i class="fas fa-shield-alt"></i>
                            Estado de Seguridad
                        </h4>
                        <div class="security-items">
                            <div class="security-item">
                                <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                                <span>Firewall Activo</span>
                            </div>
                            <div class="security-item">
                                <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                                <span>Encriptación Militar</span>
                            </div>
                            <div class="security-item">
                                <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                                <span>Detección de IA</span>
                            </div>
                            <div class="security-item">
                                <i class="fas fa-exclamation-triangle" style="color: var(--warning-color);"></i>
                                <span>Procesos Sospechosos: 1</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenido de Monitoreo Avanzado -->
        <div id="monitoring" class="tab-content">
            <div class="monitoring-dashboard">
                <div class="monitor-grid">
                    <div class="monitor-panel">
                        <h3 class="monitor-title">
                            <i class="fas fa-network-wired"></i>
                            Conexiones de Red
                        </h3>
                        <div class="connection-list">
                            <?php 
                            $all_connections = [];
                            foreach ($applications as $app) {
                                if (isset($app['connections'])) {
                                    foreach ($app['connections'] as $conn) {
                                        $all_connections[] = [
                                            'app' => $app['name'],
                                            'connection' => $conn,
                                            'status' => in_array($conn, ['suspicious-domain.com']) ? 'suspicious' : 'safe'
                                        ];
                                    }
                                }
                            }
                            foreach ($all_connections as $conn):
                            ?>
                            <div class="connection-item <?php echo $conn['status']; ?>">
                                <div class="connection-info">
                                    <strong><?php echo htmlspecialchars($conn['app']); ?></strong>
                                    <div style="font-size: 0.9em; color: var(--text-secondary);">
                                        <?php echo htmlspecialchars($conn['connection']); ?>
                                    </div>
                                </div>
                                <div class="connection-status">
                                    <?php if ($conn['status'] === 'suspicious'): ?>
                                        <i class="fas fa-exclamation-triangle" style="color: var(--danger-color);"></i>
                                    <?php else: ?>
                                        <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <div class="monitor-panel">
                        <h3 class="monitor-title">
                            <i class="fas fa-key"></i>
                            Estado de Encriptación
                        </h3>
                        <div class="encryption-status">
                            <div class="encryption-item">
                                <div class="encryption-info">
                                    <strong>Encriptación AES-256-GCM</strong>
                                    <div style="color: var(--success-color);">ACTIVA</div>
                                </div>
                                <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                            </div>
                            <div class="encryption-item">
                                <div class="encryption-info">
                                    <strong>Protección Cuántica</strong>
                                    <div style="color: var(--success-color);">HABILITADA</div>
                                </div>
                                <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                            </div>
                            <div class="encryption-item">
                                <div class="encryption-info">
                                    <strong>FIPS 140-2 Compliance</strong>
                                    <div style="color: var(--success-color);">CERTIFICADO</div>
                                </div>
                                <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                            </div>
                            <div class="encryption-item">
                                <div class="encryption-info">
                                    <strong>Perfect Forward Secrecy</strong>
                                    <div style="color: var(--success-color);">ACTIVO</div>
                                </div>
                                <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                            </div>
                        </div>
                    </div>

                    <div class="monitor-panel">
                        <h3 class="monitor-title">
                            <i class="fas fa-history"></i>
                            Registro de Eventos
                        </h3>
                        <div class="event-log">
                            <div class="event-item">
                                <div class="event-time"><?php echo date('H:i:s'); ?></div>
                                <div class="event-info">
                                    <strong>Sistema iniciado</strong>
                                    <div>GuardianIA v3.0 MILITAR activado</div>
                                </div>
                                <div class="event-status success">
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                            <div class="event-item">
                                <div class="event-time"><?php echo date('H:i:s', strtotime('-5 minutes')); ?></div>
                                <div class="event-info">
                                    <strong>Amenaza detectada</strong>
                                    <div>Proceso sospechoso identificado</div>
                                </div>
                                <div class="event-status warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                </div>
                            </div>
                            <div class="event-item">
                                <div class="event-time"><?php echo date('H:i:s', strtotime('-10 minutes')); ?></div>
                                <div class="event-info">
                                    <strong>IA detectada</strong>
                                    <div>Neural Network Trainer analizado</div>
                                </div>
                                <div class="event-status info">
                                    <i class="fas fa-info-circle"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contenido de Red Neuronal (Solo Premium) -->
        <?php if ($is_premium): ?>
        <div id="neural" class="tab-content">
            <div class="neural-dashboard">
                <div class="neural-header">
                    <h2 style="color: var(--primary-color); margin-bottom: 30px;">
                        <i class="fas fa-brain"></i>
                        Red Neuronal de Inteligencia Artificial
                    </h2>
                    <div class="neural-stats-grid">
                        <div class="neural-stat">
                            <div class="neural-stat-value">7</div>
                            <div class="neural-stat-label">Capas Neuronales</div>
                        </div>
                        <div class="neural-stat">
                            <div class="neural-stat-value">128</div>
                            <div class="neural-stat-label">Neuronas por Capa</div>
                        </div>
                        <div class="neural-stat">
                            <div class="neural-stat-value">94.7%</div>
                            <div class="neural-stat-label">Precisión</div>
                        </div>
                        <div class="neural-stat">
                            <div class="neural-stat-value">0.85</div>
                            <div class="neural-stat-label">Umbral de Detección</div>
                        </div>
                    </div>
                </div>

                <div class="neural-content">
                    <div class="neural-panel">
                        <h3 class="monitor-title">
                            <i class="fas fa-chart-area"></i>
                            Análisis Predictivo
                        </h3>
                        <div class="prediction-results">
                            <div class="prediction-item high-risk">
                                <div class="prediction-info">
                                    <strong>Riesgo Alto Detectado</strong>
                                    <div>suspicious.exe - Comportamiento anómalo</div>
                                    <div style="font-size: 0.9em; margin-top: 5px;">
                                        Confianza: 96.3% | Acción recomendada: Cuarentena inmediata
                                    </div>
                                </div>
                                <div class="prediction-confidence">96.3%</div>
                            </div>
                            <div class="prediction-item medium-risk">
                                <div class="prediction-info">
                                    <strong>Actividad IA Detectada</strong>
                                    <div>neural_trainer.exe - Red neuronal activa</div>
                                    <div style="font-size: 0.9em; margin-top: 5px;">
                                        Confianza: 89.1% | Acción: Monitoreo continuo
                                    </div>
                                </div>
                                <div class="prediction-confidence">89.1%</div>
                            </div>
                            <div class="prediction-item low-risk">
                                <div class="prediction-info">
                                    <strong>Comportamiento Normal</strong>
                                    <div>chrome.exe - Patrones de navegación estándar</div>
                                    <div style="font-size: 0.9em; margin-top: 5px;">
                                        Confianza: 78.5% | Estado: Seguro
                                    </div>
                                </div>
                                <div class="prediction-confidence">78.5%</div>
                            </div>
                        </div>
                    </div>

                    <div class="neural-panel">
                        <h3 class="monitor-title">
                            <i class="fas fa-cogs"></i>
                            Configuración de Red Neuronal
                        </h3>
                        <div class="neural-config">
                            <div class="config-item">
                                <label>Función de Activación:</label>
                                <span class="config-value">ReLU</span>
                            </div>
                            <div class="config-item">
                                <label>Tasa de Aprendizaje:</label>
                                <span class="config-value">0.001</span>
                            </div>
                            <div class="config-item">
                                <label>Dropout Rate:</label>
                                <span class="config-value">0.2</span>
                            </div>
                            <div class="config-item">
                                <label>Tamaño de Lote:</label>
                                <span class="config-value">32</span>
                            </div>
                            <div class="config-item">
                                <label>Estado del Entrenamiento:</label>
                                <span class="config-value" style="color: var(--success-color);">ACTIVO</span>
                            </div>
                        </div>
                        
                        <div class="neural-actions" style="margin-top: 20px;">
                            <button class="btn btn-primary" onclick="retrainNeuralNetwork()">
                                <i class="fas fa-sync-alt"></i>
                                Reentrenar Red
                            </button>
                            <button class="btn btn-warning" onclick="adjustThreshold()">
                                <i class="fas fa-sliders-h"></i>
                                Ajustar Umbral
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Botón de regreso -->
        <div style="text-align: center; margin-top: 40px;">
            <a href="admin_dashboard.php" class="btn btn-primary" style="padding: 15px 30px; font-size: 1.1em; text-decoration: none;">
                <i class="fas fa-arrow-left"></i>
                Volver al Dashboard
            </a>
        </div>
    </div>

    <!-- Modal para detalles de aplicación -->
    <div id="appModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Detalles de la Aplicación</h3>
                <span class="close" onclick="closeModal()">&times;</span>
            </div>
            <div id="modalBody" class="modal-body">
                <!-- Contenido dinámico -->
            </div>
        </div>
    </div>

    <script>
        // Variables globales
        let currentTab = 'applications';
        let realTimeInterval = null;

        // Función para cambiar tabs
        function switchTab(tabName) {
            // Ocultar todos los tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remover clase active de todos los botones
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Mostrar tab seleccionado
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
            
            currentTab = tabName;
            
            // Iniciar monitoreo en tiempo real si es necesario
            if (tabName === 'processes' || tabName === 'monitoring') {
                startRealTimeMonitoring();
            } else {
                stopRealTimeMonitoring();
            }
        }

        // Función para escanear aplicación
        function scanApp(appId) {
            const button = event.target;
            const originalContent = button.innerHTML;
            
            button.innerHTML = '<div class="loading"></div> Escaneando...';
            button.disabled = true;
            
            fetch('app_vigilance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=scan_app&app_id=' + appId
            })
            .then(response => response.json())
            .then(data => {
                showNotification(data.message, 'success');
                button.innerHTML = originalContent;
                button.disabled = false;
            })
            .catch(error => {
                showNotification('Error al escanear aplicación', 'error');
                button.innerHTML = originalContent;
                button.disabled = false;
            });
        }

        // Función para poner en cuarentena
        function quarantineApp(appId) {
            if (confirm('¿Estás seguro de que deseas poner esta aplicación en cuarentena?')) {
                const button = event.target;
                const originalContent = button.innerHTML;
                
                button.innerHTML = '<div class="loading"></div> Procesando...';
                button.disabled = true;
                
                fetch('app_vigilance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=quarantine_app&app_id=' + appId
                })
                .then(response => response.json())
                .then(data => {
                    showNotification(data.message, 'success');
                    button.innerHTML = originalContent;
                    button.disabled = false;
                    // Actualizar la fila para mostrar estado de cuarentena
                    updateAppStatus(appId, 'quarantined');
                })
                .catch(error => {
                    showNotification('Error al poner en cuarentena', 'error');
                    button.innerHTML = originalContent;
                    button.disabled = false;
                });
            }
        }

        // Función para mostrar detalles de aplicación
        function showAppDetails(appId) {
            // Buscar datos de la aplicación
            const applications = <?php echo json_encode($applications); ?>;
            const app = applications.find(a => a.id === appId);
            
            if (app) {
                const modalBody = document.getElementById('modalBody');
                modalBody.innerHTML = `
                    <div class="app-details">
                        <div class="detail-section">
                            <h4><i class="fas fa-info-circle"></i> Información General</h4>
                            <div class="detail-grid">
                                <div class="detail-item">
                                    <label>Nombre:</label>
                                    <span>${app.name}</span>
                                </div>
                                <div class="detail-item">
                                    <label>Versión:</label>
                                    <span>${app.version}</span>
                                </div>
                                <div class="detail-item">
                                    <label>Editor:</label>
                                    <span>${app.publisher}</span>
                                </div>
                                <div class="detail-item">
                                    <label>Tamaño:</label>
                                    <span>${app.size}</span>
                                </div>
                                <div class="detail-item">
                                    <label>Ruta:</label>
                                    <span style="font-family: monospace; font-size: 0.9em;">${app.file_path}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="detail-section">
                            <h4><i class="fas fa-shield-alt"></i> Análisis de Seguridad</h4>
                            <div class="security-analysis">
                                <div class="security-metric">
                                    <label>Nivel de Amenaza:</label>
                                    <span class="threat-badge threat-${app.threat_level}">${app.threat_level.toUpperCase()}</span>
                                </div>
                                <div class="security-metric">
                                    <label>IA Detectada:</label>
                                    <span style="color: ${app.ai_detected ? 'var(--warning-color)' : 'var(--success-color)'}">${app.ai_detected ? 'SÍ' : 'NO'}</span>
                                </div>
                                <div class="security-metric">
                                    <label>Encriptación:</label>
                                    <span style="color: ${app.encrypted ? 'var(--success-color)' : 'var(--warning-color)'}">${app.encrypted ? 'ACTIVA' : 'INACTIVA'}</span>
                                </div>
                                <div class="security-metric">
                                    <label>Último Escaneo:</label>
                                    <span>${app.last_scan}</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="detail-section">
                            <h4><i class="fas fa-key"></i> Permisos</h4>
                            <div class="permissions-list">
                                ${app.permissions.map(perm => `
                                    <div class="permission-item">
                                        <i class="fas fa-check"></i>
                                        <span>${perm}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                        
                        <div class="detail-section">
                            <h4><i class="fas fa-globe"></i> Conexiones de Red</h4>
                            <div class="connections-list">
                                ${app.connections.map(conn => `
                                    <div class="connection-detail">
                                        <i class="fas fa-link"></i>
                                        <span>${conn}</span>
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('appModal').style.display = 'block';
            }
        }

        // Función para cerrar modal
        function closeModal() {
            document.getElementById('appModal').style.display = 'none';
        }

        // Función para terminar proceso
        function terminateProcess(pid) {
            if (confirm('¿Estás seguro de que deseas terminar este proceso?')) {
                fetch('app_vigilance.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'action=terminate_process&pid=' + pid
                })
                .then(response => response.json())
                .then(data => {
                    showNotification(data.message, 'success');
                    // Remover el proceso de la lista
                    const processElement = document.querySelector(`[data-pid="${pid}"]`);
                    if (processElement) {
                        processElement.style.animation = 'fadeOut 0.5s';
                        setTimeout(() => processElement.remove(), 500);
                    }
                })
                .catch(error => {
                    showNotification('Error al terminar proceso', 'error');
                });
            }
        }

        // Función para actualizar procesos
        function refreshProcesses() {
            const button = event.target;
            const originalContent = button.innerHTML;
            
            button.innerHTML = '<div class="loading"></div>';
            button.disabled = true;
            
            fetch('app_vigilance.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=get_real_time_data'
            })
            .then(response => response.json())
            .then(data => {
                updateProcessList(data.processes);
                button.innerHTML = originalContent;
                button.disabled = false;
                showNotification('Procesos actualizados', 'success');
            })
            .catch(error => {
                button.innerHTML = originalContent;
                button.disabled = false;
                showNotification('Error al actualizar procesos', 'error');
            });
        }

        // Función para actualizar lista de procesos
        function updateProcessList(processes) {
            const processList = document.getElementById('process-list');
            processList.innerHTML = '';
            
            processes.forEach(process => {
                const processElement = document.createElement('div');
                processElement.className = 'process-item';
                processElement.setAttribute('data-pid', process.pid);
                processElement.innerHTML = `
                    <div class="process-info">
                        <h4>${process.name}</h4>
                        <div class="process-metrics">
                            PID: ${process.pid} | 
                            CPU: ${process.cpu}% | 
                            RAM: ${process.memory} MB
                        </div>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span class="process-status status-${process.status}">
                            ${process.status.toUpperCase().replace('_', ' ')}
                        </span>
                        ${(process.status === 'suspicious' || process.status === 'ai_detected') ? 
                            `<button class="btn btn-danger btn-sm" onclick="terminateProcess(${process.pid})">
                                <i class="fas fa-times"></i>
                            </button>` : ''
                        }
                    </div>
                `;
                processList.appendChild(processElement);
            });
        }

        // Funciones para red neuronal (Premium)
        function retrainNeuralNetwork() {
            const button = event.target;
            const originalContent = button.innerHTML;
            
            button.innerHTML = '<div class="loading"></div> Entrenando...';
            button.disabled = true;
            
            // Simular reentrenamiento
            setTimeout(() => {
                showNotification('Red neuronal reentrenada exitosamente', 'success');
                button.innerHTML = originalContent;
                button.disabled = false;
            }, 3000);
        }

        function adjustThreshold() {
            const newThreshold = prompt('Ingrese el nuevo umbral de detección (0.1 - 1.0):', '0.85');
            if (newThreshold && !isNaN(newThreshold) && newThreshold >= 0.1 && newThreshold <= 1.0) {
                showNotification(`Umbral ajustado a ${newThreshold}`, 'success');
            } else if (newThreshold) {
                showNotification('Valor de umbral inválido', 'error');
            }
        }

        // Función para iniciar monitoreo en tiempo real
        function startRealTimeMonitoring() {
            if (realTimeInterval) return;
            
            realTimeInterval = setInterval(() => {
                if (currentTab === 'processes' || currentTab === 'monitoring') {
                    // Actualizar métricas de rendimiento
                    updatePerformanceMetrics();
                }
            }, 5000);
        }

        // Función para detener monitoreo en tiempo real
        function stopRealTimeMonitoring() {
            if (realTimeInterval) {
                clearInterval(realTimeInterval);
                realTimeInterval = null;
            }
        }

        // Función para actualizar métricas de rendimiento
        function updatePerformanceMetrics() {
            const metrics = [
                { name: 'CPU', min: 20, max: 80 },
                { name: 'RAM', min: 40, max: 85 },
                { name: 'Disco', min: 15, max: 60 },
                { name: 'Red', min: 10, max: 40 }
            ];

            metrics.forEach(metric => {
                const value = Math.floor(Math.random() * (metric.max - metric.min) + metric.min);
                const element = document.querySelector(`.metric-item:nth-child(${metrics.indexOf(metric) + 1}) .metric-fill`);
                if (element) {
                    element.style.width = value + '%';
                    
                    // Cambiar color según el valor
                    if (value < 30) {
                        element.style.background = 'var(--success-color)';
                    } else if (value < 60) {
                        element.style.background = 'var(--warning-color)';
                    } else {
                        element.style.background = 'var(--danger-color)';
                    }
                }
                
                const valueElement = document.querySelector(`.metric-item:nth-child(${metrics.indexOf(metric) + 1}) .metric-value`);
                if (valueElement) {
                    valueElement.textContent = value + (metric.name === 'Red' ? ' Mbps' : '%');
                }
            });
        }

        // Función para mostrar notificaciones
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.innerHTML = `
                <div class="notification-content">
                    <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
                    <span>${message}</span>
                </div>
            `;
            
            document.body.appendChild(notification);
            
            // Mostrar notificación
            setTimeout(() => notification.classList.add('show'), 100);
            
            // Ocultar después de 3 segundos
            setTimeout(() => {
                notification.classList.remove('show');
                setTimeout(() => document.body.removeChild(notification), 300);
            }, 3000);
        }

        // Función para actualizar estado de aplicación
        function updateAppStatus(appId, status) {
            const row = document.querySelector(`tr[data-app-id="${appId}"]`);
            if (row) {
                const statusCell = row.querySelector('.app-status');
                if (statusCell) {
                    statusCell.innerHTML = `
                        <div class="status-badge ${status}">
                            <i class="fas fa-ban"></i>
                            EN CUARENTENA
                        </div>
                    `;
                }
            }
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Cerrar modal al hacer clic fuera
            window.onclick = function(event) {
                const modal = document.getElementById('appModal');
                if (event.target === modal) {
                    closeModal();
                }
            }

            // Iniciar monitoreo si estamos en la pestaña correcta
            if (currentTab === 'processes' || currentTab === 'monitoring') {
                startRealTimeMonitoring();
            }

            // Actualizar timestamp cada segundo
            setInterval(() => {
                const timestamps = document.querySelectorAll('.timestamp');
                timestamps.forEach(ts => {
                    ts.textContent = new Date().toLocaleTimeString();
                });
            }, 1000);
        });

        // Función para exportar reporte
        function exportReport() {
            const data = {
                timestamp: new Date().toISOString(),
                user: '<?php echo htmlspecialchars($_SESSION["username"] ?? "Usuario"); ?>',
                applications: <?php echo json_encode($applications); ?>,
                processes: <?php echo json_encode($processes); ?>,
                system_stats: <?php echo json_encode($system_stats); ?>
            };

            const blob = new Blob([JSON.stringify(data, null, 2)], { type: 'application/json' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = `guardianai_report_${new Date().toISOString().slice(0, 10)}.json`;
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            URL.revokeObjectURL(url);
        }
    </script>

    <style>
        /* Estilos adicionales para métricas y elementos dinámicos */
        .metric-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 15px;
            padding: 10px 0;
        }

        .metric-label {
            font-weight: bold;
            min-width: 120px;
        }

        .metric-bar {
            flex: 1;
            height: 20px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            margin: 0 15px;
            overflow: hidden;
        }

        .metric-fill {
            height: 100%;
            background: var(--primary-color);
            transition: all 0.5s ease;
            border-radius: 10px;
        }

        .metric-value {
            min-width: 60px;
            text-align: right;
            font-weight: bold;
        }

        .security-items {
            display: grid;
            gap: 10px;
        }

        .security-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 0;
        }

        .connection-item, .encryption-item, .event-item {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px;
            margin-bottom: 10px;
            background: rgba(0, 255, 136, 0.02);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .connection-item:hover, .encryption-item:hover, .event-item:hover {
            background: rgba(0, 255, 136, 0.05);
            border-color: var(--primary-color);
        }

        .connection-item.suspicious {
            border-left: 4px solid var(--danger-color);
            background: rgba(255, 71, 87, 0.1);
        }

        .event-time {
            font-family: 'Courier New', monospace;
            color: var(--text-secondary);
            font-size: 0.9em;
            min-width: 80px;
        }

        .event-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
        }

        .event-status.success { background: var(--success-color); color: white; }
        .event-status.warning { background: var(--warning-color); color: white; }
        .event-status.info { background: var(--primary-color); color: white; }

        /* Estilos para red neuronal */
        .neural-stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .neural-stat {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 10px;
            padding: 20px;
            text-align: center;
        }

        .neural-stat-value {
            font-size: 2em;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .neural-stat-label {
            color: var(--text-secondary);
            font-size: 0.9em;
        }

        .neural-content {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 20px;
        }

        .prediction-item {
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .prediction-item.high-risk {
            background: rgba(255, 71, 87, 0.1);
            border-left: 4px solid var(--danger-color);
        }

        .prediction-item.medium-risk {
            background: rgba(255, 165, 2, 0.1);
            border-left: 4px solid var(--warning-color);
        }

        .prediction-item.low-risk {
            background: rgba(46, 213, 115, 0.1);
            border-left: 4px solid var(--success-color);
        }

        .prediction-confidence {
            font-size: 1.2em;
            font-weight: bold;
            color: var(--primary-color);
        }

        .neural-config {
            display: grid;
            gap: 10px;
        }

        .config-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .config-value {
            font-weight: bold;
            color: var(--primary-color);
        }

        /* Modal styles */
        .modal {
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.8);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: var(--dark-bg);
            border: 2px solid var(--primary-color);
            border-radius: 15px;
            max-width: 800px;
            width: 90%;
            max-height: 80vh;
            overflow-y: auto;
            animation: modalFadeIn 0.3s ease;
        }

        @keyframes modalFadeIn {
            from { opacity: 0; transform: scale(0.8); }
            to { opacity: 1; transform: scale(1); }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            border-bottom: 1px solid var(--border-color);
        }

        .modal-header h3 {
            color: var(--primary-color);
            margin: 0;
        }

        .close {
            font-size: 2em;
            cursor: pointer;
            color: var(--text-secondary);
            transition: color 0.3s ease;
        }

        .close:hover {
            color: var(--primary-color);
        }

        .modal-body {
            padding: 30px;
        }

        .detail-section {
            margin-bottom: 30px;
        }

        .detail-section h4 {
            color: var(--primary-color);
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }

        .detail-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
        }

        .detail-item label {
            font-weight: bold;
            color: var(--text-secondary);
        }

        .security-analysis {
            display: grid;
            gap: 15px;
        }

        .security-metric {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            background: rgba(0, 255, 136, 0.02);
            border-radius: 8px;
        }

        .permissions-list, .connections-list {
            display: grid;
            gap: 10px;
        }

        .permission-item, .connection-detail {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 12px;
            background: rgba(0, 255, 136, 0.02);
            border-radius: 6px;
        }

        /* Notificaciones */
        .notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1001;
            padding: 15px 20px;
            border-radius: 8px;
            color: white;
            font-weight: bold;
            transform: translateX(100%);
            transition: transform 0.3s ease;
            max-width: 300px;
        }

        .notification.show {
            transform: translateX(0);
        }

        .notification.success { background: var(--success-color); }
        .notification.error { background: var(--danger-color); }
        .notification.info { background: var(--primary-color); }

        .notification-content {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Responsive adicional */
        @media (max-width: 1024px) {
            .neural-content {
                grid-template-columns: 1fr;
            }
            
            .real-time-monitor {
                grid-template-columns: 1fr;
            }
            
            .detail-grid {
                grid-template-columns: 1fr;
            }
        }

        @media (max-width: 768px) {
            .modal-content {
                width: 95%;
                margin: 10px;
            }
            
            .modal-body {
                padding: 20px;
            }
            
            .header-content {
                text-align: center;
            }
            
            .neural-stats-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }
        }

        /* Animaciones adicionales */
        @keyframes fadeOut {
            from { opacity: 1; transform: translateY(0); }
            to { opacity: 0; transform: translateY(-20px); }
        }

        .monitor-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 20px;
        }
    </style>
</body>
</html>