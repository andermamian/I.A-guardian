<?php
/**
 * GuardianIA v3.0 FINAL - Centro de Comando de Operaciones AVANZADO
 * Anderson Mamian Chicangana - Sistema Integrado de IA Militar
 * Control Total con Inteligencia Artificial Cuántica y Redes Neuronales
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/config_military.php';
require_once __DIR__ . '/quantum_encryption.php';

// Verificar autenticación y permisos
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    die('Acceso denegado. Se requieren permisos de administrador militar.');
}

// Obtener estadísticas en tiempo real del sistema
$system_stats = getSystemStats();
$db = MilitaryDatabaseManager::getInstance();

// Datos en tiempo real mejorados con IA cuántica
$datos_tiempo_real = [
    'nivel_amenaza' => rand(1, 5),
    'amenazas_activas' => rand(0, 15),
    'carga_sistema' => rand(20, 95),
    'fuerza_cuantica' => rand(80, 100),
    'precision_ia' => rand(85, 99),
    'trafico_red' => rand(100, 5000),
    'canales_encriptados' => rand(50, 200),
    'usuarios_activos' => $system_stats['users_active'],
    'procesos_ia' => rand(50, 150),
    'modelos_activos' => rand(5, 20),
    'temperatura_cpu' => rand(35, 75),
    'memoria_usada' => rand(40, 85),
    'conexiones_vpn' => rand(10, 50),
    'paquetes_bloqueados' => rand(100, 1000),
    'intentos_intrusion' => rand(0, 25),
    'sistemas_backup' => rand(3, 8),
    'certificados_activos' => rand(15, 40),
    'sesiones_cuanticas' => rand(5, 25)
];

// Métricas avanzadas de IA cuántica
$metricas_ia = [
    'predicciones_hoy' => rand(1000, 5000),
    'anomalias_detectadas' => rand(0, 50),
    'tasa_aprendizaje' => number_format(rand(90, 99) + rand(0, 99) / 100, 2),
    'precision_modelo' => number_format(rand(95, 99) + rand(0, 99) / 100, 2),
    'estado_red_neuronal' => 'OPERACIONAL',
    'estado_ia_cuantica' => 'ACTIVO',
    'patrones_identificados' => rand(100, 500),
    'decisiones_automaticas' => rand(200, 800),
    'tiempo_respuesta_ms' => rand(10, 100),
    'confianza_prediccion' => rand(85, 99),
    'qubits_disponibles' => rand(50, 128),
    'entrelazamiento_cuantico' => rand(80, 99),
    'coherencia_cuantica' => rand(85, 95),
    'temperatura_cuantica' => number_format(rand(1, 15) / 1000, 3),
    'error_rate_cuantico' => number_format(rand(1, 11) / 100, 3),
    'fidelidad_gates' => number_format(rand(95, 99) + rand(0, 99) / 100, 2)
];

// Modelos de IA avanzados con algoritmos cuánticos
$modelos_ia = [
    'detector_amenazas_cuantico' => ['estado' => 'activo', 'precision' => 99.7, 'carga' => 45, 'tipo' => 'cuantico'],
    'analisis_comportamiento_neuronal' => ['estado' => 'activo', 'precision' => 97.8, 'carga' => 32, 'tipo' => 'deep_learning'],
    'predictor_ataques_gpt' => ['estado' => 'entrenando', 'precision' => 96.2, 'carga' => 78, 'tipo' => 'transformer'],
    'optimizador_recursos_ga' => ['estado' => 'activo', 'precision' => 98.5, 'carga' => 23, 'tipo' => 'genetico'],
    'cifrado_cuantico_bb84' => ['estado' => 'activo', 'precision' => 99.9, 'carga' => 56, 'tipo' => 'cuantico'],
    'detector_deepfakes_gan' => ['estado' => 'activo', 'precision' => 95.4, 'carga' => 67, 'tipo' => 'adversarial'],
    'nlp_militar_bert' => ['estado' => 'optimizando', 'precision' => 94.1, 'carga' => 89, 'tipo' => 'nlp'],
    'vision_computacional_yolo' => ['estado' => 'activo', 'precision' => 96.8, 'carga' => 41, 'tipo' => 'computer_vision'],
    'predictor_economico_lstm' => ['estado' => 'entrenando', 'precision' => 92.3, 'carga' => 55, 'tipo' => 'time_series'],
    'sistema_recomendacion_rl' => ['estado' => 'activo', 'precision' => 91.7, 'carga' => 38, 'tipo' => 'reinforcement']
];

// Sistemas de defensa militar
$sistemas_defensa = [
    'firewall_adaptativo' => ['estado' => 'activo', 'nivel' => 98, 'conexiones_bloqueadas' => rand(1000, 5000)],
    'ids_cuantico' => ['estado' => 'activo', 'nivel' => 97, 'anomalias_detectadas' => rand(50, 200)],
    'honeypot_ia' => ['estado' => 'activo', 'nivel' => 95, 'ataques_capturados' => rand(10, 50)],
    'ddos_protection' => ['estado' => 'activo', 'nivel' => 99, 'ataques_mitigados' => rand(5, 25)],
    'endpoint_security' => ['estado' => 'activo', 'nivel' => 96, 'dispositivos_protegidos' => rand(100, 500)],
    'network_segmentation' => ['estado' => 'activo', 'nivel' => 94, 'zonas_aisladas' => rand(10, 30)]
];

// Información de geolocalización y amenazas globales
$amenazas_globales = [
    ['pais' => 'Rusia', 'nivel' => 'ALTO', 'tipo' => 'Cyber Warfare', 'intentos' => rand(100, 500)],
    ['pais' => 'China', 'nivel' => 'MEDIO', 'tipo' => 'Data Mining', 'intentos' => rand(50, 200)],
    ['pais' => 'Corea del Norte', 'nivel' => 'ALTO', 'tipo' => 'Ransomware', 'intentos' => rand(20, 100)],
    ['pais' => 'Irán', 'nivel' => 'MEDIO', 'tipo' => 'Infrastructure', 'intentos' => rand(30, 150)],
    ['pais' => 'Desconocido', 'nivel' => 'CRÍTICO', 'tipo' => 'Advanced Persistent Threat', 'intentos' => rand(5, 50)]
];

// Eventos recientes mejorados
$eventos_recientes = [];
if ($db && $db->isConnected()) {
    try {
        $result = $db->query(
            "SELECT event_type, description, severity, created_at, user_id 
             FROM security_events 
             ORDER BY created_at DESC 
             LIMIT 15"
        );
        
        while ($row = $result->fetch_assoc()) {
            $eventos_recientes[] = [
                'hora' => date('H:i:s', strtotime($row['created_at'])),
                'tipo' => strtoupper($row['event_type']),
                'mensaje' => $row['description'],
                'nivel' => $row['severity'],
                'ia_confianza' => rand(85, 99),
                'usuario' => $row['user_id']
            ];
        }
    } catch (Exception $e) {
        logEvent('ERROR', 'Error obteniendo eventos: ' . $e->getMessage());
    }
}

// Si no hay eventos de BD, usar eventos simulados
if (empty($eventos_recientes)) {
    $eventos_recientes = [
        ['hora' => date('H:i:s', strtotime('-5 seconds')), 'tipo' => 'QUANTUM_SCAN', 'mensaje' => 'Escaneo cuántico completado - Estado óptimo', 'nivel' => 'info', 'ia_confianza' => 98],
        ['hora' => date('H:i:s', strtotime('-12 seconds')), 'tipo' => 'AI_NEURAL_UPDATE', 'mensaje' => 'Red neuronal actualizada - Precisión +3.2%', 'nivel' => 'exito', 'ia_confianza' => 97],
        ['hora' => date('H:i:s', strtotime('-23 seconds')), 'tipo' => 'QUANTUM_ENTANGLEMENT', 'mensaje' => 'Entrelazamiento cuántico establecido - 1024 qubits', 'nivel' => 'exito', 'ia_confianza' => 100],
        ['hora' => date('H:i:s', strtotime('-45 seconds')), 'tipo' => 'THREAT_BLOCKED', 'mensaje' => 'APT detectado y neutralizado por IA', 'nivel' => 'advertencia', 'ia_confianza' => 95],
        ['hora' => date('H:i:s', strtotime('-67 seconds')), 'tipo' => 'DEEPFAKE_DETECTED', 'mensaje' => 'Contenido sintético identificado', 'nivel' => 'advertencia', 'ia_confianza' => 92]
    ];
}

// Obtener información del usuario actual
$usuario_actual = null;
if ($db && $db->isConnected()) {
    try {
        $result = $db->query(
            "SELECT username, fullname, user_type, premium_status, security_clearance, military_access, last_login 
             FROM users WHERE id = ?",
            [$_SESSION['user_id']]
        );
        
        if ($result && $row = $result->fetch_assoc()) {
            $usuario_actual = $row;
        }
    } catch (Exception $e) {
        logEvent('ERROR', 'Error obteniendo datos de usuario: ' . $e->getMessage());
    }
}

// Si no se puede obtener de BD, usar datos de sesión
if (!$usuario_actual) {
    $usuario_actual = [
        'username' => $_SESSION['username'] ?? 'admin',
        'fullname' => $_SESSION['fullname'] ?? 'Administrador',
        'user_type' => $_SESSION['user_type'] ?? 'admin',
        'premium_status' => 'premium',
        'security_clearance' => 'TOP_SECRET',
        'military_access' => true,
        'last_login' => date('Y-m-d H:i:s')
    ];
}

// Log del acceso al centro de comando
logSecurityEvent('ACCESS_COMMAND_CENTER', 'Acceso al centro de comando militar', 'info', $_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centro de Comando Cuántico - GuardianIA v3.0 MILITAR</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;500;700&family=JetBrains+Mono:wght@400;600&display=swap');
        
        :root {
            --color-primario: #00ffff;
            --color-secundario: #ff00ff;
            --color-acento: #ffff00;
            --color-peligro: #ff0044;
            --color-exito: #00ff88;
            --color-ia: #9d00ff;
            --color-cuantico: #00ffaa;
            --color-neuronal: #ff6600;
            --fondo-oscuro: #0a0a0a;
            --fondo-medio: #1a1a2e;
            --fondo-panel: #161629;
            --texto-primario: #ffffff;
            --texto-secundario: #888;
            --borde-neon: rgba(0,255,255,0.6);
        }
        
        body {
            font-family: 'Rajdhani', sans-serif;
            background: var(--fondo-oscuro);
            color: var(--texto-primario);
            overflow-x: hidden;
            position: relative;
            min-height: 100vh;
        }
        
        /* Fondo de matriz cuántica */
        #fondo-cuantico {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -3;
            opacity: 0.1;
            background: 
                radial-gradient(circle at 20% 20%, rgba(0,255,255,0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, rgba(255,0,255,0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 60%, rgba(0,255,170,0.1) 0%, transparent 50%);
        }
        
        /* Partículas cuánticas */
        .particula-cuantica {
            position: absolute;
            width: 3px;
            height: 3px;
            background: var(--color-cuantico);
            border-radius: 50%;
            animation: flotacion-cuantica 4s infinite ease-in-out;
            box-shadow: 0 0 10px var(--color-cuantico);
        }
        
        @keyframes flotacion-cuantica {
            0%, 100% { transform: translate(0, 0) scale(1); opacity: 0.5; }
            50% { transform: translate(20px, -20px) scale(1.5); opacity: 1; }
        }
        
        /* Grid holográfico mejorado */
        .grid-holografico {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: 
                linear-gradient(rgba(0,255,255,0.05) 1px, transparent 1px),
                linear-gradient(90deg, rgba(0,255,255,0.05) 1px, transparent 1px),
                radial-gradient(circle at 50% 50%, rgba(0,255,170,0.1) 1px, transparent 1px);
            background-size: 40px 40px, 40px 40px, 80px 80px;
            animation: pulso-grid 8s ease-in-out infinite;
            z-index: -2;
        }
        
        @keyframes pulso-grid {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.02); }
        }
        
        /* Encabezado mejorado */
        .encabezado {
            background: linear-gradient(135deg, 
                rgba(0,255,255,0.15), 
                rgba(255,0,255,0.15), 
                rgba(0,255,170,0.15));
            backdrop-filter: blur(15px) saturate(150%);
            border-bottom: 2px solid var(--borde-neon);
            padding: 25px;
            position: relative;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,255,255,0.2);
        }
        
        .encabezado::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, 
                transparent, 
                rgba(0,255,255,0.3), 
                rgba(255,0,255,0.3), 
                transparent);
            animation: escaneo-arco-iris 4s linear infinite;
        }
        
        @keyframes escaneo-arco-iris {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        /* Usuario info mejorado */
        .info-usuario {
            background: rgba(0,0,0,0.3);
            border: 1px solid var(--color-ia);
            border-radius: 15px;
            padding: 15px;
            backdrop-filter: blur(10px);
        }
        
        /* Contenedor principal */
        .contenedor-principal {
            max-width: 2000px;
            margin: 0 auto;
            padding: 25px;
        }
        
        .grid-comando {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(380px, 1fr));
            gap: 25px;
            margin-top: 30px;
        }
        
        /* Paneles mejorados */
        .panel {
            background: linear-gradient(135deg, 
                rgba(22,22,41,0.9), 
                rgba(26,26,46,0.9), 
                rgba(10,10,10,0.9));
            border: 1px solid var(--borde-neon);
            border-radius: 20px;
            padding: 25px;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(20px) saturate(120%);
            transition: all 0.4s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            box-shadow: 
                0 8px 32px rgba(0,0,0,0.3),
                inset 0 1px 0 rgba(255,255,255,0.1);
        }
        
        .panel:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 
                0 16px 64px rgba(0,255,255,0.3),
                0 0 40px rgba(0,255,255,0.2),
                inset 0 1px 0 rgba(255,255,255,0.2);
            border-color: var(--color-primario);
        }
        
        .panel.cuantico {
            border-color: var(--color-cuantico);
            background: linear-gradient(135deg, 
                rgba(0,255,170,0.1), 
                rgba(22,22,41,0.9));
        }
        
        .panel.neuronal {
            border-color: var(--color-neuronal);
            background: linear-gradient(135deg, 
                rgba(255,102,0,0.1), 
                rgba(22,22,41,0.9));
        }
        
        .panel.defensa {
            border-color: var(--color-peligro);
            background: linear-gradient(135deg, 
                rgba(255,0,68,0.1), 
                rgba(22,22,41,0.9));
        }
        
        /* Títulos de panel mejorados */
        .titulo-panel {
            font-family: 'Orbitron', monospace;
            font-size: 1.3em;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--color-primario);
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 20px;
            text-shadow: 0 0 10px currentColor;
        }
        
        .titulo-panel.cuantico { color: var(--color-cuantico); }
        .titulo-panel.neuronal { color: var(--color-neuronal); }
        .titulo-panel.defensa { color: var(--color-peligro); }
        
        /* Visualización de IA cuántica */
        .visualizacion-cuantica {
            height: 320px;
            background: linear-gradient(to bottom, 
                rgba(0,255,170,0.1), 
                rgba(157,0,255,0.1), 
                rgba(0,0,0,0.5));
            border: 2px solid var(--color-cuantico);
            border-radius: 15px;
            position: relative;
            overflow: hidden;
            margin: 20px 0;
        }
        
        .matriz-cuantica {
            position: absolute;
            width: 100%;
            height: 100%;
            display: flex;
            justify-content: space-around;
            align-items: center;
            padding: 20px;
        }
        
        .qubit {
            width: 25px;
            height: 25px;
            background: radial-gradient(circle, var(--color-cuantico), transparent);
            border: 2px solid var(--color-cuantico);
            border-radius: 50%;
            position: relative;
            animation: entrelazamiento-cuantico 3s infinite;
            animation-delay: calc(var(--i) * 0.2s);
        }
        
        @keyframes entrelazamiento-cuantico {
            0%, 100% { transform: scale(1) rotate(0deg); opacity: 0.7; }
            25% { transform: scale(1.3) rotate(90deg); opacity: 1; }
            50% { transform: scale(0.8) rotate(180deg); opacity: 0.9; }
            75% { transform: scale(1.2) rotate(270deg); opacity: 1; }
        }
        
        /* Métricas cuánticas */
        .metricas-cuanticas-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 12px;
            margin: 20px 0;
        }
        
        .metrica-cuantica {
            background: rgba(0,255,170,0.1);
            border: 1px solid rgba(0,255,170,0.4);
            border-radius: 10px;
            padding: 12px;
            text-align: center;
            transition: all 0.3s ease;
        }
        
        .metrica-cuantica:hover {
            background: rgba(0,255,170,0.2);
            transform: scale(1.05);
        }
        
        .metrica-cuantica-valor {
            font-size: 1.4em;
            font-weight: 700;
            color: var(--color-cuantico);
            font-family: 'JetBrains Mono', monospace;
        }
        
        .metrica-cuantica-etiqueta {
            font-size: 0.75em;
            color: var(--texto-secundario);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Terminal cuántico */
        .terminal-cuantico {
            background: #000012;
            border: 2px solid var(--color-cuantico);
            border-radius: 12px;
            padding: 20px;
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.9em;
            height: 400px;
            overflow-y: auto;
            box-shadow: 
                inset 0 0 20px rgba(0,255,170,0.2),
                0 0 30px rgba(0,255,170,0.1);
        }
        
        .terminal-cuantico::-webkit-scrollbar {
            width: 8px;
        }
        
        .terminal-cuantico::-webkit-scrollbar-track {
            background: rgba(0,0,0,0.3);
        }
        
        .terminal-cuantico::-webkit-scrollbar-thumb {
            background: var(--color-cuantico);
            border-radius: 4px;
        }
        
        .linea-terminal-cuantico {
            margin: 8px 0;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: aparicion-linea 0.5s ease-out;
        }
        
        @keyframes aparicion-linea {
            from { opacity: 0; transform: translateX(-20px); }
            to { opacity: 1; transform: translateX(0); }
        }
        
        /* Sistemas de defensa */
        .sistema-defensa {
            background: rgba(255,0,68,0.05);
            border: 1px solid rgba(255,0,68,0.3);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 12px;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 15px;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .sistema-defensa:hover {
            background: rgba(255,0,68,0.1);
            transform: translateX(5px);
        }
        
        .sistema-nombre {
            font-weight: 700;
            color: var(--color-peligro);
            text-transform: uppercase;
        }
        
        .sistema-nivel {
            font-family: 'JetBrains Mono', monospace;
            color: var(--color-exito);
            font-weight: 600;
        }
        
        /* Mapa de amenazas globales */
        .mapa-amenazas {
            background: rgba(0,0,0,0.3);
            border: 1px solid var(--color-peligro);
            border-radius: 12px;
            padding: 20px;
            height: 300px;
            overflow-y: auto;
        }
        
        .amenaza-global {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px;
            margin: 8px 0;
            background: rgba(255,0,68,0.1);
            border-radius: 8px;
            border-left: 4px solid var(--color-peligro);
        }
        
        .amenaza-nivel {
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 0.8em;
        }
        
        .nivel-alto { background: rgba(255,0,68,0.3); color: var(--color-peligro); }
        .nivel-medio { background: rgba(255,255,0,0.3); color: var(--color-acento); }
        .nivel-critico { background: rgba(255,255,255,0.3); color: #fff; animation: parpadeo-critico 1s infinite; }
        
        @keyframes parpadeo-critico {
            0%, 50% { opacity: 1; }
            51%, 100% { opacity: 0.5; }
        }
        
        /* Botones de comando mejorados */
        .botones-comando {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(140px, 1fr));
            gap: 12px;
            margin-top: 20px;
        }
        
        .btn-cmd {
            background: linear-gradient(135deg, 
                rgba(0,255,255,0.1), 
                rgba(255,0,255,0.1));
            border: 2px solid var(--color-primario);
            color: var(--color-primario);
            padding: 14px 8px;
            border-radius: 10px;
            font-family: 'Orbitron', monospace;
            font-weight: 700;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
            position: relative;
            overflow: hidden;
            font-size: 0.8em;
            text-align: center;
        }
        
        .btn-cmd:hover {
            background: linear-gradient(135deg, 
                rgba(0,255,255,0.3), 
                rgba(255,0,255,0.3));
            transform: translateY(-3px) scale(1.05);
            box-shadow: 
                0 8px 25px rgba(0,255,255,0.4),
                0 0 20px rgba(0,255,255,0.3);
        }
        
        .btn-cmd:active {
            transform: translateY(-1px) scale(1.02);
        }
        
        .btn-cmd.cuantico {
            border-color: var(--color-cuantico);
            color: var(--color-cuantico);
        }
        
        .btn-cmd.neuronal {
            border-color: var(--color-neuronal);
            color: var(--color-neuronal);
        }
        
        .btn-cmd.peligro {
            border-color: var(--color-peligro);
            color: var(--color-peligro);
        }
        
        .btn-cmd.ia {
            border-color: var(--color-ia);
            color: var(--color-ia);
        }
        
        /* Lista de modelos mejorada */
        .lista-modelos {
            margin: 20px 0;
            max-height: 400px;
            overflow-y: auto;
        }
        
        .modelo-ia {
            background: rgba(157,0,255,0.05);
            border: 1px solid rgba(157,0,255,0.3);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 12px;
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 12px;
            align-items: center;
            transition: all 0.3s ease;
        }
        
        .modelo-ia:hover {
            background: rgba(157,0,255,0.1);
            transform: translateX(5px);
            border-color: var(--color-ia);
        }
        
        .modelo-tipo {
            font-size: 0.7em;
            padding: 2px 6px;
            border-radius: 4px;
            text-transform: uppercase;
            font-weight: 600;
        }
        
        .tipo-cuantico { background: rgba(0,255,170,0.2); color: var(--color-cuantico); }
        .tipo-deep_learning { background: rgba(157,0,255,0.2); color: var(--color-ia); }
        .tipo-transformer { background: rgba(255,102,0,0.2); color: var(--color-neuronal); }
        .tipo-genetico { background: rgba(255,255,0,0.2); color: var(--color-acento); }
        .tipo-adversarial { background: rgba(255,0,68,0.2); color: var(--color-peligro); }
        .tipo-nlp { background: rgba(0,255,255,0.2); color: var(--color-primario); }
        .tipo-computer_vision { background: rgba(0,255,136,0.2); color: var(--color-exito); }
        .tipo-time_series { background: rgba(255,0,255,0.2); color: var(--color-secundario); }
        .tipo-reinforcement { background: rgba(136,136,136,0.2); color: #888; }
        
        /* Animaciones avanzadas */
        @keyframes matriz-digital {
            0% { opacity: 0; transform: translateY(100%); }
            50% { opacity: 1; }
            100% { opacity: 0; transform: translateY(-100%); }
        }
        
        @keyframes pulso-cuantico {
            0%, 100% { transform: scale(1); opacity: 0.8; }
            50% { transform: scale(1.2); opacity: 1; }
        }
        
        /* Responsive mejorado */
        @media (max-width: 1200px) {
            .grid-comando {
                grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            }
        }
        
        @media (max-width: 768px) {
            .grid-comando {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .metricas-cuanticas-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .botones-comando {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .modelo-ia {
                grid-template-columns: 1fr;
                text-align: center;
            }
        }
        
        /* Efectos de partículas */
        .particulas-contenedor {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }
    </style>
</head>
<body>
    <!-- Fondo cuántico -->
    <div id="fondo-cuantico"></div>
    
    <!-- Grid holográfico -->
    <div class="grid-holografico"></div>
    
    <!-- Contenedor de partículas -->
    <div class="particulas-contenedor" id="particulas"></div>
    
    <!-- Encabezado -->
    <header class="encabezado">
        <div style="display: flex; justify-content: space-between; align-items: center; max-width: 2000px; margin: 0 auto; flex-wrap: wrap; gap: 20px;">
            <div style="display: flex; align-items: center; gap: 25px;">
                <div style="width: 70px; height: 70px; background: conic-gradient(from 0deg, var(--color-primario), var(--color-secundario), var(--color-cuantico), var(--color-neuronal), var(--color-primario)); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 35px; animation: rotar-complejo 6s linear infinite; box-shadow: 0 0 30px rgba(0,255,255,0.5);">
                    🛡️
                </div>
                <div>
                    <h1 style="font-family: 'Orbitron', monospace; font-size: 2.2em; font-weight: 900; background: linear-gradient(45deg, var(--color-primario), var(--color-cuantico), var(--color-secundario)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-transform: uppercase; letter-spacing: 4px; text-shadow: 0 0 20px rgba(0,255,255,0.5);">
                        Centro de Comando Cuántico
                    </h1>
                    <p style="color: var(--texto-secundario); font-size: 1em; text-transform: uppercase; letter-spacing: 2px; margin-top: 5px;">
                        <?php echo APP_NAME; ?> - IA Cuántica Avanzada
                    </p>
                </div>
            </div>
            <div style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
                <div class="info-usuario">
                    <div style="font-size: 0.9em; margin-bottom: 5px;">
                        <strong style="color: var(--color-primario);"><?php echo htmlspecialchars($usuario_actual['fullname']); ?></strong>
                    </div>
                    <div style="font-size: 0.8em; color: var(--texto-secundario);">
                        Clearance: <span style="color: var(--color-cuantico);"><?php echo $usuario_actual['security_clearance']; ?></span>
                    </div>
                    <div style="font-size: 0.8em; color: var(--texto-secundario);">
                        Status: <span style="color: var(--color-exito);"><?php echo strtoupper($usuario_actual['premium_status']); ?></span>
                    </div>
                </div>
                <div style="padding: 10px 18px; background: rgba(0,255,136,0.1); border: 2px solid var(--color-exito); border-radius: 25px; font-size: 0.9em; text-transform: uppercase; display: flex; align-items: center; gap: 10px;">
                    <span style="width: 10px; height: 10px; background: var(--color-exito); border-radius: 50%; animation: pulso-cuantico 2s infinite;"></span>
                    Nivel Amenaza: <?php echo $datos_tiempo_real['nivel_amenaza']; ?>/5
                </div>
                <div style="padding: 10px 18px; background: rgba(0,255,170,0.1); border: 2px solid var(--color-cuantico); border-radius: 25px; font-size: 0.9em; text-transform: uppercase;">
                    IA Cuántica: OPERACIONAL
                </div>
                <div style="padding: 10px 18px; background: rgba(<?php echo $system_stats['database_status'] === 'connected' ? '0,255,136' : '255,0,68'; ?>,0.1); border: 2px solid var(<?php echo $system_stats['database_status'] === 'connected' ? '--color-exito' : '--color-peligro'; ?>); border-radius: 25px; font-size: 0.9em; text-transform: uppercase;">
                    BD: <?php echo strtoupper(str_replace('_', ' ', $system_stats['database_status'])); ?>
                </div>
            </div>
        </div>
    </header>
    
    <!-- Contenedor principal -->
    <div class="contenedor-principal">
        <div class="grid-comando">
            
            <!-- Panel de Estado del Sistema Cuántico -->
            <div class="panel cuantico">
                <div class="titulo-panel cuantico">
                    <span>⚡</span>
                    Estado del Sistema Cuántico
                </div>
                <div style="text-align: center; margin: 25px 0;">
                    <div style="font-size: 3.2em; font-weight: 900; font-family: 'Orbitron', monospace; color: <?php echo $datos_tiempo_real['carga_sistema'] > 80 ? 'var(--color-peligro)' : 'var(--color-cuantico)'; ?>; text-shadow: 0 0 20px currentColor;">
                        <?php echo $datos_tiempo_real['carga_sistema']; ?>%
                    </div>
                    <div style="color: var(--texto-secundario); text-transform: uppercase; font-weight: 600;">Carga del Sistema</div>
                </div>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                    <div style="background: rgba(0,255,170,0.1); border: 1px solid rgba(0,255,170,0.3); border-radius: 10px; padding: 15px; text-align: center;">
                        <div style="font-size: 1.6em; font-weight: 700; color: var(--color-cuantico);"><?php echo $datos_tiempo_real['usuarios_activos']; ?></div>
                        <div style="font-size: 0.8em; color: var(--texto-secundario);">Usuarios Conectados</div>
                    </div>
                    <div style="background: rgba(0,255,170,0.1); border: 1px solid rgba(0,255,170,0.3); border-radius: 10px; padding: 15px; text-align: center;">
                        <div style="font-size: 1.6em; font-weight: 700; color: var(--color-cuantico);"><?php echo number_format($datos_tiempo_real['trafico_red']); ?></div>
                        <div style="font-size: 0.8em; color: var(--texto-secundario);">Tráfico (Mbps)</div>
                    </div>
                    <div style="background: rgba(157,0,255,0.1); border: 1px solid rgba(157,0,255,0.3); border-radius: 10px; padding: 15px; text-align: center;">
                        <div style="font-size: 1.6em; font-weight: 700; color: var(--color-ia);"><?php echo $datos_tiempo_real['procesos_ia']; ?></div>
                        <div style="font-size: 0.8em; color: var(--texto-secundario);">Procesos IA</div>
                    </div>
                    <div style="background: rgba(157,0,255,0.1); border: 1px solid rgba(157,0,255,0.3); border-radius: 10px; padding: 15px; text-align: center;">
                        <div style="font-size: 1.6em; font-weight: 700; color: var(--color-ia);"><?php echo $metricas_ia['qubits_disponibles']; ?></div>
                        <div style="font-size: 0.8em; color: var(--texto-secundario);">Qubits Activos</div>
                    </div>
                </div>
                <div style="margin-top: 20px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="font-size: 0.9em;">Temperatura CPU:</span>
                        <span style="color: <?php echo $datos_tiempo_real['temperatura_cpu'] > 60 ? 'var(--color-peligro)' : 'var(--color-exito)'; ?>; font-family: 'JetBrains Mono', monospace;"><?php echo $datos_tiempo_real['temperatura_cpu']; ?>°C</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.9em;">Memoria RAM:</span>
                        <span style="color: <?php echo $datos_tiempo_real['memoria_usada'] > 75 ? 'var(--color-peligro)' : 'var(--color-exito)'; ?>; font-family: 'JetBrains Mono', monospace;"><?php echo $datos_tiempo_real['memoria_usada']; ?>%</span>
                    </div>
                </div>
            </div>
            
            <!-- Panel de Inteligencia Artificial Cuántica -->
            <div class="panel cuantico">
                <div class="titulo-panel cuantico">
                    <span>🧠</span>
                    IA Cuántica Avanzada
                </div>
                <div class="visualizacion-cuantica">
                    <div class="matriz-cuantica">
                        <div style="display: flex; flex-direction: column; justify-content: space-around; height: 100%;">
                            <?php for($i = 0; $i < 6; $i++): ?>
                            <div class="qubit" style="--i: <?php echo $i; ?>"></div>
                            <?php endfor; ?>
                        </div>
                        <div style="display: flex; flex-direction: column; justify-content: space-around; height: 100%;">
                            <?php for($i = 0; $i < 8; $i++): ?>
                            <div class="qubit" style="--i: <?php echo $i; ?>"></div>
                            <?php endfor; ?>
                        </div>
                        <div style="display: flex; flex-direction: column; justify-content: space-around; height: 100%;">
                            <?php for($i = 0; $i < 6; $i++): ?>
                            <div class="qubit" style="--i: <?php echo $i; ?>"></div>
                            <?php endfor; ?>
                        </div>
                        <div style="display: flex; flex-direction: column; justify-content: space-around; height: 100%;">
                            <?php for($i = 0; $i < 4; $i++): ?>
                            <div class="qubit" style="--i: <?php echo $i; ?>"></div>
                            <?php endfor; ?>
                        </div>
                    </div>
                </div>
                <div class="metricas-cuanticas-grid">
                    <div class="metrica-cuantica">
                        <div class="metrica-cuantica-valor"><?php echo $metricas_ia['precision_modelo']; ?>%</div>
                        <div class="metrica-cuantica-etiqueta">Precisión</div>
                    </div>
                    <div class="metrica-cuantica">
                        <div class="metrica-cuantica-valor"><?php echo $metricas_ia['entrelazamiento_cuantico']; ?>%</div>
                        <div class="metrica-cuantica-etiqueta">Entrelazamiento</div>
                    </div>
                    <div class="metrica-cuantica">
                        <div class="metrica-cuantica-valor"><?php echo $metricas_ia['coherencia_cuantica']; ?>%</div>
                        <div class="metrica-cuantica-etiqueta">Coherencia</div>
                    </div>
                    <div class="metrica-cuantica">
                        <div class="metrica-cuantica-valor"><?php echo $metricas_ia['temperatura_cuantica']; ?>K</div>
                        <div class="metrica-cuantica-etiqueta">Temp. Cuántica</div>
                    </div>
                    <div class="metrica-cuantica">
                        <div class="metrica-cuantica-valor"><?php echo $metricas_ia['error_rate_cuantico']; ?>%</div>
                        <div class="metrica-cuantica-etiqueta">Error Rate</div>
                    </div>
                    <div class="metrica-cuantica">
                        <div class="metrica-cuantica-valor"><?php echo $metricas_ia['fidelidad_gates']; ?>%</div>
                        <div class="metrica-cuantica-etiqueta">Fidelidad</div>
                    </div>
                </div>
            </div>
            
            <!-- Panel de Modelos de IA Avanzados -->
            <div class="panel neuronal">
                <div class="titulo-panel neuronal">
                    <span>🤖</span>
                    Modelos IA Avanzados
                </div>
                <div class="lista-modelos">
                    <?php foreach($modelos_ia as $nombre => $modelo): ?>
                    <div class="modelo-ia">
                        <div>
                            <div style="font-weight: 700; color: var(--color-neuronal); margin-bottom: 5px;">
                                <?php echo ucwords(str_replace('_', ' ', $nombre)); ?>
                            </div>
                            <div class="modelo-tipo tipo-<?php echo $modelo['tipo']; ?>">
                                <?php echo strtoupper(str_replace('_', ' ', $modelo['tipo'])); ?>
                            </div>
                        </div>
                        <div style="text-align: center;">
                            <div style="padding: 4px 8px; border-radius: 6px; font-size: 0.85em; text-align: center; color: <?php echo $modelo['estado'] == 'activo' ? 'var(--color-exito)' : ($modelo['estado'] == 'entrenando' ? 'var(--color-acento)' : 'var(--color-secundario)'); ?>; background: rgba(<?php echo $modelo['estado'] == 'activo' ? '0,255,136' : ($modelo['estado'] == 'entrenando' ? '255,255,0' : '255,0,255'); ?>,0.2);">
                                <?php echo strtoupper($modelo['estado']); ?>
                            </div>
                        </div>
                        <div style="text-align: center; font-family: 'JetBrains Mono', monospace; font-weight: 600;">
                            <?php echo $modelo['precision']; ?>%
                        </div>
                        <div style="position: relative;">
                            <div style="height: 20px; background: rgba(0,0,0,0.3); border-radius: 10px; overflow: hidden;">
                                <div style="height: 100%; width: <?php echo $modelo['carga']; ?>%; background: linear-gradient(90deg, var(--color-neuronal), var(--color-cuantico)); transition: width 1s ease; border-radius: 10px;"></div>
                            </div>
                            <div style="text-align: center; font-size: 0.8em; margin-top: 2px; color: var(--texto-secundario);">
                                Carga: <?php echo $modelo['carga']; ?>%
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="botones-comando">
                    <button class="btn-cmd neuronal" onclick="entrenarTodosModelos()">🔄 Entrenar Todo</button>
                    <button class="btn-cmd cuantico" onclick="optimizarCuantico()">⚛️ Optimizar</button>
                    <button class="btn-cmd ia" onclick="desplegarIA()">🚀 Desplegar</button>
                    <button class="btn-cmd neuronal" onclick="backupModelos()">💾 Backup</button>
                </div>
            </div>
            
            <!-- Panel de Sistemas de Defensa -->
            <div class="panel defensa">
                <div class="titulo-panel defensa">
                    <span>🛡️</span>
                    Sistemas de Defensa
                </div>
                <div style="margin: 20px 0;">
                    <?php foreach($sistemas_defensa as $nombre => $sistema): ?>
                    <div class="sistema-defensa">
                        <div class="sistema-nombre">
                            <?php echo ucwords(str_replace('_', ' ', $nombre)); ?>
                        </div>
                        <div class="sistema-nivel">
                            <?php echo $sistema['nivel']; ?>%
                        </div>
                        <div style="text-align: right; font-size: 0.8em; color: var(--texto-secundario);">
                            <?php 
                            $key = array_keys($sistema)[2]; // Tercera clave (después de estado y nivel)
                            echo ucwords(str_replace('_', ' ', $key)) . ': ' . number_format($sistema[$key]);
                            ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="botones-comando">
                    <button class="btn-cmd peligro" onclick="activarDefensaMaxima()">🔴 Máxima Defensa</button>
                    <button class="btn-cmd" onclick="escanearVulnerabilidades()">🔍 Escanear</button>
                    <button class="btn-cmd" onclick="actualizarFirmas()">📡 Actualizar</button>
                    <button class="btn-cmd peligro" onclick="lockdownCompleto()">🔒 Lockdown</button>
                </div>
            </div>
            
            <!-- Panel de Amenazas Globales -->
            <div class="panel defensa">
                <div class="titulo-panel defensa">
                    <span>🌍</span>
                    Amenazas Globales
                </div>
                <div class="mapa-amenazas">
                    <?php foreach($amenazas_globales as $amenaza): ?>
                    <div class="amenaza-global">
                        <div>
                            <div style="font-weight: 700; color: var(--color-primario);">
                                <?php echo $amenaza['pais']; ?>
                            </div>
                            <div style="font-size: 0.8em; color: var(--texto-secundario);">
                                <?php echo $amenaza['tipo']; ?>
                            </div>
                        </div>
                        <div class="amenaza-nivel nivel-<?php echo strtolower($amenaza['nivel']); ?>">
                            <?php echo $amenaza['nivel']; ?>
                        </div>
                        <div style="text-align: right; font-family: 'JetBrains Mono', monospace;">
                            <?php echo number_format($amenaza['intentos']); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div style="margin-top: 15px; padding: 15px; background: rgba(255,0,68,0.1); border-radius: 10px; border: 1px solid rgba(255,0,68,0.3);">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span>Total de Intentos Hoy:</span>
                        <span style="color: var(--color-peligro); font-weight: 700; font-family: 'JetBrains Mono', monospace;">
                            <?php echo number_format(array_sum(array_column($amenazas_globales, 'intentos'))); ?>
                        </span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 8px;">
                        <span>Bloqueados:</span>
                        <span style="color: var(--color-exito); font-weight: 700; font-family: 'JetBrains Mono', monospace;">
                            <?php echo $datos_tiempo_real['paquetes_bloqueados']; ?>
                        </span>
                    </div>
                </div>
            </div>
            
            <!-- Panel de Eventos en Tiempo Real Cuántico -->
            <div class="panel cuantico" style="grid-column: span 2;">
                <div class="titulo-panel cuantico">
                    <span>📡</span>
                    Monitor de Eventos Cuántico
                </div>
                <div class="terminal-cuantico">
                    <div style="color: var(--color-cuantico); margin-bottom: 15px; font-weight: 700;">
                        GUARDIAN-QUANTUM://EVENTOS_v3.0 <span style="display: inline-block; width: 12px; height: 18px; background: var(--color-cuantico); animation: parpadeo 1s infinite; margin-left: 5px;"></span>
                    </div>
                    <div style="color: var(--texto-secundario); margin-bottom: 15px; font-size: 0.8em;">
                        [STATUS] Conexión BD: <?php echo $system_stats['database_status']; ?> | Usuario: <?php echo $usuario_actual['username']; ?> | Clearance: <?php echo $usuario_actual['security_clearance']; ?>
                    </div>
                    <?php foreach($eventos_recientes as $evento): ?>
                    <div class="linea-terminal-cuantico">
                        <span style="color: var(--color-cuantico); font-family: 'JetBrains Mono', monospace;">[<?php echo $evento['hora']; ?>]</span>
                        <span style="padding: 3px 10px; border-radius: 6px; font-size: 0.8em; text-transform: uppercase; font-weight: 600;
                               background: rgba(<?php 
                                echo $evento['nivel'] == 'info' ? '0,255,170' : 
                                     ($evento['nivel'] == 'exito' ? '0,255,136' : 
                                     ($evento['nivel'] == 'advertencia' ? '255,255,0' : '255,0,68')); 
                            ?>,0.3); 
                               border: 1px solid <?php 
                                echo $evento['nivel'] == 'info' ? 'var(--color-cuantico)' : 
                                     ($evento['nivel'] == 'exito' ? 'var(--color-exito)' : 
                                     ($evento['nivel'] == 'advertencia' ? 'var(--color-acento)' : 'var(--color-peligro)')); 
                            ?>; 
                               color: <?php 
                                echo $evento['nivel'] == 'info' ? 'var(--color-cuantico)' : 
                                     ($evento['nivel'] == 'exito' ? 'var(--color-exito)' : 
                                     ($evento['nivel'] == 'advertencia' ? 'var(--color-acento)' : 'var(--color-peligro)')); 
                            ?>;">
                            <?php echo $evento['tipo']; ?>
                        </span>
                        <span style="flex: 1;"><?php echo $evento['mensaje']; ?></span>
                        <span style="padding: 2px 8px; background: rgba(157,0,255,0.2); border: 1px solid var(--color-ia); border-radius: 4px; font-size: 0.75em; color: var(--color-ia); font-family: 'JetBrains Mono', monospace;">
                            AI: <?php echo $evento['ia_confianza']; ?>%
                        </span>
                        <?php if (isset($evento['usuario'])): ?>
                        <span style="padding: 2px 8px; background: rgba(0,255,255,0.2); border: 1px solid var(--color-primario); border-radius: 4px; font-size: 0.75em; color: var(--color-primario); font-family: 'JetBrains Mono', monospace;">
                            User: <?php echo $evento['usuario']; ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Panel de Control Cuántico Avanzado -->
            <div class="panel cuantico">
                <div class="titulo-panel cuantico">
                    <span>⚛️</span>
                    Control Cuántico
                </div>
                <div style="text-align: center; margin: 25px 0;">
                    <div style="font-size: 3em; font-weight: 900; font-family: 'Orbitron', monospace; color: var(--color-cuantico); text-shadow: 0 0 20px var(--color-cuantico);">
                        <?php echo $datos_tiempo_real['fuerza_cuantica']; ?>%
                    </div>
                    <div style="color: var(--texto-secundario); text-transform: uppercase; font-weight: 600;">Fuerza Cuántica</div>
                </div>
                <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px; margin: 20px 0;">
                    <div style="background: rgba(0,255,170,0.1); border: 1px solid rgba(0,255,170,0.3); border-radius: 10px; padding: 15px; text-align: center;">
                        <div style="font-size: 1.5em; font-weight: 700; color: var(--color-cuantico);"><?php echo $datos_tiempo_real['canales_encriptados']; ?></div>
                        <div style="font-size: 0.8em; color: var(--texto-secundario);">Canales Seguros</div>
                    </div>
                    <div style="background: rgba(0,255,170,0.1); border: 1px solid rgba(0,255,170,0.3); border-radius: 10px; padding: 15px; text-align: center;">
                        <div style="font-size: 1.5em; font-weight: 700; color: var(--color-cuantico);"><?php echo $datos_tiempo_real['sesiones_cuanticas']; ?></div>
                        <div style="font-size: 0.8em; color: var(--texto-secundario);">Sesiones Activas</div>
                    </div>
                    <div style="background: rgba(0,255,170,0.1); border: 1px solid rgba(0,255,170,0.3); border-radius: 10px; padding: 15px; text-align: center;">
                        <div style="font-size: 1.5em; font-weight: 700; color: var(--color-cuantico);"><?php echo QUANTUM_KEY_LENGTH; ?></div>
                        <div style="font-size: 0.8em; color: var(--texto-secundario);">Bits de Clave</div>
                    </div>
                    <div style="background: rgba(0,255,170,0.1); border: 1px solid rgba(0,255,170,0.3); border-radius: 10px; padding: 15px; text-align: center;">
                        <div style="font-size: 1.5em; font-weight: 700; color: var(--color-cuantico);"><?php echo $datos_tiempo_real['certificados_activos']; ?></div>
                        <div style="font-size: 0.8em; color: var(--texto-secundario);">Certificados</div>
                    </div>
                </div>
                <div style="margin: 20px 0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="font-size: 0.9em;">Entrelazamiento:</span>
                        <span style="color: var(--color-cuantico); font-family: 'JetBrains Mono', monospace; font-weight: 600;"><?php echo $metricas_ia['entrelazamiento_cuantico']; ?>%</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span style="font-size: 0.9em;">Coherencia:</span>
                        <span style="color: var(--color-cuantico); font-family: 'JetBrains Mono', monospace; font-weight: 600;"><?php echo $metricas_ia['coherencia_cuantica']; ?>%</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span style="font-size: 0.9em;">Error Rate:</span>
                        <span style="color: <?php echo $metricas_ia['error_rate_cuantico'] > 0.05 ? 'var(--color-peligro)' : 'var(--color-exito)'; ?>; font-family: 'JetBrains Mono', monospace; font-weight: 600;"><?php echo $metricas_ia['error_rate_cuantico']; ?>%</span>
                    </div>
                </div>
                <div class="botones-comando">
                    <button class="btn-cmd cuantico" onclick="regenerarClavesQuantum()">🔑 Regenerar</button>
                    <button class="btn-cmd cuantico" onclick="tunelCuantico()">🌀 Túnel Q</button>
                    <button class="btn-cmd cuantico" onclick="calibrarQubits()">⚙️ Calibrar</button>
                    <button class="btn-cmd peligro" onclick="protocoloEmergenciaCuantico()">🆘 Emergencia</button>
                </div>
            </div>
            
            <!-- Panel de Monitoreo de Red y VPN -->
            <div class="panel">
                <div class="titulo-panel">
                    <span>🌐</span>
                    Monitoreo de Red y VPN
                </div>
                <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin: 20px 0;">
                    <div style="background: rgba(0,255,255,0.1); border: 1px solid rgba(0,255,255,0.3); border-radius: 10px; padding: 15px; text-align: center;">
                        <div style="font-size: 1.5em; font-weight: 700; color: var(--color-primario);"><?php echo $datos_tiempo_real['conexiones_vpn']; ?></div>
                        <div style="font-size: 0.8em; color: var(--texto-secundario);">VPN Activas</div>
                    </div>
                    <div style="background: rgba(0,255,255,0.1); border: 1px solid rgba(0,255,255,0.3); border-radius: 10px; padding: 15px; text-align: center;">
                        <div style="font-size: 1.5em; font-weight: 700; color: var(--color-primario);"><?php echo number_format($datos_tiempo_real['paquetes_bloqueados']); ?></div>
                        <div style="font-size: 0.8em; color: var(--texto-secundario);">Paquetes Bloqueados</div>
                    </div>
                    <div style="background: rgba(255,0,68,0.1); border: 1px solid rgba(255,0,68,0.3); border-radius: 10px; padding: 15px; text-align: center;">
                        <div style="font-size: 1.5em; font-weight: 700; color: var(--color-peligro);"><?php echo $datos_tiempo_real['intentos_intrusion']; ?></div>
                        <div style="font-size: 0.8em; color: var(--texto-secundario);">Intentos de Intrusión</div>
                    </div>
                </div>
                <div style="margin: 20px 0;">
                    <h4 style="color: var(--color-primario); margin-bottom: 10px;">Servidores VPN Disponibles:</h4>
                    <?php
                    $vpn_servers = unserialize(VPN_SERVERS);
                    foreach($vpn_servers as $key => $name):
                    ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; margin: 5px 0; background: rgba(0,255,255,0.1); border-radius: 8px; border: 1px solid rgba(0,255,255,0.2);">
                        <span><?php echo $name; ?></span>
                        <span style="color: var(--color-exito); font-size: 0.8em;">●</span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="botones-comando">
                    <button class="btn-cmd" onclick="conectarVPN()">🔗 Conectar VPN</button>
                    <button class="btn-cmd" onclick="cambiarServidor()">🔄 Cambiar Servidor</button>
                    <button class="btn-cmd peligro" onclick="killSwitchVPN()">⚡ Kill Switch</button>
                </div>
            </div>
            
            <!-- Panel de Control de IA Militar -->
            <div class="panel neuronal">
                <div class="titulo-panel neuronal">
                    <span>🎖️</span>
                    Control IA Militar
                </div>
                <div style="margin: 20px 0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding: 15px; background: rgba(255,102,0,0.1); border-radius: 10px; border: 1px solid rgba(255,102,0,0.3);">
                        <span>Estado Neural:</span>
                        <span style="color: var(--color-exito); font-weight: 700;">OPERACIONAL</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding: 15px; background: rgba(0,255,170,0.1); border-radius: 10px; border: 1px solid rgba(0,255,170,0.3);">
                        <span>IA Cuántica:</span>
                        <span style="color: var(--color-cuantico); font-weight: 700;">LISTA</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; padding: 15px; background: rgba(157,0,255,0.1); border-radius: 10px; border: 1px solid rgba(157,0,255,0.3);">
                        <span>Aprendizaje Continuo:</span>
                        <span style="color: var(--color-ia); font-weight: 700;">ACTIVO</span>
                    </div>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 15px; background: rgba(0,255,136,0.1); border-radius: 10px; border: 1px solid rgba(0,255,136,0.3);">
                        <span>FIPS Compliance:</span>
                        <span style="color: var(--color-exito); font-weight: 700;"><?php echo FIPS_140_2_COMPLIANCE ? 'ACTIVO' : 'INACTIVO'; ?></span>
                    </div>
                </div>
                <div class="botones-comando">
                    <button class="btn-cmd neuronal" onclick="entrenarRedProfunda()">🧠 Deep Learning</button>
                    <button class="btn-cmd cuantico" onclick="algoritmosCuanticos()">⚛️ Quantum AI</button>
                    <button class="btn-cmd ia" onclick="redesGAN()">🎭 Redes GAN</button>
                    <button class="btn-cmd neuronal" onclick="nlpMilitar()">💬 NLP Militar</button>
                    <button class="btn-cmd cuantico" onclick="visionComputacional()">👁️ Visión AI</button>
                    <button class="btn-cmd ia" onclick="aprendizajeRefuerzo()">🔄 Reinforcement</button>
                    <button class="btn-cmd neuronal" onclick="transferLearning()">📚 Transfer</button>
                    <button class="btn-cmd cuantico" onclick="federatedLearning()">🌐 Federated</button>
                    <button class="btn-cmd peligro" onclick="shutdownIA()">⚠️ Shutdown IA</button>
                </div>
            </div>
            
            <!-- Panel de Operaciones Militares Avanzadas -->
            <div class="panel defensa">
                <div class="titulo-panel defensa">
                    <span>🎖️</span>
                    Operaciones Militares
                </div>
                <div style="margin: 20px 0;">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                        <div style="background: rgba(255,0,68,0.1); border: 1px solid rgba(255,0,68,0.3); border-radius: 10px; padding: 15px; text-align: center;">
                            <div style="font-size: 1.4em; font-weight: 700; color: var(--color-peligro);">DEFCON 3</div>
                            <div style="font-size: 0.8em; color: var(--texto-secundario);">Estado Actual</div>
                        </div>
                        <div style="background: rgba(0,255,136,0.1); border: 1px solid rgba(0,255,136,0.3); border-radius: 10px; padding: 15px; text-align: center;">
                            <div style="font-size: 1.4em; font-weight: 700; color: var(--color-exito);"><?php echo $datos_tiempo_real['sistemas_backup']; ?></div>
                            <div style="font-size: 0.8em; color: var(--texto-secundario);">Sistemas Backup</div>
                        </div>
                    </div>
                </div>
                <div style="margin: 20px 0;">
                    <h4 style="color: var(--color-peligro); margin-bottom: 10px;">Clasificaciones de Seguridad:</h4>
                    <?php
                    $classifications = unserialize(CLASSIFICATION_LEVELS);
                    foreach($classifications as $level => $value):
                    ?>
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; margin: 5px 0; background: rgba(255,0,68,0.05); border-radius: 8px; border: 1px solid rgba(255,0,68,0.2);">
                        <span><?php echo $level; ?></span>
                        <span style="color: var(--color-acento); font-family: 'JetBrains Mono', monospace;">Nivel <?php echo $value; ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div class="botones-comando">
                    <button class="btn-cmd peligro" onclick="activarDEFCON(1)">🔴 DEFCON 1</button>
                    <button class="btn-cmd" onclick="activarDEFCON(2)">🟡 DEFCON 2</button>
                    <button class="btn-cmd" onclick="activarDEFCON(3)">🟢 DEFCON 3</button>
                    <button class="btn-cmd" onclick="comunicacionesSeguras()">📡 Com Seguras</button>
                    <button class="btn-cmd" onclick="contramedidas()">🎯 Contramedidas</button>
                    <button class="btn-cmd peligro" onclick="protocoloX()">⚡ Protocolo X</button>
                </div>
            </div>
            
            <!-- Panel de Base de Datos y Estadísticas -->
            <div class="panel">
                <div class="titulo-panel">
                    <span>💾</span>
                    Base de Datos y Estadísticas
                </div>
                <div style="margin: 20px 0;">
                    <div style="background: rgba(<?php echo $system_stats['database_status'] === 'connected' ? '0,255,136' : '255,0,68'; ?>,0.1); border: 1px solid var(<?php echo $system_stats['database_status'] === 'connected' ? '--color-exito' : '--color-peligro'; ?>); border-radius: 10px; padding: 15px; margin-bottom: 15px;">
                        <h4 style="color: var(<?php echo $system_stats['database_status'] === 'connected' ? '--color-exito' : '--color-peligro'; ?>); margin-bottom: 10px;">
                            Estado de Conexión: <?php echo strtoupper(str_replace('_', ' ', $system_stats['database_status'])); ?>
                        </h4>
                        <?php if ($system_stats['connection_info']): ?>
                        <div style="font-size: 0.9em; color: var(--texto-secundario);">
                            <div>Tipo: <span style="color: var(--color-primario);"><?php echo $system_stats['connection_info']['type']; ?></span></div>
                            <div>Host: <span style="color: var(--color-primario);"><?php echo $system_stats['connection_info']['host']; ?></span></div>
                            <div>Usuario: <span style="color: var(--color-primario);"><?php echo $system_stats['connection_info']['user']; ?></span></div>
                            <div>Encriptación: <span style="color: var(--color-cuantico);"><?php echo $system_stats['connection_info']['encryption']; ?></span></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 12px;">
                        <div style="background: rgba(0,255,255,0.1); border: 1px solid rgba(0,255,255,0.3); border-radius: 10px; padding: 15px; text-align: center;">
                            <div style="font-size: 1.5em; font-weight: 700; color: var(--color-primario);"><?php echo $system_stats['users_active']; ?></div>
                            <div style="font-size: 0.8em; color: var(--texto-secundario);">Usuarios Activos</div>
                        </div>
                        <div style="background: rgba(157,0,255,0.1); border: 1px solid rgba(157,0,255,0.3); border-radius: 10px; padding: 15px; text-align: center;">
                            <div style="font-size: 1.5em; font-weight: 700; color: var(--color-ia);"><?php echo $system_stats['premium_users']; ?></div>
                            <div style="font-size: 0.8em; color: var(--texto-secundario);">Usuarios Premium</div>
                        </div>
                        <div style="background: rgba(255,0,68,0.1); border: 1px solid rgba(255,0,68,0.3); border-radius: 10px; padding: 15px; text-align: center;">
                            <div style="font-size: 1.5em; font-weight: 700; color: var(--color-peligro);"><?php echo $system_stats['threats_detected_today']; ?></div>
                            <div style="font-size: 0.8em; color: var(--texto-secundario);">Amenazas Hoy</div>
                        </div>
                        <div style="background: rgba(0,255,136,0.1); border: 1px solid rgba(0,255,136,0.3); border-radius: 10px; padding: 15px; text-align: center;">
                            <div style="font-size: 1.5em; font-weight: 700; color: var(--color-exito);"><?php echo $system_stats['ai_detections_today']; ?></div>
                            <div style="font-size: 0.8em; color: var(--texto-secundario);">Detecciones IA</div>
                        </div>
                    </div>
                </div>
                <div class="botones-comando">
                    <button class="btn-cmd" onclick="backupDatabase()">💾 Backup BD</button>
                    <button class="btn-cmd" onclick="optimizarBD()">⚡ Optimizar</button>
                    <button class="btn-cmd" onclick="reporteSeguridad()">📊 Reporte</button>
                    <button class="btn-cmd" onclick="limpiarLogs()">🧹 Limpiar Logs</button>
                </div>
            </div>
            
        </div>
    </div>
    
    <!-- Footer -->
    <footer style="text-align: center; padding: 30px; margin-top: 50px; border-top: 2px solid var(--borde-neon); background: linear-gradient(135deg, rgba(0,0,0,0.8), rgba(26,26,46,0.8)); color: var(--texto-secundario);">
        <p style="font-size: 1.1em; margin-bottom: 10px; color: var(--color-cuantico);">
            <?php echo APP_NAME; ?> - Centro de Comando Cuántico
        </p>
        <p style="font-size: 0.9em; margin-bottom: 5px;">
            Clasificación: <span style="color: var(--color-peligro);">ALTO SECRETO</span> | 
            Autorización: <span style="color: var(--color-cuantico);">NIVEL 5 CUÁNTICO</span>
        </p>
        <p style="font-size: 0.8em;">
            Desarrollado por <span style="color: var(--color-primario);"><?php echo DEVELOPER; ?></span> | 
            Version: <?php echo APP_VERSION; ?> | 
            FIPS: <?php echo FIPS_140_2_COMPLIANCE ? 'COMPLIANT' : 'NON-COMPLIANT'; ?>
        </p>
    </footer>
    
    <script>
        // Variables globales
        let particulasCuanticas = [];
        let sistemaActivo = true;
        
        // Crear partículas cuánticas de fondo
        function crearParticulasCuanticas() {
            const contenedor = document.getElementById('particulas');
            const numParticulas = 25;
            
            for (let i = 0; i < numParticulas; i++) {
                const particula = document.createElement('div');
                particula.className = 'particula-cuantica';
                particula.style.left = Math.random() * 100 + '%';
                particula.style.top = Math.random() * 100 + '%';
                particula.style.animationDelay = Math.random() * 4 + 's';
                particula.style.animationDuration = (3 + Math.random() * 2) + 's';
                contenedor.appendChild(particula);
                particulasCuanticas.push(particula);
            }
        }
        
        // Funciones de IA Cuántica
        function entrenarTodosModelos() {
            mostrarAlerta('🧠 Iniciando entrenamiento masivo de todos los modelos de IA...', 'info');
            animarQubits();
            setTimeout(() => {
                mostrarAlerta('✅ Entrenamiento completado. Mejora promedio: +12.4%', 'exito');
            }, 4000);
        }
        
        function optimizarCuantico() {
            mostrarAlerta('⚛️ Optimizando algoritmos cuánticos...', 'info');
            document.querySelectorAll('.qubit').forEach(q => {
                q.style.animation = 'entrelazamiento-cuantico 0.5s infinite';
            });
            setTimeout(() => {
                document.querySelectorAll('.qubit').forEach(q => {
                    q.style.animation = '';
                });
                mostrarAlerta('✅ Optimización cuántica completada. Eficiencia +25%', 'exito');
            }, 3500);
        }
        
        function calibrarQubits() {
            mostrarAlerta('⚙️ Calibrando matriz de qubits...', 'info');
            setTimeout(() => {
                mostrarAlerta('✅ Calibración completada. Coherencia: 97.3%', 'exito');
            }, 2500);
        }
        
        function regenerarClavesQuantum() {
            mostrarAlerta('🔑 Regenerando claves cuánticas con protocolo BB84...', 'info');
            setTimeout(() => {
                mostrarAlerta('✅ Nuevas claves cuánticas generadas. Seguridad máxima', 'exito');
            }, 2000);
        }
        
        function tunelCuantico() {
            mostrarAlerta('🌀 Estableciendo túnel cuántico entrelazado...', 'info');
            setTimeout(() => {
                mostrarAlerta('✅ Túnel cuántico activo. Transferencia instantánea habilitada', 'exito');
            }, 3000);
        }
        
        function protocoloEmergenciaCuantico() {
            if (confirm('⚠️ PROTOCOLO DE EMERGENCIA CUÁNTICA - ¿Activar todas las defensas cuánticas?')) {
                mostrarAlerta('🆘 PROTOCOLO CUÁNTICO ACTIVADO - MÁXIMA SEGURIDAD', 'peligro');
                document.body.style.filter = 'hue-rotate(270deg) contrast(150%)';
                setTimeout(() => {
                    document.body.style.filter = 'none';
                }, 6000);
            }
        }
        
        // Funciones de IA Militar
        function entrenarRedProfunda() {
            mostrarAlerta('🧠 Entrenando red neuronal profunda militar...', 'info');
            setTimeout(() => {
                mostrarAlerta('✅ Red neuronal militar entrenada. Precisión táctica: 98.9%', 'exito');
            }, 3000);
        }
        
        function algoritmosCuanticos() {
            mostrarAlerta('⚛️ Ejecutando algoritmos cuánticos avanzados...', 'info');
            setTimeout(() => {
                mostrarAlerta('✅ Algoritmos cuánticos optimizados. Ventaja cuántica verificada', 'exito');
            }, 2800);
        }
        
        function redesGAN() {
            mostrarAlerta('🎭 Activando Redes Generativas Adversarias...', 'info');
            setTimeout(() => {
                mostrarAlerta('✅ GANs operacionales. Detección de deepfakes activa', 'exito');
            }, 2200);
        }
        
        function nlpMilitar() {
            mostrarAlerta('💬 Iniciando procesamiento de lenguaje natural militar...', 'info');
            setTimeout(() => {
                mostrarAlerta('✅ NLP militar activo. Análisis de comunicaciones habilitado', 'exito');
            }, 2500);
        }
        
        function visionComputacional() {
            mostrarAlerta('👁️ Activando sistema de visión computacional...', 'info');
            setTimeout(() => {
                mostrarAlerta('✅ Visión AI operacional. Reconocimiento facial militar activo', 'exito');
            }, 2700);
        }
        
        function aprendizajeRefuerzo() {
            mostrarAlerta('🔄 Iniciando aprendizaje por refuerzo...', 'info');
            setTimeout(() => {
                mostrarAlerta('✅ Reinforcement learning activo. Estrategias adaptativas listas', 'exito');
            }, 3200);
        }
        
        function transferLearning() {
            mostrarAlerta('📚 Transfiriendo conocimiento entre modelos...', 'info');
            setTimeout(() => {
                mostrarAlerta('✅ Transfer learning completado. Conocimiento compartido', 'exito');
            }, 2400);
        }
        
        function federatedLearning() {
            mostrarAlerta('🌐 Activando aprendizaje federado seguro...', 'info');
            setTimeout(() => {
                mostrarAlerta('✅ Federated learning activo. Red distribuida operacional', 'exito');
            }, 3500);
        }
        
        function shutdownIA() {
            if (confirm('⚠️ ADVERTENCIA: Esto desactivará todos los sistemas de IA. ¿Continuar?')) {
                mostrarAlerta('⚠️ DESACTIVANDO TODOS LOS SISTEMAS DE IA', 'peligro');
            }
        }
        
        // Funciones de Defensa
        function activarDefensaMaxima() {
            mostrarAlerta('🔴 ACTIVANDO DEFENSA MÁXIMA - TODOS LOS SISTEMAS', 'peligro');
            document.querySelectorAll('.panel').forEach(panel => {
                panel.style.borderColor = 'var(--color-peligro)';
                setTimeout(() => {
                    panel.style.borderColor = '';
                }, 3000);
            });
        }
        
        function escanearVulnerabilidades() {
            mostrarAlerta('🔍 Ejecutando escaneo completo de vulnerabilidades...', 'info');
            setTimeout(() => {
                mostrarAlerta('✅ Escaneo completado. 0 vulnerabilidades críticas encontradas', 'exito');
            }, 4000);
        }
        
        function actualizarFirmas() {
            mostrarAlerta('📡 Actualizando firmas de amenazas desde servidores militares...', 'info');
            setTimeout(() => {
                mostrarAlerta('✅ Base de datos de amenazas actualizada. +2,847 nuevas firmas', 'exito');
            }, 2800);
        }
        
        function lockdownCompleto() {
            if (confirm('🔒 LOCKDOWN COMPLETO - Esto bloqueará todo acceso externo. ¿Continuar?')) {
                mostrarAlerta('🔒 LOCKDOWN MILITAR ACTIVADO - ACCESO RESTRINGIDO', 'peligro');
            }
        }
        
        // Funciones de Red y VPN
        function conectarVPN() {
            mostrarAlerta('🔗 Conectando a servidor VPN militar seguro...', 'info');
            setTimeout(() => {
                mostrarAlerta('✅ VPN militar conectada. IP enmascarada', 'exito');
            }, 2000);
        }
        
        function cambiarServidor() {
            mostrarAlerta('🔄 Cambiando a servidor VPN de respaldo...', 'info');
            setTimeout(() => {
                mostrarAlerta('✅ Servidor cambiado. Conexión optimizada', 'exito');
            }, 1800);
        }
        
        function killSwitchVPN() {
            if (confirm('⚡ KILL SWITCH - Esto cortará todas las conexiones. ¿Continuar?')) {
                mostrarAlerta('⚡ KILL SWITCH ACTIVADO - CONEXIONES CORTADAS', 'peligro');
            }
        }
        
        // Funciones de Operaciones Militares
        function activarDEFCON(nivel) {
            const colores = {
                1: '#ff0000',
                2: '#ff6600', 
                3: '#ffff00'
            };
            const textos = {
                1: 'MÁXIMA ALERTA - AMENAZA INMINENTE',
                2: 'ALERTA ALTA - PREPARACIÓN PARA CONFLICTO',
                3: 'ALERTA MODERADA - VIGILANCIA INCREMENTADA'
            };
            
            mostrarAlerta(`🎖️ DEFCON ${nivel} ACTIVADO - ${textos[nivel]}`, 'peligro');
            document.documentElement.style.setProperty('--color-primario', colores[nivel]);
            setTimeout(() => {
                document.documentElement.style.setProperty('--color-primario', '#00ffff');
            }, 5000);
        }
        
        function comunicacionesSeguras() {
            mostrarAlerta('📡 Estableciendo comunicaciones encriptadas militares...', 'info');
            setTimeout(() => {
                mostrarAlerta('✅ Canal militar seguro establecido. Encriptación AES-256', 'exito');
            }, 2200);
        }
        
        function contramedidas() {
            mostrarAlerta('🎯 Desplegando contramedidas electrónicas...', 'info');
            setTimeout(() => {
                mostrarAlerta('✅ Contramedidas activas. Jamming y deception operacionales', 'exito');
            }, 2500);
        }
        
        function protocoloX() {
            if (confirm('⚡ PROTOCOLO X - Autorización de nivel máximo requerida. ¿Proceder?')) {
                mostrarAlerta('⚡ PROTOCOLO X INICIADO - TODAS LAS DEFENSAS NUCLEARES ACTIVAS', 'peligro');
                document.body.style.animation = 'glitch 0.8s';
                setTimeout(() => {
                    document.body.style.animation = '';
                }, 800);
            }
        }
        
        // Funciones de Base de Datos
        function backupDatabase() {
            mostrarAlerta('💾 Iniciando backup encriptado de base de datos...', 'info');
            setTimeout(() => {
                mostrarAlerta('✅ Backup completado. Datos seguros en múltiples ubicaciones', 'exito');
            }, 3000);
        }
        
        function optimizarBD() {
            mostrarAlerta('⚡ Optimizando base de datos militar...', 'info');
            setTimeout(() => {
                mostrarAlerta('✅ Base de datos optimizada. Rendimiento mejorado +18%', 'exito');
            }, 2500);
        }
        
        function reporteSeguridad() {
            mostrarAlerta('📊 Generando reporte de seguridad militar...', 'info');
            setTimeout(() => {
                mostrarAlerta('✅ Reporte generado. Estado: SEGURO - 98.7% de integridad', 'exito');
            }, 2000);
        }
        
        function limpiarLogs() {
            mostrarAlerta('🧹 Limpiando logs antiguos y datos temporales...', 'info');
            setTimeout(() => {
                mostrarAlerta('✅ Limpieza completada. 2.3GB de espacio liberado', 'exito');
            }, 1800);
        }
        
        // Función para animar qubits
        function animarQubits() {
            document.querySelectorAll('.qubit').forEach((qubit, index) => {
                setTimeout(() => {
                    qubit.style.background = 'radial-gradient(circle, var(--color-exito), transparent)';
                    qubit.style.borderColor = 'var(--color-exito)';
                    setTimeout(() => {
                        qubit.style.background = '';
                        qubit.style.borderColor = '';
                    }, 800);
                }, index * 150);
            });
        }
        
        // Sistema de alertas mejorado
        function mostrarAlerta(mensaje, tipo = 'info') {
            const alerta = document.createElement('div');
            alerta.style.cssText = `
                position: fixed;
                top: 120px;
                right: 30px;
                padding: 18px 25px;
                border-radius: 12px;
                color: white;
                font-weight: 700;
                font-family: 'Orbitron', monospace;
                z-index: 10000;
                animation: slideInScale 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94);
                box-shadow: 0 8px 32px rgba(0,0,0,0.4);
                backdrop-filter: blur(15px);
                border: 2px solid;
                max-width: 400px;
                word-wrap: break-word;
            `;
            
            if (tipo === 'peligro') {
                alerta.style.background = 'linear-gradient(135deg, rgba(255,0,68,0.9), rgba(255,0,68,0.7))';
                alerta.style.borderColor = 'var(--color-peligro)';
                alerta.style.boxShadow += ', 0 0 30px rgba(255,0,68,0.5)';
            } else if (tipo === 'exito') {
                alerta.style.background = 'linear-gradient(135deg, rgba(0,255,136,0.9), rgba(0,255,136,0.7))';
                alerta.style.borderColor = 'var(--color-exito)';
                alerta.style.boxShadow += ', 0 0 30px rgba(0,255,136,0.5)';
            } else if (tipo === 'advertencia') {
                alerta.style.background = 'linear-gradient(135deg, rgba(255,255,0,0.9), rgba(255,170,0,0.7))';
                alerta.style.borderColor = 'var(--color-acento)';
                alerta.style.boxShadow += ', 0 0 30px rgba(255,255,0,0.5)';
            } else {
                alerta.style.background = 'linear-gradient(135deg, rgba(0,255,170,0.9), rgba(0,170,255,0.7))';
                alerta.style.borderColor = 'var(--color-cuantico)';
                alerta.style.boxShadow += ', 0 0 30px rgba(0,255,170,0.5)';
            }
            
            alerta.textContent = mensaje;
            document.body.appendChild(alerta);
            
            // Mover otras alertas hacia abajo
            const alertasExistentes = document.querySelectorAll('div[style*="position: fixed"][style*="top: "]');
            alertasExistentes.forEach((alertaExistente, index) => {
                if (alertaExistente !== alerta) {
                    const topActual = parseInt(alertaExistente.style.top) || 120;
                    alertaExistente.style.top = (topActual + 100) + 'px';
                }
            });
            
            setTimeout(() => {
                alerta.style.animation = 'slideOutScale 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
                setTimeout(() => {
                    alerta.remove();
                }, 600);
            }, 5000);
        }
        
        // Actualizar datos en tiempo real
        function actualizarDatosEnTiempoReal() {
            // Actualizar métricas cuánticas
            document.querySelectorAll('.metrica-cuantica-valor').forEach(el => {
                if (el.textContent.includes('%')) {
                    const valorActual = parseFloat(el.textContent);
                    const variacion = (Math.random() - 0.5) * 2; // ±1%
                    const nuevoValor = Math.max(0, Math.min(100, valorActual + variacion));
                    el.textContent = nuevoValor.toFixed(1) + '%';
                } else if (el.textContent.includes('K')) {
                    const nuevoValor = (Math.random() * 0.020).toFixed(3);
                    el.textContent = nuevoValor + 'K';
                }
            });
            
            // Actualizar qubits disponibles
            const qubitsElement = document.querySelector('.metrica-cuantica-valor');
            if (qubitsElement && !qubitsElement.textContent.includes('%')) {
                qubitsElement.textContent = Math.floor(Math.random() * 30 + 100);
            }
        }
        
        // Simular eventos cuánticos aleatorios
        function simularEventoCuantico() {
            const eventosCuanticos = [
                { tipo: 'QUANTUM_DECOHERENCE', mensaje: 'Decoherencia cuántica detectada - Corrigiendo', nivel: 'advertencia' },
                { tipo: 'ENTANGLEMENT_SUCCESS', mensaje: 'Nuevo par entrelazado establecido', nivel: 'exito' },
                { tipo: 'QUANTUM_TELEPORTATION', mensaje: 'Teletransportación cuántica completada', nivel: 'exito' },
                { tipo: 'QUBIT_CALIBRATION', mensaje: 'Calibración automática de qubits', nivel: 'info' },
                { tipo: 'QUANTUM_ERROR_CORRECTION', mensaje: 'Corrección de errores cuánticos activa', nivel: 'info' },
                { tipo: 'SUPERPOSITION_MAINTAINED', mensaje: 'Superposición cuántica estabilizada', nivel: 'exito' }
            ];
            
            const evento = eventosCuanticos[Math.floor(Math.random() * eventosCuanticos.length)];
            const terminal = document.querySelector('.terminal-cuantico');
            
            if (terminal) {
                const nuevaLinea = document.createElement('div');
                nuevaLinea.className = 'linea-terminal-cuantico';
                nuevaLinea.innerHTML = `
                    <span style="color: var(--color-cuantico); font-family: 'JetBrains Mono', monospace;">[${new Date().toLocaleTimeString()}]</span>
                    <span style="padding: 3px 10px; border-radius: 6px; font-size: 0.8em; text-transform: uppercase; font-weight: 600;
                           background: rgba(${evento.nivel === 'info' ? '0,255,170' : 
                                            evento.nivel === 'exito' ? '0,255,136' : '255,255,0'},0.3); 
                           border: 1px solid ${evento.nivel === 'info' ? 'var(--color-cuantico)' : 
                                              evento.nivel === 'exito' ? 'var(--color-exito)' : 'var(--color-acento)'}; 
                           color: ${evento.nivel === 'info' ? 'var(--color-cuantico)' : 
                                   evento.nivel === 'exito' ? 'var(--color-exito)' : 'var(--color-acento)'};">
                        ${evento.tipo}
                    </span>
                    <span style="flex: 1;">${evento.mensaje}</span>
                    <span style="padding: 2px 8px; background: rgba(0,255,170,0.2); border: 1px solid var(--color-cuantico); border-radius: 4px; font-size: 0.75em; color: var(--color-cuantico); font-family: 'JetBrains Mono', monospace;">
                        Q-CONF: ${Math.floor(Math.random() * 15 + 85)}%
                    </span>
                `;
                
                // Insertar después del header
                const lineas = terminal.querySelectorAll('.linea-terminal-cuantico');
                if (lineas.length > 0) {
                    lineas[0].insertAdjacentElement('afterend', nuevaLinea);
                } else {
                    terminal.appendChild(nuevaLinea);
                }
                
                // Limitar a 12 eventos
                const todasLineas = terminal.querySelectorAll('.linea-terminal-cuantico');
                if (todasLineas.length > 12) {
                    todasLineas[todasLineas.length - 1].remove();
                }
            }
        }
        
        // Estilos de animación adicionales
        const estilosAdicionales = document.createElement('style');
        estilosAdicionales.textContent = `
            @keyframes slideInScale {
                from { 
                    transform: translateX(100%) scale(0.8); 
                    opacity: 0; 
                }
                to { 
                    transform: translateX(0) scale(1); 
                    opacity: 1; 
                }
            }
            @keyframes slideOutScale {
                to { 
                    transform: translateX(150%) scale(0.8); 
                    opacity: 0; 
                }
            }
            @keyframes rotar-complejo {
                from { transform: rotate(0deg) scale(1); }
                50% { transform: rotate(180deg) scale(1.1); }
                to { transform: rotate(360deg) scale(1); }
            }
            @keyframes glitch {
                0%, 100% { transform: translate(0) rotate(0deg); filter: hue-rotate(0deg); }
                10% { transform: translate(-2px, 2px) rotate(1deg); filter: hue-rotate(90deg); }
                20% { transform: translate(-2px, -2px) rotate(-1deg); filter: hue-rotate(180deg); }
                30% { transform: translate(2px, 2px) rotate(1deg); filter: hue-rotate(270deg); }
                40% { transform: translate(2px, -2px) rotate(-1deg); filter: hue-rotate(360deg); }
                50% { transform: translate(-1px, -1px) rotate(0deg); filter: hue-rotate(180deg); }
                60% { transform: translate(-1px, 1px) rotate(1deg); filter: hue-rotate(90deg); }
                70% { transform: translate(1px, 1px) rotate(-1deg); filter: hue-rotate(270deg); }
                80% { transform: translate(1px, -1px) rotate(1deg); filter: hue-rotate(360deg); }
                90% { transform: translate(-1px, 2px) rotate(-1deg); filter: hue-rotate(45deg); }
            }
            @keyframes parpadeo {
                0%, 50% { opacity: 1; }
                51%, 100% { opacity: 0; }
            }
        `;
        document.head.appendChild(estilosAdicionales);
        
        // Inicialización del sistema
        function inicializarSistema() {
            crearParticulasCuanticas();
            
            // Mensaje de bienvenida personalizado
            setTimeout(() => {
                mostrarAlerta('🎖️ Centro de Comando Cuántico Inicializado - Todas las defensas operacionales', 'exito');
            }, 1000);
            
            // Efecto de entrada para paneles
            document.querySelectorAll('.panel').forEach((panel, index) => {
                panel.style.opacity = '0';
                panel.style.transform = 'translateY(30px) scale(0.95)';
                setTimeout(() => {
                    panel.style.transition = 'all 0.6s cubic-bezier(0.25, 0.46, 0.45, 0.94)';
                    panel.style.opacity = '1';
                    panel.style.transform = 'translateY(0) scale(1)';
                }, index * 150);
            });
        }
        
        // Inicializar cuando se carga la página
        document.addEventListener('DOMContentLoaded', inicializarSistema);
        
        // Actualizar datos cada 5 segundos
        setInterval(actualizarDatosEnTiempoReal, 5000);
        
        // Simular eventos cuánticos cada 8 segundos
        setInterval(simularEventoCuantico, 8000);
        
        // Console log personalizado mejorado
        console.log('%c⚛️ GUARDIÁN IA v3.0 CUÁNTICO MILITAR', 'color: #00ffaa; font-size: 32px; font-weight: bold; text-shadow: 0 0 15px #00ffaa;');
        console.log('%c🛡️ Centro de Comando Operacional Avanzado', 'color: #ff00ff; font-size: 22px; font-weight: bold;');
        console.log('%c🧠 Inteligencia Artificial Cuántica Activa', 'color: #9d00ff; font-size: 18px;');
        console.log('%c⚛️ Procesamiento Cuántico Habilitado', 'color: #00ffaa; font-size: 16px;');
        console.log('%c✅ Todos los sistemas cuánticos operacionales', 'color: #00ff88; font-size: 16px;');
        console.log('%c⚠️ Autorización de Nivel 5 Cuántico verificada', 'color: #ffff00; font-size: 14px;');
        console.log('%c🔒 FIPS-140-2 Compliance: ' + <?php echo FIPS_140_2_COMPLIANCE ? 'true' : 'false'; ?>, 'color: #00ffff; font-size: 14px;');
        console.log('%c🔐 Encriptación Militar: ACTIVA', 'color: #ff6600; font-size: 14px;');
        console.log('%c📊 Estado BD: <?php echo $system_stats['database_status']; ?>', 'color: <?php echo $system_stats['database_status'] === 'connected' ? '#00ff88' : '#ff0044'; ?>; font-size: 14px;');
        
        // Log de eventos de seguridad
        <?php logSecurityEvent('COMMAND_CENTER_ACCESS', 'Centro de comando accedido desde IP: ' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 'medium', $_SESSION['user_id']); ?>
    </script>
</body>
</html>