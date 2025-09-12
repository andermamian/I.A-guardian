<?php
/**
 * GuardianIA v3.0 - Centro de Optimización del Usuario
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
            case 'get_system_metrics':
                $metrics = getSystemMetrics($user_id);
                $response['success'] = true;
                $response['data'] = $metrics;
                break;
                
            case 'optimize_ram':
                $result = optimizeRAM($user_id);
                logPerformanceEvent('ram_optimization', 'Optimización de RAM ejecutada', $user_id);
                $response['success'] = true;
                $response['data'] = $result;
                $response['message'] = "RAM optimizada: {$result['memory_freed']} GB liberados, mejora del {$result['improvement']}%";
                break;
                
            case 'cleanup_storage':
                $result = cleanupStorage($user_id);
                logPerformanceEvent('storage_cleanup', 'Limpieza de almacenamiento ejecutada', $user_id);
                $response['success'] = true;
                $response['data'] = $result;
                $response['message'] = "Limpieza completada: {$result['space_freed']} GB liberados, {$result['files_removed']} archivos eliminados";
                break;
                
            case 'optimize_battery':
                $result = optimizeBattery($user_id);
                logPerformanceEvent('battery_optimization', 'Optimización de batería ejecutada', $user_id);
                $response['success'] = true;
                $response['data'] = $result;
                $response['message'] = "Batería optimizada: +{$result['time_gained']}h de duración, {$result['efficiency']}% eficiencia";
                break;
                
            case 'compress_files':
                $result = compressFiles($user_id);
                logPerformanceEvent('file_compression', 'Compresión de archivos ejecutada', $user_id);
                $response['success'] = true;
                $response['data'] = $result;
                $response['message'] = "Compresión completada: {$result['space_saved']} GB ahorrados, {$result['compression_rate']}% reducción";
                break;
                
            case 'toggle_auto_optimization':
                $enabled = $_POST['enabled'] === 'true';
                updateAutoOptimizationSetting($user_id, $enabled);
                logPerformanceEvent('auto_optimization_toggle', "Optimización automática " . ($enabled ? 'activada' : 'desactivada'), $user_id);
                $response['success'] = true;
                $response['message'] = 'Optimización automática ' . ($enabled ? 'activada' : 'desactivada');
                break;
                
            case 'toggle_auto_option':
                $option = $_POST['option'] ?? '';
                $enabled = $_POST['enabled'] === 'true';
                updateAutoOptimizationOption($user_id, $option, $enabled);
                $response['success'] = true;
                $response['message'] = "$option " . ($enabled ? 'activado' : 'desactivado');
                break;
                
            case 'start_full_optimization':
                $result = startFullOptimization($user_id);
                logPerformanceEvent('full_optimization', 'Optimización completa iniciada', $user_id);
                $response['success'] = true;
                $response['data'] = $result;
                $response['message'] = 'Optimización completa iniciada';
                break;
                
            case 'complete_full_optimization':
                $result = completeFullOptimization($user_id);
                logPerformanceEvent('full_optimization_completed', 'Optimización completa finalizada', $user_id);
                $response['success'] = true;
                $response['data'] = $result;
                $response['message'] = "Optimización completada: +{$result['performance']}% rendimiento";
                break;
                
            default:
                $response['message'] = 'Acción no reconocida';
        }
    } catch (Exception $e) {
        $response['message'] = 'Error: ' . $e->getMessage();
        logEvent('ERROR', 'Error en user_performance.php: ' . $e->getMessage());
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Funciones de la base de datos
 */

function getSystemMetrics($user_id) {
    global $db;
    
    // Métricas base simuladas realistas
    $metrics = [
        'cpu' => rand(30, 60),
        'ram' => rand(50, 90),
        'storage' => rand(60, 85),
        'battery' => rand(75, 95),
        'performance_score' => 0,
        'last_optimization' => 'Nunca',
        'auto_optimization_enabled' => true,
        'optimizations_today' => 0
    ];
    
    if ($db && $db->isConnected()) {
        try {
            // Obtener métricas guardadas
            $result = $db->query(
                "SELECT config_key, config_value FROM system_config 
                 WHERE config_key LIKE 'performance_%' OR config_key LIKE 'auto_optimization_%'"
            );
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $key = str_replace('performance_', '', $row['config_key']);
                    $key = str_replace('auto_optimization_', '', $key);
                    
                    if (is_numeric($row['config_value'])) {
                        $metrics[$key] = (float)$row['config_value'];
                    } else {
                        $metrics[$key] = $row['config_value'] === 'true';
                    }
                }
            }
            
            // Obtener optimizaciones de hoy
            $result = $db->query(
                "SELECT COUNT(*) as count FROM security_events 
                 WHERE user_id = ? AND DATE(created_at) = CURDATE() 
                 AND event_type LIKE 'performance_%'",
                [$user_id]
            );
            
            if ($result && $row = $result->fetch_assoc()) {
                $metrics['optimizations_today'] = (int)$row['count'];
            }
            
            // Obtener última optimización
            $result = $db->query(
                "SELECT created_at FROM security_events 
                 WHERE user_id = ? AND event_type LIKE 'performance_%' 
                 ORDER BY created_at DESC LIMIT 1",
                [$user_id]
            );
            
            if ($result && $row = $result->fetch_assoc()) {
                $metrics['last_optimization'] = date('d/m/Y H:i', strtotime($row['created_at']));
            }
            
        } catch (Exception $e) {
            logEvent('ERROR', 'Error obteniendo métricas de rendimiento: ' . $e->getMessage());
        }
    }
    
    // Calcular score de rendimiento
    $metrics['performance_score'] = calculatePerformanceScore($metrics);
    
    // Simular variación en tiempo real
    $metrics['cpu'] = max(20, min(80, $metrics['cpu'] + rand(-5, 5)));
    $metrics['ram'] = max(40, min(95, $metrics['ram'] + rand(-3, 3)));
    $metrics['storage'] = max(50, min(90, $metrics['storage'] + rand(-2, 2)));
    $metrics['battery'] = max(70, min(100, $metrics['battery'] + rand(-1, 1)));
    
    return $metrics;
}

