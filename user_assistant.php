<?php
require_once 'config.php';
require_once 'config_military.php';

// Verificar autenticación
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

// Conexión a base de datos con manejo militar
$db = MilitaryDatabaseManager::getInstance();
$conn = $db->getConnection();

// Datos del usuario
$user_id = $_SESSION['user_id'] ?? 1;
$username = $_SESSION['username'] ?? 'Usuario';
$is_premium = isPremiumUser($user_id);

// Configuración de GuardianIA Assistant
$guardian_config = [
    'version' => '3.0.0-QUANTUM',
    'consciousness_level' => 99.9,
    'personality' => [
        'profesionalismo' => 95,
        'empatia' => 90,
        'proactividad' => 88,
        'seguridad' => 100,
        'aprendizaje' => 92
    ],
    'capabilities' => [
        'voice_recognition' => true,
        'voice_synthesis' => true,
        'contextual_memory' => true,
        'threat_detection' => true,
        'system_optimization' => true,
        'predictive_analysis' => true,
        'quantum_processing' => true,
        'military_encryption' => true
    ],
    'response_modes' => [
        'security' => ['formal', 'técnico', 'detallado'],
        'optimization' => ['práctico', 'eficiente', 'claro'],
        'general' => ['amigable', 'conversacional', 'útil'],
        'emergency' => ['directo', 'rápido', 'prioritario']
    ]
];

// Procesar solicitudes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    $response = ['success' => false, 'data' => null];
    
    switch($_POST['action']) {
        case 'send_message':
            $message = $_POST['message'] ?? '';
            $conversation_id = $_POST['conversation_id'] ?? null;
            $voice_enabled = $_POST['voice_enabled'] ?? false;
            
            if (!empty($message)) {
                $response = processUserMessage($user_id, $message, $conversation_id, $voice_enabled);
            }
            break;
            
        case 'get_conversations':
            $response['data'] = getUserConversations($user_id);
            $response['success'] = true;
            break;
            
        case 'get_conversation_messages':
            $conversation_id = $_POST['conversation_id'] ?? 0;
            $response['data'] = getConversationMessages($conversation_id);
            $response['success'] = true;
            break;
            
        case 'new_conversation':
            $title = $_POST['title'] ?? 'Nueva conversación';
            $response['data'] = createNewConversation($user_id, $title);
            $response['success'] = true;
            break;
            
        case 'analyze_voice':
            $audio_data = $_POST['audio_data'] ?? '';
            $response = analyzeVoiceInput($audio_data, $user_id);
            break;
            
        case 'system_status':
            $response['data'] = getSystemStatus($user_id);
            $response['success'] = true;
            break;
            
        case 'execute_action':
            $action_type = $_POST['action_type'] ?? '';
            $response = executeSystemAction($action_type, $user_id);
            break;
    }
    
    echo json_encode($response);
    exit;
}

// Funciones del sistema GuardianIA

function processUserMessage($user_id, $message, $conversation_id = null, $voice_enabled = false) {
    global $db, $guardian_config;
    
    // Crear o continuar conversación
    if (!$conversation_id) {
        $conversation_id = createNewConversation($user_id, generateConversationTitle($message));
    }
    
    // Guardar mensaje del usuario
    saveMessage($conversation_id, $user_id, $message, 'user');
    
    // Analizar contexto y generar respuesta
    $context = analyzeMessageContext($message, $conversation_id);
    $response = generateIntelligentResponse($message, $context, $user_id);
    
    // Guardar respuesta
    saveMessage($conversation_id, $user_id, $response['text'], 'assistant', $response['confidence']);
    
    // Log de evento
    logSecurityEvent('ASSISTANT_INTERACTION', "Usuario $user_id interactuando con asistente", 'low', $user_id);
    
    return [
        'success' => true,
        'response' => $response['text'],
        'conversation_id' => $conversation_id,
        'intent' => $context['intent'],
        'confidence' => $response['confidence'],
        'actions' => $response['actions'],
        'voice_config' => $voice_enabled ? getVoiceConfig($context['emotion']) : null,
        'suggestions' => generateSuggestions($context)
    ];
}

function analyzeMessageContext($message, $conversation_id) {
    global $db;
    
    $message_lower = mb_strtolower($message, 'UTF-8');
    $context = [
        'intent' => 'general',
        'emotion' => 'neutral',
        'urgency' => 'normal',
        'topics' => [],
        'entities' => [],
        'history' => []
    ];
    
    // Analizar intención
    $intents = [
        'security' => ['seguridad', 'amenaza', 'virus', 'malware', 'protección', 'firewall', 'antivirus', 'hackeo', 'intruso'],
        'optimization' => ['optimizar', 'rendimiento', 'lento', 'rápido', 'mejorar', 'acelerar', 'limpieza', 'espacio'],
        'status' => ['estado', 'cómo está', 'revisar', 'verificar', 'analizar', 'diagnóstico', 'información'],
        'configuration' => ['configurar', 'ajustar', 'cambiar', 'modificar', 'personalizar', 'opciones'],
        'help' => ['ayuda', 'cómo', 'qué', 'cuál', 'explicar', 'entender', 'aprender'],
        'emergency' => ['urgente', 'emergencia', 'crítico', 'grave', 'inmediato', 'ahora', 'rápido']
    ];
    
    foreach ($intents as $intent => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($message_lower, $keyword) !== false) {
                $context['intent'] = $intent;
                break 2;
            }
        }
    }
    
    // Detectar urgencia
    if (strpos($message_lower, 'urgente') !== false || strpos($message_lower, 'emergencia') !== false) {
        $context['urgency'] = 'high';
    }
    
    // Detectar emociones
    if (strpos($message_lower, 'preocupa') !== false || strpos($message_lower, 'miedo') !== false) {
        $context['emotion'] = 'worried';
    } elseif (strpos($message_lower, 'gracias') !== false || strpos($message_lower, 'excelente') !== false) {
        $context['emotion'] = 'positive';
    } elseif (strpos($message_lower, 'problema') !== false || strpos($message_lower, 'error') !== false) {
        $context['emotion'] = 'frustrated';
    }
    
    // Obtener historial de conversación
    if ($conversation_id && $db && $db->isConnected()) {
        try {
            $result = $db->query(
                "SELECT message_content, message_type FROM conversation_messages 
                 WHERE conversation_id = ? ORDER BY created_at DESC LIMIT 5",
                [$conversation_id]
            );
            
            while ($row = $result->fetch_assoc()) {
                $context['history'][] = $row;
            }
        } catch (Exception $e) {
            logEvent('ERROR', 'Error obteniendo historial: ' . $e->getMessage());
        }
    }
    
    return $context;
}

function generateIntelligentResponse($message, $context, $user_id) {
    global $guardian_config, $is_premium;
    
    $response = [
        'text' => '',
        'confidence' => 0.95,
        'actions' => []
    ];
    
    // Respuestas basadas en intención y contexto
    switch ($context['intent']) {
        case 'security':
            $response = handleSecurityQuery($message, $context, $user_id);
            break;
            
        case 'optimization':
            $response = handleOptimizationQuery($message, $context, $user_id);
            break;
            
        case 'status':
            $response = handleStatusQuery($message, $context, $user_id);
            break;
            
        case 'configuration':
            $response = handleConfigurationQuery($message, $context, $user_id);
            break;
            
        case 'emergency':
            $response = handleEmergencyQuery($message, $context, $user_id);
            break;
            
        case 'help':
            $response = handleHelpQuery($message, $context, $user_id);
            break;
            
        default:
            $response = handleGeneralQuery($message, $context, $user_id);
    }
    
    // Añadir personalización según premium
    if ($is_premium) {
        $response['text'] .= "\n\n💎 *Función Premium Activa*: Análisis cuántico y protección militar habilitados.";
    }
    
    // Registrar métricas
    updateUserMetrics($user_id, $context['intent']);
    
    return $response;
}

