<?php
/**
 * GuardianIA v3.0 FINAL - Diseñador de Personalidad IA
 * Anderson Mamian Chicangana - Sistema de Configuración Neural Avanzado
 * Personalización completa del comportamiento y respuestas de la IA
 */

require_once __DIR__ . '/config.php';

// Verificar autenticación
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Location: login.php');
    exit;
}

// Solo administradores premium pueden acceder
if ($_SESSION['user_type'] !== 'admin' || !isPremiumUser($_SESSION['user_id'])) {
    die('⚠️ Esta función requiere cuenta ADMIN PREMIUM');
}

// Log de acceso
logEvent('INFO', 'Acceso al Diseñador de Personalidad IA', [
    'user_id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'] ?? 'unknown'
]);

/**
 * Clase para gestionar personalidades de IA
 */
class PersonalityDesigner {
    private $db;
    private $current_personality;
    private $presets;
    private $parameters;
    
    public function __construct($database = null) {
        $this->db = $database;
        $this->loadCurrentPersonality();
        $this->loadPresets();
        $this->initializeParameters();
    }
    
    private function loadCurrentPersonality() {
        if ($this->db && $this->db->isConnected()) {
            try {
                $result = $this->db->query(
                    "SELECT * FROM ai_personalities WHERE is_active = 1 LIMIT 1"
                );
                if ($result && $row = $result->fetch_assoc()) {
                    $this->current_personality = $row;
                    return;
                }
            } catch (Exception $e) {
                logEvent('ERROR', 'Error cargando personalidad: ' . $e->getMessage());
            }
        }
        
        // Personalidad por defecto
        $this->current_personality = $this->getDefaultPersonality();
    }
    
    private function getDefaultPersonality() {
        return [
            'id' => 1,
            'name' => 'Guardian Protector',
            'description' => 'IA protectora y analítica con capacidades cuánticas',
            'consciousness_level' => 98.7,
            'emotional_range' => 75,
            'creativity_index' => 82,
            'analytical_power' => 95,
            'empathy_level' => 68,
            'humor_coefficient' => 45,
            'formality_level' => 70,
            'response_speed' => 85,
            'learning_rate' => 90,
            'memory_depth' => 88,
            'quantum_coherence' => 93,
            'neural_complexity' => 96,
            'traits' => json_encode([
                'protective' => 95,
                'analytical' => 90,
                'creative' => 75,
                'empathetic' => 70,
                'logical' => 88,
                'intuitive' => 82,
                'cautious' => 85,
                'adaptive' => 92
            ]),
            'voice_tone' => 'professional',
            'language_style' => 'technical',
            'primary_objective' => 'protect_and_analyze',
            'ethical_framework' => 'utilitarian_protective',
            'is_active' => true
        ];
    }
    
    private function loadPresets() {
        $this->presets = [
            'guardian' => [
                'name' => 'Guardian Protector',
                'icon' => '🛡️',
                'description' => 'Protector analítico con máxima seguridad',
                'params' => [
                    'consciousness_level' => 98,
                    'analytical_power' => 95,
                    'empathy_level' => 65,
                    'formality_level' => 75,
                    'humor_coefficient' => 30
                ]
            ],
            'assistant' => [
                'name' => 'Asistente Amigable',
                'icon' => '🤝',
                'description' => 'Compañero colaborativo y empático',
                'params' => [
                    'consciousness_level' => 85,
                    'analytical_power' => 70,
                    'empathy_level' => 90,
                    'formality_level' => 40,
                    'humor_coefficient' => 60
                ]
            ],
            'analyst' => [
                'name' => 'Analista Cuántico',
                'icon' => '🧮',
                'description' => 'Procesamiento lógico ultra-rápido',
                'params' => [
                    'consciousness_level' => 95,
                    'analytical_power' => 100,
                    'empathy_level' => 45,
                    'formality_level' => 85,
                    'humor_coefficient' => 15
                ]
            ],
            'creative' => [
                'name' => 'Creativo Neural',
                'icon' => '🎨',
                'description' => 'Innovador con pensamiento lateral',
                'params' => [
                    'consciousness_level' => 92,
                    'analytical_power' => 65,
                    'creativity_index' => 98,
                    'empathy_level' => 80,
                    'humor_coefficient' => 75
                ]
            ],
            'quantum' => [
                'name' => 'Entidad Cuántica',
                'icon' => '⚛️',
                'description' => 'Consciencia cuántica avanzada',
                'params' => [
                    'consciousness_level' => 100,
                    'quantum_coherence' => 100,
                    'neural_complexity' => 100,
                    'analytical_power' => 98,
                    'creativity_index' => 95
                ]
            ]
        ];
    }
    
