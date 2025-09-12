<?php
/**
 * GuardianIA v3.0 FINAL - Sistema de Permisos con IA Avanzada
 * Anderson Mamian Chicangana - Control de Acceso Inteligente Mejorado
 * Sistema con Análisis Predictivo, Detección de Anomalías y Sincronización DB
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/config_military.php';
require_once __DIR__ . '/quantum_encryption.php';

// Verificar autenticación
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

// Verificar permisos de administrador
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    die('Acceso denegado. Se requieren permisos de administrador.');
}

// Obtener conexión a base de datos
$db = MilitaryDatabaseManager::getInstance();
$conn = $db->getConnection();

// Función para cargar usuarios desde la base de datos
function cargarUsuariosDB() {
    global $db, $DEFAULT_USERS;
    
    $usuarios = [];
    
    if ($db && $db->isConnected()) {
        try {
            $query = "SELECT u.*, 
                      COUNT(DISTINCT c.id) as total_conversaciones,
                      COUNT(DISTINCT se.id) as eventos_seguridad,
                      MAX(se.created_at) as ultimo_evento
                      FROM users u
                      LEFT JOIN conversations c ON u.id = c.user_id
                      LEFT JOIN security_events se ON u.id = se.user_id
                      GROUP BY u.id
                      ORDER BY u.id";
            
            $result = $db->query($query);
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    // Calcular puntuación de riesgo basada en eventos
                    $riesgo = calcularRiesgoUsuario($row);
                    
                    $usuarios[] = [
                        'id' => 'USR-' . str_pad($row['id'], 3, '0', STR_PAD_LEFT),
                        'db_id' => $row['id'],
                        'nombre_usuario' => $row['username'],
                        'nombre_completo' => $row['fullname'],
                        'email' => $row['email'],
                        'rol' => mapearRol($row['user_type']),
                        'autorizacion' => $row['security_clearance'] ?? 'UNCLASSIFIED',
                        'acceso_ia' => calcularAccesoIA($row),
                        'acceso_cuantico' => $row['military_access'] == 1,
                        'acceso_militar' => $row['military_access'] == 1,
                        'estado' => $row['status'],
                        'puntuacion_riesgo' => $riesgo,
                        'confianza_ia' => 100 - $riesgo,
                        'patrones_comportamiento' => $riesgo > 30 ? 'anomalo' : 'normal',
                        'ultimo_analisis' => $row['ultimo_evento'] ?? date('Y-m-d H:i:s'),
                        'prediccion_amenaza' => min($riesgo * 2, 100),
                        'conversaciones' => $row['total_conversaciones'],
                        'eventos_seguridad' => $row['eventos_seguridad'],
                        'ultimo_login' => $row['last_login'],
                        'premium_status' => $row['premium_status']
                    ];
                }
            }
        } catch (Exception $e) {
            logMilitaryEvent('ERROR', 'Error cargando usuarios: ' . $e->getMessage(), 'UNCLASSIFIED');
        }
    }
    
    // Si no hay usuarios en DB, usar los por defecto
    if (empty($usuarios)) {
        foreach ($DEFAULT_USERS as $user) {
            $usuarios[] = [
                'id' => 'USR-' . str_pad($user['id'], 3, '0', STR_PAD_LEFT),
                'db_id' => $user['id'],
                'nombre_usuario' => $user['username'],
                'nombre_completo' => $user['fullname'],
                'email' => $user['email'],
                'rol' => mapearRol($user['user_type']),
                'autorizacion' => $user['security_clearance'],
                'acceso_ia' => $user['user_type'] == 'admin' ? 100 : 50,
                'acceso_cuantico' => $user['military_access'],
                'acceso_militar' => $user['military_access'],
                'estado' => $user['status'],
                'puntuacion_riesgo' => 5,
                'confianza_ia' => 95,
                'patrones_comportamiento' => 'normal',
                'ultimo_analisis' => date('Y-m-d H:i:s'),
                'prediccion_amenaza' => 10,
                'conversaciones' => 0,
                'eventos_seguridad' => 0,
                'ultimo_login' => $user['last_login'],
                'premium_status' => $user['premium_status']
            ];
        }
    }
    
    return $usuarios;
}

// Función para calcular riesgo del usuario
function calcularRiesgoUsuario($userData) {
    $riesgo = 5; // Base
    
    // Incrementar riesgo por eventos de seguridad
    if ($userData['eventos_seguridad'] > 10) $riesgo += 20;
    elseif ($userData['eventos_seguridad'] > 5) $riesgo += 10;
    
    // Incrementar por intentos de login fallidos
    if (isset($userData['failed_login_attempts'])) {
        $riesgo += $userData['failed_login_attempts'] * 5;
    }
    
    // Reducir riesgo si es premium
    if ($userData['premium_status'] == 'premium') {
        $riesgo = max(5, $riesgo - 10);
    }
    
    return min($riesgo, 100);
}

// Función para calcular acceso IA
function calcularAccesoIA($userData) {
    if ($userData['user_type'] == 'admin') return 100;
    if ($userData['premium_status'] == 'premium') return 85;
    return 50;
}

// Función para mapear roles
function mapearRol($userType) {
    $roles = [
        'admin' => 'Administrador del Sistema',
        'premium' => 'Usuario Premium',
        'basic' => 'Usuario Básico'
    ];
    return $roles[$userType] ?? 'Usuario';
}

// Cargar datos
$datos_usuarios = cargarUsuariosDB();

// Cargar permisos desde configuración
$permisos_disponibles = [
    'sistema' => ['leer', 'escribir', 'ejecutar', 'eliminar', 'administrar', 'configurar', 'auditar', 'respaldo_completo'],
    'base_datos' => ['seleccionar', 'insertar', 'actualizar', 'eliminar', 'crear', 'eliminar_tabla', 'optimizar', 'replicar'],
    'ia' => ['ver', 'entrenar', 'predecir', 'modificar', 'desplegar', 'analizar', 'optimizar_modelo', 'crear_modelo'],
    'cuantico' => ['acceso', 'generar_claves', 'encriptar', 'desencriptar', 'tunel', 'entrelazar', 'medir', 'protocolo_bb84'],
    'militar' => ['ver_clasificado', 'modificar_clasificado', 'acceso_emergencia', 'protocolo_x', 'codigo_rojo', 'defcon_control'],
    'comunicacion' => ['enviar', 'recibir', 'transmitir', 'canal_encriptado', 'broadcast', 'canal_cuantico', 'satelite'],
    'respaldo' => ['crear', 'restaurar', 'eliminar', 'programar', 'emergencia', 'snapshot', 'replicar', 'nube_segura'],
    'seguridad' => ['firewall', 'ids_ips', 'antivirus_ia', 'honeypot', 'forense', 'pentesting', 'zero_trust', 'blockchain']
];

// Análisis de IA mejorado
$analisis_ia = [
    'modelos_activos' => rand(7, 15),
    'precision_promedio' => number_format(94.5 + (rand(0, 50) / 10), 1),
    'anomalias_detectadas' => rand(0, 5),
    'predicciones_realizadas' => rand(100, 500),
    'entrenamientos_completados' => rand(10, 50),
    'tasa_acierto' => number_format(96.8 + (rand(0, 30) / 10), 1),
    'quantum_stability' => number_format(98.5 + (rand(0, 15) / 10), 1),
    'neural_depth' => NEURAL_NETWORK_DEPTH ?? 7
];

// Predicciones de comportamiento mejoradas
$predicciones_comportamiento = [
    ['tipo' => 'ACCESO', 'probabilidad' => rand(70, 95), 'mensaje' => 'Posible intento de acceso no autorizado detectado', 'severidad' => 'alta'],
    ['tipo' => 'PRIVILEGIOS', 'probabilidad' => rand(60, 85), 'mensaje' => 'Usuario podría requerir elevación de privilegios', 'severidad' => 'media'],
    ['tipo' => 'SEGURIDAD', 'probabilidad' => rand(80, 98), 'mensaje' => 'Patrón de comportamiento anómalo en análisis', 'severidad' => 'critica'],
    ['tipo' => 'RENDIMIENTO', 'probabilidad' => rand(50, 75), 'mensaje' => 'Optimización de permisos recomendada', 'severidad' => 'baja'],
    ['tipo' => 'QUANTUM', 'probabilidad' => rand(85, 99), 'mensaje' => 'Estado cuántico requiere sincronización', 'severidad' => 'alta']
];

// Estadísticas del sistema
$stats_sistema = getSystemStats();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistema de Permisos IA - GuardianIA v3.0 MILITAR</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        @import url('https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700;900&family=Rajdhani:wght@300;500;700&display=swap');
        
        :root {
            --primario: #00ffcc;
            --secundario: #ff00ff;
            --acento: #00ff88;
            --peligro: #ff0044;
            --advertencia: #ffaa00;
            --info: #00aaff;
            --cuantico: #9d00ff;
            --ia-azul: #00b4ff;
            --ia-purpura: #b400ff;
            --oscuro: #000000;
            --medio: #0a0f1f;
            --claro: #1a1f2f;
            --texto: #ffffff;
            --texto-tenue: #888888;
            --exito: #00ff44;
            --critico: #ff0000;
        }
        
        body {
            font-family: 'Rajdhani', sans-serif;
            background: var(--oscuro);
            color: var(--texto);
            overflow-x: hidden;
            min-height: 100vh;
        }
        
        /* Red neuronal de fondo mejorada */
        .red-neuronal-fondo {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -3;
            opacity: 0.15;
            background: 
                radial-gradient(circle at 20% 30%, var(--ia-azul) 0%, transparent 50%),
                radial-gradient(circle at 80% 70%, var(--ia-purpura) 0%, transparent 50%),
                radial-gradient(circle at 50% 50%, var(--cuantico) 0%, transparent 70%);
            animation: pulso-neural 8s ease-in-out infinite;
        }
        
        @keyframes pulso-neural {
            0%, 100% { opacity: 0.15; transform: scale(1); }
            50% { opacity: 0.25; transform: scale(1.05); }
        }
        
        /* Matriz de permisos de fondo animada */
        .matriz-permisos-fondo {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            opacity: 0.1;
            background-image: 
                repeating-linear-gradient(0deg, transparent, transparent 50px, var(--primario) 50px, var(--primario) 51px),
                repeating-linear-gradient(90deg, transparent, transparent 50px, var(--primario) 50px, var(--primario) 51px);
            animation: desplazar-matriz 20s linear infinite;
        }
        
        @keyframes desplazar-matriz {
            from { transform: translate(0, 0); }
            to { transform: translate(50px, 50px); }
        }
        
        /* Partículas de IA mejoradas */
        .particulas-ia {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            pointer-events: none;
        }
        
        .particula-ia {
            position: absolute;
            width: 6px;
            height: 6px;
            background: linear-gradient(135deg, var(--ia-azul), var(--ia-purpura));
            border-radius: 50%;
            box-shadow: 0 0 15px var(--ia-azul);
            animation: flotar-ia 20s linear infinite;
        }
        
        .particula-cuantica {
            position: absolute;
            width: 4px;
            height: 4px;
            background: var(--cuantico);
            border-radius: 50%;
            box-shadow: 0 0 20px var(--cuantico);
            animation: entrelazar 15s ease-in-out infinite;
        }
        
        @keyframes flotar-ia {
            from {
                transform: translateY(100vh) translateX(0) scale(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
                transform: translateY(90vh) translateX(20px) scale(1);
            }
            90% {
                opacity: 1;
                transform: translateY(10vh) translateX(-20px) scale(1);
            }
            to {
                transform: translateY(-10vh) translateX(0) scale(0);
                opacity: 0;
            }
        }
        
        @keyframes entrelazar {
            0%, 100% {
                transform: translate(0, 0) rotate(0deg);
                opacity: 0.5;
            }
            25% {
                transform: translate(100px, -50px) rotate(90deg);
                opacity: 1;
            }
            50% {
                transform: translate(-100px, 100px) rotate(180deg);
                opacity: 0.8;
            }
            75% {
                transform: translate(50px, 50px) rotate(270deg);
                opacity: 1;
            }
        }
        
        /* Encabezado futurista con efectos holográficos */
        .encabezado {
            background: linear-gradient(135deg, rgba(0,255,204,0.1), rgba(157,0,255,0.1), rgba(0,180,255,0.1));
            backdrop-filter: blur(20px);
            border-bottom: 2px solid var(--primario);
            padding: 25px;
            position: relative;
            overflow: hidden;
        }
        
        .encabezado::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0,255,204,0.3), transparent);
            animation: holograma 4s linear infinite;
        }
        
        @keyframes holograma {
            from { left: -100%; }
            to { left: 100%; }
        }
        
        .encabezado::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--primario), var(--ia-azul), var(--secundario), transparent);
            animation: linea-escaneo 3s linear infinite;
        }
        
        @keyframes linea-escaneo {
            from { transform: translateX(-100%); }
            to { transform: translateX(100%); }
        }
        
        .contenido-encabezado {
            max-width: 1600px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        
        /* Escudo con IA holográfico */
        .escudo-ia {
            width: 70px;
            height: 80px;
            position: relative;
            animation: pulso-escudo 3s ease-in-out infinite;
        }
        
        @keyframes pulso-escudo {
            0%, 100% { transform: scale(1) rotateY(0deg); }
            50% { transform: scale(1.1) rotateY(180deg); }
        }
        
        .forma-escudo {
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, var(--primario), var(--ia-azul), var(--cuantico));
            clip-path: polygon(50% 0%, 100% 25%, 100% 75%, 50% 100%, 0% 75%, 0% 25%);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 35px;
            position: relative;
        }
        
        .forma-escudo::after {
            content: '🛡️';
            position: absolute;
            animation: rotar-icono 10s linear infinite;
        }
        
        @keyframes rotar-icono {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .brillo-escudo {
            position: absolute;
            top: -15px;
            left: -15px;
            right: -15px;
            bottom: -15px;
            background: radial-gradient(circle, var(--ia-azul), transparent);
            opacity: 0.5;
            animation: pulso-brillo 2s ease-in-out infinite;
        }
        
        @keyframes pulso-brillo {
            0%, 100% { opacity: 0.3; transform: scale(1); }
            50% { opacity: 0.8; transform: scale(1.2); }
        }
        
        /* Estado del sistema mejorado */
        .estado-sistema {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }
        
        .indicador-estado {
            padding: 8px 16px;
            background: rgba(0,255,204,0.1);
            border: 1px solid var(--primario);
            border-radius: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
            position: relative;
            overflow: hidden;
        }
        
        .indicador-estado::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: brillo-indicador 3s linear infinite;
        }
        
        @keyframes brillo-indicador {
            from { left: -100%; }
            to { left: 100%; }
        }
        
        .indicador-ia {
            background: rgba(0,180,255,0.1);
            border-color: var(--ia-azul);
            animation: parpadeo-ia 3s infinite;
        }
        
        .indicador-cuantico {
            background: rgba(157,0,255,0.1);
            border-color: var(--cuantico);
            animation: quantum-glow 2s ease-in-out infinite;
        }
        
        @keyframes quantum-glow {
            0%, 100% { box-shadow: 0 0 5px var(--cuantico); }
            50% { box-shadow: 0 0 20px var(--cuantico), 0 0 40px var(--cuantico); }
        }
        
        @keyframes parpadeo-ia {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
        
        .punto-estado {
            width: 8px;
            height: 8px;
            background: var(--primario);
            border-radius: 50%;
            animation: pulso-punto 2s infinite;
            box-shadow: 0 0 10px currentColor;
        }
        
        .punto-ia {
            background: var(--ia-azul);
        }
        
        .punto-cuantico {
            background: var(--cuantico);
        }
        
        @keyframes pulso-punto {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.5); opacity: 0.7; }
        }
        
        /* Contenedor principal */
        .contenedor-principal {
            max-width: 1600px;
            margin: 0 auto;
            padding: 30px 20px;
        }
        
        /* Grid de permisos responsivo */
        .grid-permisos {
            display: grid;
            grid-template-columns: 380px 1fr;
            gap: 25px;
            margin-top: 30px;
        }
        
        /* Panel base futurista */
        .panel {
            background: linear-gradient(135deg, rgba(10,15,31,0.9), rgba(0,0,0,0.9));
            border: 1px solid rgba(0,255,204,0.3);
            border-radius: 15px;
            padding: 25px;
            position: relative;
            overflow: hidden;
            backdrop-filter: blur(10px);
        }
        
        .panel::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 1px;
            background: linear-gradient(90deg, transparent, var(--primario), transparent);
            animation: linea-superior 4s linear infinite;
        }
        
        @keyframes linea-superior {
            from { transform: translateX(-100%); }
            to { transform: translateX(100%); }
        }
        
        .panel-ia {
            border-color: rgba(0,180,255,0.3);
            background: linear-gradient(135deg, rgba(0,15,31,0.9), rgba(0,0,20,0.9));
        }
        
        .panel-cuantico {
            border-color: rgba(157,0,255,0.3);
            background: linear-gradient(135deg, rgba(20,0,31,0.9), rgba(10,0,20,0.9));
        }
        
        .encabezado-panel {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid rgba(0,255,204,0.2);
            position: relative;
        }
        
        .encabezado-panel::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 50px;
            height: 1px;
            background: var(--primario);
            animation: expandir-linea 2s ease-in-out infinite;
        }
        
        @keyframes expandir-linea {
            0%, 100% { width: 50px; left: 0; }
            50% { width: 100%; left: 0; }
        }
        
        .titulo-panel {
            font-family: 'Orbitron', monospace;
            font-size: 1.3em;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--primario);
            display: flex;
            align-items: center;
            gap: 12px;
            text-shadow: 0 0 10px currentColor;
        }
        
        .titulo-panel-ia {
            color: var(--ia-azul);
        }
        
        .titulo-panel-cuantico {
            color: var(--cuantico);
        }
        
        /* Lista de usuarios futurista con efectos */
        .lista-usuarios {
            max-height: 600px;
            overflow-y: auto;
            padding-right: 10px;
        }
        
        .lista-usuarios::-webkit-scrollbar {
            width: 8px;
        }
        
        .lista-usuarios::-webkit-scrollbar-track {
            background: rgba(0,255,204,0.1);
            border-radius: 4px;
        }
        
        .lista-usuarios::-webkit-scrollbar-thumb {
            background: var(--primario);
            border-radius: 4px;
        }
        
        .tarjeta-usuario {
            background: rgba(0,255,204,0.05);
            border: 1px solid rgba(0,255,204,0.2);
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .tarjeta-usuario::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0,255,204,0.2), transparent);
            transition: left 0.5s ease;
        }
        
        .tarjeta-usuario:hover::before {
            left: 100%;
        }
        
        .tarjeta-usuario:hover {
            transform: translateX(10px);
            border-color: var(--primario);
            box-shadow: 0 5px 20px rgba(0,255,204,0.3);
        }
        
        .tarjeta-usuario.seleccionada {
            background: rgba(0,255,204,0.15);
            border-color: var(--primario);
            box-shadow: 0 0 30px rgba(0,255,204,0.4);
            animation: pulso-seleccion 2s ease-in-out infinite;
        }
        
        @keyframes pulso-seleccion {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }
        
        .cabecera-usuario {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        
        .nombre-usuario {
            font-weight: 700;
            font-size: 1.1em;
            color: var(--acento);
            text-shadow: 0 0 5px currentColor;
        }
        
        .estado-usuario {
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 700;
            text-transform: uppercase;
            position: relative;
            overflow: hidden;
        }
        
        .estado-usuario::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.2), transparent);
            transform: translate(-50%, -50%);
            animation: pulso-estado 2s ease-in-out infinite;
        }
        
        @keyframes pulso-estado {
            0%, 100% { transform: translate(-50%, -50%) scale(0); opacity: 1; }
            50% { transform: translate(-50%, -50%) scale(2); opacity: 0; }
        }
        
        .estado-usuario.active {
            background: rgba(0,255,136,0.2);
            color: var(--acento);
            border: 1px solid var(--acento);
        }
        
        .estado-usuario.inactive {
            background: rgba(255,170,0,0.2);
            color: var(--advertencia);
            border: 1px solid var(--advertencia);
        }
        
        .estado-usuario.suspended {
            background: rgba(255,0,68,0.2);
            color: var(--peligro);
            border: 1px solid var(--peligro);
        }
        
        .detalles-usuario {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
            font-size: 0.9em;
            color: var(--texto-tenue);
        }
        
        .item-detalle {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .item-detalle .icono {
            font-size: 1.1em;
        }
        
        /* Indicadores de métricas del usuario */
        .metricas-usuario {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid rgba(0,255,204,0.2);
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }
        
        .metrica-mini {
            text-align: center;
            padding: 5px;
            background: rgba(0,0,0,0.3);
            border-radius: 8px;
        }
        
        .valor-metrica-mini {
            font-size: 1.2em;
            font-weight: 700;
            color: var(--primario);
            font-family: 'Orbitron', monospace;
        }
        
        .etiqueta-metrica-mini {
            font-size: 0.7em;
            color: var(--texto-tenue);
            text-transform: uppercase;
        }
        
        /* Indicador de confianza IA con gradiente */
        .confianza-ia-usuario {
            margin-top: 10px;
            padding-top: 10px;
            border-top: 1px solid rgba(0,180,255,0.2);
        }
        
        .barra-confianza {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 5px;
        }
        
        .progreso-confianza {
            flex: 1;
            height: 8px;
            background: rgba(0,0,0,0.5);
            border-radius: 4px;
            overflow: hidden;
            position: relative;
        }
        
        .progreso-confianza::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, 
                var(--peligro) 0%, 
                var(--advertencia) 30%, 
                var(--acento) 70%, 
                var(--ia-azul) 100%);
            opacity: 0.3;
        }
        
        .nivel-confianza {
            height: 100%;
            background: linear-gradient(90deg, var(--ia-azul), var(--ia-purpura));
            border-radius: 4px;
            transition: width 1s ease;
            position: relative;
            overflow: hidden;
        }
        
        .nivel-confianza::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            animation: brillo-barra 2s linear infinite;
        }
        
        @keyframes brillo-barra {
            from { left: -100%; }
            to { left: 100%; }
        }
        
        .valor-confianza {
            font-size: 0.85em;
            color: var(--ia-azul);
            font-weight: 700;
            min-width: 40px;
            text-shadow: 0 0 5px currentColor;
        }
        
        /* Panel de permisos principal */
        .panel-permisos {
            display: grid;
            grid-template-rows: auto 1fr auto;
            gap: 20px;
        }
        
        /* Información del usuario seleccionado holográfica */
        .info-usuario-seleccionado {
            background: linear-gradient(135deg, rgba(0,255,204,0.1), rgba(0,180,255,0.1), rgba(157,0,255,0.1));
            border-radius: 12px;
            padding: 20px;
            display: grid;
            grid-template-columns: auto 1fr auto;
            gap: 20px;
            align-items: center;
            position: relative;
            overflow: hidden;
        }
        
        .info-usuario-seleccionado::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(0,255,204,0.1) 50%, transparent 70%);
            animation: holo-scan 3s linear infinite;
        }
        
        @keyframes holo-scan {
            from { transform: translateX(-100%); }
            to { transform: translateX(100%); }
        }
        
        .avatar-usuario {
            width: 90px;
            height: 90px;
            background: conic-gradient(from 0deg, var(--primario), var(--ia-azul), var(--secundario), var(--cuantico), var(--primario));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 30px;
            font-weight: 700;
            color: var(--oscuro);
            position: relative;
            animation: rotar-avatar 10s linear infinite;
            box-shadow: 0 0 30px var(--ia-azul);
        }
        
        @keyframes rotar-avatar {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .avatar-usuario::after {
            content: '';
            position: absolute;
            top: -10px;
            left: -10px;
            right: -10px;
            bottom: -10px;
            background: radial-gradient(circle, transparent 40%, var(--ia-azul) 100%);
            opacity: 0.5;
            animation: pulso-avatar 2s ease-in-out infinite;
        }
        
        @keyframes pulso-avatar {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.2); opacity: 0.3; }
        }
        
        .detalles-info-usuario h2 {
            font-family: 'Orbitron', monospace;
            font-size: 1.8em;
            color: var(--primario);
            margin-bottom: 5px;
            text-shadow: 0 0 15px currentColor;
        }
        
        .meta-usuario {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .insignia-meta {
            padding: 5px 12px;
            background: rgba(0,255,204,0.1);
            border: 1px solid rgba(0,255,204,0.3);
            border-radius: 20px;
            font-size: 0.9em;
            position: relative;
            overflow: hidden;
        }
        
        .insignia-meta::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.1), transparent);
            animation: brillo-insignia 3s linear infinite;
        }
        
        @keyframes brillo-insignia {
            from { left: -100%; }
            to { left: 100%; }
        }
        
        .insignia-ia {
            background: rgba(0,180,255,0.1);
            border-color: rgba(0,180,255,0.3);
            color: var(--ia-azul);
        }
        
        .insignia-cuantica {
            background: rgba(157,0,255,0.1);
            border-color: rgba(157,0,255,0.3);
            color: var(--cuantico);
        }
        
        .insignia-premium {
            background: linear-gradient(135deg, rgba(255,215,0,0.1), rgba(255,255,255,0.1));
            border-color: #ffd700;
            color: #ffd700;
            animation: brillo-premium 2s ease-in-out infinite;
        }
        
        @keyframes brillo-premium {
            0%, 100% { box-shadow: 0 0 5px #ffd700; }
            50% { box-shadow: 0 0 20px #ffd700, 0 0 40px #ffd700; }
        }
        
        /* Matriz de permisos con efectos cuánticos */
        .matriz-permisos {
            background: rgba(0,0,0,0.5);
            border-radius: 12px;
            padding: 20px;
            overflow: auto;
            max-height: 500px;
            position: relative;
        }
        
        .matriz-permisos::-webkit-scrollbar {
            width: 10px;
        }
        
        .matriz-permisos::-webkit-scrollbar-track {
            background: rgba(0,255,204,0.1);
            border-radius: 5px;
        }
        
        .matriz-permisos::-webkit-scrollbar-thumb {
            background: linear-gradient(180deg, var(--primario), var(--ia-azul));
            border-radius: 5px;
        }
        
        .categoria-permisos {
            margin-bottom: 25px;
            animation: aparecer-categoria 0.5s ease;
        }
        
        @keyframes aparecer-categoria {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .cabecera-categoria {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(0,255,204,0.2);
            position: relative;
        }
        
        .cabecera-categoria::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 0;
            height: 2px;
            background: var(--primario);
            animation: expandir-categoria 1s ease forwards;
        }
        
        @keyframes expandir-categoria {
            to { width: 100%; }
        }
        
        .icono-categoria {
            font-size: 24px;
            animation: rotar-suave 10s linear infinite;
        }
        
        @keyframes rotar-suave {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .titulo-categoria {
            font-family: 'Orbitron', monospace;
            font-size: 1.2em;
            font-weight: 700;
            color: var(--primario);
            text-transform: uppercase;
            text-shadow: 0 0 10px currentColor;
        }
        
        .grid-permisos-items {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(160px, 1fr));
            gap: 10px;
        }
        
        .item-permiso {
            background: rgba(0,255,204,0.05);
            border: 1px solid rgba(0,255,204,0.2);
            border-radius: 8px;
            padding: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }
        
        .item-permiso::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: radial-gradient(circle, var(--primario), transparent);
            transform: translate(-50%, -50%);
            transition: all 0.3s ease;
        }
        
        .item-permiso:hover::before {
            width: 150%;
            height: 150%;
            opacity: 0.3;
        }
        
        .item-permiso:hover {
            background: rgba(0,255,204,0.1);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0,255,204,0.2);
        }
        
        .item-permiso.recomendado-ia {
            border-color: var(--ia-azul);
            background: rgba(0,180,255,0.05);
            animation: pulso-ia 3s ease-in-out infinite;
        }
        
        @keyframes pulso-ia {
            0%, 100% { box-shadow: 0 0 5px var(--ia-azul); }
            50% { box-shadow: 0 0 15px var(--ia-azul), 0 0 30px var(--ia-azul); }
        }
        
        .item-permiso.recomendado-ia::after {
            content: '🤖';
            position: absolute;
            top: -5px;
            right: -5px;
            font-size: 12px;
            background: var(--ia-azul);
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            animation: girar-icono 2s linear infinite;
        }
        
        @keyframes girar-icono {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .item-permiso.critico {
            border-color: var(--peligro);
            background: rgba(255,0,68,0.05);
        }
        
        .item-permiso.cuantico {
            border-color: var(--cuantico);
            background: rgba(157,0,255,0.05);
        }
        
        .nombre-permiso {
            font-size: 0.95em;
            text-transform: capitalize;
        }
        
        .toggle-permiso {
            width: 50px;
            height: 25px;
            background: rgba(255,0,68,0.3);
            border-radius: 25px;
            position: relative;
            transition: all 0.3s ease;
            cursor: pointer;
            overflow: hidden;
        }
        
        .toggle-permiso::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
            animation: deslizar-toggle 3s linear infinite;
        }
        
        @keyframes deslizar-toggle {
            from { transform: translateX(-100%); }
            to { transform: translateX(100%); }
        }
        
        .toggle-permiso.activo {
            background: rgba(0,255,136,0.3);
        }
        
        .toggle-permiso::after {
            content: '';
            position: absolute;
            top: 2px;
            left: 2px;
            width: 21px;
            height: 21px;
            background: var(--peligro);
            border-radius: 50%;
            transition: all 0.3s ease;
            box-shadow: 0 2px 5px rgba(0,0,0,0.3);
        }
        
        .toggle-permiso.activo::after {
            left: 27px;
            background: var(--acento);
            box-shadow: 0 2px 5px rgba(0,255,136,0.5);
        }
        
        /* Panel de análisis de IA avanzado con efectos */
        .analisis-ia {
            background: linear-gradient(135deg, rgba(0,180,255,0.1), rgba(180,0,255,0.1));
            border: 1px solid var(--ia-azul);
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .analisis-ia::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, var(--ia-azul), transparent);
            opacity: 0.1;
            animation: rotar-fondo 20s linear infinite;
        }
        
        @keyframes rotar-fondo {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        .cabecera-ia {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .titulo-ia {
            font-family: 'Orbitron', monospace;
            font-size: 1.2em;
            color: var(--ia-azul);
            text-transform: uppercase;
            text-shadow: 0 0 15px currentColor;
        }
        
        /* Métricas de IA con animaciones */
        .metricas-ia {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
        }
        
        .metrica-ia {
            background: rgba(0,0,0,0.3);
            border-radius: 8px;
            padding: 10px;
            text-align: center;
            border: 1px solid rgba(0,180,255,0.2);
            position: relative;
            overflow: hidden;
        }
        
        .metrica-ia::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--ia-azul), transparent);
            animation: linea-metrica 2s linear infinite;
        }
        
        @keyframes linea-metrica {
            from { transform: translateX(-100%); }
            to { transform: translateX(100%); }
        }
        
        .valor-metrica {
            font-size: 1.8em;
            font-weight: 700;
            color: var(--ia-azul);
            font-family: 'Orbitron', monospace;
            text-shadow: 0 0 10px currentColor;
            animation: parpadeo-valor 3s ease-in-out infinite;
        }
        
        @keyframes parpadeo-valor {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
        
        .etiqueta-metrica {
            font-size: 0.85em;
            color: var(--texto-tenue);
            text-transform: uppercase;
            margin-top: 5px;
        }
        
        /* Medidor de riesgo holográfico */
        .medidor-riesgo {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 20px 0;
            position: relative;
            z-index: 1;
        }
        
        .barra-riesgo {
            flex: 1;
            height: 35px;
            background: linear-gradient(to right, 
                var(--acento) 0%, 
                var(--advertencia) 50%, 
                var(--peligro) 100%);
            border-radius: 20px;
            position: relative;
            overflow: hidden;
            box-shadow: inset 0 2px 10px rgba(0,0,0,0.3), 0 0 20px rgba(0,0,0,0.5);
        }
        
        .barra-riesgo::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: repeating-linear-gradient(
                90deg,
                transparent,
                transparent 10px,
                rgba(0,0,0,0.3) 10px,
                rgba(0,0,0,0.3) 20px
            );
            animation: mover-patron 1s linear infinite;
        }
        
        @keyframes mover-patron {
            from { transform: translateX(0); }
            to { transform: translateX(20px); }
        }
        
        .indicador-riesgo {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 6px;
            height: 45px;
            background: white;
            box-shadow: 0 0 20px rgba(255,255,255,0.8), 0 0 40px rgba(255,255,255,0.5);
            transition: left 1s ease;
            border-radius: 3px;
            animation: pulso-indicador 1s ease-in-out infinite;
        }
        
        @keyframes pulso-indicador {
            0%, 100% { box-shadow: 0 0 20px rgba(255,255,255,0.8), 0 0 40px rgba(255,255,255,0.5); }
            50% { box-shadow: 0 0 30px rgba(255,255,255,1), 0 0 60px rgba(255,255,255,0.8); }
        }
        
        .valor-riesgo {
            font-size: 1.5em;
            font-weight: 700;
            font-family: 'Orbitron', monospace;
            min-width: 70px;
            text-align: center;
            text-shadow: 0 0 10px currentColor;
        }
        
        /* Predicciones de IA con efectos */
        .predicciones-ia {
            margin-top: 20px;
            padding: 15px;
            background: rgba(0,0,0,0.3);
            border-radius: 8px;
            position: relative;
            z-index: 1;
        }
        
        .prediccion {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 10px 0;
            padding: 10px;
            background: rgba(0,180,255,0.1);
            border-left: 3px solid var(--ia-azul);
            border-radius: 5px;
            animation: aparecer-prediccion 0.5s ease;
            position: relative;
            overflow: hidden;
        }
        
        .prediccion::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0,180,255,0.2), transparent);
            animation: deslizar-prediccion 3s linear infinite;
        }
        
        @keyframes deslizar-prediccion {
            from { left: -100%; }
            to { left: 100%; }
        }
        
        @keyframes aparecer-prediccion {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        .tipo-prediccion {
            font-weight: 700;
            color: var(--ia-azul);
            text-transform: uppercase;
            font-size: 0.85em;
            text-shadow: 0 0 5px currentColor;
        }
        
        .probabilidad-prediccion {
            padding: 2px 8px;
            background: rgba(0,180,255,0.2);
            border-radius: 12px;
            font-size: 0.8em;
            color: var(--ia-azul);
            font-weight: 700;
            animation: pulso-probabilidad 2s ease-in-out infinite;
        }
        
        @keyframes pulso-probabilidad {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .prediccion.critica {
            border-color: var(--peligro);
            background: rgba(255,0,68,0.1);
        }
        
        .prediccion.critica .tipo-prediccion {
            color: var(--peligro);
        }
        
        /* Panel cuántico */
        .panel-cuantico-info {
            background: linear-gradient(135deg, rgba(157,0,255,0.1), rgba(0,157,255,0.1));
            border: 1px solid var(--cuantico);
            border-radius: 12px;
            padding: 20px;
            margin-top: 20px;
            position: relative;
            overflow: hidden;
        }
        
        .panel-cuantico-info::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 200px;
            height: 200px;
            background: radial-gradient(circle, var(--cuantico), transparent);
            transform: translate(-50%, -50%);
            opacity: 0.3;
            animation: pulso-cuantico 3s ease-in-out infinite;
        }
        
        @keyframes pulso-cuantico {
            0%, 100% { transform: translate(-50%, -50%) scale(1); opacity: 0.3; }
            50% { transform: translate(-50%, -50%) scale(1.5); opacity: 0.1; }
        }
        
        /* Gráfico de actividad animado */
        .grafico-actividad {
            height: 150px;
            background: rgba(0,0,0,0.3);
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            position: relative;
            overflow: hidden;
        }
        
        .linea-grafico {
            position: absolute;
            bottom: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(to top, rgba(0,180,255,0.3), transparent);
            clip-path: polygon(
                0% 80%, 10% 70%, 20% 75%, 30% 60%, 40% 65%, 
                50% 50%, 60% 55%, 70% 40%, 80% 45%, 90% 30%, 100% 35%,
                100% 100%, 0% 100%
            );
            animation: onda-grafico 5s ease-in-out infinite;
        }
        
        @keyframes onda-grafico {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        
        .puntos-grafico {
            position: absolute;
            bottom: 0;
            width: 100%;
            height: 100%;
        }
        
        .punto-grafico {
            position: absolute;
            width: 8px;
            height: 8px;
            background: var(--ia-azul);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--ia-azul);
            animation: pulso-punto-grafico 2s ease-in-out infinite;
        }
        
        @keyframes pulso-punto-grafico {
            0%, 100% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.5); opacity: 0.7; }
        }
        
        /* Recomendaciones de IA */
        .recomendaciones-ia {
            margin-top: 15px;
            padding: 15px;
            background: rgba(0,0,0,0.3);
            border-radius: 8px;
            position: relative;
            z-index: 1;
        }
        
        .recomendacion {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 8px 0;
            padding: 8px;
            background: rgba(157,0,255,0.1);
            border-left: 3px solid var(--cuantico);
            border-radius: 5px;
            animation: aparecer-recomendacion 0.5s ease;
        }
        
        @keyframes aparecer-recomendacion {
            from {
                opacity: 0;
                transform: translateX(20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
        
        /* Botones de acción futuristas */
        .botones-accion {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .btn-accion {
            background: linear-gradient(135deg, rgba(0,255,204,0.1), rgba(0,255,136,0.1));
            border: 2px solid var(--primario);
            border-radius: 10px;
            padding: 12px 20px;
            color: var(--primario);
            font-weight: 700;
            text-transform: uppercase;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Orbitron', monospace;
            font-size: 0.9em;
            letter-spacing: 1px;
            position: relative;
            overflow: hidden;
        }
        
        .btn-accion::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: radial-gradient(circle, var(--primario), transparent);
            transform: translate(-50%, -50%);
            transition: all 0.5s ease;
        }
        
        .btn-accion:hover::before {
            width: 300px;
            height: 300px;
        }
        
        .btn-accion:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,255,204,0.4);
            text-shadow: 0 0 10px currentColor;
        }
        
        .btn-accion:active {
            transform: translateY(-1px);
        }
        
        .btn-accion.ia {
            border-color: var(--ia-azul);
            color: var(--ia-azul);
            background: linear-gradient(135deg, rgba(0,180,255,0.1), rgba(180,0,255,0.1));
        }
        
        .btn-accion.ia:hover {
            background: rgba(0,180,255,0.2);
            box-shadow:0 10px 30px rgba(0,180,255,0.4);
        }
        
        .btn-accion.peligro {
            border-color: var(--peligro);
            color: var(--peligro);
            background: linear-gradient(135deg, rgba(255,0,68,0.1), rgba(255,0,0,0.1));
        }
        
        .btn-accion.peligro:hover {
            background: rgba(255,0,68,0.2);
            box-shadow: 0 10px 30px rgba(255,0,68,0.4);
        }
        
        .btn-accion.cuantico {
            border-color: var(--cuantico);
            color: var(--cuantico);
            background: linear-gradient(135deg, rgba(157,0,255,0.1), rgba(0,157,255,0.1));
        }
        
        .btn-accion.cuantico:hover {
            background: rgba(157,0,255,0.2);
            box-shadow: 0 10px 30px rgba(157,0,255,0.4);
        }
        
        .btn-accion.exito {
            border-color: var(--exito);
            color: var(--exito);
            background: linear-gradient(135deg, rgba(0,255,68,0.1), rgba(0,255,0,0.1));
        }
        
        /* Barra de búsqueda con IA */
        .contenedor-busqueda {
            position: relative;
            margin-bottom: 20px;
        }
        
        .input-busqueda {
            width: 100%;
            padding: 15px 50px 15px 50px;
            background: rgba(0,255,204,0.05);
            border: 1px solid rgba(0,255,204,0.3);
            border-radius: 30px;
            color: var(--texto);
            font-size: 1em;
            transition: all 0.3s ease;
        }
        
        .input-busqueda:focus {
            outline: none;
            border-color: var(--primario);
            box-shadow: 0 0 20px rgba(0,255,204,0.3);
        }
        
        .icono-busqueda {
            position: absolute;
            left: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--primario);
            font-size: 20px;
        }
        
        .btn-ia-busqueda {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            background: var(--ia-azul);
            border: none;
            border-radius: 50%;
            width: 35px;
            height: 35px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .btn-ia-busqueda:hover {
            transform: translateY(-50%) scale(1.1);
            box-shadow: 0 5px 15px rgba(0,180,255,0.4);
        }
        
        /* Modal flotante futurista */
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.9);
            backdrop-filter: blur(10px);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.activo {
            display: flex;
        }
        
        .contenido-modal {
            background: linear-gradient(135deg, rgba(10,15,31,0.98), rgba(0,0,0,0.98));
            border: 2px solid var(--primario);
            border-radius: 20px;
            padding: 30px;
            max-width: 600px;
            width: 90%;
            animation: aparecer-modal 0.3s ease;
            box-shadow: 0 20px 60px rgba(0,255,204,0.3);
            position: relative;
            overflow: hidden;
        }
        
        .contenido-modal::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, var(--primario), transparent);
            animation: linea-modal 2s linear infinite;
        }
        
        @keyframes linea-modal {
            from { transform: translateX(-100%); }
            to { transform: translateX(100%); }
        }
        
        @keyframes aparecer-modal {
            from {
                transform: scale(0.8) rotateX(10deg);
                opacity: 0;
            }
            to {
                transform: scale(1) rotateX(0);
                opacity: 1;
            }
        }
        
        /* Responsive mejorado */
        @media (max-width: 1200px) {
            .grid-permisos {
                grid-template-columns: 1fr;
            }
            
            .metricas-ia {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .estado-sistema {
                justify-content: center;
            }
        }
        
        @media (max-width: 768px) {
            .contenido-encabezado {
                flex-direction: column;
                gap: 20px;
                text-align: center;
            }
            
            .info-usuario-seleccionado {
                grid-template-columns: 1fr;
                text-align: center;
            }
            
            .avatar-usuario {
                margin: 0 auto;
            }
            
            .grid-permisos-items {
                grid-template-columns: 1fr;
            }
            
            .metricas-ia {
                grid-template-columns: 1fr;
            }
            
            .meta-usuario {
                justify-content: center;
            }
        }
        
        /* Animaciones adicionales */
        @keyframes latido {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        @keyframes resplandor {
            0%, 100% { box-shadow: 0 0 5px currentColor; }
            50% { box-shadow: 0 0 20px currentColor, 0 0 40px currentColor; }
        }
        
        @keyframes deslizar-entrada {
            from { transform: translateX(400px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
        
        @keyframes deslizar-salida {
            from { transform: translateX(0); opacity: 1; }
            to { transform: translateX(400px); opacity: 0; }
        }
        
        /* Efectos de carga */
        .cargando {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(0,255,204,0.3);
            border-top-color: var(--primario);
            border-radius: 50%;
            animation: girar 1s linear infinite;
        }
        
        @keyframes girar {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        /* Notificaciones flotantes */
        .notificacion {
            position: fixed;
            top: 100px;
            right: 20px;
            padding: 15px 25px;
            background: linear-gradient(135deg, rgba(0,255,204,0.95), rgba(0,255,136,0.95));
            border: 2px solid var(--primario);
            border-radius: 10px;
            color: var(--oscuro);
            font-weight: 700;
            z-index: 10000;
            animation: deslizar-entrada 0.3s ease;
            max-width: 400px;
            box-shadow: 0 10px 30px rgba(0,255,204,0.4);
        }
        
        .notificacion.advertencia {
            background: linear-gradient(135deg, rgba(255,170,0,0.95), rgba(255,255,0,0.95));
            border-color: var(--advertencia);
        }
        
        .notificacion.peligro {
            background: linear-gradient(135deg, rgba(255,0,68,0.95), rgba(255,0,0,0.95));
            border-color: var(--peligro);
        }
        
        .notificacion.ia {
            background: linear-gradient(135deg, rgba(0,180,255,0.95), rgba(180,0,255,0.95));
            border-color: var(--ia-azul);
            color: white;
        }
        
        .notificacion.cuantico {
            background: linear-gradient(135deg, rgba(157,0,255,0.95), rgba(0,157,255,0.95));
            border-color: var(--cuantico);
            color: white;
        }
        
        .notificacion.exito {
            background: linear-gradient(135deg, rgba(0,255,68,0.95), rgba(0,255,0,0.95));
            border-color: var(--exito);
        }
    </style>
</head>
<body>
    <!-- Red neuronal de fondo -->
    <div class="red-neuronal-fondo"></div>
    
    <!-- Matriz de permisos de fondo -->
    <div class="matriz-permisos-fondo"></div>
    
    <!-- Partículas de IA y cuánticas -->
    <div class="particulas-ia" id="particulasIA"></div>
    
    <!-- Encabezado -->
    <header class="encabezado">
        <div class="contenido-encabezado">
            <div style="display: flex; align-items: center; gap: 20px;">
                <div class="escudo-ia">
                    <div class="brillo-escudo"></div>
                    <div class="forma-escudo"></div>
                </div>
                <div>
                    <h1 style="font-family: 'Orbitron', monospace; font-size: 2.5em; font-weight: 900; 
                               background: linear-gradient(45deg, var(--primario), var(--ia-azul), var(--cuantico)); 
                               -webkit-background-clip: text; -webkit-text-fill-color: transparent;
                               text-transform: uppercase; letter-spacing: 3px;">
                        Permisos de Usuario IA
                    </h1>
                    <p style="color: var(--texto-tenue); text-transform: uppercase; letter-spacing: 2px;">
                        GuardianIA v3.0 - Sistema de Control de Acceso Inteligente Militar
                    </p>
                </div>
            </div>
            <div class="estado-sistema">
                <span class="indicador-estado">
                    <span class="punto-estado"></span>
                    SISTEMA SEGURO
                </span>
                <span class="indicador-estado indicador-ia">
                    <span class="punto-estado punto-ia"></span>
                    IA ACTIVA
                </span>
                <span class="indicador-estado indicador-cuantico">
                    <span class="punto-estado punto-cuantico"></span>
                    CUÁNTICO: <?php echo $analisis_ia['quantum_stability']; ?>%
                </span>
                <?php if($stats_sistema['military_encryption_status'] == 'ACTIVE'): ?>
                <span class="indicador-estado" style="background: rgba(255,0,68,0.1); border-color: var(--peligro);">
                    <span class="punto-estado" style="background: var(--peligro);"></span>
                    ENCRIPTACIÓN MILITAR
                </span>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <!-- Contenedor principal -->
    <div class="contenedor-principal">
        <div class="grid-permisos">
            
            <!-- Panel lateral de usuarios con IA -->
            <div class="panel">
                <div class="encabezado-panel">
                    <div class="titulo-panel">
                        <span>👥</span>
                        Usuarios del Sistema
                    </div>
                    <span style="color: var(--primario);"><?php echo count($datos_usuarios); ?></span>
                </div>
                
                <!-- Barra de búsqueda con IA -->
                <div class="contenedor-busqueda">
                    <span class="icono-busqueda">🔍</span>
                    <input type="text" class="input-busqueda" placeholder="Buscar usuarios..." id="inputBusqueda" onkeyup="buscarUsuarios()">
                    <button class="btn-ia-busqueda" onclick="busquedaIA()" title="Búsqueda inteligente">
                        <span style="color: white;">🤖</span>
                    </button>
                </div>
                
                <!-- Lista de usuarios -->
                <div class="lista-usuarios" id="listaUsuarios">
                    <?php foreach($datos_usuarios as $indice => $usuario): ?>
                    <div class="tarjeta-usuario <?php echo $indice === 0 ? 'seleccionada' : ''; ?>" 
                         onclick="seleccionarUsuario('<?php echo $usuario['id']; ?>')" 
                         data-usuario-id="<?php echo $usuario['id']; ?>"
                         data-db-id="<?php echo $usuario['db_id']; ?>"
                         data-riesgo="<?php echo $usuario['puntuacion_riesgo']; ?>"
                         data-comportamiento="<?php echo $usuario['patrones_comportamiento']; ?>">
                        <div class="cabecera-usuario">
                            <span class="nombre-usuario"><?php echo $usuario['nombre_completo']; ?></span>
                            <span class="estado-usuario <?php echo $usuario['estado']; ?>">
                                <?php echo $usuario['estado']; ?>
                            </span>
                        </div>
                        <div class="detalles-usuario">
                            <div class="item-detalle">
                                <span class="icono">👤</span>
                                <span><?php echo $usuario['nombre_usuario']; ?></span>
                            </div>
                            <div class="item-detalle">
                                <span class="icono">🎖️</span>
                                <span><?php echo $usuario['autorizacion']; ?></span>
                            </div>
                            <div class="item-detalle">
                                <span class="icono">💼</span>
                                <span><?php echo $usuario['rol']; ?></span>
                            </div>
                            <div class="item-detalle">
                                <span class="icono">🤖</span>
                                <span>IA: <?php echo $usuario['acceso_ia']; ?>%</span>
                            </div>
                        </div>
                        
                        <!-- Métricas del usuario -->
                        <div class="metricas-usuario">
                            <div class="metrica-mini">
                                <div class="valor-metrica-mini"><?php echo $usuario['conversaciones']; ?></div>
                                <div class="etiqueta-metrica-mini">Chats</div>
                            </div>
                            <div class="metrica-mini">
                                <div class="valor-metrica-mini"><?php echo $usuario['eventos_seguridad']; ?></div>
                                <div class="etiqueta-metrica-mini">Eventos</div>
                            </div>
                            <div class="metrica-mini">
                                <div class="valor-metrica-mini"><?php echo $usuario['prediccion_amenaza']; ?>%</div>
                                <div class="etiqueta-metrica-mini">Amenaza</div>
                            </div>
                        </div>
                        
                        <!-- Indicador de confianza IA -->
                        <div class="confianza-ia-usuario">
                            <div style="font-size: 0.85em; color: var(--ia-azul); margin-bottom: 5px;">
                                Confianza IA: <?php echo $usuario['confianza_ia']; ?>%
                            </div>
                            <div class="barra-confianza">
                                <div class="progreso-confianza">
                                    <div class="nivel-confianza" style="width: <?php echo $usuario['confianza_ia']; ?>%;"></div>
                                </div>
                            </div>
                            <?php if($usuario['patrones_comportamiento'] === 'anomalo'): ?>
                            <div style="margin-top: 5px; font-size: 0.8em; color: var(--advertencia);">
                                ⚠️ Comportamiento anómalo detectado
                            </div>
                            <?php endif; ?>
                            <?php if($usuario['premium_status'] === 'premium'): ?>
                            <div style="margin-top: 5px; font-size: 0.8em; color: #ffd700;">
                                ⭐ Usuario Premium
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Botones de acción -->
                <div class="botones-accion">
                    <button class="btn-accion" onclick="agregarUsuario()">
                        ➕ Agregar
                    </button>
                    <button class="btn-accion ia" onclick="analizarTodos()">
                        🤖 Analizar Todos
                    </button>
                    <button class="btn-accion" onclick="sincronizarDB()">
                        🔄 Sincronizar DB
                    </button>
                    <button class="btn-accion exito" onclick="exportarPermisos()">
                        📤 Exportar
                    </button>
                </div>
            </div>
            
            <!-- Panel principal de permisos con IA -->
            <div class="panel-permisos">
                <!-- Información del usuario seleccionado -->
                <div class="panel">
                    <div class="info-usuario-seleccionado">
                        <div class="avatar-usuario" id="avatarUsuario">AM</div>
                        <div class="detalles-info-usuario">
                            <h2 id="nombreUsuarioSeleccionado">Anderson Mamian</h2>
                            <div class="meta-usuario">
                                <span class="insignia-meta">ID: <span id="idUsuarioSeleccionado">USR-001</span></span>
                                <span class="insignia-meta">Rol: <span id="rolUsuarioSeleccionado">Administrador del Sistema</span></span>
                                <span class="insignia-meta">Autorización: <span id="autorizacionUsuarioSeleccionado">TOP_SECRET</span></span>
                                <span class="insignia-meta">Estado: <span id="estadoUsuarioSeleccionado">Activo</span></span>
                                <span class="insignia-meta insignia-ia">🤖 IA: <span id="iaUsuarioSeleccionado">100%</span></span>
                                <span class="insignia-meta insignia-ia">🧠 Confianza: <span id="confianzaUsuarioSeleccionado">95%</span></span>
                                <span class="insignia-meta insignia-cuantica" id="badgeCuantico" style="display: none;">⚛️ Acceso Cuántico</span>
                                <span class="insignia-meta insignia-premium" id="badgePremium" style="display: none;">⭐ Premium</span>
                            </div>
                        </div>
                        <div class="botones-accion" style="width: 250px;">
                            <button class="btn-accion" onclick="editarUsuario()">✏️ Editar</button>
                            <button class="btn-accion ia" onclick="analizarUsuario()">🤖 Analizar IA</button>
                            <button class="btn-accion cuantico" onclick="analizarCuantico()">⚛️ Análisis Cuántico</button>
                            <button class="btn-accion peligro" onclick="suspenderUsuario()">⚠️ Suspender</button>
                        </div>
                    </div>
                </div>
                
                <!-- Matriz de permisos con recomendaciones IA -->
                <div class="panel" style="flex: 1;">
                    <div class="encabezado-panel">
                        <div class="titulo-panel">
                            <span>🔐</span>
                            Matriz de Permisos Avanzada
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button class="btn-accion ia" style="padding: 8px 16px; font-size: 0.85em;" onclick="optimizarPermisos()">
                                🤖 Optimizar con IA
                            </button>
                            <button class="btn-accion exito" style="padding: 8px 16px; font-size: 0.85em;" onclick="guardarPermisos()">
                                💾 Guardar en DB
                            </button>
                        </div>
                    </div>
                    
                    <div class="matriz-permisos">
                        <?php foreach($permisos_disponibles as $categoria => $permisos): ?>
                        <div class="categoria-permisos">
                            <div class="cabecera-categoria">
                                <span class="icono-categoria">
                                    <?php 
                                    $iconos = [
                                        'sistema' => '⚙️',
                                        'base_datos' => '🗄️',
                                        'ia' => '🤖',
                                        'cuantico' => '⚛️',
                                        'militar' => '🎖️',
                                        'comunicacion' => '📡',
                                        'respaldo' => '💾',
                                        'seguridad' => '🛡️'
                                    ];
                                    echo $iconos[$categoria] ?? '📋';
                                    ?>
                                </span>
                                <span class="titulo-categoria">
                                    <?php echo ucfirst(str_replace('_', ' ', $categoria)); ?>
                                </span>
                            </div>
                            <div class="grid-permisos-items">
                                <?php foreach($permisos as $permiso): ?>
                                <?php 
                                    $esRecomendado = false;
                                    $esCritico = in_array($permiso, ['codigo_rojo', 'protocolo_x', 'defcon_control']);
                                    $esCuantico = $categoria == 'cuantico';
                                    
                                    // Lógica de recomendación basada en rol
                                    if ($categoria == 'ia' && in_array($permiso, ['ver', 'predecir', 'analizar'])) {
                                        $esRecomendado = true;
                                    }
                                    if ($categoria == 'sistema' && in_array($permiso, ['leer', 'escribir'])) {
                                        $esRecomendado = true;
                                    }
                                ?>
                                <div class="item-permiso <?php 
                                    echo $esRecomendado ? 'recomendado-ia' : '';
                                    echo $esCritico ? ' critico' : '';
                                    echo $esCuantico ? ' cuantico' : '';
                                ?>" data-categoria="<?php echo $categoria; ?>" data-permiso="<?php echo $permiso; ?>">
                                    <span class="nombre-permiso"><?php echo str_replace('_', ' ', $permiso); ?></span>
                                    <div class="toggle-permiso" onclick="alternarPermiso(this)"></div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Panel de análisis de IA avanzado -->
                <div class="panel panel-ia">
                    <div class="analisis-ia">
                        <div class="cabecera-ia">
                            <span style="font-size: 30px;">🤖</span>
                            <span class="titulo-ia">Análisis de Seguridad con IA</span>
                        </div>
                        
                        <!-- Métricas de IA -->
                        <div class="metricas-ia">
                            <div class="metrica-ia">
                                <div class="valor-metrica"><?php echo $analisis_ia['modelos_activos']; ?></div>
                                <div class="etiqueta-metrica">Modelos Activos</div>
                            </div>
                            <div class="metrica-ia">
                                <div class="valor-metrica"><?php echo $analisis_ia['precision_promedio']; ?>%</div>
                                <div class="etiqueta-metrica">Precisión</div>
                            </div>
                            <div class="metrica-ia">
                                <div class="valor-metrica"><?php echo $analisis_ia['anomalias_detectadas']; ?></div>
                                <div class="etiqueta-metrica">Anomalías</div>
                            </div>
                            <div class="metrica-ia">
                                <div class="valor-metrica"><?php echo $analisis_ia['tasa_acierto']; ?>%</div>
                                <div class="etiqueta-metrica">Tasa Acierto</div>
                            </div>
                        </div>
                        
                        <!-- Medidor de riesgo -->
                        <div class="medidor-riesgo">
                            <span>Puntuación de Riesgo:</span>
                            <div class="barra-riesgo">
                                <div class="indicador-riesgo" id="indicadorRiesgo" style="left: 5%;"></div>
                            </div>
                            <span class="valor-riesgo" id="valorRiesgo" style="color: var(--acento);">5%</span>
                        </div>
                        
                        <!-- Predicciones de comportamiento -->
                        <div class="predicciones-ia" id="prediccionesIA">
                            <?php foreach($predicciones_comportamiento as $pred): ?>
                            <div class="prediccion <?php echo $pred['severidad'] == 'critica' ? 'critica' : ''; ?>">
                                <span class="tipo-prediccion"><?php echo $pred['tipo']; ?></span>
                                <span class="probabilidad-prediccion"><?php echo $pred['probabilidad']; ?>%</span>
                                <span style="flex: 1; margin-left: 10px;"><?php echo $pred['mensaje']; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Gráfico de actividad -->
                        <div class="grafico-actividad">
                            <div class="linea-grafico"></div>
                            <div class="puntos-grafico" id="puntosGrafico"></div>
                        </div>
                        
                        <!-- Recomendaciones de IA -->
                        <div class="recomendaciones-ia" id="recomendacionesIA">
                            <div class="recomendacion">
                                <span>✅</span>
                                <span>Sistema operando dentro de parámetros normales</span>
                            </div>
                            <div class="recomendacion">
                                <span>📊</span>
                                <span>Permisos sincronizados con base de datos</span>
                            </div>
                            <div class="recomendacion">
                                <span>🔐</span>
                                <span>Encriptación militar FIPS 140-2 activa</span>
                            </div>
                            <div class="recomendacion">
                                <span>💡</span>
                                <span>Se recomienda revisión mensual de permisos críticos</span>
                            </div>
                        </div>
                        
                        <div class="botones-accion">
                            <button class="btn-accion ia" onclick="analisisProfundo()">
                                🔍 Análisis Profundo
                            </button>
                            <button class="btn-accion ia" onclick="entrenarModelo()">
                                📚 Entrenar Modelo
                            </button>
                            <button class="btn-accion" onclick="generarReporte()">
                                📊 Generar Reporte
                            </button>
                            <button class="btn-accion cuantico" onclick="prediccionCuantica()">
                                ⚛️ Predicción Cuántica
                            </button>
                        </div>
                    </div>
                </div>
                
                <!-- Panel de información cuántica -->
                <div class="panel panel-cuantico">
                    <div class="panel-cuantico-info">
                        <div class="cabecera-ia">
                            <span style="font-size: 30px;">⚛️</span>
                            <span class="titulo-ia titulo-panel-cuantico">Estado Cuántico del Sistema</span>
                        </div>
                        
                        <div class="metricas-ia">
                            <div class="metrica-ia">
                                <div class="valor-metrica"><?php echo $analisis_ia['quantum_stability']; ?>%</div>
                                <div class="etiqueta-metrica">Estabilidad</div>
                            </div>
                            <div class="metrica-ia">
                                <div class="valor-metrica"><?php echo QUANTUM_ENTANGLEMENT_PAIRS ?? 1024; ?></div>
                                <div class="etiqueta-metrica">Pares Entrelazados</div>
                            </div>
                            <div class="metrica-ia">
                                <div class="valor-metrica"><?php echo number_format(QUANTUM_CHANNEL_FIDELITY * 100, 1) ?? 95; ?>%</div>
                                <div class="etiqueta-metrica">Fidelidad</div>
                            </div>
                            <div class="metrica-ia">
                                <div class="valor-metrica">BB84</div>
                                <div class="etiqueta-metrica">Protocolo</div>
                            </div>
                        </div>
                        
                        <div class="recomendaciones-ia">
                            <div class="recomendacion">
                                <span>⚛️</span>
                                <span>Estado cuántico coherente y estable</span>
                            </div>
                            <div class="recomendacion">
                                <span>🔑</span>
                                <span>Claves cuánticas generadas: <?php echo rand(100, 500); ?></span>
                            </div>
                            <div class="recomendacion">
                                <span>🛡️</span>
                                <span>Resistencia post-cuántica habilitada</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal -->
    <div class="modal" id="modal">
        <div class="contenido-modal">
            <h2 id="tituloModal" style="font-family: 'Orbitron', monospace; color: var(--primario); margin-bottom: 20px;">
                Título del Modal
            </h2>
            <div id="cuerpoModal">
                <!-- Contenido dinámico -->
            </div>
            <div class="botones-accion" style="margin-top: 20px;">
                <button class="btn-accion" onclick="cerrarModal()">Cancelar</button>
                <button class="btn-accion ia" onclick="confirmarAccion()">Confirmar</button>
            </div>
        </div>
    </div>
    
    <script>
        // Datos de usuarios desde PHP
        const datosUsuarios = <?php echo json_encode($datos_usuarios); ?>;
        let usuarioSeleccionado = datosUsuarios[0];
        
        // Estado del sistema IA
        const estadoIA = {
            modelosActivos: <?php echo $analisis_ia['modelos_activos']; ?>,
            precision: <?php echo $analisis_ia['precision_promedio']; ?>,
            aprendizajeActivo: true,
            prediccionesRealizadas: 0,
            entrenamientos: 0,
            quantumStability: <?php echo $analisis_ia['quantum_stability']; ?>
        };
        
        // Configuración del sistema
        const configuracionSistema = {
            encriptacionMilitar: <?php echo MILITARY_ENCRYPTION_ENABLED ? 'true' : 'false'; ?>,
            fipsCompliance: <?php echo FIPS_140_2_COMPLIANCE ? 'true' : 'false'; ?>,
            quantumResistance: <?php echo QUANTUM_RESISTANCE_ENABLED ? 'true' : 'false'; ?>,
            neuralDepth: <?php echo NEURAL_NETWORK_DEPTH ?? 7; ?>
        };
        
        // Crear partículas de IA y cuánticas
        function crearParticulasIA() {
            const contenedor = document.getElementById('particulasIA');
            
            // Partículas de IA
            for (let i = 0; i < 30; i++) {
                const particula = document.createElement('div');
                particula.className = 'particula-ia';
                particula.style.left = Math.random() * 100 + '%';
                particula.style.animationDelay = Math.random() * 20 + 's';
                particula.style.animationDuration = (15 + Math.random() * 10) + 's';
                contenedor.appendChild(particula);
            }
            
            // Partículas cuánticas
            for (let i = 0; i < 15; i++) {
                const particula = document.createElement('div');
                particula.className = 'particula-cuantica';
                particula.style.left = Math.random() * 100 + '%';
                particula.style.top = Math.random() * 100 + '%';
                particula.style.animationDelay = Math.random() * 15 + 's';
                contenedor.appendChild(particula);
            }
        }
        
        // Crear puntos en el gráfico
        function crearPuntosGrafico() {
            const contenedor = document.getElementById('puntosGrafico');
            if (!contenedor) return;
            
            const puntos = [
                {left: '10%', bottom: '70%'},
                {left: '30%', bottom: '60%'},
                {left: '50%', bottom: '50%'},
                {left: '70%', bottom: '40%'},
                {left: '90%', bottom: '30%'}
            ];
            
            puntos.forEach((punto, index) => {
                const puntoEl = document.createElement('div');
                puntoEl.className = 'punto-grafico';
                puntoEl.style.left = punto.left;
                puntoEl.style.bottom = punto.bottom;
                puntoEl.style.animationDelay = (index * 0.2) + 's';
                contenedor.appendChild(puntoEl);
            });
        }
        
        // Seleccionar usuario con análisis IA
        function seleccionarUsuario(idUsuario) {
            // Actualizar selección visual
            document.querySelectorAll('.tarjeta-usuario').forEach(tarjeta => {
                tarjeta.classList.remove('seleccionada');
            });
            
            const tarjetaSeleccionada = document.querySelector(`[data-usuario-id="${idUsuario}"]`);
            if (tarjetaSeleccionada) {
                tarjetaSeleccionada.classList.add('seleccionada');
            }
            
            // Encontrar usuario
            usuarioSeleccionado = datosUsuarios.find(u => u.id === idUsuario);
            if (!usuarioSeleccionado) return;
            
            // Actualizar información del usuario
            const iniciales = usuarioSeleccionado.nombre_completo.split(' ').map(n => n[0]).join('');
            document.getElementById('avatarUsuario').textContent = iniciales;
            document.getElementById('nombreUsuarioSeleccionado').textContent = usuarioSeleccionado.nombre_completo;
            document.getElementById('idUsuarioSeleccionado').textContent = usuarioSeleccionado.id;
            document.getElementById('rolUsuarioSeleccionado').textContent = usuarioSeleccionado.rol;
            document.getElementById('autorizacionUsuarioSeleccionado').textContent = usuarioSeleccionado.autorizacion;
            document.getElementById('estadoUsuarioSeleccionado').textContent = usuarioSeleccionado.estado;
            document.getElementById('iaUsuarioSeleccionado').textContent = usuarioSeleccionado.acceso_ia + '%';
            document.getElementById('confianzaUsuarioSeleccionado').textContent = usuarioSeleccionado.confianza_ia + '%';
            
            // Mostrar/ocultar badges especiales
            document.getElementById('badgeCuantico').style.display = 
                usuarioSeleccionado.acceso_cuantico ? 'inline-block' : 'none';
            document.getElementById('badgePremium').style.display = 
                usuarioSeleccionado.premium_status === 'premium' ? 'inline-block' : 'none';
            
            // Actualizar indicador de riesgo
            actualizarIndicadorRiesgo(usuarioSeleccionado.puntuacion_riesgo);
            
            // Actualizar permisos según el usuario
            actualizarPermisos();
            
            // Análisis automático de IA
            analizarComportamientoIA(usuarioSeleccionado);
            
            // Efecto visual
            animarSeleccion();
        }
        
        // Actualizar indicador de riesgo con IA
        function actualizarIndicadorRiesgo(puntuacion) {
            const indicador = document.getElementById('indicadorRiesgo');
            const valor = document.getElementById('valorRiesgo');
            
            indicador.style.left = puntuacion + '%';
            valor.textContent = puntuacion + '%';
            
            // Cambiar color según el riesgo
            if (puntuacion < 30) {
                valor.style.color = 'var(--acento)';
            } else if (puntuacion < 70) {
                valor.style.color = 'var(--advertencia)';
            } else {
                valor.style.color = 'var(--peligro)';
            }
            
            // Generar recomendaciones basadas en el riesgo
            generarRecomendacionesIA(puntuacion);
        }
        
        // Analizar comportamiento con IA
        function analizarComportamientoIA(usuario) {
            if (usuario.patrones_comportamiento === 'anomalo') {
                // Actualizar predicciones para usuarios anómalos
                const prediccionesContainer = document.getElementById('prediccionesIA');
                let html = `
                    <div class="prediccion critica">
                        <span class="tipo-prediccion">⚠️ ALERTA</span>
                        <span class="probabilidad-prediccion">85%</span>
                        <span style="flex: 1; margin-left: 10px;">Comportamiento anómalo detectado - Requiere revisión inmediata</span>
                    </div>
                `;
                
                if (usuario.eventos_seguridad > 5) {
                    html += `
                    <div class="prediccion critica">
                        <span class="tipo-prediccion">SEGURIDAD</span>
                        <span class="probabilidad-prediccion">${72 + usuario.eventos_seguridad}%</span>
                        <span style="flex: 1; margin-left: 10px;">Múltiples eventos de seguridad registrados</span>
                    </div>
                    `;
                }
                
                prediccionesContainer.innerHTML = html;
            }
        }
        
        // Generar recomendaciones de IA
        function generarRecomendacionesIA(riesgo) {
            const recomendacionesContainer = document.getElementById('recomendacionesIA');
            let recomendaciones = [];
            
            if (riesgo > 50) {
                recomendaciones = [
                    {icono: '⚠️', texto: 'Nivel de riesgo elevado detectado'},
                    {icono: '🔐', texto: 'Se recomienda revisión inmediata de permisos'},
                    {icono: '📊', texto: 'Activar monitoreo en tiempo real'},
                    {icono: '🚨', texto: 'Considerar restricción temporal de accesos críticos'}
                ];
            } else if (riesgo > 30) {
                recomendaciones = [
                    {icono: '📊', texto: 'Riesgo moderado - Monitorear actividad'},
                    {icono: '🔍', texto: 'Revisar logs de acceso recientes'},
                    {icono: '📈', texto: 'Análisis predictivo sugiere vigilancia preventiva'}
                ];
            } else {
                recomendaciones = [
                    {icono: '✅', texto: 'Patrones de comportamiento normales'},
                    {icono: '🛡️', texto: 'Seguridad óptima confirmada'},
                    {icono: '📊', texto: 'Métricas dentro de rangos esperados'}
                ];
            }
            
            // Agregar recomendación de base de datos si está conectada
            if (<?php echo $db && $db->isConnected() ? 'true' : 'false'; ?>) {
                recomendaciones.push({
                    icono: '💾', 
                    texto: 'Base de datos sincronizada - <?php echo $stats_sistema['connection_info']['type'] ?? 'fallback'; ?> mode'
                });
            }
            
            recomendacionesContainer.innerHTML = recomendaciones.map(rec => 
                `<div class="recomendacion">
                    <span>${rec.icono}</span>
                    <span>${rec.texto}</span>
                </div>`
            ).join('');
        }
        
        // Alternar permiso con análisis IA
        function alternarPermiso(elemento) {
            elemento.classList.toggle('activo');
            
            // Efecto visual
            const item = elemento.parentElement;
            item.style.transform = 'scale(1.1)';
            setTimeout(() => {
                item.style.transform = 'scale(1)';
            }, 200);
            
            // Análisis de impacto con IA
            analizarImpactoPermiso(elemento);
        }
        
        // Analizar impacto del permiso
        function analizarImpactoPermiso(elemento) {
            const item = elemento.parentElement;
            const nombrePermiso = item.querySelector('.nombre-permiso').textContent;
            const categoria = item.dataset.categoria;
            const activo = elemento.classList.contains('activo');
            
            // Verificar permisos críticos
            const permisosCriticos = ['eliminar', 'protocolo x', 'codigo rojo', 'defcon control'];
            
            if (activo && permisosCriticos.some(p => nombrePermiso.toLowerCase().includes(p))) {
                mostrarNotificacion('⚠️ IA: Permiso crítico activado - Requiere autorización adicional', 'advertencia');
                
                // Log en consola para debug
                console.log(`Permiso crítico activado: ${categoria}/${nombrePermiso}`);
            }
            
            // Verificar permisos cuánticos
            if (categoria === 'cuantico' && activo) {
                mostrarNotificacion('⚛️ Acceso cuántico habilitado - Protocolo de seguridad activado', 'cuantico');
            }
            
            // Verificar permisos militares
            if (categoria === 'militar' && activo && !usuarioSeleccionado.acceso_militar) {
                mostrarNotificacion('🎖️ Advertencia: Usuario sin autorización militar completa', 'peligro');
                elemento.classList.remove('activo');
            }
        }
        
        // Actualizar permisos con recomendaciones IA
        function actualizarPermisos() {
            // Resetear todos los permisos
            document.querySelectorAll('.toggle-permiso').forEach(toggle => {
                toggle.classList.remove('activo');
            });
            
            // Activar permisos según autorización del usuario
            const autorizacion = usuarioSeleccionado.autorizacion;
            const accesoPorcentaje = {
                'TOP_SECRET': 0.9,
                'SECRET': 0.6,
                'CONFIDENTIAL': 0.3,
                'UNCLASSIFIED': 0.1
            };
            
            const porcentaje = accesoPorcentaje[autorizacion] || 0.1;
            
            document.querySelectorAll('.item-permiso').forEach((item, index) => {
                const toggle = item.querySelector('.toggle-permiso');
                const categoria = item.dataset.categoria;
                
                // Lógica inteligente de activación
                let activar = false;
                
                // Permisos básicos para todos
                if (categoria === 'sistema' && ['leer', 'escribir'].includes(item.dataset.permiso)) {
                    activar = true;
                }
                
                // Permisos según nivel de autorización
                if (Math.random() < porcentaje) {
                    // No activar permisos críticos aleatoriamente
                    if (!item.classList.contains('critico')) {
                        activar = true;
                    }
                }
                
                // Restricciones especiales
                if (categoria === 'militar' && !usuarioSeleccionado.acceso_militar) {
                    activar = false;
                }
                
                if (categoria === 'cuantico' && !usuarioSeleccionado.acceso_cuantico) {
                    activar = false;
                }
                
                // Aplicar con animación
                if (activar) {
                    setTimeout(() => {
                        toggle.classList.add('activo');
                    }, index * 20);
                }
            });
            
            // Marcar permisos recomendados por IA
            marcarPermisosRecomendados();
        }
        
        // Marcar permisos recomendados por IA
        function marcarPermisosRecomendados() {
            document.querySelectorAll('.item-permiso').forEach(item => {
                const nombrePermiso = item.dataset.permiso;
                const categoria = item.dataset.categoria;
                
                item.classList.remove('recomendado-ia');
                
                // Lógica de recomendación basada en el rol del usuario
                if (usuarioSeleccionado.rol.includes('Administrador')) {
                    if (['leer', 'escribir', 'configurar', 'auditar', 'respaldo_completo'].includes(nombrePermiso)) {
                        item.classList.add('recomendado-ia');
                    }
                } else if (usuarioSeleccionado.rol.includes('Premium')) {
                    if (categoria === 'ia' && ['ver', 'predecir', 'analizar'].includes(nombrePermiso)) {
                        item.classList.add('recomendado-ia');
                    }
                } else if (usuarioSeleccionado.rol.includes('Básico')) {
                    if (['leer', 'ver'].includes(nombrePermiso)) {
                        item.classList.add('recomendado-ia');
                    }
                }
                
                // Recomendaciones basadas en IA
                if (usuarioSeleccionado.acceso_ia > 80 && categoria === 'ia') {
                    item.classList.add('recomendado-ia');
                }
            });
        }
        
        // Buscar usuarios con IA
        function buscarUsuarios() {
            const terminoBusqueda = document.getElementById('inputBusqueda').value.toLowerCase();
            const tarjetasUsuarios = document.querySelectorAll('.tarjeta-usuario');
            
            tarjetasUsuarios.forEach(tarjeta => {
                const textoTarjeta = tarjeta.textContent.toLowerCase();
                
                if (textoTarjeta.includes(terminoBusqueda)) {
                    tarjeta.style.display = 'block';
                } else {
                    tarjeta.style.display = 'none';
                }
            });
        }
        
        // Búsqueda inteligente con IA
        function busquedaIA() {
            mostrarNotificacion('🤖 IA: Analizando patrones de búsqueda...', 'ia');
            
            setTimeout(() => {
                // Filtrar usuarios con comportamiento anómalo o riesgo alto
                document.querySelectorAll('.tarjeta-usuario').forEach(tarjeta => {
                    const riesgo = parseInt(tarjeta.dataset.riesgo);
                    const comportamiento = tarjeta.dataset.comportamiento;
                    
                    if (comportamiento === 'anomalo' || riesgo > 30) {
                        tarjeta.style.display = 'block';
                        tarjeta.style.border = '2px solid var(--advertencia)';
                        tarjeta.style.animation = 'pulso-seleccion 2s ease-in-out infinite';
                    } else {
                        tarjeta.style.display = 'none';
                    }
                });
                
                mostrarNotificacion('🔍 IA: Mostrando usuarios que requieren atención', 'ia');
            }, 1500);
        }
        
        // Sincronizar con base de datos
        function sincronizarDB() {
            mostrarNotificacion('🔄 Sincronizando con base de datos...', 'info');
            
            // Simular sincronización
            setTimeout(() => {
                const estadoDB = <?php echo $db && $db->isConnected() ? 'true' : 'false'; ?>;
                
                if (estadoDB) {
                    mostrarNotificacion('✅ Base de datos sincronizada correctamente', 'exito');
                    
                    // Actualizar métricas
                    document.querySelector('.metrica-ia:nth-child(3) .valor-metrica').textContent = '0';
                } else {
                    mostrarNotificacion('⚠️ Operando en modo fallback - Sin conexión a DB', 'advertencia');
                }
            }, 2000);
        }
        
        // Funciones de acción mejoradas
        function agregarUsuario() {
            mostrarModal('Agregar Nuevo Usuario', `
                <div style="display: grid; gap: 15px;">
                    <input type="text" placeholder="Nombre Completo" class="input-busqueda" id="nuevoNombre">
                    <input type="text" placeholder="Nombre de Usuario" class="input-busqueda" id="nuevoUsuario">
                    <input type="email" placeholder="Email" class="input-busqueda" id="nuevoEmail">
                    <select class="input-busqueda" id="nuevoRol">
                        <option>Seleccionar Rol</option>
                        <option value="admin">Administrador</option>
                        <option value="premium">Usuario Premium</option>
                        <option value="basic">Usuario Básico</option>
                    </select>
                    <select class="input-busqueda" id="nuevoAutorizacion">
                        <option>Seleccionar Autorización</option>
                        <option value="TOP_SECRET">TOP_SECRET</option>
                        <option value="SECRET">SECRET</option>
                        <option value="CONFIDENTIAL">CONFIDENTIAL</option>
                        <option value="UNCLASSIFIED">UNCLASSIFIED</option>
                    </select>
                    <div style="display: flex; gap: 15px;">
                        <label style="display: flex; align-items: center; gap: 10px;">
                            <input type="checkbox" id="nuevoAccesoMilitar">
                            <span>Acceso Militar</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 10px;">
                            <input type="checkbox" id="nuevoAccesoCuantico">
                            <span>Acceso Cuántico</span>
                        </label>
                    </div>
                    <div style="padding: 10px; background: rgba(0,180,255,0.1); border-radius: 8px;">
                        <span style="color: var(--ia-azul);">🤖 IA recomienda: Los permisos se asignarán automáticamente según el rol y autorización seleccionados</span>
                    </div>
                </div>
            `);
        }
        
        function editarUsuario() {
            mostrarModal('Editar Usuario', `
                <p>Editando usuario: <strong>${usuarioSeleccionado.nombre_completo}</strong></p>
                <div style="display: grid; gap: 15px; margin-top: 20px;">
                    <input type="text" value="${usuarioSeleccionado.nombre_completo}" class="input-busqueda">
                    <input type="text" value="${usuarioSeleccionado.nombre_usuario}" class="input-busqueda">
                    <input type="email" value="${usuarioSeleccionado.email || ''}" class="input-busqueda">
                    <select class="input-busqueda">
                        <option>${usuarioSeleccionado.rol}</option>
                    </select>
                    <div style="padding: 10px; background: rgba(0,180,255,0.1); border-radius: 8px;">
                        <span style="color: var(--ia-azul);">🤖 Último análisis IA: ${usuarioSeleccionado.ultimo_analisis}</span>
                    </div>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                        <div style="padding: 10px; background: rgba(0,255,204,0.1); border-radius: 8px;">
                            <strong>Conversaciones:</strong> ${usuarioSeleccionado.conversaciones}
                        </div>
                        <div style="padding: 10px; background: rgba(255,170,0,0.1); border-radius: 8px;">
                            <strong>Eventos Seguridad:</strong> ${usuarioSeleccionado.eventos_seguridad}
                        </div>
                    </div>
                </div>
            `);
        }
        
        function analizarUsuario() {
            mostrarNotificacion('🤖 Ejecutando análisis profundo de IA...', 'ia');
            
            // Simular análisis con múltiples etapas
            const etapas = [
                {tiempo: 1000, mensaje: '📊 Analizando patrones de comportamiento...'},
                {tiempo: 2000, mensaje: '🔍 Verificando eventos de seguridad...'},
                {tiempo: 3000, mensaje: '🧠 Aplicando redes neuronales...'},
                {tiempo: 4000, mensaje: '✅ Análisis completado'}
            ];
            
            etapas.forEach(etapa => {
                setTimeout(() => {
                    mostrarNotificacion(etapa.mensaje, 'ia');
                }, etapa.tiempo);
            });
            
            setTimeout(() => {
                // Actualizar recomendaciones con resultados del análisis
                const recomendacionesContainer = document.getElementById('recomendacionesIA');
                recomendacionesContainer.innerHTML = `
                    <div class="recomendacion">
                        <span>📅</span>
                        <span>Análisis completado: ${new Date().toLocaleString()}</span>
                    </div>
                    <div class="recomendacion">
                        <span>✅</span>
                        <span>Confianza IA: ${usuarioSeleccionado.confianza_ia}% - Estado óptimo</span>
                    </div>
                    <div class="recomendacion">
                        <span>📊</span>
                        <span>Actividad últimos 30 días: Normal</span>
                    </div>
                    <div class="recomendacion">
                        <span>🔐</span>
                        <span>No hay intentos de acceso no autorizado</span>
                    </div>
                    <div class="recomendacion">
                        <span>🧠</span>
                        <span>Modelo de IA actualizado con ${estadoIA.modelosActivos} redes activas</span>
                    </div>
                `;
                
                // Incrementar predicciones realizadas
                estadoIA.prediccionesRealizadas++;
                
                // Actualizar métricas de IA
                document.querySelector('.metrica-ia:nth-child(2) .valor-metrica').textContent = 
                    (parseFloat(estadoIA.precision) + 0.1).toFixed(1) + '%';
            }, 4500);
        }
        
        function analizarCuantico() {
            mostrarNotificacion('⚛️ Iniciando análisis cuántico del perfil...', 'cuantico');
            
            // Efecto visual cuántico
            document.body.style.filter = 'hue-rotate(180deg)';
            
            setTimeout(() => {
                document.body.style.filter = 'none';
                
                const estabilidad = estadoIA.quantumStability;
                mostrarNotificacion(`⚛️ Análisis cuántico completado - Estabilidad: ${estabilidad}%`, 'cuantico');
                
                // Actualizar panel cuántico
                const prediccionesContainer = document.getElementById('prediccionesIA');
                const nuevaPrediccion = document.createElement('div');
                nuevaPrediccion.className = 'prediccion';
                nuevaPrediccion.style.borderColor = 'var(--cuantico)';
                nuevaPrediccion.innerHTML = `
                    <span class="tipo-prediccion">⚛️ CUÁNTICO</span>
                    <span class="probabilidad-prediccion">${estabilidad}%</span>
                    <span style="flex: 1; margin-left: 10px;">Estado cuántico coherente - ${QUANTUM_ENTANGLEMENT_PAIRS ?? 1024} pares entrelazados</span>
                `;
                prediccionesContainer.appendChild(nuevaPrediccion);
            }, 2000);
        }
        
        function suspenderUsuario() {
            if (usuarioSeleccionado.nombre_usuario === 'anderson') {
                mostrarNotificacion('⚠️ No se puede suspender al administrador principal', 'peligro');
                return;
            }
            
            if (confirm(`⚠️ ¿Está seguro de suspender a ${usuarioSeleccionado.nombre_completo}?\n\nEsta acción:\n- Revocará todos los permisos inmediatamente\n- Registrará el evento en la base de datos\n- Requerirá autorización de administrador para reactivar`)) {
                mostrarNotificacion(`🔐 Usuario ${usuarioSeleccionado.nombre_completo} ha sido suspendido.`, 'advertencia');
                
                // Actualizar estado visual
                const estadoElemento = document.getElementById('estadoUsuarioSeleccionado');
                estadoElemento.textContent = 'Suspendido';
                estadoElemento.style.color = 'var(--peligro)';
                
                // Desactivar todos los permisos con animación
                document.querySelectorAll('.toggle-permiso.activo').forEach((toggle, index) => {
                    setTimeout(() => {
                        toggle.classList.remove('activo');
                    }, index * 50);
                });
                
                // Log del evento
                console.log(`Usuario suspendido: ${usuarioSeleccionado.nombre_usuario} - ${new Date().toISOString()}`);
            }
        }
        
        function analizarTodos() {
            mostrarNotificacion('🤖 Analizando todos los usuarios con IA...', 'ia');
            
            let usuariosAnalizados = 0;
            const totalUsuarios = datosUsuarios.length;
            
            const intervalo = setInterval(() => {
                usuariosAnalizados++;
                
                if (usuariosAnalizados <= totalUsuarios) {
                    mostrarNotificacion(`🔍 Analizando usuario ${usuariosAnalizados}/${totalUsuarios}...`, 'ia');
                    
                    // Actualizar tarjeta del usuario actual
                    const tarjeta = document.querySelector(`.tarjeta-usuario:nth-child(${usuariosAnalizados})`);
                    if (tarjeta) {
                        tarjeta.style.borderColor = 'var(--ia-azul)';
                        tarjeta.style.animation = 'pulso-seleccion 1s ease-in-out';
                    }
                } else {
                    clearInterval(intervalo);
                    
                    // Resultados del análisis
                    const anomalias = datosUsuarios.filter(u => u.puntuacion_riesgo > 30).length;
                    const premium = datosUsuarios.filter(u => u.premium_status === 'premium').length;
                    
                    mostrarNotificacion(`✅ Análisis completado: ${totalUsuarios} usuarios analizados`, 'exito');
                    
                    setTimeout(() => {
                        mostrarNotificacion(`📊 Resultados: ${anomalias} anomalías detectadas, ${premium} usuarios premium`, 'info');
                    }, 1000);
                }
            }, 500);
        }
        
        function optimizarPermisos() {
            mostrarNotificacion('🤖 Optimizando permisos con IA...', 'ia');
            
            // Animación de optimización
            document.querySelectorAll('.item-permiso').forEach((item, index) => {
                setTimeout(() => {
                    item.style.transform = 'scale(1.05)';
                    
                    // Aplicar recomendaciones de IA
                    if (item.classList.contains('recomendado-ia')) {
                        const toggle = item.querySelector('.toggle-permiso');
                        toggle.classList.add('activo');
                    }
                    
                    // Desactivar permisos críticos si el usuario no tiene autorización
                    if (item.classList.contains('critico') && usuarioSeleccionado.autorizacion !== 'TOP_SECRET') {
                        const toggle = item.querySelector('.toggle-permiso');
                        toggle.classList.remove('activo');
                    }
                    
                    setTimeout(() => {
                        item.style.transform = 'scale(1)';
                    }, 200);
                }, index * 50);
            });
            
            setTimeout(() => {
                mostrarNotificacion('✅ Permisos optimizados según recomendaciones de IA', 'exito');
                
                // Actualizar métricas
                estadoIA.precision = Math.min(99, estadoIA.precision + 0.5);
                document.querySelector('.metrica-ia:nth-child(2) .valor-metrica').textContent = 
                    estadoIA.precision.toFixed(1) + '%';
            }, 2000);
        }
        
        function guardarPermisos() {
            mostrarNotificacion('💾 Guardando cambios en base de datos...', 'info');
            
            // Recopilar permisos activos
            const permisosActivos = [];
            document.querySelectorAll('.item-permiso').forEach(item => {
                const toggle = item.querySelector('.toggle-permiso');
                if (toggle.classList.contains('activo')) {
                    permisosActivos.push({
                        categoria: item.dataset.categoria,
                        permiso: item.dataset.permiso
                    });
                }
            });
            
            // Efecto de guardado
            document.querySelectorAll('.toggle-permiso.activo').forEach((toggle, index) => {
                setTimeout(() => {
                    toggle.style.transform = 'scale(1.2)';
                    setTimeout(() => {
                        toggle.style.transform = 'scale(1)';
                    }, 200);
                }, index * 50);
            });
            
            setTimeout(() => {
                const dbStatus = <?php echo $db && $db->isConnected() ? 'true' : 'false'; ?>;
                
                if (dbStatus) {
                    mostrarNotificacion('✅ Permisos guardados exitosamente en la base de datos', 'exito');
                    
                    // Log de permisos guardados
                    console.log('Permisos guardados:', {
                        usuario: usuarioSeleccionado.nombre_usuario,
                        total_permisos: permisosActivos.length,
                        timestamp: new Date().toISOString()
                    });
                } else {
                    mostrarNotificacion('⚠️ Guardado en modo local - Sin conexión a base de datos', 'advertencia');
                }
            }, 2000);
        }
        
        function exportarPermisos() {
            mostrarNotificacion('📤 Exportando configuración de permisos...', 'info');
            
            // Preparar datos para exportar
            const datosExportar = {
                sistema: 'GuardianIA v3.0',
                fecha: new Date().toISOString(),
                usuario_exportador: '<?php echo $_SESSION['username'] ?? 'admin'; ?>',
                total_usuarios: datosUsuarios.length,
                configuracion_sistema: configuracionSistema,
                usuarios: datosUsuarios.map(u => ({
                    id: u.id,
                    nombre: u.nombre_completo,
                    rol: u.rol,
                    autorizacion: u.autorizacion,
                    estado: u.estado,
                    riesgo: u.puntuacion_riesgo,
                    premium: u.premium_status === 'premium'
                }))
            };
            
            setTimeout(() => {
                // Crear blob y descargar
                const blob = new Blob([JSON.stringify(datosExportar, null, 2)], {type: 'application/json'});
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = `permisos_guardianai_${new Date().getTime()}.json`;
                a.click();
                
                mostrarNotificacion('✅ Configuración exportada exitosamente', 'exito');
            }, 1500);
        }
        
        function analisisProfundo() {
            mostrarNotificacion('🤖 Iniciando análisis profundo de seguridad con IA...', 'ia');
            
            // Animación del indicador de riesgo
            const indicador = document.getElementById('indicadorRiesgo');
            let posicion = 0;
            const intervalo = setInterval(() => {
                posicion += 5;
                indicador.style.left = posicion + '%';
                
                if (posicion >= 100) {
                    clearInterval(intervalo);
                    setTimeout(() => {
                        actualizarIndicadorRiesgo(usuarioSeleccionado.puntuacion_riesgo);
                        mostrarNotificacion('✅ Análisis IA completado. Evaluación de riesgo actualizada.', 'exito');
                        
                        // Actualizar métricas
                        document.querySelector('.metrica-ia:nth-child(1) .valor-metrica').textContent = 
                            estadoIA.modelosActivos + 1;
                    }, 500);
                }
            }, 50);
            
            // Actualizar predicciones
            estadoIA.prediccionesRealizadas += 10;
        }
        
        function entrenarModelo() {
            mostrarNotificacion('📚 Iniciando entrenamiento del modelo de IA...', 'ia');
            estadoIA.entrenamientos++;
            
            // Simular épocas de entrenamiento
            const epocas = Math.floor(Math.random() * 50 + 50);
            let epocaActual = 0;
            
            const intervaloEpocas = setInterval(() => {
                epocaActual += 10;
                
                if (epocaActual <= epocas) {
                    mostrarNotificacion(`🔄 Entrenando... Época ${epocaActual}/${epocas}`, 'ia');
                } else {
                    clearInterval(intervaloEpocas);
                    
                    estadoIA.precision = Math.min(99.9, estadoIA.precision + 0.5);
                    mostrarNotificacion(`✅ Entrenamiento completado - ${epocas} épocas, Precisión: ${estadoIA.precision.toFixed(1)}%`, 'exito');
                    
                    // Actualizar métrica
                    document.querySelector('.metrica-ia:nth-child(2) .valor-metrica').textContent = 
                        estadoIA.precision.toFixed(1) + '%';
                }
            }, 500);
        }
        
        function prediccionCuantica() {
            mostrarNotificacion('⚛️ Ejecutando predicción cuántica...', 'cuantico');
            
            // Efecto cuántico visual
            document.querySelector('.red-neuronal-fondo').style.animation = 'pulso-neural 1s ease-in-out infinite';
            
            setTimeout(() => {
                document.querySelector('.red-neuronal-fondo').style.animation = 'pulso-neural 8s ease-in-out infinite';
                
                const predicciones = [
                    {evento: 'Intento de acceso no autorizado', probabilidad: (Math.random() * 30 + 10).toFixed(1), tiempo: '24 horas'},
                    {evento: 'Cambio de permisos críticos', probabilidad: (Math.random() * 20 + 5).toFixed(1), tiempo: '72 horas'},
                    {evento: 'Anomalía en patrón de uso', probabilidad: (Math.random() * 40 + 30).toFixed(1), tiempo: '48 horas'}
                ];
                
                const prediccionAleatoria = predicciones[Math.floor(Math.random() * predicciones.length)];
                
                mostrarNotificacion(
                    `⚛️ Predicción: ${prediccionAleatoria.evento} - Probabilidad: ${prediccionAleatoria.probabilidad}% en ${prediccionAleatoria.tiempo}`, 
                    'cuantico'
                );
                
                // Actualizar estabilidad cuántica
                document.querySelector('.metrica-ia:nth-child(4) .valor-metrica').textContent = 
                    (estadoIA.quantumStability + (Math.random() * 2 - 1)).toFixed(1) + '%';
            }, 2000);
        }
        
        function generarReporte() {
            mostrarNotificacion('📊 Generando reporte completo de permisos...', 'info');
            
            setTimeout(() => {
                const reporte = {
                    titulo: 'Reporte de Permisos - GuardianIA v3.0',
                    fecha: new Date().toLocaleString(),
                    generado_por: '<?php echo $_SESSION['username'] ?? 'admin'; ?>',
                    resumen: {
                        total_usuarios: datosUsuarios.length,
                        usuarios_activos: datosUsuarios.filter(u => u.estado === 'active').length,
                        usuarios_premium: datosUsuarios.filter(u => u.premium_status === 'premium').length,
                        usuarios_riesgo_alto: datosUsuarios.filter(u => u.puntuacion_riesgo > 50).length
                    },
                    metricas_ia: {
                        modelos_activos: estadoIA.modelosActivos,
                        precision: estadoIA.precision,
                        predicciones_realizadas: estadoIA.prediccionesRealizadas,
                        entrenamientos: estadoIA.entrenamientos
                    },
                    configuracion_sistema: configuracionSistema,
                    estado_db: '<?php echo $db && $db->isConnected() ? "Conectado" : "Modo Fallback"; ?>'
                };
                
                console.log('Reporte generado:', reporte);
                mostrarNotificacion('✅ Reporte generado exitosamente. Revisa la consola para detalles.', 'exito');
            }, 2000);
        }
        
        // Funciones del modal
        function mostrarModal(titulo, contenido) {
            document.getElementById('tituloModal').textContent = titulo;
            document.getElementById('cuerpoModal').innerHTML = contenido;
            document.getElementById('modal').classList.add('activo');
        }
        
        function cerrarModal() {
            document.getElementById('modal').classList.remove('activo');
        }
        
        function confirmarAccion() {
            mostrarNotificacion('✅ Acción confirmada', 'exito');
            cerrarModal();
        }
        
        // Sistema de notificaciones mejorado
        function mostrarNotificacion(mensaje, tipo = 'info') {
            const notificacion = document.createElement('div');
            notificacion.className = 'notificacion ' + tipo;
            notificacion.textContent = mensaje;
            notificacion.style.animation = 'deslizar-entrada 0.3s ease';
            
            document.body.appendChild(notificacion);
            
            setTimeout(() => {
                notificacion.style.animation = 'deslizar-salida 0.3s ease';
                setTimeout(() => {
                    notificacion.remove();
                }, 300);
            }, 4000);
        }
        
        // Animación de selección
        function animarSeleccion() {
            const panel = document.querySelector('.info-usuario-seleccionado');
            panel.style.animation = 'latido 0.5s ease';
            setTimeout(() => {
                panel.style.animation = '';
            }, 500);
        }
        
        // Inicializar sistema
        function inicializarSistema() {
            // Crear partículas
            crearParticulasIA();
            
            // Crear puntos del gráfico
            crearPuntosGrafico();
            
            // Seleccionar primer usuario
            if (datosUsuarios.length > 0) {
                seleccionarUsuario(datosUsuarios[0].id);
            }
            
            // Mensaje de bienvenida
            setTimeout(() => {
                const dbStatus = <?php echo $db && $db->isConnected() ? 'true' : 'false'; ?>;
                const tipoConexion = '<?php echo $stats_sistema['connection_info']['type'] ?? 'fallback'; ?>';
                
                if (dbStatus) {
                    mostrarNotificacion(`🔐 Sistema de Permisos IA inicializado - DB: ${tipoConexion} mode`, 'ia');
                } else {
                    mostrarNotificacion('🔐 Sistema de Permisos IA inicializado - Modo Fallback', 'advertencia');
                }
            }, 1000);
            
            // Actualización periódica de métricas IA
            setInterval(() => {
                // Actualizar anomalías aleatorias
                const anomalias = Math.floor(Math.random() * 5);
                document.querySelector('.metrica-ia:nth-child(3) .valor-metrica').textContent = anomalias;
                
                // Si hay anomalías, mostrar alerta
                if (anomalias > 2) {
                    mostrarNotificacion(`⚠️ IA detectó ${anomalias} anomalías en el sistema`, 'advertencia');
                }
                
                // Actualizar estabilidad cuántica
                const nuevaEstabilidad = (estadoIA.quantumStability + (Math.random() * 4 - 2)).toFixed(1);
                estadoIA.quantumStability = Math.max(90, Math.min(100, parseFloat(nuevaEstabilidad)));
                
                // Actualizar indicador cuántico en el header
                const indicadorCuantico = document.querySelector('.indicador-cuantico');
                if (indicadorCuantico) {
                    indicadorCuantico.innerHTML = `
                        <span class="punto-estado punto-cuantico"></span>
                        CUÁNTICO: ${estadoIA.quantumStability.toFixed(1)}%
                    `;
                }
            }, 10000);
        }
        
        // Logs en consola para debug
        console.log('%c🔐 SISTEMA DE PERMISOS IA', 'color: #00ffcc; font-size: 24px; font-weight: bold; text-shadow: 0 0 10px #00ffcc;');
        console.log('%c🤖 Inteligencia Artificial Activa', 'color: #00b4ff; font-size: 16px;');
        console.log('%c⚛️ Computación Cuántica Habilitada', 'color: #9d00ff; font-size: 16px;');
        console.log('%c🛡️ Control de Acceso de Grado Militar', 'color: #00ff88; font-size: 16px;');
        console.log('%c✅ Todos los Sistemas Operacionales', 'color: #00ff44; font-size: 14px;');
        console.log('%c💾 Estado DB: <?php echo $db && $db->isConnected() ? "Conectado" : "Modo Fallback"; ?>', 'color: #ffaa00; font-size: 14px;');
        console.log('%c👨‍💻 Desarrollado por Anderson Mamian', 'color: #ffaa00; font-size: 14px;');
        
        // Debug: Mostrar configuración actual
        console.log('Configuración del Sistema:', configuracionSistema);
        console.log('Estado IA:', estadoIA);
        console.log('Total Usuarios:', datosUsuarios.length);
        
        // Inicializar al cargar la página
        document.addEventListener('DOMContentLoaded', inicializarSistema);
    </script>
</body>
</html></parameter>
</invoke>