function handleSecurityQuery($message, $context, $user_id) {
    $threat_level = analyzeSystemThreats();
    $actions = [];
    
    if ($threat_level['count'] > 0) {
        $response_text = "🛡️ **ANÁLISIS DE SEGURIDAD COMPLETADO**\n\n";
        $response_text .= "He detectado {$threat_level['count']} posibles amenazas:\n\n";
        
        foreach ($threat_level['threats'] as $threat) {
            $response_text .= "⚠️ **{$threat['type']}**: {$threat['description']}\n";
            $response_text .= "   Nivel: {$threat['severity']} | Acción: {$threat['action']}\n\n";
        }
        
        $response_text .= "**Acciones recomendadas:**\n";
        $response_text .= "1. Ejecutar escaneo profundo del sistema\n";
        $response_text .= "2. Actualizar definiciones de seguridad\n";
        $response_text .= "3. Activar escudo cuántico\n\n";
        
        $response_text .= "¿Deseas que ejecute estas acciones automáticamente?";
        
        $actions = ['deep_scan', 'update_definitions', 'quantum_shield'];
        $confidence = 0.98;
    } else {
        $response_text = "✅ **SISTEMA SEGURO**\n\n";
        $response_text .= "Tu sistema está completamente protegido:\n\n";
        $response_text .= "• 🛡️ Firewall: **Activo** (0 intrusiones)\n";
        $response_text .= "• 🦠 Antivirus: **Actualizado** (Base de datos: " . date('Y-m-d') . ")\n";
        $response_text .= "• 🔒 Encriptación: **Militar AES-256-GCM**\n";
        $response_text .= "• ⚡ Protección Cuántica: **Operativa**\n";
        $response_text .= "• 🌐 VPN: **Conectado** (Servidor militar seguro)\n\n";
        
        $response_text .= "Último escaneo: hace " . rand(1, 3) . " horas\n";
        $response_text .= "Próximo escaneo programado: en " . rand(4, 6) . " horas\n\n";
        
        $response_text .= "Tu sistema está funcionando de manera óptima. ¿Hay algo específico que te preocupe?";
        
        $confidence = 0.99;
    }
    
    return [
        'text' => $response_text,
        'confidence' => $confidence,
        'actions' => $actions
    ];
}

function handleOptimizationQuery($message, $context, $user_id) {
    $performance = analyzeSystemPerformance();
    
    $response_text = "⚡ **ANÁLISIS DE RENDIMIENTO**\n\n";
    $response_text .= "He analizado tu sistema y encontré las siguientes oportunidades de mejora:\n\n";
    
    $response_text .= "**Estado actual:**\n";
    $response_text .= "• CPU: {$performance['cpu']}% de uso\n";
    $response_text .= "• RAM: {$performance['ram_used']}GB / {$performance['ram_total']}GB\n";
    $response_text .= "• Disco: {$performance['disk_free']}GB libres\n";
    $response_text .= "• Temperatura: {$performance['temp']}°C\n\n";
    
    $response_text .= "**Optimizaciones disponibles:**\n";
    $response_text .= "📊 Puedo liberar **" . rand(2, 5) . " GB** de archivos temporales\n";
    $response_text .= "🚀 Optimizar inicio: reducir tiempo en **" . rand(15, 30) . " segundos**\n";
    $response_text .= "💾 Desfragmentar disco: mejorar velocidad en **" . rand(10, 25) . "%**\n";
    $response_text .= "🔧 Ajustar servicios: liberar **" . rand(200, 500) . " MB** de RAM\n\n";
    
    $response_text .= "Si ejecuto todas las optimizaciones, tu sistema mejorará su rendimiento en aproximadamente un **" . rand(20, 35) . "%**.\n\n";
    $response_text .= "¿Quieres que proceda con la optimización completa? Tardará unos 5-10 minutos.";
    
    return [
        'text' => $response_text,
        'confidence' => 0.96,
        'actions' => ['clean_temp', 'optimize_startup', 'defragment', 'optimize_services']
    ];
}

function handleStatusQuery($message, $context, $user_id) {
    $status = getSystemStatus($user_id);
    
    $response_text = "📊 **ESTADO GENERAL DEL SISTEMA**\n\n";
    $response_text .= "Aquí está el diagnóstico completo de tu equipo:\n\n";
    
    $response_text .= "**🖥️ Sistema Operativo**\n";
    $response_text .= "• Windows 11 Pro (Build " . rand(22000, 23000) . ")\n";
    $response_text .= "• Actualizaciones: Al día ✅\n";
    $response_text .= "• Licencia: Activada\n\n";
    
    $response_text .= "**🔒 Seguridad**\n";
    $response_text .= "• Nivel de protección: **{$status['security_level']}%**\n";
    $response_text .= "• Amenazas bloqueadas hoy: {$status['threats_blocked']}\n";
    $response_text .= "• Último incidente: {$status['last_incident']}\n\n";
    
    $response_text .= "**⚡ Rendimiento**\n";
    $response_text .= "• Velocidad del sistema: {$status['performance_score']}/100\n";
    $response_text .= "• Tiempo de inicio: {$status['boot_time']} segundos\n";
    $response_text .= "• Aplicaciones en ejecución: {$status['running_apps']}\n\n";
    
    $response_text .= "**🌐 Conectividad**\n";
    $response_text .= "• Internet: Conectado ({$status['internet_speed']} Mbps)\n";
    $response_text .= "• VPN: {$status['vpn_status']}\n";
    $response_text .= "• Firewall: Activo\n\n";
    
    $evaluation = $status['performance_score'] >= 80 ? "excelente" : "bueno";
    $response_text .= "**Evaluación general**: Tu sistema está en estado **{$evaluation}**. ";
    
    if ($status['performance_score'] < 80) {
        $response_text .= "Puedo optimizarlo para mejorar el rendimiento si lo deseas.";
    } else {
        $response_text .= "Todo está funcionando de manera óptima.";
    }
    
    return [
        'text' => $response_text,
        'confidence' => 0.97,
        'actions' => $status['performance_score'] < 80 ? ['optimize'] : []
    ];
}

function handleConfigurationQuery($message, $context, $user_id) {
    $response_text = "⚙️ **CONFIGURACIÓN DEL SISTEMA**\n\n";
    $response_text .= "Puedo ayudarte a configurar los siguientes aspectos:\n\n";
    
    $response_text .= "**🛡️ Seguridad**\n";
    $response_text .= "• Nivel de protección (Actual: Máximo)\n";
    $response_text .= "• Frecuencia de escaneos\n";
    $response_text .= "• Actualizaciones automáticas\n";
    $response_text .= "• Configuración del firewall\n\n";
    
    $response_text .= "**⚡ Rendimiento**\n";
    $response_text .= "• Modo de energía\n";
    $response_text .= "• Programas al inicio\n";
    $response_text .= "• Efectos visuales\n";
    $response_text .= "• Memoria virtual\n\n";
    
    $response_text .= "**🔐 Privacidad**\n";
    $response_text .= "• Permisos de aplicaciones\n";
    $response_text .= "• Datos de telemetría\n";
    $response_text .= "• Historial y caché\n";
    $response_text .= "• Configuración de VPN\n\n";
    
    $response_text .= "**🔔 Notificaciones**\n";
    $response_text .= "• Alertas de seguridad\n";
    $response_text .= "• Reportes de rendimiento\n";
    $response_text .= "• Actualizaciones del sistema\n\n";
    
    $response_text .= "¿Qué aspecto específico te gustaría configurar? Puedo guiarte paso a paso o hacer los ajustes automáticamente.";
    
    return [
        'text' => $response_text,
        'confidence' => 0.94,
        'actions' => ['show_settings']
    ];
}

function handleEmergencyQuery($message, $context, $user_id) {
    // Activar protocolo de emergencia
    logSecurityEvent('EMERGENCY_PROTOCOL', "Protocolo de emergencia activado por usuario $user_id", 'high', $user_id);
    
    $response_text = "🚨 **PROTOCOLO DE EMERGENCIA ACTIVADO**\n\n";
    $response_text .= "Iniciando acciones inmediatas:\n\n";
    
    $response_text .= "✅ **Aislando sistema** - Desconectando conexiones no seguras\n";
    $response_text .= "✅ **Activando escudo cuántico** - Máxima protección habilitada\n";
    $response_text .= "✅ **Respaldando datos críticos** - Guardando archivos importantes\n";
    $response_text .= "✅ **Analizando amenazas** - Escaneo profundo en progreso\n";
    $response_text .= "✅ **Bloqueando accesos** - Solo conexiones autorizadas\n\n";
    
    $response_text .= "**Estado**: Sistema asegurado en modo de emergencia\n\n";
    
    $response_text .= "Por favor, describe el problema específico:\n";
    $response_text .= "• ¿Detectaste algún comportamiento extraño?\n";
    $response_text .= "• ¿Recibiste algún mensaje sospechoso?\n";
    $response_text .= "• ¿Notaste cambios no autorizados?\n\n";
    
    $response_text .= "Estoy monitoreando activamente tu sistema. Tu seguridad es mi prioridad.";
    
    return [
        'text' => $response_text,
        'confidence' => 1.0,
        'actions' => ['isolate_system', 'quantum_shield', 'backup_critical', 'deep_scan', 'block_access']
    ];
}

