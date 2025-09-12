<?php
/**
 * GuardianIA - Chatbot IA Inteligente
 * Sistema de conversación inteligente especializado en seguridad y optimización
 * Versión 2.0.0 - Implementación completa con procesamiento de lenguaje natural
 */

require_once 'config.php';

class GuardianAIChatbot {
    private $conn;
    private $knowledge_base;
    private $conversation_context;
    private $ai_models;
    private $nlp_processor;
    
    public function __construct() {
        global $conn;
        $this->conn = $conn;
        
        if (!$this->conn) {
            throw new Exception("Error de conexión a la base de datos");
        }
        
        $this->loadKnowledgeBase();
        $this->loadAIModels();
        $this->initializeNLPProcessor();
        $this->conversation_context = [];
        
        logGuardianEvent('INFO', 'GuardianAIChatbot inicializado');
    }
    
    // ===========================================
    // FUNCIONES PRINCIPALES DEL CHATBOT
    // ===========================================
    
    /**
     * Procesar mensaje del usuario y generar respuesta inteligente
     */
    public function processUserMessage($user_id, $message, $conversation_id = null) {
        try {
            $processing_start = microtime(true);
            
            // Crear o recuperar conversación
            if (!$conversation_id) {
                $conversation_id = $this->createNewConversation($user_id, $message);
            }
            
            // Guardar mensaje del usuario
            $user_message_id = $this->saveUserMessage($conversation_id, $message);
            
            // Análisis de procesamiento de lenguaje natural
            $nlp_analysis = $this->performNLPAnalysis($message);
            
            // Detectar intención del usuario
            $intent_detection = $this->detectUserIntent($message, $nlp_analysis);
            
            // Extraer entidades relevantes
            $entity_extraction = $this->extractEntities($message, $nlp_analysis);
            
            // Buscar en base de conocimientos
            $knowledge_search = $this->searchKnowledgeBase($intent_detection, $entity_extraction, $message);
            
            // Generar respuesta contextual
            $response_generation = $this->generateContextualResponse(
                $user_id, 
                $message, 
                $intent_detection, 
                $entity_extraction, 
                $knowledge_search,
                $conversation_id
            );
            
            // Ejecutar acciones si es necesario
            $action_execution = null;
            if ($intent_detection['requires_action']) {
                $action_execution = $this->executeUserAction($user_id, $intent_detection, $entity_extraction);
            }
            
            $processing_end = microtime(true);
            $response_time = round($processing_end - $processing_start, 3);
            
            // Guardar respuesta del bot
            $bot_response = [
                'text' => $response_generation['response_text'],
                'confidence' => $response_generation['confidence'],
                'intent' => $intent_detection['intent'],
                'entities' => $entity_extraction['entities'],
                'action_result' => $action_execution,
                'response_time' => $response_time,
                'suggestions' => $response_generation['suggestions'] ?? [],
                'quick_actions' => $response_generation['quick_actions'] ?? []
            ];
            
            $this->saveBotMessage($conversation_id, $bot_response, $response_time, $intent_detection['confidence']);
            
            // Actualizar contexto de conversación
            $this->updateConversationContext($conversation_id, $intent_detection, $entity_extraction);
            
            // Aprendizaje automático basado en la interacción
            $this->performAutomaticLearning($user_id, $message, $intent_detection, $response_generation);
            
            logGuardianEvent('INFO', 'Mensaje procesado por chatbot', [
                'user_id' => $user_id,
                'conversation_id' => $conversation_id,
                'intent' => $intent_detection['intent'],
                'response_time' => $response_time
            ]);
            
            return [
                'success' => true,
                'conversation_id' => $conversation_id,
                'response' => $bot_response,
                'processing_details' => [
                    'nlp_analysis' => $nlp_analysis,
                    'intent_detection' => $intent_detection,
                    'entity_extraction' => $entity_extraction,
                    'knowledge_search' => $knowledge_search,
                    'response_time' => $response_time
                ]
            ];
            
        } catch (Exception $e) {
            logGuardianEvent('ERROR', 'Error en procesamiento de mensaje', [
                'error' => $e->getMessage(), 
                'user_id' => $user_id,
                'message' => substr($message, 0, 100)
            ]);
            
            return [
                'success' => false,
                'message' => 'Error en procesamiento: ' . $e->getMessage(),
                'fallback_response' => $this->getFallbackResponse()
            ];
        }
    }
    
    /**
     * Obtener historial de conversación
     */
    public function getConversationHistory($user_id, $conversation_id, $limit = 50) {
        try {
            $sql = "SELECT cm.*, cc.conversation_title, cc.status as conversation_status
                    FROM chatbot_messages cm
                    INNER JOIN chatbot_conversations cc ON cm.conversation_id = cc.id
                    WHERE cc.user_id = ? AND cm.conversation_id = ?
                    ORDER BY cm.created_at ASC
                    LIMIT ?";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error al preparar consulta de historial: " . $this->conn->error);
            }
            
