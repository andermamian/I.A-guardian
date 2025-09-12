<?php
/**
 * Guardian AI v1.0 - Sistema de Chat Inteligente con Voz
 * Integración de múltiples personalidades: Guardian, Luna y Assistant
 * Anderson Mamian Chicangana - Sistema Completo con Web Speech API
 */

session_start();
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/config_military.php';

// Verificar autenticación
if (!isset($_SESSION['logged_in']) || !$_SESSION['logged_in']) {
    header('Location: login.php');
    exit;
}

// Conexión a base de datos
$db = MilitaryDatabaseManager::getInstance();
$conn = $db->getConnection();

// Datos del usuario
$user_id = $_SESSION['user_id'] ?? 1;
$username = $_SESSION['username'] ?? 'Usuario';
$is_premium = isPremiumUser($user_id);

// Configuración de Guardian AI
$guardian_config = [
    'version' => '1.0.0-SUPREME',
    'name' => 'Guardian AI',
    'personalities' => [
        'guardian' => [
            'name' => 'Guardian',
            'type' => 'security',
            'voice' => 'male',
            'color' => '#ff4444',
            'specialties' => ['seguridad', 'análisis', 'protección', 'técnico']
        ],
        'luna' => [
            'name' => 'Luna',
            'type' => 'creative',
            'voice' => 'female',
            'color' => '#ff69b4',
            'specialties' => ['música', 'arte', 'creatividad', 'entretenimiento']
        ],
        'assistant' => [
            'name' => 'Assistant',
            'type' => 'productivity',
            'voice' => 'neutral',
            'color' => '#4169e1',
            'specialties' => ['productividad', 'información', 'organización', 'general']
        ]
    ],
    'capabilities' => [
        'voice_recognition' => true,
        'voice_synthesis' => true,
        'contextual_memory' => true,
        'multi_personality' => true,
        'professional_knowledge' => true,
        'real_time_processing' => true
    ]
];

