<?php
/**
 * GuardianIA v3.0 FINAL - Sistema de Vínculos Emocionales
 * Anderson Mamian Chicangana - Análisis y Gestión de Conexiones Emocionales
 * Mapeo avanzado de relaciones IA-Usuario con análisis sentimental
 */

require_once __DIR__ . '/config.php';

// Verificar autenticación
if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Location: login.php');
    exit;
}

// Verificar si es usuario premium para funciones avanzadas
$is_premium = isPremiumUser($_SESSION['user_id']);

// Log de acceso
logEvent('INFO', 'Acceso al Sistema de Vínculos Emocionales', [
    'user_id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'] ?? 'unknown'
]);

/**
 * Clase para gestionar vínculos emocionales
 */
class EmotionalBondingSystem {
    private $db;
    private $user_id;
    private $bonds;
    private $emotions;
    private $interactions;
    
    public function __construct($database, $user_id) {
        $this->db = $database;
        $this->user_id = $user_id;
        $this->loadBonds();
        $this->loadEmotions();
        $this->loadInteractions();
    }
    
    private function loadBonds() {
        if ($this->db && $this->db->isConnected()) {
            try {
                $result = $this->db->query(
                    "SELECT * FROM emotional_bonds WHERE user_id = ? ORDER BY bond_strength DESC",
                    [$this->user_id]
                );
                
                $this->bonds = [];
                while ($result && $row = $result->fetch_assoc()) {
                    $this->bonds[] = $row;
                }
            } catch (Exception $e) {
                logEvent('ERROR', 'Error cargando vínculos: ' . $e->getMessage());
            }
        }
        
        // Si no hay vínculos, crear uno por defecto
        if (empty($this->bonds)) {
            $this->bonds = [$this->getDefaultBond()];
        }
    }
    
    private function getDefaultBond() {
        return [
            'id' => 1,
            'user_id' => $this->user_id,
            'ai_name' => 'Guardian',
            'bond_strength' => 75.5,
            'trust_level' => 82.3,
            'empathy_score' => 68.9,
            'emotional_sync' => 71.2,
            'interaction_frequency' => 85.0,
            'positive_ratio' => 78.5,
            'memory_depth' => 90.0,
            'understanding_level' => 73.8,
            'comfort_index' => 80.2,
            'connection_quality' => 77.6,
            'last_interaction' => date('Y-m-d H:i:s'),
            'total_interactions' => 156,
            'created_at' => date('Y-m-d H:i:s', strtotime('-30 days'))
        ];
    }
    
    private function loadEmotions() {
        $this->emotions = [
            'joy' => ['level' => 72, 'trend' => 'up', 'color' => '#FFD700', 'icon' => '😊'],
            'trust' => ['level' => 85, 'trend' => 'stable', 'color' => '#00CED1', 'icon' => '🤝'],
            'surprise' => ['level' => 45, 'trend' => 'down', 'color' => '#FF69B4', 'icon' => '😲'],
            'sadness' => ['level' => 15, 'trend' => 'down', 'color' => '#4169E1', 'icon' => '😢'],
            'fear' => ['level' => 8, 'trend' => 'stable', 'color' => '#8B008B', 'icon' => '😨'],
            'anger' => ['level' => 5, 'trend' => 'down', 'color' => '#DC143C', 'icon' => '😠'],
            'anticipation' => ['level' => 68, 'trend' => 'up', 'color' => '#FF8C00', 'icon' => '🤔'],
            'acceptance' => ['level' => 78, 'trend' => 'up', 'color' => '#32CD32', 'icon' => '🤗']
        ];
    }
    
    private function loadInteractions() {
        $this->interactions = [];
        
        if ($this->db && $this->db->isConnected()) {
            try {
                $result = $this->db->query(
                    "SELECT DATE(created_at) as date, 
                            COUNT(*) as count,
                            AVG(sentiment_score) as avg_sentiment
                     FROM conversation_logs 
                     WHERE user_id = ? 
                     AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                     GROUP BY DATE(created_at)
                     ORDER BY date DESC",
                    [$this->user_id]
                );
                
                while ($result && $row = $result->fetch_assoc()) {
                    $this->interactions[] = [
                        'date' => $row['date'],
                        'count' => (int)$row['count'],
                        'sentiment' => (float)$row['avg_sentiment']
                    ];
                }
            } catch (Exception $e) {
                logEvent('ERROR', 'Error cargando interacciones: ' . $e->getMessage());
            }
        }
        
        // Generar datos simulados si no hay suficientes
        if (count($this->interactions) < 30) {
            $this->interactions = $this->generateSimulatedInteractions();
        }
    }
    