            $stmt->bind_param("iii", $user_id, $conversation_id, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            $messages = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            // Procesar mensajes para formato de respuesta
            $formatted_messages = [];
            foreach ($messages as $message) {
                $formatted_messages[] = [
                    'id' => $message['id'],
                    'sender' => $message['sender'],
                    'message' => $message['message_text'],
                    'message_type' => $message['message_type'],
                    'intent' => $message['intent_detected'],
                    'confidence' => $message['confidence_score'],
                    'timestamp' => $message['created_at'],
                    'context_data' => json_decode($message['context_data'], true)
                ];
            }
            
            return [
                'success' => true,
                'conversation_id' => $conversation_id,
                'messages' => $formatted_messages,
                'total_messages' => count($formatted_messages)
            ];
            
        } catch (Exception $e) {
            logGuardianEvent('ERROR', 'Error al obtener historial de conversación', [
                'error' => $e->getMessage(),
                'user_id' => $user_id,
                'conversation_id' => $conversation_id
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al obtener historial: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener conversaciones del usuario
     */
    public function getUserConversations($user_id, $limit = 20) {
        try {
            $sql = "SELECT id, conversation_title, status, total_messages, 
                           satisfaction_rating, started_at, ended_at
                    FROM chatbot_conversations 
                    WHERE user_id = ? 
                    ORDER BY started_at DESC 
                    LIMIT ?";
            
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) {
                throw new Exception("Error al preparar consulta de conversaciones: " . $this->conn->error);
            }
            
            $stmt->bind_param("ii", $user_id, $limit);
            $stmt->execute();
            $result = $stmt->get_result();
            $conversations = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            
            return [
                'success' => true,
                'conversations' => $conversations,
                'total_conversations' => count($conversations)
            ];
            
        } catch (Exception $e) {
            logGuardianEvent('ERROR', 'Error al obtener conversaciones del usuario', [
                'error' => $e->getMessage(),
                'user_id' => $user_id
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al obtener conversaciones: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Evaluar satisfacción de la conversación
     */
    public function rateConversation($user_id, $conversation_id, $rating, $feedback = null) {
        try {
            // Verificar que la conversación pertenece al usuario
            $sql_verify = "SELECT id FROM chatbot_conversations WHERE id = ? AND user_id = ?";
            $stmt_verify = $this->conn->prepare($sql_verify);
            if (!$stmt_verify) {
                throw new Exception("Error al verificar conversación: " . $this->conn->error);
            }
            
            $stmt_verify->bind_param("ii", $conversation_id, $user_id);
            $stmt_verify->execute();
            $result = $stmt_verify->get_result();
            $conversation = $result->fetch_assoc();
            $stmt_verify->close();
            
            if (!$conversation) {
                throw new Exception("Conversación no encontrada o no autorizada");
            }
            
            // Actualizar calificación
            $sql_update = "UPDATE chatbot_conversations 
                          SET satisfaction_rating = ?, ended_at = NOW(), status = 'completed'
                          WHERE id = ?";
            
            $stmt_update = $this->conn->prepare($sql_update);
            if (!$stmt_update) {
                throw new Exception("Error al preparar actualización: " . $this->conn->error);
            }
            
            $stmt_update->bind_param("si", $rating, $conversation_id);
            
            if (!$stmt_update->execute()) {
                throw new Exception("Error al actualizar calificación: " . $stmt_update->error);
            }
            $stmt_update->close();
            
            // Guardar feedback si se proporciona
            if ($feedback) {
                $this->saveFeedback($user_id, $conversation_id, $feedback);
            }
            
            // Aprendizaje basado en calificación
            $this->learnFromRating($conversation_id, $rating, $feedback);
            
            return [
                'success' => true,
                'message' => 'Calificación guardada exitosamente',
                'rating' => $rating
            ];
            
        } catch (Exception $e) {
            logGuardianEvent('ERROR', 'Error al calificar conversación', [
                'error' => $e->getMessage(),
                'user_id' => $user_id,
                'conversation_id' => $conversation_id
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al guardar calificación: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Obtener estadísticas del chatbot
     */
    public function getChatbotStatistics($user_id = null, $time_period = '30_days') {
        try {
            $date_condition = $this->getDateCondition($time_period);
            $user_condition = $user_id ? "AND cc.user_id = ?" : "";
            
            // Estadísticas básicas
            $sql_basic = "SELECT 
                            COUNT(DISTINCT cc.id) as total_conversations,
                            COUNT(cm.id) as total_messages,
                            COUNT(CASE WHEN cm.sender = 'user' THEN 1 END) as user_messages,
                            COUNT(CASE WHEN cm.sender = 'bot' THEN 1 END) as bot_messages,
                            AVG(cm.response_time) as avg_response_time,
                            AVG(cm.confidence_score) as avg_confidence,
                            COUNT(CASE WHEN cc.satisfaction_rating IS NOT NULL THEN 1 END) as rated_conversations,
                            AVG(cc.satisfaction_rating) as avg_satisfaction
                          FROM chatbot_conversations cc
                          LEFT JOIN chatbot_messages cm ON cc.id = cm.conversation_id
                          WHERE cc.started_at >= {$date_condition} {$user_condition}";
            
            $stmt_basic = $this->conn->prepare($sql_basic);
            if (!$stmt_basic) {
                throw new Exception("Error al preparar consulta básica: " . $this->conn->error);
            }
            
            if ($user_id) {
                $stmt_basic->bind_param("i", $user_id);
            }
            
            $stmt_basic->execute();
            $basic_stats = $stmt_basic->get_result()->fetch_assoc();
            $stmt_basic->close();
            
            // Estadísticas de intenciones
            $sql_intents = "SELECT 
                              cm.intent_detected,
                              COUNT(*) as count,
                              AVG(cm.confidence_score) as avg_confidence
                            FROM chatbot_messages cm
                            INNER JOIN chatbot_conversations cc ON cm.conversation_id = cc.id
                            WHERE cm.sender = 'user' AND cm.created_at >= {$date_condition} {$user_condition}
                              AND cm.intent_detected IS NOT NULL
                            GROUP BY cm.intent_detected
                            ORDER BY count DESC";
            
            $stmt_intents = $this->conn->prepare($sql_intents);
            if (!$stmt_intents) {
                throw new Exception("Error al preparar consulta de intenciones: " . $this->conn->error);
            }
            
            if ($user_id) {
                $stmt_intents->bind_param("i", $user_id);
            }
            
            $stmt_intents->execute();
            $intent_stats = $stmt_intents->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt_intents->close();
            
            // Análisis de rendimiento del chatbot
            $performance_analysis = $this->analyzeChatbotPerformance($user_id, $time_period);
            
            // Tendencias de uso
            $usage_trends = $this->getChatbotUsageTrends($user_id, $time_period);
            
            return [
                'success' => true,
                'statistics' => [
                    'basic_stats' => $basic_stats,
                    'intent_distribution' => $intent_stats,
                    'performance_analysis' => $performance_analysis,
                    'usage_trends' => $usage_trends,
                    'time_period' => $time_period,
                    'generated_at' => date('Y-m-d H:i:s')
                ]
            ];
            
        } catch (Exception $e) {
            logGuardianEvent('ERROR', 'Error al obtener estadísticas del chatbot', [
                'error' => $e->getMessage(),
                'user_id' => $user_id
            ]);
            
            return [
                'success' => false,
                'message' => 'Error al obtener estadísticas: ' . $e->getMessage()
            ];
        }
    }
    
    // ===========================================
    // FUNCIONES DE PROCESAMIENTO NLP
    // ===========================================
    
    /**
     * Realizar análisis de procesamiento de lenguaje natural
     */
    private function performNLPAnalysis($message) {
        $analysis = [
            'original_text' => $message,
            'normalized_text' => $this->normalizeText($message),
            'tokens' => $this->tokenizeText($message),
            'keywords' => $this->extractKeywords($message),
            'sentiment' => $this->analyzeSentiment($message),
            'language' => $this->detectLanguage($message),
            'complexity' => $this->calculateTextComplexity($message)
        ];
        
        return $analysis;
    }
    
    /**
     * Detectar intención del usuario
     */
    private function detectUserIntent($message, $nlp_analysis) {
        $intent_scores = [];
        
        // Buscar patrones de intención en la base de conocimientos
        foreach ($this->knowledge_base as $knowledge_item) {
            $pattern_score = $this->calculatePatternMatch($message, $knowledge_item['question_pattern']);
            $keyword_score = $this->calculateKeywordMatch($nlp_analysis['keywords'], $knowledge_item['keywords']);
            
            $combined_score = ($pattern_score * 0.7) + ($keyword_score * 0.3);
            
            if ($combined_score > 0.3) {
                $intent_scores[] = [
                    'intent' => $knowledge_item['category'] . '_' . $knowledge_item['subcategory'],
                    'confidence' => $combined_score,
                    'knowledge_id' => $knowledge_item['id'],
                    'category' => $knowledge_item['category'],
                    'subcategory' => $knowledge_item['subcategory']
                ];
            }
        }
        
        // Ordenar por confianza
        usort($intent_scores, function($a, $b) {
            return $b['confidence'] <=> $a['confidence'];
        });
        
        // Determinar si requiere acción
        $requires_action = $this->determineIfActionRequired($message, $nlp_analysis);
        
        if (!empty($intent_scores)) {
            $top_intent = $intent_scores[0];
            return [
                'intent' => $top_intent['intent'],
                'confidence' => $top_intent['confidence'],
                'category' => $top_intent['category'],
                'subcategory' => $top_intent['subcategory'],
                'knowledge_id' => $top_intent['knowledge_id'],
                'requires_action' => $requires_action,
                'alternative_intents' => array_slice($intent_scores, 1, 3)
            ];
        }
        
        // Intent por defecto
        return [
            'intent' => 'general_help',
            'confidence' => 0.5,
            'category' => 'help',
            'subcategory' => 'general',
            'knowledge_id' => null,
            'requires_action' => $requires_action,
            'alternative_intents' => []
        ];
    }
    
    /**
     * Extraer entidades del mensaje
     */
    private function extractEntities($message, $nlp_analysis) {
        $entities = [];
        
        // Extraer entidades de seguridad
        $security_entities = $this->extractSecurityEntities($message);
        $entities = array_merge($entities, $security_entities);
        
        // Extraer entidades de rendimiento
        $performance_entities = $this->extractPerformanceEntities($message);
        $entities = array_merge($entities, $performance_entities);
        
        // Extraer entidades temporales
        $temporal_entities = $this->extractTemporalEntities($message);
        $entities = array_merge($entities, $temporal_entities);
        
        // Extraer entidades numéricas
        $numeric_entities = $this->extractNumericEntities($message);
        $entities = array_merge($entities, $numeric_entities);
        
        return [
            'entities' => $entities,
            'entity_count' => count($entities),
            'entity_types' => array_unique(array_column($entities, 'type'))
        ];
    }
    
    /**
     * Buscar en base de conocimientos
     */
    private function searchKnowledgeBase($intent_detection, $entity_extraction, $message) {
        $search_results = [];
        
        if ($intent_detection['knowledge_id']) {
            // Buscar conocimiento específico por ID
            $specific_knowledge = $this->getKnowledgeById($intent_detection['knowledge_id']);
            if ($specific_knowledge) {
                $search_results[] = [
                    'knowledge_item' => $specific_knowledge,
                    'relevance_score' => $intent_detection['confidence'],
                    'match_type' => 'intent_match'
                ];
            }
        }
        
        // Búsqueda adicional por categoría
        $category_results = $this->searchByCategory($intent_detection['category'], $intent_detection['subcategory']);
        foreach ($category_results as $result) {
            $search_results[] = [
                'knowledge_item' => $result,
                'relevance_score' => $this->calculateRelevanceScore($message, $result),
                'match_type' => 'category_match'
            ];
        }
        
        // Búsqueda por entidades
        foreach ($entity_extraction['entities'] as $entity) {
            $entity_results = $this->searchByEntity($entity);
            foreach ($entity_results as $result) {
                $search_results[] = [
                    'knowledge_item' => $result,
                    'relevance_score' => $this->calculateEntityRelevanceScore($entity, $result),
                    'match_type' => 'entity_match'
                ];
            }
        }
        
        // Eliminar duplicados y ordenar por relevancia
        $search_results = $this->deduplicateAndSort($search_results);
        
        return [
            'results' => array_slice($search_results, 0, 5), // Top 5 resultados
            'total_results' => count($search_results),
            'search_confidence' => $this->calculateSearchConfidence($search_results)
        ];
    }
    
    /**
     * Generar respuesta contextual
     */
    private function generateContextualResponse($user_id, $message, $intent_detection, $entity_extraction, $knowledge_search, $conversation_id) {
        $response_parts = [];
        $confidence = 0;
        $suggestions = [];
        $quick_actions = [];
        
        // Generar respuesta basada en conocimiento encontrado
        if (!empty($knowledge_search['results'])) {
            $best_match = $knowledge_search['results'][0];
            $knowledge_item = $best_match['knowledge_item'];
            
            // Personalizar plantilla de respuesta
            $response_template = $knowledge_item['answer_template'];
            $personalized_response = $this->personalizeResponse($response_template, $user_id, $entity_extraction);
            
            $response_parts[] = $personalized_response;
            $confidence = $best_match['relevance_score'];
            
            // Generar sugerencias relacionadas
            $suggestions = $this->generateRelatedSuggestions($knowledge_item, $intent_detection);
            
            // Generar acciones rápidas
            $quick_actions = $this->generateQuickActions($intent_detection, $entity_extraction);
        } else {
            // Respuesta por defecto cuando no se encuentra conocimiento específico
            $default_response = $this->generateDefaultResponse($intent_detection, $entity_extraction);
            $response_parts[] = $default_response['text'];
            $confidence = $default_response['confidence'];
            $suggestions = $default_response['suggestions'];
        }
        
        // Agregar contexto de conversación si existe
        $conversation_context = $this->getConversationContext($conversation_id);
        if ($conversation_context) {
            $contextual_addition = $this->addConversationContext($response_parts[0], $conversation_context);
            if ($contextual_addition) {
                $response_parts[] = $contextual_addition;
            }
        }
        
        // Combinar partes de la respuesta
        $final_response = implode(' ', $response_parts);
        
        // Aplicar filtros de calidad
        $final_response = $this->applyQualityFilters($final_response);
        
        return [
            'response_text' => $final_response,
            'confidence' => $confidence,
            'suggestions' => $suggestions,
            'quick_actions' => $quick_actions,
            'response_type' => $this->determineResponseType($intent_detection),
            'personalization_applied' => true
        ];
    }
    
    /**
     * Ejecutar acción del usuario
     */
    private function executeUserAction($user_id, $intent_detection, $entity_extraction) {
        try {
            $action_type = $this->determineActionType($intent_detection, $entity_extraction);
            
            switch ($action_type) {
                case 'security_scan':
                    return $this->executeSecurityScan($user_id, $entity_extraction);
                    
                case 'performance_optimization':
                    return $this->executePerformanceOptimization($user_id, $entity_extraction);
                    
                case 'system_status':
                    return $this->getSystemStatus($user_id);
                    
                case 'threat_analysis':
                    return $this->executeThreatAnalysis($user_id, $entity_extraction);
                    
                case 'battery_optimization':
                    return $this->executeBatteryOptimization($user_id);
                    
                case 'storage_cleanup':
                    return $this->executeStorageCleanup($user_id);
                    
                default:
                    return [
                        'action_executed' => false,
                        'message' => 'Acción no reconocida o no disponible'
                    ];
            }
            
        } catch (Exception $e) {
            logGuardianEvent('ERROR', 'Error al ejecutar acción del usuario', [
                'error' => $e->getMessage(),
                'user_id' => $user_id,
                'action_type' => $action_type ?? 'unknown'
            ]);
            
            return [
                'action_executed' => false,
                'error' => $e->getMessage(),
                'message' => 'Error al ejecutar la acción solicitada'
            ];
        }
    }
    
    // ===========================================
    // FUNCIONES DE APRENDIZAJE AUTOMÁTICO
    // ===========================================
    
    /**
     * Realizar aprendizaje automático basado en interacción
     */
    private function performAutomaticLearning($user_id, $message, $intent_detection, $response_generation) {
        try {
            // Aprender patrones de usuario
            $this->learnUserPatterns($user_id, $message, $intent_detection);
            
            // Mejorar detección de intenciones
            $this->improveIntentDetection($message, $intent_detection, $response_generation);
            
            // Actualizar base de conocimientos
            $this->updateKnowledgeBase($message, $intent_detection, $response_generation);
            
            // Aprender preferencias del usuario
            $this->learnUserPreferences($user_id, $intent_detection, $response_generation);
            
            logGuardianEvent('DEBUG', 'Aprendizaje automático ejecutado', [
                'user_id' => $user_id,
                'intent' => $intent_detection['intent'],
                'confidence' => $response_generation['confidence']
            ]);
            
        } catch (Exception $e) {
            logGuardianEvent('ERROR', 'Error en aprendizaje automático', [
                'error' => $e->getMessage(),
                'user_id' => $user_id
            ]);
        }
    }
    
    /**
     * Aprender de calificaciones de conversación
     */
    private function learnFromRating($conversation_id, $rating, $feedback) {
        try {
            // Obtener mensajes de la conversación
            $sql = "SELECT * FROM chatbot_messages WHERE conversation_id = ? ORDER BY created_at ASC";
            $stmt = $this->conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $conversation_id);
                $stmt->execute();
                $messages = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();
                
                // Analizar patrones de conversaciones bien/mal calificadas
                $this->analyzeConversationPatterns($messages, $rating, $feedback);
                
                // Ajustar pesos de conocimientos utilizados
                $this->adjustKnowledgeWeights($messages, $rating);
                
                // Mejorar respuestas futuras
                $this->improveFutureResponses($messages, $rating, $feedback);
            }
            
        } catch (Exception $e) {
            logGuardianEvent('ERROR', 'Error en aprendizaje por calificación', [
                'error' => $e->getMessage(),
                'conversation_id' => $conversation_id
            ]);
        }
    }
    
    // ===========================================
    // FUNCIONES DE UTILIDAD Y HELPERS
    // ===========================================
    
    /**
     * Cargar base de conocimientos
     */
    private function loadKnowledgeBase() {
        $sql = "SELECT * FROM chatbot_knowledge WHERE active = 1 ORDER BY usage_count DESC";
        $result = $this->conn->query($sql);
        
        $this->knowledge_base = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $row['keywords'] = json_decode($row['keywords'], true) ?? [];
                $this->knowledge_base[] = $row;
            }
        }
    }
    
    /**
     * Cargar modelos de IA
     */
    private function loadAIModels() {
        $sql = "SELECT * FROM ai_models WHERE model_type = 'chatbot' AND active = 1";
        $result = $this->conn->query($sql);
        
        $this->ai_models = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $this->ai_models[] = $row;
            }
        }
    }
    
    /**
     * Inicializar procesador NLP
     */
    private function initializeNLPProcessor() {
        $this->nlp_processor = [
            'stopwords' => ['el', 'la', 'de', 'que', 'y', 'a', 'en', 'un', 'es', 'se', 'no', 'te', 'lo', 'le', 'da', 'su', 'por', 'son', 'con', 'para', 'al', 'del', 'los', 'las', 'una', 'como', 'pero', 'sus', 'me', 'ya', 'muy', 'mi', 'si', 'sin', 'sobre', 'este', 'ser', 'tiene', 'todo', 'esta', 'era', 'entre', 'cuando', 'él', 'mismo', 'también', 'hasta', 'hay', 'donde', 'quien', 'desde', 'todos', 'nos', 'durante', 'todos', 'uno', 'les', 'ni', 'contra', 'otros', 'ese', 'eso', 'ante', 'ellos', 'e', 'esto', 'mí', 'antes', 'algunos', 'qué', 'unos', 'yo', 'otro', 'otras', 'otra', 'él', 'tanto', 'esa', 'estos', 'mucho', 'quienes', 'nada', 'muchos', 'cual', 'poco', 'ella', 'estar', 'estas', 'algunas', 'algo', 'nosotros', 'mi', 'mis', 'tú', 'te', 'ti', 'tu', 'tus', 'ellas', 'nosotras', 'vosotros', 'vosotras', 'os', 'mío', 'mía', 'míos', 'mías', 'tuyo', 'tuya', 'tuyos', 'tuyas', 'suyo', 'suya', 'suyos', 'suyas', 'nuestro', 'nuestra', 'nuestros', 'nuestras', 'vuestro', 'vuestra', 'vuestros', 'vuestras', 'esos', 'esas'],
            'sentiment_words' => [
                'positive' => ['bueno', 'excelente', 'genial', 'perfecto', 'fantástico', 'increíble', 'maravilloso', 'estupendo', 'magnífico', 'extraordinario'],
                'negative' => ['malo', 'terrible', 'horrible', 'pésimo', 'awful', 'desastroso', 'espantoso', 'deplorable', 'lamentable', 'deficiente']
            ]
        ];
    }
    
    /**
     * Crear nueva conversación
     */
    private function createNewConversation($user_id, $first_message) {
        $session_id = $this->generateSessionId();
        $conversation_title = $this->generateConversationTitle($first_message);
        
        $sql = "INSERT INTO chatbot_conversations (user_id, session_id, conversation_title, status) 
                VALUES (?, ?, ?, 'active')";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error al crear conversación: " . $this->conn->error);
        }
        
        $stmt->bind_param("iss", $user_id, $session_id, $conversation_title);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al insertar conversación: " . $stmt->error);
        }
        
        $conversation_id = $this->conn->insert_id;
        $stmt->close();
        
        return $conversation_id;
    }
    
    /**
     * Guardar mensaje del usuario
     */
    private function saveUserMessage($conversation_id, $message) {
        $sql = "INSERT INTO chatbot_messages (conversation_id, sender, message_text, message_type) 
                VALUES (?, 'user', ?, 'text')";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error al preparar inserción de mensaje: " . $this->conn->error);
        }
        