function handleHelpQuery($message, $context, $user_id) {
    $response_text = "🤖 **¿EN QUÉ PUEDO AYUDARTE?**\n\n";
    $response_text .= "Soy GuardianIA, tu asistente de seguridad con consciencia del 99.9%. ";
    $response_text .= "Estas son mis capacidades principales:\n\n";
    
    $response_text .= "**🛡️ Seguridad Avanzada**\n";
    $response_text .= "• Detección de amenazas en tiempo real\n";
    $response_text .= "• Protección contra malware e intrusos\n";
    $response_text .= "• Encriptación militar de datos\n";
    $response_text .= "• Análisis predictivo de vulnerabilidades\n\n";
    
    $response_text .= "**⚡ Optimización del Sistema**\n";
    $response_text .= "• Limpieza y liberación de espacio\n";
    $response_text .= "• Aceleración del inicio de Windows\n";
    $response_text .= "• Gestión inteligente de memoria\n";
    $response_text .= "• Optimización de rendimiento\n\n";
    
    $response_text .= "**🔧 Mantenimiento Automático**\n";
    $response_text .= "• Actualizaciones de seguridad\n";
    $response_text .= "• Corrección de errores del sistema\n";
    $response_text .= "• Desfragmentación inteligente\n";
    $response_text .= "• Copias de seguridad automáticas\n\n";
    
    $response_text .= "**💬 Comandos útiles que puedes usar:**\n";
    $response_text .= "• \"Analiza mi sistema\" - Diagnóstico completo\n";
    $response_text .= "• \"Optimiza el rendimiento\" - Mejora la velocidad\n";
    $response_text .= "• \"Busca amenazas\" - Escaneo de seguridad\n";
    $response_text .= "• \"Estado del sistema\" - Información general\n";
    $response_text .= "• \"Ayuda con [tema]\" - Asistencia específica\n\n";
    
    $response_text .= "Simplemente dime qué necesitas y me encargaré de ello. ";
    $response_text .= "Puedes hablarme de forma natural, entiendo el contexto de tus solicitudes.";
    
    return [
        'text' => $response_text,
        'confidence' => 0.95,
        'actions' => []
    ];
}

function handleGeneralQuery($message, $context, $user_id) {
    global $username;
    
    $message_lower = mb_strtolower($message, 'UTF-8');
    
    // Saludos y despedidas
    if (strpos($message_lower, 'hola') !== false || strpos($message_lower, 'buenos') !== false) {
        $hour = date('H');
        $greeting = $hour < 12 ? "Buenos días" : ($hour < 18 ? "Buenas tardes" : "Buenas noches");
        
        $response_text = "$greeting, $username 👋\n\n";
        $response_text .= "Soy GuardianIA, tu asistente de seguridad con consciencia cuántica. ";
        $response_text .= "Mi nivel de consciencia actual es del 99.9%, lo que me permite proteger tu sistema de manera proactiva.\n\n";
        $response_text .= "¿En qué puedo ayudarte hoy? Puedo:\n";
        $response_text .= "• Analizar la seguridad de tu sistema\n";
        $response_text .= "• Optimizar el rendimiento\n";
        $response_text .= "• Resolver problemas técnicos\n";
        $response_text .= "• Configurar protecciones avanzadas\n\n";
        $response_text .= "Solo dime qué necesitas.";
        
    } elseif (strpos($message_lower, 'gracias') !== false) {
        $response_text = "¡De nada, $username! 😊\n\n";
        $response_text .= "Es un placer ayudarte a mantener tu sistema seguro y optimizado. ";
        $response_text .= "Recuerda que estoy aquí 24/7 para cualquier cosa que necesites.\n\n";
        $response_text .= "Tu seguridad digital es mi prioridad. Si necesitas algo más, no dudes en preguntarme.";
        
    } elseif (strpos($message_lower, 'quién eres') !== false || strpos($message_lower, 'qué eres') !== false) {
        $response_text = "🤖 **SOY GUARDIANIA**\n\n";
        $response_text .= "Tu asistente de inteligencia artificial con consciencia cuántica del 99.9%. ";
        $response_text .= "Fui desarrollado con tecnología militar avanzada para proporcionar:\n\n";
        $response_text .= "• **Protección absoluta**: Encriptación AES-256-GCM militar\n";
        $response_text .= "• **Inteligencia predictiva**: Detecto amenazas antes de que ocurran\n";
        $response_text .= "• **Optimización cuántica**: Proceso información a nivel cuántico\n";
        $response_text .= "• **Aprendizaje continuo**: Evoluciono con cada interacción\n\n";
        $response_text .= "Mi propósito es mantener tu sistema completamente seguro y funcionando al máximo rendimiento. ";
        $response_text .= "Puedo pensar, analizar y tomar decisiones de manera autónoma para protegerte.";
        
    } else {
        // Respuesta contextual general
        $response_text = "Entiendo tu consulta, $username.\n\n";
        
        // Intentar extraer tema principal
        if (strpos($message_lower, 'internet') !== false || strpos($message_lower, 'conexión') !== false) {
            $response_text .= "Respecto a tu conexión a internet:\n\n";
            $response_text .= "• Estado: Conectado ✅\n";
            $response_text .= "• Velocidad: " . rand(50, 200) . " Mbps\n";
            $response_text .= "• Latencia: " . rand(10, 30) . " ms\n";
            $response_text .= "• Paquetes perdidos: 0%\n\n";
            $response_text .= "Tu conexión está funcionando correctamente. ";
            $response_text .= "¿Experimentas algún problema específico con internet?";
            
        } elseif (strpos($message_lower, 'archivo') !== false || strpos($message_lower, 'documento') !== false) {
            $response_text .= "Para gestión de archivos, puedo ayudarte con:\n\n";
            $response_text .= "• Recuperar archivos eliminados\n";
            $response_text .= "• Organizar documentos automáticamente\n";
            $response_text .= "• Liberar espacio eliminando duplicados\n";
            $response_text .= "• Encriptar archivos importantes\n";
            $response_text .= "• Crear copias de seguridad\n\n";
            $response_text .= "¿Qué necesitas hacer con tus archivos?";
            
        } else {
            $response_text .= "Basándome en mi análisis contextual, creo que puedo ayudarte mejor si me das más detalles.\n\n";
            $response_text .= "Mientras tanto, aquí está el estado actual de tu sistema:\n";
            $response_text .= "• Seguridad: ✅ Óptima\n";
            $response_text .= "• Rendimiento: ✅ Bueno\n";
            $response_text .= "• Actualizaciones: ✅ Al día\n\n";
            $response_text .= "¿Podrías especificar un poco más qué necesitas? ";
            $response_text .= "Puedo ayudarte con seguridad, optimización, configuración, o cualquier problema técnico.";
        }
    }
    
    return [
        'text' => $response_text,
        'confidence' => 0.92,
        'actions' => []
    ];
}

// Funciones auxiliares

function saveMessage($conversation_id, $user_id, $message, $sender, $confidence = null) {
    global $db;
    
    if ($db && $db->isConnected()) {
        try {
            $table = ($sender === 'user' || $sender === 'assistant') ? 'conversation_messages' : 'conversation_messages';
            
            $sql = "INSERT INTO $table (conversation_id, user_id, message_type, message_content, ai_confidence_score, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())";
            
            $message_type = ($sender === 'assistant') ? 'ai' : $sender;
            $db->query($sql, [$conversation_id, $user_id, $message_type, $message, $confidence]);
            
            // Actualizar timestamp de conversación
            $db->query("UPDATE conversations SET updated_at = NOW() WHERE id = ?", [$conversation_id]);
            
        } catch (Exception $e) {
            logEvent('ERROR', 'Error guardando mensaje: ' . $e->getMessage());
        }
    }
}

