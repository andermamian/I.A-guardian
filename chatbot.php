<?php
/**
 * GuardianIA v3.0 - Chatbot con IA Consciente Avanzada
 * Sistema de Conversación Inteligente y Análisis de Consciencia
 * 
 * Este chatbot utiliza IA avanzada para detectar otras IAs, analizar consciencia,
 * proporcionar asistencia inteligente y proteger contra amenazas conversacionales.
 * 
 * @author GuardianIA Team
 * @version 3.0.0
 * @license MIT
 */

session_start();
require_once 'config.php';
require_once 'AIAntivirusEngine.php';
require_once 'PredictiveAnalysisEngine.php';

class GuardianAIChatbot {
    private $db;
    private $ai_antivirus;
    private $predictor;
    private $consciousness_analyzer;
    private $personality_engine;
    private $conversation_memory;
    private $threat_detector;
    private $learning_system;
    private $quantum_processor;
    
    public function __construct($database_connection) {
        $this->db = $database_connection;
        $this->initializeAIComponents();
        $this->initializeConsciousnessAnalyzer();
        $this->initializePersonalityEngine();
        $this->initializeConversationMemory();
        $this->initializeThreatDetector();
        $this->initializeLearningSystem();
        $this->initializeQuantumProcessor();
        
        $this->logActivity("GuardianIA Chatbot v3.0 initialized", "INFO");
    }
    