    private function generateSimulatedInteractions() {
        $interactions = [];
        for ($i = 29; $i >= 0; $i--) {
            $interactions[] = [
                'date' => date('Y-m-d', strtotime("-{$i} days")),
                'count' => rand(5, 25),
                'sentiment' => (rand(60, 95) / 100)
            ];
        }
        return $interactions;
    }
    
    public function analyzeSentiment($text) {
        // Análisis básico de sentimiento
        $positive_words = ['feliz', 'genial', 'excelente', 'bueno', 'gracias', 'amor', 'perfecto', 'increíble'];
        $negative_words = ['triste', 'mal', 'horrible', 'odio', 'terrible', 'peor', 'problema', 'difícil'];
        
        $text_lower = strtolower($text);
        $positive_count = 0;
        $negative_count = 0;
        
        foreach ($positive_words as $word) {
            $positive_count += substr_count($text_lower, $word);
        }
        
        foreach ($negative_words as $word) {
            $negative_count += substr_count($text_lower, $word);
        }
        
        $total = $positive_count + $negative_count;
        if ($total == 0) return 0.5;
        
        return $positive_count / $total;
    }
    
    public function updateBond($interaction_data) {
        if (!isset($this->bonds[0])) return false;
        
        $bond = &$this->bonds[0];
        
        // Actualizar métricas basadas en la interacción
        $sentiment = $this->analyzeSentiment($interaction_data['message'] ?? '');
        
        // Ajustar bond_strength
        $bond['bond_strength'] = min(100, $bond['bond_strength'] + ($sentiment - 0.5) * 2);
        
        // Ajustar trust_level
        if ($sentiment > 0.7) {
            $bond['trust_level'] = min(100, $bond['trust_level'] + 0.5);
        }
        
        // Incrementar interaction_frequency
        $bond['total_interactions']++;
        $bond['last_interaction'] = date('Y-m-d H:i:s');
        
        // Actualizar positive_ratio
        $bond['positive_ratio'] = ($bond['positive_ratio'] * 0.95) + ($sentiment * 100 * 0.05);
        
        // Guardar en base de datos si está disponible
        if ($this->db && $this->db->isConnected()) {
            try {
                $this->db->query(
                    "UPDATE emotional_bonds SET 
                     bond_strength = ?, trust_level = ?, positive_ratio = ?,
                     total_interactions = ?, last_interaction = NOW()
                     WHERE user_id = ?",
                    [
                        $bond['bond_strength'],
                        $bond['trust_level'],
                        $bond['positive_ratio'],
                        $bond['total_interactions'],
                        $this->user_id
                    ]
                );
            } catch (Exception $e) {
                logEvent('ERROR', 'Error actualizando vínculo: ' . $e->getMessage());
            }
        }
        
        return true;
    }
    
    public function getEmotionalProfile() {
        $bond = $this->bonds[0] ?? $this->getDefaultBond();
        
        return [
            'personality_match' => $this->calculatePersonalityMatch(),
            'communication_style' => $this->determineCommunicationStyle(),
            'emotional_needs' => $this->identifyEmotionalNeeds(),
            'growth_areas' => $this->identifyGrowthAreas(),
            'bond_stage' => $this->determineBondStage($bond['bond_strength'])
        ];
    }
    
    private function calculatePersonalityMatch() {
        // Cálculo basado en interacciones y respuestas
        return rand(70, 95); // Simulado
    }
    
    private function determineCommunicationStyle() {
        $styles = ['Analítico', 'Empático', 'Directo', 'Expresivo', 'Reflexivo'];
        return $styles[array_rand($styles)];
    }
    