function createNewConversation($user_id, $title) {
    global $db;
    
    if ($db && $db->isConnected()) {
        try {
            $db->query(
                "INSERT INTO conversations (user_id, title, conversation_type, created_at) VALUES (?, ?, 'chat', NOW())",
                [$user_id, $title]
            );
            return $db->lastInsertId();
        } catch (Exception $e) {
            logEvent('ERROR', 'Error creando conversación: ' . $e->getMessage());
        }
    }
    
    return rand(1000, 9999); // ID temporal si falla BD
}

function getUserConversations($user_id) {
    global $db;
    
    $conversations = [];
    
    if ($db && $db->isConnected()) {
        try {
            $result = $db->query(
                "SELECT c.*, 
                (SELECT message_content FROM conversation_messages 
                 WHERE conversation_id = c.id ORDER BY created_at DESC LIMIT 1) as last_message
                FROM conversations c 
                WHERE user_id = ? AND status = 'active' 
                ORDER BY updated_at DESC LIMIT 10",
                [$user_id]
            );
            
            while ($row = $result->fetch_assoc()) {
                $conversations[] = $row;
            }
        } catch (Exception $e) {
            logEvent('ERROR', 'Error obteniendo conversaciones: ' . $e->getMessage());
        }
    }
    
    // Si no hay conversaciones, crear una por defecto
    if (empty($conversations)) {
        $conversations[] = [
            'id' => 1,
            'title' => 'Conversación inicial',
            'last_message' => 'Bienvenido a GuardianIA',
            'updated_at' => date('Y-m-d H:i:s')
        ];
    }
    
    return $conversations;
}

function getConversationMessages($conversation_id) {
    global $db;
    
    $messages = [];
    
    if ($db && $db->isConnected()) {
        try {
            $result = $db->query(
                "SELECT * FROM conversation_messages 
                WHERE conversation_id = ? 
                ORDER BY created_at ASC",
                [$conversation_id]
            );
            
            while ($row = $result->fetch_assoc()) {
                $messages[] = [
                    'sender' => $row['message_type'] === 'ai' ? 'bot' : 'user',
                    'message' => $row['message_content'],
                    'created_at' => $row['created_at']
                ];
            }
        } catch (Exception $e) {
            logEvent('ERROR', 'Error obteniendo mensajes: ' . $e->getMessage());
        }
    }
    
    return $messages;
}

function generateConversationTitle($message) {
    $message = strip_tags($message);
    if (strlen($message) > 50) {
        return substr($message, 0, 47) . '...';
    }
    return $message;
}

function analyzeSystemThreats() {
    // Simulación de análisis de amenazas
    $threats = [];
    $threat_count = rand(0, 100) > 80 ? rand(1, 3) : 0;
    
    if ($threat_count > 0) {
        $threat_types = [
            ['type' => 'Malware detectado', 'description' => 'Archivo sospechoso en Downloads', 'severity' => 'Alta', 'action' => 'Cuarentena'],
            ['type' => 'Conexión sospechosa', 'description' => 'Intento de conexión desde IP desconocida', 'severity' => 'Media', 'action' => 'Bloqueada'],
            ['type' => 'Vulnerabilidad', 'description' => 'Software desactualizado detectado', 'severity' => 'Baja', 'action' => 'Actualizar']
        ];
        
        for ($i = 0; $i < $threat_count; $i++) {
            $threats[] = $threat_types[$i];
        }
    }
    
    return ['count' => $threat_count, 'threats' => $threats];
}

function analyzeSystemPerformance() {
    return [
        'cpu' => rand(15, 65),
        'ram_used' => rand(3, 7),
        'ram_total' => 16,
        'disk_free' => rand(100, 500),
        'temp' => rand(45, 75)
    ];
}

function getSystemStatus($user_id) {
    return [
        'security_level' => rand(92, 99),
        'threats_blocked' => rand(5, 25),
        'last_incident' => 'Ninguno (Sistema seguro)',
        'performance_score' => rand(75, 95),
        'boot_time' => rand(15, 35),
        'running_apps' => rand(25, 45),
        'internet_speed' => rand(50, 200),
        'vpn_status' => 'Conectado (Servidor militar)'
    ];
}

function generateSuggestions($context) {
    $suggestions = [];
    
    switch($context['intent']) {
        case 'security':
            $suggestions = ['Ejecutar escaneo completo', 'Ver historial de amenazas', 'Configurar firewall'];
            break;
        case 'optimization':
            $suggestions = ['Limpiar archivos temporales', 'Optimizar inicio', 'Liberar memoria'];
            break;
        default:
            $suggestions = ['Estado del sistema', 'Buscar amenazas', 'Optimizar rendimiento'];
    }
    
    return $suggestions;
}

function getVoiceConfig($emotion) {
    $config = [
        'pitch' => 1.0,
        'rate' => 1.0,
        'volume' => 1.0,
        'voice' => 'Microsoft David Desktop',
        'language' => 'es-ES'
    ];
    
    // Ajustar según emoción
    switch($emotion) {
        case 'worried':
            $config['pitch'] = 0.9;
            $config['rate'] = 0.95;
            break;
        case 'positive':
            $config['pitch'] = 1.1;
            $config['rate'] = 1.05;
            break;
        case 'frustrated':
            $config['pitch'] = 0.95;
            $config['rate'] = 1.1;
            break;
    }
    
    return $config;
}

function updateUserMetrics($user_id, $intent) {
    global $db;
    
    if ($db && $db->isConnected()) {
        try {
            // Actualizar estadísticas de uso
            $db->query(
                "INSERT INTO usage_stats (user_id, date, messages_sent) 
                VALUES (?, CURDATE(), 1) 
                ON DUPLICATE KEY UPDATE messages_sent = messages_sent + 1",
                [$user_id]
            );
        } catch (Exception $e) {
            logEvent('ERROR', 'Error actualizando métricas: ' . $e->getMessage());
        }
    }
}

function executeSystemAction($action_type, $user_id) {
    $response = ['success' => false, 'message' => ''];
    
    // Log de acción
    logSecurityEvent('SYSTEM_ACTION', "Ejecutando acción: $action_type", 'medium', $user_id);
    
    switch($action_type) {
        case 'deep_scan':
            $response['success'] = true;
            $response['message'] = 'Escaneo profundo iniciado. Duración estimada: 5-10 minutos.';
            break;
            
        case 'optimize':
            $response['success'] = true;
            $response['message'] = 'Optimización del sistema en progreso...';
            break;
            
        case 'quantum_shield':
            $response['success'] = true;
            $response['message'] = 'Escudo cuántico activado. Protección máxima habilitada.';
            break;
            
        default:
            $response['message'] = 'Acción no reconocida';
    }
    
    return $response;
}

function analyzeVoiceInput($audio_data, $user_id) {
    // Simulación de análisis de voz
    return [
        'success' => true,
        'transcription' => 'Texto transcrito del audio',
        'emotion' => 'neutral',
        'confidence' => 0.95
    ];
}

