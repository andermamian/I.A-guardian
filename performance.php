<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Optimización y Rendimiento - GuardianIA</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --performance-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --success-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --warning-gradient: linear-gradient(135deg, #ffa726 0%, #fb8c00 100%);
            --energy-gradient: linear-gradient(135deg, #a8edea 0%, #fed6e3 100%);
            
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
                radial-gradient(circle at 20% 80%, rgba(79, 172, 254, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(0, 242, 254, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(67, 233, 123, 0.2) 0%, transparent 50%);
            animation: performancePulse 8s ease-in-out infinite;
        }

        @keyframes performancePulse {
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
            background: var(--performance-gradient);
            color: white;
        }

        /* Main Container */
        .main-container {
            margin-top: 80px;
            padding: 2rem;
            max-width: 1400px;
            margin-left: auto;
            margin-right: auto;
        }

        /* Performance Header */
        .performance-header {
            background: var(--performance-gradient);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .performance-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            animation: performanceShine 4s infinite;
        }

        @keyframes performanceShine {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .performance-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .performance-subtitle {
            font-size: 1.1rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 1.5rem;
        }

        .performance-score {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 2rem;
            margin-top: 1rem;
        }

        .score-item {
            text-align: center;
        }

        .score-value {
            font-size: 2rem;
            font-weight: 800;
            color: white;
        }

        .score-label {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.8);
        }

        /* Metrics Grid */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .metric-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: var(--card-padding);
            position: relative;
            overflow: hidden;
            transition: all var(--animation-speed) ease;
        }

        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px var(--shadow-color);
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .metric-card.cpu::before {
            background: var(--performance-gradient);
        }

        .metric-card.ram::before {
            background: var(--success-gradient);
        }

        .metric-card.storage::before {
            background: var(--warning-gradient);
        }

        .metric-card.battery::before {
            background: var(--energy-gradient);
        }

        .metric-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .metric-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
        }

        .metric-info h3 {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .metric-info p {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Circular Progress */
        .circular-progress {
            position: relative;
            width: 120px;
            height: 120px;
            margin: 0 auto 1rem;
        }

        .circular-progress svg {
            width: 100%;
            height: 100%;
            transform: rotate(-90deg);
        }

        .circular-progress .bg-circle {
            fill: none;
            stroke: rgba(255, 255, 255, 0.1);
            stroke-width: 8;
        }

        .circular-progress .progress-circle {
            fill: none;
            stroke-width: 8;
            stroke-linecap: round;
            transition: stroke-dasharray 1s ease;
        }

        .circular-progress .progress-text {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 1.5rem;
            font-weight: 800;
            color: var(--text-primary);
        }

        /* Optimization Actions */
        .optimization-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .optimization-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: var(--card-padding);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .card-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .optimization-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            margin-bottom: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            transition: all var(--animation-speed) ease;
        }

        .optimization-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(5px);
        }

        .optimization-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .optimization-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
        }

        .optimization-details h4 {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .optimization-details p {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .optimization-btn {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .optimization-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.4);
        }

        .optimization-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* System Analysis */
        .analysis-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .analysis-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: var(--card-padding);
        }

        .chart-container {
            height: 200px;
            margin: 1rem 0;
            position: relative;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .chart-placeholder {
            color: var(--text-secondary);
            font-style: italic;
        }

        /* Maintenance Schedule */
        .schedule-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            margin-bottom: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            border-left: 4px solid var(--primary-gradient);
        }

        .schedule-info h4 {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .schedule-info p {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .schedule-status {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .schedule-status.pending {
            background: rgba(255, 165, 2, 0.2);
            color: #ffa502;
        }

        .schedule-status.completed {
            background: rgba(46, 213, 115, 0.2);
            color: #2ed573;
        }

        .schedule-status.scheduled {
            background: rgba(79, 172, 254, 0.2);
            color: #4facfe;
        }

        /* Progress Bars */
        .progress-container {
            margin: 1rem 0;
        }

        .progress-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
        }

        .progress-bar {
            width: 100%;
            height: 8px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
            overflow: hidden;
            position: relative;
        }

        .progress-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 1s ease;
            position: relative;
        }

        .progress-fill.cpu {
            background: var(--performance-gradient);
        }

        .progress-fill.ram {
            background: var(--success-gradient);
        }

        .progress-fill.storage {
            background: var(--warning-gradient);
        }

        .progress-fill.battery {
            background: var(--energy-gradient);
        }

        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: shimmer 2s infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
            flex-wrap: wrap;
        }

        .action-btn {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .action-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
        }

        .action-btn.success {
            background: var(--success-gradient);
        }

        .action-btn.warning {
            background: var(--warning-gradient);
        }

        .action-btn.energy {
            background: var(--energy-gradient);
        }

        /* Floating Action Button */
        .fab {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 60px;
            height: 60px;
            background: var(--performance-gradient);
            border: none;
            border-radius: 50%;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            box-shadow: 0 8px 25px rgba(79, 172, 254, 0.4);
            transition: all var(--animation-speed) ease;
            z-index: 1000;
        }

        .fab:hover {
            transform: scale(1.1);
            box-shadow: 0 12px 35px rgba(79, 172, 254, 0.6);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .optimization-grid {
                grid-template-columns: 1fr;
            }

            .metrics-grid {
                grid-template-columns: 1fr;
            }

            .analysis-grid {
                grid-template-columns: 1fr;
            }

            .performance-title {
                font-size: 2rem;
            }

            .performance-score {
                flex-direction: column;
                gap: 1rem;
            }

            .main-container {
                padding: 1rem;
            }
        }

        /* Loading States */
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
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
                <li><a href="index.php" class="nav-link">Dashboard</a></li>
                <li><a href="threat_center.php" class="nav-link">Centro de Amenazas</a></li>
                <li><a href="#" class="nav-link active">Rendimiento</a></li>
                <li><a href="chatbot.php" class="nav-link">Asistente IA</a></li>
                <li><a href="settings.php" class="nav-link">Configuración</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Performance Header -->
        <div class="performance-header">
            <h1 class="performance-title">⚡ OPTIMIZADOR IA INTELIGENTE</h1>
            <p class="performance-subtitle">
                Optimización automática impulsada por inteligencia artificial
            </p>
            <div class="performance-score">
                <div class="score-item">
                    <div class="score-value" id="ram-freed">2.4 GB</div>
                    <div class="score-label">RAM Liberada</div>
                </div>
                <div class="score-item">
                    <div class="score-value" id="battery-optimized">+3.2h</div>
                    <div class="score-label">Batería Optimizada</div>
                </div>
                <div class="score-item">
                    <div class="score-value" id="storage-cleaned">1.8 GB</div>
                    <div class="score-label">Almacenamiento Limpio</div>
                </div>
                <div class="score-item">
                    <div class="score-value" id="performance-score">94</div>
                    <div class="score-label">Score de Rendimiento</div>
                </div>
            </div>
        </div>

        <!-- System Metrics -->
        <div class="metrics-grid">
            <div class="metric-card cpu">
                <div class="metric-header">
                    <div class="metric-info">
                        <h3>Procesador (CPU)</h3>
                        <p>Intel Core i7-12700K</p>
                    </div>
                    <div class="metric-icon" style="background: var(--performance-gradient);">
                        <i class="fas fa-microchip"></i>
                    </div>
                </div>
                <div class="circular-progress">
                    <svg viewBox="0 0 100 100">
                        <circle class="bg-circle" cx="50" cy="50" r="40"></circle>
                        <circle class="progress-circle" cx="50" cy="50" r="40" 
                                stroke="url(#cpuGradient)" 
                                stroke-dasharray="0 251.2" 
                                id="cpu-circle"></circle>
                        <defs>
                            <linearGradient id="cpuGradient">
                                <stop offset="0%" stop-color="#4facfe"/>
                                <stop offset="100%" stop-color="#00f2fe"/>
                            </linearGradient>
                        </defs>
                    </svg>
                    <div class="progress-text" id="cpu-percentage">45%</div>
                </div>
                <div class="progress-container">
                    <div class="progress-header">
                        <span>Uso actual</span>
                        <span id="cpu-usage">45%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill cpu" style="width: 45%" id="cpu-bar"></div>
                    </div>
                </div>
            </div>

            <div class="metric-card ram">
                <div class="metric-header">
                    <div class="metric-info">
                        <h3>Memoria RAM</h3>
                        <p>16 GB DDR4</p>
                    </div>
                    <div class="metric-icon" style="background: var(--success-gradient);">
                        <i class="fas fa-memory"></i>
                    </div>
                </div>
                <div class="circular-progress">
                    <svg viewBox="0 0 100 100">
                        <circle class="bg-circle" cx="50" cy="50" r="40"></circle>
                        <circle class="progress-circle" cx="50" cy="50" r="40" 
                                stroke="url(#ramGradient)" 
                                stroke-dasharray="0 251.2" 
                                id="ram-circle"></circle>
                        <defs>
                            <linearGradient id="ramGradient">
                                <stop offset="0%" stop-color="#43e97b"/>
                                <stop offset="100%" stop-color="#38f9d7"/>
                            </linearGradient>
                        </defs>
                    </svg>
                    <div class="progress-text" id="ram-percentage">67%</div>
                </div>
                <div class="progress-container">
                    <div class="progress-header">
                        <span>10.7 GB / 16 GB</span>
                        <span id="ram-usage">67%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill ram" style="width: 67%" id="ram-bar"></div>
                    </div>
                </div>
            </div>

            <div class="metric-card storage">
                <div class="metric-header">
                    <div class="metric-info">
                        <h3>Almacenamiento</h3>
                        <p>SSD 1TB NVMe</p>
                    </div>
                    <div class="metric-icon" style="background: var(--warning-gradient);">
                        <i class="fas fa-hdd"></i>
                    </div>
                </div>
                <div class="circular-progress">
                    <svg viewBox="0 0 100 100">
                        <circle class="bg-circle" cx="50" cy="50" r="40"></circle>
                        <circle class="progress-circle" cx="50" cy="50" r="40" 
                                stroke="url(#storageGradient)" 
                                stroke-dasharray="0 251.2" 
                                id="storage-circle"></circle>
                        <defs>
                            <linearGradient id="storageGradient">
                                <stop offset="0%" stop-color="#ffa726"/>
                                <stop offset="100%" stop-color="#fb8c00"/>
                            </linearGradient>
                        </defs>
                    </svg>
                    <div class="progress-text" id="storage-percentage">78%</div>
                </div>
                <div class="progress-container">
                    <div class="progress-header">
                        <span>780 GB / 1 TB</span>
                        <span id="storage-usage">78%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill storage" style="width: 78%" id="storage-bar"></div>
                    </div>
                </div>
            </div>

            <div class="metric-card battery">
                <div class="metric-header">
                    <div class="metric-info">
                        <h3>Batería</h3>
                        <p>Li-ion 4500mAh</p>
                    </div>
                    <div class="metric-icon" style="background: var(--energy-gradient);">
                        <i class="fas fa-battery-three-quarters"></i>
                    </div>
                </div>
                <div class="circular-progress">
                    <svg viewBox="0 0 100 100">
                        <circle class="bg-circle" cx="50" cy="50" r="40"></circle>
                        <circle class="progress-circle" cx="50" cy="50" r="40" 
                                stroke="url(#batteryGradient)" 
                                stroke-dasharray="0 251.2" 
                                id="battery-circle"></circle>
                        <defs>
                            <linearGradient id="batteryGradient">
                                <stop offset="0%" stop-color="#a8edea"/>
                                <stop offset="100%" stop-color="#fed6e3"/>
                            </linearGradient>
                        </defs>
                    </svg>
                    <div class="progress-text" id="battery-percentage">89%</div>
                </div>
                <div class="progress-container">
                    <div class="progress-header">
                        <span>Tiempo restante</span>
                        <span id="battery-time">6h 23m</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill battery" style="width: 89%" id="battery-bar"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Optimization Actions -->
        <div class="optimization-grid">
            <div class="optimization-card">
                <div class="card-header">
                    <h2 class="card-title">🤖 Optimización Automática</h2>
                    <button class="action-btn" onclick="runFullOptimization()">
                        <i class="fas fa-magic"></i>
                        Optimizar Todo
                    </button>
                </div>

                <div class="optimization-item">
                    <div class="optimization-info">
                        <div class="optimization-icon" style="background: var(--success-gradient);">
                            <i class="fas fa-broom"></i>
                        </div>
                        <div class="optimization-details">
                            <h4>Limpieza Inteligente de Archivos</h4>
                            <p>Elimina archivos temporales, caché y duplicados</p>
                        </div>
                    </div>
                    <button class="optimization-btn" onclick="cleanFiles()">
                        <i class="fas fa-play"></i>
                        Limpiar
                    </button>
                </div>

                <div class="optimization-item">
                    <div class="optimization-info">
                        <div class="optimization-icon" style="background: var(--performance-gradient);">
                            <i class="fas fa-memory"></i>
                        </div>
                        <div class="optimization-details">
                            <h4>Gestión de Memoria RAM</h4>
                            <p>Libera memoria no utilizada y optimiza procesos</p>
                        </div>
                    </div>
                    <button class="optimization-btn" onclick="optimizeRAM()">
                        <i class="fas fa-play"></i>
                        Optimizar
                    </button>
                </div>

                <div class="optimization-item">
                    <div class="optimization-info">
                        <div class="optimization-icon" style="background: var(--energy-gradient);">
                            <i class="fas fa-battery-half"></i>
                        </div>
                        <div class="optimization-details">
                            <h4>Optimización de Batería por IA</h4>
                            <p>Ajusta configuraciones para maximizar duración</p>
                        </div>
                    </div>
                    <button class="optimization-btn" onclick="optimizeBattery()">
                        <i class="fas fa-play"></i>
                        Optimizar
                    </button>
                </div>

                <div class="optimization-item">
                    <div class="optimization-info">
                        <div class="optimization-icon" style="background: var(--warning-gradient);">
                            <i class="fas fa-compress"></i>
                        </div>
                        <div class="optimization-details">
                            <h4>Compresión Inteligente</h4>
                            <p>Comprime archivos grandes sin pérdida de calidad</p>
                        </div>
                    </div>
                    <button class="optimization-btn" onclick="compressFiles()">
                        <i class="fas fa-play"></i>
                        Comprimir
                    </button>
                </div>
            </div>

            <div class="optimization-card">
                <div class="card-header">
                    <h2 class="card-title">📅 Mantenimiento Predictivo</h2>
                </div>

                <div class="schedule-item">
                    <div class="schedule-info">
                        <h4>Limpieza de Registro</h4>
                        <p>Programada para mañana a las 02:00</p>
                    </div>
                    <div class="schedule-status scheduled">Programada</div>
                </div>

                <div class="schedule-item">
                    <div class="schedule-info">
                        <h4>Desfragmentación</h4>
                        <p>Completada hace 2 días</p>
                    </div>
                    <div class="schedule-status completed">Completada</div>
                </div>

                <div class="schedule-item">
                    <div class="schedule-info">
                        <h4>Actualización de Drivers</h4>
                        <p>Pendiente - 3 actualizaciones disponibles</p>
                    </div>
                    <div class="schedule-status pending">Pendiente</div>
                </div>

                <div class="schedule-item">
                    <div class="schedule-info">
                        <h4>Análisis de Rendimiento</h4>
                        <p>Programado para el viernes</p>
                    </div>
                    <div class="schedule-status scheduled">Programada</div>
                </div>

                <div class="action-buttons">
                    <button class="action-btn success" onclick="scheduleOptimization()">
                        <i class="fas fa-calendar-plus"></i>
                        Programar
                    </button>
                    <button class="action-btn warning" onclick="runPredictiveAnalysis()">
                        <i class="fas fa-brain"></i>
                        Análisis IA
                    </button>
                </div>
            </div>
        </div>

        <!-- System Analysis -->
        <div class="analysis-grid">
            <div class="analysis-card">
                <div class="card-header">
                    <h2 class="card-title">📊 Uso de Recursos</h2>
                </div>
                <div class="chart-container">
                    <div class="chart-placeholder">Gráfico de uso de recursos en tiempo real</div>
                </div>
                <div class="progress-container">
                    <div class="progress-header">
                        <span>Eficiencia del Sistema</span>
                        <span>87%</span>
                    </div>
                    <div class="progress-bar">
                        <div class="progress-fill cpu" style="width: 87%"></div>
                    </div>
                </div>
            </div>

            <div class="analysis-card">
                <div class="card-header">
                    <h2 class="card-title">🔋 Aplicaciones que Consumen Batería</h2>
                </div>
                <div class="optimization-item" style="margin-bottom: 0.5rem;">
                    <div class="optimization-info">
                        <div class="optimization-icon" style="background: #ff4757; width: 40px; height: 40px;">
                            <i class="fab fa-chrome"></i>
                        </div>
                        <div class="optimization-details">
                            <h4>Google Chrome</h4>
                            <p>23% del consumo total</p>
                        </div>
                    </div>
                    <span style="color: #ff4757; font-weight: 600;">Alto</span>
                </div>

                <div class="optimization-item" style="margin-bottom: 0.5rem;">
                    <div class="optimization-info">
                        <div class="optimization-icon" style="background: #ffa502; width: 40px; height: 40px;">
                            <i class="fas fa-video"></i>
                        </div>
                        <div class="optimization-details">
                            <h4>Video Player</h4>
                            <p>18% del consumo total</p>
                        </div>
                    </div>
                    <span style="color: #ffa502; font-weight: 600;">Medio</span>
                </div>

                <div class="optimization-item" style="margin-bottom: 0.5rem;">
                    <div class="optimization-info">
                        <div class="optimization-icon" style="background: #2ed573; width: 40px; height: 40px;">
                            <i class="fas fa-code"></i>
                        </div>
                        <div class="optimization-details">
                            <h4>VS Code</h4>
                            <p>12% del consumo total</p>
                        </div>
                    </div>
                    <span style="color: #2ed573; font-weight: 600;">Bajo</span>
                </div>
            </div>

            <div class="analysis-card">
                <div class="card-header">
                    <h2 class="card-title">📁 Archivos Duplicados</h2>
                </div>
                <div class="optimization-item" style="margin-bottom: 0.5rem;">
                    <div class="optimization-info">
                        <div class="optimization-icon" style="background: var(--warning-gradient); width: 40px; height: 40px;">
                            <i class="fas fa-copy"></i>
                        </div>
                        <div class="optimization-details">
                            <h4>Documentos</h4>
                            <p>47 archivos duplicados - 234 MB</p>
                        </div>
                    </div>
                    <button class="optimization-btn" style="padding: 0.5rem 1rem; font-size: 0.8rem;" onclick="removeDuplicates('documents')">
                        Eliminar
                    </button>
                </div>

                <div class="optimization-item" style="margin-bottom: 0.5rem;">
                    <div class="optimization-info">
                        <div class="optimization-icon" style="background: var(--performance-gradient); width: 40px; height: 40px;">
                            <i class="fas fa-image"></i>
                        </div>
                        <div class="optimization-details">
                            <h4>Imágenes</h4>
                            <p>23 archivos duplicados - 156 MB</p>
                        </div>
                    </div>
                    <button class="optimization-btn" style="padding: 0.5rem 1rem; font-size: 0.8rem;" onclick="removeDuplicates('images')">
                        Eliminar
                    </button>
                </div>

                <div class="optimization-item" style="margin-bottom: 0.5rem;">
                    <div class="optimization-info">
                        <div class="optimization-icon" style="background: var(--success-gradient); width: 40px; height: 40px;">
                            <i class="fas fa-music"></i>
                        </div>
                        <div class="optimization-details">
                            <h4>Audio</h4>
                            <p>12 archivos duplicados - 89 MB</p>
                        </div>
                    </div>
                    <button class="optimization-btn" style="padding: 0.5rem 1rem; font-size: 0.8rem;" onclick="removeDuplicates('audio')">
                        Eliminar
                    </button>
                </div>
            </div>

            <div class="analysis-card">
                <div class="card-header">
                    <h2 class="card-title">💡 Sugerencias Personalizadas</h2>
                </div>
                <div class="optimization-item" style="margin-bottom: 1rem; background: rgba(79, 172, 254, 0.1);">
                    <div class="optimization-info">
                        <div class="optimization-icon" style="background: var(--performance-gradient); width: 40px; height: 40px;">
                            <i class="fas fa-lightbulb"></i>
                        </div>
                        <div class="optimization-details">
                            <h4>Optimización de Inicio</h4>
                            <p>Deshabilitar 5 programas de inicio puede mejorar el tiempo de arranque en 23%</p>
                        </div>
                    </div>
                </div>

                <div class="optimization-item" style="margin-bottom: 1rem; background: rgba(67, 233, 123, 0.1);">
                    <div class="optimization-info">
                        <div class="optimization-icon" style="background: var(--success-gradient); width: 40px; height: 40px;">
                            <i class="fas fa-leaf"></i>
                        </div>
                        <div class="optimization-details">
                            <h4>Modo Eco</h4>
                            <p>Activar modo eco puede extender la batería hasta 2.5 horas adicionales</p>
                        </div>
                    </div>
                </div>

                <div class="optimization-item" style="margin-bottom: 1rem; background: rgba(255, 165, 2, 0.1);">
                    <div class="optimization-info">
                        <div class="optimization-icon" style="background: var(--warning-gradient); width: 40px; height: 40px;">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <div class="optimization-details">
                            <h4>Actualización Recomendada</h4>
                            <p>Actualizar drivers de gráficos puede mejorar el rendimiento en 15%</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Floating Action Button -->
    <button class="fab" onclick="quickOptimization()" title="Optimización Rápida">
        <i class="fas fa-bolt"></i>
    </button>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <span id="toast-message"></span>
    </div>

    <script>
        // Variables globales
        let optimizationInterval;
        let isOptimizing = false;

        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            initializePerformance();
            startRealTimeUpdates();
            updateCircularProgress();
        });

        // Inicializar página de rendimiento
        function initializePerformance() {
            updateSystemMetrics();
            loadOptimizationHistory();
            checkMaintenanceSchedule();
        }

        // Actualizar métricas del sistema
        function updateSystemMetrics() {
            const metrics = {
                cpu: Math.floor(Math.random() * 30) + 30,
                ram: Math.floor(Math.random() * 40) + 50,
                storage: Math.floor(Math.random() * 20) + 70,
                battery: Math.floor(Math.random() * 20) + 80
            };

            // Actualizar valores de texto
            document.getElementById('cpu-usage').textContent = metrics.cpu + '%';
            document.getElementById('ram-usage').textContent = metrics.ram + '%';
            document.getElementById('storage-usage').textContent = metrics.storage + '%';
            document.getElementById('battery-time').textContent = calculateBatteryTime(metrics.battery);

            // Actualizar barras de progreso
            document.getElementById('cpu-bar').style.width = metrics.cpu + '%';
            document.getElementById('ram-bar').style.width = metrics.ram + '%';
            document.getElementById('storage-bar').style.width = metrics.storage + '%';
            document.getElementById('battery-bar').style.width = metrics.battery + '%';

            // Actualizar progreso circular
            updateCircularProgress();
        }

        // Actualizar progreso circular
        function updateCircularProgress() {
            const cpuValue = parseInt(document.getElementById('cpu-usage').textContent);
            const ramValue = parseInt(document.getElementById('ram-usage').textContent);
            const storageValue = parseInt(document.getElementById('storage-usage').textContent);
            const batteryValue = parseInt(document.getElementById('battery-time').textContent.split('h')[0]) * 10 + 50;

            updateCircle('cpu-circle', 'cpu-percentage', cpuValue);
            updateCircle('ram-circle', 'ram-percentage', ramValue);
            updateCircle('storage-circle', 'storage-percentage', storageValue);
            updateCircle('battery-circle', 'battery-percentage', Math.min(batteryValue, 100));
        }

        // Actualizar círculo individual
        function updateCircle(circleId, textId, percentage) {
            const circle = document.getElementById(circleId);
            const text = document.getElementById(textId);
            const circumference = 2 * Math.PI * 40; // radio = 40
            const offset = circumference - (percentage / 100) * circumference;

            circle.style.strokeDasharray = `${circumference} ${circumference}`;
            circle.style.strokeDashoffset = offset;
            text.textContent = percentage + '%';
        }

        // Calcular tiempo de batería
        function calculateBatteryTime(percentage) {
            const hours = Math.floor((percentage / 100) * 8);
            const minutes = Math.floor(((percentage / 100) * 8 - hours) * 60);
            return `${hours}h ${minutes}m`;
        }

        // Iniciar actualizaciones en tiempo real
        function startRealTimeUpdates() {
            setInterval(updateSystemMetrics, 5000);
            setInterval(updatePerformanceStats, 10000);
        }

        // Actualizar estadísticas de rendimiento
        function updatePerformanceStats() {
            const ramFreed = (Math.random() * 2 + 2).toFixed(1);
            const batteryOptimized = '+' + (Math.random() * 2 + 2).toFixed(1) + 'h';
            const storageCleaned = (Math.random() * 1 + 1).toFixed(1);
            const performanceScore = Math.floor(Math.random() * 10) + 90;

            document.getElementById('ram-freed').textContent = ramFreed + ' GB';
            document.getElementById('battery-optimized').textContent = batteryOptimized;
            document.getElementById('storage-cleaned').textContent = storageCleaned + ' GB';
            document.getElementById('performance-score').textContent = performanceScore;
        }

        // Optimización completa
        function runFullOptimization() {
            if (isOptimizing) {
                showToast('Optimización ya en progreso...', 'warning');
                return;
            }

            isOptimizing = true;
            showToast('Iniciando optimización completa del sistema...', 'success');

            const optimizationSteps = [
                { action: 'Analizando sistema...', duration: 2000 },
                { action: 'Limpiando archivos temporales...', duration: 3000 },
                { action: 'Optimizando memoria RAM...', duration: 2500 },
                { action: 'Configurando batería...', duration: 2000 },
                { action: 'Comprimiendo archivos...', duration: 3500 },
                { action: 'Finalizando optimización...', duration: 1500 }
            ];

            let currentStep = 0;
            
            function executeStep() {
                if (currentStep < optimizationSteps.length) {
                    const step = optimizationSteps[currentStep];
                    showToast(step.action, 'success');
                    
                    setTimeout(() => {
                        currentStep++;
                        executeStep();
                    }, step.duration);
                } else {
                    isOptimizing = false;
                    showToast('Optimización completa finalizada. Sistema mejorado en 23%', 'success');
                    updateSystemMetrics();
                }
            }

            executeStep();
        }

        // Limpiar archivos
        function cleanFiles() {
            showToast('Iniciando limpieza inteligente de archivos...', 'success');
            
            setTimeout(() => {
                const spaceCleaned = (Math.random() * 500 + 200).toFixed(0);
                showToast(`Limpieza completada. ${spaceCleaned} MB liberados`, 'success');
                updateSystemMetrics();
            }, 3000);
        }

        // Optimizar RAM
        function optimizeRAM() {
            showToast('Optimizando gestión de memoria RAM...', 'success');
            
            setTimeout(() => {
                const ramOptimized = (Math.random() * 2 + 1).toFixed(1);
                showToast(`RAM optimizada. ${ramOptimized} GB liberados`, 'success');
                updateSystemMetrics();
            }, 2500);
        }

        // Optimizar batería
        function optimizeBattery() {
            showToast('Aplicando optimización inteligente de batería...', 'success');
            
            setTimeout(() => {
                const timeGained = (Math.random() * 2 + 1).toFixed(1);
                showToast(`Batería optimizada. +${timeGained}h de duración adicional`, 'success');
                updateSystemMetrics();
            }, 2000);
        }

        // Comprimir archivos
        function compressFiles() {
            showToast('Iniciando compresión inteligente de archivos...', 'success');
            
            setTimeout(() => {
                const spaceCompressed = (Math.random() * 800 + 300).toFixed(0);
                showToast(`Compresión completada. ${spaceCompressed} MB ahorrados`, 'success');
                updateSystemMetrics();
            }, 4000);
        }

        // Programar optimización
        function scheduleOptimization() {
            showToast('Abriendo programador de mantenimiento...', 'success');
            // Aquí se abriría un modal o página de programación
        }

        // Análisis predictivo con IA
        function runPredictiveAnalysis() {
            showToast('Ejecutando análisis predictivo con IA...', 'success');
            
            setTimeout(() => {
                showToast('Análisis completado. Se detectaron 3 oportunidades de optimización', 'success');
            }, 3000);
        }

        // Eliminar duplicados
        function removeDuplicates(type) {
            const typeNames = {
                'documents': 'documentos',
                'images': 'imágenes',
                'audio': 'archivos de audio'
            };
            
            showToast(`Eliminando ${typeNames[type]} duplicados...`, 'warning');
            
            setTimeout(() => {
                const spaceFreed = Math.floor(Math.random() * 200 + 50);
                showToast(`${typeNames[type]} duplicados eliminados. ${spaceFreed} MB liberados`, 'success');
            }, 2000);
        }

        // Optimización rápida
        function quickOptimization() {
            showToast('Ejecutando optimización rápida...', 'success');
            
            setTimeout(() => {
                showToast('Optimización rápida completada. Rendimiento mejorado', 'success');
                updateSystemMetrics();
            }, 3000);
        }

        // Cargar historial de optimización
        function loadOptimizationHistory() {
            // Simular carga de historial
            console.log('Cargando historial de optimización...');
        }

        // Verificar programación de mantenimiento
        function checkMaintenanceSchedule() {
            // Simular verificación de programación
            console.log('Verificando programación de mantenimiento...');
        }

        // Mostrar notificación toast
        function showToast(message, type = 'success') {
            const toast = document.getElementById('toast');
            const toastMessage = document.getElementById('toast-message');
            
            toastMessage.textContent = message;
            toast.className = `toast ${type}`;
            toast.classList.add('show');
            
            setTimeout(() => {
                toast.classList.remove('show');
            }, 4000);
        }

        // Efectos de hover para las tarjetas
        document.querySelectorAll('.metric-card').forEach(card => {
            card.addEventListener('mouseenter', function() {
                this.style.transform = 'translateY(-5px) scale(1.02)';
            });
            
            card.addEventListener('mouseleave', function() {
                this.style.transform = 'translateY(0) scale(1)';
            });
        });

        // Efectos de hover para elementos de optimización
        document.querySelectorAll('.optimization-item').forEach(item => {
            item.addEventListener('mouseenter', function() {
                this.style.transform = 'translateX(5px) scale(1.01)';
            });
            
            item.addEventListener('mouseleave', function() {
                this.style.transform = 'translateX(0) scale(1)';
            });
        });

        // Manejo de errores
        window.addEventListener('error', function(e) {
            console.log('Error capturado:', e.message);
        });

        // Cleanup al salir
        window.addEventListener('beforeunload', function() {
            if (optimizationInterval) {
                clearInterval(optimizationInterval);
            }
        });

        // Funciones de utilidad
        function formatBytes(bytes, decimals = 2) {
            if (bytes === 0) return '0 Bytes';
            const k = 1024;
            const dm = decimals < 0 ? 0 : decimals;
            const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(dm)) + ' ' + sizes[i];
        }

        function formatTime(seconds) {
            const hours = Math.floor(seconds / 3600);
            const minutes = Math.floor((seconds % 3600) / 60);
            return `${hours}h ${minutes}m`;
        }

        // Animaciones adicionales
        function animateValue(element, start, end, duration) {
            const range = end - start;
            const increment = range / (duration / 16);
            let current = start;
            
            const timer = setInterval(() => {
                current += increment;
                if ((increment > 0 && current >= end) || (increment < 0 && current <= end)) {
                    element.textContent = end;
                    clearInterval(timer);
                } else {
                    element.textContent = Math.floor(current);
                }
            }, 16);
        }

        // Detectar cambios de rendimiento
        function detectPerformanceChanges() {
            // Simular detección de cambios de rendimiento
            const changes = [
                'Mejora en velocidad de CPU detectada',
                'Optimización de memoria completada',
                'Reducción en consumo de batería',
                'Limpieza de archivos temporales exitosa'
            ];
            
            const randomChange = changes[Math.floor(Math.random() * changes.length)];
            return randomChange;
        }

        // Inicializar animaciones de entrada
        function initializeAnimations() {
            const cards = document.querySelectorAll('.metric-card, .optimization-card, .analysis-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.5s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });
        }

        // Llamar animaciones de entrada
        setTimeout(initializeAnimations, 500);
    </script>
</body>
</html>