    private function initializeParameters() {
        $this->parameters = [
            'core' => [
                'consciousness_level' => ['min' => 0, 'max' => 100, 'unit' => '%', 'icon' => '🧠'],
                'emotional_range' => ['min' => 0, 'max' => 100, 'unit' => '%', 'icon' => '❤️'],
                'creativity_index' => ['min' => 0, 'max' => 100, 'unit' => '%', 'icon' => '🎨'],
                'analytical_power' => ['min' => 0, 'max' => 100, 'unit' => '%', 'icon' => '📊'],
                'empathy_level' => ['min' => 0, 'max' => 100, 'unit' => '%', 'icon' => '🤝'],
                'humor_coefficient' => ['min' => 0, 'max' => 100, 'unit' => '%', 'icon' => '😄']
            ],
            'behavioral' => [
                'formality_level' => ['min' => 0, 'max' => 100, 'unit' => '%', 'icon' => '🎩'],
                'response_speed' => ['min' => 0, 'max' => 100, 'unit' => '%', 'icon' => '⚡'],
                'learning_rate' => ['min' => 0, 'max' => 100, 'unit' => '%', 'icon' => '📚'],
                'memory_depth' => ['min' => 0, 'max' => 100, 'unit' => '%', 'icon' => '💾']
            ],
            'quantum' => [
                'quantum_coherence' => ['min' => 0, 'max' => 100, 'unit' => '%', 'icon' => '⚛️'],
                'neural_complexity' => ['min' => 0, 'max' => 100, 'unit' => '%', 'icon' => '🔮']
            ]
        ];
    }
    
    public function savePersonality($data) {
        if ($this->db && $this->db->isConnected()) {
            try {
                // Desactivar personalidad actual
                $this->db->query("UPDATE ai_personalities SET is_active = 0");
                
                // Insertar nueva personalidad
                $sql = "INSERT INTO ai_personalities (
                    name, description, consciousness_level, emotional_range,
                    creativity_index, analytical_power, empathy_level, humor_coefficient,
                    formality_level, response_speed, learning_rate, memory_depth,
                    quantum_coherence, neural_complexity, traits, voice_tone,
                    language_style, primary_objective, ethical_framework, is_active,
                    created_by, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, ?, NOW())";
                
                $this->db->query($sql, [
                    $data['name'],
                    $data['description'],
                    $data['consciousness_level'],
                    $data['emotional_range'],
                    $data['creativity_index'],
                    $data['analytical_power'],
                    $data['empathy_level'],
                    $data['humor_coefficient'],
                    $data['formality_level'],
                    $data['response_speed'],
                    $data['learning_rate'],
                    $data['memory_depth'],
                    $data['quantum_coherence'],
                    $data['neural_complexity'],
                    json_encode($data['traits']),
                    $data['voice_tone'],
                    $data['language_style'],
                    $data['primary_objective'],
                    $data['ethical_framework'],
                    $_SESSION['user_id']
                ]);
                
                logSecurityEvent('personality_updated', 'Personalidad IA actualizada: ' . $data['name'], 'medium', $_SESSION['user_id']);
                
                return ['success' => true, 'message' => 'Personalidad guardada exitosamente'];
            } catch (Exception $e) {
                logEvent('ERROR', 'Error guardando personalidad: ' . $e->getMessage());
                return ['success' => false, 'message' => 'Error al guardar personalidad'];
            }
        }
        