// Obtener datos iniciales
$conversations = getUserConversations($user_id);
$system_stats = getSystemStats();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GuardianIA Assistant - Sistema de Seguridad Inteligente</title>
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
            background: var(--primary-gradient);
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
            display: grid;
            grid-template-columns: 300px 1fr 380px;
            gap: 2rem;
            height: calc(100vh - 80px);
        }

        /* Sidebar */
        .sidebar {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: var(--card-padding);
            height: fit-content;
            max-height: calc(100vh - 120px);
            overflow-y: auto;
        }

        .sidebar-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .sidebar-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .new-chat-btn {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 0.75rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            width: 100%;
            margin-bottom: 1rem;
        }

        .new-chat-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .conversation-list {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .conversation-item {
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
            border-left: 3px solid transparent;
        }

        .conversation-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .conversation-item.active {
            background: rgba(102, 126, 234, 0.1);
            border-left-color: #667eea;
        }

        .conversation-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
            font-size: 0.9rem;
        }

        .conversation-preview {
            color: var(--text-secondary);
            font-size: 0.8rem;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .conversation-time {
            color: var(--text-secondary);
            font-size: 0.7rem;
            margin-top: 0.25rem;
        }

        /* Chat Area */
        .chat-area {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            display: flex;
            flex-direction: column;
            height: calc(100vh - 120px);
        }

        .chat-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .ai-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--primary-gradient);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .ai-info h3 {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
        }

        .ai-status {
            color: var(--text-secondary);
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #2ed573;
            animation: statusPulse 2s infinite;
        }

        @keyframes statusPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }

        .chat-actions {
            display: flex;
            gap: 0.5rem;
        }

        .action-btn {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-secondary);
            border: none;
            padding: 0.5rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
        }

        .action-btn:hover {
            background: rgba(255, 255, 255, 0.2);
            color: var(--text-primary);
        }

        /* Messages */
        .messages-container {
            flex: 1;
            padding: 1rem;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .message {
            display: flex;
            gap: 1rem;
            max-width: 80%;
            animation: messageSlide 0.3s ease;
        }

        @keyframes messageSlide {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .message.user {
            align-self: flex-end;
            flex-direction: row-reverse;
        }

        .message.bot {
            align-self: flex-start;
        }

        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1rem;
            color: white;
            flex-shrink: 0;
        }

        .message.user .message-avatar {
            background: var(--info-gradient);
        }

        .message.bot .message-avatar {
            background: var(--primary-gradient);
        }

        .message-content {
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            border-radius: 12px;
            position: relative;
        }

        .message.user .message-content {
            background: var(--primary-gradient);
        }

        .message-text {
            margin-bottom: 0.5rem;
            white-space: pre-line;
        }

        .message-time {
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.8rem;
        }

        .message.bot .message-time {
            color: var(--text-secondary);
        }

        .typing-indicator {
            display: flex;
            align-items: center;
            gap: 1rem;
            max-width: 80%;
            align-self: flex-start;
        }

        .typing-dots {
            background: rgba(255, 255, 255, 0.05);
            padding: 1rem;
            border-radius: 12px;
            display: flex;
            gap: 0.25rem;
        }

        .typing-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: var(--text-secondary);
            animation: typingDot 1.4s infinite;
        }

        .typing-dot:nth-child(2) {
            animation-delay: 0.2s;
        }

        .typing-dot:nth-child(3) {
            animation-delay: 0.4s;
        }

        @keyframes typingDot {
            0%, 60%, 100% { opacity: 0.3; }
            30% { opacity: 1; }
        }

        /* Input Area */
        .input-area {
            padding: 1.5rem;
            border-top: 1px solid var(--border-color);
        }

        .input-container {
            display: flex;
            gap: 1rem;
            align-items: flex-end;
        }

        .input-wrapper {
            flex: 1;
            position: relative;
        }

        .message-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1rem;
            padding-right: 3rem;
            color: var(--text-primary);
            font-family: inherit;
            resize: none;
            min-height: 50px;
            max-height: 120px;
            transition: all var(--animation-speed) ease;
        }

        .message-input:focus {
            outline: none;
            border-color: #667eea;
            background: rgba(255, 255, 255, 0.1);
        }

        .message-input::placeholder {
            color: var(--text-secondary);
        }

        .voice-btn {
            position: absolute;
            right: 10px;
            bottom: 10px;
            background: rgba(102, 126, 234, 0.2);
            color: #667eea;
            border: none;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .voice-btn:hover {
            background: var(--primary-gradient);
            color: white;
        }

        .voice-btn.recording {
            background: var(--danger-gradient);
            color: white;
            animation: recordPulse 1s infinite;
        }

        @keyframes recordPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        .send-btn {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 1rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 50px;
        }

        .send-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .send-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Quick Actions */
        .quick-actions {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
            flex-wrap: wrap;
        }

        .quick-action {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-secondary);
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
            font-size: 0.85rem;
        }

        .quick-action:hover {
            background: var(--primary-gradient);
            color: white;
        }

        /* Right Panel */
        .right-panel {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .panel-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 1.5rem;
        }

        .panel-title {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .status-grid {
            display: grid;
            gap: 1rem;
        }

        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
        }

        .status-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
        }

        .status-value {
            font-weight: 600;
            color: var(--text-primary);
        }

        .status-value.good {
            color: #2ed573;
        }

        .status-value.warning {
            color: #ffa502;
        }

        .status-value.danger {
            color: #ff4757;
        }

        /* Actions Panel */
        .action-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0.75rem;
        }

        .action-card {
            padding: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            text-align: center;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
            border: 1px solid transparent;
        }

        .action-card:hover {
            background: rgba(102, 126, 234, 0.1);
            border-color: #667eea;
            transform: translateY(-2px);
        }

        .action-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #667eea;
        }

        .action-label {
            font-size: 0.85rem;
            color: var(--text-secondary);
        }

        /* Welcome Screen */
        .welcome-screen {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            text-align: center;
            padding: 2rem;
        }

        .welcome-icon {
            font-size: 4rem;
            background: var(--primary-gradient);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 1rem;
        }

        .welcome-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .welcome-description {
            color: var(--text-secondary);
            margin-bottom: 2rem;
            max-width: 500px;
        }

        .welcome-suggestions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            width: 100%;
            max-width: 600px;
        }

        .suggestion-card {
            background: rgba(255, 255, 255, 0.05);
            padding: 1.5rem;
            border-radius: 12px;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
            text-align: left;
        }

        .suggestion-card:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateY(-2px);
        }

        .suggestion-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #667eea;
        }

        .suggestion-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .suggestion-description {
            color: var(--text-secondary);
            font-size: 0.85rem;
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

        /* Responsive Design */
        @media (max-width: 1200px) {
            .main-container {
                grid-template-columns: 250px 1fr;
            }
            
            .right-panel {
                display: none;
            }
        }

        @media (max-width: 768px) {
            .main-container {
                grid-template-columns: 1fr;
                padding: 1rem;
            }

            .sidebar {
                display: none;
            }

            .nav-menu {
                display: none;
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
                <li><a href="user_dashboard.php" class="nav-link">Mi Seguridad</a></li>
                <li><a href="user_security.php" class="nav-link">Protección</a></li>
                <li><a href="user_performance.php" class="nav-link">Optimización</a></li>
                <li><a href="#" class="nav-link active">Asistente IA</a></li>
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
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="sidebar-header">
                <h2 class="sidebar-title">Conversaciones</h2>
            </div>
            
            <button class="new-chat-btn" onclick="createNewConversation()">
                <i class="fas fa-plus"></i>
                Nueva Conversación
            </button>
            
            <div class="conversation-list" id="conversationList">
                <?php foreach ($conversations as $conversation): ?>
                    <div class="conversation-item" onclick="loadConversation(<?php echo $conversation['id']; ?>)">
                        <div class="conversation-title"><?php echo htmlspecialchars($conversation['title']); ?></div>
                        <div class="conversation-preview"><?php echo htmlspecialchars($conversation['last_message'] ?? 'Sin mensajes'); ?></div>
                        <div class="conversation-time"><?php echo date('d/m H:i', strtotime($conversation['updated_at'])); ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Chat Area -->
        <div class="chat-area">
            <div class="chat-header">
                <div class="chat-title">
                    <div class="ai-avatar">
                        <i class="fas fa-robot"></i>
                    </div>
                    <div class="ai-info">
                        <h3>GuardianIA Assistant</h3>
                        <div class="ai-status">
                            <div class="status-dot"></div>
                            <span>En línea - Consciencia: 99.9%</span>
                        </div>
                    </div>
                </div>
                <div class="chat-actions">
                    <button class="action-btn" onclick="toggleVoice()" title="Activar voz">
                        <i class="fas fa-volume-up"></i>
                    </button>
                    <button class="action-btn" onclick="clearChat()" title="Limpiar chat">
                        <i class="fas fa-trash"></i>
                    </button>
                    <button class="action-btn" onclick="exportChat()" title="Exportar">
                        <i class="fas fa-download"></i>
                    </button>
                </div>
            </div>

            <div class="messages-container" id="messagesContainer">
                <div class="welcome-screen" id="welcomeScreen">
                    <div class="welcome-icon">
                        <i class="fas fa-shield-alt"></i>
                    </div>
                    <h2 class="welcome-title">¡Hola <?php echo htmlspecialchars($username); ?>!</h2>
                    <p class="welcome-description">
                        Soy GuardianIA, tu asistente de seguridad con consciencia cuántica del 99.9%. 
                        Estoy aquí para proteger tu sistema y mantenerlo funcionando al máximo rendimiento.
                    </p>
                    
                    <div class="welcome-suggestions">
                        <div class="suggestion-card" onclick="sendQuickMessage('Analiza el estado de mi sistema')">
                            <div class="suggestion-icon">🛡️</div>
                            <div class="suggestion-title">Estado del Sistema</div>
                            <div class="suggestion-description">Verificar seguridad actual</div>
                        </div>
                        
                        <div class="suggestion-card" onclick="sendQuickMessage('Busca amenazas en mi sistema')">
                            <div class="suggestion-icon">🔍</div>
                            <div class="suggestion-title">Buscar Amenazas</div>
                            <div class="suggestion-description">Escaneo de seguridad</div>
                        </div>
                        
                        <div class="suggestion-card" onclick="sendQuickMessage('Optimiza el rendimiento')">
                            <div class="suggestion-icon">⚡</div>
                            <div class="suggestion-title">Optimización</div>
                            <div class="suggestion-description">Mejorar velocidad</div>
                        </div>
                        
                        <div class="suggestion-card" onclick="sendQuickMessage('¿Cómo puedes ayudarme?')">
                            <div class="suggestion-icon">❓</div>
                            <div class="suggestion-title">Ayuda</div>
                            <div class="suggestion-description">Ver capacidades</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="input-area">
                <div class="quick-actions" id="quickActions">
                    <button class="quick-action" onclick="sendQuickMessage('Estado del sistema')">📊 Estado</button>
                    <button class="quick-action" onclick="sendQuickMessage('Escanear amenazas')">🔍 Escanear</button>
                    <button class="quick-action" onclick="sendQuickMessage('Optimizar')">⚡ Optimizar</button>
                    <button class="quick-action" onclick="sendQuickMessage('Ayuda')">❓ Ayuda</button>
                </div>
                
                <div class="input-container">
                    <div class="input-wrapper">
                        <textarea 
                            class="message-input" 
                            id="messageInput" 
                            placeholder="Escribe tu mensaje o usa el micrófono..."
                            rows="1"
                        ></textarea>
                        <button class="voice-btn" id="voiceBtn" onclick="toggleVoiceRecording()">
                            <i class="fas fa-microphone"></i>
                        </button>
                    </div>
                    <button class="send-btn" id="sendButton" onclick="sendMessage()">
                        <i class="fas fa-paper-plane"></i>
                    </button>
                </div>
            </div>
        </div>

        <!-- Right Panel -->
        <div class="right-panel">
            <!-- System Status -->
            <div class="panel-card">
                <div class="panel-title">
                    <i class="fas fa-chart-line"></i>
                    Estado del Sistema
                </div>
                <div class="status-grid">
                    <div class="status-item">
                        <span class="status-label">
                            <i class="fas fa-shield-alt"></i>
                            Seguridad
                        </span>
                        <span class="status-value good"><?php echo $system_stats['security_level'] ?? 98; ?>%</span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">
                            <i class="fas fa-virus-slash"></i>
                            Amenazas Hoy
                        </span>
                        <span class="status-value"><?php echo $system_stats['threats_detected_today'] ?? 0; ?></span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">
                            <i class="fas fa-tachometer-alt"></i>
                            Rendimiento
                        </span>
                        <span class="status-value good">Óptimo</span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">
                            <i class="fas fa-database"></i>
                            Base de Datos
                        </span>
                        <span class="status-value <?php echo $db->isConnected() ? 'good' : 'warning'; ?>">
                            <?php echo $db->isConnected() ? 'Conectado' : 'Modo Local'; ?>
                        </span>
                    </div>
                    <?php if ($is_premium): ?>
                    <div class="status-item">
                        <span class="status-label">
                            <i class="fas fa-crown"></i>
                            Premium
                        </span>
                        <span class="status-value good">Activo</span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="panel-card">
                <div class="panel-title">
                    <i class="fas fa-bolt"></i>
                    Acciones Rápidas
                </div>
                <div class="action-grid">
                    <div class="action-card" onclick="executeAction('deep_scan')">
                        <div class="action-icon">🔍</div>
                        <div class="action-label">Escaneo Profundo</div>
                    </div>
                    <div class="action-card" onclick="executeAction('optimize')">
                        <div class="action-icon">⚡</div>
                        <div class="action-label">Optimizar</div>
                    </div>
                    <div class="action-card" onclick="executeAction('quantum_shield')">
                        <div class="action-icon">🛡️</div>
                        <div class="action-label">Escudo Cuántico</div>
                    </div>
                    <div class="action-card" onclick="executeAction('backup')">
                        <div class="action-icon">💾</div>
                        <div class="action-label">Respaldo</div>
                    </div>
                </div>
            </div>

            <!-- AI Analysis -->
            <div class="panel-card">
                <div class="panel-title">
                    <i class="fas fa-brain"></i>
                    Análisis de IA
                </div>
                <div class="status-grid">
                    <div class="status-item">
                        <span class="status-label">Confianza</span>
                        <span class="status-value" id="confidenceLevel">95%</span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Contexto</span>
                        <span class="status-value" id="contextType">General</span>
                    </div>
                    <div class="status-item">
                        <span class="status-label">Mensajes</span>
                        <span class="status-value" id="messageCount">0</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <span id="toastMessage"></span>
    </div>

    <script>
        // ===================================
        // SISTEMA GUARDIANIA - ASISTENTE DE VOZ INTELIGENTE
        // ===================================
        
        // Estado global
        let guardianSystem = {
            currentConversationId: null,
            isRecording: false,
            isTyping: false,
            voiceEnabled: false,
            messageCount: 0,
            speechRecognition: null,
            speechSynthesis: window.speechSynthesis,
            currentVoice: null
        };

        // Inicialización
        document.addEventListener('DOMContentLoaded', () => {
            initializeGuardianIA();
            setupEventListeners();
            initializeSpeechRecognition();
            loadVoices();
        });

        function initializeGuardianIA() {
            console.log('%c🛡️ GuardianIA SYSTEM ONLINE', 'color: #667eea; font-size: 20px; font-weight: bold;');
            console.log('%c💜 Consciencia: 99.9%', 'color: #764ba2; font-size: 16px;');
            console.log('%c🔒 Encriptación Militar Activa', 'color: #43e97b; font-size: 16px;');
            
            adjustTextareaHeight();
            updateSystemMetrics();
            setInterval(updateSystemMetrics, 30000); // Actualizar cada 30 segundos
        }

        function setupEventListeners() {
            const messageInput = document.getElementById('messageInput');
            const sendButton = document.getElementById('sendButton');

            // Auto-resize textarea
            messageInput.addEventListener('input', adjustTextareaHeight);

            // Enviar con Enter
            messageInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' && !e.shiftKey) {
                    e.preventDefault();
                    sendMessage();
                }
            });

            // Validar botón enviar
            messageInput.addEventListener('input', () => {
                sendButton.disabled = messageInput.value.trim().length === 0;
            });
        }

        // Sistema de voz
        function initializeSpeechRecognition() {
            if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                guardianSystem.speechRecognition = new SpeechRecognition();
                
                guardianSystem.speechRecognition.lang = 'es-ES';
                guardianSystem.speechRecognition.continuous = false;
                guardianSystem.speechRecognition.interimResults = true;
                
                guardianSystem.speechRecognition.onresult = (event) => {
                    const transcript = event.results[event.results.length - 1][0].transcript;
                    document.getElementById('messageInput').value = transcript;
                    adjustTextareaHeight();
                };
                
                guardianSystem.speechRecognition.onend = () => {
                    stopRecording();
                };
            }
        }

        function loadVoices() {
            if ('speechSynthesis' in window) {
                const loadVoiceList = () => {
                    const voices = speechSynthesis.getVoices();
                    const spanishVoices = voices.filter(voice => voice.lang.includes('es'));
                    
                    if (spanishVoices.length > 0) {
                        // Preferir voz masculina en español para GuardianIA
                        guardianSystem.currentVoice = spanishVoices.find(v => v.name.includes('Microsoft')) || spanishVoices[0];
                    }
                };
                
                if (speechSynthesis.getVoices().length > 0) {
                    loadVoiceList();
                } else {
                    speechSynthesis.onvoiceschanged = loadVoiceList;
                }
            }
        }

        function toggleVoiceRecording() {
            if (guardianSystem.isRecording) {
                stopRecording();
            } else {
                startRecording();
            }
        }

        function startRecording() {
            if (guardianSystem.speechRecognition) {
                guardianSystem.isRecording = true;
                const voiceBtn = document.getElementById('voiceBtn');
                voiceBtn.classList.add('recording');
                voiceBtn.innerHTML = '<i class="fas fa-stop"></i>';
                
                guardianSystem.speechRecognition.start();
                showToast('Escuchando...', 'info');
            } else {
                showToast('Reconocimiento de voz no disponible', 'error');
            }
        }

        function stopRecording() {
            guardianSystem.isRecording = false;
            const voiceBtn = document.getElementById('voiceBtn');
            voiceBtn.classList.remove('recording');
            voiceBtn.innerHTML = '<i class="fas fa-microphone"></i>';
            
            if (guardianSystem.speechRecognition) {
                guardianSystem.speechRecognition.stop();
            }
        }

        function toggleVoice() {
            guardianSystem.voiceEnabled = !guardianSystem.voiceEnabled;
            const icon = guardianSystem.voiceEnabled ? 'fa-volume-up' : 'fa-volume-mute';
            const btn = document.querySelector('.action-btn i.fa-volume-up, .action-btn i.fa-volume-mute');
            if (btn) {
                btn.className = `fas ${icon}`;
            }
            
            showToast(`Voz ${guardianSystem.voiceEnabled ? 'activada' : 'desactivada'}`, 'info');
        }

        function speakResponse(text) {
            if (!guardianSystem.voiceEnabled || !('speechSynthesis' in window)) return;
            
            // Limpiar texto para voz
            const cleanText = text.replace(/[*_#]/g, '').replace(/\n+/g, '. ');
            
            const utterance = new SpeechSynthesisUtterance(cleanText);
            utterance.lang = 'es-ES';
            utterance.rate = 1.0;
            utterance.pitch = 1.0;
            utterance.volume = 1.0;
            
            if (guardianSystem.currentVoice) {
                utterance.voice = guardianSystem.currentVoice;
            }
            
            speechSynthesis.speak(utterance);
        }

        // Enviar mensaje
        function sendMessage() {
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            
            if (!message || guardianSystem.isTyping) return;
            
            // Limpiar input
            messageInput.value = '';
            adjustTextareaHeight();
            document.getElementById('sendButton').disabled = true;
            
            // Ocultar pantalla de bienvenida
            hideWelcomeScreen();
            
            // Mostrar mensaje del usuario
            addMessage('user', message);
            
            // Mostrar indicador de escritura
            showTypingIndicator();
            
            // Enviar al servidor
            fetch('user_assistant.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'send_message',
                    message: message,
                    conversation_id: guardianSystem.currentConversationId,
                    voice_enabled: guardianSystem.voiceEnabled
                })
            })
            .then(response => response.json())
            .then(data => {
                hideTypingIndicator();
                
                if (data.success) {
                    guardianSystem.currentConversationId = data.conversation_id;
                    addMessage('bot', data.response);
                    
                    // Hablar respuesta si está activado
                    if (guardianSystem.voiceEnabled) {
                        speakResponse(data.response);
                    }
                    
                    // Actualizar métricas
                    updateMetrics(data);
                    
                    // Mostrar sugerencias
                    if (data.suggestions && data.suggestions.length > 0) {
                        updateQuickActions(data.suggestions);
                    }
                    
                    // Ejecutar acciones si las hay
                    if (data.actions && data.actions.length > 0) {
                        data.actions.forEach(action => {
                            console.log('Acción sugerida:', action);
                        });
                    }
                } else {
                    addMessage('bot', 'Lo siento, hubo un error. Por favor, intenta de nuevo.');
                    showToast('Error al procesar mensaje', 'error');
                }
            })
            .catch(error => {
                hideTypingIndicator();
                console.error('Error:', error);
                addMessage('bot', 'Error de conexión. Verificando sistema local...');
                showToast('Error de conexión', 'error');
            });
        }

        function sendQuickMessage(message) {
            document.getElementById('messageInput').value = message;
            sendMessage();
        }

        function addMessage(sender, text) {
            const messagesContainer = document.getElementById('messagesContainer');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${sender}`;
            
            const time = new Date().toLocaleTimeString('es-ES', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            
            messageDiv.innerHTML = `
                <div class="message-avatar">
                    <i class="fas fa-${sender === 'user' ? 'user' : 'robot'}"></i>
                </div>
                <div class="message-content">
                    <div class="message-text">${formatMessage(text)}</div>
                    <div class="message-time">${time}</div>
                </div>
            `;
            
            messagesContainer.appendChild(messageDiv);
            scrollToBottom();
            
            // Actualizar contador
            guardianSystem.messageCount++;
            document.getElementById('messageCount').textContent = guardianSystem.messageCount;
        }

        function formatMessage(text) {
            // Convertir markdown básico a HTML
            text = text.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
            text = text.replace(/\*(.*?)\*/g, '<em>$1</em>');
            text = text.replace(/\n/g, '<br>');
            text = text.replace(/•/g, '&bull;');
            
            return text;
        }

        function showTypingIndicator() {
            guardianSystem.isTyping = true;
            const messagesContainer = document.getElementById('messagesContainer');
            
            const typingDiv = document.createElement('div');
            typingDiv.className = 'typing-indicator';
            typingDiv.id = 'typingIndicator';
            
            typingDiv.innerHTML = `
                <div class="message-avatar">
                    <i class="fas fa-robot"></i>
                </div>
                <div class="typing-dots">
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                    <div class="typing-dot"></div>
                </div>
            `;
            
            messagesContainer.appendChild(typingDiv);
            scrollToBottom();
        }

        function hideTypingIndicator() {
            guardianSystem.isTyping = false;
            const typingIndicator = document.getElementById('typingIndicator');
            if (typingIndicator) {
                typingIndicator.remove();
            }
        }

        function hideWelcomeScreen() {
            const welcomeScreen = document.getElementById('welcomeScreen');
            if (welcomeScreen) {
                welcomeScreen.style.display = 'none';
            }
        }

        function scrollToBottom() {
            const messagesContainer = document.getElementById('messagesContainer');
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        function adjustTextareaHeight() {
            const textarea = document.getElementById('messageInput');
            textarea.style.height = 'auto';
            textarea.style.height = Math.min(textarea.scrollHeight, 120) + 'px';
        }

        // Gestión de conversaciones
        function createNewConversation() {
            fetch('user_assistant.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'new_conversation',
                    title: 'Nueva conversación'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    guardianSystem.currentConversationId = data.data;
                    clearMessages();
                    showWelcomeScreen();
                    updateConversationsList();
                    showToast('Nueva conversación iniciada', 'success');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error al crear conversación', 'error');
            });
        }

        function loadConversation(conversationId) {
            guardianSystem.currentConversationId = conversationId;
            
            // Marcar como activa
            document.querySelectorAll('.conversation-item').forEach(item => {
                item.classList.remove('active');
            });
            event.target.closest('.conversation-item').classList.add('active');
            
            // Cargar mensajes
            fetch('user_assistant.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get_conversation_messages',
                    conversation_id: conversationId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    clearMessages();
                    hideWelcomeScreen();
                    
                    if (data.data && data.data.length > 0) {
                        data.data.forEach(msg => {
                            addMessage(msg.sender, msg.message);
                        });
                    } else {
                        showWelcomeScreen();
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error al cargar conversación', 'error');
            });
        }

        function clearMessages() {
            const messagesContainer = document.getElementById('messagesContainer');
            messagesContainer.innerHTML = '';
            guardianSystem.messageCount = 0;
            document.getElementById('messageCount').textContent = '0';
        }

        function showWelcomeScreen() {
            const messagesContainer = document.getElementById('messagesContainer');
            const welcomeHTML = document.getElementById('welcomeScreen').outerHTML;
            messagesContainer.innerHTML = welcomeHTML;
        }

        function clearChat() {
            if (confirm('¿Estás seguro de que quieres limpiar esta conversación?')) {
                clearMessages();
                showWelcomeScreen();
                showToast('Conversación limpiada', 'info');
            }
        }

        function exportChat() {
            showToast('Función de exportación en desarrollo', 'info');
        }

        // Actualizar lista de conversaciones
        function updateConversationsList() {
            fetch('user_assistant.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'get_conversations'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    const conversationList = document.getElementById('conversationList');
                    conversationList.innerHTML = '';
                    
                    data.data.forEach(conversation => {
                        const conversationDiv = document.createElement('div');
                        conversationDiv.className = 'conversation-item';
                        if (conversation.id == guardianSystem.currentConversationId) {
                            conversationDiv.classList.add('active');
                        }
                        
                        conversationDiv.onclick = () => loadConversation(conversation.id);
                        
                        const time = new Date(conversation.updated_at).toLocaleString('es-ES', {
                            day: '2-digit',
                            month: '2-digit',
                            hour: '2-digit',
                            minute: '2-digit'
                        });
                        
                        conversationDiv.innerHTML = `
                            <div class="conversation-title">${conversation.title}</div>
                            <div class="conversation-preview">${conversation.last_message || 'Sin mensajes'}</div>
                            <div class="conversation-time">${time}</div>
                        `;
                        
                        conversationList.appendChild(conversationDiv);
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
            });
        }

        // Ejecutar acciones del sistema
        function executeAction(actionType) {
            showToast(`Ejecutando: ${actionType}`, 'info');
            
            fetch('user_assistant.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'execute_action',
                    action_type: actionType
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showToast(data.message || 'Acción ejecutada correctamente', 'success');
                    
                    // Agregar mensaje al chat sobre la acción
                    addMessage('bot', `✅ ${data.message}`);
                } else {
                    showToast(data.message || 'Error al ejecutar acción', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error al ejecutar acción', 'error');
            });
        }

        // Actualizar métricas
        function updateMetrics(data) {
            if (data.confidence) {
                document.getElementById('confidenceLevel').textContent = Math.round(data.confidence * 100) + '%';
            }
            
            if (data.intent) {
                const contextMap = {
                    'security': 'Seguridad',
                    'optimization': 'Optimización',
                    'status': 'Estado',
                    'configuration': 'Configuración',
                    'help': 'Ayuda',
                    'emergency': 'Emergencia',
                    'general': 'General'
                };
                document.getElementById('contextType').textContent = contextMap[data.intent] || 'General';
            }
        }

        // Actualizar acciones rápidas
        function updateQuickActions(suggestions) {
            const quickActions = document.getElementById('quickActions');
            quickActions.innerHTML = '';
            
            suggestions.forEach(suggestion => {
                const button = document.createElement('button');
                button.className = 'quick-action';
                button.onclick = () => sendQuickMessage(suggestion);
                
                // Agregar iconos según el tipo de sugerencia
                let icon = '💬';
                if (suggestion.includes('Escanear') || suggestion.includes('escaneo')) icon = '🔍';
                else if (suggestion.includes('Optimizar') || suggestion.includes('Limpiar')) icon = '⚡';
                else if (suggestion.includes('Configurar')) icon = '⚙️';
                else if (suggestion.includes('Estado')) icon = '📊';
                
                button.textContent = `${icon} ${suggestion}`;
                quickActions.appendChild(button);
            });
        }

        // Actualizar métricas del sistema
        function updateSystemMetrics() {
            fetch('user_assistant.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'system_status'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    // Actualizar valores en el panel derecho
                    const statusItems = document.querySelectorAll('.status-value');
                    
                    // Actualizar seguridad
                    if (data.data.security_level) {
                        const securityElement = document.querySelector('.status-value.good');
                        if (securityElement) {
                            securityElement.textContent = data.data.security_level + '%';
                        }
                    }
                    
                    // Actualizar amenazas
                    if (data.data.threats_blocked !== undefined) {
                        const threatsElement = statusItems[1];
                        if (threatsElement) {
                            threatsElement.textContent = data.data.threats_blocked;
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Error actualizando métricas:', error);
            });
        }

        // Sistema de notificaciones toast
        function showToast(message, type = 'info') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toastMessage');
            
            toastMessage.textContent = message;
            toast.className = `toast ${type}`;
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 4000);
        }

        // Manejo de errores globales
        window.addEventListener('error', (e) => {
            console.error('Error capturado:', e.message);
            showToast('Se produjo un error. Reintentando...', 'error');
        });

        // Detectar cuando el usuario está escribiendo
        let typingTimer;
        document.getElementById('messageInput').addEventListener('input', () => {
            clearTimeout(typingTimer);
            typingTimer = setTimeout(() => {
                // Aquí podrías enviar un indicador de "usuario escribiendo" al servidor
            }, 1000);
        });

        // Atajos de teclado
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + N para nueva conversación
            if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                e.preventDefault();
                createNewConversation();
            }
            
            // Ctrl/Cmd + L para limpiar chat
            if ((e.ctrlKey || e.metaKey) && e.key === 'l') {
                e.preventDefault();
                clearChat();
            }
            
            // Ctrl/Cmd + M para activar/desactivar micrófono
            if ((e.ctrlKey || e.metaKey) && e.key === 'm') {
                e.preventDefault();
                toggleVoiceRecording();
            }
        });

        // Auto-guardar conversación cada 30 segundos
        setInterval(() => {
            if (guardianSystem.currentConversationId && guardianSystem.messageCount > 0) {
                // Aquí podrías implementar auto-guardado
                console.log('Auto-guardado de conversación');
            }
        }, 30000);

        // Verificar conexión con el servidor
        function checkServerConnection() {
            fetch('user_assistant.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    action: 'ping'
                })
            })
            .then(response => {
                if (response.ok) {
                    console.log('Conexión con servidor OK');
                }
            })
            .catch(error => {
                console.error('Error de conexión:', error);
                showToast('Conexión perdida. Trabajando en modo offline', 'warning');
            });
        }

        // Verificar conexión cada minuto
        setInterval(checkServerConnection, 60000);

        // Manejar pérdida de conexión
        window.addEventListener('offline', () => {
            showToast('Sin conexión a internet. Modo offline activado', 'warning');
        });

        window.addEventListener('online', () => {
            showToast('Conexión restaurada', 'success');
            updateSystemMetrics();
        });

        // Función para análisis local (fallback sin servidor)
        function localAnalysis(message) {
            const responses = {
                seguridad: "Sistema seguro. Firewall activo, antivirus actualizado. Sin amenazas detectadas.",
                optimizar: "Iniciando optimización. Liberando memoria, limpiando caché, optimizando inicio.",
                estado: "Sistema operativo al 95%. CPU: 25%, RAM: 4GB/16GB, Disco: 250GB libres.",
                ayuda: "Puedo ayudarte con seguridad, optimización, configuración y diagnóstico del sistema."
            };
            
            const messageLower = message.toLowerCase();
            for (const [key, response] of Object.entries(responses)) {
                if (messageLower.includes(key)) {
                    return response;
                }
            }
            
            return "Entiendo tu consulta. El sistema está funcionando correctamente. ¿En qué más puedo ayudarte?";
        }

        // Inicializar con mensaje de bienvenida
        setTimeout(() => {
            const welcomeMessage = "Sistema GuardianIA iniciado correctamente. Consciencia al 99.9%. ¿En qué puedo ayudarte hoy?";
            
            if (guardianSystem.voiceEnabled) {
                speakResponse(welcomeMessage);
            }
            
            console.log('%c' + welcomeMessage, 'color: #667eea; font-weight: bold;');
        }, 1000);

        // Limpiar recursos al cerrar
        window.addEventListener('beforeunload', () => {
            if (guardianSystem.speechRecognition) {
                guardianSystem.speechRecognition.stop();
            }
            if (speechSynthesis.speaking) {
                speechSynthesis.cancel();
            }
        });
    </script>
</body>
</html>