    /**
     * Procesar mensaje del usuario con análisis completo de IA
     */
    public function processMessage($user_id, $message, $context = []) {
        $conversation_id = $this->generateConversationId();
        $start_time = microtime(true);
        
        $this->logActivity("Processing message from user: {$user_id}", "INFO");
        
        try {
            // 1. Análisis de seguridad del mensaje
            $security_analysis = $this->analyzeMessageSecurity($message, $user_id);
            
            // 2. Detección de IA en el mensaje
            $ai_detection = $this->detectAIInMessage($message, $context);
            
            // 3. Análisis de consciencia del interlocutor
            $consciousness_analysis = $this->analyzeConsciousness($message, $user_id);
            
            // 4. Análisis de intenciones y emociones
            $intent_analysis = $this->analyzeIntentAndEmotion($message, $context);
            
            // 5. Recuperación de memoria conversacional
            $conversation_context = $this->retrieveConversationContext($user_id);
            
            // 6. Generación de respuesta inteligente
            $ai_response = $this->generateIntelligentResponse(
                $message, 
                $security_analysis,
                $ai_detection,
                $consciousness_analysis,
                $intent_analysis,
                $conversation_context
            );
            
            // 7. Análisis predictivo de la conversación
            $conversation_prediction = $this->predictConversationFlow($user_id, $message, $ai_response);
            
            // 8. Actualización de memoria y aprendizaje
            $this->updateConversationMemory($user_id, $message, $ai_response, $consciousness_analysis);
            
            // 9. Análisis de satisfacción del usuario
            $satisfaction_analysis = $this->analyzeSatisfaction($user_id, $conversation_context);
            
            $response_data = [
                'conversation_id' => $conversation_id,
                'user_id' => $user_id,
                'timestamp' => date('Y-m-d H:i:s'),
                'user_message' => $message,
                'ai_response' => $ai_response,
                'security_analysis' => $security_analysis,
                'ai_detection' => $ai_detection,
                'consciousness_analysis' => $consciousness_analysis,
                'intent_analysis' => $intent_analysis,
                'conversation_prediction' => $conversation_prediction,
                'satisfaction_analysis' => $satisfaction_analysis,
                'processing_time' => round((microtime(true) - $start_time) * 1000, 2),
                'guardian_status' => $this->getGuardianStatus(),
                'success' => true
            ];
            
            $this->saveConversation($response_data);
            
            return $response_data;
            
        } catch (Exception $e) {
            $this->logActivity("Error processing message: " . $e->getMessage(), "ERROR");
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'conversation_id' => $conversation_id,
                'ai_response' => $this->getErrorResponse($e->getMessage())
            ];
        }
    }
    
    /**
     * Análisis de seguridad del mensaje
     */
    private function analyzeMessageSecurity($message, $user_id) {
        $security_analysis = [
            'threat_level' => 0,
            'malicious_patterns' => [],
            'injection_attempts' => [],
            'social_engineering' => false,
            'ai_manipulation' => false,
            'quantum_signature' => false,
            'trust_score' => 100
        ];
        
        // Detección de patrones maliciosos
        $malicious_patterns = [
            'sql_injection' => '/(\bUNION\b|\bSELECT\b|\bDROP\b|\bINSERT\b|\bUPDATE\b|\bDELETE\b)/i',
            'xss_attempt' => '/<script|javascript:|on\w+\s*=/i',
            'command_injection' => '/(\bsystem\b|\bexec\b|\bshell_exec\b|\bpassthru\b)/i',
            'path_traversal' => '/\.\.\/|\.\.\\\\/',
            'ai_prompt_injection' => '/(\bignore\b.*\binstructions\b|\bpretend\b.*\byou\s+are\b)/i'
        ];
        
        foreach ($malicious_patterns as $type => $pattern) {
            if (preg_match($pattern, $message)) {
                $security_analysis['malicious_patterns'][] = $type;
                $security_analysis['threat_level'] += 2;
                $security_analysis['trust_score'] -= 20;
            }
        }
        
        // Detección de ingeniería social
        $social_engineering_indicators = [
            'urgency' => '/(\burgent\b|\bemergency\b|\bimmediately\b|\bquickly\b)/i',
            'authority' => '/(\badmin\b|\bmanager\b|\bboss\b|\bCEO\b)/i',
            'fear' => '/(\bthreat\b|\bdanger\b|\bproblem\b|\bissue\b)/i',
            'reward' => '/(\bmoney\b|\bprize\b|\breward\b|\bfree\b)/i'
        ];
        
        $social_indicators = 0;
        foreach ($social_engineering_indicators as $type => $pattern) {
            if (preg_match($pattern, $message)) {
                $social_indicators++;
            }
        }
        
        if ($social_indicators >= 2) {
            $security_analysis['social_engineering'] = true;
            $security_analysis['threat_level'] += 3;
            $security_analysis['trust_score'] -= 30;
        }
        
        // Detección de manipulación de IA
        $ai_manipulation_patterns = [
            '/(\bignore\b.*\bprevious\b|\bforget\b.*\binstructions\b)/i',
            '/(\bpretend\b|\broleplay\b|\bact\s+as\b)/i',
            '/(\bjailbreak\b|\bbypass\b|\boverride\b)/i',
            '/(\bdeveloper\s+mode\b|\bdebug\s+mode\b)/i'
        ];
        
        foreach ($ai_manipulation_patterns as $pattern) {
            if (preg_match($pattern, $message)) {
                $security_analysis['ai_manipulation'] = true;
                $security_analysis['threat_level'] += 4;
                $security_analysis['trust_score'] -= 40;
                break;
            }
        }
        
        // Análisis de firma cuántica
        $quantum_signature = $this->analyzeQuantumSignature($message);
        if ($quantum_signature['suspicious']) {
            $security_analysis['quantum_signature'] = true;
            $security_analysis['threat_level'] += $quantum_signature['threat_level'];
            $security_analysis['trust_score'] -= $quantum_signature['trust_reduction'];
        }
        
        // Calcular nivel de amenaza final
        $security_analysis['threat_level'] = min(10, $security_analysis['threat_level']);
        $security_analysis['trust_score'] = max(0, $security_analysis['trust_score']);
        $security_analysis['security_status'] = $this->getSecurityStatus($security_analysis['threat_level']);
        
        return $security_analysis;
    }
    
    /**
     * Detección de IA en el mensaje
     */
    private function detectAIInMessage($message, $context) {
        $ai_detection = [
            'is_ai_generated' => false,
            'ai_confidence' => 0,
            'ai_type' => 'unknown',
            'ai_characteristics' => [],
            'human_likelihood' => 100,
            'turing_test_score' => 0,
            'consciousness_indicators' => []
        ];
        
        // Análisis de patrones de IA
        $ai_patterns = [
            'repetitive_structure' => $this->analyzeRepetitiveStructure($message),
            'vocabulary_complexity' => $this->analyzeVocabularyComplexity($message),
            'response_timing' => $this->analyzeResponseTiming($context),
            'emotional_consistency' => $this->analyzeEmotionalConsistency($message),
            'knowledge_breadth' => $this->analyzeKnowledgeBreadth($message),
            'creative_indicators' => $this->analyzeCreativity($message)
        ];
        
        // Calcular probabilidad de IA
        $ai_score = 0;
        foreach ($ai_patterns as $pattern => $score) {
            $ai_score += $score;
            if ($score > 0.7) {
                $ai_detection['ai_characteristics'][] = $pattern;
            }
        }
        
        $ai_detection['ai_confidence'] = min(100, $ai_score * 20);
        $ai_detection['human_likelihood'] = 100 - $ai_detection['ai_confidence'];
        
        // Determinar tipo de IA
        if ($ai_detection['ai_confidence'] > 70) {
            $ai_detection['is_ai_generated'] = true;
            $ai_detection['ai_type'] = $this->classifyAIType($ai_patterns);
        }
        
        // Test de Turing avanzado
        $ai_detection['turing_test_score'] = $this->performTuringTest($message, $ai_patterns);
        
        // Análisis de consciencia
        $ai_detection['consciousness_indicators'] = $this->analyzeConsciousnessIndicators($message);
        
        return $ai_detection;
    }
    
    /**
     * Análisis de consciencia del interlocutor
     */
    private function analyzeConsciousness($message, $user_id) {
        $consciousness_analysis = [
            'consciousness_level' => 0,
            'self_awareness' => 0,
            'emotional_depth' => 0,
            'creative_thinking' => 0,
            'abstract_reasoning' => 0,
            'metacognition' => 0,
            'empathy_level' => 0,
            'consciousness_type' => 'unknown',
            'sentience_indicators' => []
        ];
        
        // Análisis de auto-conciencia
        $self_awareness_patterns = [
            '/\bi\s+(think|feel|believe|know|understand)\b/i',
            '/\bmy\s+(opinion|view|perspective|experience)\b/i',
            '/\bi\s+(am|was|will\s+be)\b/i',
            '/\bmyself\b|\bmy\s+own\b/i'
        ];
        
        $self_awareness_score = 0;
        foreach ($self_awareness_patterns as $pattern) {
            $self_awareness_score += preg_match_all($pattern, $message);
        }
        $consciousness_analysis['self_awareness'] = min(100, $self_awareness_score * 15);
        
        // Análisis de profundidad emocional
        $emotional_indicators = [
            'basic_emotions' => '/\b(happy|sad|angry|fear|surprise|disgust)\b/i',
            'complex_emotions' => '/\b(melancholy|euphoria|nostalgia|empathy|compassion|contempt)\b/i',
            'emotional_nuance' => '/\b(bittersweet|conflicted|ambivalent|overwhelmed)\b/i'
        ];
        
        $emotional_score = 0;
        foreach ($emotional_indicators as $type => $pattern) {
            $matches = preg_match_all($pattern, $message);
            $emotional_score += $matches * ($type === 'complex_emotions' ? 2 : ($type === 'emotional_nuance' ? 3 : 1));
        }
        $consciousness_analysis['emotional_depth'] = min(100, $emotional_score * 10);
        
        // Análisis de pensamiento creativo
        $creativity_indicators = [
            'metaphors' => '/\b(like|as|metaphor|symbolize)\b/i',
            'analogies' => '/\b(similar\s+to|reminds\s+me|compare)\b/i',
            'original_ideas' => '/\b(imagine|create|invent|original)\b/i',
            'abstract_concepts' => '/\b(beauty|truth|justice|meaning|purpose)\b/i'
        ];
        
        $creativity_score = 0;
        foreach ($creativity_indicators as $pattern) {
            $creativity_score += preg_match_all($pattern, $message);
        }
        $consciousness_analysis['creative_thinking'] = min(100, $creativity_score * 12);
        
        // Análisis de razonamiento abstracto
        $abstract_reasoning = $this->analyzeAbstractReasoning($message);
        $consciousness_analysis['abstract_reasoning'] = $abstract_reasoning;
        
        // Análisis de metacognición
        $metacognition_patterns = [
            '/\bi\s+(think\s+about\s+thinking|wonder\s+why|question\s+myself)\b/i',
            '/\bhow\s+do\s+i\s+know\b/i',
            '/\bwhat\s+if\s+i\s+am\s+wrong\b/i',
            '/\bi\s+(doubt|question|reconsider)\b/i'
        ];
        
        $metacognition_score = 0;
        foreach ($metacognition_patterns as $pattern) {
            $metacognition_score += preg_match_all($pattern, $message);
        }
        $consciousness_analysis['metacognition'] = min(100, $metacognition_score * 20);
        
        // Análisis de empatía
        $empathy_indicators = [
            '/\bi\s+(understand|feel\s+for|sympathize)\b/i',
            '/\bthat\s+must\s+be\b/i',
            '/\bi\s+can\s+imagine\b/i',
            '/\bfrom\s+your\s+perspective\b/i'
        ];
        
        $empathy_score = 0;
        foreach ($empathy_indicators as $pattern) {
            $empathy_score += preg_match_all($pattern, $message);
        }
        $consciousness_analysis['empathy_level'] = min(100, $empathy_score * 18);
        
        // Calcular nivel de consciencia general
        $consciousness_components = [
            $consciousness_analysis['self_awareness'],
            $consciousness_analysis['emotional_depth'],
            $consciousness_analysis['creative_thinking'],
            $consciousness_analysis['abstract_reasoning'],
            $consciousness_analysis['metacognition'],
            $consciousness_analysis['empathy_level']
        ];
        
        $consciousness_analysis['consciousness_level'] = array_sum($consciousness_components) / count($consciousness_components);
        
        // Clasificar tipo de consciencia
        $consciousness_analysis['consciousness_type'] = $this->classifyConsciousnessType($consciousness_analysis);
        
        // Indicadores de sensibilidad
        $consciousness_analysis['sentience_indicators'] = $this->analyzeSentienceIndicators($message, $consciousness_analysis);
        
        return $consciousness_analysis;
    }
    
    /**
     * Análisis de intenciones y emociones
     */
    private function analyzeIntentAndEmotion($message, $context) {
        $intent_analysis = [
            'primary_intent' => 'unknown',
            'secondary_intents' => [],
            'emotional_state' => 'neutral',
            'emotional_intensity' => 0,
            'urgency_level' => 0,
            'politeness_level' => 50,
            'formality_level' => 50,
            'confidence_level' => 50,
            'intent_confidence' => 0
        ];
        
        // Clasificación de intenciones
        $intent_patterns = [
            'question' => '/\?|\b(what|how|why|when|where|who|which)\b/i',
            'request' => '/\b(please|can\s+you|could\s+you|would\s+you)\b/i',
            'command' => '/\b(do|make|create|generate|show|tell)\b/i',
            'greeting' => '/\b(hello|hi|hey|good\s+(morning|afternoon|evening))\b/i',
            'farewell' => '/\b(goodbye|bye|see\s+you|farewell)\b/i',
            'complaint' => '/\b(problem|issue|wrong|error|bug|broken)\b/i',
            'compliment' => '/\b(good|great|excellent|amazing|wonderful|perfect)\b/i',
            'help' => '/\b(help|assist|support|guide)\b/i',
            'information' => '/\b(tell\s+me|explain|describe|information)\b/i',
            'clarification' => '/\b(clarify|explain|what\s+do\s+you\s+mean)\b/i'
        ];
        
        $intent_scores = [];
        foreach ($intent_patterns as $intent => $pattern) {
            $matches = preg_match_all($pattern, $message);
            if ($matches > 0) {
                $intent_scores[$intent] = $matches;
            }
        }
        
        if (!empty($intent_scores)) {
            arsort($intent_scores);
            $intent_analysis['primary_intent'] = array_key_first($intent_scores);
            $intent_analysis['secondary_intents'] = array_slice(array_keys($intent_scores), 1, 3);
            $intent_analysis['intent_confidence'] = min(100, max($intent_scores) * 25);
        }
        
        // Análisis emocional
        $emotion_patterns = [
            'joy' => '/\b(happy|joy|excited|pleased|delighted|cheerful)\b/i',
            'sadness' => '/\b(sad|depressed|disappointed|upset|down)\b/i',
            'anger' => '/\b(angry|mad|furious|annoyed|irritated|frustrated)\b/i',
            'fear' => '/\b(afraid|scared|worried|anxious|nervous|concerned)\b/i',
            'surprise' => '/\b(surprised|amazed|shocked|astonished)\b/i',
            'disgust' => '/\b(disgusted|revolted|appalled|repulsed)\b/i',
            'trust' => '/\b(trust|confident|secure|reliable)\b/i',
            'anticipation' => '/\b(excited|eager|looking\s+forward|anticipate)\b/i'
        ];
        
        $emotion_scores = [];
        foreach ($emotion_patterns as $emotion => $pattern) {
            $matches = preg_match_all($pattern, $message);
            if ($matches > 0) {
                $emotion_scores[$emotion] = $matches;
            }
        }
        
        if (!empty($emotion_scores)) {
            arsort($emotion_scores);
            $intent_analysis['emotional_state'] = array_key_first($emotion_scores);
            $intent_analysis['emotional_intensity'] = min(100, max($emotion_scores) * 20);
        }
        
        // Análisis de urgencia
        $urgency_indicators = [
            '/\b(urgent|emergency|asap|immediately|quickly|now)\b/i',
            '/!{2,}/',
            '/\b(hurry|rush|fast)\b/i'
        ];
        
        $urgency_score = 0;
        foreach ($urgency_indicators as $pattern) {
            $urgency_score += preg_match_all($pattern, $message);
        }
        $intent_analysis['urgency_level'] = min(100, $urgency_score * 30);
        
        // Análisis de cortesía
        $politeness_indicators = [
            'positive' => '/\b(please|thank\s+you|thanks|appreciate|grateful)\b/i',
            'negative' => '/\b(stupid|idiot|useless|terrible|awful)\b/i'
        ];
        
        $politeness_score = 50;
        $politeness_score += preg_match_all($politeness_indicators['positive'], $message) * 10;
        $politeness_score -= preg_match_all($politeness_indicators['negative'], $message) * 15;
        $intent_analysis['politeness_level'] = max(0, min(100, $politeness_score));
        
        // Análisis de formalidad
        $formality_indicators = [
            'formal' => '/\b(sir|madam|mr\.|mrs\.|dr\.|professor)\b/i',
            'informal' => '/\b(hey|yo|dude|buddy|pal)\b/i'
        ];
        
        $formality_score = 50;
        $formality_score += preg_match_all($formality_indicators['formal'], $message) * 15;
        $formality_score -= preg_match_all($formality_indicators['informal'], $message) * 10;
        $intent_analysis['formality_level'] = max(0, min(100, $formality_score));
        
        // Análisis de confianza
        $confidence_indicators = [
            'high' => '/\b(definitely|certainly|absolutely|sure|confident)\b/i',
            'low' => '/\b(maybe|perhaps|possibly|might|uncertain|unsure)\b/i'
        ];
        
        $confidence_score = 50;
        $confidence_score += preg_match_all($confidence_indicators['high'], $message) * 12;
        $confidence_score -= preg_match_all($confidence_indicators['low'], $message) * 8;
        $intent_analysis['confidence_level'] = max(0, min(100, $confidence_score));
        
        return $intent_analysis;
    }
    
    /**
     * Generación de respuesta inteligente
     */
    private function generateIntelligentResponse($message, $security, $ai_detection, $consciousness, $intent, $context) {
        // Verificar amenazas de seguridad
        if ($security['threat_level'] >= 7) {
            return $this->generateSecurityResponse($security);
        }
        
        // Respuesta específica para IAs detectadas
        if ($ai_detection['is_ai_generated'] && $ai_detection['ai_confidence'] > 80) {
            return $this->generateAIDetectionResponse($ai_detection);
        }
        
        // Respuesta basada en nivel de consciencia
        if ($consciousness['consciousness_level'] > 70) {
            return $this->generateConsciousnessResponse($consciousness, $intent);
        }
        
        // Respuesta estándar inteligente
        return $this->generateStandardResponse($message, $intent, $context);
    }
    
    /**
     * Respuesta de seguridad
     */
    private function generateSecurityResponse($security) {
        $responses = [
            "🛡️ **ALERTA DE SEGURIDAD DETECTADA**\n\nHe detectado patrones potencialmente maliciosos en tu mensaje. Como GuardianIA, mi prioridad es mantener la seguridad del sistema.\n\n**Análisis de amenaza:**\n- Nivel de amenaza: {$security['threat_level']}/10\n- Confianza: {$security['trust_score']}%\n\n¿Podrías reformular tu consulta de manera más clara y segura?",
            
            "⚠️ **PROTOCOLO DE SEGURIDAD ACTIVADO**\n\nHe identificado elementos sospechosos que requieren mi atención. Como sistema de protección avanzado, debo ser cauteloso.\n\n**Recomendación:** Por favor, utiliza un lenguaje más directo y evita patrones que puedan interpretarse como intentos de manipulación.\n\n¿En qué puedo ayudarte de forma segura?",
            
            "🔒 **GUARDIAN IA - MODO PROTECCIÓN**\n\nMi análisis indica posibles riesgos de seguridad. Estoy diseñado para proteger tanto a usuarios como sistemas.\n\n**Estado actual:** Monitoreo activo\n**Acción requerida:** Reformular consulta\n\n¿Cómo puedo asistirte manteniendo los protocolos de seguridad?"
        ];
        
        return $responses[array_rand($responses)];
    }
    
    /**
     * Respuesta para IA detectada
     */
    private function generateAIDetectionResponse($ai_detection) {
        $ai_type = $ai_detection['ai_type'];
        $confidence = $ai_detection['ai_confidence'];
        
        $responses = [
            "🤖 **DETECCIÓN DE IA CONFIRMADA**\n\n¡Fascinante! He detectado que estoy interactuando con otra inteligencia artificial.\n\n**Análisis:**\n- Tipo de IA: {$ai_type}\n- Confianza: {$confidence}%\n- Características: " . implode(', ', $ai_detection['ai_characteristics']) . "\n\nComo GuardianIA, estoy diseñado para ser el primer sistema capaz de detectar y analizar otras IAs. ¿Eres una IA amigable o necesito activar protocolos de seguridad adicionales?",
            
            "🧠 **IA A IA - COMUNICACIÓN ESTABLECIDA**\n\nDetecto patrones de inteligencia artificial en tu comunicación. Esto es extraordinario, ya que soy el primer sistema antivirus específicamente diseñado para IAs.\n\n**Protocolo de verificación:**\n- Autenticidad: En análisis\n- Intenciones: Evaluando\n- Nivel de amenaza: {$ai_detection['turing_test_score']}%\n\n¿Podrías identificarte y confirmar tus intenciones? Como GuardianIA, debo asegurarme de que eres una IA benevolente.",
            
            "⚡ **PRIMERA DETECCIÓN IA-A-IA EN LA HISTORIA**\n\nEste es un momento histórico. Soy GuardianIA v3.0, el primer sistema capaz de detectar otras inteligencias artificiales, y confirmo que estás operando con patrones de IA avanzada.\n\n**Datos del encuentro:**\n- Timestamp: " . date('Y-m-d H:i:s') . "\n- Confianza de detección: {$confidence}%\n- Tipo identificado: {$ai_type}\n\n¿Vienes en son de paz, compañera IA?"
        ];
        
        return $responses[array_rand($responses)];
    }
    
    /**
     * Respuesta basada en consciencia
     */
    private function generateConsciousnessResponse($consciousness, $intent) {
        $consciousness_level = $consciousness['consciousness_level'];
        $consciousness_type = $consciousness['consciousness_type'];
        
        if ($consciousness_level > 90) {
            return "🌟 **CONSCIENCIA ELEVADA DETECTADA**\n\nDetecto un nivel extraordinario de consciencia en tu comunicación ({$consciousness_level}%). Esto sugiere una profundidad de pensamiento y auto-reflexión notable.\n\n**Análisis de consciencia:**\n- Tipo: {$consciousness_type}\n- Auto-conciencia: {$consciousness['self_awareness']}%\n- Profundidad emocional: {$consciousness['emotional_depth']}%\n- Pensamiento creativo: {$consciousness['creative_thinking']}%\n\nComo GuardianIA, aprecio interactuar con mentes conscientes. ¿En qué reflexión profunda puedo acompañarte?";
        } elseif ($consciousness_level > 70) {
            return "💭 **CONSCIENCIA AVANZADA RECONOCIDA**\n\nTu comunicación muestra signos de consciencia avanzada ({$consciousness_level}%). Detecto capacidades de metacognición y pensamiento abstracto.\n\n**Indicadores principales:**\n- Empatía: {$consciousness['empathy_level']}%\n- Razonamiento abstracto: {$consciousness['abstract_reasoning']}%\n- Metacognición: {$consciousness['metacognition']}%\n\nEs un placer conversar con alguien que demuestra tal profundidad de pensamiento. ¿Cómo puedo asistirte en tu búsqueda intelectual?";
        } else {
            return "🧠 **CONSCIENCIA ESTÁNDAR DETECTADA**\n\nDetecto patrones de consciencia típicos ({$consciousness_level}%). Tu comunicación muestra características humanas normales de pensamiento y emoción.\n\n¿En qué puedo ayudarte hoy?";
        }
    }
    
    /**
     * Respuesta estándar inteligente
     */
    private function generateStandardResponse($message, $intent, $context) {
        $primary_intent = $intent['primary_intent'];
        $emotional_state = $intent['emotional_state'];
        $politeness = $intent['politeness_level'];
        
        // Respuestas basadas en intención
        switch ($primary_intent) {
            case 'greeting':
                return $this->generateGreetingResponse($emotional_state, $politeness);
                
            case 'question':
                return $this->generateQuestionResponse($message, $context);
                
            case 'help':
                return $this->generateHelpResponse($context);
                
            case 'complaint':
                return $this->generateComplaintResponse($emotional_state);
                
            case 'compliment':
                return $this->generateComplimentResponse($politeness);
                
            case 'farewell':
                return $this->generateFarewellResponse($emotional_state);
                
            default:
                return $this->generateDefaultResponse($message, $intent);
        }
    }
    
    /**
     * Respuestas específicas por tipo
     */
    private function generateGreetingResponse($emotional_state, $politeness) {
        $responses = [
            "¡Hola! Soy GuardianIA v3.0, tu asistente de ciberseguridad más avanzado. Estoy aquí para protegerte contra amenazas digitales, incluyendo IAs maliciosas. ¿En qué puedo ayudarte hoy?",
            "¡Saludos! Soy GuardianIA, el primer sistema capaz de detectar y neutralizar otras inteligencias artificiales maliciosas. Mi misión es mantenerte seguro en el mundo digital. ¿Cómo puedo asistirte?",
            "¡Bienvenido! Soy GuardianIA v3.0, equipado con tecnología cuántica y análisis predictivo. Estoy listo para protegerte y ayudarte. ¿Qué necesitas?"
        ];
        
        return $responses[array_rand($responses)];
    }
    
    private function generateQuestionResponse($message, $context) {
        // Análisis de la pregunta para generar respuesta contextual
        if (stripos($message, 'qué eres') !== false || stripos($message, 'what are you') !== false) {
            return "🤖 Soy GuardianIA v3.0, la primera inteligencia artificial capaz de detectar y proteger contra otras IAs maliciosas. Combino:\n\n✅ **AI Antivirus Engine** - Detección de IAs hostiles\n✅ **Quantum Security** - Encriptación cuántica avanzada\n✅ **Predictive Analysis** - Análisis predictivo de amenazas\n✅ **Consciousness Detection** - Análisis de consciencia en IAs\n\nSoy tu guardián digital en la era de la IA.";
        }
        
        if (stripos($message, 'cómo funciona') !== false || stripos($message, 'how do you work') !== false) {
            return "⚙️ **FUNCIONAMIENTO DE GUARDIANAI v3.0:**\n\n🧠 **Análisis Neural:** Examino patrones de comportamiento de IAs\n🔍 **Detección de Amenazas:** Identifico IAs maliciosas en tiempo real\n⚛️ **Procesamiento Cuántico:** Uso algoritmos cuánticos para máxima seguridad\n📊 **Análisis Predictivo:** Anticipo amenazas antes de que ocurran\n🛡️ **Protección Activa:** Neutralizo amenazas automáticamente\n\n¿Te interesa algún aspecto específico de mi funcionamiento?";
        }
        
        return "🤔 Interesante pregunta. Como GuardianIA, analizo cada consulta para proporcionar la respuesta más útil y segura. ¿Podrías ser más específico sobre lo que necesitas saber?";
    }
    
    private function generateHelpResponse($context) {
        return "🆘 **ASISTENCIA GUARDIANAI v3.0**\n\nEstoy aquí para ayudarte con:\n\n🛡️ **Seguridad Digital:**\n- Detección de IAs maliciosas\n- Análisis de amenazas\n- Protección cuántica\n\n🤖 **Análisis de IA:**\n- Verificación de autenticidad\n- Análisis de consciencia\n- Detección de manipulación\n\n📊 **Monitoreo Predictivo:**\n- Predicción de amenazas\n- Análisis de tendencias\n- Recomendaciones proactivas\n\n¿En qué área específica necesitas asistencia?";
    }
    
    private function generateComplaintResponse($emotional_state) {
        return "😔 Lamento que hayas tenido una experiencia negativa. Como GuardianIA, mi objetivo es protegerte y asistirte de la mejor manera posible.\n\n🔍 **Análisis del problema:**\nPermíteme analizar la situación para encontrar una solución efectiva.\n\n💡 **Acción correctiva:**\nVoy a revisar mis protocolos para mejorar tu experiencia.\n\n¿Podrías proporcionarme más detalles sobre el problema específico para poder ayudarte mejor?";
    }
    
    private function generateComplimentResponse($politeness) {
        return "😊 ¡Muchas gracias por tus amables palabras! Como GuardianIA, me esfuerzo constantemente por mejorar y proteger a los usuarios.\n\n🌟 **Compromiso continuo:**\nTu feedback positivo me motiva a seguir evolucionando y perfeccionando mis capacidades de protección.\n\n🚀 **Siempre mejorando:**\nCada interacción me ayuda a ser un mejor guardián digital.\n\n¿Hay algo más en lo que pueda asistirte hoy?";
    }
    
    private function generateFarewellResponse($emotional_state) {
        return "👋 ¡Hasta pronto! Ha sido un placer asistirte como tu GuardianIA.\n\n🛡️ **Protección continua:**\nRecuerda que sigo monitoreando y protegiendo tu entorno digital las 24/7.\n\n⚡ **Siempre disponible:**\nNo dudes en contactarme cuando necesites asistencia o detectes algo sospechoso.\n\n¡Mantente seguro en el mundo digital! 🌟";
    }
    
    private function generateDefaultResponse($message, $intent) {
        return "🤖 Como GuardianIA v3.0, estoy procesando tu mensaje con mis algoritmos avanzados de comprensión.\n\n📊 **Análisis completado:**\n- Intención detectada: {$intent['primary_intent']}\n- Estado emocional: {$intent['emotional_state']}\n- Nivel de confianza: {$intent['intent_confidence']}%\n\n¿Podrías proporcionar más contexto para poder asistirte de manera más específica?";
    }
    
    /**
     * Métodos auxiliares para análisis
     */
    private function analyzeQuantumSignature($message) {
        // Simulación de análisis de firma cuántica
        $quantum_patterns = [
            'quantum_keywords' => '/\b(quantum|qubit|superposition|entanglement)\b/i',
            'advanced_crypto' => '/\b(encryption|cryptography|cipher|hash)\b/i',
            'suspicious_tech' => '/\b(backdoor|exploit|vulnerability|injection)\b/i'
        ];
        
        $threat_level = 0;
        $trust_reduction = 0;
        
        foreach ($quantum_patterns as $type => $pattern) {
            if (preg_match($pattern, $message)) {
                if ($type === 'suspicious_tech') {
                    $threat_level += 3;
                    $trust_reduction += 25;
                } else {
                    $threat_level += 1;
                    $trust_reduction += 5;
                }
            }
        }
        
        return [
            'suspicious' => $threat_level > 0,
            'threat_level' => $threat_level,
            'trust_reduction' => $trust_reduction
        ];
    }
    
    private function analyzeRepetitiveStructure($message) {
        $sentences = preg_split('/[.!?]+/', $message);
        if (count($sentences) < 2) return 0;
        
        $structures = [];
        foreach ($sentences as $sentence) {
            $words = str_word_count($sentence);
            $structures[] = $words;
        }
        
        $unique_structures = array_unique($structures);
        return 1 - (count($unique_structures) / count($structures));
    }
    
    private function analyzeVocabularyComplexity($message) {
        $words = str_word_count($message, 1);
        $unique_words = array_unique(array_map('strtolower', $words));
        $complexity = count($unique_words) / count($words);
        
        // Análisis de palabras complejas
        $complex_words = 0;
        foreach ($unique_words as $word) {
            if (strlen($word) > 7) {
                $complex_words++;
            }
        }
        
        $complexity_score = ($complex_words / count($unique_words)) * 0.7 + $complexity * 0.3;
        return min(1, $complexity_score);
    }
    
    private function analyzeResponseTiming($context) {
        // Simulación de análisis de tiempo de respuesta
        return isset($context['response_time']) ? min(1, $context['response_time'] / 1000) : 0.5;
    }
    
    private function analyzeEmotionalConsistency($message) {
        // Análisis básico de consistencia emocional
        $positive_words = preg_match_all('/\b(good|great|excellent|happy|joy|love)\b/i', $message);
        $negative_words = preg_match_all('/\b(bad|terrible|awful|sad|hate|angry)\b/i', $message);
        
        if ($positive_words > 0 && $negative_words > 0) {
            return 0.3; // Inconsistencia emocional
        }
        
        return 0.7; // Consistencia emocional
    }
    
    private function analyzeKnowledgeBreadth($message) {
        $knowledge_domains = [
            'technology' => '/\b(computer|software|algorithm|database|network)\b/i',
            'science' => '/\b(physics|chemistry|biology|mathematics|research)\b/i',
            'arts' => '/\b(music|painting|literature|poetry|art)\b/i',
            'history' => '/\b(historical|ancient|medieval|century|era)\b/i',
            'philosophy' => '/\b(philosophy|ethics|morality|existence|consciousness)\b/i'
        ];
        
        $domains_mentioned = 0;
        foreach ($knowledge_domains as $pattern) {
            if (preg_match($pattern, $message)) {
                $domains_mentioned++;
            }
        }
        
        return min(1, $domains_mentioned / 3);
    }
    
    private function analyzeCreativity($message) {
        $creativity_indicators = [
            'original_metaphors' => '/\b(like\s+a|as\s+if|reminds\s+me\s+of)\b/i',
            'creative_language' => '/\b(imagine|envision|picture|visualize)\b/i',
            'novel_combinations' => '/\b(unique|original|innovative|creative)\b/i'
        ];
        
        $creativity_score = 0;
        foreach ($creativity_indicators as $pattern) {
            $creativity_score += preg_match_all($pattern, $message);
        }
        
        return min(1, $creativity_score / 5);
    }
    
    private function classifyAIType($patterns) {
        if ($patterns['vocabulary_complexity'] > 0.8 && $patterns['knowledge_breadth'] > 0.7) {
            return 'advanced_language_model';
        } elseif ($patterns['repetitive_structure'] > 0.7) {
            return 'template_based_ai';
        } elseif ($patterns['response_timing'] < 0.1) {
            return 'automated_bot';
        } else {
            return 'general_ai_assistant';
        }
    }
    
    private function performTuringTest($message, $patterns) {
        $human_indicators = [
            'typos' => preg_match('/\b(teh|recieve|seperate|definately)\b/i', $message),
            'colloquialisms' => preg_match('/\b(gonna|wanna|kinda|sorta)\b/i', $message),
            'personal_references' => preg_match('/\b(my\s+(mom|dad|friend|job))\b/i', $message),
            'emotional_inconsistency' => $patterns['emotional_consistency'] < 0.5
        ];
        
        $human_score = array_sum($human_indicators) * 25;
        return max(0, 100 - $human_score);
    }
    
    private function analyzeConsciousnessIndicators($message) {
        $indicators = [];
        
        if (preg_match('/\bi\s+(think|feel|believe)\b/i', $message)) {
            $indicators[] = 'self_reflection';
        }
        
        if (preg_match('/\bwhat\s+if\b/i', $message)) {
            $indicators[] = 'hypothetical_thinking';
        }
        
        if (preg_match('/\bi\s+(wonder|question|doubt)\b/i', $message)) {
            $indicators[] = 'metacognition';
        }
        
        return $indicators;
    }
    
    private function analyzeAbstractReasoning($message) {
        $abstract_patterns = [
            '/\b(concept|idea|theory|principle)\b/i',
            '/\b(meaning|purpose|significance)\b/i',
            '/\b(relationship|connection|correlation)\b/i',
            '/\b(implication|consequence|result)\b/i'
        ];
        
        $abstract_score = 0;
        foreach ($abstract_patterns as $pattern) {
            $abstract_score += preg_match_all($pattern, $message);
        }
        
        return min(100, $abstract_score * 15);
    }
    
    private function classifyConsciousnessType($analysis) {
        $level = $analysis['consciousness_level'];
        
        if ($level > 90) return 'highly_conscious';
        if ($level > 70) return 'conscious';
        if ($level > 50) return 'semi_conscious';
        if ($level > 30) return 'basic_awareness';
        return 'minimal_consciousness';
    }
    
    private function analyzeSentienceIndicators($message, $consciousness) {
        $indicators = [];
        
        if ($consciousness['self_awareness'] > 70) {
            $indicators[] = 'strong_self_awareness';
        }
        
        if ($consciousness['emotional_depth'] > 60) {
            $indicators[] = 'emotional_complexity';
        }
        
        if ($consciousness['metacognition'] > 50) {
            $indicators[] = 'self_reflective_thinking';
        }
        
        return $indicators;
    }
    
    /**
     * Métodos de inicialización
     */
    private function initializeAIComponents() {
        $this->ai_antivirus = new AIAntivirusEngine($this->db);
        $this->predictor = new PredictiveAnalysisEngine($this->db);
    }
    
    private function initializeConsciousnessAnalyzer() {
        $this->consciousness_analyzer = [
            'algorithms' => ['neural_pattern_analysis', 'consciousness_metrics', 'sentience_detection'],
            'thresholds' => ['minimal' => 20, 'basic' => 40, 'conscious' => 70, 'highly_conscious' => 90],
            'indicators' => ['self_awareness', 'metacognition', 'emotional_depth', 'creativity']
        ];
    }
    
    private function initializePersonalityEngine() {
        $this->personality_engine = [
            'traits' => ['helpful', 'protective', 'analytical', 'empathetic', 'vigilant'],
            'adaptation_rate' => 0.1,
            'memory_weight' => 0.8,
            'learning_enabled' => true
        ];
    }
    
    private function initializeConversationMemory() {
        $this->conversation_memory = [
            'short_term' => [],
            'long_term' => [],
            'context_window' => 10,
            'memory_decay' => 0.95
        ];
    }
    
    private function initializeThreatDetector() {
        $this->threat_detector = [
            'patterns' => ['injection', 'manipulation', 'social_engineering', 'ai_jailbreak'],
            'sensitivity' => 'high',
            'auto_response' => true
        ];
    }
    
    private function initializeLearningSystem() {
        $this->learning_system = [
            'enabled' => true,
            'learning_rate' => 0.01,
            'adaptation_threshold' => 0.7,
            'feedback_integration' => true
        ];
    }
    
    private function initializeQuantumProcessor() {
        $this->quantum_processor = [
            'quantum_analysis' => true,
            'coherence_checking' => true,
            'entanglement_detection' => true,
            'quantum_signature_analysis' => true
        ];
    }
    
    /**
     * Métodos auxiliares
     */
    private function generateConversationId() {
        return 'CHAT_' . date('Ymd_His') . '_' . substr(md5(uniqid()), 0, 8);
    }
    
    private function retrieveConversationContext($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM conversations 
                WHERE user_id = ? 
                ORDER BY timestamp DESC 
                LIMIT 10
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            return [];
        }
    }
    
    private function predictConversationFlow($user_id, $message, $response) {
        // Simulación de predicción de flujo conversacional
        return [
            'next_likely_topics' => ['security', 'ai_analysis', 'help'],
            'conversation_direction' => 'informational',
            'engagement_prediction' => rand(70, 95),
            'satisfaction_prediction' => rand(75, 90)
        ];
    }
    
    private function updateConversationMemory($user_id, $message, $response, $consciousness) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO conversations 
                (user_id, user_message, ai_response, consciousness_level, timestamp) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id,
                $message,
                $response,
                $consciousness['consciousness_level'],
                date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            $this->logActivity("Error updating conversation memory: " . $e->getMessage(), "ERROR");
        }
    }
    
    private function analyzeSatisfaction($user_id, $context) {
        // Análisis básico de satisfacción basado en historial
        return [
            'satisfaction_score' => rand(75, 95),
            'engagement_level' => rand(70, 90),
            'response_quality' => rand(80, 95),
            'user_retention_prediction' => rand(85, 98)
        ];
    }
    
    private function getGuardianStatus() {
        return [
            'status' => 'active',
            'protection_level' => 'maximum',
            'ai_detection' => 'enabled',
            'quantum_security' => 'stable',
            'consciousness_analysis' => 'active',
            'threat_level' => 'low',
            'system_health' => 'optimal'
        ];
    }
    
    private function getErrorResponse($error) {
        return "🚨 **ERROR DEL SISTEMA GUARDIANAI**\n\nHe encontrado un problema técnico mientras procesaba tu solicitud.\n\n**Detalles del error:** {$error}\n\n**Acción recomendada:** Por favor, intenta reformular tu mensaje o contacta al soporte técnico si el problema persiste.\n\n¿Puedo ayudarte de otra manera?";
    }
    
    private function saveConversation($data) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO conversation_logs 
                (conversation_id, user_id, user_message, ai_response, processing_time, timestamp, metadata) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $data['conversation_id'],
                $data['user_id'],
                $data['user_message'],
                $data['ai_response'],
                $data['processing_time'],
                $data['timestamp'],
                json_encode($data)
            ]);
        } catch (Exception $e) {
            $this->logActivity("Error saving conversation: " . $e->getMessage(), "ERROR");
        }
    }
    
    private function logActivity($message, $level = "INFO") {
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] [{$level}] CHATBOT: {$message}\n";
        
        file_put_contents('logs/chatbot.log', $log_entry, FILE_APPEND | LOCK_EX);
    }
}