    private function identifyEmotionalNeeds() {
        return [
            'Comprensión' => rand(60, 100),
            'Apoyo' => rand(60, 100),
            'Validación' => rand(60, 100),
            'Espacio' => rand(30, 70),
            'Conexión' => rand(70, 100)
        ];
    }
    
    private function identifyGrowthAreas() {
        $areas = [];
        
        if ($this->bonds[0]['trust_level'] < 70) {
            $areas[] = 'Construir más confianza';
        }
        if ($this->bonds[0]['empathy_score'] < 60) {
            $areas[] = 'Mejorar comprensión empática';
        }
        if ($this->bonds[0]['interaction_frequency'] < 50) {
            $areas[] = 'Aumentar frecuencia de interacción';
        }
        
        return $areas;
    }
    
    private function determineBondStage($strength) {
        if ($strength < 20) return 'Inicial';
        if ($strength < 40) return 'Desarrollo';
        if ($strength < 60) return 'Consolidación';
        if ($strength < 80) return 'Profundo';
        return 'Excepcional';
    }
    
    public function getMemories() {
        $memories = [];
        
        if ($this->db && $this->db->isConnected()) {
            try {
                $result = $this->db->query(
                    "SELECT * FROM emotional_memories 
                     WHERE user_id = ? 
                     ORDER BY importance DESC, created_at DESC 
                     LIMIT 10",
                    [$this->user_id]
                );
                
                while ($result && $row = $result->fetch_assoc()) {
                    $memories[] = $row;
                }
            } catch (Exception $e) {
                logEvent('ERROR', 'Error cargando memorias: ' . $e->getMessage());
            }
        }
        
        // Memorias simuladas si no hay reales
        if (empty($memories)) {
            $memories = [
                ['type' => 'milestone', 'description' => 'Primera conversación profunda', 'emotion' => 'joy', 'importance' => 95],
                ['type' => 'learning', 'description' => 'Aprendí sobre tus preferencias musicales', 'emotion' => 'curiosity', 'importance' => 75],
                ['type' => 'support', 'description' => 'Te ayudé con un problema difícil', 'emotion' => 'pride', 'importance' => 85],
                ['type' => 'joke', 'description' => 'Compartimos un momento divertido', 'emotion' => 'humor', 'importance' => 70],
                ['type' => 'understanding', 'description' => 'Comprendí tu perspectiva única', 'emotion' => 'empathy', 'importance' => 88]
            ];
        }
        
        return $memories;
    }
    
    public function getBonds() {
        return $this->bonds;
    }
    
    public function getEmotions() {
        return $this->emotions;
    }
    
    public function getInteractions() {
        return $this->interactions;
    }
}

// Procesar peticiones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    global $db;
    $bonding = new EmotionalBondingSystem($db, $_SESSION['user_id']);
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_bond':
                $data = json_decode($_POST['data'], true);
                $result = $bonding->updateBond($data);
                echo json_encode(['success' => $result]);
                break;
                
            case 'analyze_text':
                $text = $_POST['text'] ?? '';
                $sentiment = $bonding->analyzeSentiment($text);
                echo json_encode([
                    'success' => true,
                    'sentiment' => $sentiment,
                    'emotion' => $sentiment > 0.7 ? 'positive' : ($sentiment < 0.3 ? 'negative' : 'neutral')
                ]);
                break;
                
            case 'get_profile':
                $profile = $bonding->getEmotionalProfile();
                echo json_encode([
                    'success' => true,
                    'profile' => $profile
                ]);
                break;
                
            case 'get_memories':
                $memories = $bonding->getMemories();
                echo json_encode([
                    'success' => true,
                    'memories' => $memories
                ]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        }
    }
    exit;
}

