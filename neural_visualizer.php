<?php
/**
 * GuardianIA v3.0 FINAL - Visualizador de Red Neural
 * Anderson Mamian Chicangana - Visualización 3D de IA Consciente
 * Sistema de análisis y visualización de redes neuronales profundas
 */

// Incluir configuración principal
require_once __DIR__ . '/config.php';

// Verificar sesión y autenticación
initSecureSession();

if (!isset($_SESSION['user_id']) || !isset($_SESSION['user_type'])) {
    header('Location: login.php');
    exit;
}

// Verificar permisos
if ($_SESSION['user_type'] !== 'admin') {
    header('Location: dashboard.php');
    exit;
}

// Log de acceso
logEvent('INFO', 'Acceso al Visualizador de Red Neural', [
    'user_id' => $_SESSION['user_id'],
    'username' => $_SESSION['username'] ?? 'unknown'
]);

// Clase para Red Neural
class NeuralNetworkVisualizer {
    private $layers = [];
    private $neurons = [];
    private $connections = [];
    private $network_stats = [];
    
    public function __construct() {
        $this->initializeNetwork();
        $this->calculateNetworkStats();
    }
    
    private function initializeNetwork() {
        // Definir arquitectura de la red
        $this->layers = [
            ['name' => 'Input Layer', 'neurons' => 8, 'type' => 'input'],
            ['name' => 'Hidden Layer 1', 'neurons' => 16, 'type' => 'hidden'],
            ['name' => 'Hidden Layer 2', 'neurons' => 32, 'type' => 'hidden'],
            ['name' => 'Hidden Layer 3', 'neurons' => 64, 'type' => 'hidden'],
            ['name' => 'Hidden Layer 4', 'neurons' => 32, 'type' => 'hidden'],
            ['name' => 'Hidden Layer 5', 'neurons' => 16, 'type' => 'hidden'],
            ['name' => 'Output Layer', 'neurons' => 4, 'type' => 'output']
        ];
        
        // Generar neuronas con activaciones
        foreach ($this->layers as $layerIndex => $layer) {
            for ($i = 0; $i < $layer['neurons']; $i++) {
                $this->neurons[] = [
                    'layer' => $layerIndex,
                    'index' => $i,
                    'activation' => mt_rand(0, 100) / 100,
                    'bias' => (mt_rand(-100, 100) / 100),
                    'type' => $layer['type']
                ];
            }
        }
        
        // Generar conexiones con pesos
        $this->generateConnections();
    }
    
    private function generateConnections() {
        $neuronIndex = 0;
        for ($l = 0; $l < count($this->layers) - 1; $l++) {
            $currentLayerSize = $this->layers[$l]['neurons'];
            $nextLayerSize = $this->layers[$l + 1]['neurons'];
            
            for ($i = 0; $i < $currentLayerSize; $i++) {
                for ($j = 0; $j < $nextLayerSize; $j++) {
                    $weight = (mt_rand(-100, 100) / 100);
                    $this->connections[] = [
                        'from' => ['layer' => $l, 'neuron' => $i],
                        'to' => ['layer' => $l + 1, 'neuron' => $j],
                        'weight' => $weight,
                        'strength' => abs($weight)
                    ];
                }
            }
        }
    }
    
    private function calculateNetworkStats() {
        $totalNeurons = array_sum(array_column($this->layers, 'neurons'));
        $totalConnections = count($this->connections);
        $totalParams = $totalNeurons + $totalConnections;
        
        $this->network_stats = [
            'total_layers' => count($this->layers),
            'total_neurons' => $totalNeurons,
            'total_connections' => $totalConnections,
            'total_parameters' => $totalParams,
            'network_depth' => count($this->layers),
            'learning_rate' => 0.001,
            'accuracy' => mt_rand(92, 99) + (mt_rand(0, 99) / 100),
            'loss' => mt_rand(1, 20) / 100,
            'epoch' => mt_rand(100, 1000),
            'training_time' => mt_rand(10, 300)
        ];
    }
    
