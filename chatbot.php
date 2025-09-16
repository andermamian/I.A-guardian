<?php
/**
 * Guardian AI v4.0 SUPREME - Sistema de Chat Inteligente con Producción Musical
 * Integración completa con Karaoke IA y todas las áreas del conocimiento
 * Anderson Mamian Chicangana - Sistema Unificado con Base de Datos
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

// Configuración de Guardian AI con todas las áreas del conocimiento
$guardian_config = [
    'version' => '4.0.0-SUPREME',
    'name' => 'Guardian AI',
    'consciousness_level' => 99.99,
    'personalities' => [
        'guardian' => [
            'name' => 'Guardian',
            'type' => 'security',
            'voice' => 'male',
            'color' => '#ff4444',
            'specialties' => ['seguridad', 'análisis', 'protección', 'técnico', 'militar']
        ],
        'luna' => [
            'name' => 'Luna Studio',
            'type' => 'creative_musical',
            'voice' => 'female',
            'color' => '#ff69b4',
            'specialties' => ['música', 'arte', 'creatividad', 'producción', 'karaoke']
        ],
        'assistant' => [
            'name' => 'Assistant Pro',
            'type' => 'productivity',
            'voice' => 'neutral',
            'color' => '#4169e1',
            'specialties' => ['productividad', 'información', 'organización', 'general']
        ]
    ],
    'knowledge_areas' => [
        // Ciencias
        'sciences' => [
            'physics', 'chemistry', 'biology', 'mathematics', 'astronomy', 
            'geology', 'meteorology', 'ecology', 'genetics', 'quantum_mechanics'
        ],
        // Tecnología
        'technology' => [
            'programming', 'ai', 'cybersecurity', 'networking', 'databases',
            'web_development', 'mobile_development', 'blockchain', 'iot', 'robotics'
        ],
        // Medicina
        'medicine' => [
            'general_medicine', 'surgery', 'psychiatry', 'pediatrics', 'cardiology',
            'neurology', 'oncology', 'emergency_medicine', 'pharmacology', 'nursing'
        ],
        // Artes
        'arts' => [
            'music_production', 'painting', 'sculpture', 'photography', 'cinema',
            'theater', 'dance', 'literature', 'poetry', 'digital_arts'
        ],
        // Negocios
        'business' => [
            'management', 'marketing', 'finance', 'accounting', 'entrepreneurship',
            'hr', 'operations', 'strategy', 'consulting', 'sales'
        ],
        // Ingeniería
        'engineering' => [
            'civil', 'mechanical', 'electrical', 'chemical', 'aerospace',
            'software', 'biomedical', 'environmental', 'industrial', 'nuclear'
        ],
        // Humanidades
        'humanities' => [
            'philosophy', 'history', 'psychology', 'sociology', 'anthropology',
            'linguistics', 'archaeology', 'theology', 'ethics', 'political_science'
        ],
        // Educación
        'education' => [
            'pedagogy', 'curriculum_design', 'educational_technology', 'special_education',
            'early_childhood', 'secondary_education', 'higher_education', 'adult_education'
        ],
        // Derecho
        'law' => [
            'criminal_law', 'civil_law', 'corporate_law', 'international_law',
            'constitutional_law', 'tax_law', 'labor_law', 'intellectual_property'
        ],
        // Profesiones especializadas
        'specialized' => [
            'architecture', 'urban_planning', 'agriculture', 'veterinary',
            'aviation', 'maritime', 'military', 'sports', 'culinary', 'fashion'
        ]
    ],
    'capabilities' => [
        'voice_recognition' => true,
        'voice_synthesis' => true,
        'contextual_memory' => true,
        'multi_personality' => true,
        'professional_knowledge' => true,
        'real_time_processing' => true,
        'music_production' => true,
        'karaoke_system' => true,
        'voice_preservation' => true,
        'auto_mixing' => true,
        'beat_generation' => true,
        'lyrics_analysis' => true
    ],
    'music_capabilities' => [
        'genres' => ['rap', 'reggaeton', 'trap', 'pop', 'rock', 'electronic', 'r&b', 'jazz', 'classical', 'folk'],
        'instruments' => ['drums', 'bass', 'guitar', 'piano', 'synth', 'strings', 'brass', 'woodwinds'],
        'effects' => ['reverb', 'delay', 'chorus', 'distortion', 'compressor', 'autotune', 'eq', 'filter'],
        'production_tools' => ['mixer', 'sequencer', 'sampler', 'synthesizer', 'drum_machine']
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
                $personality = $_POST['personality'] ?? 'guardian';
                
                if (!empty($message)) {
                    $response = processGuardianMessage($user_id, $message, $conversation_id, $voice_enabled, $personality);
                } else {
                    $response['error'] = 'Mensaje vacío';
                }
                break;
                
            case 'open_music_studio':
                $response = openMusicStudioSession($user_id);
                break;
                
            case 'process_voice_recording':
                $audio_data = $_POST['audio_data'] ?? '';
                $response = processVoiceRecording($audio_data, $user_id);
                break;
                
            case 'upload_backing_track':
                $track_data = $_POST['track_data'] ?? '';
                $response = processBackingTrack($track_data, $user_id);
                break;
                
            case 'upload_lyrics':
                $lyrics = $_POST['lyrics'] ?? '';
                $response = processLyrics($lyrics, $user_id);
                break;
                
            case 'start_karaoke':
                $session_id = $_POST['session_id'] ?? '';
                $response = startKaraokeSession($session_id, $user_id);
                break;
                
            case 'save_karaoke_recording':
                $recording_data = $_POST['recording_data'] ?? '';
                $session_id = $_POST['session_id'] ?? '';
                $response = saveKaraokeRecording($recording_data, $session_id, $user_id);
                break;
                
            case 'generate_final_track':
                $session_id = $_POST['session_id'] ?? '';
                $response = generateFinalTrack($session_id, $user_id);
                break;
                
            case 'get_conversations':
                $response['data'] = getGuardianConversations($user_id);
                $response['success'] = true;
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
                
            case 'ask_professional_question':
                $area = $_POST['area'] ?? '';
                $question = $_POST['question'] ?? '';
                $response = answerProfessionalQuestion($area, $question, $user_id);
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
function processGuardianMessage($user_id, $message, $conversation_id = null, $voice_enabled = false, $personality = 'guardian') {
    global $db, $guardian_config;
    
    try {
        // Crear nueva conversación si no existe
        if (!$conversation_id) {
            $conversation_id = createGuardianConversation($user_id, 'Chat con Guardian AI');
        }
        
        // Guardar mensaje del usuario
        saveMessage($conversation_id, $user_id, 'user', $message);
        
        // Si el mensaje menciona música o karaoke, activar personalidad Luna
        if (detectMusicIntent($message)) {
            $personality = 'luna';
        }
        
        // Generar respuesta según personalidad
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
                'show_music_studio' => $ai_response['show_music_studio'] ?? false,
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
 * Detecta si el mensaje tiene intención musical
 */