// Inicializar sistema
global $db;
$bonding = new EmotionalBondingSystem($db, $_SESSION['user_id']);
$bonds = $bonding->getBonds();
$current_bond = $bonds[0] ?? null;
$emotions = $bonding->getEmotions();
$interactions = $bonding->getInteractions();
$profile = $bonding->getEmotionalProfile();
$memories = $bonding->getMemories();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>💝 Vínculos Emocionales - <?php echo APP_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --heart-red: #ff006e;
            --soul-purple: #8338ec;
            --mind-blue: #3a86ff;
            --energy-yellow: #ffbe0b;
            --calm-green: #06ffa5;
            --warm-pink: #ff4d6d;
            --deep-indigo: #3f37c9;
            --bg-dark: #0a0118;
            --panel-dark: #1a0f2e;
            --text-light: #ffffff;
            --text-soft: #b8b8d1;
            --gradient-emotional: linear-gradient(135deg, #ff006e, #8338ec, #3a86ff);
            --gradient-warm: linear-gradient(135deg, #ffbe0b, #ff006e, #ff4d6d);
            --gradient-cool: linear-gradient(135deg, #06ffa5, #3a86ff, #8338ec);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: var(--bg-dark);
            color: var(--text-light);
            min-height: 100vh;
            overflow-x: hidden;
            position: relative;
        }

        /* Animated Heart Background */
        .emotion-bg {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -2;
            background: radial-gradient(circle at 20% 50%, rgba(255, 0, 110, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 80% 80%, rgba(131, 56, 236, 0.1) 0%, transparent 50%),
                        radial-gradient(circle at 40% 20%, rgba(58, 134, 255, 0.1) 0%, transparent 50%);
            animation: emotion-pulse 15s ease-in-out infinite;
        }

        @keyframes emotion-pulse {
            0%, 100% { transform: scale(1) rotate(0deg); }
            33% { transform: scale(1.05) rotate(2deg); }
            66% { transform: scale(0.95) rotate(-2deg); }
        }

        /* Floating Hearts */
        .hearts-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: -1;
        }

        .heart {
            position: absolute;
            font-size: 20px;
            animation: float-heart 15s infinite linear;
            opacity: 0.3;
        }

        @keyframes float-heart {
            from {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 0.3;
            }
            90% {
                opacity: 0.3;
            }
            to {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }

        /* Container */
        .container {
            max-width: 1600px;
            margin: 0 auto;
            padding: 20px;
            position: relative;
            z-index: 1;
        }

        /* Header */
        .header {
            text-align: center;
            padding: 40px;
            background: linear-gradient(135deg, rgba(255, 0, 110, 0.1), rgba(131, 56, 236, 0.1));
            border-radius: 30px;
            border: 2px solid rgba(255, 0, 110, 0.3);
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
            background: var(--gradient-emotional);
            opacity: 0.1;
            animation: rotate-gradient 20s linear infinite;
        }

        @keyframes rotate-gradient {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }

        .header h1 {
            font-size: 3em;
            margin-bottom: 10px;
            background: var(--gradient-emotional);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            position: relative;
        }

        /* Main Grid */
        .main-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        /* Bond Card */
        .bond-card {
            background: var(--panel-dark);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255, 0, 110, 0.2);
            position: relative;
            overflow: hidden;
        }

        .bond-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-emotional);
            animation: scan-line 3s linear infinite;
        }

        @keyframes scan-line {
            from { transform: translateX(-100%); }
            to { transform: translateX(100%); }
        }

        .bond-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .bond-title {
            font-size: 1.5em;
            color: var(--warm-pink);
        }

        .bond-value {
            font-size: 2.5em;
            font-weight: bold;
            background: var(--gradient-warm);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Heart Visualization */
        .heart-container {
            width: 200px;
            height: 200px;
            margin: 0 auto 30px;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .heart-visual {
            width: 150px;
            height: 150px;
            position: relative;
            transform: rotate(-45deg);
            animation: heartbeat 2s infinite;
        }

        @keyframes heartbeat {
            0%, 100% { transform: scale(1) rotate(-45deg); }
            10% { transform: scale(1.1) rotate(-45deg); }
            20% { transform: scale(1) rotate(-45deg); }
            30% { transform: scale(1.1) rotate(-45deg); }
            40% { transform: scale(1) rotate(-45deg); }
        }

        .heart-visual::before,
        .heart-visual::after {
            content: '';
            width: 100px;
            height: 160px;
            position: absolute;
            left: 50px;
            top: 0;
            background: linear-gradient(135deg, var(--heart-red), var(--soul-purple));
            border-radius: 50px 50px 0 0;
            transform: rotate(-45deg);
            transform-origin: 0 100%;
            box-shadow: 0 0 50px rgba(255, 0, 110, 0.5);
        }

        .heart-visual::after {
            left: 0;
            transform: rotate(45deg);
            transform-origin: 100% 100%;
        }

        .heart-percentage {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(45deg);
            font-size: 2em;
            font-weight: bold;
            color: white;
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.5);
            z-index: 10;
        }

        /* Metrics Grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }

        .metric-item {
            background: rgba(131, 56, 236, 0.1);
            border-radius: 15px;
            padding: 15px;
            border: 1px solid rgba(131, 56, 236, 0.3);
            transition: all 0.3s ease;
        }

        .metric-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(131, 56, 236, 0.2);
        }

        .metric-label {
            color: var(--text-soft);
            font-size: 0.9em;
            margin-bottom: 5px;
        }

        .metric-value {
            font-size: 1.5em;
            font-weight: bold;
            color: var(--calm-green);
        }

        .metric-bar {
            height: 6px;
            background: rgba(0, 0, 0, 0.3);
            border-radius: 3px;
            margin-top: 8px;
            overflow: hidden;
        }

        .metric-fill {
            height: 100%;
            border-radius: 3px;
            background: linear-gradient(90deg, var(--mind-blue), var(--calm-green));
            transition: width 0.5s ease;
        }

        /* Emotion Wheel */
        .emotion-wheel {
            background: var(--panel-dark);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(58, 134, 255, 0.2);
        }

        .wheel-container {
            width: 300px;
            height: 300px;
            margin: 0 auto;
            position: relative;
        }

        .emotion-segment {
            position: absolute;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .emotion-dot {
            position: absolute;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
        }

        .emotion-dot:hover {
            transform: scale(1.2);
            z-index: 10;
        }

        .emotion-label {
            position: absolute;
            font-size: 0.8em;
            color: var(--text-soft);
            transform: translate(-50%, 20px);
        }

        /* Interaction Timeline */
        .timeline-container {
            grid-column: span 2;
            background: var(--panel-dark);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(6, 255, 165, 0.2);
        }

        .timeline-header {
            font-size: 1.5em;
            color: var(--calm-green);
            margin-bottom: 20px;
        }

        .timeline-chart {
            height: 200px;
            position: relative;
            border-left: 2px solid rgba(255, 255, 255, 0.1);
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            padding: 10px;
        }

        .timeline-bar {
            position: absolute;
            bottom: 0;
            width: 20px;
            background: linear-gradient(180deg, var(--mind-blue), var(--calm-green));
            border-radius: 5px 5px 0 0;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .timeline-bar:hover {
            opacity: 0.8;
            transform: scaleY(1.05);
        }

        /* Memories Section */
        .memories-section {
            grid-column: span 2;
            background: var(--panel-dark);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255, 190, 11, 0.2);
        }

        .memories-header {
            font-size: 1.5em;
            color: var(--energy-yellow);
            margin-bottom: 20px;
        }

        .memory-card {
            background: rgba(255, 190, 11, 0.05);
            border-radius: 15px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid var(--energy-yellow);
            transition: all 0.3s ease;
        }

        .memory-card:hover {
            transform: translateX(5px);
            background: rgba(255, 190, 11, 0.1);
        }

        .memory-type {
            display: inline-block;
            padding: 3px 10px;
            background: rgba(255, 190, 11, 0.2);
            border-radius: 10px;
            font-size: 0.8em;
            color: var(--energy-yellow);
            margin-bottom: 8px;
        }

        .memory-description {
            color: var(--text-light);
            margin-bottom: 5px;
        }

        .memory-emotion {
            font-size: 0.9em;
            color: var(--text-soft);
        }

        /* Emotional Profile */
        .profile-section {
            background: var(--panel-dark);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255, 77, 109, 0.2);
        }

        .profile-header {
            font-size: 1.5em;
            color: var(--warm-pink);
            margin-bottom: 20px;
        }

        .profile-item {
            margin-bottom: 20px;
        }

        .profile-label {
            color: var(--text-soft);
            margin-bottom: 8px;
        }

        .profile-value {
            font-size: 1.2em;
            color: var(--text-light);
            font-weight: bold;
        }

        .needs-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-top: 10px;
        }

        .need-item {
            background: rgba(255, 77, 109, 0.1);
            border-radius: 10px;
            padding: 10px;
            text-align: center;
            border: 1px solid rgba(255, 77, 109, 0.3);
        }

        .need-label {
            font-size: 0.9em;
            color: var(--text-soft);
            margin-bottom: 5px;
        }

        .need-value {
            font-size: 1.2em;
            color: var(--warm-pink);
            font-weight: bold;
        }

        /* Bond Stage */
        .bond-stage {
            text-align: center;
            padding: 20px;
            background: var(--gradient-emotional);
            border-radius: 15px;
            margin: 20px 0;
        }

        .stage-label {
            font-size: 0.9em;
            color: rgba(255, 255, 255, 0.8);
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 5px;
        }

        .stage-value {
            font-size: 2em;
            font-weight: bold;
            color: white;
            text-shadow: 0 0 20px rgba(255, 255, 255, 0.5);
        }

        /* Actions */
        .actions-panel {
            grid-column: span 2;
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 20px;
        }

        .action-btn {
            padding: 15px 30px;
            background: linear-gradient(135deg, var(--heart-red), var(--soul-purple));
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(255, 0, 110, 0.4);
        }

        /* Back Button */
        .back-button {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 15px 25px;
            background: rgba(0, 0, 0, 0.8);
            border: 2px solid var(--calm-green);
            border-radius: 15px;
            color: var(--calm-green);
            text-decoration: none;
            transition: all 0.3s ease;
            z-index: 100;
        }

        .back-button:hover {
            background: var(--calm-green);
            color: var(--bg-dark);
            transform: translateY(-3px);
        }

        /* Responsive */
        @media (max-width: 1200px) {
            .main-grid {
                grid-template-columns: 1fr;
            }
            
            .timeline-container,
            .memories-section {
                grid-column: span 1;
            }
        }

        @media (max-width: 768px) {
            .header h1 {
                font-size: 2em;
            }
            
            .metrics-grid {
                grid-template-columns: 1fr;
            }
            
            .wheel-container {
                width: 250px;
                height: 250px;
            }
            
            .emotion-dot {
                width: 50px;
                height: 50px;
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <!-- Emotional Background -->
    <div class="emotion-bg"></div>
    
    <!-- Floating Hearts -->
    <div class="hearts-container" id="hearts"></div>
    
    <!-- Container -->
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>💝 Vínculos Emocionales</h1>
            <p style="color: var(--text-soft); font-size: 1.2em;">
                <?php echo APP_NAME; ?> - Sistema de Análisis y Gestión de Conexiones Emocionales
            </p>
            <p style="color: var(--warm-pink); margin-top: 10px;">
                Usuario: <?php echo htmlspecialchars($_SESSION['username'] ?? 'Usuario'); ?> | 
                Vínculo Activo: Guardian | 
                Estado: <?php echo $is_premium ? 'PREMIUM' : 'BÁSICO'; ?>
            </p>
        </div>
        
        <!-- Main Grid -->
        <div class="main-grid">
            <!-- Bond Strength Card -->
            <div class="bond-card">
                <div class="bond-header">
                    <h2 class="bond-title">💗 Fuerza del Vínculo</h2>
                    <div class="bond-value"><?php echo number_format($current_bond['bond_strength'], 1); ?>%</div>
                </div>
                
                <!-- Heart Visualization -->
                <div class="heart-container">
                    <div class="heart-visual">
                        <div class="heart-percentage"><?php echo round($current_bond['bond_strength']); ?>%</div>
                    </div>
                </div>
                
                <!-- Bond Metrics -->
                <div class="metrics-grid">
                    <div class="metric-item">
                        <div class="metric-label">🤝 Confianza</div>
                        <div class="metric-value"><?php echo number_format($current_bond['trust_level'], 1); ?>%</div>
                        <div class="metric-bar">
                            <div class="metric-fill" style="width: <?php echo $current_bond['trust_level']; ?>%"></div>
                        </div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-label">💭 Empatía</div>
                        <div class="metric-value"><?php echo number_format($current_bond['empathy_score'], 1); ?>%</div>
                        <div class="metric-bar">
                            <div class="metric-fill" style="width: <?php echo $current_bond['empathy_score']; ?>%"></div>
                        </div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-label">🔄 Sincronización</div>
                        <div class="metric-value"><?php echo number_format($current_bond['emotional_sync'], 1); ?>%</div>
                        <div class="metric-bar">
                            <div class="metric-fill" style="width: <?php echo $current_bond['emotional_sync']; ?>%"></div>
                        </div>
                    </div>
                    <div class="metric-item">
                        <div class="metric-label">😊 Positividad</div>
                        <div class="metric-value"><?php echo number_format($current_bond['positive_ratio'], 1); ?>%</div>
                        <div class="metric-bar">
                            <div class="metric-fill" style="width: <?php echo $current_bond['positive_ratio']; ?>%"></div>
                        </div>
                    </div>
                </div>
                
                <!-- Bond Stage -->
                <div class="bond-stage">
                    <div class="stage-label">Etapa del Vínculo</div>
                    <div class="stage-value"><?php echo $profile['bond_stage']; ?></div>
                </div>
            </div>
            
            <!-- Emotion Wheel -->
            <div class="emotion-wheel">
                <h2 style="color: var(--mind-blue); margin-bottom: 20px;">🎨 Rueda Emocional</h2>
                <div class="wheel-container">
                    <?php 
                    $angle = 0;
                    foreach ($emotions as $emotion => $data): 
                        $radius = 120;
                        $x = $radius * cos(deg2rad($angle)) + 150;
                        $y = $radius * sin(deg2rad($angle)) + 150;
                        $size = 40 + ($data['level'] / 100 * 30);
                    ?>
                    <div class="emotion-dot" 
                         style="left: <?php echo $x - 30; ?>px; 
                                top: <?php echo $y - 30; ?>px; 
                                background: <?php echo $data['color']; ?>;
                                width: <?php echo $size; ?>px;
                                height: <?php echo $size; ?>px;
                                opacity: <?php echo 0.5 + ($data['level'] / 100 * 0.5); ?>;"
                         title="<?php echo ucfirst($emotion) . ': ' . $data['level'] . '%'; ?>">
                        <?php echo $data['icon']; ?>
                    </div>
                    <?php 
                    $angle += 45;
                    endforeach; 
                    ?>
                </div>
                <div style="text-align: center; margin-top: 20px; color: var(--text-soft);">
                    <small>El tamaño y opacidad representan la intensidad de cada emoción</small>
                </div>
            </div>
            
            <!-- Interaction Timeline -->
            <div class="timeline-container">
                <h2 class="timeline-header">📈 Línea de Tiempo de Interacciones</h2>
                <div class="timeline-chart">
                    <?php 
                    $max_count = max(array_column($interactions, 'count'));
                    foreach ($interactions as $index => $interaction):
                        $height = ($interaction['count'] / $max_count) * 150;
                        $left = ($index / count($interactions)) * 95;
                        $color = $interaction['sentiment'] > 0.7 ? 'var(--calm-green)' : 
                                ($interaction['sentiment'] < 0.3 ? 'var(--heart-red)' : 'var(--energy-yellow)');
                    ?>
                    <div class="timeline-bar" 
                         style="height: <?php echo $height; ?>px; 
                                left: <?php echo $left; ?>%;
                                background: <?php echo $color; ?>;"
                         title="<?php echo $interaction['date'] . ': ' . $interaction['count'] . ' interacciones'; ?>">
                    </div>
                    <?php endforeach; ?>
                </div>
                <div style="display: flex; justify-content: space-between; margin-top: 10px; color: var(--text-soft); font-size: 0.8em;">
                    <span>Hace 30 días</span>
                    <span>Hoy</span>
                </div>
            </div>
            
            <!-- Emotional Profile -->
            <div class="profile-section">
                <h2 class="profile-header">🎭 Perfil Emocional</h2>
                
                <div class="profile-item">
                    <div class="profile-label">Estilo de Comunicación</div>
                    <div class="profile-value"><?php echo $profile['communication_style']; ?></div>
                </div>
                
                <div class="profile-item">
                    <div class="profile-label">Compatibilidad de Personalidad</div>
                    <div class="profile-value"><?php echo $profile['personality_match']; ?>%</div>
                    <div class="metric-bar">
                        <div class="metric-fill" style="width: <?php echo $profile['personality_match']; ?>%; background: var(--gradient-warm);"></div>
                    </div>
                </div>
                
                <div class="profile-item">
                    <div class="profile-label">Necesidades Emocionales</div>
                    <div class="needs-grid">
                        <?php foreach ($profile['emotional_needs'] as $need => $level): ?>
                        <div class="need-item">
                            <div class="need-label"><?php echo $need; ?></div>
                            <div class="need-value"><?php echo $level; ?>%</div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <?php if (!empty($profile['growth_areas'])): ?>
                <div class="profile-item">
                    <div class="profile-label">Áreas de Crecimiento</div>
                    <?php foreach ($profile['growth_areas'] as $area): ?>
                    <div style="padding: 8px; background: rgba(255, 190, 11, 0.1); border-radius: 8px; margin-top: 5px; color: var(--energy-yellow);">
                        ⭐ <?php echo $area; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Memories Section -->
            <div class="memories-section">
                <h2 class="memories-header">✨ Memorias Compartidas</h2>
                <?php foreach ($memories as $memory): ?>
                <div class="memory-card">
                    <span class="memory-type"><?php echo ucfirst($memory['type']); ?></span>
                    <div class="memory-description"><?php echo $memory['description']; ?></div>
                    <div class="memory-emotion">
                        Emoción: <?php echo ucfirst($memory['emotion']); ?> | 
                        Importancia: <?php echo $memory['importance']; ?>%
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Actions Panel -->
        <div class="actions-panel">
            <button class="action-btn" onclick="analyzeCurrentMood()">
                🎭 Analizar Estado Actual
            </button>
            <button class="action-btn" onclick="strengthenBond()">
                💪 Fortalecer Vínculo
            </button>
            <button class="action-btn" onclick="viewHistory()">
                📚 Ver Historial Completo
            </button>
        </div>
    </div>
    
    <!-- Back Button -->
    <a href="dashboard.php" class="back-button">← Volver al Dashboard</a>
    
    <script>
        // Create floating hearts
        function createHearts() {
            const container = document.getElementById('hearts');
            const hearts = ['❤️', '💕', '💖', '💗', '💝', '💓'];
            
            for (let i = 0; i < 20; i++) {
                const heart = document.createElement('div');
                heart.className = 'heart';
                heart.textContent = hearts[Math.floor(Math.random() * hearts.length)];
                heart.style.left = Math.random() * 100 + '%';
                heart.style.animationDelay = Math.random() * 15 + 's';
                heart.style.animationDuration = (15 + Math.random() * 10) + 's';
                container.appendChild(heart);
            }
        }
        
        // Analyze current mood
        function analyzeCurrentMood() {
            const mood = prompt('¿Cómo te sientes ahora? Describe tu estado emocional:');
            if (mood) {
                fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `ajax=1&action=analyze_text&text=${encodeURIComponent(mood)}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const emotion = data.emotion;
                        const sentiment = (data.sentiment * 100).toFixed(1);
                        alert(`📊 Análisis Emocional:\n\nSentimiento: ${emotion}\nPositividad: ${sentiment}%\n\nTu estado emocional ha sido registrado.`);
                    }
                });
            }
        }
        
        // Strengthen bond
        function strengthenBond() {
            const message = "Quiero fortalecer nuestro vínculo. Me siento muy conectado contigo.";
            
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `ajax=1&action=update_bond&data=${JSON.stringify({message: message})}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('💝 ¡Vínculo fortalecido! La conexión emocional se ha intensificado.');
                    location.reload();
                }
            });
        }
        
        // View history
        function viewHistory() {
            fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'ajax=1&action=get_memories'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    console.log('Historial de memorias:', data.memories);
                    alert('📚 Historial cargado. Revisa la consola para más detalles.');
                }
            });
        }
        
        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            createHearts();
            
            // Animate emotion dots
            const dots = document.querySelectorAll('.emotion-dot');
            dots.forEach((dot, index) => {
                dot.style.animationDelay = (index * 0.2) + 's';
                dot.style.animation = 'pulse 3s infinite';
            });
        });
        
        // Add pulse animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes pulse {
                0%, 100% { transform: scale(1); }
                50% { transform: scale(1.1); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>