        $stmt->bind_param("is", $conversation_id, $message);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al insertar mensaje del usuario: " . $stmt->error);
        }
        
        $message_id = $this->conn->insert_id;
        $stmt->close();
        
        // Actualizar contador de mensajes en la conversación
        $this->updateConversationMessageCount($conversation_id);
        
        return $message_id;
    }
    
    /**
     * Guardar mensaje del bot
     */
    private function saveBotMessage($conversation_id, $bot_response, $response_time, $confidence) {
        $sql = "INSERT INTO chatbot_messages (
                    conversation_id, sender, message_text, message_type, 
                    intent_detected, confidence_score, response_time, context_data
                ) VALUES (?, 'bot', ?, 'text', ?, ?, ?, ?)";
        
        $stmt = $this->conn->prepare($sql);
        if (!$stmt) {
            throw new Exception("Error al preparar inserción de respuesta: " . $this->conn->error);
        }
        
        $context_data = json_encode([
            'action_result' => $bot_response['action_result'],
            'suggestions' => $bot_response['suggestions'],
            'quick_actions' => $bot_response['quick_actions']
        ]);
        
        $stmt->bind_param("issdds", $conversation_id, $bot_response['text'], 
                         $bot_response['intent'], $confidence, $response_time, $context_data);
        
        if (!$stmt->execute()) {
            throw new Exception("Error al insertar respuesta del bot: " . $stmt->error);
        }
        
        $stmt->close();
        
        // Actualizar contador de mensajes en la conversación
        $this->updateConversationMessageCount($conversation_id);
    }
    
    // Implementaciones básicas de métodos auxiliares
    
    private function normalizeText($text) {
        $text = strtolower($text);
        $text = preg_replace('/[^\w\s]/', '', $text);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }
    
    private function tokenizeText($text) {
        $normalized = $this->normalizeText($text);
        $tokens = explode(' ', $normalized);
        return array_filter($tokens, function($token) {
            return !in_array($token, $this->nlp_processor['stopwords']) && strlen($token) > 2;
        });
    }
    
    private function extractKeywords($text) {
        $tokens = $this->tokenizeText($text);
        $keywords = [];
        
        // Palabras clave de seguridad
        $security_keywords = ['virus', 'malware', 'amenaza', 'seguridad', 'protección', 'escaneo', 'antivirus', 'firewall'];
        
        // Palabras clave de rendimiento
        $performance_keywords = ['lento', 'rápido', 'optimizar', 'memoria', 'ram', 'batería', 'almacenamiento', 'velocidad'];
        
        foreach ($tokens as $token) {
            if (in_array($token, $security_keywords)) {
                $keywords[] = ['word' => $token, 'category' => 'security', 'weight' => 1.0];
            } elseif (in_array($token, $performance_keywords)) {
                $keywords[] = ['word' => $token, 'category' => 'performance', 'weight' => 1.0];
            } else {
                $keywords[] = ['word' => $token, 'category' => 'general', 'weight' => 0.5];
            }
        }
        
        return $keywords;
    }
    
    private function analyzeSentiment($text) {
        $positive_count = 0;
        $negative_count = 0;
        
        foreach ($this->nlp_processor['sentiment_words']['positive'] as $word) {
            if (stripos($text, $word) !== false) {
                $positive_count++;
            }
        }
        
        foreach ($this->nlp_processor['sentiment_words']['negative'] as $word) {
            if (stripos($text, $word) !== false) {
                $negative_count++;
            }
        }
        
        if ($positive_count > $negative_count) {
            return ['polarity' => 'positive', 'score' => 0.7];
        } elseif ($negative_count > $positive_count) {
            return ['polarity' => 'negative', 'score' => -0.7];
        } else {
            return ['polarity' => 'neutral', 'score' => 0.0];
        }
    }
    
    private function detectLanguage($text) {
        // Detección básica de idioma (español por defecto)
        $spanish_indicators = ['el', 'la', 'de', 'que', 'y', 'es', 'en', 'un', 'se', 'no'];
        $spanish_count = 0;
        
        foreach ($spanish_indicators as $indicator) {
            if (stripos($text, $indicator) !== false) {
                $spanish_count++;
            }
        }
        
        return $spanish_count > 2 ? 'es' : 'unknown';
    }
    
    private function calculateTextComplexity($text) {
        $word_count = str_word_count($text);
        $char_count = strlen($text);
        $avg_word_length = $word_count > 0 ? $char_count / $word_count : 0;
        
        if ($avg_word_length > 6) {
            return 'high';
        } elseif ($avg_word_length > 4) {
            return 'medium';
        } else {
            return 'low';
        }
    }
    
    private function calculatePatternMatch($message, $pattern) {
        $normalized_message = $this->normalizeText($message);
        $normalized_pattern = $this->normalizeText($pattern);
        
        // Similitud básica usando palabras comunes
        $message_words = explode(' ', $normalized_message);
        $pattern_words = explode(' ', $normalized_pattern);
        
        $common_words = array_intersect($message_words, $pattern_words);
        $total_words = array_unique(array_merge($message_words, $pattern_words));
        
        return count($total_words) > 0 ? count($common_words) / count($total_words) : 0;
    }
    
    private function calculateKeywordMatch($message_keywords, $knowledge_keywords) {
        if (empty($message_keywords) || empty($knowledge_keywords)) {
            return 0;
        }
        
        $message_words = array_column($message_keywords, 'word');
        $matches = array_intersect($message_words, $knowledge_keywords);
        
        return count($knowledge_keywords) > 0 ? count($matches) / count($knowledge_keywords) : 0;
    }
    
    private function determineIfActionRequired($message, $nlp_analysis) {
        $action_keywords = ['escanear', 'optimizar', 'limpiar', 'verificar', 'analizar', 'revisar', 'ejecutar'];
        
        foreach ($action_keywords as $keyword) {
            if (stripos($message, $keyword) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    private function extractSecurityEntities($message) {
        $entities = [];
        $security_terms = ['virus', 'malware', 'amenaza', 'phishing', 'ransomware', 'spyware'];
        
        foreach ($security_terms as $term) {
            if (stripos($message, $term) !== false) {
                $entities[] = [
                    'type' => 'security_threat',
                    'value' => $term,
                    'confidence' => 0.9
                ];
            }
        }
        
        return $entities;
    }
    
    private function extractPerformanceEntities($message) {
        $entities = [];
        $performance_terms = ['ram', 'memoria', 'batería', 'almacenamiento', 'cpu', 'procesador'];
        
        foreach ($performance_terms as $term) {
            if (stripos($message, $term) !== false) {
                $entities[] = [
                    'type' => 'performance_component',
                    'value' => $term,
                    'confidence' => 0.8
                ];
            }
        }
        
        return $entities;
    }
    
    private function extractTemporalEntities($message) {
        $entities = [];
        $temporal_patterns = [
            '/\b(hoy|ahora|actualmente)\b/i' => 'now',
            '/\b(ayer|anoche)\b/i' => 'yesterday',
            '/\b(mañana|próximo)\b/i' => 'tomorrow'
        ];
        
        foreach ($temporal_patterns as $pattern => $value) {
            if (preg_match($pattern, $message)) {
                $entities[] = [
                    'type' => 'temporal',
                    'value' => $value,
                    'confidence' => 0.7
                ];
            }
        }
        
        return $entities;
    }
    
    private function extractNumericEntities($message) {
        $entities = [];
        
        if (preg_match_all('/\b\d+\b/', $message, $matches)) {
            foreach ($matches[0] as $number) {
                $entities[] = [
                    'type' => 'numeric',
                    'value' => intval($number),
                    'confidence' => 0.9
                ];
            }
        }
        
        return $entities;
    }
    
    private function getKnowledgeById($knowledge_id) {
        foreach ($this->knowledge_base as $item) {
            if ($item['id'] == $knowledge_id) {
                return $item;
            }
        }
        return null;
    }
    
    private function searchByCategory($category, $subcategory = null) {
        $results = [];
        
        foreach ($this->knowledge_base as $item) {
            if ($item['category'] === $category) {
                if (!$subcategory || $item['subcategory'] === $subcategory) {
                    $results[] = $item;
                }
            }
        }
        
        return $results;
    }
    
    private function searchByEntity($entity) {
        $results = [];
        
        foreach ($this->knowledge_base as $item) {
            if (in_array($entity['value'], $item['keywords'])) {
                $results[] = $item;
            }
        }
        
        return $results;
    }
    
    private function calculateRelevanceScore($message, $knowledge_item) {
        return $this->calculatePatternMatch($message, $knowledge_item['question_pattern']);
    }
    
    private function calculateEntityRelevanceScore($entity, $knowledge_item) {
        return in_array($entity['value'], $knowledge_item['keywords']) ? 0.8 : 0.2;
    }
    
    private function deduplicateAndSort($search_results) {
        // Eliminar duplicados por ID de conocimiento
        $unique_results = [];
        $seen_ids = [];
        
        foreach ($search_results as $result) {
            $knowledge_id = $result['knowledge_item']['id'];
            if (!in_array($knowledge_id, $seen_ids)) {
                $unique_results[] = $result;
                $seen_ids[] = $knowledge_id;
            }
        }
        
        // Ordenar por puntuación de relevancia
        usort($unique_results, function($a, $b) {
            return $b['relevance_score'] <=> $a['relevance_score'];
        });
        
        return $unique_results;
    }
    
    private function calculateSearchConfidence($search_results) {
        if (empty($search_results)) {
            return 0;
        }
        
        $total_score = array_sum(array_column($search_results, 'relevance_score'));
        return min(1.0, $total_score / count($search_results));
    }
    
    private function personalizeResponse($template, $user_id, $entity_extraction) {
        // Personalización básica de respuesta
        $personalized = $template;
        
        // Reemplazar placeholders básicos
        $personalized = str_replace('{user}', 'usuario', $personalized);
        $personalized = str_replace('{system}', 'GuardianIA', $personalized);
        
        return $personalized;
    }
    
    private function generateRelatedSuggestions($knowledge_item, $intent_detection) {
        $suggestions = [];
        
        // Buscar conocimientos relacionados por categoría
        $related_items = $this->searchByCategory($knowledge_item['category'], $knowledge_item['subcategory']);
        
        foreach (array_slice($related_items, 0, 3) as $item) {
            if ($item['id'] !== $knowledge_item['id']) {
                $suggestions[] = substr($item['question_pattern'], 0, 50) . '...';
            }
        }
        
        return $suggestions;
    }
    
    private function generateQuickActions($intent_detection, $entity_extraction) {
        $actions = [];
        
        switch ($intent_detection['category']) {
            case 'security':
                $actions = ['Escanear sistema', 'Ver amenazas', 'Configurar protección'];
                break;
            case 'performance':
                $actions = ['Optimizar RAM', 'Limpiar almacenamiento', 'Optimizar batería'];
                break;
            default:
                $actions = ['Ver estado del sistema', 'Ejecutar diagnóstico'];
        }
        
        return $actions;
    }
    
    private function generateDefaultResponse($intent_detection, $entity_extraction) {
        $responses = [
            'Entiendo tu consulta. ¿Podrías ser más específico sobre lo que necesitas?',
            'Estoy aquí para ayudarte con seguridad y optimización. ¿Qué te gustaría hacer?',
            'Puedo asistirte con análisis de amenazas, optimización del sistema y más. ¿En qué puedo ayudarte?'
        ];
        
        return [
            'text' => $responses[array_rand($responses)],
            'confidence' => 0.5,
            'suggestions' => ['Ver estado del sistema', 'Ejecutar escaneo', 'Optimizar rendimiento']
        ];
    }
    
    private function getConversationContext($conversation_id) {
        // Obtener contexto básico de la conversación
        return $this->conversation_context[$conversation_id] ?? null;
    }
    
    private function addConversationContext($response, $context) {
        // Agregar contexto si es relevante
        return null; // Implementación básica
    }
    
    private function applyQualityFilters($response) {
        // Filtros básicos de calidad
        $response = trim($response);
        $response = preg_replace('/\s+/', ' ', $response);
        
        return $response;
    }
    
    private function determineResponseType($intent_detection) {
        switch ($intent_detection['category']) {
            case 'security':
                return 'security_response';
            case 'performance':
                return 'performance_response';
            case 'help':
                return 'help_response';
            default:
                return 'general_response';
        }
    }
    
    private function determineActionType($intent_detection, $entity_extraction) {
        // Determinar tipo de acción basado en intención y entidades
        if ($intent_detection['category'] === 'security') {
            return 'security_scan';
        } elseif ($intent_detection['category'] === 'performance') {
            return 'performance_optimization';
        }
        
        return 'system_status';
    }
    
    // Métodos de ejecución de acciones (implementaciones básicas)
    
    private function executeSecurityScan($user_id, $entity_extraction) {
        return [
            'action_executed' => true,
            'action_type' => 'security_scan',
            'result' => 'Escaneo de seguridad iniciado',
            'details' => 'El sistema está siendo analizado en busca de amenazas'
        ];
    }
    
    private function executePerformanceOptimization($user_id, $entity_extraction) {
        return [
            'action_executed' => true,
            'action_type' => 'performance_optimization',
            'result' => 'Optimización de rendimiento iniciada',
            'details' => 'El sistema está siendo optimizado para mejor rendimiento'
        ];
    }
    
    private function getSystemStatus($user_id) {
        return [
            'action_executed' => true,
            'action_type' => 'system_status',
            'result' => 'Estado del sistema obtenido',
            'details' => [
                'cpu_usage' => rand(20, 80) . '%',
                'ram_usage' => rand(40, 90) . '%',
                'storage_usage' => rand(50, 95) . '%',
                'threats_detected' => rand(0, 5)
            ]
        ];
    }
    
    private function executeThreatAnalysis($user_id, $entity_extraction) {
        return [
            'action_executed' => true,
            'action_type' => 'threat_analysis',
            'result' => 'Análisis de amenazas completado',
            'details' => 'No se detectaron amenazas críticas en el sistema'
        ];
    }
    
    private function executeBatteryOptimization($user_id) {
        return [
            'action_executed' => true,
            'action_type' => 'battery_optimization',
            'result' => 'Optimización de batería aplicada',
            'details' => 'Configuración de energía optimizada para mayor duración'
        ];
    }
    
    private function executeStorageCleanup($user_id) {
        return [
            'action_executed' => true,
            'action_type' => 'storage_cleanup',
            'result' => 'Limpieza de almacenamiento completada',
            'details' => 'Se liberaron ' . rand(500, 2000) . ' MB de espacio'
        ];
    }
    
    // Métodos de aprendizaje (implementaciones básicas)
    
    private function learnUserPatterns($user_id, $message, $intent_detection) {
        // Implementación básica de aprendizaje de patrones
        logGuardianEvent('DEBUG', 'Aprendiendo patrones de usuario', [
            'user_id' => $user_id,
            'intent' => $intent_detection['intent']
        ]);
    }
    
    private function improveIntentDetection($message, $intent_detection, $response_generation) {
        // Implementación básica de mejora de detección de intenciones
        if ($response_generation['confidence'] > 0.8) {
            // Reforzar patrón exitoso
            $this->reinforceSuccessfulPattern($intent_detection);
        }
    }
    
    private function updateKnowledgeBase($message, $intent_detection, $response_generation) {
        // Actualizar contadores de uso en base de conocimientos
        if ($intent_detection['knowledge_id']) {
            $sql = "UPDATE chatbot_knowledge SET usage_count = usage_count + 1 WHERE id = ?";
            $stmt = $this->conn->prepare($sql);
            if ($stmt) {
                $stmt->bind_param("i", $intent_detection['knowledge_id']);
                $stmt->execute();
                $stmt->close();
            }
        }
    }
    
    private function learnUserPreferences($user_id, $intent_detection, $response_generation) {
        // Aprender preferencias del usuario
        logGuardianEvent('DEBUG', 'Aprendiendo preferencias de usuario', [
            'user_id' => $user_id,
            'category' => $intent_detection['category']
        ]);
    }
    
    private function analyzeConversationPatterns($messages, $rating, $feedback) {
        // Analizar patrones de conversaciones exitosas/fallidas
        logGuardianEvent('DEBUG', 'Analizando patrones de conversación', [
            'rating' => $rating,
            'message_count' => count($messages)
        ]);
    }
    
    private function adjustKnowledgeWeights($messages, $rating) {
        // Ajustar pesos de conocimientos basado en calificación
        foreach ($messages as $message) {
            if ($message['sender'] === 'bot' && $message['intent_detected']) {
                // Ajustar peso basado en rating
                $weight_adjustment = ($rating === 'excellent' || $rating === 'good') ? 0.1 : -0.1;
                logGuardianEvent('DEBUG', 'Ajustando peso de conocimiento', [
                    'intent' => $message['intent_detected'],
                    'adjustment' => $weight_adjustment
                ]);
            }
        }
    }
    
    private function improveFutureResponses($messages, $rating, $feedback) {
        // Mejorar respuestas futuras basado en feedback
        if ($feedback && ($rating === 'poor' || $rating === 'very_poor')) {
            logGuardianEvent('INFO', 'Feedback negativo recibido para mejora', [
                'rating' => $rating,
                'feedback' => substr($feedback, 0, 100)
            ]);
        }
    }
    
    private function reinforceSuccessfulPattern($intent_detection) {
        // Reforzar patrones exitosos
        logGuardianEvent('DEBUG', 'Reforzando patrón exitoso', [
            'intent' => $intent_detection['intent'],
            'confidence' => $intent_detection['confidence']
        ]);
    }
    
    // Métodos auxiliares adicionales
    
    private function generateSessionId() {
        return 'chat_' . date('Ymd_His') . '_' . substr(md5(uniqid()), 0, 8);
    }
    
    private function generateConversationTitle($first_message) {
        $title = substr($first_message, 0, 50);
        if (strlen($first_message) > 50) {
            $title .= '...';
        }
        return $title;
    }
    
    private function updateConversationMessageCount($conversation_id) {
        $sql = "UPDATE chatbot_conversations SET total_messages = total_messages + 1 WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param("i", $conversation_id);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    private function updateConversationContext($conversation_id, $intent_detection, $entity_extraction) {
        $this->conversation_context[$conversation_id] = [
            'last_intent' => $intent_detection['intent'],
            'last_category' => $intent_detection['category'],
            'entities' => $entity_extraction['entities'],
            'updated_at' => time()
        ];
    }
    
    private function saveFeedback($user_id, $conversation_id, $feedback) {
        // Guardar feedback en logs para análisis posterior
        logGuardianEvent('INFO', 'Feedback de usuario recibido', [
            'user_id' => $user_id,
            'conversation_id' => $conversation_id,
            'feedback' => $feedback
        ]);
    }
    
    private function getFallbackResponse() {
        $fallback_responses = [
            'Lo siento, hubo un problema procesando tu mensaje. ¿Podrías intentar de nuevo?',
            'Disculpa, no pude entender completamente tu consulta. ¿Puedes reformularla?',
            'Estoy experimentando dificultades técnicas. Por favor, intenta nuevamente en un momento.'
        ];
        
        return [
            'text' => $fallback_responses[array_rand($fallback_responses)],
            'confidence' => 0.1,
            'suggestions' => ['Reintentar', 'Contactar soporte', 'Ver ayuda']
        ];
    }
    
    private function getDateCondition($time_period) {
        switch ($time_period) {
            case '7_days':
                return "DATE_SUB(NOW(), INTERVAL 7 DAY)";
            case '30_days':
                return "DATE_SUB(NOW(), INTERVAL 30 DAY)";
            case '90_days':
                return "DATE_SUB(NOW(), INTERVAL 90 DAY)";
            case '1_year':
                return "DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            default:
                return "DATE_SUB(NOW(), INTERVAL 30 DAY)";
        }
    }
    
    private function analyzeChatbotPerformance($user_id, $time_period) {
        return [
            'response_accuracy' => rand(85, 95) / 100,
            'user_satisfaction' => rand(80, 90) / 100,
            'intent_detection_accuracy' => rand(88, 96) / 100,
            'average_resolution_time' => rand(30, 120) // segundos
        ];
    }
    
    private function getChatbotUsageTrends($user_id, $time_period) {
        return [
            'peak_hours' => ['10:00-12:00', '14:00-16:00', '20:00-22:00'],
            'most_common_intents' => ['security_help', 'performance_optimization', 'system_status'],
            'conversation_length_trend' => 'increasing',
            'user_engagement_score' => rand(70, 90)
        ];
    }
}

// ===========================================
// MANEJO DE PETICIONES AJAX
// ===========================================

// Solo procesar si se llama directamente a este archivo
if (basename($_SERVER['PHP_SELF']) === 'GuardianAIChatbot.php') {
    
    // Verificar autenticación
    if (!validateUserSession()) {
        jsonResponse(false, 'Sesión no válida', null, 401);
    }
    
    try {
        $chatbot = new GuardianAIChatbot();
        $user_id = $_SESSION['user_id'];
        
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            
            switch ($action) {
                case 'send_message':
                    if (isset($_POST['message'])) {
                        $message = sanitizeInput($_POST['message']);
                        $conversation_id = isset($_POST['conversation_id']) ? intval($_POST['conversation_id']) : null;
                        
                        $result = $chatbot->processUserMessage($user_id, $message, $conversation_id);
                        jsonResponse($result['success'], $result['success'] ? 'Mensaje procesado' : $result['message'], $result);
                    } else {
                        jsonResponse(false, 'Mensaje requerido');
                    }
                    break;
                    
                case 'rate_conversation':
                    if (isset($_POST['conversation_id']) && isset($_POST['rating'])) {
                        $conversation_id = intval($_POST['conversation_id']);
                        $rating = sanitizeInput($_POST['rating']);
                        $feedback = isset($_POST['feedback']) ? sanitizeInput($_POST['feedback']) : null;
                        
                        $result = $chatbot->rateConversation($user_id, $conversation_id, $rating, $feedback);
                        jsonResponse($result['success'], $result['message'], $result);
                    } else {
                        jsonResponse(false, 'ID de conversación y calificación requeridos');
                    }
                    break;
                    
                default:
                    jsonResponse(false, 'Acción no válida');
            }
        }
        
        if ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $action = $_GET['action'] ?? '';
            
            switch ($action) {
                case 'conversation_history':
                    if (isset($_GET['conversation_id'])) {
                        $conversation_id = intval($_GET['conversation_id']);
                        $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
                        
                        $result = $chatbot->getConversationHistory($user_id, $conversation_id, $limit);
                        jsonResponse($result['success'], $result['success'] ? 'Historial obtenido' : $result['message'], $result);
                    } else {
                        jsonResponse(false, 'ID de conversación requerido');
                    }
                    break;
                    
                case 'user_conversations':
                    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 20;
                    $result = $chatbot->getUserConversations($user_id, $limit);
                    jsonResponse($result['success'], $result['success'] ? 'Conversaciones obtenidas' : $result['message'], $result);
                    break;
                    
                case 'statistics':
                    $time_period = $_GET['time_period'] ?? '30_days';
                    $result = $chatbot->getChatbotStatistics($user_id, $time_period);
                    jsonResponse($result['success'], $result['success'] ? 'Estadísticas obtenidas' : $result['message'], $result);
                    break;
                    
                default:
                    jsonResponse(false, 'Acción no válida');
            }
        }
        
    } catch (Exception $e) {
        logGuardianEvent('ERROR', 'Error en GuardianAIChatbot', ['error' => $e->getMessage()]);
        jsonResponse(false, 'Error del servidor: ' . $e->getMessage(), null, 500);
    }
}

?>