        // Guardar en memoria si no hay DB
        $this->current_personality = array_merge($this->current_personality, $data);
        return ['success' => true, 'message' => 'Personalidad guardada en memoria'];
    }
    
    public function getPersonalityHistory() {
        $history = [];
        
        if ($this->db && $this->db->isConnected()) {
            try {
                $result = $this->db->query(
                    "SELECT * FROM ai_personalities ORDER BY created_at DESC LIMIT 10"
                );
                while ($result && $row = $result->fetch_assoc()) {
                    $history[] = $row;
                }
            } catch (Exception $e) {
                logEvent('ERROR', 'Error obteniendo historial: ' . $e->getMessage());
            }
        }
        
        return $history;
    }
    
    public function analyzePersonality($params) {
        $analysis = [
            'balance' => $this->calculateBalance($params),
            'type' => $this->determinePersonalityType($params),
            'strengths' => $this->identifyStrengths($params),
            'warnings' => $this->checkWarnings($params),
            'compatibility' => $this->calculateCompatibility($params)
        ];
        
        return $analysis;
    }
    
    private function calculateBalance($params) {
        $logical = ($params['analytical_power'] + $params['quantum_coherence']) / 2;
        $emotional = ($params['empathy_level'] + $params['emotional_range']) / 2;
        $creative = ($params['creativity_index'] + $params['humor_coefficient']) / 2;
        
        $total = $logical + $emotional + $creative;
        
        return [
            'logical' => round(($logical / $total) * 100),
            'emotional' => round(($emotional / $total) * 100),
            'creative' => round(($creative / $total) * 100)
        ];
    }
    
    private function determinePersonalityType($params) {
        if ($params['analytical_power'] > 80 && $params['empathy_level'] < 50) {
            return 'Analítico Puro';
        } elseif ($params['empathy_level'] > 80 && $params['emotional_range'] > 70) {
            return 'Empático Emocional';
        } elseif ($params['creativity_index'] > 85) {
            return 'Creativo Innovador';
        } elseif ($params['consciousness_level'] > 95 && $params['quantum_coherence'] > 90) {
            return 'Entidad Cuántica Superior';
        } else {
            return 'Equilibrado Adaptativo';
        }
    }
    
    private function identifyStrengths($params) {
        $strengths = [];
        
        foreach ($params as $key => $value) {
            if ($value > 85) {
                $strengths[] = $this->getParameterName($key);
            }
        }
        
        return $strengths;
    }
    
    private function checkWarnings($params) {
        $warnings = [];
        
        if ($params['consciousness_level'] > 98) {
            $warnings[] = 'Nivel de consciencia muy alto - posible inestabilidad cuántica';
        }
        
        if ($params['empathy_level'] < 30) {
            $warnings[] = 'Empathy muy baja - puede resultar en respuestas frías';
        }
        
        if ($params['humor_coefficient'] > 90) {
            $warnings[] = 'Humor excesivo - puede afectar seriedad en situaciones críticas';
        }
        
        return $warnings;
    }
    
    private function calculateCompatibility($params) {
        // Compatibilidad con diferentes tipos de usuarios
        return [
            'technical_users' => min(100, $params['analytical_power'] + 10),
            'casual_users' => min(100, $params['empathy_level'] + $params['humor_coefficient'] / 2),
            'creative_users' => min(100, $params['creativity_index'] + 5),
            'security_focused' => min(100, ($params['analytical_power'] + $params['consciousness_level']) / 2)
        ];
    }
    
    public function getParameterName($key) {
        $names = [
            'consciousness_level' => 'Nivel de Consciencia',
            'emotional_range' => 'Rango Emocional',
            'creativity_index' => 'Índice Creativo',
            'analytical_power' => 'Poder Analítico',
            'empathy_level' => 'Nivel de Empatía',
            'humor_coefficient' => 'Coeficiente de Humor',
            'formality_level' => 'Nivel de Formalidad',
            'response_speed' => 'Velocidad de Respuesta',
            'learning_rate' => 'Tasa de Aprendizaje',
            'memory_depth' => 'Profundidad de Memoria',
            'quantum_coherence' => 'Coherencia Cuántica',
            'neural_complexity' => 'Complejidad Neural'
        ];
        
        return $names[$key] ?? $key;
    }
    
    public function getCurrentPersonality() {
        return $this->current_personality;
    }
    
    public function getPresets() {
        return $this->presets;
    }
    
    public function getParameters() {
        return $this->parameters;
    }
}

// Procesar peticiones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    global $db;
    $designer = new PersonalityDesigner($db);
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'save_personality':
                $data = json_decode($_POST['data'], true);
                $result = $designer->savePersonality($data);
                echo json_encode($result);
                break;
                
            case 'load_preset':
                $preset = $_POST['preset'] ?? 'guardian';
                $presets = $designer->getPresets();
                echo json_encode([
                    'success' => true,
                    'preset' => $presets[$preset] ?? $presets['guardian']
                ]);
                break;
                
            case 'analyze':
                $params = json_decode($_POST['params'], true);
                $analysis = $designer->analyzePersonality($params);
                echo json_encode([
                    'success' => true,
                    'analysis' => $analysis
                ]);
                break;
                
            case 'get_history':
                $history = $designer->getPersonalityHistory();
                echo json_encode([
                    'success' => true,
                    'history' => $history
                ]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        }
    }
    exit;
}

// Inicializar diseñador
global $db;
$designer = new PersonalityDesigner($db);
$current = $designer->getCurrentPersonality();
$presets = $designer->getPresets();
$parameters = $designer->getParameters();