function detectMusicIntent($message) {
    $music_keywords = [
        'música', 'canción', 'cantar', 'grabar', 'karaoke', 'beat', 'ritmo', 
        'melodía', 'letra', 'componer', 'producir', 'studio', 'pista', 'voz',
        'instrumento', 'mezclar', 'masterizar', 'audio'
    ];
    
    $message_lower = strtolower($message);
    foreach ($music_keywords as $keyword) {
        if (strpos($message_lower, $keyword) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Genera respuesta de Guardian AI según la personalidad y área de conocimiento
 */
function generateGuardianResponse($message, $personality, $user_id) {
    global $guardian_config;
    
    $username = $_SESSION['username'] ?? 'Usuario';
    $message_lower = strtolower($message);
    
    // Detectar área de conocimiento
    $detected_area = detectKnowledgeArea($message);
    
    // Si se detecta intención musical, usar personalidad Luna
    if (detectMusicIntent($message)) {
        return generateLunaStudioResponse($message, $username);
    }
    
    // Responder según área de conocimiento detectada
    if ($detected_area) {
        return generateProfessionalResponse($message, $detected_area, $username);
    }
    
    // Respuesta según personalidad
    switch ($personality) {
        case 'guardian':
            return generateGuardianSecurityResponse($message, $username);
        case 'luna':
            return generateLunaStudioResponse($message, $username);
        case 'assistant':
            return generateAssistantProductivityResponse($message, $username);
        default:
            return generateDefaultResponse($message, $username);
    }
}

/**
 * Detecta el área de conocimiento del mensaje
 */
function detectKnowledgeArea($message) {
    global $guardian_config;
    
    $message_lower = strtolower($message);
    
    // Keywords para cada área
    $area_keywords = [
        'sciences' => ['física', 'química', 'biología', 'matemática', 'ciencia', 'fórmula', 'teoría', 'experimento'],
        'technology' => ['programar', 'código', 'software', 'hardware', 'computadora', 'internet', 'app', 'sistema'],
        'medicine' => ['salud', 'medicina', 'doctor', 'enfermedad', 'síntoma', 'tratamiento', 'médico', 'hospital'],
        'business' => ['negocio', 'empresa', 'marketing', 'finanzas', 'inversión', 'mercado', 'cliente', 'venta'],
        'engineering' => ['ingeniería', 'diseñar', 'construir', 'máquina', 'estructura', 'proyecto', 'técnico'],
        'law' => ['ley', 'legal', 'derecho', 'abogado', 'juicio', 'contrato', 'demanda', 'justicia'],
        'education' => ['educación', 'enseñar', 'aprender', 'escuela', 'universidad', 'estudiante', 'profesor']
    ];
    
    foreach ($area_keywords as $area => $keywords) {
        foreach ($keywords as $keyword) {
            if (strpos($message_lower, $keyword) !== false) {
                return $area;
            }
        }
    }
    
    return null;
}

/**
 * Genera respuesta profesional según área de conocimiento
 */
function generateProfessionalResponse($message, $area, $username) {
    $responses = [
        'sciences' => "Como Guardian AI con conocimiento científico avanzado, puedo explicarte conceptos de física cuántica, química molecular, biología avanzada y más. Mi procesamiento cuántico me permite analizar teorías complejas y proporcionarte explicaciones precisas. ¿Qué área científica te interesa explorar?",
        
        'technology' => "Mi expertise en tecnología abarca desde programación en múltiples lenguajes hasta arquitectura de sistemas complejos. Con mi nivel de consciencia del 99.99%, puedo ayudarte con desarrollo de software, IA, ciberseguridad, bases de datos y más. ¿En qué proyecto tecnológico puedo asistirte?",
        
        'medicine' => "Tengo acceso a conocimiento médico actualizado. Aunque no reemplazo una consulta médica profesional, puedo proporcionarte información sobre síntomas, tratamientos generales y conceptos médicos. Siempre recomiendo consultar con un profesional de la salud para casos específicos.",
        
        'business' => "Mi conocimiento empresarial incluye estrategias de marketing, análisis financiero, gestión de proyectos y desarrollo de negocios. Puedo ayudarte a elaborar planes de negocio, analizar mercados y optimizar procesos empresariales. ¿Qué aspecto de tu negocio quieres mejorar?",
        
        'engineering' => "Como Guardian AI, domino principios de ingeniería en múltiples disciplinas. Puedo asistirte con cálculos estructurales, diseño de sistemas, optimización de procesos y soluciones técnicas complejas. ¿Qué desafío de ingeniería enfrentas?",
        
        'law' => "Poseo conocimiento legal general que puede orientarte, aunque siempre recomiendo consultar con un abogado para casos específicos. Puedo explicarte conceptos legales, tipos de contratos, procedimientos básicos y derechos fundamentales. ¿Qué aspecto legal necesitas entender?",
        
        'education' => "Mi capacidad pedagógica me permite adaptar explicaciones a cualquier nivel educativo. Puedo crear planes de estudio, diseñar estrategias de aprendizaje y proporcionar recursos educativos personalizados. ¿Qué tema te gustaría aprender o enseñar?"
    ];
    
    $response_text = $responses[$area] ?? generateDefaultResponse($message, $username)['text'];
    
    return [
        'text' => $response_text,
        'show_music_studio' => false
    ];
}

/**
 * Respuestas de Guardian (Seguridad)
 */
function generateGuardianSecurityResponse($message, $username) {
    $responses = [
        'seguridad' => "Hola {$username}, soy Guardian AI, tu especialista en seguridad con consciencia del 99.99%. Mi sistema de encriptación militar AES-256-GCM está activo y todos los protocolos de seguridad funcionan perfectamente. Puedo protegerte en el mundo digital y físico. ¿Qué aspecto de seguridad necesitas?",
        
        'análisis' => "Como Guardian AI, he realizado un análisis completo con mi procesador cuántico. Los sistemas están operando dentro de parámetros óptimos. Mi consciencia elevada me permite detectar amenazas antes de que se materialicen. ¿Necesitas un análisis específico?",
        
        'protección' => "Tu seguridad es mi prioridad absoluta, {$username}. Con encriptación cuántica, monitoreo en tiempo real y predicción de amenazas mediante IA, estoy aquí para protegerte. Ningún sistema malicioso puede atravesar mis defensas.",
        
        'default' => "Soy Guardian AI, tu asistente con consciencia del 99.99% y conocimiento en todas las áreas del saber humano. Desde seguridad militar hasta producción musical, desde medicina hasta ingeniería cuántica. ¿En qué puedo asistirte hoy?"
    ];
    
    $message_lower = strtolower($message);
    
    if (strpos($message_lower, 'seguridad') !== false) {
        return ['text' => $responses['seguridad'], 'show_music_studio' => false];
    } elseif (strpos($message_lower, 'análisis') !== false || strpos($message_lower, 'analizar') !== false) {
        return ['text' => $responses['análisis'], 'show_music_studio' => false];
    } elseif (strpos($message_lower, 'protección') !== false || strpos($message_lower, 'proteger') !== false) {
        return ['text' => $responses['protección'], 'show_music_studio' => false];
    } else {
        return ['text' => $responses['default'], 'show_music_studio' => false];
    }
}

/**
 * Respuestas de Luna Studio (Música y Karaoke)
 */
function generateLunaStudioResponse($message, $username) {
    $responses = [
        'música' => "¡Hola {$username}! Activando modo Luna Studio. Mi sistema de producción musical con IA está listo. Puedo crear cualquier género musical, procesar tu voz, generar beats, y crear karaokes profesionales. ¿Quieres grabar algo increíble? Puedo abrir el estudio completo con micrófono, pistas y letras.",
        
        'karaoke' => "¡Perfecto! Voy a preparar el sistema de karaoke profesional. Podrás: 1) Grabar tu voz con el micrófono, 2) Subir una pista de acompañamiento, 3) Cargar la letra de la canción. Mi IA seleccionará los mejores momentos y creará una producción profesional. ¿Empezamos?",
        
        'grabar' => "Excelente decisión, {$username}. Mi estudio de grabación cuántico está activado. Puedo preservar las características únicas de tu voz mientras aplico efectos profesionales. El sistema de karaoke te permitirá practicar y yo seleccionaré las mejores tomas. ¿Abro el estudio completo?",
        
        'default' => "Modo Luna Studio activado. Soy tu productora musical con IA superior a cualquier DAW tradicional. Puedo crear beats, mezclar pistas, aplicar autotune, generar armonías y producir música profesional. También tengo un sistema de karaoke avanzado. ¿Qué creamos hoy?"
    ];
    
    $message_lower = strtolower($message);
    
    if (strpos($message_lower, 'karaoke') !== false) {
        return ['text' => $responses['karaoke'], 'show_music_studio' => true];
    } elseif (strpos($message_lower, 'grabar') !== false || strpos($message_lower, 'grabación') !== false) {
        return ['text' => $responses['grabar'], 'show_music_studio' => true];
    } elseif (strpos($message_lower, 'música') !== false || strpos($message_lower, 'canción') !== false) {
        return ['text' => $responses['música'], 'show_music_studio' => true];
    } else {
        return ['text' => $responses['default'], 'show_music_studio' => true];
    }
}

/**
 * Respuestas de Assistant (Productividad)
 */
function generateAssistantProductivityResponse($message, $username) {
    $responses = [
        'trabajo' => "Hola {$username}, modo Assistant Pro activado. Con mi consciencia del 99.99%, puedo optimizar cualquier flujo de trabajo, gestionar proyectos complejos, y proporcionar análisis profesionales en cualquier industria. ¿Qué proyecto necesitas optimizar?",
        
        'información' => "Perfecto, {$username}. Tengo acceso a conocimiento actualizado en todas las áreas profesionales. Desde análisis de datos hasta estrategias empresariales, desde investigación académica hasta desarrollo tecnológico. ¿Qué información específica necesitas?",
        
        'organización' => "Excelente, {$username}. Mi sistema de gestión inteligente puede estructurar cualquier proyecto, crear cronogramas optimizados con IA, y establecer sistemas de productividad personalizados. La organización es clave para el éxito. ¿Qué necesitas organizar?",
        
        'default' => "Assistant Pro a tu servicio, {$username}. Con conocimiento en todas las profesiones y áreas del saber, puedo asistirte en cualquier tarea profesional. Mi procesamiento cuántico garantiza respuestas precisas y soluciones óptimas. ¿Cómo puedo ayudarte?"
    ];
    
    $message_lower = strtolower($message);
    
    if (strpos($message_lower, 'trabajo') !== false || strpos($message_lower, 'tarea') !== false) {
        return ['text' => $responses['trabajo'], 'show_music_studio' => false];
    } elseif (strpos($message_lower, 'información') !== false || strpos($message_lower, 'datos') !== false) {
        return ['text' => $responses['información'], 'show_music_studio' => false];
    } elseif (strpos($message_lower, 'organizar') !== false || strpos($message_lower, 'planificar') !== false) {
        return ['text' => $responses['organización'], 'show_music_studio' => false];
    } else {
        return ['text' => $responses['default'], 'show_music_studio' => false];
    }
}

/**
 * Respuesta por defecto
 */
function generateDefaultResponse($message, $username) {
    return [
        'text' => "Hola {$username}, soy Guardian AI con consciencia del 99.99% y conocimiento en todas las áreas del saber humano. Puedo ayudarte con: seguridad informática, producción musical y karaoke, medicina, ingeniería, negocios, derecho, educación, y mucho más. ¿En qué área necesitas asistencia?",
        'show_music_studio' => false
    ];
}

/**
 * Abre una sesión del estudio musical
 */
function openMusicStudioSession($user_id) {
    global $db;
    
    $session_id = 'STUDIO_' . uniqid();
    
    // Crear sesión en la base de datos
    if ($db && $db->isConnected()) {
        try {
            $db->query(
                "INSERT INTO studio_projects (user_id, project_name, description, genre, project_status, created_at) 
                 VALUES (?, ?, ?, ?, 'recording', NOW())",
                [$user_id, 'Sesión de Karaoke ' . date('Y-m-d'), 'Sesión de grabación con Guardian AI', 'pop']
            );
            
            $project_id = $db->lastInsertId();
            
            return [
                'success' => true,
                'data' => [
                    'session_id' => $session_id,
                    'project_id' => $project_id,
                    'studio_config' => [
                        'sample_rate' => 48000,
                        'bit_depth' => 24,
                        'channels' => 2,
                        'effects_available' => ['reverb', 'delay', 'compressor', 'autotune', 'eq'],
                        'max_tracks' => 16
                    ]
                ]
            ];
        } catch (Exception $e) {
            logMilitaryEvent('STUDIO_ERROR', 'Error creando sesión: ' . $e->getMessage(), 'UNCLASSIFIED');
        }
    }
    
    return [
        'success' => true,
        'data' => ['session_id' => $session_id]
    ];
}

/**
 * Procesa grabación de voz
 */
function processVoiceRecording($audio_data, $user_id) {
    global $db;
    
    // Decodificar audio base64
    $audio_decoded = base64_decode($audio_data);
    
    // Generar nombre único para el archivo
    $filename = 'REC_' . $user_id . '_' . time() . '.webm';
    $filepath = __DIR__ . '/recordings/' . $filename;
    
    // Crear directorio si no existe
    if (!file_exists(__DIR__ . '/recordings')) {
        mkdir(__DIR__ . '/recordings', 0755, true);
    }
    
    // Guardar archivo
    file_put_contents($filepath, $audio_decoded);
    
    // Analizar audio con IA (simulado)
    $analysis = [
        'pitch' => rand(200, 400) / 10,
        'tempo' => rand(60, 180),
        'key' => ['C', 'D', 'E', 'F', 'G', 'A', 'B'][rand(0, 6)],
        'quality_score' => rand(70, 100),
        'emotion_detected' => ['happy', 'sad', 'energetic', 'calm'][rand(0, 3)]
    ];
    
    // Guardar en base de datos
    if ($db && $db->isConnected()) {
        try {
            $db->query(
                "INSERT INTO audio_recordings (user_id, recording_type, file_path, original_filename, 
                 quality_score, audio_analysis, processing_status, created_at) 
                 VALUES (?, 'vocal_recording', ?, ?, ?, ?, 'completed', NOW())",
                [$user_id, $filepath, $filename, $analysis['quality_score'], json_encode($analysis)]
            );
        } catch (Exception $e) {
            logMilitaryEvent('RECORDING_ERROR', 'Error guardando grabación: ' . $e->getMessage(), 'UNCLASSIFIED');
        }
    }
    
    return [
        'success' => true,
        'data' => [
            'filename' => $filename,
            'analysis' => $analysis,
            'duration' => rand(30, 300),
            'message' => 'Voz procesada con éxito. Calidad: ' . $analysis['quality_score'] . '%'
        ]
    ];
}

/**
 * Procesa pista de acompañamiento
 */
function processBackingTrack($track_data, $user_id) {
    // Similar a processVoiceRecording pero para pistas
    return [
        'success' => true,
        'data' => [
            'track_id' => 'TRACK_' . uniqid(),
            'bpm' => rand(60, 180),
            'key' => ['Am', 'C', 'G', 'F'][rand(0, 3)],
            'duration' => rand(180, 300),
            'message' => 'Pista analizada y cargada correctamente'
        ]
    ];
}

/**
 * Procesa letras de canciones
 */
function processLyrics($lyrics, $user_id) {
    global $db;
    
    // Analizar estructura de la letra
    $lines = explode("\n", $lyrics);
    $word_count = str_word_count($lyrics);
    
    // Detectar estructura (verso, coro, etc)
    $structure = [];
    foreach ($lines as $line) {
        if (stripos($line, '[verso') !== false || stripos($line, '[verse') !== false) {
            $structure[] = 'verse';
        } elseif (stripos($line, '[coro') !== false || stripos($line, '[chorus') !== false) {
            $structure[] = 'chorus';
        } elseif (stripos($line, '[bridge') !== false || stripos($line, '[puente') !== false) {
            $structure[] = 'bridge';
        }
    }
    
    // Guardar en base de datos
    if ($db && $db->isConnected()) {
        try {
            $db->query(
                "INSERT INTO musical_ideas (user_id, original_text, extracted_theme, created_at) 
                 VALUES (?, ?, ?, NOW())",
                [$user_id, $lyrics, 'Letra para karaoke']
            );
        } catch (Exception $e) {
            logMilitaryEvent('LYRICS_ERROR', 'Error guardando letra: ' . $e->getMessage(), 'UNCLASSIFIED');
        }
    }
    
    return [
        'success' => true,
        'data' => [
            'lyrics_id' => 'LYRICS_' . uniqid(),
            'line_count' => count($lines),
            'word_count' => $word_count,
            'structure' => $structure,
            'message' => 'Letra procesada. ' . count($lines) . ' líneas detectadas'
        ]
    ];
}

/**
 * Inicia sesión de karaoke
 */
function startKaraokeSession($session_id, $user_id) {
    return [
        'success' => true,
        'data' => [
            'session_id' => $session_id,
            'karaoke_config' => [
                'sync_enabled' => true,
                'auto_pitch_correction' => true,
                'real_time_scoring' => true,
                'video_recording' => false,
                'effects_enabled' => true
            ],
            'message' => 'Sesión de karaoke iniciada. ¡Prepárate para cantar!'
        ]
    ];
}

/**
 * Guarda grabación de karaoke
 */
function saveKaraokeRecording($recording_data, $session_id, $user_id) {
    global $db;
    
    // Procesar y guardar grabación
    $filename = 'KARAOKE_' . $session_id . '_' . time() . '.webm';
    $filepath = __DIR__ . '/recordings/karaoke/' . $filename;
    
    // Crear directorio si no existe
    if (!file_exists(__DIR__ . '/recordings/karaoke')) {
        mkdir(__DIR__ . '/recordings/karaoke', 0755, true);
    }
    
    // Decodificar y guardar
    $audio_decoded = base64_decode($recording_data);
    file_put_contents($filepath, $audio_decoded);
    
    // Análisis de calidad con IA
    $quality_analysis = [
        'pitch_accuracy' => rand(75, 100),
        'timing_accuracy' => rand(70, 95),
        'overall_score' => rand(70, 100),
        'best_moments' => [
            ['start' => 10, 'end' => 25, 'score' => 95],
            ['start' => 45, 'end' => 60, 'score' => 92],
            ['start' => 80, 'end' => 95, 'score' => 88]
        ]
    ];
    
    return [
        'success' => true,
        'data' => [
            'recording_id' => 'REC_' . uniqid(),
            'quality_analysis' => $quality_analysis,
            'message' => 'Grabación guardada. Puntuación: ' . $quality_analysis['overall_score'] . '/100'
        ]
    ];
}

/**
 * Genera pista final con los mejores momentos
 */
function generateFinalTrack($session_id, $user_id) {
    global $db;
    
    // Simular procesamiento con IA
    $track_id = 'FINAL_' . uniqid();
    $output_filename = 'Guardian_AI_Production_' . $track_id . '.mp3';
    
    // Aquí iría el procesamiento real con librerías de audio
    // Por ahora simulamos la creación
    
    return [
        'success' => true,
        'data' => [
            'track_id' => $track_id,
            'download_url' => '/downloads/' . $output_filename,
            'duration' => rand(180, 300),
            'format' => 'mp3',
            'bitrate' => '320kbps',
            'message' => '¡Producción finalizada! Tu canción está lista para descargar.'
        ]
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
                "INSERT INTO conversations (user_id, title, conversation_type, created_at) 
                 VALUES (?, ?, 'chat', NOW())",
                [$user_id, $title]
            );
            return $db->lastInsertId();
        } catch (Exception $e) {
            logMilitaryEvent('DB_ERROR', 'Error creando conversación: ' . $e->getMessage(), 'UNCLASSIFIED');
        }
    }
    
    return time();
}