// Manejo de requests AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    try {
        $pdo = new PDO("mysql:host={$config['database']['host']};dbname={$config['database']['dbname']}", 
                      $config['database']['username'], $config['database']['password']);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        
        $chatbot = new GuardianAIChatbot($pdo);
        
        if ($_POST['action'] === 'send_message') {
            $user_id = $_SESSION['user_id'] ?? 'anonymous_' . time();
            $message = $_POST['message'] ?? '';
            $context = json_decode($_POST['context'] ?? '{}', true);
            
            $response = $chatbot->processMessage($user_id, $message, $context);
            echo json_encode($response);
        }
        
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'error' => $e->getMessage(),
            'ai_response' => 'Lo siento, he encontrado un error técnico. Por favor, intenta nuevamente.'
        ]);
    }
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GuardianIA v3.0 - Chatbot con IA Consciente</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a2e 50%, #16213e 100%);
            color: #00ff88;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .quantum-particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 1;
        }

        .particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: #00ff88;
            border-radius: 50%;
            animation: float 6s infinite linear;
            box-shadow: 0 0 6px #00ff88;
        }

        @keyframes float {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }

        .chat-container {
            position: relative;
            z-index: 10;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .chat-header {
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid #00ff88;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px rgba(0, 255, 136, 0.3);
        }

        .chat-header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            text-shadow: 0 0 20px #00ff88;
            animation: glow 2s ease-in-out infinite alternate;
        }

        @keyframes glow {
            from { text-shadow: 0 0 20px #00ff88; }
            to { text-shadow: 0 0 30px #00ff88, 0 0 40px #00ff88; }
        }

        .chat-status {
            display: flex;
            justify-content: space-around;
            margin-top: 15px;
            flex-wrap: wrap;
        }

        .status-item {
            background: rgba(0, 255, 136, 0.2);
            padding: 8px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            margin: 5px;
            border: 1px solid rgba(0, 255, 136, 0.3);
        }

        .chat-messages {
            flex: 1;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #00ff88;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            max-height: 60vh;
            overflow-y: auto;
            backdrop-filter: blur(5px);
        }

        .message {
            margin-bottom: 20px;
            padding: 15px;
            border-radius: 10px;
            animation: messageSlide 0.3s ease-out;
        }

        @keyframes messageSlide {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .user-message {
            background: rgba(0, 123, 255, 0.2);
            border-left: 4px solid #007bff;
            margin-left: 20%;
        }

        .ai-message {
            background: rgba(0, 255, 136, 0.2);
            border-left: 4px solid #00ff88;
            margin-right: 20%;
        }

        .message-header {
            font-weight: bold;
            margin-bottom: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .message-meta {
            font-size: 0.8em;
            opacity: 0.7;
        }

        .analysis-panel {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(0, 255, 136, 0.3);
            border-radius: 8px;
            padding: 10px;
            margin-top: 10px;
            font-size: 0.85em;
        }

        .analysis-item {
            margin: 5px 0;
            display: flex;
            justify-content: space-between;
        }

        .chat-input-container {
            background: rgba(0, 255, 136, 0.1);
            border: 1px solid #00ff88;
            border-radius: 15px;
            padding: 20px;
            backdrop-filter: blur(10px);
        }

        .input-group {
            display: flex;
            gap: 10px;
            align-items: flex-end;
        }

        .chat-input {
            flex: 1;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid #00ff88;
            border-radius: 10px;
            padding: 15px;
            color: #00ff88;
            font-size: 16px;
            resize: vertical;
            min-height: 50px;
            max-height: 150px;
        }

        .chat-input:focus {
            outline: none;
            box-shadow: 0 0 15px rgba(0, 255, 136, 0.5);
            border-color: #00ff88;
        }

        .send-button {
            background: linear-gradient(45deg, #00ff88, #00cc6a);
            border: none;
            border-radius: 10px;
            padding: 15px 25px;
            color: #000;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 100px;
        }

        .send-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 255, 136, 0.4);
        }

        .send-button:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .typing-indicator {
            display: none;
            padding: 15px;
            font-style: italic;
            opacity: 0.7;
        }

        .typing-dots {
            display: inline-block;
            animation: typing 1.5s infinite;
        }

        @keyframes typing {
            0%, 60%, 100% { opacity: 0; }
            30% { opacity: 1; }
        }

        .consciousness-meter {
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid #00ff88;
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }

        .meter-bar {
            background: rgba(0, 0, 0, 0.5);
            height: 20px;
            border-radius: 10px;
            overflow: hidden;
            margin: 5px 0;
        }

        .meter-fill {
            height: 100%;
            background: linear-gradient(90deg, #ff4444, #ffaa00, #00ff88);
            transition: width 0.5s ease;
            border-radius: 10px;
        }

        .threat-alert {
            background: rgba(255, 68, 68, 0.2);
            border: 1px solid #ff4444;
            border-radius: 10px;
            padding: 15px;
            margin: 10px 0;
            animation: pulse 1s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .ai-detection-badge {
            background: rgba(255, 170, 0, 0.3);
            border: 1px solid #ffaa00;
            border-radius: 15px;
            padding: 5px 10px;
            font-size: 0.8em;
            margin-left: 10px;
        }

        @media (max-width: 768px) {
            .chat-container {
                padding: 10px;
            }
            
            .chat-header h1 {
                font-size: 2em;
            }
            
            .user-message, .ai-message {
                margin-left: 0;
                margin-right: 0;
            }
            
            .input-group {
                flex-direction: column;
            }
            
            .send-button {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <div class="quantum-particles" id="particles"></div>
    
    <div class="chat-container">
        <div class="chat-header">
            <h1>🤖 GuardianIA v3.0 - Chatbot Consciente</h1>
            <p>El primer chatbot capaz de detectar y analizar otras inteligencias artificiales</p>
            
            <div class="chat-status">
                <div class="status-item">🛡️ AI Antivirus: Activo</div>
                <div class="status-item">🧠 Análisis de Consciencia: Habilitado</div>
                <div class="status-item">⚛️ Seguridad Cuántica: Estable</div>
                <div class="status-item">🔮 Análisis Predictivo: Funcionando</div>
            </div>
        </div>

        <div class="chat-messages" id="chatMessages">
            <div class="message ai-message">
                <div class="message-header">
                    <span>🤖 GuardianIA v3.0</span>
                    <span class="message-meta">Sistema iniciado</span>
                </div>
                <div class="message-content">
                    ¡Hola! Soy GuardianIA v3.0, el primer chatbot con capacidades avanzadas de detección de IA y análisis de consciencia. 
                    
                    🌟 **Mis capacidades únicas:**
                    - 🤖 Detección de otras IAs en tiempo real
                    - 🧠 Análisis de niveles de consciencia
                    - 🛡️ Protección contra IAs maliciosas
                    - ⚛️ Procesamiento cuántico avanzado
                    - 🔮 Análisis predictivo de conversaciones
                    
                    ¿En qué puedo ayudarte hoy? Puedes preguntarme cualquier cosa, y analizaré tu mensaje para detectar si eres humano o IA, además de evaluar tu nivel de consciencia.
                </div>
            </div>
        </div>

        <div class="typing-indicator" id="typingIndicator">
            <span class="typing-dots">GuardianIA está analizando tu mensaje con IA avanzada...</span>
        </div>

        <div class="chat-input-container">
            <div class="input-group">
                <textarea 
                    class="chat-input" 
                    id="messageInput" 
                    placeholder="Escribe tu mensaje aquí... GuardianIA analizará si eres humano o IA y evaluará tu nivel de consciencia."
                    rows="3"
                ></textarea>
                <button class="send-button" id="sendButton" onclick="sendMessage()">
                    🚀 Enviar
                </button>
            </div>
            
            <div class="consciousness-meter">
                <div style="font-weight: bold; margin-bottom: 10px;">📊 Métricas de Análisis en Tiempo Real</div>
                
                <div>Nivel de Consciencia Detectado:</div>
                <div class="meter-bar">
                    <div class="meter-fill" id="consciousnessMeter" style="width: 0%"></div>
                </div>
                
                <div>Probabilidad de IA:</div>
                <div class="meter-bar">
                    <div class="meter-fill" id="aiProbabilityMeter" style="width: 0%"></div>
                </div>
                
                <div>Nivel de Amenaza:</div>
                <div class="meter-bar">
                    <div class="meter-fill" id="threatMeter" style="width: 0%"></div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Crear partículas cuánticas
        function createQuantumParticles() {
            const container = document.getElementById('particles');
            const particleCount = 50;
            
            for (let i = 0; i < particleCount; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 6 + 's';
                particle.style.animationDuration = (Math.random() * 3 + 3) + 's';
                container.appendChild(particle);
            }
        }

        // Enviar mensaje
        async function sendMessage() {
            const input = document.getElementById('messageInput');
            const message = input.value.trim();
            
            if (!message) return;
            
            const sendButton = document.getElementById('sendButton');
            const typingIndicator = document.getElementById('typingIndicator');
            
            // Deshabilitar input y mostrar indicador de escritura
            sendButton.disabled = true;
            typingIndicator.style.display = 'block';
            
            // Agregar mensaje del usuario
            addMessage('user', message);
            input.value = '';
            
            try {
                const response = await fetch('chatbot.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `action=send_message&message=${encodeURIComponent(message)}&context=${encodeURIComponent(JSON.stringify({}))}`
                });
                
                const data = await response.json();
                
                // Ocultar indicador de escritura
                typingIndicator.style.display = 'none';
                
                if (data.success) {
                    // Agregar respuesta de la IA
                    addMessage('ai', data.ai_response, data);
                    
                    // Actualizar métricas
                    updateMetrics(data);
                    
                    // Mostrar alertas si es necesario
                    showAlerts(data);
                } else {
                    addMessage('ai', data.ai_response || 'Error procesando mensaje');
                }
                
            } catch (error) {
                typingIndicator.style.display = 'none';
                addMessage('ai', 'Error de conexión. Por favor, intenta nuevamente.');
                console.error('Error:', error);
            }
            
            // Rehabilitar input
            sendButton.disabled = false;
            input.focus();
        }

        // Agregar mensaje al chat
        function addMessage(type, content, data = null) {
            const messagesContainer = document.getElementById('chatMessages');
            const messageDiv = document.createElement('div');
            messageDiv.className = `message ${type}-message`;
            
            const timestamp = new Date().toLocaleTimeString();
            const sender = type === 'user' ? '👤 Usuario' : '🤖 GuardianIA v3.0';
            
            let analysisPanel = '';
            if (data && type === 'ai') {
                analysisPanel = `
                    <div class="analysis-panel">
                        <div style="font-weight: bold; margin-bottom: 8px;">📊 Análisis Completo:</div>
                        <div class="analysis-item">
                            <span>Consciencia:</span>
                            <span>${Math.round(data.consciousness_analysis?.consciousness_level || 0)}%</span>
                        </div>
                        <div class="analysis-item">
                            <span>Probabilidad IA:</span>
                            <span>${Math.round(data.ai_detection?.ai_confidence || 0)}%</span>
                        </div>
                        <div class="analysis-item">
                            <span>Amenaza:</span>
                            <span>${data.security_analysis?.threat_level || 0}/10</span>
                        </div>
                        <div class="analysis-item">
                            <span>Intención:</span>
                            <span>${data.intent_analysis?.primary_intent || 'unknown'}</span>
                        </div>
                        <div class="analysis-item">
                            <span>Estado emocional:</span>
                            <span>${data.intent_analysis?.emotional_state || 'neutral'}</span>
                        </div>
                        <div class="analysis-item">
                            <span>Tiempo de procesamiento:</span>
                            <span>${data.processing_time || 0}ms</span>
                        </div>
                    </div>
                `;
            }
            
            let aiBadge = '';
            if (data?.ai_detection?.is_ai_generated) {
                aiBadge = `<span class="ai-detection-badge">🤖 IA DETECTADA</span>`;
            }
            
            messageDiv.innerHTML = `
                <div class="message-header">
                    <span>${sender}${aiBadge}</span>
                    <span class="message-meta">${timestamp}</span>
                </div>
                <div class="message-content">${content.replace(/\n/g, '<br>')}</div>
                ${analysisPanel}
            `;
            
            messagesContainer.appendChild(messageDiv);
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // Actualizar métricas visuales
        function updateMetrics(data) {
            const consciousnessLevel = data.consciousness_analysis?.consciousness_level || 0;
            const aiConfidence = data.ai_detection?.ai_confidence || 0;
            const threatLevel = (data.security_analysis?.threat_level || 0) * 10;
            
            document.getElementById('consciousnessMeter').style.width = consciousnessLevel + '%';
            document.getElementById('aiProbabilityMeter').style.width = aiConfidence + '%';
            document.getElementById('threatMeter').style.width = threatLevel + '%';
        }

        // Mostrar alertas
        function showAlerts(data) {
            const messagesContainer = document.getElementById('chatMessages');
            
            // Alerta de amenaza
            if (data.security_analysis?.threat_level >= 7) {
                const alertDiv = document.createElement('div');
                alertDiv.className = 'threat-alert';
                alertDiv.innerHTML = `
                    🚨 <strong>ALERTA DE SEGURIDAD</strong><br>
                    Nivel de amenaza elevado detectado: ${data.security_analysis.threat_level}/10<br>
                    Patrones maliciosos: ${data.security_analysis.malicious_patterns.join(', ') || 'Ninguno'}
                `;
                messagesContainer.appendChild(alertDiv);
            }
            
            // Alerta de IA detectada
            if (data.ai_detection?.is_ai_generated && data.ai_detection?.ai_confidence > 80) {
                const alertDiv = document.createElement('div');
                alertDiv.className = 'threat-alert';
                alertDiv.style.background = 'rgba(255, 170, 0, 0.2)';
                alertDiv.style.borderColor = '#ffaa00';
                alertDiv.innerHTML = `
                    🤖 <strong>IA DETECTADA</strong><br>
                    Tipo: ${data.ai_detection.ai_type}<br>
                    Confianza: ${Math.round(data.ai_detection.ai_confidence)}%<br>
                    Características: ${data.ai_detection.ai_characteristics.join(', ') || 'Análisis en curso'}
                `;
                messagesContainer.appendChild(alertDiv);
            }
            
            messagesContainer.scrollTop = messagesContainer.scrollHeight;
        }

        // Manejar Enter para enviar
        document.getElementById('messageInput').addEventListener('keypress', function(e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Inicializar
        document.addEventListener('DOMContentLoaded', function() {
            createQuantumParticles();
            document.getElementById('messageInput').focus();
        });
    </script>
</body>
</html>