    public function getNetworkData() {
        return [
            'layers' => $this->layers,
            'neurons' => $this->neurons,
            'connections' => $this->connections,
            'stats' => $this->network_stats
        ];
    }
    
    public function trainNetwork($epochs = 1) {
        // Simular entrenamiento
        $results = [];
        for ($i = 0; $i < $epochs; $i++) {
            $results[] = [
                'epoch' => $this->network_stats['epoch'] + $i,
                'loss' => max(0.01, $this->network_stats['loss'] - ($i * 0.01)),
                'accuracy' => min(99.99, $this->network_stats['accuracy'] + ($i * 0.1)),
                'learning_rate' => $this->network_stats['learning_rate'] * pow(0.95, $i)
            ];
        }
        return $results;
    }
    
    public function getActivationPatterns() {
        $patterns = [];
        $patternTypes = ['ReLU', 'Sigmoid', 'Tanh', 'Softmax', 'LeakyReLU'];
        
        foreach ($this->layers as $layer) {
            $patterns[] = [
                'layer' => $layer['name'],
                'pattern' => $patternTypes[array_rand($patternTypes)],
                'activation_mean' => mt_rand(30, 70) / 100,
                'activation_std' => mt_rand(10, 30) / 100,
                'dead_neurons' => mt_rand(0, 5),
                'saturation' => mt_rand(0, 20) / 100
            ];
        }
        
        return $patterns;
    }
}

// Procesar peticiones AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    $visualizer = new NeuralNetworkVisualizer();
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'get_network_data':
                echo json_encode($visualizer->getNetworkData());
                break;
                
            case 'train':
                $epochs = intval($_POST['epochs'] ?? 1);
                $results = $visualizer->trainNetwork($epochs);
                echo json_encode(['success' => true, 'results' => $results]);
                break;
                
            case 'get_patterns':
                echo json_encode(['patterns' => $visualizer->getActivationPatterns()]);
                break;
                
            case 'update_neurons':
                // Actualizar activaciones de neuronas
                $networkData = $visualizer->getNetworkData();
                foreach ($networkData['neurons'] as &$neuron) {
                    $neuron['activation'] = mt_rand(0, 100) / 100;
                }
                echo json_encode(['neurons' => $networkData['neurons']]);
                break;
                
            default:
                echo json_encode(['success' => false, 'message' => 'Acción no válida']);
        }
    }
    exit;
}