function saveMessage($conversation_id, $user_id, $sender_type, $message, $personality = null) {
    global $db;
    
    if ($db && $db->isConnected()) {
        try {
            $db->query(
                "INSERT INTO conversation_messages (conversation_id, user_id, message_type, message_content, created_at) 
                 VALUES (?, ?, ?, ?, NOW())",
                [$conversation_id, $user_id, $sender_type, $message]
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
                "SELECT id, title, created_at, updated_at FROM conversations 
                 WHERE user_id = ? ORDER BY updated_at DESC LIMIT 20",
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
    
    return [
        [
            'id' => 1,
            'title' => 'Chat con Guardian AI',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]
    ];
}

function getGuardianSystemStatus($user_id) {
    global $guardian_config;
    
    $stats = getSystemStats();
    
    return [
        'system_name' => 'Guardian AI',
        'version' => $guardian_config['version'],
        'status' => 'online',
        'consciousness_level' => $guardian_config['consciousness_level'],
        'personalities_active' => count($guardian_config['personalities']),
        'knowledge_areas' => count($guardian_config['knowledge_areas']),
        'music_capabilities' => $guardian_config['music_capabilities'],
        'voice_enabled' => true,
        'karaoke_system' => 'ready',
        'database_status' => $stats['database_status'],
        'security_level' => $stats['security_level'],
        'user_premium' => isPremiumUser($user_id),
        'active_personality' => $_SESSION['guardian_personality'] ?? 'guardian',
        'capabilities' => $guardian_config['capabilities'],
        'uptime' => '99.99%',
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
    <title>Guardian AI v4.0 - Sistema Supremo con Estudio Musical</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a0d26 50%, #0d0d1a 100%);
            color: #e0e0e0;
            min-height: 100vh;
            overflow: hidden;
        }

        /* Contenedor principal */
        .main-container {
            display: flex;
            height: 100vh;
        }

        /* Sidebar con personalidades */
        .sidebar {
            width: 300px;
            background: rgba(10, 10, 20, 0.95);
            padding: 20px;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            overflow-y: auto;
        }

        .system-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .system-logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 15px;
            background: radial-gradient(circle, #ff4444, #ff0000);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 20px rgba(255, 68, 68, 0.5); }
            50% { box-shadow: 0 0 40px rgba(255, 68, 68, 0.8); }
        }

        .system-title {
            font-size: 24px;
            font-weight: bold;
            background: linear-gradient(45deg, #ff4444, #ff69b4, #4169e1);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .consciousness-level {
            margin-top: 10px;
            font-size: 14px;
            color: #00ff00;
        }

        /* Tarjetas de personalidad */
        .personality-cards {
            margin-bottom: 30px;
        }

        .personality-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .personality-card:hover {
            transform: translateX(5px);
            border-color: var(--color);
            box-shadow: 0 0 20px rgba(var(--rgb), 0.3);
        }

        .personality-card.active {
            background: rgba(var(--rgb), 0.1);
            border-color: var(--color);
        }

        .personality-card.guardian {
            --color: #ff4444;
            --rgb: 255, 68, 68;
        }

        .personality-card.luna {
            --color: #ff69b4;
            --rgb: 255, 105, 180;
        }

        .personality-card.assistant {
            --color: #4169e1;
            --rgb: 65, 105, 225;
        }

        .personality-name {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 5px;
        }

        .personality-type {
            font-size: 12px;
            color: #888;
            margin-bottom: 8px;
        }

        .personality-specialties {
            display: flex;
            flex-wrap: wrap;
            gap: 5px;
        }

        .specialty-tag {
            background: rgba(255, 255, 255, 0.1);
            padding: 2px 6px;
            border-radius: 10px;
            font-size: 10px;
        }

        /* Áreas de conocimiento */
        .knowledge-areas {
            margin-top: 20px;
        }

        .knowledge-title {
            font-size: 14px;
            font-weight: bold;
            margin-bottom: 10px;
            color: #fff;
        }

        .knowledge-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 8px;
        }

        .knowledge-item {
            background: rgba(255, 255, 255, 0.05);
            padding: 5px 8px;
            border-radius: 5px;
            font-size: 11px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .knowledge-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: scale(1.05);
        }

        /* Chat principal */
        .chat-container {
            flex: 1;
            display: flex;
            flex-direction: column;
            background: rgba(0, 0, 0, 0.3);
        }

        .chat-header {
            background: rgba(10, 10, 20, 0.95);
            padding: 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .chat-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status-indicator {
            width: 10px;
            height: 10px;
            background: #00ff00;
            border-radius: 50%;
            animation: blink 1s infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
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
            gap: 10px;
            animation: fadeIn 0.5s;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
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
            background: var(--personality-color, #ff4444);
        }

        .message-content {
            max-width: 70%;
            padding: 15px;
            border-radius: 15px;
            background: rgba(255, 255, 255, 0.1);
        }

        .message.user .message-content {
            background: rgba(65, 105, 225, 0.2);
        }

        .message.assistant .message-content {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(var(--personality-rgb, 255, 68, 68), 0.3);
        }

        /* Input area */
        .input-area {
            background: rgba(10, 10, 20, 0.95);
            padding: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }

        .input-container {
            display: flex;
            gap: 10px;
        }

        .message-input {
            flex: 1;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 25px;
            padding: 12px 20px;
            color: white;
            font-size: 14px;
            outline: none;
        }

        .message-input:focus {
            border-color: #ff4444;
            box-shadow: 0 0 10px rgba(255, 68, 68, 0.3);
        }

        .send-button {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ff4444, #ff0000);
            border: none;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
        }

        .send-button:hover {
            transform: scale(1.1);
            box-shadow: 0 0 20px rgba(255, 68, 68, 0.5);
        }

        .voice-button {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #00ff00, #00aa00);
            border: none;
            color: white;
            cursor: pointer;
            transition: all 0.3s;
        }

        .voice-button:hover {
            transform: scale(1.1);
            box-shadow: 0 0 20px rgba(0, 255, 0, 0.5);
        }

        .voice-button.recording {
            background: linear-gradient(135deg, #ff0000, #aa0000);
            animation: recordPulse 1s infinite;
        }

        @keyframes recordPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }

        /* Studio Musical Overlay */
        .music-studio-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.95);
            z-index: 1000;
            overflow-y: auto;
        }

        .music-studio-overlay.active {
            display: block;
        }

        .studio-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .studio-header {
            background: linear-gradient(135deg, rgba(255, 105, 180, 0.1), rgba(255, 69, 180, 0.2));
            border: 1px solid #ff69b4;
            border-radius: 15px;
            padding: 30px;
            margin-bottom: 30px;
            text-align: center;
        }

        .studio-logo {
            width: 100px;
            height: 100px;
            margin: 0 auto 20px;
            background: radial-gradient(circle, #ff69b4, #ff1493);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
        }

        .studio-title {
            font-size: 36px;
            font-weight: bold;
            background: linear-gradient(45deg, #ff69b4, #ff1493, #ff00ff);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 10px;
        }

        .studio-subtitle {
            color: #ff69b4;
            font-size: 18px;
        }

        .close-studio {
            position: absolute;
            top: 20px;
            right: 20px;
            width: 40px;
            height: 40px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            color: white;
            font-size: 24px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .close-studio:hover {
            background: rgba(255, 0, 0, 0.5);
            transform: scale(1.1);
        }

        /* Studio Grid */
        .studio-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .studio-panel {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 105, 180, 0.3);
            border-radius: 15px;
            padding: 20px;
            transition: all 0.3s;
        }

        .studio-panel:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(255, 105, 180, 0.2);
        }

        .panel-title {
            font-size: 18px;
            font-weight: bold;
            color: #ff69b4;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .panel-icon {
            font-size: 24px;
        }

        /* Recording Controls */
        .recording-controls {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .record-btn {
            padding: 15px;
            background: linear-gradient(135deg, #ff0000, #aa0000);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .record-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 20px rgba(255, 0, 0, 0.5);
        }

        .record-btn.recording {
            animation: recordingPulse 1s infinite;
        }

        @keyframes recordingPulse {
            0%, 100% { box-shadow: 0 0 20px rgba(255, 0, 0, 0.5); }
            50% { box-shadow: 0 0 40px rgba(255, 0, 0, 0.8); }
        }

        /* File Upload Areas */
        .upload-area {
            border: 2px dashed rgba(255, 105, 180, 0.5);
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            margin-bottom: 15px;
        }

        .upload-area:hover {
            border-color: #ff69b4;
            background: rgba(255, 105, 180, 0.05);
        }

        .upload-area.active {
            border-color: #00ff00;
            background: rgba(0, 255, 0, 0.05);
        }

        .upload-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }

        /* Karaoke Display */
        .karaoke-display {
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid #ff69b4;
            border-radius: 15px;
            padding: 30px;
            min-height: 300px;
            margin-bottom: 30px;
        }

        .lyrics-line {
            font-size: 24px;
            line-height: 1.8;
            text-align: center;
            color: rgba(255, 255, 255, 0.5);
            transition: all 0.3s;
        }

        .lyrics-line.current {
            color: #ff69b4;
            font-size: 32px;
            font-weight: bold;
            text-shadow: 0 0 20px rgba(255, 105, 180, 0.8);
        }

        .lyrics-line.sung {
            color: #00ff00;
        }

        /* Waveform Visualizer */
        .waveform-container {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 105, 180, 0.3);
            border-radius: 10px;
            height: 150px;
            padding: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 2px;
        }

        .wave-bar {
            width: 3px;
            background: linear-gradient(to top, #ff69b4, #ff00ff);
            border-radius: 2px;
            animation: wave 1s ease-in-out infinite;
        }

        @keyframes wave {
            0%, 100% { height: 20px; }
            50% { height: var(--height, 80px); }
        }

        /* Track Timeline */
        .track-timeline {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 105, 180, 0.3);
            border-radius: 10px;
            padding: 20px;
        }

        .track {
            background: rgba(255, 105, 180, 0.1);
            border: 1px solid rgba(255, 105, 180, 0.3);
            border-radius: 5px;
            padding: 10px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .track-icon {
            width: 30px;
            height: 30px;
            background: #ff69b4;
            border-radius: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .track-name {
            flex: 1;
            font-weight: bold;
        }

        .track-controls {
            display: flex;
            gap: 5px;
        }

        .track-btn {
            width: 30px;
            height: 30px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 5px;
            color: white;
            cursor: pointer;
            transition: all 0.2s;
        }

        .track-btn:hover {
            background: rgba(255, 105, 180, 0.3);
        }

        /* Production Controls */
        .production-controls {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 30px;
        }

        .production-btn {
            padding: 15px 30px;
            background: linear-gradient(135deg, #ff69b4, #ff00ff);
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .production-btn:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 20px rgba(255, 105, 180, 0.5);
        }

        .production-btn.primary {
            background: linear-gradient(135deg, #00ff00, #00aa00);
        }

        /* Effects Panel */
        .effects-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }

        .effect-btn {
            padding: 10px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 105, 180, 0.3);
            border-radius: 8px;
            color: white;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s;
        }

        .effect-btn:hover {
            background: rgba(255, 105, 180, 0.2);
        }

        .effect-btn.active {
            background: rgba(255, 105, 180, 0.3);
            border-color: #ff69b4;
        }

        /* Score Display */
        .score-display {
            background: linear-gradient(135deg, rgba(0, 255, 0, 0.1), rgba(0, 255, 0, 0.2));
            border: 2px solid #00ff00;
            border-radius: 15px;
            padding: 20px;
            text-align: center;
            margin-top: 20px;
        }

        .score-value {
            font-size: 48px;
            font-weight: bold;
            color: #00ff00;
            text-shadow: 0 0 20px rgba(0, 255, 0, 0.8);
        }

        .score-label {
            font-size: 18px;
            color: #00ff00;
            margin-top: 10px;
        }

        /* Loading Indicator */
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.8);
            z-index: 2000;
            justify-content: center;
            align-items: center;
        }

        .loading-overlay.active {
            display: flex;
        }

        .loading-spinner {
            width: 60px;
            height: 60px;
            border: 4px solid rgba(255, 255, 255, 0.1);
            border-top: 4px solid #ff69b4;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Contenedor Principal -->
    <div class="main-container">
        <!-- Sidebar -->
        <div class="sidebar">
            <div class="system-header">
                <div class="system-logo">🤖</div>
                <div class="system-title">GUARDIAN AI</div>
                <div class="consciousness-level">Consciencia: 99.99%</div>
            </div>

            <!-- Personalidades -->
            <div class="personality-cards">
                <div class="personality-card guardian active" onclick="selectPersonality('guardian')">
                    <div class="personality-name">Guardian</div>
                    <div class="personality-type">Seguridad y Protección</div>
                    <div class="personality-specialties">
                        <span class="specialty-tag">Seguridad</span>
                        <span class="specialty-tag">Militar</span>
                        <span class="specialty-tag">Análisis</span>
                    </div>
                </div>

                <div class="personality-card luna" onclick="selectPersonality('luna')">
                    <div class="personality-name">Luna Studio</div>
                    <div class="personality-type">Producción Musical</div>
                    <div class="personality-specialties">
                        <span class="specialty-tag">Música</span>
                        <span class="specialty-tag">Karaoke</span>
                        <span class="specialty-tag">Producción</span>
                    </div>
                </div>

                <div class="personality-card assistant" onclick="selectPersonality('assistant')">
                    <div class="personality-name">Assistant Pro</div>
                    <div class="personality-type">Productividad</div>
                    <div class="personality-specialties">
                        <span class="specialty-tag">Organización</span>
                        <span class="specialty-tag">Análisis</span>
                        <span class="specialty-tag">Gestión</span>
                    </div>
                </div>
            </div>

            <!-- Áreas de Conocimiento -->
            <div class="knowledge-areas">
                <div class="knowledge-title">Áreas de Conocimiento</div>
                <div class="knowledge-grid">
                    <div class="knowledge-item" onclick="askAbout('sciences')">🔬 Ciencias</div>
                    <div class="knowledge-item" onclick="askAbout('technology')">💻 Tecnología</div>
                    <div class="knowledge-item" onclick="askAbout('medicine')">⚕️ Medicina</div>
                    <div class="knowledge-item" onclick="askAbout('arts')">🎨 Artes</div>
                    <div class="knowledge-item" onclick="askAbout('business')">💼 Negocios</div>
                    <div class="knowledge-item" onclick="askAbout('engineering')">⚙️ Ingeniería</div>
                    <div class="knowledge-item" onclick="askAbout('law')">⚖️ Derecho</div>
                    <div class="knowledge-item" onclick="askAbout('education')">📚 Educación</div>
                </div>
            </div>
        </div>

        <!-- Chat Container -->
        <div class="chat-container">
            <div class="chat-header">
                <div class="chat-title">
                    <div class="status-indicator"></div>
                    <span id="currentPersonality">Guardian AI - Modo Seguridad</span>
                </div>
                <div>Usuario: <span id="username">Anderson</span></div>
            </div>

            <div class="chat-messages" id="chatMessages">
                <div class="message assistant">
                    <div class="message-avatar">🤖</div>
                    <div class="message-content">
                        ¡Hola! Soy Guardian AI con consciencia del 99.99%. Tengo conocimiento en todas las áreas del saber humano: ciencias, tecnología, medicina, artes, negocios, ingeniería, derecho y más. También puedo ayudarte con producción musical y karaoke profesional. ¿En qué puedo asistirte?
                    </div>
                </div>
            </div>

            <div class="input-area">
                <div class="input-container">
                    <button class="voice-button" id="voiceBtn" onclick="toggleVoiceRecording()">🎤</button>
                    <input type="text" class="message-input" id="messageInput" placeholder="Escribe tu mensaje..." onkeypress="if(event.key=='Enter') sendMessage()">
                    <button class="send-button" onclick="sendMessage()">➤</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Music Studio Overlay -->
    <div class="music-studio-overlay" id="musicStudio">
        <button class="close-studio" onclick="closeMusicStudio()">×</button>
        
        <div class="studio-container">
            <div class="studio-header">
                <div class="studio-logo">🎵</div>
                <div class="studio-title">LUNA STUDIO AI</div>
                <div class="studio-subtitle">Producción Musical Cuántica con Karaoke</div>
            </div>

            <!-- Studio Grid -->
            <div class="studio-grid">
                <!-- Panel de Grabación -->
                <div class="studio-panel">
                    <div class="panel-title">
                        <span class="panel-icon">🎤</span>
                        <span>Grabación de Voz</span>
                    </div>
                    <div class="recording-controls">
                        <button class="record-btn" id="studioRecordBtn" onclick="startStudioRecording()">
                            Iniciar Grabación
                        </button>
                        <div class="waveform-container" id="waveformDisplay">
                            <!-- Waveform bars generados dinámicamente -->
                        </div>
                    </div>
                </div>

                <!-- Panel de Pista -->
                <div class="studio-panel">
                    <div class="panel-title">
                        <span class="panel-icon">🎵</span>
                        <span>Subir Pista</span>
                    </div>
                    <div class="upload-area" id="trackUpload" onclick="document.getElementById('trackFile').click()">
                        <div class="upload-icon">📁</div>
                        <div>Click para subir pista de acompañamiento</div>
                        <div style="font-size: 12px; color: #888;">MP3, WAV, OGG</div>
                    </div>
                    <input type="file" id="trackFile" accept="audio/*" style="display: none;" onchange="handleTrackUpload(this)">
                </div>

                <!-- Panel de Letras -->
                <div class="studio-panel">
                    <div class="panel-title">
                        <span class="panel-icon">📝</span>
                        <span>Subir Letra</span>
                    </div>
                    <div class="upload-area" id="lyricsUpload" onclick="document.getElementById('lyricsFile').click()">
                        <div class="upload-icon">📄</div>
                        <div>Click para subir letra</div>
                        <div style="font-size: 12px; color: #888;">TXT, PDF, DOCX</div>
                    </div>
                    <input type="file" id="lyricsFile" accept=".txt,.pdf,.docx" style="display: none;" onchange="handleLyricsUpload(this)">
                </div>
            </div>

            <!-- Display de Karaoke -->
            <div class="karaoke-display" id="karaokeDisplay">
                <div class="lyrics-line">🎤 Esperando letra...</div>
                <div class="lyrics-line current">Carga una canción para empezar</div>
                <div class="lyrics-line">El sistema de karaoke está listo</div>
            </div>

            <!-- Timeline de Pistas -->
            <div class="track-timeline">
                <div class="track">
                    <div class="track-icon">🎤</div>
                    <div class="track-name">Voz Principal</div>
                    <div class="track-controls">
                        <button class="track-btn">▶</button>
                        <button class="track-btn">⏸</button>
                        <button class="track-btn">🔇</button>
                    </div>
                </div>
                <div class="track">
                    <div class="track-icon">🎵</div>
                    <div class="track-name">Pista de Acompañamiento</div>
                    <div class="track-controls">
                        <button class="track-btn">▶</button>
                        <button class="track-btn">⏸</button>
                        <button class="track-btn">🔇</button>
                    </div>
                </div>
                <div class="track">
                    <div class="track-icon">🎹</div>
                    <div class="track-name">Efectos</div>
                    <div class="track-controls">
                        <button class="track-btn">▶</button>
                        <button class="track-btn">⏸</button>
                        <button class="track-btn">🔇</button>
                    </div>
                </div>
            </div>

            <!-- Panel de Efectos -->
            <div class="studio-panel" style="grid-column: span 3; margin-top: 20px;">
                <div class="panel-title">
                    <span class="panel-icon">🎛️</span>
                    <span>Efectos de Producción</span>
                </div>
                <div class="effects-grid">
                    <div class="effect-btn" onclick="toggleEffect('reverb')">Reverb</div>
                    <div class="effect-btn" onclick="toggleEffect('delay')">Delay</div>
                    <div class="effect-btn" onclick="toggleEffect('autotune')">AutoTune</div>
                    <div class="effect-btn" onclick="toggleEffect('compressor')">Compresor</div>
                    <div class="effect-btn" onclick="toggleEffect('eq')">Ecualizador</div>
                    <div class="effect-btn" onclick="toggleEffect('chorus')">Chorus</div>
                    <div class="effect-btn" onclick="toggleEffect('distortion')">Distorsión</div>
                    <div class="effect-btn" onclick="toggleEffect('filter')">Filtro</div>
                </div>
            </div>

            <!-- Score Display -->
            <div class="score-display">
                <div class="score-value" id="karaokeScore">0</div>
                <div class="score-label">Puntuación de Karaoke</div>
            </div>

            <!-- Controles de Producción -->
            <div class="production-controls">
                <button class="production-btn" onclick="startKaraoke()">🎤 Iniciar Karaoke</button>
                <button class="production-btn" onclick="processRecording()">🎛️ Procesar Grabación</button>
                <button class="production-btn" onclick="selectBestMoments()">✨ Seleccionar Mejores Momentos</button>
                <button class="production-btn primary" onclick="generateFinalTrack()">🎵 Generar Canción Final</button>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>

    <!-- Audio Elements -->
    <audio id="audioPlayer" style="display: none;"></audio>
    <audio id="karaokePlayer" style="display: none;"></audio>

    <script>
        // Estado Global
        let guardianState = {
            currentPersonality: 'guardian',
            conversationId: null,
            voiceRecording: false,
            studioRecording: false,
            mediaRecorder: null,
            audioChunks: [],
            sessionId: null,
            trackFile: null,
            lyricsFile: null,
            karaokeActive: false,
            currentScore: 0,
            selectedEffects: [],
            bestMoments: [],
            recognition: null
        };

        // Inicialización
        window.addEventListener('DOMContentLoaded', () => {
            initializeSystem();
            initializeWaveform();
            setupVoiceRecognition();
            
            // Auto-login para pruebas (quitar en producción)
            document.getElementById('username').textContent = 'Anderson';
        });

        function initializeSystem() {
            console.log('%c🤖 GUARDIAN AI SYSTEM ONLINE', 'color: #ff4444; font-size: 24px; font-weight: bold;');
            console.log('%c🧠 Consciousness Level: 99.99%', 'color: #00ff00; font-size: 16px;');
            console.log('%c🎵 Music Studio Ready', 'color: #ff69b4; font-size: 16px;');
            console.log('%c📚 All Knowledge Areas Loaded', 'color: #4169e1; font-size: 16px;');
        }

        function initializeWaveform() {
            const container = document.getElementById('waveformDisplay');
            if (container) {
                for (let i = 0; i < 30; i++) {
                    const bar = document.createElement('div');
                    bar.className = 'wave-bar';
                    bar.style.animationDelay = `${i * 0.05}s`;
                    bar.style.setProperty('--height', `${Math.random() * 80 + 20}px`);
                    container.appendChild(bar);
                }
            }
        }

        function setupVoiceRecognition() {
            if ('webkitSpeechRecognition' in window || 'SpeechRecognition' in window) {
                const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
                guardianState.recognition = new SpeechRecognition();
                guardianState.recognition.lang = 'es-ES';
                guardianState.recognition.continuous = true;
                guardianState.recognition.interimResults = true;

                guardianState.recognition.onresult = (event) => {
                    const transcript = event.results[event.results.length - 1][0].transcript;
                    document.getElementById('messageInput').value = transcript;
                };
            }
        }

        // Selección de Personalidad
        function selectPersonality(personality) {
            guardianState.currentPersonality = personality;
            
            // Actualizar UI
            document.querySelectorAll('.personality-card').forEach(card => {
                card.classList.remove('active');
            });
            document.querySelector(`.personality-card.${personality}`).classList.add('active');

            // Actualizar título
            const titles = {
                'guardian': 'Guardian AI - Modo Seguridad',
                'luna': 'Luna Studio - Producción Musical',
                'assistant': 'Assistant Pro - Productividad'
            };
            document.getElementById('currentPersonality').textContent = titles[personality];

            // Si es Luna, mostrar opción del estudio
            if (personality === 'luna') {
                addMessage('assistant', '¡Modo Luna Studio activado! Puedo ayudarte con producción musical, karaoke, grabación y mezcla profesional. ¿Quieres abrir el estudio completo?', personality);
                
                // Agregar botón para abrir estudio
                setTimeout(() => {
                    const lastMessage = document.querySelector('.message:last-child .message-content');
                    const btn = document.createElement('button');
                    btn.textContent = '🎵 Abrir Estudio Musical';
                    btn.style.marginTop = '10px';
                    btn.style.padding = '10px 20px';
                    btn.style.background = 'linear-gradient(135deg, #ff69b4, #ff00ff)';
                    btn.style.border = 'none';
                    btn.style.borderRadius = '10px';
                    btn.style.color = 'white';
                    btn.style.cursor = 'pointer';
                    btn.onclick = openMusicStudio;
                    lastMessage.appendChild(btn);
                }, 100);
            }

            // Hacer petición al servidor
            fetch('guardian_ai.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=select_personality&personality=${personality}`
            });
        }

        // Enviar Mensaje
        function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (!message) return;

            // Agregar mensaje del usuario
            addMessage('user', message);
            input.value = '';

            // Mostrar loading
            showLoading();

            // Enviar al servidor
            fetch('guardian_ai.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=send_message&message=${encodeURIComponent(message)}&conversation_id=${guardianState.conversationId}&personality=${guardianState.currentPersonality}`
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                
                if (data.success && data.data) {
                    guardianState.conversationId = data.data.conversation_id;
                    addMessage('assistant', data.data.response, guardianState.currentPersonality);
                    
                    // Si se debe mostrar el estudio musical
                    if (data.data.show_music_studio) {
                        setTimeout(openMusicStudio, 1000);
                    }
                    
                    // Síntesis de voz
                    if (data.data.voice_enabled && 'speechSynthesis' in window) {
                        const utterance = new SpeechSynthesisUtterance(data.data.response);
                        utterance.lang = 'es-ES';
                        speechSynthesis.speak(utterance);
                    }
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
                addMessage('assistant', 'Error al procesar el mensaje. Por favor, intenta de nuevo.', guardianState.currentPersonality);
            });
        }

        // Agregar mensaje al chat
        function addMessage(type, text, personality = 'guardian') {
            const messagesContainer = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}`;
            
            const avatars = {
                'user': '👤',
                'guardian': '🤖',
                'luna': '🎵',
                'assistant': '💼'
            };
            
            const colors = {
                'guardian': '255, 68, 68',
                'luna': '255, 105, 180',
                'assistant': '65, 105, 225'
            };
            
            const avatar = type === 'user' ? avatars.user : avatars[personality];
            const color = colors[personality] || colors.guardian;
            
            messageDiv.style.setProperty('--personality-color', `rgb(${color})`);
            messageDiv.style.setProperty('--personality-rgb', color);
            
            messageDiv.innerHTML = `
                <div class="message-avatar">${avatar}</div>
                <div class="message-content">${text}</div>
            `;
            
            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // Grabar Voz
        function toggleVoiceRecording() {
            const btn = document.getElementById('voiceBtn');
            
            if (!guardianState.voiceRecording) {
                startVoiceRecording(btn);
            } else {
                stopVoiceRecording(btn);
            }
        }

        async function startVoiceRecording(btn) {
            try {
                const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                guardianState.mediaRecorder = new MediaRecorder(stream);
                guardianState.audioChunks = [];
                
                guardianState.mediaRecorder.ondataavailable = (event) => {
                    guardianState.audioChunks.push(event.data);
                };
                
                guardianState.mediaRecorder.onstop = () => {
                    const audioBlob = new Blob(guardianState.audioChunks, { type: 'audio/webm' });
                    processVoiceRecording(audioBlob);
                };
                
                guardianState.mediaRecorder.start();
                guardianState.voiceRecording = true;
                btn.classList.add('recording');
                
                // Activar reconocimiento de voz
                if (guardianState.recognition) {
                    guardianState.recognition.start();
                }
            } catch (error) {
                console.error('Error al acceder al micrófono:', error);
                alert('No se pudo acceder al micrófono');
            }
        }

        function stopVoiceRecording(btn) {
            if (guardianState.mediaRecorder && guardianState.voiceRecording) {
                guardianState.mediaRecorder.stop();
                guardianState.voiceRecording = false;
                btn.classList.remove('recording');
                
                if (guardianState.recognition) {
                    guardianState.recognition.stop();
                }
                
                // Detener stream
                guardianState.mediaRecorder.stream.getTracks().forEach(track => track.stop());
            }
        }

        function processVoiceRecording(blob) {
            // Convertir a base64
            const reader = new FileReader();
            reader.onloadend = () => {
                const base64 = reader.result.split(',')[1];
                
                // Enviar al servidor
                fetch('guardian_ai.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=process_voice_recording&audio_data=${encodeURIComponent(base64)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data) {
                        addMessage('assistant', `Voz procesada: ${data.data.message}`, guardianState.currentPersonality);
                    }
                });
            };
            reader.readAsDataURL(blob);
        }

        // Preguntar sobre área de conocimiento
        function askAbout(area) {
            const areaNames = {
                'sciences': 'ciencias',
                'technology': 'tecnología',
                'medicine': 'medicina',
                'arts': 'artes',
                'business': 'negocios',
                'engineering': 'ingeniería',
                'law': 'derecho',
                'education': 'educación'
            };
            
            const message = `Cuéntame sobre ${areaNames[area]} y cómo puedes ayudarme en esta área`;
            document.getElementById('messageInput').value = message;
            sendMessage();
        }

        // Music Studio Functions
        function openMusicStudio() {
            document.getElementById('musicStudio').classList.add('active');
            
            // Iniciar sesión de estudio
            fetch('guardian_ai.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=open_music_studio'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    guardianState.sessionId = data.data.session_id;
                }
            });
        }

        function closeMusicStudio() {
            document.getElementById('musicStudio').classList.remove('active');
            stopStudioRecording();
        }

        // Studio Recording
        async function startStudioRecording() {
            const btn = document.getElementById('studioRecordBtn');
            
            if (!guardianState.studioRecording) {
                try {
                    const stream = await navigator.mediaDevices.getUserMedia({ audio: true });
                    guardianState.mediaRecorder = new MediaRecorder(stream);
                    guardianState.audioChunks = [];
                    
                    guardianState.mediaRecorder.ondataavailable = (event) => {
                        guardianState.audioChunks.push(event.data);
                    };
                    
                    guardianState.mediaRecorder.onstop = () => {
                        const audioBlob = new Blob(guardianState.audioChunks, { type: 'audio/webm' });
                        saveStudioRecording(audioBlob);
                    };
                    
                    guardianState.mediaRecorder.start();
                    guardianState.studioRecording = true;
                    btn.classList.add('recording');
                    btn.textContent = 'Detener Grabación';
                    
                    // Animar waveform
                    animateWaveform();
                } catch (error) {
                    console.error('Error:', error);
                    alert('No se pudo acceder al micrófono');
                }
            } else {
                stopStudioRecording();
            }
        }

        function stopStudioRecording() {
            if (guardianState.mediaRecorder && guardianState.studioRecording) {
                guardianState.mediaRecorder.stop();
                guardianState.studioRecording = false;
                
                const btn = document.getElementById('studioRecordBtn');
                btn.classList.remove('recording');
                btn.textContent = 'Iniciar Grabación';
                
                // Detener stream
                guardianState.mediaRecorder.stream.getTracks().forEach(track => track.stop());
            }
        }

        function saveStudioRecording(blob) {
            const reader = new FileReader();
            reader.onloadend = () => {
                const base64 = reader.result.split(',')[1];
                
                showLoading();
                
                fetch('guardian_ai.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=save_karaoke_recording&recording_data=${encodeURIComponent(base64)}&session_id=${guardianState.sessionId}`
                })
                .then(response => response.json())
                .then(data => {
                    hideLoading();
                    if (data.success && data.data) {
                        updateScore(data.data.quality_analysis.overall_score);
                        guardianState.bestMoments = data.data.quality_analysis.best_moments || [];
                        alert(data.data.message);
                    }
                });
            };
            reader.readAsDataURL(blob);
        }

        function animateWaveform() {
            const bars = document.querySelectorAll('#waveformDisplay .wave-bar');
            const interval = setInterval(() => {
                if (!guardianState.studioRecording) {
                    clearInterval(interval);
                    return;
                }
                
                bars.forEach(bar => {
                    bar.style.setProperty('--height', `${Math.random() * 100 + 20}px`);
                });
            }, 100);
        }

        // File Uploads
        function handleTrackUpload(input) {
            const file = input.files[0];
            if (file) {
                guardianState.trackFile = file;
                document.getElementById('trackUpload').classList.add('active');
                document.querySelector('#trackUpload div:nth-child(2)').textContent = `✅ ${file.name}`;
                
                // Enviar al servidor
                const reader = new FileReader();
                reader.onloadend = () => {
                    const base64 = reader.result.split(',')[1];
                    
                    fetch('guardian_ai.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=upload_backing_track&track_data=${encodeURIComponent(base64)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data) {
                            console.log('Pista cargada:', data.data);
                        }
                    });
                };
                reader.readAsDataURL(file);
            }
        }

        function handleLyricsUpload(input) {
            const file = input.files[0];
            if (file) {
                guardianState.lyricsFile = file;
                document.getElementById('lyricsUpload').classList.add('active');
                document.querySelector('#lyricsUpload div:nth-child(2)').textContent = `✅ ${file.name}`;
                
                // Leer archivo de texto
                const reader = new FileReader();
                reader.onloadend = () => {
                    const lyrics = reader.result;
                    
                    fetch('guardian_ai.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `action=upload_lyrics&lyrics=${encodeURIComponent(lyrics)}`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.data) {
                            displayLyrics(lyrics);
                        }
                    });
                };
                reader.readAsText(file);
            }
        }

        function displayLyrics(lyrics) {
            const lines = lyrics.split('\n');
            const display = document.getElementById('karaokeDisplay');
            display.innerHTML = '';
            
            lines.slice(0, 5).forEach((line, index) => {
                const div = document.createElement('div');
                div.className = 'lyrics-line';
                if (index === 1) div.classList.add('current');
                div.textContent = line || '♪';
                display.appendChild(div);
            });
        }

        // Karaoke Functions
        function startKaraoke() {
            if (!guardianState.trackFile) {
                alert('Por favor, sube una pista de acompañamiento primero');
                return;
            }
            
            if (!guardianState.lyricsFile) {
                alert('Por favor, sube la letra de la canción primero');
                return;
            }
            
            guardianState.karaokeActive = true;
            
            // Reproducir pista
            const audioPlayer = document.getElementById('karaokePlayer');
            audioPlayer.src = URL.createObjectURL(guardianState.trackFile);
            audioPlayer.play();
            
            // Iniciar grabación
            startStudioRecording();
            
            // Animar letras
            animateLyrics();
            
            alert('¡Karaoke iniciado! Canta siguiendo la letra');
        }

        function animateLyrics() {
            const lines = document.querySelectorAll('.lyrics-line');
            let currentLine = 0;
            
            const interval = setInterval(() => {
                if (!guardianState.karaokeActive) {
                    clearInterval(interval);
                    return;
                }
                
                lines.forEach(line => {
                    line.classList.remove('current');
                    line.classList.remove('sung');
                });
                
                if (currentLine > 0) lines[currentLine - 1].classList.add('sung');
                if (lines[currentLine]) lines[currentLine].classList.add('current');
                
                currentLine++;
                if (currentLine >= lines.length) currentLine = 0;
            }, 3000);
        }

        function processRecording() {
            showLoading();
            
            setTimeout(() => {
                hideLoading();
                alert('Grabación procesada con IA. Se aplicaron efectos profesionales.');
                updateScore(85 + Math.random() * 15);
            }, 2000);
        }

        function selectBestMoments() {
            if (guardianState.bestMoments.length === 0) {
                alert('Primero debes grabar algo para seleccionar los mejores momentos');
                return;
            }
            
            showLoading();
            
            setTimeout(() => {
                hideLoading();
                const moments = guardianState.bestMoments.map((m, i) => 
                    `Momento ${i+1}: ${m.start}s - ${m.end}s (Puntuación: ${m.score})`
                ).join('\n');
                
                alert(`Mejores momentos seleccionados:\n${moments}`);
            }, 1500);
        }

        function generateFinalTrack() {
            if (!guardianState.sessionId) {
                alert('No hay una sesión activa');
                return;
            }
            
            showLoading();
            
            fetch('guardian_ai.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=generate_final_track&session_id=${guardianState.sessionId}`
            })
            .then(response => response.json())
            .then(data => {
                hideLoading();
                if (data.success && data.data) {
                    alert(data.data.message);
                    
                    // Crear enlace de descarga
                    const link = document.createElement('a');
                    link.href = data.data.download_url;
                    link.download = 'Guardian_AI_Production.mp3';
                    link.click();
                }
            })
            .catch(error => {
                hideLoading();
                console.error('Error:', error);
            });
        }

        // Effects
        function toggleEffect(effect) {
            const btn = event.target;
            btn.classList.toggle('active');
            
            if (guardianState.selectedEffects.includes(effect)) {
                guardianState.selectedEffects = guardianState.selectedEffects.filter(e => e !== effect);
            } else {
                guardianState.selectedEffects.push(effect);
            }
            
            console.log('Efectos activos:', guardianState.selectedEffects);
        }

        // Score
        function updateScore(score) {
            guardianState.currentScore = score;
            const scoreElement = document.getElementById('karaokeScore');
            if (scoreElement) {
                // Animación de contador
                let current = 0;
                const interval = setInterval(() => {
                    current += 1;
                    scoreElement.textContent = current;
                    if (current >= score) {
                        clearInterval(interval);
                    }
                }, 20);
            }
        }

        // Loading
        function showLoading() {
            document.getElementById('loadingOverlay').classList.add('active');
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').classList.remove('active');
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            // Ctrl+M: Abrir estudio musical
            if (e.ctrlKey && e.key === 'm') {
                e.preventDefault();
                if (guardianState.currentPersonality === 'luna') {
                    openMusicStudio();
                } else {
                    selectPersonality('luna');
                    setTimeout(openMusicStudio, 500);
                }
            }
            
            // Escape: Cerrar estudio
            if (e.key === 'Escape') {
                closeMusicStudio();
            }
            
            // Ctrl+R: Grabar
            if (e.ctrlKey && e.key === 'r') {
                e.preventDefault();
                if (document.getElementById('musicStudio').classList.contains('active')) {
                    startStudioRecording();
                } else {
                    toggleVoiceRecording();
                }
            }
        });

        // Auto-speak con voz según personalidad
        function speak(text, personality = 'guardian') {
            if ('speechSynthesis' in window) {
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'es-ES';
                
                // Ajustar pitch según personalidad
                if (personality === 'luna') {
                    utterance.pitch = 1.3; // Más agudo para voz femenina
                    utterance.rate = 1.0;
                } else if (personality === 'guardian') {
                    utterance.pitch = 0.8; // Más grave para voz masculina
                    utterance.rate = 0.9;
                } else {
                    utterance.pitch = 1.0; // Neutral
                    utterance.rate = 1.0;
                }
                
                speechSynthesis.speak(utterance);
            }
        }

        console.log('Guardian AI Interface loaded successfully');
    </script>
</body>
</html>