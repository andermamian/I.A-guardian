<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centro de Amenazas - GuardianIA</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-gradient: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            --danger-gradient: linear-gradient(135deg, #fa709a 0%, #fee140 100%);
            --warning-gradient: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);
            --success-gradient: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            --critical-gradient: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);
            
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
                radial-gradient(circle at 20% 80%, rgba(255, 107, 107, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(238, 90, 36, 0.3) 0%, transparent 50%),
                radial-gradient(circle at 40% 40%, rgba(250, 112, 154, 0.2) 0%, transparent 50%);
            animation: threatPulse 6s ease-in-out infinite;
        }

        @keyframes threatPulse {
            0%, 100% { opacity: 0.3; }
            50% { opacity: 0.7; }
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
            background: var(--danger-gradient);
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

        /* Emergency Header */
        .emergency-header {
            background: var(--critical-gradient);
            border-radius: var(--border-radius);
            padding: 2rem;
            margin-bottom: 2rem;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .emergency-header::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            animation: emergencyShine 3s infinite;
        }

        @keyframes emergencyShine {
            0% { left: -100%; }
            100% { left: 100%; }
        }

        .emergency-title {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .alert-status {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .alert-level {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1.5rem;
            border-radius: 25px;
            font-weight: 600;
            color: white;
        }

        .panic-button {
            background: #ff4757;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
            box-shadow: 0 8px 25px rgba(255, 71, 87, 0.4);
            animation: panicPulse 2s infinite;
        }

        @keyframes panicPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        .panic-button:hover {
            transform: scale(1.1);
            box-shadow: 0 12px 35px rgba(255, 71, 87, 0.6);
        }

        /* Stats Grid */
        .threat-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .threat-stat-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            transition: all var(--animation-speed) ease;
        }

        .threat-stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px var(--shadow-color);
        }

        .threat-stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .threat-stat-card.critical::before {
            background: var(--critical-gradient);
        }

        .threat-stat-card.warning::before {
            background: var(--warning-gradient);
        }

        .threat-stat-card.success::before {
            background: var(--success-gradient);
        }

        .stat-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.3rem;
            color: white;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }

        /* Timeline */
        .timeline-section {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: var(--card-padding);
            margin-bottom: 2rem;
        }

        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
        }

        .timeline {
            position: relative;
            padding-left: 2rem;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 1rem;
            top: 0;
            bottom: 0;
            width: 2px;
            background: var(--border-color);
        }

        .timeline-item {
            position: relative;
            margin-bottom: 2rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 1.5rem;
            transition: all var(--animation-speed) ease;
        }

        .timeline-item:hover {
            background: rgba(255, 255, 255, 0.1);
            transform: translateX(10px);
        }

        .timeline-dot {
            position: absolute;
            left: -2.5rem;
            top: 1.5rem;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            border: 3px solid var(--bg-card);
        }

        .timeline-dot.critical {
            background: #ff4757;
            animation: criticalBlink 1s infinite;
        }

        .timeline-dot.warning {
            background: #ffa502;
        }

        .timeline-dot.success {
            background: #2ed573;
        }

        @keyframes criticalBlink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }

        .timeline-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .timeline-title {
            font-weight: 600;
            color: var(--text-primary);
        }

        .timeline-time {
            color: var(--text-secondary);
            font-size: 0.85rem;
        }

        .timeline-description {
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }

        .timeline-actions {
            display: flex;
            gap: 0.5rem;
        }

        .timeline-action {
            background: var(--primary-gradient);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
        }

        .timeline-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        /* Forensic Analysis */
        .forensic-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .forensic-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: var(--card-padding);
        }

        .forensic-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            margin-bottom: 1rem;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            transition: all var(--animation-speed) ease;
        }

        .forensic-item:hover {
            background: rgba(255, 255, 255, 0.1);
        }

        .forensic-label {
            font-weight: 600;
            color: var(--text-primary);
        }

        .forensic-value {
            color: var(--text-secondary);
            font-family: 'Courier New', monospace;
        }

        /* Response Configuration */
        .config-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }

        .config-card {
            background: var(--bg-card);
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: var(--card-padding);
        }

        .config-option {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid var(--border-color);
        }

        .config-option:last-child {
            border-bottom: none;
        }

        .toggle-switch {
            position: relative;
            width: 60px;
            height: 30px;
            background: var(--border-color);
            border-radius: 15px;
            cursor: pointer;
            transition: all var(--animation-speed) ease;
        }

        .toggle-switch.active {
            background: var(--primary-gradient);
        }

        .toggle-switch::before {
            content: '';
            position: absolute;
            top: 3px;
            left: 3px;
            width: 24px;
            height: 24px;
            background: white;
            border-radius: 50%;
            transition: all var(--animation-speed) ease;
        }

        .toggle-switch.active::before {
            transform: translateX(30px);
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

        .action-btn.danger {
            background: var(--critical-gradient);
        }

        .action-btn.warning {
            background: var(--warning-gradient);
        }

        .action-btn.success {
            background: var(--success-gradient);
        }

        /* Real-time Monitor */
        .monitor-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .monitor-display {
            background: #000;
            border: 1px solid var(--border-color);
            border-radius: var(--border-radius);
            padding: 1rem;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            height: 400px;
            overflow-y: auto;
            position: relative;
        }

        .monitor-line {
            margin-bottom: 0.5rem;
            animation: fadeIn 0.5s ease;
        }

        @keyframes fadeIn {
            0% { opacity: 0; transform: translateY(10px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .monitor-line.info {
            color: #4facfe;
        }

        .monitor-line.warning {
            color: #ffa502;
        }

        .monitor-line.error {
            color: #ff4757;
        }

        .monitor-line.success {
            color: #2ed573;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .forensic-grid {
                grid-template-columns: 1fr;
            }

            .monitor-grid {
                grid-template-columns: 1fr;
            }

            .threat-stats {
                grid-template-columns: 1fr;
            }

            .emergency-title {
                font-size: 2rem;
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
                <li><a href="#" class="nav-link active">Centro de Amenazas</a></li>
                <li><a href="performance.php" class="nav-link">Rendimiento</a></li>
                <li><a href="chatbot.php" class="nav-link">Asistente IA</a></li>
                <li><a href="settings.php" class="nav-link">Configuración</a></li>
            </ul>
        </div>
    </nav>

    <!-- Main Container -->
    <div class="main-container">
        <!-- Emergency Header -->
        <div class="emergency-header">
            <h1 class="emergency-title">🚨 CENTRO DE RESPUESTA RÁPIDA</h1>
            <div class="alert-status">
                <div class="alert-level" id="alert-level">NIVEL: BAJO</div>
                <button class="panic-button" onclick="activatePanicMode()">
                    <i class="fas fa-exclamation-triangle"></i>
                    EMERGENCIA
                </button>
            </div>
        </div>

        <!-- Threat Statistics -->
        <div class="threat-stats">
            <div class="threat-stat-card critical">
                <div class="stat-header">
                    <div>
                        <div class="stat-value" style="color: #ff4757;" id="critical-threats">3</div>
                        <div class="stat-label">Amenazas Críticas</div>
                    </div>
                    <div class="stat-icon" style="background: var(--critical-gradient);">
                        <i class="fas fa-skull-crossbones"></i>
                    </div>
                </div>
            </div>

            <div class="threat-stat-card warning">
                <div class="stat-header">
                    <div>
                        <div class="stat-value" style="color: #ffa502;" id="warning-threats">12</div>
                        <div class="stat-label">Advertencias</div>
                    </div>
                    <div class="stat-icon" style="background: var(--warning-gradient);">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                </div>
            </div>

            <div class="threat-stat-card success">
                <div class="stat-header">
                    <div>
                        <div class="stat-value" style="color: #2ed573;" id="blocked-threats">247</div>
                        <div class="stat-label">Amenazas Bloqueadas</div>
                    </div>
                    <div class="stat-icon" style="background: var(--success-gradient);">
                        <i class="fas fa-shield-check"></i>
                    </div>
                </div>
            </div>

            <div class="threat-stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-value" style="color: #4facfe;" id="response-time">1.2s</div>
                        <div class="stat-label">Tiempo de Respuesta</div>
                    </div>
                    <div class="stat-icon" style="background: var(--primary-gradient);">
                        <i class="fas fa-stopwatch"></i>
                    </div>
                </div>
            </div>
        </div>

        <!-- Real-time Monitor -->
        <div class="monitor-grid">
            <div class="timeline-section">
                <div class="section-header">
                    <h2 class="section-title">📊 Monitor en Tiempo Real</h2>
                    <button class="action-btn" onclick="clearMonitor()">
                        <i class="fas fa-trash"></i>
                        Limpiar
                    </button>
                </div>
                <div class="monitor-display" id="monitor-display">
                    <div class="monitor-line info">[INFO] Sistema de monitoreo iniciado</div>
                    <div class="monitor-line success">[OK] Todos los módulos funcionando correctamente</div>
                    <div class="monitor-line info">[SCAN] Escaneando puertos de red...</div>
                </div>
            </div>

            <div class="timeline-section">
                <div class="section-header">
                    <h2 class="section-title">⚡ Acciones Rápidas</h2>
                </div>
                <div class="action-buttons" style="flex-direction: column;">
                    <button class="action-btn" onclick="runThreatScan()">
                        <i class="fas fa-search"></i>
                        Escaneo de Amenazas
                    </button>
                    <button class="action-btn warning" onclick="quarantineThreats()">
                        <i class="fas fa-lock"></i>
                        Cuarentena Automática
                    </button>
                    <button class="action-btn danger" onclick="emergencyLockdown()">
                        <i class="fas fa-ban"></i>
                        Bloqueo de Emergencia
                    </button>
                    <button class="action-btn success" onclick="updateDefinitions()">
                        <i class="fas fa-download"></i>
                        Actualizar Definiciones
                    </button>
                </div>
            </div>
        </div>

        <!-- Timeline of Threats -->
        <div class="timeline-section">
            <div class="section-header">
                <h2 class="section-title">📅 Timeline de Amenazas</h2>
                <button class="action-btn" onclick="refreshTimeline()">
                    <i class="fas fa-sync"></i>
                    Actualizar
                </button>
            </div>
            <div class="timeline" id="threat-timeline">
                <div class="timeline-item">
                    <div class="timeline-dot critical"></div>
                    <div class="timeline-header">
                        <div class="timeline-title">Malware Detectado</div>
                        <div class="timeline-time">Hace 5 minutos</div>
                    </div>
                    <div class="timeline-description">
                        Trojan.Win32.Agent detectado en C:\Users\Downloads\file.exe
                    </div>
                    <div class="timeline-actions">
                        <button class="timeline-action" onclick="quarantineFile('file.exe')">Cuarentena</button>
                        <button class="timeline-action" onclick="analyzeFile('file.exe')">Analizar</button>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-dot warning"></div>
                    <div class="timeline-header">
                        <div class="timeline-title">Conexión Sospechosa</div>
                        <div class="timeline-time">Hace 12 minutos</div>
                    </div>
                    <div class="timeline-description">
                        Intento de conexión desde IP 192.168.1.100 bloqueado
                    </div>
                    <div class="timeline-actions">
                        <button class="timeline-action" onclick="blockIP('192.168.1.100')">Bloquear IP</button>
                        <button class="timeline-action" onclick="investigateIP('192.168.1.100')">Investigar</button>
                    </div>
                </div>

                <div class="timeline-item">
                    <div class="timeline-dot success"></div>
                    <div class="timeline-header">
                        <div class="timeline-title">Amenaza Neutralizada</div>
                        <div class="timeline-time">Hace 25 minutos</div>
                    </div>
                    <div class="timeline-description">
                        Phishing email bloqueado automáticamente
                    </div>
                    <div class="timeline-actions">
                        <button class="timeline-action" onclick="viewDetails('phishing-001')">Ver Detalles</button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Forensic Analysis -->
        <div class="forensic-grid">
            <div class="forensic-card">
                <div class="section-header">
                    <h2 class="section-title">🔍 Análisis Forense</h2>
                </div>
                <div class="forensic-item">
                    <div class="forensic-label">Origen del Ataque:</div>
                    <div class="forensic-value">185.220.101.42</div>
                </div>
                <div class="forensic-item">
                    <div class="forensic-label">Método de Ataque:</div>
                    <div class="forensic-value">SQL Injection</div>
                </div>
                <div class="forensic-item">
                    <div class="forensic-label">Impacto Potencial:</div>
                    <div class="forensic-value">ALTO</div>
                </div>
                <div class="forensic-item">
                    <div class="forensic-label">Tiempo de Detección:</div>
                    <div class="forensic-value">0.8 segundos</div>
                </div>
                <div class="forensic-item">
                    <div class="forensic-label">Hash MD5:</div>
                    <div class="forensic-value">a1b2c3d4e5f6...</div>
                </div>
            </div>

            <div class="forensic-card">
                <div class="section-header">
                    <h2 class="section-title">💡 Recomendaciones</h2>
                </div>
                <div class="forensic-item">
                    <div class="forensic-label">Acción Inmediata:</div>
                    <div class="forensic-value">Bloquear IP origen</div>
                </div>
                <div class="forensic-item">
                    <div class="forensic-label">Prevención:</div>
                    <div class="forensic-value">Actualizar WAF</div>
                </div>
                <div class="forensic-item">
                    <div class="forensic-label">Monitoreo:</div>
                    <div class="forensic-value">Vigilar logs 24h</div>
                </div>
                <div class="forensic-item">
                    <div class="forensic-label">Notificación:</div>
                    <div class="forensic-value">Alertar admin</div>
                </div>
            </div>
        </div>

        <!-- Response Configuration -->
        <div class="timeline-section">
            <div class="section-header">
                <h2 class="section-title">⚙️ Configuración de Respuesta</h2>
            </div>
            <div class="config-grid">
                <div class="config-card">
                    <h3 style="margin-bottom: 1rem;">Detección Automática</h3>
                    <div class="config-option">
                        <span>Escaneo en Tiempo Real</span>
                        <div class="toggle-switch active" onclick="toggleOption(this)"></div>
                    </div>
                    <div class="config-option">
                        <span>Análisis Heurístico</span>
                        <div class="toggle-switch active" onclick="toggleOption(this)"></div>
                    </div>
                    <div class="config-option">
                        <span>Detección de Comportamiento</span>
                        <div class="toggle-switch" onclick="toggleOption(this)"></div>
                    </div>
                </div>

                <div class="config-card">
                    <h3 style="margin-bottom: 1rem;">Respuesta Automática</h3>
                    <div class="config-option">
                        <span>Cuarentena Automática</span>
                        <div class="toggle-switch active" onclick="toggleOption(this)"></div>
                    </div>
                    <div class="config-option">
                        <span>Bloqueo de IP</span>
                        <div class="toggle-switch active" onclick="toggleOption(this)"></div>
                    </div>
                    <div class="config-option">
                        <span>Notificaciones Push</span>
                        <div class="toggle-switch active" onclick="toggleOption(this)"></div>
                    </div>
                </div>

                <div class="config-card">
                    <h3 style="margin-bottom: 1rem;">Niveles de Sensibilidad</h3>
                    <div class="config-option">
                        <span>Detección Crítica</span>
                        <div class="toggle-switch active" onclick="toggleOption(this)"></div>
                    </div>
                    <div class="config-option">
                        <span>Detección Media</span>
                        <div class="toggle-switch active" onclick="toggleOption(this)"></div>
                    </div>
                    <div class="config-option">
                        <span>Detección Baja</span>
                        <div class="toggle-switch" onclick="toggleOption(this)"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast" class="toast">
        <span id="toast-message"></span>
    </div>

    <script>
        // Variables globales
        let monitorInterval;
        let threatCount = 0;
        let isMonitoring = true;

        // Inicialización
        document.addEventListener('DOMContentLoaded', function() {
            initializeThreatCenter();
            startRealTimeMonitoring();
            updateThreatStats();
        });

        // Inicializar centro de amenazas
        function initializeThreatCenter() {
            loadThreatHistory();
            checkSystemStatus();
            updateAlertLevel();
        }

        // Monitoreo en tiempo real
        function startRealTimeMonitoring() {
            monitorInterval = setInterval(() => {
                if (isMonitoring) {
                    addMonitorLine();
                    simulateThreatDetection();
                }
            }, 3000);
        }

        // Agregar línea al monitor
        function addMonitorLine() {
            const monitor = document.getElementById('monitor-display');
            const lines = [
                { type: 'info', text: '[SCAN] Escaneando archivos del sistema...' },
                { type: 'info', text: '[NET] Monitoreando tráfico de red...' },
                { type: 'success', text: '[OK] Firewall funcionando correctamente' },
                { type: 'info', text: '[AI] Analizando patrones de comportamiento...' },
                { type: 'warning', text: '[WARN] Actividad sospechosa detectada' },
                { type: 'success', text: '[BLOCK] Conexión maliciosa bloqueada' }
            ];

            const randomLine = lines[Math.floor(Math.random() * lines.length)];
            const timestamp = new Date().toLocaleTimeString();
            
            const lineElement = document.createElement('div');
            lineElement.className = `monitor-line ${randomLine.type}`;
            lineElement.textContent = `[${timestamp}] ${randomLine.text}`;
            
            monitor.appendChild(lineElement);
            monitor.scrollTop = monitor.scrollHeight;

            // Mantener solo las últimas 50 líneas
            const lines_elements = monitor.querySelectorAll('.monitor-line');
            if (lines_elements.length > 50) {
                monitor.removeChild(lines_elements[0]);
            }
        }

        // Simular detección de amenazas
        function simulateThreatDetection() {
            if (Math.random() < 0.1) { // 10% de probabilidad
                const threats = [
                    'Malware detectado en archivo temporal',
                    'Intento de phishing bloqueado',
                    'Conexión sospechosa desde IP externa',
                    'Actividad de ransomware detectada',
                    'Exploit kit identificado'
                ];

                const threat = threats[Math.floor(Math.random() * threats.length)];
                addThreatToTimeline(threat);
                updateThreatStats();
            }
        }

        // Agregar amenaza al timeline
        function addThreatToTimeline(threatDescription) {
            const timeline = document.getElementById('threat-timeline');
            const threatItem = document.createElement('div');
            threatItem.className = 'timeline-item';
            
            const severity = Math.random() < 0.3 ? 'critical' : (Math.random() < 0.5 ? 'warning' : 'success');
            
            threatItem.innerHTML = `
                <div class="timeline-dot ${severity}"></div>
                <div class="timeline-header">
                    <div class="timeline-title">${threatDescription}</div>
                    <div class="timeline-time">Ahora</div>
                </div>
                <div class="timeline-description">
                    Amenaza detectada y procesada automáticamente por GuardianIA
                </div>
                <div class="timeline-actions">
                    <button class="timeline-action" onclick="investigateThreat(this)">Investigar</button>
                    <button class="timeline-action" onclick="blockThreat(this)">Bloquear</button>
                </div>
            `;
            
            timeline.insertBefore(threatItem, timeline.firstChild);
            
            // Mantener solo los últimos 10 elementos
            const items = timeline.querySelectorAll('.timeline-item');
            if (items.length > 10) {
                timeline.removeChild(items[items.length - 1]);
            }
        }

        // Actualizar estadísticas de amenazas
        function updateThreatStats() {
            const criticalThreats = Math.floor(Math.random() * 5);
            const warningThreats = Math.floor(Math.random() * 20) + 5;
            const blockedThreats = Math.floor(Math.random() * 50) + 200;
            const responseTime = (Math.random() * 2 + 0.5).toFixed(1);

            document.getElementById('critical-threats').textContent = criticalThreats;
            document.getElementById('warning-threats').textContent = warningThreats;
            document.getElementById('blocked-threats').textContent = blockedThreats;
            document.getElementById('response-time').textContent = responseTime + 's';

            updateAlertLevel(criticalThreats);
        }

        // Actualizar nivel de alerta
        function updateAlertLevel(criticalThreats = 0) {
            const alertLevel = document.getElementById('alert-level');
            
            if (criticalThreats > 3) {
                alertLevel.textContent = 'NIVEL: CRÍTICO';
                alertLevel.style.background = 'var(--critical-gradient)';
            } else if (criticalThreats > 1) {
                alertLevel.textContent = 'NIVEL: ALTO';
                alertLevel.style.background = 'var(--warning-gradient)';
            } else {
                alertLevel.textContent = 'NIVEL: BAJO';
                alertLevel.style.background = 'var(--success-gradient)';
            }
        }

        // Activar modo pánico
        function activatePanicMode() {
            showToast('🚨 MODO PÁNICO ACTIVADO - Bloqueando todas las conexiones', 'error');
            
            // Simular acciones de emergencia
            setTimeout(() => {
                addMonitorLine('error', '[EMERGENCY] Todas las conexiones bloqueadas');
                addMonitorLine('warning', '[EMERGENCY] Sistema en modo seguro');
                addMonitorLine('info', '[EMERGENCY] Notificando al administrador');
            }, 1000);
        }

        // Ejecutar escaneo de amenazas
        function runThreatScan() {
            showToast('Iniciando escaneo completo de amenazas...', 'success');
            
            const monitor = document.getElementById('monitor-display');
            const scanSteps = [
                '[SCAN] Iniciando escaneo completo...',
                '[SCAN] Verificando archivos del sistema...',
                '[SCAN] Analizando procesos activos...',
                '[SCAN] Revisando conexiones de red...',
                '[SCAN] Verificando integridad del registro...',
                '[SCAN] Escaneo completado - Sistema limpio'
            ];

            scanSteps.forEach((step, index) => {
                setTimeout(() => {
                    const lineElement = document.createElement('div');
                    lineElement.className = 'monitor-line info';
                    lineElement.textContent = `[${new Date().toLocaleTimeString()}] ${step}`;
                    monitor.appendChild(lineElement);
                    monitor.scrollTop = monitor.scrollHeight;
                }, index * 1000);
            });

            setTimeout(() => {
                showToast('Escaneo completado - No se encontraron amenazas', 'success');
            }, scanSteps.length * 1000);
        }

        // Cuarentena automática
        function quarantineThreats() {
            showToast('Activando cuarentena automática...', 'warning');
            
            setTimeout(() => {
                addMonitorLine('warning', '[QUARANTINE] 3 archivos movidos a cuarentena');
                addMonitorLine('success', '[QUARANTINE] Sistema protegido');
                showToast('Cuarentena completada - 3 amenazas aisladas', 'success');
            }, 2000);
        }

        // Bloqueo de emergencia
        function emergencyLockdown() {
            showToast('Iniciando bloqueo de emergencia...', 'error');
            
            setTimeout(() => {
                addMonitorLine('error', '[LOCKDOWN] Todas las conexiones bloqueadas');
                addMonitorLine('warning', '[LOCKDOWN] Acceso restringido activado');
                addMonitorLine('info', '[LOCKDOWN] Sistema en modo seguro');
                showToast('Bloqueo de emergencia activado', 'error');
            }, 1500);
        }

        // Actualizar definiciones
        function updateDefinitions() {
            showToast('Actualizando definiciones de amenazas...', 'success');
            
            setTimeout(() => {
                addMonitorLine('info', '[UPDATE] Descargando nuevas definiciones...');
                addMonitorLine('success', '[UPDATE] 1,247 nuevas firmas instaladas');
                addMonitorLine('success', '[UPDATE] Base de datos actualizada');
                showToast('Definiciones actualizadas exitosamente', 'success');
            }, 3000);
        }

        // Limpiar monitor
        function clearMonitor() {
            document.getElementById('monitor-display').innerHTML = '';
            addMonitorLine('info', '[SYSTEM] Monitor limpiado');
        }

        // Actualizar timeline
        function refreshTimeline() {
            showToast('Actualizando timeline de amenazas...', 'success');
            // Simular actualización
            setTimeout(() => {
                showToast('Timeline actualizado', 'success');
            }, 1000);
        }

        // Toggle de opciones
        function toggleOption(element) {
            element.classList.toggle('active');
            const isActive = element.classList.contains('active');
            const option = element.parentElement.querySelector('span').textContent;
            
            showToast(`${option} ${isActive ? 'activado' : 'desactivado'}`, 'success');
        }

        // Funciones de timeline
        function quarantineFile(filename) {
            showToast(`Archivo ${filename} movido a cuarentena`, 'warning');
        }

        function analyzeFile(filename) {
            showToast(`Analizando archivo ${filename}...`, 'success');
        }

        function blockIP(ip) {
            showToast(`IP ${ip} bloqueada permanentemente`, 'error');
        }

        function investigateIP(ip) {
            showToast(`Investigando IP ${ip}...`, 'success');
        }

        function viewDetails(id) {
            showToast(`Mostrando detalles de ${id}`, 'success');
        }

        function investigateThreat(button) {
            const threatItem = button.closest('.timeline-item');
            const threatTitle = threatItem.querySelector('.timeline-title').textContent;
            showToast(`Investigando: ${threatTitle}`, 'success');
        }

        function blockThreat(button) {
            const threatItem = button.closest('.timeline-item');
            const threatTitle = threatItem.querySelector('.timeline-title').textContent;
            showToast(`Bloqueando: ${threatTitle}`, 'warning');
        }

        // Cargar historial de amenazas
        function loadThreatHistory() {
            // Simular carga de historial
            console.log('Cargando historial de amenazas...');
        }

        // Verificar estado del sistema
        function checkSystemStatus() {
            // Simular verificación de estado
            console.log('Verificando estado del sistema...');
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

        // Agregar línea al monitor con tipo específico
        function addMonitorLine(type, text) {
            const monitor = document.getElementById('monitor-display');
            const timestamp = new Date().toLocaleTimeString();
            
            const lineElement = document.createElement('div');
            lineElement.className = `monitor-line ${type}`;
            lineElement.textContent = `[${timestamp}] ${text}`;
            
            monitor.appendChild(lineElement);
            monitor.scrollTop = monitor.scrollHeight;
        }

        // Manejo de errores
        window.addEventListener('error', function(e) {
            console.log('Error capturado:', e.message);
        });

        // Cleanup al salir
        window.addEventListener('beforeunload', function() {
            if (monitorInterval) {
                clearInterval(monitorInterval);
            }
        });
    </script>
</body>
</html>