function calculatePerformanceScore($metrics) {
    $cpu_score = 100 - $metrics['cpu'];
    $ram_score = 100 - $metrics['ram'];
    $storage_score = 100 - $metrics['storage'];
    $battery_score = $metrics['battery'];
    
    return round(($cpu_score + $ram_score + $storage_score + $battery_score) / 4);
}

function optimizeRAM($user_id) {
    $memory_freed = round(rand(10, 30) / 10, 1); // 1.0 - 3.0 GB
    $improvement = rand(15, 35); // 15-35%
    
    // Guardar resultado en BD
    saveOptimizationResult($user_id, 'ram_optimization', [
        'memory_freed' => $memory_freed,
        'improvement' => $improvement
    ]);
    
    return [
        'memory_freed' => $memory_freed,
        'improvement' => $improvement
    ];
}

function cleanupStorage($user_id) {
    $space_freed = round(rand(20, 50) / 10, 1); // 2.0 - 5.0 GB
    $files_removed = rand(500, 1500);
    
    // Guardar resultado en BD
    saveOptimizationResult($user_id, 'storage_cleanup', [
        'space_freed' => $space_freed,
        'files_removed' => $files_removed
    ]);
    
    return [
        'space_freed' => $space_freed,
        'files_removed' => $files_removed
    ];
}

function optimizeBattery($user_id) {
    $time_gained = round(rand(15, 35) / 10, 1); // 1.5 - 3.5 horas
    $efficiency = rand(85, 95);
    
    // Guardar resultado en BD
    saveOptimizationResult($user_id, 'battery_optimization', [
        'time_gained' => $time_gained,
        'efficiency' => $efficiency
    ]);
    
    return [
        'time_gained' => $time_gained,
        'efficiency' => $efficiency
    ];
}

function compressFiles($user_id) {
    $space_saved = round(rand(8, 20) / 10, 1); // 0.8 - 2.0 GB
    $compression_rate = rand(35, 55); // 35-55%
    $files_compressed = rand(300, 800);
    
    // Guardar resultado en BD
    saveOptimizationResult($user_id, 'file_compression', [
        'space_saved' => $space_saved,
        'compression_rate' => $compression_rate,
        'files_compressed' => $files_compressed
    ]);
    
    return [
        'space_saved' => $space_saved,
        'compression_rate' => $compression_rate,
        'files_compressed' => $files_compressed
    ];
}

function startFullOptimization($user_id) {
    $optimization_id = 'OPT_' . uniqid();
    
    // Registrar inicio de optimización completa
    if ($GLOBALS['db'] && $GLOBALS['db']->isConnected()) {
        try {
            $GLOBALS['db']->query(
                "INSERT INTO security_events (user_id, event_type, description, severity, ip_address, created_at) 
                 VALUES (?, 'performance_full_optimization', ?, 'low', ?, NOW())",
                [
                    $user_id,
                    "Optimización completa iniciada: $optimization_id",
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]
            );
        } catch (Exception $e) {
            logEvent('ERROR', 'Error registrando optimización completa: ' . $e->getMessage());
        }
    }
    
    return ['optimization_id' => $optimization_id];
}

function completeFullOptimization($user_id) {
    $improvements = [
        'performance' => rand(20, 35),
        'memory' => round(rand(20, 40) / 10, 1),
        'storage' => round(rand(30, 50) / 10, 1),
        'battery' => round(rand(20, 40) / 10, 1)
    ];
    
    // Guardar resultados de optimización completa
    saveOptimizationResult($user_id, 'full_optimization', $improvements);
    
    // Actualizar score de rendimiento
    updatePerformanceScore($user_id, $improvements['performance']);
    
    return $improvements;
}

function saveOptimizationResult($user_id, $type, $data) {
    global $db;
    
    if ($db && $db->isConnected()) {
        try {
            $db->query(
                "INSERT INTO security_events (user_id, event_type, description, severity, event_data, ip_address, created_at) 
                 VALUES (?, ?, ?, 'low', ?, ?, NOW())",
                [
                    $user_id,
                    "performance_$type",
                    "Optimización ejecutada: $type",
                    json_encode($data),
                    $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]
            );
        } catch (Exception $e) {
            logEvent('ERROR', 'Error guardando resultado de optimización: ' . $e->getMessage());
        }
    }
}

function updateAutoOptimizationSetting($user_id, $enabled) {
    global $db;
    
    if ($db && $db->isConnected()) {
        try {
            $db->query(
                "INSERT INTO system_config (config_key, config_value, config_type, description, updated_at) 
                 VALUES ('auto_optimization_enabled', ?, 'boolean', 'Configuración de optimización automática', NOW()) 
                 ON DUPLICATE KEY UPDATE config_value = ?, updated_at = NOW()",
                [$enabled ? 'true' : 'false', $enabled ? 'true' : 'false']
            );
        } catch (Exception $e) {
            logEvent('ERROR', 'Error actualizando configuración de optimización automática: ' . $e->getMessage());
        }
    }
}