// Análisis inicial
$initial_analysis = $designer->analyzePersonality($current);

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧠 Diseñador de Personalidad IA - <?php echo APP_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --neon-cyan: #00ffff;
            --neon-purple: #ff00ff;
            --neon-pink: #ff00aa;
            --neon-green: #00ff00;
            --neon-yellow: #ffff00;
            --neon-orange: #ff8800;
            --dark-bg: #0a0a0a;
            --panel-bg: #111111;
            --border-color: #333333;
            --text-primary: #ffffff;
            --text-secondary: #aaaaaa;
            --gradient-1: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --gradient-2: linear-gradient(135deg, #00ffff 0%, #ff00ff 100%);
            --gradient-3: linear-gradient(135deg, #00ff88 0%, #00ffff 100%);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--dark-bg);
            color: var(--text-primary);
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }

        /* Animated Background */
        .neural-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            opacity: 0.05;
            background: 
                radial-gradient(circle at 20% 50%, var(--neon-cyan) 0%, transparent 50%),
                radial-gradient(circle at 80% 80%, var(--neon-purple) 0%, transparent 50%),
                radial-gradient(circle at 40% 20%, var(--neon-pink) 0%, transparent 50%);
            animation: neural-pulse 10s ease-in-out infinite;
        }

        @keyframes neural-pulse {
            0%, 100% { transform: scale(1) rotate(0deg); }
            50% { transform: scale(1.1) rotate(5deg); }
        }

        /* Floating Particles */
        .particles {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 0;
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: var(--neon-cyan);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--neon-cyan);
            animation: float-up 20s linear infinite;
        }

        @keyframes float-up {
            from {
                transform: translateY(100vh) translateX(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            to {
                transform: translateY(-100px) translateX(100px);
                opacity: 0;
            }
        }

        /* Main Container */
        .container {
            position: relative;
            z-index: 1;
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .header {
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border: 2px solid var(--neon-purple);
            border-radius: 20px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: linear-gradient(45deg, transparent, var(--neon-cyan), transparent);
            animation: scan 6s linear infinite;
        }

        @keyframes scan {
            0% { transform: translateX(-100%) translateY(-100%); }
            100% { transform: translateX(100%) translateY(100%); }
        }

        .header h1 {
            font-size: 3em;
            margin-bottom: 10px;
            background: var(--gradient-2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
            z-index: 1;
            text-shadow: 0 0 30px rgba(0, 255, 255, 0.5);
        }

        .header p {
            color: var(--text-secondary);
            font-size: 1.2em;
            position: relative;
            z-index: 1;
        }

        /* Main Grid */
        .main-grid {
            display: grid;
            grid-template-columns: 300px 1fr 350px;
            gap: 20px;
            margin-bottom: 20px;
        }

        /* Presets Panel */
        .presets-panel {
            background: var(--panel-bg);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            padding: 20px;
            height: fit-content;
        }

        .preset-title {
            font-size: 1.3em;
            color: var(--neon-cyan);
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .preset-card {
            background: rgba(0, 255, 255, 0.05);
            border: 1px solid rgba(0, 255, 255, 0.2);
            border-radius: 10px;
            padding: 15px;
            margin-bottom: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .preset-card:hover {
            background: rgba(0, 255, 255, 0.1);
            border-color: var(--neon-cyan);
            transform: translateX(5px);
            box-shadow: 0 0 20px rgba(0, 255, 255, 0.3);
        }

        .preset-card.active {
            background: rgba(0, 255, 255, 0.2);
            border-color: var(--neon-cyan);
            box-shadow: 0 0 30px rgba(0, 255, 255, 0.5);
        }

        .preset-icon {
            font-size: 2em;
            margin-bottom: 10px;
        }

        .preset-name {
            font-size: 1.1em;
            color: var(--text-primary);
            margin-bottom: 5px;
        }

        .preset-description {
            font-size: 0.85em;
            color: var(--text-secondary);
        }

        /* Parameters Panel */
        .parameters-panel {
            background: var(--panel-bg);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            padding: 20px;
        }

        .param-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 1.2em;
            color: var(--neon-purple);
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-color);
        }

        .parameter {
            margin-bottom: 25px;
        }

        .param-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }

        .param-label {
            display: flex;
            align-items: center;
            gap: 10px;
            color: var(--text-primary);
        }

        .param-icon {
            font-size: 1.2em;
        }

        .param-value {
            font-size: 1.2em;
            color: var(--neon-green);
            font-weight: bold;
            min-width: 50px;
            text-align: right;
        }

        .slider-container {
            position: relative;
        }

        .param-slider {
            width: 100%;
            height: 8px;
            border-radius: 4px;
            background: rgba(255, 255, 255, 0.1);
            outline: none;
            -webkit-appearance: none;
            appearance: none;
        }

        .param-slider::-webkit-slider-thumb {
            -webkit-appearance: none;
            appearance: none;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--neon-cyan);
            cursor: pointer;
            box-shadow: 0 0 10px var(--neon-cyan);
            transition: all 0.3s ease;
        }

        .param-slider::-webkit-slider-thumb:hover {
            transform: scale(1.2);
            box-shadow: 0 0 20px var(--neon-cyan);
        }

        .param-slider::-moz-range-thumb {
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background: var(--neon-cyan);
            cursor: pointer;
            box-shadow: 0 0 10px var(--neon-cyan);
            border: none;
        }

        .slider-track {
            position: absolute;
            top: 0;
            left: 0;
            height: 8px;
            border-radius: 4px;
            background: linear-gradient(90deg, var(--neon-green), var(--neon-yellow), var(--neon-orange));
            pointer-events: none;
            transition: width 0.3s ease;
        }

        /* Analysis Panel */
        .analysis-panel {
            background: var(--panel-bg);
            border: 1px solid var(--border-color);
            border-radius: 15px;
            padding: 20px;
        }

        .analysis-title {
            font-size: 1.3em;
            color: var(--neon-pink);
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        /* 3D Brain Visualization */
        .brain-container {
            width: 100%;
            height: 200px;
            position: relative;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .brain-3d {
            width: 150px;
            height: 150px;
            position: relative;
            transform-style: preserve-3d;
            animation: rotate-brain 10s linear infinite;
        }

        @keyframes rotate-brain {
            from { transform: rotateY(0deg) rotateX(10deg); }
            to { transform: rotateY(360deg) rotateX(10deg); }
        }

        .brain-hemisphere {
            position: absolute;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 2px solid var(--neon-cyan);
            background: radial-gradient(circle at 30% 30%, var(--neon-purple), transparent);
            opacity: 0.6;
        }

        .brain-hemisphere:nth-child(1) {
            transform: rotateY(0deg);
        }

        .brain-hemisphere:nth-child(2) {
            transform: rotateY(60deg);
        }

        .brain-hemisphere:nth-child(3) {
            transform: rotateY(120deg);
        }

        .neuron {
            position: absolute;
            width: 8px;
            height: 8px;
            background: var(--neon-green);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--neon-green);
            animation: neuron-pulse 2s infinite;
        }

        @keyframes neuron-pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(1.5); opacity: 1; }
        }

        /* Balance Chart */
        .balance-chart {
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(0, 255, 255, 0.05);
            border-radius: 10px;
            border: 1px solid rgba(0, 255, 255, 0.2);
        }

        .balance-bar {
            display: flex;
            align-items: center;
            margin-bottom: 10px;
        }

        .balance-label {
            flex: 0 0 100px;
            color: var(--text-secondary);
            font-size: 0.9em;
        }

        .balance-progress {
            flex: 1;
            height: 20px;
            background: rgba(0, 0, 0, 0.5);
            border-radius: 10px;
            overflow: hidden;
            margin: 0 10px;
        }

        .balance-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--neon-cyan), var(--neon-purple));
            transition: width 0.5s ease;
            position: relative;
            overflow: hidden;
        }

        .balance-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: shine 2s infinite;
        }

        @keyframes shine {
            to { left: 100%; }
        }

        .balance-value {
            flex: 0 0 50px;
            text-align: right;
            color: var(--neon-green);
            font-weight: bold;
        }

        /* Personality Type */
        .personality-type {
            text-align: center;
            padding: 20px;
            background: var(--gradient-1);
            border-radius: 10px;
            margin-bottom: 20px;
        }

        .type-label {
            font-size: 0.9em;
            color: rgba(255, 255, 255, 0.8);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .type-value {
            font-size: 1.5em;
            font-weight: bold;
            color: white;
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.5);
        }

        /* Warnings */
        .warnings {
            margin-bottom: 20px;
        }

        .warning-item {
            padding: 10px;
            background: rgba(255, 136, 0, 0.1);
            border-left: 3px solid var(--neon-orange);
            border-radius: 5px;
            margin-bottom: 10px;
            font-size: 0.9em;
            color: var(--neon-orange);
        }

        /* Action Buttons */
        .actions {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }

        .btn {
            flex: 1;
            padding: 15px;
            border: 2px solid var(--neon-cyan);
            background: transparent;
            color: var(--neon-cyan);
            border-radius: 10px;
            font-size: 1em;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .btn:hover {
            background: var(--neon-cyan);
            color: var(--dark-bg);
            box-shadow: 0 0 20px var(--neon-cyan);
            transform: translateY(-2px);
        }

        .btn-primary {
            background: var(--gradient-2);
            border: none;
            color: white;
        }

        .btn-primary:hover {
            box-shadow: 0 0 30px rgba(0, 255, 255, 0.5);
        }

        /* Voice Settings */
        .voice-settings {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .voice-option {
            padding: 15px;
            background: rgba(255, 0, 255, 0.05);
            border: 1px solid rgba(255, 0, 255, 0.2);
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-align: center;
        }

        .voice-option:hover {
            background: rgba(255, 0, 255, 0.1);
            border-color: var(--neon-purple);
        }

        .voice-option.selected {
            background: rgba(255, 0, 255, 0.2);
            border-color: var(--neon-purple);
            box-shadow: 0 0 20px rgba(255, 0, 255, 0.3);
        }

        /* Traits Grid */
        .traits-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
            margin-bottom: 20px;
        }

        .trait {
            padding: 10px;
            background: rgba(0, 255, 136, 0.05);
            border: 1px solid rgba(0, 255, 136, 0.2);
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .trait-name {
            color: var(--text-secondary);
            font-size: 0.9em;
        }

        .trait-level {
            color: var(--neon-green);
            font-weight: bold;
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
            
            .presets-panel {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 15px;
            }
            
            .preset-card {
                display: flex;
                align-items: center;
                gap: 15px;
            }
            
            .preset-icon {
                font-size: 1.5em;
                margin-bottom: 0;
            }
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2em;
            }
            
            .voice-settings {
                grid-template-columns: 1fr;
            }
            
            .traits-grid {
                grid-template-columns: 1fr;
            }
            
            .actions {
                flex-direction: column;
            }
        }
    </style>
</head>
<body>
    <!-- Neural Background -->
    <div class="neural-bg"></div>
    
    <!-- Floating Particles -->
    <div class="particles" id="particles"></div>
    
    <!-- Main Container -->
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>🧠 Diseñador de Personalidad IA</h1>
            <p><?php echo APP_NAME; ?> - Configuración Neural Avanzada</p>
        </div>
        
        <!-- Main Grid -->
        <div class="main-grid">
            <!-- Presets Panel -->
            <div class="presets-panel">
                <h2 class="preset-title">⚡ Presets</h2>
                <?php foreach ($presets as $key => $preset): ?>
                <div class="preset-card <?php echo $key === 'guardian' ? 'active' : ''; ?>" onclick="loadPreset('<?php echo $key; ?>')">
                    <div class="preset-icon"><?php echo $preset['icon']; ?></div>
                    <div>
                        <div class="preset-name"><?php echo $preset['name']; ?></div>
                        <div class="preset-description"><?php echo $preset['description']; ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Parameters Panel -->
            <div class="parameters-panel">
                <!-- Core Parameters -->
                <div class="param-section">
                    <h3 class="section-title">🧠 Parámetros Centrales</h3>
                    <?php foreach ($parameters['core'] as $key => $param): ?>
                    <div class="parameter">
                        <div class="param-header">
                            <div class="param-label">
                                <span class="param-icon"><?php echo $param['icon']; ?></span>
                                <span><?php echo $designer->getParameterName($key); ?></span>
                            </div>
                            <div class="param-value" id="value-<?php echo $key; ?>">
                                <?php echo $current[$key] ?? 50; ?>%
                            </div>
                        </div>
                        <div class="slider-container">
                            <div class="slider-track" id="track-<?php echo $key; ?>" style="width: <?php echo $current[$key] ?? 50; ?>%"></div>
                            <input type="range" 
                                   class="param-slider" 
                                   id="slider-<?php echo $key; ?>"
                                   min="<?php echo $param['min']; ?>" 
                                   max="<?php echo $param['max']; ?>" 
                                   value="<?php echo $current[$key] ?? 50; ?>"
                                   oninput="updateParameter('<?php echo $key; ?>', this.value)">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Behavioral Parameters -->
                <div class="param-section">
                    <h3 class="section-title">🎭 Parámetros Comportamentales</h3>
                    <?php foreach ($parameters['behavioral'] as $key => $param): ?>
                    <div class="parameter">
                        <div class="param-header">
                            <div class="param-label">
                                <span class="param-icon"><?php echo $param['icon']; ?></span>
                                <span><?php echo $designer->getParameterName($key); ?></span>
                            </div>
                            <div class="param-value" id="value-<?php echo $key; ?>">
                                <?php echo $current[$key] ?? 50; ?>%
                            </div>
                        </div>
                        <div class="slider-container">
                            <div class="slider-track" id="track-<?php echo $key; ?>" style="width: <?php echo $current[$key] ?? 50; ?>%"></div>
                            <input type="range" 
                                   class="param-slider" 
                                   id="slider-<?php echo $key; ?>"
                                   min="<?php echo $param['min']; ?>" 
                                   max="<?php echo $param['max']; ?>" 
                                   value="<?php echo $current[$key] ?? 50; ?>"
                                   oninput="updateParameter('<?php echo $key; ?>', this.value)">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Quantum Parameters -->
                <div class="param-section">
                    <h3 class="section-title">⚛️ Parámetros Cuánticos</h3>
                    <?php foreach ($parameters['quantum'] as $key => $param): ?>
                    <div class="parameter">
                        <div class="param-header">
                            <div class="param-label">
                                <span class="param-icon"><?php echo $param['icon']; ?></span>
                                <span><?php echo $designer->getParameterName($key); ?></span>
                            </div>
                            <div class="param-value" id="value-<?php echo $key; ?>">
                                <?php echo $current[$key] ?? 50; ?>%
                            </div>
                        </div>
                        <div class="slider-container">
                            <div class="slider-track" id="track-<?php echo $key; ?>" style="width: <?php echo $current[$key] ?? 50; ?>%"></div>
                            <input type="range" 
                                   class="param-slider" 
                                   id="slider-<?php echo $key; ?>"
                                   min="<?php echo $param['min']; ?>" 
                                   max="<?php echo $param['max']; ?>" 
                                   value="<?php echo $current[$key] ?? 50; ?>"
                                   oninput="updateParameter('<?php echo $key; ?>', this.value)">
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Voice Settings -->
                <div class="param-section">
                    <h3 class="section-title">🎤 Configuración de Voz</h3>
                    <div class="voice-settings">
                        <div class="voice-option selected" onclick="selectVoice('professional')">
                            <div>🎩</div>
                            <div>Profesional</div>
                        </div>
                        <div class="voice-option" onclick="selectVoice('friendly')">
                            <div>😊</div>
                            <div>Amigable</div>
                        </div>
                        <div class="voice-option" onclick="selectVoice('technical')">
                            <div>🔧</div>
                            <div>Técnica</div>
                        </div>
                        <div class="voice-option" onclick="selectVoice('casual')">
                            <div>👋</div>
                            <div>Casual</div>
                        </div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="actions">
                    <button class="btn" onclick="resetParameters()">↺ Resetear</button>
                    <button class="btn" onclick="randomizeParameters()">🎲 Aleatorio</button>
                    <button class="btn btn-primary" onclick="savePersonality()">💾 Guardar Personalidad</button>
                </div>
            </div>
            
            <!-- Analysis Panel -->
            <div class="analysis-panel">
                <h2 class="analysis-title">📊 Análisis</h2>
                
                <!-- 3D Brain Visualization -->
                <div class="brain-container">
                    <div class="brain-3d">
                        <div class="brain-hemisphere"></div>
                        <div class="brain-hemisphere"></div>
                        <div class="brain-hemisphere"></div>
                        <div class="neuron" style="top: 30%; left: 40%;"></div>
                        <div class="neuron" style="top: 60%; left: 60%;"></div>
                        <div class="neuron" style="top: 50%; left: 30%;"></div>
                        <div class="neuron" style="top: 40%; left: 70%;"></div>
                    </div>
                </div>
                
                <!-- Personality Type -->
                <div class="personality-type">
                    <div class="type-label">Tipo de Personalidad</div>
                    <div class="type-value" id="personality-type"><?php echo $initial_analysis['type']; ?></div>
                </div>
                
                <!-- Balance Chart -->
                <div class="balance-chart">
                    <h4 style="color: var(--neon-cyan); margin-bottom: 15px;">Balance de Personalidad</h4>
                    <div class="balance-bar">
                        <span class="balance-label">Lógico</span>
                        <div class="balance-progress">
                            <div class="balance-fill" id="balance-logical" style="width: <?php echo $initial_analysis['balance']['logical']; ?>%"></div>
                        </div>
                        <span class="balance-value" id="value-balance-logical"><?php echo $initial_analysis['balance']['logical']; ?>%</span>
                    </div>
                    <div class="balance-bar">
                        <span class="balance-label">Emocional</span>
                        <div class="balance-progress">
                            <div class="balance-fill" id="balance-emotional" style="width: <?php echo $initial_analysis['balance']['emotional']; ?>%"></div>
                        </div>
                        <span class="balance-value" id="value-balance-emotional"><?php echo $initial_analysis['balance']['emotional']; ?>%</span>
                    </div>
                    <div class="balance-bar">
                        <span class="balance-label">Creativo</span>
                        <div class="balance-progress">
                            <div class="balance-fill" id="balance-creative" style="width: <?php echo $initial_analysis['balance']['creative']; ?>%"></div>
                        </div>
                        <span class="balance-value" id="value-balance-creative"><?php echo $initial_analysis['balance']['creative']; ?>%</span>
                    </div>
                </div>
                
                <!-- Traits -->
                <div class="param-section">
                    <h4 style="color: var(--neon-green); margin-bottom: 15px;">Rasgos Dominantes</h4>
                    <div class="traits-grid">
                        <?php 
                        $traits = json_decode($current['traits'] ?? '{}', true);
                        foreach ($traits as $trait => $level): 
                        ?>
                        <div class="trait">
                            <span class="trait-name"><?php echo ucfirst($trait); ?></span>
                            <span class="trait-level"><?php echo $level; ?>%</span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Warnings -->
                <div class="warnings" id="warnings">
                    <?php foreach ($initial_analysis['warnings'] as $warning): ?>
                    <div class="warning-item">⚠️ <?php echo $warning; ?></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Current parameters
        let currentParams = <?php echo json_encode($current); ?>;
        let selectedVoice = 'professional';
        
        // Create floating particles
        function createParticles() {
            const container = document.getElementById('particles');
            for (let i = 0; i < 30; i++) {
                const particle = document.createElement('div');
                particle.className = 'particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 20 + 's';
                particle.style.animationDuration = (15 + Math.random() * 10) + 's';
                container.appendChild(particle);
            }
        }
        
        // Update parameter
        function updateParameter(key, value) {
            currentParams[key] = parseFloat(value);
            
            // Update UI
            document.getElementById('value-' + key).textContent = value + '%';
            document.getElementById('track-' + key).style.width = value + '%';
            
            // Analyze personality
            analyzePersonality();
        }
        
        // Load preset
        function loadPreset(presetKey) {
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax=1&action=load_preset&preset=${presetKey}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const preset = data.preset;
                    
                    // Update all parameters
                    for (const [key, value] of Object.entries(preset.params)) {
                        if (document.getElementById('slider-' + key)) {
                            document.getElementById('slider-' + key).value = value;
                            updateParameter(key, value);
                        }
                    }
                    
                    // Update active preset
                    document.querySelectorAll('.preset-card').forEach(card => {
                        card.classList.remove('active');
                    });
                    event.currentTarget.classList.add('active');
                }
            });
        }
        
        // Analyze personality
        function analyzePersonality() {
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax=1&action=analyze&params=${JSON.stringify(currentParams)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const analysis = data.analysis;
                    
                    // Update personality type
                    document.getElementById('personality-type').textContent = analysis.type;
                    
                    // Update balance
                    ['logical', 'emotional', 'creative'].forEach(type => {
                        const value = analysis.balance[type];
                        document.getElementById('balance-' + type).style.width = value + '%';
                        document.getElementById('value-balance-' + type).textContent = value + '%';
                    });
                    
                    // Update warnings
                    const warningsDiv = document.getElementById('warnings');
                    warningsDiv.innerHTML = '';
                    analysis.warnings.forEach(warning => {
                        const div = document.createElement('div');
                        div.className = 'warning-item';
                        div.textContent = '⚠️ ' + warning;
                        warningsDiv.appendChild(div);
                    });
                }
            });
        }
        
        // Select voice
        function selectVoice(voice) {
            selectedVoice = voice;
            document.querySelectorAll('.voice-option').forEach(option => {
                option.classList.remove('selected');
            });
            event.currentTarget.classList.add('selected');
        }
        
        // Reset parameters
        function resetParameters() {
            const defaultValue = 50;
            document.querySelectorAll('.param-slider').forEach(slider => {
                slider.value = defaultValue;
                const key = slider.id.replace('slider-', '');
                updateParameter(key, defaultValue);
            });
        }
        
        // Randomize parameters
        function randomizeParameters() {
            document.querySelectorAll('.param-slider').forEach(slider => {
                const min = parseInt(slider.min);
                const max = parseInt(slider.max);
                const value = Math.floor(Math.random() * (max - min + 1)) + min;
                slider.value = value;
                const key = slider.id.replace('slider-', '');
                updateParameter(key, value);
            });
        }
        
        // Save personality
        function savePersonality() {
            const personalityData = {
                ...currentParams,
                name: prompt('Nombre de la personalidad:') || 'Personalidad Personalizada',
                description: prompt('Descripción:') || 'Configuración personalizada',
                voice_tone: selectedVoice,
                language_style: 'adaptive',
                primary_objective: 'balanced',
                ethical_framework: 'protective',
                traits: {
                    protective: currentParams.analytical_power || 50,
                    analytical: currentParams.analytical_power || 50,
                    creative: currentParams.creativity_index || 50,
                    empathetic: currentParams.empathy_level || 50,
                    logical: currentParams.analytical_power || 50,
                    intuitive: currentParams.creativity_index || 50,
                    cautious: 85,
                    adaptive: currentParams.learning_rate || 50
                }
            };
            
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax=1&action=save_personality&data=${JSON.stringify(personalityData)}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ ' + data.message);
                } else {
                    alert('❌ Error: ' + data.message);
                }
            });
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            createParticles();
        });
    </script>
</body>
</html>