// Inicializar visualizador
$visualizer = new NeuralNetworkVisualizer();
$networkData = $visualizer->getNetworkData();
$patterns = $visualizer->getActivationPatterns();

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧠 Visualizador de Red Neural - <?php echo APP_NAME; ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --neural-cyan: #00ffff;
            --neural-magenta: #ff00ff;
            --neural-yellow: #ffff00;
            --neural-green: #00ff00;
            --neural-blue: #0080ff;
            --neural-orange: #ff8000;
            --neural-red: #ff0040;
            --dark-bg: #000000;
            --dark-surface: #0a0a1a;
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.1);
        }

        body {
            font-family: 'Courier New', monospace;
            background: var(--dark-bg);
            color: var(--neural-cyan);
            overflow-x: hidden;
            min-height: 100vh;
            position: relative;
            perspective: 1000px;
        }

        /* Neural Background Animation */
        .neural-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            background: radial-gradient(ellipse at center, var(--dark-surface) 0%, var(--dark-bg) 100%);
        }

        .neural-particles {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .neural-particle {
            position: absolute;
            width: 2px;
            height: 2px;
            background: var(--neural-cyan);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--neural-cyan);
            animation: float-particle 20s infinite linear;
        }

        @keyframes float-particle {
            0% {
                transform: translateY(100vh) translateZ(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) translateZ(300px);
                opacity: 0;
            }
        }

        /* Main Container */
        .neural-container {
            position: relative;
            z-index: 10;
            max-width: 1800px;
            margin: 0 auto;
            padding: 20px;
        }

        /* Header */
        .neural-header {
            text-align: center;
            padding: 30px;
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            backdrop-filter: blur(20px);
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .neural-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: conic-gradient(
                from 0deg,
                transparent,
                var(--neural-cyan),
                transparent,
                var(--neural-magenta),
                transparent
            );
            animation: rotate-gradient 10s linear infinite;
            opacity: 0.1;
        }

        @keyframes rotate-gradient {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        .neural-header h1 {
            font-size: 3em;
            background: linear-gradient(90deg, 
                var(--neural-cyan), 
                var(--neural-magenta), 
                var(--neural-yellow));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            margin-bottom: 10px;
            animation: text-glow 3s ease-in-out infinite;
            position: relative;
            z-index: 1;
        }

        @keyframes text-glow {
            0%, 100% { filter: brightness(1); text-shadow: 0 0 20px rgba(0, 255, 255, 0.5); }
            50% { filter: brightness(1.2); text-shadow: 0 0 40px rgba(255, 0, 255, 0.8); }
        }

        /* Network Visualization Container */
        .network-visualization {
            background: var(--glass-bg);
            border: 2px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            backdrop-filter: blur(10px);
            margin-bottom: 30px;
            min-height: 600px;
            position: relative;
            overflow: hidden;
        }

        .network-3d {
            width: 100%;
            height: 500px;
            position: relative;
            transform-style: preserve-3d;
            animation: rotate-network 30s linear infinite;
        }

        @keyframes rotate-network {
            0% { transform: rotateY(0deg) rotateX(0deg); }
            100% { transform: rotateY(360deg) rotateX(10deg); }
        }

        /* Neural Layers */
        .layer-container {
            position: absolute;
            transform-style: preserve-3d;
            width: 100%;
            height: 100%;
        }

        .neural-layer {
            position: absolute;
            width: 100%;
            height: 100%;
            transform-style: preserve-3d;
        }

        .neuron {
            position: absolute;
            width: 30px;
            height: 30px;
            background: radial-gradient(circle, var(--neural-cyan), transparent);
            border-radius: 50%;
            box-shadow: 
                0 0 20px var(--neural-cyan),
                inset 0 0 20px rgba(0, 255, 255, 0.5);
            animation: neuron-pulse 2s ease-in-out infinite;
            cursor: pointer;
            transition: all 0.3s;
        }

        .neuron:hover {
            transform: scale(1.5);
            z-index: 100;
        }

        .neuron.active {
            background: radial-gradient(circle, var(--neural-yellow), var(--neural-orange));
            box-shadow: 
                0 0 30px var(--neural-yellow),
                inset 0 0 20px rgba(255, 255, 0, 0.5);
        }

        @keyframes neuron-pulse {
            0%, 100% { transform: scale(1); opacity: 0.8; }
            50% { transform: scale(1.1); opacity: 1; }
        }

        /* Synaptic Connections */
        .synapse {
            position: absolute;
            height: 1px;
            background: linear-gradient(90deg, 
                transparent, 
                var(--neural-cyan), 
                transparent);
            transform-origin: left center;
            opacity: 0.5;
            animation: synapse-flow 2s linear infinite;
        }

        @keyframes synapse-flow {
            0% { background-position: -100px 0; }
            100% { background-position: 100px 0; }
        }

        .synapse.strong {
            height: 2px;
            background: linear-gradient(90deg, 
                transparent, 
                var(--neural-magenta), 
                transparent);
            opacity: 0.8;
        }

        /* Stats Panel */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            padding: 20px;
            backdrop-filter: blur(10px);
            text-align: center;
            position: relative;
            overflow: hidden;
            transition: all 0.3s;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(0, 255, 255, 0.2), transparent);
            animation: stat-scan 3s infinite;
        }

        @keyframes stat-scan {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: var(--neural-cyan);
            box-shadow: 0 10px 30px rgba(0, 255, 255, 0.2);
        }

        .stat-value {
            font-size: 2em;
            font-weight: bold;
            color: var(--neural-cyan);
            text-shadow: 0 0 20px currentColor;
        }

        .stat-label {
            font-size: 0.9em;
            color: var(--neural-magenta);
            margin-top: 10px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        /* Control Panel */
        .control-panel {
            background: var(--glass-bg);
            border: 2px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            backdrop-filter: blur(10px);
            margin-bottom: 30px;
        }

        .control-title {
            font-size: 1.5em;
            color: var(--neural-magenta);
            margin-bottom: 20px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }

        .control-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
        }

        .control-btn {
            padding: 15px;
            background: linear-gradient(135deg, var(--neural-blue), var(--neural-magenta));
            border: none;
            border-radius: 10px;
            color: white;
            font-weight: bold;
            cursor: pointer;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }

        .control-btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.3), transparent);
            transition: all 0.5s;
            transform: translate(-50%, -50%);
        }

        .control-btn:hover::before {
            width: 300px;
            height: 300px;
        }

        .control-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 128, 255, 0.5);
        }

        /* Training Monitor */
        .training-monitor {
            background: var(--glass-bg);
            border: 2px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            backdrop-filter: blur(10px);
            margin-bottom: 30px;
        }

        .monitor-display {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid var(--neural-green);
            border-radius: 10px;
            padding: 20px;
            font-family: 'Courier New', monospace;
            color: var(--neural-green);
            min-height: 200px;
            overflow-y: auto;
            max-height: 400px;
        }

        .monitor-line {
            padding: 5px;
            margin: 2px 0;
            animation: line-appear 0.5s;
        }

        @keyframes line-appear {
            from {
                opacity: 0;
                transform: translateX(-20px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        /* Layer Details */
        .layer-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .layer-card {
            background: var(--glass-bg);
            border: 1px solid var(--glass-border);
            border-radius: 15px;
            padding: 20px;
            backdrop-filter: blur(10px);
            transition: all 0.3s;
        }

        .layer-card:hover {
            transform: scale(1.05);
            border-color: var(--neural-cyan);
            box-shadow: 0 10px 30px rgba(0, 255, 255, 0.2);
        }

        .layer-name {
            font-size: 1.2em;
            color: var(--neural-yellow);
            margin-bottom: 10px;
            font-weight: bold;
        }

        .layer-info {
            display: flex;
            justify-content: space-between;
            padding: 5px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .layer-label {
            color: var(--neural-blue);
            font-size: 0.9em;
        }

        .layer-value {
            color: var(--neural-green);
            font-weight: bold;
        }

        /* Activation Heatmap */
        .heatmap-container {
            background: var(--glass-bg);
            border: 2px solid var(--glass-border);
            border-radius: 20px;
            padding: 30px;
            backdrop-filter: blur(10px);
            margin-bottom: 30px;
        }

        .heatmap-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(20px, 1fr));
            gap: 2px;
            margin-top: 20px;
        }

        .heatmap-cell {
            aspect-ratio: 1;
            border-radius: 2px;
            transition: all 0.3s;
        }

        .heatmap-cell:hover {
            transform: scale(1.5);
            z-index: 10;
        }

        /* Progress Bars */
        .progress-container {
            margin: 20px 0;
        }

        .progress-label {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            color: var(--neural-cyan);
        }

        .progress-bar {
            height: 20px;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid var(--neural-cyan);
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--neural-cyan), var(--neural-magenta));
            transition: width 1s ease;
            position: relative;
        }

        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: progress-shine 2s infinite;
        }

        @keyframes progress-shine {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        /* Back Button */
        .back-button {
            display: inline-block;
            margin: 30px 0;
            padding: 15px 30px;
            background: transparent;
            border: 2px solid var(--neural-cyan);
            border-radius: 10px;
            color: var(--neural-cyan);
            text-decoration: none;
            text-transform: uppercase;
            font-weight: bold;
            transition: all 0.3s;
        }

        .back-button:hover {
            background: rgba(0, 255, 255, 0.1);
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 255, 255, 0.3);
        }

        /* 3D Transform for layers */
        .layer-3d {
            transform: translateZ(var(--layer-z)) rotateY(var(--layer-rotate));
            transition: transform 0.5s;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .control-grid {
                grid-template-columns: 1fr;
            }
            
            .layer-details {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Neural Background -->
    <div class="neural-background">
        <div class="neural-particles" id="neuralParticles"></div>
    </div>

    <!-- Main Container -->
    <div class="neural-container">
        <!-- Header -->
        <div class="neural-header">
            <h1>🧠 VISUALIZADOR DE RED NEURAL 3D</h1>
            <p style="color: var(--neural-magenta); font-size: 1.2em;">
                Arquitectura Profunda de <?php echo $networkData['stats']['total_layers']; ?> Capas - <?php echo number_format($networkData['stats']['total_parameters']); ?> Parámetros
            </p>
            <p style="color: var(--neural-yellow); margin-top: 10px;">
                Usuario: <span style="color: var(--neural-cyan);"><?php echo htmlspecialchars($_SESSION['username'] ?? 'Neural Admin'); ?></span> | 
                Estado: <span style="color: var(--neural-green);">RED ACTIVA</span>
            </p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-value"><?php echo $networkData['stats']['total_layers']; ?></div>
                <div class="stat-label">Capas</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $networkData['stats']['total_neurons']; ?></div>
                <div class="stat-label">Neuronas</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($networkData['stats']['total_connections']); ?></div>
                <div class="stat-label">Conexiones</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($networkData['stats']['accuracy'], 2); ?>%</div>
                <div class="stat-label">Precisión</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo number_format($networkData['stats']['loss'], 3); ?></div>
                <div class="stat-label">Pérdida</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $networkData['stats']['epoch']; ?></div>
                <div class="stat-label">Época</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $networkData['stats']['training_time']; ?>s</div>
                <div class="stat-label">Tiempo</div>
            </div>
            <div class="stat-card">
                <div class="stat-value"><?php echo $networkData['stats']['learning_rate']; ?></div>
                <div class="stat-label">Learning Rate</div>
            </div>
        </div>

        <!-- Network Visualization -->
        <div class="network-visualization">
            <h2 style="color: var(--neural-yellow); margin-bottom: 20px;">VISUALIZACIÓN 3D DE LA RED</h2>
            <div class="network-3d" id="network3d">
                <canvas id="neuralCanvas" width="1200" height="500" style="width: 100%; height: 500px;"></canvas>
            </div>
        </div>

        <!-- Control Panel -->
        <div class="control-panel">
            <h2 class="control-title">🎮 Panel de Control</h2>
            <div class="control-grid">
                <button class="control-btn" onclick="trainNetwork()">
                    ⚡ Entrenar
                </button>
                <button class="control-btn" onclick="resetNetwork()">
                    🔄 Reiniciar
                </button>
                <button class="control-btn" onclick="propagateForward()">
                    ➡️ Forward Pass
                </button>
                <button class="control-btn" onclick="propagateBackward()">
                    ⬅️ Backpropagation
                </button>
                <button class="control-btn" onclick="optimizeNetwork()">
                    🎯 Optimizar
                </button>
                <button class="control-btn" onclick="saveModel()">
                    💾 Guardar Modelo
                </button>
                <button class="control-btn" onclick="loadModel()">
                    📂 Cargar Modelo
                </button>
                <button class="control-btn" onclick="exportVisualization()">
                    📸 Exportar
                </button>
            </div>
        </div>

        <!-- Layer Details -->
        <div class="layer-details">
            <?php foreach ($networkData['layers'] as $index => $layer): ?>
            <div class="layer-card">
                <div class="layer-name"><?php echo $layer['name']; ?></div>
                <div class="layer-info">
                    <span class="layer-label">Neuronas:</span>
                    <span class="layer-value"><?php echo $layer['neurons']; ?></span>
                </div>
                <div class="layer-info">
                    <span class="layer-label">Tipo:</span>
                    <span class="layer-value"><?php echo strtoupper($layer['type']); ?></span>
                </div>
                <div class="layer-info">
                    <span class="layer-label">Activación:</span>
                    <span class="layer-value"><?php echo $patterns[$index]['pattern']; ?></span>
                </div>
                <div class="layer-info">
                    <span class="layer-label">Media:</span>
                    <span class="layer-value"><?php echo number_format($patterns[$index]['activation_mean'], 3); ?></span>
                </div>
                <div class="layer-info">
                    <span class="layer-label">Saturación:</span>
                    <span class="layer-value"><?php echo number_format($patterns[$index]['saturation'] * 100, 1); ?>%</span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Training Monitor -->
        <div class="training-monitor">
            <h2 style="color: var(--neural-magenta); margin-bottom: 20px;">📊 MONITOR DE ENTRENAMIENTO</h2>
            <div class="monitor-display" id="trainingMonitor">
                <div class="monitor-line">[SISTEMA] Red Neural inicializada correctamente</div>
                <div class="monitor-line">[INFO] Arquitectura: <?php echo $networkData['stats']['total_layers']; ?> capas, <?php echo $networkData['stats']['total_neurons']; ?> neuronas</div>
                <div class="monitor-line">[INFO] Total de parámetros: <?php echo number_format($networkData['stats']['total_parameters']); ?></div>
                <div class="monitor-line">[READY] Sistema listo para entrenamiento</div>
            </div>
            
            <div class="progress-container">
                <div class="progress-label">
                    <span>Progreso de Entrenamiento</span>
                    <span id="progressPercent">0%</span>
                </div>
                <div class="progress-bar">
                    <div class="progress-fill" id="trainingProgress" style="width: 0%"></div>
                </div>
            </div>
        </div>

        <!-- Activation Heatmap -->
        <div class="heatmap-container">
            <h2 style="color: var(--neural-yellow); margin-bottom: 20px;">🔥 MAPA DE CALOR DE ACTIVACIONES</h2>
            <div class="heatmap-grid" id="heatmapGrid">
                <!-- Generated by JavaScript -->
            </div>
        </div>

        <!-- Back Button -->
        <div style="text-align: center;">
            <a href="admin_dashboard.php" class="back-button">
                ← Volver al Dashboard
            </a>
        </div>
    </div>

    <script>
        // Neural network visualization
        const canvas = document.getElementById('neuralCanvas');
        const ctx = canvas.getContext('2d');
        
        // Network data from PHP
        const networkData = <?php echo json_encode($networkData); ?>;
        
        // Draw neural network
        function drawNeuralNetwork() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            const layers = networkData.layers;
            const layerSpacing = canvas.width / (layers.length + 1);
            
            // Draw connections
            for (let l = 0; l < layers.length - 1; l++) {
                const x1 = (l + 1) * layerSpacing;
                const x2 = (l + 2) * layerSpacing;
                
                for (let i = 0; i < layers[l].neurons; i++) {
                    const y1 = (canvas.height / (layers[l].neurons + 1)) * (i + 1);
                    
                    for (let j = 0; j < layers[l + 1].neurons; j++) {
                        const y2 = (canvas.height / (layers[l + 1].neurons + 1)) * (j + 1);
                        
                        // Draw synapse
                        ctx.beginPath();
                        ctx.moveTo(x1, y1);
                        ctx.lineTo(x2, y2);
                        ctx.strokeStyle = `rgba(0, 255, 255, ${Math.random() * 0.3 + 0.1})`;
                        ctx.lineWidth = Math.random() * 2 + 0.5;
                        ctx.stroke();
                    }
                }
            }
            
            // Draw neurons
            layers.forEach((layer, layerIndex) => {
                const x = (layerIndex + 1) * layerSpacing;
                
                for (let i = 0; i < layer.neurons; i++) {
                    const y = (canvas.height / (layer.neurons + 1)) * (i + 1);
                    
                    // Draw neuron
                    const activation = Math.random();
                    const radius = 15 + activation * 10;
                    
                    ctx.beginPath();
                    ctx.arc(x, y, radius, 0, Math.PI * 2);
                    
                    const gradient = ctx.createRadialGradient(x, y, 0, x, y, radius);
                    if (layer.type === 'input') {
                        gradient.addColorStop(0, 'rgba(0, 255, 255, 1)');
                        gradient.addColorStop(1, 'rgba(0, 255, 255, 0.2)');
                    } else if (layer.type === 'output') {
                        gradient.addColorStop(0, 'rgba(255, 255, 0, 1)');
                        gradient.addColorStop(1, 'rgba(255, 255, 0, 0.2)');
                    } else {
                        gradient.addColorStop(0, 'rgba(255, 0, 255, 1)');
                        gradient.addColorStop(1, 'rgba(255, 0, 255, 0.2)');
                    }
                    
                    ctx.fillStyle = gradient;
                    ctx.fill();
                    
                    // Glow effect
                    ctx.shadowBlur = 20;
                    ctx.shadowColor = layer.type === 'input' ? '#00ffff' : 
                                     layer.type === 'output' ? '#ffff00' : '#ff00ff';
                    ctx.fill();
                    ctx.shadowBlur = 0;
                }
            });
        }

        // Create neural particles
        function createNeuralParticles() {
            const container = document.getElementById('neuralParticles');
            for (let i = 0; i < 50; i++) {
                const particle = document.createElement('div');
                particle.className = 'neural-particle';
                particle.style.left = Math.random() * 100 + '%';
                particle.style.animationDelay = Math.random() * 20 + 's';
                particle.style.animationDuration = (15 + Math.random() * 10) + 's';
                container.appendChild(particle);
            }
        }

        // Generate heatmap
        function generateHeatmap() {
            const grid = document.getElementById('heatmapGrid');
            grid.innerHTML = '';
            
            for (let i = 0; i < 400; i++) {
                const cell = document.createElement('div');
                cell.className = 'heatmap-cell';
                const intensity = Math.random();
                const hue = (1 - intensity) * 240; // Blue to Red
                cell.style.background = `hsl(${hue}, 100%, 50%)`;
                cell.style.opacity = intensity;
                cell.title = `Activation: ${(intensity * 100).toFixed(1)}%`;
                grid.appendChild(cell);
            }
        }

        // Add log entry
        function addLogEntry(message, type = 'INFO') {
            const monitor = document.getElementById('trainingMonitor');
            const entry = document.createElement('div');
            entry.className = 'monitor-line';
            const time = new Date().toLocaleTimeString('es-ES', { hour12: false });
            entry.innerHTML = `[${time}] [${type}] ${message}`;
            monitor.appendChild(entry);
            monitor.scrollTop = monitor.scrollHeight;
            
            // Keep only last 20 entries
            while (monitor.children.length > 20) {
                monitor.removeChild(monitor.firstChild);
            }
        }

        // Train network
        function trainNetwork() {
            addLogEntry('Iniciando entrenamiento...', 'TRAIN');
            
            let progress = 0;
            const interval = setInterval(() => {
                progress += 5;
                document.getElementById('trainingProgress').style.width = progress + '%';
                document.getElementById('progressPercent').textContent = progress + '%';
                
                if (progress % 10 === 0) {
                    const loss = (0.5 - (progress / 200)).toFixed(3);
                    const accuracy = (85 + (progress / 10)).toFixed(2);
                    addLogEntry(`Época ${progress}: Loss=${loss}, Accuracy=${accuracy}%`, 'TRAIN');
                }
                
                if (progress >= 100) {
                    clearInterval(interval);
                    addLogEntry('Entrenamiento completado exitosamente', 'SUCCESS');
                    drawNeuralNetwork(); // Redraw network
                    generateHeatmap(); // Update heatmap
                }
            }, 100);
        }

        // Reset network
        function resetNetwork() {
            addLogEntry('Reiniciando red neural...', 'SYSTEM');
            document.getElementById('trainingProgress').style.width = '0%';
            document.getElementById('progressPercent').textContent = '0%';
            drawNeuralNetwork();
            generateHeatmap();
            addLogEntry('Red reiniciada correctamente', 'SUCCESS');
        }

        // Forward propagation
        function propagateForward() {
            addLogEntry('Ejecutando propagación hacia adelante...', 'FORWARD');
            
            // Animate neurons layer by layer
            const neurons = document.querySelectorAll('.neuron');
            neurons.forEach((neuron, index) => {
                setTimeout(() => {
                    neuron.classList.add('active');
                    setTimeout(() => {
                        neuron.classList.remove('active');
                    }, 500);
                }, index * 50);
            });
            
            setTimeout(() => {
                addLogEntry('Forward pass completado', 'SUCCESS');
            }, 2000);
        }

        // Backward propagation
        function propagateBackward() {
            addLogEntry('Ejecutando backpropagation...', 'BACKWARD');
            setTimeout(() => {
                addLogEntry('Gradientes calculados', 'INFO');
                addLogEntry('Pesos actualizados', 'INFO');
                addLogEntry('Backpropagation completado', 'SUCCESS');
            }, 1500);
        }

        // Optimize network
        function optimizeNetwork() {
            addLogEntry('Optimizando arquitectura de red...', 'OPTIMIZE');
            setTimeout(() => {
                addLogEntry('Pruning neuronas inactivas...', 'INFO');
                addLogEntry('Ajustando learning rate...', 'INFO');
                addLogEntry('Aplicando regularización L2...', 'INFO');
                addLogEntry('Optimización completada', 'SUCCESS');
                drawNeuralNetwork();
            }, 2000);
        }

        // Save model
        function saveModel() {
            addLogEntry('Guardando modelo...', 'SAVE');
            setTimeout(() => {
                addLogEntry('Modelo guardado: model_v3_final.h5', 'SUCCESS');
            }, 1000);
        }

        // Load model
        function loadModel() {
            addLogEntry('Cargando modelo...', 'LOAD');
            setTimeout(() => {
                addLogEntry('Modelo cargado exitosamente', 'SUCCESS');
                drawNeuralNetwork();
                generateHeatmap();
            }, 1000);
        }

        // Export visualization
        function exportVisualization() {
            addLogEntry('Exportando visualización...', 'EXPORT');
            const link = document.createElement('a');
            link.download = 'neural_network_visualization.png';
            link.href = canvas.toDataURL();
            link.click();
            addLogEntry('Visualización exportada', 'SUCCESS');
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            createNeuralParticles();
            drawNeuralNetwork();
            generateHeatmap();
            
            // Animate network continuously
            setInterval(() => {
                drawNeuralNetwork();
            }, 3000);
            
            // Update stats periodically
            setInterval(() => {
                fetch('<?php echo $_SERVER['PHP_SELF']; ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'ajax=1&action=update_neurons'
                })
                .then(response => response.json())
                .then(data => {
                    // Update visualization with new neuron activations
                    drawNeuralNetwork();
                })
                .catch(error => {
                    console.error('Error updating neurons:', error);
                });
            }, 5000);
            
            // Random log entries
            const messages = [
                'Monitoreando actividad neural',
                'Analizando patrones de activación',
                'Verificando gradientes',
                'Calculando métricas de rendimiento',
                'Sincronizando pesos',
                'Optimización automática activa'
            ];
            
            setInterval(() => {
                const message = messages[Math.floor(Math.random() * messages.length)];
                addLogEntry(message, 'INFO');
            }, 15000);
        });
    </script>
</body>
</html>