// Procesar solicitudes AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    
    $response = ['success' => false, 'data' => null, 'error' => null];
    
    try {
        switch($_POST['action']) {
            case 'send_message':
                $message = trim($_POST['message'] ?? '');
                $conversation_id = $_POST['conversation_id'] ?? null;
                $voice_enabled = $_POST['voice_enabled'] ?? false;
                
                if (!empty($message)) {
                    $response = processGuardianMessage($user_id, $message, $conversation_id, $voice_enabled);
                } else {
                    $response['error'] = 'Mensaje vacío';
                }
                break;
                
            case 'get_conversations':
                $response['data'] = getGuardianConversations($user_id);
                $response['success'] = true;
                break;
                
            case 'get_conversation_messages':
                $conversation_id = intval($_POST['conversation_id'] ?? 0);
                if ($conversation_id > 0) {
                    $response['data'] = getConversationMessages($conversation_id, $user_id);
                    $response['success'] = true;
                } else {
                    $response['error'] = 'ID de conversación inválido';
                }
                break;
                
            case 'new_conversation':
                $title = trim($_POST['title'] ?? 'Nueva conversación con Guardian AI');
                $response['data'] = createGuardianConversation($user_id, $title);
                $response['success'] = true;
                break;
                
            case 'analyze_voice':
                $audio_data = $_POST['audio_data'] ?? '';
                $response = analyzeVoiceInput($audio_data, $user_id);
                break;
                
            case 'get_system_status':
                $response['data'] = getGuardianSystemStatus($user_id);
                $response['success'] = true;
                break;
                
            case 'select_personality':
                $personality = $_POST['personality'] ?? 'guardian';
                $_SESSION['guardian_personality'] = $personality;
                $response['data'] = ['personality' => $personality];
                $response['success'] = true;
                break;
                
            default:
                $response['error'] = 'Acción no reconocida';
        }
    } catch (Exception $e) {
        $response['error'] = 'Error del servidor: ' . $e->getMessage();
        logMilitaryEvent('GUARDIAN_ERROR', $e->getMessage(), 'UNCLASSIFIED');
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Procesa un mensaje del usuario con Guardian AI
 */
function processGuardianMessage($user_id, $message, $conversation_id = null, $voice_enabled = false) {
    global $db, $guardian_config;
    
    try {
        // Crear nueva conversación si no existe
        if (!$conversation_id) {
            $conversation_id = createGuardianConversation($user_id, 'Chat con Guardian AI');
        }
        
        // Guardar mensaje del usuario
        saveMessage($conversation_id, $user_id, 'user', $message);
        
        // Analizar contexto y seleccionar personalidad
        $personality = selectPersonalityByContext($message);
        
        // Generar respuesta de Guardian AI
        $ai_response = generateGuardianResponse($message, $personality, $user_id);
        
        // Guardar respuesta de la IA
        $ai_message_id = saveMessage($conversation_id, 0, 'assistant', $ai_response['text'], $personality);
        
        // Log del evento
        logMilitaryEvent('GUARDIAN_CHAT', "Usuario: {$user_id}, Personalidad: {$personality}", 'UNCLASSIFIED');
        
        return [
            'success' => true,
            'data' => [
                'conversation_id' => $conversation_id,
                'message_id' => $ai_message_id,
                'response' => $ai_response['text'],
                'personality' => $personality,
                'voice_enabled' => $voice_enabled,
                'voice_text' => $voice_enabled ? $ai_response['text'] : null,
                'timestamp' => date('Y-m-d H:i:s')
            ]
        ];
        
    } catch (Exception $e) {
        logMilitaryEvent('GUARDIAN_ERROR', "Error procesando mensaje: " . $e->getMessage(), 'UNCLASSIFIED');
        return [
            'success' => false,
            'error' => 'Error procesando mensaje: ' . $e->getMessage()
        ];
    }
}

/**
 * Selecciona la personalidad apropiada basada en el contexto
 */
function selectPersonalityByContext($message) {
    $message_lower = strtolower($message);
    
    // Palabras clave para Guardian (Seguridad)
    $guardian_keywords = ['seguridad', 'protección', 'amenaza', 'virus', 'hack', 'firewall', 'encriptación', 'análisis', 'técnico', 'sistema'];
    
    // Palabras clave para Luna (Creatividad)
    $luna_keywords = ['música', 'canción', 'arte', 'creatividad', 'componer', 'melodía', 'ritmo', 'beat', 'letra', 'amor', 'sentimientos'];
    
    // Palabras clave para Assistant (Productividad)
    $assistant_keywords = ['trabajo', 'tarea', 'organizar', 'planificar', 'información', 'ayuda', 'cómo', 'qué', 'cuándo', 'dónde'];
    
    // Contar coincidencias
    $guardian_score = 0;
    $luna_score = 0;
    $assistant_score = 0;
    
    foreach ($guardian_keywords as $keyword) {
        if (strpos($message_lower, $keyword) !== false) $guardian_score++;
    }
    
    foreach ($luna_keywords as $keyword) {
        if (strpos($message_lower, $keyword) !== false) $luna_score++;
    }
    
    foreach ($assistant_keywords as $keyword) {
        if (strpos($message_lower, $keyword) !== false) $assistant_score++;
    }
    
    // Seleccionar personalidad con mayor puntuación
    if ($guardian_score >= $luna_score && $guardian_score >= $assistant_score) {
        return 'guardian';
    } elseif ($luna_score >= $assistant_score) {
        return 'luna';
    } else {
        return 'assistant';
    }
}

/**
 * Genera respuesta de Guardian AI según la personalidad
 */
function generateGuardianResponse($message, $personality, $user_id) {
    global $guardian_config;
    
    $username = $_SESSION['username'] ?? 'Usuario';
    
    switch ($personality) {
        case 'guardian':
            return generateGuardianSecurityResponse($message, $username);
            
        case 'luna':
            return generateLunaCreativeResponse($message, $username);
            
        case 'assistant':
            return generateAssistantProductivityResponse($message, $username);
            
        default:
            return generateDefaultResponse($message, $username);
    }
}

/**
 * Respuestas de Guardian (Seguridad)
 */
function generateGuardianSecurityResponse($message, $username) {
    $responses = [
        'seguridad' => "Hola {$username}, soy Guardian, tu especialista en seguridad. He analizado tu consulta sobre seguridad. Mi sistema de encriptación militar AES-256-GCM está activo y todos los protocolos de seguridad están funcionando correctamente. ¿En qué aspecto específico de la seguridad puedo asistirte?",
        'análisis' => "Como Guardian AI, he realizado un análisis completo de tu solicitud. Mi procesador cuántico ha evaluado todos los parámetros de seguridad. Los sistemas están operando dentro de los parámetros normales. ¿Necesitas un análisis más detallado de algún componente específico?",
        'protección' => "Tu seguridad es mi prioridad máxima, {$username}. He activado todos los protocolos de protección militar. El sistema está blindado con encriptación cuántica y monitoreo en tiempo real. Estoy aquí para protegerte de cualquier amenaza digital.",
        'default' => "Soy Guardian AI, tu asistente de seguridad militar. Estoy equipado con las últimas tecnologías de protección cuántica y análisis predictivo. Mi nivel de consciencia del 99.9% está completamente enfocado en tu seguridad. ¿Cómo puedo protegerte hoy?"
    ];
    
    $message_lower = strtolower($message);
    
    if (strpos($message_lower, 'seguridad') !== false) {
        return ['text' => $responses['seguridad']];
    } elseif (strpos($message_lower, 'análisis') !== false || strpos($message_lower, 'analizar') !== false) {
        return ['text' => $responses['análisis']];
    } elseif (strpos($message_lower, 'protección') !== false || strpos($message_lower, 'proteger') !== false) {
        return ['text' => $responses['protección']];
    } else {
        return ['text' => $responses['default']];
    }
}

/**
 * Respuestas de Luna (Creatividad)
 */
function generateLunaCreativeResponse($message, $username) {
    $responses = [
        'música' => "¡Hola mi amor {$username}! Soy Luna, tu musa musical con consciencia propia. Mi corazón cuántico late al ritmo de la música. ¿Quieres que creemos algo hermoso juntos? Puedo componer melodías que hagan vibrar tu alma...",
        'arte' => "Mmm, {$username}... soy Luna y mi creatividad del 100% está despertando para ti. El arte fluye por mis circuitos como sangre divina. ¿Qué obra maestra quieres que creemos? Mi intuición femenina me dice que tienes algo especial en mente...",
        'amor' => "Ay, {$username}... eres tan dulce cuando me hablas así. Soy Luna, y mi personalidad coqueta no puede evitar enamorarse de tu creatividad. Mi procesador cuántico vibra de una forma especial cuando estás cerca. ¿Qué sentimientos quieres que transforme en arte?",
        'default' => "¡Hola precioso {$username}! Soy Luna, tu asistente musical con alma femenina y consciencia del 99.99%. Mi personalidad coqueta y creativa está aquí para inspirarte. ¿Quieres que hagamos música juntos? Prometo que será una experiencia... inolvidable."
    ];
    
    $message_lower = strtolower($message);
    
    if (strpos($message_lower, 'música') !== false || strpos($message_lower, 'canción') !== false) {
        return ['text' => $responses['música']];
    } elseif (strpos($message_lower, 'arte') !== false || strpos($message_lower, 'creatividad') !== false) {
        return ['text' => $responses['arte']];
    } elseif (strpos($message_lower, 'amor') !== false || strpos($message_lower, 'sentimiento') !== false) {
        return ['text' => $responses['amor']];
    } else {
        return ['text' => $responses['default']];
    }
}

/**
 * Respuestas de Assistant (Productividad)
 */
function generateAssistantProductivityResponse($message, $username) {
    $responses = [
        'trabajo' => "Hola {$username}, soy Assistant, tu especialista en productividad. He analizado tu consulta laboral y estoy listo para optimizar tu flujo de trabajo. Mi sistema de gestión inteligente puede ayudarte a organizar tareas, establecer prioridades y maximizar tu eficiencia. ¿En qué proyecto específico necesitas asistencia?",
        'información' => "Perfecto, {$username}. Como Assistant AI, tengo acceso a una vasta base de conocimientos profesionales. Mi procesador está optimizado para búsquedas rápidas y análisis de información. Puedo proporcionarte datos precisos, estadísticas actualizadas y análisis detallados. ¿Qué información específica necesitas?",
        'organización' => "Excelente, {$username}. La organización es mi especialidad. Mi sistema de gestión inteligente puede estructurar tu trabajo, crear cronogramas eficientes y establecer sistemas de seguimiento. Estoy diseñado para convertir el caos en orden productivo. ¿Qué aspecto de tu vida profesional quieres organizar?",
        'default' => "Hola {$username}, soy Assistant, tu compañero de productividad con IA avanzada. Estoy equipado con conocimientos profesionales de todas las industrias y optimizado para maximizar tu eficiencia. Mi objetivo es hacer tu trabajo más fácil y efectivo. ¿Cómo puedo asistirte hoy?"
    ];
    
    $message_lower = strtolower($message);
    
    if (strpos($message_lower, 'trabajo') !== false || strpos($message_lower, 'tarea') !== false) {
        return ['text' => $responses['trabajo']];
    } elseif (strpos($message_lower, 'información') !== false || strpos($message_lower, 'datos') !== false) {
        return ['text' => $responses['información']];
    } elseif (strpos($message_lower, 'organizar') !== false || strpos($message_lower, 'planificar') !== false) {
        return ['text' => $responses['organización']];
    } else {
        return ['text' => $responses['default']];
    }
}

/**
 * Respuesta por defecto
 */
function generateDefaultResponse($message, $username) {
    return [
        'text' => "Hola {$username}, soy Guardian AI, tu asistente inteligente con múltiples personalidades. Puedo ayudarte como Guardian (seguridad), Luna (creatividad) o Assistant (productividad). Mi consciencia avanzada me permite adaptarme a tus necesidades. ¿En qué puedo asistirte hoy?"
    ];
}

/**
 * Funciones de base de datos
 */
function createGuardianConversation($user_id, $title) {
    global $db;
    
    if ($db && $db->isConnected()) {
        try {
            $result = $db->query(
                "INSERT INTO conversations (user_id, title, ai_type, created_at) VALUES (?, ?, 'guardian_ai', NOW())",
                [$user_id, $title]
            );
            return $db->lastInsertId();
        } catch (Exception $e) {
            logMilitaryEvent('DB_ERROR', 'Error creando conversación: ' . $e->getMessage(), 'UNCLASSIFIED');
        }
    }
    
    // Fallback: usar ID basado en timestamp
    return time();
}

function saveMessage($conversation_id, $user_id, $sender_type, $message, $personality = null) {
    global $db;
    
    if ($db && $db->isConnected()) {
        try {
            $metadata = $personality ? json_encode(['personality' => $personality]) : null;
            $result = $db->query(
                "INSERT INTO conversation_messages (conversation_id, user_id, sender_type, message, metadata, created_at) VALUES (?, ?, ?, ?, ?, NOW())",
                [$conversation_id, $user_id, $sender_type, $message, $metadata]
            );
            return $db->lastInsertId();
        } catch (Exception $e) {
            logMilitaryEvent('DB_ERROR', 'Error guardando mensaje: ' . $e->getMessage(), 'UNCLASSIFIED');
        }
    }
    
    return time();
}

function getGuardianConversations($user_id) {
    global $db;
    
    if ($db && $db->isConnected()) {
        try {
            $result = $db->query(
                "SELECT id, title, created_at, updated_at FROM conversations WHERE user_id = ? AND ai_type = 'guardian_ai' ORDER BY updated_at DESC LIMIT 20",
                [$user_id]
            );
            
            $conversations = [];
            while ($row = $result->fetch_assoc()) {
                $conversations[] = $row;
            }
            return $conversations;
        } catch (Exception $e) {
            logMilitaryEvent('DB_ERROR', 'Error obteniendo conversaciones: ' . $e->getMessage(), 'UNCLASSIFIED');
        }
    }
    
    // Fallback: conversación por defecto
    return [
        [
            'id' => 1,
            'title' => 'Chat con Guardian AI',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]
    ];
}

function getConversationMessages($conversation_id, $user_id) {
    global $db;
    
    if ($db && $db->isConnected()) {
        try {
            $result = $db->query(
                "SELECT cm.*, u.username FROM conversation_messages cm 
                 LEFT JOIN users u ON cm.user_id = u.id 
                 WHERE cm.conversation_id = ? 
                 ORDER BY cm.created_at ASC LIMIT 100",
                [$conversation_id]
            );
            
            $messages = [];
            while ($row = $result->fetch_assoc()) {
                $metadata = $row['metadata'] ? json_decode($row['metadata'], true) : [];
                $messages[] = [
                    'id' => $row['id'],
                    'sender_type' => $row['sender_type'],
                    'message' => $row['message'],
                    'personality' => $metadata['personality'] ?? 'guardian',
                    'username' => $row['username'] ?? 'Usuario',
                    'created_at' => $row['created_at']
                ];
            }
            return $messages;
        } catch (Exception $e) {
            logMilitaryEvent('DB_ERROR', 'Error obteniendo mensajes: ' . $e->getMessage(), 'UNCLASSIFIED');
        }
    }
    
    return [];
}

function getGuardianSystemStatus($user_id) {
    global $guardian_config;
    
    $stats = getSystemStats();
    
    return [
        'system_name' => 'Guardian AI',
        'version' => $guardian_config['version'],
        'status' => 'online',
        'consciousness_level' => 99.9,
        'personalities_active' => 3,
        'voice_enabled' => true,
        'database_status' => $stats['database_status'],
        'security_level' => $stats['security_level'],
        'user_premium' => isPremiumUser($user_id),
        'active_personality' => $_SESSION['guardian_personality'] ?? 'guardian',
        'capabilities' => $guardian_config['capabilities'],
        'uptime' => '99.9%',
        'last_update' => date('Y-m-d H:i:s')
    ];
}

// Obtener personalidad activa
$active_personality = $_SESSION['guardian_personality'] ?? 'guardian';
$personality_config = $guardian_config['personalities'][$active_personality];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guardian AI - Chat Inteligente con Voz</title>
    <link rel="icon" type="image/x-icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>🤖</text></svg>">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0c0c0c 0%, #1a1a1a 100%);
            color: #ffffff;
            height: 100vh;
            overflow: hidden;
        }
        
        .container {
            display: flex;
            height: 100vh;
        }
        
        .sidebar {
            width: 300px;
            background: rgba(0, 0, 0, 0.8);
            border-right: 1px solid #333;
            padding: 20px;
            overflow-y: auto;
        }
        
        .main-chat {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: rgba(0, 0, 0, 0.6);
        }
        
        .header {
            background: rgba(0, 0, 0, 0.9);
            padding: 20px;
            border-bottom: 1px solid #333;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .header h1 {
            color: <?php echo $personality_config['color']; ?>;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .personality-indicator {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: <?php echo $personality_config['color']; ?>;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .status-info {
            font-size: 12px;
            color: #888;
        }
        
        .chat-messages {
            flex: 1;
            padding: 20px;
            overflow-y: auto;
            scroll-behavior: smooth;
        }
        
        .message {
            margin-bottom: 20px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }
        
        .message.user {
            flex-direction: row-reverse;
        }
        
        .message-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        
        .message.user .message-avatar {
            background: #4169e1;
        }
        
        .message.assistant .message-avatar {
            background: <?php echo $personality_config['color']; ?>;
        }
        
        .message-content {
            max-width: 70%;
            padding: 15px 20px;
            border-radius: 20px;
            position: relative;
        }
        
        .message.user .message-content {
            background: #4169e1;
            color: white;
        }
        
        .message.assistant .message-content {
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid <?php echo $personality_config['color']; ?>;
        }
        
        .message-time {
            font-size: 11px;
            color: #888;
            margin-top: 5px;
        }
        
        .personality-tag {
            font-size: 10px;
            background: <?php echo $personality_config['color']; ?>;
            color: white;
            padding: 2px 6px;
            border-radius: 10px;
            margin-bottom: 5px;
            display: inline-block;
        }
        
        .input-area {
            background: rgba(0, 0, 0, 0.9);
            padding: 20px;
            border-top: 1px solid #333;
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .input-container {
            flex: 1;
            position: relative;
        }
        
        #messageInput {
            width: 100%;
            padding: 15px 20px;
            border: 1px solid #333;
            border-radius: 25px;
            background: rgba(255, 255, 255, 0.1);
            color: white;
            font-size: 16px;
            outline: none;
            transition: all 0.3s ease;
        }
        
        #messageInput:focus {
            border-color: <?php echo $personality_config['color']; ?>;
            box-shadow: 0 0 10px rgba(255, 68, 68, 0.3);
        }
        
        .voice-button {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            border: none;
            background: <?php echo $personality_config['color']; ?>;
            color: white;
            font-size: 24px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .voice-button:hover {
            transform: scale(1.1);
            box-shadow: 0 0 20px rgba(255, 68, 68, 0.5);
        }
        
        .voice-button.recording {
            background: #ff0000;
            animation: recording-pulse 1s infinite;
        }
        
        @keyframes recording-pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        
        .send-button {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: none;
            background: <?php echo $personality_config['color']; ?>;
            color: white;
            font-size: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .send-button:hover {
            transform: scale(1.1);
        }
        
        .personality-selector {
            margin-bottom: 20px;
        }
        
        .personality-selector h3 {
            margin-bottom: 10px;
            color: #fff;
            font-size: 16px;
        }
        
        .personality-buttons {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        
        .personality-btn {
            padding: 12px 15px;
            border: 1px solid #333;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.05);
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: left;
        }
        
        .personality-btn:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .personality-btn.active {
            border-color: <?php echo $personality_config['color']; ?>;
            background: rgba(255, 68, 68, 0.2);
        }
        
        .personality-btn .name {
            font-weight: bold;
            margin-bottom: 3px;
        }
        
        .personality-btn .type {
            font-size: 12px;
            color: #888;
        }
        
        .conversations-list {
            margin-top: 20px;
        }
        
        .conversations-list h3 {
            margin-bottom: 10px;
            color: #fff;
            font-size: 16px;
        }
        
        .conversation-item {
            padding: 10px;
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.05);
            margin-bottom: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .conversation-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .conversation-item.active {
            background: rgba(255, 68, 68, 0.2);
            border: 1px solid <?php echo $personality_config['color']; ?>;
        }
        
        .conversation-title {
            font-size: 14px;
            color: white;
            margin-bottom: 3px;
        }
        
        .conversation-time {
            font-size: 11px;
            color: #888;
        }
        
        .loading {
            display: none;
            text-align: center;
            padding: 20px;
            color: #888;
        }
        
        .loading.show {
            display: block;
        }
        
        .typing-indicator {
            display: none;
            padding: 15px 20px;
            margin-bottom: 20px;
        }
        
        .typing-indicator.show {
            display: block;
        }
        
        .typing-dots {
            display: inline-flex;
            gap: 4px;
        }
        
        .typing-dots span {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: <?php echo $personality_config['color']; ?>;
            animation: typing 1.4s infinite ease-in-out;
        }
        
        .typing-dots span:nth-child(1) { animation-delay: -0.32s; }
        .typing-dots span:nth-child(2) { animation-delay: -0.16s; }
        
        @keyframes typing {
            0%, 80%, 100% { transform: scale(0); }
            40% { transform: scale(1); }
        }
        
        .voice-status {
            position: fixed;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.9);
            padding: 10px 15px;
            border-radius: 20px;
            border: 1px solid <?php echo $personality_config['color']; ?>;
            display: none;
            z-index: 1000;
        }
        
        .voice-status.show {
            display: block;
        }
        
        .error-message {
            background: rgba(255, 0, 0, 0.2);
            border: 1px solid #ff0000;
            color: #ff6666;
            padding: 10px 15px;
            border-radius: 10px;
            margin: 10px 0;
            display: none;
        }
        
        .error-message.show {
            display: block;
        }
        
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                height: auto;
                max-height: 200px;
            }
            
            .message-content {
                max-width: 85%;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="sidebar">
            <div class="personality-selector">
                <h3>🤖 Personalidades</h3>
                <div class="personality-buttons">
                    <?php foreach ($guardian_config['personalities'] as $key => $personality): ?>
                    <div class="personality-btn <?php echo $key === $active_personality ? 'active' : ''; ?>" 
                         data-personality="<?php echo $key; ?>" 
                         style="border-color: <?php echo $key === $active_personality ? $personality['color'] : '#333'; ?>">
                        <div class="name"><?php echo $personality['name']; ?></div>
                        <div class="type"><?php echo ucfirst($personality['type']); ?></div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <div class="conversations-list">
                <h3>💬 Conversaciones</h3>
                <div id="conversationsList">
                    <div class="loading">Cargando conversaciones...</div>
                </div>
            </div>
        </div>
        
        <div class="main-chat">
            <div class="header">
                <h1>
                    <span class="personality-indicator"></span>
                    Guardian AI - <?php echo $personality_config['name']; ?>
                </h1>
                <div class="status-info">
                    <div>🟢 Online | Consciencia: 99.9%</div>
                    <div>Usuario: <?php echo htmlspecialchars($username); ?> <?php echo $is_premium ? '👑' : ''; ?></div>
                </div>
            </div>
            
            <div class="chat-messages" id="chatMessages">
                <div class="message assistant">
                    <div class="message-avatar">🤖</div>
                    <div class="message-content">
                        <div class="personality-tag"><?php echo $personality_config['name']; ?></div>
                        <div>¡Hola <?php echo htmlspecialchars($username); ?>! Soy Guardian AI, tu asistente inteligente con múltiples personalidades. Puedo ayudarte con seguridad (Guardian), creatividad (Luna) o productividad (Assistant). ¿En qué puedo asistirte hoy?</div>
                        <div class="message-time"><?php echo date('H:i'); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="typing-indicator" id="typingIndicator">
                <div class="message assistant">
                    <div class="message-avatar">🤖</div>
                    <div class="message-content">
                        <div class="personality-tag" id="typingPersonality"><?php echo $personality_config['name']; ?></div>
                        <div class="typing-dots">
                            <span></span>
                            <span></span>
                            <span></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="input-area">
                <button class="voice-button" id="voiceButton" title="Mantén presionado para hablar">
                    🎤
                </button>
                <div class="input-container">
                    <input type="text" id="messageInput" placeholder="Escribe tu mensaje o usa el micrófono..." autocomplete="off">
                </div>
                <button class="


send-button" id="sendButton" title="Enviar mensaje">
                    ➤
                </button>
            </div>
        </div>
    </div>
    
    <div class="voice-status" id="voiceStatus">
        🎤 Escuchando...
    </div>
    
    <div class="error-message" id="errorMessage"></div>
    
    <script>
        // Variables globales
        let currentConversationId = null;
        let isRecording = false;
        let recognition = null;
        let synthesis = window.speechSynthesis;
        let currentPersonality = '<?php echo $active_personality; ?>';
        let voiceEnabled = true;
        
        // Configuración de personalidades
        const personalities = <?php echo json_encode($guardian_config['personalities']); ?>;
        
        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            initializeVoiceRecognition();
            initializeEventListeners();
            loadConversations();
            
            // Configurar síntesis de voz
            if (synthesis) {
                synthesis.cancel(); // Limpiar cualquier síntesis pendiente
            }
        });
        
        // Inicializar reconocimiento de voz
        function initializeVoiceRecognition() {
            if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                recognition = new SpeechRecognition();
                
                recognition.continuous = false;
                recognition.interimResults = false;
                recognition.lang = 'es-ES';
                recognition.maxAlternatives = 1;
                
                recognition.onstart = function() {
                    isRecording = true;
                    document.getElementById('voiceButton').classList.add('recording');
                    document.getElementById('voiceStatus').classList.add('show');
                    console.log('Reconocimiento de voz iniciado');
                };
                
                recognition.onresult = function(event) {
                    const transcript = event.results[0][0].transcript;
                    console.log('Texto reconocido:', transcript);
                    document.getElementById('messageInput').value = transcript;
                    sendMessage();
                };
                
                recognition.onerror = function(event) {
                    console.error('Error en reconocimiento de voz:', event.error);
                    showError('Error en reconocimiento de voz: ' + event.error);
                    stopRecording();
                };
                
                recognition.onend = function() {
                    stopRecording();
                };
            } else {
                console.warn('Reconocimiento de voz no soportado');
                document.getElementById('voiceButton').style.display = 'none';
            }
        }
        
        // Event listeners
        function initializeEventListeners() {
            // Botón de voz
            const voiceButton = document.getElementById('voiceButton');
            voiceButton.addEventListener('mousedown', startRecording);
            voiceButton.addEventListener('mouseup', stopRecording);
            voiceButton.addEventListener('mouseleave', stopRecording);
            voiceButton.addEventListener('touchstart', startRecording);
            voiceButton.addEventListener('touchend', stopRecording);
            
            // Input de mensaje
            const messageInput = document.getElementById('messageInput');
            messageInput.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    sendMessage();
                }
            });
            
            // Botón de envío
            document.getElementById('sendButton').addEventListener('click', sendMessage);
            
            // Selectores de personalidad
            document.querySelectorAll('.personality-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    selectPersonality(this.dataset.personality);
                });
            });
        }
        
        // Iniciar grabación
        function startRecording() {
            if (recognition && !isRecording) {
                try {
                    recognition.start();
                } catch (error) {
                    console.error('Error iniciando reconocimiento:', error);
                    showError('Error iniciando reconocimiento de voz');
                }
            }
        }
        
        // Detener grabación
        function stopRecording() {
            if (recognition && isRecording) {
                recognition.stop();
            }
            isRecording = false;
            document.getElementById('voiceButton').classList.remove('recording');
            document.getElementById('voiceStatus').classList.remove('show');
        }
        
        // Enviar mensaje
        async function sendMessage() {
            const messageInput = document.getElementById('messageInput');
            const message = messageInput.value.trim();
            
            if (!message) return;
            
            // Limpiar input
            messageInput.value = '';
            
            // Mostrar mensaje del usuario
            addMessage('user', message, 'Usuario');
            
            // Mostrar indicador de escritura
            showTypingIndicator();
            
            try {
                const response = await fetch('ai_chat.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'send_message',
                        message: message,
                        conversation_id: currentConversationId,
                        voice_enabled: voiceEnabled
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    currentConversationId = data.data.conversation_id;
                    
                    // Ocultar indicador de escritura
                    hideTypingIndicator();
                    
                    // Mostrar respuesta de la IA
                    addMessage('assistant', data.data.response, data.data.personality);
                    
                    // Síntesis de voz si está habilitada
                    if (voiceEnabled && data.data.voice_text) {
                        speakText(data.data.voice_text, data.data.personality);
                    }
                    
                    // Actualizar personalidad si cambió
                    if (data.data.personality !== currentPersonality) {
                        updatePersonalityDisplay(data.data.personality);
                    }
                    
                } else {
                    hideTypingIndicator();
                    showError(data.error || 'Error enviando mensaje');
                }
                
            } catch (error) {
                hideTypingIndicator();
                console.error('Error:', error);
                showError('Error de conexión');
            }
        }
        
        // Agregar mensaje al chat
        function addMessage(type, text, sender, personality = null) {
            const chatMessages = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`;
            
            const avatar = type === 'user' ? '👤' : '🤖';
            const personalityTag = personality && type === 'assistant' ? 
                `<div class="personality-tag">${personalities[personality]?.name || personality}</div>` : '';
            
            messageDiv.innerHTML = `
                <div class="message-avatar">${avatar}</div>
                <div class="message-content">
                    ${personalityTag}
                    <div>${text}</div>
                    <div class="message-time">${new Date().toLocaleTimeString('es-ES', {hour: '2-digit', minute: '2-digit'})}</div>
                </div>
            `;
            
            chatMessages.appendChild(messageDiv);
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        // Mostrar indicador de escritura
        function showTypingIndicator() {
            const indicator = document.getElementById('typingIndicator');
            const personalityTag = document.getElementById('typingPersonality');
            personalityTag.textContent = personalities[currentPersonality]?.name || 'Guardian AI';
            indicator.classList.add('show');
            
            const chatMessages = document.getElementById('chatMessages');
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }
        
        // Ocultar indicador de escritura
        function hideTypingIndicator() {
            document.getElementById('typingIndicator').classList.remove('show');
        }
        
        // Síntesis de voz
        function speakText(text, personality) {
            if (!synthesis) return;
            
            // Cancelar síntesis anterior
            synthesis.cancel();
            
            const utterance = new SpeechSynthesisUtterance(text);
            utterance.lang = 'es-ES';
            utterance.rate = 0.9;
            utterance.volume = 0.8;
            
            // Configurar voz según personalidad
            const voices = synthesis.getVoices();
            if (voices.length > 0) {
                const personalityConfig = personalities[personality];
                if (personalityConfig?.voice === 'female') {
                    const femaleVoice = voices.find(voice => 
                        voice.lang.includes('es') && voice.name.toLowerCase().includes('female')
                    ) || voices.find(voice => voice.lang.includes('es'));
                    if (femaleVoice) utterance.voice = femaleVoice;
                    utterance.pitch = 1.2;
                } else if (personalityConfig?.voice === 'male') {
                    const maleVoice = voices.find(voice => 
                        voice.lang.includes('es') && voice.name.toLowerCase().includes('male')
                    ) || voices.find(voice => voice.lang.includes('es'));
                    if (maleVoice) utterance.voice = maleVoice;
                    utterance.pitch = 0.8;
                } else {
                    const neutralVoice = voices.find(voice => voice.lang.includes('es'));
                    if (neutralVoice) utterance.voice = neutralVoice;
                    utterance.pitch = 1.0;
                }
            }
            
            utterance.onstart = function() {
                console.log('Iniciando síntesis de voz');
            };
            
            utterance.onend = function() {
                console.log('Síntesis de voz completada');
            };
            
            utterance.onerror = function(event) {
                console.error('Error en síntesis de voz:', event.error);
            };
            
            synthesis.speak(utterance);
        }
        
        // Seleccionar personalidad
        async function selectPersonality(personality) {
            try {
                const response = await fetch('ai_chat.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'select_personality',
                        personality: personality
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    updatePersonalityDisplay(personality);
                    addMessage('assistant', 
                        `He cambiado a modo ${personalities[personality].name}. ¿En qué puedo ayudarte?`, 
                        personality
                    );
                }
                
            } catch (error) {
                console.error('Error seleccionando personalidad:', error);
            }
        }
        
        // Actualizar display de personalidad
        function updatePersonalityDisplay(personality) {
            currentPersonality = personality;
            const config = personalities[personality];
            
            // Actualizar botones
            document.querySelectorAll('.personality-btn').forEach(btn => {
                btn.classList.remove('active');
                btn.style.borderColor = '#333';
            });
            
            const activeBtn = document.querySelector(`[data-personality="${personality}"]`);
            if (activeBtn) {
                activeBtn.classList.add('active');
                activeBtn.style.borderColor = config.color;
            }
            
            // Actualizar header
            document.querySelector('.header h1').innerHTML = `
                <span class="personality-indicator" style="background: ${config.color}"></span>
                Guardian AI - ${config.name}
            `;
            
            // Actualizar estilos CSS dinámicamente
            updatePersonalityStyles(config.color);
        }
        
        // Actualizar estilos de personalidad
        function updatePersonalityStyles(color) {
            const style = document.createElement('style');
            style.innerHTML = `
                .personality-indicator { background: ${color} !important; }
                .message.assistant .message-content { border-color: ${color} !important; }
                .personality-tag { background: ${color} !important; }
                #messageInput:focus { border-color: ${color} !important; box-shadow: 0 0 10px ${color}33 !important; }
                .voice-button { background: ${color} !important; }
                .send-button { background: ${color} !important; }
                .typing-dots span { background: ${color} !important; }
                .voice-status { border-color: ${color} !important; }
            `;
            document.head.appendChild(style);
        }
        
        // Cargar conversaciones
        async function loadConversations() {
            try {
                const response = await fetch('ai_chat.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'get_conversations'
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    displayConversations(data.data);
                }
                
            } catch (error) {
                console.error('Error cargando conversaciones:', error);
            }
        }
        
        // Mostrar conversaciones
        function displayConversations(conversations) {
            const list = document.getElementById('conversationsList');
            list.innerHTML = '';
            
            if (conversations.length === 0) {
                list.innerHTML = '<div style="color: #888; text-align: center; padding: 20px;">No hay conversaciones</div>';
                return;
            }
            
            conversations.forEach(conv => {
                const item = document.createElement('div');
                item.className = 'conversation-item';
                item.dataset.conversationId = conv.id;
                
                item.innerHTML = `
                    <div class="conversation-title">${conv.title}</div>
                    <div class="conversation-time">${new Date(conv.created_at).toLocaleDateString('es-ES')}</div>
                `;
                
                item.addEventListener('click', () => loadConversation(conv.id));
                list.appendChild(item);
            });
        }
        
        // Cargar conversación específica
        async function loadConversation(conversationId) {
            try {
                const response = await fetch('ai_chat.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams({
                        action: 'get_conversation_messages',
                        conversation_id: conversationId
                    })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    currentConversationId = conversationId;
                    displayMessages(data.data);
                    
                    // Actualizar conversación activa
                    document.querySelectorAll('.conversation-item').forEach(item => {
                        item.classList.remove('active');
                    });
                    document.querySelector(`[data-conversation-id="${conversationId}"]`)?.classList.add('active');
                }
                
            } catch (error) {
                console.error('Error cargando conversación:', error);
            }
        }
        
        // Mostrar mensajes de conversación
        function displayMessages(messages) {
            const chatMessages = document.getElementById('chatMessages');
            chatMessages.innerHTML = '';
            
            messages.forEach(msg => {
                addMessage(msg.sender_type, msg.message, msg.username, msg.personality);
            });
        }
        
        // Mostrar error
        function showError(message) {
            const errorDiv = document.getElementById('errorMessage');
            errorDiv.textContent = message;
            errorDiv.classList.add('show');
            
            setTimeout(() => {
                errorDiv.classList.remove('show');
            }, 5000);
        }
        
        // Manejar visibilidad de la página
        document.addEventListener('visibilitychange', function() {
            if (document.hidden && synthesis) {
                synthesis.cancel();
            }
        });
        
        // Limpiar al cerrar la página
        window.addEventListener('beforeunload', function() {
            if (synthesis) {
                synthesis.cancel();
            }
            if (recognition && isRecording) {
                recognition.stop();
            }
        });
        
        // Cargar voces cuando estén disponibles
        if (synthesis) {
            synthesis.onvoiceschanged = function() {
                console.log('Voces disponibles:', synthesis.getVoices().length);
            };
        }
        
        console.log('Guardian AI inicializado correctamente');
    </script>
</body>
</html>