function updateAutoOptimizationOption($user_id, $option, $enabled) {
    global $db;
    
    if ($db && $db->isConnected()) {
        try {
            $config_key = "auto_optimization_" . str_replace(' ', '_', strtolower($option));
            $db->query(
                "INSERT INTO system_config (config_key, config_value, config_type, description, updated_at) 
                 VALUES (?, ?, 'boolean', ?, NOW()) 
                 ON DUPLICATE KEY UPDATE config_value = ?, updated_at = NOW()",
                [
                    $config_key,
                    $enabled ? 'true' : 'false',
                    "Opción de optimización automática: $option",
                    $enabled ? 'true' : 'false'
                ]
            );
        } catch (Exception $e) {
            logEvent('ERROR', 'Error actualizando opción de optimización: ' . $e->getMessage());
        }
    }
}

function updatePerformanceScore($user_id, $improvement) {
    global $db;
    
    if ($db && $db->isConnected()) {
        try {
            // Obtener score actual
            $result = $db->query(
                "SELECT config_value FROM system_config WHERE config_key = 'performance_score'"
            );
            
            $current_score = 85; // Default
            if ($result && $row = $result->fetch_assoc()) {
                $current_score = (int)$row['config_value'];
            }
            
            $new_score = min(100, $current_score + $improvement);
            
            $db->query(
                "INSERT INTO system_config (config_key, config_value, config_type, description, updated_at) 
                 VALUES ('performance_score', ?, 'integer', 'Score de rendimiento del sistema', NOW()) 
                 ON DUPLICATE KEY UPDATE config_value = ?, updated_at = NOW()",
                [$new_score, $new_score]
            );
        } catch (Exception $e) {
            logEvent('ERROR', 'Error actualizando score de rendimiento: ' . $e->getMessage());
        }
    }
}

function logPerformanceEvent($event_type, $description, $user_id) {
    logSecurityEvent("performance_$event_type", $description, 'low', $user_id);
    logMilitaryEvent('PERFORMANCE_OPTIMIZATION', $description, 'UNCLASSIFIED');
}

function getAutoOptimizationSettings($user_id) {
    global $db;
    
    $settings = [
        'enabled' => true,
        'limpieza_diaria' => true,
        'optimización_de_ram' => true,
        'modo_ahorro_de_energía' => false,
        'compresión_inteligente' => true,
        'desfragmentación' => false,
        'análisis_predictivo' => true
    ];
    
    if ($db && $db->isConnected()) {
        try {
            $result = $db->query(
                "SELECT config_key, config_value FROM system_config 
                 WHERE config_key LIKE 'auto_optimization_%'"
            );
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $key = str_replace('auto_optimization_', '', $row['config_key']);
                    $settings[$key] = $row['config_value'] === 'true';
                }
            }
        } catch (Exception $e) {
            logEvent('ERROR', 'Error obteniendo configuraciones de optimización: ' . $e->getMessage());
        }
    }
    
    return $settings;
}

// Obtener datos iniciales
$system_metrics = getSystemMetrics($user_id);
$auto_settings = getAutoOptimizationSettings($user_id);

// Log de acceso a la página
logSecurityEvent('performance_page_access', 'Usuario accedió al centro de optimización', 'low', $user_id);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Optimización - GuardianIA</title>
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
            background: var(--info-gradient);
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

        /* Performance Header */
        .performance-header {
            background: var(--info-gradient);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .performance-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            animation: performanceShine 4s infinite;
        }

        @keyframes performanceShine {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .performance-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .performance-subtitle {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 1.5rem;
        }

        .performance-score {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 255, 255, 0.2);
            padding: 0.75rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            color: white;
        }

        .score-number {
            font-size: 1.5rem;
            font-weight: 800;
            animation: scoreCounter 2s ease-out;
        }

        @keyframes scoreCounter {
            from { opacity: 0; transform: scale(0.5); }
            to { opacity: 1; transform: scale(1); }
        }

        /* System Metrics */
        .system-metrics {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .metric-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: var(--card-padding);
            position: relative;
            overflow: hidden;
            transition: all var(--animation-speed) ease;
        }

        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px var(--shadow-color);
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .metric-card.cpu::before {
            background: var(--info-gradient);
        }

        .metric-card.ram::before {
            background: var(--warning-gradient);
        }

        .metric-card.storage::before {
            background: var(--danger-gradient);
        }

        .metric-card.battery::before {
            background: var(--success-gradient);
        }

        .metric-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .metric-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: white;
        }

        .metric-value {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .metric-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 1rem;
        }

        .metric-bar {
            width: 100%;
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .metric-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 1s ease;
        }

        .metric-fill.good {
            background: var(--success-gradient);
        }

        .metric-fill.warning {
            background: var(--warning-gradient);
        }

        .metric-fill.danger {
            background: var(--danger-gradient);
        }

        .metric-status {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        /* Optimization Tools */
        .optimization-tools {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .tool-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: var(--card-padding);
            transition: all var(--animation-speed) ease;
        }

        .tool-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px var(--shadow-color);
        }

        .tool-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .tool-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .tool-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .tool-description {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .tool-stats {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
        }

        .stat {
            text-align: center;
        }

        .stat-value {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--text-secondary);
        }

        .tool-button {
            width: 100%;
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
            justify-content: center;
            gap: 0.5rem;
        }

        .tool-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .tool-button.success {
            background: var(--success-gradient);
        }

        .tool-button.warning {
            background: var(--warning-gradient);
        }

        .tool-button.info {
            background: var(--info-gradient);
        }

        .tool-button.danger {
            background: var(--danger-gradient);
        }

        /* Auto Optimization */
        .auto-optimization {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: var(--card-padding);
            margin-bottom: 2rem;
        }

        .auto-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .auto-title {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .auto-toggle {
            display: flex;
            align-items: center;
            gap: 1rem;
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

        .auto-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .auto-option {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
        }

        .auto-option:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .auto-option.active {
            background: rgba(67, 233, 123, 0.1);
            border: 1px solid rgba(67, 233, 123, 0.3);
        }

        .option-checkbox {
            width: 20px;
            height: 20px;
            border: 2px solid var(--border-color);
            border-radius: 4px;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all var(--animation-speed) ease;
        }

        .auto-option.active .option-checkbox {
            background: var(--success-gradient);
            border-color: transparent;
        }

        .option-checkbox i {
            color: white;
            font-size: 0.8rem;
            opacity: 0;
            transition: opacity var(--animation-speed) ease;
        }

        .auto-option.active .option-checkbox i {
            opacity: 1;
        }

        .option-content {
            flex: 1;
        }

        .option-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .option-description {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        /* Progress Section */
        .optimization-progress {
            margin: 2rem 0;
            display: none;
        }

        .optimization-progress.active {
            display: block;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .progress-title {
            font-size: 1.2rem;
            font-weight: 600;
        }

        .progress-percentage {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--success-gradient);
        }

        .progress-bar {
            width: 100%;
            height: 12px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 1rem;
        }

        .progress-fill {
            height: 100%;
            background: var(--success-gradient);
            border-radius: 6px;
            transition: width 0.3s ease;
            width: 0%;
        }

        .progress-text {
            text-align: center;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        .progress-steps {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }

        .progress-step {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            border-radius: 6px;
            transition: all var(--animation-speed) ease;
        }

        .progress-step.active {
            background: rgba(67, 233, 123, 0.1);
        }

        .progress-step.completed {
            background: rgba(67, 233, 123, 0.2);
        }

        .step-icon {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            background: rgba(255, 255, 255, 0.2);
            color: var(--text-secondary);
        }

        .progress-step.active .step-icon {
            background: var(--warning-gradient);
            color: white;
        }

        .progress-step.completed .step-icon {
            background: var(--success-gradient);
            color: white;
        }

        .step-text {
            font-size: 0.9rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .system-metrics {
                grid-template-columns: 1fr;
            }

            .optimization-tools {
                grid-template-columns: 1fr;
            }

            .auto-options {
                grid-template-columns: 1fr;
            }

            .performance-title {
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
                <li><a href="user_security.php" class="nav-link">Protección</a></li>
                <li><a href="#" class="nav-link active">Optimización</a></li>
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
        <!-- Performance Header -->
        <div class="performance-header">
            <h1 class="performance-title">⚡ Optimizador Inteligente</h1>
            <p class="performance-subtitle">
                Maximiza el rendimiento de tu sistema con IA avanzada
            </p>
            <div class="performance-score">
                <i class="fas fa-tachometer-alt"></i>
                <span>Score de Rendimiento: </span>
                <span class="score-number"><?php echo $system_metrics['performance_score']; ?></span>
                <span>/100</span>
            </div>
        </div>

        <!-- System Metrics -->
        <div class="system-metrics">
            <div class="metric-card cpu">
                <div class="metric-header">
                    <div>
                        <div class="metric-value" style="color: #4facfe;"><?php echo $system_metrics['cpu']; ?>%</div>
                        <div class="metric-label">Uso de CPU</div>
                    </div>
                    <div class="metric-icon" style="background: var(--info-gradient);">
                        <i class="fas fa-microchip"></i>
                    </div>
                </div>
                <div class="metric-bar">
                    <div class="metric-fill <?php echo $system_metrics['cpu'] < 50 ? 'good' : ($system_metrics['cpu'] < 80 ? 'warning' : 'danger'); ?>" style="width: <?php echo $system_metrics['cpu']; ?>%"></div>
                </div>
                <div class="metric-status">
                    <?php echo $system_metrics['cpu'] < 50 ? 'Rendimiento óptimo' : ($system_metrics['cpu'] < 80 ? 'Se puede optimizar' : 'Optimización recomendada'); ?>
                </div>
            </div>

            <div class="metric-card ram">
                <div class="metric-header">
                    <div>
                        <div class="metric-value" style="color: #ffa726;"><?php echo $system_metrics['ram']; ?>%</div>
                        <div class="metric-label">Memoria RAM</div>
                    </div>
                    <div class="metric-icon" style="background: var(--warning-gradient);">
                        <i class="fas fa-memory"></i>
                    </div>
                </div>
                <div class="metric-bar">
                    <div class="metric-fill <?php echo $system_metrics['ram'] < 50 ? 'good' : ($system_metrics['ram'] < 80 ? 'warning' : 'danger'); ?>" style="width: <?php echo $system_metrics['ram']; ?>%"></div>
                </div>
                <div class="metric-status">
                    <?php echo $system_metrics['ram'] < 50 ? 'Rendimiento óptimo' : ($system_metrics['ram'] < 80 ? 'Se puede optimizar' : 'Optimización recomendada'); ?>
                </div>
            </div>

            <div class="metric-card storage">
                <div class="metric-header">
                    <div>
                        <div class="metric-value" style="color: #fa709a;"><?php echo $system_metrics['storage']; ?>%</div>
                        <div class="metric-label">Almacenamiento</div>
                    </div>
                    <div class="metric-icon" style="background: var(--danger-gradient);">
                        <i class="fas fa-hdd"></i>
                    </div>
                </div>
                <div class="metric-bar">
                    <div class="metric-fill <?php echo $system_metrics['storage'] < 50 ? 'good' : ($system_metrics['storage'] < 80 ? 'warning' : 'danger'); ?>" style="width: <?php echo $system_metrics['storage']; ?>%"></div>
                </div>
                <div class="metric-status">
                    <?php echo $system_metrics['storage'] < 50 ? 'Rendimiento óptimo' : ($system_metrics['storage'] < 80 ? 'Se puede optimizar' : 'Limpieza recomendada'); ?>
                </div>
            </div>

            <div class="metric-card battery">
                <div class="metric-header">
                    <div>
                        <div class="metric-value" style="color: #43e97b;"><?php echo $system_metrics['battery']; ?>%</div>
                        <div class="metric-label">Batería</div>
                    </div>
                    <div class="metric-icon" style="background: var(--success-gradient);">
                        <i class="fas fa-battery-three-quarters"></i>
                    </div>
                </div>
                <div class="metric-bar">
                    <div class="metric-fill good" style="width: <?php echo $system_metrics['battery']; ?>%"></div>
                </div>
                <div class="metric-status">Excelente duración</div>
            </div>
        </div>

        <!-- Optimization Tools -->
        <div class="optimization-tools">
            <div class="tool-card">
                <div class="tool-header">
                    <div class="tool-icon" style="background: var(--warning-gradient);">
                        <i class="fas fa-memory"></i>
                    </div>
                    <div>
                        <div class="tool-title">Optimizador de RAM</div>
                        <div class="tool-description">
                            Libera memoria no utilizada y optimiza procesos
                        </div>
                    </div>
                </div>
                <div class="tool-stats">
                    <div class="stat">
                        <div class="stat-value" style="color: #ffa726;">2.4 GB</div>
                        <div class="stat-label">Disponible</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value" style="color: #ffa726;">87.3%</div>
                        <div class="stat-label">Eficiencia</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value" style="color: #ffa726;">+18%</div>
                        <div class="stat-label">Mejora</div>
                    </div>
                </div>
                <button class="tool-button warning" onclick="optimizeRAM()">
                    <i class="fas fa-magic"></i>
                    Optimizar RAM
                </button>
            </div>

            <div class="tool-card">
                <div class="tool-header">
                    <div class="tool-icon" style="background: var(--info-gradient);">
                        <i class="fas fa-broom"></i>
                    </div>
                    <div>
                        <div class="tool-title">Limpieza Inteligente</div>
                        <div class="tool-description">
                            Elimina archivos basura y duplicados automáticamente
                        </div>
                    </div>
                </div>
                <div class="tool-stats">
                    <div class="stat">
                        <div class="stat-value" style="color: #4facfe;">3.7 GB</div>
                        <div class="stat-label">Detectados</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value" style="color: #4facfe;">94.1%</div>
                        <div class="stat-label">Precisión</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value" style="color: #4facfe;">1,247</div>
                        <div class="stat-label">Archivos</div>
                    </div>
                </div>
                <button class="tool-button info" onclick="cleanupStorage()">
                    <i class="fas fa-trash-alt"></i>
                    Limpiar Ahora
                </button>
            </div>

            <div class="tool-card">
                <div class="tool-header">
                    <div class="tool-icon" style="background: var(--success-gradient);">
                        <i class="fas fa-battery-full"></i>
                    </div>
                    <div>
                        <div class="tool-title">Optimizador de Batería</div>
                        <div class="tool-description">
                            Extiende la duración de la batería con IA
                        </div>
                    </div>
                </div>
                <div class="tool-stats">
                    <div class="stat">
                        <div class="stat-value" style="color: #43e97b;">+2.8h</div>
                        <div class="stat-label">Ganancia</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value" style="color: #43e97b;">92%</div>
                        <div class="stat-label">Eficiencia</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value" style="color: #43e97b;">Auto</div>
                        <div class="stat-label">Modo</div>
                    </div>
                </div>
                <button class="tool-button success" onclick="optimizeBattery()">
                    <i class="fas fa-bolt"></i>
                    Optimizar Batería
                </button>
            </div>

            <div class="tool-card">
                <div class="tool-header">
                    <div class="tool-icon" style="background: var(--primary-gradient);">
                        <i class="fas fa-compress-alt"></i>
                    </div>
                    <div>
                        <div class="tool-title">Compresión Inteligente</div>
                        <div class="tool-description">
                            Reduce el tamaño de archivos sin perder calidad
                        </div>
                    </div>
                </div>
                <div class="tool-stats">
                    <div class="stat">
                        <div class="stat-value" style="color: #667eea;">45%</div>
                        <div class="stat-label">Reducción</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value" style="color: #667eea;">1.2 GB</div>
                        <div class="stat-label">Ahorrado</div>
                    </div>
                    <div class="stat">
                        <div class="stat-value" style="color: #667eea;">847</div>
                        <div class="stat-label">Archivos</div>
                    </div>
                </div>
                <button class="tool-button" onclick="compressFiles()">
                    <i class="fas fa-compress"></i>
                    Comprimir Archivos
                </button>
            </div>
        </div>

        <!-- Auto Optimization -->
        <div class="auto-optimization">
            <div class="auto-header">
                <h2 class="auto-title">🤖 Optimización Automática</h2>
                <div class="auto-toggle">
                    <span>Activar optimización automática</span>
                    <div class="toggle-switch <?php echo $auto_settings['enabled'] ? 'active' : ''; ?>" onclick="toggleAutoOptimization()">
                        <div class="toggle-slider"></div>
                    </div>
                </div>
            </div>

            <div class="auto-options">
                <div class="auto-option <?php echo $auto_settings['limpieza_diaria'] ? 'active' : ''; ?>" onclick="toggleOption(this, 'Limpieza Diaria')">
                    <div class="option-checkbox">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="option-content">
                        <div class="option-title">Limpieza Diaria</div>
                        <div class="option-description">Elimina archivos temporales automáticamente</div>
                    </div>
                </div>

                <div class="auto-option <?php echo $auto_settings['optimización_de_ram'] ? 'active' : ''; ?>" onclick="toggleOption(this, 'Optimización de RAM')">
                    <div class="option-checkbox">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="option-content">
                        <div class="option-title">Optimización de RAM</div>
                        <div class="option-description">Libera memoria cuando sea necesario</div>
                    </div>
                </div>

                <div class="auto-option <?php echo $auto_settings['modo_ahorro_de_energía'] ? 'active' : ''; ?>" onclick="toggleOption(this, 'Modo Ahorro de Energía')">
                    <div class="option-checkbox">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="option-content">
                        <div class="option-title">Modo Ahorro de Energía</div>
                        <div class="option-description">Activa automáticamente cuando la batería esté baja</div>
                    </div>
                </div>

                <div class="auto-option <?php echo $auto_settings['compresión_inteligente'] ? 'active' : ''; ?>" onclick="toggleOption(this, 'Compresión Inteligente')">
                    <div class="option-checkbox">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="option-content">
                        <div class="option-title">Compresión Inteligente</div>
                        <div class="option-description">Comprime archivos grandes automáticamente</div>
                    </div>
                </div>

                <div class="auto-option <?php echo $auto_settings['desfragmentación'] ? 'active' : ''; ?>" onclick="toggleOption(this, 'Desfragmentación')">
                    <div class="option-checkbox">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="option-content">
                        <div class="option-title">Desfragmentación</div>
                        <div class="option-description">Optimiza el disco duro semanalmente</div>
                    </div>
                </div>

                <div class="auto-option <?php echo $auto_settings['análisis_predictivo'] ? 'active' : ''; ?>" onclick="toggleOption(this, 'Análisis Predictivo')">
                    <div class="option-checkbox">
                        <i class="fas fa-check"></i>
                    </div>
                    <div class="option-content">
                        <div class="option-title">Análisis Predictivo</div>
                        <div class="option-description">Predice y previene problemas de rendimiento</div>
                    </div>
                </div>
            </div>

            <div class="optimization-progress" id="optimizationProgress">
                <div class="progress-header">
                    <div class="progress-title">Optimizando sistema...</div>
                    <div class="progress-percentage" id="progressPercentage">0%</div>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="progressFill"></div>
                </div>
                <div class="progress-text" id="progressText">Preparando optimización...</div>
                
                <div class="progress-steps">
                    <div class="progress-step" id="step1">
                        <div class="step-icon">1</div>
                        <div class="step-text">Análisis del sistema</div>
                    </div>
                    <div class="progress-step" id="step2">
                        <div class="step-icon">2</div>
                        <div class="step-text">Limpieza de archivos</div>
                    </div>
                    <div class="progress-step" id="step3">
                        <div class="step-icon">3</div>
                        <div class="step-text">Optimización de RAM</div>
                    </div>
                    <div class="progress-step" id="step4">
                        <div class="step-icon">4</div>
                        <div class="step-text">Configuración de energía</div>
                    </div>
                    <div class="progress-step" id="step5">
                        <div class="step-icon">5</div>
                        <div class="step-text">Finalización</div>
                    </div>
                </div>
            </div>

            <button class="tool-button success" onclick="startFullOptimization()" id="fullOptimizeButton">
                <i class="fas fa-rocket"></i>
                Optimización Completa
            </button>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <span id="toast-message"></span>
    </div>

    <script>
        // Variables globales con datos PHP
        let isOptimizing = false;
        let optimizationProgress = 0;
        let optimizationInterval;
        let autoOptimizationEnabled = <?php echo $auto_settings['enabled'] ? 'true' : 'false'; ?>;
        
        // Datos del servidor
        const systemMetrics = <?php echo json_encode($system_metrics); ?>;
        const autoSettings = <?php echo json_encode($auto_settings); ?>;

        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            initializePerformance();
            updateSystemMetrics();
            startRealTimeMonitoring();
            initializeAnimations();
        });

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

        // Inicializar módulo de rendimiento
        function initializePerformance() {
            console.log('Inicializando módulo de rendimiento...');
            updatePerformanceScore();
            checkAutoOptimization();
        }

        // Actualizar métricas del sistema
        async function updateSystemMetrics() {
            const result = await makeRequest('get_system_metrics');
            
            if (result.success && result.data) {
                const metrics = result.data;
                updateMetricCard('cpu', metrics.cpu, '%');
                updateMetricCard('ram', metrics.ram, '%');
                updateMetricCard('storage', metrics.storage, '%');
                updateMetricCard('battery', metrics.battery, '%');
                
                // Actualizar score de rendimiento
                document.querySelector('.score-number').textContent = metrics.performance_score;
            }
        }

        // Actualizar tarjeta de métrica
        function updateMetricCard(type, value, unit) {
            const card = document.querySelector(`.metric-card.${type}`);
            const valueElement = card.querySelector('.metric-value');
            const fillElement = card.querySelector('.metric-fill');
            const statusElement = card.querySelector('.metric-status');

            valueElement.textContent = value + unit;
            fillElement.style.width = value + '%';

            // Actualizar clase de color y estado
            fillElement.className = 'metric-fill';
            if (value < 50) {
                fillElement.classList.add('good');
                statusElement.textContent = 'Rendimiento óptimo';
            } else if (value < 80) {
                fillElement.classList.add('warning');
                statusElement.textContent = 'Se puede optimizar';
            } else {
                fillElement.classList.add('danger');
                statusElement.textContent = 'Optimización recomendada';
            }
        }

        // Optimizar RAM
        async function optimizeRAM() {
            if (isOptimizing) {
                showToast('Ya hay una optimización en progreso...', 'warning');
                return;
            }

            const button = event.target;
            const originalText = button.innerHTML;
            
            button.innerHTML = '<div class="loading"></div> Optimizando...';
            button.disabled = true;

            showToast('Iniciando optimización de memoria RAM...', 'info');

            const result = await makeRequest('optimize_ram');
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
                
                if (result.success) {
                    showToast(result.message, 'success');
                    updateSystemMetrics();
                } else {
                    showToast('Error: ' + result.message, 'error');
                }
            }, 3000);
        }

        // Limpiar almacenamiento
        async function cleanupStorage() {
            if (isOptimizing) {
                showToast('Ya hay una optimización en progreso...', 'warning');
                return;
            }

            const button = event.target;
            const originalText = button.innerHTML;
            
            button.innerHTML = '<div class="loading"></div> Limpiando...';
            button.disabled = true;

            showToast('Iniciando limpieza inteligente del sistema...', 'info');

            const cleanupSteps = [
                'Analizando archivos temporales...',
                'Eliminando archivos basura...',
                'Buscando duplicados...',
                'Limpiando caché del sistema...',
                'Optimizando registro...'
            ];

            let currentStep = 0;
            const stepInterval = setInterval(async () => {
                if (currentStep < cleanupSteps.length) {
                    showToast(cleanupSteps[currentStep], 'info');
                    currentStep++;
                } else {
                    clearInterval(stepInterval);
                    
                    const result = await makeRequest('cleanup_storage');
                    
                    button.innerHTML = originalText;
                    button.disabled = false;
                    
                    if (result.success) {
                        showToast(result.message, 'success');
                        updateSystemMetrics();
                    } else {
                        showToast('Error: ' + result.message, 'error');
                    }
                }
            }, 1500);
        }

        // Optimizar batería
        async function optimizeBattery() {
            if (isOptimizing) {
                showToast('Ya hay una optimización en progreso...', 'warning');
                return;
            }

            const button = event.target;
            const originalText = button.innerHTML;
            
            button.innerHTML = '<div class="loading"></div> Optimizando...';
            button.disabled = true;

            showToast('Optimizando configuración de energía...', 'info');

            const result = await makeRequest('optimize_battery');
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
                
                if (result.success) {
                    showToast(result.message, 'success');
                    updateSystemMetrics();
                } else {
                    showToast('Error: ' + result.message, 'error');
                }
            }, 2500);
        }

        // Comprimir archivos
        async function compressFiles() {
            if (isOptimizing) {
                showToast('Ya hay una optimización en progreso...', 'warning');
                return;
            }

            const button = event.target;
            const originalText = button.innerHTML;
            
            button.innerHTML = '<div class="loading"></div> Comprimiendo...';
            button.disabled = true;

            showToast('Iniciando compresión inteligente de archivos...', 'info');

            const result = await makeRequest('compress_files');
            
            setTimeout(() => {
                button.innerHTML = originalText;
                button.disabled = false;
                
                if (result.success) {
                    showToast(result.message, 'success');
                    updateSystemMetrics();
                } else {
                    showToast('Error: ' + result.message, 'error');
                }
            }, 4000);
        }

        // Toggle optimización automática
        async function toggleAutoOptimization() {
            const toggle = event.target.closest('.toggle-switch');
            autoOptimizationEnabled = !autoOptimizationEnabled;
            
            const result = await makeRequest('toggle_auto_optimization', { enabled: autoOptimizationEnabled });
            
            if (result.success) {
                if (autoOptimizationEnabled) {
                    toggle.classList.add('active');
                    showToast('Optimización automática activada', 'success');
                } else {
                    toggle.classList.remove('active');
                    showToast('Optimización automática desactivada', 'warning');
                }
            } else {
                showToast('Error: ' + result.message, 'error');
                autoOptimizationEnabled = !autoOptimizationEnabled; // Revertir
            }
        }

        // Toggle opción de auto optimización
        async function toggleOption(option, optionName) {
            option.classList.toggle('active');
            const isActive = option.classList.contains('active');
            
            const result = await makeRequest('toggle_auto_option', { 
                option: optionName, 
                enabled: isActive 
            });
            
            if (result.success) {
                showToast(result.message, isActive ? 'success' : 'info');
            } else {
                showToast('Error: ' + result.message, 'error');
                option.classList.toggle('active'); // Revertir en caso de error
            }
        }

        // Iniciar optimización completa
        async function startFullOptimization() {
            if (isOptimizing) {
                showToast('Ya hay una optimización en progreso...', 'warning');
                return;
            }

            const startResult = await makeRequest('start_full_optimization');
            
            if (!startResult.success) {
                showToast('Error iniciando optimización: ' + startResult.message, 'error');
                return;
            }

            isOptimizing = true;
            optimizationProgress = 0;
            
            const button = document.getElementById('fullOptimizeButton');
            const progressSection = document.getElementById('optimizationProgress');
            const progressFill = document.getElementById('progressFill');
            const progressText = document.getElementById('progressText');
            const progressPercentage = document.getElementById('progressPercentage');
            
            // Mostrar barra de progreso
            progressSection.classList.add('active');
            button.innerHTML = '<div class="loading"></div> Optimizando Sistema...';
            button.disabled = true;
            
            showToast('Iniciando optimización completa del sistema...', 'info');
            
            // Pasos de optimización
            const optimizationSteps = [
                { text: 'Analizando rendimiento del sistema...', step: 1 },
                { text: 'Limpiando archivos temporales y basura...', step: 2 },
                { text: 'Optimizando memoria RAM y procesos...', step: 3 },
                { text: 'Configurando ahorro de energía...', step: 4 },
                { text: 'Aplicando configuraciones finales...', step: 5 }
            ];
            
            let currentStepIndex = 0;
            
            optimizationInterval = setInterval(() => {
                if (currentStepIndex < optimizationSteps.length) {
                    const currentStep = optimizationSteps[currentStepIndex];
                    
                    // Actualizar texto y progreso
                    progressText.textContent = currentStep.text;
                    optimizationProgress = ((currentStepIndex + 1) / optimizationSteps.length) * 100;
                    progressFill.style.width = optimizationProgress + '%';
                    progressPercentage.textContent = Math.floor(optimizationProgress) + '%';
                    
                    // Actualizar pasos visuales
                    updateProgressStep(currentStep.step);
                    
                    currentStepIndex++;
                } else {
                    completeFullOptimization();
                }
            }, 2000);
        }

        // Actualizar paso de progreso
        function updateProgressStep(stepNumber) {
            // Marcar pasos anteriores como completados
            for (let i = 1; i < stepNumber; i++) {
                const step = document.getElementById(`step${i}`);
                step.classList.remove('active');
                step.classList.add('completed');
            }
            
            // Marcar paso actual como activo
            const currentStep = document.getElementById(`step${stepNumber}`);
            currentStep.classList.add('active');
            currentStep.classList.remove('completed');
        }

        // Completar optimización completa
        async function completeFullOptimization() {
            clearInterval(optimizationInterval);
            
            const result = await makeRequest('complete_full_optimization');
            
            const button = document.getElementById('fullOptimizeButton');
            const progressSection = document.getElementById('optimizationProgress');
            const progressText = document.getElementById('progressText');
            
            // Marcar último paso como completado
            const lastStep = document.getElementById('step5');
            lastStep.classList.remove('active');
            lastStep.classList.add('completed');
            
            progressText.textContent = 'Optimización completada exitosamente';
            
            setTimeout(() => {
                progressSection.classList.remove('active');
                button.innerHTML = '<i class="fas fa-rocket"></i> Optimización Completa';
                button.disabled = false;
                isOptimizing = false;
                
                // Resetear pasos
                for (let i = 1; i <= 5; i++) {
                    const step = document.getElementById(`step${i}`);
                    step.classList.remove('active', 'completed');
                }
                
                if (result.success) {
                    showToast(result.message, 'success');
                    updateSystemMetrics();
                    updatePerformanceScore();
                } else {
                    showToast('Error: ' + result.message, 'error');
                }
            }, 3000);
        }

        // Actualizar score de rendimiento
        function updatePerformanceScore() {
            const scoreElement = document.querySelector('.score-number');
            const currentScore = parseInt(scoreElement.textContent);
            const newScore = Math.min(100, currentScore + Math.floor(Math.random() * 5) + 1);
            
            // Animación de contador
            let counter = currentScore;
            const increment = (newScore - currentScore) / 20;
            
            const counterInterval = setInterval(() => {
                counter += increment;
                if (counter >= newScore) {
                    counter = newScore;
                    clearInterval(counterInterval);
                }
                scoreElement.textContent = Math.floor(counter);
            }, 50);
        }

        // Verificar optimización automática
        function checkAutoOptimization() {
            if (autoOptimizationEnabled) {
                console.log('Optimización automática habilitada');
            }
        }

        // Iniciar monitoreo en tiempo real
        function startRealTimeMonitoring() {
            setInterval(() => {
                updateSystemMetrics();
            }, 15000); // Actualizar cada 15 segundos
        }

        // Animación de entrada para las tarjetas
        function initializeAnimations() {
            const cards = document.querySelectorAll('.metric-card, .tool-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
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

        // Simulación de optimización automática
        setInterval(() => {
            if (autoOptimizationEnabled && Math.random() < 0.1) { // 10% de probabilidad cada 2 minutos
                const autoActions = [
                    'Limpieza automática de archivos temporales completada',
                    'Memoria RAM optimizada automáticamente',
                    'Configuración de energía ajustada',
                    'Archivos comprimidos automáticamente'
                ];
                
                const randomAction = autoActions[Math.floor(Math.random() * autoActions.length)];
                showToast(randomAction, 'success');
                updateSystemMetrics();
            }
        }, 120000); // Cada 2 minutos

        // Efectos de hover para las tarjetas
        document.querySelectorAll('.metric-card, .tool-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Manejo de errores
        window.addEventListener('error', function(e) {
            console.log('Error capturado:', e.message);
            showToast('Se produjo un error en el optimizador. Reintentando...', 'error');
        });

        // Cleanup al salir
        window.addEventListener('beforeunload', function() {
            if (optimizationInterval) {
                clearInterval(optimizationInterval);
            }
        });
    </script>
</body>
</html>